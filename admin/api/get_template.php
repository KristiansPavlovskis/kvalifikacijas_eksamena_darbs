<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }

    $user_id = $_SESSION["user_id"];
    $is_superadmin = false;

    $sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $is_superadmin = ($row['count'] > 0);
    }

    if (!$is_superadmin) {
        echo json_encode(["success" => false, "message" => "Access denied"]);
        exit;
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['error' => 'Template ID is required']);
        exit;
    }

    $template_id = intval($_GET['id']);

    $template_query = "SELECT * FROM workout_templates WHERE id = ?";
    $stmt = $conn->prepare($template_query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template_result = $stmt->get_result();

    if ($template_result->num_rows === 0) {
        echo json_encode(['error' => 'Template not found']);
        exit;
    }

    $template = $template_result->fetch_assoc();

    $exercises_query = "
        SELECT wte.*, e.* 
        FROM workout_template_exercises wte
        INNER JOIN exercises e ON wte.exercise_id = e.id
        WHERE wte.workout_template_id = ?
        ORDER BY wte.position ASC
    ";
    $stmt = $conn->prepare($exercises_query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $exercises_result = $stmt->get_result();

    $exercises = [];
    while ($exercise = $exercises_result->fetch_assoc()) {
        $exercises[] = [
            'id' => $exercise['exercise_id'],
            'name' => $exercise['name'],
            'sets' => $exercise['sets'],
            'rest_time' => $exercise['rest_time'],
            'position' => $exercise['position']
        ];
    }

    $template['exercises'] = $exercises;

    echo json_encode($template);
    
} catch (Exception $e) {
    error_log("API Error in get_template.php: " . $e->getMessage());
    
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 