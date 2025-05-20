<?php
session_start();
// adatb kapcs
require_once "db_connect.php";
// projekt ID
$projekt_id = isset($_GET['projekt_id']) ? intval($_GET['projekt_id']) : null;
if ($projekt_id === null) {
    echo "Nincs projekt kiválasztva.";
    exit();
}
//fajlok tipusa lekérése
$check_files_stmt = $conn->prepare("
    SELECT DISTINCT tipus
    FROM fajlok
    WHERE projekt_id = ?
");
$check_files_stmt->bind_param("i", $projekt_id);
$check_files_stmt->execute();
$file_types_result = $check_files_stmt->get_result();
$file_types = $file_types_result->fetch_all(MYSQLI_ASSOC);
$check_files_stmt->close();
// ha csak képek
if (in_array(['tipus' => 'kep'], $file_types)) {
    // képek lekérdezése átlag pontszám és csak 3at jelenít meg
    $top_images_stmt = $conn->prepare("
        SELECT fajl_nev, ROUND(AVG(pontszam), 2) as atlag_pontszam
        FROM ertekelt_fajlok
        INNER JOIN fajlok ON ertekelt_fajlok.fajl_id = fajlok.id
        WHERE fajlok.tipus = 'kep' AND fajlok.projekt_id = ?
        GROUP BY fajlok.id
        ORDER BY atlag_pontszam DESC
        LIMIT 3
    ");
    $top_images_stmt->bind_param("i", $projekt_id);
    $top_images_stmt->execute();
    $top_images_result = $top_images_stmt->get_result();
    $top_images = $top_images_result->fetch_all(MYSQLI_ASSOC);
    $top_images_stmt->close();
    // képek megjelenítése
    $media_section = '<h2>Top Képek</h2>';
    foreach ($top_images as $image) {
        $media_section .= '
            <div>
                <img src="../feltoltesek/' . htmlspecialchars($image['fajl_nev']) . '" alt="Top Kép">
                <p>Átlag pontszám: ' . $image['atlag_pontszam'] . '</p>
            </div>';
    }
}
// ha csak videók
elseif (in_array(['tipus' => 'video'], $file_types)) {
    // videók lekérdezése átlag pnt és csak 3at
    $top_videos_stmt = $conn->prepare("
        SELECT fajl_nev, ROUND(AVG(pontszam), 2) as atlag_pontszam
        FROM ertekelt_fajlok
        INNER JOIN fajlok ON ertekelt_fajlok.fajl_id = fajlok.id
        WHERE fajlok.tipus = 'video' AND fajlok.projekt_id = ?
        GROUP BY fajlok.id
        ORDER BY atlag_pontszam DESC
        LIMIT 3
    ");
    $top_videos_stmt->bind_param("i", $projekt_id);
    $top_videos_stmt->execute();
    $top_videos_result = $top_videos_stmt->get_result();
    $top_videos = $top_videos_result->fetch_all(MYSQLI_ASSOC);
    $top_videos_stmt->close();
    // videók megjelenítése
    $media_section = '<h2>Top Videók</h2>';
    foreach ($top_videos as $video) {
        $media_section .= '
            <div>
                <video width="200" controls>
                    <source src="../feltoltesek/' . htmlspecialchars($video['fajl_nev']) . '" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <p>Átlag pontszám: ' . $video['atlag_pontszam'] . '</p>
            </div>';
    }
} else {
    echo "Nincs képek vagy videók az adott projektben.";
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <title>Top 3 Legjobb Média</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 50px;
            background-image: url('../oldalra_kepek/hatterkep.jfif');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .top-media {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .top-media div {
            text-align: center;
        }

        .top-media img,
        .top-media video {
            width: 200px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .button-container {
            margin-top: 20px;
        }

        .button {
            padding: 10px 20px;
            font-size: 16px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <h1>Top 3 Legjobbra Értékelt Média</h1>
    <div class="top-media">
        <?php echo $media_section; ?>
    </div>
    <div class="button-container">
        <a href="../html/kezdolap.html" class="button">Vissza a Kezdőlapra</a>
    </div>
</body>

</html>