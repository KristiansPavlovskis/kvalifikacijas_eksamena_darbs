<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
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
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Access denied"]);
        exit;
    }

    $json_data = file_get_contents('php://input');
    if (!$json_data) {
        throw new Exception("No data received");
    }
    
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }

    if (empty($data['name'])) {
        echo json_encode(["success" => false, "message" => "Template name is required"]);
        exit;
    }

    if (empty($data['exercises']) || !is_array($data['exercises']) || count($data['exercises']) === 0) {
        echo json_encode(["success" => false, "message" => "At least one exercise is required"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        $template_sql = "INSERT INTO workout_templates (name, description, category, difficulty, user_id, created_at, estimated_time, rest_time) 
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($template_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $description = !empty($data['description']) ? $data['description'] : '';
        $category = !empty($data['category']) ? $data['category'] : 'Strength Training';
        $difficulty = !empty($data['difficulty']) ? $data['difficulty'] : 'intermediate';
        $estimated_time = !empty($data['estimated_time']) ? $data['estimated_time'] : 45;
        $rest_time = !empty($data['rest_time']) ? $data['rest_time'] : 1;
        
        $stmt->bind_param("ssssidi", 
            $data['name'], 
            $description,
            $category,
            $difficulty,
            $user_id,
            $estimated_time,
            $rest_time
        );
        
        $stmt->execute();
        $template_id = $conn->insert_id;
        
        $exercise_sql = "INSERT INTO workout_template_exercises (workout_template_id, exercise_id, position, sets, rest_time) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($exercise_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        foreach ($data['exercises'] as $exercise) {
            $stmt->bind_param("iiidi", 
                $template_id, 
                $exercise['exercise_id'],
                $exercise['position'],
                $exercise['sets'],
                $exercise['rest_time']
            );
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            "success" => true, 
            "message" => "Template created successfully", 
            "template_id" => $template_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
        error_log("API Error in save_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 