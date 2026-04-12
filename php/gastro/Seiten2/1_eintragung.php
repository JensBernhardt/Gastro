<link rel="stylesheet" href="1_essen.css">

<div>
    <fieldset class="zurück2">
        <legend>Hauptseite</legend>
        <a href="../hauptseite.php">
            <span>Zurück</span>
        </a>
    </fieldset>
</div>


<?php

// Verbindung zur Session und Datenbank herstellen
require_once('../session.php');
include_once 'connection.php';

// Dynamisches Abrufen des aktuellen Datums
$currentYear = date('Y');  // Aktuelles Jahr
$currentMonth = date('m'); // Aktueller Monat

// Arrays zum Speichern der ausgewählten Tage
$normaleTage = [];
$vegiTage = [];

// Überprüfen, ob das Formular abgesendet wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Durchlaufen aller POST-Parameter
    foreach ($_POST as $key => $value) {
        echo $key . ' Ist:' . $value . '</br>';

        // Prüfen, ob der Wert ungleich 0 ist
        if ($value != 0) {
            // Key anhand des Unterstrichs aufteilen, um Informationen zu Tag, Monat und Jahr zu extrahieren
            $splittedKey = explode('_', $key);
            echo '<p>' . implode(',', $splittedKey) . '</p>';

            // Unterscheidung zwischen normalem und vegetarischem Tag
            if ($splittedKey[0] == 'normal')
                array_push($normaleTage, $splittedKey);
            if ($splittedKey[0] == 'vegi')
                array_push($vegiTage, $splittedKey);
        }
    }
    echo "<pre>";
    var_dump($normaleTage);
    echo "</pre>";

    // Einfügen der normalen Tage in die Datenbank
    foreach ($normaleTage as $value) {

        //Einfügen der Normalen Tage in die Datenbank
        $stmt = $conn->prepare("INSERT OR IGNORE INTO attendance_plan(user_id, year, month, date, status, created_at, updated_at) VALUES(:sessionId, :year, :month, :date, :status, DATETIME('now'), DATETIME('now'))");
        $stmt->bindValue(":sessionId",  $_SESSION['user_id']);
        $stmt->bindValue(":year", (int)$value[3]);
        $stmt->bindValue(":month", (int)$value[2]);
        $stmt->bindValue(":date", $value[1] . '-' . $value[2] . '-' . $value[3]);
        $stmt->bindValue(":status", 1); // Status für normale Tage
        $stmt->execute();
    }
    
    // Einfügen der vegetarischen Tage in die Datenbank
    foreach ($vegiTage as $value) {

        // Einfugen der Vegi Tage in die Datenbank
        $stmt = $conn->prepare("INSERT OR IGNORE INTO attendance_plan(user_id, year, month, date, status, created_at, updated_at) VALUES(:sessionId, :year, :month, :date, :status, DATETIME('now'), DATETIME('now'))");
        $stmt->bindValue(":sessionId",  $_SESSION['user_id']);
        $stmt->bindValue(":year", (int)$value[3]);
        $stmt->bindValue(":month", (int)$value[2]);
        $stmt->bindValue(":date", $value[1] . '-' . $value[2] . '-' . $value[3]);
        $stmt->bindValue(":status", 2); // Status für vegetarische Tage
        $stmt->execute();
    }
}





