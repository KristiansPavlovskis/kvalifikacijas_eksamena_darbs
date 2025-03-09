<?php
/**
 * Main Database Connection File
 * This is the ONLY database connection file you should use in the entire application.
 */

// Database connection credentials
$serveris = "localhost";
$lietotajs = "grobina1_pavlovskis";
$parole = "3LZeL@hxv";
$db_nosaukums = "grobina1_pavlovskis";

// Create connection using mysqli
$conn = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);
$savienojums = $conn; // For backward compatibility with any code using $savienojums

// Check connection and enable error display for debugging
if (!$conn) {
    // Log the error
    error_log("Database Connection Error: " . mysqli_connect_error());
    
    // In development, show the error
    echo "<div style='background-color: #ffdddd; color: #ff0000; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;'>";
    echo "Database Connection Error: " . mysqli_connect_error();
    echo "</div>";
    die();
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
    // Log the error
    error_log("PDO Connection Error: " . $e->getMessage());
    
    // In development, show the error
    echo "<div style='background-color: #ffdddd; color: #ff0000; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;'>";
    echo "PDO Connection Error: " . $e->getMessage();
    echo "</div>";
}

// Function to show SQL errors during development
function showSqlError($message, $sql = "", $error = "") {
    echo "<div style='background-color: #ffdddd; color: #ff0000; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;'>";
    echo "<strong>Database Error:</strong> " . $message . "<br>";
    if (!empty($sql)) {
        echo "<strong>SQL:</strong> " . $sql . "<br>";
    }
    if (!empty($error)) {
        echo "<strong>Error:</strong> " . $error;
    }
    echo "</div>";
}
?> 