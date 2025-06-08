<?php
require_once 'languages.php';

require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';

if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    header('Content-Type: application/json');
    
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    $user_id = $_SESSION["user_id"];

    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $goals_per_page = 3;
    
    try {
        $count_query = "SELECT COUNT(*) as total FROM goals WHERE user_id = ?";
        $count_params = [$user_id];
        $count_types = "i";
        
        $query = "SELECT * FROM goals WHERE user_id = ?";
        $params = [$user_id];
        $types = "i";
        
        if ($categoryFilter !== 'all') {
            error_log("Applying category filter: " . $categoryFilter);
            
            $count_query .= " AND LOWER(goal_type) = ?";
            $count_params[] = strtolower($categoryFilter);
            $count_types .= "s";
            
            $query .= " AND LOWER(goal_type) = ?";
            $params[] = strtolower($categoryFilter);
            $types .= "s";
        }
        
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'completed') {
                $count_query .= " AND completed = 1";
                $query .= " AND completed = 1";
            } else if ($statusFilter === 'on-track') {
                $count_query .= " AND completed = 0 AND deadline >= NOW()";
                $query .= " AND completed = 0 AND deadline >= NOW()";
            } else if ($statusFilter === 'behind') {
                $count_query .= " AND completed = 0 AND deadline < NOW()";
                $query .= " AND completed = 0 AND deadline < NOW()";
            } else if ($statusFilter === 'active') {
                $count_query .= " AND completed = 0";
                $query .= " AND completed = 0";
            }
        }
        
        $count_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_goals = mysqli_fetch_assoc($count_result)['total'];
        $total_pages = ceil($total_goals / $goals_per_page);
        
        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $goals_per_page;
        
        $query .= " ORDER BY deadline ASC LIMIT ? OFFSET ?";
        $params[] = $goals_per_page;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $goals = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $progress = min(100, round(($row['current_value'] / $row['target_value']) * 100));
            $daysLeft = ceil((strtotime($row['deadline']) - time()) / (60 * 60 * 24));
            
            $status = 'on-track';
            if ($row['completed']) {
                $status = 'completed';
            } elseif (strtotime($row['deadline']) < time()) {
                $status = 'behind';
            }
            
            $row['progress'] = $progress;
            $row['days_left'] = $daysLeft;
            $row['status'] = $status;
            $row['formatted_deadline'] = date('M d, Y', strtotime($row['deadline']));
            
            $goals[] = $row;
        }
        
        echo json_encode([
            'goals' => $goals,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_goals' => $total_goals,
                'start' => min(($page - 1) * $goals_per_page + 1, $total_goals),
                'end' => min($page * $goals_per_page, $total_goals)
            ],
            'filters' => [
                'category' => $categoryFilter,
                'status' => $statusFilter
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/current-goal.php");
    exit;
}

$goalTypeIcons = [
    'strength' => '💪',
    'weight' => '⚖️',
    'nutrition' => '🥗',
    'endurance' => '⏱️',
    'workout' => '🏃'
];

error_log("Goal type icons defined as: " . json_encode($goalTypeIcons));

$user_id = $_SESSION["user_id"];

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
$upcomingDeadlines = array_slice($upcomingDeadlines, 0, 5);

function getGoalIcon($goalType) {
    global $goalTypeIcons;
    $type = strtolower($goalType);
    return isset($goalTypeIcons[$type]) ? $goalTypeIcons[$type] : $goalTypeIcons['other'];
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_goal"])) {
        error_log("=== POST GOAL SUBMISSION ===");
        error_log("POST: " . json_encode($_POST));
        
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $target_value = floatval($_POST["target_value"]);
        $current_value = floatval($_POST["current_value"]);
        $goal_type = trim($_POST["goal_type"]);
        
        error_log("Raw goal_type received: '" . $goal_type . "'");
        
        $valid_goal_types = ['weight', 'strength', 'endurance', 'workout', 'nutrition'];
        
        $goal_type_lower = strtolower($goal_type);
        
        if (!in_array($goal_type_lower, $valid_goal_types)) {
            error_log("Invalid goal type: '$goal_type_lower' not in valid ENUM types. Defaulting to 'workout'");
            $goal_type = 'workout';
        } else {
            foreach ($valid_goal_types as $type) {
                if (strtolower($type) === $goal_type_lower) {
                    $goal_type = $type;
                    break;
                }
            }
        }
        
        error_log("Final goal_type for database: '" . $goal_type . "'");
        
        $deadline = $_POST["deadline"];
        
        error_log("=== GOAL CREATION DEBUGGING ===");
        error_log("POST data received: " . json_encode($_POST));
        error_log("Goal type received: '" . $goal_type . "'");
        error_log("Goal type validation - Empty: " . (empty($goal_type) ? 'true' : 'false'));
        error_log("Valid ENUM types: " . implode(", ", $valid_goal_types));
        error_log("Goal type in valid ENUM list: " . (in_array($goal_type, $valid_goal_types) ? 'true' : 'false'));
        
        $valid_goal_types = array_keys($goalTypeIcons);
        error_log("Valid goal types: " . implode(", ", $valid_goal_types));
        error_log("Goal type in valid list: " . (in_array($goal_type, $valid_goal_types) ? 'true' : 'false'));
        
        error_log("Goal type icons array: " . json_encode($goalTypeIcons));
        
        if (empty($title) || empty($goal_type) || empty($deadline)) {
            $message = "Please fill out all required fields.";
            $message_type = "error";
            
            $missing = [];
            if (empty($title)) $missing[] = "title";
            if (empty($goal_type)) $missing[] = "goal_type";
            if (empty($deadline)) $missing[] = "deadline";
            error_log("Missing required fields: " . implode(", ", $missing));
        } else {
            try {
                error_log("Attempting to insert goal with type: " . $goal_type);
                
                $query = "INSERT INTO goals (user_id, title, description, target_value, current_value, goal_type, deadline, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issddss", $user_id, $title, $description, $target_value, $current_value, $goal_type, $deadline);
               
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Goal added successfully!";
                    $message_type = "success";
                    $new_goal_id = mysqli_insert_id($conn);
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $message, 'goal_id' => $new_goal_id]);
                        exit;
                    }
                    
                    header("Location: current-goal.php?success=1");
                    exit;
                } else {
                    $message = "Error: " . mysqli_error($conn);
                    $message_type = "error";
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $message]);
                        exit;
                    }
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = "error";
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $message]);
                    exit;
                }
            }
        }
    } else if (isset($_POST["update_goal"])) {
        
        $goal_id = intval($_POST["goal_id"]);
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $target_value = floatval($_POST["target_value"]);
        $current_value = floatval($_POST["current_value"]);
        $goal_type = trim($_POST["goal_type"]);
        
        $valid_goal_types = ['weight', 'strength', 'endurance', 'workout', 'nutrition'];
        
        $goal_type_lower = strtolower($goal_type);
        
        if (!in_array($goal_type_lower, $valid_goal_types)) {
            $goal_type = 'workout';
        } else {
            foreach ($valid_goal_types as $type) {
                if (strtolower($type) === $goal_type_lower) {
                    $goal_type = $type;
                    break;
                }
            }
        }
        
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
                error_log("Goal updated successfully: $goal_id");
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message, 'goal_id' => $goal_id]);
                    exit;
                }
                
                header("Location: current-goal.php?success=1");
                exit;
            } else {
                $message = "Error: " . mysqli_error($conn);
                $message_type = "error";
                error_log("Database error when updating goal: " . mysqli_error($conn));
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $message]);
                    exit;
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
            error_log("Exception when updating goal: " . $e->getMessage());
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
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
        $isMobile = isset($_GET['view']) && $_GET['view'] === 'mobile' || 
                   (isset($_SERVER['HTTP_USER_AGENT']) && 
                   (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
                    strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false));
        
        $categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
        
        $goals_per_page = 3;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $page = max(1, $page);
        $offset = ($page - 1) * $goals_per_page;
        
        $count_query = "SELECT COUNT(*) as total FROM goals WHERE user_id = ?";
        $count_params = [$user_id];
        $count_types = "i";
        
        $query = "SELECT * FROM goals WHERE user_id = ?";
        $params = [$user_id];
        $types = "i";
            
        if ($categoryFilter !== 'all') {
            error_log("Applying category filter: " . $categoryFilter);
            
            $count_query .= " AND LOWER(goal_type) = ?";
            $count_params[] = strtolower($categoryFilter);
            $count_types .= "s";
            
            $query .= " AND LOWER(goal_type) = ?";
            $params[] = strtolower($categoryFilter);
            $types .= "s";
        }
        
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'completed') {
                $count_query .= " AND completed = 1";
                $query .= " AND completed = 1";
            } else if ($statusFilter === 'on-track') {
                $count_query .= " AND completed = 0 AND deadline >= NOW()";
                $query .= " AND completed = 0 AND deadline >= NOW()";
            } else if ($statusFilter === 'behind') {
                $count_query .= " AND completed = 0 AND deadline < NOW()";
                $query .= " AND completed = 0 AND deadline < NOW()";
            } else if ($statusFilter === 'active') {
                $count_query .= " AND completed = 0";
                $query .= " AND completed = 0";
            }
        }
        
        $count_stmt = mysqli_prepare($conn, $count_query);
        if (count($count_params) > 1) {
            mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
        } else {
            mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        }
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_goals = mysqli_fetch_assoc($count_result)['total'];
        $total_pages = ceil($total_goals / $goals_per_page);
        
        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
            $offset = ($page - 1) * $goals_per_page;
        }
        
        $query .= " ORDER BY deadline ASC LIMIT ? OFFSET ?";
        $params[] = $goals_per_page;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
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
            goal_type ENUM('weight','strength','endurance','workout','nutrition') NOT NULL, 
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
                    <h2><?= t('your_active_goals') ?></h2>
                    <button class="goal-btn goal-btn-primary goal-create-btn" id="addGoalBtn">
                        <i class="fas fa-plus"></i> <?= t('create_new_goal') ?>
                    </button>
        </div>
        <div class="goal-main-content">
            <div class="goal-left-filters">
                <div class="goal-filter-category">
                    <h3><?= t('categories') ?></h3>
                    <div class="goal-filter-option active" data-filter="all">
                        <span class="goal-filter-count"><?= count($goals) ?></span>
                        <span><?= t('all_categories') ?></span>
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
                            <span><?= t(strtolower($type)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="goal-filter-category">
                    <h3><?= t('all_status') ?></h3>
                    <div class="goal-filter-option active" data-status="all">
                        <span class="goal-filter-count"><?= count($goals) ?></span>
                        <span><?= t('all') ?></span>
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
                        <span><?= t('on_track') ?></span>
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
                        <span><?= t('behind') ?></span>
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
                        <span><?= t('completed') ?></span>
                    </div>
                </div>
            </div>
            <div class="goal-goal-cards">
                <?php if (empty($active_goals)): ?>
                    <div class="goal-no-goals" style="display: block !important;">
                        <div class="goal-empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="goal-empty-state-title"><?= t('no_goals_yet') ?></h3>
                        <p class="goal-empty-state-text"><?= t('setting_goals_first_step') ?></p>
                        <button class="goal-btn goal-btn-primary goal-create-btn">
                            <i class="fas fa-plus"></i> <?= t('create_first_goal') ?>
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
                                    <span class="goal-status-badge <?= $status ?>"><?= t($status) ?></span>
                                </h3>
                                <div class="goal-actions">
                                    <button class="goal-action-btn goal-delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <span class="goal-type"><?= t(strtolower($goal['goal_type'])) ?></span>
                                </div>
                            </div>
                            <p class="goal-description"><?= htmlspecialchars($goal['description'] ?: t('no_description')) ?></p>
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
                                <span class="deadline"><?= t('deadline') ?>: <?= date('M d, Y', strtotime($goal['deadline'])) ?></span>
                                <span class="days-left"><?= $daysLeft ?> <?= t('days_left') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                
                
                
                <div class="goal-mobile-pagination">
                    <div class="goal-mobile-pagination-controls">
                        <button class="goal-btn goal-pagination-prev" id="mobile-pagination-prev" data-page="<?= max(1, $page - 1) ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        
                        <span class="goal-pagination-info">
                            Page <span id="mobile-pagination-current"><?= $page ?></span> of <span id="mobile-pagination-total"><?= $total_pages ?></span>
                        </span>
                        
                        <button class="goal-btn goal-pagination-next" id="mobile-pagination-next" data-page="<?= min($total_pages, $page + 1) ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="goal-right-sidebar">
                <div class="goal-summary">
                    <h2><?= t('goals_summary') ?></h2>
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
                        <p><?= t('completion_rate') ?></p>
                    </div>
                </div>

                <div class="goal-upcoming-deadlines">
                    <h2><?= t('upcoming_deadlines') ?></h2>
                    <?php if (empty($upcomingDeadlines)): ?>
                        <p><?= t('no_upcoming_deadlines') ?></p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($upcomingDeadlines as $goal): 
                                $daysLeft = ceil((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                            ?>
                                <li>
                                    <span class="goal-deadline-goal"><?= htmlspecialchars($goal['title']) ?></span>
                                    <span class="goal-deadline-date"><?= $daysLeft ?> <?= t('days_left') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
            </div>
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div class="goal-pagination">
                    <div class="goal-pagination-info">
                        <?= t('showing') ?> <span id="pagination-start"><?= min(($page - 1) * $goals_per_page + 1, $total_goals) ?></span> <?= t('to') ?> 
                        <span id="pagination-end"><?= min($page * $goals_per_page, $total_goals) ?></span> <?= t('of') ?> 
                        <span id="pagination-total"><?= $total_goals ?></span> <?= t('goals') ?>
                    </div>
                    <div class="goal-pagination-controls" id="pagination-controls">
                        <button class="goal-pagination-button goal-pagination-prev" id="pagination-prev" data-page="<?= max(1, $page - 1) ?>">
                            <i class="fas fa-chevron-left"></i> <?= t('previous') ?>
                        </button>
                        
                        <?php
                        $range = 2;
                        $start_page = max(1, $page - $range);
                        $end_page = min($total_pages, $page + $range);
                        
                        if ($start_page > 1) {
                            echo '<button class="goal-pagination-button" data-page="1">1</button>';
                            if ($start_page > 2) {
                                echo '<span class="goal-pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<button class="goal-pagination-button ' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="goal-pagination-ellipsis">...</span>';
                            }
                            echo '<button class="goal-pagination-button" data-page="' . $total_pages . '">' . $total_pages . '</button>';
                        }
                        ?>
                        
                        <button class="goal-pagination-button goal-pagination-next" id="pagination-next" data-page="<?= min($total_pages, $page + 1) ?>">
                            <?= t('next') ?> <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
        </div>

        <div class="goal-mobile-view">
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
                <div><?= t('overall_progress') ?></div>
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
                    <div><?= t('next_milestone') ?> <?= $daysToNext ?> <?= t('days_left') ?></div>
                <?php else: ?>
                    <div><?= t('no_upcoming_milestones') ?></div>
                <?php endif; ?>
            </div>
            
            <div class="goal-mobile-tabs">
                <div class="goal-mobile-tab active" data-filter="active"><?= t('active') ?></div>
                <div class="goal-mobile-tab" data-filter="completed"><?= t('completed') ?></div>
                <div class="goal-mobile-tab" data-filter="all"><?= t('all') ?></div>
            </div>
            
            <div class="goal-mobile-goals">
                <?php if (empty($active_goals)): ?>
                    <div class="goal-empty-state" style="display: block !important;">
                        <div class="goal-empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="goal-empty-state-title"><?= t('no_goals_yet') ?></h3>
                        <p class="goal-empty-state-text"><?= t('setting_goals_first_step') ?></p>
                        <button class="goal-btn goal-btn-primary" id="mobileAddGoalBtn">
                            <i class="fas fa-plus"></i> <?= t('create_first_goal') ?>
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
                                    <button class="goal-action-btn goal-delete-btn" onclick="deleteGoal(<?= $goal['id'] ?>); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="goal-target">
                                <div><?= t('current') ?>: <?= $goal['current_value'] ?></div>
                                <div><?= t('target') ?>: <?= $goal['target_value'] ?></div>
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
                    
                    <div class="goal-mobile-pagination">
                        <div class="goal-mobile-pagination-controls">
                            <button class="goal-btn goal-pagination-prev" id="mobile-view-pagination-prev" data-page="<?= max(1, $page - 1) ?>">
                                <i class="fas fa-chevron-left"></i> <?= t('previous') ?>
                            </button>
                            
                            <span class="goal-pagination-info">
                                <?= t('page') ?> <span id="mobile-view-pagination-current"><?= $page ?></span> <?= t('of') ?> <span id="mobile-view-pagination-total"><?= $total_pages ?></span>
                            </span>
                            
                            <button class="goal-btn goal-pagination-next" id="mobile-view-pagination-next" data-page="<?= min($total_pages, $page + 1) ?>">
                                <?= t('next') ?> <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
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
                <h2 class="goal-modal-title"><?= t('create_new_goal') ?></h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="goalForm">
                            <input type="hidden" name="add_goal" value="1">
                            
                            <div class="goal-form-section">
                                <h3><?= t('basic_information') ?></h3>
                                <div class="goal-form-group">
                                    <input type="text" id="title" name="title" class="goal-form-control" placeholder="<?= t('goal_title') ?>" required>
                                </div>
                                
                                <div class="goal-form-group">
                                    <textarea id="description" name="description" class="goal-form-control" rows="3" placeholder="<?= t('description') ?>"></textarea>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="goal_type" name="goal_type" class="goal-form-control" required>
                                        <option value=""><?= t('select_category') ?></option>
                                        <?php 
                                        error_log("Populating goal type dropdown with options: " . json_encode($goalTypeIcons));
                                        
                                        foreach ($goalTypeIcons as $type => $icon): 
                                            $typeValue = strtolower($type);
                                            $displayName = t($typeValue);
                                            
                                            error_log("Adding goal type option: value='$typeValue', display='$displayName', icon='$icon'");
                                        ?>
                                            <option value="<?= htmlspecialchars($typeValue) ?>">
                                                <?= $icon ?> <?= $displayName ?> 
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
                                <h3><?= t('target_setting') ?></h3>
                                <div class="goal-form-row">
                                    <div class="goal-form-group">
                                        <input type="number" id="current_value" name="current_value" class="goal-form-control" step="0.01" placeholder="<?= t('starting_value') ?>">
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <input type="number" id="target_value" name="target_value" class="goal-form-control" step="0.01" placeholder="<?= t('target_value') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="unit" name="unit" class="goal-form-control">
                                        <option value=""><?= t('select_unit') ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
                                <h3><?= t('timeline') ?></h3>
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
                                <button type="submit" class="goal-btn goal-btn-primary"><?= t('create_goal') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="goal-modal-content goal-mobile-goal-form">
            <div class="goal-mobile-form-header">
                <button class="goal-back-button" id="mobileFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2><?= t('create_new_goal') ?></h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-mobile-form-steps">
                <div class="goal-form-step" data-step="1">
                    <div class="goal-form-group">
                        <input type="text" name="mobile_title" placeholder="<?= t('goal_title') ?>" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <textarea name="mobile_description" placeholder="<?= t('description') ?>" class="goal-form-control" rows="4"></textarea>
                    </div>
                    
                    <input type="hidden" name="mobile_goal_type" value="">
                    
                    <div class="goal-types-grid">
                        <div class="goal-type-card" data-type="strength">
                            <div class="goal-type-icon">💪</div>
                            <div class="goal-type-name"><?= t('strength') ?></div>
                        </div>
                        <div class="goal-type-card" data-type="workout">
                            <div class="goal-type-icon">🏃</div>
                            <div class="goal-type-name"><?= t('workout') ?></div>
                        </div>
                        <div class="goal-type-card" data-type="endurance">
                            <div class="goal-type-icon">⏱️</div>
                            <div class="goal-type-name"><?= t('endurance') ?></div>
                        </div>
                        <div class="goal-type-card" data-type="weight">
                            <div class="goal-type-icon">⚖️</div>
                            <div class="goal-type-name"><?= t('weight') ?></div>
                        </div>
                        <div class="goal-type-card" data-type="nutrition">
                            <div class="goal-type-icon">🥗</div>
                            <div class="goal-type-name"><?= t('nutrition') ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="goal-form-step" data-step="2">
                    <h3><?= t('set_your_target') ?></h3>
                    
                    <div class="goal-form-group">
                        <label><?= t('starting_value') ?></label>
                        <input type="number" name="mobile_current_value" class="goal-form-control" step="0.01" placeholder="<?= t('starting_value') ?>">
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('target_value') ?></label>
                        <input type="number" name="mobile_target_value" class="goal-form-control" step="0.01" required>
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('select_unit') ?></label>
                        <select name="mobile_unit" id="mobile_unit" class="goal-form-control">
                            <option value=""><?= t('select_unit') ?></option>
                        </select>
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('start_date') ?></label>
                        <input type="date" name="mobile_start_date" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('target_date') ?></label>
                        <input type="date" name="mobile_deadline" class="goal-form-control">
                    </div>
                </div>
            </div>
            
            <div class="goal-mobile-form-actions">
                <button class="goal-btn goal-btn-primary goal-btn-continue" id="mobileFormContinue"><?= t('continue') ?></button>
                <button class="goal-btn goal-btn-primary goal-btn-create" id="mobileFormCreate"><?= t('create_goal') ?></button>
            </div>
        </div>
    </div>
    
    <div class="goal-modal" id="editGoalModal">
        <div class="goal-modal-content goal-desktop-goal-form">
            <div class="goal-modal-header">
                <h2 class="goal-modal-title"><?= t('edit_goal') ?></h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-modal-body">
                <div class="goal-form-container">
                    <div class="goal-form">
                        <form action="current-goal.php" method="post" id="editGoalForm">
                            <input type="hidden" name="update_goal" value="1">
                            <input type="hidden" name="goal_id" id="edit_goal_id">
                            
                            <div class="goal-form-section">
                                <h3><?= t('basic_information') ?></h3>
                                <div class="goal-form-group">
                                    <input type="text" id="edit_title" name="title" class="goal-form-control" placeholder="<?= t('goal_title') ?>" required>
                                </div>
                                
                                <div class="goal-form-group">
                                    <textarea id="edit_description" name="description" class="goal-form-control" rows="3" placeholder="<?= t('description') ?>"></textarea>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="edit_goal_type" name="goal_type" class="goal-form-control" required>
                                        <option value=""><?= t('select_category') ?></option>
                                        <?php 
                                        foreach ($goalTypeIcons as $type => $icon): 
                                            $typeValue = strtolower($type);
                                            $displayName = t($typeValue);
                                        ?>
                                            <option value="<?= htmlspecialchars($typeValue) ?>">
                                                <?= $icon ?> <?= $displayName ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="goal-form-section">
                                <h3><?= t('target_setting') ?></h3>
                                <h3>Target Setting</h3>
                                <div class="goal-form-row">
                                    <div class="goal-form-group">
                                        <input type="number" id="edit_current_value" name="current_value" class="goal-form-control" step="0.01" placeholder="Current Value">
                                    </div>
                                    
                                    <div class="goal-form-group">
                                        <input type="number" id="edit_target_value" name="target_value" class="goal-form-control" step="0.01" placeholder="Target Value" required>
                                    </div>
                                </div>
                                
                                <div class="goal-form-group">
                                    <select id="edit_unit" name="unit" class="goal-form-control">
                                        <option value="">Select Unit</option>
                                    </select>
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
                </div>
            </div>
        </div>
        
        <div class="goal-modal-content goal-mobile-goal-form">
            <div class="goal-mobile-form-header">
                <button class="goal-back-button" id="mobileEditFormBack"><i class="fas fa-arrow-left"></i></button>
                <h2><?= t('edit_goal') ?></h2>
                <button class="goal-modal-close">&times;</button>
            </div>
            
            <div class="goal-mobile-form-steps">
                <div class="goal-form-step" data-step="1">
                    <div class="goal-form-group">
                        <label><?= t('goal_title') ?></label>
                        <input type="text" name="mobile_edit_title" id="mobile_edit_title" placeholder="<?= t('goal_title') ?>" class="goal-form-control">
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('description') ?></label>
                        <textarea name="mobile_edit_description" id="mobile_edit_description" placeholder="<?= t('description') ?>" class="goal-form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="goal-form-group">
                        <label><?= t('category') ?></label>
                        <select name="mobile_edit_goal_type" id="mobile_edit_goal_type" class="goal-form-control">
                            <option value=""><?= t('select_category') ?></option>
                            <?php foreach ($goalTypeIcons as $type => $icon): ?>
                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= $icon ?> <?= t(strtolower($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="goal-form-step" data-step="2">
                    <h3><?= t('set_your_target') ?></h3>
                    
                    <div class="goal-form-group">
                        <label><?= t('current_value') ?></label>
                        <label>Current Value</label>
                        <input type="number" name="mobile_edit_current_value" id="mobile_edit_current_value" class="goal-form-control" step="0.01" placeholder="Current Value">
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Target Value</label>
                        <input type="number" name="mobile_edit_target_value" id="mobile_edit_target_value" class="goal-form-control" step="0.01" required>
                    </div>
                    
                    <div class="goal-form-group">
                        <label>Unit</label>
                        <select name="mobile_edit_unit" id="mobile_edit_unit" class="goal-form-control">
                            <option value="">Select Unit</option>
                        </select>
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
        const translations = {
            confirmDeleteGoal: '<?= t('confirm_delete_goal') ?>',
            noGoalsMatchFilters: '<?= t('no_goals_match_filters') ?>',
            tryChangingFilters: '<?= t('try_changing_filters') ?>',
            loading: '<?= t('loading') ?>',
            loadingGoals: '<?= t('loading') ?> <?= t('goals') ?>...',
            retry: '<?= t('retry') ?>',
            errorLoadingGoals: '<?= t('error_loading_goals') ?>',
            errorFetchingGoalDetails: '<?= t('error_fetching_goal_details') ?>',
            pleaseLoginAgain: '<?= t('please_try_again') ?>',
            errorDeletingGoal: '<?= t('error_deleting_goal') ?>',
            errorAddingGoal: '<?= t('error_adding_goal') ?>',
            errorUpdatingGoal: '<?= t('error_updating_goal') ?>',
            page: '<?= t('page') ?>',
            of: '<?= t('of') ?>',
            next: '<?= t('next') ?>',
            previous: '<?= t('previous') ?>',
            goalAdded: '<?= t('goal_added_successfully') ?>',
            goalUpdated: '<?= t('goal_updated_successfully') ?>',
            showing: '<?= t('showing') ?>',
            to: '<?= t('to') ?>',
            goals: '<?= t('goals') ?>',
            deadline: '<?= t('deadline') ?>',
            daysLeft: '<?= t('days_left') ?>',
            noDescription: '<?= t('no_description') ?>',
            overdueBy: '<?= t('overdue_by') ?>',
            days: '<?= t('days') ?>',
            hours: '<?= t('hours') ?>',
            dueToday: '<?= t('due_today') ?>',
            dueTomorrow: '<?= t('due_tomorrow') ?>',
            dueIn: '<?= t('due_in') ?>',
            weeks: '<?= t('weeks') ?>',
            dueOn: '<?= t('due_on') ?>',
            current: '<?= t('current') ?>',
            target: '<?= t('target') ?>',
            'on-track': '<?= t('on_track') ?>',
            'behind': '<?= t('behind') ?>',
            'completed': '<?= t('completed') ?>',
            'strength': '<?= t('strength') ?>',
            'weight': '<?= t('weight') ?>',
            'endurance': '<?= t('endurance') ?>',
            'workout': '<?= t('workout') ?>',
            'nutrition': '<?= t('nutrition') ?>'
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('.goal-modal');
            const addGoalBtn = document.getElementById('addGoalBtn');
            const mobileAddGoalBtn = document.getElementById('mobileAddGoalBtn');
            const startNewGoalBtn = document.getElementById('startNewGoalBtn');
            const updateGoalBtns = document.querySelectorAll('.update-goal-btn');
            const deleteGoalBtns = document.querySelectorAll('.delete-goal-btn');
            const closeBtns = document.querySelectorAll('.goal-modal-close, .close-modal');
            
            function setDefaultDates() {
                const today = new Date();
                const formattedDate = today.toISOString().split('T')[0]; 
                
                const startDateInput = document.getElementById('start_date');
                if (startDateInput) {
                    startDateInput.value = formattedDate;
                }
                
                const mobileStartDateInput = document.querySelector('input[name="mobile_start_date"]');
                if (mobileStartDateInput) {
                    mobileStartDateInput.value = formattedDate;
                }
            }
            
            let currentCategoryFilter = localStorage.getItem('goalCategoryFilter') || 'all';
            let currentStatusFilter = localStorage.getItem('goalStatusFilter') || 'all';
            let currentPage = 1;
            
            const goalCards = document.querySelectorAll('.goal-card');
            
            const goalCardsContainer = document.querySelector('.goal-goal-cards');
            const paginationContainer = document.querySelector('.goal-pagination');
            const mobilePaginationContainer = document.querySelector('.goal-mobile-pagination');
            
            function loadGoals(page = 1, category = 'all', status = 'all') {
                if (goalCardsContainer) {
                    goalCardsContainer.innerHTML = '<div class="goal-loading"><i class="fas fa-spinner fa-spin"></i> Loading goals...</div>';
                }
                
                const url = new URL(window.location);
                url.searchParams.set('page', page);
                url.searchParams.set('category', category);
                url.searchParams.set('status', status);
                window.history.pushState({}, '', url);
                
                fetch(`current-goal.php?ajax=true&page=${page}&category=${category}&status=${status}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        currentPage = data.pagination.current_page;
                        currentCategoryFilter = data.filters.category;
                        currentStatusFilter = data.filters.status;
                        
                        localStorage.setItem('goalCategoryFilter', currentCategoryFilter);
                        localStorage.setItem('goalStatusFilter', currentStatusFilter);
                        
                        updateActiveFilters();
                        
                        updateGoalCards(data.goals);
                        
                        updatePagination(data.pagination);
                        
                        console.log(`Loaded ${data.goals.length} goals. Page ${data.pagination.current_page} of ${data.pagination.total_pages}`);
                    })
                    .catch(error => {
                        console.error('Error loading goals:', error);
                        goalCardsContainer.innerHTML = `
                            <div class="goal-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>${translations.errorLoadingGoals}</p>
                                <button class="goal-btn goal-btn-primary" onclick="loadGoals(${page}, '${category}', '${status}')">
                                    ${translations.retry}
                                </button>
                            </div>
                        `;
                    });
            }
            
            function updateGoalCards(goals) {
                if (!goalCardsContainer) return;
                
                if (goals.length === 0) {
                    goalCardsContainer.innerHTML = `
                        <div class="goal-no-goals" style="display: block !important;">
                            <div class="goal-empty-state-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3 class="goal-empty-state-title">${translations.noGoalsMatchFilters}</h3>
                            <p class="goal-empty-state-text">${translations.tryChangingFilters}</p>
                            <button class="goal-btn goal-btn-primary goal-create-btn">
                                <i class="fas fa-plus"></i> <?= t('create_new_goal') ?>
                            </button>
                        </div>
                    `;
                    
                    const createBtn = goalCardsContainer.querySelector('.goal-create-btn');
                    if (createBtn) {
                        createBtn.addEventListener('click', () => openModal('addGoalModal'));
                    }
                    
                    return;
                }
                
                goalCardsContainer.innerHTML = '';
                
                goals.forEach(goal => {
                    const goalCard = document.createElement('div');
                    goalCard.className = 'goal-card';
                    goalCard.dataset.id = goal.id;
                    goalCard.dataset.type = goal.goal_type.toLowerCase();
                    goalCard.dataset.completed = goal.completed ? 'true' : 'false';
                    goalCard.dataset.status = goal.status;
                    goalCard.onclick = function() { viewGoal(goal.id); };
                    
                    goalCard.innerHTML = `
                        <div class="goal-header">
                            <h3>
                                ${goal.title}
                                <span class="goal-status-badge ${goal.status}">${translations[goal.status]}</span>
                            </h3>
                            <div class="goal-actions">
                                <button class="goal-action-btn goal-delete-btn" onclick="event.stopPropagation(); deleteGoal(${goal.id});">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <span class="goal-type">${translations[goal.goal_type.toLowerCase()]}</span>
                            </div>
                        </div>
                        <p class="goal-description">${goal.description || translations.noDescription}</p>
                        <div class="goal-progress" data-completed="${goal.completed ? 'true' : 'false'}">
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill" style="width: ${goal.progress}%"></div>
                            </div>
                            <div class="goal-progress-numbers">
                                <span class="current">${goal.current_value}</span>
                                <span class="target">/ ${goal.target_value}</span>
                            </div>
                        </div>
                        <div class="goal-footer">
                            <span class="deadline">${translations.deadline}: ${goal.formatted_deadline}</span>
                            <span class="days-left">${goal.days_left} ${translations.daysLeft}</span>
                        </div>
                    `;
                    
                    goalCardsContainer.appendChild(goalCard);
                });
            
                const mobileGoalsContainer = document.querySelector('.goal-mobile-goals');
                if (mobileGoalsContainer) {
                    const existingMobileCards = mobileGoalsContainer.querySelectorAll('.goal-mobile-goal-card');
                    existingMobileCards.forEach(card => card.remove());
                    
                    const otherContent = Array.from(mobileGoalsContainer.children).filter(
                        el => !el.classList.contains('goal-mobile-goal-card')
                    );
                    
                    if (goals.length === 0) {
                        return;
                    }
                    
                    existingMobileCards.forEach(card => card.remove());
                    
                    goals.forEach(goal => {
                        const progress = Math.min(100, Math.round((goal.current_value / goal.target_value) * 100));
                        const isCompleted = goal.completed ? 'true' : 'false';
                        
                        const mobileCard = document.createElement('div');
                        mobileCard.className = 'goal-mobile-goal-card';
                        mobileCard.dataset.completed = isCompleted;
                        mobileCard.onclick = function() { viewGoal(goal.id); };
                        
                        mobileCard.innerHTML = `
                            <div class="goal-mobile-goal-title">
                                ${goal.title}
                                <div class="goal-mobile-goal-actions">
                                    <button class="goal-action-btn goal-delete-btn" onclick="deleteGoal(${goal.id}); event.stopPropagation();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="goal-target">
                                <div>${translations.current}: ${goal.current_value}</div>
                                <div>${translations.target}: ${goal.target_value}</div>
                            </div>
                            
                            <div class="goal-target">
                                <div>${progress}%</div>
                            </div>
                            
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill ${goal.goal_type}" style="width: ${progress}%"></div>
                            </div>
                            
                            <div>
                                <i class="fas fa-clock"></i> ${formatDeadline(goal.formatted_deadline)}
                            </div>
                        `;
                    
                        const pagination = mobileGoalsContainer.querySelector('.goal-mobile-pagination');
                        if (pagination) {
                            mobileGoalsContainer.insertBefore(mobileCard, pagination);
                        } else {
                            mobileGoalsContainer.appendChild(mobileCard);
                        }
                    });
                }
                
                document.querySelectorAll('.goal-delete-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const goalId = this.closest('[data-id]').dataset.id || this.closest('.goal-card').dataset.id;
                        deleteGoal(goalId);
                    });
                });
            }
            
            function updatePagination(pagination) {
                
                let paginationContainer = document.querySelector('.goal-pagination');
                const goalCardsContainer = document.querySelector('.goal-goal-cards');
                const rightSidebar = document.querySelector('.goal-right-sidebar');
                
                if (!paginationContainer && goalCardsContainer) {
                    console.log('Creating desktop pagination container');
                    paginationContainer = document.createElement('div');
                    paginationContainer.className = 'goal-pagination';
                    
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'goal-pagination-info';
                    paginationContainer.appendChild(infoDiv);
                    
                    const controlsDiv = document.createElement('div');
                    controlsDiv.className = 'goal-pagination-controls';
                    controlsDiv.id = 'pagination-controls';
                    paginationContainer.appendChild(controlsDiv);
                    
                    if (rightSidebar && rightSidebar.parentNode) {
                        rightSidebar.parentNode.insertBefore(paginationContainer, rightSidebar.nextSibling);
                    } else {
                        goalCardsContainer.parentNode.insertBefore(paginationContainer, goalCardsContainer.nextSibling);
                    }
                }
                
                let mobilePaginationContainer = document.querySelector('.goal-mobile-pagination');
                
                if (!mobilePaginationContainer && goalCardsContainer) {
                    console.log('Creating mobile pagination container');
                    mobilePaginationContainer = document.createElement('div');
                    mobilePaginationContainer.className = 'goal-mobile-pagination';
                    
                    const mobileControlsDiv = document.createElement('div');
                    mobileControlsDiv.className = 'goal-mobile-pagination-controls';
                    
                    const mobilePrevBtn = document.createElement('button');
                    mobilePrevBtn.className = 'goal-btn goal-pagination-prev';
                    mobilePrevBtn.id = 'mobile-pagination-prev';
                    mobilePrevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> ' + translations.previous;
                    mobileControlsDiv.appendChild(mobilePrevBtn);
                    
                    const mobileInfoSpan = document.createElement('span');
                    mobileInfoSpan.className = 'goal-pagination-info';
                    mobileInfoSpan.innerHTML = translations.page + ' <span id="mobile-pagination-current">1</span> ' + translations.of + ' <span id="mobile-pagination-total">1</span>';
                    mobileControlsDiv.appendChild(mobileInfoSpan);
                    
                    const mobileNextBtn = document.createElement('button');
                    mobileNextBtn.className = 'goal-btn goal-pagination-next';
                    mobileNextBtn.id = 'mobile-pagination-next';
                    mobileNextBtn.innerHTML = translations.next + ' <i class="fas fa-chevron-right"></i>';
                    mobileControlsDiv.appendChild(mobileNextBtn);
                    
                    mobilePaginationContainer.appendChild(mobileControlsDiv);
                    
                    goalCardsContainer.parentNode.insertBefore(mobilePaginationContainer, goalCardsContainer.nextSibling.nextSibling);
                }
                
                const mobileViewContainer = document.querySelector('.goal-mobile-goals');
                let mobileViewPaginationContainer = document.querySelector('.goal-mobile-view .goal-mobile-pagination');
                
                if (!mobileViewPaginationContainer && mobileViewContainer) {
                    console.log('Creating mobile view pagination container');
                    mobileViewPaginationContainer = document.createElement('div');
                    mobileViewPaginationContainer.className = 'goal-mobile-pagination';
                    
                    const mobileViewControlsDiv = document.createElement('div');
                    mobileViewControlsDiv.className = 'goal-mobile-pagination-controls';
                    
                    const mobileViewPrevBtn = document.createElement('button');
                    mobileViewPrevBtn.className = 'goal-btn goal-pagination-prev';
                    mobileViewPrevBtn.id = 'mobile-view-pagination-prev';
                    mobileViewPrevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Previous';
                    mobileViewControlsDiv.appendChild(mobileViewPrevBtn);
                    
                    const mobileViewInfoSpan = document.createElement('span');
                    mobileViewInfoSpan.className = 'goal-pagination-info';
                    mobileViewInfoSpan.innerHTML = translations.page + ' <span id="mobile-view-pagination-current">1</span> ' + translations.of + ' <span id="mobile-view-pagination-total">1</span>';
                    mobileViewControlsDiv.appendChild(mobileViewInfoSpan);
                    
                    const mobileViewNextBtn = document.createElement('button');
                    mobileViewNextBtn.className = 'goal-btn goal-pagination-next';
                    mobileViewNextBtn.id = 'mobile-view-pagination-next';
                    mobileViewNextBtn.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
                    mobileViewControlsDiv.appendChild(mobileViewNextBtn);
                    
                    mobileViewPaginationContainer.appendChild(mobileViewControlsDiv);
                    
                    mobileViewContainer.appendChild(mobileViewPaginationContainer);
                }
                
                if (!paginationContainer && !mobilePaginationContainer && !mobileViewPaginationContainer) {
                    console.error('Cannot create any pagination container - parent elements not found');
                    return;
                }
                
                const styleElement = document.getElementById('dynamic-pagination-styles');
                if (!styleElement) {
                    const css = `
                        .goal-pagination {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            margin-top: 20px;
                            margin-bottom: 20px;
                        }
                        .goal-pagination-info {
                            margin-bottom: 10px;
                            color: #ccc;
                            font-size: 14px;
                        }
                        .goal-pagination-controls {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .goal-pagination-ellipsis {
                            margin: 0 5px;
                            color: #fff;
                        }
                        .goal-mobile-pagination {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            margin-top: 20px;
                            margin-bottom: 20px;
                        }
                        .goal-mobile-pagination-controls {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            gap: 10px;
                            width: 100%;
                        }
                        @media (max-width: 768px) {
                            .goal-main-content .goal-pagination {
                                display: none !important;
                            }
                            .goal-mobile-pagination {
                                display: flex !important;
                            }
                        }
                        @media (min-width: 769px) {
                            .goal-pagination {
                                display: flex !important;
                            }
                            .goal-mobile-pagination {
                                display: none !important;
                            }
                        }
                    `;
                    const newStyle = document.createElement('style');
                    newStyle.id = 'dynamic-pagination-styles';
                    newStyle.textContent = css;
                    document.head.appendChild(newStyle);
                }
                
                if (paginationContainer) {
                    const paginationInfo = paginationContainer.querySelector('.goal-pagination-info');
                    if (paginationInfo) {
                        paginationInfo.innerHTML = `
                            ${translations.showing} <span id="pagination-start">${pagination.start}</span> ${translations.to} 
                            <span id="pagination-end">${pagination.end}</span> ${translations.of} 
                            <span id="pagination-total">${pagination.total_goals}</span> ${translations.goals}
                        `;
                    }
                    
                    paginationContainer.style.display = pagination.total_pages <= 1 ? 'none' : 'flex';
                    
                    const paginationControls = paginationContainer.querySelector('.goal-pagination-controls');
                    if (paginationControls) {
                        paginationControls.innerHTML = '';
                        
                        const prevButton = document.createElement('button');
                        prevButton.className = `goal-pagination-button goal-pagination-prev ${pagination.current_page <= 1 ? 'disabled' : ''}`;
                        prevButton.dataset.page = Math.max(1, pagination.current_page - 1);
                        prevButton.innerHTML = '<i class="fas fa-chevron-left"></i> ' + translations.previous;
                        paginationControls.appendChild(prevButton);
                        
                        const totalPages = pagination.total_pages;
                        const currentPage = pagination.current_page;
                        
                        let startPage = Math.max(1, currentPage - 2);
                        let endPage = Math.min(totalPages, startPage + 4);
                        
                        if (endPage - startPage < 4) {
                            startPage = Math.max(1, endPage - 4);
                        }
                        
                        if (startPage > 1) {
                            const firstPageBtn = document.createElement('button');
                            firstPageBtn.className = 'goal-pagination-button';
                            firstPageBtn.dataset.page = 1;
                            firstPageBtn.textContent = '1';
                            paginationControls.appendChild(firstPageBtn);
                            
                            if (startPage > 2) {
                                const ellipsis = document.createElement('span');
                                ellipsis.className = 'goal-pagination-ellipsis';
                                ellipsis.textContent = '...';
                                paginationControls.appendChild(ellipsis);
                            }
                        }
                        
                        for (let i = startPage; i <= endPage; i++) {
                            const pageBtn = document.createElement('button');
                            pageBtn.className = `goal-pagination-button ${i === currentPage ? 'active' : ''}`;
                            pageBtn.dataset.page = i;
                            pageBtn.textContent = i;
                            paginationControls.appendChild(pageBtn);
                        }
                        
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                const ellipsis = document.createElement('span');
                                ellipsis.className = 'goal-pagination-ellipsis';
                                ellipsis.textContent = '...';
                                paginationControls.appendChild(ellipsis);
                            }
                            
                            const lastPageBtn = document.createElement('button');
                            lastPageBtn.className = 'goal-pagination-button';
                            lastPageBtn.dataset.page = totalPages;
                            lastPageBtn.textContent = totalPages;
                            paginationControls.appendChild(lastPageBtn);
                        }
                        
                        const nextButton = document.createElement('button');
                        nextButton.className = `goal-pagination-button goal-pagination-next ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}`;
                        nextButton.dataset.page = Math.min(pagination.total_pages, pagination.current_page + 1);
                        nextButton.innerHTML = translations.next + ' <i class="fas fa-chevron-right"></i>';
                        paginationControls.appendChild(nextButton);
                        
                        paginationContainer.querySelectorAll('.goal-pagination-button').forEach(button => {
                            button.addEventListener('click', function() {
                                if (!this.classList.contains('disabled')) {
                                    const page = parseInt(this.dataset.page);
                                    console.log('Desktop pagination button clicked, loading page:', page);
                                    loadGoals(page, currentCategoryFilter, currentStatusFilter);
                                }
                            });
                        });
                    }
                }
                
                if (mobilePaginationContainer) {
                    const mobilePaginationCurrent = mobilePaginationContainer.querySelector('#mobile-pagination-current');
                    const mobilePaginationTotal = mobilePaginationContainer.querySelector('#mobile-pagination-total');
                    
                    if (mobilePaginationCurrent) mobilePaginationCurrent.textContent = pagination.current_page;
                    if (mobilePaginationTotal) mobilePaginationTotal.textContent = pagination.total_pages;
                    
                    const mobilePrevBtn = mobilePaginationContainer.querySelector('#mobile-pagination-prev');
                    const mobileNextBtn = mobilePaginationContainer.querySelector('#mobile-pagination-next');
                    
                    if (mobilePrevBtn) {
                        mobilePrevBtn.dataset.page = Math.max(1, pagination.current_page - 1);
                        mobilePrevBtn.classList.toggle('disabled', pagination.current_page <= 1);
                        
                        mobilePrevBtn.replaceWith(mobilePrevBtn.cloneNode(true));
                        const newMobilePrevBtn = mobilePaginationContainer.querySelector('#mobile-pagination-prev');
                        
                        newMobilePrevBtn.addEventListener('click', function() {
                            if (!this.classList.contains('disabled')) {
                                const page = parseInt(this.dataset.page);
                                console.log('Mobile prev button clicked, loading page:', page);
                                loadGoals(page, currentCategoryFilter, currentStatusFilter);
                            }
                        });
                    }
                    
                    if (mobileNextBtn) {
                        mobileNextBtn.dataset.page = Math.min(pagination.total_pages, pagination.current_page + 1);
                        mobileNextBtn.classList.toggle('disabled', pagination.current_page >= pagination.total_pages);
                        
                        mobileNextBtn.replaceWith(mobileNextBtn.cloneNode(true));
                        const newMobileNextBtn = mobilePaginationContainer.querySelector('#mobile-pagination-next');
                        
                        newMobileNextBtn.addEventListener('click', function() {
                            if (!this.classList.contains('disabled')) {
                                const page = parseInt(this.dataset.page);
                                console.log('Mobile next button clicked, loading page:', page);
                                loadGoals(page, currentCategoryFilter, currentStatusFilter);
                            }
                        });
                    }
                    
                    mobilePaginationContainer.style.display = pagination.total_pages <= 1 ? 'none' : 'flex';
                }
                
                if (mobileViewPaginationContainer) {
                    const mobileViewCurrentPage = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-current');
                    const mobileViewTotalPages = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-total');
                    
                    if (mobileViewCurrentPage) mobileViewCurrentPage.textContent = pagination.current_page;
                    if (mobileViewTotalPages) mobileViewTotalPages.textContent = pagination.total_pages;
                    
                    const mobileViewPrevBtn = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-prev');
                    const mobileViewNextBtn = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-next');
                    
                    if (mobileViewPrevBtn) {
                        mobileViewPrevBtn.dataset.page = Math.max(1, pagination.current_page - 1);
                        mobileViewPrevBtn.classList.toggle('disabled', pagination.current_page <= 1);
                        
                        mobileViewPrevBtn.replaceWith(mobileViewPrevBtn.cloneNode(true));
                        const newMobileViewPrevBtn = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-prev');
                        
                        newMobileViewPrevBtn.addEventListener('click', function() {
                            if (!this.classList.contains('disabled')) {
                                const page = parseInt(this.dataset.page);
                                console.log('Mobile view prev button clicked, loading page:', page);
                                loadGoals(page, currentCategoryFilter, currentStatusFilter);
                            }
                        });
                    }
                    
                    if (mobileViewNextBtn) {
                        mobileViewNextBtn.dataset.page = Math.min(pagination.total_pages, pagination.current_page + 1);
                        mobileViewNextBtn.classList.toggle('disabled', pagination.current_page >= pagination.total_pages);
                        
                        mobileViewNextBtn.replaceWith(mobileViewNextBtn.cloneNode(true));
                        const newMobileViewNextBtn = mobileViewPaginationContainer.querySelector('#mobile-view-pagination-next');
                        
                        newMobileViewNextBtn.addEventListener('click', function() {
                            if (!this.classList.contains('disabled')) {
                                const page = parseInt(this.dataset.page);
                                console.log('Mobile view next button clicked, loading page:', page);
                                loadGoals(page, currentCategoryFilter, currentStatusFilter);
                            }
                        });
                    }
                    
                    mobileViewPaginationContainer.style.display = pagination.total_pages <= 1 ? 'none' : 'flex';
                }
            }
            
            function updateActiveFilters() {
                const categoryFilters = document.querySelectorAll('.goal-filter-category:nth-child(1) .goal-filter-option');
                categoryFilters.forEach(filter => {
                    const filterType = filter.getAttribute('data-filter');
                    console.log(`Checking filter: ${filterType} against current: ${currentCategoryFilter}`);
                    if (filterType.toLowerCase() === currentCategoryFilter.toLowerCase()) {
                        filter.classList.add('active');
                    } else {
                        filter.classList.remove('active');
                    }
                });
                
                const statusFilters = document.querySelectorAll('.goal-filter-category:nth-child(2) .goal-filter-option');
                statusFilters.forEach(filter => {
                    const filterStatus = filter.getAttribute('data-status');
                    if (filterStatus === currentStatusFilter) {
                        filter.classList.add('active');
                    } else {
                        filter.classList.remove('active');
                    }
                });
                
                const mobileTabs = document.querySelectorAll('.goal-mobile-tab');
                mobileTabs.forEach(tab => {
                    const filter = tab.getAttribute('data-filter');
                    
                    if ((filter === 'active' && currentStatusFilter === 'active') ||
                        (filter === 'completed' && currentStatusFilter === 'completed') ||
                        (filter === 'all' && currentStatusFilter === 'all')) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
            }
            
            function initFilters() {
                updateActiveFilters();
                
                const categoryFilters = document.querySelectorAll('.goal-filter-category:nth-child(1) .goal-filter-option');
                categoryFilters.forEach(filter => {
                    filter.addEventListener('click', function() {
                        const newCategory = this.getAttribute('data-filter').toLowerCase();
                        console.log(`Filter clicked: ${newCategory}, current: ${currentCategoryFilter}`);
                        if (newCategory.toLowerCase() !== currentCategoryFilter.toLowerCase()) {
                            console.log(`Loading goals with new category filter: ${newCategory}`);
                            loadGoals(1, newCategory, currentStatusFilter);
                        }
                    });
                });
                
                const statusFilters = document.querySelectorAll('.goal-filter-category:nth-child(2) .goal-filter-option');
                statusFilters.forEach(filter => {
                    filter.addEventListener('click', function() {
                        const newStatus = this.getAttribute('data-status');
                        if (newStatus !== currentStatusFilter) {
                            loadGoals(1, currentCategoryFilter, newStatus);
                        }
                    });
                });
                
                const mobileTabs = document.querySelectorAll('.goal-mobile-tab');
                mobileTabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        const filter = this.getAttribute('data-filter');
                        
                        let statusFilter = 'all';
                        if (filter === 'active') statusFilter = 'active';
                        if (filter === 'completed') statusFilter = 'completed';
                        
                        if (statusFilter !== currentStatusFilter) {
                            loadGoals(1, currentCategoryFilter, statusFilter);
                        }
                    });
                });
                
                const mobilePrevButton = document.getElementById('mobile-pagination-prev');
                const mobileNextButton = document.getElementById('mobile-pagination-next');
                
                if (mobilePrevButton) {
                    mobilePrevButton.addEventListener('click', function() {
                        if (!this.classList.contains('disabled')) {
                            const page = parseInt(this.dataset.page);
                            console.log('Initial mobile prev clicked, loading page:', page);
                            loadGoals(page, currentCategoryFilter, currentStatusFilter);
                        }
                    });
                }
                
                if (mobileNextButton) {
                    mobileNextButton.addEventListener('click', function() {
                        if (!this.classList.contains('disabled')) {
                            const page = parseInt(this.dataset.page);
                            console.log('Initial mobile next clicked, loading page:', page);
                            loadGoals(page, currentCategoryFilter, currentStatusFilter);
                        }
                    });
                }
                
                const mobileViewPrevButton = document.getElementById('mobile-view-pagination-prev');
                const mobileViewNextButton = document.getElementById('mobile-view-pagination-next');
                
                if (mobileViewPrevButton) {
                    mobileViewPrevButton.addEventListener('click', function() {
                        if (!this.classList.contains('disabled')) {
                            const page = parseInt(this.dataset.page);
                            console.log('Initial mobile view prev clicked, loading page:', page);
                            loadGoals(page, currentCategoryFilter, currentStatusFilter);
                        }
                    });
                }
                
                if (mobileViewNextButton) {
                    mobileViewNextButton.addEventListener('click', function() {
                        if (!this.classList.contains('disabled')) {
                            const page = parseInt(this.dataset.page);
                            console.log('Initial mobile view next clicked, loading page:', page);
                            loadGoals(page, currentCategoryFilter, currentStatusFilter);
                        }
                    });
                }
            }
            
            initFilters();
            
            loadGoals(currentPage, currentCategoryFilter, currentStatusFilter);
            
            filterGoals();
            
            function filterGoals() {
                if (goalCards.length === 0) {
                    return;
                }
                
                goalCards.forEach(card => {
                    const goalType = card.getAttribute('data-type');
                    const status = card.getAttribute('data-status');
                    
                    const matchesCategory = currentCategoryFilter === 'all' || 
                                           goalType.toLowerCase() === currentCategoryFilter.toLowerCase();
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
                
                localStorage.setItem('goalCategoryFilter', currentCategoryFilter);
                localStorage.setItem('goalStatusFilter', currentStatusFilter);
            }
            
            function createNoGoalsElement() {
                const noMatchElement = document.createElement('div');
                noMatchElement.className = 'goal-no-matches';
                noMatchElement.style.padding = '20px';
                noMatchElement.style.textAlign = 'center';
                noMatchElement.style.backgroundColor = '#1a1b26';
                noMatchElement.style.borderRadius = '12px';
                noMatchElement.style.margin = '10px 0';
                
                let message = translations.noGoalsMatchFilters;
                
                if (currentCategoryFilter !== 'all' && currentStatusFilter !== 'all') {
                    message = `${translations.noGoalsMatchFilters} (${currentStatusFilter}, ${currentCategoryFilter})`;
                } else if (currentCategoryFilter !== 'all') {
                    message = `${translations.noGoalsMatchFilters} (${currentCategoryFilter})`;
                } else if (currentStatusFilter !== 'all') {
                    message = `${translations.noGoalsMatchFilters} (${currentStatusFilter})`;
                }
                
                noMatchElement.innerHTML = `
                    <p>${message}</p>
                    <p>${translations.tryChangingFilters}</p>
                `;
                
                const existingNoMatch = document.querySelector('.goal-no-matches');
                if (existingNoMatch) {
                    existingNoMatch.remove();
                }
                
                document.querySelector('.goal-goal-cards').appendChild(noMatchElement);
                return noMatchElement;
            }
            
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    
                    if (modalId === 'addGoalModal') {
                        setDefaultDates();
                    }
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

            filterGoals();
            
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
                            
                            updateEditUnitOptions(goal.goal_type);
                            
                            showEditStep(1);
                            
                            modal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        })
                        .catch(error => {
                            console.error('Error fetching goal details:', error);
                            alert(translations.errorFetchingGoalDetails);
                        });
                }
            };
            
            window.deleteGoal = function(goalId) {
                if (confirm(translations.confirmDeleteGoal)) {
                    const formData = new FormData();
                    formData.append('delete_goal', '1');
                    formData.append('goal_id', goalId);
                    
                    fetch('current-goal.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            loadGoals(currentPage, currentCategoryFilter, currentStatusFilter);
                        } else {
                            throw new Error(translations.errorDeletingGoal);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting goal:', error);
                        alert(translations.errorDeletingGoal);
                    });
                }
            };
            
            document.getElementById('goalForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                console.log("=== GOAL FORM SUBMISSION DEBUG ===");
                console.log("Form elements:", this.elements);
                
                const goalTypeSelect = document.getElementById('goal_type');
                console.log("Goal type select element:", goalTypeSelect);
                console.log("Goal type select value:", goalTypeSelect?.value);
                
                let goalType = goalTypeSelect?.value || '';
                
                goalType = goalType.replace(/[^a-zA-Z0-9\s]/g, '');
                
                const validGoalTypes = [
                    'weight', 'strength', 'endurance', 'workout', 'nutrition'
                ];
                
                const goalTypeLower = goalType.toLowerCase();
                
                if (!validGoalTypes.includes(goalTypeLower)) {
                    console.log("Invalid goal type detected: " + goalType + ". Defaulting to 'workout'");
                    goalTypeSelect.value = 'workout';
                    formData.set('goal_type', 'workout');
                } else {
                    const index = validGoalTypes.indexOf(goalTypeLower);
                    goalTypeSelect.value = validGoalTypes[index];
                    formData.set('goal_type', validGoalTypes[index]);
                }
                
                console.log("Goal type select options:", Array.from(goalTypeSelect?.options || []).map(opt => ({
                    value: opt.value,
                    text: opt.text,
                    selected: opt.selected
                })));
                
                console.log("Form data being submitted:");
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                    
                    if (pair[0] === 'goal_type') {
                        console.log("Goal type found in form data: ", pair[1]);
                        console.log("Goal type length: ", pair[1].length);
                        console.log("Goal type trimmed: ", pair[1].trim());
                        console.log("Goal type lowercase: ", pair[1].toLowerCase());
                        console.log("Goal type in valid types: ", validGoalTypes.includes(pair[1].toLowerCase()));
                    }
                }
                
                const fetchOptions = {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                
                console.log("Sending AJAX request with X-Requested-With header");
                
                fetch('current-goal.php', fetchOptions)
                .then(response => {
                    console.log("Response status:", response.status);
                    console.log("Response headers:", [...response.headers.entries()]);
                    
                    if (!response.ok) {
                        console.error('Server returned error:', response.status, response.statusText);
                        return response.text().then(text => {
                            console.error('Server response text:', text);
                            throw new Error('Failed to add goal: ' + text);
                        });
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.warn('Received HTML response instead of JSON:', text.substring(0, 100) + '...');
                            return { success: text.includes('Goal added successfully'), message: 'Goal processed' };
                        });
                    }
                })
                .then(data => {
                    console.log('Goal submission response:', data);
                    
                    if (data.success) {
                        console.log('Goal added successfully.');
                        closeAllModals();
                        
                        this.reset();
                        setDefaultDates(); 
                        
                        fetch(`current-goal.php?ajax=true&category=${currentCategoryFilter}&status=${currentStatusFilter}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log('Pagination data after goal creation:', data);
                                const totalGoals = data.pagination.total_goals;
                                const goalsPerPage = 3; 
                                const totalPages = Math.ceil(totalGoals / goalsPerPage);
                                
                                console.log(`Total goals: ${totalGoals}, Pages: ${totalPages}, Current page: ${currentPage}`);
                                
                                if (totalPages > currentPage) {
                                    console.log(`Moving to new page ${totalPages}`);
                                    loadGoals(totalPages, currentCategoryFilter, currentStatusFilter);
                                } else {
                                    console.log(`Reloading current page ${currentPage}`);
                                    loadGoals(currentPage, currentCategoryFilter, currentStatusFilter);
                                }
                            })
                            .catch(error => {
                                console.error('Error checking pagination after goal creation:', error);
                                loadGoals(currentPage, currentCategoryFilter, currentStatusFilter);
                            });
                    } else {
                        console.error('Goal submission failed:', data.message);
                        alert(translations.errorAddingGoal + ': ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error adding goal:', error);
                    alert('Failed to add goal. Please try again. Error: ' + error.message);
                });
            });
            
            const mobileFormContinue = document.getElementById('mobileFormContinue');
            const mobileFormBack = document.getElementById('mobileFormBack');
            const mobileFormCreate = document.getElementById('mobileFormCreate');
            
            function showStep(step) {
                const steps = document.querySelectorAll('.goal-form-step');
                steps.forEach(s => {
                    if (parseInt(s.dataset.step) === step) {
                        s.style.display = 'block';
                    } else {
                        s.style.display = 'none';
                    }
                });
                
                if (mobileFormContinue) mobileFormContinue.style.display = step === 1 ? 'block' : 'none';
                if (mobileFormCreate) mobileFormCreate.style.display = step === 2 ? 'block' : 'none';
            }
            
            if (mobileFormContinue) {
                mobileFormContinue.addEventListener('click', function() {
                    showStep(2);
                });
            }
            
            if (mobileFormBack) {
                mobileFormBack.addEventListener('click', function() {
                    showStep(1);
                });
            }
            
            if (mobileFormCreate) {
                mobileFormCreate.addEventListener('click', function() {
                    const title = document.querySelector('input[name="mobile_title"]').value;
                    const description = document.querySelector('textarea[name="mobile_description"]').value;
                    let goalType = document.querySelector('.goal-type-card.active')?.dataset.type;
                    
                    const validGoalTypes = [
                        'weight', 'strength', 'endurance', 'workout', 'nutrition'
                    ];
                    
                    if (goalType) {
                        goalType = goalType.replace(/[^a-zA-Z0-9\s]/g, '');
                        const goalTypeLower = goalType.toLowerCase();
                        
                        if (!validGoalTypes.includes(goalTypeLower)) {
                            console.log('Invalid goal type: ' + goalType + '. Using default: workout');
                            goalType = 'workout';
                        } else {
                            const index = validGoalTypes.indexOf(goalTypeLower);
                            goalType = validGoalTypes[index];
                        }
                    } else {
                        goalType = 'workout';
                    }
                    
                    const currentValue = document.querySelector('input[name="mobile_current_value"]').value;
                    const targetValue = document.querySelector('input[name="mobile_target_value"]').value;
                    const startDate = document.querySelector('input[name="mobile_start_date"]').value;
                    const deadline = document.querySelector('input[name="mobile_deadline"]').value;
                    
                    document.querySelector('input[name="title"]').value = title;
                    document.querySelector('textarea[name="description"]').value = description;
                    if (goalType) document.querySelector('select[name="goal_type"]').value = goalType;
                    document.querySelector('input[name="current_value"]').value = currentValue;
                    document.querySelector('input[name="target_value"]').value = targetValue;
                    document.querySelector('input[name="deadline"]').value = deadline;
                    
                    console.log('Goal type before submission:', goalType);
                    console.log('Form data before submission:', {
                        title, description, goalType, currentValue, targetValue, deadline
                    });
                    
                    document.getElementById('goalForm').dispatchEvent(new Event('submit'));
                });
            }
            
            document.getElementById('editGoalForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                console.log("=== EDIT GOAL FORM SUBMISSION ===");
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                fetch('current-goal.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log("Edit response status:", response.status);
                    
                    if (!response.ok) {
                        console.error('Server returned error:', response.status, response.statusText);
                        return response.text().then(text => {
                            console.error('Server response text:', text);
                            throw new Error('Failed to update goal: ' + text);
                        });
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.warn('Received HTML response instead of JSON:', text.substring(0, 100) + '...');
                            return { success: text.includes('Goal updated successfully'), message: 'Goal processed' };
                        });
                    }
                })
                .then(data => {
                    console.log('Goal update response:', data);
                    
                    if (data.success) {
                        console.log('Goal updated successfully');
                        closeAllModals();
                        loadGoals(currentPage, currentCategoryFilter, currentStatusFilter);
                    } else {
                        console.error('Goal update failed:', data.message);
                        alert(translations.errorUpdatingGoal + ': ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error updating goal:', error);
                    alert('Failed to update goal. Please try again. Error: ' + error.message);
                });
            });
            
            setupGoalTypeCards();
            setDefaultDates();
            
            showStep(1);

            function setupGoalTypeCards() {
                const goalTypeCards = document.querySelectorAll('.goal-type-card');
                const goalTypeSelect = document.getElementById('goal_type');
                
                goalTypeCards.forEach(card => {
                    card.addEventListener('click', function() {
                        goalTypeCards.forEach(c => c.classList.remove('active'));
                        
                        this.classList.add('active');
                        
                        const selectedType = this.getAttribute('data-type');
                        if (goalTypeSelect) {
                            goalTypeSelect.value = selectedType;
                        }
                        
                        updateMobileUnitOptions(selectedType);
                    });
                });
            }
            
            function updateMobileUnitOptions(goalType) {
                const mobileUnitSelect = document.getElementById('mobile_unit');
                if (!mobileUnitSelect) return;
                
                while (mobileUnitSelect.options.length > 1) {
                    mobileUnitSelect.remove(1);
                }
                
                const unitsByType = {
                    'strength': ['kg', 'reps'],
                    'weight': ['kg'],
                    'endurance': ['km', 'min'],
                    'workout': ['reps', 'min'],
                    'nutrition': ['g', 'kcal']
                };
                
                const unitNames = {
                    'kg': 'Kilograms (kg)',
                    'reps': 'Repetitions',
                    'km': 'Kilometers (km)',
                    'min': 'Minutes',
                    'g': 'Grams (g)',
                    'kcal': 'Kilocalories (kcal)'
                };
        
                const units = unitsByType[goalType.toLowerCase()] || ['kg', 'reps', 'km', 'min'];
                
                units.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit;
                    option.textContent = unitNames[unit] || unit;
                    mobileUnitSelect.appendChild(option);
                });
            }
            
            function updateUnitOptions() {
                const goalTypeSelect = document.getElementById('goal_type');
                const unitSelect = document.getElementById('unit');
                
                if (!goalTypeSelect || !unitSelect) return;
                
                while (unitSelect.options.length > 1) {
                    unitSelect.remove(1);
                }
                
                const goalType = goalTypeSelect.value.toLowerCase();
                
                const unitsByType = {
                    'strength': ['kg', 'reps'],
                    'weight': ['kg'],
                    'endurance': ['km', 'min'],
                    'workout': ['reps', 'min'],
                    'nutrition': ['g', 'kcal']
                };
                
                const unitNames = {
                    'kg': 'Kilograms (kg)',
                    'reps': 'Repetitions',
                    'km': 'Kilometers (km)',
                    'min': 'Minutes',
                    'g': 'Grams (g)',
                    'kcal': 'Kilocalories (kcal)'
                };
                
                const units = unitsByType[goalType] || ['kg', 'reps', 'km', 'min'];
                
                units.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit;
                    option.textContent = unitNames[unit] || unit;
                    unitSelect.appendChild(option);
                });
            }
            
            const goalTypeSelect = document.getElementById('goal_type');
            if (goalTypeSelect) {
                goalTypeSelect.addEventListener('change', updateUnitOptions);
                
                if (goalTypeSelect.value) {
                    updateUnitOptions();
                }
            }

            function updateEditUnitOptions(goalType) {
                const unitsByType = {
                    'strength': ['kg', 'reps'],
                    'weight': ['kg'],
                    'endurance': ['km', 'min'],
                    'workout': ['reps', 'min'],
                    'nutrition': ['g', 'kcal']
                };
                
                const unitNames = {
                    'kg': 'Kilograms (kg)',
                    'reps': 'Repetitions',
                    'km': 'Kilometers (km)',
                    'min': 'Minutes',
                    'g': 'Grams (g)',
                    'kcal': 'Kilocalories (kcal)'
                };
                
                const editUnitSelect = document.getElementById('edit_unit');
                if (editUnitSelect) {
                    while (editUnitSelect.options.length > 1) {
                        editUnitSelect.remove(1);
                    }
                    
                    const units = unitsByType[goalType.toLowerCase()] || ['kg', 'reps', 'km', 'min'];
                    
                    units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit;
                        option.textContent = unitNames[unit] || unit;
                        editUnitSelect.appendChild(option);
                    });
                }
                
                const mobileEditUnitSelect = document.getElementById('mobile_edit_unit');
                if (mobileEditUnitSelect) {
                    while (mobileEditUnitSelect.options.length > 1) {
                        mobileEditUnitSelect.remove(1);
                    }
                    
                    const units = unitsByType[goalType.toLowerCase()] || ['kg', 'reps', 'km', 'min'];
                    
                    units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit;
                        option.textContent = unitNames[unit] || unit;
                        mobileEditUnitSelect.appendChild(option);
                    });
                }
            }
            
            const editGoalTypeSelect = document.getElementById('edit_goal_type');
            if (editGoalTypeSelect) {
                editGoalTypeSelect.addEventListener('change', function() {
                    updateEditUnitOptions(this.value);
                });
            }
            
            const mobileEditGoalTypeSelect = document.getElementById('mobile_edit_goal_type');
            if (mobileEditGoalTypeSelect) {
                mobileEditGoalTypeSelect.addEventListener('change', function() {
                    updateEditUnitOptions(this.value);
                });
            }

            function formatDeadline(deadlineStr) {
                const date = new Date(deadlineStr);
                const now = new Date();
                const diffTime = date.getTime() - now.getTime();
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays < 0) {
                    const absDiffDays = Math.abs(diffDays);
                    if (absDiffDays === 0) {
                        const diffHours = Math.ceil(Math.abs(diffTime) / (1000 * 60 * 60));
                        return `${translations.overdueBy} ${diffHours} ${translations.hours}`;
                    }
                    return `${translations.overdueBy} ${absDiffDays} ${translations.days}`;
                } else if (diffDays === 0) {
                    return translations.dueToday;
                } else if (diffDays === 1) {
                    return translations.dueTomorrow;
                } else if (diffDays < 7) {
                    return `${translations.dueIn} ${diffDays} ${translations.days}`;
                } else if (diffDays < 30) {
                    const weeks = Math.ceil(diffDays / 7);
                    return `${translations.dueIn} ${weeks} ${translations.weeks}`;
                } else {
                    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    return `${translations.dueOn} ${formattedDate}`;
                }
            }
        });
    </script> 
</body>
</html>