// Funktion zum Einfügen oder Ersetzen des Status in der Datenbank
function saveAttendanceStatus($conn, $userId, $value, $newStatus) {
    $year = (int)$value[3];
    $month = (int)$value[2];
    $date = $value[1] . '-' . $value[2] . '-' . $value[3];
    
    
    // Überprüfen, ob bereits ein Eintrag für das Datum existiert
    $stmt = $conn->prepare("SELECT status FROM attendance_plan WHERE user_id = :userId AND year = :year AND month = :month AND date = :date");
    $stmt->bindValue(":userId", $userId);
    $stmt->bindValue(":year", $year);
    $stmt->bindValue(":month", $month);
    $stmt->bindValue(":date", $date);
    $stmt->execute();
    $existingStatus = $stmt->fetchColumn();

    // Vorherige Einträge sicher löschen
    $stmt = $conn->prepare("DELETE FROM attendance_plan WHERE user_id = :userId AND year = :year AND month = :month AND date = :date");
    $stmt->bindValue(":userId", $userId);
    $stmt->bindValue(":year", $year);
    $stmt->bindValue(":month", $month);
    $stmt->bindValue(":date", $date);
    $stmt->execute();

    // Neuen Status nur speichern, wenn er nicht leer ist
    if (!empty($newStatus)) {
        $stmt = $conn->prepare("INSERT INTO attendance_plan(user_id, year, month, date, status, created_at, updated_at) 
                                VALUES(:userId, :year, :month, :date, :status, DATETIME('now'), DATETIME('now'))");
        $stmt->bindValue(":userId", $userId);
        $stmt->bindValue(":year", $year);
        $stmt->bindValue(":month", $month);
        $stmt->bindValue(":date", $date);
        $stmt->bindValue(":status", $newStatus);
        $stmt->execute();
    }
}





// Anwesenheit speichern
$userId = $_SESSION['user_id'];

foreach ($normaleTage as $value) {
    saveAttendanceStatus($conn, $userId, $value, 1);
}

foreach ($vegiTage as $value) {
    saveAttendanceStatus($conn, $userId, $value, 2);
}


// Funktion zum Überprüfen, ob ein normaler Tag bereits in der Datenbank gespeichert ist
function isNormalChecked($day, $month, $year, $normaleTage)
{

    if (is_array($normaleTage) && count($normaleTage) !== 0) {
        foreach ($normaleTage as $eintrag) {
            if (
                $eintrag['date'] == $day . '-' . $month . '-' . $year &&
                $eintrag['month'] == $month &&
                $eintrag['year'] == $year &&
                (int)$eintrag['status'] === 1
            ) { // Status für normale Tage
                return 1;
            }
        }
    }
    return false;
}

// Funktion zum Überprüfen, ob ein vegetarischer Tag bereits in der Datenbank gespeichert ist
function isVegiChecked($day, $month, $year, $vegiTage)
{
    if (is_array($vegiTage) && count($vegiTage) !== 0) {
        foreach ($vegiTage as $eintrag) {
            if (
                $eintrag['date'] == $day . '-' . $month . '-' . $year &&
                $eintrag['month'] == $month &&
                $eintrag['year'] == $year &&
                (int)$eintrag['status'] === 2
            ) { // Status für vegetarische
                return 2;
            }
        }
    }
    return false;
}




// Funktion zum Erstellen des Monatsplans
function getNormalAttendece($year, $month)
{
    global $conn;
    $gespeicherteNormaleTage = [];
    // Abfrage der gespeicherten Anwesenheit aus der Datenbank
    $stmt = $conn->prepare("SELECT year,month,date, status FROM attendance_plan WHERE user_id=:user_id and year =:year and month =:month and status = :status");
    $stmt->bindValue(":user_id", $_SESSION['user_id']);
    $stmt->bindValue(":year", (int)$year);
    $stmt->bindValue(":month", (int)$month);
    $stmt->bindValue(":status", 1);
    $stmt->execute();



    // Ergebnisse in Array speichern
    while ($value = $stmt->fetch(PDO::FETCH_BOTH)) {
        $gespeicherteNormaleTage[] = $value;
    }
    return $gespeicherteNormaleTage;
}

// Funktion zum Erstellen des Monatsplans
function getVeggiAttendece($year, $month)
{
    global $conn;
    $gespeicherteVegiTage = [];

    // Abfrage der gespeicherten Anwesenheit aus der Datenbank
    $stmt = $conn->prepare("SELECT year,month,date, status FROM attendance_plan WHERE user_id=:user_id and year =:year and month =:month and status = :status");
    $stmt->bindValue(":user_id", $_SESSION['user_id']);
    $stmt->bindValue(":year", (int)$year);
    $stmt->bindValue(":month", (int)$month);
    $stmt->bindValue(":status", 2);
    $stmt->execute();

    // Ergebnisse in Array speichern
    while ($value = $stmt->fetch(PDO::FETCH_BOTH)) {
        $gespeicherteVegiTage[] = $value;
    }
    return $gespeicherteVegiTage;
}

function generateMonthPlan($year, $month)
{
    $gespeicherteNormaleTage = getNormalAttendece($year, $month);
    $gespeicherteVegiTage = getVeggiAttendece($year, $month);

    // Startdatum und Anzahl der Tage im Monat berechnen
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
    $maxRows = ceil(($daysInMonth + $daysFromPrevMonth) / 7);

    // Ausgabe des Kalenders
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
            $todayClass = ($date == $today) ? ' style="background-color: lightblue;"' : '';

            echo "<td class='$isToday $isHolidayClass $isCurrentMonth' $todayClass>";
            echo "<div class='day-number'>$day</div>";

            if ($isHoliday) {
                echo "<div class='holiday-emoji' title='" . $holidays[$date] . "'>🎉</div>";
            }

            // Generieren der Checkbox-Namen
            $resultStringvegi = 'vegi_' . $day . '_' . $monthNum . '_' . $year;
            $resultStringall = 'normal_' . $day . '_' . $monthNum . '_' . $year;

            echo "<div class='checkboxes'>";
            // Überprüfung ob bereits gespeichert, um Checkboxen entsprechend anzuzeigen; NormaleTage, Status 1
            echo "<input type='hidden' id='greyphidden_$resultStringall' class='checkbox-gray' name='$resultStringall' data-week='$weekIndex' value=0>";
            echo "<input type='checkbox' id='greyp_$resultStringall' class='checkbox-gray' name='$resultStringall' onclick='toggleGreen(this, $weekIndex, $j)' data-week='$weekIndex' " . (isNormalChecked($day, $month, $year, $gespeicherteNormaleTage) ? " checked" : "") . ">";

            // Überprüfung ob bereits gespeichert, um Checkboxen entsprechend anzuzeigen; Vegi, Status 2
            echo "<input type='hidden' id='greenphidden_$resultStringvegi' class='checkbox-green' name='$resultStringvegi' data-week='$weekIndex' value=0>";
            echo "<input type='checkbox' id='greenp_$resultStringvegi' class='checkbox-green' name='$resultStringvegi' onclick='toggleGreen(this)' data-week='$weekIndex' " . (isVegiChecked($day, $month, $year, $gespeicherteVegiTage) ? " checked" : "") . ">";
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
        $startOfWeek = strtotime("+$i week", $startDate);
        $weekNumber = date('W', $startOfWeek);
        echo "<div class='week-cell'>KW $weekNumber";
        echo "<div class='checkboxes'>";

        echo "<input type='checkbox' class='week-checkbox-gray' onclick='toggleWeekGray(this, $i, -1)'>";


        echo "<input type='checkbox' class='week-checkbox-green' onclick='toggleWeekGreen(this, $i, -1)'>";
        echo "</div></div>";
    }
    echo "</div></div>";
}
?>



