<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/quick-workout.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];

$workout_saved = false;
$workout_message = "";
$workout_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_workout"])) {
    $workoutData = json_decode($_POST["workout_data"], true);
    
    if (!empty($workoutData["exercises"])) {
        try {
            $workout_id = saveWorkoutToDatabase($conn, $user_id, $workoutData);
            $workout_saved = true;
            $workout_message = "Workout saved successfully!";
        } catch (Exception $e) {
            $workout_message = "Error saving workout: " . $e->getMessage();
        }
    } else {
        $workout_message = "No exercises recorded. Please add at least one exercise to save your workout.";
    }
}

function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

try {
    if (tableExists($conn, 'workout_exercises')) {
        $history_query = "SELECT we.exercise_name, COUNT(*) as count
                        FROM workout_exercises we
                        JOIN workouts w ON we.workout_id = w.id
                            WHERE w.user_id = ? 
                        GROUP BY we.exercise_name
                        ORDER BY count DESC
                        LIMIT 10";
        $stmt = mysqli_prepare($conn, $history_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare history query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $exercise_history = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $exercise_history[] = $row;
        }
    } else {
        $exercise_history = false;
    }
} catch (Exception $e) {
    error_log("Error fetching exercise history: " . $e->getMessage());
    $exercise_history = false;
}

try {
    if (tableExists($conn, 'exercise_library')) {
        $common_exercises_query = "SELECT exercise_name, 
                              el.muscle_group_id as muscle_group, 
                              el.equipment_id as equipment_needed 
                              FROM exercise_library el
                              ORDER BY popularity DESC 
                                ";
        $common_exercises = mysqli_query($conn, $common_exercises_query);
        if ($common_exercises === false) {
            throw new Exception("Failed to fetch common exercises: " . mysqli_error($conn));
        }
    } else {
        $common_exercises = false;
    }
} catch (Exception $e) {
    error_log("Error fetching common exercises: " . $e->getMessage());
    $common_exercises = false;
}

try {
    if (tableExists($conn, 'user_favorite_exercises') && tableExists($conn, 'exercise_library')) {
        $favorites_query = "SELECT el.exercise_name 
                        FROM user_favorite_exercises uf
                        JOIN exercise_library el ON uf.exercise_id = el.id
                        WHERE uf.user_id = ?";
        $stmt = mysqli_prepare($conn, $favorites_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare favorites query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $favorite_exercises = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $favorite_exercises[] = $row;
        }
    } else {
        $favorite_exercises = false;
    }
} catch (Exception $e) {
    error_log("Error fetching favorite exercises: " . $e->getMessage());
    $favorite_exercises = false;
}

