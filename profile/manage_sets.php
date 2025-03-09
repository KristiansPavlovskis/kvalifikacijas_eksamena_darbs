<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once 'assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Get request parameters
$action = $_GET['action'] ?? '';
$exercise_name = $_GET['exercise'] ?? '';

// Handle exercise history
function getExerciseHistory($conn, $user_id, $exercise_name) {
    $query = "SELECT 
        we.id as exercise_id,
        we.exercise_name,
        w.workout_name,
        w.created_at as workout_date,
        GROUP_CONCAT(
            CONCAT(es.set_number, ':', es.weight, ':', es.reps, ':', IFNULL(es.rpe, 0))
            ORDER BY es.set_number
            SEPARATOR ';'
        ) as sets_data
    FROM workout_exercises we
    JOIN workouts w ON we.workout_id = w.id
    JOIN exercise_sets es ON we.id = es.exercise_id
    WHERE we.user_id = ? AND we.exercise_name = ?
    GROUP BY we.id
    ORDER BY w.created_at DESC
    LIMIT 10";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $exercise_name);
    mysqli_stmt_execute($stmt);
    
    $history = [];
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $sets = [];
        if ($row['sets_data']) {
            foreach (explode(';', $row['sets_data']) as $set_data) {
                list($set_number, $weight, $reps, $rpe) = explode(':', $set_data);
                $sets[] = [
                    'set_number' => $set_number,
                    'weight' => $weight,
                    'reps' => $reps,
                    'rpe' => $rpe
                ];
            }
        }
        $row['sets'] = $sets;
        unset($row['sets_data']);
        $history[] = $row;
    }
    
    return $history;
}

