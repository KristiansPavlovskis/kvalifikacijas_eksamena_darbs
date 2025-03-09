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
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

function getExerciseHistory($conn, $user_id) {
    $query = "SELECT 
        we.exercise_name,
        MAX(es.weight) as max_weight,
        AVG(es.reps) as avg_reps,
        COUNT(DISTINCT we.workout_id) as usage_count,
        MAX(we.created_at) as last_used
    FROM workout_exercises we
    LEFT JOIN exercise_sets es ON we.id = es.exercise_id
    WHERE we.user_id = ?
    GROUP BY we.exercise_name
    ORDER BY last_used DESC, usage_count DESC
    LIMIT 20";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function searchExercises($conn, $search) {
    $search = "%$search%";
    $query = "SELECT 
        el.*,
        mg.name as muscle_group_name,
        mg.body_part,
        eq.name as equipment_name
    FROM exercise_library el
    LEFT JOIN muscle_groups mg ON el.muscle_group_id = mg.id
    LEFT JOIN equipment eq ON el.equipment_id = eq.id
    WHERE el.exercise_name LIKE ? 
    OR el.alternative_names LIKE ? 
    OR mg.name LIKE ?
    ORDER BY el.popularity DESC
    LIMIT 15";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $search, $search, $search);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function getFavorites($conn, $user_id) {
    $query = "SELECT 
        el.*,
        mg.name as muscle_group_name,
        eq.name as equipment_name,
        uf.created_at as favorited_at
    FROM user_favorite_exercises uf
    JOIN exercise_library el ON uf.exercise_id = el.id
    LEFT JOIN muscle_groups mg ON el.muscle_group_id = mg.id
    LEFT JOIN equipment eq ON el.equipment_id = eq.id
    WHERE uf.user_id = ?
    ORDER BY uf.created_at DESC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function getPopularExercises($conn) {
    $query = "SELECT 
        el.*,
        mg.name as muscle_group_name,
        mg.body_part,
        eq.name as equipment_name,
        COUNT(we.id) as global_usage_count
    FROM exercise_library el
    LEFT JOIN muscle_groups mg ON el.muscle_group_id = mg.id
    LEFT JOIN equipment eq ON el.equipment_id = eq.id
    LEFT JOIN workout_exercises we ON el.exercise_name = we.exercise_name
    GROUP BY el.id
    ORDER BY global_usage_count DESC, el.popularity DESC
    LIMIT 20";

    return mysqli_query($conn, $query);
}

function getMuscleGroups($conn) {
    $query = "SELECT * FROM muscle_groups ORDER BY body_part, name";
    return mysqli_query($conn, $query);
}

function getEquipment($conn) {
    $query = "SELECT * FROM equipment ORDER BY name";
    return mysqli_query($conn, $query);
}

// Handle different actions
$response = [];

switch($action) {
    case 'search':
        if (strlen($search) >= 2) {
            $results = searchExercises($conn, $search);
            $exercises = [];
            while ($row = mysqli_fetch_assoc($results)) {
                // Get user's history with this exercise
                $history_query = "SELECT 
                    MAX(es.weight) as personal_best_weight,
                    MAX(es.reps) as max_reps,
                    COUNT(DISTINCT we.workout_id) as times_performed,
                    MAX(we.created_at) as last_performed
                FROM workout_exercises we
                LEFT JOIN exercise_sets es ON we.id = es.exercise_id
                WHERE we.user_id = ? AND we.exercise_name = ?
                GROUP BY we.exercise_name";

                $stmt = mysqli_prepare($conn, $history_query);
                mysqli_stmt_bind_param($stmt, "is", $user_id, $row['exercise_name']);
                mysqli_stmt_execute($stmt);
                $history = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                $row['user_stats'] = $history;
                $exercises[] = $row;
            }
            $response['exercises'] = $exercises;
        }
        break;

    case 'recent':
        $results = getExerciseHistory($conn, $user_id);
        $exercises = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $exercises[] = $row;
        }
        $response['exercises'] = $exercises;
        break;

    case 'favorites':
        $results = getFavorites($conn, $user_id);
        $exercises = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $exercises[] = $row;
        }
        $response['exercises'] = $exercises;
        break;

    case 'popular':
        $results = getPopularExercises($conn);
        $exercises = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $exercises[] = $row;
        }
        $response['exercises'] = $exercises;
        break;

    case 'categories':
        $response['muscle_groups'] = [];
        $results = getMuscleGroups($conn);
        while ($row = mysqli_fetch_assoc($results)) {
            $response['muscle_groups'][] = $row;
        }

        $response['equipment'] = [];
        $results = getEquipment($conn);
        while ($row = mysqli_fetch_assoc($results)) {
            $response['equipment'][] = $row;
        }
        break;

    default:
        http_response_code(400);
        $response['error'] = 'Invalid action';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 