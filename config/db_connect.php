<?php
// config/db_connect.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = "localhost";
$user     = "root";
$pass     = ""; 
$dbname   = "attendance_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>