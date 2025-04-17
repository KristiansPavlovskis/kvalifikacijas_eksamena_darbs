<?php

ob_start();

// $serveris = "localhost";
// $lietotajs = "grobina1_pavlovskis";
// $parole = "3LZeL@hxv";
// $db_nosaukums = "grobina1_pavlovskis";
$serveris = '127.0.0.1';
$db_nosaukums = 'gymverse_db';
$lietotajs = 'root';
$parole = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);
$savienojums = $conn; 

if (!$conn) {
    error_log("Database Connection Error: " . mysqli_connect_error());
    
    $_SESSION['db_error'] = "Database Connection Error: " . mysqli_connect_error();
    
    
    $conn = false;
    $savienojums = false;
} else {
    mysqli_set_charset($conn, "utf8mb4");
}

try {
    $pdo = new PDO("mysql:host=$serveris;dbname=$db_nosaukums;charset=utf8mb4", $lietotajs, $parole);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    
    $_SESSION['pdo_error'] = "PDO Connection Error: " . $e->getMessage();
    
    $pdo = false;
}

function showSqlError($message, $sql = "", $error = "") {
    error_log("SQL Error: $message - SQL: $sql - Error: $error");
    
    $_SESSION['sql_error'] = [
        'message' => $message,
        'sql' => $sql,
        'error' => $error
    ];
}
?> 