<script>
    function toggleWeekGray(checkbox, weekIndex, dayIndex) {
        const grayCheckboxes = document.querySelectorAll(`.checkbox-gray[data-week='${weekIndex}']`);
        if (dayIndex === -1) {
            for (let i = 0; i < grayCheckboxes.length; i++) {
                if (i % 14 < 10) { // Montag bis Freitag
                    grayCheckboxes[i].checked = checkbox.checked;
                    localStorage.setItem(grayCheckboxes[i].id, checkbox.checked);
                }
            }
        }
    }

    function toggleWeekGreen(checkbox, weekIndex, dayIndex) {
        const greenCheckboxes = document.querySelectorAll(`.checkbox-green[data-week='${weekIndex}']`);
        if (dayIndex === -1) {
            for (let i = 0; i < greenCheckboxes.length; i++) {
                if (i % 14 < 10) { // Montag bis Freitag
                    greenCheckboxes[i].checked = checkbox.checked;
                    localStorage.setItem(greenCheckboxes[i].id, checkbox.checked);
                }
            }
        }
    }

    function toggleGreen(checkbox) {
       var id = checkbox.id;
       if (checkbox.checked){
            if(id.includes("greyp_normal")){
                var otherCheckbox = document.getElementById(id.replace("greyp_normal","greenp_vegi"));
                otherCheckbox.checked = false;
            }
            else{
                var otherCheckbox = document.getElementById(id.replace("greenp_vegi","greyp_normal"));
                otherCheckbox.checked = false;
            }
       }
    }
  
    // function toggleWeekGreen(checkbox) {
    //    var id = checkbox.id;
    //    if (checkbox.checked){
    //         if(id.includes("greyp_normal")){
    //             var otherCheckbox = document.getElementById(id.replace("week-checkbox-gray","week-checkbox-green"));
    //             otherCheckbox.checked = false;
    //         }
    //         else{
    //             var otherCheckbox = document.getElementById(id.replace("week-checkbox-green","week-checkbox-gray"));
    //             otherCheckbox.checked = false;
    //         }
    //    }
    // }

</script>


<form method="POST" action="1_eintragung.php">
    <h3>Aktueller Monat</h3>
    <div class="calendar-container">
        <div class="calendar">
            <?php generateMonthPlan($currentYear, $currentMonth); ?>

        </div>
    </div>
    <button class="abgabe" type="submit" name="submit_current_month" class="submit-button">Abgabe</button>

</form>