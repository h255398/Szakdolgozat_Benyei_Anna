<?php
session_start();
// ell felh bejel
if (!isset($_SESSION['felhasznalonev'])) {
    header("Location: bejelentkezes.php");
    exit();
}
// adatb kapcs
require_once "db_connect.php";
// projekt id
$projektId = $_GET['id'];
// először az ertekelt fajlokból töröljük
$sqlDeleteHivatkozott = "DELETE FROM ertekelt_fajlok WHERE projekt_id = ?";
$stmtDeleteHivatkozott = $conn->prepare($sqlDeleteHivatkozott);
$stmtDeleteHivatkozott->bind_param("i", $projektId);
$stmtDeleteHivatkozott->execute();
// fajlokból töröljük
$sqlDeleteFajlok = "DELETE FROM fajlok WHERE projekt_id = ?";
$stmtDeleteFajlok = $conn->prepare($sqlDeleteFajlok);
$stmtDeleteFajlok->bind_param("i", $projektId);
$stmtDeleteFajlok->execute();
// projektek táblából töröljük
$sqlDeleteProjekt = "DELETE FROM projektek WHERE id = ?";
$stmtDeleteProjekt = $conn->prepare($sqlDeleteProjekt);
$stmtDeleteProjekt->bind_param("i", $projektId);
if ($stmtDeleteProjekt->execute()) {
    header("Location: projektjeim.php");
} else {
    echo "Hiba a törlés során: " . $conn->error;
}
$conn->close();
?>