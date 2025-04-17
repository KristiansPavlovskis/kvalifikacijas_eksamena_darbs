<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/quick-workout.php");
    exit;
}

require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_workout"])) {
    header('Content-Type: application/json');
    
    try {
        $workoutData = json_decode($_POST["workout_data"], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid workout data format");
        }
        
        if (empty($workoutData["exercises"])) {
            throw new Exception("No exercises recorded. Please add at least one exercise to save your workout.");
        }
        
        $workout_id = saveWorkoutToDatabase($conn, $user_id, $workoutData);
        
        echo json_encode([
            'success' => true,
            'workout_id' => $workout_id,
            'message' => 'Workout saved successfully!'
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

$workout_saved = false;
$workout_message = "";
$workout_id = 0;

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
        return 1.0;
    } else if ($avg_volume < 150) {
        return 2.0;
    } else if ($avg_volume < 300) {
        return 3.0;
    } else if ($avg_volume < 500) {
        return 4.0;
    } else {
        return 5.0;
    }
}



function getAverageRPE($workoutData) {
    $allRPE = [];
    
    if (!isset($workoutData['exercises']) || !is_array($workoutData['exercises'])) {
        return 5;
    }
    
    foreach ($workoutData['exercises'] as $exercise) {
        if (isset($exercise['sets']) && is_array($exercise['sets'])) {
            foreach ($exercise['sets'] as $set) {
                if (isset($set['rpe'])) {
                    $allRPE[] = $set['rpe'];
                }
            }
        }
    }
    
    return empty($allRPE) ? 5 : array_sum($allRPE) / count($allRPE);
}

try {
    if (tableExists($conn, 'workout_templates')) {
        $templates_query = "SELECT wt.*, COUNT(wte.id) as exercise_count 
                          FROM workout_templates wt
                          LEFT JOIN workout_template_exercises wte ON wt.id = wte.workout_template_id
                          WHERE wt.user_id = ?
                          GROUP BY wt.id
                          ORDER BY wt.created_at DESC";
        $stmt = mysqli_prepare($conn, $templates_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare templates query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $templates = mysqli_stmt_get_result($stmt);
    } else {
        $templates = false;
    }
} catch (Exception $e) {
    error_log("Error fetching workout templates: " . $e->getMessage());
    $templates = false;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }

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

        .add-exercise-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .add-exercise-form .form-row {
            grid-template-columns: 1fr 1fr;
        }

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
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .step-content.active {
            display: block;
            opacity: 1;
        }

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

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: opacity 0.3s ease;
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .notification.error {
            background-color: #F44336;
        }

        .notification.fade-out {
            opacity: 0;
        }

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
        
        .template-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .template-item {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        
        .template-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            border-color: rgba(157, 78, 221, 0.3);
        }
        
        .template-header {
            margin-bottom: 15px;
        }
        
        .template-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: white;
        }
        
        .template-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--gray-light);
        }
        
        .template-meta i {
            color: #9d4edd;
            margin-right: 5px;
        }
        
        .template-description {
            margin-bottom: 20px;
            color: var(--gray-light);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .template-actions {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            color: var(--gray-light);
        }
        
        .empty-message p {
            margin-bottom: 15px;
        }

        .step-content {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .step-content.active {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Quick Workout</h1>
                <div class="page-actions">
                    <button class="btn btn-primary" id="startNewWorkoutBtn">
                        <i class="fas fa-plus"></i> Start New Workout
                    </button>
                </div>
            </div>

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

            <div class="steps-content">
            <div class="step-content active" id="step1-content">
                <div class="dashboard-grid">
                    <div class="template-list">
                        <?php if ($templates && mysqli_num_rows($templates) > 0): ?>
                            <?php while ($template = mysqli_fetch_assoc($templates)): ?>
                                <div class="template-item" data-id="<?= $template['id'] ?>">
                                    <div class="template-header">
                                        <h3 class="template-title"><?= htmlspecialchars($template['name']) ?></h3>
                                        <div class="template-meta">
                                            <span class="template-exercises">
                                                <i class="fas fa-dumbbell"></i> <?= $template['exercise_count'] ?> exercises
                                            </span>
                                            <span class="template-difficulty">
                                                <i class="fas fa-bolt"></i> <?= ucfirst($template['difficulty']) ?>
                                            </span>
                                            <span class="template-time">
                                                <i class="fas fa-clock"></i> <?= $template['estimated_time'] ?> min
                                            </span>
                                        </div>
                                    </div>
                                    <?php if (!empty($template['description'])): ?>
                                        <p class="template-description"><?= htmlspecialchars($template['description']) ?></p>
                                    <?php endif; ?>
                                        <div class="template-actions">
                                    <button class="btn btn-primary select-template-btn">
                                                <i class="fas fa-plus"></i> Load Template
                                            </button>
                                            <button class="btn btn-success begin-workout-from-template-btn" style="display:none;">
                                                <i class="fas fa-play"></i> Begin Workout
                                    </button>
                                        </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-message">
                                <p>No workout templates found. Create a template first to start a workout.</p>
                                <a href="workout-templates.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Template
                                </a>
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
                
            <div class="step-content" id="step2-content">
                <div class="dashboard-grid">
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
                                            <div class="weight-input-container">
                                                <button class="btn btn-sm btn-secondary weight-btn" id="decrementWeight">-</button>
                                                <input type="number" id="weightInput" class="form-control" value="10" min="0">
                                                <button class="btn btn-sm btn-secondary weight-btn" id="incrementWeight">+</button>
                                            </div>
                                            <div class="weight-presets">
                                                <button class="weight-preset-btn" data-value="5">5</button>
                                                <button class="weight-preset-btn" data-value="10">10</button>
                                                <button class="weight-preset-btn" data-value="20">20</button>
                                                <button class="weight-preset-btn" data-value="40">40</button>
                                            </div>
                                        </div>
                            
                                        <div class="form-group">
                                            <label for="repsInput">Reps</label>
                                            <div class="reps-input-container">
                                                <button class="btn btn-sm btn-secondary reps-btn" id="decrementReps">-</button>
                                                <input type="number" id="repsInput" class="form-control" value="8" min="1">
                                                <button class="btn btn-sm btn-secondary reps-btn" id="incrementReps">+</button>
                                            </div>
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
            
                    <div>
                        <div class="section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-clipboard-list"></i> Workout Plan
                                </h2>
                            </div>
                            
                            <div class="section-body">
                                <div id="exercisePlanList" class="exercise-plan-list"></div>
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
        </div>
            <div class="step-content" id="step3-content">
                <div class="dashboard-grid">
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
                                <div id="exerciseSummaryList" class="exercise-summary-list"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="workoutNameInput">Workout Name</label>
                                <input type="text" id="workoutNameInput" class="form-control" value="Quick Workout" placeholder="Name your workout">
                            </div>
                            
                            <div class="form-group">
                                <label for="workoutNotesInput">Workout Notes</label>
                                <textarea id="workoutNotesInput" class="form-control" rows="3" placeholder="Add any notes about your workout..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="workoutRating">Rate Your Workout (1-5)</label>
                                <select id="workoutRating" class="form-control">
                                    <option value="">Select rating...</option>
                                    <option value="1">1 - Poor</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="3">3 - Good</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="5">5 - Excellent</option>
                                </select>
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
            </div>
        </main>
        
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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        let workoutData = {
            template_id: null,
            exercises: [],
            duration: 0,
            startTime: null,
            notes: "",
            name: "Quick Workout",
            currentSet: 1
        };
        
        let currentExerciseIndex = 0;
        let timerInterval = null;
        let restTimerInterval = null;
        let restDuration = 60; 
        let restTimeRemaining = 0;
        
        document.addEventListener('DOMContentLoaded', function() {
            const startNewWorkoutBtn = document.getElementById('startNewWorkoutBtn');
            const clearSelectedBtn = document.getElementById('clearSelectedBtn');
            const beginWorkoutBtn = document.getElementById('beginWorkoutBtn');
            const selectedExercisesList = document.getElementById('selectedExercisesList');
            const stepItems = document.querySelectorAll('.step-item');
            const stepContents = document.querySelectorAll('.step-content');

            document.getElementById('beginWorkoutBtn').addEventListener('click', () => goToStep(2));
            document.getElementById('finishWorkoutBtn').addEventListener('click', () => goToStep(3));
            
            document.querySelectorAll('.select-template-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    try {
                        const templateId = btn.closest('.template-item').dataset.id;
                        
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        btn.disabled = true;
                        
                        const response = await fetch(`get_template.php?id=${templateId}`);
                        
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Raw response:', errorText);
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (!data.success) {
                            throw new Error(data.error || 'Unknown error loading template');
                        }
                        
                        workoutData = {
                            template_id: data.template.id,
                            name: data.template.name,
                            exercises: data.exercises.map(ex => ({
                                name: ex.exercise_name,
                                sets: parseInt(ex.sets) || 3,
                                reps: parseInt(ex.reps) || 10,
                                rest_time: parseInt(ex.rest_time) || 60,
                                weight: 0,
                                completedSets: []
                            })),
                            duration: 0,
                            startTime: null,
                            notes: ""
                        };
                        
                        updateSelectedExercisesList();
                        document.getElementById('beginWorkoutBtn').disabled = false;
                        
                        btn.innerHTML = '<i class="fas fa-play"></i> Start Workout';
                        btn.disabled = false;
                        
                        showNotification(`Template "${data.template.name}" loaded successfully!`, 'success');
                        
                        const beginTemplateBtn = btn.closest('.template-actions').querySelector('.begin-workout-from-template-btn');
                        if (beginTemplateBtn) {
                            beginTemplateBtn.style.display = 'inline-flex';
                            
                            beginTemplateBtn.addEventListener('click', () => {
                        goToStep(2);
                                
                                showNotification(`Starting workout: ${workoutData.name}`, 'success');
                            });
                        }
                        
                    } catch (error) {
                        console.error('Template loading error:', error);
                        showNotification(`Failed to load template: ${error.message}`, 'error');
                        
                        btn.innerHTML = '<i class="fas fa-play"></i> Start Workout';
                        btn.disabled = false;
                    }
                });
            });

            if (clearSelectedBtn) {
                clearSelectedBtn.addEventListener('click', () => {
                    workoutData.exercises = [];
                    updateSelectedExercisesList();
                });
            }

            const exerciseSearch = document.getElementById('exerciseSearch');
            if (exerciseSearch) {
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
            }

            document.querySelectorAll('.exercise-item').forEach(item => {
                item.addEventListener('click', function() {
                    const exerciseName = this.dataset.name;
                    if (exerciseName) {
                        addExerciseToSelected(exerciseName);
                    }
                });
            });

            document.querySelectorAll('.exercise-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    if (tabId) {
                        activateTab(tabId);
                    }
                });
            });

            goToStep(1);
            updateSelectedExercisesList();
        
            if (document.getElementById('finishWorkoutBtn')) {
                document.getElementById('finishWorkoutBtn').addEventListener('click', function() {
                    setTimeout(initializeCharts, 500);
                });
            }
            
            const addCustomExerciseBtn = document.getElementById('addCustomExerciseBtn');
            if (addCustomExerciseBtn) {
                addCustomExerciseBtn.addEventListener('click', function() {
                    const customExerciseName = document.getElementById('customExerciseName').value.trim();
                    if (customExerciseName) {
                        addExerciseToSelected(customExerciseName);
                        document.getElementById('customExerciseName').value = '';
                    }
                });
            }

            const finishWorkoutBtn = document.getElementById('finishWorkoutBtn');
