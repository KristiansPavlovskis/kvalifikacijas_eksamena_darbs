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

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['exercise_name']) || !isset($data['action'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid data format']));
}

$exercise_name = $data['exercise_name'];
$action = $data['action'];

// Get the exercise_id from the exercise name
$query = "SELECT id FROM exercise_library WHERE exercise_name = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $exercise_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$exercise = mysqli_fetch_assoc($result);

if (!$exercise) {
    // The exercise doesn't exist in the library yet, add it
    $insert_query = "INSERT INTO exercise_library (exercise_name, popularity) VALUES (?, 1)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "s", $exercise_name);
    mysqli_stmt_execute($stmt);
    $exercise_id = mysqli_insert_id($conn);
} else {
    $exercise_id = $exercise['id'];
    
    // Increase popularity
    $update_query = "UPDATE exercise_library SET popularity = popularity + 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $exercise_id);
    mysqli_stmt_execute($stmt);
}

// Add/remove from favorites
$response = [];

if ($action === 'add') {
    // Check if already a favorite
    $check_query = "SELECT id FROM user_favorite_exercises WHERE user_id = ? AND exercise_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $exercise_id);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_stmt_get_result($stmt)->num_rows > 0;
    
    if (!$exists) {
        $add_query = "INSERT INTO user_favorite_exercises (user_id, exercise_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $exercise_id);
        $success = mysqli_stmt_execute($stmt);
        
        $response['success'] = $success;
        $response['message'] = $success ? 'Added to favorites' : 'Failed to add to favorites';
    } else {
        $response['success'] = true;
        $response['message'] = 'Already in favorites';
    }
} else if ($action === 'remove') {
    $remove_query = "DELETE FROM user_favorite_exercises WHERE user_id = ? AND exercise_id = ?";
    $stmt = mysqli_prepare($conn, $remove_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $exercise_id);
    $success = mysqli_stmt_execute($stmt);
    
    $response['success'] = $success;
    $response['message'] = $success ? 'Removed from favorites' : 'Failed to remove from favorites';
} else {
    http_response_code(400);
    $response['error'] = 'Invalid action';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 