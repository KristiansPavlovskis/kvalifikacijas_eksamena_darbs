<?php
require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout.php");
    exit;
}

error_log("MOBILE-WORKOUT DEBUG - GET params: " . json_encode($_GET));
error_log("MOBILE-WORKOUT DEBUG - POST params: " . json_encode($_POST));

$user_id = $_SESSION["user_id"];

$template_id = null;
$auto_start = false;
$start_step = null;

if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
    $template_id = $_POST['template_id'];
    $auto_start = isset($_POST['auto_start']) && $_POST['auto_start'] == 1;
    $start_step = isset($_POST['start_step']) ? intval($_POST['start_step']) : null;
}

if ($template_id === null && isset($_GET['template_id']) && !empty($_GET['template_id'])) {
    $template_id = $_GET['template_id'];
    $auto_start = isset($_GET['auto_start']) && $_GET['auto_start'] == 1;
    $start_step = isset($_GET['start_step']) ? intval($_GET['start_step']) : null;
}

if ($template_id !== null) {
    $_SESSION['active_template_id'] = $template_id;
    
    if ($auto_start) {
        $_SESSION['start_workout_directly'] = true;
        $_SESSION['skip_template_selection'] = true;
        
        if ($start_step !== null && $start_step > 0) {
            $_SESSION['start_step'] = $start_step;
            error_log("MOBILE-WORKOUT DEBUG - Setting start_step in session to: " . $start_step);
        }
    }
}

error_log("MOBILE-WORKOUT DEBUG - SESSION after processing: " . 
          "active_template_id=" . (isset($_SESSION['active_template_id']) ? $_SESSION['active_template_id'] : 'null') . ", " . 
          "start_workout_directly=" . (isset($_SESSION['start_workout_directly']) ? 'true' : 'false') . ", " . 
          "skip_template_selection=" . (isset($_SESSION['skip_template_selection']) ? 'true' : 'false') . ", " . 
          "start_step=" . (isset($_SESSION['start_step']) ? $_SESSION['start_step'] : 'null'));

$debug_info = [];

