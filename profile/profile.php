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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Your Fitness Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
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

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
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

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .stat-icon {
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

        .stat-icon.workout {
            background: var(--gradient-blue);
        }

        .stat-icon.volume {
            background: var(--gradient-purple);
        }

        .stat-icon.streak {
            background: var(--gradient-pink);
        }

        .stat-icon.level {
            background: var(--gradient-green);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--danger);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.03));
            border-radius: 0 0 0 100%;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1400px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
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
            color: var(--primary-light);
        }

        .section-action {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: 8px;
            background-color: rgba(67, 97, 238, 0.08);
        }

        .section-action:hover {
            background-color: rgba(67, 97, 238, 0.15);
            transform: translateX(3px);
        }

        .section-body {
            padding: 25px 30px;
        }

        .workout-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .workout-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.03);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .workout-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
        }

        .workout-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient-blue);
            border-radius: 2px;
        }

        .workout-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--gradient-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            flex-shrink: 0;
        }

        .workout-info {
            flex-grow: 1;
        }

        .workout-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .workout-date {
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .workout-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px 30px;
            margin-top: 10px;
        }

        .workout-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .workout-stat i {
            color: var(--primary-light);
            width: 16px;
        }

        .workout-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .workout-button {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .workout-button:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .view-all-link {
            display: block;
            text-align: center;
            padding: 12px;
            margin-top: 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .view-all-link:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .goal-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            transition: var(--transition);
        }

        .goal-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .goal-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--gradient-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .goal-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .goal-dates {
            font-size: 0.85rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }

        .goal-progress {
            margin-bottom: 15px;
        }

        .goal-progress-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .goal-progress-bar {
            height: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .goal-progress-value {
            height: 100%;
            border-radius: 4px;
            background: var(--gradient-purple);
            position: relative;
        }

        .goal-progress-value::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: shine 1.5s infinite;
        }

        .personal-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .personal-stat-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            transition: var(--transition);
        }

        .personal-stat-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .personal-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--gradient-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            margin-bottom: 15px;
        }

        .personal-stat-label {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 5px;
        }

        .personal-stat-value {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .activity-calendar {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .calendar-day {
            aspect-ratio: 1/1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: var(--transition);
            position: relative;
            background-color: rgba(255, 255, 255, 0.03);
        }

        .calendar-day.active {
            background-color: rgba(67, 97, 238, 0.2);
            color: var(--primary-light);
        }

        .calendar-day.today {
            border: 2px solid var(--primary);
        }

        .calendar-day:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .calendar-day.active:hover {
            background-color: rgba(67, 97, 238, 0.3);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .quick-action-button {
            background-color: rgba(255, 255, 255, 0.03);
            border: none;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            color: white;
            text-align: left;
        }

        .quick-action-button:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-3px);
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
        }

        .quick-action-icon.blue {
            background: var(--gradient-blue);
        }

        .quick-action-icon.purple {
            background: var(--gradient-purple);
        }

        .quick-action-icon.pink {
            background: var(--gradient-pink);
        }

        .quick-action-icon.green {
            background: var(--gradient-green);
        }

        .quick-action-text {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state-text {
            color: var(--gray-light);
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .empty-state-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 50px;
            background: var(--gradient-blue);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .empty-state-button:hover {
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            transform: translateY(-3px);
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @media (max-width: 1200px) {
            .stats-summary {
                grid-template-columns: repeat(2, 1fr);
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
            
            .personal-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .goals-grid {
                grid-template-columns: 1fr;
            }
            
            .workout-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .workout-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .workout-actions {
                position: absolute;
                top: 20px;
                right: 20px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Fitness Dashboard</h1>
                <div class="page-actions">
                    <button class="action-button secondary">
                        <i class="fas fa-calendar-alt"></i> This Month
                    </button>
                    <button class="action-button primary">
                        <i class="fas fa-plus"></i> New Workout
                    </button>
                </div>
            </div>
            
            <div class="stats-summary">
                <div class="stat-card">
                    <div class="stat-icon workout">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="stat-value"><?= $total_workouts ?></div>
                    <div class="stat-label">Total Workouts</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon volume">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stat-value"><?= number_format($total_volume) ?></div>
                    <div class="stat-label">Total Volume (kg)</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon streak">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-value"><?= $activity_streak ?></div>
                    <div class="stat-label">Day Streak</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 2 days longer
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon level">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-value"><?= $fitness_level ?></div>
                    <div class="stat-label">Fitness Level</div>
                    <div class="stat-change">
                        <i class="fas fa-minus"></i> Unchanged
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div>
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-history"></i> Recent Workouts
                            </h2>
                            <a href="../workouts.php" class="section-action">
                                View All <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        
                        <div class="section-body">
                            <?php if (isset($recent_workouts) && $recent_workouts && mysqli_num_rows($recent_workouts) > 0): ?>
                                <div class="workout-list">
                                    <?php while ($workout = mysqli_fetch_assoc($recent_workouts)): ?>
                                        <div class="workout-card">
                                            <div class="workout-icon">
                                                <i class="fas fa-dumbbell"></i>
                                            </div>
                                            <div class="workout-info">
                                                <div class="workout-title"><?= htmlspecialchars($workout['workout_name']) ?></div>
                                                <div class="workout-date"><?= date("F d, Y", strtotime($workout['created_at'])) ?></div>
                                                <div class="workout-stats">
                                                    <div class="workout-stat">
                                                        <i class="fas fa-stopwatch"></i>
                                                        <span><?= $workout['duration'] ?? 0 ?> min</span>
                                                    </div>
                                                    <div class="workout-stat">
                                                        <i class="fas fa-fire"></i>
                                                        <span><?= $workout['calories_burned'] ?? 0 ?> cal</span>
                                                    </div>
                                                    <div class="workout-stat">
                                                        <i class="fas fa-cubes"></i>
                                                        <span><?= number_format($workout['total_volume'] ?? 0) ?> kg</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="workout-actions">
                                                <a href="workout-summary.php?id=<?= $workout['id'] ?>" class="workout-button">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <a href="workout-history.php" class="view-all-link">
                                    View All Workouts <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-dumbbell"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Workouts Yet</h3>
                                    <p class="empty-state-text">Start tracking your fitness journey by recording your workouts.</p>
                                    <a href="quick-workout.php" class="empty-state-button">
                                        <i class="fas fa-plus-circle"></i> Start a Workout
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-bullseye"></i> Fitness Goals
                            </h2>
                            <a href="current-goal.php" class="section-action">
                                View All <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        
                        <div class="section-body">
                            <?php if (isset($goals) && $goals && mysqli_num_rows($goals) > 0): ?>
                                <div class="goals-grid">
                                    <?php while ($goal = mysqli_fetch_assoc($goals)): ?>
                                        <?php 
                                            $progress_percent = 0;
                                            if ($goal['target_value'] > 0) {
                                                $progress_percent = min(100, ($goal['current_value'] / $goal['target_value']) * 100);
                                            }
                                            
                                            $icon_class = "fas fa-bullseye";
                                            switch(strtolower($goal['goal_type'] ?? '')) {
                                                case 'weight':
                                                    $icon_class = "fas fa-weight";
                                                    break;
                                                case 'cardio':
                                                    $icon_class = "fas fa-heartbeat";
                                                    break;
                                                case 'strength':
                                                    $icon_class = "fas fa-dumbbell";
                                                    break;
                                                case 'endurance':
                                                    $icon_class = "fas fa-running";
                                                    break;
                                            }
                                        ?>
                                        <div class="goal-card">
                                            <div class="goal-header">
                                                <div>
                                                    <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
                                                    <div class="goal-dates">
                                                        <i class="fas fa-calendar"></i>
                                                        <?= date("M d", strtotime($goal['start_date'])) ?> - <?= date("M d, Y", strtotime($goal['end_date'])) ?>
                                                    </div>
                                                </div>
                                                <div class="goal-icon">
                                                    <i class="<?= $icon_class ?>"></i>
                                                </div>
                                            </div>
                                            <div class="goal-progress">
                                                <div class="goal-progress-stats">
                                                    <span>Progress</span>
                                                    <span><?= $goal['current_value'] ?> / <?= $goal['target_value'] ?> <?= $goal['unit'] ?></span>
                                                </div>
                                                <div class="goal-progress-bar">
                                                    <div class="goal-progress-value" style="width: <?= $progress_percent ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <a href="current-goal.php" class="view-all-link">
                                    Manage All Goals <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Goals Set</h3>
                                    <p class="empty-state-text">Setting specific fitness goals helps you stay motivated and track your progress effectively.</p>
                                    <a href="current-goal.php" class="empty-state-button">
                                        <i class="fas fa-plus-circle"></i> Set a Goal
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-user-chart"></i> Personal Stats
                            </h2>
                            <a href="#" class="section-action">
                                Update <i class="fas fa-edit"></i>
                            </a>
                        </div>
                        
                        <div class="section-body">
                            <div class="personal-stats">
                                <div class="personal-stat-card">
                                    <div class="personal-stat-icon">
                                        <i class="fas fa-weight"></i>
                                    </div>
                                    <div class="personal-stat-label">Body Weight</div>
                                    <div class="personal-stat-value"><?= $body_weight ? $body_weight . ' kg' : 'Not set' ?></div>
                                </div>
                                
                                <div class="personal-stat-card">
                                    <div class="personal-stat-icon">
                                        <i class="fas fa-ruler-vertical"></i>
                                    </div>
                                    <div class="personal-stat-label">Height</div>
                                    <div class="personal-stat-value"><?= $height ? $height . ' cm' : 'Not set' ?></div>
                                </div>
                                
                                <div class="personal-stat-card">
                                    <div class="personal-stat-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="personal-stat-label">Fitness Level</div>
                                    <div class="personal-stat-value"><?= $fitness_level ?></div>
                                </div>
                                
                                <div class="personal-stat-card">
                                    <div class="personal-stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="personal-stat-label">Last Active</div>
                                    <div class="personal-stat-value"><?= $last_active ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-calendar-check"></i> Activity Streak
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <p style="margin-bottom: 15px; color: var(--gray-light);">Your recent workout activity for the last 28 days:</p>
                            
                            <?php
                                $current_day = date('d');
                                $days_in_month = date('t');
                                $current_month = date('M');
                                
                                $calendar_days = min(28, $current_day);
                                $calendar_array = [];
                                
                                for ($i = $calendar_days - 1; $i >= 0; $i--) {
                                    $day = $current_day - $i;
                                    $is_active = (($day % 3 == 0) || ($day % 5 == 0)); 
                                    $is_today = ($day == $current_day);
                                    $calendar_array[] = [
                                        'day' => $day,
                                        'active' => $is_active,
                                        'today' => $is_today
                                    ];
                                }
                            ?>
                            
                            <div class="activity-calendar">
                                <?php foreach($calendar_array as $day): ?>
                                    <div class="calendar-day <?= $day['active'] ? 'active' : '' ?> <?= $day['today'] ? 'today' : '' ?>">
                                        <?= $day['day'] ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 20px; font-size: 0.9rem; color: var(--gray-light); display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-fire"></i> Current streak: <span style="color: white; font-weight: 600;"><?= $activity_streak ?> days</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <div class="quick-actions">
                                <a href="quick-workout.php" class="quick-action-button">
                                    <div class="quick-action-icon blue">
                                        <i class="fas fa-stopwatch"></i>
                                    </div>
                                    <div class="quick-action-text">Start Quick Workout</div>
                                </a>
                                
                                <a href="current-goal.php" class="quick-action-button">
                                    <div class="quick-action-icon purple">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="quick-action-text">Add New Goal</div>
                                </a>
                                
                                <a href="calories-burned.php" class="quick-action-button">
                                    <div class="quick-action-icon pink">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="quick-action-text">Calories Tracker</div>
                                </a>
                                
                                <a href="nutrition.php" class="quick-action-button">
                                    <div class="quick-action-icon green">
                                        <i class="fas fa-apple-alt"></i>
                                    </div>
                                    <div class="quick-action-text">Nutrition Log</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card, .goal-card, .personal-stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.25)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
            
            const actionButtons = document.querySelectorAll('.action-button, .quick-action-button');
            actionButtons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    if (this.classList.contains('primary')) {
                        this.style.boxShadow = '0 8px 20px rgba(67, 97, 238, 0.3)';
                    }
                    this.style.transform = 'translateY(-3px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.boxShadow = '';
                    this.style.transform = '';
                });
            });
            
            const newWorkoutBtn = document.querySelector('.action-button.primary');
            if (newWorkoutBtn) {
                newWorkoutBtn.addEventListener('click', function() {
                    window.location.href = 'quick-workout.php';
                });
            }
        });
    </script>
</body>
</html> 