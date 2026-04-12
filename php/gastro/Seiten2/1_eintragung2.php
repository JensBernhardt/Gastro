<?php
require_once('../session.php');
include_once 'connection.php';

// Dynamisches Abrufen des aktuellen Datums
$currentYear = date('Y');  // Aktuelles Jahr
$currentMonth = date('m'); // Aktueller Monat

// Berechne den nächsten Monat
$nextMonthTimestamp = strtotime('+1 month', strtotime("$currentYear-$currentMonth-01"));
$nextYear = date('Y', $nextMonthTimestamp);
$nextMonth = date('m', $nextMonthTimestamp);

// Aufruf der Funktion für den nächsten Monat
generateMonthPlan($nextYear, $nextMonth);

function generateMonthPlan($year, $month) {
    $firstDayOfMonth = strtotime("$year-$month-01");
    $firstDayWeekday = date('N', $firstDayOfMonth);
    $daysInMonth = date('t', $firstDayOfMonth);
    $daysFromPrevMonth = $firstDayWeekday - 1;
    $startDate = strtotime("-$daysFromPrevMonth days", $firstDayOfMonth);

    // Feiertage (Beispielhaft: Feste können später dynamisch hinzugefügt werden)
    $holidays = [
        '2025-02-14' => 'Valentinstag',
        '2025-12-25' => 'Weihnachten',
    ];

    echo "<div class='calendar-container'>";
    echo "<div class='calendar'>";
    echo "<div class='month-title'>" . date('F Y', $firstDayOfMonth) . "</div>";
    echo "<table>";
    echo "<tr><th>Mo</th><th>Di</th><th>Mi</th><th>Do</th><th>Fr</th><th>Sa</th><th>So</th></tr>";
    
    $currentDate = $startDate;
    $today = date('Y-m-d');
    $weekIndex = 0;
    
    // Berechnung der vollen Wochen im Monat
    $maxRows = ceil(($daysInMonth + $daysFromPrevMonth) / 7); // Maximale Anzahl der Reihen
    for ($i = 0; $i < $maxRows; $i++) {
        echo "<tr>";
        for ($j = 0; $j < 7; $j++) {
            $day = date('j', $currentDate);
            $date = date('Y-m-d', $currentDate);
            $monthNum = date('m', $currentDate);
            $currentMonthNum = date('m', $firstDayOfMonth);
            $isHoliday = isset($holidays[$date]);

            $isToday = ($date == $today) ? 'today' : '';
            $isHolidayClass = $isHoliday ? 'holiday' : '';
            $isCurrentMonth = ($monthNum == $currentMonthNum) ? 'current-month' : 'other-month';

            echo "<td class='$isToday $isHolidayClass $isCurrentMonth'>";
            echo "<div class='day-number'>$day</div>";
            
            if ($isHoliday) {
                echo "<div class='holiday-emoji' title='" . $holidays[$date] . "'>🎉</div>";
            }
            
            echo "<div class='checkboxes'>";
            echo "<input type='checkbox' class='checkbox-gray' data-week='$weekIndex' onclick='toggleWeekGray(this, $weekIndex, $j)'>";
            echo "<input type='checkbox' class='checkbox-green' data-week='$weekIndex' onclick='toggleWeekGreen(this, $weekIndex, $j)'>";
            echo "</div></td>";
            
            $currentDate = strtotime('+1 day', $currentDate);
        }
        echo "</tr>";
        $weekIndex++;
    }
    echo "</table></div>";

    // Woche-Spalte außerhalb des Kalenders mit Checkboxen
    echo "<div class='week-column'>";
    echo "<div class='week-header'>Woche</div>";
    for ($i = 0; $i < $weekIndex; $i++) {
        // Berechne die Kalenderwoche basierend auf dem Startdatum der Woche
        $startOfWeek = strtotime("+$i week", $startDate);
        $weekNumber = date('W', $startOfWeek); // Kalenderwoche ermitteln
        echo "<div class='week-cell'>KW $weekNumber";
        echo "<div class='checkboxes'>";
        echo "<input type='checkbox' class='week-checkbox-gray' onclick='toggleWeekGray(this, $i, -1)'>";
        echo "<input type='checkbox' class='week-checkbox-green' onclick='toggleWeekGreen(this, $i, -1)'>";
        echo "</div></div>";
    }
    echo "</div></div>";
}
?>

