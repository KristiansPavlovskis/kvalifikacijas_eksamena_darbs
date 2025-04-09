<?php
/**
 * Main Database Connection File
 * This is the ONLY database connection file you should use in the entire application.
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Database connection credentials
// $serveris = "localhost";
// $lietotajs = "grobina1_pavlovskis";
// $parole = "3LZeL@hxv";
// $db_nosaukums = "grobina1_pavlovskis";
$serveris = '127.0.0.1'; // or 'localhost'
$db_nosaukums = 'gymverse_db';
$lietotajs = 'root';
$parole = ''; // No password by default

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create connection using mysqli
$conn = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);
$savienojums = $conn; // For backward compatibility with any code using $savienojums

// Check connection and enable error logging
if (!$conn) {
    // Log the error
    error_log("Database Connection Error: " . mysqli_connect_error());
    
    // Store error in session instead of direct output
    $_SESSION['db_error'] = "Database Connection Error: " . mysqli_connect_error();
    
    // Only show error in development mode and when not processing headers
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE && !headers_sent()) {
        // This will only be shown in development mode
        echo "<div style='background-color: #ffdddd; color: #ff0000; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;'>";
        echo "Database Connection Error: " . mysqli_connect_error();
        echo "</div>";
    }
    
    // Don't die, just set connection to false
    $conn = false;
    $savienojums = false;
} else {
    // Set character set to utf8mb4
    mysqli_set_charset($conn, "utf8mb4");
}

// Optional PDO connection for modern PHP code
try {
    $pdo = new PDO("mysql:host=$serveris;dbname=$db_nosaukums;charset=utf8mb4", $lietotajs, $parole);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log the error
    error_log("PDO Connection Error: " . $e->getMessage());
    
    // Store error in session instead of direct output
    $_SESSION['pdo_error'] = "PDO Connection Error: " . $e->getMessage();
    
    // Only show error in development mode and when not processing headers
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE && !headers_sent()) {
        echo "<div style='background-color: #ffdddd; color: #ff0000; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;'>";
        echo "PDO Connection Error: " . $e->getMessage();
        echo "</div>";
    }
    
    $pdo = false;
}

// Function to show SQL errors during development
function showSqlError($message, $sql = "", $error = "") {
    // Log the error
    error_log("SQL Error: $message - SQL: $sql - Error: $error");
    
    // Store in session
    $_SESSION['sql_error'] = [
        'message' => $message,
        'sql' => $sql,
        'error' => $error
    ];
    
    // Only output if in development mode and headers not sent
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE && !headers_sent()) {
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
}
?> 