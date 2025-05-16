<?php

require_once 'profile_access_control.php';

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$email = $_SESSION["email"];
$join_date = "";
$total_workouts = 0;
$total_volume = 0;
$last_active = "";
$body_weight = 0;
$height = 0;
$fitness_level = "Beginner";
$activity_streak = 0;
$active_goals_count = 0;
$completed_goals_count = 0;

function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

try {
    $stmt = mysqli_prepare($conn, "SELECT created_at, last_active, body_weight, height, fitness_level FROM users WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare user info query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user_data = mysqli_fetch_assoc($result)) {
        $join_date = date("M d, Y", strtotime($user_data["created_at"]));
        $last_active = $user_data["last_active"] ? date("M d, Y", strtotime($user_data["last_active"])) : "Never";
        $body_weight = $user_data["body_weight"] ?: 0;
        $height = $user_data["height"] ?: 0;
        $fitness_level = $user_data["fitness_level"] ?: "Beginner";
    }
} catch (Exception $e) {
    error_log("Error fetching user info: " . $e->getMessage());
}

try {
    if (tableExists($conn, 'workouts')) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as workout_count, MAX(created_at) as last_workout FROM workouts WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare workout stats query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($workout_data = mysqli_fetch_assoc($result)) {
            $total_workouts = $workout_data["workout_count"];
            $last_workout_date = $workout_data["last_workout"] ? date("M d, Y", strtotime($workout_data["last_workout"])) : "Never";
        }
    }
} catch (Exception $e) {
    error_log("Error fetching workout stats: " . $e->getMessage());
}

try {
    if (tableExists($conn, 'workouts')) {
        $stmt = mysqli_prepare($conn, "SELECT SUM(total_volume) as total_volume FROM workouts WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare volume query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($volume_data = mysqli_fetch_assoc($result)) {
            $total_volume = round($volume_data["total_volume"] ?: 0);
        }
    }
} catch (Exception $e) {
    error_log("Error fetching workout volume: " . $e->getMessage());
}

