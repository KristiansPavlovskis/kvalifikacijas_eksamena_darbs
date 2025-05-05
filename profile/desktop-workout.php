<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/active-workout.php");
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

try {
    if (tableExists($conn, 'workout_templates')) {
        $all_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $all_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $all_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $strength_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                         WHERE user_id = ? AND 
                                         (LOWER(name) LIKE '%strength%' OR 
                                          LOWER(description) LIKE '%strength%')";
        $stmt = mysqli_prepare($conn, $strength_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $strength_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $hiit_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                     WHERE user_id = ? AND 
                                     (LOWER(name) LIKE '%hiit%' OR 
                                      LOWER(description) LIKE '%hiit%' OR
                                      LOWER(name) LIKE '%interval%' OR
                                      LOWER(description) LIKE '%interval%')";
        $stmt = mysqli_prepare($conn, $hiit_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hiit_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $cardio_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                       WHERE user_id = ? AND 
                                       (LOWER(name) LIKE '%cardio%' OR 
                                        LOWER(description) LIKE '%cardio%')";
        $stmt = mysqli_prepare($conn, $cardio_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cardio_templates_count = mysqli_fetch_assoc($result)['count'];
    } else {
        $all_templates_count = 0;
        $strength_templates_count = 0;
        $hiit_templates_count = 0;
        $cardio_templates_count = 0;
    }
} catch (Exception $e) {
    error_log("Error counting templates by category: " . $e->getMessage());
    $all_templates_count = 0;
    $strength_templates_count = 0;
    $hiit_templates_count = 0;
    $cardio_templates_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Workout - GYMVERSE</title>
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
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .page-actions {
            display: flex;
            gap: 15px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .breadcrumb a {
            color: var(--gray-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: white;
        }

        .breadcrumb-separator {
            margin: 0 10px;
            color: var(--gray-light);
        }

        .breadcrumb-current {
            color: var(--secondary);
            font-weight: 500;
        }

        .workout-layout {
            display: grid;
            grid-template-columns: 3fr 7fr 4fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .categories-panel, .templates-panel, .selected-panel {
            background-color: var(--dark-card);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--primary);
        }

        .panel-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-content {
            padding: 20px;
        }

        .category-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .category-item:hover, .category-item.active {
            background-color: rgba(255, 255, 255, 0.07);
            border-left-color: var(--primary);
            transform: translateX(5px);
        }

        .category-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .category-name i {
            color: var(--primary);
        }

        .category-count {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .filters-section {
            margin-top: 30px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-label {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 10px;
            display: block;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .view-toggle {
            display: flex;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 5px;
        }

        .view-toggle-btn {
            flex: 1;
            background: none;
            border: none;
            color: var(--gray-light);
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .view-toggle-btn.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
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

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .template-grid::-webkit-scrollbar {
            width: 6px;
        }

        .template-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .template-grid::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        .template-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .template-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
            cursor: pointer;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(67, 97, 238, 0.3);
        }

        .template-card-header {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .template-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .template-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.8rem;
            color: var(--gray-light);
        }

        .template-card-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .template-card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .template-card-exercises {
            margin-bottom: 15px;
        }

        .template-card-exercise {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .template-card-exercise i {
            color: var(--primary);
            font-size: 0.8rem;
        }

        .template-card-footer {
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.02);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .template-card-date {
            font-size: 0.8rem;
            color: var(--gray-light);
        }

        .template-card-action {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .template-card-action:hover {
            background-color: rgba(67, 97, 238, 0.2);
        }

        .template-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .template-list::-webkit-scrollbar {
            width: 6px;
        }

        .template-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .template-list::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        .template-list::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .template-list-item {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: var(--transition);
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .template-list-item:hover {
            background-color: rgba(255, 255, 255, 0.07);
            transform: translateX(5px);
            border-left-color: var(--primary);
        }

        .template-list-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .template-list-item-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .template-list-item-difficulty {
            font-size: 0.8rem;
            padding: 3px 10px;
            border-radius: 20px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .template-list-item-meta {
            display: flex;
            gap: 15px;
            color: var(--gray-light);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .template-list-item-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .template-list-item-exercises {
            font-size: 0.9rem;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .selected-template {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .selected-template-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .selected-template-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .selected-template-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .selected-template-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .selected-template-body {
            padding: 20px;
            flex-grow: 1;
        }

        .selected-template-description {
            margin-bottom: 20px;
            color: var(--gray-light);
            line-height: 1.6;
        }

        .selected-template-exercises {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .selected-template-exercises::-webkit-scrollbar {
            width: 6px;
        }

        .selected-template-exercises::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .selected-template-exercises::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        .selected-template-exercises::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .exercise-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 300px;
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
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        .exercise-list::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .previous-sets-section {
            margin-bottom: 30px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .previous-sets-section::-webkit-scrollbar {
            width: 6px;
        }

        .previous-sets-section::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .previous-sets-section::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        .previous-sets-section::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .selected-template-exercise {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .selected-template-exercise-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .selected-template-exercise-name i {
            color: var(--primary);
        }

        .selected-template-exercise-details {
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .selected-template-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            padding: 30px;
            color: var(--gray-light);
        }

        .selected-template-placeholder i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .selected-template-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .begin-workout-btn {
            width: 100%;
            padding: 15px;
            background: var(--gradient-blue);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .begin-workout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }

        .modify-template-btn {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modify-template-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-light);
        }

        .empty-message i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-message p {
            margin-bottom: 20px;
        }

        .steps-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 0 auto;
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
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(67, 97, 238, 0.5);
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

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: slide-in 0.3s ease forwards, fade-out 0.3s ease 4.7s forwards;
            max-width: 350px;
        }

        .notification.success {
            background: var(--gradient-green);
        }

        .notification.error {
            background: var(--gradient-pink);
        }

        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fade-out {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        @media (max-width: 1200px) {
            .workout-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }

        .workout-tracking-layout {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .workout-header {
            background-color: var(--dark-card);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--card-shadow);
            display: none;
        }

        .workout-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .workout-progress {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .timer-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            min-width: 100px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(to right, #ef476f, #ff5c8a);
            border-radius: 4px;
        }

        .progress-percentage {
            font-size: 0.9rem;
            color: var(--gray-light);
            min-width: 100px;
            text-align: right;
        }

        .overview-panel, .current-exercise-panel, .next-exercise-panel {
            background-color: var(--dark-card);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel-section {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .panel-section:last-child {
            border-bottom: none;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .exercise-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .exercise-list-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            transition: var(--transition);
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .exercise-list-item.completed {
            border-left-color: var(--success);
        }

        .exercise-list-item.current {
            border-left-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
        }

        .exercise-list-item:hover:not(.current) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .exercise-status {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: var(--gray-light);
        }

        .exercise-status.completed {
            background-color: var(--success);
            color: white;
        }

        .exercise-status.current {
            background-color: var(--primary);
            color: white;
        }

        .exercise-name {
            flex: 1;
            font-weight: 500;
        }

        .exercise-progress {
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .workout-notes {
            width: 100%;
            height: 120px;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
        }

        .workout-notes:focus {
            outline: none;
            border-color: var(--primary);
        }

        .exercise-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .exercise-target {
            font-size: 1rem;
            color: var(--gray-light);
            margin-bottom: 25px;
        }

        .current-set-section, .previous-sets-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--gray-light);
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .exercise-input {
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            text-align: center;
        }

        .exercise-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .complete-set-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #ef476f, #ff5c8a);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .complete-set-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(239, 71, 111, 0.3);
        }

        .sets-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .sets-table th {
            text-align: left;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray-light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .sets-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sets-table tr:last-child td {
            border-bottom: none;
        }

        .rest-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            height: 100%;
            text-align: center;
        }

        .rest-message {
            margin-bottom: 30px;
        }

        .rest-message h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .rest-message p {
            color: var(--gray-light);
        }

        .rest-timer-display {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 30px;
            font-family: 'Courier New', monospace;
            color: #ef476f;
        }

        .rest-controls {
            display: flex;
            gap: 15px;
        }

        .skip-rest-btn {
            padding: 12px 25px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .skip-rest-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .next-exercise-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .next-exercise-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #ef476f;
        }

        .next-exercise-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .next-exercise-details {
            font-size: 0.9rem;
            color: var(--gray-light);
        }

        .timer-display {
            font-size: 3rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            color: #ef476f;
        }

        .timer-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .timer-preset-btn {
            padding: 8px 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .timer-preset-btn:hover, .timer-preset-btn.active {
            background-color: rgba(239, 71, 111, 0.2);
            border-color: rgba(239, 71, 111, 0.3);
        }

        .stats-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            color: var(--gray-light);
        }

        .stat-value {
            font-weight: 600;
        }

        .workout-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .footer-btn {
            padding: 12px 25px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .footer-btn.danger {
            background-color: rgba(239, 71, 111, 0.1);
            border-color: rgba(239, 71, 111, 0.2);
            color: #ff5c8a;
        }

        .footer-btn.danger:hover {
            background-color: rgba(239, 71, 111, 0.2);
        }

        @media (max-width: 1200px) {
            .workout-tracking-layout {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto;
            }
            
            .next-exercise-panel {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .workout-tracking-layout {
                grid-template-columns: 1fr;
            }
            
            .next-exercise-panel {
                grid-column: span 1;
            }
            
            .workout-footer {
                flex-direction: column;
            }
        }

        #step3-content {
            animation: fade-in 0.5s ease-out;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            padding-right: 10px;
        }

        #step3-content::-webkit-scrollbar {
            width: 6px;
        }

        #step3-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        #step3-content::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.3);
            border-radius: 10px;
        }

        #step3-content::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.5);
        }

        .workout-complete-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .workout-complete-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .workout-complete-date {
            font-size: 1rem;
            color: var(--gray-light);
        }

        .workout-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-stat-card {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-stat-label {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 10px;
        }

        .summary-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-stat-comparison {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .summary-stat-comparison.positive {
            color: var(--success);
        }

        .summary-stat-comparison.neutral {
            color: var(--gray-light);
        }

        .summary-stat-comparison.negative {
            color: var(--danger);
        }

        .workout-chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
            min-height: 300px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .exercise-breakdown {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 30px;
        }

        .exercise-breakdown-list {
            margin-top: 15px;
        }

        .exercise-breakdown-item {
            padding: 15px;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.03);
            margin-bottom: 10px;
        }

        .exercise-breakdown-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .exercise-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            margin-right: 15px;
        }

        .exercise-detail {
            flex: 1;
        }

        .exercise-name {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .exercise-sets {
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .exercise-volume {
            font-weight: 600;
        }

        .exercise-comparison {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .exercise-comparison.positive {
            color: var(--success);
        }

        .summary-actions {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 30px;
        }

        .save-workout-btn, .save-template-btn {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
        }

        .save-workout-btn {
            background-color: var(--primary);
            color: white;
        }

        .save-workout-btn:hover {
            background-color: var(--primary-dark);
        }

        .save-template-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .save-template-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .workout-notes-container {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Start Workout</h1>
                <div class="page-actions">
                    <button class="btn btn-primary" id="startNewWorkoutBtn">
                        <i class="fas fa-plus"></i> Create Template
                    </button>
                </div>
            </div>
            
            <div class="breadcrumb">
                <a href="dashboard.php">Home</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <a href="workout-history.php">Workouts</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Start Workout</span>
            </div>

           
            <div class="step-content active" id="step1-content">
                <div class="workout-layout">
                    <div class="categories-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-th-large"></i> Categories
                            </div>
                        </div>
                        <div class="panel-content">
                            <div class="category-list">
                                <div class="category-item active" data-category="all">
                                    <div class="category-name">
                                        <i class="fas fa-layer-group"></i> All Templates
                                    </div>
                                    <div class="category-count"><?php echo $all_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="strength">
                                    <div class="category-name">
                                        <i class="fas fa-dumbbell"></i> Strength Training
                                    </div>
                                    <div class="category-count"><?php echo $strength_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="hiit">
                                    <div class="category-name">
                                        <i class="fas fa-bolt"></i> HIIT
                                    </div>
                                    <div class="category-count"><?php echo $hiit_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="cardio">
                                    <div class="category-name">
                                        <i class="fas fa-heartbeat"></i> Cardio
                                    </div>
                                    <div class="category-count"><?php echo $cardio_templates_count; ?></div>
                                </div>
                            </div>
                            
                            <div class="filters-section">
                                <h3 class="panel-title" style="margin-bottom: 15px;">
                                    <i class="fas fa-filter"></i> Filters
                                </h3>
                                <div class="filter-group">
                                    <label class="filter-label">Duration</label>
                                    <select class="filter-select" id="durationFilter">
                                        <option value="any">Any duration</option>
                                        <option value="short">Short (< 30 min)</option>
                                        <option value="medium">Medium (30-60 min)</option>
                                        <option value="long">Long (> 60 min)</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Difficulty</label>
                                    <select class="filter-select" id="difficultyFilter">
                                        <option value="any">All levels</option>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
            
                    <div class="templates-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-clipboard-list"></i> Available Templates
                            </div>
                            <div class="panel-actions">
                                <button class="view-toggle-btn" id="gridViewBtn">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="view-toggle-btn active" id="listViewBtn">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                        <div class="panel-content">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" id="templateSearch" placeholder="Search templates...">
                            </div>
                            
                            <?php if ($templates && mysqli_num_rows($templates) > 0): ?>
                                <div class="template-grid" style="display: none;">
                                    <?php 
                                    mysqli_data_seek($templates, 0);
                                    while ($template = mysqli_fetch_assoc($templates)): 
                                        $categoryClasses = 'template-all';
                                        $difficultyClass = strtolower($template['difficulty']);
                                        $durationClass = '';
                                        
                                        if ($template['estimated_time'] < 30) {
                                            $durationClass = 'duration-short';
                                        } elseif ($template['estimated_time'] < 60) {
                                            $durationClass = 'duration-medium';
                                        } else {
                                            $durationClass = 'duration-long';
                                        }

                                        if (
                                            stripos($template['name'], 'strength') !== false || 
                                            stripos($template['description'], 'strength') !== false
                                        ) {
                                            $categoryClasses .= ' template-strength';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'hiit') !== false || 
                                            stripos($template['description'], 'hiit') !== false ||
                                            stripos($template['name'], 'interval') !== false || 
                                            stripos($template['description'], 'interval') !== false
                                        ) {
                                            $categoryClasses .= ' template-hiit';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'cardio') !== false || 
                                            stripos($template['description'], 'cardio') !== false
                                        ) {
                                            $categoryClasses .= ' template-cardio';
                                        }
                                    ?>
                                    <div class="template-card <?php echo $categoryClasses . ' ' . $difficultyClass . ' ' . $durationClass; ?>" data-id="<?php echo $template['id']; ?>">
                                        <div class="template-card-header">
                                            <div class="template-card-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                            <div class="template-card-meta">
                                                <div class="template-card-meta-item">
                                                    <i class="fas fa-stopwatch"></i>
                                                    <span><?php echo $template['estimated_time']; ?> min</span>
                                                </div>
                                                <div class="template-card-meta-item">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <span><?php echo $template['exercise_count']; ?> exercises</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="template-card-body">
                                            <?php if (!empty($template['description'])): ?>
                                                <div class="template-card-description"><?php echo htmlspecialchars(substr($template['description'], 0, 50)) . (strlen($template['description']) > 50 ? '...' : ''); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="template-card-footer">
                                            <div class="template-card-action">
                                                <i class="fas fa-chevron-right"></i> Select
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                        
                                <div class="template-list">
                                    <?php 
                                    mysqli_data_seek($templates, 0);
                                    while ($template = mysqli_fetch_assoc($templates)): 
                                        $categoryClasses = 'template-all';
                                        $difficultyClass = strtolower($template['difficulty']);
                                        $durationClass = '';
                                        
                                        if ($template['estimated_time'] < 30) {
                                            $durationClass = 'duration-short';
                                        } elseif ($template['estimated_time'] < 60) {
                                            $durationClass = 'duration-medium';
                                        } else {
                                            $durationClass = 'duration-long';
                                        }

                                        if (
                                            stripos($template['name'], 'strength') !== false || 
                                            stripos($template['description'], 'strength') !== false
                                        ) {
                                            $categoryClasses .= ' template-strength';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'hiit') !== false || 
                                            stripos($template['description'], 'hiit') !== false ||
                                            stripos($template['name'], 'interval') !== false || 
                                            stripos($template['description'], 'interval') !== false
                                        ) {
                                            $categoryClasses .= ' template-hiit';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'cardio') !== false || 
                                            stripos($template['description'], 'cardio') !== false
                                        ) {
                                            $categoryClasses .= ' template-cardio';
                                        }
                                    ?>
                                    <div class="template-list-item <?php echo $categoryClasses . ' ' . $difficultyClass . ' ' . $durationClass; ?>" data-id="<?php echo $template['id']; ?>">
                                        <div class="template-list-item-header">
                                            <div class="template-list-item-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                            <div class="template-list-item-difficulty"><?php echo ucfirst($template['difficulty'] ?: 'Beginner'); ?></div>
                                        </div>
                                        <div class="template-list-item-meta">
                                            <div class="template-list-item-meta-item">
                                                <i class="fas fa-stopwatch"></i>
                                                <span><?php echo $template['estimated_time']; ?> min</span>
                                            </div>
                                            <div class="template-list-item-meta-item">
                                                <i class="fas fa-dumbbell"></i>
                                                <span><?php echo $template['exercise_count']; ?> exercises</span>
                                            </div>
                                        </div>
                                        <?php if (!empty($template['description'])): ?>
                                            <div class="template-list-item-description"><?php echo htmlspecialchars(substr($template['description'], 0, 80)) . (strlen($template['description']) > 80 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-message">
                                    <i class="fas fa-clipboard"></i>
                                    <p>No workout templates found. Create a template first to start a workout.</p>
                                    <a href="workout-templates.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Template
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="selected-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-clipboard-check"></i> Selected Template
                            </div>
                        </div>
                        <div id="selectedTemplateContainer">
                            <div class="selected-template-placeholder">
                                <i class="fas fa-hand-pointer"></i>
                                <h3>Choose a template to view</h3>
                                <p>Select a workout template from the list to view details and begin your workout.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-content" id="step2-content">
                <div class="workout-header">
                    <h1 class="workout-title" id="workout-title">Loading workout...</h1>
                    <div class="workout-progress">
                        <div class="timer-container">
                            <i class="fas fa-clock"></i>
                            <span id="workout-timer">00:00:00</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-percentage">0% Complete</div>
                    </div>
                </div>

                <div class="workout-tracking-layout">
                    <div class="overview-panel">
                        <div class="panel-section">
                            <h2 class="panel-title">Workout Overview</h2>
                            <div class="exercise-list" id="exercise-list">
                            </div>
                        </div>
                        
                        <div class="panel-section">
                            <h2 class="panel-title">Notes</h2>
                            <textarea class="workout-notes" id="workout-notes" placeholder="Add notes about your workout..."></textarea>
                        </div>
                    </div>

                    <div class="current-exercise-panel">
                        <div id="current-exercise-container">
                            <h2 class="exercise-title" id="current-exercise-name">Loading exercise...</h2>
                            <p class="exercise-target" id="exercise-target">Target: -</p>
                            
                            <div class="current-set-section">
                                <h3 class="section-title">Current Set</h3>
                                <div class="input-row">
                                    <div class="input-group">
                                        <label for="weight-input">Weight (kg)</label>
                                        <input type="number" id="weight-input" class="exercise-input" value="0">
                                    </div>
                                    <div class="input-group">
                                        <label for="reps-input">Reps</label>
                                        <input type="number" id="reps-input" class="exercise-input" value="0">
                                    </div>
                                </div>
                                
                                <button id="complete-set-btn" class="complete-set-btn">Complete Set</button>
                            </div>
                            
                            <div class="previous-sets-section">
                                <h3 class="section-title">Previous Sets</h3>
                                <table class="sets-table">
                                    <thead>
                                        <tr>
                                            <th>Set</th>
                                            <th>Weight</th>
                                            <th>Reps</th>
                                            <th>1RM</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previous-sets-table">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="rest-screen" class="rest-screen" style="display: none;">
                            <div class="rest-message">
                                <h2>Rest Time</h2>
                                <p>How was that set?</p>
                            </div>
                            
                            <div class="rpe-selection">
                                <button class="rpe-button" data-rpe="1">😊</button>
                                <button class="rpe-button" data-rpe="2">🙂</button>
                                <button class="rpe-button" data-rpe="3">😐</button>
                                <button class="rpe-button" data-rpe="4">🥵</button>
                                <button class="rpe-button" data-rpe="5">💀</button>
                            </div>
                            
                            <div class="rest-timer-display" id="rest-timer-display">00:00</div>
                            
                            <div class="next-exercise-preview">
                                <h3>Next Up</h3>
                                <div id="rest-next-exercise"></div>
                            </div>
                            
                            <div class="rest-controls">
                                <button id="skip-rest-btn" class="skip-rest-btn">Skip Rest</button>
                            </div>
                        </div>
                    </div>

                    <div class="next-exercise-panel">
                        <div class="panel-section">
                            <h2 class="panel-title">Next Exercise</h2>
                            <div class="next-exercise-card" id="next-exercise-card">
                            </div>
                        </div>
                        
                        
                        
                        <div class="panel-section">
                            <h2 class="panel-title">Workout Stats</h2>
                            <div class="stats-grid">
                                <div class="stat-row">
                                    <div class="stat-label">Sets Completed</div>
                                    <div class="stat-value" id="stats-sets-completed">0/0</div>
                                </div>
                                <div class="stat-row">
                                    <div class="stat-label">Volume</div>
                                    <div class="stat-value" id="stats-volume">0 kg</div>
                                </div>
                                <div class="stat-row">
                                    <div class="stat-label">Calories Burned</div>
                                    <div class="stat-value" id="stats-time-remaining">0 kcal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="workout-footer">
                    <button id="add-exercise-btn" class="footer-btn"><i class="fas fa-plus"></i> Add Exercise</button>
                    <button id="skip-exercise-btn" class="footer-btn"><i class="fas fa-forward"></i> Skip Exercise</button>
                    <button id="end-workout-btn" class="footer-btn danger"><i class="fas fa-flag-checkered"></i> End Workout</button>
                </div>
            </div>
            
            <div id="step3-content" class="step-content">
                <div class="workout-complete-header">
                    <h1 class="workout-complete-title">Workout Complete!</h1>
                    <div class="workout-complete-date" id="workout-complete-date">Loading...</div>
                </div>

                <div class="workout-summary-grid">
                    <div class="summary-stat-card">
                        <div class="summary-stat-label">Total Volume</div>
                        <div class="summary-stat-value" id="summary-volume">0 kg</div>
                        <div class="summary-stat-comparison positive" id="volume-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label">Total Sets</div>
                        <div class="summary-stat-value" id="summary-sets">0</div>
                        <div class="summary-stat-comparison neutral" id="sets-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label">Peak Weight</div>
                        <div class="summary-stat-value" id="summary-peak-weight">0 kg</div>
                        <div class="summary-stat-comparison positive" id="peak-weight-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label">Avg Rest Time</div>
                        <div class="summary-stat-value" id="summary-rest-time">0 sec</div>
                        <div class="summary-stat-comparison positive" id="rest-time-comparison">-</div>
                    </div>
                </div>


                <div class="exercise-breakdown">
                    <h3 class="chart-title">Exercise Breakdown</h3>
                    <div class="exercise-breakdown-list" id="exercise-breakdown-list">
                    </div>
                </div>

                <div class="workout-notes-container">
                    <h3 class="chart-title">Workout Notes</h3>
                    <textarea class="workout-notes" id="summary-workout-notes" placeholder="Add your thoughts about today's workout..."></textarea>
                </div>

                <div class="summary-actions">
                    <button class="save-workout-btn" id="final-save-workout-btn">
                        <i class="fas fa-save"></i> Save Workout
                    </button>
                    <button class="save-template-btn" id="save-as-template-btn">
                        <i class="fas fa-bookmark"></i> Save as Template
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let workoutState = {
                templateId: null,
                templateName: '',
                exercises: [],
                currentExerciseIndex: 0,
                currentSet: 1,
                startTime: null,
                endTime: null,
                timerInterval: null,
                restTimerInterval: null,
                restTime: 90,
                totalVolume: 0,
                totalSets: 0,
                completedSets: 0,
                notes: '',
                peakWeight: 0
            };
            

            const gridViewBtn = document.getElementById('gridViewBtn');
            const listViewBtn = document.getElementById('listViewBtn');
            const templateGrid = document.querySelector('.template-grid');
            const templateList = document.querySelector('.template-list');
            const skipRestBtn = document.getElementById('skip-rest-btn');


            if (gridViewBtn && listViewBtn) {
                gridViewBtn.addEventListener('click', function() {
                    gridViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    templateGrid.style.display = 'grid';
                    templateList.style.display = 'none';
                });

                listViewBtn.addEventListener('click', function() {
                    listViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    templateList.style.display = 'block';
                    templateGrid.style.display = 'none';
                });
            }

            const categoryItems = document.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                item.addEventListener('click', function() {
                    categoryItems.forEach(cat => cat.classList.remove('active'));
                    this.classList.add('active');

                    const category = this.dataset.category;
                    filterTemplates();
                });
            });

            const durationFilter = document.getElementById('durationFilter');
            const difficultyFilter = document.getElementById('difficultyFilter');

            if (durationFilter) {
                durationFilter.addEventListener('change', filterTemplates);
            }

            if (difficultyFilter) {
                difficultyFilter.addEventListener('change', filterTemplates);
            }

            const templateSearch = document.getElementById('templateSearch');
            if (templateSearch) {
                templateSearch.addEventListener('input', filterTemplates);
            }

            const templateCards = document.querySelectorAll('.template-card');
            const templateListItems = document.querySelectorAll('.template-list-item');
            const selectedTemplateContainer = document.getElementById('selectedTemplateContainer');

            [...templateCards, ...templateListItems].forEach(template => {
                template.addEventListener('click', function() {
                    const templateId = this.dataset.id;
                    loadTemplateDetails(templateId);
                });
            });

            function filterTemplates() {
                const activeCategory = document.querySelector('.category-item.active').dataset.category;
                const selectedDuration = durationFilter.value;
                const selectedDifficulty = difficultyFilter.value;
                const searchQuery = templateSearch.value.toLowerCase();

                const allTemplates = [...document.querySelectorAll('.template-card'), ...document.querySelectorAll('.template-list-item')];

                allTemplates.forEach(template => {
                    let showTemplate = true;

                    if (activeCategory !== 'all' && !template.classList.contains(`template-${activeCategory}`)) {
                        showTemplate = false;
                    }

                    if (selectedDuration !== 'any') {
                        if (selectedDuration === 'short' && !template.classList.contains('duration-short')) {
                            showTemplate = false;
                        } else if (selectedDuration === 'medium' && !template.classList.contains('duration-medium')) {
                            showTemplate = false;
                        } else if (selectedDuration === 'long' && !template.classList.contains('duration-long')) {
                            showTemplate = false;
                        }
                    }

                    if (selectedDifficulty !== 'any' && !template.classList.contains(selectedDifficulty)) {
                        showTemplate = false;
                    }

                    if (searchQuery) {
                        const templateName = template.querySelector('.template-card-title, .template-list-item-title').textContent.toLowerCase();
                        const templateDescription = template.querySelector('.template-card-description, .template-list-item-description')?.textContent.toLowerCase() || '';
                        
                        if (!templateName.includes(searchQuery) && !templateDescription.includes(searchQuery)) {
                            showTemplate = false;
                        }
                    }

                    template.style.display = showTemplate ? '' : 'none';
                });
            }

            function loadTemplateDetails(templateId) {
                [...templateCards, ...templateListItems].forEach(template => {
                    template.classList.remove('selected');
                    if (template.dataset.id === templateId) {
                        template.classList.add('selected');
                    }
                });

                selectedTemplateContainer.innerHTML = '<div class="selected-template-placeholder"><i class="fas fa-spinner fa-spin"></i><h3>Loading template...</h3></div>';

                fetch(`get_template.php?id=${templateId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            renderSelectedTemplate(data.template, data.exercises);
                            showNotification(`Template "${data.template.name}" loaded successfully!`, 'success');
                        } else {
                            throw new Error(data.error || 'Failed to load template');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading template:', error);
                        selectedTemplateContainer.innerHTML = `
                            <div class="selected-template-placeholder">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error loading template</h3>
                                <p>${error.message}</p>
                            </div>
                        `;
                        showNotification(`Error: ${error.message}`, 'error');
                    });
            }

            function renderSelectedTemplate(template, exercises) {
                const difficultyLabel = template.difficulty ? template.difficulty.charAt(0).toUpperCase() + template.difficulty.slice(1) : 'Beginner';
                
                let exercisesHtml = '';
                exercises.forEach(exercise => {
                    exercisesHtml += `
                        <div class="selected-template-exercise">
                            <div class="selected-template-exercise-name">
                                <i class="fas fa-dumbbell"></i>
                                ${exercise.exercise_name}
                            </div>
                            <div class="selected-template-exercise-details">
                                ${exercise.sets} sets • ${exercise.rest_time}s rest
                            </div>
                        </div>
                    `;
                });

                const html = `
                    <div class="selected-template">
                        <div class="selected-template-header">
                            <h2 class="selected-template-title">${template.name}</h2>
                            <div class="selected-template-meta">
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-stopwatch"></i>
                                    <span>${template.estimated_time} min</span>
                                </div>
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>${exercises.length} exercises</span>
                                </div>
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-bolt"></i>
                                    <span>${difficultyLabel}</span>
                                </div>
                            </div>
                        </div>
                        <div class="selected-template-body">
                            ${template.description ? `<div class="selected-template-description">${template.description}</div>` : ''}
                            <h3 style="margin-bottom: 15px; font-size: 1.1rem;"><i class="fas fa-list-ul"></i> Exercises</h3>
                            <div class="selected-template-exercises">
                                ${exercisesHtml}
                            </div>
                        </div>
                        <div class="selected-template-footer">
                            <button class="begin-workout-btn" data-template-id="${template.id}">
                                <i class="fas fa-play"></i> Begin Workout
                            </button>
                            <button class="modify-template-btn" data-template-id="${template.id}">
                                <i class="fas fa-edit"></i> Modify Template
                            </button>
                        </div>
                    </div>
                `;
                
                selectedTemplateContainer.innerHTML = html;

                const beginWorkoutBtn = selectedTemplateContainer.querySelector('.begin-workout-btn');
                if (beginWorkoutBtn) {
                    beginWorkoutBtn.addEventListener('click', function() {
                        const templateId = this.dataset.templateId;
                        startWorkout(templateId);
                    });
                }

                const modifyTemplateBtn = selectedTemplateContainer.querySelector('.modify-template-btn');
                if (modifyTemplateBtn) {
                    modifyTemplateBtn.addEventListener('click', function() {
                        const templateId = this.dataset.templateId;
                        window.location.href = `workout-templates.php?edit=${templateId}`;
                    });
                }
            }

            function startWorkout(templateId) {
                fetch(`get_template.php?id=${templateId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) throw new Error(data.error);

                        workoutState = {
                            templateId: templateId,
                            templateName: data.template.name,
                            exercises: data.exercises.map(ex => ({
                                ...ex,
                                completedSets: 0,
                                sets: Array(parseInt(ex.sets)).fill().map(() => ({
                                    weight: 0,
                                    reps: 0,
                                    rpe: null,
                                    completed: false
                                }))
                            })),
                            currentExerciseIndex: 0,
                            currentSet: 1,
                            startTime: Date.now(),
                            endTime: null,
                            timerInterval: null,
                            restTimerInterval: null,
                            restTime: parseInt(data.template.rest_time) || 90,
                            totalVolume: 0,
                            totalSets: data.exercises.reduce((acc, ex) => acc + parseInt(ex.sets), 0),
                            completedSets: 0,
                            notes: '',
                            peakWeight: 0,
                            caloriesBurned: 0
                        };

                        workoutState.timerInterval = setInterval(updateWorkoutTimer, 1000);
                        
                        document.getElementById('workout-title').textContent = data.template.name;
                        
                        initializeWorkoutTracking();
                        updateWorkoutStats();
                        goToStep(2);
                    })
                    .catch(error => {
                        showNotification(`Failed to start workout: ${error.message}`, 'error');
                    });
            }

            function updateWorkoutTimer() {
                const elapsed = Math.floor((Date.now() - workoutState.startTime) / 1000);
                const hours = String(Math.floor(elapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(elapsed % 60).padStart(2, '0');
                document.getElementById('workout-timer').textContent = `${hours}:${minutes}:${seconds}`;
            }

            function initializeWorkoutTracking() {
                const exerciseList = document.getElementById('exercise-list');
                exerciseList.innerHTML = workoutState.exercises.map((ex, index) => `
                    <div class="exercise-list-item ${index === 0 ? 'current' : ''}">
                        <div class="exercise-status ${index === 0 ? 'current' : ''}">${index + 1}</div>
                        <div class="exercise-name">${ex.exercise_name}</div>
                        <div class="exercise-progress">0/${ex.sets.length} sets</div>
                    </div>
                `).join('');

                updateCurrentExerciseDisplay();
                updateNextExercisePreview();
            }

            function updateCurrentExerciseDisplay() {
                const currentExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                
                document.getElementById('current-exercise-name').textContent = currentExercise.exercise_name;
                document.getElementById('exercise-target').textContent = `Target: ${currentExercise.target || 'General Exercise'}`;
                document.getElementById('weight-input').value = '';
                document.getElementById('reps-input').value = '';
                
                document.querySelectorAll('.exercise-list-item').forEach((item, index) => {
                    const exercise = workoutState.exercises[index];
                    item.classList.remove('current', 'completed');
                    item.querySelector('.exercise-status').classList.remove('current', 'completed');
                    
                    item.querySelector('.exercise-progress').textContent = 
                        `${exercise.completedSets}/${exercise.sets.length} sets`;
                    
                    if (index === workoutState.currentExerciseIndex) {
                        item.classList.add('current');
                        item.querySelector('.exercise-status').classList.add('current');
                    } else if (exercise.completedSets === exercise.sets.length) {
                        item.classList.add('completed');
                        item.querySelector('.exercise-status').classList.add('completed');
                        item.querySelector('.exercise-status').innerHTML = '<i class="fas fa-check"></i>';
                    }
                });
                
                updateSetsTable();
            }

            function updateNextExercisePreview() {
                const nextIndex = workoutState.currentExerciseIndex + 1;
                const nextExerciseCard = document.getElementById('next-exercise-card');
                
                if (nextIndex < workoutState.exercises.length) {
                    const nextEx = workoutState.exercises[nextIndex];
                    nextExerciseCard.innerHTML = `
                        <div class="next-exercise-icon"><i class="fas fa-dumbbell"></i></div>
                        <div class="next-exercise-name">${nextEx.exercise_name}</div>
                        <div class="next-exercise-details">
                            ${nextEx.sets.length} sets • ${nextEx.rest_time || workoutState.restTime}s rest
                        </div>
                    `;
                } else {
                    nextExerciseCard.innerHTML = `
                        <div class="next-exercise-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="next-exercise-name">Workout Complete!</div>
                        <div class="next-exercise-details">Finish strong! 💪</div>
                    `;
                }
            }

            document.getElementById('complete-set-btn').addEventListener('click', () => {
                const weight = parseFloat(document.getElementById('weight-input').value) || 0;
                const reps = parseInt(document.getElementById('reps-input').value) || 0;
                
                if (reps < 1) {
                    showNotification("Please enter at least 1 rep", 'error');
                    return;
                }

                const currentEx = workoutState.exercises[workoutState.currentExerciseIndex];
                currentEx.sets[workoutState.currentSet - 1] = { 
                    weight, 
                    reps,
                    rpe: null,
                    completed: true 
                };
                currentEx.completedSets++;
                
                workoutState.completedSets++;
                workoutState.totalVolume += weight * reps;
                if (weight > workoutState.peakWeight) {
                    workoutState.peakWeight = weight;
                }

                updateSetsTable();
                updateWorkoutStats();
                updateCurrentExerciseDisplay();
                
                if (workoutState.currentSet < currentEx.sets.length) {
                    workoutState.currentSet++;
                    showRestScreen();
                } else {
                    moveToNextExercise();
                }
            });

            function moveToNextExercise() {
                workoutState.currentExerciseIndex++;
                workoutState.currentSet = 1;
                
                if (workoutState.currentExerciseIndex < workoutState.exercises.length) {
                    updateCurrentExerciseDisplay();
                    updateNextExercisePreview();
                    showRestScreen();
                } else {
                    clearInterval(workoutState.restTimerInterval);
                    document.getElementById('rest-screen').style.display = 'none';
                    endWorkout();
                }
            }

            function endWorkout() {
                console.log("Ending workout and transitioning to summary");
                clearInterval(workoutState.timerInterval);
                clearInterval(workoutState.restTimerInterval);
                
                workoutState.endTime = Date.now();
                const durationMs = workoutState.endTime - workoutState.startTime;
                const durationMinutes = Math.round(durationMs / 60000);
                workoutState.notes = document.getElementById('workout-notes').value;
                
                try {
                    prepareWorkoutSummary(durationMinutes);
                    
                    goToStep(3);
                } catch (error) {
                    console.error("Error preparing workout summary:", error);
                    showNotification("Error generating workout summary. Please try again.", "error");
                }
            }

            function prepareWorkoutSummary(durationMinutes) {
                console.log("Preparing workout summary with duration:", durationMinutes);
                const now = new Date();
                document.getElementById('workout-complete-date').textContent = now.toLocaleDateString() + " at " + now.toLocaleTimeString();
                document.getElementById('summary-volume').textContent = `${workoutState.totalVolume.toFixed(1)} kg`;
                document.getElementById('summary-sets').textContent = `${workoutState.completedSets}`;
                document.getElementById('summary-peak-weight').textContent = `${workoutState.peakWeight} kg`;
                document.getElementById('summary-rest-time').textContent = `${workoutState.restTime} sec`;
                document.getElementById('summary-workout-notes').value = workoutState.notes || '';
                
                const breakdownList = document.getElementById('exercise-breakdown-list');
                breakdownList.innerHTML = '';
                
                workoutState.exercises.forEach(exercise => {
                    if (exercise.completedSets > 0) {
                        let exerciseVolume = 0;
                        let setDetails = [];
                        
                        exercise.sets.forEach(set => {
                            if (set.completed) {
                                exerciseVolume += set.weight * set.reps;
                                setDetails.push(`${set.weight}kg × ${set.reps}`);
                            }
                        });
                        
                        const exerciseItem = document.createElement('div');
                        exerciseItem.className = 'exercise-breakdown-item';
                        exerciseItem.innerHTML = `
                            <div class="exercise-breakdown-header">
                                <div class="exercise-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="exercise-detail">
                                    <div class="exercise-name">${exercise.exercise_name}</div>
                                    <div class="exercise-sets">${exercise.completedSets} sets completed</div>
                                </div>
                                <div class="exercise-volume">${exerciseVolume.toFixed(1)} kg</div>
                            </div>
                            <div class="exercise-sets-detail">
                                ${setDetails.join(' | ')}
                            </div>
                        `;
                        
                        breakdownList.appendChild(exerciseItem);
                    }
                });
                
                setTimeout(() => {
                    try {
                        createSetPerformanceChart();
                        createVolumeDistributionChart();
                    } catch (error) {
                        console.error("Error creating charts:", error);
                    }
                }, 100);
                
                const saveBtn = document.getElementById('final-save-workout-btn');
                const oldSaveBtn = saveBtn.cloneNode(true);
                saveBtn.parentNode.replaceChild(oldSaveBtn, saveBtn);
                
                document.getElementById('final-save-workout-btn').addEventListener('click', saveWorkout);
            }
            
            function createSetPerformanceChart() {
                const canvas = document.getElementById('set-performance-chart');
                if (canvas.__chart) {
                    canvas.__chart.destroy();
                }
                
                const ctx = canvas.getContext('2d');
                
                const labels = [];
                const data = [];
                
                workoutState.exercises.forEach(exercise => {
                    exercise.sets.forEach((set, index) => {
                        if (set.completed) {
                            labels.push(`${exercise.exercise_name} Set ${index + 1}`);
                            data.push(set.weight * set.reps);
                        }
                    });
                });
                
                canvas.__chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Volume (kg)',
                            data: data,
                            backgroundColor: 'rgba(67, 97, 238, 0.7)',
                            borderColor: 'rgba(67, 97, 238, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)',
                                    maxRotation: 90,
                                    minRotation: 45
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            }
            
            function createVolumeDistributionChart() {
                const canvas = document.getElementById('volume-distribution-chart');
                if (canvas.__chart) {
                    canvas.__chart.destroy();
                }
                
                const ctx = canvas.getContext('2d');
                const labels = [];
                const data = [];
                
                workoutState.exercises.forEach(exercise => {
                    if (exercise.completedSets > 0) {
                        let exerciseVolume = 0;
                        
                        exercise.sets.forEach(set => {
                            if (set.completed) {
                                exerciseVolume += set.weight * set.reps;
                            }
                        });
                        
                        if (exerciseVolume > 0) {
                            labels.push(exercise.exercise_name);
                            data.push(exerciseVolume);
                        }
                    }
                });
                
                if (data.length > 0) {
                    canvas.__chart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: [
                                    'rgba(67, 97, 238, 0.7)',
                                    'rgba(76, 201, 240, 0.7)',
                                    'rgba(247, 37, 133, 0.7)',
                                    'rgba(255, 92, 138, 0.7)',
                                    'rgba(58, 86, 212, 0.7)',
                                    'rgba(6, 214, 160, 0.7)',
                                    'rgba(255, 209, 102, 0.7)',
                                    'rgba(239, 71, 111, 0.7)'
                                ],
                                borderColor: 'rgba(15, 15, 26, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    canvas.getContext('2d').fillStyle = 'rgba(255, 255, 255, 0.7)';
                    canvas.getContext('2d').font = '16px Poppins';
                    canvas.getContext('2d').textAlign = 'center';
                    canvas.getContext('2d').fillText('No volume data available', canvas.width / 2, canvas.height / 2);
                }
            }

            function saveWorkout() {
                const workoutData = {
                    title: workoutState.templateName,
                    type: 'strength',
                    notes: document.getElementById('summary-workout-notes').value,
                    duration_minutes: Math.round((workoutState.endTime - workoutState.startTime) / 60000),
                    template_id: workoutState.templateId,
                    total_volume: workoutState.totalVolume,
                    calories_burned: workoutState.caloriesBurned || Math.round(calculateCaloriesBurned()),
                    exercises: workoutState.exercises.map(exercise => {
                        if (exercise.completedSets > 0) {
                            return {
                                name: exercise.exercise_name,
                                sets: exercise.sets.filter(set => set.completed)
                            };
                        }
                        return null;
                    }).filter(ex => ex !== null)
                };
                
                fetch('save_workout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `save_workout=1&workout_data=${encodeURIComponent(JSON.stringify(workoutData))}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Workout saved successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = 'workout-history.php';
                        }, 2000);
                    } else {
                        showNotification(`Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showNotification(`Error: ${error.message}`, 'error');
                });
            }
            
            document.getElementById('save-as-template-btn').addEventListener('click', function() {
                const templateData = {
                    name: `${workoutState.templateName} Copy`,
                    description: 'Template created from completed workout',
                    exercises: workoutState.exercises.map(ex => ({
                        exercise_name: ex.exercise_name,
                        sets: ex.completedSets > 0 ? ex.completedSets : ex.sets.length,
                        rest_time: ex.rest_time || workoutState.restTime
                    }))
                };
                
                localStorage.setItem('template_data', JSON.stringify(templateData));
                window.location.href = 'workout-templates.php?new=1';
            });

            function updateSetsTable() {
                const currentEx = workoutState.exercises[workoutState.currentExerciseIndex];
                const tbody = document.getElementById('previous-sets-table');
                tbody.innerHTML = '';
                
                currentEx.sets.forEach((set, index) => {
                    if (set.completed) {
                        const row = document.createElement('tr');
                        const oneRM = set.weight * (1 + set.reps / 30);
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${set.weight} kg</td>
                            <td>${set.reps}</td>
                            <td>${oneRM.toFixed(1)} kg</td>
                        `;
                        tbody.appendChild(row);
                    }
                });
            }

            function updateWorkoutStats() {
                document.getElementById('stats-sets-completed').textContent = 
                    `${workoutState.completedSets}/${workoutState.totalSets}`;
                
                document.getElementById('stats-volume').textContent = 
                    `${workoutState.totalVolume.toFixed(1)} kg`;
                
                const caloriesBurned = calculateCaloriesBurned();
                document.getElementById('stats-time-remaining').textContent = 
                    `${Math.round(caloriesBurned)} kcal`;
            }

            function calculateCaloriesBurned() {
                let totalCalories = 0;
                
                workoutState.exercises.forEach(exercise => {
                    let exerciseCalories = 0;
                    exercise.sets.forEach(set => {
                        if (set.completed) {
                            const baseCaloriesPerMinute = 4;
                            const setDurationMinutes = (set.reps * 3.5) / 60;
                            const userWeight = 70;
                            const intensityMultiplier = 1 + (set.weight / userWeight) * 0.5;
                            const setCalories = (baseCaloriesPerMinute * setDurationMinutes * intensityMultiplier) + (set.weight * set.reps * 0.02);
                            
                            exerciseCalories += setCalories;
                        }
                    });
                    
                    totalCalories += exerciseCalories;
                });
                
                workoutState.caloriesBurned = Math.round(totalCalories);
                return totalCalories;
            }

            function calculateIntensity(weight, reps) {
                if (weight === 0) {
                    return 4.0;
                }
                
                const volume = weight * reps;
                
                if (volume < 100) {
                    return 4.0;
                } else if (volume < 300) {
                    return 6.0;
                } else {
                    return 8.0;
                }
            }
                
            let restTimeRemaining = 90;

            skipRestBtn.addEventListener('click', () => {
                clearInterval(workoutState.restTimerInterval);
                document.getElementById('rest-screen').style.display = 'none';
                document.getElementById('current-exercise-container').style.display = 'block';
            });

            document.querySelectorAll('.timer-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.timer-preset-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const restTime = parseInt(this.dataset.time);
                    workoutState.restTime = restTime;
                    
                    const minutes = Math.floor(restTime / 60);
                    const seconds = restTime % 60;
                    document.getElementById('timer-display').textContent = 
                        `${minutes}:${String(seconds).padStart(2, '0')}`;
                });
            });

            function updateRestTimerDisplay() {
                const minutes = Math.floor(restTimeRemaining / 60);
                const seconds = restTimeRemaining % 60;
                document.getElementById('rest-timer-display').textContent = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            const weightInput = document.getElementById('weight-input');
            const repsInput = document.getElementById('reps-input');

            weightInput.addEventListener('input', (e) => {
                if (e.target.value < 0) e.target.value = 0;
            });

            repsInput.addEventListener('input', (e) => {
                if (e.target.value < 0) e.target.value = 0;
            });

            function goToStep(stepNumber) {
                document.querySelectorAll('.step-item').forEach(item => {
                    const itemStep = parseInt(item.dataset.step);
                    item.classList.remove('active', 'completed');
                    
                    if (itemStep < stepNumber) {
                        item.classList.add('completed');
                    } else if (itemStep === stepNumber) {
                        item.classList.add('active');
                    }
                });

                document.querySelectorAll('.step-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                document.getElementById(`step${stepNumber}-content`).classList.add('active');
            }

            function showNotification(message, type = 'success') {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => {
                    notification.remove();
                });

                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }

            function initRPESelection() {
                const rpeButtons = document.querySelectorAll('.rpe-button');
                
                rpeButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        rpeButtons.forEach(btn => btn.classList.remove('selected'));
                        button.classList.add('selected');
                        const selectedRPE = parseInt(button.dataset.rpe);
                        
                        const currentExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                        const setIndex = workoutState.currentSet - 1 > 0 ? workoutState.currentSet - 1 : 0;
                        
                        if (currentExercise && currentExercise.sets[setIndex]) {
                            currentExercise.sets[setIndex].rpe = selectedRPE;
                        }
                    });
                });
            }
                
            function showRestScreen() {
                document.getElementById('current-exercise-container').style.display = 'none';
                document.getElementById('rest-screen').style.display = 'flex';
                
                restTimeRemaining = workoutState.restTime;
                updateRestTimerDisplay();
                
                let nextExercise = "";
                if (workoutState.currentSet < workoutState.exercises[workoutState.currentExerciseIndex].sets.length) {
                    nextExercise = `${workoutState.exercises[workoutState.currentExerciseIndex].exercise_name} (Set ${workoutState.currentSet})`;
                } else if (workoutState.currentExerciseIndex + 1 < workoutState.exercises.length) {
                    nextExercise = workoutState.exercises[workoutState.currentExerciseIndex + 1].exercise_name;
                } else {
                    nextExercise = 'Workout Complete!';
                }
                
                document.getElementById('rest-next-exercise').textContent = nextExercise;
                
                if (workoutState.restTimerInterval) {
                    clearInterval(workoutState.restTimerInterval);
                }
                
                workoutState.restTimerInterval = setInterval(() => {
                    restTimeRemaining--;
                    updateRestTimerDisplay();
                    
                    if (restTimeRemaining <= 0) {
                        clearInterval(workoutState.restTimerInterval);
                        document.getElementById('rest-screen').style.display = 'none';
                        document.getElementById('current-exercise-container').style.display = 'block';
                    }
                }, 1000);
            }
            
            document.getElementById('add-exercise-btn').addEventListener('click', () => {
                showNotification('Add exercise functionality coming soon', 'success');
            });
            
            document.getElementById('skip-exercise-btn').addEventListener('click', () => {
                if (workoutState.currentExerciseIndex < workoutState.exercises.length - 1) {
                    moveToNextExercise();
                    showNotification('Skipped to next exercise', 'success');
                } else {
                    showNotification('This is the last exercise', 'error');
                }
            });
            
            document.getElementById('end-workout-btn').addEventListener('click', () => {
                if (confirm('Are you sure you want to end the workout now?')) {
                    endWorkout();
                }
            });
            
            initRPESelection();
            
            const emptyWorkoutCard = document.getElementById('emptyWorkoutCard');
            if (emptyWorkoutCard) {
                emptyWorkoutCard.addEventListener('click', function() {
                    showNotification("Starting empty workout...", 'success');
                    goToStep(2);
                });
            }
        });
    </script>
</body>
</html> 