

<?php
require_once('../session.php');
include_once 'connection.php';

// Berechnung der Wochentage
$currentDate = new DateTime();
$currentDate->modify('monday this week');
$thisMonday = $currentDate->format('d-m-Y');
$currentDate->modify('friday this week');
$thisFriday = $currentDate->format('d-m-Y');

$userId = $_SESSION['user_id']; // ID des eingeloggten Benutzers

// Wenn das Formular abgesendet wird, um Essensplan zu speichern
if (isset($_POST['abgabe'])) {
    $montag = $_POST['Montag'] ?? '';
    $dienstag = $_POST['Dienstag'] ?? '';
    $mittwoch = $_POST['Mittwoch'] ?? '';
    $donnerstag = $_POST['Donnerstag'] ?? '';
    $freitag = $_POST['Freitag'] ?? '';
    
    // Überprüfen, ob der Eintrag bereits existiert
    $stmt = $conn->prepare("SELECT COUNT(*) FROM essensplan1 WHERE user_id = :user_id AND woche1 = 1");
    $stmt->execute([':user_id' => $userId]);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        // Daten existieren, also UPDATE durchführen
        $stmt = $conn->prepare("UPDATE essensplan1 SET Montag = :Montag, Dienstag = :Dienstag, Mittwoch = :Mittwoch, Donnerstag = :Donnerstag, Freitag = :Freitag WHERE user_id = :user_id AND woche1 = 1");
        $stmt->execute([
            ':user_id' => $userId,
            ':Montag' => $montag,
            ':Dienstag' => $dienstag,
            ':Mittwoch' => $mittwoch,
            ':Donnerstag' => $donnerstag,
            ':Freitag' => $freitag
        ]);
    } else {
        // Eintrag existiert noch nicht, also INSERT durchführen
        $stmt = $conn->prepare("INSERT INTO essensplan1 (user_id, woche1, Montag, Dienstag, Mittwoch, Donnerstag, Freitag)
                                VALUES (:user_id, 1, :Montag, :Dienstag, :Mittwoch, :Donnerstag, :Freitag)");
        $stmt->execute([
            ':user_id' => $userId,
            ':Montag' => $montag,
            ':Dienstag' => $dienstag,
            ':Mittwoch' => $mittwoch,
            ':Donnerstag' => $donnerstag,
            ':Freitag' => $freitag
        ]);
    }
}

// Wenn der "Löschen"-Button gedrückt wird, die Daten löschen
if (isset($_POST['loeschen'])) {
    $stmt = $conn->prepare("DELETE FROM essensplan1 WHERE user_id = :user_id AND woche1 = 1");
    $stmt->execute([':user_id' => $userId]);
}

// Abfrage, um die gespeicherten Werte aus der Datenbank zu holen
$stmt = $conn->prepare("SELECT * FROM essensplan1 WHERE user_id = :user_id AND woche1 = 1");
$stmt->execute([':user_id' => $userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Wenn Daten vorhanden sind, fülle die Formularfelder mit den gespeicherten Werten
$hauptspeise1 = $data['Montag'] ?? '';
$hauptspeise2 = $data['Dienstag'] ?? '';
$nachspeise = $data['Mittwoch'] ?? '';
$zutatenliste = $data['Donnerstag'] ?? '';
$freitag = $data['Freitag'] ?? '';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8, user-scalable=no">

    <title>Wochenplan für Woche 1</title>
    

    <link rel="stylesheet" href="2_essen.css">
    <link href="https://fonts.googleapis.com/css2?family=Shadows+Into+Light&display=swap" rel="stylesheet">
</head>
<body class="body3">

<div>
    <fieldset class="zurück2">
        <legend>Hauptseite</legend>
        <a href="../hauptseite.php">
            <span>Zurück</span>
        </a>
    </fieldset>
</div>

<div class="kreuz">
    <a href="2_essensPlanL.php">
        <button class="arrow-left">←</button>
    </a>
    <a href="2_essensPlanR.php">
        <button class="arrow-right">→</button>
    </a>
</div>

<!-- Datumsausgabe -->
<h2>Wochenplan der aktuellen Woche: vom
    <input class="date1" type="text" value="<?php echo $thisMonday; ?>" readonly>
    bis zum
    <input class="date1" type="text" value="<?php echo $thisFriday; ?>" readonly>
</h2>

<!-- Essensplan für Woche 1 -->
 
<form method="post" action="">
    <table class="essensPlan" border="1">
        <thead>
            <tr>
                <th>Montag:</th>
                <th>Dienstag:</th>
                <th>Mittwoch:</th>
                <th>Donnerstag:</th>
                <th>Freitag:</th>
            </tr>
        </thead>
        <tbody>
            <tr>
            <div class="alles">
                <td><textarea class="tex1" name="Montag" rows="4" <?php echo ($_SESSION['rolle'] !== 'Admin') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($hauptspeise1); ?></textarea></td>
                <td><textarea class="tex2" name="Dienstag" rows="4" <?php echo ($_SESSION['rolle'] !== 'Admin') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($hauptspeise2); ?></textarea></td>
                <td><textarea class="tex3" name="Mittwoch" rows="4" <?php echo ($_SESSION['rolle'] !== 'Admin') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($nachspeise); ?></textarea></td>
                <td><textarea class="tex4" name="Donnerstag" rows="4" <?php echo ($_SESSION['rolle'] !== 'Admin') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($zutatenliste); ?></textarea></td>
                <td><textarea class="tex5" name="Freitag" rows="4" <?php echo ($_SESSION['rolle'] !== 'Admin') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($freitag); ?></textarea></td>
                </div>
            </tr>
            <tr>
                <td colspan="5">
                    <?php if ($_SESSION['rolle'] === 'Admin') { ?>
                        <button class="abgabe" type="submit" name="abgabe">Abgabe</button>
                        <button class="löschen" type="submit" name="loeschen">Löschen</button>
                    <?php } ?>
                </td>
            </tr>
        </tbody>
    </table>
</form>
           




</body>
</html>