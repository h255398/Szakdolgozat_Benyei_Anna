<?php
session_start();
// admin e
if (!isset($_SESSION['felhasznalonev']) || $_SESSION['felhasznalonev'] !== 'admin') {
    header('Location: bejelentkezes.php');// ha nem admin
    exit();
}
if (isset($_GET['id'])) {
    $userId = $_GET['id'];// felh id
    require_once "db_connect.php"; // adatb kapcs
    $sql = "UPDATE felhasznalok SET letiltva = FALSE WHERE id = '$userId'"; // letiltas feloldasa
    if ($conn->query($sql) === TRUE) {
        header("Location: felhasznalok.php");
        exit();
    } else {
        echo "Hiba történt a feloldás során: " . $conn->error;
    }
    $conn->close();
} else {
    echo "Nem adtál meg felhasználó ID-t.";
}
?>