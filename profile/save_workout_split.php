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
    $split_name = isset($_POST['splitName']) ? trim($_POST['splitName']) : '';
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    
    if (isset($_POST['target_month']) && strpos($_POST['target_month'], '_') !== false) {
        list($month, $year) = explode('_', $_POST['target_month']);
        $month = intval($month);
        $year = intval($year);
    } else {
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    }
    
    if (empty($split_name)) {
        $response['message'] = 'Please provide a name for your split.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($days)) {
        $response['message'] = 'No workout days provided.';
        echo json_encode($response);
        exit;
    }
    
    $conn->query("CREATE TABLE IF NOT EXISTS workout_splits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
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
        $conn->begin_transaction();
        
        $days_data = [];
        for ($i = 0; $i < 7; $i++) {
            $days_data[$i] = [
                'type' => '',
                'template_id' => null
            ];
        }
        
        foreach ($days as $dayIndex => $dayData) {
            if (isset($dayData['type']) && !empty($dayData['type'])) {
                $day_info = [];
                
                if ($dayData['type'] === 'rest') {
                    $day_info = [
                        'type' => 'rest'
                    ];
                } 
                else if (is_numeric($dayData['type'])) {
                    $template_id = intval($dayData['type']);
                    
                    $stmt = $conn->prepare("SELECT name, category FROM workout_templates WHERE id = ? AND user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Failed to prepare template query: " . mysqli_error($conn));
                    }
                    
                    $stmt->bind_param("ii", $template_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($template = $result->fetch_assoc()) {
                        $category = $template['category'] ?: '';
                        
                        $day_info = [
                            'type' => strtolower($category),
                            'template_id' => $template_id
                        ];
                        
                        if (empty($category)) {
                            $day_info['type'] = 'custom';
                            $day_info['custom_name'] = $template['name'];
                        }
                    } else {
                        error_log("Template ID $template_id not found for user $user_id");
                        continue;
                    }
                }
                else {
                    $day_info = [
                        'type' => $dayData['type']
                    ];
                    
                    if ($dayData['type'] !== 'rest') {
                        $day_info['template_id'] = isset($dayData['template_id']) ? intval($dayData['template_id']) : null;
                        
                        if ($dayData['type'] === 'custom' && isset($dayData['custom_name'])) {
                            $day_info['custom_name'] = $dayData['custom_name'];
                        }
                    }
                }
                
                $days_data[intval($dayIndex)] = $day_info;
            }
        }
        
        error_log("Saving split data: " . json_encode($days_data));
        
        $split_data = json_encode($days_data);
        if ($split_data === false) {
            throw new Exception("Failed to encode split data as JSON: " . json_last_error_msg());
        }
        
        $stmt = $conn->prepare("INSERT INTO workout_splits (user_id, name, data) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $split_name, $split_data);
        $stmt->execute();
        $split_id = $conn->insert_id;
        
        $first_day = new DateTime("{$year}-{$month}-01");
        $days_in_month = intval($first_day->format('t'));
        
        $first_day_of_week = intval($first_day->format('w'));
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $day_of_week = ($first_day_of_week + $day - 1) % 7;
            
            $day_index = $day_of_week === 0 ? 6 : $day_of_week - 1;
            
            if (isset($days_data[$day_index])) {
                $day_data = $days_data[$day_index];
                $workout_type = $day_data['type'];
                $template_id = null;
                $template_name = '';
                
                $current_day = date('j');
                $current_month = date('m');
                $current_year = date('Y');
                
                $is_past_date = ($year < $current_year) || 
                              ($year == $current_year && $month < $current_month) || 
                              ($year == $current_year && $month == $current_month && $day < $current_day);
                
                $apply_to_calendar = isset($_POST['apply_to_calendar']) && $_POST['apply_to_calendar'] == '1';
                
                if ($is_past_date && !$apply_to_calendar) {
                    continue;
                }
                
                if ($workout_type === 'rest') {
                    $template_name = 'Rest Day';
                } else {
                    if (!empty($day_data['template_id'])) {
                        $template_id = $day_data['template_id'];
                        
                        $stmt = $conn->prepare("SELECT name FROM workout_templates WHERE id = ? AND user_id = ?");
                        $stmt->bind_param("ii", $template_id, $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($row = $result->fetch_assoc()) {
                            $template_name = $row['name'];
                        }
                    }
                    
                    if ($workout_type === 'custom' && !empty($day_data['custom_name'])) {
                        $template_name = $day_data['custom_name'];
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO scheduled_workouts (user_id, day, month, year, template_id, template_name, workout_type) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE 
                                      template_id = VALUES(template_id), 
                                      template_name = VALUES(template_name), 
                                      workout_type = VALUES(workout_type),
                                      created_at = CURRENT_TIMESTAMP");
                
                $stmt->bind_param("iiiisss", $user_id, $day, $month, $year, $template_id, $template_name, $workout_type);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Workout split applied successfully.';
        $response['split_id'] = $split_id;
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit; 