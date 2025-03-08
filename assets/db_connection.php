<?php
// Database connection credentials
$serveris = "localhost";
$lietotajs = "grobina1_pavlovskis";
$parole = "3LZeL@hxv";
$db_nosaukums = "grobina1_pavlovskis";

// Create connection using mysqli
$conn = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Optional PDO connection for modern PHP code
try {
    $pdo = new PDO("mysql:host=$serveris;dbname=$db_nosaukums;charset=utf8mb4", $lietotajs, $parole);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // In production you would log this rather than display
    // error_log("PDO Connection Error: " . $e->getMessage());
}
?> 