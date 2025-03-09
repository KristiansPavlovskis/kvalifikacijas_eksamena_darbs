<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/profile.php");
    exit;
}

// Include database connection
require_once '../assets/db_connection.php';

// Fetch additional user information 
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

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Get basic user info
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
    // Log error and continue with default values
    error_log("Error fetching user info: " . $e->getMessage());
}

// Get workout statistics
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

// Get workout volume
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

// Calculate activity streak
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

// Update last active
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

// Get active goals count
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

// Get favorite exercises
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

// Get most recent workouts
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

// Get active goals for display
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
        /* Profile-specific styles with modern UI improvements */
        .prof-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        
        .prof-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 30px;
            background: linear-gradient(135deg, #4361ee, #4cc9f0);
            border-radius: 16px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .prof-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 40%;
            background: rgba(255, 255, 255, 0.1);
            transform: skewX(-15deg);
            transform-origin: top right;
        }
        
        .prof-user-info {
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            z-index: 2;
        }
        
        .prof-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4361ee;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 4px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        .prof-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .prof-user-details h1 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .prof-user-details p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .prof-stats {
            display: flex;
            gap: 20px;
            z-index: 2;
            position: relative;
        }
        
        .prof-stat-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 12px;
            backdrop-filter: blur(5px);
            min-width: 120px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .prof-stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .prof-stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .prof-stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .prof-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 10px;
        }
        
        .prof-nav::-webkit-scrollbar {
            display: none;
        }
        
        .prof-nav-item {
            padding: 12px 24px;
            background-color: #1E1E1E;
            color: white;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .prof-nav-item:hover, .prof-nav-item.active {
            background-color: #4361ee;
            transform: translateY(-3px);
        }
        
        .prof-nav-item i {
            font-size: 1.2rem;
        }
        
        .prof-section {
            margin-bottom: 30px;
            background-color: #1E1E1E;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .prof-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }
        
        .prof-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .prof-section-title i {
            color: #4361ee;
        }
        
        .prof-section-action {
            color: #4361ee;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .prof-section-action:hover {
            color: #4cc9f0;
        }
        
        .prof-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .prof-card {
            background-color: #292929;
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .prof-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .prof-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .prof-card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .prof-card-subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 5px;
        }
        
        .prof-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background-color: rgba(67, 97, 238, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #4361ee;
        }
        
        .prof-progress {
            margin-top: 15px;
        }
        
        .prof-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .prof-progress-bar {
            height: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .prof-progress-value {
            height: 100%;
            background: linear-gradient(to right, #4361ee, #4cc9f0);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .prof-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .prof-button:hover {
            background-color: #3a56d4;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }
        
        .prof-button.secondary {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .prof-button.secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .prof-chart {
            width: 100%;
            height: 250px;
            margin-top: 20px;
            position: relative;
        }
        
        .prof-stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .prof-stat-card {
            background: linear-gradient(135deg, #292929, #343434);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .prof-stat-card:hover {
            transform: translateY(-5px);
        }
        
        .prof-stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(67, 97, 238, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #4361ee;
            margin-bottom: 15px;
        }
        
        .prof-stat-card-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
            background: linear-gradient(to right, #4361ee, #4cc9f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .prof-stat-card-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        @media (max-width: 768px) {
            .prof-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 20px;
            }
            
            .prof-user-info {
                width: 100%;
                justify-content: flex-start;
            }
            
            .prof-stats {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 15px;
            }
            
            .prof-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="logo">
            <a href="../index.php">
                <i class="fas fa-dumbbell"></i>
                <span>GYMVERSE</span>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../workouts.php"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                <li><a href="../excercises.php"><i class="fas fa-running"></i> Exercises</a></li>
                <li><a href="../quick-workout.php"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                <li><a class="active" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="prof-container">
        <!-- Profile Header -->
        <div class="prof-header">
            <div class="prof-user-info">
                <div class="prof-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="prof-user-details">
                    <h1><?= htmlspecialchars($username) ?></h1>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Member since <?= $join_date ?></p>
                </div>
            </div>
            <div class="prof-stats">
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $total_workouts ?></div>
                    <div class="prof-stat-label">Workouts</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= number_format($total_volume) ?></div>
                    <div class="prof-stat-label">Total Volume</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $activity_streak ?></div>
                    <div class="prof-stat-label">Day Streak</div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="prof-nav">
            <a href="profile.php" class="prof-nav-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="calories-burned.php" class="prof-nav-item">
                <i class="fas fa-fire"></i> Calories Burned
            </a>
            <a href="current-goal.php" class="prof-nav-item">
                <i class="fas fa-bullseye"></i> Goals
            </a>
            <a href="nutrition.php" class="prof-nav-item">
                <i class="fas fa-apple-alt"></i> Nutrition
            </a>
            <a href="workout-analytics.php" class="prof-nav-item">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="quick-workout.php" class="prof-nav-item">
                <i class="fas fa-stopwatch"></i> Quick Workout
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="prof-stat-cards">
            <div class="prof-stat-card">
                <div class="prof-stat-card-icon">
                    <i class="fas fa-weight"></i>
                </div>
                <div class="prof-stat-card-value"><?= $body_weight ? $body_weight . ' kg' : 'N/A' ?></div>
                <div class="prof-stat-card-label">Body Weight</div>
            </div>
            <div class="prof-stat-card">
                <div class="prof-stat-card-icon">
                    <i class="fas fa-ruler-vertical"></i>
                </div>
                <div class="prof-stat-card-value"><?= $height ? $height . ' cm' : 'N/A' ?></div>
                <div class="prof-stat-card-label">Height</div>
            </div>
            <div class="prof-stat-card">
                <div class="prof-stat-card-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="prof-stat-card-value"><?= $fitness_level ?></div>
                <div class="prof-stat-card-label">Fitness Level</div>
            </div>
            <div class="prof-stat-card">
                <div class="prof-stat-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="prof-stat-card-value"><?= $last_active ?></div>
                <div class="prof-stat-card-label">Last Active</div>
            </div>
        </div>

        <!-- Recent Workouts Section -->
        <div class="prof-section">
            <div class="prof-section-header">
                <div class="prof-section-title">
                    <i class="fas fa-history"></i> Recent Workouts
                </div>
                <a href="../workouts.php" class="prof-section-action">
                    View All <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <?php if (isset($recent_workouts) && $recent_workouts && mysqli_num_rows($recent_workouts) > 0): ?>
                <div class="prof-grid">
                    <?php while ($workout = mysqli_fetch_assoc($recent_workouts)): ?>
                        <div class="prof-card">
                            <div class="prof-card-header">
                                <div>
                                    <div class="prof-card-title"><?= htmlspecialchars($workout['workout_name']) ?></div>
                                    <div class="prof-card-subtitle">
                                        <?= date("M d, Y", strtotime($workout['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="prof-card-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                            </div>
                            <div>
                                <p><i class="fas fa-stopwatch"></i> Duration: <?= $workout['duration'] ?? 0 ?> min</p>
                                <p><i class="fas fa-fire"></i> Calories: <?= $workout['calories_burned'] ?? 0 ?></p>
                                <p><i class="fas fa-cubes"></i> Volume: <?= number_format($workout['total_volume'] ?? 0) ?> kg</p>
                            </div>
                            <div style="margin-top: 15px;">
                                <a href="workout-summary.php?id=<?= $workout['id'] ?>" class="prof-button secondary" style="width: 100%;">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-dumbbell" style="font-size: 48px; opacity: 0.2; margin-bottom: 15px;"></i>
                    <h3>No Workouts Yet</h3>
                    <p style="margin-bottom: 20px;">Start tracking your fitness journey by recording your workouts.</p>
                    <a href="quick-workout.php" class="prof-button">
                        <i class="fas fa-plus-circle"></i> Start a Workout
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Fitness Progress -->
        <div class="prof-section">
            <div class="prof-section-header">
                <div class="prof-section-title">
                    <i class="fas fa-chart-line"></i> Fitness Goals
                </div>
                <a href="current-goal.php" class="prof-section-action">
                    View All <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <?php if (isset($goals) && $goals && mysqli_num_rows($goals) > 0): ?>
                <div class="prof-grid">
                    <?php while ($goal = mysqli_fetch_assoc($goals)): ?>
                        <?php 
                            $progress_percent = 0;
                            if ($goal['target_value'] > 0) {
                                $progress_percent = min(100, ($goal['current_value'] / $goal['target_value']) * 100);
                            }
                        ?>
                        <div class="prof-card">
                            <div class="prof-card-header">
                                <div>
                                    <div class="prof-card-title"><?= htmlspecialchars($goal['title']) ?></div>
                                    <div class="prof-card-subtitle">
                                        <?= date("M d", strtotime($goal['start_date'])) ?> - <?= date("M d, Y", strtotime($goal['end_date'])) ?>
                                    </div>
                                </div>
                                <div class="prof-card-icon">
                                    <i class="fas fa-bullseye"></i>
                                </div>
                            </div>
                            <div class="prof-progress">
                                <div class="prof-progress-label">
                                    <span>Progress</span>
                                    <span><?= $goal['current_value'] ?> / <?= $goal['target_value'] ?> <?= $goal['unit'] ?></span>
                                </div>
                                <div class="prof-progress-bar">
                                    <div class="prof-progress-value" style="width: <?= $progress_percent ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-bullseye" style="font-size: 48px; opacity: 0.2; margin-bottom: 15px;"></i>
                    <h3>No Goals Set</h3>
                    <p style="margin-bottom: 20px;">Set fitness goals to track your progress and stay motivated.</p>
                    <a href="current-goal.php" class="prof-button">
                        <i class="fas fa-plus-circle"></i> Set a Goal
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Hide loading screen if present
            const loadingScreen = document.querySelector('.loading-screen');
            if (loadingScreen) {
                setTimeout(() => {
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }, 500);
            }
        });
    </script>
</body>
</html> 