document.getElementById('finishWorkoutBtn').addEventListener('click', function() {
    console.log("Finish workout button clicked");
    
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    workoutData.endTime = new Date();
    
    workoutData.duration = Math.floor((new Date() - workoutData.startTime) / 1000);
    
    updateWorkoutSummary();
    
    goToStep(3);
    
    if (typeof initializeCharts === 'function') {
        setTimeout(initializeCharts, 500);
    }
});
        });

        function addExerciseToSelected(exerciseName) {
            if (workoutData.exercises.some(e => e.name === exerciseName)) {
                showNotification('This exercise is already in your workout', 'error');
                return;
            }
            
            workoutData.exercises.push({
                name: exerciseName,
                sets: 3,
                reps: 10,
                weight: 0,
                completedSets: []
            });

            updateSelectedExercisesList();
            showNotification(`Added ${exerciseName} to your workout`, 'success');
        }

        function goToStep(stepNumber) {
            console.log(`Transitioning to step ${stepNumber}`);
            
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            
            const targetStep = document.getElementById(`step${stepNumber}-content`);
            if (targetStep) {
                targetStep.classList.add('active');
            }
            
            document.querySelectorAll('.step-item').forEach(item => {
                const itemStep = parseInt(item.dataset.step);
                if (itemStep < stepNumber) {
                    item.classList.add('completed');
                    item.classList.remove('active');
                } else if (itemStep === stepNumber) {
                    item.classList.add('active');
                    item.classList.remove('completed');
                } else {
                    item.classList.remove('active', 'completed');
                }
            });
            
            if (stepNumber === 2) {
                startWorkoutTimer();
                updateExercisePlanList();
                updateCurrentExerciseDisplay();
            }
            
            if (stepNumber === 3) {
                console.log("Initializing step 3");
                updateWorkoutSummary();
                if (typeof initializeCharts === 'function') {
                    setTimeout(initializeCharts, 500);
                }
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        function startWorkoutTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            workoutData.startTime = new Date();
            
            timerInterval = setInterval(() => {
                const now = new Date();
                workoutData.duration = Math.floor((now - workoutData.startTime) / 1000);
                
                const hours = Math.floor(workoutData.duration / 3600);
                const minutes = Math.floor((workoutData.duration % 3600) / 60);
                const seconds = workoutData.duration % 60;
                
                const timerDisplay = document.getElementById('workoutTimerDisplay');
                if (timerDisplay) {
                    timerDisplay.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }

        function updateSelectedExercisesList() {
            const container = document.getElementById('selectedExercisesList');
            if (!container) return;
            
            container.innerHTML = '';
            
            if (workoutData.exercises.length === 0) {
                container.innerHTML = `
                    <div class="empty-message">
                        <p>No exercises selected yet.</p>
                    </div>
                `;
                const beginWorkoutBtn = document.getElementById('beginWorkoutBtn');
                if (beginWorkoutBtn) {
                    beginWorkoutBtn.disabled = true;
                }
                return;
            }
            
            workoutData.exercises.forEach((exercise, index) => {
                const exerciseElement = document.createElement('div');
                exerciseElement.className = 'selected-exercise-item';
                exerciseElement.dataset.index = index;
                
                exerciseElement.innerHTML = `
                    <div class="exercise-info">
                        <div class="exercise-name">${exercise.name}</div>
                        <div class="exercise-sets">${exercise.sets} sets × ${exercise.reps} reps</div>
                    </div>
                    <button class="btn btn-sm btn-danger remove-exercise-btn">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                exerciseElement.querySelector('.remove-exercise-btn').addEventListener('click', () => {
                    workoutData.exercises.splice(index, 1);
                    updateSelectedExercisesList();
                });
                
                container.appendChild(exerciseElement);
            });
            
            const beginWorkoutBtn = document.getElementById('beginWorkoutBtn');
            if (beginWorkoutBtn) {
                beginWorkoutBtn.disabled = false;
            }
        }
        
        function updateExercisePlanList() {
            const container = document.getElementById('exercisePlanList');
            if (!container) return;
            
            container.innerHTML = '';
            
            workoutData.exercises.forEach((exercise, index) => {
                const isCurrent = index === currentExerciseIndex;
                
                const exerciseElement = document.createElement('div');
                exerciseElement.className = `exercise-plan-item ${isCurrent ? 'current' : ''}`;
                
                exerciseElement.innerHTML = `
                    <div class="exercise-plan-name">
                        ${isCurrent ? '<i class="fas fa-play-circle"></i>' : '<i class="fas fa-circle"></i>'}
                        ${exercise.name}
                    </div>
                    <div class="exercise-plan-sets">
                        ${exercise.completedSets.length}/${exercise.sets} sets
                    </div>
                `;
                
                container.appendChild(exerciseElement);
            });
        }
        
        function updateCurrentExerciseDisplay() {
            if (workoutData.exercises.length === 0) return;
            
            const currentExercise = workoutData.exercises[currentExerciseIndex];
            
            const nameElement = document.getElementById('currentExerciseName');
            if (nameElement) {
                nameElement.textContent = currentExercise.name;
            }
            
            const indexElement = document.getElementById('currentExerciseIndex');
            if (indexElement) {
                indexElement.textContent = (currentExerciseIndex + 1).toString();
            }
            
            const totalElement = document.getElementById('totalExercises');
            if (totalElement) {
                totalElement.textContent = workoutData.exercises.length.toString();
            }
            
            const completedSetsCount = document.getElementById('completedSetsCount');
            if (completedSetsCount) {
                completedSetsCount.textContent = currentExercise.completedSets.length.toString();
            }
            
            const targetSetsCount = document.getElementById('targetSetsCount');
            if (targetSetsCount) {
                targetSetsCount.textContent = currentExercise.sets.toString();
            }
            
            const progressFill = document.getElementById('setsProgressFill');
            if (progressFill) {
                const percentage = (currentExercise.completedSets.length / currentExercise.sets) * 100;
                progressFill.style.width = `${percentage}%`;
            }
            
            updateCompletedSetsTable();
            
            const prevBtn = document.getElementById('prevExerciseBtn');
            if (prevBtn) {
                prevBtn.disabled = currentExerciseIndex === 0;
            }
            
            const nextBtn = document.getElementById('nextExerciseBtn');
            if (nextBtn) {
                nextBtn.disabled = currentExerciseIndex === workoutData.exercises.length - 1;
            }
        }
        
        function updateCompletedSetsTable() {
            const tableBody = document.getElementById('completedSetsTableBody');
            if (!tableBody) return;
            
            const currentExercise = workoutData.exercises[currentExerciseIndex];
            
            if (currentExercise.completedSets.length === 0) {
                tableBody.innerHTML = '<div class="empty-message">No sets completed yet</div>';
                return;
            }
            
            tableBody.innerHTML = '';
            
            currentExercise.completedSets.forEach((set, index) => {
                const row = document.createElement('div');
                row.className = 'table-row';
                
                row.innerHTML = `
                    <div class="table-cell">${index + 1}</div>
                    <div class="table-cell">${set.weight} kg</div>
                    <div class="table-cell">${set.reps}</div>
                    <div class="table-cell">${set.rpe}</div>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        function updateWorkoutSummary() {
            console.log("Updating workout summary");
            
            const durationElement = document.getElementById('summaryDuration');
            if (durationElement) {
                const hours = Math.floor(workoutData.duration / 3600);
                const minutes = Math.floor((workoutData.duration % 3600) / 60);
                const seconds = workoutData.duration % 60;
                durationElement.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            const exercisesElement = document.getElementById('summaryExercises');
            if (exercisesElement) {
                exercisesElement.textContent = workoutData.exercises.length.toString();
            }
            
            let totalVolume = 0;
            let totalReps = 0;
            let totalRPE = 0;
            let rpeCount = 0;
            
            workoutData.exercises.forEach(exercise => {
                exercise.completedSets.forEach(set => {
                    totalVolume += set.weight * set.reps;
                    totalReps += set.reps;
                    if (set.rpe > 0) {
                        totalRPE += set.rpe;
                        rpeCount++;
                    }
                });
            });
            
            const volumeElement = document.getElementById('statsTotalVolume');
            if (volumeElement) {
                volumeElement.textContent = `${Math.round(totalVolume)} kg`;
            }
            
            const repsElement = document.getElementById('statsTotalReps');
            if (repsElement) {
                repsElement.textContent = totalReps.toString();
            }
        
            const rpeElement = document.getElementById('statsAvgRPE');
            if (rpeElement) {
                const avgRPE = rpeCount > 0 ? Math.round((totalRPE / rpeCount) * 10) / 10 : 0;
                rpeElement.textContent = avgRPE.toString();
            }
        
            const summaryList = document.getElementById('exerciseSummaryList');
            if (summaryList) {
                summaryList.innerHTML = '';
                workoutData.exercises.forEach(exercise => {
                    const exerciseElement = document.createElement('div');
                    exerciseElement.className = 'exercise-summary-item';
                    
                    const totalVolume = exercise.completedSets.reduce((sum, set) => sum + (set.weight * set.reps), 0);
                    
                    exerciseElement.innerHTML = `
                        <div class="exercise-summary-name">${exercise.name}</div>
                        <div class="exercise-summary-stats">
                            <div class="stat">
                                <span class="stat-value">${exercise.completedSets.length}/${exercise.sets}</span>
                                <span class="stat-label">Sets</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">${Math.round(totalVolume)}</span>
                                <span class="stat-label">Volume (kg)</span>
                            </div>
                        </div>
                    `;
                    
                    summaryList.appendChild(exerciseElement);
                });
            }
            
            console.log("Workout summary updated");
        }
        
        function updateWorkoutStats() {
            let totalVolume = 0;
            let totalSets = 0;
            let totalReps = 0;
            let totalRPE = 0;
            let rpeCount = 0;
            
            workoutData.exercises.forEach(exercise => {
                exercise.completedSets.forEach(set => {
                    totalVolume += set.weight * set.reps;
                    totalSets++;
                    totalReps += set.reps;
                    
                    if (set.rpe) {
                        totalRPE += set.rpe;
                        rpeCount++;
                    }
                });
            });
            
            const volumeElement = document.getElementById('statsTotalVolume');
            if (volumeElement) {
                volumeElement.textContent = `${totalVolume} kg`;
            }
            
            const setsElement = document.getElementById('statsTotalSets');
            if (setsElement) {
                setsElement.textContent = totalSets.toString();
            }
            
            const repsElement = document.getElementById('statsTotalReps');
            if (repsElement) {
                repsElement.textContent = totalReps.toString();
            }
            
            const rpeElement = document.getElementById('statsAvgRPE');
            if (rpeElement) {
                const avgRPE = rpeCount > 0 ? Math.round((totalRPE / rpeCount) * 10) / 10 : 0;
                rpeElement.textContent = avgRPE.toString();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const completeSetBtn = document.getElementById('completeSetBtn');
            if (completeSetBtn) {
                completeSetBtn.addEventListener('click', completeSet);
            }
            
            const skipSetBtn = document.getElementById('skipSetBtn');
            if (skipSetBtn) {
                skipSetBtn.addEventListener('click', skipSet);
            }
            
            const prevExerciseBtn = document.getElementById('prevExerciseBtn');
            if (prevExerciseBtn) {
                prevExerciseBtn.addEventListener('click', () => {
                    if (currentExerciseIndex > 0) {
                        currentExerciseIndex--;
                        updateCurrentExerciseDisplay();
                        updateExercisePlanList();
                    }
                });
            }
            
            const nextExerciseBtn = document.getElementById('nextExerciseBtn');
            if (nextExerciseBtn) {
                nextExerciseBtn.addEventListener('click', () => {
                    if (currentExerciseIndex < workoutData.exercises.length - 1) {
                        currentExerciseIndex++;
                        updateCurrentExerciseDisplay();
                        updateExercisePlanList();
                    }
                });
            }
            
            const startRestBtn = document.getElementById('startRestBtn');
            if (startRestBtn) {
                startRestBtn.addEventListener('click', startRestTimer);
            }
            
            const stopRestBtn = document.getElementById('stopRestBtn');
            if (stopRestBtn) {
                stopRestBtn.addEventListener('click', stopRestTimer);
            }
            
            document.querySelectorAll('.rest-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.rest-preset-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    restDuration = parseInt(this.dataset.seconds) || 60;
                });
            });
            
            const decrementWeight = document.getElementById('decrementWeight');
            if (decrementWeight) {
                decrementWeight.addEventListener('click', () => {
                    const input = document.getElementById('weightInput');
                    const value = parseInt(input.value) || 0;
                    input.value = Math.max(0, value - 2.5);
                });
            }
            
            const incrementWeight = document.getElementById('incrementWeight');
            if (incrementWeight) {
                incrementWeight.addEventListener('click', () => {
                    const input = document.getElementById('weightInput');
                    const value = parseInt(input.value) || 0;
                    input.value = value + 2.5;
                });
            }
            
            const decrementReps = document.getElementById('decrementReps');
            if (decrementReps) {
                decrementReps.addEventListener('click', () => {
                    const input = document.getElementById('repsInput');
                    const value = parseInt(input.value) || 0;
                    input.value = Math.max(1, value - 1);
                });
            }
            
            const incrementReps = document.getElementById('incrementReps');
            if (incrementReps) {
                incrementReps.addEventListener('click', () => {
                    const input = document.getElementById('repsInput');
                    const value = parseInt(input.value) || 0;
                    input.value = value + 1;
                });
            }
            
            document.querySelectorAll('.weight-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('weightInput').value = this.dataset.value;
                });
            });
            
            document.querySelectorAll('.reps-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('repsInput').value = this.dataset.value;
                });
            });
            
            const rpeInput = document.getElementById('rpeInput');
            const rpeValue = document.getElementById('rpeValue');
            if (rpeInput && rpeValue) {
                rpeInput.addEventListener('input', function() {
                    rpeValue.textContent = this.value;
                });
            }
            
            const saveWorkoutBtn = document.getElementById('saveWorkoutBtn');
            if (saveWorkoutBtn) {
                saveWorkoutBtn.addEventListener('click', saveWorkout);
            }
            
            const discardWorkoutBtn = document.getElementById('discardWorkoutBtn');
            if (discardWorkoutBtn) {
                discardWorkoutBtn.addEventListener('click', () => {
                    if (confirm('Are you sure you want to discard this workout? All progress will be lost.')) {
                        window.location.reload();
                    }
                });
            }
        });
        
        function completeSet() {
            if (workoutData.exercises.length === 0) return;
            
            const currentExercise = workoutData.exercises[currentExerciseIndex];
            
            const weight = parseFloat(document.getElementById('weightInput').value) || 0;
            const reps = parseInt(document.getElementById('repsInput').value) || 0;
            const rpe = parseInt(document.getElementById('rpeInput').value) || 0;
            
            currentExercise.completedSets.push({
                weight,
                reps,
                rpe
            });
            
            updateCurrentExerciseDisplay();
            updateExercisePlanList();
            updateWorkoutStats();
            
            showNotification(`Set ${currentExercise.completedSets.length} completed!`, 'success');
            
            startRestTimer();
            
            if (currentExercise.completedSets.length >= currentExercise.sets) {
                if (currentExerciseIndex < workoutData.exercises.length - 1) {
                    setTimeout(() => {
                        currentExerciseIndex++;
                        updateCurrentExerciseDisplay();
                        updateExercisePlanList();
                    }, 1000);
                }
            }
        }
        
        function skipSet() {
            if (workoutData.exercises.length === 0) return;
            
            const currentExercise = workoutData.exercises[currentExerciseIndex];
            
            currentExercise.completedSets.push({
                weight: 0,
                reps: 0,
                rpe: 0,
                skipped: true
            });
            
            updateCurrentExerciseDisplay();
            updateExercisePlanList();
            
            showNotification(`Set ${currentExercise.completedSets.length} skipped`, 'warning');
            
            if (currentExercise.completedSets.length >= currentExercise.sets) {
                if (currentExerciseIndex < workoutData.exercises.length - 1) {
                    setTimeout(() => {
                        currentExerciseIndex++;
                        updateCurrentExerciseDisplay();
                        updateExercisePlanList();
                    }, 1000);
                }
            }
        }
        
        function startRestTimer() {
            if (restTimerInterval) {
                clearInterval(restTimerInterval);
            }
            
            restTimeRemaining = restDuration;
            
            document.getElementById('startRestBtn').style.display = 'none';
            document.getElementById('stopRestBtn').style.display = 'inline-flex';
            
            showNotification(`Rest timer started: ${restDuration} seconds`, 'success');
            
            updateRestTimerDisplay();
        
            restTimerInterval = setInterval(() => {
                restTimeRemaining--;
                
                if (restTimeRemaining <= 0) {
                    stopRestTimer();
                    showNotification('Rest time is up! Ready for next set', 'success');
                    
                    const timerSound = document.getElementById('timerSound');
                    if (timerSound) {
                        timerSound.play().catch(e => console.error('Error playing sound:', e));
                    }
                } else {
                    updateRestTimerDisplay();
                }
            }, 1000);
        }
        
        function stopRestTimer() {
            if (restTimerInterval) {
                clearInterval(restTimerInterval);
                restTimerInterval = null;
            }
            
            document.getElementById('startRestBtn').style.display = 'inline-flex';
            document.getElementById('stopRestBtn').style.display = 'none';
            
            updateRestTimerDisplay();
        }
        
        function updateRestTimerDisplay() {
            const display = document.getElementById('restTimerDisplay');
            if (!display) return;
            
            const minutes = Math.floor(restTimeRemaining / 60);
            const seconds = restTimeRemaining % 60;
            
            display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            const progressElement = document.querySelector('.rest-timer-progress');
            if (progressElement) {
                const progressPercent = ((restDuration - restTimeRemaining) / restDuration) * 100;
                progressElement.style.setProperty('--progress', `${progressPercent}%`);
            }
        }
        
        function saveWorkout() {
            const rating = document.getElementById('workoutRating') ? 
                parseInt(document.getElementById('workoutRating').value) : 0;

            const postData = {
                name: document.getElementById('workoutNameInput').value.trim() || 'Quick Workout',
                notes: document.getElementById('workoutNotesInput').value.trim() || '',
                duration: Math.round(workoutData.duration || 0),
                rating: rating,
                exercises: workoutData.exercises.map(exercise => ({
                    name: exercise.name,
                    sets: exercise.completedSets.map(set => ({
                        weight: parseFloat(set.weight) || 0,
                        reps: parseInt(set.reps) || 0,
                        rpe: parseInt(set.rpe) || 0
                    }))
                })).filter(exercise => exercise.sets.length > 0)
            };

            const totalSets = workoutData.exercises.reduce((total, exercise) => 
                total + exercise.completedSets.length, 0);
            
            const totalSetsElement = document.getElementById('statsTotalSets');
            if (totalSetsElement) {
                totalSetsElement.textContent = totalSets.toString();
            }

            if (postData.exercises.length === 0) {
                showNotification('No exercises with completed sets to save', 'error');
                return;
            }

            console.log("Sending workout data:", postData);

            const formData = new FormData();
            formData.append('save_workout', '1');
            formData.append('workout_data', JSON.stringify(postData));

            fetch('active-workout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message || 'Unknown error');
                        } catch (e) {
                            throw new Error(text || 'Unknown error');
                        }
                    });
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        return { message: text };
                    }
                });
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.message || 'Unknown error');
                }
                showNotification(data.message || 'Workout saved successfully!', 'success');
                
                const saveAsTemplate = document.getElementById('saveAsTemplateCheckbox').checked;
                if (saveAsTemplate) {
                }
                
                setTimeout(() => window.location.href = 'workout-history.php', 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error saving workout: ' + error.message, 'error');
            });
        }

        function updateWorkoutStats() {
            const totalSets = workoutData.exercises.reduce((total, exercise) => 
                total + exercise.completedSets.length, 0);

            const totalSetsElement = document.getElementById('statsTotalSets');
            if (totalSetsElement) {
                totalSetsElement.textContent = totalSets.toString();
            }
        }
        
        function initializeCharts() {
    const chartElement = document.getElementById('workoutProgressChart');
    if (!chartElement) return;
    
    if (chartElement.chart) {
        chartElement.chart.destroy();
    }
    
    const exerciseNames = workoutData.exercises.map(ex => ex.name);
    const exerciseVolumes = workoutData.exercises.map(ex => {
        return ex.completedSets.reduce((sum, set) => sum + (set.weight * set.reps), 0);
    });
    
    chartElement.chart = new Chart(chartElement, {
        type: 'bar',
        data: {
            labels: exerciseNames,
            datasets: [{
                label: 'Volume (kg)',
                data: exerciseVolumes,
                backgroundColor: 'rgba(157, 78, 221, 0.7)',
                borderColor: '#9d4edd',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });
}
    </script>
</body>
</html> 