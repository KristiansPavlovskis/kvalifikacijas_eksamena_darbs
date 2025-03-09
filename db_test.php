<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// Test connection with the current credentials
echo "<h2>Testing connection with current credentials</h2>";
$serveris = "localhost";
$lietotajs = "grobina1_pavlovskis";
$parole = "3LZeL@hxv";
$db_nosaukums = "grobina1_pavlovskis";

echo "Attempting to connect to: $serveris, Database: $db_nosaukums, User: $lietotajs<br>";

// Try mysqli connection
echo "<h3>Testing mysqli connection</h3>";
try {
    $conn = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);
    if ($conn) {
        echo "<p style='color:green'>✓ MySQLi connection successful!</p>";
        
        // Test if users table exists
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p style='color:green'>✓ 'users' table exists</p>";
            
            // Check users table structure
            $result = mysqli_query($conn, "DESCRIBE users");
            echo "<p>Users table structure:</p>";
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<li>{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}</li>";
            }
            echo "</ul>";
            
            // Count users
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
            $row = mysqli_fetch_assoc($result);
            echo "<p>Total users in database: {$row['count']}</p>";
        } else {
            echo "<p style='color:red'>✗ 'users' table does not exist!</p>";
        }
        
        mysqli_close($conn);
    } else {
        echo "<p style='color:red'>✗ MySQLi connection failed: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Suggest next steps
echo "<h2>Recommendations:</h2>";
echo "<ol>";
echo "<li>If connection fails, check if MySQL is running on your local machine.</li>";
echo "<li>Verify that the user 'grobina1_pavlovskis' exists and has proper permissions.</li>";
echo "<li>Check if the database 'grobina1_pavlovskis' exists.</li>";
echo "<li>If using a remote database, ensure your IP is whitelisted.</li>";
echo "</ol>";

?>