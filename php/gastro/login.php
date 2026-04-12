<?php
require_once(__DIR__ . '/Seiten2/connection.php');

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["passwort"] ?? '';

    if (empty(trim($username)) || empty(trim($password))) {
        $error = "Bitte Benutzername und Passwort eingeben.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, passwort FROM benutzer WHERE username = :username");
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["passwort"])) {
            session_start();
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            header("Location: hauptseite.php");
            exit();
        } else {
            $error = "Login fehlgeschlagen: Benutzername oder Passwort ist falsch.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"],
        input[type="password"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box; /* Ensures padding is inside the element */
        }

        button {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        p {
            color: red;
            font-size: 14px;
        }

        .regi {
            margin-top: 20px;
            border-radius: 10px;
            
        }

        .regi a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            
        }

        .regi a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="post">
            <input type="text" name="username" placeholder="Benutzername" required>
            <input type="password" name="passwort" placeholder="Passwort" required>
            <button type="submit">Login</button>
        </form>
        <?php if ($error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <div class="regi">
        <a href="registrierung.php">
            <fieldset>
                
                Zur Registrierung</a>
            </fieldset>
        </div>
    </div>
</body>
</html>