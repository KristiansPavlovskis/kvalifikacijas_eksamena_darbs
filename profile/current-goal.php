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
    <link href="global-profile.css" rel="stylesheet">
</head>
<body>
    <div class="goal-dashboard">
        <?php require_once 'sidebar.php'; ?>
        <div class="goal-container-column">
        <div class="goal-goals-header">
                    <h2>Your Active Goals</h2>
                    <button class="goal-btn goal-btn-primary goal-create-btn" id="addGoalBtn">
                        <i class="fas fa-plus"></i> Create New Goal
                    </button>
        </div>
        <div class="goal-main-content">
            <div class="goal-left-filters">
                <div class="goal-filter-category">
                    <h3>Categories</h3>
                    <div class="goal-filter-option active" data-filter="all">
                        <span class="goal-filter-count"><?= count($goals) ?></span>
                        <span>All Categories</span>
                    </div>
                    <?php foreach ($goalTypeIcons as $type => $icon): ?>
                        <div class="goal-filter-option" data-filter="<?= htmlspecialchars($type) ?>">
                            <?php 
                            $typeCount = 0;
                            foreach ($goals as $goal) {
                                if (strtolower($goal['goal_type']) === $type) {
                                    $typeCount++;
                                }
                            }
                            ?>
                            <span class="goal-filter-count"><?= $typeCount ?></span>
                            <span><?= ucfirst(htmlspecialchars($type)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="goal-filter-category">
                    <h3>Status</h3>
                    <div class="goal-filter-option active" data-status="all">
                        <span class="goal-filter-count"><?= count($goals) ?></span>
                        <span>All Status</span>
                    </div>
                    <div class="goal-filter-option" data-status="on-track">
                        <?php
                        $onTrackCount = 0;
                        foreach ($goals as $goal) {
                            if (!$goal['completed'] && strtotime($goal['deadline']) > time()) {
                                $onTrackCount++;
                            }
                        }
                        ?>
                        <span class="goal-filter-count"><?= $onTrackCount ?></span>
                        <span>On Track</span>
                    </div>
                    <div class="goal-filter-option" data-status="behind">
                        <?php
                        $behindCount = 0;
                        foreach ($goals as $goal) {
                            if (!$goal['completed'] && strtotime($goal['deadline']) < time()) {
                                $behindCount++;
                            }
                        }
                        ?>
                        <span class="goal-filter-count"><?= $behindCount ?></span>
                        <span>Behind</span>
                    </div>
                    <div class="goal-filter-option" data-status="completed">
                        <?php
                        $completedCount = 0;
                        foreach ($goals as $goal) {
                            if ($goal['completed']) {
                                $completedCount++;
                            }
                        }
                        ?>
                        <span class="goal-filter-count"><?= $completedCount ?></span>
                        <span>Completed</span>
                    </div>
                </div>
            </div>
            <div class="goal-goal-cards">
                <?php if (empty($active_goals)): ?>
                    <div class="goal-no-goals" style="display: block !important;">
                        <div class="goal-empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="goal-empty-state-title">You haven't set any fitness goals yet</h3>
                        <p class="goal-empty-state-text">Setting clear goals is the first step toward achieving your fitness dreams</p>
                        <button class="goal-btn goal-btn-primary goal-create-btn">
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
                                    <span class="goal-status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                                </h3>
                                <div class="goal-actions">
                                    <button class="goal-action-btn goal-edit-btn" onclick="editGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="goal-action-btn goal-delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <span class="goal-type"><?= htmlspecialchars(ucfirst($goal['goal_type'])) ?></span>
                                </div>
                            </div>
                            <p class="goal-description"><?= htmlspecialchars($goal['description'] ?: 'No description provided') ?></p>
                            <div class="goal-progress" data-completed="<?= $goal['completed'] ? 'true' : 'false' ?>">
                                <div class="goal-progress-bar">
                                    <div class="goal-progress-fill" style="width: <?= $progress ?>%"></div>
                                </div>
                                <div class="goal-progress-numbers">
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
                <div class="goal-pagination">
                    <div class="goal-pagination-info">
                        Showing <?= min(($page - 1) * $goals_per_page + 1, $total_goals) ?> to 
                        <?= min($page * $goals_per_page, $total_goals) ?> of <?= $total_goals ?> goals
                    </div>
                    <div class="goal-pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="goal-pagination-button goal-pagination-prev">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $range = 2;
                        $start_page = max(1, $page - $range);
                        $end_page = min($total_pages, $page + $range);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1" class="goal-pagination-button">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="goal-pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<a href="?page=' . $i . '" class="goal-pagination-button ' . $active_class . '">' . $i . '</a>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="goal-pagination-ellipsis">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '" class="goal-pagination-button">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="goal-pagination-button goal-pagination-next">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="goal-right-sidebar">
                <div class="goal-summary">
                    <h2>Goals Summary</h2>
                    <div class="goal-completion-rate">
                        <div class="goal-progress-circle" data-progress="<?= $completionRate ?>">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#2a2b36" stroke-width="12" />
                                <?php
                                $circumference = 2 * 3.14159 * 54;
                                $dashOffset = $circumference - ($circumference * $completionRate / 100);
                                ?>
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#ff4d4d" stroke-width="12" 
                                        stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $dashOffset ?>" transform="rotate(-90 60 60)" />
                            </svg>
                            <div class="goal-progress-text"><?= $completionRate ?>%</div>
                        </div>
                        <p>Completion Rate</p>
                    </div>
                </div>

                <div class="goal-upcoming-deadlines">
                    <h2>Upcoming Deadlines</h2>
                    <?php if (empty($upcomingDeadlines)): ?>
                        <p>No upcoming deadlines</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($upcomingDeadlines as $goal): 
                                $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                            ?>
                                <li>
                                    <span class="goal-deadline-goal"><?= htmlspecialchars($goal['title']) ?></span>
                                    <span class="goal-deadline-date"><?= $daysLeft ?> days left</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <div class="goal-mobile-view">
            <div class="goal-mobile-header">
                <h1 class="goal-mobile-title">My Fitness Goals</h1>
                <div class="goal-mobile-filter">
                    <i class="fas fa-filter"></i>
                </div>
            </div>
            
            <div class="goal-mobile-progress">
                <div class="goal-progress-circle" data-progress="<?= $completionRate ?>">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#2a2b36" stroke-width="12" />
                        <?php
                        $circumference = 2 * 3.14159 * 54;
                        $dashOffset = $circumference - ($circumference * $completionRate / 100);
                        ?>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#ff4d4d" stroke-width="12" 
                                stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $dashOffset ?>" transform="rotate(-90 60 60)" />
                    </svg>
                    <div class="goal-progress-text"><?= $completionRate ?>%</div>
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
            
            <div class="goal-mobile-tabs">
                <div class="goal-mobile-tab active" data-filter="active">Active</div>
                <div class="goal-mobile-tab" data-filter="completed">Completed</div>
                <div class="goal-mobile-tab" data-filter="all">All</div>
            </div>
            
            <div class="goal-mobile-goals">
                <?php if (empty($active_goals)): ?>
                    <div class="goal-empty-state" style="display: block !important;">
                        <div class="goal-empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="goal-empty-state-title">You haven't set any fitness goals yet</h3>
                        <p class="goal-empty-state-text">Setting clear goals is the first step toward achieving your fitness dreams</p>
                        <button class="goal-btn goal-btn-primary" id="mobileAddGoalBtn">
                            <i class="fas fa-plus"></i> Create Your First Goal
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_goals as $goal): 
                        $progress = calculateProgress($goal['current_value'], $goal['target_value']);
                        $isCompleted = $goal['completed'] ? 'true' : 'false';
                    ?>
                        <div class="goal-mobile-goal-card" data-completed="<?= $isCompleted ?>" onclick="viewGoal(<?= $goal['id'] ?>)">
                            <div class="goal-mobile-goal-title">
                                <?= htmlspecialchars($goal['title']) ?>
                                <div class="goal-mobile-goal-actions">
                                    <button class="goal-action-btn goal-edit-btn" onclick="editGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="goal-action-btn goal-delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
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
            
            <button class="goal-mobile-add-button" id="mobileAddGoalBtn">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        </div>
    </div>
    
    <div class="goal-modal" id="addGoalModal">
        <div class="goal-modal-content goal-desktop-goal-form">
            <div class="goal-modal-header">
                <h2 class="goal-modal-title">Create New Goal</h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="goalForm">
                            <input type="hidden" name="add_goal" value="1">
                            
                            <div class="goal-form-section">
                                <h3>Basic Information</h3>
                                <div class="goal-form-group">
                                    <input type="text" id="title" name="title" class="goal-form-control" placeholder="Goal Title" required>
                                </div>
                                
                                <div class="goal-form-group">
                                    <textarea id="description" name="description" class="goal-form-control" rows="3" placeholder="Description"></textarea>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="goal_type" name="goal_type" class="goal-form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                                <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
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
                            
                            <div class="goal-form-section">
                                <h3>Target Setting</h3>
                                <div class="goal-form-row">
                                    <div class="goal-form-group">
                                        <input type="number" id="current_value" name="current_value" class="goal-form-control" step="0.01" placeholder="Starting Value" value="0">
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <input type="number" id="target_value" name="target_value" class="goal-form-control" step="0.01" placeholder="Target Value" required>
                                    </div>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="unit" name="unit" class="goal-form-control">
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
                            
                            <div class="goal-form-section">
                                <h3>Timeline</h3>
                                <div class="goal-form-row">
                                    <div class="goal-form-group">
                                        <input type="date" id="start_date" name="start_date" class="goal-form-control" required>
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <input type="date" id="deadline" name="deadline" class="goal-form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="goal-form-actions">
                                <button type="submit" class="goal-btn goal-btn-primary">Create Goal</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="goal-preview">
                        <h3>Goal Preview</h3>
                        <div class="goal-preview-card">
                            <div class="goal-preview-header">
                                <h4 id="preview-title">Your Goal Title</h4>
                                <span class="goal-status-badge">In Progress</span>
                            </div>
                            
                            <div class="goal-preview-progress">
                                <div class="goal-progress-bar">
                                    <div class="goal-progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="goal-progress-info">
                                    <span>Current: <span id="preview-current">0</span></span>
                                    <span>Target: <span id="preview-target">100</span></span>
                                </div>
                            </div>
                            
                            <div class="goal-preview-deadline">
                                <i class="far fa-calendar-alt"></i>
                                <span>Deadline: <span id="preview-deadline">Dec 31, 2023</span></span>
                            </div>
                            
                            <div class="goal-preview-actions">
                                <button class="goal-btn-outline">
                                    <i class="fas fa-share"></i> Share
                                </button>
                                <button class="goal-btn-outline">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="goal-modal-content goal-mobile-goal-form">
            <div class="goal-mobile-form-header">
                <button class="goal-back-button" id="mobileFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2>Create New Goal</h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-mobile-form-steps">
                <div class="goal-form-step" data-step="1">
                    <div class="goal-form-group">
                        <input type="text" name="mobile_title" placeholder="Enter your goal title" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <textarea name="mobile_description" placeholder="Add some details about your goal" class="goal-form-control" rows="4"></textarea>
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
                
                <div class="goal-form-step" data-step="2">
                    <h3>Set Your Target</h3>
                    
                    <div class="goal-form-group">
                        <label>Starting Value</label>
                        <input type="number" name="mobile_current_value" class="goal-form-control" step="0.01" value="0">
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Target Value</label>
                        <input type="number" name="mobile_target_value" class="goal-form-control" step="0.01" required>
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Unit</label>
                        <select name="mobile_unit" class="goal-form-control">
                            <option value="kg">Kilograms (kg)</option>
                            <option value="lb">Pounds (lb)</option>
                            <option value="reps">Repetitions</option>
                            <option value="km">Kilometers (km)</option>
                            <option value="min">Minutes</option>
                        </select>
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Start Date</label>
                        <input type="date" name="mobile_start_date" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Target Date</label>
                        <input type="date" name="mobile_deadline" class="goal-form-control">
                    </div>
                </div>
            </div>
            
            <div class="goal-mobile-form-actions">
                <button class="goal-btn goal-btn-primary goal-btn-continue" id="mobileFormContinue">Continue</button>
                <button class="goal-btn goal-btn-primary goal-btn-create" id="mobileFormCreate">Create Goal</button>
            </div>
        </div>
    </div>
    
    <div class="goal-modal" id="editGoalModal">
        <div class="goal-modal-content goal-desktop-goal-form">
            <div class="goal-modal-header">
                <h2 class="goal-modal-title">Edit Goal</h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="editGoalForm">
                            <input type="hidden" name="update_goal" value="1">
                            <input type="hidden" name="goal_id" id="edit_goal_id">
                            
                            <div class="goal-form-section">
                                <h3>Basic Information</h3>
                                <div class="goal-form-group">
                                    <input type="text" id="edit_title" name="title" class="goal-form-control" placeholder="Goal Title" required>
                                </div>
                                
                                <div class="goal-form-group">
                                    <textarea id="edit_description" name="description" class="goal-form-control" rows="3" placeholder="Description"></textarea>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="edit_goal_type" name="goal_type" class="goal-form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                                <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
                                <h3>Target Setting</h3>
                                <div class="goal-form-row">
                                    <div class="goal-form-group">
                                        <input type="number" id="edit_current_value" name="current_value" class="goal-form-control" step="0.01" placeholder="Current Value" value="0">
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <input type="number" id="edit_target_value" name="target_value" class="goal-form-control" step="0.01" placeholder="Target Value" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
                                <h3>Timeline</h3>
                                <div class="goal-form-group">
                                    <input type="date" id="edit_deadline" name="deadline" class="goal-form-control" required>
                                </div>
                                
                                <div class="goal-form-group goal-checkbox-group">
                                    <div class="goal-checkbox-item">
                                        <input type="checkbox" id="edit_completed" name="completed" value="1">
                                        <label for="edit_completed">Mark as completed</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="goal-form-actions">
                                <button type="submit" class="goal-btn goal-btn-primary">Update Goal</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="goal-preview">
                        <h3>Goal Preview</h3>
                        <div class="goal-preview-card">
                            <div class="goal-preview-header">
                                <h4 id="edit-preview-title">Your Goal Title</h4>
                                <span class="goal-status-badge">In Progress</span>
                            </div>
                            
                            <div class="goal-preview-progress">
                                <div class="goal-progress-bar">
                                    <div class="goal-progress-fill" style="width: 0%"></div>
                                </div>
                                <div class="goal-progress-info">
                                    <span>Current: <span id="edit-preview-current">0</span></span>
                                    <span>Target: <span id="edit-preview-target">100</span></span>
                                </div>
                            </div>
                            
                            <div class="goal-preview-deadline">
                                <i class="far fa-calendar-alt"></i>
                                <span>Deadline: <span id="edit-preview-deadline">Not set</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="goal-modal-content goal-mobile-goal-form">
            <div class="goal-mobile-form-header">
                <button class="goal-back-button" id="mobileEditFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2>Edit Goal</h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-mobile-form-steps">
                <div class="goal-form-step" data-step="1">
                    <div class="goal-form-group">
                        <label>Goal Title</label>
                        <input type="text" name="mobile_edit_title" id="mobile_edit_title" placeholder="Enter your goal title" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Description</label>
                        <textarea name="mobile_edit_description" id="mobile_edit_description" placeholder="Add some details about your goal" class="goal-form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Category</label>
                        <select name="mobile_edit_goal_type" id="mobile_edit_goal_type" class="goal-form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= $icon ?> <?= ucfirst(htmlspecialchars($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="goal-form-step" data-step="2">
                    <h3>Set Your Target</h3>
                    
                    <div class="goal-form-group">
                        <label>Current Value</label>
                        <input type="number" name="mobile_edit_current_value" id="mobile_edit_current_value" class="goal-form-control" step="0.01">
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Target Value</label>
                        <input type="number" name="mobile_edit_target_value" id="mobile_edit_target_value" class="goal-form-control" step="0.01" required>
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Target Date</label>
                        <input type="date" name="mobile_edit_deadline" id="mobile_edit_deadline" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group goal-checkbox-group">
                        <div class="goal-checkbox-item">
                            <input type="checkbox" id="mobile_edit_completed" name="mobile_edit_completed">
                            <label for="mobile_edit_completed">Mark as completed</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="goal-mobile-form-actions">
                <button class="goal-btn goal-btn-primary goal-btn-continue" id="mobileEditFormContinue">Continue</button>
                <button class="goal-btn goal-btn-primary goal-btn-update" id="mobileEditFormUpdate">Update Goal</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('.goal-modal');
            const addGoalBtn = document.getElementById('addGoalBtn');
            const mobileAddGoalBtn = document.getElementById('mobileAddGoalBtn');
            const startNewGoalBtn = document.getElementById('startNewGoalBtn');
            const updateGoalBtns = document.querySelectorAll('.update-goal-btn');
            const deleteGoalBtns = document.querySelectorAll('.delete-goal-btn');
            const closeBtns = document.querySelectorAll('.goal-modal-close, .close-modal');
            
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
                const noGoalsElement = document.querySelector('.goal-no-goals');
                
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
                noMatchElement.className = 'goal-no-matches';
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
                
                const existingNoMatch = document.querySelector('.goal-no-matches');
                if (existingNoMatch) {
                    existingNoMatch.remove();
                }
                
                document.querySelector('.goal-goal-cards').appendChild(noMatchElement);
                return noMatchElement;
            }
            
            const categoryFilters = document.querySelectorAll('.goal-filter-category:nth-child(1) .goal-filter-option');
            const statusFilters = document.querySelectorAll('.goal-filter-category:nth-child(2) .goal-filter-option');
            
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
                document.querySelector('.goal-empty-state .goal-btn')
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
            
            const mobileTabs = document.querySelectorAll('.goal-mobile-tab');
            mobileTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    mobileTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    console.log(`Selected tab: ${this.textContent}, filter: ${filter}`);
                    
                    const mobileGoalCards = document.querySelectorAll('.goal-mobile-goal-card');
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
                    const emptyState = document.querySelector('.goal-mobile-goals .goal-empty-state');
                    
                    if (emptyState) {
                        if (visibleGoals.length === 0 && mobileGoalCards.length > 0) {
                            let message = 'No goals match your filter.';
                            
                            if (filter === 'active') {
                                message = 'You don\'t have any active goals.';
                            } else if (filter === 'completed') {
                                message = 'You haven\'t completed any goals yet.';
                            }
                            
                            const emptyTitle = emptyState.querySelector('.goal-empty-state-title');
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

            const filterItems = document.querySelectorAll('.goal-filter-item');
            filterItems.forEach(item => {
                item.addEventListener('click', function() {
                    const siblings = item.parentElement.querySelectorAll('.goal-filter-item');
                    siblings.forEach(sibling => sibling.classList.remove('active'));
                    
                    item.classList.add('active');

                    const category = document.querySelector('.goal-filter-section:nth-child(1) .goal-filter-item.active')?.textContent.trim();
                    const timePeriod = document.querySelector('.goal-filter-section:nth-child(2) .goal-filter-item.active')?.textContent.trim();
                    const status = document.querySelector('.goal-filter-section:nth-child(3) .goal-filter-item.active')?.textContent.trim();

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

                const onTrackFilter = document.querySelector('.goal-filter-option[data-status="on-track"]');
                const behindFilter = document.querySelector('.goal-filter-option[data-status="behind"]');
                const completedFilter = document.querySelector('.goal-filter-option[data-status="completed"]');
                
                if (onTrackFilter) onTrackFilter.querySelector('.goal-filter-count').textContent = onTrack;
                if (behindFilter) behindFilter.querySelector('.goal-filter-count').textContent = behind;
                if (completedFilter) completedFilter.querySelector('.goal-filter-count').textContent = completed;
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
                
                document.querySelector('.goal-preview-progress .goal-progress-fill').style.width = progress + '%';
            }
            
            const goalTypeOptions = document.querySelectorAll('.goal-type-option');
            goalTypeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    goalTypeOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('goal_type').value = this.dataset.type;
                });
            });
            
            const mobileFormSteps = document.querySelectorAll('.goal-form-step');
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
                
                const stepToShow = document.querySelector(`.goal-form-step[data-step="${step}"]`);
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
                            document.querySelector('#editGoalModal .goal-preview-progress .goal-progress-fill').style.width = progress + '%';
                            
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
                
                document.querySelector('#editGoalModal .goal-preview-progress .goal-progress-fill').style.width = progress + '%';
            }

            const filterOptions = document.querySelectorAll('.goal-filter-option');
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const siblings = this.parentElement.querySelectorAll('.goal-filter-option');
                    siblings.forEach(sibling => sibling.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    console.log('Filter selected:', this.querySelector('span').textContent);

                });
            });

            const mobileGoalsContainer = document.querySelector('.goal-mobile-goals');
            if (mobileGoalsContainer && document.querySelectorAll('.goal-mobile-goal-card').length > 0) {
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
                const editFormSteps = document.querySelectorAll('#editGoalModal .goal-form-step');
                editFormSteps.forEach(formStep => {
                    formStep.style.display = 'none';
                });
                
                const stepToShow = document.querySelector(`#editGoalModal .goal-form-step[data-step="${step}"]`);
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

            const activeTab = document.querySelector('.goal-mobile-tab.active');
            if (activeTab) {
                const defaultFilter = activeTab.getAttribute('data-filter');
                const mobileGoalCards = document.querySelectorAll('.goal-mobile-goal-card');
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

            const progressCircles = document.querySelectorAll('.goal-progress-circle');
            progressCircles.forEach(progressCircle => {
                if (progressCircle) {
                    const progress = progressCircle.dataset.progress;
                    console.log(`Updating progress circle with value: ${progress}%`);
                    
                    const circle = progressCircle.querySelector('svg circle:nth-child(2)');
                    if (circle) {
                        const radius = circle.getAttribute('r');
                        const circumference = 2 * Math.PI * radius;
                        const dashOffset = circumference - (circumference * progress / 100);
                        circle.style.strokeDasharray = circumference;
                        circle.style.strokeDashoffset = dashOffset;
                    }
                    
                    const progressText = progressCircle.querySelector('.goal-progress-text');
                    if (progressText) {
                        progressText.textContent = `${progress}%`;
                    }
                }
            });
        });
    </script> 
</body>
</html> 