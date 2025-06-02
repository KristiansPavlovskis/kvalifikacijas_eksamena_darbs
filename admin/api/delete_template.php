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

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID is required']);
        exit;
    }

    $template_id = intval($_GET['id']);

    $conn->begin_transaction();

    try {
        $delete_exercises_query = "DELETE FROM workout_template_exercises WHERE workout_template_id = ?";
        $stmt = $conn->prepare($delete_exercises_query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        
        $delete_template_query = "DELETE FROM workout_templates WHERE id = ?";
        $stmt = $conn->prepare($delete_template_query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Template not found']);
            exit;
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("API Error in delete_template.php: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 