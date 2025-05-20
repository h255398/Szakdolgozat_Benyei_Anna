<?php
require '../vendor/autoload.php'; // autoload composer csomag hogy minden fájl be legyen töltve
use PhpOffice\PhpSpreadsheet\Spreadsheet; // excel miatt
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; // ezzel irom ki excelbe és mentem
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing; // ez miatt tudok képeket rakni bele
// adatbhez kapcsolódás
require_once "db_connect.php";
// projekt id
$projektId = $_GET['id'];
// új Spreadsheet létrehozása ez egyébként az excelek létrehozásához kll
$spreadsheet = new Spreadsheet();
// 1. projektek adatai
$sqlProject = "SELECT * FROM projektek WHERE id = '$projektId'";
$projectResult = $conn->query($sqlProject);
$projectData = $projectResult->fetch_assoc();
$projectSheet = $spreadsheet->getActiveSheet();
$projectSheet->setTitle('projektek');
// fejlécek beállítása
$projectSheet->setCellValue('A1', 'ID');
$projectSheet->setCellValue('B1', 'Név');
$projectSheet->setCellValue('C1', 'Fő kép');
$projectSheet->setCellValue('D1', 'Leírás');
$projectSheet->setCellValue('E1', 'Eddigi kitöltések');
$projectSheet->setCellValue('F1', 'Kitöltési cél');
// projekt adatok kiírása
$projectSheet->setCellValue('A2', $projectData['id']);
$projectSheet->setCellValue('B2', $projectData['nev']);
$projectSheet->setCellValue('C2', $projectData['fokep']);
$projectSheet->setCellValue('D2', $projectData['leiras']);
$projectSheet->setCellValue('E2', $projectData['eddigi_kitoltesek']);
$projectSheet->setCellValue('F2', $projectData['kitoltesi_cel']);
// fejlécek szűrő beállítása
$projectSheet->setAutoFilter('A1:F1');
// 2. fájlok adatai
$sqlFiles = "SELECT * FROM fajlok WHERE projekt_id = '$projektId'";
$filesResult = $conn->query($sqlFiles);
$filesSheet = $spreadsheet->createSheet(1);
$filesSheet->setTitle('fajlok');
// fejlécek beállítása
$filesSheet->setCellValue('A1', 'ID');
$filesSheet->setCellValue('B1', 'Fájl név');
$filesSheet->setCellValue('C1', 'Típus');
$filesSheet->setCellValue('D1', 'Kép');
$filesSheet->setCellValue('E1', 'Hiperhivatkozás');  // új oszlop a linkhez
// fájlok adatai kiírása
$row = 2;
while ($fileData = $filesResult->fetch_assoc()) {
    $filesSheet->setCellValue('A' . $row, $fileData['id']);
    $filesSheet->setCellValue('B' . $row, $fileData['fajl_nev']);
    $filesSheet->setCellValue('C' . $row, $fileData['tipus']);
    // fájl elérési útja
    $filePath = "../feltoltesek/" . $fileData['fajl_nev'];  // fájl elérési útja
    $fileExtension = pathinfo($fileData['fajl_nev'], PATHINFO_EXTENSION);  // fájl kiterjesztése
    $fileUrl = "../feltoltesek/" . $fileData['fajl_nev']; // alapértelmezett fájl URL
    if (file_exists($filePath)) {
        // ha a fájl képfájl (pl. jpg, png, gif), akkor képet beill
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $drawing = new Drawing();
            $drawing->setName('Kép');
            $drawing->setDescription('Kép a fájlhoz');
            $drawing->setPath($filePath);
            $drawing->setHeight(50);  // kép magasságának beállítása (kicsi)
            $drawing->setCoordinates('D' . $row); // kép pozíciója
            $drawing->setWorksheet($filesSheet);
        } else {
            // ha nem képfájl, akkor hivatkozást adunk hozzá
            $filesSheet->setCellValue('D' . $row, 'Fájl link');
            $filesSheet->getCell('D' . $row)->getHyperlink()->setUrl($fileUrl);  // fájl URL-je
        }
        // link az új oszlopba
        $filesSheet->setCellValue('E' . $row, 'Megnyitás');
        $filesSheet->getCell('E' . $row)->getHyperlink()->setUrl($fileUrl); // fájl URL-je
    } else {
        // ha a fájl nem található, akkor hibaüzenet
        $filesSheet->setCellValue('D' . $row, 'Nincs elérhető fájl');
        $filesSheet->setCellValue('E' . $row, 'Nincs elérhető fájl');  // ha nincs fájl
    }
    $row++;
}
// fejlécek szűrő beállítása
$filesSheet->setAutoFilter('A1:E1');
// 3. értékelések adatai
$sqlRatings = "SELECT * FROM ertekelt_fajlok WHERE projekt_id = '$projektId'";
$ratingsResult = $conn->query($sqlRatings);
$ratingsSheet = $spreadsheet->createSheet(2);
$ratingsSheet->setTitle('ertekelt_fajlok');
// fejlécek beállítása
$ratingsSheet->setCellValue('A1', 'ID');
$ratingsSheet->setCellValue('B1', 'Kitöltő ID');
$ratingsSheet->setCellValue('C1', 'Fájl ID');
$ratingsSheet->setCellValue('D1', 'Pontszám');
// értékelések adatai kiírása
$row = 2;
while ($ratingData = $ratingsResult->fetch_assoc()) {
    $ratingsSheet->setCellValue('A' . $row, $ratingData['id']);
    $ratingsSheet->setCellValue('B' . $row, $ratingData['kitolto_id']);
    $ratingsSheet->setCellValue('C' . $row, $ratingData['fajl_id']);
    $ratingsSheet->setCellValue('D' . $row, $ratingData['pontszam']);
    $row++;
}
// fejlécek szűrő beállítása
$ratingsSheet->setAutoFilter('A1:D1');
// 4. felhasználók adatai
$sqlUsers = "SELECT * FROM felhasznalok WHERE id IN (SELECT felhasznalok_id FROM projektek WHERE id = '$projektId')";
$usersResult = $conn->query($sqlUsers);
$usersSheet = $spreadsheet->createSheet(3);
$usersSheet->setTitle('felhasznalok');
// fejlécek beállítása
$usersSheet->setCellValue('A1', 'ID');
$usersSheet->setCellValue('B1', 'Felhasználónév');
$usersSheet->setCellValue('C1', 'Email');
$usersSheet->setCellValue('D1', 'Regisztráció Dátum');
// felhasználók adatai kiírása
$row = 2;
while ($userData = $usersResult->fetch_assoc()) {
    $usersSheet->setCellValue('A' . $row, $userData['id']);
    $usersSheet->setCellValue('B' . $row, $userData['felhasznalonev']);
    $usersSheet->setCellValue('C' . $row, $userData['email']);
    $usersSheet->setCellValue('D' . $row, $userData['regisztracio_datum']);
    $row++;
}
// fejlécek szűrő beállítása
$usersSheet->setAutoFilter('A1:D1');
// 5. kérdések adatai
$sqlQuestions = "SELECT * FROM kerdesek WHERE projekt_id = '$projektId'";
$questionsResult = $conn->query($sqlQuestions);
$questionsSheet = $spreadsheet->createSheet(4);
$questionsSheet->setTitle('kerdesek');
// fejlécek beállítása
$questionsSheet->setCellValue('A1', 'ID');
$questionsSheet->setCellValue('B1', 'Kérdés');
$questionsSheet->setCellValue('C1', 'Válasz Típus');
$questionsSheet->setCellValue('D1', 'Lehetséges Válaszok');
// kérdések adatai kiírása
$row = 2;
while ($questionData = $questionsResult->fetch_assoc()) {
    $questionsSheet->setCellValue('A' . $row, $questionData['id']);
    $questionsSheet->setCellValue('B' . $row, $questionData['kerdes']);
    $questionsSheet->setCellValue('C' . $row, $questionData['valasz_tipus']);
    $questionsSheet->setCellValue('D' . $row, $questionData['lehetseges_valaszok']);
    $row++;
}
// fejlécek szűrő beállítása
$questionsSheet->setAutoFilter('A1:D1');
// 6. kérdésekre adott válaszok adatai
$sqlAnswers = "SELECT * FROM kerdesekre_valasz WHERE projekt_id = '$projektId'";
$answersResult = $conn->query($sqlAnswers);
$answersSheet = $spreadsheet->createSheet(5);
$answersSheet->setTitle('kerdesekre_valasz');
// fejlécek beállítása
$answersSheet->setCellValue('A1', 'ID');
$answersSheet->setCellValue('B1', 'Kérdés ID');
$answersSheet->setCellValue('C1', 'Válasz');
$answersSheet->setCellValue('D1', 'Kitöltő ID');
// válaszok adatai kiírása
$row = 2;
while ($answerData = $answersResult->fetch_assoc()) {
    $answersSheet->setCellValue('A' . $row, $answerData['id']);
    $answersSheet->setCellValue('B' . $row, $answerData['kerdesek_id']);
    $answersSheet->setCellValue('C' . $row, $answerData['valasz']);
    $answersSheet->setCellValue('D' . $row, $answerData['kitolto_id']);
    $row++;
}
// fejlécek szűrő beállítása
$answersSheet->setAutoFilter('A1:D1');
// 7. kitöltők adatai
$sqlFillers = "SELECT * FROM kitoltok WHERE projekt_id = '$projektId'";
$fillersResult = $conn->query($sqlFillers);
$fillersSheet = $spreadsheet->createSheet(6);
$fillersSheet->setTitle('kitoltok');
// fejlécek beállítása
$fillersSheet->setCellValue('A1', 'ID');
$fillersSheet->setCellValue('B1', 'Projekt ID');
// kitöltők adatai kiírása
$row = 2;
while ($fillerData = $fillersResult->fetch_assoc()) {
    $fillersSheet->setCellValue('A' . $row, $fillerData['id']);
    $fillersSheet->setCellValue('B' . $row, $fillerData['projekt_id']);
    $row++;
}
// fejlécek szűrő beállítása
$fillersSheet->setAutoFilter('A1:B1');
// fájl ideiglenes mentése
$tempFile = tempnam(sys_get_temp_dir(), 'projekt_adatok_') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempFile);
// fájl méretének ellenőrzése
$fileSize = filesize($tempFile);
// ha a fájl túl nagy, zipeljük be
if ($fileSize > 50 * 1024 * 1024) { // 50 MBnél ha nagyobb akkor zip
    $zip = new ZipArchive();
    $zipFile = tempnam(sys_get_temp_dir(), 'projekt_adatok_') . '.zip';
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($tempFile, 'projekt_adatok.xlsx');
        $zip->close();
        // ZIP fájl letöltése
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="projekt_adatok_' . date('Y-m-d_H-i') . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        // törlés
        unlink($zipFile);
    }
} else {
    // excel fájl letöltése
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="projekt_adatok_' . date('Y-m-d_H-i') . '.xlsx"');
    header('Content-Length: ' . $fileSize);
    readfile($tempFile);
}
// törlés
unlink($tempFile);
$conn->close();
exit;
?>