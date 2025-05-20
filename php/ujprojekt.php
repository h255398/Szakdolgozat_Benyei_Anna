<?php
session_start();
// felh bejel ell
if (!isset($_SESSION['felhasznalonev'])) {
    header("Location: bejelentkezes.php");
    exit();
}
// adatb kapcs
require_once "db_connect.php";
// már meglévő kérdések lekérdezése
$letezoKerdesek = [];
$kerdesQuery = "SELECT DISTINCT kerdes FROM kerdesek";
$kerdesEredmeny = $conn->query($kerdesQuery);
while ($row = $kerdesEredmeny->fetch_assoc()) {
    $letezoKerdesek[] = $row['kerdes'];
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új Projekt - Projektértékelő</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.1">
    <link rel="stylesheet" href="../css2/reg.css?v=1.1">
    <link rel="stylesheet" href="../css2/ujprojekt.css?v=1.2">
    <script>
        const existingQuestions = <?php echo json_encode($letezoKerdesek); ?>; // már létező kérdések
        // kérdés hozzáadása
        function addQuestion() {
            const questionContainer = document.createElement('div');
            questionContainer.classList.add('question-container');
            const index = document.querySelectorAll('.question-container').length;
            const optionsHTML = existingQuestions.map(q => `<option value="${q}">${q}</option>`).join(''); // meglévők
            questionContainer.innerHTML = `
                <label>Kérdés:</label>
                <select class="custom-question-select" onchange="toggleCustomQuestion(this, ${index})">
                    <option value="">-- Új kérdés --</option>
                    ${optionsHTML}
                </select>
                <input type="text" name="questions[${index}][kerdes]" required placeholder="Írd be az új kérdést">
<!-- Rejtett input a kérdés tárolására -->
<input type="hidden" name="questions[${index}][hidden_kerdes]" value="">
                <label for="type">Típus:</label>
                <select name="questions[${index}][valasz_tipus]" required onchange="toggleRequiredField(this)">
                    <option value="int">Szám</option>
                    <option value="enum">Választásos</option>
                    <option value="text">Szöveg</option>
                    <option value="date">Dátum</option> <!-- Dátum típus hozzáadása -->
                </select>
                <div class="enum-options" style="display: none;">
                    <label for="options">Választék (választásos esetén):</label>
                    <input type="text" name="questions[${index}][lehetseges_valaszok]" placeholder="Példa: Igen, Nem">
                </div>
                <label for="required">Kötelező?</label>
                <input type="checkbox" name="questions[${index}][required]" onchange="toggleRequiredField(this)">
                <button type="button" class="remove-question" onclick="removeQuestion(this)">Eltávolítás</button>
            `;
            // legördülő menü és enum kezelésénél az a plusz mező
            questionContainer.querySelector('select[name="questions[' + index + '][valasz_tipus]"]').addEventListener('change', function () {
                const enumOptions = questionContainer.querySelector('.enum-options');
                enumOptions.style.display = this.value === 'enum' ? 'block' : 'none';
            });
            document.getElementById('questions').appendChild(questionContainer);
        }
        // típus kezelés
        function toggleCustomQuestion(selectElem, index) {
            const container = selectElem.closest('.question-container');
            const input = container.querySelector(`input[name="questions[${index}][kerdes]"]`);
            const hiddenInput = container.querySelector(`input[name="questions[${index}][hidden_kerdes]"]`); // új rejtett input
            // Ha van választás a legördülő menüből
            if (selectElem.value !== '') {
                input.value = selectElem.value;
                hiddenInput.value = selectElem.value; // frissítjük a rejtett mezőt a kiválasztottra
            } else {
                input.value = ''; // ha az "Új kérdés" opció van kiválasztva, akkor üres lesz
                hiddenInput.value = ''; // frissítjük a rejtett mezőt is
            }
            input.disabled = selectElem.value !== ''; // ha van kiválasztott kérdés, akkor az input mező ne legyen szerkeszthető
        }
        // kérdés törlése
        function removeQuestion(button) {
            button.parentElement.remove();
        }
        // enum esetén válaszlehetőségek
        function toggleRequiredField(elem) {
            const questionContainer = elem.closest('.question-container');
            const enumOptions = questionContainer.querySelector('.enum-options');
            if (elem.tagName === 'SELECT') {
                enumOptions.style.display = elem.value === 'enum' ? 'block' : 'none';
            }
        }
    </script>
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
    <div class="content">
        <div class="form-container">
            <h2>Új Projekt Létrehozása</h2>
            <form action="ujprojekt.php" method="post" enctype="multipart/form-data">
                <label for="project_name">Projekt neve:</label>
                <input type="text" id="project_name" name="project_name" required>
                <label for="cover_image">Főkép:</label>
                <input type="file" id="cover_image" name="cover_image" accept="image/*" required>
                <label for="files">Feltöltendő fájlok (Kép vagy Videó):</label>
                <input type="file" id="files" name="files[]" multiple accept="image/*,video/*">
                <label for="project_description">Leírás:</label>
                <textarea id="project_description" name="project_description"></textarea>
                <label for="kitoltesi_cel">Kitöltési cél:</label>
                <input type="number" id="kitoltesi_cel" class="small-input" name="kitoltesi_cel" value="200" required>
                <h3>Kérdések hozzáadása:</h3>
                <div id="questions"></div>
                <button type="button" onclick="addQuestion()">Új kérdés hozzáadása</button>
                <input type="submit" value="Projekt létrehozása">
            </form>
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $projectName = $conn->real_escape_string($_POST['project_name']);
                $projectDescription = $conn->real_escape_string($_POST['project_description']);
                $kitoltesiCel = (int) $_POST['kitoltesi_cel'];
                $coverImageName = $_FILES['cover_image']['name'];
                $coverImageTmpName = $_FILES['cover_image']['tmp_name'];
                $coverImageTarget = "../feltoltesek/" . basename($coverImageName);
                // ell hogy a borítókép létezik e
                if (!file_exists($coverImageTmpName)) {
                    die("Hiba: A feltöltött borítókép nem létezik!");
                }
                // borító  áthely
                if (!move_uploaded_file($coverImageTmpName, $coverImageTarget)) {
                    die("Hiba a borítókép feltöltésekor!");
                }
                // üres tömb létrehozása a feltöltött fájlok nyilvántartásához
                $uploadedFiles = [];
                // végigmegy az összes fájlon, amit feltöltöttek
                foreach ($_FILES['files']['name'] as $index => $fileName) {
                    // fájl ideiglenes neve
                    $fileTmpName = $_FILES['files']['tmp_name'][$index];
                    // fájl kép vagy videó
                    $fileType = (strpos($_FILES['files']['type'][$index], 'image') !== false) ? 'kep' : 'video';
                    // ahova menteni kell
                    $fileTarget = "../feltoltesek/" . basename($fileName);
                    // fájl sikeresen áthelyezésre került a célba, hozzáadjuk a tömbhöz
                    if (move_uploaded_file($fileTmpName, $fileTarget)) {
                        $uploadedFiles[] = ['fileName' => $fileName, 'type' => $fileType];
                    }
                }
                $felhasznalonev = $_SESSION['felhasznalonev'];
                // felh id lekérése
                $sqlUser = "SELECT id FROM felhasznalok WHERE felhasznalonev = '$felhasznalonev'";
                $resultUser = $conn->query($sqlUser);
                $userId = $resultUser->fetch_assoc()['id'];
                // beszúrja a projektet az adatbázisba
                $sqlProject = "INSERT INTO projektek (felhasznalok_id, nev, leiras, fokep, kitoltesi_cel)
                   VALUES ('$userId', '$projectName', '$projectDescription', '$coverImageName', '$kitoltesiCel')";
                // ha a beszúrás sikeres volt
                if ($conn->query($sqlProject) === TRUE) {
                    // projekt id
                    $projectId = $conn->insert_id;
                    // ha vannak kérdések az űrlapban
                    if (!empty($_POST['questions'])) {
                        foreach ($_POST['questions'] as $question) {
                            // ha van rejtett kérdés, azt használja, egyébként a láthatót
                            $kerdes = !empty($question['hidden_kerdes']) ? $conn->real_escape_string($question['hidden_kerdes']) : $conn->real_escape_string($question['kerdes']);
                            $valaszTipus = $conn->real_escape_string($question['valasz_tipus']);
                            $lehetsegesValaszok = isset($question['lehetseges_valaszok']) ? $conn->real_escape_string($question['lehetseges_valaszok']) : NULL;
                            $required = isset($question['required']) ? 1 : 0;
                            // kérdés beszúrása az adatbázisba
                            $sqlQuestion = "INSERT INTO kerdesek (projekt_id, kerdes, valasz_tipus, lehetseges_valaszok, required)
                                VALUES ('$projectId', '$kerdes', '$valaszTipus', '$lehetsegesValaszok', '$required')";
                            $conn->query($sqlQuestion);
                            $question_id = $conn->insert_id;
                            if ($valaszTipus === 'date' && isset($question['valasz']) && !empty($question['valasz'])) {
                                $valasz = $conn->real_escape_string($question['valasz']);
                                $sqlAnswer = "INSERT INTO kerdesek_valaszok (kerdes_id, valasz)
                  VALUES ('$question_id', '$valasz')";
                                $conn->query($sqlAnswer);
                            }
                        }
                    }
                    // feltöltött fájlok mentése az adatbázisba
                    foreach ($uploadedFiles as $file) {
                        $fileName = $file['fileName'];
                        $fileType = $file['type'];
                        $sqlFile = "INSERT INTO fajlok (projekt_id, fajl_nev, tipus)
                        VALUES ('$projectId', '$fileName', '$fileType')";
                        $conn->query($sqlFile);
                    }
                    echo "<script>alert('Sikeres projekt létrehozás!');</script>";
                    echo "<script>window.location.href = 'projektjeim.php';</script>";
                    exit();
                } else {
                    echo "Hiba történt a projekt létrehozásakor: " . $conn->error;
                }
            }
            ?>
        </div>
    </div>
</body>

</html>