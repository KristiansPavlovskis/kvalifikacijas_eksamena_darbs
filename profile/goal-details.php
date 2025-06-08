<?php

require_once 'profile_access_control.php';
require_once "../assets/db_connection.php";
require_once "languages.php";

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
            $message = t("goal_progress_updated");
            $message_type = "success";
        } else {
            $message = t("error") . ": " . mysqli_error($conn);
            $message_type = "error";
        }
    } catch (Exception $e) {
        $message = t("error") . ": " . $e->getMessage();
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
                     LIMIT 3";
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
<html lang="<?= $_SESSION['language'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t("goal_details") ?> - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../profile/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../profile/global-profile.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?= t("goal_details") ?></h1>
                <a href="current-goal.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> <?= t("back_to_goals") ?>
                </a>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="goal-message <?= $message_type ?>">
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
                                    <span><?= t("created_on") ?> <?= date('M d, Y', strtotime($goal['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="goal-action-buttons">
                                <button class="goal-action-btn" onclick="deleteGoal(<?= $goal['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="goal-progress-section">
                            <div class="goal-progress-header">
                                <h2><?= t("progress_tracking") ?></h2>
                                <span class="goal-status-badge <?= $isCompleted ? 'completed' : '' ?>">
                                    <?= $isCompleted ? t("completed") : t("in_progress") ?>
                                </span>
                            </div>
                            
                            <div class="goal-progress-stats">
                                <div class="goal-progress-stat">
                                    <div class="goal-stat-value"><?= $progress ?>%</div>
                                    <div class="goal-stat-label"><?= t("progress") ?></div>
                                </div>
                                <div class="goal-progress-stat">
                                    <div class="goal-stat-value"><?= $goal['current_value'] ?></div>
                                    <div class="goal-stat-label"><?= t("current_value") ?></div>
                                </div>
                                <div class="goal-progress-stat">
                                    <div class="goal-stat-value"><?= $goal['target_value'] ?></div>
                                    <div class="goal-stat-label"><?= t("target_value") ?></div>
                                </div>
                                <div class="goal-progress-stat">
                                    <div class="goal-stat-value"><?= $daysLeft >= 0 ? $daysLeft : t("expired") ?></div>
                                    <div class="goal-stat-label"><?= $daysLeft >= 0 ? t("days_left") : t("days_overdue") ?></div>
                                </div>
                            </div>
                            
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill" style="width: <?= $progress ?>%"></div>
                            </div>
                            <div class="goal-progress-numbers">
                                <span class="start">0</span>
                                <span class="target"><?= $goal['target_value'] ?></span>
                            </div>
                            
                            <div class="goal-update-progress-form">
                                <h3 class="goal-form-title"><?= t("update_progress") ?></h3>
                                <form action="goal-details.php?id=<?= $goal_id ?>" method="post">
                                    <input type="hidden" name="update_progress" value="1">
                                    
                                    <div class="goal-form-group">
                                        <label class="goal-form-label"><?= t("current_value") ?></label>
                                        <input type="number" name="current_value" class="goal-form-control" value="<?= $goal['current_value'] ?>" step="any" required>
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <div class="goal-checkbox-group">
                                            <input type="checkbox" id="completed" name="completed" <?= $isCompleted ? 'checked' : '' ?>>
                                            <label for="completed"><?= t("mark_completed") ?></label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="goal-btn goal-btn-primary"><?= t("update_progress") ?></button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (!empty($goal['description'])): ?>
                            <div class="goal-description">
                                <h3><?= t("description") ?></h3>
                                <p><?= nl2br(htmlspecialchars($goal['description'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($history)): ?>
                        <div class="goal-details-card">
                            <div class="goal-history-section">
                                <h3 class="goal-history-title"><?= t("progress_history") ?></h3>
                                <ul class="goal-history-list">
                                    <?php foreach ($history as $entry): ?>
                                        <li class="goal-history-item">
                                            <div class="goal-history-date">
                                                <?= date('M d, Y', strtotime($entry['logged_at'])) ?>
                                            </div>
                                            <div class="goal-history-value">
                                                <span><?= t("value") ?>: <?= $entry['value'] ?></span>
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
                            <h3><?= t("goal_timeline") ?></h3>
                            <div class="goal-deadline-info">
                                <div class="goal-deadline-item">
                                    <span class="goal-deadline-label"><?= t("start_date") ?></span>
                                    <span class="goal-deadline-value"><?= date('M d, Y', strtotime($goal['created_at'])) ?></span>
                                </div>
                                <div class="goal-deadline-item">
                                    <span class="goal-deadline-label"><?= t("deadline") ?></span>
                                    <span class="goal-deadline-value"><?= date('M d, Y', strtotime($goal['deadline'])) ?></span>
                                </div>
                                <?php if ($goal['completed'] && !empty($goal['completed_at'])): ?>
                                    <div class="goal-deadline-item">
                                        <span class="goal-deadline-label"><?= t("completed_on") ?></span>
                                        <span class="goal-deadline-value"><?= date('M d, Y', strtotime($goal['completed_at'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($related_workouts)): ?>
                        <div class="goal-details-card">
                            <div class="goal-related-workouts">
                                <h3 class="goal-workouts-title"><?= t("recent_workouts") ?></h3>
                                <?php foreach ($related_workouts as $workout): ?>
                                    <div class="goal-workout-card">
                                        <div>
                                            <div class="goal-workout-name"><?= htmlspecialchars($workout['name']) ?></div>
                                            <div class="goal-workout-meta">
                                                <?= date('M d, Y', strtotime($workout['created_at'])) ?> • 
                                                <?= $workout['duration_minutes'] ?> <?= t("min") ?>
                                            </div>
                                        </div>
                                        <a href="workout-details.php?id=<?= $workout['id'] ?>" class="goal-view-link"><?= t("view") ?></a>
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
            if (confirm('<?= t("confirm_delete_goal") ?>')) {
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