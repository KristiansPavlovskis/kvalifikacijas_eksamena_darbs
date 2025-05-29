<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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
$recent_templates = [];

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

try {
    $stmt = mysqli_prepare($conn, "SELECT weight, body_fat FROM body_measurements WHERE user_id = ? ORDER BY measurement_date DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($metrics_data = mysqli_fetch_assoc($result)) {
        $current_weight = $metrics_data["weight"] ?: 0;
        $body_fat = $metrics_data["body_fat"] ?: 0;
    } else {
        $current_weight = $body_weight;
        $body_fat = 0;
    }
} catch (Exception $e) {
    error_log("Error fetching body metrics: " . $e->getMessage());
    $current_weight = $body_weight;
    $body_fat = 0;
}

$recent_templates = [];
try {
    $query = "SELECT id, name, category FROM workout_templates WHERE user_id = ? ORDER BY id DESC LIMIT 2";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $template_count = mysqli_num_rows($result);
        error_log("Found $template_count workout templates for user $user_id");
        
        while ($template = mysqli_fetch_assoc($result)) {
            $recent_templates[] = $template;
            error_log("Added template: " . json_encode($template));
        }
    } else {
        error_log("Failed to prepare template query: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    error_log("Error fetching recent templates: " . $e->getMessage());
}

$current_month = date('m');
$current_year = date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = $current_month;
}

