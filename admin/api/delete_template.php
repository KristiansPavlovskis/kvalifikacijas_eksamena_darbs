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

    $template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($template_id === 0) {
        echo json_encode(["success" => false, "message" => "Template ID is required"]);
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
        
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found or you don't have permission to delete it");
        }

        $delete_exercises_sql = "DELETE FROM workout_template_exercises WHERE workout_template_id = ?";
        $stmt = $conn->prepare($delete_exercises_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        
        $delete_template_sql = "DELETE FROM workout_templates WHERE id = ?";
        $stmt = $conn->prepare($delete_template_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete template");
        }
        
        $conn->commit();
        
        echo json_encode([
            "success" => true, 
            "message" => "Template deleted successfully"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("API Error in delete_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 