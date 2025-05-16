<?php

require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout.php");
    exit;
}

if (isset($_POST['template_id']) && is_numeric($_POST['template_id'])) {
    $template_id = intval($_POST['template_id']);
    
    $_SESSION['active_template_id'] = $template_id;
    $_SESSION['start_workout_directly'] = true;
    $_SESSION['skip_template_selection'] = true; 

    $user_id = $_SESSION["user_id"];
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $template_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($template = mysqli_fetch_assoc($result)) {
        $_SESSION['active_template_name'] = $template['name'];
        
        $exercises_stmt = mysqli_prepare($conn, 
            "SELECT wte.*, e.name as exercise_name 
            FROM workout_template_exercises wte 
            JOIN exercises e ON wte.exercise_id = e.id 
            WHERE wte.workout_template_id = ? 
            ORDER BY wte.position");
        
        mysqli_stmt_bind_param($exercises_stmt, "i", $template_id);
        mysqli_stmt_execute($exercises_stmt);
        $exercises_result = mysqli_stmt_get_result($exercises_stmt);
        
        $exercises = array();
        while ($exercise = mysqli_fetch_assoc($exercises_result)) {
            $exercises[] = $exercise;
        }
        
        $_SESSION['active_template_exercises'] = $exercises;
    }
    
    header("location: workout.php");
    exit;
} else {
    header("location: workout.php");
    exit;
} 