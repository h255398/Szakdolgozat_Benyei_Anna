<?php
session_start();
// ell felh bejel
if (!isset($_SESSION['felhasznalonev'])) {
    header("Location: bejelentkezes.php");
    exit();
}
// adatb kapcs
require_once "db_connect.php";
// projekt ID
$projektId = $_GET['id'];
// projekt adatainak lekérdezése
$sqlProject = "SELECT * FROM projektek WHERE id = '$projektId'";
$projectResult = $conn->query($sqlProject);
$project = $projectResult->fetch_assoc();
// médiafájlok lekérdezése
$sqlMedia = "SELECT * FROM fajlok WHERE projekt_id = '$projektId'";
$mediaResult = $conn->query($sqlMedia);
// kérdések lekérdezése
$sqlQuestions = "SELECT * FROM kerdesek WHERE projekt_id = '$projektId'";
$questionsResult = $conn->query($sqlQuestions);
// már meglévő kérdések lekérdezése összes
$letezoKerdesek = [];
$kerdesQuery = "SELECT DISTINCT kerdes FROM kerdesek WHERE kerdes NOT IN (SELECT kerdes FROM kerdesek WHERE projekt_id = '$projektId')";
$kerdesEredmeny = $conn->query($kerdesQuery);
while ($row = $kerdesEredmeny->fetch_assoc()) {
    $letezoKerdesek[] = $row['kerdes'];
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // csak akkor frissítjük az adatokat, ha az új mezők nem üresek ha igen akkor nyilván a régi kell
    $nev = !empty($_POST['nev']) ? $_POST['nev'] : $project['nev'];
    $leiras = !empty($_POST['leiras']) ? $_POST['leiras'] : $project['leiras'];
    // borítókép kezelése
    if (!empty($_FILES['fokep']['name'])) {
        $fokep = $_FILES['fokep']['name'];
        move_uploaded_file($_FILES['fokep']['tmp_name'], "../feltoltesek/" . $fokep); // fájl feltöltése
    } else {
        $fokep = $project['fokep']; // ha nincs új kép, megtartjuk a régit
    }
    // médiafájlok feltöltése
if (!empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['name'] as $key => $name) {
        $targetPath = "../feltoltesek/" . basename($name);
        move_uploaded_file($_FILES['media']['tmp_name'][$key], $targetPath); // fájl feltöltése
        // fájl típusának meghatározása
        $fileType = '';
        if (strpos($name, '.mp4') !== false || strpos($name, '.webm') !== false) {
            $fileType = 'video';
        } elseif (strpos($name, '.jpg') !== false || strpos($name, '.png') !== false || strpos($name, '.jpeg') !== false) {
            $fileType = 'kep';
        } elseif (strpos($name, '.mp3') !== false || strpos($name, '.wav') !== false) {
            $fileType = 'audio';
        } else {
            $fileType = 'other';
        }
        // új fájl mentése az adatbázisba
        $sqlFile = "INSERT INTO fajlok (fajl_nev, projekt_id, tipus) VALUES ('$name', '$projektId', '$fileType')";
        $conn->query($sqlFile);
    }
}
    // meglévő médiafájlok törlése
    if (!empty($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $fileId) {
            // fájl nevének lekérdezése törlés előtt
            $sqlGetFileName = "SELECT fajl_nev FROM fajlok WHERE id = '$fileId'";
            $resultFileName = $conn->query($sqlGetFileName);
            $fileName = $resultFileName->fetch_assoc()['fajl_nev'];
            // fájl törlése a feltöltések mappából
            $filePath = "../feltoltesek/" . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath); // fájl törlése
            }
            // fájl törlése az adatbázisból
            $sqlDelete = "DELETE FROM fajlok WHERE id = '$fileId'";
            $conn->query($sqlDelete);
        }
    }
    // új kérdések feldolgozása
