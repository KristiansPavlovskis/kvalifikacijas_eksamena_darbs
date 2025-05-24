<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION["user_id"];
$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$is_superadmin = false;

if ($row = $result->fetch_assoc()) {
    $is_superadmin = ($row['count'] > 0);
}

if (!$is_superadmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $sql = "SELECT id, name FROM exercises ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $exercises = [];
    while ($row = $result->fetch_assoc()) {
        $exercises[] = [
            'id' => $row['id'],
            'name' => $row['name'],
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($exercises);
    
} catch (Exception $e) {
    error_log("Error in get_exercises.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 