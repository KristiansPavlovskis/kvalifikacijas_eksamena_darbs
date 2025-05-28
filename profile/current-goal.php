<?php

require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/current-goal.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$result = mysqli_query($conn, "SHOW TABLES LIKE 'goal_types'");
$goalTypesTableExists = mysqli_num_rows($result) > 0;

if (!$goalTypesTableExists) {
    $createGoalTypesTable = "CREATE TABLE goal_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createGoalTypesTable)) {
        $defaultTypes = [
            ['Strength', '💪'],
            ['Cardio', '🏃'],
            ['Weight', '⚖️'],
            ['Nutrition', '🥗'],
            ['Flexibility', '🧘'],
            ['Endurance', '⏱️']
        ];
        
        foreach ($defaultTypes as $type) {
            $stmt = mysqli_prepare($conn, "INSERT INTO goal_types (name, icon) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $type[0], $type[1]);
            mysqli_stmt_execute($stmt);
        }
    }
}

$query = "SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$goals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $goals[] = $row;
}

$totalGoals = count($goals);
$completedGoals = 0;
foreach ($goals as $goal) {
    if ($goal['completed'] || ($goal['current_value'] >= $goal['target_value'])) {
        $completedGoals++;
    }
}
$completionRate = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100) : 0;

$upcomingDeadlines = array_filter($goals, function($goal) {
    return !$goal['completed'] && strtotime($goal['deadline']) > time();
});
usort($upcomingDeadlines, function($a, $b) {
    return strtotime($a['deadline']) - strtotime($b['deadline']);
});
$upcomingDeadlines = array_slice($upcomingDeadlines, 0, 8);

$goalTypeIcons = [
    'strength' => '💪',
    'cardio' => '🏃',
    'weight' => '⚖️',
    'nutrition' => '🥗',
    'flexibility' => '🧘',
    'endurance' => '⏱️',
    'other' => '🎯'
];