if (!empty($_POST['new_questions'])) {
    foreach ($_POST['new_questions'] as $newQuestion) {
        if (!empty($newQuestion['kerdes']) || !empty($newQuestion['kerdes_select'])) {
            // ha van választott kérdés, akkor a választott kérdést mentjük el
            $kerdes = !empty($newQuestion['kerdes_select']) ? $newQuestion['kerdes_select'] : $newQuestion['kerdes'];
            $tipus = $newQuestion['valasz_tipus'];
            $lehetseges_valaszok = !empty($newQuestion['lehetseges_valaszok']) ? $newQuestion['lehetseges_valaszok'] : null;
            $required = isset($newQuestion['required']) ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO kerdesek (kerdes, valasz_tipus, lehetseges_valaszok, required, projekt_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $kerdes, $tipus, $lehetseges_valaszok, $required, $projektId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
    // kérdések frissítése
    if (!empty($_POST['edit_questions'])) {
        foreach ($_POST['edit_questions'] as $questionId => $questionData) {
            $kerdes = $questionData['kerdes'];
            $tipus = $questionData['valasz_tipus'];
            $lehetseges_valaszok = isset($questionData['lehetseges_valaszok']) ? $questionData['lehetseges_valaszok'] : null;
            $required = isset($questionData['required']) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE kerdesek SET kerdes = ?, valasz_tipus = ?, lehetseges_valaszok = ?, required = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $kerdes, $tipus, $lehetseges_valaszok, $required, $questionId);
            $stmt->execute();
            $stmt->close();
        }
    }
    // kérdés törlése előtt töröljük a kapcsolódó válaszokat
    if (!empty($_POST['delete_questions'])) {
        foreach ($_POST['delete_questions'] as $questionId) {
            // töröljük a válaszokat a kerdesekre_valasz táblából
            $stmtDeleteAnswers = $conn->prepare("DELETE FROM kerdesekre_valasz WHERE kerdesek_id = ?");
            $stmtDeleteAnswers->bind_param("i", $questionId);
            $stmtDeleteAnswers->execute();
            // töröljük a kérdést
            $stmtDeleteQuestion = $conn->prepare("DELETE FROM kerdesek WHERE id = ?");
            $stmtDeleteQuestion->bind_param("i", $questionId);
            $stmtDeleteQuestion->execute();
            $stmtDeleteQuestion->close();
        }
    }
    // kérdések törlése
    if (!empty($_POST['delete_questions'])) {
        foreach ($_POST['delete_questions'] as $questionId) {
            $stmt = $conn->prepare("DELETE FROM kerdesek WHERE id = ?");
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            $stmt->close();
        }
    }
    // kitöltési cél frissítése az adatbázisban
    $kitoltesi_cel = !empty($_POST['kitoltesi_cel']) ? $_POST['kitoltesi_cel'] : $project['kitoltesi_cel']; // Kitöltési cél
    // projekt frissítése az adatbázisban
    $sqlUpdate = "UPDATE projektek SET nev = '$nev', leiras = '$leiras', fokep = '$fokep', kitoltesi_cel = '$kitoltesi_cel' WHERE id = '$projektId'";
    $conn->query($sqlUpdate);
    header("Location: projekt_reszletek.php?id=$projektId");
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projekt Módosítása</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.5">
    <link rel="stylesheet" href="../css2/modositas.css?v=1.5">
</head>
<style>
</style>
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
<div class="content">
    <div class="form-container">
        <h2>Projekt Módosítása</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="nev">Projekt neve:</label>
            <input type="text" id="nev" name="nev" value="<?php echo htmlspecialchars($project['nev']); ?>" required>
            <label for="leiras">Leírás:</label>
            <textarea id="leiras" name="leiras"><?php echo htmlspecialchars($project['leiras']); ?></textarea>
            <label for="fokep">Borítókép:</label>
            <input type="file" id="fokep" name="fokep" accept="image/*"><br>
            <small>Ha nem szeretnél új képet feltölteni, csak hagyd üresen.</small><br>
            <label>Képek/Videók/Hangfájlok:</label>
            <div class="media-preview">
                <?php
                $mediaCount = 0; // média száma de csak első 5 kell majd
                $videoCount = 0;  // csak 3 vidi lesz max megjel
                $totalVideos = 0; // összes videó
                while ($media = $mediaResult->fetch_assoc()):
                    if (strpos($media['fajl_nev'], '.mp4') !== false || strpos($media['fajl_nev'], '.webm') !== false):
                        $totalVideos++;
                    endif;
                endwhile;
                $mediaResult->data_seek(0); // visszaállítjuk az eredményt
                while ($media = $mediaResult->fetch_assoc()):
                    if ($mediaCount < 5): // csak az első 5 fájl megjelenítése
                        ?>
                        <div class="media-item">
                            <?php if (strpos($media['fajl_nev'], '.jpg') !== false || strpos($media['fajl_nev'], '.png') !== false): ?>
                                <!-- kép -->
                                <img src="../feltoltesek/<?php echo htmlspecialchars($media['fajl_nev']); ?>" alt="<?php echo htmlspecialchars($media['fajl_nev']); ?>">
 <?php elseif (strpos($media['fajl_nev'], '.mp4') !== false || strpos($media['fajl_nev'], '.webm') !== false): ?>
                                <!-- videó -->
                                <?php if ($videoCount < 3): ?>
                                    <div class="video-container">
                                    <video width="200" controls>
    <source src="../feltoltesek/<?php echo htmlspecialchars($media['fajl_nev']); ?>" type="video/<?php echo pathinfo($media['fajl_nev'], PATHINFO_EXTENSION); ?>">
    A böngésződ nem támogatja a videólejátszást.
</video>
                                    </div>
                                    <?php $videoCount++; ?>
                                <?php endif; ?>
                            <?php elseif (strpos($media['fajl_nev'], '.mp3') !== false || strpos($media['fajl_nev'], '.wav') !== false): ?>
                                <!-- hang fájl -->
                                <audio controls>
                                <img src="../feltoltesek/<?php echo htmlspecialchars($media['fajl_nev']); ?>" alt="<?php echo htmlspecialchars($media['fajl_nev']); ?>">
                                A böngésződ nem támogatja a hang lejátszást.
                                </audio>
                            <?php else: ?>
                                <!-- egyéb fájl -->
                                <p><?php echo htmlspecialchars($media['fajl_nev']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php
                    endif;
                    $mediaCount++;
                endwhile;
                // ha több mint 3 videó van, megjelenítjük a hátralévő videók számát
                if ($totalVideos > 3):
                ?>
                    <p><strong>Hátralévő videók: <?php echo $totalVideos - 3; ?></strong></p>
                <?php endif; ?>
            </div>
            <button type="button" id="show-all-media">Összes fájl megjelenítése</button>
            <div id="all-media" style="display:none;">
                <h3>Összes Médiafájl:</h3>
                <?php
                // újra lekérdezzük az összes médiafájlt, hogy megjeleníthessük
                $mediaResult->data_seek(0); // visszaállítjuk az eredményt
                while ($media = $mediaResult->fetch_assoc()):
                ?>
                    <div class="media-item">
                        <?php if (strpos($media['fajl_nev'], '.jpg') !== false || strpos($media['fajl_nev'], '.png') !== false): ?>
                            <img src="../feltoltesek/<?php echo htmlspecialchars($media['fajl_nev']); ?>" alt="<?php echo htmlspecialchars($media['fajl_nev']); ?>">
 <?php else: ?>
                            <p><?php echo htmlspecialchars($media['fajl_nev']); ?></p>
                        <?php endif; ?>
                        <input type="checkbox" name="delete_files[]" value="<?php echo $media['id']; ?>"> Törlés
                    </div>
                <?php endwhile; ?>
            </div>
            <input type="file" name="media[]" accept="image/*,video/*,audio/*" multiple><br> <!-- Új médiafájlok feltöltése -->
            <small>Több fájl is feltölthető.</small><br>
            <label for="kitoltesi_cel">Kitöltési cél:</label>
            <input type="text" id="kitoltesi_cel" class="small-input" name="kitoltesi_cel" value="<?php echo htmlspecialchars($project['kitoltesi_cel']); ?>" required>
            <h3>Kérdések Módosítása:</h3>
<div id="questions-wrapper" style="border: 2px solid #ccc; padding: 15px; border-radius: 5px;">
    <div id="questions">
        <?php while ($question = $questionsResult->fetch_assoc()): ?>
            <div class="question-container" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                <label for="edit_questions_<?php echo $question['id']; ?>">Kérdés:</label>
                <input type="text" class="small-input" name="edit_questions[<?php echo $question['id']; ?>][kerdes]"
                    id="edit_questions_<?php echo $question['id']; ?>"
                    value="<?php echo htmlspecialchars($question['kerdes']); ?>" required>
                <label for="edit_questions_<?php echo $question['id']; ?>_tipus">Típus:</label>
                <select name="edit_questions[<?php echo $question['id']; ?>][valasz_tipus]"
                        id="edit_questions_<?php echo $question['id']; ?>_tipus"
                        onchange="toggleEnumOptions(this)">
                    <option value="int" <?php echo ($question['valasz_tipus'] === 'int') ? 'selected' : ''; ?>>Szám</option>
                    <option value="enum" <?php echo ($question['valasz_tipus'] === 'enum') ? 'selected' : ''; ?>>Választásos</option>
                    <option value="text" <?php echo ($question['valasz_tipus'] === 'text') ? 'selected' : ''; ?>>Szöveg</option>
                </select>
                <div class="enum-options" style="display: <?php echo ($question['valasz_tipus'] === 'enum') ? 'block' : 'none'; ?>;">
                    <label for="edit_questions_<?php echo $question['id']; ?>_enum">Választék (választásos esetén):</label>
                    <input type="text" name="edit_questions[<?php echo $question['id']; ?>][lehetseges_valaszok]"
                        id="edit_questions_<?php echo $question['id']; ?>_enum"
                        value="<?php echo htmlspecialchars($question['lehetseges_valaszok'] ?? ''); ?>"
                        placeholder="Példa: Igen, Nem" class="small-input">
                </div>
                <label for="edit_questions_<?php echo $question['id']; ?>_kotelezo">Kötelező?</label>
                <input type="checkbox" name="edit_questions[<?php echo $question['id']; ?>][required]"
                    id="edit_questions_<?php echo $question['id']; ?>_kotelezo"
                    <?php echo $question['required'] ? 'checked' : ''; ?>>
                <button type="button" class="remove-button" onclick="removeQuestion(this)">Eltávolítás</button>
                <input type="hidden" name="delete_questions[]" value="<?php echo $question['id']; ?>" class="delete-flag" disabled>
            </div>
        <?php endwhile; ?>
    </div>
</div>
<button type="button" onclick="addQuestion()">Új kérdés hozzáadása</button>
<input type="submit" value="Mentés">
<input type="button" value="Vissza" class="back-button" onclick="window.location.href='projektjeim.php';">
    </div>
</div>
<script>
const existingQuestions = <?php echo json_encode($letezoKerdesek); ?> // létező kérdések
function addQuestion() { // kérdés hozzáadása
    const questionContainer = document.createElement('div');
    questionContainer.classList.add('question-container');
    const index = document.querySelectorAll('.question-container').length;
    const optionsHTML = existingQuestions.map(q => `<option value="${q}">${q}</option>`).join(''); // létező kérdések legördülő vagy majd újat beírni is lesz lehetőség
    questionContainer.innerHTML = `
        <label>Kérdés:</label>
<select class="custom-question-select" onchange="toggleCustomQuestion(this, ${index})">
    <option value="">-- Új kérdés --</option>
    ${optionsHTML}
</select>
<input type="text" name="new_questions[${index}][kerdes]" required placeholder="Írd be az új kérdést">
<!-- A kérdésválasztás nélküli típus beállítása -->
<input type="hidden" name="new_questions[${index}][kerdes_select]" value="" id="kerdes_select_${index}">
        <label for="type">Típus:</label>
        <select name="new_questions[${index}][valasz_tipus]" required onchange="toggleRequiredField(this)">
            <option value="int">Szám</option>
            <option value="enum">Választásos</option>
            <option value="text">Szöveg</option>
            <option value="date">Dátum</option> <!-- Új lehetőség a dátumhoz -->
        </select>
        <div class="enum-options" style="display: none;">
            <label for="options">Választék (választásos esetén):</label>
            <input type="text" name="new_questions[${index}][lehetseges_valaszok]" placeholder="Példa: Igen, Nem">
        </div>
        <div class="date-options" style="display: none;">
            <label for="date_input_${index}">Dátum:</label>
            <input type="date" name="new_questions[${index}][date]" id="date_input_${index}">
        </div>
        <label for="required">Kötelező?</label>
        <input type="checkbox" name="new_questions[${index}][required]" onchange="toggleRequiredField(this)">
        <button type="button" class="remove-button" onclick="removeQuestion(this)">Eltávolítás</button>
    `;
    document.getElementById('questions').appendChild(questionContainer); // ezzel kerül jó helyre
}
// típus szerint datehez és enumhoz
function toggleRequiredField(elem) {
    const questionContainer = elem.closest('.question-container');
    const enumOptions = questionContainer.querySelector('.enum-options');
    const dateOptions = questionContainer.querySelector('.date-options');
    if (elem.tagName === 'SELECT') {
        // enum opció megjelenítése
        enumOptions.style.display = elem.value === 'enum' ? 'block' : 'none';
        // date opció megjelenítése
        dateOptions.style.display = elem.value === 'date' ? 'block' : 'none';
    }
}
// kérdés törlése
function removeQuestion(button) {
    const container = button.closest('.question-container');
    const deleteFlag = container.querySelector('.delete-flag');
    if (deleteFlag) {
        deleteFlag.disabled = false;
        container.style.display = 'none';
    } else {
        container.remove();
    }
}
// ha meglévő kérdés van akkoor ne legyen input mező
function toggleCustomQuestion(selectElem, index) {
        const container = selectElem.closest('.question-container');
        const input = container.querySelector(`input[name="new_questions[${index}][kerdes]"]`);
        const hiddenInput = container.querySelector(`#kerdes_select_${index}`);
        if (selectElem.value !== "") {
            input.value = selectElem.value;  // legördülő listából választott kérdés beállítása
            input.disabled = true; // ha van kiválasztott kérdés, ne lehessen szerkeszteni
            hiddenInput.value = selectElem.value; // az elrejtett mezőbe is beírjuk
        } else {
            input.value = ""; // nincs kiválasztott kérdés, a mezőt töröljük
            input.disabled = false; // nincs választás, akkor szerkeszthető
            hiddenInput.value = ""; // az elrejtett mező törlésre kerül
        }
    }
// új ablak megnyitása az összes médiafájl megjelenítéséhez
document.getElementById('show-all-media').onclick = function() {
    window.open('torlendok.php?id=<?php echo $projektId; ?>', 'MediaWindow', 'width=800,height=600');
};
// média frissítése media-preview
function refreshMedia() {
    // AJAX kérés küldése
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'frissit_media.php?id=<?php echo htmlspecialchars($_GET['id']); ?>', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // media-preview frissítése
            document.querySelector('.media-preview').innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}
</script>
</body>
</html>
