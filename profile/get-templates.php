<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];

$templates_query = "
    SELECT 
        id, name
    FROM workout_templates
    WHERE user_id = ?
    ORDER BY name ASC";

$templates_stmt = mysqli_prepare($conn, $templates_query);
mysqli_stmt_bind_param($templates_stmt, "i", $user_id);
mysqli_stmt_execute($templates_stmt);
$templates_result = mysqli_stmt_get_result($templates_stmt);

$templates = [];
while ($row = mysqli_fetch_assoc($templates_result)) {
    $templates[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

header('Content-Type: application/json');
echo json_encode($templates);
?> 