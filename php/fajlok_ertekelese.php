<?php
session_start();
// projekt ID ell
$projekt_id = isset($_GET['projekt_id']) ? intval($_GET['projekt_id']) : null;
if ($projekt_id === null) {
    echo "Nincs projekt kiválasztva.";
    exit();
}
// kitöltő azonosító ell
$kitolto_id = isset($_SESSION['kitolto_id_' . $projekt_id]) ? $_SESSION['kitolto_id_' . $projekt_id] : null;
if ($kitolto_id === null) {
    echo "Nincs kitöltő azonosító.";
    exit();
}
// adatb kapcsolat
require_once "db_connect.php";
// fájlok betöltése session-be, ha még nincs
if (!isset($_SESSION['files_images_' . $projekt_id]) || !isset($_SESSION['files_videos_' . $projekt_id])) {
    // képek lekérése meg rendezzük ert. szama alapjan és random 20at max megjel
    $sql_images = "SELECT * FROM fajlok WHERE projekt_id = ? AND tipus = 'kep' ORDER BY ertekelesek_szama ASC, RAND() LIMIT 20";
    $stmt = $conn->prepare($sql_images);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();
    $result_images = $stmt->get_result();
    $_SESSION['files_images_' . $projekt_id] = $result_images->fetch_all(MYSQLI_ASSOC);
    // videók lekérése és ua. mint a képeknél
    $sql_videos = "SELECT * FROM fajlok WHERE projekt_id = ? AND tipus = 'video' ORDER BY ertekelesek_szama ASC, RAND() LIMIT 20";
    $stmt = $conn->prepare($sql_videos);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();
    $result_videos = $stmt->get_result();
    $_SESSION['files_videos_' . $projekt_id] = $result_videos->fetch_all(MYSQLI_ASSOC);
}
// fájlok és teljes számuk
$files_images = $_SESSION['files_images_' . $projekt_id];
$files_videos = $_SESSION['files_videos_' . $projekt_id];
$total_files = count($files_images) + count($files_videos);
// aktuális fájl index
$current_file = isset($_GET['current_file']) ? intval($_GET['current_file']) : 1;
if ($current_file > $total_files) {
    echo "Mindent értékeltél!";
    unset($_SESSION['files_' . $projekt_id], $_SESSION['pontozasok_' . $projekt_id]);
    exit();
}
// aktuális fájl adatainak lekérése
if ($current_file <= count($files_images)) {
    $rowFajl = $files_images[$current_file - 1]; // 1-alapú index miatt -1
} else {
    $rowFajl = $files_videos[$current_file - count($files_images) - 1]; // videók
}
// pontozás mentése POST kérés esetén
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pontszam'])) {
    $pontszam = intval($_POST['pontszam']);
    $fajl_id = intval($_POST['fajl_id']);
    // ell, hogy a fájl ID helyes-e
    if ($fajl_id <= 0) {
        echo "Hibás fájl azonosító!";
        exit();
    }
    if (!isset($_SESSION['pontozasok_' . $projekt_id])) {
        $_SESSION['pontozasok_' . $projekt_id] = [];
    }
    $_SESSION['pontozasok_' . $projekt_id][] = [
        'fajl_id' => $fajl_id,
        'pontszam' => $pontszam
    ];
    // értékelések számának frissítése minden fájlnál
    $update_fajl = $conn->prepare("UPDATE fajlok SET ertekelesek_szama = ertekelesek_szama + 1 WHERE id = ?");
    $update_fajl->bind_param("i", $fajl_id);
    $update_fajl->execute();
    $update_fajl->close();
    // ha az utolsó fájlt értékeljük, mentjük az értékeléseket az adatbba
    if ($current_file == $total_files) {
        // mentjük a válaszokat az adatbba
        if (isset($_SESSION['valaszok_' . $projekt_id])) {
            $valaszok = $_SESSION['valaszok_' . $projekt_id];
            // válaszok táblába beszúrás
            $stmt = $conn->prepare("INSERT INTO kerdesekre_valasz (projekt_id, kerdesek_id, valasz, kitolto_id) VALUES (?, ?, ?, ?)");
            foreach ($valaszok as $kerdes_id => $valasz) {
                $stmt->bind_param("iisi", $projekt_id, $kerdes_id, $valasz, $kitolto_id);
                $stmt->execute();
            }
            $stmt->close();
        }
        // töröljük a session adatokat
        unset($_SESSION['valaszok_' . $projekt_id]);
        $pontozasok = $_SESSION['pontozasok_' . $projekt_id]; // pontozások kiszedése majd beszúrás
        $stmt = $conn->prepare("INSERT INTO ertekelt_fajlok (kitolto_id, fajl_id, projekt_id, pontszam) VALUES (?, ?, ?, ?)");
        foreach ($pontozasok as $pontozas) {
            // ell, hogy a pontozás valóban különböző fájlokhoz tartozik
            if ($pontozas['fajl_id'] <= 0 || !in_array($pontozas['fajl_id'], array_column($files_images, 'id')) && !in_array($pontozas['fajl_id'], array_column($files_videos, 'id'))) {
                echo "Hibás fájl azonosító a pontozás során!";
                exit();
            }
            $stmt->bind_param("iiii", $kitolto_id, $pontozas['fajl_id'], $projekt_id, $pontozas['pontszam']);
            $stmt->execute();
        }
        $stmt->close();
        // kitöltések frissítése
        $update_stmt = $conn->prepare("UPDATE projektek SET eddigi_kitoltesek = eddigi_kitoltesek + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $projekt_id);
        $update_stmt->execute();
        $update_stmt->close();
        // session törlése
        unset($_SESSION['pontozasok_' . $projekt_id], $_SESSION['files_images_' . $projekt_id], $_SESSION['files_videos_' . $projekt_id]);
    }
    // fájlok fele
    $felso_hatar = ceil($total_files / 2);
    // ha a fájlok száma legalább 10, és épp a felénél vagyunk, akkor jelenítsük meg a cuki képet
    if ($total_files >= 10 && $current_file == $felso_hatar) {
        echo '<!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <title>Félúton jársz</title>
        <link rel="stylesheet" href="../css2/ertekeles_fajlok.css?v=1.1">
        <style>
            #tovabbi-ertekeles {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                font-size: 1.2em;
                margin-top: 50px;
                text-align: center;
            }
            #tovabbi-ertekeles p {
                font-weight: bold;
                margin-bottom: 20px;
            }
            #tovabbi-ertekeles .button-container {
                display: flex;
                gap: 20px;
                justify-content: center;
            }
            #tovabbi-ertekeles .button {
                background-color: #007BFF;
                color: white;
                padding: 12px 20px;
                font-size: 1.1em;
                text-decoration: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
            }
            #tovabbi-ertekeles .button:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Köszönöm, hogy értékelésével segíti a szakdolgozatomat</h1>
        </header>
        <div id="tovabbi-ertekeles">
            <p>A fájlok felénél jársz, tarts ki!</p>
            <img src="../oldalra_kepek/thank you memes.jpg" alt="Motiváló üzenet" style="max-width: 400px; height: auto; padding: 20px; margin-bottom:20px;">
            <div class="button-container">
                <a href="fajlok_ertekelese.php?projekt_id=' . $projekt_id . '&current_file=' . ($current_file + 1) . '" class="button">Tovább a következő fájlhoz</a>
            </div>
        </div>
    </body>
    </html>';
        exit();
    }
    // kövi fájlra lépés
    header("Location: fajlok_ertekelese.php?projekt_id=$projekt_id&current_file=" . ($current_file + 1));
    if ($current_file == $total_files) {
        header("Location: topharomvalasztas.php?projekt_id=" . $projekt_id);
        exit();
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <title>Értékelés - Projektértékelő</title>
    <link rel="stylesheet" href="../css2/ertekeles_fajlok.css?v=1.4">
</head>

<body>
    <header>
        <h1>Köszönöm, hogy értékelésével segíti a szakdolgozatomat</h1>
    </header>
    <div class="container">
        <p>Kérjük, értékelje a fájlt!</p> <strong>1-es a legrosszabb értékelés, 5-ös a legjobb értékelés.</strong> <p>(Ha egy fájl rossz minőségű, torz, akkor kap 1-es értékelést, ha pedig tiszta, jó minőségű, akkor 5-ös.)</p>
        <h3><?php echo "$current_file / $total_files"; ?></h3>
        <?php
        // ell, hogy a fájl videó-e
        $file_extension = pathinfo($rowFajl['fajl_nev'], PATHINFO_EXTENSION);
        $video_extensions = ['mp4', 'webm', 'ogg']; // jó videó kiterjesztések
        if (in_array(strtolower($file_extension), $video_extensions)) {
            // ha videó, akkor <video> tag
            echo '<video width="600" controls>
                <source src="../feltoltesek/' . htmlspecialchars($rowFajl['fajl_nev']) . '" type="video/' . $file_extension . '">
                Your browser does not support the video tag.
              </video>';
        } else {
            // ha kép, akkor <img> tag
            echo '<img src="../feltoltesek/' . htmlspecialchars($rowFajl['fajl_nev']) . '" alt="Fájl kép" width="600">';
        }
        ?>
        <form method="post">
            <input type="hidden" name="fajl_id" value="<?php echo $rowFajl['id']; ?>">
            <input type="hidden" name="pontszam" id="pontszam-hidden" value="" />
            <div class="pontozas-container">
                <?php for ($i = 1; $i <= 5; $i++): ?> <!-- pontozás gombok megjelenítése 1-5 -->
                    <button type="button" class="pontozas-kor"
                        onclick="selectRating(<?php echo $i; ?>)"><?php echo $i; ?></button>
                <?php endfor; ?>
            </div>
            <button type="submit"
                id="tovabb-gomb">Tovább</button><!-- tovább gomb, csak akkor jelenik meg, ha ki van választva egy pontszám -->
        </form>
    </div>
    <script>
        function selectRating(rating) {
            document.getElementById('pontszam-hidden').value = rating; // kiválasztott pontszám értékének beállítása a rejtett input mezőben
            document.querySelectorAll('.pontozas-kor').forEach(circle => circle.classList.remove('selected')); // összes pontozás gomb állapotának visszaállítása
            document.querySelector('.pontozas-kor:nth-child(' + rating + ')').classList.add('selected'); // kiválasztott gomb kiemelése
            document.getElementById('tovabb-gomb').style.display = 'block'; // tovább gomb megjelenítése, ha pontszámot választottak
        }
    </script>
</body>

</html>