try {
    if (tableExists($conn, 'workout_templates')) {
        $templates_query = "SELECT wt.*, COUNT(wte.id) as exercise_count, 
                          wt.estimated_time,
                          (SELECT MAX(created_at) FROM workouts WHERE template_id = wt.id AND user_id = ?) as last_used
                          FROM workout_templates wt
                          LEFT JOIN workout_template_exercises wte ON wt.id = wte.workout_template_id
                          WHERE wt.user_id = ?
                          GROUP BY wt.id
                          ORDER BY wt.created_at DESC";
        
        error_log("MOBILE-WORKOUT DEBUG - Personal templates query: " . str_replace('?', $user_id, $templates_query));
                          
        $stmt = mysqli_prepare($conn, $templates_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
        mysqli_stmt_execute($stmt);
        $templates = mysqli_stmt_get_result($stmt);
        
        $debug_info['personal_templates_count'] = mysqli_num_rows($templates);
    } else {
        $templates = false;
        $debug_info['error'] = 'workout_templates table does not exist';
    }
} catch (Exception $e) {
    error_log("Error fetching personal workout templates: " . $e->getMessage());
    $templates = false;
    $debug_info['personal_error'] = $e->getMessage();
}

try {
    if (tableExists($conn, 'workout_templates')) {
        $global_templates_query = "SELECT wt.*, COUNT(wte.id) as exercise_count, 
                               wt.estimated_time,
                               u.username as creator
                               FROM workout_templates wt
                               LEFT JOIN workout_template_exercises wte ON wt.id = wte.workout_template_id
                               LEFT JOIN users u ON wt.user_id = u.id
                               WHERE EXISTS (
                                 SELECT 1 FROM user_roles ur 
                                 JOIN roles r ON ur.role_id = r.id 
                                 WHERE ur.user_id = wt.user_id AND (r.name = 'admin' OR r.id = 5)
                               )
                               GROUP BY wt.id
                               ORDER BY wt.created_at DESC";
                               
        
        $global_stmt = mysqli_prepare($conn, $global_templates_query);
        mysqli_stmt_execute($global_stmt);
        $global_templates = mysqli_stmt_get_result($global_stmt);
        
        $debug_info['global_templates_count'] = mysqli_num_rows($global_templates);
        
    } else {
        $global_templates = false;
        $debug_info['global_error'] = 'workout_templates table does not exist';
    }
} catch (Exception $e) {
    error_log("Error fetching global workout templates: " . $e->getMessage());
    $global_templates = false;
    $debug_info['global_error'] = $e->getMessage();
}

function formatLastUsed($lastUsedDate) {
    if (!$lastUsedDate) return "Never used";
    
    $lastUsed = new DateTime($lastUsedDate);
    $now = new DateTime();
    $diff = $now->diff($lastUsed);
    
    if ($diff->days == 0) {
        return "Today";
    } elseif ($diff->days == 1) {
        return "Yesterday";
    } else {
        return $diff->days . " days ago";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Mobile Workout</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        
        body {
            background-color: var(--dark-bg);
            color: #ffffff;
            overflow: hidden;
            height: 100vh;
        }
                
        .step-container {
            width: 100%;
            height: 100vh;
            position: absolute;
            transition: transform 0.3s ease-in-out;
            left: 0;
            top: 0;
            transform: translateX(100%);
            z-index: 5;
            visibility: hidden;
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        #step1 {
            transform: translateX(0%);
            z-index: 2;
        }

        .step-active {
            transform: translateX(0%) !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 15 !important;
        }

        .step-previous {
            transform: translateX(-100%) !important;
            z-index: 1 !important;
        }

        .step-next {
            transform: translateX(100%) !important;
            z-index: 1 !important;
        }
        
        .mobile-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--dark-bg);
            color: white;
        }

        .mobile-header {
            background: var(--dark-card);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .back-button {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: 600;
            flex: 1;
        }
        
        .header-timer {
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .header-calories {
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobile-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .mobile-navigation {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--dark-card);
            display: flex;
            justify-content: space-around;
            padding: 0.75rem 0.5rem;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--gray-light);
            text-decoration: none;
            font-size: 0.8rem;
        }
        
        .nav-item i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }
        
        .nav-item.active {
            color: var(--primary);
        }

        .mobile-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 1rem 1rem 0.5rem;
            margin-bottom: 1rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .mobile-tabs::-webkit-scrollbar {
            display: none;
        }

        .mobile-tab {
            background: var(--dark-card);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .mobile-tab.active {
            background: var(--primary);
        }

        .mobile-templates {
            flex: 1;
            overflow-y: auto;
            padding: 0 1rem 1rem;
        }

        .mobile-template-card {
            background: var(--dark-card);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        .mobile-template-card.selected {
            border: 2px solid var(--primary);
        }

        .mobile-template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .mobile-template-title {
            font-weight: 600;
        }

        .mobile-template-edit {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .mobile-template-meta {
            color: var(--gray-light);
            font-size: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .mobile-template-meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .begin-workout-container {
            margin: 1rem 1rem 5rem;
        }
        
        #begin-workout-btn {
            background: var(--dark-card);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 0.5rem;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        #begin-workout-btn:not([disabled]) {
            background: var(--primary);
        }
        
        #begin-workout-btn[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .workout-progress {
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .exercise-card {
            background: var(--dark-card);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin: 1rem;
            margin-bottom: 5rem;
        }
        
        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .exercise-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        #set-counter {
            color: var(--primary);
            font-weight: 500;
        }
        
        .previous-set {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .set-label {
            color: var(--gray-light);
            font-size: 0.9rem;
        }
        
        .set-info {
            font-weight: 500;
            color: #3498db;
        }
        
        .set-completion-mark {
            color: #2ecc71;
            font-size: 1.25rem;
        }
        
        .input-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .number-input {
            display: flex;
            margin-bottom: 0.5rem;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .number-input button {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 0.5rem 1.25rem;
            cursor: pointer;
        }
        
        .number-input input {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            width: 100%;
            padding: 0.5rem;
        }
        
        .input-hint {
            color: var(--gray-light);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        
        .exercise-image {
            padding: 0 1rem 5rem;
        }
        
        .exercise-image img {
            width: 100%;
            border-radius: 0.5rem;
            object-fit: cover;
            height: 200px;
        }
        
        .rest-message {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        
        .rest-message h2 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .rating-options {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .rating-option {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--dark-card);
            cursor: pointer;
        }
        
        .rating-option.selected {
            border: 2px solid var(--primary);
        }
        
        .rating-icon {
            font-size: 1.5rem;
        }
        
        .rest-timer {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .timer-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 8px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transform: translateZ(0);
            isolation: isolate;
        }
        
        .timer-display {
            font-size: 3rem;
            font-weight: 700;
        }
        
        .timer-controls {
            display: flex;
            gap: 1rem;
        }
        
        .timer-adjust-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            cursor: pointer;
        }
        
        .next-exercise-preview {
            background: var(--dark-card);
            margin: 1.5rem 1rem;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .preview-header {
            color: var(--gray-light);
            margin-bottom: 0.75rem;
        }
        
        .preview-exercise {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .preview-icon {
            background: rgba(0, 0, 0, 0.2);
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary);
        }
        
        .preview-info {
            flex: 1;
        }
        
        .preview-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .preview-detail {
            color: var(--gray-light);
            font-size: 0.85rem;
        }
        
        #skip-rest-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: calc(100% - 2rem);
            margin: 0 1rem 5rem;
            padding: 0.875rem;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        #complete-set-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        #complete-set-btn:hover {
            background: #c0392b;
        }

        #complete-set-btn:active {
            transform: scale(0.98);
        }
        
        #summary-sets {
        color: #ffffff !important;
        font-size: 1.25rem;
        font-weight: 500; 
        }
        
        .mobile-template-global-tag {
            background-color: var(--primary);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .no-templates-message {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="step-container" id="step1">
        <div class="mobile-container">
            <div class="mobile-header">
                <button class="back-button" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-title">Start Workout</div>
            </div>

            <div class="mobile-content step1-content">
                <div class="mobile-tabs">
                    <div class="mobile-tab active" data-category="personal">Personal Templates</div>
                    <div class="mobile-tab" data-category="global">Global Templates</div>
                </div>

                <div class="mobile-templates">
                    <?php if ($templates && mysqli_num_rows($templates) > 0): ?>
                        <?php while ($template = mysqli_fetch_assoc($templates)): 
                            $lastUsed = formatLastUsed($template['last_used']);
                        ?>
                            <div class="mobile-template-card" data-id="<?php echo $template['id']; ?>" data-name="<?php echo htmlspecialchars($template['name']); ?>" data-category="personal">
                                <div class="mobile-template-header">
                                    <div class="mobile-template-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                    <a href="workout-templates.php?edit=<?php echo $template['id']; ?>" class="mobile-template-edit">edit template</a>
                                </div>
                                <div class="mobile-template-meta">
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-dumbbell"></i>
                                        <span><?php echo $template['exercise_count']; ?> exercises</span>
                                    </div>
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $template['estimated_time'] ?? '45'; ?> min</span>
                                    </div>
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Last: <?php echo $lastUsed; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-templates-message personal-message">
                            <p>You don't have any personal templates yet.</p>
                            <p>Create your first template in the Templates section!</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($global_templates && mysqli_num_rows($global_templates) > 0): ?>
                        <?php while ($template = mysqli_fetch_assoc($global_templates)): ?>
                            <div class="mobile-template-card" data-id="<?php echo $template['id']; ?>" data-name="<?php echo htmlspecialchars($template['name']); ?>" data-category="global" style="display:none;">
                                <div class="mobile-template-header">
                                    <div class="mobile-template-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                    <div class="mobile-template-global-tag">global</div>
                                </div>
                                <div class="mobile-template-meta">
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-dumbbell"></i>
                                        <span><?php echo $template['exercise_count']; ?> exercises</span>
                                    </div>
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $template['estimated_time'] ?? '45'; ?> min</span>
                                    </div>
                                    <div class="mobile-template-meta-item">
                                        <i class="fas fa-user"></i>
                                        <span>By: <?php echo htmlspecialchars($template['creator'] ?? 'GYMVERSE'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-templates-message global-message" style="display:none;">
                            <p>No global templates available at this time.</p>
                            <p>Check back later for new workouts!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="begin-workout-container">
                    <button id="begin-workout-btn" disabled>Begin Workout</button>
                </div>
            </div>
            
            <div class="mobile-navigation">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Templates</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>
    </div>

    <div class="step-container" id="step2">
        <div class="mobile-container">
            <div class="mobile-header">
                <button class="back-button" id="back-to-templates">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-title" id="workout-title">Start Workout</div>
                <div class="header-timer">
                    <i class="fas fa-clock"></i>
                    <span id="workout-timer">00:00</span>
                </div>
                <div class="header-calories">
                    <i class="fas fa-fire"></i>
                    <span id="calories-burned">0 kcal</span>
                </div>
            </div>

            <div class="mobile-content step2-content">
                <div class="workout-progress">
                    <div id="workout-progress-text">0/0 Exercises</div> 
                </div>
                
                <div class="exercise-card">
                    <div class="exercise-header">
                        <h2 id="exercise-name">Select a template to start</h2>
                        <div id="set-counter">Set 0/0</div>
                    </div>
                    
                    <div class="previous-set" style="display: none;">
                        <div class="set-label">Previous Set</div>
                        <div class="set-info" id="previous-set-info"></div>
                        <div class="set-completion-mark">✓</div>
                    </div>
                    
                    <div class="weight-input">
                        <div class="input-label">Weight (kg)</div>
                        <div class="number-input">
                            <button class="decrease-btn">−</button>
                            <input type="number" id="weight-input" value="0">
                            <button class="increase-btn">+</button>
                        </div>
                        <div class="input-hint" id="weight-hint"></div>
                    </div>
                    
                    <div class="reps-input">
                        <div class="input-label">Reps</div>
                        <div class="number-input">
                            <button class="decrease-btn">−</button>
                            <input type="number" id="reps-input" value="0">
                            <button class="increase-btn">+</button>
                        </div>
                        <div class="input-hint" id="reps-hint"></div>
                    </div>
                    
                    <button id="complete-set-btn">Complete Set</button>
                    
                </div>
                
                <div class="exercise-image">
                    <img id="exercise-image" src="https://cdn.pixabay.com/photo/2016/07/07/16/46/dice-1502706_640.jpg" alt="Exercise">
                </div>
            </div>
            
            <div class="mobile-navigation">
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Templates</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>
    </div>

    <div class="step-container" id="step3">
        <div class="mobile-container">
            <div class="mobile-header">
                <button class="back-button" id="back-to-exercise">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-title" id="rest-title">Full Body Workout</div>
                <div class="header-timer">
                    <i class="fas fa-clock"></i>
                    <span id="rest-workout-timer">00:00</span>
                </div>
                <div class="header-calories">
                    <i class="fas fa-fire"></i>
                    <span id="rest-calories-burned">0 kcal</span>
                </div>
            </div>

            <div class="mobile-content step3-content">
                <div class="rest-message">
                    <h2>How was that set?</h2>
                    
                    <div class="rating-options">
                        <div class="rating-option" data-rating="1">
                            <div class="rating-icon">😣</div>
                        </div>
                        <div class="rating-option" data-rating="2">
                            <div class="rating-icon">😕</div>
                        </div>
                        <div class="rating-option selected" data-rating="3">
                            <div class="rating-icon">😊</div>
                        </div>
                        <div class="rating-option" data-rating="4">
                            <div class="rating-icon">😄</div>
                        </div>
                        <div class="rating-option" data-rating="5">
                            <div class="rating-icon">🤩</div>
                        </div>
                    </div>
                </div>
                
                <div class="rest-timer">
                    <div class="timer-circle">
                        <div class="timer-display" id="timer-display">00:00</div>
                    </div>
                    
                    <div class="timer-controls">
                        <button class="timer-adjust-btn" id="decrease-time">-15s</button>
                        <button class="timer-adjust-btn" id="increase-time">+15s</button>
                    </div>
                </div>
                
                <div class="next-exercise-preview">
                    <div class="preview-header">Next Exercise</div>
                    <div class="preview-exercise">
                        <div class="preview-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="preview-info">
                            <div class="preview-name">Bench Press</div>
                            <div class="preview-detail">3 sets × 12 reps</div>
                        </div>
                        <div class="preview-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
                
                <button id="skip-rest-btn">Skip Rest</button>
            </div>
            
            <div class="mobile-navigation">
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Templates</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>
    </div>

    <div class="step-container" id="step4">
        <div class="mobile-container">
            <div class="mobile-header">
                <button class="back-button" id="back-to-rest">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-title">Workout Summary</div>
            </div>

            <div class="mobile-content step4-content">
                <div style="display: flex; justify-content: center; padding: 2rem 0;">
                    <div style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid #e74c3c; display: flex; align-items: center; justify-content: center; background-color: #e74c3c;">
                        <i class="fas fa-check" style="color: white; font-size: 2.5rem;"></i>
                    </div>
                </div>
                
                <h2 style="text-align: center; margin-bottom: 0.5rem;">Workout Complete!</h2>
                <p id="achievement-text" style="text-align: center; color: var(--gray-light); margin-bottom: 2rem;">Great job! You crushed it today.</p>
                
                <div style="display: flex; justify-content: space-between; margin: 0 1rem 2rem;">
                    <div style="background: var(--dark-card); border-radius: 0.5rem; padding: 1rem; text-align: center; width: 30%;">
                        <div style="color: var(--primary); font-size: 1.2rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div style="color: var(--gray-light); margin-bottom: 0.5rem;">Duration</div>
                        <div id="summary-time" style="font-size: 1.25rem; font-weight: 500;">00:00</div>
                    </div>
                    
                    <div style="background: var(--dark-card); border-radius: 0.5rem; padding: 1rem; text-align: center; width: 30%;">
                        <div style="color: var(--primary); font-size: 1.2rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div style="color: var(--gray-light); margin-bottom: 0.5rem;">Sets Done</div>
                        <div id="summary-sets" style="font-size: 1.25rem; font-weight: 500;">0</div>
                    </div>
                    
                    <div style="background: var(--dark-card); border-radius: 0.5rem; padding: 1rem; text-align: center; width: 30%;">
                        <div style="color: var(--primary); font-size: 1.2rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div style="color: var(--gray-light); margin-bottom: 0.5rem;">Volume</div>
                        <div id="summary-weight" style="font-size: 1.25rem; font-weight: 500;">0 kg</div>
                    </div>
                </div>
                
                <div style="margin: 0 1rem 1rem;">
                    <h3 style="margin-bottom: 0.75rem;">Workout Notes</h3>
                    <textarea id="workout-notes" placeholder="How was your workout? Note any achievements or challenges..." style="width: 100%; background: var(--dark-card); border: none; color: white; padding: 1rem; border-radius: 0.5rem; height: 120px; resize: none;"></textarea>
                </div>
                
                <div style="margin: 0 1rem 1.5rem;">
                    <h3 style="margin-bottom: 0.75rem;">Rate Your Workout</h3>
                    <div class="rating-options" style="justify-content: space-between; padding: 0 0.5rem;">
                        <div class="rating-option" data-rating="1">
                            <div class="rating-icon">😣</div>
                        </div>
                        <div class="rating-option" data-rating="2">
                            <div class="rating-icon">😕</div>
                        </div>
                        <div class="rating-option selected" data-rating="3">
                            <div class="rating-icon">😊</div>
                        </div>
                        <div class="rating-option" data-rating="4">
                            <div class="rating-icon">😄</div>
                        </div>
                        <div class="rating-option" data-rating="5">
                            <div class="rating-icon">🤩</div>
                        </div>
                    </div>
                </div>
                
                <div style="margin: 1.5rem 1rem 5rem;">
                    <button id="save-workout-btn" style="background: var(--primary); color: white; border: none; width: 100%; padding: 1rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Workout
                    </button>
                    <button id="discard-workout-btn" style="background: transparent; color: white; border: none; width: 100%; padding: 1rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600;">
                        <i class="fas fa-trash" style="margin-right: 0.5rem;"></i> Discard
                    </button>
                </div>
            </div>
            
            <div class="mobile-navigation">
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Templates</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Mobile workout page initialized with global templates support");
            window.autoStartInProgress = false;
            
            const personalTemplates = document.querySelectorAll('.mobile-template-card[data-category="personal"]');
            const globalTemplates = document.querySelectorAll('.mobile-template-card[data-category="global"]');
            const personalNoTemplatesMsg = document.querySelector('.no-templates-message.personal-message');
            const globalNoTemplatesMsg = document.querySelector('.no-templates-message.global-message');
            
            let currentStep = 1;
            let workoutData = {
                title: '',
                templateId: null,
                exercises: [],
                currentExercise: 0,
                currentSet: 0,
                startTime: null,
                duration_minutes: 0,
                caloriesBurned: 0,
                totalSets: 0,
                completedSets: 0,
                restTime: 90,
                restTimeRemaining: 0,
                restTimerInterval: null,
                currentRestTime: 0,
                overallRating: 3,
                workoutTimerRAF: null
            };

            function setupTabFunctionality() {
                console.log("Setting up tab functionality");
                
                document.querySelectorAll('.mobile-tab').forEach(tab => {
                    tab.removeEventListener('click', handleTabClick);
                    tab.addEventListener('click', handleTabClick);
                });
                
                setupTemplateCardHandlers();
                
                const activeTab = document.querySelector('.mobile-tab.active');
                if (activeTab) {
                    console.log("Setting initial tab:", activeTab.dataset.category);
                    handleTabClick.call(activeTab);
                }
            }
            
            function setupTemplateCardHandlers() {
                console.log("Setting up template card handlers");
                document.querySelectorAll('.mobile-template-card').forEach(card => {
                    card.removeEventListener('click', handleTemplateCardClick);
                    card.addEventListener('click', handleTemplateCardClick);
                });
            }
            
            function handleTemplateCardClick() {
                console.log("Template card clicked:", this.dataset.id, this.dataset.name, "Category:", this.dataset.category);
                
                document.querySelectorAll('.mobile-template-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                this.classList.add('selected');
                
                workoutData.templateId = parseInt(this.dataset.id, 10);
                workoutData.title = this.dataset.name;
                
                const beginWorkoutBtn = document.getElementById('begin-workout-btn');
                beginWorkoutBtn.removeAttribute('disabled');
            }
            
            function handleTabClick() {
                const category = this.getAttribute('data-category');
                console.log("Tab clicked:", category);
                
                document.querySelectorAll('.mobile-tab').forEach(t => {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                
                const visibleTemplates = [];
                
                document.querySelectorAll('.mobile-template-card').forEach(card => {
                    if (card.dataset.category === category) {
                        card.style.display = '';
                        visibleTemplates.push(card);
                        console.log(`Showing template: ${card.querySelector('.mobile-template-title')?.textContent}`);
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                console.log(`Found ${visibleTemplates.length} ${category} templates to display`);
                
                if (category === 'personal') {
                    const personalMsg = document.querySelector('.no-templates-message.personal-message');
                    if (personalMsg) {
                        personalMsg.style.display = visibleTemplates.length > 0 ? 'none' : 'block';
                    }
                    
                    const globalMsg = document.querySelector('.no-templates-message.global-message');
                    if (globalMsg) globalMsg.style.display = 'none';
                    
                } else if (category === 'global') {
                    const personalMsg = document.querySelector('.no-templates-message.personal-message');
                    if (personalMsg) personalMsg.style.display = 'none';
                    
                    const globalMsg = document.querySelector('.no-templates-message.global-message');
                    if (globalMsg) {
                        globalMsg.style.display = visibleTemplates.length > 0 ? 'none' : 'block';
                    }
                }
                
                console.log("Templates visibility updated:", category);
                
                document.querySelectorAll('.mobile-template-card').forEach(c => {
                    c.classList.remove('selected');
                });
                document.getElementById('begin-workout-btn').setAttribute('disabled', 'disabled');
            }
            
            
            setupTabFunctionality();
            
            
            const templatesContainer = document.querySelector('.mobile-templates');
            if (templatesContainer) {
                console.log("Setting up MutationObserver for template container");
                const observer = new MutationObserver((mutations) => {
                    console.log("Templates container changed, updating handlers");
                    setupTemplateCardHandlers();
                    
                    const activeTab = document.querySelector('.mobile-tab.active');
                    if (activeTab) {
                        console.log("Re-triggering active tab:", activeTab.dataset.category);
                        handleTabClick.call(activeTab);
                    }
                });
                
                observer.observe(templatesContainer, { 
                    childList: true, 
                    subtree: true 
                });
            }
            
            
            <?php if (isset($_SESSION['active_template_id']) && isset($_SESSION['start_workout_directly']) && $_SESSION['start_workout_directly']): ?>
            console.log("Mobile - Auto-start detected!");
            const templateId = <?= $_SESSION['active_template_id'] ?>;
            console.log("Mobile - Template ID:", templateId);
            
            window.autoStartInProgress = true;
            
            <?php if (isset($_SESSION['start_step']) && $_SESSION['start_step'] > 1): ?>
            const targetStep = <?= $_SESSION['start_step'] ?>;
            console.log("Mobile - Starting at step:", targetStep);
            
            document.getElementById('step1').classList.remove('step-active');
            document.getElementById('step1').classList.add('step-previous');
            const targetStepElement = document.getElementById('step' + targetStep);
            targetStepElement.classList.remove('step-next');
            targetStepElement.classList.add('step-active');
            
            currentStep = targetStep;
            
            if (targetStep === 2) {
                console.log("Mobile - Auto-triggering workout start for step 2");
                fetch('get_template.php?id=' + templateId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Mobile - Template data loaded successfully");
                            
                            workoutData.title = data.template.name;
                            workoutData.templateId = templateId;
                            workoutData.exercises = data.exercises.map(ex => ({
                                name: ex.exercise_name,
                                sets: parseInt(ex.sets) || 3,
                                restTime: parseInt(ex.rest_time) || 90,
                                currentSetData: [],
                                image: `../assets/images/exercises/${ex.exercise_name.toLowerCase().replace(/ /g, '-')}.jpg`,
                                notes: ex.notes || '',
                                lastWeight: 0,
                                lastReps: 8
                            }));
                            
                            if (workoutData.exercises.length > 0) {
                                workoutData.restTime = workoutData.exercises[0].restTime;
                                workoutData.currentExercise = 0;
                                workoutData.currentSet = 0;
                            }
            
                            workoutData.totalSets = workoutData.exercises.reduce((total, exercise) => total + exercise.sets, 0);
                            
                            initializeWorkoutTimer();
                            workoutTitle.textContent = workoutData.title;
                            
                            loadCurrentExercise();
                            
                            startWorkout();
                        } else {
                            console.error("Error loading template data:", data.message);
                            alert("Error starting workout. Please try again.");
                            window.autoStartInProgress = false;
                            resetStepStates();
                        }
                    })
                    .catch(error => {
                        console.error("Error auto-starting workout at step 2:", error);
                        alert("Error starting workout. Please try again.");
                        window.autoStartInProgress = false;
                        resetStepStates();
                    });
            }
            
            <?php 
                unset($_SESSION['start_step']);
            ?>
            <?php 

            else:
            
            if (isset($_SESSION['skip_template_selection']) && $_SESSION['skip_template_selection']): ?>
                console.log("Mobile - Skip template selection enabled - starting workout immediately");
                
                document.getElementById('step1').classList.remove('step-active');
                document.getElementById('step1').classList.add('step-previous');
                const step2Element = document.getElementById('step2');
                step2Element.classList.remove('step-next');
                step2Element.classList.add('step-active');
                
                currentStep = 2;
                
                fetch(`get_template.php?id=${templateId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Mobile - Template data loaded successfully, starting workout");
                            console.log("Mobile - Template name:", data.template.name);
                            console.log("Mobile - Exercise count:", data.exercises.length);
                            
                            workoutData.title = data.template.name;
                            workoutData.templateId = templateId;
                            workoutData.exercises = data.exercises.map(ex => ({
                                name: ex.exercise_name,
                                sets: parseInt(ex.sets) || 3,
                                restTime: parseInt(ex.rest_time) || 90,
                                currentSetData: [],
                                image: `../assets/images/exercises/${ex.exercise_name.toLowerCase().replace(/ /g, '-')}.jpg`,
                                notes: ex.notes || '',
                                lastWeight: 0,
                                lastReps: 8
                            }));
                            
                            if (workoutData.exercises.length > 0) {
                                workoutData.restTime = workoutData.exercises[0].restTime;
                                workoutData.currentExercise = 0;
                                workoutData.currentSet = 0;
                            }
                            
                            workoutData.totalSets = workoutData.exercises.reduce((total, exercise) => total + exercise.sets, 0);
                            
                            initializeWorkoutTimer();
                            workoutTitle.textContent = workoutData.title;
                            
                            loadCurrentExercise();
                            
                            if (!document.getElementById('step2').classList.contains('step-active')) {
                                console.log("Step 2 was reset - forcing it active again");
                                document.getElementById('step1').classList.remove('step-active');
                                document.getElementById('step1').classList.add('step-previous');
                                document.getElementById('step2').classList.remove('step-next');
                                document.getElementById('step2').classList.add('step-active');
                                currentStep = 2;
                            }
                            
                            console.log("Mobile - Workout initialization complete");
                        } else {
                            console.error("Error loading template data:", data.message);
                            alert("Error starting workout. Please try again.");
                            window.autoStartInProgress = false;
                            resetStepStates();
                        }
                    })
                    .catch(error => {
                        console.error("Error auto-starting workout:", error);
                        alert("Error starting workout. Please try again.");
                        window.autoStartInProgress = false;
                        resetStepStates();
                    });
                    
                <?php 
                    unset($_SESSION['start_workout_directly']);
                    unset($_SESSION['skip_template_selection']);
                    unset($_SESSION['active_template_id']);
                    unset($_SESSION['start_step']);
                ?>
            <?php 
            endif;
            endif; 
            endif; 
            ?>
            
            if (!window.autoStartInProgress) {
                resetStepStates();
            }
            
            function resetStepStates() {
                console.log("Resetting all steps to initial state");
                const steps = document.querySelectorAll('.step-container');
                steps.forEach(step => {
                    step.classList.remove('step-active', 'step-previous', 'step-next');
                    const targetStep = parseInt(step.id.replace('step', ''));
                    if (targetStep === 1) {
                        step.classList.add('step-active');
                    } else {
                        step.classList.add('step-next');
                    }
                });
                currentStep = 1;
            }
            
            function showStep(stepNumber) {
                console.log(`Transitioning to step ${stepNumber}`);
                const steps = document.querySelectorAll('.step-container');
                
                steps.forEach(step => {
                    const targetStep = parseInt(step.id.replace('step', ''));
                    step.classList.remove('step-active', 'step-previous', 'step-next');
                    
                    if (targetStep === stepNumber) {
                        step.classList.add('step-active');
                    } else if (targetStep < stepNumber) {
                        step.classList.add('step-previous');
                    } else {
                        step.classList.add('step-next');
                    }
                });
                
                currentStep = stepNumber;
                
                if (stepNumber === 2 && !workoutData.startTime) {
                    startWorkout();
                }
            }
            
            const beginWorkoutBtn = document.getElementById('begin-workout-btn');
            const templateCards = document.querySelectorAll('.mobile-template-card');
            const tabs = document.querySelectorAll('.mobile-tab');
            const backToTemplates = document.getElementById('back-to-templates');
            const workoutTitle = document.getElementById('workout-title');
            const workoutTimer = document.getElementById('workout-timer');
            const caloriesBurned = document.getElementById('calories-burned'); 
            const workoutProgressText = document.getElementById('workout-progress-text');
            const exerciseName = document.getElementById('exercise-name');
            const setCounter = document.getElementById('set-counter');
            const previousSetInfo = document.getElementById('previous-set-info');
            const weightInput = document.getElementById('weight-input');
            const repsInput = document.getElementById('reps-input');

            weightInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9.]/g, '');
            });

            repsInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            const weightHint = document.getElementById('weight-hint');
            const repsHint = document.getElementById('reps-hint');
            const exerciseImage = document.getElementById('exercise-image');
            const backToExercise = document.getElementById('back-to-exercise');
            const restTitle = document.getElementById('rest-title');
            const restWorkoutTimer = document.getElementById('rest-workout-timer');
            const restCaloriesBurned = document.getElementById('rest-calories-burned');
            const ratingOptions = document.querySelectorAll('.rating-option');
            const timerDisplay = document.getElementById('timer-display');
            const decreaseTimeBtn = document.getElementById('decrease-time');
            const increaseTimeBtn = document.getElementById('increase-time');
            const skipRestBtn = document.getElementById('skip-rest-btn');
            const previewName = document.querySelector('.preview-name');
            const previewDetail = document.querySelector('.preview-detail');

            beginWorkoutBtn.addEventListener('click', function() {
                if (workoutData.templateId) {
                    fetchTemplateExercises(workoutData.templateId);
                } else if (emptyWorkout.classList.contains('selected')) {
                    showStep(2);
                }
            });
            
            backToTemplates.addEventListener('click', function() {
                if (confirm('Are you sure you want to cancel this workout?')) {
                    resetWorkout();
                    showStep(1);
                }
            });
            
            document.querySelector('.weight-input .decrease-btn').addEventListener('click', function() {
                let currentWeight = parseFloat(weightInput.value);
                if (currentWeight >= 2.5) {
                    weightInput.value = (currentWeight - 2.5).toFixed(1);
                    updateWeightInput();
                }
            });
            
            document.querySelector('.weight-input .increase-btn').addEventListener('click', function() {
                let currentWeight = parseFloat(weightInput.value);
                weightInput.value = (currentWeight + 2.5).toFixed(1);
                updateWeightInput();
            });
            
            weightInput.addEventListener('change', function() {
                let value = parseFloat(this.value);
                if (!isNaN(value) && value >= 0) {
                    this.value = value.toFixed(1);
                } else {
                    this.value = '0.0';
                }
                console.log("Weight input changed to: " + this.value);
            });

            function updateWeightInput() {
                let value = parseFloat(weightInput.value);
                if (isNaN(value) || value < 0) {
                    weightInput.value = '0.0';
                } else {
                    weightInput.value = value.toFixed(1);
                }
                console.log("Weight input updated to: " + weightInput.value);
            }
            
            document.querySelector('.reps-input .decrease-btn').addEventListener('click', function() {
                let currentReps = parseInt(repsInput.value);
                if (currentReps > 1) {
                    repsInput.value = currentReps - 1;
                    updateRepsInput();
                }
            });
            
            document.querySelector('.reps-input .increase-btn').addEventListener('click', function() {
                let currentReps = parseInt(repsInput.value);
                repsInput.value = currentReps + 1;
                updateRepsInput();
            });
            
           repsInput.addEventListener('change', function() {
                let value = parseInt(this.value, 10);
                if (!isNaN(value) && value > 0) {
                    this.value = value;
                } else {
                    this.value = '8';
                }
                console.log("Reps input changed to: " + this.value);
            });
            document.body.addEventListener('click', function(e) {
    if (e.target?.matches('#complete-set-btn')) {
        const weightInput = document.querySelector('#step2.step-active #weight-input');
        const repsInput = document.querySelector('#step2.step-active #reps-input');
        const currentWeight = parseFloat(weightInput.value) || 0;
        const currentReps = parseInt(repsInput.value, 10) || 0;
        const exercise = workoutData.exercises[workoutData.currentExercise];
        if (exercise) {
            exercise.currentSetData.push({
                weight: currentWeight,
                reps: currentReps,
                rating: 3
            });
            
            exercise.lastWeight = currentWeight;
            exercise.lastReps = currentReps;
            
            console.log('Saved set:', exercise.currentSetData[exercise.currentSetData.length-1]);

            const weight = parseFloat(weightInput.value) || 0;
            const reps = parseInt(repsInput.value) || 0;
            const caloriesPerSet = (weight * reps * 0.1) + (reps * 0.5);
            
            workoutData.caloriesBurned += Math.max(1, Math.round(caloriesPerSet));
            
            caloriesBurned.textContent = `${Math.round(workoutData.caloriesBurned)} kcal`; 
            restCaloriesBurned.textContent = `${Math.round(workoutData.caloriesBurned)} kcal`;

            showRestTimer();
        }
    }
});
document.body.addEventListener('input', function(e) {
    if (e.target?.matches('#weight-input')) {
        e.target.value = e.target.value.replace(/[^0-9.]/g, '');
        const parts = e.target.value.split('.');
        if (parts.length > 1) e.target.value = parts[0] + '.' + parts[1].slice(0,1);
    }
    
    if (e.target?.matches('#reps-input')) {
        e.target.value = e.target.value.replace(/\D/g, '');
    }
});

document.body.addEventListener('click', function(e) {
    if (e.target?.matches('.number-input button')) {
        const input = e.target.closest('.number-input').querySelector('input');
        const isWeight = input.id === 'weight-input';
        const step = isWeight ? 2.5 : 1;
        
        if (e.target.classList.contains('decrease-btn')) {
            input.value = Math.max(0, (parseFloat(input.value) || 0) - step);
        } else {
            input.value = (parseFloat(input.value) || 0) + step;
        }
        
        if (isWeight) {
            input.value = parseFloat(input.value).toFixed(1);
        } else {
            input.value = parseInt(input.value, 10);
        }
        
        input.dispatchEvent(new Event('input'));
    }
});

            function updateRepsInput() {
                let value = parseInt(repsInput.value);
                if (isNaN(value) || value < 1) {
                    repsInput.value = '0';
                }
            }

            function updateRestTimerContent() {
                restTitle.textContent = workoutData.title;
                
                ratingOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.dataset.rating === '3') {
                        option.classList.add('selected');
                    }
                });
                
                updateNextExercisePreview();
                
                const currentExercise = workoutData.exercises[workoutData.currentExercise];
                if (currentExercise && currentExercise.restTime) {
                    workoutData.restTime = currentExercise.restTime;
                }
                
            }

            
            ratingOptions.forEach(option => {
                option.addEventListener('click', function() {
                    if (workoutData.currentExercise < 0 || 
                        workoutData.currentExercise >= workoutData.exercises.length) {
                        console.error('Cannot rate - workout not in progress');
                        return;
                    }

                    ratingOptions.forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    const rating = parseInt(this.dataset.rating);
                    const exercise = workoutData.exercises[workoutData.currentExercise];
                    
                    if (exercise.currentSetData.length > 0) {
                        const lastSetIndex = exercise.currentSetData.length - 1;
                        exercise.currentSetData[lastSetIndex].rating = rating;
                    }
                });
            });

            function updateRestTimerDisplay() {
                const timerElement = document.getElementById('timer-display');
                if (timerElement) {
                    timerElement.textContent = formatTime(workoutData.currentRestTime);
                }
            }
                        
            decreaseTimeBtn.addEventListener('click', () => {
                console.log('Skip time button clicked');
                workoutData.currentRestTime = Math.max(0, workoutData.currentRestTime - 15);
                updateRestTimerDisplay();
            });

            increaseTimeBtn.addEventListener('click', () => {
                console.log('Skip time button clicked');
                workoutData.currentRestTime += 15;
                updateRestTimerDisplay(); 
            });
 
            document.body.addEventListener('click', function(e) {
                if (e.target && e.target.matches('#skip-rest-btn')) {
                    console.log('Skip rest button clicked');
                    
                    if (timerRAF) {
                        cancelAnimationFrame(timerRAF);
                        timerRAF = null;
                    }
                    
                    goToNextSet();
                }
            });

                        
            function initializeWorkoutTimer() {
                if (workoutData.startTime === null) {
                    workoutData.startTime = Date.now();
                }
                
                if (workoutData.workoutTimerRAF) {
                    cancelAnimationFrame(workoutData.workoutTimerRAF);
                }
                
                function updateWorkoutTimer() {
                    if (!workoutData.startTime) return;
                    
                    const elapsed = Math.floor((Date.now() - workoutData.startTime) / 1000);
                    const minutes = Math.floor(elapsed / 60);
                    const seconds = elapsed % 60;
                    const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    document.querySelectorAll('#workout-timer, #rest-workout-timer').forEach(el => {
                        if (el) el.textContent = formattedTime;
                    });
                    
                    workoutData.duration_minutes = elapsed / 60;
                    workoutData.workoutTimerRAF = requestAnimationFrame(updateWorkoutTimer);
                }
                
                workoutData.workoutTimerRAF = requestAnimationFrame(updateWorkoutTimer);
            }

            function startWorkout() {
                console.log("Starting workout...");
                
                if (!workoutData.startTime) {
                    console.log("Initializing workout timer");
                    initializeWorkoutTimer();
                }
                
                if (!workoutData.title || !workoutData.exercises || workoutData.exercises.length === 0) {
                    console.error("Cannot start workout - missing required data");
                    return;
                }
                
                console.log("Setting workout title:", workoutData.title);
                workoutTitle.textContent = workoutData.title;
                
                if (window.autoStartInProgress && !document.getElementById('step2').classList.contains('step-active')) {
                    console.log("Auto-start: Re-activating step 2");
                    document.getElementById('step1').classList.remove('step-active');
                    document.getElementById('step1').classList.add('step-previous');
                    document.getElementById('step2').classList.remove('step-next');
                    document.getElementById('step2').classList.add('step-active');
                    currentStep = 2;
                }

                else if (!window.autoStartInProgress && currentStep !== 2) {
                    console.log("Transitioning to step 2");
                    showStep(2);
                }
                
                if (workoutData.currentExercise === undefined) {
                    console.log("Initializing current exercise");
                    workoutData.currentExercise = 0;
                    workoutData.currentSet = 0;
                }
                
                console.log("Loading current exercise");
                loadCurrentExercise();
            }
            
            function fetchTemplateExercises(templateId) {
                console.log(`Fetching exercises for template ID: ${templateId}`);
                fetch(`get_template.php?id=${templateId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.exercises && data.exercises.length > 0) {
                            console.log('Received exercises:', data.exercises);
                            workoutData.exercises = data.exercises.map(ex => {
                                return {
                                    name: ex.exercise_name,
                                    sets: parseInt(ex.sets) || 3,
                                    restTime: parseInt(ex.rest_time) || 90,
                                    currentSetData: [],
                                    image: `../assets/images/exercises/${ex.exercise_name.toLowerCase().replace(/ /g, '-')}.jpg`,
                                    notes: ex.notes || ''
                                };
                            });
                            
                            if (workoutData.exercises.length > 0) {
                                workoutData.restTime = workoutData.exercises[0].restTime;
                            }
                            
                            workoutData.totalSets = workoutData.exercises.reduce((total, exercise) => total + exercise.sets, 0);
                            console.log(`Workout prepared with ${workoutData.exercises.length} exercises and ${workoutData.totalSets} total sets`);
                            
                            showStep(2);
                        } else {
                            console.warn('No exercises found in template, creating empty workout');
                            alert('No exercises found in this template. Creating an empty workout.');
                            showStep(2);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching template exercises:', error);
                        
                        workoutData.exercises = [
                            {
                                name: 'Bench Press',
                                sets: 3,
                                lastWeight: 75,
                                lastReps: 12,
                                currentSetData: [],
                                image: '../assets/images/exercises/bench-press.jpg',
                                restTime: 90
                            },
                            {
                                name: 'Squat',
                                sets: 3,
                                lastWeight: 100,
                                lastReps: 10,
                                currentSetData: [],
                                image: '../assets/images/exercises/squat.jpg',
                                restTime: 90
                            }
                        ];
                        
                        workoutData.totalSets = workoutData.exercises.reduce((total, exercise) => total + exercise.sets, 0);
                        console.log(`Using fallback workout with ${workoutData.exercises.length} exercises`);
                        
                        showStep(2);
                    });
            }
            
            function loadCurrentExercise() {
                console.log("Loading current exercise...");
                
                if (!workoutData.exercises || workoutData.exercises.length === 0) {
                    console.error("No exercises available");
                    return;
                }
                
                if (workoutData.currentExercise === undefined || workoutData.currentExercise < 0) {
                    console.log("Initializing current exercise to 0");
                    workoutData.currentExercise = 0;
                }
                
                if (workoutData.currentSet === undefined || workoutData.currentSet < 0) {
                    console.log("Initializing current set to 0");
                    workoutData.currentSet = 0;
                }
                
                const exercise = workoutData.exercises[workoutData.currentExercise];
                if (!exercise) {
                    console.error("Invalid exercise index:", workoutData.currentExercise);
                    return;
                }
                
                console.log("Loading exercise:", exercise.name);
                
                exerciseName.textContent = exercise.name;
                setCounter.textContent = `Set ${workoutData.currentSet + 1}/${exercise.sets}`;
                
                const totalExercises = workoutData.exercises.length;
                const currentExerciseNumber = workoutData.currentExercise + 1;
                workoutProgressText.textContent = `${currentExerciseNumber}/${totalExercises} Exercises`;
                
                if (workoutData.currentSet > 0 && exercise.currentSetData && exercise.currentSetData.length > 0) {
                    const prevSet = exercise.currentSetData[workoutData.currentSet - 1];
                    if (prevSet) {
                        previousSetInfo.textContent = `${prevSet.weight} kg × ${prevSet.reps} reps`;
                        previousSetInfo.parentElement.style.display = 'flex';
                        
                        weightInput.value = prevSet.weight.toFixed(1);
                        repsInput.value = prevSet.reps;
                    } else {
                        previousSetInfo.parentElement.style.display = 'none';
                        weightInput.value = (exercise.lastWeight || 0).toFixed(1);
                        repsInput.value = exercise.lastReps || 8;
                    }
                } else {
                    previousSetInfo.parentElement.style.display = 'none';
                    weightInput.value = (exercise.lastWeight || 0).toFixed(1);
                    repsInput.value = exercise.lastReps || 8;
                }

                weightHint.textContent = `last time was ${exercise.lastWeight || 0}kg`;
                repsHint.textContent = `last time was ${exercise.lastReps || 8} reps for ${exercise.lastWeight || 0}kg`;
                
                exerciseImage.src = exercise.image || 'https://cdn.pixabay.com/photo/2016/07/07/16/46/dice-1502706_640.jpg';
                exerciseImage.onerror = function() {
                    this.src = 'https://cdn.pixabay.com/photo/2016/07/07/16/46/dice-1502706_640.jpg';
                };
                
                updateWeightInput();
                updateRepsInput();
                
                console.log(`Exercise loaded: ${exercise.name} - Set ${workoutData.currentSet + 1}/${exercise.sets}`);
                console.log(`Input values set: weight=${weightInput.value}, reps=${repsInput.value}`);
            }
            
            function showRestTimer() {
                updateRestTimerContent();
                showStep(3);
                startRestTimer();
            }
                        
            function updateNextExercisePreview() {
                const exercise = workoutData.exercises[workoutData.currentExercise];

                if (workoutData.currentSet + 1 >= exercise.sets) {
                    if (workoutData.currentExercise + 1 < workoutData.exercises.length) {
                        const nextExercise = workoutData.exercises[workoutData.currentExercise + 1];
                        previewName.textContent = nextExercise.name;
                        previewDetail.textContent = `${nextExercise.sets} sets`;
                        
                        if (nextExercise.lastReps) {
                            previewDetail.textContent += ` × ${nextExercise.lastReps} reps`;
                        }
                    } else {
                        previewName.textContent = 'Workout Complete';
                        previewDetail.textContent = 'Almost there!';
                    }
                } else {
                    previewName.textContent = exercise.name;
                    previewDetail.textContent = `Set ${workoutData.currentSet + 2} of ${exercise.sets}`;
                    
                    if (workoutData.currentSet < exercise.currentSetData.length) {
                        const prevSetData = exercise.currentSetData[workoutData.currentSet];
                        if (prevSetData) {
                            previewDetail.textContent += ` (Last: ${prevSetData.weight}kg × ${prevSetData.reps})`;
                        }
                    }
                }
            }
            
            let timerRAF = null;

            function startRestTimer() {
                const currentExercise = workoutData.exercises[workoutData.currentExercise];
                let timerElement = document.querySelector('#step3.step-active #timer-display');
                
                if (!timerElement) {
                    console.error('Timer element not found in active step!');
                    return;
                }

                if (timerRAF) {
                    cancelAnimationFrame(timerRAF);
                    timerRAF = null;
                }
                
                workoutData.currentRestTime = currentExercise.restTime || 90;
                timerElement.textContent = formatTime(workoutData.currentRestTime);
                let lastUpdate = performance.now();

                function updateTimer(timestamp) {
                    const delta = timestamp - lastUpdate;
                    
                    if (delta >= 1000) {
                        workoutData.currentRestTime--;
                        lastUpdate = timestamp;
                        
                        if (timerElement && timerElement.parentNode) {
                            timerElement.textContent = formatTime(workoutData.currentRestTime);
                        } else {
                            cancelAnimationFrame(timerRAF);
                            timerRAF = null;
                            return;
                        }
                    }

                    if (workoutData.currentRestTime > 0) {
                        timerRAF = requestAnimationFrame(updateTimer);
                    } else {
                        goToNextSet();
                    }
                }

                timerRAF = requestAnimationFrame(updateTimer);
            }

            function formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
            }

            
            function clearRestTimer() {
                if (workoutData.restInterval) {
                    clearInterval(workoutData.restInterval);
                }
            }
            
            ratingOptions.forEach(option => {
                option.addEventListener('click', function() {
                    if (workoutData.currentExercise < 0 || 
                        workoutData.currentExercise >= workoutData.exercises.length) {
                        console.error('Cannot rate - workout not in progress');
                        return;
                    }

                    ratingOptions.forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    const rating = parseInt(this.dataset.rating);
                    const exercise = workoutData.exercises[workoutData.currentExercise];
                    
                    if (exercise.currentSetData.length > 0) {
                        const lastSetIndex = exercise.currentSetData.length - 1;
                        exercise.currentSetData[lastSetIndex].rating = rating;
                    }
                });
            });

            document.getElementById('back-to-rest').addEventListener('click', function() {
                if (workoutData.currentExercise >= workoutData.exercises.length) {
                    workoutData.currentExercise = 0;
                    workoutData.currentSet = 0;
                }
                showStep(3);
            });

            function goToNextSet() {
                const exercise = workoutData.exercises[workoutData.currentExercise];
                
                if (!exercise || typeof exercise.sets === 'undefined') {
                    console.error('Invalid exercise state');
                    return;
                }

                if (workoutData.currentSet + 1 >= exercise.sets) {
                    workoutData.currentExercise++;
                    workoutData.currentSet = 0;
                    
                    if (workoutData.currentExercise >= workoutData.exercises.length) {
                        completeWorkout();
                        return;
                    }
                } else {
                    workoutData.currentSet++;
                }

                workoutData.currentExercise = Math.max(0, workoutData.currentExercise);
                
                loadCurrentExercise();
                showStep(2);
            }
            
            function completeWorkout() {
                if (workoutData.workoutTimerRAF) {
                    cancelAnimationFrame(workoutData.workoutTimerRAF);
                    workoutData.workoutTimerRAF = null;
                }
                
                if (timerRAF) {
                    cancelAnimationFrame(timerRAF);
                    timerRAF = null;
                }
                
                const endTime = Date.now();
                const durationMs = endTime - workoutData.startTime;
                workoutData.duration_minutes = durationMs / 60000;
                
                console.log(`Workout completed in ${workoutData.duration_minutes.toFixed(2)} minutes`);
                
                showWorkoutSummary();
            }
            
            function showWorkoutSummary() {
            showStep(4);
            
            const allSummarySetsElements = document.querySelectorAll('#summary-sets');
            console.log(`Found ${allSummarySetsElements.length} elements with ID 'summary-sets'`);
            
            const totalSets = workoutData.exercises.reduce((total, exercise) => 
                total + exercise.currentSetData.length, 0
            );
            
            let totalVolume = 0;
            workoutData.exercises.forEach(exercise => { 
                exercise.currentSetData.forEach(set => {
                    totalVolume += set.weight * set.reps;
                });
            });

            const mins = Math.floor(workoutData.duration_minutes);
            const secs = Math.round((workoutData.duration_minutes - mins) * 60);
            const formattedTime = `${mins}:${String(secs).padStart(2, '0')}`;
            
            workoutData.totalVolume = totalVolume;
            workoutData.totalSets = totalSets;
            workoutData.avgIntensity = totalSets > 0 ? (totalVolume / totalSets).toFixed(2) : 0;
            
            setTimeout(() => {
                document.querySelectorAll('#summary-sets').forEach((element, index) => {
                    console.log(`Updating summary-sets element ${index}:`, element);
                    element.textContent = totalSets;
                    element.style.cssText += "; color: #ffffff !important; visibility: visible; display: block;";
                });
                
                document.querySelectorAll('#summary-time').forEach(element => {
                    element.textContent = formattedTime;
                });
                
                document.querySelectorAll('#summary-weight').forEach(element => {
                    element.textContent = `${totalVolume.toFixed(1)} kg`;
                });
                 
                document.querySelectorAll('#summary-sets').forEach((element, index) => {
                    console.log(`Element ${index} after updates:`, {
                        text: element.textContent,
                        isVisible: window.getComputedStyle(element).display !== 'none' && 
                                window.getComputedStyle(element).visibility !== 'hidden',
                        position: element.getBoundingClientRect()
                    });
                });
                
                const duplicates = document.querySelectorAll('#summary-sets');
                for (let i = 1; i < duplicates.length; i++) {
                    duplicates[i].id = `summary-sets-${i}`; 
                }
            }, 300);

            if (!window.summaryListenersAdded) {
                const summaryRatingOptions = document.querySelectorAll('#step4 .rating-option');
                
                summaryRatingOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        summaryRatingOptions.forEach(o => o.classList.remove('selected'));
                        this.classList.add('selected');
                        workoutData.overallRating = parseInt(this.dataset.rating);
                        
                        const rating = parseInt(this.dataset.rating);
                        const exercise = workoutData.exercises[workoutData.currentExercise];
                        
                        if (exercise?.currentSetData?.length > 0) {
                            const lastSetIndex = exercise.currentSetData.length - 1;
                            exercise.currentSetData[lastSetIndex].rating = rating;
                        }
                    });
                });
                
                document.getElementById('save-workout-btn').addEventListener('click', function() {
                    saveWorkoutData();
                });
                
                document.getElementById('discard-workout-btn').addEventListener('click', function() {
                    if (confirm('Are you sure you want to discard this workout?')) {
                        resetWorkout();
                        showStep(1);
                    }
                });
                
                document.getElementById('back-to-rest').addEventListener('click', function() {
                    if (workoutData.currentExercise >= workoutData.exercises.length) {
                        workoutData.currentExercise = 0;
                        workoutData.currentSet = 0;
                    }
                    showStep(3);
                });
                
                window.summaryListenersAdded = true;
            }
        }

            function saveWorkoutData() {
                try {
                    const notesElement = document.querySelector('#step4.step-active #workout-notes');
                    if (!notesElement) {
                        console.error('Notes textarea element not found!');
                        return;
                    }
                    
                    const notes = notesElement.value.trim();
                    console.log('SAVING NOTES:', notes);
    
                    let totalVolume = workoutData.exercises.reduce((total, exercise) => {
                        return total + exercise.currentSetData.reduce((exTotal, set) => {
                            return exTotal + (set.weight * set.reps);
                        }, 0);
                    }, 0);

                    const workoutToSave = {
                        title: workoutData.title || 'Quick Workout',
                        type: 'strength',
                        duration_minutes: workoutData.duration_minutes,
                        calories_burned: Math.round(workoutData.caloriesBurned) || 0,
                        template_id: workoutData.templateId || 0,
                        notes: notes,
                        rating: workoutData.overallRating || 3,
                        total_volume: totalVolume,
                        exercises: []
                    };
                    
                    console.log('Workout data being prepared:', workoutToSave);
                    
                    workoutData.exercises.forEach((ex, exIndex) => {
                        if (ex.currentSetData && ex.currentSetData.length > 0) {
                            console.log(`Processing exercise ${exIndex+1}: ${ex.name} with ${ex.currentSetData.length} sets`);
                            
                            const sets = ex.currentSetData.map((set, setIndex) => {
                                const weight = Number(set.weight || 0);
                                const reps = Number(set.reps || 0);
                                const rating = Number(set.rating || 3);
                                
                                console.log(`Set ${setIndex+1}: weight=${weight}, reps=${reps}, rating=${rating}`);
                                
                                return {
                                    weight: weight,
                                    reps: reps,
                                    rpe: rating
                                };
                            });
                            
                            workoutToSave.exercises.push({
                                name: ex.name || 'Unknown Exercise',
                                sets: sets
                            });
                        }
                    });
                    
                    const formData = new FormData();
                    formData.append('save_workout', '1');
                
                    const jsonString = JSON.stringify(workoutToSave, function replacer(key, value) {
                        if (value === undefined) return null;
                        if (typeof value === 'number' && !isFinite(value)) return 0;
                        return value;
                    });
                    
                    console.log('Saving workout data:', jsonString);
                    formData.append('workout_data', jsonString);
                    
                    fetch('save_workout.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Workout saved! Great job!');
                            resetWorkout();
                            showStep(1);
                        } else {
                            console.error('Error saving workout:', data.message);
                            alert('There was an issue saving your workout data: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error saving workout: ' + error.message);
                    });
                } catch (e) {
                    console.error('Error preparing workout data:', e);
                    alert('There was an error preparing your workout data: ' + e.message);
                }
            }
            
            function getSelectedRating() {
                const selectedRating = document.querySelector('#step4 .rating-option.selected');
                return selectedRating ? parseInt(selectedRating.dataset.rating) : 3;
            }
            
            function resetWorkout() {
                if (workoutData.workoutTimerRAF) {
                    cancelAnimationFrame(workoutData.workoutTimerRAF);
                    workoutData.workoutTimerRAF = null;
                }
                
                if (workoutData.restTimerInterval) {
                    clearInterval(workoutData.restTimerInterval);
                }
                
                if (timerRAF) {
                    cancelAnimationFrame(timerRAF);
                }
                
                workoutData = {
                    title: '',
                    templateId: null,
                    exercises: [],
                    currentExercise: 0,
                    currentSet: 0,
                    startTime: null,
                    duration_minutes: 0,
                    caloriesBurned: 0,
                    totalSets: 0,
                    completedSets: 0,
                    totalVolume: 0,
                    avgIntensity: 0,
                    restTime: 90,
                    currentRestTime: 0,
                    restTimerInterval: null,
                    workoutTimerRAF: null
                };
                
                workoutTitle.textContent = 'Workout';
                document.querySelectorAll('#workout-timer, #rest-workout-timer').forEach(el => {
                    if (el) el.textContent = '00:00';
                });
                caloriesBurned.textContent = '0 kcal';
                workoutProgressText.textContent = '0/0 Exercises';
                exerciseName.textContent = 'Exercise';
                setCounter.textContent = 'Set 0/0';
                previousSetInfo.parentElement.style.display = 'none';
                weightInput.value = '0';
                repsInput.value = '0';
                weightHint.textContent = '';
                repsHint.textContent = '';
                
                document.getElementById('summary-time').textContent = '00:00';
                document.getElementById('summary-sets').textContent = '0';
                document.getElementById('summary-weight').textContent = '0 kg';
                document.getElementById('workout-notes').value = '';
                
                const ratingOptions = document.querySelectorAll('.rating-option');
                ratingOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.dataset.rating === '3') {
                        option.classList.add('selected');
                    }
                });
            }
            
            showStep(1);
        });
    </script>
</body>
</html>