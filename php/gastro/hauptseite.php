


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.6, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <meta name="description" content="">
    <meta name="author" content="Dein Name">
    <meta name="keywords" content="">

    <title>EssensPlan</title>

    <link rel="stylesheet" href="hauptseite.css" >
</head>

<body class="body1">



<!--Ab hier beginnt die Anzeige was für Daten von User etc angezeigt werden wenn er in seiner Session ist-->

<?php
require_once('session.php'); // Stellt sicher, dass der Benutzer eingeloggt ist

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once(__DIR__ . '/Seiten2/connection.php'); // Datenbankverbindung laden

$user_id = $_SESSION['user_id'];

// Benutzerrolle abrufen
$sql = "SELECT r.name AS rolle, r.beschreibung AS rollenbeschreibung 
        FROM rollen r
        JOIN benutzer_rollen br ON r.id = br.rolle_id
        WHERE br.benutzer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if ($role) {
    $_SESSION['rolle'] = $role['rolle'];
    $_SESSION['rollenbeschreibung'] = $role['rollenbeschreibung'];
}

// Berechtigungen des Benutzers abfragen
$sql_perms = "SELECT be.name 
              FROM berechtigungen be
              JOIN rollen_berechtigungen rb ON be.id = rb.berechtigung_id
              JOIN benutzer_rollen br ON rb.rolle_id = br.rolle_id
              WHERE br.benutzer_id = ?";
$stmt_perms = $conn->prepare($sql_perms);
$stmt_perms->execute([$user_id]);
$_SESSION['permissions'] = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="user-info-box">
    <p><strong>ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
    <p><strong>Benutzername:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    <p><strong>Rolle:</strong> <?php echo htmlspecialchars($_SESSION['rolle'] ?? 'Keine Rolle'); ?></p>
    <p><strong>Rollenbeschreibung:</strong> <?php echo htmlspecialchars($_SESSION['rollenbeschreibung'] ?? 'Keine Beschreibung'); ?></p>
</div>



<!-- Die 4 Pannel, linke Seite -->


<div >  
<fieldset class="seite1">
<legend>Hier kannst du dich eintragen.</legend>
<a href="Seiten2/1_eintragung.php">
<span>Zum Kreuze setzen</span>  
</a>
</fieldset>
</div>

<div >
<fieldset class="seite2">
<legend>Hier kommst du zum aktuellen EssensPlan, dieser Woche.</legend>
<a href="Seiten2/2_essensPlan.php"> 
<span>Zum WochenPlan</span>
</a>
</fieldset>
</div>

<div >
<fieldset class="seite3">
<legend>Hier gehts es zu den aktuellen Essenwünschen.</legend>
<a href="Seiten2/3_kalender.php">
<span>Essenswunsch</span>
</a>
</fieldset>
</div>

<div >
<fieldset class="seite4">
<legend>Hier kannst du Rezepte anschauen.</legend>
<a href="Seiten2/4_wunsch.php">
<span>Zur Rezeptliste</span>
</a>
</fieldset>
</div>



<!--Logout Knopf-->

<div class="logout1">
<h2 class="logout"></h2>
    <form method="POST" action="logout.php">

        <button class="loghhhgg" type="submit" name="submit">Logout</button>
    </form>
</div>


<!--Rolle Zuweisen Knopf-->

<?php
if ($_SESSION['rolle'] === 'Admin') {
    ?>
    <form action="assign_role.php" method="get" style="display: inline;">
        <button type="submit" class="btn">Rolle zuweisen</button>
    </form>
    <?php
} else {
    echo "";
}
?>

<!--Account löschen Knopf-->

<div class="löschen1">
<h2 class="löschen2"></h2>
    <form method="POST" action="löschen.php">

        <button class="loghhhgg" type="submit" name="submit">Account löschen</button>
    </form>
</div>



</body>
</html>

