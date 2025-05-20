<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció - Projektértékelő</title>
    <link rel="stylesheet" href="../css2/kezdolap.css?v=1.1">
    <link rel="stylesheet" href="../css2/reg.css?v=1.2">
</head>

<body>
    <header>
        <h1>Projektértékelő</h1>
        <div class="auth-links">
            <a href="regisztracio.php">Regisztráció</a>
            <a href="bejelentkezes.php">Bejelentkezés</a>
        </div>
    </header>
    <nav>
        <ul>
            <li><a href="../html/kezdolap.html">Kezdőlap</a></li>
            <li><a href="projektek.php">Projektek</a></li>
        </ul>
    </nav>
    <div class="content">
        <div class="form-container">
            <h2>Regisztráció</h2>
            <form action="" method="post">
                <label for="username">Felhasználónév:</label>
                <input type="text" id="username" name="username" required>
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Jelszó:</label>
                <input type="password" id="password" name="password" required minlength="4"
                    title="A jelszónak legalább 4 karakterből kell állnia.">
                <input type="submit" value="Regisztrálok">
            </form>
        </div>
    </div>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // adatb kapcs
        require_once "db_connect.php";
        // reg adatok
        $felhasznalonev = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $jelszo = password_hash($_POST['password'], PASSWORD_DEFAULT); // jelszó titkosítása
        // ell felh név már van e
        $checkUsernameSql = "SELECT * FROM felhasznalok WHERE felhasznalonev = '$felhasznalonev'";
        $checkUsernameResult = $conn->query($checkUsernameSql);
        // ell email van e már
        $checkEmailSql = "SELECT * FROM felhasznalok WHERE email = '$email'";
        $checkEmailResult = $conn->query($checkEmailSql);
        if ($checkUsernameResult->num_rows > 0) {
            // ha a felhasználónév foglalt
            echo "<script>alert('A felhasználónév már foglalt. Kérlek válassz másikat!');</script>";
        } elseif ($checkEmailResult->num_rows > 0) {
            // ha az email már regisztrálva van
            echo "<script>alert('Ez az e-mail cím már regisztrálva van!');</script>";
        } else {
            // ha nincs baj, akkor végrehajtjuk a regisztrációt
            $sql = "INSERT INTO felhasznalok (felhasznalonev, email, jelszo)
                VALUES ('$felhasznalonev', '$email', '$jelszo')";
            if ($conn->query($sql) === TRUE) {
                header("Location: bejelentkezes.php");
                exit();
            } else {
                echo "<p>Hiba: " . $conn->error . "</p>";
            }
        }
        $conn->close();
    }
    ?>
</body>

</html>