<?php
// Verbindung zur Datenbank herstellen
include 'Seiten2/connection.php';

// Erfolgs- und Fehlermeldungen
$successMessage = '';
$errorMessage = '';

// Starten der Session
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Admin-Rolle ID holen
$sql = "SELECT id FROM rollen WHERE name = 'Admin'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$adminRoleId = $stmt->fetchColumn();

// Prüfen, ob der Benutzer Admin ist
$sql_check = "SELECT 1 FROM benutzer_rollen WHERE benutzer_id = :user_id AND rolle_id = :role_id";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt_check->bindValue(':role_id', $adminRoleId, PDO::PARAM_INT);
$stmt_check->execute();
$isAdmin = $stmt_check->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $errorMessage = 'Bitte einen Benutzer auswählen!';
    } else {
        $userId = $_POST['user_id'];

        // Admin kann sich nicht selbst löschen
        if ($isAdmin && $userId == $_SESSION['user_id']) {
            $errorMessage = 'Admins können sich nicht selbst löschen!';
        } else {
            // Benutzer löschen
            $stmtDeleteUser = $conn->prepare('DELETE FROM benutzer WHERE id = :user_id');
            $stmtDeleteUser->bindValue(':user_id', $userId, PDO::PARAM_INT);

            if ($stmtDeleteUser->execute()) {
                // Benutzer-Rolle löschen
                $stmtDeleteRole = $conn->prepare('DELETE FROM benutzer_rollen WHERE benutzer_id = :user_id');
                $stmtDeleteRole->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmtDeleteRole->execute();

                // Falls sich der Benutzer selbst löscht, Session beenden
                if ($userId == $_SESSION['user_id']) {
                    session_destroy();
                    header('Location: login.php');
                    exit();
                }

                $successMessage = 'Account erfolgreich gelöscht!';
            } else {
                $errorMessage = 'Fehler beim Löschen des Accounts.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account löschen</title>
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

        select, button {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #f44336;
            color: white;
        }

        button:hover {
            background-color: #d32f2f;
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
            margin-top: 20px;
            
        }

        .zurück1 a {
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            padding: 10px 20px;
            border-radius: 4px;
        }

        .zurück1 a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1 style="text-align: center;">Account löschen</h1>

    <?php if ($successMessage): ?>
        <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST" action="löschen.php" onsubmit="return confirmDeletion()">
        <label for="user_id">Benutzer auswählen:</label>
        <select name="user_id" id="user_id" required>
            <option value="">-- Benutzer auswählen --</option>
            <?php
            if ($isAdmin) {
                // Admin sieht alle Benutzer außer sich selbst
                $sql = 'SELECT id, username FROM benutzer WHERE id != :user_id';
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            } else {
                // Normale Benutzer sehen nur sich selbst
                $sql = 'SELECT id, username FROM benutzer WHERE id = :user_id';
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            }

            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['username']) . '</option>';
            }
            ?>
        </select>

        <p>Sind Sie sicher, dass Sie Ihren Account löschen möchten? Alle Ihre Daten werden dauerhaft gelöscht.</p>

        <button type="submit" name="submit">Ja, Account löschen</button>
    </form>

    <div class="zurück1">
        <a href="hauptseite.php">Zurück</a>
    </div>

    <script>
        function confirmDeletion() {
            var isAdmin = <?= json_encode($isAdmin) ?>;
            if (!isAdmin) {
                return confirm("Möchten Sie Ihren Account wirklich löschen? Dies kann nicht rückgängig gemacht werden.");
            }
            return true;
        }
    </script>

</body>
</html>