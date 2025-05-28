<?php
session_start();
require_once "../assets/db_connection.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/goal-details.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("location: current-goal.php");
    exit;
}

$goal_id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_progress"])) {
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
            $message = "Goal progress updated successfully!";
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

$query = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $goal_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($goal = mysqli_fetch_assoc($result)) {
    $progress = min(100, round(($goal['current_value'] / $goal['target_value']) * 100));
    $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
    $isCompleted = $goal['completed'] || $goal['current_value'] >= $goal['target_value'];
    
    $history = [];
    try {
        $history_query = "SELECT * FROM goal_progress_history WHERE goal_id = ? ORDER BY logged_at DESC LIMIT 10";
        $history_stmt = mysqli_prepare($conn, $history_query);
        if ($history_stmt) {
            mysqli_stmt_bind_param($history_stmt, "i", $goal_id);
            mysqli_stmt_execute($history_stmt);
            $history_result = mysqli_stmt_get_result($history_stmt);
            
            while ($row = mysqli_fetch_assoc($history_result)) {
                $history[] = $row;
            }
        }
    } catch (Exception $e) {
    }
} else {
    header("location: current-goal.php");
    exit;
}

$related_workouts = [];
try {
    $workouts_query = "SELECT w.id, w.name, w.created_at, w.duration_minutes 
                     FROM workouts w 
                     WHERE w.user_id = ? 
                     AND w.created_at BETWEEN ? AND NOW() 
                     ORDER BY w.created_at DESC 
                     LIMIT 5";
    $workouts_stmt = mysqli_prepare($conn, $workouts_query);
    if ($workouts_stmt) {
        $start_date = date('Y-m-d', strtotime('-30 days'));
        mysqli_stmt_bind_param($workouts_stmt, "is", $user_id, $start_date);
        mysqli_stmt_execute($workouts_stmt);
        $workouts_result = mysqli_stmt_get_result($workouts_stmt);
        
        while ($row = mysqli_fetch_assoc($workouts_result)) {
            $related_workouts[] = $row;
        }
    }
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Details - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../profile/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet">
    <style>
        .goal-details-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        
        @media (max-width: 992px) {
            .goal-details-container {
                grid-template-columns: 1fr;
            }
        }
        
        .goal-details-card {
            background-color: #1a1b26;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .goal-details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        
        .goal-title-area h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .goal-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #a9b1d6;
            font-size: 14px;
        }
        
        .goal-type-badge {
            padding: 4px 12px;
            border-radius: 16px;
            background-color: #24273a;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: #24273a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            background-color: #2a2d45;
        }
        
        .goal-progress-section {
            padding: 24px 0;
        }
        
        .goal-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .progress-stats {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
        }
        
        .progress-stat {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .goal-progress-bar {
            height: 12px;
            background-color: #24273a;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7aa2f7 0%, #bb9af7 100%);
            border-radius: 6px;
            transition: width 0.3s ease;
        }
        
        .progress-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .update-progress-form {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-title {
            margin-bottom: 16px;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            background-color: #24273a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: #7aa2f7;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #6e93e8;
        }
        
        .goal-description {
            margin-top: 24px;
            color: #a9b1d6;
            line-height: 1.6;
        }
        
        .history-section {
            margin-top: 24px;
        }
        
        .history-title {
            margin-bottom: 16px;
            font-size: 18px;
        }
        
        .history-list {
            list-style: none;
            padding: 0;
        }
        
        .history-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .history-date {
            font-size: 14px;
            color: #a9b1d6;
            margin-bottom: 4px;
        }
        
        .history-value {
            display: flex;
            justify-content: space-between;
        }
        
        .related-workouts {
            margin-top: 24px;
        }
        
        .workouts-title {
            margin-bottom: 16px;
            font-size: 18px;
        }
        
        .workout-card {
            padding: 12px;
            border-radius: 8px;
            background-color: #24273a;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .workout-name {
            font-weight: 500;
        }
        
        .workout-meta {
            font-size: 14px;
            color: #a9b1d6;
        }
        
        .view-link {
            color: #7aa2f7;
            text-decoration: none;
        }
        
        .view-link:hover {
            text-decoration: underline;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .message.success {
            background-color: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .message.error {
            background-color: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Goal Details</h1>
                <a href="current-goal.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Goals
                </a>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="goal-details-container">
                <div class="goal-details-main">
                    <div class="goal-details-card">
                        <div class="goal-details-header">
                            <div class="goal-title-area">
                                <h1><?= htmlspecialchars($goal['title']) ?></h1>
                                <div class="goal-meta">
                                    <span class="goal-type-badge"><?= htmlspecialchars(ucfirst($goal['goal_type'])) ?></span>
                                    <span>Created on <?= date('M d, Y', strtotime($goal['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="action-btn" onclick="deleteGoal(<?= $goal['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="goal-progress-section">
                            <div class="goal-progress-header">
                                <h2>Progress Tracking</h2>
                                <span class="status-badge <?= $isCompleted ? 'completed' : '' ?>">
                                    <?= $isCompleted ? 'Completed' : 'In Progress' ?>
                                </span>
                            </div>
                            
                            <div class="progress-stats">
                                <div class="progress-stat">
                                    <div class="stat-value"><?= $progress ?>%</div>
                                    <div class="stat-label">Progress</div>
                                </div>
                                <div class="progress-stat">
                                    <div class="stat-value"><?= $goal['current_value'] ?></div>
                                    <div class="stat-label">Current Value</div>
                                </div>
                                <div class="progress-stat">
                                    <div class="stat-value"><?= $goal['target_value'] ?></div>
                                    <div class="stat-label">Target Value</div>
                                </div>
                                <div class="progress-stat">
                                    <div class="stat-value"><?= $daysLeft >= 0 ? $daysLeft : 'Expired' ?></div>
                                    <div class="stat-label"><?= $daysLeft >= 0 ? 'Days Left' : 'Days Overdue' ?></div>
                                </div>
                            </div>
                            
                            <div class="goal-progress-bar">
                                <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                            </div>
                            <div class="progress-numbers">
                                <span class="start">0</span>
                                <span class="target"><?= $goal['target_value'] ?></span>
                            </div>
                            
                            <div class="update-progress-form">
                                <h3 class="form-title">Update Progress</h3>
                                <form action="goal-details.php?id=<?= $goal_id ?>" method="post">
                                    <input type="hidden" name="update_progress" value="1">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Current Value</label>
                                        <input type="number" name="current_value" class="form-control" value="<?= $goal['current_value'] ?>" step="any" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="completed" name="completed" <?= $isCompleted ? 'checked' : '' ?>>
                                            <label for="completed">Mark as completed</label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Progress</button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (!empty($goal['description'])): ?>
                            <div class="goal-description">
                                <h3>Description</h3>
                                <p><?= nl2br(htmlspecialchars($goal['description'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($history)): ?>
                        <div class="goal-details-card">
                            <div class="history-section">
                                <h3 class="history-title">Progress History</h3>
                                <ul class="history-list">
                                    <?php foreach ($history as $entry): ?>
                                        <li class="history-item">
                                            <div class="history-date">
                                                <?= date('M d, Y', strtotime($entry['logged_at'])) ?>
                                            </div>
                                            <div class="history-value">
                                                <span>Value: <?= $entry['value'] ?></span>
                                                <span><?= round(($entry['value'] / $goal['target_value']) * 100) ?>%</span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="goal-details-sidebar">
                    <div class="goal-details-card">
                        <div class="goal-deadline">
                            <h3>Goal Timeline</h3>
                            <div class="deadline-info">
                                <div class="deadline-item">
                                    <span class="deadline-label">Start Date</span>
                                    <span class="deadline-value"><?= date('M d, Y', strtotime($goal['created_at'])) ?></span>
                                </div>
                                <div class="deadline-item">
                                    <span class="deadline-label">Deadline</span>
                                    <span class="deadline-value"><?= date('M d, Y', strtotime($goal['deadline'])) ?></span>
                                </div>
                                <?php if ($goal['completed'] && !empty($goal['completed_at'])): ?>
                                    <div class="deadline-item">
                                        <span class="deadline-label">Completed On</span>
                                        <span class="deadline-value"><?= date('M d, Y', strtotime($goal['completed_at'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($related_workouts)): ?>
                        <div class="goal-details-card">
                            <div class="related-workouts">
                                <h3 class="workouts-title">Recent Workouts</h3>
                                <?php foreach ($related_workouts as $workout): ?>
                                    <div class="workout-card">
                                        <div>
                                            <div class="workout-name"><?= htmlspecialchars($workout['name']) ?></div>
                                            <div class="workout-meta">
                                                <?= date('M d, Y', strtotime($workout['created_at'])) ?> • 
                                                <?= $workout['duration_minutes'] ?> min
                                            </div>
                                        </div>
                                        <a href="workout-details.php?id=<?= $workout['id'] ?>" class="view-link">View</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function editGoal(goalId) {
            window.location.href = `edit-goal.php?id=${goalId}`;
        }
        
        function deleteGoal(goalId) {
            if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'current-goal.php';
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_goal';
                deleteInput.value = '1';
                form.appendChild(deleteInput);
                
                const goalIdInput = document.createElement('input');
                goalIdInput.type = 'hidden';
                goalIdInput.name = 'goal_id';
                goalIdInput.value = goalId;
                form.appendChild(goalIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 