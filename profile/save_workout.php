<?php
// Prevent any output before headers
// At the top of the file replace:
error_reporting(E_ALL);
ini_set('display_errors', 0);

// With:
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set JSON content type
header('Content-Type: application/json');

require_once 'profile_access_control.php';

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => true, 'message' => 'Unauthorized']));
}

require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

// Check required tables exist and create them if necessary
function ensureTablesExist($conn) {
    $tables = ['workouts', 'workout_exercises', 'exercise_sets'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        if (!tableExists($conn, $table)) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        // Create workouts table
        if (in_array('workouts', $missingTables)) {
            $sql = "CREATE TABLE workouts (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                workout_type VARCHAR(50) DEFAULT NULL,
                duration_minutes INT DEFAULT NULL,
                calories_burned INT DEFAULT NULL,
                notes TEXT,
                rating INT DEFAULT NULL,
                template_id INT DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                total_volume DECIMAL(10,2) DEFAULT '0.00',
                avg_intensity DECIMAL(3,1) DEFAULT '0.0',
                PRIMARY KEY (id),
                KEY idx_workout_user (user_id, created_at),
                KEY idx_workout_date (created_at),
                KEY idx_workout_type (workout_type),
                CONSTRAINT workouts_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
        
        // Create workout_exercises table
        if (in_array('workout_exercises', $missingTables)) {
            $sql = "CREATE TABLE workout_exercises (
                id INT NOT NULL AUTO_INCREMENT,
                workout_id INT NOT NULL,
                user_id INT NOT NULL,
                exercise_name VARCHAR(100) NOT NULL,
                exercise_order INT DEFAULT NULL,
                notes TEXT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                sets_completed INT DEFAULT '0',
                total_reps INT DEFAULT '0',
                total_volume DECIMAL(10,2) DEFAULT '0.00',
                avg_rpe DECIMAL(3,1) DEFAULT '0.0',
                PRIMARY KEY (id),
                KEY workout_id (workout_id),
                KEY idx_user_exercises (user_id, exercise_name),
                CONSTRAINT workout_exercises_ibfk_1 FOREIGN KEY (workout_id) REFERENCES workouts (id) ON DELETE CASCADE,
                CONSTRAINT workout_exercises_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
        
        // Create exercise_sets table
        if (in_array('exercise_sets', $missingTables)) {
            $sql = "CREATE TABLE exercise_sets (
                id INT NOT NULL AUTO_INCREMENT,
                exercise_id INT NOT NULL,
                user_id INT NOT NULL,
                set_number INT NOT NULL,
                weight DECIMAL(6,2) DEFAULT NULL,
                reps INT DEFAULT NULL,
                rpe INT DEFAULT NULL,
                is_warmup TINYINT(1) DEFAULT '0',
                note TEXT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY exercise_id (exercise_id),
                KEY idx_exercise_sets_user (user_id),
                CONSTRAINT exercise_sets_ibfk_1 FOREIGN KEY (exercise_id) REFERENCES workout_exercises (id) ON DELETE CASCADE,
                CONSTRAINT exercise_sets_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
    }
}

// Get and validate input
$json = file_get_contents('php://input');
if (!$json) {
    http_response_code(400);
    exit(json_encode(['error' => true, 'message' => 'No data received']));
}

$data = json_decode($json, true);
if (!$data) {
    http_response_code(400);
    exit(json_encode(['error' => true, 'message' => 'Invalid JSON format']));
}

if (empty($data['exercises'])) {
    http_response_code(400);
    exit(json_encode(['error' => true, 'message' => 'No exercises provided']));
}

$user_id = $_SESSION["user_id"];

try {
    // Ensure necessary tables exist
    ensureTablesExist($conn);
    
    // Save workout using the shared function
    $workout_id = saveWorkoutToDatabase($conn, $user_id, $data);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'workout_id' => $workout_id,
        'message' => 'Workout saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>