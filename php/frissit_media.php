<?php
// adatb kapcs
require_once "db_connect.php";
// projekt id
$projekt_id = isset($_GET['id']) ? $_GET['id'] : null;
if ($projekt_id === null) {
    die("Hiba: Nincs megadva projekt ID!");
}
// média lekérdezése
$query = "SELECT * FROM fajlok WHERE projekt_id = ? ORDER BY id ASC LIMIT 5"; // első 5 fájl lekérdezése
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $projekt_id);
$stmt->execute();
$resultMedia = $stmt->get_result();
?>
<div class="media-preview">
    <?php
    $mediaCount = 0; // számláló a médiafájlok számára
    while ($media = $resultMedia->fetch_assoc()):
        if ($mediaCount < 5): // csak az első 5 fájl megjelenítése
            ?>
            <div class="media-item">
                <?php
                $fileName = $media['fajl_nev'];
                $filePath = '../feltoltesek/' . htmlspecialchars($fileName);
                // képek, videók kezelése
                if (strpos($fileName, '.jpg') !== false || strpos($fileName, '.png') !== false) {
                    echo '<img src="' . $filePath . '" alt="' . htmlspecialchars($fileName) . '">';
                } elseif (strpos($fileName, '.mp4') !== false || strpos($fileName, '.webm') !== false) {
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    echo '<video controls>
                            <source src="' . $filePath . '" type="video/' . $ext . '">
                            Your browser does not support the video tag.
                        </video>';
                } else {
                    echo '<p>' . htmlspecialchars($fileName) . '</p>'; //más
                }
                ?>
            </div>
            <?php
        endif;
        $mediaCount++;
    endwhile;
    ?>
</div>
<?php
$stmt->close();
$conn->close();
?>