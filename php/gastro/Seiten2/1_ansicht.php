<?php

//require_once('1_save.php');
// require_once('../session.php');
include_once 'connection.php';

// Dynamisches Abrufen des aktuellen Datums
$currentYear = date('Y');  // Aktuelles Jahr
$currentMonth = date('m'); // Aktueller Monat


function getNormalAttendance($date)
{
    global $conn;
    $normalCount = 0;
    $vegiCount = 0;
    
    $stmt = $conn->prepare("SELECT status, count(1) AS anzahl
                            FROM attendance_plan 
                            WHERE status IN(1, 2) and date = :date
                            GROUP BY status");

    $stmt->bindValue(":date",  $date);
    $stmt->execute();
    
    // Ergebnisse auswerten und in den entsprechenden Variablen speichern
    while ($value = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($value['status'] == 1) {
            $normalCount = $value['anzahl']; // Anzahl der Normal-Einträge
        } elseif ($value['status'] == 2) {
            $vegiCount = $value['anzahl']; // Anzahl der Vegi-Einträge
        }
    }

    // Rückgabe der beiden Variablen als Array
    return [
        'normalCount' => $normalCount,
        'vegiCount' => $vegiCount
    ];
}
    


        

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
    $maxRows = ceil(($daysInMonth + $daysFromPrevMonth) / 7);
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
            $resultStringvegi = 'vegi_'.$day.'_'.$monthNum.'_'.$year;
            $resultStringall = 'normal_'.$day.'_'.$monthNum.'_'.$year;

            // Beispiel für den Funktionsaufruf
            $result = getNormalAttendance($day.'-'.$monthNum.'-'.$year);

             // Zugriff auf die Variablen
            "Normal Count: " . $result['normalCount']; // Ausgabe der Normal-Anzahl
            "Vegi Count: " . $result['vegiCount'];   // Ausgabe der Vegi-Anzahl

            echo "<div class='checkboxes'>";
            echo "<input type='text' id='greyp' class='checkbox-gray' name='$resultStringall' value='$result[normalCount]' data-week='$weekIndex' onclick='toggleWeekGray(this, $weekIndex, $j)'>";
            echo "<input type='text' id='greenp' class='checkbox-green' name='$resultStringvegi' value='$result[vegiCount]' data-week='$weekIndex' onclick='toggleWeekGreen(this, $weekIndex, $j)'>";
            echo "</div></td>";
            
            $currentDate = strtotime('+1 day', $currentDate);
        }
        echo "</tr>";
        $weekIndex++;
    }
    echo "</table></div>";

    
}
?>



<form method="POST" action="1_eintragung.php">
    <h3>Aktueller Monat</h3>
    <div class="calendar-container">
        <div class="calendar">
        <?php generateMonthPlan($currentYear, $currentMonth);?>
           
        </div>
    </div>
    
</form>
<?php

?>



<style>
    .calendar-container {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin: 20px;
    max-width: 1200px;
    margin: 0 auto; /* Zentrierung des Containers */
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
</style>