$days_in_month = date('t', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$month_workouts = [];

try {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'scheduled_workouts'")->num_rows > 0) {
        $stmt = mysqli_prepare($conn, "SELECT day, template_id, template_name, workout_type 
                                     FROM scheduled_workouts 
                                     WHERE user_id = ? AND month = ? AND year = ?");
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $selected_month, $selected_year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($workout = mysqli_fetch_assoc($result)) {
            $month_workouts[$workout['day']] = $workout;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching month workouts: " . $e->getMessage());
}

$workout_splits = [];
try {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'workout_splits'")->num_rows > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, data FROM workout_splits WHERE user_id = ? ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($split = mysqli_fetch_assoc($result)) {
            $workout_splits[] = $split;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching workout splits: " . $e->getMessage());
}

$today = date('j');
$today_workout = $month_workouts[$today] ?? null;

$today_exercises = [];
if ($today_workout && !empty($today_workout['template_id'])) {
    try {
        $stmt = mysqli_prepare($conn, "SELECT e.name as exercise_name FROM workout_template_exercises wte
                                     JOIN exercises e ON wte.exercise_id = e.id
                                     WHERE wte.workout_template_id = ?
                                     ORDER BY wte.position");
        mysqli_stmt_bind_param($stmt, "i", $today_workout['template_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($exercise = mysqli_fetch_assoc($result)) {
            $today_exercises[] = $exercise['exercise_name'];
        }
    } catch (Exception $e) {
        error_log("Error fetching today's exercises: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="global-profile.css" rel="stylesheet">
    <script>
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }
        
        function startWorkout(button) {
            try {
                console.log('Starting workout with button:', button);
                const templateId = button.getAttribute('data-template-id');
                console.log('Template ID:', templateId);
                if (templateId) {
                    button.innerText = 'Starting...';
                    button.disabled = true;
                    
                    button.classList.add('profile-loading');
                    
                    const targetPage = isMobile() ? 'mobile-workout.php' : 'workout.php';
                    const workoutUrl = `${targetPage}?template_id=${templateId}&start_workout=1&auto_start=1&start_step=2`;
                    
                    console.log('Redirecting to:', workoutUrl);
                    window.location.href = workoutUrl;
                } else {
                    console.error('No template ID found on button');
                    alert('Error: No workout template selected. Please select a workout template first.');
                }
            } catch (error) {
                console.error('Error starting workout:', error);
                alert('An error occurred while starting the workout.');
            }
        }
    </script>
</head>
<body>
    <div class="profile-dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <main class="profile-main-content">
            <?php 
            if ($today_workout && !empty($today_workout['template_id'])) {
                echo "<!-- Template ID available: " . $today_workout['template_id'] . " -->";
            } else {
                echo "<!-- Template ID not available -->";
            }
            ?>
            <div class="profile-page-header">
                <h1 class="profile-page-title">Fitness Dashboard</h1>
            </div>
            
            <div class="profile-dashboard-grid">
                <div class="profile-left-column">
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <h3 class="profile-panel-title">Body Metrics</h3>
                        </div>
                        <?php if ($current_weight == 0 && $body_fat == 0): ?>
                            <div style="text-align: center; padding: 20px 0;">
                                <i class="fas fa-weight" style="font-size: 2rem; opacity: 0.5; margin-bottom: 15px;"></i>
                                <p>You haven't set your body metrics yet</p>
                            </div>
                        <?php else: ?>
                            <div class="profile-body-metrics-list">
                                <div class="profile-metric-item">
                                    <span class="profile-metric-label">Weight</span>
                                    <span class="profile-metric-value"><?= $current_weight ?> kg</span>
                                </div>
                                <div class="profile-metric-item">
                                    <span class="profile-metric-label">Body Fat</span>
                                    <span class="profile-metric-value"><?= $body_fat ?>%</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="body-measurements.php" class="profile-start-workout-btn">
                            Update Metrics
                        </a>
                    </div>
                    
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <h3 class="profile-panel-title">Recent templates</h3>
                        </div>
                        <?php if (empty($recent_templates)): ?>
                            <div style="text-align: center; padding: 20px 0;">
                                <i class="fas fa-clipboard" style="font-size: 2rem; opacity: 0.5; margin-bottom: 15px;"></i>
                                <p>You haven't created any templates yet</p>
                            </div>
                        <?php else: ?>
                            <div class="profile-templates-grid">
                                <?php foreach ($recent_templates as $template): ?>
                                    <div class="profile-template-card" data-template-id="<?= $template['id'] ?>">
                                        <?php 
                                            $cssClass = '';
                                            
                                            if (strtolower($template['category']) === 'push') {
                                                $cssClass = 'profile-push-day';
                                            } elseif (strtolower($template['category']) === 'pull') {
                                                $cssClass = 'profile-pull-day';
                                            } elseif (strtolower($template['category']) === 'leg') {
                                                $cssClass = 'profile-leg-day';
                                            }
                                        ?>
                                        <div class="profile-template-label <?= $cssClass ?>"><?= htmlspecialchars($template['name']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <a href="workout-templates.php" class="profile-start-workout-btn">
                            Update templates
                        </a>
                    </div>
                </div>
                
                <div class="profile-middle-column">
                    <div class="profile-panel">
                        <div class="profile-calendar-header">
                            <h2 class="profile-calendar-title"><?= $month_name ?> <?= $selected_year ?></h2>
                            <div class="profile-calendar-actions">
                                <button class="profile-action-button secondary" id="create-split-btn">
                                    <i class="fas fa-calendar-week"></i> Create Split
                                </button>
                            </div>
                        </div>
                        
                        <?php
                            $firstDayOfMonth = mktime(0, 0, 0, $selected_month, 1, $selected_year);
                            $numberDays = date('t', $firstDayOfMonth);
                            $dateComponents = getdate($firstDayOfMonth);
                            $monthName = $dateComponents['month'];
                            $dayOfWeek = $dateComponents['wday'];
                            
                            $currentDay = 0;
                            $isCurrentMonth = ($selected_month == $current_month && $selected_year == $current_year);
                            if ($isCurrentMonth) {
                                $currentDay = date('j');
                            }
                            
                            $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            $calendar = "<div class='profile-calendar-grid'>";
                            
                            foreach ($weekdays as $day) {
                                $calendar .= "<div class='profile-calendar-weekday'>{$day}</div>";
                            }
                            
                            if ($dayOfWeek > 0) {
                                for ($i = 0; $i < $dayOfWeek; $i++) {
                                    $calendar .= "<div class='profile-calendar-day' style='visibility: hidden'></div>";
                                }
                            }
                            
                            for ($i = 1; $i <= $numberDays; $i++) {
                                $id = "day_" . $i;
                                $isPastDay = $isCurrentMonth && $i < $currentDay;
                                $dayClass = $isPastDay ? 'profile-calendar-day profile-past-day' : 'profile-calendar-day';
                                $calendar .= "<div class='{$dayClass}' id='{$id}' data-day='{$i}' " . ($isPastDay ? 'data-past="true"' : '') . ">";
                                $calendar .= "<div class='profile-calendar-day-number'>{$i}</div>";
                                
                                if ($isCurrentMonth && $i == $currentDay) {
                                    $calendar .= "<div class='profile-today-marker'></div>";
                                }
                                
                                if (isset($month_workouts[$i])) {
                                    $workout = $month_workouts[$i];
                                    $type = strtolower($workout['workout_type'] ?? '');
                                    
                                    $typeClass = '';
                                    
                                    if ($type === 'push') {
                                        $typeClass = 'profile-push-day';
                                    } elseif ($type === 'pull') {
                                        $typeClass = 'profile-pull-day';
                                    } elseif ($type === 'leg') {
                                        $typeClass = 'profile-leg-day';
                                    } elseif ($type === 'rest') {
                                        $typeClass = 'profile-rest-day';
                                    }
                                    
                                    $calendar .= "<div class='profile-calendar-day-content profile-day-has-workout'>";
                                    $calendar .= "<div class='profile-workout-type {$typeClass}'>{$type}</div>";
                                    $calendar .= "</div>";
                                } else {
                                    $calendar .= "<div class='profile-calendar-day-content'>";
                                    if (!$isPastDay) {
                                        $calendar .= "<i class='fas fa-plus-circle' style='opacity: 0.5; font-size: 0.8rem;'></i>";
                                    }
                                    $calendar .= "</div>";
                                }
                                
                                $calendar .= "</div>";
                            }
                            
                            $calendar .= "</div>";
                            echo $calendar;
                        ?>
                    </div>
                </div>
                
                <div class="profile-right-column">
                    <div class="profile-panel profile-today-workout">
                        <div class="profile-panel-header">
                            <h3 class="profile-panel-title">Today's Workout</h3>
                        </div>
                        
                        <?php if ($today_workout): ?>
                            <?php if ($today_workout['workout_type'] === 'rest'): ?>
                                <div class="profile-rest-day-message">
                                    <i class="fas fa-bed"></i>
                                    <h3>Rest Day</h3>
                                    <p>Take time to recover and recharge.</p>
                                </div>
                            <?php else: ?>
                                <div class="profile-workout-time">
                                    <i class="far fa-clock"></i> 45 minutes • <?= count($today_exercises) ?> exercises
                                </div>
                                <h4><?= htmlspecialchars($today_workout['template_name']) ?></h4>
                                
                                <?php if (!empty($today_exercises)): ?>
                                <?php else: ?>
                                    <p style="opacity: 0.7; margin-top: 20px;">No exercises found for this workout.</p>
                                <?php endif; ?>
                                
                                <button class="profile-start-workout-btn" id="startWorkoutBtn" data-template-id="<?= htmlspecialchars($today_workout['template_id']) ?>" onclick="return startWorkout(this)">
                                    Start Workout
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="profile-day-has-workout" style="text-align: center; padding: 30px 0;">
                                <i class="fas fa-calendar-plus" style="font-size: 2rem; opacity: 0.5; margin-bottom: 15px;"></i>
                                <p>No workout scheduled for today</p>
                                <button class="profile-start-workout-btn" style="margin-top: 20px;" id="plan-today-btn">
                                    Plan Today's Workout
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($today_exercises)): ?>
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <h3 class="profile-panel-title"><?= htmlspecialchars($today_workout['workout_type']) ?> day</h3>
                            <span class="profile-workout-type profile-<?= strtolower($today_workout['workout_type']) ?>-day"><?= count($today_exercises) ?> exercises</span>
                        </div>
                        
                        <ul class="profile-workout-exercises">
                            <?php foreach ($today_exercises as $exercise): ?>
                                <li class="profile-exercise-item"><?= htmlspecialchars($exercise) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        
    
    
            <div class="profile-mobile-app-view">
                <div class="profile-mobile-header">
                    <div class="profile-mobile-header-title">
                        Today's Focus
                        <span class="profile-mobile-header-date"><?= date('F j, Y') ?></span>
                    </div>
                </div>
                
                <div class="profile-mobile-card">
                    <?php if ($today_workout && $today_workout['workout_type'] !== 'rest'): ?>
                        <h3 class="profile-mobile-card-title">Scheduled: <?= htmlspecialchars($today_workout['template_name']) ?></h3>
                        <div class="profile-mobile-workout-meta">
                            <span>45 min</span> • <span><?= count($today_exercises) ?> exercises</span>
                        </div>
                        <button class="profile-mobile-start-btn" id="mobileStartWorkoutBtn" data-template-id="<?= htmlspecialchars($today_workout['template_id']) ?>" onclick="return startWorkout(this)">Start Workout</button>
                    <?php elseif ($today_workout && $today_workout['workout_type'] === 'rest'): ?>
                        <h3 class="profile-mobile-card-title">Scheduled: Rest Day</h3>
                        <div class="profile-mobile-workout-meta">
                            <span>Take time to recover and recharge</span>
                        </div>
                        <button class="profile-mobile-start-btn" style="background-color: #555;">Rest Day</button>
                    <?php else: ?>
                        <h3 class="profile-mobile-card-title">No Workout Scheduled</h3>
                        <div class="profile-mobile-workout-meta">
                            <span>Schedule a workout for today</span>
                        </div>
                        <button class="profile-mobile-start-btn" id="mobilePlanWorkoutBtn">Schedule Workout</button>
                    <?php endif; ?>
                </div>
                
                <div class="profile-mobile-card">
                    <h3 class="profile-mobile-card-title">Today's Weight</h3>
                    <div class="profile-mobile-weight-section">
                        <div>
                            <span class="profile-mobile-weight-value"><?= $current_weight ?></span>
                            <span class="profile-mobile-weight-unit">kg</span>
                        </div>
                        <button class="profile-mobile-update-btn">Update</button>
                    </div>
                </div>
                
                <div class="profile-mobile-week-selector">
                    <?php
                        $today = new DateTime();
                        $weekday = $today->format('w');
                        $offset = $weekday;
                        
                        $days = [];
                        for ($i = 0; $i < 7; $i++) {
                            $day = clone $today;
                            $day->modify('-' . $offset . ' day');
                            $day->modify('+' . $i . ' day');
                            $days[] = $day;
                        }
                        
                        foreach ($days as $index => $day) {
                            $dayNum = $day->format('j');
                            $weekdayShort = $day->format('D');
                            $isToday = $day->format('Y-m-d') === $today->format('Y-m-d');
                            $dayClass = 'profile-mobile-day-btn';
                            if ($isToday) {
                                $dayClass .= ' active';
                            }
                            
                            $hasWorkout = false;
                            if ($isToday && $today_workout) {
                                $hasWorkout = true;
                                $dayClass .= ' profile-workout-scheduled';
                            } elseif (isset($month_workouts[$dayNum]) && $day->format('m') == $selected_month) {
                                $hasWorkout = true;
                                $dayClass .= ' profile-workout-scheduled';
                            }
                            
                            echo "<button class=\"{$dayClass}\" data-date=\"{$day->format('Y-m-d')}\">";
                            echo "<span class=\"profile-mobile-day-weekday\">{$weekdayShort}</span>";
                            echo "<span class=\"profile-mobile-day-date\">{$dayNum}</span>";
                            echo "</button>";
                        }
                    ?>
                </div>
                
                <button type="button" class="profile-mobile-week-action" id="mobileWeekAction" onclick="document.getElementById('mobileSplitModal').style.display='flex'">
                    <i class="fas fa-calendar-week"></i>
                </button>
                
                <div class="profile-mobile-button-grid">
                    <a href="workout.php" class="profile-mobile-feature-btn">
                        <div class="profile-mobile-feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="profile-mobile-feature-label">Quick Workout</div>
                    </a>
                    <a href="workout-history.php" class="profile-mobile-feature-btn">
                        <div class="profile-mobile-feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="profile-mobile-feature-label">Workout History</div>
                    </a>
                    <a href="workout-templates.php" class="profile-mobile-feature-btn">
                        <div class="profile-mobile-feature-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="profile-mobile-feature-label">Edit Templates</div>
                    </a>
                    <a href="workout-analytics.php" class="profile-mobile-feature-btn">
                        <div class="profile-mobile-feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="profile-mobile-feature-label">Progress</div>
                    </a>
                </div>
                
                <form id="mobileWorkoutForm" action="mobile-workout.php" method="POST" style="display: none;">
                <form id="mobileWorkoutForm" action="workout.php" method="POST" style="display: none;">
                    <input type="hidden" name="template_id" id="mobileTemplateIdInput" value="<?= $today_workout['template_id'] ?>">
                    <input type="hidden" name="start_workout" value="1">
                    <input type="hidden" name="auto_start" value="1">
                    <input type="hidden" name="start_step" value="2">
                </form>
            </div>
            
            <div class="profile-modal" id="mobileDayModal">
                <div class="profile-modal-content">
                    <span class="profile-modal-close">&times;</span>
                    <h3 class="profile-modal-title">Edit workout for <span id="mobileSelectedDate"></span></h3>
                    
                    <form id="mobileWorkoutPlanForm">
                        <input type="hidden" id="mobileSelectedDay" name="day">
                        <input type="hidden" id="mobileSelectedMonth" name="month" value="<?= $selected_month ?>">
                        <input type="hidden" id="mobileSelectedYear" name="year" value="<?= $selected_year ?>">
                        
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="mobileWorkoutType">Workout Type</label>
                            <select class="profile-form-select" id="mobileWorkoutType" name="workoutType">
                                <option value="">Select workout type</option>
                                <option value="push">Push Day</option>
                                <option value="pull">Pull Day</option>
                                <option value="leg">Leg Day</option>
                                <option value="rest">Rest Day</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div class="profile-form-group" id="mobileTemplateSelectGroup" style="display: none;">
                            <label class="profile-form-label" for="mobileTemplateSelect">Select Template</label>
                            <select class="profile-form-select" id="mobileTemplateSelect" name="templateId">
                                <option value="">Select a template</option>
                                <?php 
                                try {
                                    $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    
                                    while ($template = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$template['id']}'>{$template['name']}</option>";
                                    }
                                } catch (Exception $e) {
                                    error_log("Error fetching templates: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="profile-form-group" id="mobileCustomNameGroup" style="display: none;">
                            <label class="profile-form-label" for="mobileCustomName">Custom Workout Name</label>
                            <input type="text" class="profile-form-input" id="mobileCustomName" name="customName" placeholder="Enter workout name">
                        </div>
                        
                        <div class="profile-form-actions">
                            <button type="button" class="profile-form-button secondary" id="mobileEditCancel">Cancel</button>
                            <button type="submit" class="profile-form-button primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="profile-modal" id="mobileSplitModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; overflow: auto;">
                <div class="profile-modal-content" style="background-color: #21242e; width: 90%; max-width: 500px; border-radius: 15px; padding: 20px; position: relative; max-height: 80vh; overflow-y: auto;">
                    <span class="profile-modal-close" onclick="document.getElementById('mobileSplitModal').style.display='none'" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer;">&times;</span>
                    <h3 class="profile-modal-title">Weekly Split Plan</h3>
                    
                    <form id="mobileSplitForm">
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="mobileSplitName">Split Name</label>
                            <input type="text" class="profile-form-input" id="mobileSplitName" name="splitName" placeholder="e.g., Bro Split, PPL, Upper/Lower" required>
                        </div>
                        
                        <div class="profile-mobile-split-days">
                            <?php
                                $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($weekdays as $index => $day) {
                                    echo "<div class='profile-mobile-split-day'>";
                                    echo "<h4>{$day}</h4>";
                                    
                                    echo "<div class='profile-form-group'>";
                                    echo "<select class='profile-form-select profile-mobile-split-type' id='mobile_day_{$index}' name='days[{$index}][type]'>";
                                    echo "<option value=''>Select workout</option>";
                                    echo "<option value='rest'>Rest Day</option>";
                                    
                                    try {
                                        $stmt = mysqli_prepare($conn, "SELECT id, name, category FROM workout_templates WHERE user_id = ? ORDER BY name");
                                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        
                                        while ($template = mysqli_fetch_assoc($result)) {
                                            $category = !empty($template['category']) ? " ({$template['category']})" : "";
                                            echo "<option value='{$template['id']}' data-category='{$template['category']}'>{$template['name']}{$category}</option>";
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error fetching templates: " . $e->getMessage());
                                    }
                                    
                                    echo "</select>";
                                    echo "</div>";
                                    
                                    echo "</div>";
                                }
                            ?>
                        </div>
                        
                        <div class="profile-form-actions">
                            <button type="button" class="profile-form-button secondary" id="mobileSplitCancel">Cancel</button>
                            <div class="profile-form-group">
                                <label for="mobileTargetMonth">Apply to:</label>
                                <select id="mobileTargetMonth" name="target_month" class="profile-form-select">
                                    <?php
                                        for ($i = 0; $i <= 3; $i++) {
                                            $month = ($current_month + $i - 1) % 12 + 1;
                                            $year = $current_year + floor(($current_month + $i - 1) / 12);
                                            $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                                            $selected = ($month == $selected_month && $year == $selected_year) ? 'selected' : '';
                                            echo "<option value='{$month}_{$year}' {$selected}>{$monthName} {$year}</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="profile-form-button primary" id="mobileSplitSave">Save Split</button>
                            <button type="button" class="profile-form-button primary" id="mobileSplitApply">Apply to Calendar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <div class="profile-modal" id="dayModal">
        <div class="profile-modal-content">
            <span class="profile-modal-close">&times;</span>
            <h3 class="profile-modal-title">Plan workout for <span id="selectedDate"></span></h3>
            
            <form id="workoutPlanForm">
                <input type="hidden" id="selectedDay" name="day">
                
                <div class="profile-form-group">
                    <label class="profile-form-label" for="workoutType">Workout Type</label>
                    <select class="profile-form-select" id="workoutType" name="workoutType">
                        <option value="">Select workout type</option>
                        <option value="push">Push Day</option>
                        <option value="pull">Pull Day</option>
                        <option value="leg">Leg Day</option>
                        <option value="rest">Rest Day</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                
                <div class="profile-form-group" id="templateSelectGroup" style="display: none;">
                    <label class="profile-form-label" for="templateSelect">Select Template</label>
                    <select class="profile-form-select" id="templateSelect" name="templateId">
                        <option value="">Select a template</option>
                        <?php 
                        try {
                            $stmt = mysqli_prepare($conn, "SELECT id, name FROM workout_templates WHERE user_id = ? ORDER BY name");
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            while ($template = mysqli_fetch_assoc($result)) {
                                echo "<option value='{$template['id']}'>{$template['name']}</option>";
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching templates: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                
                <div class="profile-form-group" id="customNameGroup" style="display: none;">
                    <label class="profile-form-label" for="customName">Custom Workout Name</label>
                    <input type="text" class="profile-form-input" id="customName" name="customName" placeholder="Enter workout name">
                </div>
                
                <div class="profile-form-actions">
                    <button type="button" class="profile-form-button secondary" id="cancelPlan">Cancel</button>
                    <button type="submit" class="profile-form-button primary">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="profile-modal" id="splitModal">
        <div class="profile-modal-content profile-split-modal">
            <span class="profile-modal-close">&times;</span>
            <h3 class="profile-modal-title">Create Weekly Split</h3>
            
            <form id="splitPlanForm">
                <div class="profile-form-group">
                    <label class="profile-form-label" for="splitName">Split Name</label>
                    <input type="text" class="profile-form-input" id="splitName" name="splitName" placeholder="e.g., Bro Split, PPL, Upper/Lower" required>
                </div>
                
                <div class="profile-weekly-calendar">
                    <?php
                        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($weekdays as $index => $day) {
                            echo "<div class='profile-week-day'>";
                            echo "<div class='profile-week-day-header'>{$day}</div>";
                            echo "<div class='profile-week-day-content'>";
                            echo "<select class='profile-form-select profile-week-day-select' id='day_{$index}' name='days[{$index}][type]'>";
                            echo "<option value=''>Select workout</option>";
                            echo "<option value='rest'>Rest Day</option>";
                            
                            try {
                                $stmt = mysqli_prepare($conn, "SELECT id, name, category FROM workout_templates WHERE user_id = ? ORDER BY name");
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                
                                while ($template = mysqli_fetch_assoc($result)) {
                                    $category = !empty($template['category']) ? " ({$template['category']})" : "";
                                    echo "<option value='{$template['id']}' data-category='{$template['category']}'>{$template['name']}{$category}</option>";
                                }
                            } catch (Exception $e) {
                                error_log("Error fetching templates: " . $e->getMessage());
                            }
                            
                            echo "</select>";
                            echo "</div></div>";
                        }
                    ?>
                </div>
                
                <div class="profile-split-actions">
                    <div class="profile-form-actions">
                        <div class="profile-form-field">
                            <label for="targetMonth">Apply to:</label>
                            <select id="targetMonth" name="target_month" class="profile-form-select">
                                <?php
                                    $nextFewMonths = [];
                                    for ($i = 0; $i <= 3; $i++) {
                                        $month = ($current_month + $i - 1) % 12 + 1;
                                        $year = $current_year + floor(($current_month + $i - 1) / 12);
                                        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                                        $selected = ($month == $selected_month && $year == $selected_year) ? 'selected' : '';
                                        echo "<option value='{$month}_{$year}' {$selected}>{$monthName} {$year}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="profile-form-button primary">Apply to Month</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.showSplitModal = function() {
            console.log("Showing split modal");
            const modal = document.getElementById('splitModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.style.zIndex = '10000';
            } else {
                console.error('Split modal element not found');
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            var createSplitBtn = document.getElementById('create-split-btn');
            if (createSplitBtn) {
                console.log("Found Create Split button, adding click handler");
                createSplitBtn.addEventListener('click', function(e) {
                    console.log("Create Split button clicked");
                    e.preventDefault();
                    showSplitModal();
                });
            } else {
                console.error("Create Split button not found!");
            }
            
            const splitPlanForm = document.getElementById('splitPlanForm');
            if (splitPlanForm) {
                splitPlanForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const splitName = document.getElementById('splitName').value;
                    if (!splitName) {
                        alert('Please enter a name for your split');
                        return;
                    }
                    
                    let hasWorkout = false;
                    const weekDaySelects = document.querySelectorAll('.profile-week-day-select');
                    weekDaySelects.forEach(select => {
                        if (select.value) hasWorkout = true;
                    });
                    
                    if (!hasWorkout) {
                        alert('Please assign at least one workout to your weekly split');
                        return;
                    }
                    
                    const formData = new FormData(this);
                    const targetMonthSelect = document.getElementById('targetMonth');
                    const [targetMonth, targetYear] = targetMonthSelect.value.split('_');
                    
                    formData.append('month', targetMonth);
                    formData.append('year', targetYear);
                    formData.append('apply_to_calendar', '1');
                    
                    fetch('save_workout_split.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout split applied successfully to ' + targetMonthSelect.options[targetMonthSelect.selectedIndex].text);
                            const splitModal = document.getElementById('splitModal');
                            if (splitModal) {
                                splitModal.style.display = 'none';
                            }
                            window.location.href = `profile.php?month=${targetMonth}&year=${targetYear}`;
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout split.');
                    });
                });
            }
            
            const mobileSplitForm = document.getElementById('mobileSplitForm');
            if (mobileSplitForm && document.getElementById('mobileSplitSave')) {
                mobileSplitForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveMobileSplit(false);
                });
            }
            
            const mobileSplitApply = document.getElementById('mobileSplitApply');
            if (mobileSplitApply) {
                mobileSplitApply.addEventListener('click', function(e) {
                    e.preventDefault();
                    saveMobileSplit(true);
                });
            }
            
            function saveMobileSplit(applyToCalendar) {
                const splitName = document.getElementById('mobileSplitName').value;
                if (!splitName) {
                    alert('Please enter a name for your split');
                    return;
                }
                
                let hasWorkout = false;
                const mobileSplitTypeSelects = document.querySelectorAll('.profile-mobile-split-type');
                mobileSplitTypeSelects.forEach(select => {
                    if (select.value) hasWorkout = true;
                });
                
                if (!hasWorkout) {
                    alert('Please assign at least one workout to your weekly split');
                    return;
                }
                
                const formData = new FormData(document.getElementById('mobileSplitForm'));
                
                if (applyToCalendar) {
                    const mobileTargetMonthSelect = document.getElementById('mobileTargetMonth');
                    const [targetMonth, targetYear] = mobileTargetMonthSelect.value.split('_');
                    
                    formData.append('month', targetMonth);
                    formData.append('year', targetYear);
                    formData.append('apply_to_calendar', '1');
                    
                    fetch('save_workout_split.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout split applied to calendar successfully!');
                            window.location.href = `profile.php?month=${targetMonth}&year=${targetYear}`;
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout split.');
                    });
                } else {
                    fetch('save_workout_split.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Workout split saved successfully!');
                            document.getElementById('mobileSplitModal').style.display = 'none';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving your workout split.');
                    });
                }
            }
                        
            const modalCloseButtons = document.querySelectorAll('.profile-modal-close');
            modalCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.profile-modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            });
            
            const cancelPlan = document.getElementById('cancelPlan');
            if (cancelPlan) {
                cancelPlan.addEventListener('click', function() {
                    document.getElementById('dayModal').style.display = 'none';
                });
            }
            
            const cancelSplit = document.getElementById('cancelSplit');
            if (cancelSplit) {
                cancelSplit.addEventListener('click', function() {
                    document.getElementById('splitModal').style.display = 'none';
                });
            }
            
            const mobileEditCancel = document.getElementById('mobileEditCancel');
            if (mobileEditCancel) {
                mobileEditCancel.addEventListener('click', function() {
                    document.getElementById('mobileDayModal').style.display = 'none';
                });
            }
            
            const mobileSplitCancel = document.getElementById('mobileSplitCancel');
            if (mobileSplitCancel) {
                mobileSplitCancel.addEventListener('click', function() {
                    document.getElementById('mobileSplitModal').style.display = 'none';
                });
            }
        });
    </script>
    
    <?php if ($today_workout && !empty($today_workout['template_id'])): ?>
    <form id="workoutForm" action="workout.php" method="POST" style="display: none;">
        <input type="hidden" name="template_id" id="templateIdInput" value="<?= $today_workout['template_id'] ?>">
        <input type="hidden" name="start_workout" value="1">
        <input type="hidden" name="auto_start" value="1">
        <input type="hidden" name="start_step" value="2">
    </form>
    
    <form id="mobileWorkoutForm" action="workout.php" method="POST" style="display: none;">
        <input type="hidden" name="template_id" id="mobileTemplateIdInput" value="<?= $today_workout['template_id'] ?>">
        <input type="hidden" name="start_workout" value="1">
        <input type="hidden" name="auto_start" value="1">
        <input type="hidden" name="start_step" value="2">
    </form>
    <?php endif; ?>
</body>
</html> 