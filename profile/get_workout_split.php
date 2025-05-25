<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/profile.php");
    exit;
}

require_once '../assets/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'split' => null];

error_log("Get workout split request: " . json_encode($_GET));

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $split_id = intval($_GET['id']);
    
    if ($split_id <= 0) {
        $response['message'] = 'Invalid split ID.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'workout_splits'");
        if (mysqli_num_rows($table_check) == 0) {
            throw new Exception("The workout_splits table does not exist.");
        }
        
        $stmt = mysqli_prepare($conn, "SELECT id, name, data FROM workout_splits WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare query: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $split_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($split = mysqli_fetch_assoc($result)) {
            if (!empty($split['data'])) {
                $data = json_decode($split['data'], true);
                
                if (is_array($data)) {
                    $normalized_data = [];
                    for ($i = 0; $i < 7; $i++) {
                        if (isset($data[$i]) && is_array($data[$i])) {
                            $normalized_data[$i] = $data[$i];
                        } else {
                            $normalized_data[$i] = [
                                'type' => '',
                                'template_id' => null
                            ];
                        }
                    }
                    
                    $split['data'] = json_encode($normalized_data);
                    error_log("Normalized split data: " . $split['data']);
                }
            }
            
            $response['success'] = true;
            $response['split'] = $split;
            error_log("Split found: " . json_encode($split));
        } else {
            $response['message'] = 'Split not found or access denied.';
            error_log("Split not found: ID $split_id for user $user_id");
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Error in get_workout_split.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request.';
    error_log("Invalid request to get_workout_split.php: " . json_encode($_SERVER));
}

echo json_encode($response);
exit;