// Get personal bests for an exercise
function getPersonalBests($conn, $user_id, $exercise_name) {
    $query = "SELECT 
        MAX(es.weight) as max_weight,
        MAX(es.reps) as max_reps,
        MAX(es.weight * es.reps) as max_volume_single_set,
        (
            SELECT MAX(inner_set.weight * inner_set.reps) * COUNT(inner_es.id)
            FROM workout_exercises inner_we
            JOIN exercise_sets inner_es ON inner_we.id = inner_es.exercise_id
            JOIN (
                SELECT exercise_id, MAX(weight * reps) as max_vol
                FROM exercise_sets
                GROUP BY exercise_id
            ) inner_set ON inner_set.exercise_id = inner_we.id
            WHERE inner_we.user_id = ? AND inner_we.exercise_name = ?
            GROUP BY inner_we.id
            ORDER BY MAX(inner_set.max_vol) DESC
            LIMIT 1
        ) as max_volume_workout,
        (
            SELECT CONCAT(inner_es.weight, ':', inner_es.reps)
            FROM workout_exercises inner_we
            JOIN exercise_sets inner_es ON inner_we.id = inner_es.exercise_id
            WHERE inner_we.user_id = ? AND inner_we.exercise_name = ?
            ORDER BY (inner_es.weight * inner_es.reps) DESC
            LIMIT 1
        ) as best_set,
        (
            SELECT COUNT(DISTINCT inner_we.id)
            FROM workout_exercises inner_we
            WHERE inner_we.user_id = ? AND inner_we.exercise_name = ?
        ) as times_performed,
        (
            SELECT MAX(w.created_at)
            FROM workout_exercises inner_we
            JOIN workouts w ON inner_we.workout_id = w.id
            WHERE inner_we.user_id = ? AND inner_we.exercise_name = ?
        ) as last_performed
    FROM workout_exercises we
    JOIN exercise_sets es ON we.id = es.exercise_id
    WHERE we.user_id = ? AND we.exercise_name = ?
    GROUP BY we.exercise_name";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isisisissis", 
        $user_id, $exercise_name, 
        $user_id, $exercise_name,
        $user_id, $exercise_name,
        $user_id, $exercise_name,
        $user_id, $exercise_name
    );
    mysqli_stmt_execute($stmt);
    
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Get recommended sets based on history
function getRecommendedSets($conn, $user_id, $exercise_name) {
    // Get last workout with this exercise
    $last_workout_query = "SELECT 
        we.id as exercise_id,
        GROUP_CONCAT(
            CONCAT(es.set_number, ':', es.weight, ':', es.reps)
            ORDER BY es.set_number
            SEPARATOR ';'
        ) as sets_data
    FROM workout_exercises we
    JOIN workouts w ON we.workout_id = w.id
    JOIN exercise_sets es ON we.id = es.exercise_id
    WHERE we.user_id = ? AND we.exercise_name = ?
    GROUP BY we.id
    ORDER BY w.created_at DESC
    LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $last_workout_query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $exercise_name);
    mysqli_stmt_execute($stmt);
    $last_workout = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Get personal bests
    $pbs = getPersonalBests($conn, $user_id, $exercise_name);
    
    // Calculate recommended sets
    $recommended = [];
    
    // If this is the first time doing this exercise
    if (!$last_workout) {
        // Default starting recommendations
        switch (true) {
            case (stripos($exercise_name, 'bench') !== false):
                $recommended[] = ['set' => 1, 'weight' => 45, 'reps' => 10, 'is_warmup' => true];
                $recommended[] = ['set' => 2, 'weight' => 65, 'reps' => 8, 'is_warmup' => true];
                $recommended[] = ['set' => 3, 'weight' => 95, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 4, 'weight' => 95, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 5, 'weight' => 95, 'reps' => 5, 'is_warmup' => false];
                break;
                
            case (stripos($exercise_name, 'squat') !== false):
                $recommended[] = ['set' => 1, 'weight' => 45, 'reps' => 10, 'is_warmup' => true];
                $recommended[] = ['set' => 2, 'weight' => 75, 'reps' => 8, 'is_warmup' => true];
                $recommended[] = ['set' => 3, 'weight' => 115, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 4, 'weight' => 115, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 5, 'weight' => 115, 'reps' => 5, 'is_warmup' => false];
                break;
                
            case (stripos($exercise_name, 'deadlift') !== false):
                $recommended[] = ['set' => 1, 'weight' => 65, 'reps' => 8, 'is_warmup' => true];
                $recommended[] = ['set' => 2, 'weight' => 95, 'reps' => 5, 'is_warmup' => true];
                $recommended[] = ['set' => 3, 'weight' => 135, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 4, 'weight' => 135, 'reps' => 5, 'is_warmup' => false];
                $recommended[] = ['set' => 5, 'weight' => 135, 'reps' => 5, 'is_warmup' => false];
                break;
                
            default:
                $recommended[] = ['set' => 1, 'weight' => 10, 'reps' => 12, 'is_warmup' => true];
                $recommended[] = ['set' => 2, 'weight' => 15, 'reps' => 10, 'is_warmup' => false];
                $recommended[] = ['set' => 3, 'weight' => 15, 'reps' => 10, 'is_warmup' => false];
                $recommended[] = ['set' => 4, 'weight' => 15, 'reps' => 10, 'is_warmup' => false];
        }
    } else {
        // Base recommendations on last workout
        $sets = [];
        if ($last_workout['sets_data']) {
            foreach (explode(';', $last_workout['sets_data']) as $set_data) {
                list($set_number, $weight, $reps) = explode(':', $set_data);
                $sets[] = [
                    'set' => (int)$set_number,
                    'weight' => (float)$weight,
                    'reps' => (int)$reps,
                    'is_warmup' => (int)$set_number <= 2 // Assume first 2 sets are warmup
                ];
            }
        }
        
        // Progressive overload: increase weight by 5% if all sets hit target reps
        $can_progress = true;
        $working_sets = array_filter($sets, function($set) {
            return !$set['is_warmup'];
        });
        
        foreach ($working_sets as $set) {
            if ($set['reps'] < 5) { // Couldn't complete minimum reps
                $can_progress = false;
                break;
            }
        }
        
        if ($can_progress && count($working_sets) > 0) {
            // Apply progressive overload
            foreach ($sets as &$set) {
                if (!$set['is_warmup']) {
                    $set['weight'] = round($set['weight'] * 1.05 / 2.5) * 2.5; // Round to nearest 2.5
                    $set['note'] = "Increased from previous " . ($set['weight'] / 1.05) . "kg";
                }
            }
        }
        
        $recommended = $sets;
    }
    
    return [
        'recommended_sets' => $recommended,
        'personal_bests' => $pbs
    ];
}

// Get RPE guidelines
function getRpeGuidelines() {
    return [
        [
            'rpe' => 10,
            'description' => 'Maximum effort, could not do more reps',
            'feeling' => 'Very hard',
            'reps_in_reserve' => 0
        ],
        [
            'rpe' => 9,
            'description' => 'Could have done 1 more rep with good form',
            'feeling' => 'Hard',
            'reps_in_reserve' => 1
        ],
        [
            'rpe' => 8,
            'description' => 'Could have done 2 more reps with good form',
            'feeling' => 'Challenging',
            'reps_in_reserve' => 2
        ],
        [
            'rpe' => 7,
            'description' => 'Could have done 3 more reps with good form',
            'feeling' => 'Moderate',
            'reps_in_reserve' => 3
        ],
        [
            'rpe' => 6,
            'description' => 'Could have done 4 more reps with good form',
            'feeling' => 'Moderate-Light',
            'reps_in_reserve' => 4
        ],
        [
            'rpe' => 5,
            'description' => 'Could have done 5+ more reps with good form',
            'feeling' => 'Light',
            'reps_in_reserve' => 5
        ]
    ];
}

// Handle request
$response = [];

switch($action) {
    case 'history':
        $response['history'] = getExerciseHistory($conn, $user_id, $exercise_name);
        break;
        
    case 'recommendations':
        $response = getRecommendedSets($conn, $user_id, $exercise_name);
        break;
        
    case 'rpe':
        $response['rpe_guidelines'] = getRpeGuidelines();
        break;
        
    default:
        http_response_code(400);
        $response['error'] = 'Invalid action';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 