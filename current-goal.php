<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once 'assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Fetch active goals for the user
$goals_query = "SELECT * FROM goals WHERE user_id = ? AND completed = 0 ORDER BY target_date ASC";
$goals_stmt = mysqli_prepare($conn, $goals_query);
mysqli_stmt_bind_param($goals_stmt, "i", $user_id);
mysqli_stmt_execute($goals_stmt);
$goals_result = mysqli_stmt_get_result($goals_stmt);
$active_goals = [];
while ($row = mysqli_fetch_assoc($goals_result)) {
    $active_goals[] = $row;
}

// Fetch completed goals for the user
$completed_query = "SELECT * FROM goals WHERE user_id = ? AND completed = 1 ORDER BY completed_date DESC LIMIT 5";
$completed_stmt = mysqli_prepare($conn, $completed_query);
mysqli_stmt_bind_param($completed_stmt, "i", $user_id);
mysqli_stmt_execute($completed_stmt);
$completed_result = mysqli_stmt_get_result($completed_stmt);
$completed_goals = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_goals[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Current Goals</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
</head>
<body>
    <header class="top-header">
        <div class="logo-section">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <nav class="nav-links">
            <a href="workout-analytics.php" class="nav-link">Total Workouts</a>
            <a href="calories-burned.php" class="nav-link">Calories Burned</a>
            <a href="current-goal.php" class="nav-link active">Current Goal</a>
            <a href="workout-planer.php" class="nav-link">Plan</a>
            <a href="logout.php" class="nav-link nav-link-logout">Logout</a>
        </nav>
    </header>

    <main class="goals-dashboard">
        <div class="goals-dashboard-header">
            <h1 class="goals-dashboard-title">YOUR FITNESS GOALS</h1>
            <p class="goals-dashboard-subtitle">Track your progress, stay motivated, and achieve your fitness goals one step at a time.</p>
        </div>
        
        <div class="goals-container">
            <section class="goals-section">
                <div class="goals-section-header">
                    <h2 class="goals-section-title"><i class="fas fa-bullseye"></i> Active Goals</h2>
                </div>
                
                <?php if (count($active_goals) > 0): ?>
                    <?php foreach ($active_goals as $goal): 
                        // Calculate progress percentage
                        $progress = 0;
                        if ($goal['target_value'] != 0 && $goal['current_value'] != 0) {
                            if ($goal['goal_type'] == 'weight' && $goal['target_value'] < $goal['current_value']) {
                                // For weight loss, progress is reversed
                                $total_change = $goal['current_value'] - $goal['target_value'];
                                $current_change = $goal['current_value'] - $goal['current_value'];
                                $progress = ($current_change / $total_change) * 100;
                            } else {
                                $progress = ($goal['current_value'] / $goal['target_value']) * 100;
                            }
                        }
                        $progress = min(100, max(0, $progress));
                        
                        // Determine goal type class
                        $type_class = 'goal-type-weight';
                        switch ($goal['goal_type']) {
                            case 'strength':
                                $type_class = 'goal-type-strength';
                                break;
                            case 'endurance':
                                $type_class = 'goal-type-endurance';
                                break;
                            case 'workout':
                                $type_class = 'goal-type-workout';
                                break;
                            case 'nutrition':
                                $type_class = 'goal-type-nutrition';
                                break;
                        }
                        
                        // Calculate days remaining
                        $target_date = new DateTime($goal['target_date']);
                        $today = new DateTime();
                        $days_remaining = $today->diff($target_date)->days;
                        $days_text = $target_date < $today ? "Overdue by $days_remaining days" : "$days_remaining days remaining";
                    ?>
                    <div class="goal-card">
                        <div class="goal-header">
                            <div>
                                <span class="goal-type <?php echo $type_class; ?>"><?php echo ucfirst(htmlspecialchars($goal['goal_type'])); ?></span>
                                <h3 class="goal-title"><?php echo htmlspecialchars($goal['goal_name']); ?></h3>
                            </div>
                        </div>
                        
                        <div class="goal-dates">
                            <div class="goal-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Started: <?php echo date('M d, Y', strtotime($goal['start_date'])); ?></span>
                            </div>
                            <div class="goal-date">
                                <i class="fas fa-flag-checkered"></i>
                                <span>Target: <?php echo date('M d, Y', strtotime($goal['target_date'])); ?></span>
                            </div>
                            <div class="goal-date">
                                <i class="fas fa-hourglass-half"></i>
                                <span><?php echo $days_text; ?></span>
                            </div>
                        </div>
                        
                        <p class="goal-description"><?php echo htmlspecialchars($goal['goal_description']); ?></p>
                        
                        <div class="goal-progress">
                            <div class="goal-progress-header">
                                <span>Progress: <?php echo round($progress); ?>%</span>
                                <span><?php echo htmlspecialchars($goal['current_value']); ?> / <?php echo htmlspecialchars($goal['target_value']); ?></span>
                            </div>
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="goal-actions">
                            <button class="goal-button goal-button-update">
                                <i class="fas fa-edit"></i> Update Progress
                            </button>
                            <button class="goal-button goal-button-complete">
                                <i class="fas fa-check-circle"></i> Mark as Complete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-goals-message">
                        <p>You don't have any active goals yet. Create your first goal to start tracking your progress!</p>
                    </div>
                <?php endif; ?>
                
                <a href="#" class="add-goal-button">
                    <i class="fas fa-plus-circle"></i> Add New Goal
                </a>
            </section>
            
            <section class="goals-section">
                <div class="goals-section-header">
                    <h2 class="goals-section-title"><i class="fas fa-trophy"></i> Completed Goals</h2>
                </div>
                
                <?php if (count($completed_goals) > 0): ?>
                    <?php foreach ($completed_goals as $goal): 
                        // Determine goal type class
                        $type_class = 'goal-type-weight';
                        switch ($goal['goal_type']) {
                            case 'strength':
                                $type_class = 'goal-type-strength';
                                break;
                            case 'endurance':
                                $type_class = 'goal-type-endurance';
                                break;
                            case 'workout':
                                $type_class = 'goal-type-workout';
                                break;
                            case 'nutrition':
                                $type_class = 'goal-type-nutrition';
                                break;
                        }
                    ?>
                    <div class="goal-card completed-goal-card">
                        <div class="completed-badge">
                            <i class="fas fa-check-circle"></i> Completed
                        </div>
                        
                        <div class="goal-header">
                            <div>
                                <span class="goal-type <?php echo $type_class; ?>"><?php echo ucfirst(htmlspecialchars($goal['goal_type'])); ?></span>
                                <h3 class="goal-title"><?php echo htmlspecialchars($goal['goal_name']); ?></h3>
                            </div>
                        </div>
                        
                        <div class="goal-dates">
                            <div class="goal-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Started: <?php echo date('M d, Y', strtotime($goal['start_date'])); ?></span>
                            </div>
                            <div class="goal-date">
                                <i class="fas fa-flag-checkered"></i>
                                <span>Completed: <?php echo date('M d, Y', strtotime($goal['completed_date'])); ?></span>
                            </div>
                        </div>
                        
                        <p class="goal-description"><?php echo htmlspecialchars($goal['goal_description']); ?></p>
                        
                        <div class="goal-progress">
                            <div class="goal-progress-header">
                                <span>Final Result</span>
                                <span><?php echo htmlspecialchars($goal['current_value']); ?> / <?php echo htmlspecialchars($goal['target_value']); ?></span>
                            </div>
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-goals-message">
                        <p>You haven't completed any goals yet. Keep working on your active goals!</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html> 