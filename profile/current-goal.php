<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/current-goal.php");
    exit;
}

// Include database connection
require_once '../assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Process goal submission
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_goal"])) {
        // Add new goal
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $target_value = floatval($_POST["target_value"]);
        $current_value = floatval($_POST["current_value"]);
        $goal_type = trim($_POST["goal_type"]);
        $deadline = $_POST["deadline"];
        
        if (empty($title) || empty($goal_type) || empty($deadline)) {
            $message = "Please fill out all required fields.";
            $message_type = "error";
        } else {
            try {
                $query = "INSERT INTO goals (user_id, title, description, target_value, current_value, goal_type, deadline, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issddss", $user_id, $title, $description, $target_value, $current_value, $goal_type, $deadline);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Goal added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . mysqli_error($conn);
                    $message_type = "error";
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    } else if (isset($_POST["update_goal"])) {
        // Update existing goal
        $goal_id = intval($_POST["goal_id"]);
        $current_value = floatval($_POST["current_value"]);
        $completed = isset($_POST["completed"]) ? 1 : 0;
        
        try {
            $query = "UPDATE goals SET current_value = ?, completed = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "diii", $current_value, $completed, $goal_id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Goal updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . mysqli_error($conn);
                $message_type = "error";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    } else if (isset($_POST["delete_goal"])) {
        // Delete goal
        $goal_id = intval($_POST["goal_id"]);
        
        try {
            $query = "DELETE FROM goals WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $goal_id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Goal deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . mysqli_error($conn);
                $message_type = "error";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch active goals
$active_goals = [];
try {
    // Check if goals table exists first
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    if (mysqli_num_rows($result) > 0) {
        $query = "SELECT * FROM goals WHERE user_id = ? AND completed = 0 ORDER BY deadline ASC";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute query: " . mysqli_error($conn));
        }
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            throw new Exception("Failed to get result: " . mysqli_error($conn));
        }
        
        while ($row = mysqli_fetch_assoc($result)) {
            $active_goals[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching active goals: " . $e->getMessage());
    $message = "Error fetching active goals: " . $e->getMessage();
    $message_type = "error";
}

// Fetch completed goals
$completed_goals = [];
try {
    // Check if goals table exists first
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    if (mysqli_num_rows($result) > 0) {
        $query = "SELECT * FROM goals WHERE user_id = ? AND completed = 1 ORDER BY updated_at DESC LIMIT 5";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute query: " . mysqli_error($conn));
        }
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            throw new Exception("Failed to get result: " . mysqli_error($conn));
        }
        
        while ($row = mysqli_fetch_assoc($result)) {
            $completed_goals[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching completed goals: " . $e->getMessage());
    $message = "Error fetching completed goals: " . $e->getMessage();
    $message_type = "error";
}

// Function to calculate progress percentage
function calculateProgress($current, $target) {
    if ($target == 0) return 0;
    $progress = ($current / $target) * 100;
    return min(100, max(0, $progress));
}

// Function to get badge color based on goal type
function getGoalTypeColor($type) {
    switch ($type) {
        case 'weight':
            return '#4361ee';
        case 'strength':
            return '#e63946';
        case 'endurance':
            return '#2a9d8f';
        case 'workout':
            return '#fb8500';
        case 'nutrition':
            return '#7209b7';
        default:
            return '#6c757d';
    }
}

// Function to get icon based on goal type
function getGoalTypeIcon($type) {
    switch ($type) {
        case 'weight':
            return 'fa-weight';
        case 'strength':
            return 'fa-dumbbell';
        case 'endurance':
            return 'fa-running';
        case 'workout':
            return 'fa-calendar-check';
        case 'nutrition':
            return 'fa-apple-alt';
        default:
            return 'fa-bullseye';
    }
}

// Function to format the deadline
function formatDeadline($deadline) {
    $date = new DateTime($deadline);
    $now = new DateTime();
    $interval = $now->diff($date);
    
    if ($interval->invert) {
        return 'Overdue by ' . ($interval->days > 0 ? $interval->days . ' days' : $interval->h . ' hours');
    } else if ($interval->days == 0) {
        return 'Due today';
    } else if ($interval->days == 1) {
        return 'Due tomorrow';
    } else if ($interval->days < 7) {
        return 'Due in ' . $interval->days . ' days';
    } else if ($interval->days < 30) {
        return 'Due in ' . ceil($interval->days / 7) . ' weeks';
    } else {
        return 'Due on ' . $date->format('M d, Y');
    }
}

// Check if goals table exists
$goalsTableExists = false;
try {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    $goalsTableExists = mysqli_num_rows($result) > 0;
    
    if (!$goalsTableExists) {
        // Create goals table
        $createGoalsTable = "CREATE TABLE goals (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            target_value DECIMAL(10,2) DEFAULT 0,
            current_value DECIMAL(10,2) DEFAULT 0,
            goal_type VARCHAR(50) NOT NULL,
            deadline DATE NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        if (mysqli_query($conn, $createGoalsTable)) {
            $message = "Goals table created successfully. You can now add your fitness goals!";
            $message_type = "success";
            $goalsTableExists = true;
        } else {
            $message = "Error creating goals table: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
} catch (Exception $e) {
    $message = "Error checking or creating goals table: " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Fitness Goals</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
    <style>
        /* Common profile section styles */
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
        }
        
        /* Goals page specific styles */
        .goals-container {
            max-width:
            1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        
        .goals-header {
            margin-bottom: 24px;
            background: linear-gradient(135deg, #7209b7, #3a0ca3);
            border-radius: 12px;
            color: white;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .goals-header::before {
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
        
        .goals-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .goals-title i {
            font-size: 1.8rem;
        }
        
        .goals-subtitle {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .goals-nav {
            padding: 5px;
            background-color: white;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            overflow-x: auto;
        }
        
        .goals-nav-link {
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .goals-nav-link:hover {
            background-color: #f5f5f5;
            color: #7209b7;
        }
        
        .goals-nav-link.active {
            background-color: #7209b7;
            color: white;
        }
        
        .goals-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        
        .goals-active-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .goals-section {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }
        
        .goals-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .goals-section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .goals-section-title i {
            color: #7209b7;
        }
        
        .goals-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: #7209b7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .goals-btn:hover {
            background-color: #5f0799;
            transform: translateY(-2px);
        }
        
        .goals-btn-secondary {
            background-color: #6c757d;
        }
        
        .goals-btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .goals-btn-danger {
            background-color: #e63946;
        }
        
        .goals-btn-danger:hover {
            background-color: #c82333;
        }
        
        .goals-btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .goals-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .goal-card {
            background-color: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        
        .goal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .goal-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .goal-type {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            gap: 5px;
            color: white;
        }
        
        .goal-deadline {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .goal-description {
            margin: 0 0 16px 0;
            color: #666;
            font-size: 0.95rem;
        }
        
        .goal-progress {
            margin-bottom: 12px;
        }
        
        .goal-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .goal-progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .goal-progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .goal-actions {
            display: flex;
            gap: 10px;
        }
        
        .goals-form-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }
        
        .goals-form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .goals-form-title i {
            color: #7209b7;
        }
        
        .goals-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .goals-form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .goals-form-group label {
            font-size: 0.95rem;
            font-weight: 500;
            color: #333;
        }
        
        .goals-form-group input, 
        .goals-form-group textarea, 
        .goals-form-group select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .goals-form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .goals-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .goals-form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }
        
        .goals-form-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 4px;
        }
        
        .goals-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }
        
        .goals-empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 16px;
        }
        
        .goals-empty-state h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .goals-empty-state p {
            margin: 0 0 20px 0;
            max-width: 400px;
        }
        
        .completed-goal {
            opacity: 0.7;
        }
        
        .message {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .update-form {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 992px) {
            .goals-content {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .goals-header {
                padding: 20px;
            }
            
            .goals-title {
                font-size: 1.8rem;
            }
            
            .goals-subtitle {
                font-size: 1rem;
            }
            
            .goals-section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .goals-form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Navigation Bar Styles */
        .navbar {
            background-color: #1E1E1E;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1000;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .navbar .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
            font-family: 'Koulen', sans-serif;
            font-size: 1.5rem;
        }

        .navbar .logo i {
            color: #FF4D4D;
            font-size: 1.8rem;
        }

        .navbar nav ul {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .navbar nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .navbar nav ul li a i {
            font-size: 1.1rem;
        }

        .navbar nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .navbar nav ul li a.active {
            background-color: #FF4D4D;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .navbar nav ul {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 1rem;
                gap: 10px;
            }

            .navbar nav ul li a {
                padding: 6px 12px;
                font-size: 0.9rem;
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
            <div>
                <h1><i class="fas fa-bullseye"></i> Fitness Goals</h1>
                <p>Set, track and achieve your fitness targets</p>
            </div>
            <div class="prof-stats">
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $active_goals_count ?></div>
                    <div class="prof-stat-label">Active Goals</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $completed_goals_count ?></div>
                    <div class="prof-stat-label">Completed</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= isset($success_rate) ? $success_rate . '%' : '0%' ?></div>
                    <div class="prof-stat-label">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="prof-nav">
            <a href="profile.php" class="prof-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="calories-burned.php" class="prof-nav-item">
                <i class="fas fa-fire"></i> Calories Burned
            </a>
            <a href="current-goal.php" class="prof-nav-item active">
                <i class="fas fa-bullseye"></i> Goals
            </a>
            <a href="nutrition.php" class="prof-nav-item">
                <i class="fas fa-apple-alt"></i> Nutrition
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-chart-line"></i> Progress
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>

        <div class="goals-container">
            <div class="goals-header">
                <h1 class="goals-title"><i class="fas fa-bullseye"></i> Fitness Goals</h1>
                <p class="goals-subtitle">Set meaningful goals, track your progress, and celebrate your achievements in your fitness journey.</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="goals-content">
                <div class="goals-active-container">
                    <div class="goals-section">
                        <div class="goals-section-header">
                            <h2 class="goals-section-title"><i class="fas fa-tasks"></i> Active Goals</h2>
                            <button class="goals-btn" onclick="document.getElementById('addGoalForm').style.display = 'block'">
                                <i class="fas fa-plus"></i> Add New Goal
                            </button>
                        </div>
                        
                        <div class="goals-grid">
                            <?php if (empty($active_goals) && $goalsTableExists): ?>
                                <div class="goals-empty-state">
                                    <i class="fas fa-bullseye"></i>
                                    <h3>No active goals yet</h3>
                                    <p>Set your first fitness goal to start tracking your progress towards success.</p>
                                    <button class="goals-btn" onclick="document.getElementById('addGoalForm').style.display = 'block'">
                                        <i class="fas fa-plus"></i> Add Your First Goal
                                    </button>
                                </div>
                            <?php elseif (!$goalsTableExists): ?>
                                <div class="goals-empty-state">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h3>Goals table not found</h3>
                                    <p>The goals feature is not yet set up. Please try again later or contact support.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($active_goals as $goal): ?>
                                    <?php 
                                        $progress = calculateProgress($goal['current_value'], $goal['target_value']);
                                        $color = getGoalTypeColor($goal['goal_type']);
                                        $icon = getGoalTypeIcon($goal['goal_type']);
                                        $deadline_text = formatDeadline($goal['deadline']);
                                    ?>
                                    <div class="goal-card" style="border-color: <?php echo $color; ?>;">
                                        <div class="goal-header">
                                            <div class="goal-info">
                                                <h3><?php echo htmlspecialchars($goal['title']); ?></h3>
                                                <div class="goal-type" style="background-color: <?php echo $color; ?>;">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst(htmlspecialchars($goal['goal_type'])); ?>
                                                </div>
                                                <div class="goal-deadline">
                                                    <i class="far fa-calendar-alt"></i> <?php echo $deadline_text; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($goal['description'])): ?>
                                            <div class="goal-description"><?php echo htmlspecialchars($goal['description']); ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="goal-progress">
                                            <div class="goal-progress-header">
                                                <span><?php echo $goal['current_value']; ?> / <?php echo $goal['target_value']; ?></span>
                                                <span><?php echo round($progress); ?>% complete</span>
                                            </div>
                                            <div class="goal-progress-bar">
                                                <div class="goal-progress-fill" style="width: <?php echo $progress; ?>%; background-color: <?php echo $color; ?>;"></div>
                                            </div>
                                        </div>
                                        
                                        <form class="update-form" method="post" action="">
                                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                            <div class="goals-form-row">
                                                <div class="goals-form-group">
                                                    <label for="current_value_<?php echo $goal['id']; ?>">Current Value</label>
                                                    <input type="number" step="0.01" name="current_value" id="current_value_<?php echo $goal['id']; ?>" value="<?php echo $goal['current_value']; ?>">
                                                </div>
                                                <div class="goals-form-group">
                                                    <label for="completed_<?php echo $goal['id']; ?>">
                                                        <input type="checkbox" name="completed" id="completed_<?php echo $goal['id']; ?>" <?php echo $goal['completed'] ? 'checked' : ''; ?>>
                                                        Mark as Completed
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="goal-actions">
                                                <button type="submit" name="update_goal" class="goals-btn goals-btn-sm">
                                                    <i class="fas fa-save"></i> Update Progress
                                                </button>
                                                <button type="submit" name="delete_goal" class="goals-btn goals-btn-sm goals-btn-danger" onclick="return confirm('Are you sure you want to delete this goal?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="goals-section">
                        <div class="goals-section-header">
                            <h2 class="goals-section-title"><i class="fas fa-trophy"></i> Completed Goals</h2>
                        </div>
                        
                        <div class="goals-grid">
                            <?php if (empty($completed_goals) && $goalsTableExists): ?>
                                <div class="goals-empty-state">
                                    <i class="fas fa-medal"></i>
                                    <h3>No completed goals yet</h3>
                                    <p>Complete your active goals to see them here. Keep going!</p>
                                </div>
                            <?php elseif ($goalsTableExists): ?>
                                <?php foreach ($completed_goals as $goal): ?>
                                    <?php 
                                        $color = getGoalTypeColor($goal['goal_type']);
                                        $icon = getGoalTypeIcon($goal['goal_type']);
                                    ?>
                                    <div class="goal-card completed-goal" style="border-color: <?php echo $color; ?>;">
                                        <div class="goal-header">
                                            <div class="goal-info">
                                                <h3><?php echo htmlspecialchars($goal['title']); ?></h3>
                                                <div class="goal-type" style="background-color: <?php echo $color; ?>;">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst(htmlspecialchars($goal['goal_type'])); ?>
                                                </div>
                                                <div class="goal-deadline">
                                                    <i class="fas fa-check-circle"></i> Completed on <?php echo date('M d, Y', strtotime($goal['updated_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($goal['description'])): ?>
                                            <div class="goal-description"><?php echo htmlspecialchars($goal['description']); ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="goal-progress">
                                            <div class="goal-progress-header">
                                                <span><?php echo $goal['current_value']; ?> / <?php echo $goal['target_value']; ?></span>
                                                <span>100% complete</span>
                                            </div>
                                            <div class="goal-progress-bar">
                                                <div class="goal-progress-fill" style="width: 100%; background-color: <?php echo $color; ?>;"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="goals-form-container" id="addGoalForm" style="display: <?php echo !empty($message) && $message_type === 'error' ? 'block' : 'none'; ?>">
                    <h2 class="goals-form-title"><i class="fas fa-plus-circle"></i> Add New Goal</h2>
                    
                    <form class="goals-form" method="post" action="">
                        <div class="goals-form-group">
                            <label for="title">Goal Title*</label>
                            <input type="text" name="title" id="title" required placeholder="e.g., Increase Bench Press Weight">
                        </div>
                        
                        <div class="goals-form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" placeholder="Describe your goal in detail..."></textarea>
                        </div>
                        
                        <div class="goals-form-row">
                            <div class="goals-form-group">
                                <label for="target_value">Target Value*</label>
                                <input type="number" step="0.01" name="target_value" id="target_value" required placeholder="e.g., 100">
                                <div class="goals-form-hint">The value you want to achieve (kg, reps, etc.)</div>
                            </div>
                            
                            <div class="goals-form-group">
                                <label for="current_value">Current Value</label>
                                <input type="number" step="0.01" name="current_value" id="current_value" placeholder="e.g., 80">
                                <div class="goals-form-hint">Your current progress towards this goal</div>
                            </div>
                        </div>
                        
                        <div class="goals-form-row">
                            <div class="goals-form-group">
                                <label for="goal_type">Goal Type*</label>
                                <select name="goal_type" id="goal_type" required>
                                    <option value="">Select a type</option>
                                    <option value="weight">Weight Loss/Gain</option>
                                    <option value="strength">Strength</option>
                                    <option value="endurance">Endurance</option>
                                    <option value="workout">Workout Frequency</option>
                                    <option value="nutrition">Nutrition</option>
                                </select>
                            </div>
                            
                            <div class="goals-form-group">
                                <label for="deadline">Deadline*</label>
                                <input type="date" name="deadline" id="deadline" required>
                                <div class="goals-form-hint">When do you want to achieve this goal?</div>
                            </div>
                        </div>
                        
                        <div class="goals-form-actions">
                            <button type="button" class="goals-btn goals-btn-secondary" onclick="document.getElementById('addGoalForm').style.display = 'none'">
                                Cancel
                            </button>
                            <button type="submit" name="add_goal" class="goals-btn">
                                <i class="fas fa-plus"></i> Add Goal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Set minimum date for deadline to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deadline').min = today;
        });
    </script>
</body>
</html> 