<?php

// Verbindung zur Datenbank sicherstellen
$connectionPath = __DIR__ . '/Seiten2/connection.php';
if (!file_exists($connectionPath)) {
    die('Fehler: Die Datei connection.php wurde nicht gefunden.');
}
require_once($connectionPath);

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Eingaben validieren und Standardwerte setzen
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["passwort"] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Bitte alle Felder ausfüllen.";
    } else {
        // Prüfen, ob der Benutzer bereits existiert
        $stmt = $conn->prepare("SELECT 1 FROM benutzer WHERE username = :username OR email = :email");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->fetch()) {
            $error = "Benutzername oder E-Mail existiert bereits.";
        } else {
            // Benutzer registrieren
            if (registerUser($conn, $username, $email, $password)) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Fehler bei der Registrierung.";
            }
        }
    }
}

// Funktion zur Registrierung des Benutzers
function registerUser($conn, $username, $email, $password) {
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Benutzer erstellen
        $stmt = $conn->prepare("INSERT INTO benutzer (username, email, passwort) VALUES (:username, :email, :passwort)");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":passwort", $hashedPassword);
        $stmt->execute();

        // ID des neuen Benutzers abrufen
        $user_id = $conn->lastInsertId();

        // Standardrolle "User" zuweisen (ID 3)
        $roleStmt = $conn->prepare("INSERT INTO benutzer_rollen (benutzer_id, rolle_id) VALUES (:user_id, 3)");
        $roleStmt->bindParam(":user_id", $user_id);
        $roleStmt->execute();

        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }
        form {
            display: flex;
            flex-direction: column;
            width: 300px;
        }
        input, button {
            margin-bottom: 10px;
            padding: 8px;
            font-size: 16px;
        }
        .error {
            color: red;
        }
        .login-link {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Registrierung</h2>
    <form method="post">
        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="text" name="username" placeholder="Benutzername" required>
        <input type="password" name="passwort" placeholder="Passwort" required>
        <button type="submit">Registrieren</button>
    </form>
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <div class="login-link">
        <fieldset>
            
            <a href="login.php"><span>Zum Einloggen</span></a>
        </fieldset>
    </div>
</body>
</html>
