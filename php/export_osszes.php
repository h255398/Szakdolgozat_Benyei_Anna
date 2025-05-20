<?php
require '../vendor/autoload.php'; // autoload composer csomag
use PhpOffice\PhpSpreadsheet\Spreadsheet; //ua mint elozo php
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
// Kadatb kapcs
require_once "db_connect.php";
// ez az excel miatt kell
$spreadsheet = new Spreadsheet();
// 1. felhasználók adatai
$sqlUsers = "SELECT * FROM felhasznalok";
$usersResult = $conn->query($sqlUsers);
$usersSheet = $spreadsheet->getActiveSheet();
$usersSheet->setTitle('felhasznalok');
// fejlécek beállítása
$usersSheet->setCellValue('A1', 'ID');
$usersSheet->setCellValue('B1', 'Felhasználónév');
$usersSheet->setCellValue('C1', 'Email');
$usersSheet->setCellValue('D1', 'Regisztráció Dátum');
$usersSheet->setCellValue('E1', 'Admin');
// felhasználók adatai kiírása
$row = 2;
while ($userData = $usersResult->fetch_assoc()) {
    $usersSheet->setCellValue('A' . $row, $userData['id']);
    $usersSheet->setCellValue('B' . $row, $userData['felhasznalonev']);
    $usersSheet->setCellValue('C' . $row, $userData['email']);
    $usersSheet->setCellValue('D' . $row, $userData['regisztracio_datum']);
    $usersSheet->setCellValue('E' . $row, $userData['admin']);
    $row++;
}
// fejlécek szűrő beállítása
$usersSheet->setAutoFilter('A1:E1');
// 3. projektek adatai
$sqlProjects = "SELECT * FROM projektek";
$projectsResult = $conn->query($sqlProjects);
$projectsSheet = $spreadsheet->createSheet(2);
$projectsSheet->setTitle('projektek');
// fejlécek beállítása
$projectsSheet->setCellValue('A1', 'ID');
$projectsSheet->setCellValue('B1', 'Név');
$projectsSheet->setCellValue('C1', 'Fő kép');
$projectsSheet->setCellValue('D1', 'Leírás');
$projectsSheet->setCellValue('E1', 'Felhasználók ID');
$projectsSheet->setCellValue('F1', 'Eddigi kitöltések');
$projectsSheet->setCellValue('G1', 'Kitöltési cél');
// projektek adatai kiírása
$row = 2;
while ($projectData = $projectsResult->fetch_assoc()) {
    $projectsSheet->setCellValue('A' . $row, $projectData['id']);
    $projectsSheet->setCellValue('B' . $row, $projectData['nev']);
    $projectsSheet->setCellValue('C' . $row, $projectData['fokep']);
    $projectsSheet->setCellValue('D' . $row, $projectData['leiras']);
    $projectsSheet->setCellValue('E' . $row, $projectData['felhasznalok_id']);
    $projectsSheet->setCellValue('F' . $row, $projectData['eddigi_kitoltesek']);
    $projectsSheet->setCellValue('G' . $row, $projectData['kitoltesi_cel']);
    $row++;
}
// fejlécek szűrő beállítása
$projectsSheet->setAutoFilter('A1:G1');
// 4. fájlok adatai
$sqlFiles = "SELECT * FROM fajlok";
$filesResult = $conn->query($sqlFiles);
$filesSheet = $spreadsheet->createSheet(3);
$filesSheet->setTitle('fajlok');
// fejlécek beállítása
$filesSheet->setCellValue('A1', 'ID');
$filesSheet->setCellValue('B1', 'Fájl név');
$filesSheet->setCellValue('C1', 'Típus');
$filesSheet->setCellValue('D1', 'Kép');
$filesSheet->setCellValue('E1', 'Hiperhivatkozás');  // új oszlop a link
// fájlok adatai kiírása
$row = 2;
while ($fileData = $filesResult->fetch_assoc()) {
    $filesSheet->setCellValue('A' . $row, $fileData['id']);
    $filesSheet->setCellValue('B' . $row, $fileData['fajl_nev']);
    $filesSheet->setCellValue('C' . $row, $fileData['tipus']);
    // fájl elérési útjának lekérése
    $filePath = "../feltoltesek/" . $fileData['fajl_nev'];  // fájl elérési útja
    $fileExtension = pathinfo($fileData['fajl_nev'], PATHINFO_EXTENSION);  // fájl kiterjesztése
    $fileUrl = "../feltoltesek/" . $fileData['fajl_nev']; // alapértelmezett fájl URL
    if (file_exists($filePath)) {
        // ha a fájl képfájl (pl. jpg, png), akkor kép hozzáadása
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $drawing = new Drawing();
            $drawing->setName('Kép');
            $drawing->setDescription('Kép a fájlhoz');
            $drawing->setPath($filePath);
            $drawing->setHeight(50);  // kép magasságának beállítása (kicsi)
            $drawing->setCoordinates('D' . $row); // kép pozíciója
            $drawing->setWorksheet($filesSheet);
        } else {
            // ha nem kép, akkor hivatkozást adunk hozzá (pl. videó)
            // $fileUrl már defva van, ha a fájl létezik
            $filesSheet->setCellValue('D' . $row, 'Fájl link');
            $filesSheet->getCell('D' . $row)->getHyperlink()->setUrl($fileUrl);  // fájl URL-je
        }
        // link az új oszlopba
        $filesSheet->setCellValue('E' . $row, 'Megnyitás');
        $filesSheet->getCell('E' . $row)->getHyperlink()->setUrl($fileUrl); // fájl URL-je
    } else {
        // ha nem található a fájl
        $filesSheet->setCellValue('D' . $row, 'Nincs elérhető fájl');
        $filesSheet->setCellValue('E' . $row, 'Nincs elérhető fájl');  // ha nincs fájl, ezt írja ki
    }
    $row++;
}
// fejlécek szűrő beállítása
$filesSheet->setAutoFilter('A1:E1');
// 5. értékelések adatai
$sqlRatings = "SELECT * FROM ertekelt_fajlok";
$ratingsResult = $conn->query($sqlRatings);
$ratingsSheet = $spreadsheet->createSheet(4);
$ratingsSheet->setTitle('ertekelt_fajlok');
// fejlécek beállítása
$ratingsSheet->setCellValue('A1', 'ID');
$ratingsSheet->setCellValue('B1', 'Kitöltő ID');
$ratingsSheet->setCellValue('C1', 'Fájl ID');
$ratingsSheet->setCellValue('D1', 'Projekt ID');
$ratingsSheet->setCellValue('E1', 'Pontszám');
// értékelések adatai kiírása
$row = 2;
while ($ratingData = $ratingsResult->fetch_assoc()) {
    $ratingsSheet->setCellValue('A' . $row, $ratingData['id']);
    $ratingsSheet->setCellValue('B' . $row, $ratingData['kitolto_id']);
    $ratingsSheet->setCellValue('C' . $row, $ratingData['fajl_id']);
    $ratingsSheet->setCellValue('D' . $row, $ratingData['projekt_id']);
    $ratingsSheet->setCellValue('E' . $row, $ratingData['pontszam']);
    $row++;
}
// fejlécek szűrő beállítása
$ratingsSheet->setAutoFilter('A1:E1');
// 6. kérdések adatai
$sqlQuestions = "SELECT * FROM kerdesek";
$questionsResult = $conn->query($sqlQuestions);
$questionsSheet = $spreadsheet->createSheet(5);
$questionsSheet->setTitle('kerdesek');
// fejlécek beállítása
$questionsSheet->setCellValue('A1', 'ID');
$questionsSheet->setCellValue('B1', 'Projekt ID');
$questionsSheet->setCellValue('C1', 'Kérdés');
$questionsSheet->setCellValue('D1', 'Válasz Típus');
$questionsSheet->setCellValue('E1', 'Lehetséges válaszok');
// kérdések adatai kiírása
$row = 2;
while ($questionData = $questionsResult->fetch_assoc()) {
    $questionsSheet->setCellValue('A' . $row, $questionData['id']);
    $questionsSheet->setCellValue('B' . $row, $questionData['projekt_id']);
    $questionsSheet->setCellValue('C' . $row, $questionData['kerdes']);
    $questionsSheet->setCellValue('D' . $row, $questionData['valasz_tipus']);
    $questionsSheet->setCellValue('E' . $row, $questionData['lehetseges_valaszok']);
    $row++;
}
// fejlécek szűrő beállítása
$questionsSheet->setAutoFilter('A1:E1');
// 7. kérdésekre adott válaszok
$sqlAnswers = "SELECT * FROM kerdesekre_valasz";
$answersResult = $conn->query($sqlAnswers);
$answersSheet = $spreadsheet->createSheet(6);
$answersSheet->setTitle('kerdesekre_valasz');
//fFejlécek beállítása
$answersSheet->setCellValue('A1', 'ID');
$answersSheet->setCellValue('B1', 'Projekt ID');
$answersSheet->setCellValue('C1', 'Kérdés ID');
$answersSheet->setCellValue('D1', 'Válasz');
$answersSheet->setCellValue('E1', 'Kitöltő ID');
// kérdésekre adott válaszok adatai kiírása
$row = 2;
while ($answerData = $answersResult->fetch_assoc()) {
    $answersSheet->setCellValue('A' . $row, $answerData['id']);
    $answersSheet->setCellValue('B' . $row, $answerData['projekt_id']);
    $answersSheet->setCellValue('C' . $row, $answerData['kerdesek_id']);
    $answersSheet->setCellValue('D' . $row, $answerData['valasz']);
    $answersSheet->setCellValue('E' . $row, $answerData['kitolto_id']);
    $row++;
}
// Fófejlécek szűrő beállítása
$answersSheet->setAutoFilter('A1:E1');
// excel fájl mentése ideiglenes fájlba
$tempDir = sys_get_temp_dir();  // jó ideiglenes könyvtár használata Windows rendszeren
$excelFilePath = $tempDir . '/adatbazis_export_' . date('Y-m-d_H-i') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($excelFilePath);
// ellenőrizzük a fájl méretét
$fileSize = filesize($excelFilePath);
$maxFileSize = 50 * 1024 * 1024; // 50 MB
if ($fileSize > $maxFileSize) {
    // ha nagyobb mint 50 MB, létrehozzuk a ZIP fájlt
    $zip = new ZipArchive();
    $zipFilePath = $tempDir . '/adatbazis_export_' . date('Y-m-d_H-i') . '.zip';
    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
        // excel fájl hozzáadása a ZIP-hez
        $zip->addFile($excelFilePath, basename($excelFilePath));
        $zip->close();
    }
    // ZIP fájl letöltése
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
    header('Cache-Control: max-age=0');
    // A ZIP fájl küldése
    readfile($zipFilePath);
    // ideiglenes fájlok törlése
    unlink($excelFilePath);
    unlink($zipFilePath);
} else {
    // ha nem túl nagy a fájl, közvetlenül letöltjük az excel fájlt
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($excelFilePath) . '"');
    header('Cache-Control: max-age=0');
    // excel fájl küldése
    readfile($excelFilePath);
    // ideiglenes fájl törlése
    unlink($excelFilePath);
}
$conn->close();
exit;
?>