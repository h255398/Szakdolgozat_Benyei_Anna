<?php
session_start();
// ell, hogy az admin be van-e jelentkezve
if (!isset($_SESSION['felhasznalonev']) || $_SESSION['felhasznalonev'] !== 'admin') {
    // ha nem admin
    header('Location: bejelentkezes.php');
    exit();
}
// adatb kapcs
require_once "db_connect.php";
// felhasználók lekérése kivéve admin
$sql = "SELECT id, felhasznalonev, email, letiltva FROM felhasznalok WHERE felhasznalonev != 'admin'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.1">
    <link rel="stylesheet" href="../css2/felhasznalok.css?v=1.1">
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
            <li><a href="osszesprojekt.php">Összes projekt</a></li>
            <li><a href="felhasznalok.php">Felhasználók</a></li>
        </ul>
    </nav>
    <div class="container">
        <h2>Felhasználók</h2>
        <table>
            <thead>
                <tr>
                    <th>Felhasználónév</th>
                    <th>Email cím</th>
                    <th>Letiltás</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ha van felhasználó akkor kiírni
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // felhasználó adatok
                        $username = htmlspecialchars($row['felhasznalonev']);
                        $email = htmlspecialchars($row['email']);
                        $userId = $row['id'];
                        $isDeactivated = $row['letiltva'];
                        // letilt felold gomb
                        $actionUrl = $isDeactivated ? "feloldas.php?id=$userId" : "deactivate_user.php?id=$userId";
                        $buttonClass = $isDeactivated ? "activate" : "deactivate";
                        $buttonText = $isDeactivated ? "Feloldás" : "Letiltás";
                        echo "<tr>
                            <td>$username</td>
                            <td>$email</td>
                            <td><a href='$actionUrl' class='action-button $buttonClass'>$buttonText</a></td>
                          </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>Nincs felhasználó az adatbázisban.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>