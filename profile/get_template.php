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

    $is_global = false;
    $check_global_stmt = $conn->prepare("
        SELECT wt.id 
        FROM workout_templates wt
        JOIN users u ON wt.user_id = u.id
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE wt.id = ? AND (r.name = 'admin' OR r.id = 5)
    ");
    
    if (!$check_global_stmt) {
        throw new Exception("Failed to prepare global check: " . $conn->error);
    }
    
    $check_global_stmt->bind_param("i", $template_id);
    $check_global_stmt->execute();
    $check_global_result = $check_global_stmt->get_result();
    
    if ($check_global_result->num_rows > 0) {
        $is_global = true;
    }
    $check_global_stmt->close();
    
    $template_query = "SELECT id, name, description, difficulty, estimated_time, rest_time, user_id 
                     FROM workout_templates 
                     WHERE id = ? AND (user_id = ? OR ?)";
    $stmt = $conn->prepare($template_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $template_id, $user_id, $is_global);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Template not found or you don't have permission to access it");
    }

    $template = $result->fetch_assoc();
    $stmt->close();

    if ($template['user_id'] != $user_id) {
        $creator_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $creator_stmt->bind_param("i", $template['user_id']);
        $creator_stmt->execute();
        $creator_result = $creator_stmt->get_result();
        if ($creator_result->num_rows > 0) {
            $creator = $creator_result->fetch_assoc();
            $template['creator'] = $creator['username'];
        }
        $creator_stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT 
            e.name as exercise_name,
            wte.sets,
            wte.rest_time,
            wte.notes,
            wte.position,
            e.primary_muscle as target_muscles
        FROM workout_template_exercises wte
        LEFT JOIN exercises e ON wte.exercise_id = e.id
        WHERE wte.workout_template_id = ?
        ORDER BY wte.position ASC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed for exercises: " . $conn->error);
    }

    $stmt->bind_param("i", $template_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed for exercises: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $exercisesData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $exercises = [];
    foreach ($exercisesData as $row) {
        if (empty($row['exercise_name'])) {
            continue;
        }
        
        $exercises[] = [
            'exercise_name' => $row['exercise_name'],
            'sets' => $row['sets'] ?? 3,
            'rest_time' => $row['rest_time'] ?? 90,
            'position' => $row['position'] ?? 0,
            'notes' => $row['notes'] ?? '',
            'target_muscles' => $row['target_muscles'] ?? 'General'
        ];
    }
    
    if (empty($exercises)) {
        $exercises[] = [
            'exercise_name' => 'General Exercise',
            'sets' => 3,
            'rest_time' => 90,
            'position' => 0,
            'notes' => '',
            'target_muscles' => 'General'
        ];
    }

    $response = [
        'success' => true,
        'template' => $template,
        'exercises' => $exercises,
        'is_global' => $is_global
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