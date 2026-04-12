<?php
require_once('connection.php');


if ($_SERVER['REQUEST_METHOD'] == 'POST' || !empty($_GET)) {
    // Beispiel: Wenn die Daten per GET übergeben wurden
    $data = $_POST['submit_current_month'] ?? $_GET['submit_current_month'] ?? 'Kein Daten'; // Beispiel für ein Feld
    // Hier kannst du deine Logik für die Verarbeitung der Daten einfügen
    echo 'Daten empfangen: ' . $data;
}


$currentYear = date('Y');
$currentMonth = date('m');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Für den aktuellen Monat (ähnlich für den nächsten und übernächsten Monat)
    if (isset($_POST['submit_current_month'])) {
        saveAttendance($currentYear, $currentMonth);
    }
    if (isset($_POST['delete_current_month'])) {
        deleteAttendance($currentYear, $currentMonth);
    }
}

function saveAttendance($year, $month) {
    global $conn;
    
    // Durchlaufe alle Tage und Checkboxen für den Monat und speichere sie
    for ($day = 1; $day <= 31; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, "0", STR_PAD_LEFT);
        
        // Abruf der Checkbox-Werte aus dem Formular
        $checkbox_gray = isset($_POST["checkbox_gray_$date"]) ? 1 : 0;
        $checkbox_green = isset($_POST["checkbox_green_$date"]) ? 1 : 0;
        $week_checkbox_gray = isset($_POST["week_checkbox_gray_$date"]) ? 1 : 0;
        $week_checkbox_green = isset($_POST["week_checkbox_green_$date"]) ? 1 : 0;

        // Eintrag in die Datenbank
        $stmt = $conn->prepare("INSERT INTO attendance_plan (year, month, date, checkbox_gray, checkbox_green, week_checkbox_gray, week_checkbox_green) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$year, $month, $date, $checkbox_gray, $checkbox_green, $week_checkbox_gray, $week_checkbox_green]);
    }
}

function deleteAttendance($year, $month) {
    global $conn;
    
    // Lösche alle Einträge für den angegebenen Monat
    $stmt = $conn->prepare("DELETE FROM attendance_plan WHERE year = ? AND month = ?");
    $stmt->execute([$year, $month]);
}
?>