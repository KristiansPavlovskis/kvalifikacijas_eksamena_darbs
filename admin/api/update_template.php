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

    if (empty($data['id'])) {
        echo json_encode(["success" => false, "message" => "Template ID is required"]);
        exit;
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
        $check_sql = "SELECT wt.id 
                    FROM workout_templates wt
                    INNER JOIN users u ON wt.user_id = u.id
                    INNER JOIN user_roles ur ON u.id = ur.user_id
                    WHERE wt.id = ? AND ur.role_id = 5";
        $stmt = $conn->prepare($check_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $data['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found or you don't have permission to edit it");
        }

        $template_sql = "UPDATE workout_templates 
                        SET name = ?, description = ?, category = ?, 
                            difficulty = ?, estimated_time = ?, 
                            rest_time = ?, 
                            updated_at = NOW() 
                        WHERE id = ?";
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
            $estimated_time,
            $rest_time,
            $data['id']
        );
        
        $stmt->execute();
        
        $delete_exercises_sql = "DELETE FROM workout_template_exercises WHERE workout_template_id = ?";
        $stmt = $conn->prepare($delete_exercises_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $data['id']);
        $stmt->execute();
        
        $exercise_sql = "INSERT INTO workout_template_exercises (workout_template_id, exercise_id, position, sets, rest_time) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($exercise_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        foreach ($data['exercises'] as $exercise) {
            $stmt->bind_param("iiidi", 
                $data['id'], 
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
            "message" => "Template updated successfully"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("API Error in update_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 