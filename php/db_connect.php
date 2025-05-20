<?php
$servername = "imageeval.mysql.database.azure.com";
$username = "annaAdmin";
$password = "FvhrFnjHzgF32!";
$dbname = "szakdoga2";
// kapcsolódás az adatbázishoz
$conn = new mysqli($servername, $username, $password, $dbname);
// ellenőrizzük, hogy a kapcsolat sikerült-e
if ($conn->connect_error) {
    die("Kapcsolódás hiba: " . $conn->connect_error);
}
?>