<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];
$is_superadmin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_superadmin = ($row['count'] > 0);
}

if (!$is_superadmin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $delete_user_id = intval($_POST['user_id']);

    if ($delete_user_id == $user_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $delete_roles_sql = "DELETE FROM user_roles WHERE user_id = ?";
        $delete_roles_stmt = $conn->prepare($delete_roles_sql);
        $delete_roles_stmt->bind_param("i", $delete_user_id);
        $delete_roles_stmt->execute();
        
        $delete_user_sql = "DELETE FROM users WHERE id = ?";
        $delete_user_stmt = $conn->prepare($delete_user_sql);
        $delete_user_stmt->bind_param("i", $delete_user_id);
        $delete_user_stmt->execute();
        
        if ($delete_user_stmt->affected_rows == 0) {
            throw new Exception("User not found or could not be deleted");
        }
        
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 