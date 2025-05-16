<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/profile.php");
    exit;
}

require_once '../assets/db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $day = isset($_POST['day']) ? intval($_POST['day']) : 0;
    $workout_type = isset($_POST['workoutType']) ? $_POST['workoutType'] : '';
    $template_id = isset($_POST['templateId']) ? intval($_POST['templateId']) : 0;
    $custom_name = isset($_POST['customName']) ? $_POST['customName'] : '';
    
    if (!$day || $day < 1 || $day > 31) {
        $response['message'] = 'Invalid day selected.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($workout_type)) {
        $response['message'] = 'Please select a workout type.';
        echo json_encode($response);
        exit;
    }
    
    if ($workout_type !== 'rest' && empty($template_id)) {
        $response['message'] = 'Please select a workout template.';
        echo json_encode($response);
        exit;
    }
    
    if ($workout_type === 'custom' && empty($custom_name)) {
        $response['message'] = 'Please enter a custom workout name.';
        echo json_encode($response);
        exit;
    }
    
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    if ($month < 1 || $month > 12) {
        $month = date('m');
    }
    
    $current_day = date('j');
    $current_month = date('m');
    $current_year = date('Y');
    
    $is_past_date = ($year < $current_year) || 
                    ($year == $current_year && $month < $current_month) || 
                    ($year == $current_year && $month == $current_month && $day < $current_day);
    
    if ($is_past_date) {
        $response['message'] = 'Cannot modify workouts for past dates.';
        echo json_encode($response);
        exit;
    }
    
    $conn->query("CREATE TABLE IF NOT EXISTS scheduled_workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        day INT NOT NULL,
        month INT NOT NULL,
        year INT NOT NULL,
        template_id INT,
        template_name VARCHAR(255),
        workout_type VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(user_id, day, month, year),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    try {
        $template_name = '';
        if ($template_id > 0) {
            $stmt = $conn->prepare("SELECT name FROM workout_templates WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $template_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $template_name = $row['name'];
            } else {
                $response['message'] = 'Selected template not found.';
                echo json_encode($response);
                exit;
            }
        }
        
        if (!empty($custom_name)) {
            $template_name = $custom_name;
        }
        
        if ($workout_type === 'rest') {
            $template_id = null;
            $template_name = 'Rest Day';
        }
        
        $stmt = $conn->prepare("INSERT INTO scheduled_workouts (user_id, day, month, year, template_id, template_name, workout_type) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE 
                              template_id = VALUES(template_id), 
                              template_name = VALUES(template_name), 
                              workout_type = VALUES(workout_type),
                              created_at = CURRENT_TIMESTAMP");
        
        $stmt->bind_param("iiiisss", $user_id, $day, $month, $year, $template_id, $template_name, $workout_type);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Workout plan saved successfully.';
        } else {
            $response['message'] = 'Error saving workout plan: ' . $stmt->error;
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit; 