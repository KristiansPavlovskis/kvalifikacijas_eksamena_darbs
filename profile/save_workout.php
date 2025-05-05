<?php

declare(strict_types=1);

ob_start();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__.'/save_workout_errors.log');
error_reporting(E_ALL);

if (!file_exists(__DIR__.'/save_workout_errors.log')) {
    file_put_contents(__DIR__.'/save_workout_errors.log', "[" . date('Y-m-d H:i:s') . "] Log created\n");
    chmod(__DIR__.'/save_workout_errors.log', 0666);
}

require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

if (!$conn || $conn->connect_error){
    error_log('Database connection failed: '.($conn->connect_error ?? 'Unknown error'));
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => 'No action taken'];

if (isset($_POST['save_workout']) && isset($_POST['workout_data'])) {
    try {
        $workoutData = json_decode($_POST['workout_data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid workout data format: " . json_last_error_msg());
        }
        
        if (!$workoutData) {
            throw new Exception("Invalid workout data format");
        }
        
        error_log("Received workout data: " . print_r($workoutData, true));
    
        if (!isset($workoutData['title'])) {
            $workoutData['title'] = 'Quick Workout';
        }
        
        if (!isset($workoutData['duration_minutes'])) {
            $workoutData['duration_minutes'] = 0;
        }
        
        if (!isset($workoutData['exercises']) || !is_array($workoutData['exercises'])) {
            throw new Exception("No exercise data found");
        }

        $total_workout_volume = 0;
        $total_sets_count = 0;
        $total_weight = 0;
        
        $conn->begin_transaction();
        
        $workoutName = $workoutData['title'] ?? 'Quick Workout';
        $workoutType = $workoutData['type'] ?? 'strength';
        $notes = isset($workoutData['notes']) ? trim($workoutData['notes']) : null;
        if ($notes === '') {
            $notes = null;
        }
        error_log("Notes value being saved: " . $notes);
        $rating = intval($workoutData['rating'] ?? 3);
        $duration = floatval($workoutData['duration_minutes'] ?? 0.0);
        $calories = intval($workoutData['calories_burned'] ?? 0);
        $template_id = isset($workoutData['template_id']) && is_numeric($workoutData['template_id'])
            ? (int)$workoutData['template_id']
            : 0;

        $workout_stmt = $conn->prepare("INSERT INTO workouts 
        (user_id, name, workout_type, duration_minutes, calories_burned, 
        notes, rating, template_id, created_at, total_volume, avg_intensity) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        
        $total_volume = floatval($workoutData['total_volume'] ?? 0);
        $avg_intensity = $total_sets_count > 0 
        ? $total_volume / $total_sets_count 
        : 0;


        $workout_stmt->bind_param("issiisiidd", 
            $user_id, 
            $workoutName, 
            $workoutType, 
            $duration, 
            $calories, 
            $notes,
            $rating, 
            $template_id, 
            $total_volume, 
            $avg_intensity
        );
        if (!$workout_stmt->execute()) {
            throw new Exception("Execute failed: " . $workout_stmt->error);
        }
        
        $workout_id = $conn->insert_id;
        $workout_stmt->close();
        
        foreach ($workoutData['exercises'] as $exercise_index => $exercise) {
            if (!isset($exercise['name']) || !isset($exercise['sets']) || !is_array($exercise['sets'])) {
                continue;
            }
            
            $exercise_stmt = $conn->prepare("INSERT INTO workout_exercises 
                (workout_id, user_id, exercise_name, exercise_order, sets_completed, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())");
                
            if (!$exercise_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $sets_completed = count($exercise['sets']);
            
            $exercise_order = $exercise_index + 1;
            $exercise_stmt->bind_param("iisii", 
                $workout_id,
                $user_id,
                $exercise['name'],
                $exercise_order,
                $sets_completed
            );
                        
            if (!$exercise_stmt->execute()) {
                throw new Exception("Execute failed: " . $exercise_stmt->error);
            }
            
            $workout_exercise_id = $conn->insert_id;
            $exercise_stmt->close();
            
            $total_reps = 0;
            $total_volume = 0;
            $total_rpe = 0;
            
            foreach ($exercise['sets'] as $set_index => $set) {
                error_log("Processing Set Data: " . print_r($set, true));
                $weight = floatval($set['weight'] ?? 0);
                $reps = intval($set['reps'] ?? 0);
                $rpe = isset($set['rpe']) && is_numeric($set['rpe']) 
                ? (float)$set['rpe'] 
                : 0.0;
                
                $total_reps += $reps;
                $total_volume += ($weight * $reps);
                $total_rpe += $rpe;
                $total_workout_volume += ($weight * $reps);
                $total_weight += $weight;
                $total_sets_count++;
                
                $set_stmt = $conn->prepare("INSERT INTO workout_sets 
                (workout_exercise_id, set_number, weight, reps, rpe) 
                VALUES (?, ?, ?, ?, ?)");
                    
                if (!$set_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $set_number = $set_index + 1;
                
                $rpe = isset($set['rpe']) && is_numeric($set['rpe']) ? (float)$set['rpe'] : NULL;
                $set_stmt->bind_param("iidid", 
                    $workout_exercise_id,
                    $set_number,
                    $weight,
                    $reps,
                    $rpe
                );
                
                if (!$set_stmt->execute()) {
                    throw new Exception("Execute failed: " . $set_stmt->error);
                }

                if (!isset($set['weight'])) {
                    throw new Exception("Missing weight for set " . ($set_index + 1));
                }
                
                $weight = floatval($set['weight']);
                $reps = intval($set['reps'] ?? 0);
                
                if ($weight < 0 || $reps < 0) {
                    throw new Exception("Invalid set values for " . $exercise['name']);
                }
                error_log("Inserting set for exercise ID $workout_exercise_id: weight=$weight, reps=$reps, rpe=" . ($rpe ?? 'NULL'));
                $set_stmt->close();
            }
        
            $avg_rpe = $sets_completed > 0 ? $total_rpe / $sets_completed : 0;
            
            $update_exercise_stmt = $conn->prepare("UPDATE workout_exercises 
                SET total_reps = ?, total_volume = ?, avg_rpe = ? 
                WHERE id = ?");
                
            if (!$update_exercise_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $update_exercise_stmt->bind_param("iddi", 
                $total_reps,
                $total_volume,
                $avg_rpe,
                $workout_exercise_id
            );
            
            if (!$update_exercise_stmt->execute()) {
                throw new Exception("Execute failed: " . $update_exercise_stmt->error);
            }
            
            $update_exercise_stmt->close();
        }
        
        $conn->commit();
        
        $response = ['success' => true, 'message' => 'Workout saved successfully', 'workout_id' => $workout_id];
    }
    catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'Error saving workout: ' . $e->getMessage()];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;