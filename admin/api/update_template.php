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

    if (empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID is required']);
        exit;
    }

    if (empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Template name is required']);
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
        
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found or you don't have permission to edit it");
        }

        $template_query = "UPDATE workout_templates SET 
                          name = ?, 
                          description = ?, 
                          category = ?, 
                          difficulty = ?, 
                          estimated_time = ?, 
                          rest_time = ? 
                          WHERE id = ?";
        
        $stmt = $conn->prepare($template_query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssssidi", 
            $input['name'], 
            $input['description'], 
            $input['category'], 
            $input['difficulty'], 
            $input['estimated_time'],
            $input['rest_time'], 
            $input['id']
        );
        
        $stmt->execute();
        
        $delete_query = "DELETE FROM workout_template_exercises WHERE workout_template_id = ?";
        $stmt = $conn->prepare($delete_query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        
        if (!empty($input['exercises'])) {
            $exercise_query = "INSERT INTO workout_template_exercises (workout_template_id, exercise_id, position, sets, rest_time) 
                               VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($exercise_query);
            
            foreach ($input['exercises'] as $exercise) {
                $stmt->bind_param(
                    "iiidi", 
                    $input['id'], 
                    $exercise['exercise_id'], 
                    $exercise['position'], 
                    $exercise['sets'], 
                    $exercise['rest_time']
                );
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'template_id' => $input['id']]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("API Error in update_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 