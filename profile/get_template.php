<?php
ob_start();

try {
    require_once 'profile_access_control.php';
    
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        throw new Exception("User not logged in");
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid template ID format");
    }

    $template_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION["user_id"];

    require_once '../assets/db_connection.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }

    $stmt = $conn->prepare("SELECT id, name, description, difficulty, estimated_time 
                          FROM workout_templates 
                          WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $template_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Template not found or doesn't belong to user");
    }

    $template = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT 
            e.name as exercise_name,
            wte.sets,
            wte.rest_time,
            wte.position,
            wte.notes
        FROM workout_template_exercises wte
        JOIN exercises e ON wte.exercise_id = e.id
        WHERE wte.workout_template_id = ?
        ORDER BY wte.position ASC
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $template_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $exercises = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$exercises) {
        $exercises = [];
    }

    $exercises = array_map(function($exercise) {
        return [
            'exercise_name' => $exercise['exercise_name'],
            'sets' => $exercise['sets'] ?? 3,
            'reps' => 10,
            'rest_time' => $exercise['rest_time'] ?? 60,
            'position' => $exercise['position'],
            'notes' => $exercise['notes'] ?? ''
        ];
    }, $exercises);

    $response = [
        'success' => true,
        'template' => $template,
        'exercises' => $exercises
    ];

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}