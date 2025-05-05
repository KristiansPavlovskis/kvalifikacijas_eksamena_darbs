<?php

require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/current-goal.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];

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
        $current_value = floatval($_POST["current_value"]);
        $completed = isset($_POST["completed"]) ? 1 : 0;
        
        try {
            $query = "UPDATE goals SET current_value = ?, completed = ?";
            
            if ($completed) {
                $query .= ", completed_at = NOW()";
            }
            
            $query .= " WHERE id = ? AND user_id = ?";
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

        .metrics-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .metric-card {
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
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .metric-icon {
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

        .metric-icon.active {
            background: var(--gradient-purple);
        }

        .metric-icon.completed {
            background: var(--gradient-green);
        }

        .metric-icon.success {
            background: var(--gradient-pink);
        }

        .metric-icon.streak {
            background: var(--gradient-blue);
        }

        .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .metric-card::before {
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
        }

        .section-action:hover {
            background-color: rgba(157, 78, 221, 0.15);
            transform: translateX(3px);
        }

        .section-body {
            padding: 25px 30px;
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .goal-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 20px;
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }

        .goal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 4px;
            background: var(--gradient-purple);
        }
        
        .goal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .goal-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
        }

        .goal-type-weight .goal-icon { background: var(--gradient-blue); }
        .goal-type-strength .goal-icon { background: var(--gradient-purple); }
        .goal-type-endurance .goal-icon { background: var(--gradient-pink); }
        .goal-type-workout .goal-icon { background: var(--gradient-green); }
        .goal-type-nutrition .goal-icon { background: linear-gradient(135deg, #ff9e00, #ffcc00); }

        .goal-info {
            flex: 1;
        }

        .goal-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .goal-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--gray-light);
            margin-bottom: 5px;
        }

        .goal-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }

        .goal-badge.weight { background-color: #4361ee; }
        .goal-badge.strength { background-color: #9d4edd; }
        .goal-badge.endurance { background-color: #f72585; }
        .goal-badge.workout { background-color: #06d6a0; }
        .goal-badge.nutrition { background-color: #ff9e00; }
        
        .goal-deadline {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: var(--gray-light);
        }
        
        .goal-progress {
            margin: 15px 0;
        }
        
        .goal-progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .goal-progress-bar {
            height: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .goal-progress-fill {
            height: 100%;
            border-radius: 4px;
            position: relative;
        }

        .goal-type-weight .goal-progress-fill { background: var(--gradient-blue); }
        .goal-type-strength .goal-progress-fill { background: var(--gradient-purple); }
        .goal-type-endurance .goal-progress-fill { background: var(--gradient-pink); }
        .goal-type-workout .goal-progress-fill { background: var(--gradient-green); }
        .goal-type-nutrition .goal-progress-fill { background: linear-gradient(135deg, #ff9e00, #ffcc00); }

        .goal-progress-fill::after {
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

        .goal-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .update-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
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

        .btn-danger {
            background: linear-gradient(135deg, #ef476f, #e63946);
            color: white;
        }

        .btn-danger:hover {
            box-shadow: 0 8px 20px rgba(239, 71, 111, 0.3);
            transform: translateY(-3px);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
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

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .message.success {
            background-color: rgba(6, 214, 160, 0.1);
            border-left: 4px solid var(--success);
        }
        
        .message.error {
            background-color: rgba(239, 71, 111, 0.1);
            border-left: 4px solid var(--danger);
        }

        .message-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .message.success .message-icon {
            background-color: var(--success);
            color: white;
        }

        .message.error .message-icon {
            background-color: var(--danger);
            color: white;
        }

        .message-content {
            flex: 1;
        }

        .message-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .recommendation-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .recommendation-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            width: 100%;
            background: var(--gradient-purple);
        }

        .recommendation-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .recommendation-card h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: white;
        }

        .recommendation-card p {
            color: var(--gray-light);
            font-size: 0.9rem;
            margin-bottom: 15px;
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

        .completed-goals {
            opacity: 0.7;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
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

        @media (max-width: 1200px) {
            .metrics-container {
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
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .mobile-nav {
                display: block;
            }

            .main-content {
                padding-bottom: 70px;
            }
        }

        @media (max-width: 768px) {
            .metrics-container {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .section-action {
                align-self: flex-end;
            }
            
            .goals-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
                <h1 class="page-title">Fitness Goals</h1>
                <div class="page-actions">
                    <button class="btn btn-primary" id="addGoalBtn">
                        <i class="fas fa-plus"></i> Add New Goal
                    </button>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <div class="message-icon">
                        <i class="fas <?= $message_type === 'success' ? 'fa-check' : 'fa-exclamation-triangle' ?>"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-title"><?= $message_type === 'success' ? 'Success!' : 'Error!' ?></div>
                        <div><?= $message ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="metrics-container">
                <div class="metric-card">
                    <div class="metric-icon active">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="metric-value"><?= $active_goals_count ?></div>
                    <div class="metric-label">Active Goals</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon completed">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="metric-value"><?= $completed_goals_count ?></div>
                    <div class="metric-label">Completed Goals</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon success">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="metric-value"><?= isset($success_rate) ? $success_rate : '0' ?>%</div>
                    <div class="metric-label">Success Rate</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon streak">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="metric-value">
                        <?php 
                            $now = new DateTime();
                            $earliest_active_deadline = null;
                            foreach ($active_goals as $goal) {
                                $deadline = new DateTime($goal['deadline']);
                                if ($earliest_active_deadline === null || $deadline < $earliest_active_deadline) {
                                    $earliest_active_deadline = $deadline;
                                }
                            }
                            
                            echo $earliest_active_deadline ? $now->diff($earliest_active_deadline)->days : '0';
                        ?>
                    </div>
                    <div class="metric-label">Days to Next Deadline</div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div>
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-tasks"></i> Active Goals
                            </h2>
                            <button class="section-action" id="viewActiveGoals">
                                View All <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="section-body">
                            <?php if (empty($active_goals) && $goalsTableExists): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                    <i class="fas fa-bullseye"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Active Goals</h3>
                                    <p class="empty-state-text">Set meaningful goals to track your fitness progress and stay motivated.</p>
                                    <button class="btn btn-primary" id="startNewGoalBtn">
                                        <i class="fas fa-plus"></i> Create Your First Goal
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="goals-grid">
                                <?php foreach ($active_goals as $goal): ?>
                                    <?php 
                                        $progress = calculateProgress($goal['current_value'], $goal['target_value']);
                                            $goal_type_class = 'goal-type-' . $goal['goal_type'];
                                    ?>
                                        <div class="goal-card <?= $goal_type_class ?>">
                                        <div class="goal-header">
                                            <div class="goal-info">
                                                    <h3 class="goal-title"><?= htmlspecialchars($goal['title']) ?></h3>
                                                    <div class="goal-meta">
                                                        <span class="goal-badge <?= $goal['goal_type'] ?>">
                                                            <?= ucfirst($goal['goal_type']) ?>
                                                        </span>
                                                </div>
                                                <div class="goal-deadline">
                                                        <i class="fas fa-clock"></i> <?= formatDeadline($goal['deadline']) ?>
                                                </div>
                                            </div>
                                                <div class="goal-icon">
                                                    <i class="fas <?= getGoalTypeIcon($goal['goal_type']) ?>"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="goal-progress">
                                                <div class="goal-progress-stats">
                                                    <span><?= $goal['current_value'] ?> / <?= $goal['target_value'] ?></span>
                                                    <span><?= round($progress) ?>%</span>
                                            </div>
                                            <div class="goal-progress-bar">
                                                    <div class="goal-progress-fill" style="width: <?= $progress ?>%"></div>
                                            </div>
                                        </div>
                                        
                                            <div class="goal-actions">
                                                <button class="btn btn-secondary btn-sm update-goal-btn" data-goal-id="<?= $goal['id'] ?>">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-goal-btn" data-goal-id="<?= $goal['id'] ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-layer-group"></i> Goal Categories
                            </h2>
                        </div>
                        
                        <div class="section-body">
                        <div class="goals-grid">
                                <div class="goal-card goal-type-weight" style="cursor: pointer;" onclick="filterGoalsByType('weight')">
                                    <div class="goal-header">
                                        <div class="goal-info">
                                            <h3 class="goal-title">Weight Goals</h3>
                                            <p class="goal-meta">Track weight loss or gain progress</p>
                                </div>
                                        <div class="goal-icon">
                                            <i class="fas fa-weight"></i>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                        $weightGoalsCount = 0;
                                        foreach ($active_goals as $goal) {
                                            if ($goal['goal_type'] == 'weight') $weightGoalsCount++;
                                        }
                                    ?>
                                    
                                    <div class="goal-progress">
                                        <div class="goal-progress-stats">
                                            <span><?= $weightGoalsCount ?> active goals</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="goal-card goal-type-strength" style="cursor: pointer;" onclick="filterGoalsByType('strength')">
                                        <div class="goal-header">
                                            <div class="goal-info">
                                            <h3 class="goal-title">Strength Goals</h3>
                                            <p class="goal-meta">Track lifting and muscle gain progress</p>
                                                </div>
                                        <div class="goal-icon">
                                            <i class="fas fa-dumbbell"></i>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                        $strengthGoalsCount = 0;
                                        foreach ($active_goals as $goal) {
                                            if ($goal['goal_type'] == 'strength') $strengthGoalsCount++;
                                        }
                                    ?>
                                    
                                    <div class="goal-progress">
                                        <div class="goal-progress-stats">
                                            <span><?= $strengthGoalsCount ?> active goals</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                <div class="goal-card goal-type-endurance" style="cursor: pointer;" onclick="filterGoalsByType('endurance')">
                                    <div class="goal-header">
                                        <div class="goal-info">
                                            <h3 class="goal-title">Endurance Goals</h3>
                                            <p class="goal-meta">Track cardio and stamina progress</p>
                                        </div>
                                        <div class="goal-icon">
                                            <i class="fas fa-running"></i>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                        $enduranceGoalsCount = 0;
                                        foreach ($active_goals as $goal) {
                                            if ($goal['goal_type'] == 'endurance') $enduranceGoalsCount++;
                                        }
                                    ?>
                                    
                                    <div class="goal-progress">
                                        <div class="goal-progress-stats">
                                            <span><?= $enduranceGoalsCount ?> active goals</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="goal-card goal-type-workout" style="cursor: pointer;" onclick="filterGoalsByType('workout')">
                                    <div class="goal-header">
                                        <div class="goal-info">
                                            <h3 class="goal-title">Workout Goals</h3>
                                            <p class="goal-meta">Track workout frequency and consistency</p>
                                        </div>
                                        <div class="goal-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                        $workoutGoalsCount = 0;
                                        foreach ($active_goals as $goal) {
                                            if ($goal['goal_type'] == 'workout') $workoutGoalsCount++;
                                        }
                                    ?>
                                    
                                    <div class="goal-progress">
                                        <div class="goal-progress-stats">
                                            <span><?= $workoutGoalsCount ?> active goals</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-clock"></i> Goal Timeline
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <div class="timeline">
                                <?php 
                                    usort($active_goals, function($a, $b) {
                                        return strtotime($a['deadline']) - strtotime($b['deadline']);
                                    });
                                    
                                    $timelineGoals = array_slice($active_goals, 0, 5); 
                                ?>
                                
                                <?php if (count($timelineGoals) > 0): ?>
                                    <?php foreach ($timelineGoals as $index => $goal): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker <?= $goal['goal_type'] ?>">
                                                <i class="fas <?= getGoalTypeIcon($goal['goal_type']) ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-date">
                                                    <?= date('M d, Y', strtotime($goal['deadline'])) ?>
                                                </div>
                                                <h3 class="timeline-title"><?= htmlspecialchars($goal['title']) ?></h3>
                                                <div class="timeline-progress">
                                                    <div class="goal-progress-bar">
                                                        <div class="goal-progress-fill <?= $goal['goal_type'] ?>" 
                                                            style="width: <?= calculateProgress($goal['current_value'], $goal['target_value']) ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <h3 class="empty-state-title">No Goals Timeline</h3>
                                        <p class="empty-state-text">Add goals to see your timeline.</p>
                                    </div>
                                        <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-chart-pie"></i> Goals Distribution
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <div class="chart-container">
                                <canvas id="goalsDistributionChart"></canvas>
                            </div>
                            
                            <?php
                                $goalsByType = [
                                    'weight' => 0,
                                    'strength' => 0,
                                    'endurance' => 0,
                                    'workout' => 0,
                                    'nutrition' => 0
                                ];
                                
                                foreach ($active_goals as $goal) {
                                    if (isset($goalsByType[$goal['goal_type']])) {
                                        $goalsByType[$goal['goal_type']]++;
                                    }
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-trophy"></i> Recent Achievements
                            </h2>
                        </div>
                        
                        <div class="section-body">
                            <?php if (empty($completed_goals)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Completed Goals Yet</h3>
                                    <p class="empty-state-text">Keep working on your active goals to see your achievements here!</p>
                                </div>
                            <?php else: ?>
                                <div class="completed-goals">
                                    <?php foreach ($completed_goals as $goal): ?>
                                        <div class="goal-card goal-type-<?= $goal['goal_type'] ?>" style="opacity: 0.8;">
                                            <div class="goal-header">
                                                <div class="goal-info">
                                                    <h3 class="goal-title"><?= htmlspecialchars($goal['title']) ?></h3>
                                                    <div class="goal-meta">
                                                        <span class="goal-badge <?= $goal['goal_type'] ?>">
                                                            <?= ucfirst($goal['goal_type']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="goal-deadline">
                                                        <i class="fas fa-check-circle"></i> Completed on <?= date('M d, Y', strtotime($goal['completed_at'])) ?>
                                                    </div>
                                                </div>
                                                <div class="goal-icon">
                                                    <i class="fas <?= getGoalTypeIcon($goal['goal_type']) ?>"></i>
                                                </div>
                                            </div>
                                        
                                        <div class="goal-progress">
                                                <div class="goal-progress-stats">
                                                    <span><?= $goal['current_value'] ?> / <?= $goal['target_value'] ?></span>
                                                    <span>100%</span>
                                            </div>
                                            <div class="goal-progress-bar">
                                                    <div class="goal-progress-fill" style="width: 100%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-lightbulb"></i> Goal Suggestions
                            </h2>
                </div>
                
                        <div class="section-body">
                            <div class="recommendations-grid">
                                <div class="recommendation-card">
                                    <h3>Workout Frequency</h3>
                                    <p>Aim to work out 3-5 times per week for optimal results.</p>
                                    <button class="btn btn-secondary btn-sm add-suggested-goal" 
                                            data-title="Workout 4x per week" 
                                            data-type="workout" 
                                            data-target="4" 
                                            data-description="Complete 4 workouts each week">
                                        <i class="fas fa-plus"></i> Add to Goals
                                    </button>
                                </div>
                                
                                <div class="recommendation-card">
                                    <h3>Strength Building</h3>
                                    <p>Increase your bench press by 15% in the next 8 weeks.</p>
                                    <button class="btn btn-secondary btn-sm add-suggested-goal"
                                            data-title="Improve bench press" 
                                            data-type="strength" 
                                            data-target="15" 
                                            data-description="Increase bench press weight by 15%">
                                        <i class="fas fa-plus"></i> Add to Goals
                                    </button>
                        </div>
                        
                                <div class="recommendation-card">
                                    <h3>Cardio Endurance</h3>
                                    <p>Run 5K under 30 minutes within the next 12 weeks.</p>
                                    <button class="btn btn-secondary btn-sm add-suggested-goal"
                                            data-title="Run 5K under 30 min" 
                                            data-type="endurance" 
                                            data-target="30" 
                                            data-description="Complete a 5K run in under 30 minutes">
                                        <i class="fas fa-plus"></i> Add to Goals
                                    </button>
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
                <a href="current-goal.php" class="mobile-nav-link active">
                    <i class="fas fa-bullseye"></i>
                    <span>Goals</span>
                </a>
                <a href="quick-workout.php" class="mobile-nav-link">
                    <i class="fas fa-stopwatch"></i>
                    <span>Workout</span>
                </a>
            </div>
        </nav>
    </div>
    
    <div class="modal" id="addGoalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <form action="current-goal.php" method="post">
                    <input type="hidden" name="add_goal" value="1">
                    
                    <div class="form-group">
                        <label for="title">Goal Title <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="goal_type">Goal Type <span style="color: var(--danger);">*</span></label>
                            <select id="goal_type" name="goal_type" required>
                                <option value="">Select Goal Type</option>
                                <option value="weight">Weight</option>
                                <option value="strength">Strength</option>
                                <option value="endurance">Endurance</option>
                                <option value="workout">Workout</option>
                                <option value="nutrition">Nutrition</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="deadline">Deadline <span style="color: var(--danger);">*</span></label>
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="target_value">Target Value <span style="color: var(--danger);">*</span></label>
                            <input type="number" id="target_value" name="target_value" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_value">Current Value</label>
                            <input type="number" id="current_value" name="current_value" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal" id="updateGoalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <form action="current-goal.php" method="post">
                    <input type="hidden" name="update_goal" value="1">
                    <input type="hidden" id="update_goal_id" name="goal_id">
                    
                    <div class="form-group">
                        <label for="update_current_value">Current Value</label>
                        <input type="number" id="update_current_value" name="current_value" step="0.01">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="completed" name="completed">
                        <label for="completed">Mark as completed</label>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal" id="deleteGoalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Goal</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Are you sure you want to delete this goal? This action cannot be undone.</p>
                
                <form action="current-goal.php" method="post">
                    <input type="hidden" name="delete_goal" value="1">
                    <input type="hidden" id="delete_goal_id" name="goal_id">
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>

        const modals = document.querySelectorAll('.modal');
        const addGoalBtn = document.getElementById('addGoalBtn');
        const startNewGoalBtn = document.getElementById('startNewGoalBtn');
        const updateGoalBtns = document.querySelectorAll('.update-goal-btn');
        const deleteGoalBtns = document.querySelectorAll('.delete-goal-btn');
        const closeBtns = document.querySelectorAll('.modal-close, .close-modal');
        
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
        
        if (addGoalBtn) {
            addGoalBtn.addEventListener('click', () => openModal('addGoalModal'));
        }
        
        if (startNewGoalBtn) {
            startNewGoalBtn.addEventListener('click', () => openModal('addGoalModal'));
        }
        
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const distributionCtx = document.getElementById('goalsDistributionChart');
            
            if (distributionCtx) {
                const goalTypes = <?= json_encode(array_keys($goalsByType)) ?>;
                const goalCounts = <?= json_encode(array_values($goalsByType)) ?>;
                
                const distributionChart = new Chart(distributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: goalTypes.map(type => type.charAt(0).toUpperCase() + type.slice(1)),
                        datasets: [{
                            data: goalCounts,
                            backgroundColor: [
                                '#4361ee',
                                '#9d4edd',
                                '#f72585',
                                '#06d6a0',
                                '#ff9e00'
                            ],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: 'white',
                                    font: {
                                        family: 'Poppins',
                                        size: 12
                                    },
                                    padding: 15
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(26, 26, 46, 0.9)',
                                titleFont: {
                                    family: 'Poppins',
                                    size: 14
                                },
                                bodyFont: {
                                    family: 'Poppins',
                                    size: 13
                                },
                                padding: 12,
                                boxPadding: 8
                            }
                        },
                        cutout: '65%'
                    }
                });
            }
        });
        
        function filterGoalsByType(type) {
            console.log(`Filtering goals by type: ${type}`);
        }

        document.querySelectorAll('.add-suggested-goal').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = document.getElementById('addGoalModal');
                const titleInput = modal.querySelector('#title');
                const typeSelect = modal.querySelector('#goal_type');
                const targetInput = modal.querySelector('#target_value');
                const descriptionInput = modal.querySelector('#description');
                
                titleInput.value = this.dataset.title;
                typeSelect.value = this.dataset.type;
                targetInput.value = this.dataset.target;
                descriptionInput.value = this.dataset.description;
                
                const deadlineInput = modal.querySelector('#deadline');
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 56); 
                const formattedDate = futureDate.toISOString().split('T')[0];
                deadlineInput.value = formattedDate;
                
                openModal('addGoalModal');
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            timelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animated');
                }, index * 200);
            });
        });
    </script>
    <?php require_once 'mobile-nav.php'; ?>
</body>
</html> 