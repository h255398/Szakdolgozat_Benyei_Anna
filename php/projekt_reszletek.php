<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projekt Részletek - Projektértékelő</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.1">
    <link rel="stylesheet" href="../css2/projekt_reszletek.css?v=1.4">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
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
        session_start();
        require '../vendor/autoload.php'; // autoload Composer packages
        // ell felh bejel
        if (!isset($_SESSION['felhasznalonev'])) {
            header("Location: bejelentkezes.php");
            exit();
        }
        // adatb kapcs
        require_once "db_connect.php";
        // projekt ID
        $projektId = $_GET['id'];
        // projekt adatai
        $sqlProject = "SELECT * FROM projektek WHERE id = '$projektId'";
        $projectResult = $conn->query($sqlProject);
        $project = $projectResult->fetch_assoc();
        if ($project) {
            echo '<div class="cover-image-wrapper">';
            echo '<div class="project-name"><strong>Projekt borítóképe:</strong></div>';
            echo '<img class="cover-image" src="/szakdolgozat31/feltoltesek/' . htmlspecialchars($project['fokep']) . '" alt="' . htmlspecialchars($project['nev']) . '">';
            echo '</div>';
            echo '<div class="project-name"><strong>Projekt neve:</strong><br>' . htmlspecialchars($project['nev']) . '</div>';
            echo '<div class="project-description"><strong>Projekt leírása:</strong><br>' . htmlspecialchars($project['leiras']) . '</div>';
        } else {
            echo '<p>Nincs megjeleníthető projekt.</p>';
        }
        // fajlok
        $sqlMedia = "SELECT * FROM fajlok WHERE projekt_id = '$projektId'";
        $mediaResult = $conn->query($sqlMedia);
        echo '<div class="media-container">';
        $mediaCount = 0; // számláló
        if ($mediaResult->num_rows > 0) {
            while ($media = $mediaResult->fetch_assoc()) {
                $fileName = $media['fajl_nev'];
                $fileType = $media['tipus'];
                if ($fileType == 'kep') {
                    // képeek
                    echo '<div class="media-item">';
                    echo '<img src="../feltoltesek/' . htmlspecialchars($fileName) . '" alt="' . htmlspecialchars($fileName) . '">';
                    echo '</div>';
                    $mediaCount++;
                } elseif ($fileType == 'video') {
                    // videók
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                        echo '<div class="media-item">';
                        echo '<video controls>';
                        echo '<source src="../feltoltesek/' . htmlspecialchars($fileName) . '" type="video/' . $ext . '">';
                        echo 'A böngésződ nem támogatja a videó lejátszást.';
                        echo '</video>';
                        echo '</div>';
                    } else {
                        echo '<p>Ez a fájl nem támogatott videó formátum.</p>';
                    }
                    $mediaCount++;
                }
                // max 12 média
                if ($mediaCount >= 12) {
                    break;
                }
            }
            // ha több mint 12 fájl van
            if ($mediaResult->num_rows > 12) {
                echo '<div class="more-media">Több média elérhető...</div>';
            }
        } else {
            echo '<p>Nincs megjeleníthető média.</p>';
        }
        echo '</div>';
        // többi adat kitoltesek száma és cél
        $kitoltesekSzama = $project['eddigi_kitoltesek'];
        $kitoltesiCel = $project['kitoltesi_cel'];
        // fájlok száma
        $sqlFilesCount = "SELECT COUNT(*) as files_count FROM fajlok WHERE projekt_id = '$projektId'";
        $filesCountResult = $conn->query($sqlFilesCount);
        $filesCountRow = $filesCountResult->fetch_assoc();
        $fajlokSzama = $filesCountRow['files_count'];
        // megjel
        echo '<div class="kitoltesek-szama" style="margin-top: 20px;">';
        echo '<h3>Fájlok száma: ' . htmlspecialchars($fajlokSzama) . '</h3>';
        echo '<h3>Eddigi kitöltések száma: ' . htmlspecialchars($kitoltesekSzama) . '</h3>';
        echo '</div>';
        // cél megjelenítése és progress bar
        echo '<div class="goal-container" style="margin-top: 20px;">';
        echo '<h3>Cél: ' . htmlspecialchars($kitoltesiCel) . ' kitöltő</h3>';
        echo '<div id="progress-container">';
        // ha nem nulla a kitöltési cél
        if ($kitoltesiCel != 0) {
            $progress = ($kitoltesekSzama / $kitoltesiCel) * 100;
        } else {
            // ha mégis 0 akkkor 0ra beállítani
            $progress = 0;
        }
        // progress bar
        echo '<div id="progress-bar" style="width: ' . min($progress, 100) . '%;"></div>';
        echo '</div>';
        echo '</div>';
        // értékelések megjelenítése
        echo '<div class="ertekelesek-megjelenitese" style="margin-top: 20px;">';
        echo '<h3>Képek értékelései:</h3>';
        // pontszámok összesítése
        $sqlErtekelesek = "SELECT pontszam, COUNT(*) as szam FROM ertekelt_fajlok ef
                       JOIN fajlok f ON ef.fajl_id = f.id
                       WHERE f.projekt_id = '$projektId'
                       GROUP BY pontszam
                       ORDER BY pontszam ASC";
        $ertekelesekResult = $conn->query($sqlErtekelesek);
        $ertekelesekOsszesen = array(0, 0, 0, 0, 0); // 1-től 5-ig  számlálás 1es a 0.idexen
        if ($ertekelesekResult->num_rows > 0) {
            while ($ertekeles = $ertekelesekResult->fetch_assoc()) {
                $ertekelesOsszeg = (int) $ertekeles['pontszam'] - 1; // 1-5 helyett 0-4 index miatt
                $ertekelesekOsszesen[$ertekelesOsszeg] = (int) $ertekeles['szam'];
            }
        }
        // értékelések megjelenítése
        echo '<ul class="ul">';
        for ($i = 0; $i < 5; $i++) {
            echo '<li>' . ($i + 1) . ' pont: ' . htmlspecialchars($ertekelesekOsszesen[$i]) . ' értékelés</li>';
        }
        echo '</ul>';
        echo '</div>';
        ?>
        <div class="chart-container">
            <?php
            // ell van e értékelés
            $hasRatings = false;
            foreach ($ertekelesekOsszesen as $ratingCount) {
                if ($ratingCount > 0) {
                    $hasRatings = true;
                    break;
                }
            }
            // ha van értékelés akkor diagramm megjel
            if ($hasRatings) {
                echo '<canvas id="ratingChart" style="display: block; box-sizing: border-box;" width="500" height="500"></canvas>';
            } else {
                echo '<p>Nincs elég értékelés a grafikon megjelenítéséhez.</p>';
            }
            ?>
        </div>
        <?php
        // top 3 kép megjel ha van és átlag értékelésük
        $sqlTopKepek = "SELECT f.fajl_nev, AVG(ef.pontszam) AS atlag_pontszam
                FROM ertekelt_fajlok ef
                JOIN fajlok f ON ef.fajl_id = f.id
                WHERE f.projekt_id = '$projektId'
                GROUP BY f.id
                ORDER BY atlag_pontszam DESC
                LIMIT 3";
        $topKepekResult = $conn->query($sqlTopKepek);
        $conn->close();
        echo '<div class="top-kepek-container">';
        echo '<h3>Top 3 legjobbra értékelt kép:</h3>';
        if ($topKepekResult->num_rows > 0) {
            echo '<div class="top-kepek">';
            while ($kep = $topKepekResult->fetch_assoc()) {
                echo '<div class="top-kep">';
                echo '<img src="../feltoltesek/' . htmlspecialchars($kep['fajl_nev']) . '" alt="Legjobbra értékelt kép">';
                echo '<p>Átlagos értékelés: ' . number_format($kep['atlag_pontszam'], 2) . '</p>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>Nincsenek értékelt képek.</p>';
        }
        echo '</div>';
        ?>
        <script>
            const ertekelesek = <?php echo json_encode($ertekelesekOsszesen); ?>;
            let filteredRatings = [...ertekelesek]; // másolat a diagramm módosításához kell majd
            const totalRatings = () => filteredRatings.reduce((acc, value) => acc + value, 0); // összes értékelés összege
            // van e már canvas elem
            const canvas = document.getElementById('ratingChart');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                // update ha változtatunk rajta (elrejtünk)
                function updateChart() {
                    if (ratingChart) {
                        ratingChart.data.datasets[0].data = filteredRatings;
                        ratingChart.update();
                    }
                }
                // diagramm inicializálása
                const ratingChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['1 pont', '2 pont', '3 pont', '4 pont', '5 pont'],
                        datasets: [{
                            label: 'Értékelések megoszlása',
                            data: filteredRatings,
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'], // színek hozzá
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    generateLabels: function (chart) {
                                        const data = chart.data;
                                        return data.labels.map((label, index) => ({
                                            text: label,
                                            fillStyle: data.datasets[0].backgroundColor[index],
                                            hidden: filteredRatings[index] === 0,
                                            datasetIndex: 0,
                                            index: index
                                        }));
                                    }
                                }, // ha rákatt akkor kivonjuk a körből
                                onClick: (event, legendItem) => {
                                    const index = legendItem.index;
                                    filteredRatings[index] = filteredRatings[index] === 0 ? ertekelesek[index] : 0;
                                    // frissít
                                    const legendItems = document.querySelectorAll('.chartjs-legend li');
                                    if (legendItems[index]) {
                                        legendItems[index].classList.toggle('legend-item-removed', filteredRatings[index] === 0);
                                    }
                                    updateChart();
                                }
                            },// ha fölé visszük megjel a %os arányt és db szám is
                            tooltip: {
                                callbacks: {
                                    label: function (tooltipItem) {
                                        const value = tooltipItem.raw;
                                        const percentage = ((value / totalRatings()) * 100).toFixed(1);
                                        return `${tooltipItem.label}: ${value} értékelés (${percentage}%)`;
                                    }
                                }
                            },// a szeleteken belül is legyen megjelenve
                            datalabels: {
                                color: '#fff',
                                formatter: function (value) {
                                    const percentage = ((value / totalRatings()) * 100).toFixed(1);
                                    return `${percentage}%`;
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
                updateChart();
            } else {
                console.log("A grafikon canvas elem nem található.");
            }
        </script>
    </div>
    <div class="button-container">
        <button class="edit-button" onclick="window.location.href='modositas.php?id=<?php echo $projektId; ?>'">Projekt
            módosítása</button>
        <button class="delete-button"
            onclick="if(confirm('Biztosan törli ezt a projektet?')) { window.location.href='torles.php?id=<?php echo $projektId; ?>'; }">Projekt
            törlése</button>
        <button class="export-button"
            onclick="window.location.href='export_excel.php?id=<?php echo $projektId; ?>'">Excel Exportálás</button>
    </div>
</body>

</html>