

<?php
require_once('../session.php'); // Lädt die Session, um den Benutzerstatus zu überprüfen
include_once 'connection.php'; // Verbindet sich mit der Datenbank

// Verhindert das Caching, um die Wiederholung von Formularaktionen beim Zurückkehren zu verhindern
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    die("Nicht eingeloggt!"); // Wenn der Benutzer nicht eingeloggt ist, wird der Zugriff verweigert
}

$user_id = $_SESSION['user_id']; // Benutzer-ID aus der Session holen

// Abfrage der Benutzerrolle
$query = "SELECT r.name FROM benutzer b
          JOIN benutzer_rollen br ON b.id = br.benutzer_id
          JOIN rollen r ON br.rolle_id = r.id
          WHERE b.id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$rolle = $stmt->fetch(PDO::FETCH_ASSOC); // Hole die Rolle des Benutzers

// Überprüfen, ob der Benutzer Admin ist
$isAdmin = $rolle['name'] === 'Admin';

// Essen speichern (nur wenn das Formular abgesendet wurde)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['meal'], $_POST['date']) && !empty($_POST['meal']) && !empty($_POST['date'])) {
    // Sicherstellen, dass das Formular abgesendet wurde
    if (isset($_POST['submit_meal'])) {
        $meal = trim($_POST['meal']); // Essenswunsch aus dem Formular
        $date = $_POST['date']; // Datum aus dem Formular

        // Eintrag in die Essensplan-Tabelle speichern
        $stmt = $conn->prepare("INSERT INTO essensplan (user_id, datum, meal) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $date, $meal]);

        // Redirect, um zu verhindern, dass das Formular erneut abgeschickt wird, wenn der Benutzer zurückgeht
        header("Location: 3_kalender.php");
        exit(); // Verhindert, dass der restliche Code ausgeführt wird
    }
}

// Einträge abrufen
$entries = []; 
if ($isAdmin) {
  // Admin sieht alle Einträge
  $stmt = $conn->prepare("SELECT id, datum, meal FROM essensplan ORDER BY datum ASC");
} else {
  // Normale Benutzer sehen nur ihre eigenen Einträge
  $stmt = $conn->prepare("SELECT id, datum, meal FROM essensplan WHERE user_id = :user_id ORDER BY datum ASC");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}
$stmt->execute();
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC); // Alle Essenswünsche des Benutzers oder alle Einträge für Admin abrufen

// Löschen eines Eintrags
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id']; // ID des zu löschenden Eintrags

    // Löschen des Eintrags aus der Datenbank
    $stmt = $conn->prepare("DELETE FROM essensplan WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);

    // Antwort zurück an JavaScript
    echo json_encode(['status' => 'success']);
    exit();
}







// Angemeldete Benutzer-ID aus der Session holen
$user_id = $_SESSION['user_id']; // Benutzer-ID aus der Session holen

// Abfrage der Benutzerrolle
$query_role = "SELECT r.name FROM benutzer_rollen br
               JOIN rollen r ON br.rolle_id = r.id
               WHERE br.benutzer_id = :user_id";
$stmt_role = $conn->prepare($query_role);
$stmt_role->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_role->execute();
$user_role = $stmt_role->fetchColumn(); // Rolle des Benutzers abrufen

// Debug: Ausgabe der Benutzerrolle (prüfen, ob es tatsächlich 'Admin' ist)
echo "Benutzerrolle: " . htmlspecialchars($user_role); // Zum Testen, ob der Wert 'Admin' korrekt ist

// SQL für Admin oder normalen Benutzer
if ($user_role === 'Admin') { // Admin sieht alle Einträge
    $query = "SELECT * FROM essensplan"; // Alle Einträge anzeigen
    $stmt = $conn->prepare($query);
} else { // Normale Benutzer sehen nur ihre eigenen Einträge
    $query = "SELECT * FROM essensplan WHERE user_id = :user_id"; // Einträge nur des Benutzers anzeigen
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}

$stmt->execute();
$essensplan = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta name="description" content="Essensplan Verwaltung">
    <title>EssensPlan</title>
    <link rel="stylesheet" href="3_essen.css"> <!-- Verlinkt das CSS für das Design -->
</head>
<body>

