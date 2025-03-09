<?php
// This is a compatibility file for any code that might be using connect_db.php

// Include the main database connection file
require_once __DIR__ . '/db_connection.php';

// The main connection file already creates $savienojums for backward compatibility
?>