try {
    if (tableExists($conn, 'workouts')) {
        $stmt = mysqli_prepare($conn, "SELECT created_at FROM workouts WHERE user_id = ? ORDER BY created_at DESC");
        if ($stmt === false) {
            throw new Exception("Failed to prepare activity streak query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $workout_dates = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $workout_dates[] = date('Y-m-d', strtotime($row['created_at']));
        }
        
        if (!empty($workout_dates)) {
            $today = new DateTime();
            $yesterday = new DateTime();
            $yesterday->modify('-1 day');
            
            $today_str = $today->format('Y-m-d');
            $yesterday_str = $yesterday->format('Y-m-d');
            
            if (in_array($today_str, $workout_dates) || in_array($yesterday_str, $workout_dates)) {
                $activity_streak = 1;
                $date_to_check = clone $yesterday;
                $date_to_check->modify('-1 day');
                
                while (true) {
                    $date_str = $date_to_check->format('Y-m-d');
                    if (in_array($date_str, $workout_dates)) {
                        $activity_streak++;
                        $date_to_check->modify('-1 day');
                    } else {
                        break;
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error calculating activity streak: " . $e->getMessage());
}

try {
    $stmt = mysqli_prepare($conn, "UPDATE users SET last_active = NOW() WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare last active update query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
} catch (Exception $e) {
    error_log("Error updating last active: " . $e->getMessage());
}

try {
    if (tableExists($conn, 'goals')) {
        $stmt = mysqli_prepare($conn, "SELECT 
                    SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count
                  FROM goals WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare goals count query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($goal_data = mysqli_fetch_assoc($result)) {
            $active_goals_count = $goal_data["active_count"] ?: 0;
            $completed_goals_count = $goal_data["completed_count"] ?: 0;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching goals count: " . $e->getMessage());
}

$favorite_exercises = [];
try {
    if (tableExists($conn, 'user_favorite_exercises') && tableExists($conn, 'exercise_library')) {
        $stmt = mysqli_prepare($conn, "SELECT el.exercise_name 
                FROM user_favorite_exercises uf 
                JOIN exercise_library el ON uf.exercise_id = el.id 
                WHERE uf.user_id = ? 
                ORDER BY uf.created_at DESC 
                LIMIT 5");
        if ($stmt === false) {
            throw new Exception("Failed to prepare favorite exercises query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $favorite_exercises[] = $row["exercise_name"];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching favorite exercises: " . $e->getMessage());
}

$recent_workouts = [];
try {
    if (tableExists($conn, 'workouts')) {
        $stmt = mysqli_prepare($conn, "SELECT id, name as workout_name, created_at, duration_minutes as duration, calories_burned, total_volume 
                FROM workouts 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 3");
        if ($stmt === false) {
            throw new Exception("Failed to prepare recent workouts query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $recent_workouts = mysqli_stmt_get_result($stmt);
    }
} catch (Exception $e) {
    error_log("Error fetching recent workouts: " . $e->getMessage());
}

$goals = false;
try {
    if (tableExists($conn, 'goals')) {
        $stmt = mysqli_prepare($conn, "SELECT id, title, description, target_value, current_value, unit, goal_type, deadline as end_date, 
                         DATE(created_at) as start_date 
                  FROM goals 
                  WHERE user_id = ? AND completed = 0 
                  ORDER BY deadline ASC 
                  LIMIT 4");
        if ($stmt === false) {
            throw new Exception("Failed to prepare active goals query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $goals = mysqli_stmt_get_result($stmt);
    }
} catch (Exception $e) {
    error_log("Error fetching active goals: " . $e->getMessage());
}

try {
    $stmt = mysqli_prepare($conn, "SELECT weight, body_fat FROM body_measurements WHERE user_id = ? ORDER BY measurement_date DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($metrics_data = mysqli_fetch_assoc($result)) {
        $current_weight = $metrics_data["weight"] ?: 0;
        $body_fat = $metrics_data["body_fat"] ?: 0;
    } else {
        $current_weight = $body_weight;
        $body_fat = 0;
    }
} catch (Exception $e) {
    error_log("Error fetching body metrics: " . $e->getMessage());
    $current_weight = $body_weight;
    $body_fat = 0;
}

$recent_templates = [];
try {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'workout_templates'")->num_rows > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, category FROM workout_templates 
                                      WHERE user_id = ? 
                                      ORDER BY last_used_at DESC, updated_at DESC 
                                      LIMIT 2");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($template = mysqli_fetch_assoc($result)) {
            $recent_templates[] = $template;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching recent templates: " . $e->getMessage());
}

$current_month = date('m');
$current_year = date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = $current_month;
}

$days_in_month = date('t', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$month_workouts = [];

try {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'scheduled_workouts'")->num_rows > 0) {
        $stmt = mysqli_prepare($conn, "SELECT day, template_id, template_name, workout_type 
                                     FROM scheduled_workouts 
                                     WHERE user_id = ? AND month = ? AND year = ?");
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $selected_month, $selected_year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($workout = mysqli_fetch_assoc($result)) {
            $month_workouts[$workout['day']] = $workout;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching month workouts: " . $e->getMessage());
}

$workout_splits = [];
try {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'workout_splits'")->num_rows > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, data FROM workout_splits WHERE user_id = ? ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($split = mysqli_fetch_assoc($result)) {
            $workout_splits[] = $split;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching workout splits: " . $e->getMessage());
}

$today = date('j');
$today_workout = $month_workouts[$today] ?? null;

$today_exercises = [];
if ($today_workout && !empty($today_workout['template_id'])) {
    try {
        $stmt = mysqli_prepare($conn, "SELECT e.name as exercise_name FROM workout_template_exercises wte
                                     JOIN exercises e ON wte.exercise_id = e.id
                                     WHERE wte.workout_template_id = ?
                                     ORDER BY wte.position");
        mysqli_stmt_bind_param($stmt, "i", $today_workout['template_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($exercise = mysqli_fetch_assoc($result)) {
            $today_exercises[] = $exercise['exercise_name'];
        }
    } catch (Exception $e) {
        error_log("Error fetching today's exercises: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <script>
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }
        
        function startWorkout(button) {
            try {
                console.log('Starting workout with button:', button);
                const templateId = button.getAttribute('data-template-id');
                console.log('Template ID:', templateId);
                if (templateId) {
                    button.innerText = 'Starting...';
                    button.disabled = true;
                    
                    button.classList.add('loading');
                    
                    const targetPage = isMobile() ? 'mobile-workout.php' : 'workout.php';
                    const workoutUrl = `${targetPage}?template_id=${templateId}&start_workout=1&auto_start=1&start_step=2`;
                    
                    console.log('Redirecting to:', workoutUrl);
                    window.location.href = workoutUrl;
                } else {
                    console.error('No template ID found on button');
                    alert('Error: No workout template selected. Please select a workout template first.');
                }
            } catch (error) {
                console.error('Error starting workout:', error);
                alert('An error occurred while starting the workout.');
            }
        }
    </script>
    <style>
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

        .action-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-button.primary {
            background: var(--gradient-blue);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .action-button.primary:hover {
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            transform: translateY(-3px);
        }

        .action-button.secondary {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
        }

        .action-button.secondary:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr) 320px;
            gap: 25px;
        }

        .left-column > div {
            margin-bottom: 25px;
        }

        .panel {
            background-color: var(--dark-card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .panel-action {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .panel-action:hover {
            text-decoration: underline;
        }

        .body-metrics-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-label {
            color: var(--gray-light);
        }

        .metric-value {
            font-weight: 600;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .template-card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .template-card:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        .template-label {
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .calendar-nav {
            display: flex;
            gap: 15px;
        }

        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.05);
            border: none;
            color: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .calendar-nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 0.8rem;
            color: var(--gray-light);
            margin-bottom: 10px;
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.03);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 5px;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .calendar-day:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .calendar-day-number {
            font-weight: 600;
            font-size: 0.9rem;
            position: absolute;
            top: 5px;
            left: 8px;
        }

        .calendar-day-content {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            text-align: center;
            color: white;
        }

        .day-has-workout {
            color: white;
        }

        .workout-type {
            margin-top: 5px;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .push-day {
            background-color: rgba(230, 22, 22, 0.2);
            color: #ff5c5c;
        }

        .pull-day {
            background-color: rgba(67, 97, 238, 0.2);
            color: #7b8cff;
        }

        .leg-day {
            background-color: rgba(111, 66, 193, 0.2);
            color: #b69fff;
        }

        .rest-day {
            background-color: rgba(255, 255, 255, 0.1);
            color: #cccccc;
        }

        .day-icon {
            margin-top: 5px;
            font-size: 1rem;
        }

        .today-workout {
            margin-bottom: 25px;
        }

        .workout-time {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 5px;
        }

        .workout-exercises {
            list-style: none;
            margin-top: 15px;
            max-height: 250px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .workout-exercises::-webkit-scrollbar {
            width: 6px;
        }

        .workout-exercises::-webkit-scrollbar-track {
            background: transparent;
        }

        .workout-exercises::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .exercise-item {
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .exercise-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .start-workout-btn {
            display: block;
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: var(--gradient-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .start-workout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--dark-card);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: var(--gray-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: white;
        }

        .modal-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-select, .form-input {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
        }

        .form-select:focus, .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }

        .form-button {
            padding: 12px 20px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .form-button.primary {
            background: var(--gradient-blue);
            color: white;
        }

        .form-button.secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .right-column {
                grid-column: span 2;
            }
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                width: 100%;
                padding: 20px;
            }
            
            .right-column {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .templates-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .calendar-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .calendar-grid {
                gap: 5px;
            }
            
            .calendar-day {
                padding: 5px 3px;
            }
            
            .workout-type {
                padding: 2px 4px;
                font-size: 0.6rem;
            }
            
            .calendar-day-number {
                font-size: 0.8rem;
            }
        }

        .calendar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .past-day {
            opacity: 0.5;
            cursor: default;
        }
        
        .today-marker {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            border: 2px solid var(--primary);
            border-radius: 10px;
            pointer-events: none;
        }
        
        .rest-day-message {
            text-align: center;
            padding: 30px 0;
        }
        
        .rest-day-message i {
            font-size: 2.5rem;
            color: var(--gray-light);
            margin-bottom: 15px;
        }
        
        .rest-day-message h3 {
            margin-bottom: 10px;
        }
        
        .rest-day-message p {
            color: var(--gray-light);
        }
        
        .weekly-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin: 20px 0;
            max-width: 100%;
            overflow-x: auto;
        }
        
        .week-day {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .week-day-header {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 8px;
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .week-day-content {
            padding: 10px;
        }
        
        .split-modal {
            max-width: 900px;
            width: 90%;
        }
        
        .split-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .saved-splits {
            flex: 1;
        }

        .mobile-app-view {
                display: none;
            }
        
        @media (max-width: 1200px) {
            .weekly-calendar {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .weekly-calendar {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .split-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .saved-splits {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .weekly-calendar {
                grid-template-columns: 1fr;
            }
        }
        
        .templates-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .template-label {
            font-size: 0.9rem;
            padding: 15px 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .template-label.push-day {
            color: #ff5c5c;
        }
        
        .template-label.pull-day {
            color: #7b8cff;
        }
        
        .template-label.leg-day {
            color: #b69fff;
        }

        @media (max-width: 767px) {
            body {
                background-color: #1c1f2a;
                margin: 0;
                padding: 0;
                overflow-x: hidden;
            }
            
            .sidebar {
                display: none !important;
            }
            
            .dashboard, .main-content {
                padding: 0;
                width: 100%;
            }
            
            .page-title, .page-actions, .page-header {
                display: none;
            }
            
            .dashboard-grid {
                display: none;
            }
            
            .mobile-app-view {
                display: block;
            }
            
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 15px 0 20px;
            }
            
            .mobile-header-title {
                font-size: 1.8rem;
                font-weight: 700;
                display: flex;
                flex-direction: column;
            }
            
            .mobile-header-date {
                color: #e63e3e;
                font-size: 1rem;
                font-weight: normal;
            }
            
            .mobile-card {
                background-color: #21242e;
                border-radius: 15px;
                margin: 15px;
                padding: 20px;
            }
            
            .mobile-card-title {
                margin-bottom: 15px;
                font-size: 1.2rem;
                font-weight: 600;
            }
            
            .mobile-scheduled-workout {
                margin-bottom: 5px;
            }
            
            .mobile-workout-meta {
                display: flex;
                gap: 5px;
                color: #7f8489;
                font-size: 0.85rem;
                margin-bottom: 10px;
            }
            
            .mobile-start-btn {
                display: block;
                width: 100%;
                background-color: #e63e3e;
                color: white;
                border: none;
                border-radius: 10px;
                padding: 15px;
                font-weight: 600;
                font-size: 1rem;
                margin-top: 20px;
                cursor: pointer;
            }
            
            .mobile-weight-section {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .mobile-weight-value {
                font-size: 1.6rem;
                font-weight: 700;
            }
            
            .mobile-weight-unit {
                color: #7f8489;
            }
            
            .mobile-update-btn {
                color: #e63e3e;
                background: none;
                border: none;
                font-size: 0.9rem;
                padding: 5px;
                cursor: pointer;
            }
            
            .mobile-week-selector {
                display: flex;
                justify-content: space-between;
                padding: 0 15px;
                margin: 10px 0;
            }
            
            .mobile-day-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                background: none;
                border: none;
                color: white;
                width: 48px;
                padding: 10px 0;
                border-radius: 10px;
                cursor: pointer;
                position: relative;
            }
            
            .mobile-day-weekday {
                text-transform: uppercase;
                font-size: 0.8rem;
                font-weight: 600;
                margin-bottom: 8px;
            }
            
            .mobile-day-date {
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .mobile-day-btn.active {
                background-color: #21242e;
            }
            
            .workout-scheduled {
                background-color: #21242e;
            }

            .mobile-day-btn.workout-scheduled::after {
                content: '';
                position: absolute;
                bottom: 5px;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background-color: #e63e3e;
            }
            
            .mobile-day-btn.active.workout-scheduled{
                background-color: #e63e3e;
            }

            .mobile-workout-card {
                display: flex;
                background-color: #21242e;
                border-radius: 15px;
                margin: 15px;
                overflow: hidden;
            }
            
            .mobile-button-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                padding: 15px;
            }
            
            .mobile-feature-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background-color: #21242e;
                border-radius: 15px;
                padding: 10px 15px 20px;
                text-decoration: none;
                color: white;
                text-align: center;
                transition: transform 0.2s ease, background-color 0.2s ease;
            }
            
            .mobile-feature-btn:active {
                transform: scale(0.98);
                background-color: #2a2d38;
            }
            
            .mobile-feature-icon {
                font-size: 1.8rem;
                margin-bottom: 12px;
                color: #e63e3e;
            }
            
            .mobile-feature-label {
                font-weight: 600;
                font-size: 0.9rem;
            }
            
            .mobile-bottom-nav {
                display: flex;
                justify-content: space-around;
                background-color: #21242e;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 15px 0;
                box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
            }
            
            .mobile-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                color: #7f8489;
                text-decoration: none;
            }
            
            .mobile-nav-item.active {
                color: white;
            }
            
            .mobile-nav-icon {
                font-size: 1.2rem;
                margin-bottom: 5px;
            }
            
            .mobile-nav-label {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 767px) {
            .main-content {
                padding-bottom: 80px;
            }
        }

        .loading {
            position: relative;
            opacity: 0.8;
        }
        .loading:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .mobile-split-days {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 65vh;
            overflow-y: auto;
            margin: 15px 0;
            padding-right: 5px;
        }
        
        .mobile-split-day {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
        }
        
        .mobile-split-day h4 {
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .mobile-split-template, .mobile-split-custom {
            margin-top: 10px;
        }
        
        .mobile-split-days::-webkit-scrollbar {
            width: 5px;
        }
        
        .mobile-split-days::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .mobile-split-days::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .modal.mobile-slide-up {
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
        }
        
        .modal.mobile-slide-up.show {
            transform: translateY(0);
        }
        
        .mobile-week-selector {
            padding: 0 5px;
            margin: 15px 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .mobile-week-selector::-webkit-scrollbar {
            display: none;
        }
        
        .mobile-week-action {
            background-color: #e63e3e;
            color: white;
            border: none;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            position: fixed;
            bottom: 80px;
            right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            z-index: 100;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
        }
        
        .mobile-week-action:active {
            transform: scale(0.95);
            background-color: #d33636;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 767px) {
            .modal {
                background-color: rgba(0, 0, 0, 0.9);
            }
            
            .modal-content {
                width: 92% !important;
                max-width: 92% !important;
                max-height: 80vh !important;
                overflow-y: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <main class="main-content">
            <?php 
            if ($today_workout && !empty($today_workout['template_id'])) {
                echo "<!-- Template ID available: " . $today_workout['template_id'] . " -->";
            } else {
                echo "<!-- Template ID not available -->";
            }
            ?>
            <div class="page-header">
                <h1 class="page-title">Fitness Dashboard</h1>
            </div>
            
            <div class="dashboard-grid">
                <div class="left-column">
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">Body Metrics</h3>
                        </div>
                        <div class="body-metrics-list">
                            <div class="metric-item">
                                <span class="metric-label">Weight</span>
                                <span class="metric-value"><?= $current_weight ?> kg</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">Body Fat</span>
                                <span class="metric-value"><?= $body_fat ?>%</span>
                            </div>
                        </div>
                        <a href="body-measurements.php" class="start-workout-btn">
                            Update Metrics
                        </a>
                    </div>
                    
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">Recent templates</h3>
                        </div>
                        <div class="templates-grid">
                            <?php if (!empty($recent_templates)): ?>
                                <?php foreach ($recent_templates as $template): ?>
                                    <div class="template-card" data-template-id="<?= $template['id'] ?>">
                                        <?php 
                                            $cssClass = '';
                                            
                                            if (strtolower($template['category']) === 'push') {
                                                $cssClass = 'push-day';
                                            } elseif (strtolower($template['category']) === 'pull') {
                                                $cssClass = 'pull-day';
                                            } elseif (strtolower($template['category']) === 'leg') {
                                                $cssClass = 'leg-day';
                                            }
                                        ?>
                                        <div class="template-label <?= $cssClass ?>"><?= htmlspecialchars($template['name']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="template-card">
                                    <div class="template-label push-day">Push Workout</div>
                                </div>
                                <div class="template-card">
                                    <div class="template-label pull-day">Pull Workout</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="workout-templates.php" class="start-workout-btn">
                            Update templates
                        </a>
                    </div>
                </div>
                
                <div class="middle-column">
                    <div class="panel">
                        <div class="calendar-header">
                            <h2 class="calendar-title"><?= $month_name ?> <?= $selected_year ?></h2>
                            <div class="calendar-actions">
                                <button class="action-button secondary" id="create-split-btn">
                                    <i class="fas fa-calendar-week"></i> Create Split
                                </button>
                                <div class="calendar-nav">
                                    <button class="calendar-nav-btn" id="prev-month">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="calendar-nav-btn" id="next-month">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                            $firstDayOfMonth = mktime(0, 0, 0, $selected_month, 1, $selected_year);
                            $numberDays = date('t', $firstDayOfMonth);
                            $dateComponents = getdate($firstDayOfMonth);
                            $monthName = $dateComponents['month'];
                            $dayOfWeek = $dateComponents['wday'];
                            
                            $currentDay = 0;
                            $isCurrentMonth = ($selected_month == $current_month && $selected_year == $current_year);
                            if ($isCurrentMonth) {
                                $currentDay = date('j');
                            }
                            
                            $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            $calendar = "<div class='calendar-grid'>";
                            
                            foreach ($weekdays as $day) {
                                $calendar .= "<div class='calendar-weekday'>{$day}</div>";
                            }
                            
                            if ($dayOfWeek > 0) {
                                for ($i = 0; $i < $dayOfWeek; $i++) {
                                    $calendar .= "<div class='calendar-day' style='visibility: hidden'></div>";
                                }
                            }
                            
                            for ($i = 1; $i <= $numberDays; $i++) {
                                $id = "day_" . $i;
                                $isPastDay = $isCurrentMonth && $i < $currentDay;
                                $dayClass = $isPastDay ? 'calendar-day past-day' : 'calendar-day';
                                $calendar .= "<div class='{$dayClass}' id='{$id}' data-day='{$i}' " . ($isPastDay ? 'data-past="true"' : '') . ">";
                                $calendar .= "<div class='calendar-day-number'>{$i}</div>";
                                
                                if ($isCurrentMonth && $i == $currentDay) {
                                    $calendar .= "<div class='today-marker'></div>";
                                }
                                
                                if (isset($month_workouts[$i])) {
                                    $workout = $month_workouts[$i];
                                    $type = strtolower($workout['workout_type'] ?? '');
                                    
                                    $typeClass = '';
                                    
                                    if ($type === 'push') {
                                        $typeClass = 'push-day';
                                    } elseif ($type === 'pull') {
                                        $typeClass = 'pull-day';
                                    } elseif ($type === 'leg') {
                                        $typeClass = 'leg-day';
                                    } elseif ($type === 'rest') {
                                        $typeClass = 'rest-day';
                                    }
                                    
                                    $calendar .= "<div class='calendar-day-content day-has-workout'>";
                                    $calendar .= "<div class='workout-type {$typeClass}'>{$type}</div>";
                                    $calendar .= "</div>";
                                } else {
                                    $calendar .= "<div class='calendar-day-content'>";
                                    if (!$isPastDay) {
                                        $calendar .= "<i class='fas fa-plus-circle' style='opacity: 0.5; font-size: 0.8rem;'></i>";
                                    }
                                    $calendar .= "</div>";
                                }
                                
                                $calendar .= "</div>";
                            }
                            
                            $calendar .= "</div>";
                            echo $calendar;
                        ?>
                    </div>
                </div>
                
                <div class="right-column">
                    <div class="panel today-workout">
                        <div class="panel-header">
                            <h3 class="panel-title">Today's Workout</h3>
                        </div>
                        
                        <?php if ($today_workout): ?>
                            <?php if ($today_workout['workout_type'] === 'rest'): ?>
                                <div class="rest-day-message">
                                    <i class="fas fa-bed"></i>
                                    <h3>Rest Day</h3>
                                    <p>Take time to recover and recharge.</p>
                                </div>
                            <?php else: ?>
                                <div class="workout-time">
                                    <i class="far fa-clock"></i> 45 minutes • <?= count($today_exercises) ?> exercises
                                </div>
                                <h4><?= htmlspecialchars($today_workout['template_name']) ?></h4>
                                
                                <?php if (!empty($today_exercises)): ?>
                                <?php else: ?>
                                    <p style="opacity: 0.7; margin-top: 20px;">No exercises found for this workout.</p>
                                <?php endif; ?>
                                
                                <button class="start-workout-btn" id="startWorkoutBtn" data-template-id="<?= htmlspecialchars($today_workout['template_id']) ?>" onclick="return startWorkout(this)">
                                    Start Workout
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="day-has-workout" style="text-align: center; padding: 30px 0;">
                                <i class="fas fa-calendar-plus" style="font-size: 2rem; opacity: 0.5; margin-bottom: 15px;"></i>
                                <p>No workout scheduled for today</p>
                                <button class="start-workout-btn" style="margin-top: 20px;" id="plan-today-btn">
                                    Plan Today's Workout
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($today_exercises)): ?>
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title"><?= htmlspecialchars($today_workout['workout_type']) ?> day</h3>
                            <span class="workout-type <?= strtolower($today_workout['workout_type']) ?>-day"><?= count($today_exercises) ?> exercises</span>
                        </div>
                        
                        <ul class="workout-exercises">
                            <?php foreach ($today_exercises as $exercise): ?>
                                <li class="exercise-item"><?= htmlspecialchars($exercise) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        
    
    
            <div class="mobile-app-view">
                <div class="mobile-header">
                    <div class="mobile-header-title">
                        Today's Focus
                        <span class="mobile-header-date"><?= date('F j, Y') ?></span>
                    </div>
                </div>
                
                <div class="mobile-card">
                    <?php if ($today_workout && $today_workout['workout_type'] !== 'rest'): ?>
                        <h3 class="mobile-card-title">Scheduled: <?= htmlspecialchars($today_workout['template_name']) ?></h3>
                        <div class="mobile-workout-meta">
                            <span>45 min</span> • <span><?= count($today_exercises) ?> exercises</span>
                        </div>
                        <button class="mobile-start-btn" id="mobileStartWorkoutBtn" data-template-id="<?= htmlspecialchars($today_workout['template_id']) ?>" onclick="return startWorkout(this)">Start Workout</button>
                    <?php elseif ($today_workout && $today_workout['workout_type'] === 'rest'): ?>
                        <h3 class="mobile-card-title">Scheduled: Rest Day</h3>
                        <div class="mobile-workout-meta">
                            <span>Take time to recover and recharge</span>
                        </div>
                        <button class="mobile-start-btn" style="background-color: #555;">Rest Day</button>
                    <?php else: ?>
                        <h3 class="mobile-card-title">No Workout Scheduled</h3>
                        <div class="mobile-workout-meta">
                            <span>Schedule a workout for today</span>
                        </div>
                        <button class="mobile-start-btn" id="mobilePlanWorkoutBtn">Schedule Workout</button>
                    <?php endif; ?>
                </div>
                
                <div class="mobile-card">
                    <h3 class="mobile-card-title">Today's Weight</h3>
                    <div class="mobile-weight-section">
                        <div>
                            <span class="mobile-weight-value"><?= $current_weight ?></span>
                            <span class="mobile-weight-unit">kg</span>
                        </div>
                        <button class="mobile-update-btn">Update</button>
                    </div>
                </div>
                
                <div class="mobile-week-selector">
                    <?php
                        $today = new DateTime();
                        $weekday = $today->format('w');
                        $offset = $weekday;
                        
                        $days = [];
                        for ($i = 0; $i < 7; $i++) {
                            $day = clone $today;
                            $day->modify('-' . $offset . ' day');
                            $day->modify('+' . $i . ' day');
                            $days[] = $day;
                        }
                        
                        foreach ($days as $index => $day) {
                            $dayNum = $day->format('j');
                            $weekdayShort = $day->format('D');
                            $isToday = $day->format('Y-m-d') === $today->format('Y-m-d');
                            $dayClass = 'mobile-day-btn';
                            if ($isToday) {
                                $dayClass .= ' active';
                            }
                            
                            $hasWorkout = false;
                            if ($isToday && $today_workout) {
                                $hasWorkout = true;
                                $dayClass .= ' workout-scheduled';
                            } elseif (isset($month_workouts[$dayNum]) && $day->format('m') == $selected_month) {
                                $hasWorkout = true;
                                $dayClass .= ' workout-scheduled';
                            }
                            
                            echo "<button class=\"{$dayClass}\" data-date=\"{$day->format('Y-m-d')}\">";
                            echo "<span class=\"mobile-day-weekday\">{$weekdayShort}</span>";
                            echo "<span class=\"mobile-day-date\">{$dayNum}</span>";
                            echo "</button>";
                        }
                    ?>
                </div>
                
                <button type="button" class="mobile-week-action" id="mobileWeekAction" onclick="document.getElementById('mobileSplitModal').style.display='flex'">
                    <i class="fas fa-calendar-week"></i>
                </button>
                
                <div class="mobile-button-grid">
                    <a href="workout.php" class="mobile-feature-btn">
                        <div class="mobile-feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="mobile-feature-label">Quick Workout</div>
                    </a>
                    <a href="workout-history.php" class="mobile-feature-btn">
                        <div class="mobile-feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="mobile-feature-label">Workout History</div>
                    </a>
                    <a href="workout-templates.php" class="mobile-feature-btn">
                        <div class="mobile-feature-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="mobile-feature-label">Edit Templates</div>
                    </a>
                    <a href="workout-analytics.php" class="mobile-feature-btn">
                        <div class="mobile-feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="mobile-feature-label">Progress</div>
                    </a>
                </div>
                
                <form id="mobileWorkoutForm" action="mobile-workout.php" method="POST" style="display: none;">
                <form id="mobileWorkoutForm" action="workout.php" method="POST" style="display: none;">
                    <input type="hidden" name="template_id" id="mobileTemplateIdInput" value="<?= $today_workout['template_id'] ?>">
                    <input type="hidden" name="start_workout" value="1">
                    <input type="hidden" name="auto_start" value="1">
                    <input type="hidden" name="start_step" value="2">
                </form>
                
                <div class="mobile-bottom-nav">
                    <a href="#" class="mobile-nav-item active">
                        <div class="mobile-nav-icon"><i class="fas fa-home"></i></div>
                        <div class="mobile-nav-label">Home</div>
                    </a>
                    <a href="#" class="mobile-nav-item">
                        <div class="mobile-nav-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="mobile-nav-label">Templates</div>
                    </a>
                    <a href="#" class="mobile-nav-item">
                        <div class="mobile-nav-icon"><i class="fas fa-history"></i></div>
                        <div class="mobile-nav-label">History</div>
                    </a>
                    <a href="#" class="mobile-nav-item">
                        <div class="mobile-nav-icon"><i class="fas fa-user"></i></div>
                        <div class="mobile-nav-label">Profile</div>
                    </a>
                </div>
            </div>
            
            <div class="modal" id="mobileDayModal">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <h3 class="modal-title">Edit workout for <span id="mobileSelectedDate"></span></h3>
                    
                    <form id="mobileWorkoutPlanForm">
                        <input type="hidden" id="mobileSelectedDay" name="day">
                        <input type="hidden" id="mobileSelectedMonth" name="month" value="<?= $selected_month ?>">
                        <input type="hidden" id="mobileSelectedYear" name="year" value="<?= $selected_year ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="mobileWorkoutType">Workout Type</label>
                            <select class="form-select" id="mobileWorkoutType" name="workoutType">
                                <option value="">Select workout type</option>
                                <option value="push">Push Day</option>
                                <option value="pull">Pull Day</option>
                                <option value="leg">Leg Day</option>
                                <option value="rest">Rest Day</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="mobileTemplateSelectGroup" style="display: none;">
                            <label class="form-label" for="mobileTemplateSelect">Select Template</label>
                            <select class="form-select" id="mobileTemplateSelect" name="templateId">
                                <option value="">Select a template</option>
                                <?php 
                                try {
                                    $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    
                                    while ($template = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$template['id']}'>{$template['name']}</option>";
                                    }
                                } catch (Exception $e) {
                                    error_log("Error fetching templates: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="mobileCustomNameGroup" style="display: none;">
                            <label class="form-label" for="mobileCustomName">Custom Workout Name</label>
                            <input type="text" class="form-input" id="mobileCustomName" name="customName" placeholder="Enter workout name">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="form-button secondary" id="mobileEditCancel">Cancel</button>
                            <button type="submit" class="form-button primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal" id="mobileSplitModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; overflow: auto;">
                <div class="modal-content" style="background-color: #21242e; width: 90%; max-width: 500px; border-radius: 15px; padding: 20px; position: relative; max-height: 80vh; overflow-y: auto;">
                    <span class="modal-close" onclick="document.getElementById('mobileSplitModal').style.display='none'" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer;">&times;</span>
                    <h3 class="modal-title">Weekly Split Plan</h3>
                    
                    <div class="form-group">
                        <select class="form-select" id="mobileSavedSplits">
                            <option value="">Create new split</option>
                            <?php foreach ($workout_splits as $split): ?>
                                <option value="<?= $split['id'] ?>"><?= htmlspecialchars($split['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <form id="mobileSplitForm">
                        <div class="form-group">
                            <label class="form-label" for="mobileSplitName">Split Name</label>
                            <input type="text" class="form-input" id="mobileSplitName" name="splitName" placeholder="e.g., Bro Split, PPL, Upper/Lower" required>
                        </div>
                        
                        <div class="mobile-split-days">
                            <?php
                                $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($weekdays as $index => $day) {
                                    echo "<div class='mobile-split-day'>";
                                    echo "<h4>{$day}</h4>";
                                    
                                    echo "<div class='form-group'>";
                                    echo "<select class='form-select mobile-split-type' id='mobile_day_{$index}' name='days[{$index}][type]'>";
                                    echo "<option value=''>Select type</option>";
                                    echo "<option value='push'>Push</option>";
                                    echo "<option value='pull'>Pull</option>";
                                    echo "<option value='leg'>Leg</option>";
                                    echo "<option value='rest'>Rest</option>";
                                    echo "<option value='custom'>Custom</option>";
                                    echo "</select>";
                                    echo "</div>";
                                    
                                    echo "<div class='form-group mobile-split-template' id='mobile_template_group_{$index}' style='display:none;'>";
                                    echo "<select class='form-select' id='mobile_template_{$index}' name='days[{$index}][template_id]'>";
                                    echo "<option value=''>Select template</option>";
                                    
                                    try {
                                        $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        
                                        while ($template = mysqli_fetch_assoc($result)) {
                                            echo "<option value='{$template['id']}'>{$template['name']}</option>";
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error fetching templates: " . $e->getMessage());
                                    }
                                    
                                    echo "</select>";
                                    echo "</div>";
                                    
                                    echo "<div class='form-group mobile-split-custom' id='mobile_custom_group_{$index}' style='display:none;'>";
                                    echo "<input type='text' class='form-input' id='mobile_custom_{$index}' name='days[{$index}][custom_name]' placeholder='Custom name'>";
                                    echo "</div>";
                                    
                                    echo "</div>";
                                }
                            ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="form-button secondary" id="mobileSplitCancel">Cancel</button>
                            <button type="submit" class="form-button primary" id="mobileSplitSave">Save Split</button>
                            <button type="button" class="form-button primary" id="mobileSplitApply">Apply to Calendar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <div class="modal" id="dayModal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3 class="modal-title">Plan workout for <span id="selectedDate"></span></h3>
            
            <form id="workoutPlanForm">
                <input type="hidden" id="selectedDay" name="day">
                
                <div class="form-group">
                    <label class="form-label" for="workoutType">Workout Type</label>
                    <select class="form-select" id="workoutType" name="workoutType">
                        <option value="">Select workout type</option>
                        <option value="push">Push Day</option>
                        <option value="pull">Pull Day</option>
                        <option value="leg">Leg Day</option>
                        <option value="rest">Rest Day</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                
                <div class="form-group" id="templateSelectGroup" style="display: none;">
                    <label class="form-label" for="templateSelect">Select Template</label>
                    <select class="form-select" id="templateSelect" name="templateId">
                        <option value="">Select a template</option>
                        <?php 
                        try {
                            $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            while ($template = mysqli_fetch_assoc($result)) {
                                echo "<option value='{$template['id']}'>{$template['name']}</option>";
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching templates: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group" id="customNameGroup" style="display: none;">
                    <label class="form-label" for="customName">Custom Workout Name</label>
                    <input type="text" class="form-input" id="customName" name="customName" placeholder="Enter workout name">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="form-button secondary" id="cancelPlan">Cancel</button>
                    <button type="submit" class="form-button primary">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="splitModal">
        <div class="modal-content split-modal">
            <span class="modal-close">&times;</span>
            <h3 class="modal-title">Create Weekly Split</h3>
            
            <form id="splitPlanForm">
                <div class="form-group">
                    <label class="form-label" for="splitName">Split Name</label>
                    <input type="text" class="form-input" id="splitName" name="splitName" placeholder="e.g., Bro Split, PPL, Upper/Lower" required>
                </div>
                
                <div class="weekly-calendar">
                    <?php
                        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($weekdays as $index => $day) {
                            echo "<div class='week-day'>";
                            echo "<div class='week-day-header'>{$day}</div>";
                            echo "<div class='week-day-content'>";
                            echo "<select class='form-select week-day-select' id='day_{$index}' name='days[{$index}][type]'>";
                            echo "<option value=''>Select type</option>";
                            echo "<option value='push'>Push</option>";
                            echo "<option value='pull'>Pull</option>";
                            echo "<option value='leg'>Leg</option>";
                            echo "<option value='rest'>Rest</option>";
                            echo "<option value='custom'>Custom</option>";
                            echo "</select>";
                            
                            echo "<select class='form-select week-day-template' id='template_{$index}' name='days[{$index}][template_id]' style='display:none; margin-top:10px;'>";
                            echo "<option value=''>Select template</option>";
                            
                            try {
                                $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                
                                while ($template = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$template['id']}'>{$template['name']}</option>";
                                }
                            } catch (Exception $e) {
                                error_log("Error fetching templates: " . $e->getMessage());
                            }
                            
                            echo "</select>";
                            
                            echo "<input type='text' class='form-input week-day-custom' id='custom_{$index}' name='days[{$index}][custom_name]' placeholder='Custom name' style='display:none; margin-top:10px;'>";
                            echo "</div></div>";
                        }
                    ?>
                </div>
                
                <div class="split-actions">
                    <?php if (!empty($workout_splits)): ?>
                    <div class="saved-splits">
                        <label class="form-label">Load saved split:</label>
                        <select class="form-select" id="savedSplits">
                            <option value="">Select a saved split</option>
                            <?php foreach ($workout_splits as $split): ?>
                                <option value="<?= $split['id'] ?>"><?= htmlspecialchars($split['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="button" class="form-button secondary" id="cancelSplit">Cancel</button>
                        <button type="submit" class="form-button primary">Apply to Month</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        console.log("Script starting execution");
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM fully loaded");
            
            const elementsToCheck = [
                'dayModal', 'splitModal', 'mobileDayModal', 'mobileSplitModal', 
                'mobileWeekAction', 'mobileSplitForm', 'mobileSavedSplits'
            ];
            
            elementsToCheck.forEach(id => {
                const element = document.getElementById(id);
                console.log(`Element ${id} exists:`, !!element);
            });
            
            console.log("Desktop button exists:", !!document.getElementById('startWorkoutBtn'));
            console.log("Mobile button exists:", !!document.getElementById('mobileStartWorkoutBtn'));
            
            const dayModal = document.getElementById('dayModal');
            const splitModal = document.getElementById('splitModal');
            const mobileDayModal = document.getElementById('mobileDayModal');
            const mobileSplitModal = document.getElementById('mobileSplitModal');
            const selectedDate = document.getElementById('selectedDate');
            const mobileSelectedDate = document.getElementById('mobileSelectedDate');
            const selectedDayInput = document.getElementById('selectedDay');
            const mobileSelectedDayInput = document.getElementById('mobileSelectedDay');
            const modalClose = document.querySelectorAll('.modal-close');
            const cancelPlan = document.getElementById('cancelPlan');
            const mobileEditCancel = document.getElementById('mobileEditCancel');
            const mobileSplitCancel = document.getElementById('mobileSplitCancel');
            const cancelSplit = document.getElementById('cancelSplit');
            const workoutTypeSelect = document.getElementById('workoutType');
            const mobileWorkoutTypeSelect = document.getElementById('mobileWorkoutType');
            const templateSelectGroup = document.getElementById('templateSelectGroup');
            const mobileTemplateSelectGroup = document.getElementById('mobileTemplateSelectGroup');
            const customNameGroup = document.getElementById('customNameGroup');
            const mobileCustomNameGroup = document.getElementById('mobileCustomNameGroup');
            const calendarDays = document.querySelectorAll('.calendar-day[data-day]');
            const mobileDayButtons = document.querySelectorAll('.mobile-day-btn');
            const workoutPlanForm = document.getElementById('workoutPlanForm');
            const mobileWorkoutPlanForm = document.getElementById('mobileWorkoutPlanForm');
            const splitPlanForm = document.getElementById('splitPlanForm');
            const mobileSplitForm = document.getElementById('mobileSplitForm');
            const newWorkoutBtn = document.getElementById('new-workout-btn');
            const createSplitBtn = document.getElementById('create-split-btn');
            const mobileWeekAction = document.getElementById('mobileWeekAction');
            const prevMonthBtn = document.getElementById('prev-month');
            const nextMonthBtn = document.getElementById('next-month');
            const planTodayBtn = document.getElementById('plan-today-btn');
            const savedSplitsSelect = document.getElementById('savedSplits');
            const mobileSavedSplits = document.getElementById('mobileSavedSplits');
            const weekDaySelects = document.querySelectorAll('.week-day-select');
            const mobileSplitTypeSelects = document.querySelectorAll('.mobile-split-type');
            const mobileSplitSave = document.getElementById('mobileSplitSave');
            const mobileSplitApply = document.getElementById('mobileSplitApply');
            
            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', function() {
                    navigateMonth(-1);
                });
            }
            
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', function() {
                    navigateMonth(1);
                });
            }
            
            function navigateMonth(direction) {
                let currentMonth = <?= $selected_month ?>;
                let currentYear = <?= $selected_year ?>;
                
                currentMonth += direction;
                
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                } else if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                
                window.location.href = `profile.php?month=${currentMonth}&year=${currentYear}`;
            }
            
            if (mobileDayButtons) {
                mobileDayButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const dateStr = this.getAttribute('data-date');
                        if (dateStr) {
                            const date = new Date(dateStr);
                            const formattedDate = date.toLocaleDateString('en-US', {
                                weekday: 'long',
                                month: 'long',
                                day: 'numeric'
                            });
                            
                            mobileSelectedDate.textContent = formattedDate;
                            mobileSelectedDayInput.value = date.getDate();
                            document.getElementById('mobileSelectedMonth').value = date.getMonth() + 1;
                            document.getElementById('mobileSelectedYear').value = date.getFullYear();
                            
                            mobileWorkoutTypeSelect.value = '';
                            mobileTemplateSelectGroup.style.display = 'none';
                            mobileCustomNameGroup.style.display = 'none';
                            
                            mobileDayModal.style.display = 'flex';
                        }
                    });
                });
            }

            calendarDays.forEach(day => {
                if (!day.hasAttribute('data-past')) {
                    day.addEventListener('click', function() {
                        const dayNum = this.getAttribute('data-day');
                        const monthName = '<?= $monthName ?>';
                        selectedDate.textContent = `${monthName} ${dayNum}, <?= $selected_year ?>`;
                        selectedDayInput.value = dayNum;
                        
                        workoutTypeSelect.value = '';
                        templateSelectGroup.style.display = 'none';
                        customNameGroup.style.display = 'none';
                        
                        dayModal.style.display = 'flex';
                    });
                }
            });
            
            if (planTodayBtn) {
                planTodayBtn.addEventListener('click', function() {
                    const dayNum = <?= $today ?>;
                    const monthName = '<?= $monthName ?>';
                    selectedDate.textContent = `${monthName} ${dayNum}, <?= $selected_year ?>`;
                    selectedDayInput.value = dayNum;
                    
                    workoutTypeSelect.value = '';
                    templateSelectGroup.style.display = 'none';
                    customNameGroup.style.display = 'none';
                    
                    dayModal.style.display = 'flex';
                });
            }
            
            if (createSplitBtn) {
                createSplitBtn.addEventListener('click', function() {
                    splitModal.style.display = 'flex';
                });
            }
            
            modalClose.forEach(close => {
                close.addEventListener('click', function() {
                    dayModal.style.display = 'none';
                    splitModal.style.display = 'none';
                    mobileDayModal.style.display = 'none';
                    mobileSplitModal.style.display = 'none';
                });
            });
            
            if (cancelPlan) {
                cancelPlan.addEventListener('click', function() {
                    dayModal.style.display = 'none';
                });
            }
            
            if (mobileEditCancel) {
                mobileEditCancel.addEventListener('click', function() {
                    mobileDayModal.style.display = 'none';
                });
            }
            
            if (mobileSplitCancel) {
                mobileSplitCancel.addEventListener('click', function() {
                    mobileSplitModal.style.display = 'none';
                });
            }
            
            if (cancelSplit) {
                cancelSplit.addEventListener('click', function() {
                    splitModal.style.display = 'none';
                });
            }
            
            window.addEventListener('click', function(event) {
                if (event.target === dayModal) {
                    dayModal.style.display = 'none';
                }
                if (event.target === splitModal) {
                    splitModal.style.display = 'none';
                }
                if (event.target === mobileDayModal) {
                    mobileDayModal.style.display = 'none';
                }
                if (event.target === mobileSplitModal) {
                    mobileSplitModal.style.display = 'none';
                }
            });
            
            if (workoutTypeSelect) {
                workoutTypeSelect.addEventListener('change', function() {
                    const value = this.value;
                    
                    if (value === 'custom') {
                        customNameGroup.style.display = 'block';
                        templateSelectGroup.style.display = 'block';
                    } else if (value === 'rest') {
                        customNameGroup.style.display = 'none';
                        templateSelectGroup.style.display = 'none';
                    } else if (value !== '') {
                        customNameGroup.style.display = 'none';
                        templateSelectGroup.style.display = 'block';
                    } else {
                        customNameGroup.style.display = 'none';
                        templateSelectGroup.style.display = 'none';
                    }
                });
            }
            
            if (mobileWorkoutTypeSelect) {
                mobileWorkoutTypeSelect.addEventListener('change', function() {
                    const value = this.value;
                    
                    if (value === 'custom') {
                        mobileCustomNameGroup.style.display = 'block';
                        mobileTemplateSelectGroup.style.display = 'block';
                    } else if (value === 'rest') {
                        mobileCustomNameGroup.style.display = 'none';
                        mobileTemplateSelectGroup.style.display = 'none';
                    } else if (value !== '') {
                        mobileCustomNameGroup.style.display = 'none';
                        mobileTemplateSelectGroup.style.display = 'block';
                    } else {
                        mobileCustomNameGroup.style.display = 'none';
                        mobileTemplateSelectGroup.style.display = 'none';
                    }
                });
            }
            
            weekDaySelects.forEach((select, index) => {
                select.addEventListener('change', function() {
                    const value = this.value;
                    const templateSelect = document.getElementById(`template_${index}`);
                    const customInput = document.getElementById(`custom_${index}`);
                    
                    if (value === 'custom') {
                        customInput.style.display = 'block';
                        templateSelect.style.display = 'block';
                    } else if (value === 'rest') {
                        customInput.style.display = 'none';
                        templateSelect.style.display = 'none';
                    } else if (value !== '') {
                        customInput.style.display = 'none';
                        templateSelect.style.display = 'block';
                    } else {
                        customInput.style.display = 'none';
                        templateSelect.style.display = 'none';
                    }
                });
            });
            
            mobileSplitTypeSelects.forEach((select, index) => {
                select.addEventListener('change', function() {
                    const value = this.value;
                    const templateGroup = document.getElementById(`mobile_template_group_${index}`);
                    const customGroup = document.getElementById(`mobile_custom_group_${index}`);
                    
                    if (value === 'custom') {
                        customGroup.style.display = 'block';
                        templateGroup.style.display = 'block';
                    } else if (value === 'rest') {
                        customGroup.style.display = 'none';
                        templateGroup.style.display = 'none';
                    } else if (value !== '') {
                        customGroup.style.display = 'none';
                        templateGroup.style.display = 'block';
                    } else {
                        customGroup.style.display = 'none';
                        templateGroup.style.display = 'none';
                    }
                });
            });
            
            if (savedSplitsSelect) {
                savedSplitsSelect.addEventListener('change', function() {
                    const splitId = this.value;
                    if (!splitId) return;
                    
                    fetch(`get_workout_split.php?id=${splitId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const split = data.split;
                                document.getElementById('splitName').value = split.name;
                                
                                if (split.data) {
                                    const weekData = JSON.parse(split.data);
                                    weekData.forEach((day, index) => {
                                        const typeSelect = document.getElementById(`day_${index}`);
                                        const templateSelect = document.getElementById(`template_${index}`);
                                        const customInput = document.getElementById(`custom_${index}`);
                                        
                                        if (typeSelect && day.type) {
                                            typeSelect.value = day.type;
                                            
                                            if (day.type === 'rest') {
                                                templateSelect.style.display = 'none';
                                                customInput.style.display = 'none';
                                            } else {
                                                templateSelect.style.display = 'block';
                                                
                                                if (day.template_id) {
                                                    templateSelect.value = day.template_id;
                                                }
                                                
                                                if (day.type === 'custom' && day.custom_name) {
                                                    customInput.style.display = 'block';
                                                    customInput.value = day.custom_name;
                                                }
                                            }
                                        }
                                    });
                                }
                            } else {
                                alert('Error loading split: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while loading the split.');
                        });
                });
            }
            
            if (mobileSavedSplits) {
                mobileSavedSplits.addEventListener('change', function() {
                    const splitId = this.value;
                    if (!splitId) {
                        document.getElementById('mobileSplitName').value = '';
                        mobileSplitTypeSelects.forEach((select, index) => {
                            select.value = '';
                            document.getElementById(`mobile_template_group_${index}`).style.display = 'none';
                            document.getElementById(`mobile_custom_group_${index}`).style.display = 'none';
                        });
                        return;
                    }
                    
                    fetch(`get_workout_split.php?id=${splitId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const split = data.split;
                                document.getElementById('mobileSplitName').value = split.name;
                                
                                if (split.data) {
                                    const weekData = JSON.parse(split.data);
                                    weekData.forEach((day, index) => {
                                        const typeSelect = document.getElementById(`mobile_day_${index}`);
                                        const templateGroup = document.getElementById(`mobile_template_group_${index}`);
                                        const templateSelect = document.getElementById(`mobile_template_${index}`);
                                        const customGroup = document.getElementById(`mobile_custom_group_${index}`);
                                        const customInput = document.getElementById(`mobile_custom_${index}`);
                                        
                                        if (typeSelect && day.type) {
                                            typeSelect.value = day.type;
                                            
                                            if (day.type === 'rest') {
                                                templateGroup.style.display = 'none';
                                                customGroup.style.display = 'none';
                                            } else {
                                                templateGroup.style.display = 'block';
                                                
                                                if (day.template_id) {
                                                    templateSelect.value = day.template_id;
                                                }
                                                
                                                if (day.type === 'custom' && day.custom_name) {
                                                    customGroup.style.display = 'block';
                                                    customInput.value = day.custom_name;
                                                } else {
                                                    customGroup.style.display = 'none';
                                                }
                                            }
                                        }
                                    });
                                }
                            } else {
                                alert('Error loading split: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while loading the split.');
                        });
                });
            }
            
            if (workoutPlanForm) {
                workoutPlanForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('month', <?= $selected_month ?>);
                    formData.append('year', <?= $selected_year ?>);
                    
                    fetch('save_workout_plan.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout plan saved successfully!');
                            dayModal.style.display = 'none';
                            
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout plan.');
                    });
                });
            }
            
            if (mobileWorkoutPlanForm) {
                mobileWorkoutPlanForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('save_workout_plan.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout plan saved successfully!');
                            mobileDayModal.style.display = 'none';
                            
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout plan.');
                    });
                });
            }
        
            if (splitPlanForm) {
                splitPlanForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const splitName = document.getElementById('splitName').value;
                    if (!splitName) {
                        alert('Please enter a name for your split');
                        return;
                    }
                    
                    let hasWorkout = false;
                    weekDaySelects.forEach(select => {
                        if (select.value) hasWorkout = true;
                    });
                    
                    if (!hasWorkout) {
                        alert('Please assign at least one workout to your weekly split');
                        return;
                    }
                    
                    const formData = new FormData(this);
                    formData.append('month', <?= $selected_month ?>);
                    formData.append('year', <?= $selected_year ?>);
                    
                    fetch('save_workout_split.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout split applied successfully!');
                            splitModal.style.display = 'none';
                            
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout split.');
                    });
                });
            }
            
            if (mobileSplitForm && mobileSplitSave) {
                mobileSplitForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveMobileSplit(false);
                });
            }
            
            if (mobileSplitApply) {
                mobileSplitApply.addEventListener('click', function(e) {
                    e.preventDefault();
                    saveMobileSplit(true);
                });
            }
            
            function saveMobileSplit(applyToCalendar) {
                const splitName = document.getElementById('mobileSplitName').value;
                if (!splitName) {
                    alert('Please enter a name for your split');
                    return;
                }
                
                let hasWorkout = false;
                mobileSplitTypeSelects.forEach(select => {
                    if (select.value) hasWorkout = true;
                });
                
                if (!hasWorkout) {
                    alert('Please assign at least one workout to your weekly split');
                    return;
                }
                
                const formData = new FormData(document.getElementById('mobileSplitForm'));
                
                if (applyToCalendar) {
                    formData.append('apply_to_calendar', '1');
                    formData.append('month', <?= $selected_month ?>);
                    formData.append('year', <?= $selected_year ?>);
                }
                
                fetch('save_workout_split.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (applyToCalendar) {
                            alert('Workout split applied to calendar successfully!');
                            window.location.reload();
                        } else {
                            alert('Workout split saved successfully!');
                            if (data.split_id) {
                                if (mobileSavedSplits) {
                                    const option = document.createElement('option');
                                    option.value = data.split_id;
                                    option.textContent = splitName;
                                    mobileSavedSplits.appendChild(option);
                                    mobileSavedSplits.value = data.split_id;
                                }
                            }
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving your workout split.');
                });
            }
            
            if (newWorkoutBtn) {
                newWorkoutBtn.addEventListener('click', function() {
                    window.location.href = 'workout-templates.php';
                });
            }
            
            const templateCards = document.querySelectorAll('.template-card');
            templateCards.forEach(card => {
                card.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-template-id');
                    if (templateId) {
                        window.location.href = `quick-workout.php?template_id=${templateId}`;
                    }
                });
            });
            
            const startBtn = document.getElementById('startWorkoutBtn');
            if (startBtn) {
                startBtn.addEventListener('click', function(e) {
                    try {
                        e.preventDefault();
                        const templateId = this.getAttribute('data-template-id');
                        console.log('Desktop - Starting workout with template ID:', templateId);
                        
                        window.location.href = 'workout.php?template_id=' + templateId + '&start_workout=1&auto_start=1';
                    } catch (error) {
                        console.error('Error starting desktop workout:', error);
                    }
                });
            }
        
            const mobileBtn = document.getElementById('mobileStartWorkoutBtn');
            if (mobileBtn) {
                mobileBtn.addEventListener('click', function(e) {
                    try {
                        e.preventDefault();
                        const templateId = this.getAttribute('data-template-id');
                        console.log('Mobile - Starting workout with template ID:', templateId);
                        
                        window.location.href = 'mobile-workout.php?template_id=' + templateId + '&start_workout=1&auto_start=1&start_step=2';
                    } catch (error) {
                        console.error('Error starting mobile workout:', error);
                    }
                });
            }
            
            const mobileAppView = document.querySelector('.mobile-app-view');
            const dashboardGrid = document.querySelector('.dashboard-grid');
            
            function handleViewChange() {
                const width = window.innerWidth;
                console.log('Window width:', width);
                
                if (width < 768) {
                    console.log('Switching to mobile view');
                    if (mobileAppView) {
                        mobileAppView.style.display = 'block';
                        console.log('Mobile view display set to block');
                    }
                    if (dashboardGrid) {
                        dashboardGrid.style.display = 'none';
                        console.log('Dashboard grid display set to none');
                    }
                } else {
                    console.log('Switching to desktop view');
                    if (mobileAppView) {
                        mobileAppView.style.display = 'none';
                        console.log('Mobile view display set to none');
                    }
                    if (dashboardGrid) {
                        dashboardGrid.style.display = 'grid';
                        console.log('Dashboard grid display set to grid');
                    }
                }
            }
            
            window.addEventListener('DOMContentLoaded', handleViewChange);
            
            window.addEventListener('resize', handleViewChange);

            const mobileDayBtns = document.querySelectorAll('.mobile-day-btn');
            mobileDayBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    mobileDayBtns.forEach(b => b.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const selectedDate = this.getAttribute('data-date');
                    console.log(`Selected date: ${selectedDate}`);

                });
            });
        
            const mobilePlanWorkoutBtn = document.getElementById('mobilePlanWorkoutBtn');
            if (mobilePlanWorkoutBtn) {
                mobilePlanWorkoutBtn.addEventListener('click', function() {
                    const dayNum = <?= $today ?>;
                    const monthName = '<?= $monthName ?>';
                    
                    document.getElementById('selectedDate').textContent = `${monthName} ${dayNum}, <?= $selected_year ?>`;
                    document.getElementById('selectedDay').value = dayNum;
                    
                    document.getElementById('workoutType').value = '';
                    document.getElementById('templateSelectGroup').style.display = 'none';
                    document.getElementById('customNameGroup').style.display = 'none';
                    
                    document.getElementById('dayModal').style.display = 'flex';
                });
            }
            
            const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
            mobileNavItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                        
                        mobileNavItems.forEach(i => i.classList.remove('active'));
                        
                        this.classList.add('active');
                    }
                });
            });
        });
    </script>

    <?php if ($today_workout && !empty($today_workout['template_id'])): ?>
    <form id="workoutForm" action="workout.php" method="POST" style="display: none;">
        <input type="hidden" name="template_id" id="templateIdInput" value="<?= $today_workout['template_id'] ?>">
        <input type="hidden" name="start_workout" value="1">
        <input type="hidden" name="auto_start" value="1">
        <input type="hidden" name="start_step" value="2">
    </form>
    
    <form id="mobileWorkoutForm" action="workout.php" method="POST" style="display: none;">
        <input type="hidden" name="template_id" id="mobileTemplateIdInput" value="<?= $today_workout['template_id'] ?>">
        <input type="hidden" name="start_workout" value="1">
        <input type="hidden" name="auto_start" value="1">
        <input type="hidden" name="start_step" value="2">
    </form>
    <?php endif; ?>
</body>
</html> 