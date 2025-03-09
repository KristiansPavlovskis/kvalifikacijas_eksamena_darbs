<?php
/**
 * Database Setup Script for Fitness Application
 * Run this script to set up your database with all required tables and sample data
 */

// Database connection settings - modify these to match your environment
$host = "localhost";
$username = "root";
$password = "";
$dbname = "gymverse";

// Connect to MySQL
echo "Connecting to MySQL...\n";
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected successfully.\n";

// Create database if it doesn't exist
echo "Creating database if it doesn't exist...\n";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.\n";
} else {
    die("Error creating database: " . $conn->error . "\n");
}

// Select the database
echo "Selecting database...\n";
$conn->select_db($dbname);

// Read the SQL setup file
echo "Reading SQL setup file...\n";
$sql_file = file_get_contents('database_setup.sql');

if (!$sql_file) {
    die("Error: Could not read database_setup.sql file.\n");
}

// Split the SQL by delimiter changes for stored procedures and triggers
$sql_lines = explode("\n", $sql_file);
$current_delimiter = ';';
$sql_statement = '';
$in_block = false;
$processed_commands = 0;
$successful_commands = 0;

echo "Executing SQL commands...\n";

foreach ($sql_lines as $line) {
    $trimmed_line = trim($line);
    
    // Skip empty lines and comments
    if (empty($trimmed_line) || strpos($trimmed_line, '--') === 0) {
        continue;
    }
    
    // Check for delimiter change
    if (strpos($trimmed_line, 'DELIMITER') === 0) {
        // Execute any pending SQL before changing delimiter
        if (!empty($sql_statement)) {
            execute_sql($conn, $sql_statement, $current_delimiter, $processed_commands, $successful_commands);
            $sql_statement = '';
        }
        
        // Set new delimiter
        $current_delimiter = trim(substr($trimmed_line, 10));
        $in_block = ($current_delimiter != ';');
        continue;
    }
    
    // Add the line to the current SQL statement
    $sql_statement .= $line . "\n";
    
    // Check if statement is complete with current delimiter
    if (!$in_block && substr(rtrim($sql_statement), -strlen($current_delimiter)) === $current_delimiter) {
        execute_sql($conn, $sql_statement, $current_delimiter, $processed_commands, $successful_commands);
        $sql_statement = '';
    } else if ($in_block && strpos($trimmed_line, $current_delimiter) !== false) {
        // If we're in a block (stored proc, function, trigger) and delimiter is found
        $in_block = false;
        execute_sql($conn, $sql_statement, $current_delimiter, $processed_commands, $successful_commands);
        $sql_statement = '';
        $current_delimiter = ';'; // Reset to default
    }
}

// Execute any remaining SQL
if (!empty($sql_statement)) {
    execute_sql($conn, $sql_statement, $current_delimiter, $processed_commands, $successful_commands);
}

// Summary
echo "\nDatabase setup completed.\n";
echo "$successful_commands of $processed_commands SQL commands executed successfully.\n";

// Close connection
$conn->close();
echo "MySQL connection closed.\n";

/**
 * Execute an SQL statement with the given delimiter
 */
function execute_sql($conn, $sql, $delimiter, &$processed_commands, &$successful_commands) {
    $processed_commands++;
    
    // Replace the delimiter if it's not semicolon
    if ($delimiter != ';') {
        $sql = str_replace($delimiter, ';', $sql);
    }
    
    // Execute the statement
    if ($conn->multi_query($sql)) {
        $successful_commands++;
        
        // Clear any result sets
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "Error executing SQL: " . $conn->error . "\n";
        echo "SQL statement: " . substr($sql, 0, 150) . "...\n\n";
    }
} 