<?php
// Überprüfen, ob das Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Weiterleitung der Formulardaten an 1_save.php
    header('Location: 1_save.php?' . http_build_query($_POST));
    exit; // Beenden, damit keine weitere Ausgabe erfolgt
}
?>

<form method="POST" action="1_eintragung2.php">
    <h3>Nächster Monat</h3>
    <div class="calendar-container">
        <div class="calendar">
            <!-- Monatstabelle hier, wie im Originalcode -->
        </div>
    </div>
    <button type="submit" name="submit_next_month" class="submit-button">Abgabe</button>
    <button type="submit" name="delete_next_month" class="delete-button">Löschen</button>
</form>

<script>
// Funktion zum Verhindern, dass beide Checkboxen gleichzeitig aktiviert werden
function toggleCheckbox(checkbox, otherCheckboxClass) {
    const tdElement = checkbox.closest('td');
    const otherCheckbox = tdElement.querySelector(otherCheckboxClass);
    
    if (checkbox.checked) {
        otherCheckbox.checked = false;
    }
}

function toggleWeekGray(checkbox, weekIndex, dayIndex) {
    const grayCheckboxes = document.querySelectorAll(`.checkbox-gray[data-week='${weekIndex}']`);
    if (dayIndex === -1) {
        for (let i = 0; i < grayCheckboxes.length; i++) {
            if (i % 7 < 5) grayCheckboxes[i].checked = checkbox.checked;
        }
    }
}

function toggleWeekGreen(checkbox, weekIndex, dayIndex) {
    const greenCheckboxes = document.querySelectorAll(`.checkbox-green[data-week='${weekIndex}']`);
    if (dayIndex === -1) {
        for (let i = 0; i < greenCheckboxes.length; i++) {
            if (i % 7 < 5) greenCheckboxes[i].checked = checkbox.checked;
        }
    }
}
</script>

<style>
    .calendar-container {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .calendar {
        width: 75%;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .month-title {
        font-size: 28px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th, td {
        width: 14.28%;
        height: 80px;
        text-align: center;
        vertical-align: middle;
        font-size: 18px;
        font-weight: 500;
    }

    th {
        background-color: #f4f4f4;
        padding: 10px;
        font-size: 16px;
    }

    td {
        position: relative;
        padding: 10px;
    }

    .day-number {
        font-size: 20px;
        font-weight: 600;
    }

    /* Styling für den heutigen Tag */
    .today {
        background-color: #4c8ff5; /* Blau für den aktuellen Tag */
        border-radius: 5px;
        font-weight: bold;
        color: white;
    }

    /* Styling für Feiertage */
    .holiday {
        background-color: #ffd700; /* Goldenes Hintergrund für Feiertage */
        border-radius: 5px;
    }

    /* Feiertag Emoji */
    .holiday-emoji {
        position: absolute;
        bottom: 5px;
        right: 5px;
        font-size: 18px;
    }

    /* Styling für Tage des aktuellen Monats */
    .current-month {
        background-color: #ffffff;
    }

    /* Styling für andere Monate */
    .other-month {
        background-color: #f9f9f9;
    }

    /* Checkboxen */
    .checkboxes {
        display: flex;
        justify-content: space-around;
        margin-top: 10px;
    }

    .checkbox-gray, .checkbox-green {
        width: 25px;
        height: 25px;
        cursor: pointer;
        border-radius: 5px;
    }

    .checkbox-green {
        border: 2px solid green;
        box-shadow: 0 0 5px green;
    }

    .checkbox-gray {
        border: 2px solid gray;
        box-shadow: 0 0 5px gray;
    }

    /* Styling für die Woche-Spalte */
    .week-column {
        width: 23%;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    .week-header {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
        text-align: center;
    }

    .week-cell {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        background-color: #f7f7f7;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
    }

    .week-checkbox-gray, .week-checkbox-green {
        width: 22px;
        height: 22px;
        cursor: pointer;
    }

    .week-checkbox-gray {
        border: 2px solid gray;
        box-shadow: 0 0 5px gray;
    }

    .week-checkbox-green {
        border: 2px solid green;
        box-shadow: 0 0 5px green;
    }

    /* Hover-Effekte für Feiertage */
    td.holiday:hover {
        background-color: #ffb300; /* Helleres Gold bei Hover */
        cursor: pointer;
    }

    /* Stile für den Kalender insgesamt */
    table td, .week-column .week-cell {
        transition: all 0.3s ease;
    }

    table td:hover, .week-column .week-cell:hover {
        background-color: #f0f0f0;
        cursor: pointer;
    }
</style>