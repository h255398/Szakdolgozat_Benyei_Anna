<?php
session_start();
// felh bejel ell
if (!isset($_SESSION['felhasznalonev'])) {
    header("Location: bejelentkezes.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projektjeim - Projektértékelő</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.2">
    <link rel="stylesheet" href="../css2/projektek.css?v=1.4">
    <style>
    </style>
</head>

<body>
    <header>
        <h1>Projektértékelő</h1>
        <div class="auth-links">
            <a href="../html/kezdolap.html">Kijelentkezés</a>
        </div>
    </header>
    <nav>
        <ul>
            <li><a href="projektjeim.php">Projektjeim</a></li>
            <li><a href="ujprojekt.php">Új projekt</a></li>
        </ul>
    </nav>
    <div class="container">
        <?php
        // adatb kapcs
        require_once "db_connect.php";
        // felh id lekér
        $felhasznalonev = $_SESSION['felhasznalonev'];
        $sqlUser = "SELECT id FROM felhasznalok WHERE felhasznalonev = '$felhasznalonev'";
        $userResult = $conn->query($sqlUser);
        if ($userResult && $userResult->num_rows > 0) {
            $userId = $userResult->fetch_assoc()['id']; // felh id
        } else {
            echo "Hiba történt a felhasználó ID-jának lekérdezésekor.";
            exit();
        }
        // adott felh projekttjei és adatok hozzá
        $sql = "SELECT id, nev, leiras, fokep, eddigi_kitoltesek, kitoltesi_cel FROM projektek WHERE felhasznalok_id = '$userId'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="project-box">'; // ezt is boxba írjuk
                echo '<a href="projekt_reszletek.php?id=' . $row['id'] . '">';
                echo '<img src="../feltoltesek/' . htmlspecialchars($row['fokep']) . '" alt="' . htmlspecialchars($row['nev']) . '">';
                // projekt név
                $projectName = htmlspecialchars($row['nev']);
                // ha hosszabb mint 10 akkor ...
                if (strlen($projectName) > 10) {
                    $displayName = substr($projectName, 0, 10) . '...';  // Az első 10 karakter + "..."
                } else {
                    $displayName = $projectName;
                }
                echo '<div class="project-name" title="' . $projectName . '">
                    <a href="projekt_reszletek.php?id=' . $row['id'] . '">' . $displayName . '</a>
                </div>';
                // leírás rövidítése 50 után
                $leiras = htmlspecialchars($row['leiras']);
                if (strlen($leiras) > 50) {
                    $leiras = substr($leiras, 0, 50) . '...';
                }
                echo '<div class="project-description">' . $leiras . '</div>';
                // kitöltési szám és cél
                echo '<div class="project-kitoltesek">Kitöltések száma: ' . htmlspecialchars($row['eddigi_kitoltesek']) . '</div>';
                echo '<div class="project-target">Kitöltési cél: ' . htmlspecialchars($row['kitoltesi_cel']) . '</div>';
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<p>Nincs megjeleníthető projekt.</p>';
        }
        $conn->close();
        ?>
    </div>
</body>

</html>