function getGoalIcon($goalType) {
    global $goalTypeIcons;
    $type = strtolower($goalType);
    return isset($goalTypeIcons[$type]) ? $goalTypeIcons[$type] : $goalTypeIcons['other'];
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_goal"])) {
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
        $goal_id = intval($_POST["goal_id"]);
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $target_value = floatval($_POST["target_value"]);
        $current_value = floatval($_POST["current_value"]);
        $goal_type = trim($_POST["goal_type"]);
        $deadline = $_POST["deadline"];
        $completed = isset($_POST["completed"]) ? 1 : 0;
        
        try {
            $query = "UPDATE goals SET 
                title = ?, 
                description = ?, 
                target_value = ?, 
                current_value = ?, 
                goal_type = ?,
                deadline = ?,
                completed = ?";
            
            if ($completed) {
                $query .= ", completed_at = NOW()";
            }
            
            $query .= " WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssddssiis", $title, $description, $target_value, $current_value, $goal_type, $deadline, $completed, $goal_id, $user_id);
            
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

$active_goals = [];
try {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    $goalsTableExists = mysqli_num_rows($result) > 0;
    if ($goalsTableExists) {
        $goals_per_page = 3;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $page = max(1, $page);
        $offset = ($page - 1) * $goals_per_page;
        
        $count_query = "SELECT COUNT(*) as total FROM goals WHERE user_id = ?";
        $count_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_goals = mysqli_fetch_assoc($count_result)['total'];
        $total_pages = ceil($total_goals / $goals_per_page);
        
        $query = "SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $goals_per_page, $offset);
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
    $active_goals_count = count($active_goals);
} catch (Exception $e) {
    error_log("Error fetching active goals: " . $e->getMessage());
    $message = "Error fetching active goals: " . $e->getMessage();
    $message_type = "error";
    $active_goals_count = 0;
}

$completed_goals = [];
try {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    if (mysqli_num_rows($result) > 0) {
        $query = "SELECT * FROM goals WHERE user_id = ? AND completed = 1 ORDER BY completed_at DESC, created_at DESC LIMIT 5";
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
    $completed_goals_count = count($completed_goals);
} catch (Exception $e) {
    error_log("Error fetching completed goals: " . $e->getMessage());
    $message = "Error fetching completed goals: " . $e->getMessage();
    $message_type = "error";
    $completed_goals_count = 0;
}

function calculateProgress($current, $target) {
    if ($target == 0) return 0;
    $progress = ($current / $target) * 100;
    return min(100, max(0, $progress));
}

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

$goalsTableExists = false;
try {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
    $goalsTableExists = mysqli_num_rows($result) > 0;
    
    if (!$goalsTableExists) {
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
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    <link href="../assets/css/variables.css" rel="stylesheet">
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
            width: 100%;
            overflow-x: hidden;
        }

        .dashboard {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .emoji-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            display: inline-block;
            width: 24px;
            text-align: center;
        }

        .categories {
            width: 200px;
            background-color: #1a1b26;
            padding: 20px 0;
        }
        
        .categories h3 {
            padding: 0 20px;
            margin-bottom: 15px;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #1a1b26;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .category-item:hover {
            transform: translateX(4px);
            background-color: #24273a;
        }
        
        .category-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #0f111a;
            display: grid;
            grid-template-columns: 220px 1fr 300px;
            gap: 20px;
        }

        .left-filters {
            background-color: #1a1b26;
            border-radius: 12px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .filter-category {
            margin-bottom: 30px;
        }

        .filter-category h3 {
            color: white;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 3px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #a9b1d6;
            position: relative;
            margin-bottom: 8px;
        }

        .filter-option:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .filter-option.active {
            background-color: #24273a;
            color: white;
        }

        .filter-option i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .filter-count {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #a9b1d6;
        }

        .filter-count.green {
            background-color: #73daca;
        }

        .filter-count.red {
            background-color: #f7768e;
        }

        .filter-count.blue {
            background-color: #7aa2f7;
        }

        .goal-cards {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .goal-card {
            background-color: #1a1b26;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            min-height: 150px;
            display: flex;
            flex-direction: column;
        }

        .goal-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .goals-header {
            grid-column: 1 / -1;
        }

        @media (max-width: 1200px) {
            .goal-cards {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            .goal-cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .right-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .goals-summary, .upcoming-deadlines, .suggested-goals {
            background-color: #1a1b26;
            border-radius: 12px;
            padding: 20px;
            height: fit-content;
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .goal-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #a9b1d6;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .edit-btn:hover {
            color: #7aa2f7;
        }
        
        .delete-btn:hover {
            color: #f7768e;
        }
        
        .goal-title {
            font-size: 16px;
            font-weight: 600;
            color: white;
        }
        
        .goal-description {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
        }
        
        .goal-target {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .goal-value {
            font-size: 14px;
        }
        
        .goal-percentage {
            font-size: 14px;
            font-weight: 600;
        }
        
        .goal-progress {
            margin: 16px 0;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #24273a;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #7aa2f7 0%, #bb9af7 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .goal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .goal-type {
            padding: 4px 12px;
            border-radius: 16px;
            background-color: #24273a;
            font-size: 14px;
        }

        .goals-summary h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .completion-rate {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }
        
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(#7aa2f7 var(--progress), #24273a var(--progress));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .progress-circle::before {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background-color: #1a1b26;
            border-radius: 50%;
        }

        .progress-text {
            position: relative;
            font-size: 24px;
            font-weight: bold;
            color: #c0caf5;
        }

        .upcoming-deadlines h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .upcoming-deadlines ul {
            list-style: none;
            padding: 0;
            margin: 16px 0 0 0;
        }
        
        .upcoming-deadlines li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #24273a;
        }
        
        .upcoming-deadlines li:last-child {
            border-bottom: none;
        }
        
        .deadline-goal {
            font-size: 14px;
            font-weight: 600;
        }
        
        .deadline-date {
            font-size: 14px;
            color: #a9b1d6;
        }

        .suggested-goals h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .suggested-goal {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #24273a;
            border-radius: 8px;
            margin-top: 12px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .suggested-goal:hover {
            transform: translateX(4px);
        }
        
        .goal-icon {
            font-size: 24px;
        }
        
        .goal-info h3 {
            font-size: 16px;
            margin: 0 0 4px 0;
        }
        
        .goal-info p {
            font-size: 14px;
            color: #a9b1d6;
            margin: 0;
        }

        .mobile-view {
            display: none;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr 250px;
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .categories, .main-content {
                display: none !important;
            }
            
            .mobile-view {
                display: block;
            }
            
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px;
                background-color: #1a1b26;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                position: sticky;
                top: 0;
                z-index: 10;
            }
            
            .mobile-title {
                font-size: 18px;
                font-weight: 600;
            }
            
            .mobile-filter {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
            }
            
            .mobile-progress {
                padding: 20px;
                text-align: center;
            }
            
            .progress-circle {
                width: 120px;
                height: 120px;
                margin: 0 auto 15px;
                position: relative;
            }
            
            .progress-text {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 24px;
                font-weight: 700;
            }
            
            .mobile-tabs {
                display: flex;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                margin-bottom: 20px;
            }
            
            .mobile-tab {
                flex: 1;
                text-align: center;
                padding: 10px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
            }
            
            .mobile-tab.active {
                color: #ff4d4d;
                position: relative;
            }
            
            .mobile-tab.active::after {
                content: '';
                position: absolute;
                bottom: -1px;
                left: 0;
                width: 100%;
                height: 2px;
                background-color: #ff4d4d;
            }
            
            .mobile-goal-card {
                background-color: #1a1b26;
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .mobile-goal-title {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .mobile-goal-actions {
                display: flex;
                gap: 4px;
            }
            
            .mobile-add-button {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background-color: #ff4d4d;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 100;
                border: none;
                cursor: pointer;
            }
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: #1a1b26;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-primary {
            background-color: #ff4d4d;
            color: white;
        }

        .btn-danger {
            background-color: #e63946;
            color: white;
        }

        .filter-section {
            background-color: #1a1b26;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-section h3 {
            color: #c0caf5;
            font-size: 16px;
            margin-bottom: 16px;
            font-weight: 500;
        }

        .filter-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #a9b1d6;
            position: relative;
        }

        .filter-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .filter-item.active {
            background-color: #24273a;
            color: #c0caf5;
        }

        .filter-item i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }

        .filter-count {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #a9b1d6;
        }

        .filter-count.red {
            background-color: #f7768e;
        }

        .filter-count.green {
            background-color: #73daca;
        }

        .filter-count.blue {
            background-color: #7aa2f7;
        }

        .goals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }
        
        .create-goal-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #ff4d4d;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .create-goal-btn:hover {
            background-color: #ff3333;
        }

        .goal-form-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        .goal-form {
            background-color: #1a1b26;
            border-radius: 12px;
        }
        
        .form-section {
            margin-bottom: 24px;
        }
        
        .form-section h3 {
            font-size: 16px;
            color: #a9b1d6;
            margin-bottom: 16px;
        }
        
        .goal-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .goal-type-option {
            background-color: #24273a;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
        }
        
        .goal-type-option:hover, 
        .goal-type-option.selected {
            background-color: #2a2d45;
            transform: translateY(-2px);
        }
        
        .goal-type-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .goal-type-label {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .goal-type-desc {
            font-size: 12px;
            color: #a9b1d6;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-actions {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
        }
        
        .goal-preview {
            padding: 20px;
            background-color: #1a1b26;
            border-radius: 12px;
        }
        
        .goal-preview h3 {
            font-size: 16px;
            color: #a9b1d6;
            margin-bottom: 16px;
        }
        
        .preview-card {
            background-color: #24273a;
            border-radius: 8px;
            padding: 20px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .preview-header h4 {
            font-size: 18px;
            margin: 0;
        }
        
        .status-badge {
            background-color: #ff4d4d;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .preview-progress {
            margin-bottom: 16px;
        }
        
        .preview-progress .progress-bar {
            height: 8px;
            background-color: #1a1b26;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .preview-progress .progress {
            height: 100%;
            background: linear-gradient(90deg, #ff4d4d 0%, #ff8080 100%);
            border-radius: 4px;
        }
        
        .preview-progress .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .preview-deadline {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #a9b1d6;
            font-size: 14px;
        }
        
        .preview-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .pagination {
            grid-column: 1 / -1;
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .pagination-info {
            color: #a9b1d6;
            font-size: 14px;
            text-align: center;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .pagination-button {
            background-color: #1a1b26;
            color: #c0caf5;
            border: 1px solid #24273a;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
        }
        
        .pagination-button:hover {
            background-color: #24273a;
            transform: translateY(-2px);
        }
        
        .pagination-button.active {
            background-color: #ff4d4d;
            color: white;
            border-color: #ff4d4d;
        }
        
        .pagination-prev, .pagination-next {
            padding: 8px 16px;
        }
        
        .pagination-prev i {
            margin-right: 6px;
        }
        
        .pagination-next i {
            margin-left: 6px;
        }
        
        .pagination-ellipsis {
            color: #a9b1d6;
            padding: 0 8px;
        }
        
        @media (max-width: 768px) {
            .pagination {
                margin-top: 20px;
            }
            
            .pagination-button {
                padding: 6px 10px;
                min-width: 36px;
            }
            
            .pagination-prev, .pagination-next {
                padding: 6px 10px;
            }
        }
        
        .mobile-goal-form {
            display: none;
            max-width: 100%;
            height: 100%;
            border-radius: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .mobile-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .back-button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
        }
        
        .mobile-form-steps {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 140px);
        }
        
        .form-step {
            display: none;
        }
        
        .form-step[data-step="1"] {
            display: block;
        }
        
        .goal-types-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
        }
        
        .goal-type-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #24273a;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            gap: 8px;
        }
        
        .goal-type-card:hover,
        .goal-type-card.selected {
            background-color: #2a2d45;
            transform: translateY(-2px);
        }
        
        .goal-type-card .goal-type-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .goal-type-card .goal-type-name {
            font-weight: 500;
        }
        
        .mobile-form-actions {
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            position: sticky;
            bottom: 0;
            background-color: #1a1b26;
        }
        
        .btn-continue, .btn-create {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-create {
            display: none;
        }
        
        @media (max-width: 768px) {
            .desktop-goal-form {
                display: none;
            }
            
            .mobile-goal-form {
                display: block;
            }
            
            .modal-content {
                width: 100%;
                height: 100%;
                max-width: none;
                max-height: none;
                border-radius: 0;
            }
        }

        .container-column {
            display: flex;
            flex-direction: column;
            width: -webkit-fill-available;
        }
        
        .goal-card[data-status="on-track"] .goal-type {
            background-color: #73daca;
            color: #1a1b26;
        }
        
        .goal-card[data-status="behind"] .goal-type {
            background-color: #f7768e;
            color: #1a1b26;
        }
        
        .goal-card[data-status="completed"] .goal-type {
            background-color: #7aa2f7;
            color: #1a1b26;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
            font-weight: 500;
        }
        
        .status-badge.on-track {
            background-color: #73daca;
            color: #1a1b26;
        }
        
        .status-badge.behind {
            background-color: #f7768e;
            color: #1a1b26;
        }
        
        .status-badge.completed {
            background-color: #7aa2f7;
            color: #1a1b26;
        }
        
        .emoji-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            display: inline-block;
            width: 24px;
            text-align: center;
        }
        
        .filter-option {
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 5px;
        }
        
        .filter-option.active {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }

        .no-goals, .empty-state, .no-matches {
            text-align: center;
            padding: 30px;
            background-color: #1a1b26;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .has-goals .no-goals,
        .has-goals .empty-state {
            display: none;
        }
        
        .empty-state-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #7aa2f7;
        }
        
        .empty-state-title {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .empty-state-text {
            margin-bottom: 20px;
            color: #a9b1d6;
        }
        
        @media (max-width: 768px) {
            .empty-state {
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        <div class="container-column">
        <div class="goals-header">
                    <h2>Your Active Goals</h2>
                    <button class="btn btn-primary create-goal-btn" id="addGoalBtn">
                        <i class="fas fa-plus"></i> Create New Goal
                    </button>
        </div>
        <div class="main-content">
            <div class="left-filters">
                <div class="filter-category">
                    <h3>Categories</h3>
                    <div class="filter-option active" data-filter="all">
                        <span class="filter-count"><?= count($goals) ?></span>
                        <span>All Categories</span>
                    </div>
                    <?php foreach ($goalTypeIcons as $type => $icon): ?>
                        <div class="filter-option" data-filter="<?= htmlspecialchars($type) ?>">
                            <?php 
                            $typeCount = 0;
                            foreach ($goals as $goal) {
                                if (strtolower($goal['goal_type']) === $type) {
                                    $typeCount++;
                                }
                            }
                            ?>
                            <span class="filter-count"><?= $typeCount ?></span>
                            <span><?= ucfirst(htmlspecialchars($type)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="filter-category">
                    <h3>Status</h3>
                    <div class="filter-option active" data-status="all">
                        <span class="filter-count"><?= count($goals) ?></span>
                        <span>All Status</span>
                    </div>
                    <div class="filter-option" data-status="on-track">
                        <?php
                        $onTrackCount = 0;
                        foreach ($goals as $goal) {
                            if (!$goal['completed'] && strtotime($goal['deadline']) > time()) {
                                $onTrackCount++;
                            }
                        }
                        ?>
                        <span class="filter-count"><?= $onTrackCount ?></span>
                        <span>On Track</span>
                    </div>
                    <div class="filter-option" data-status="behind">
                        <?php
                        $behindCount = 0;
                        foreach ($goals as $goal) {
                            if (!$goal['completed'] && strtotime($goal['deadline']) < time()) {
                                $behindCount++;
                            }
                        }
                        ?>
                        <span class="filter-count"><?= $behindCount ?></span>
                        <span>Behind</span>
                    </div>
                    <div class="filter-option" data-status="completed">
                        <?php
                        $completedCount = 0;
                        foreach ($goals as $goal) {
                            if ($goal['completed']) {
                                $completedCount++;
                            }
                        }
                        ?>
                        <span class="filter-count"><?= $completedCount ?></span>
                        <span>Completed</span>
                    </div>
                </div>
            </div>
            <div class="goal-cards">
                <?php if (empty($active_goals)): ?>
                    <div class="no-goals" style="display: block !important;">
                        <div class="empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="empty-state-title">You haven't set any fitness goals yet</h3>
                        <p class="empty-state-text">Setting clear goals is the first step toward achieving your fitness dreams</p>
                        <button class="btn btn-primary create-goal-btn">
                            <i class="fas fa-plus"></i> Create Your First Goal
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_goals as $goal): 
                        $progress = min(100, round(($goal['current_value'] / $goal['target_value']) * 100));
                        $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                        
                        $status = 'on-track';
                        if ($goal['completed']) {
                            $status = 'completed';
                        } elseif (strtotime($goal['deadline']) < time()) {
                            $status = 'behind';
                        }
                    ?>
                        <div class="goal-card" 
                             data-id="<?= $goal['id'] ?>" 
                             data-type="<?= strtolower($goal['goal_type']) ?>" 
                             data-completed="<?= $goal['completed'] ? 'true' : 'false' ?>"
                             data-status="<?= $status ?>"
                             onclick="viewGoal(<?= $goal['id'] ?>)">
                            <div class="goal-header">
                                <h3>
                                    <?= htmlspecialchars($goal['title']) ?>
                                    <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                                </h3>
                                <div class="goal-actions">
                                    <button class="action-btn edit-btn" onclick="editGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <span class="goal-type"><?= htmlspecialchars(ucfirst($goal['goal_type'])) ?></span>
                                </div>
                            </div>
                            <p class="goal-description"><?= htmlspecialchars($goal['description'] ?: 'No description provided') ?></p>
                            <div class="goal-progress" data-completed="<?= $goal['completed'] ? 'true' : 'false' ?>">
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?= $progress ?>%"></div>
                                </div>
                                <div class="progress-numbers">
                                    <span class="current"><?= $goal['current_value'] ?></span>
                                    <span class="target">/ <?= $goal['target_value'] ?></span>
                                </div>
                            </div>
                            <div class="goal-footer">
                                <span class="deadline">Deadline: <?= date('M d, Y', strtotime($goal['deadline'])) ?></span>
                                <span class="days-left"><?= $daysLeft ?> days left</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Showing <?= min(($page - 1) * $goals_per_page + 1, $total_goals) ?> to 
                        <?= min($page * $goals_per_page, $total_goals) ?> of <?= $total_goals ?> goals
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="pagination-button pagination-prev">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $range = 2;
                        $start_page = max(1, $page - $range);
                        $end_page = min($total_pages, $page + $range);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1" class="pagination-button">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<a href="?page=' . $i . '" class="pagination-button ' . $active_class . '">' . $i . '</a>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '" class="pagination-button">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="pagination-button pagination-next">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="right-sidebar">
                <div class="goals-summary">
                    <h2>Goals Summary</h2>
                    <div class="completion-rate">
                        <div class="progress-circle" data-progress="<?= $completionRate ?>">
                            <span class="progress-text"><?= $completionRate ?>%</span>
                        </div>
                        <p>Completion Rate</p>
                    </div>
                </div>

                <div class="upcoming-deadlines">
                    <h2>Upcoming Deadlines</h2>
                    <?php if (empty($upcomingDeadlines)): ?>
                        <p>No upcoming deadlines</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($upcomingDeadlines as $goal): 
                                $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                            ?>
                                <li>
                                    <span class="deadline-goal"><?= htmlspecialchars($goal['title']) ?></span>
                                    <span class="deadline-date"><?= $daysLeft ?> days left</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <div class="mobile-view">
            <div class="mobile-header">
                <h1 class="mobile-title">My Fitness Goals</h1>
                <div class="mobile-filter">
                    <i class="fas fa-filter"></i>
                </div>
            </div>
            
            <div class="mobile-progress">
                <div class="progress-circle" data-progress="<?= $completionRate ?>">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#2a2b36" stroke-width="12" />
                        <?php
                        $circumference = 2 * 3.14159 * 54;
                        $dashOffset = $circumference - ($circumference * $completionRate / 100);
                        ?>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#ff4d4d" stroke-width="12" 
                                stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $dashOffset ?>" />
                    </svg>
                    <div class="progress-text"><?= $completionRate ?>%</div>
                </div>
                <div>Overall Progress</div>
                <?php
                $nextMilestone = null;
                $daysToNext = null;
                foreach ($upcomingDeadlines as $goal) {
                    $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                    if ($daysToNext === null || $daysLeft < $daysToNext) {
                        $nextMilestone = $goal;
                        $daysToNext = $daysLeft;
                    }
                }
                ?>
                <?php if ($nextMilestone): ?>
                    <div>Next milestone in <?= $daysToNext ?> days</div>
                <?php else: ?>
                    <div>No upcoming milestones</div>
                <?php endif; ?>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-filter="active">Active</div>
                <div class="mobile-tab" data-filter="completed">Completed</div>
                <div class="mobile-tab" data-filter="all">All</div>
            </div>
            
            <div class="mobile-goals">
                <?php if (empty($active_goals)): ?>
                    <div class="empty-state" style="display: block !important;">
                        <div class="empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="empty-state-title">You haven't set any fitness goals yet</h3>
                        <p class="empty-state-text">Setting clear goals is the first step toward achieving your fitness dreams</p>
                        <button class="btn btn-primary" id="mobileAddGoalBtn">
                            <i class="fas fa-plus"></i> Create Your First Goal
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_goals as $goal): 
                        $progress = calculateProgress($goal['current_value'], $goal['target_value']);
                        $isCompleted = $goal['completed'] ? 'true' : 'false';
                    ?>
                        <div class="mobile-goal-card" data-completed="<?= $isCompleted ?>" onclick="viewGoal(<?= $goal['id'] ?>)">
                            <div class="mobile-goal-title">
                                <?= htmlspecialchars($goal['title']) ?>
                                <div class="mobile-goal-actions">
                                    <button class="action-btn edit-btn" onclick="editGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="goal-target">
                                <div>Current: <?= $goal['current_value'] ?></div>
                                <div>Target: <?= $goal['target_value'] ?></div>
                            </div>
                            
                            <div class="goal-target">
                                <div><?= round($progress) ?>%</div>
                            </div>
                            
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill <?= $goal['goal_type'] ?>" style="width: <?= $progress ?>%"></div>
                            </div>
                            
                            <div>
                                <i class="fas fa-clock"></i> <?= formatDeadline($goal['deadline']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button class="mobile-add-button" id="mobileAddGoalBtn">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        </div>
    </div>
    
    <div class="modal" id="addGoalModal">
        <div class="modal-content desktop-goal-form">
            <div class="modal-header">
                <h2 class="modal-title">Create New Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="goalForm">
                            <input type="hidden" name="add_goal" value="1">
                            
                            <div class="form-section">
                                <h3>Basic Information</h3>
                                <div class="form-group">
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Goal Title" required>
                                </div>
                                
                                <div class="form-group">
                                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Description"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <select id="goal_type" name="goal_type" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                                <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Goal Type</h3>
                                <div class="goal-types">
                                    <div class="goal-type-option" data-type="strength">
                                        <div class="goal-type-icon">💪</div>
                                        <div class="goal-type-label">Strength</div>
                                        <div class="goal-type-desc">Track 1RM, reps, volume</div>
                                    </div>
                                    <div class="goal-type-option" data-type="endurance">
                                        <div class="goal-type-icon">🏃</div>
                                        <div class="goal-type-label">Endurance</div>
                                        <div class="goal-type-desc">Track distance, duration</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Target Setting</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="number" id="current_value" name="current_value" class="form-control" step="0.01" placeholder="Starting Value" value="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <input type="number" id="target_value" name="target_value" class="form-control" step="0.01" placeholder="Target Value" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <select id="unit" name="unit" class="form-control">
                                        <option value="">Select Unit</option>
                                        <option value="kg">Kilograms (kg)</option>
                                        <option value="lb">Pounds (lb)</option>
                                        <option value="reps">Repetitions</option>
                                        <option value="km">Kilometers (km)</option>
                                        <option value="mi">Miles (mi)</option>
                                        <option value="min">Minutes</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Timeline</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <input type="date" id="deadline" name="deadline" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Create Goal</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="goal-preview">
                        <h3>Goal Preview</h3>
                        <div class="preview-card">
                            <div class="preview-header">
                                <h4 id="preview-title">Your Goal Title</h4>
                                <span class="status-badge">In Progress</span>
                            </div>
                            
                            <div class="preview-progress">
                                <div class="progress-bar">
                                    <div class="progress" style="width: 0%"></div>
                                </div>
                                <div class="progress-info">
                                    <span>Current: <span id="preview-current">0</span></span>
                                    <span>Target: <span id="preview-target">100</span></span>
                                </div>
                            </div>
                            
                            <div class="preview-deadline">
                                <i class="far fa-calendar-alt"></i>
                                <span>Deadline: <span id="preview-deadline">Dec 31, 2023</span></span>
                            </div>
                            
                            <div class="preview-actions">
                                <button class="btn btn-outline">
                                    <i class="fas fa-share"></i> Share
                                </button>
                                <button class="btn btn-outline">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-content mobile-goal-form">
            <div class="mobile-form-header">
                <button class="back-button" id="mobileFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2>Create New Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="mobile-form-steps">
                <div class="form-step" data-step="1">
                    <div class="form-group">
                        <input type="text" name="mobile_title" placeholder="Enter your goal title" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <textarea name="mobile_description" placeholder="Add some details about your goal" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="goal-types-grid">
                        <div class="goal-type-card" data-type="strength">
                            <div class="goal-type-icon">💪</div>
                            <div class="goal-type-name">Strength</div>
                        </div>
                        <div class="goal-type-card" data-type="endurance">
                            <div class="goal-type-icon">🏃</div>
                            <div class="goal-type-name">Endurance</div>
                        </div>
                        <div class="goal-type-card" data-type="volume">
                            <div class="goal-type-icon">📊</div>
                            <div class="goal-type-name">Volume</div>
                        </div>
                        <div class="goal-type-card" data-type="body">
                            <div class="goal-type-icon">⚖️</div>
                            <div class="goal-type-name">Body</div>
                        </div>
                        <div class="goal-type-card" data-type="consistency">
                            <div class="goal-type-icon">📆</div>
                            <div class="goal-type-name">Consistency</div>
                        </div>
                        <div class="goal-type-card" data-type="custom">
                            <div class="goal-type-icon">➕</div>
                            <div class="goal-type-name">Custom</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-step" data-step="2">
                    <h3>Set Your Target</h3>
                    
                    <div class="form-group">
                        <label>Starting Value</label>
                        <input type="number" name="mobile_current_value" class="form-control" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Target Value</label>
                        <input type="number" name="mobile_target_value" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="mobile_unit" class="form-control">
                            <option value="kg">Kilograms (kg)</option>
                            <option value="lb">Pounds (lb)</option>
                            <option value="reps">Repetitions</option>
                            <option value="km">Kilometers (km)</option>
                            <option value="min">Minutes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="mobile_start_date" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Target Date</label>
                        <input type="date" name="mobile_deadline" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="mobile-form-actions">
                <button class="btn btn-primary btn-continue" id="mobileFormContinue">Continue</button>
                <button class="btn btn-primary btn-create" id="mobileFormCreate">Create Goal</button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="editGoalModal">
        <div class="modal-content desktop-goal-form">
            <div class="modal-header">
                <h2 class="modal-title">Edit Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="editGoalForm">
                            <input type="hidden" name="update_goal" value="1">
                            <input type="hidden" name="goal_id" id="edit_goal_id">
                            
                            <div class="form-section">
                                <h3>Basic Information</h3>
                                <div class="form-group">
                                    <input type="text" id="edit_title" name="title" class="form-control" placeholder="Goal Title" required>
                                </div>
                                
                                <div class="form-group">
                                    <textarea id="edit_description" name="description" class="form-control" rows="3" placeholder="Description"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <select id="edit_goal_type" name="goal_type" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                                <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Target Setting</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="number" id="edit_current_value" name="current_value" class="form-control" step="0.01" placeholder="Current Value" value="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <input type="number" id="edit_target_value" name="target_value" class="form-control" step="0.01" placeholder="Target Value" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Timeline</h3>
                                <div class="form-group">
                                    <input type="date" id="edit_deadline" name="deadline" class="form-control" required>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="edit_completed" name="completed" value="1">
                                        <label for="edit_completed">Mark as completed</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update Goal</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="goal-preview">
                        <h3>Goal Preview</h3>
                        <div class="preview-card">
                            <div class="preview-header">
                                <h4 id="edit-preview-title">Your Goal Title</h4>
                                <span class="status-badge">In Progress</span>
                            </div>
                            
                            <div class="preview-progress">
                                <div class="progress-bar">
                                    <div class="progress" style="width: 0%"></div>
                                </div>
                                <div class="progress-info">
                                    <span>Current: <span id="edit-preview-current">0</span></span>
                                    <span>Target: <span id="edit-preview-target">100</span></span>
                                </div>
                            </div>
                            
                            <div class="preview-deadline">
                                <i class="far fa-calendar-alt"></i>
                                <span>Deadline: <span id="edit-preview-deadline">Not set</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-content mobile-goal-form">
            <div class="mobile-form-header">
                <button class="back-button" id="mobileEditFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2>Edit Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="mobile-form-steps">
                <div class="form-step" data-step="1">
                    <div class="form-group">
                        <label>Goal Title</label>
                        <input type="text" name="mobile_edit_title" id="mobile_edit_title" placeholder="Enter your goal title" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="mobile_edit_description" id="mobile_edit_description" placeholder="Add some details about your goal" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="mobile_edit_goal_type" id="mobile_edit_goal_type" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-step" data-step="2">
                    <h3>Set Your Target</h3>
                    
                    <div class="form-group">
                        <label>Current Value</label>
                        <input type="number" name="mobile_edit_current_value" id="mobile_edit_current_value" class="form-control" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Target Value</label>
                        <input type="number" name="mobile_edit_target_value" id="mobile_edit_target_value" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Target Date</label>
                        <input type="date" name="mobile_edit_deadline" id="mobile_edit_deadline" class="form-control">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="mobile_edit_completed" name="mobile_edit_completed">
                            <label for="mobile_edit_completed">Mark as completed</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mobile-form-actions">
                <button class="btn btn-primary btn-continue" id="mobileEditFormContinue">Continue</button>
                <button class="btn btn-primary btn-update" id="mobileEditFormUpdate">Update Goal</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('.modal');
            const addGoalBtn = document.getElementById('addGoalBtn');
            const mobileAddGoalBtn = document.getElementById('mobileAddGoalBtn');
            const startNewGoalBtn = document.getElementById('startNewGoalBtn');
            const updateGoalBtns = document.querySelectorAll('.update-goal-btn');
            const deleteGoalBtns = document.querySelectorAll('.delete-goal-btn');
            const closeBtns = document.querySelectorAll('.modal-close, .close-modal');
            
            let currentCategoryFilter = 'all';
            let currentStatusFilter = 'all';
            
            const goalCards = document.querySelectorAll('.goal-card');
            
            function filterGoals() {
                if (goalCards.length === 0) {
                    return;
                }
                
                goalCards.forEach(card => {
                    const goalType = card.getAttribute('data-type');
                    const status = card.getAttribute('data-status');
                    
                    const matchesCategory = currentCategoryFilter === 'all' || goalType === currentCategoryFilter;
                    const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;
                    
                    if (matchesCategory && matchesStatus) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                const visibleGoals = Array.from(goalCards).filter(card => card.style.display !== 'none');
                const noGoalsElement = document.querySelector('.no-goals');
                
                if (noGoalsElement && visibleGoals.length === 0 && goalCards.length > 0) {
                    const filterNoMatchElement = createNoGoalsElement();
                    noGoalsElement.style.display = 'none';
                    filterNoMatchElement.style.display = 'block';
                }
                
                console.log(`Filtered by category: ${currentCategoryFilter}, status: ${currentStatusFilter}`);
                console.log(`Visible goals: ${visibleGoals.length} of ${goalCards.length}`);
            }
            
            function createNoGoalsElement() {
                const noMatchElement = document.createElement('div');
                noMatchElement.className = 'no-matches';
                noMatchElement.style.padding = '20px';
                noMatchElement.style.textAlign = 'center';
                noMatchElement.style.backgroundColor = '#1a1b26';
                noMatchElement.style.borderRadius = '12px';
                noMatchElement.style.margin = '10px 0';
                
                let message = 'No goals match your current filters.';
                
                if (currentCategoryFilter !== 'all' && currentStatusFilter !== 'all') {
                    message = `No ${currentStatusFilter} goals found in the ${currentCategoryFilter} category.`;
                } else if (currentCategoryFilter !== 'all') {
                    message = `No goals found in the ${currentCategoryFilter} category.`;
                } else if (currentStatusFilter !== 'all') {
                    message = `No ${currentStatusFilter} goals found.`;
                }
                
                noMatchElement.innerHTML = `
                    <p>${message}</p>
                    <p>Try changing your filters or <a href="#" onclick="document.getElementById('addGoalBtn').click(); return false;">add a new goal</a>.</p>
                `;
                
                const existingNoMatch = document.querySelector('.no-matches');
                if (existingNoMatch) {
                    existingNoMatch.remove();
                }
                
                document.querySelector('.goal-cards').appendChild(noMatchElement);
                return noMatchElement;
            }
            
            const categoryFilters = document.querySelectorAll('.filter-category:nth-child(1) .filter-option');
            const statusFilters = document.querySelectorAll('.filter-category:nth-child(2) .filter-option');
            
            categoryFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    categoryFilters.forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    currentCategoryFilter = this.getAttribute('data-filter');
                    filterGoals();
                });
            });
            
            statusFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    statusFilters.forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    currentStatusFilter = this.getAttribute('data-status');

                    filterGoals();
                });
            });
            
            goalCards.forEach(card => {
                const completed = card.querySelector('.goal-progress').getAttribute('data-completed') === 'true';
                card.setAttribute('data-completed', completed);
                
                const goalType = card.querySelector('.goal-type').textContent.trim().toLowerCase();
                card.setAttribute('data-type', goalType);
            });
            
            const goalCardsContainer = document.querySelector('.goal-cards');
            if (goalCardsContainer && goalCards.length > 0) {
                goalCardsContainer.classList.add('has-goals');
            }
            
            filterGoals();
            
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            function closeAllModals() {
                modals.forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
            
            const addGoalButtons = [
                document.getElementById('addGoalBtn'),
                document.getElementById('mobileAddGoalBtn'),
                document.getElementById('startNewGoalBtn'),
                document.querySelector('.empty-state .btn')
            ];
            
            addGoalButtons.forEach(btn => {
                if (btn) {
                    btn.addEventListener('click', () => openModal('addGoalModal'));
                }
            });
            
            updateGoalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const goalId = this.dataset.goalId;
                    document.getElementById('update_goal_id').value = goalId;
                    openModal('updateGoalModal');
                });
            });
            
            deleteGoalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const goalId = this.dataset.goalId;
                    document.getElementById('delete_goal_id').value = goalId;
                    openModal('deleteGoalModal');
                });
            });
            
            closeBtns.forEach(btn => {
                btn.addEventListener('click', closeAllModals);
            });
            
            window.addEventListener('click', function(event) {
                modals.forEach(modal => {
                    if (event.target === modal) {
                        closeAllModals();
                    }
                });
            });
            
            const mobileTabs = document.querySelectorAll('.mobile-tab');
            mobileTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    mobileTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    console.log(`Selected tab: ${this.textContent}, filter: ${filter}`);
                    
                    const mobileGoalCards = document.querySelectorAll('.mobile-goal-card');
                    mobileGoalCards.forEach(card => {
                        const isCompleted = card.hasAttribute('data-completed') && card.getAttribute('data-completed') === 'true';
                        
                        if (filter === 'all') {
                            card.style.display = 'block';
                        } else if (filter === 'active' && !isCompleted) {
                            card.style.display = 'block';
                        } else if (filter === 'completed' && isCompleted) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    const visibleGoals = Array.from(mobileGoalCards).filter(card => card.style.display !== 'none');
                    const emptyState = document.querySelector('.mobile-goals .empty-state');
                    
                    if (emptyState) {
                        if (visibleGoals.length === 0 && mobileGoalCards.length > 0) {
                            let message = 'No goals match your filter.';
                            
                            if (filter === 'active') {
                                message = 'You don\'t have any active goals.';
                            } else if (filter === 'completed') {
                                message = 'You haven\'t completed any goals yet.';
                            }
                            
                            const emptyTitle = emptyState.querySelector('.empty-state-title');
                            if (emptyTitle) {
                                emptyTitle.textContent = message;
                            }
                            
                            emptyState.style.display = 'block';
                        } else if (mobileGoalCards.length === 0) {
                            emptyState.style.display = 'block';
                        } else {
                            emptyState.style.display = 'none';
                        }
                    }
                });
            });
            
            const progressCircle = document.querySelector('.progress-circle');
            if (progressCircle) {
                const progress = progressCircle.dataset.progress;
                progressCircle.style.setProperty('--progress', `${progress}%`);
            }

            const categoryItems = document.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                item.addEventListener('click', function() {
                    const category = this.dataset.category.toLowerCase();
                    console.log(`Filtering by category: ${category}`);
                    
                    categoryItems.forEach(cat => cat.classList.remove('active'));
                    this.classList.add('active');

                });
            });

            const suggestedGoals = document.querySelectorAll('.suggested-goal');
            suggestedGoals.forEach(goal => {
                goal.addEventListener('click', function() {
                    const goalData = this.dataset;
                    const addGoalModal = document.getElementById('addGoalModal');
                    if (addGoalModal) {
                        addGoalModal.querySelector('[name="title"]').value = goalData.title;
                        addGoalModal.querySelector('[name="target_value"]').value = goalData.target;
                        addGoalModal.querySelector('[name="unit"]').value = goalData.unit;
                        addGoalModal.querySelector('[name="goal_type"]').value = goalData.type;
                        
                        const defaultDeadline = new Date();
                        defaultDeadline.setDate(defaultDeadline.getDate() + 30);
                        const deadlineInput = addGoalModal.querySelector('[name="deadline"]');
                        if (deadlineInput) {
                            deadlineInput.value = defaultDeadline.toISOString().split('T')[0];
                        }
                        
                        addGoalModal.style.display = 'block';
                    }
                });
            });

            const filterItems = document.querySelectorAll('.filter-item');
            filterItems.forEach(item => {
                item.addEventListener('click', function() {
                    const siblings = item.parentElement.querySelectorAll('.filter-item');
                    siblings.forEach(sibling => sibling.classList.remove('active'));
                    
                    item.classList.add('active');

                    const category = document.querySelector('.filter-section:nth-child(1) .filter-item.active')?.textContent.trim();
                    const timePeriod = document.querySelector('.filter-section:nth-child(2) .filter-item.active')?.textContent.trim();
                    const status = document.querySelector('.filter-section:nth-child(3) .filter-item.active')?.textContent.trim();

                    console.log('Filtering by:', { category, timePeriod, status });
                });
            });

            function updateStatusCounts() {
                const goals = <?= json_encode($goals) ?>;
                let onTrack = 0;
                let behind = 0;
                let completed = 0;

                goals.forEach(goal => {
                    const progress = (goal.current_value / goal.target_value) * 100;
                    const deadline = new Date(goal.deadline);
                    const now = new Date();
                    const totalDays = (deadline - new Date(goal.start_date)) / (1000 * 60 * 60 * 24);
                    const daysElapsed = (now - new Date(goal.start_date)) / (1000 * 60 * 60 * 24);
                    const expectedProgress = (daysElapsed / totalDays) * 100;

                    if (goal.completed) {
                        completed++;
                    } else if (progress >= expectedProgress) {
                        onTrack++;
                    } else {
                        behind++;
                    }
                });

                const onTrackFilter = document.querySelector('.filter-option[data-status="on-track"]');
                const behindFilter = document.querySelector('.filter-option[data-status="behind"]');
                const completedFilter = document.querySelector('.filter-option[data-status="completed"]');
                
                if (onTrackFilter) onTrackFilter.querySelector('.filter-count').textContent = onTrack;
                if (behindFilter) behindFilter.querySelector('.filter-count').textContent = behind;
                if (completedFilter) completedFilter.querySelector('.filter-count').textContent = completed;
            }

            updateStatusCounts();

            const titleInput = document.getElementById('title');
            const currentValueInput = document.getElementById('current_value');
            const targetValueInput = document.getElementById('target_value');
            const deadlineInput = document.getElementById('deadline');
            
            const previewTitle = document.getElementById('preview-title');
            const previewCurrent = document.getElementById('preview-current');
            const previewTarget = document.getElementById('preview-target');
            const previewDeadline = document.getElementById('preview-deadline');
            
            if (titleInput && previewTitle) {
                titleInput.addEventListener('input', function() {
                    previewTitle.textContent = this.value || 'Your Goal Title';
                });
            }
            
            if (currentValueInput && previewCurrent) {
                currentValueInput.addEventListener('input', function() {
                    previewCurrent.textContent = this.value || '0';
                    updatePreviewProgress();
                });
            }
            
            if (targetValueInput && previewTarget) {
                targetValueInput.addEventListener('input', function() {
                    previewTarget.textContent = this.value || '100';
                    updatePreviewProgress();
                });
            }
            
            if (deadlineInput && previewDeadline) {
                deadlineInput.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        previewDeadline.textContent = date.toLocaleDateString('en-US', { 
                            month: 'short', 
                            day: 'numeric', 
                            year: 'numeric' 
                        });
                    } else {
                        previewDeadline.textContent = 'Not set';
                    }
                });
            }
            
            function updatePreviewProgress() {
                const current = parseFloat(previewCurrent.textContent) || 0;
                const target = parseFloat(previewTarget.textContent) || 100;
                let progress = (current / target) * 100;
                progress = Math.min(100, Math.max(0, progress));
                
                document.querySelector('.preview-progress .progress').style.width = progress + '%';
            }
            
            const goalTypeOptions = document.querySelectorAll('.goal-type-option');
            goalTypeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    goalTypeOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('goal_type').value = this.dataset.type;
                });
            });
            
            const mobileFormSteps = document.querySelectorAll('.form-step');
            const mobileFormContinue = document.getElementById('mobileFormContinue');
            const mobileFormCreate = document.getElementById('mobileFormCreate');
            const mobileFormBack = document.getElementById('mobileFormBack');
            
            let currentStep = 1;
            
            if (mobileFormContinue) {
                mobileFormContinue.addEventListener('click', function() {
                    const nextStep = currentStep + 1;
                    if (nextStep <= 2) {
                        showStep(nextStep);
                    }
                });
            }
            
            if (mobileFormBack) {
                mobileFormBack.addEventListener('click', function() {
                    if (currentStep > 1) {
                        showStep(currentStep - 1);
                    } else {
                        closeAllModals();
                    }
                });
            }
            
            function showStep(step) {
                mobileFormSteps.forEach(formStep => {
                    formStep.style.display = 'none';
                });
                
                const stepToShow = document.querySelector(`.form-step[data-step="${step}"]`);
                if (stepToShow) {
                    stepToShow.style.display = 'block';
                    currentStep = step;
                    
                    if (currentStep === 2) {
                        mobileFormContinue.style.display = 'none';
                        mobileFormCreate.style.display = 'block';
                    } else {
                        mobileFormContinue.style.display = 'block';
                        mobileFormCreate.style.display = 'none';
                    }
                }
            }
            
            const goalTypeCards = document.querySelectorAll('.goal-type-card');
            goalTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    goalTypeCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
            
            if (mobileFormCreate) {
                mobileFormCreate.addEventListener('click', function() {
                    const mobileTitle = document.querySelector('input[name="mobile_title"]').value;
                    const mobileDescription = document.querySelector('textarea[name="mobile_description"]').value;
                    const mobileType = document.querySelector('.goal-type-card.selected')?.dataset.type || '';
                    const mobileCurrent = document.querySelector('input[name="mobile_current_value"]').value;
                    const mobileTarget = document.querySelector('input[name="mobile_target_value"]').value;
                    const mobileUnit = document.querySelector('select[name="mobile_unit"]').value;
                    const mobileStartDate = document.querySelector('input[name="mobile_start_date"]').value;
                    const mobileDeadline = document.querySelector('input[name="mobile_deadline"]').value;
                    
                    document.getElementById('title').value = mobileTitle;
                    document.getElementById('description').value = mobileDescription;
                    document.getElementById('goal_type').value = mobileType;
                    document.getElementById('current_value').value = mobileCurrent;
                    document.getElementById('target_value').value = mobileTarget;
                    if (document.getElementById('unit')) {
                        document.getElementById('unit').value = mobileUnit;
                    }
                    if (document.getElementById('start_date')) {
                        document.getElementById('start_date').value = mobileStartDate;
                    }
                    document.getElementById('deadline').value = mobileDeadline;
                    
                    document.getElementById('goalForm').submit();
                });
            }
            
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];
            dateInputs.forEach(input => {
                if (input.id === 'start_date' || input.name === 'mobile_start_date') {
                    input.value = today;
                } else {
                    const defaultDeadline = new Date();
                    defaultDeadline.setDate(defaultDeadline.getDate() + 30);
                    input.value = defaultDeadline.toISOString().split('T')[0];
                }
            });

            window.viewGoal = function(goalId) {
                window.location.href = `goal-details.php?id=${goalId}`;
            };
            
            window.editGoal = function(goalId) {
                const modal = document.getElementById('editGoalModal');
                if (modal) {
                    document.getElementById('edit_goal_id').value = goalId;
                    
                    fetch(`get-goal.php?id=${goalId}`)
                        .then(response => response.json())
                        .then(goal => {
                            document.getElementById('edit_title').value = goal.title;
                            document.getElementById('edit_description').value = goal.description || '';
                            document.getElementById('edit_goal_type').value = goal.goal_type;
                            document.getElementById('edit_current_value').value = goal.current_value;
                            document.getElementById('edit_target_value').value = goal.target_value;
                            document.getElementById('edit_deadline').value = goal.deadline;
                            document.getElementById('edit_completed').checked = goal.completed == 1;
                            
                            document.getElementById('mobile_edit_title').value = goal.title;
                            document.getElementById('mobile_edit_description').value = goal.description || '';
                            document.getElementById('mobile_edit_goal_type').value = goal.goal_type;
                            document.getElementById('mobile_edit_current_value').value = goal.current_value;
                            document.getElementById('mobile_edit_target_value').value = goal.target_value;
                            document.getElementById('mobile_edit_deadline').value = goal.deadline;
                            document.getElementById('mobile_edit_completed').checked = goal.completed == 1;
                            
                            document.getElementById('edit-preview-title').textContent = goal.title;
                            document.getElementById('edit-preview-current').textContent = goal.current_value;
                            document.getElementById('edit-preview-target').textContent = goal.target_value;
                            
                            const date = new Date(goal.deadline);
                            document.getElementById('edit-preview-deadline').textContent = date.toLocaleDateString('en-US', { 
                                month: 'short', 
                                day: 'numeric', 
                                year: 'numeric' 
                            });
                            
                            const progress = Math.min(100, (goal.current_value / goal.target_value) * 100) || 0;
                            document.querySelector('#editGoalModal .preview-progress .progress').style.width = progress + '%';
                            
                            showEditStep(1);
                            
                            modal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        })
                        .catch(error => {
                            console.error('Error fetching goal details:', error);
                            alert('Failed to load goal details. Please try again.');
                        });
                }
            };
            
            window.deleteGoal = function(goalId) {
                if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
                    const formData = new FormData();
                    formData.append('delete_goal', '1');
                    formData.append('goal_id', goalId);
                    
                    fetch('current-goal.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            throw new Error('Failed to delete goal');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting goal:', error);
                        alert('Failed to delete goal. Please try again.');
                    });
                }
            };
            
            function populateEditForm(goal) {
                const titleInput = document.querySelector('#updateGoalModal input[name="title"]');
                const descriptionInput = document.querySelector('#updateGoalModal textarea[name="description"]');
                const currentValueInput = document.querySelector('#updateGoalModal input[name="current_value"]');
                const targetValueInput = document.querySelector('#updateGoalModal input[name="target_value"]');
                const goalTypeSelect = document.querySelector('#updateGoalModal select[name="goal_type"]');
                const deadlineInput = document.querySelector('#updateGoalModal input[name="deadline"]');
                
                if (titleInput) titleInput.value = goal.title;
                if (descriptionInput) descriptionInput.value = goal.description;
                if (currentValueInput) currentValueInput.value = goal.current_value;
                if (targetValueInput) targetValueInput.value = goal.target_value;
                if (goalTypeSelect) goalTypeSelect.value = goal.goal_type;
                if (deadlineInput) deadlineInput.value = goal.deadline;
            }

            const editTitleInput = document.getElementById('edit_title');
            const editCurrentValueInput = document.getElementById('edit_current_value');
            const editTargetValueInput = document.getElementById('edit_target_value');
            const editDeadlineInput = document.getElementById('edit_deadline');
            
            const editPreviewTitle = document.getElementById('edit-preview-title');
            const editPreviewCurrent = document.getElementById('edit-preview-current');
            const editPreviewTarget = document.getElementById('edit-preview-target');
            const editPreviewDeadline = document.getElementById('edit-preview-deadline');
            
            if (editTitleInput && editPreviewTitle) {
                editTitleInput.addEventListener('input', function() {
                    editPreviewTitle.textContent = this.value || 'Your Goal Title';
                });
            }
            
            if (editCurrentValueInput && editPreviewCurrent) {
                editCurrentValueInput.addEventListener('input', function() {
                    editPreviewCurrent.textContent = this.value || '0';
                    updateEditPreviewProgress();
                });
            }
            
            if (editTargetValueInput && editPreviewTarget) {
                editTargetValueInput.addEventListener('input', function() {
                    editPreviewTarget.textContent = this.value || '100';
                    updateEditPreviewProgress();
                });
            }
            
            if (editDeadlineInput && editPreviewDeadline) {
                editDeadlineInput.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        editPreviewDeadline.textContent = date.toLocaleDateString('en-US', { 
                            month: 'short', 
                            day: 'numeric', 
                            year: 'numeric' 
                        });
                    } else {
                        editPreviewDeadline.textContent = 'Not set';
                    }
                });
            }
            
            function updateEditPreviewProgress() {
                const current = parseFloat(editPreviewCurrent.textContent) || 0;
                const target = parseFloat(editPreviewTarget.textContent) || 100;
                let progress = (current / target) * 100;
                progress = Math.min(100, Math.max(0, progress));
                
                document.querySelector('#editGoalModal .preview-progress .progress').style.width = progress + '%';
            }

            const filterOptions = document.querySelectorAll('.filter-option');
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const siblings = this.parentElement.querySelectorAll('.filter-option');
                    siblings.forEach(sibling => sibling.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    console.log('Filter selected:', this.querySelector('span').textContent);

                });
            });

            const mobileGoalsContainer = document.querySelector('.mobile-goals');
            if (mobileGoalsContainer && document.querySelectorAll('.mobile-goal-card').length > 0) {
                mobileGoalsContainer.classList.add('has-goals');
            }

            const mobileEditFormContinue = document.getElementById('mobileEditFormContinue');
            const mobileEditFormUpdate = document.getElementById('mobileEditFormUpdate');
            const mobileEditFormBack = document.getElementById('mobileEditFormBack');
            let currentEditStep = 1;
            
            if (mobileEditFormContinue) {
                mobileEditFormContinue.addEventListener('click', function() {
                    const nextStep = currentEditStep + 1;
                    if (nextStep <= 2) {
                        showEditStep(nextStep);
                    }
                });
            }
            
            if (mobileEditFormBack) {
                mobileEditFormBack.addEventListener('click', function() {
                    if (currentEditStep > 1) {
                        showEditStep(currentEditStep - 1);
                    } else {
                        closeAllModals();
                    }
                });
            }
            
            function showEditStep(step) {
                const editFormSteps = document.querySelectorAll('#editGoalModal .form-step');
                editFormSteps.forEach(formStep => {
                    formStep.style.display = 'none';
                });
                
                const stepToShow = document.querySelector(`#editGoalModal .form-step[data-step="${step}"]`);
                if (stepToShow) {
                    stepToShow.style.display = 'block';
                    currentEditStep = step;
                    
                    if (currentEditStep === 2) {
                        mobileEditFormContinue.style.display = 'none';
                        mobileEditFormUpdate.style.display = 'block';
                    } else {
                        mobileEditFormContinue.style.display = 'block';
                        mobileEditFormUpdate.style.display = 'none';
                    }
                }
            }
            
            if (mobileEditFormUpdate) {
                mobileEditFormUpdate.addEventListener('click', function() {
                    document.getElementById('edit_title').value = document.getElementById('mobile_edit_title').value;
                    document.getElementById('edit_description').value = document.getElementById('mobile_edit_description').value;
                    document.getElementById('edit_goal_type').value = document.getElementById('mobile_edit_goal_type').value;
                    document.getElementById('edit_current_value').value = document.getElementById('mobile_edit_current_value').value;
                    document.getElementById('edit_target_value').value = document.getElementById('mobile_edit_target_value').value;
                    document.getElementById('edit_deadline').value = document.getElementById('mobile_edit_deadline').value;
                    document.getElementById('edit_completed').checked = document.getElementById('mobile_edit_completed').checked;
                    
                    document.getElementById('editGoalForm').submit();
                });
            }

            const activeTab = document.querySelector('.mobile-tab.active');
            if (activeTab) {
                const defaultFilter = activeTab.getAttribute('data-filter');
                const mobileGoalCards = document.querySelectorAll('.mobile-goal-card');
                mobileGoalCards.forEach(card => {
                    const isCompleted = card.hasAttribute('data-completed') && card.getAttribute('data-completed') === 'true';
                    
                    if (defaultFilter === 'all') {
                        card.style.display = 'block';
                    } else if (defaultFilter === 'active' && !isCompleted) {
                        card.style.display = 'block';
                    } else if (defaultFilter === 'completed' && isCompleted) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
    </script> 
</body>
</html> 