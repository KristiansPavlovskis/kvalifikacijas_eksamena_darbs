<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit('Unauthorized');
}

require_once 'assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid data format');
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Insert workout record
    $workout_query = "INSERT INTO workouts (
        user_id, 
        workout_name, 
        notes, 
        rating, 
        duration_seconds, 
        start_time, 
        end_time, 
        total_volume,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $workout_query);
    
    // Calculate total volume
    $total_volume = 0;
    foreach ($data['exercises'] as $exercise) {
        foreach ($exercise['sets'] as $set) {
            $total_volume += $set['weight'] * $set['reps'];
        }
    }

    mysqli_stmt_bind_param($stmt, "issiissd", 
        $user_id,
        $data['name'],
        $data['notes'],
        $data['rating'],
        $data['duration'],
        $data['startTime'],
        $data['endTime'],
        $total_volume
    );

    mysqli_stmt_execute($stmt);
    $workout_id = mysqli_insert_id($conn);

    // Insert exercises and sets
    foreach ($data['exercises'] as $exercise_index => $exercise) {
        $exercise_query = "INSERT INTO workout_exercises (
            workout_id,
            user_id,
            exercise_name,
            exercise_order,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($conn, $exercise_query);
        mysqli_stmt_bind_param($stmt, "iisis",
            $workout_id,
            $user_id,
            $exercise['name'],
            $exercise_index + 1,
            $exercise['notes']
        );
        mysqli_stmt_execute($stmt);
        $exercise_id = mysqli_insert_id($conn);

        // Insert sets for this exercise
        foreach ($exercise['sets'] as $set_index => $set) {
            $set_query = "INSERT INTO exercise_sets (
                exercise_id,
                workout_id,
                user_id,
                set_number,
                weight,
                reps,
                rpe,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = mysqli_prepare($conn, $set_query);
            mysqli_stmt_bind_param($stmt, "iiiiiii",
                $exercise_id,
                $workout_id,
                $user_id,
                $set_index + 1,
                $set['weight'],
                $set['reps'],
                $set['rpe']
            );
            mysqli_stmt_execute($stmt);
        }
    }

    // Update user's workout stats
    $stats_query = "INSERT INTO user_workout_stats (
        user_id,
        total_workouts,
        total_volume,
        last_workout_date
    ) VALUES (?, 1, ?, NOW())
    ON DUPLICATE KEY UPDATE
        total_workouts = total_workouts + 1,
        total_volume = total_volume + ?,
        last_workout_date = NOW()";

    $stmt = mysqli_prepare($conn, $stats_query);
    mysqli_stmt_bind_param($stmt, "idd",
        $user_id,
        $total_volume,
        $total_volume
    );
    mysqli_stmt_execute($stmt);

    // Commit transaction
    mysqli_commit($conn);

    // Return workout ID
    echo $workout_id;

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    http_response_code(500);
    exit('Error saving workout: ' . $e->getMessage());
}
?> 