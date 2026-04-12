<?php

// Verbindet sich mit der Datenbank
include 'Seiten2/connection.php'; 

// Erfolgs- und Fehlermeldungen
$successMessage = '';
$errorMessage = '';


// Starten der Session
session_start();

// Prüfen, ob der Benutzer eingeloggt ist und die Rolle Admin hat
if (!isset($_SESSION['user_id'])) {
    // Wenn der Benutzer nicht eingeloggt ist, zur Login-Seite umleiten
    header('Location: login.php');
    exit();
}

// Datenbankverbindung
include 'Seiten2/connection.php'; // Verbindung zur Datenbank herstellen

// Admin-Rolle ID holen
$sql = "SELECT id FROM rollen WHERE name = 'Admin'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$adminRoleId = $stmt->fetchColumn();

// Prüfen, ob der Benutzer die Admin-Rolle hat
$sql_check = "SELECT 1 FROM benutzer_rollen WHERE benutzer_id = :user_id AND rolle_id = :role_id";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt_check->bindValue(':role_id', $adminRoleId, PDO::PARAM_INT);
$stmt_check->execute();

// Wenn der Benutzer nicht die Admin-Rolle hat, umleiten
if (!$stmt_check->fetchColumn()) {
    header('Location: hauptseite.php');
    exit();
}

// Erfolgsmeldung und Fehlerbehandlung
$successMessage = '';
$errorMessage = '';

// Prüfen, ob das Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Benutzerdaten aus dem Formular
    $userId = $_POST['user_id'];
    $roleId = $_POST['role_id'];

    // Benutzerrolle zuweisen
    if ($userId && $roleId) {
        // Zuerst die alte Rolle des Benutzers löschen
        $stmtDelete = $conn->prepare('DELETE FROM benutzer_rollen WHERE benutzer_id = :user_id');
        $stmtDelete->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmtDelete->execute();

        // Jetzt die neue Rolle zuweisen
        $stmtInsert = $conn->prepare('INSERT INTO benutzer_rollen (benutzer_id, rolle_id) VALUES (:user_id, :role_id)');
        $stmtInsert->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmtInsert->bindValue(':role_id', $roleId, SQLITE3_INTEGER);

        if ($stmtInsert->execute()) {
            $successMessage = "Rolle erfolgreich zugewiesen!";
        } else {
            $errorMessage = "Fehler beim Zuweisen der Rolle: " . $conn->errorInfo()[2];
        }
    } else {
        $errorMessage = "Bitte wählen Sie sowohl einen Benutzer als auch eine Rolle aus.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.2">
    <title>Benutzerrolle zuweisen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        form {
            max-width: 500px;
            margin: auto;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select,
        button {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }

        .zurück1 {
            text-align: center;
            margin-top: 30px;
        }

        .zurück1 a {
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
        }

        .zurück1 a:hover {
            background-color: #0056b3;
        }

        fieldset {
            border: none;
        }
    </style>
</head>

<body class="zuweisung">

    <h1 style="text-align: center;">Benutzerrolle zuweisen</h1>

    <!-- Erfolgsmeldung anzeigen -->
    <?php if (isset($successMessage)): ?>
        <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <!-- Formular für Benutzerrollen -->
    <form action="assign_role.php" method="post">
        <!-- Benutzer auswählen -->
        <label for="user_id">Benutzer auswählen:</label>
        <select name="user_id" id="user_id" required>
            <option value="">-- Benutzer auswählen --</option>
            <?php
            $sql = 'SELECT id, username FROM benutzer';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            ?>
                <option value="<?php echo $row["id"]; ?>"><?php echo $row["username"]; ?></option>
            <?php
            }
            ?>
        </select>

        <!-- Rolle auswählen -->
        <label for="role_id">Rolle auswählen:</label>
        <select name="role_id" id="role_id" required>
            <option value="">-- Rolle auswählen --</option>
            <?php
            $sql = 'SELECT id, name FROM rollen';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            ?>
                <option value="<?php echo $row["id"]; ?>"><?php echo $row["name"]; ?></option>
            <?php
            }
            ?>
        </select>

        <!-- Submit-Button -->
        <button type="submit" name="assign_role">Rolle zuweisen</button>
    </form>

    <!-- Zurück-Button -->
    <div>
        <fieldset class="zurück1">          
            <a href="hauptseite.php">
                <span>Zurück</span>
            </a>
        </fieldset>
    </div>

</body>

</html>