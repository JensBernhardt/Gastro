<?php
if (class_exists('SQLite3')) {
    // echo "SQLite3 ist aktiviert und funktioniert!";
} else {
    // echo "SQLite3 ist nicht aktiviert.";
}

$dsn = 'sqlite:' . __DIR__ . '/../Datenbank/gastro.db'; 

//echo "Datenbankpfad: " . __DIR__ . '/../Datenbank/gastro.db' . PHP_EOL;

try {
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
   // echo "Verbindung zur Datenbank erfolgreich hergestellt.";
} catch (PDOException $e) {
    die('Verbindung fehlgeschlagen: ' . $e->getMessage());
}
?>