<div>
    <fieldset class="zurück2">
        <legend>Hauptseite</legend>
        <a href="../hauptseite.php">
            <span>Zurück</span>
        </a>
    </fieldset>
</div>

<div class="calendar">
    <div class="header">
        <!-- Schaltflächen für Monatsnavigation -->
        <button onclick="prevMonth()">&#9665;</button>
        <h2 id="monthYear"></h2>
        <button onclick="nextMonth()">&#9655;</button>
    </div>
    <div class="days" id="calendarDays"></div> <!-- Der Kalender wird hier gerendert -->
</div>

<div>
    <p>Ausgewähltes Datum: <span id="selectedDate">Kein Datum gewählt</span></p>
    <form id="mealForm" method="POST" action="3_kalender.php">
        <!-- Formular zum Hinzufügen eines Essenswunsches -->
        <input type="hidden" id="dateInput" name="date">
        <input type="text" id="mealInput" name="meal" placeholder="Essenswunsch eingeben" required>
        <button type="submit" name="submit_meal">Speichern</button>
    </form>
</div>

<div class="entries" id="mealEntries">
    <?php foreach ($entries as $entry): ?>
        <div class="entry" id="entry-<?= $entry['id'] ?>">
            <?= htmlspecialchars($entry['datum']) ?>: <?= htmlspecialchars($entry['meal']) ?>
            <!-- Löschen-Button für den Essenswunsch -->
            <button class="delete-btn" onclick="deleteMeal(<?= $entry['id'] ?>)">Löschen</button>
        </div>
    <?php endforeach; ?>
</div>

<script>
    let date = new Date(); // Erstellt ein Date-Objekt für das aktuelle Datum
    let selectedDay = null; // Hält den ausgewählten Tag

    // Funktion zum Rendern des Kalenders
    function renderCalendar() {
        const monthYear = document.getElementById("monthYear");
        const calendarDays = document.getElementById("calendarDays");
        calendarDays.innerHTML = ""; // Kalender zurücksetzen

        date.setDate(1); // Setzt das Datum auf den ersten Tag des aktuellen Monats
        const firstDayIndex = date.getDay(); // Wochentag des ersten Tages im Monat
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate(); // Letzter Tag im Monat

        monthYear.textContent = date.toLocaleString("de-DE", { month: "long", year: "numeric" });

        // Leere Felder für Tage vor dem ersten Tag des Monats
        for (let i = 0; i < firstDayIndex; i++) {
            calendarDays.appendChild(document.createElement("div"));
        }

        // Kalender-Tage generieren
        for (let i = 1; i <= lastDay; i++) {
            const dayDiv = document.createElement("div");
            dayDiv.classList.add("day");
            dayDiv.textContent = i;
            dayDiv.onclick = function () { selectDate(i); }; // Beim Klick auf den Tag
            if (selectedDay === i) dayDiv.classList.add("selected"); // Hervorhebung des ausgewählten Tags
            calendarDays.appendChild(dayDiv);
        }
    }

    // Funktion zum Wechseln zum vorherigen Monat
    function prevMonth() {
        date.setMonth(date.getMonth() - 1);
        selectedDay = null;
        renderCalendar();
    }

    // Funktion zum Wechseln zum nächsten Monat
    function nextMonth() {
        date.setMonth(date.getMonth() + 1);
        selectedDay = null;
        renderCalendar();
    }

    // Funktion zur Auswahl eines Datums
    function selectDate(day) {
        selectedDay = day;
        const fullDate = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, "0")}-${day.toString().padStart(2, "0")}`;
        document.getElementById("selectedDate").textContent = fullDate; // Datum im Formular anzeigen
        document.getElementById("dateInput").value = fullDate;
        renderCalendar();
    }

    // Funktion zum Löschen eines Essenswunsches
    function deleteMeal(id) {
        fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "delete_id=" + id // Senden der ID des zu löschenden Eintrags
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Den Eintrag aus der Seite entfernen
                const entryDiv = document.getElementById("entry-" + id);
                entryDiv.remove();
            } else {
                console.error("Fehler beim Löschen");
            }
        })
        .catch(error => console.error('Fehler beim Löschen:', error));
    }

    renderCalendar(); // Den Kalender beim Laden der Seite rendern
</script>

</body>
</html>