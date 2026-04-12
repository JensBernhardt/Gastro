<?php
session_start();
session_regenerate_id(true); // Schützt vor Session-Fixation

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once(__DIR__ . '/Seiten2/connection.php');

$user_id = $_SESSION['user_id'];
$sql = "SELECT b.id,b.username, b.email, r.name AS role, r.beschreibung AS role_description 
        FROM benutzer b
        JOIN benutzer_rollen br ON b.id = br.benutzer_id
        JOIN rollen r ON br.rolle_id = r.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['userId'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['role_description'] = $user['role_description'];
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