function saveWorkoutToDatabase($conn, $userId, $workoutData) {
    mysqli_begin_transaction($conn);
        
    try {
        $workout_name = isset($workoutData["name"]) ? $workoutData["name"] : "Quick Workout";
        $duration = isset($workoutData["duration"]) ? $workoutData["duration"] : 0;
        $intensity = calculateWorkoutIntensity($workoutData["exercises"]);
        $calories_burned = calculateCaloriesBurned($duration, $intensity, $userId);
        
        if (!tableExists($conn, 'workouts')) {
            $create_workouts_table = "CREATE TABLE workouts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                workout_name VARCHAR(100) NOT NULL,
                duration INT NOT NULL,
                intensity DECIMAL(3,1) NOT NULL,
                calories_burned INT NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";
            
            if (!mysqli_query($conn, $create_workouts_table)) {
                throw new Exception("Error creating workouts table: " . mysqli_error($conn));
            }
        }
        
        $workout_query = "INSERT INTO workouts (user_id, workout_name, duration, intensity, calories_burned, notes) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $workout_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare workout query: " . mysqli_error($conn));
        }
        
        $notes = isset($workoutData["notes"]) ? $workoutData["notes"] : "";
        mysqli_stmt_bind_param($stmt, "isidis", $userId, $workout_name, $duration, $intensity, $calories_burned, $notes);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute workout query: " . mysqli_error($conn));
        }
        
        $workout_id = mysqli_insert_id($conn);
        
        if (!tableExists($conn, 'workout_exercises')) {
            $create_exercises_table = "CREATE TABLE workout_exercises (
                id INT PRIMARY KEY AUTO_INCREMENT,
                workout_id INT NOT NULL,
                exercise_name VARCHAR(100) NOT NULL,
                sets INT NOT NULL,
                reps INT NOT NULL,
                weight DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
            )";
            
            if (!mysqli_query($conn, $create_exercises_table)) {
                throw new Exception("Error creating workout_exercises table: " . mysqli_error($conn));
            }
        }
        
        $exercise_query = "INSERT INTO workout_exercises (workout_id, exercise_name, sets, reps, weight) 
                          VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $exercise_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare exercise query: " . mysqli_error($conn));
        }
        
        foreach ($workoutData["exercises"] as $exercise) {
            $exercise_name = $exercise["name"];
            $sets = $exercise["sets"];
            $reps = $exercise["reps"];
            $weight = isset($exercise["weight"]) ? $exercise["weight"] : 0;
            
            mysqli_stmt_bind_param($stmt, "isiid", $workout_id, $exercise_name, $sets, $reps, $weight);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute exercise query: " . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        
        return $workout_id;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

function calculateWorkoutIntensity($exercises) {
    if (empty($exercises)) {
        return 1.0;
    }
    
    $total_volume = 0;
    $exercise_count = count($exercises);
    
    foreach ($exercises as $exercise) {
        $sets = isset($exercise["sets"]) ? $exercise["sets"] : 0;
        $reps = isset($exercise["reps"]) ? $exercise["reps"] : 0;
        $weight = isset($exercise["weight"]) ? $exercise["weight"] : 0;
        
        $volume = $sets * $reps * ($weight > 0 ? $weight : 0.5);
        $total_volume += $volume;
    }
    
    $avg_volume = $total_volume / $exercise_count;
    
    if ($avg_volume < 50) {
        return 1.0; // Very light
    } else if ($avg_volume < 150) {
        return 2.0; // Light
    } else if ($avg_volume < 300) {
        return 3.0; // Moderate
    } else if ($avg_volume < 500) {
        return 4.0; // Intense
    } else {
        return 5.0; // Very intense
    }
}

function calculateCaloriesBurned($durationMinutes, $intensity, $userId) {
    $weight_kg = 70; // Average weight
    $age = 30; // Average age
    $gender = 'M'; // Default gender
    
    try {
        $query = "SELECT weight, birthdate, gender FROM user_profile WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
                if (!empty($row['weight'])) {
                    $weight_kg = $row['weight'];
                }
                
                if (!empty($row['birthdate'])) {
                    $birthdate = new DateTime($row['birthdate']);
                    $today = new DateTime();
                    $age = $birthdate->diff($today)->y;
                }
                
                if (!empty($row['gender'])) {
                    $gender = $row['gender'];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching user data for calorie calculation: " . $e->getMessage());
    }
    
    $met_values = [
        1.0 => 2.5,  // Very light (walking)
        2.0 => 4.0,  // Light (light calisthenics)
        3.0 => 6.0,  // Moderate (general weight lifting)
        4.0 => 8.0,  // Intense (heavy weight lifting)
        5.0 => 10.0  // Very intense (circuit training with minimal rest)
    ];
    
    $met = isset($met_values[$intensity]) ? $met_values[$intensity] : 6.0;
    
    if ($gender == 'M') {
        $estimated_height_cm = 170; 
        $bmr = 88.362 + (13.397 * $weight_kg) + (4.799 * $estimated_height_cm) - (5.677 * $age);
    } else {
        $estimated_height_cm = 160;
        $bmr = 447.593 + (9.247 * $weight_kg) + (3.098 * $estimated_height_cm) - (4.330 * $age);
    }
    
    $calories_per_minute = ($bmr / 1440) * $met;
    
    $total_calories = round($calories_per_minute * $durationMinutes);
    
    return $total_calories;
}

function calculateCaloriesBurnedAccurate($workoutData) {
    $durationMinutes = $workoutData['duration'] / 60;
    
    $totalVolume = $workoutData['exercises']->reduce(function($total, $exercise) {
        return $total + $exercise['sets']->reduce(function($setTotal, $set) {
            return $setTotal + ($set['weight'] * $set['reps']);
        }, 0);
    }, 0);
    
    $totalReps = $workoutData['exercises']->reduce(function($total, $exercise) {
        return $total + $exercise['sets']->reduce(function($setTotal, $set) {
            return $setTotal + $set['reps'];
        }, 0);
    }, 0);
    
    $avgRPE = getAverageRPE();
    $volumePerMinute = $totalVolume / max(1, $durationMinutes);
    
    $baseMet = 3.5; // Light effort
    
    if ($volumePerMinute > 100) {
        $baseMet = 6.0; // Vigorous effort
    } else if ($volumePerMinute > 50) {
        $baseMet = 5.0; // Moderate to vigorous
    } else if ($volumePerMinute > 20) {
        $baseMet = 4.0; // Moderate effort
    }
    
    $baseMet += ($avgRPE - 5) * 0.2; // Adjust MET by RPE
    
    $userWeight = 75;
    
    $timeHours = $durationMinutes / 60;
    $calories = round($baseMet * $userWeight * $timeHours);
    
    if ($totalReps <= 1) {
        return min(5, $calories);
    }
    
    if ($durationMinutes < 1) {
        return min(10, $calories);
    }
    
    return max(1, $calories);
}

function getAverageRPE() {
    $allRPE = $workoutData['exercises']->reduce(function($all, $exercise) {
        return $all . $exercise['sets']->map(function($set) {
            return $set['rpe'];
        })->implode(',');
    }, '');
    
    return $allRPE ? array_sum(explode(',', $allRPE)) / count(explode(',', $allRPE)) : 5;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Workout - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Core variables and base styling */
        :root {
            --primary: #4361ee;
            --primary-light: #4cc9f0;
            --primary-dark: #3a56d4;
            --secondary: #f72585;
            --secondary-light: #ff5c8a;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --dark: #0f0f1a;
            --dark-card: #1a1a2e;
            --gray-dark: #2b2b3d;
            --gray-light: rgba(255, 255, 255, 0.7);
            --gradient-blue: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            --gradient-purple: linear-gradient(135deg, #9d4edd, #c77dff);
            --gradient-pink: linear-gradient(135deg, #f72585, #ff5c8a);
            --gradient-green: linear-gradient(135deg, #06d6a0, #64dfdf);
            --gradient-orange: linear-gradient(135deg, #fb8500, #ffb703);
            --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(67, 97, 238, 0.05) 0%, transparent 200px),
                radial-gradient(circle at 70% 80%, rgba(67, 97, 238, 0.05) 0%, transparent 200px);
            width: 100%;
            overflow-x: hidden;
        }

        .dashboard {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar styling */
        .sidebar {
            background-color: var(--dark-card);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px 20px;
            position: fixed;
            width: var(--sidebar-width);
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 25px;
        }

        .sidebar-logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
            text-decoration: none;
            font-family: 'Koulen', sans-serif;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            margin-bottom: 15px;
            position: relative;
            background-color: var(--gray-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar-avatar::after {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            bottom: -2px;
            left: -2px;
            background: var(--gradient-purple);
            z-index: -1;
            border-radius: 50%;
        }

        .sidebar-user-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-user-email {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 15px;
        }

        .sidebar-user-since {
            font-size: 0.85rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .sidebar-nav {
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .sidebar-nav-title {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--gray-light);
            margin-bottom: 15px;
            padding-left: 10px;
        }

        .sidebar-nav-items {
            list-style: none;
        }

        .sidebar-nav-item {
            margin-bottom: 8px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-nav-link:hover, 
        .sidebar-nav-link.active {
            background-color: rgba(157, 78, 221, 0.1);
            color: var(--primary-light);
        }

        .sidebar-nav-link.active {
            background-color: #9d4edd;
            color: white;
            box-shadow: 0 5px 10px rgba(157, 78, 221, 0.3);
        }

        .sidebar-nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-footer-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .sidebar-footer-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Main content styling */
        .main-content {
            flex: 1;
            padding: 30px 40px;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .page-actions {
            display: flex;
            gap: 15px;
        }

        /* Workout Summary Section */
        .summary-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .summary-card {
            background-color: var(--dark-card);
            border-radius: 16px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .summary-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            margin-bottom: 15px;
        }

        .icon-timer { background: var(--gradient-purple); }
        .icon-exercise { background: var(--gradient-blue); }
        .icon-calories { background: var(--gradient-pink); }
        .icon-intensity { background: var(--gradient-orange); }

        .summary-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.03));
            border-radius: 0 0 0 100%;
        }

        /* Exercise Card styling */
        .exercise-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .exercise-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .exercise-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .exercise-title i {
            color: #9d4edd;
        }

        .exercise-title span {
            font-size: 0.9rem;
            color: var(--gray-light);
            font-weight: 400;
            margin-left: 8px;
        }

        .exercise-actions {
            display: flex;
            gap: 8px;
        }

        .exercise-data {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .data-item {
            display: flex;
            flex-direction: column;
        }

        .data-label {
            font-size: 0.85rem;
            color: var(--gray-light);
            margin-bottom: 3px;
        }

        .data-value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Dashboard grid layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* Section styling */
        .section {
            background-color: var(--dark-card);
            border-radius: 20px;
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--card-shadow);
        }

        .section:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #9d4edd;
        }

        .section-action {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #9d4edd;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: 8px;
            background-color: rgba(157, 78, 221, 0.08);
            cursor: pointer;
        }

        .section-action:hover {
            background-color: rgba(157, 78, 221, 0.15);
            transform: translateX(3px);
        }

        .section-body {
            padding: 25px 30px;
        }

        /* Timer styling */
        .timer-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .timer-display {
            font-size: 3.5rem;
            font-weight: 700;
            margin: 15px 0 25px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 10px rgba(157, 78, 221, 0.5);
        }

        .timer-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        /* Exercise tabs */
        .exercise-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .exercise-tab {
            padding: 10px 20px;
            font-weight: 500;
            color: var(--gray-light);
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 2px solid transparent;
        }

        .exercise-tab.active {
            color: white;
            border-bottom: 2px solid #9d4edd;
        }

        .exercise-tab:hover:not(.active) {
            color: white;
            border-bottom: 2px solid rgba(157, 78, 221, 0.3);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Exercise lists */
        .exercise-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .exercise-list::-webkit-scrollbar {
            width: 6px;
        }

        .exercise-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .exercise-list::-webkit-scrollbar-thumb {
            background: rgba(157, 78, 221, 0.3);
            border-radius: 10px;
        }

        .exercise-list::-webkit-scrollbar-thumb:hover {
            background: rgba(157, 78, 221, 0.5);
        }

        .exercise-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: var(--transition);
            cursor: pointer;
        }

        .exercise-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
        }

        .exercise-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .exercise-name i {
            color: #9d4edd;
        }

        .exercise-item-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            background-color: rgba(157, 78, 221, 0.2);
        }

        /* Current workout section */
        .current-workout-exercises {
            margin-bottom: 25px;
        }

        .workout-notes {
            margin-bottom: 25px;
        }

        .workout-notes textarea {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 12px 15px;
            color: white;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 100px;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: var(--gradient-purple);
            color: white;
            box-shadow: 0 5px 15px rgba(157, 78, 221, 0.2);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.3);
            transform: translateY(-3px);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.12);
            transform: translateY(-3px);
        }

        .btn-success {
            background: var(--gradient-green);
            color: white;
            box-shadow: 0 5px 15px rgba(6, 214, 160, 0.2);
        }

        .btn-success:hover {
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.3);
            transform: translateY(-3px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef476f, #e63946);
            color: white;
        }

        .btn-danger:hover {
            box-shadow: 0 8px 20px rgba(239, 71, 111, 0.3);
            transform: translateY(-3px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffd166, #ffb703);
            color: white;
        }

        .btn-warning:hover {
            box-shadow: 0 8px 20px rgba(255, 209, 102, 0.3);
            transform: translateY(-3px);
        }

        .btn-lg {
            padding: 12px 24px;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Modal styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 15, 26, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: var(--dark-card);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(20px);
            transition: var(--transition);
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--gray-light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: white;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Add exercise form */
        .add-exercise-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .add-exercise-form .form-row {
            grid-template-columns: 1fr 1fr;
        }

        /* Rest timer styling */
        .rest-timer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
        }

        .rest-timer-circle {
            position: relative;
            width: 180px;
            height: 180px;
            margin-bottom: 20px;
        }

        .rest-timer-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .rest-timer-progress {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(#9d4edd var(--progress), transparent 0);
            transform: rotate(-90deg);
        }

        .rest-timer-display {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .rest-timer-presets {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }

        .rest-preset-btn {
            background-color: rgba(255, 255, 255, 0.08);
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .rest-preset-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .rest-preset-btn.active {
            background-color: #9d4edd;
        }

        /* Steps navigator */
        .steps-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 0 auto 40px;
            max-width: 800px;
        }

        .steps-container::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 60px;
            right: 60px;
            height: 2px;
            background-color: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 120px;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--dark-card);
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .step-label {
            font-size: 0.9rem;
            color: var(--gray-light);
            transition: var(--transition);
        }

        .step-item.active .step-number {
            background-color: #9d4edd;
            border-color: #9d4edd;
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.5);
        }

        .step-item.active .step-label {
            color: white;
            font-weight: 500;
        }

        .step-item.completed .step-number {
            background-color: var(--success);
            border-color: var(--success);
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        /* Search input */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-light);
        }

        /* Responsive design */
        @media (max-width: 1400px) {
            .summary-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .summary-container {
                grid-template-columns: 1fr;
            }
            
            .exercise-data {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .steps-container {
                overflow-x: auto;
                padding-bottom: 15px;
            }
            
            .steps-container::before {
                left: 0;
                right: 0;
            }
            
            .step-item {
                min-width: 100px;
            }
        }

        /* Mobile navigation */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: var(--dark-card);
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 1000;
        }

        .mobile-nav-links {
            display: flex;
            justify-content: space-around;
        }

        .mobile-nav-link {
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .mobile-nav-link.active {
            color: #9d4edd;
        }

        .mobile-nav-link i {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        @media (max-width: 992px) {
            .mobile-nav {
                display: block;
            }
            
            .main-content {
                padding-bottom: 70px;
            }
        }
        
        /* Fixed input containers for weight/reps */
        .weight-input-container,
        .reps-input-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .weight-input-container input,
        .reps-input-container input {
            flex: 1;
            text-align: center;
            margin: 0 5px;
        }
        
        .weight-btn,
        .reps-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            padding: 0;
        }
        
        .weight-presets,
        .reps-presets {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        
        .weight-preset-btn,
        .reps-preset-btn {
            flex: 1;
            padding: 6px;
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: white;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .weight-preset-btn:hover,
        .reps-preset-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .rpe-input-container {
            padding: 0 10px;
        }
        
        .rpe-display {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Custom exercise input */
        .custom-input-container {
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .custom-exercise-input {
            display: flex;
            gap: 10px;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Include the sidebar -->
        <?php require_once 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Quick Workout</h1>
                <div class="page-actions">
                    <button class="btn btn-primary" id="startNewWorkoutBtn">
                        <i class="fas fa-plus"></i> Start New Workout
                    </button>
                            </div>
                                </div>

            <!-- Steps Navigator -->
            <div class="steps-container">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Select Exercises</div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Track Workout</div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Complete & Save</div>
                        </div>
                </div>

        <!-- Step 1: Select Exercises -->
            <div class="step-content active" id="step1-content">
                <div class="dashboard-grid">
                    <!-- Exercise Selection Panel -->
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-dumbbell"></i> Exercise Library
                            </h2>
                            <!-- Removed the Create Exercise button which doesn't make sense in this context -->
                        </div>
                        
                        <div class="section-body">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" placeholder="Search exercises..." id="exerciseSearch">
                </div>
                
                            <div class="exercise-tabs">
                                <div class="exercise-tab active" data-tab="recent">Recent</div>
                                <div class="exercise-tab" data-tab="favorites">Favorites</div>
                                <div class="exercise-tab" data-tab="popular">Popular</div>
                                <!-- Changed this to better match our actual capabilities -->
                                <div class="exercise-tab" data-tab="custom">Custom</div>
                </div>
                
                            <div class="tab-content active" id="recent-tab">
                                <div class="exercise-list">
                                    <?php if (!empty($exercise_history)): ?>
                                        <?php foreach ($exercise_history as $exercise): ?>
                                            <div class="exercise-item" data-name="<?= htmlspecialchars($exercise['exercise_name']) ?>">
                                                <div class="exercise-name">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <?= htmlspecialchars($exercise['exercise_name']) ?>
                                                </div>
                                                <div class="exercise-item-badge">
                                                    <i class="fas fa-history"></i> <?= $exercise['count'] ?> times
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-message">
                                            <p>No recent exercises found. Start tracking your workouts!</p>
                                        </div>
                                    <?php endif; ?>
                    </div>
                </div>
                
                            <div class="tab-content" id="favorites-tab">
                                <div class="exercise-list">
                                    <?php if (!empty($favorite_exercises)): ?>
                                        <?php foreach ($favorite_exercises as $exercise): ?>
                                            <div class="exercise-item" data-name="<?= htmlspecialchars($exercise['exercise_name']) ?>">
                                                <div class="exercise-name">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <?= htmlspecialchars($exercise['exercise_name']) ?>
                                                </div>
                                                <div class="exercise-item-badge">
                                                    <i class="fas fa-star"></i> Favorite
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-message">
                                            <p>No favorite exercises yet. Mark exercises as favorites to see them here.</p>
                                        </div>
                                    <?php endif; ?>
                    </div>
                </div>
                
                            <div class="tab-content" id="popular-tab">
                                <div class="exercise-list">
                                    <?php if (!empty($common_exercises)): ?>
                                        <?php while ($exercise = mysqli_fetch_assoc($common_exercises)): ?>
                                            <div class="exercise-item" data-name="<?= htmlspecialchars($exercise['exercise_name']) ?>">
                                                <div class="exercise-name">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <?= htmlspecialchars($exercise['exercise_name']) ?>
                </div>
                                                <div class="exercise-item-badge">
                                                    <i class="fas fa-fire"></i> Popular
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="empty-message">
                                            <p>No popular exercises found. Please set up the exercise database.</p>
                                        </div>
                                    <?php endif; ?>
            </div>
        </div>
        
                            <div class="tab-content" id="custom-tab">
                                <div class="exercise-list">
                                    <div class="custom-input-container">
                                        <div class="form-group">
                                            <label for="customExerciseName">Add Custom Exercise</label>
                                            <div class="custom-exercise-input">
                                                <input type="text" id="customExerciseName" class="form-control" placeholder="Enter exercise name">
                                                <button class="btn btn-primary" id="addCustomExerciseBtn">
                                                    <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                                        <p class="help-text">Enter a custom exercise name to add to your workout</p>
                            </div>
                            </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected Exercises Panel -->
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-clipboard-list"></i> Selected Exercises
                            </h2>
                            <button class="section-action" id="clearSelectedBtn">
                                <i class="fas fa-trash"></i> Clear All
                                </button>
                            </div>
                        
                        <div class="section-body">
                            <div id="selectedExercisesList" class="selected-exercises-list">
                                <div class="empty-message">
                                    <p>No exercises selected yet. Click on exercises from the library to add them to your workout.</p>
                        </div>
                    </div>
                    
                            <div class="form-actions">
                                <button class="btn btn-primary btn-lg" id="beginWorkoutBtn" disabled>
                                    <i class="fas fa-play"></i> Begin Workout
                                </button>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                
            <!-- Step 2: Track Workout -->
            <div class="step-content" id="step2-content">
                <div class="dashboard-grid">
                    <!-- Workout Tracking Panel -->
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-stopwatch"></i> Current Exercise
                            </h2>
                            <div class="workout-timer">
                                <i class="fas fa-clock"></i> <span id="workoutTimerDisplay">00:00:00</span>
                            </div>
                        </div>
                        
                        <div class="section-body">
                            <div class="current-exercise-container">
                                <h3 id="currentExerciseName" class="current-exercise-name">Exercise Name</h3>
                                <p class="exercise-index-display">
                                    Exercise <span id="currentExerciseIndex">1</span> of <span id="totalExercises">0</span>
                                </p>
                                
                                <div class="sets-progress">
                                    <div class="sets-progress-bar">
                                        <div class="sets-progress-fill" id="setsProgressFill" style="width: 0%;"></div>
                                    </div>
                                    <div class="sets-progress-text">
                                        <span id="completedSetsCount">0</span>/<span id="targetSetsCount">4</span> sets completed
                            </div>
                        </div>
                        
                                <div class="set-form">
                                    <div class="form-row">
                                        <div class="form-group">
                                <label for="weightInput">Weight (kg)</label>
                                            <!-- Fixed the input with clear buttons -->
                                            <div class="weight-input-container">
                                                <button class="btn btn-sm btn-secondary weight-btn" id="decrementWeight">-</button>
                                                <input type="number" id="weightInput" class="form-control" value="10" min="0">
                                                <button class="btn btn-sm btn-secondary weight-btn" id="incrementWeight">+</button>
                                </div>
                                            <!-- Simplified presets -->
                                            <div class="weight-presets">
                                                <button class="weight-preset-btn" data-value="5">5</button>
                                                <button class="weight-preset-btn" data-value="10">10</button>
                                                <button class="weight-preset-btn" data-value="20">20</button>
                                                <button class="weight-preset-btn" data-value="40">40</button>
                                </div>
                            </div>
                            
                                        <div class="form-group">
                                            <label for="repsInput">Reps</label>
                                            <!-- Fixed the input with clear buttons -->
                                            <div class="reps-input-container">
                                                <button class="btn btn-sm btn-secondary reps-btn" id="decrementReps">-</button>
                                                <input type="number" id="repsInput" class="form-control" value="8" min="1">
                                                <button class="btn btn-sm btn-secondary reps-btn" id="incrementReps">+</button>
                                </div>
                                            <!-- Simplified presets -->
                                            <div class="reps-presets">
                                                <button class="reps-preset-btn" data-value="8">8</button>
                                                <button class="reps-preset-btn" data-value="10">10</button>
                                                <button class="reps-preset-btn" data-value="12">12</button>
                                                <button class="reps-preset-btn" data-value="15">15</button>
                                </div>
                            </div>
                            
                                        <div class="form-group">
                                            <label for="rpeInput">RPE (1-10)</label>
                                            <div class="rpe-input-container">
                                                <input type="range" id="rpeInput" class="form-control" min="1" max="10" value="7" step="1">
                                                <div class="rpe-display">
                                                    <span id="rpeValue">7</span> / 10
                                    </div>
                                </div>
                                </div>
                            </div>
                            
                                    <div class="set-actions">
                                        <button class="btn btn-success" id="completeSetBtn">
                                    <i class="fas fa-check"></i> Complete Set
                                </button>
                                        <button class="btn btn-secondary" id="skipSetBtn">
                                            <i class="fas fa-forward"></i> Skip Set
                                </button>
                            </div>
                        </div>
                        
                                <div class="completed-sets">
                                    <h4><i class="fas fa-history"></i> Completed Sets</h4>
                                    <div class="completed-sets-table">
                                        <div class="table-header">
                                            <div class="table-cell">Set</div>
                                            <div class="table-cell">Weight</div>
                                            <div class="table-cell">Reps</div>
                                            <div class="table-cell">RPE</div>
                                </div>
                                        <div id="completedSetsTableBody" class="table-body">
                                            <div class="empty-message">No sets completed yet</div>
                                </div>
                            </div>
                        </div>
                        
                                <div class="exercise-navigation">
                                    <button class="btn btn-secondary" id="prevExerciseBtn" disabled>
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                                    <button class="btn btn-primary" id="nextExerciseBtn">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
                    <!-- Workout Info Panel -->
                    <div>
                        <div class="section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-clipboard-list"></i> Workout Plan
                                </h2>
                            </div>
                            
                            <div class="section-body">
                                <div id="exercisePlanList" class="exercise-plan-list">
                                    <!-- Will be populated with JavaScript -->
                                </div>
                                </div>
                                    </div>

                        <div class="section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-hourglass-half"></i> Rest Timer
                                </h2>
                                </div>
                
                            <div class="section-body">
                                <div class="rest-timer">
                                    <div class="rest-timer-circle">
                                        <div class="rest-timer-background"></div>
                                        <div class="rest-timer-progress" style="--progress: 0%"></div>
                                        <div class="rest-timer-display" id="restTimerDisplay">00:00</div>
                    </div>
                    
                                    <div class="rest-timer-presets">
                                        <button class="rest-preset-btn" data-seconds="30">30s</button>
                                        <button class="rest-preset-btn active" data-seconds="60">60s</button>
                                        <button class="rest-preset-btn" data-seconds="90">90s</button>
                                        <button class="rest-preset-btn" data-seconds="120">2m</button>
                                        <button class="rest-preset-btn" data-seconds="180">3m</button>
                    </div>
                    
                                    <div class="rest-timer-controls">
                                        <button class="btn btn-primary" id="startRestBtn">
                                            <i class="fas fa-play"></i> Start Rest
                                        </button>
                                        <button class="btn btn-danger" id="stopRestBtn" style="display:none;">
                                            <i class="fas fa-stop"></i> Stop Rest
                                        </button>
                        </div>
                    </div>
                    
                                <div class="finish-workout-container">
                                    <button class="btn btn-warning btn-lg" id="finishWorkoutBtn">
                                        <i class="fas fa-flag-checkered"></i> Finish Workout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                    
            <!-- Step 3: Complete & Save -->
            <div class="step-content" id="step3-content">
                <div class="dashboard-grid">
                    <!-- Workout Summary Panel -->
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-trophy"></i> Workout Summary
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <div class="summary-container">
                                <div class="summary-card">
                                    <div class="summary-icon icon-timer">
                            <i class="fas fa-clock"></i>
                        </div>
                                    <div class="summary-value" id="summaryDuration">00:00:00</div>
                                    <div class="summary-label">Duration</div>
                                </div>
                                
                                <div class="summary-card">
                                    <div class="summary-icon icon-exercise">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                                    <div class="summary-value" id="summaryExercises">0</div>
                                    <div class="summary-label">Exercises</div>
                        </div>
                                
                                <div class="summary-card">
                                    <div class="summary-icon icon-calories">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="summary-value" id="summaryCalories">0</div>
                                    <div class="summary-label">Calories Burned</div>
                    </div>
                    
                                <div class="summary-card">
                                    <div class="summary-icon icon-intensity">
                                        <i class="fas fa-tachometer-alt"></i>
                                    </div>
                                    <div class="summary-value" id="summaryIntensity">0</div>
                                    <div class="summary-label">Workout Intensity</div>
                                </div>
                            </div>
                            
                            <div class="exercise-summary">
                                <h3><i class="fas fa-clipboard-list"></i> Completed Exercises</h3>
                                <div id="exerciseSummaryList" class="exercise-summary-list">
                                    <!-- Will be populated with JavaScript -->
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="workoutNameInput">Workout Name</label>
                                <input type="text" id="workoutNameInput" class="form-control" value="Quick Workout" placeholder="Name your workout">
                            </div>
                            
                            <div class="form-group">
                                <label for="workoutNotesInput">Workout Notes</label>
                                <textarea id="workoutNotesInput" class="form-control" rows="3" placeholder="Add notes about your workout (optional)"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="saveAsTemplateCheckbox">
                                    <label for="saveAsTemplateCheckbox">Save as workout template for future use</label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button class="btn btn-success btn-lg" id="saveWorkoutBtn">
                            <i class="fas fa-save"></i> Save Workout
                                    </button>
                                <button class="btn btn-secondary" id="discardWorkoutBtn">
                                    <i class="fas fa-trash"></i> Discard
                                    </button>
                                </div>
                            </div>
                    </div>
                    
                    <!-- Additional Stats Panel -->
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-chart-bar"></i> Statistics
                            </h2>
        </div>
                        
                        <div class="section-body">
                            <div class="stats-container">
                                <div class="stat-item">
                                    <div class="stat-label">Total Volume</div>
                                    <div class="stat-value" id="statsTotalVolume">0 kg</div>
    </div>

                                <div class="stat-item">
                                    <div class="stat-label">Total Sets</div>
                                    <div class="stat-value" id="statsTotalSets">0</div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-label">Total Reps</div>
                                    <div class="stat-value" id="statsTotalReps">0</div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-label">Average RPE</div>
                                    <div class="stat-value" id="statsAvgRPE">0</div>
                                </div>
                            </div>
                            
                            <div class="workout-progress">
                                <h3><i class="fas fa-chart-line"></i> Progress Trends</h3>
                                <div class="chart-container">
                                    <canvas id="workoutProgressChart"></canvas>
                                </div>
                            </div>
                            
                            <div class="share-workout">
                                <h3><i class="fas fa-share-alt"></i> Share Your Workout</h3>
                                <div class="share-buttons">
                                    <button class="btn btn-primary">
                                        <i class="fab fa-facebook"></i> Facebook
                                    </button>
                                    <button class="btn btn-primary">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </button>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-envelope"></i> Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Mobile Navigation -->
        <nav class="mobile-nav">
            <div class="mobile-nav-links">
                <a href="profile.php" class="mobile-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="workout-analytics.php" class="mobile-nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="current-goal.php" class="mobile-nav-link">
                    <i class="fas fa-bullseye"></i>
                    <span>Goals</span>
                </a>
                <a href="quick-workout.php" class="mobile-nav-link active">
                    <i class="fas fa-stopwatch"></i>
                    <span>Workout</span>
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Create Exercise Modal -->
    <div class="modal" id="createExerciseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create New Exercise</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="createExerciseForm">
                    <div class="form-group">
                        <label for="exerciseName">Exercise Name</label>
                        <input type="text" id="exerciseName" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="muscleGroup">Muscle Group</label>
                            <select id="muscleGroup" class="form-control">
                                <option value="">Select Muscle Group</option>
                                <option value="chest">Chest</option>
                                <option value="back">Back</option>
                                <option value="shoulders">Shoulders</option>
                                <option value="arms">Arms</option>
                                <option value="legs">Legs</option>
                                <option value="core">Core</option>
                                <option value="cardio">Cardio</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="equipment">Equipment</label>
                            <select id="equipment" class="form-control">
                                <option value="">Select Equipment</option>
                                <option value="barbell">Barbell</option>
                                <option value="dumbbell">Dumbbell</option>
                                <option value="machine">Machine</option>
                                <option value="bodyweight">Bodyweight</option>
                                <option value="cable">Cable</option>
                                <option value="kettlebell">Kettlebell</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="exerciseDescription">Description (Optional)</label>
                        <textarea id="exerciseDescription" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveExerciseBtn">Create Exercise</button>
            </div>
        </div>
    </div>
    
    <!-- Script for Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Main JavaScript -->
    <script>
        let workoutData = {
            exercises: [],
            duration: 0,
            startTime: null,
            notes: "",
            name: "Quick Workout"
        };
        
        let currentExerciseIndex = 0;
        let timerInterval = null;
        let restTimerInterval = null;
        let restDuration = 60; 
        let restTimeRemaining = 0;
        
        const startNewWorkoutBtn = document.getElementById('startNewWorkoutBtn');
        const createExerciseBtn = document.getElementById('createExerciseBtn');
        const clearSelectedBtn = document.getElementById('clearSelectedBtn');
        const beginWorkoutBtn = document.getElementById('beginWorkoutBtn');
        const selectedExercisesList = document.getElementById('selectedExercisesList');
        const stepItems = document.querySelectorAll('.step-item');
        const stepContents = document.querySelectorAll('.step-content');
        
        document.querySelectorAll('.exercise-item').forEach(item => {
            item.addEventListener('click', function() {
                const exerciseName = this.dataset.name;
                addExerciseToSelected(exerciseName);
            });
        });
        
        document.querySelectorAll('.exercise-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.exercise-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                this.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                const tabId = this.dataset.tab + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        const exerciseSearch = document.getElementById('exerciseSearch');
        exerciseSearch.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            
            document.querySelectorAll('.exercise-item').forEach(item => {
                const exerciseName = item.querySelector('.exercise-name').textContent.toLowerCase();
                
                if (exerciseName.includes(searchValue)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        function addExerciseToSelected(exerciseName) {
            if (workoutData.exercises.some(e => e.name === exerciseName)) {
                showNotification('Exercise already added to workout');
                return;
            }
            
            workoutData.exercises.push({
                name: exerciseName,
                sets: [],
                notes: ''
            });
            
            updateSelectedExercisesList();
            
            beginWorkoutBtn.disabled = false;
        }
        
        function updateSelectedExercisesList() {
            if (workoutData.exercises.length === 0) {
                selectedExercisesList.innerHTML = `
                    <div class="empty-message">
                        <p>No exercises selected yet. Click on exercises from the library to add them to your workout.</p>
                    </div>
                `;
                beginWorkoutBtn.disabled = true;
                return;
            }
            
            let html = '';
            
            workoutData.exercises.forEach((exercise, index) => {
                html += `
                    <div class="exercise-item selected-exercise">
                        <div class="exercise-name">
                            <i class="fas fa-dumbbell"></i>
                            ${exercise.name}
                        </div>
                        <div class="exercise-actions">
                            <button class="btn btn-sm btn-secondary move-exercise" data-direction="up" data-index="${index}" ${index === 0 ? 'disabled' : ''}>
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary move-exercise" data-direction="down" data-index="${index}" ${index === workoutData.exercises.length - 1 ? 'disabled' : ''}>
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button class="btn btn-sm btn-danger remove-exercise" data-index="${index}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            selectedExercisesList.innerHTML = html;
            
            document.querySelectorAll('.remove-exercise').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    workoutData.exercises.splice(index, 1);
                    updateSelectedExercisesList();
                });
            });
            
            document.querySelectorAll('.move-exercise').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    const direction = this.dataset.direction;
                    
                    if (direction === 'up' && index > 0) {
                        [workoutData.exercises[index], workoutData.exercises[index - 1]] = 
                        [workoutData.exercises[index - 1], workoutData.exercises[index]];
                    } else if (direction === 'down' && index < workoutData.exercises.length - 1) {
                        [workoutData.exercises[index], workoutData.exercises[index + 1]] = 
                        [workoutData.exercises[index + 1], workoutData.exercises[index]];
                    }
                    
                    updateSelectedExercisesList();
                });
            });
        }
        
        clearSelectedBtn.addEventListener('click', function() {
            workoutData.exercises = [];
            updateSelectedExercisesList();
        });
        
        beginWorkoutBtn.addEventListener('click', function() {
            goToStep(2);
            startWorkout();
        });
        
        createExerciseBtn.addEventListener('click', function() {
            document.getElementById('createExerciseModal').classList.add('active');
        });
        
        document.querySelectorAll('.modal-close, .modal-close-btn').forEach(button => {
                button.addEventListener('click', function() {
                document.getElementById('createExerciseModal').classList.remove('active');
            });
        });
        
        document.getElementById('saveExerciseBtn').addEventListener('click', function() {
            const exerciseName = document.getElementById('exerciseName').value.trim();
            
            if (exerciseName === '') {
                alert('Please enter an exercise name');
                return;
            }
            
            addExerciseToSelected(exerciseName);
            
            document.getElementById('createExerciseModal').classList.remove('active');
            
            document.getElementById('createExerciseForm').reset();
        });
        
        function goToStep(stepNumber) {
            stepItems.forEach((item, index) => {
                const step = index + 1;
                
                item.classList.remove('active', 'completed');
                
                if (step < stepNumber) {
                    item.classList.add('completed');
                } else if (step === stepNumber) {
                    item.classList.add('active');
                }
            });
            
            stepContents.forEach((content, index) => {
                content.classList.remove('active');
                
                if (index + 1 === stepNumber) {
                    content.classList.add('active');
                }
            });
        }
        
        function startWorkout() {
            workoutData.startTime = new Date();
            startWorkoutTimer();
            
            updateExercisePlanList();
            currentExerciseIndex = 0;
            showCurrentExercise();
        }
        
        function updateExercisePlanList() {
            const exercisePlanList = document.getElementById('exercisePlanList');
            let html = '';
            
            workoutData.exercises.forEach((exercise, index) => {
                const isActive = index === currentExerciseIndex;
                const isCompleted = exercise.sets.length > 0 && index !== currentExerciseIndex;
                
                html += `
                    <div class="exercise-item ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}" data-index="${index}">
                        <div class="exercise-name">
                            ${isCompleted ? '<i class="fas fa-check-circle"></i>' : 
                              isActive ? '<i class="fas fa-play-circle"></i>' : 
                              '<i class="far fa-circle"></i>'}
                            ${exercise.name}
                        </div>
                        <div class="exercise-item-badge">
                            ${exercise.sets.length} sets
                        </div>
                            </div>
                        `;
                    });
            
            exercisePlanList.innerHTML = html;
            
            document.querySelectorAll('#exercisePlanList .exercise-item').forEach(item => {
                item.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    currentExerciseIndex = index;
                    updateExercisePlanList();
                    showCurrentExercise();
                });
            });
        }
        
        function showCurrentExercise() {
            const exercise = workoutData.exercises[currentExerciseIndex];
            
            document.getElementById('currentExerciseName').textContent = exercise.name;
            document.getElementById('currentExerciseIndex').textContent = currentExerciseIndex + 1;
            document.getElementById('totalExercises').textContent = workoutData.exercises.length;
            
            const completedSets = exercise.sets.length;
            document.getElementById('completedSetsCount').textContent = completedSets;
            document.getElementById('setsProgressFill').style.width = `${(completedSets / 4) * 100}%`;
            
            updateCompletedSetsTable();
            
            document.getElementById('prevExerciseBtn').disabled = currentExerciseIndex === 0;
            document.getElementById('nextExerciseBtn').disabled = currentExerciseIndex === workoutData.exercises.length - 1;
        }
        
        function updateCompletedSetsTable() {
            const tableBody = document.getElementById('completedSetsTableBody');
            const exercise = workoutData.exercises[currentExerciseIndex];
            
            if (exercise.sets.length === 0) {
                tableBody.innerHTML = '<div class="empty-message">No sets completed yet</div>';
                    return;
                }
                
            let html = '';
                
            exercise.sets.forEach((set, index) => {
                    html += `
                    <div class="table-row">
                        <div class="table-cell">${index + 1}</div>
                        <div class="table-cell">${set.weight} kg</div>
                        <div class="table-cell">${set.reps}</div>
                        <div class="table-cell">${set.rpe}</div>
                        </div>
                    `;
                });
                
            tableBody.innerHTML = html;
        }
        
        document.getElementById('prevExerciseBtn').addEventListener('click', function() {
            if (currentExerciseIndex > 0) {
                currentExerciseIndex--;
                updateExercisePlanList();
                showCurrentExercise();
            }
        });
        
        document.getElementById('nextExerciseBtn').addEventListener('click', function() {
            if (currentExerciseIndex < workoutData.exercises.length - 1) {
                currentExerciseIndex++;
                updateExercisePlanList();
                showCurrentExercise();
            }
        });
        
        document.getElementById('rpeInput').addEventListener('input', function() {
            document.getElementById('rpeValue').textContent = this.value;
        });
        
        document.getElementById('decrementWeight').addEventListener('click', function() {
            const input = document.getElementById('weightInput');
            if (input.value > 0) {
                input.value = parseInt(input.value) - 5;
            }
        });
        
        document.getElementById('incrementWeight').addEventListener('click', function() {
            const input = document.getElementById('weightInput');
            input.value = parseInt(input.value) + 5;
        });
        
        document.getElementById('decrementReps').addEventListener('click', function() {
            const input = document.getElementById('repsInput');
            if (input.value > 0) {
                input.value = parseInt(input.value) - 1;
            }
        });
        
        document.getElementById('incrementReps').addEventListener('click', function() {
            const input = document.getElementById('repsInput');
            input.value = parseInt(input.value) + 1;
        });
        
        document.querySelectorAll('.weight-preset').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('weightInput').value = this.dataset.value;
            });
        });
        
        document.querySelectorAll('.reps-preset').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('repsInput').value = this.dataset.value;
            });
        });
        
        document.getElementById('completeSetBtn').addEventListener('click', function() {
            const weight = parseInt(document.getElementById('weightInput').value) || 0;
            const reps = parseInt(document.getElementById('repsInput').value) || 0;
            const rpe = parseInt(document.getElementById('rpeInput').value) || 7;
            
            if (reps === 0) {
                alert('Please enter at least 1 rep');
                return;
            }
            
            workoutData.exercises[currentExerciseIndex].sets.push({
                weight,
                reps,
                rpe
            });
            
            updateExercisePlanList();
            showCurrentExercise();
            
            startRestTimer();
        });
        
        document.getElementById('skipSetBtn').addEventListener('click', function() {
            if (currentExerciseIndex < workoutData.exercises.length - 1) {
                currentExerciseIndex++;
                updateExercisePlanList();
                showCurrentExercise();
            }
        });
        
        document.querySelectorAll('.rest-preset-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.rest-preset-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                this.classList.add('active');
                restDuration = parseInt(this.dataset.seconds);
                    });
                });
                
        document.getElementById('startRestBtn').addEventListener('click', function() {
            startRestTimer();
        });
        
        document.getElementById('stopRestBtn').addEventListener('click', function() {
            stopRestTimer();
        });
        
        function startRestTimer() {
            restTimeRemaining = restDuration;
            updateRestTimerDisplay();
            
            document.getElementById('startRestBtn').style.display = 'none';
            document.getElementById('stopRestBtn').style.display = 'block';
            
            if (restTimerInterval) {
                clearInterval(restTimerInterval);
            }
            
            restTimerInterval = setInterval(function() {
                restTimeRemaining--;
                updateRestTimerDisplay();
                
                if (restTimeRemaining <= 0) {
                    stopRestTimer();
                    playTimerCompleteSound();
                }
                }, 1000);
            }
            
        function stopRestTimer() {
            if (restTimerInterval) {
                clearInterval(restTimerInterval);
                restTimerInterval = null;
            }
            
            document.getElementById('startRestBtn').style.display = 'block';
            document.getElementById('stopRestBtn').style.display = 'none';
        }
        
        function updateRestTimerDisplay() {
            const minutes = Math.floor(restTimeRemaining / 60);
            const seconds = restTimeRemaining % 60;
            
            document.getElementById('restTimerDisplay').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            const progressPercent = (restTimeRemaining / restDuration) * 100;
            document.querySelector('.rest-timer-progress').style.setProperty('--progress', `${progressPercent}%`);
        }
        
        function playTimerCompleteSound() {
            showNotification('Rest timer complete!');
        }
        
        document.getElementById('finishWorkoutBtn').addEventListener('click', function() {
            stopWorkoutTimer();
            goToStep(3);
            showWorkoutSummary();
        });
        
        function startWorkoutTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            timerInterval = setInterval(function() {
                updateWorkoutTimer();
            }, 1000);
            
            updateWorkoutTimer();
        }
        
        function stopWorkoutTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            const endTime = new Date();
            workoutData.duration = Math.floor((endTime - workoutData.startTime) / 1000);
        }
        
        function updateWorkoutTimer() {
            const now = new Date();
            const elapsedSeconds = Math.floor((now - workoutData.startTime) / 1000);
            
            const hours = Math.floor(elapsedSeconds / 3600);
            const minutes = Math.floor((elapsedSeconds % 3600) / 60);
            const seconds = elapsedSeconds % 60;
            
            document.getElementById('workoutTimerDisplay').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        function showWorkoutSummary() {
            const totalSets = workoutData.exercises.reduce((total, ex) => total + ex.sets.length, 0);
            const totalReps = workoutData.exercises.reduce((total, ex) => {
                return total + ex.sets.reduce((setTotal, set) => setTotal + set.reps, 0);
            }, 0);
            const totalVolume = workoutData.exercises.reduce((total, ex) => {
                return total + ex.sets.reduce((setTotal, set) => setTotal + (set.weight * set.reps), 0);
            }, 0);
            const avgRPE = getAverageRPE();
            
            const hours = Math.floor(workoutData.duration / 3600);
            const minutes = Math.floor((workoutData.duration % 3600) / 60);
            const seconds = workoutData.duration % 60;
            document.getElementById('summaryDuration').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('summaryExercises').textContent = workoutData.exercises.filter(ex => ex.sets.length > 0).length;
            
            const calories = calculateCaloriesBurnedAccurate();
            document.getElementById('summaryCalories').textContent = calories;
            
            const intensity = calculateWorkoutIntensity();
            document.getElementById('summaryIntensity').textContent = intensity.toFixed(1);
            
            document.getElementById('statsTotalVolume').textContent = `${totalVolume} kg`;
            document.getElementById('statsTotalSets').textContent = totalSets;
            document.getElementById('statsTotalReps').textContent = totalReps;
            document.getElementById('statsAvgRPE').textContent = avgRPE.toFixed(1);
            
            updateExerciseSummary();
        }
        
        function calculateWorkoutIntensity() {
            const durationMinutes = Math.max(1, workoutData.duration / 60);
            const totalVolume = workoutData.exercises.reduce((total, ex) => {
                return total + ex.sets.reduce((setTotal, set) => setTotal + (set.weight * set.reps), 0);
            }, 0);
            
            const volumePerMinute = totalVolume / durationMinutes;
            const avgRPE = getAverageRPE();
            
            let intensity = 1.0;
            
            if (volumePerMinute > 100) intensity = 4.5;
            else if (volumePerMinute > 75) intensity = 4.0;
            else if (volumePerMinute > 50) intensity = 3.5;
            else if (volumePerMinute > 25) intensity = 3.0;
            else if (volumePerMinute > 10) intensity = 2.5;
            else if (volumePerMinute > 5) intensity = 2.0;
            else intensity = 1.5;
            
            intensity += (avgRPE - 5) * 0.1;
            
            return Math.min(5.0, Math.max(1.0, intensity));
        }
        
        function updateExerciseSummary() {
            const summaryList = document.getElementById('exerciseSummaryList');
            let html = '';
            
            workoutData.exercises.forEach((exercise, index) => {
                if (exercise.sets.length === 0) return; 
                
                const totalSets = exercise.sets.length;
                const totalReps = exercise.sets.reduce((total, set) => total + set.reps, 0);
                const totalVolume = exercise.sets.reduce((total, set) => total + (set.weight * set.reps), 0);
                const maxWeight = Math.max(...exercise.sets.map(set => set.weight));
                
                html += `
                    <div class="exercise-card">
                        <div class="exercise-header">
                            <h3 class="exercise-title">
                                <i class="fas fa-dumbbell"></i>
                                ${exercise.name}
                            </h3>
                        </div>
                        
                        <div class="exercise-data">
                            <div class="data-item">
                                <div class="data-label">Sets</div>
                                <div class="data-value">${totalSets}</div>
                            </div>
                            
                            <div class="data-item">
                                <div class="data-label">Reps</div>
                                <div class="data-value">${totalReps}</div>
                            </div>
                            
                            <div class="data-item">
                                <div class="data-label">Volume</div>
                                <div class="data-value">${totalVolume} kg</div>
                            </div>
                            
                            <div class="data-item">
                                <div class="data-label">Max Weight</div>
                                <div class="data-value">${maxWeight} kg</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            if (html === '') {
                html = '<div class="empty-message">No exercises completed</div>';
            }
            
            summaryList.innerHTML = html;
        }
        
        document.getElementById('saveWorkoutBtn').addEventListener('click', function() {
            workoutData.name = document.getElementById('workoutNameInput').value || "Quick Workout";
            workoutData.notes = document.getElementById('workoutNotesInput').value;
            workoutData.saveAsTemplate = document.getElementById('saveAsTemplateCheckbox').checked;
            
            const completedExercises = workoutData.exercises.filter(ex => ex.sets.length > 0);
            
            if (completedExercises.length === 0) {
                alert('No exercises completed. Please complete at least one exercise set before saving the workout.');
                return;
            }
            
            const submitData = {
                name: workoutData.name,
                duration: workoutData.duration,
                notes: workoutData.notes,
                saveAsTemplate: workoutData.saveAsTemplate,
                exercises: completedExercises
            };
            
            const formData = new FormData();
            formData.append('save_workout', '1');
            formData.append('workout_data', JSON.stringify(submitData));
            
            fetch('quick-workout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Workout saved successfully!');
                    
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 2000);
                } else {
                    showNotification('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving workout: ' + error, 'error');
            });
        });
        
        document.getElementById('discardWorkoutBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to discard this workout? All progress will be lost.')) {
                window.location.href = 'profile.php';
            }
        });
        
        function initializeCharts() {
            const ctx = document.getElementById('workoutProgressChart').getContext('2d');
            
            const chartData = {
                labels: ['Previous', 'Current'],
                datasets: [{
                    label: 'Volume (kg)',
                    data: [1200, totalVolume()],
                    backgroundColor: 'rgba(157, 78, 221, 0.7)',
                    borderColor: 'rgba(157, 78, 221, 1)',
                    borderWidth: 1
                }]
            };
            
            new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function totalVolume() {
            return workoutData.exercises.reduce((total, ex) => {
                return total + ex.sets.reduce((setTotal, set) => setTotal + (set.weight * set.reps), 0);
            }, 0);
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'}"></i>
                </div>
                <div class="notification-message">${message}</div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            goToStep(1);
            updateSelectedExercisesList();
            
            document.getElementById('finishWorkoutBtn').addEventListener('click', function() {
                setTimeout(initializeCharts, 500);
            });
        });
        
        document.getElementById('addCustomExerciseBtn').addEventListener('click', function() {
            const customExerciseName = document.getElementById('customExerciseName').value.trim();
            
            if (customExerciseName === '') {
                showNotification('Please enter an exercise name', 'error');
                return;
            }
            
            addExerciseToSelected(customExerciseName);
            
            document.getElementById('customExerciseName').value = '';
            
            showNotification(`Added "${customExerciseName}" to your workout`);
        });
        
        document.getElementById('customExerciseName').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('addCustomExerciseBtn').click();
            }
        });
    </script>
</body>
</html> 