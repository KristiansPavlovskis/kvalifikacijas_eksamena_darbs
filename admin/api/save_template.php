<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
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
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        exit;
    }

    if (empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Template name is required']);
        exit;
    }

    if (empty($input['exercises']) || !is_array($input['exercises']) || count($input['exercises']) === 0) {
        echo json_encode(["success" => false, "message" => "At least one exercise is required"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        $template_query = "INSERT INTO workout_templates (name, description, category, difficulty, estimated_time, rest_time, user_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($template_query);
        $stmt->bind_param(
            "ssssddi", 
            $input['name'], 
            $input['description'], 
            $input['category'], 
            $input['difficulty'], 
            $input['estimated_time'],
            $input['rest_time'], 
            $user_id
        );
        
        $stmt->execute();
        $template_id = $conn->insert_id;
        
        $exercise_query = "INSERT INTO workout_template_exercises (workout_template_id, exercise_id, position, sets, rest_time) 
                           VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($exercise_query);
        
        foreach ($input['exercises'] as $exercise) {
            $stmt->bind_param(
                "iiidi", 
                $template_id, 
                $exercise['exercise_id'], 
                $exercise['position'], 
                $exercise['sets'], 
                $exercise['rest_time']
            );
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'template_id' => $template_id]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("API Error in save_template.php: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    error_log("API Error in save_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 