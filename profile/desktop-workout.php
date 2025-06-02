<?php
require_once 'profile_access_control.php';
require_once 'languages.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/active-workout.php");
    exit;
}

require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_workout"])) {
    header('Content-Type: application/json');
    
    try {
        $workoutData = json_decode($_POST["workout_data"], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid workout data format");
        }
        
        if (empty($workoutData["exercises"])) {
            throw new Exception("No exercises recorded. Please add at least one exercise to save your workout.");
        } 
        
        $workout_id = saveWorkoutToDatabase($conn, $user_id, $workoutData);
        
        echo json_encode([
            'success' => true,
            'workout_id' => $workout_id,
            'message' => 'Workout saved successfully!'
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

$workout_saved = false;
$workout_message = "";
$workout_id = 0;

try {
    if (tableExists($conn, 'workout_exercises')) {
        $history_query = "SELECT we.exercise_name, COUNT(*) as count
                        FROM workout_exercises we
                        JOIN workouts w ON we.workout_id = w.id
                            WHERE w.user_id = ? 
                        GROUP BY we.exercise_name
                        ORDER BY count DESC
                        LIMIT 10";
        $stmt = mysqli_prepare($conn, $history_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare history query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $exercise_history = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $exercise_history[] = $row;
        }
    } else {
        $exercise_history = false;
    }
} catch (Exception $e) {
    error_log("Error fetching exercise history: " . $e->getMessage());
    $exercise_history = false;
}

try {
    if (tableExists($conn, 'exercises')) {
        $common_exercises_query = "SELECT name as exercise_name, 
                              primary_muscle as muscle_group, 
                              equipment as equipment_needed 
                              FROM exercises
                              ORDER BY id DESC 
                              LIMIT 10";
        $common_exercises = mysqli_query($conn, $common_exercises_query);
        if ($common_exercises === false) {
            throw new Exception("Failed to fetch common exercises: " . mysqli_error($conn));
        }
    } else {
        $common_exercises = false;
    }
} catch (Exception $e) {
    error_log("Error fetching common exercises: " . $e->getMessage());
    $common_exercises = false;
}

$favorite_exercises = false;

function calculateWorkoutIntensity($exercises) {
    if (empty($exercises)) {
        return 1.0;
    }
    
    $total_volume = 0;
    $exercise_count = count($exercises);
    
    foreach ($exercises as $exercise) {
        $sets = isset($exercise["sets"]) ? $exercise["sets"] : 0;
        $reps = isset($exercise["reps"]) ? $exercise["reps"] : 0;
        $weight = isset($exercise["weight"]) ? $exercise["weight"] : 0;
        
        $volume = $sets * $reps * ($weight > 0 ? $weight : 0.5);
        $total_volume += $volume;
    }
    
    $avg_volume = $total_volume / $exercise_count;
    
    if ($avg_volume < 50) {
        return 1.0;
    } else if ($avg_volume < 150) {
        return 2.0;
    } else if ($avg_volume < 300) {
        return 3.0;
    } else if ($avg_volume < 500) {
        return 4.0;
    } else {
        return 5.0;
    }
}

function getAverageRPE($workoutData) {
    $allRPE = [];
    
    if (!isset($workoutData['exercises']) || !is_array($workoutData['exercises'])) {
        return 5;
    }
    
    foreach ($workoutData['exercises'] as $exercise) {
        if (isset($exercise['sets']) && is_array($exercise['sets'])) {
            foreach ($exercise['sets'] as $set) {
                if (isset($set['rpe'])) {
                    $allRPE[] = $set['rpe'];
                }
            }
        }
    }
    
    return empty($allRPE) ? 5 : array_sum($allRPE) / count($allRPE);
}

try {
    if (tableExists($conn, 'workout_templates')) {
        $templates_query = "SELECT wt.*, COUNT(wte.id) as exercise_count 
                          FROM workout_templates wt
                          LEFT JOIN workout_template_exercises wte ON wt.id = wte.workout_template_id
                          WHERE wt.user_id = ?
                          GROUP BY wt.id
                          ORDER BY wt.created_at DESC";
        $stmt = mysqli_prepare($conn, $templates_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare templates query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $templates = mysqli_stmt_get_result($stmt);
    } else {
        $templates = false;
    }
} catch (Exception $e) {
    error_log("Error fetching workout templates: " . $e->getMessage());
    $templates = false;
}

try {
    if (tableExists($conn, 'workout_templates')) {
        $all_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $all_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $all_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $strength_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                         WHERE user_id = ? AND 
                                         (category = 'Strength Training' OR
                                         LOWER(name) LIKE '%strength%' OR 
                                         LOWER(description) LIKE '%strength%')";
        $stmt = mysqli_prepare($conn, $strength_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $strength_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $hiit_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                     WHERE user_id = ? AND 
                                     (category = 'hiit' OR
                                     LOWER(name) LIKE '%hiit%' OR 
                                     LOWER(description) LIKE '%hiit%' OR
                                     LOWER(name) LIKE '%interval%' OR
                                     LOWER(description) LIKE '%interval%')";
        $stmt = mysqli_prepare($conn, $hiit_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hiit_templates_count = mysqli_fetch_assoc($result)['count'];
        
        $cardio_templates_count_query = "SELECT COUNT(*) as count FROM workout_templates 
                                       WHERE user_id = ? AND 
                                       (category = 'cardio' OR
                                       LOWER(name) LIKE '%cardio%' OR 
                                       LOWER(description) LIKE '%cardio%')";
        $stmt = mysqli_prepare($conn, $cardio_templates_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cardio_templates_count = mysqli_fetch_assoc($result)['count'];
    } else {
        $all_templates_count = 0;
        $strength_templates_count = 0;
        $hiit_templates_count = 0;
        $cardio_templates_count = 0;
    }
} catch (Exception $e) {
    error_log("Error counting templates by category: " . $e->getMessage());
    $all_templates_count = 0;
    $strength_templates_count = 0;
    $hiit_templates_count = 0;
    $cardio_templates_count = 0;
}

function getGlobalWorkoutTemplates($conn) {
    try {
        $check_table_query = "SHOW TABLES LIKE 'workout_templates'";
        $table_result = mysqli_query($conn, $check_table_query);
        if (mysqli_num_rows($table_result) == 0) {
            error_log("workout_templates table does not exist");
            return [];
        }
         
        $query = "SELECT wt.id, wt.name, wt.description, wt.difficulty, wt.estimated_time, 
                   wt.category, wt.created_at, wt.updated_at, u.username as creator,
                   (SELECT COUNT(*) FROM workout_template_exercises WHERE workout_template_id = wt.id) as exercise_count
                   FROM workout_templates wt
                   JOIN users u ON wt.user_id = u.id
                   WHERE (
                     EXISTS (
                       SELECT 1 FROM user_roles ur 
                       JOIN roles r ON ur.role_id = r.id 
                       WHERE ur.user_id = wt.user_id AND (r.name = 'admin' OR r.id = 5)
                     )
                   )
                   ORDER BY wt.updated_at DESC";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            error_log("Failed to execute query: " . mysqli_error($conn));
            return [];
        }
        
        $templates = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $templates[] = $row;
        }
        
        error_log("Found " . count($templates) . " global templates from admins");
        return $templates;
    } catch (Exception $e) {
        error_log("Error in getGlobalWorkoutTemplates: " . $e->getMessage());
        return [];
    }
}

$global_templates = false;
try {
    $global_templates = getGlobalWorkoutTemplates($conn);
    error_log("Retrieved " . count($global_templates) . " global templates");
} catch (Exception $e) {
    error_log("Error fetching global templates: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION["language"] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?php echo t('start_workout'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="desktop-workout.css">
    <link rel="stylesheet" href="profile-desktop-workout.css">
</head>
<body>
    <div class="dashboard">
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo t('start_workout'); ?></h1>
            </div>
           
            <div class="step-content active" id="step1-content">
                <div class="workout-layout">
                    <div class="categories-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-th-large"></i> <?php echo t('categories'); ?>
                            </div>
                        </div>
                        <div class="panel-content">
                            <div class="category-list">
                                <div class="category-item active" data-category="all">
                                    <div class="category-name">
                                        <i class="fas fa-layer-group"></i> <?php echo t('all_templates'); ?>
                                    </div>
                                    <div class="category-count"><?php echo $all_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="Strength Training">
                                    <div class="category-name">
                                        <i class="fas fa-dumbbell"></i> <?php echo t('strength_training'); ?>
                                    </div>
                                    <div class="category-count"><?php echo $strength_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="hiit">
                                    <div class="category-name">
                                        <i class="fas fa-bolt"></i> <?php echo t('hiit'); ?>
                                    </div>
                                    <div class="category-count"><?php echo $hiit_templates_count; ?></div>
                                </div>
                                <div class="category-item" data-category="cardio">
                                    <div class="category-name">
                                        <i class="fas fa-heartbeat"></i> <?php echo t('cardio'); ?>
                                    </div>
                                    <div class="category-count"><?php echo $cardio_templates_count; ?></div>
                                </div>
                            </div>
                            
                            <div class="filters-section">
                                <h3 class="panel-title pdw-panel-title">
                                    <i class="fas fa-globe"></i> <?php echo t('global_templates'); ?>
                                </h3>
                                <div class="pdw-toggle-container">
                                    <label class="pdw-toggle-switch">
                                        <input type="checkbox" id="globalTemplatesToggle">
                                        <span class="pdw-toggle-slider"></span>
                                    </label>
                                    <span class="pdw-toggle-label"><?php echo t('show_global_templates'); ?></span>
                                </div>
                                <p class="pdw-toggle-description"><?php echo t('toggle_global_description'); ?></p>
                                
                                <div class="filter-group">
                                    <label class="filter-label"><?php echo t('duration'); ?></label>
                                    <select class="filter-select" id="durationFilter">
                                        <option value="any"><?php echo t('any_duration'); ?></option>
                                        <option value="short"><?php echo t('short'); ?> (< 30 min)</option>
                                        <option value="medium"><?php echo t('medium'); ?> (30-60 min)</option>
                                        <option value="long"><?php echo t('long'); ?> (> 60 min)</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label"><?php echo t('difficulty'); ?></label>
                                    <select class="filter-select" id="difficultyFilter">
                                        <option value="any"><?php echo t('all_levels'); ?></option>
                                        <option value="beginner"><?php echo t('beginner'); ?></option>
                                        <option value="intermediate"><?php echo t('intermediate'); ?></option>
                                        <option value="advanced"><?php echo t('advanced'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
            
                    <div class="templates-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-clipboard-list"></i> <?php echo t('available_templates'); ?>
                            </div>
                            <div class="panel-actions">
                                <button class="view-toggle-btn" id="gridViewBtn">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="view-toggle-btn active" id="listViewBtn">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                        <div class="panel-content">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" id="templateSearch" placeholder="<?php echo t('search_templates'); ?>">
                            </div>
                            
                            <?php if ($templates && mysqli_num_rows($templates) > 0): ?>
                                <div class="template-grid" style="display: none;">
                                    <?php 
                                    mysqli_data_seek($templates, 0);
                                    while ($template = mysqli_fetch_assoc($templates)): 
                                        $categoryClasses = 'template-all';
                                        $difficultyClass = strtolower($template['difficulty']);
                                        $durationClass = '';
                                        
                                        if ($template['estimated_time'] < 30) {
                                            $durationClass = 'duration-short';
                                        } elseif ($template['estimated_time'] < 60) {
                                            $durationClass = 'duration-medium';
                                        } else {
                                            $durationClass = 'duration-long';
                                        }

                                        if (
                                            stripos($template['name'], 'strength') !== false || 
                                            stripos($template['description'], 'strength') !== false
                                        ) {
                                            $categoryClasses .= ' template-strength';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'hiit') !== false || 
                                            stripos($template['description'], 'hiit') !== false ||
                                            stripos($template['name'], 'interval') !== false || 
                                            stripos($template['description'], 'interval') !== false
                                        ) {
                                            $categoryClasses .= ' template-hiit';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'cardio') !== false || 
                                            stripos($template['description'], 'cardio') !== false
                                        ) {
                                            $categoryClasses .= ' template-cardio';
                                        }
                                    ?>
                                    <div class="template-card <?php echo $categoryClasses . ' ' . $difficultyClass . ' ' . $durationClass; ?>" 
                                         data-id="<?php echo $template['id']; ?>"
                                         data-category="<?php echo htmlspecialchars($template['category'] ?? ''); ?>">
                                        <div class="template-card-header">
                                            <div class="template-card-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                            <div class="template-card-meta">
                                                <div class="template-card-meta-item">
                                                    <i class="fas fa-stopwatch"></i>
                                                    <span><?php echo $template['estimated_time']; ?> min</span>
                                                </div>
                                                <div class="template-card-meta-item">
                                                    <i class="fas fa-dumbbell"></i>
                                                    <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="template-card-body">
                                            <?php if (!empty($template['description'])): ?>
                                                <div class="template-card-description"><?php echo htmlspecialchars(substr($template['description'], 0, 50)) . (strlen($template['description']) > 50 ? '...' : ''); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="template-card-footer">
                                            <div class="template-card-action">
                                                <i class="fas fa-chevron-right"></i> <?php echo t('select'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                        
                                <div class="template-list">
                                    <?php 
                                    mysqli_data_seek($templates, 0);
                                    while ($template = mysqli_fetch_assoc($templates)): 
                                        $categoryClasses = 'template-all';
                                        $difficultyClass = strtolower($template['difficulty']);
                                        $durationClass = '';
                                        
                                        if ($template['estimated_time'] < 30) {
                                            $durationClass = 'duration-short';
                                        } elseif ($template['estimated_time'] < 60) {
                                            $durationClass = 'duration-medium';
                                        } else {
                                            $durationClass = 'duration-long';
                                        }

                                        if (
                                            stripos($template['name'], 'strength') !== false || 
                                            stripos($template['description'], 'strength') !== false
                                        ) {
                                            $categoryClasses .= ' template-strength';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'hiit') !== false || 
                                            stripos($template['description'], 'hiit') !== false ||
                                            stripos($template['name'], 'interval') !== false || 
                                            stripos($template['description'], 'interval') !== false
                                        ) {
                                            $categoryClasses .= ' template-hiit';
                                        }
                                        
                                        if (
                                            stripos($template['name'], 'cardio') !== false || 
                                            stripos($template['description'], 'cardio') !== false
                                        ) {
                                            $categoryClasses .= ' template-cardio';
                                        }
                                    ?>
                                    <div class="template-list-item <?php echo $categoryClasses . ' ' . $difficultyClass . ' ' . $durationClass; ?>" 
                                         data-id="<?php echo $template['id']; ?>"
                                         data-category="<?php echo htmlspecialchars($template['category'] ?? ''); ?>">
                                        <div class="template-list-item-header">
                                            <div class="template-list-item-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                            <div class="template-list-item-difficulty"><?php echo ucfirst($template['difficulty'] ?: 'Beginner'); ?></div>
                                        </div>
                                        <div class="template-list-item-meta">
                                            <div class="template-list-item-meta-item">
                                                <i class="fas fa-stopwatch"></i>
                                                <span><?php echo $template['estimated_time']; ?> min</span>
                                            </div>
                                            <div class="template-list-item-meta-item">
                                                <i class="fas fa-dumbbell"></i>
                                                <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                            </div>
                                        </div>
                                        <?php if (!empty($template['description'])): ?>
                                            <div class="template-list-item-description"><?php echo htmlspecialchars(substr($template['description'], 0, 80)) . (strlen($template['description']) > 80 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-message" id="noTemplatesMessage">
                                    <i class="fas fa-clipboard"></i>
                                    <p><?php echo t('no_templates_found'); ?></p>
                                    <a href="workout-templates.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> <?php echo t('create_template'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="selected-panel">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fas fa-clipboard-check"></i> <?php echo t('selected_template'); ?>
                            </div>
                        </div>
                        <div id="selectedTemplateContainer">
                            <div class="selected-template-placeholder">
                                <i class="fas fa-hand-pointer"></i>
                                <h3><?php echo t('choose_template_to_view'); ?></h3>
                                <p><?php echo t('select_template_details'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-content" id="step2-content">
                <div class="workout-header">
                    <h1 class="workout-title" id="workout-title"><?php echo t('loading_workout'); ?></h1>
                    <div class="workout-progress">
                        <div class="timer-container">
                            <i class="fas fa-clock"></i>
                            <span id="workout-timer">00:00:00</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-percentage">0% <?php echo t('complete'); ?></div>
                    </div>
                </div>

                <div class="workout-tracking-layout">
                    <div class="overview-panel">
                        <div class="panel-section">
                            <h2 class="panel-title"><?php echo t('workout_overview'); ?></h2>
                            <div class="exercise-list" id="exercise-list">
                            </div>
                            <div class="pdw-exercise-reorder-controls">
                                <p class="pdw-reorder-instructions"><?php echo t('drag_exercises_reorder'); ?></p>
                                <button class="btn btn-sm" id="move-exercise-up-btn" disabled>
                                    <i class="fas fa-arrow-up"></i> <?php echo t('move_up'); ?>
                                </button>
                                <button class="btn btn-sm" id="move-exercise-down-btn" disabled>
                                    <i class="fas fa-arrow-down"></i> <?php echo t('move_down'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="current-exercise-panel">
                        <div id="current-exercise-container">
                            <h2 class="exercise-title" id="current-exercise-name"><?php echo t('loading_exercise'); ?></h2>
                            <p class="exercise-target" id="exercise-target"><?php echo t('target'); ?>: -</p>
                            
                            <div class="current-set-section">
                                <h3 class="section-title"><?php echo t('current_set'); ?></h3>
                                <div class="input-row">
                                    <div class="input-group">
                                        <label for="weight-input"><?php echo t('weight'); ?> (kg)</label>
                                        <input type="number" id="weight-input" class="exercise-input" value="0">
                                    </div>
                                    <div class="input-group">
                                        <label for="reps-input"><?php echo t('reps'); ?></label>
                                        <input type="number" id="reps-input" class="exercise-input" value="0">
                                    </div>
                                </div>
                                
                                <button id="complete-set-btn" class="complete-set-btn"><?php echo t('complete_set'); ?></button>
                            </div>
                            
                            <div class="previous-sets-section">
                                <h3 class="section-title"><?php echo t('previous_sets'); ?></h3>
                                <table class="sets-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('set'); ?></th>
                                            <th><?php echo t('weight'); ?></th>
                                            <th><?php echo t('reps'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="previous-sets-table">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="rest-screen" class="rest-screen" style="display: none;">
                            <div class="rest-message">
                                <h2><?php echo t('rest_time'); ?></h2>
                                <p><?php echo t('how_was_set'); ?></p>
                            </div>
                            
                            <div class="rpe-selection">
                                <button class="rpe-button" data-rpe="1">😊</button>
                                <button class="rpe-button" data-rpe="2">🙂</button>
                                <button class="rpe-button" data-rpe="3">😐</button>
                                <button class="rpe-button" data-rpe="4">🥵</button>
                                <button class="rpe-button" data-rpe="5">💀</button>
                            </div>
                            
                            <div class="rest-timer-display" id="rest-timer-display">00:00</div>
                            
                            <div class="pdw-rest-timer-controls">
                                <button class="pdw-timer-adjust-btn" id="decrease-rest-btn">
                                    <i class="fas fa-minus"></i> 15s
                                </button>
                                <button class="pdw-timer-adjust-btn" id="increase-rest-btn">
                                    <i class="fas fa-plus"></i> 15s
                                </button>
                            </div>
                            
                            <div class="next-exercise-preview">
                                <h3><?php echo t('next_up'); ?></h3>
                                <div id="rest-next-exercise"></div>
                            </div>
                            
                            <div class="rest-controls">
                                <button id="skip-rest-btn" class="skip-rest-btn"><?php echo t('skip_rest'); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="next-exercise-panel">
                        <div class="panel-section">
                            <h2 class="panel-title"><?php echo t('next_exercise'); ?></h2>
                            <div class="next-exercise-card" id="next-exercise-card">
                            </div>
                        </div>
                        
                        <div class="panel-section">
                            <h2 class="panel-title"><?php echo t('workout_stats'); ?></h2>
                            <div class="pdw-stats-grid">
                                <div class="pdw-stat-row">
                                    <div class="pdw-stat-label"><?php echo t('sets_completed'); ?></div>
                                    <div class="pdw-stat-value" id="stats-sets-completed">0/0</div>
                                </div>
                                <div class="pdw-stat-row">
                                    <div class="pdw-stat-label"><?php echo t('volume'); ?></div>
                                    <div class="pdw-stat-value" id="stats-volume">0 kg</div>
                                </div>
                                <div class="pdw-stat-row">
                                    <div class="pdw-stat-label"><?php echo t('elapsed_time'); ?></div>
                                    <div class="pdw-stat-value" id="stats-elapsed-time">00:00:00</div>
                                </div>
                                <div class="pdw-stat-row">
                                    <div class="pdw-stat-label"><?php echo t('calories_burned'); ?></div>
                                    <div class="pdw-stat-value" id="stats-calories-burned">0 kcal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="workout-footer">
                    <button id="end-workout-btn" class="footer-btn danger"><i class="fas fa-flag-checkered"></i> <?php echo t('end_workout'); ?></button>
                </div>
            </div>
            
            <div id="step3-content" class="step-content">
                <div class="workout-complete-header">
                    <h1 class="workout-complete-title"><?php echo t('workout_complete'); ?></h1>
                    <div class="workout-complete-date" id="workout-complete-date"><?php echo t('loading'); ?>...</div>
                </div>

                <div class="workout-summary-grid">
                    <div class="summary-stat-card">
                        <div class="summary-stat-label"><?php echo t('total_volume'); ?></div>
                        <div class="summary-stat-value" id="summary-volume">0 kg</div>
                        <div class="summary-stat-comparison positive" id="volume-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label"><?php echo t('total_sets'); ?></div>
                        <div class="summary-stat-value" id="summary-sets">0</div>
                        <div class="summary-stat-comparison neutral" id="sets-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label"><?php echo t('peak_weight'); ?></div>
                        <div class="summary-stat-value" id="summary-peak-weight">0 kg</div>
                        <div class="summary-stat-comparison positive" id="peak-weight-comparison">-</div>
                    </div>
                    <div class="summary-stat-card">
                        <div class="summary-stat-label"><?php echo t('total_time'); ?></div>
                        <div class="summary-stat-value" id="summary-rest-time">00:00:00</div>
                        <div class="summary-stat-comparison positive" id="rest-time-comparison">-</div>
                    </div>
                </div>

                <div class="exercise-breakdown">
                    <h3 class="chart-title"><?php echo t('exercise_breakdown'); ?></h3>
                    <div class="exercise-breakdown-list" id="exercise-breakdown-list">
                    </div>
                </div>

                <div class="workout-notes-container">
                    <h3 class="chart-title"><?php echo t('workout_notes'); ?></h3>
                    <textarea class="workout-notes" id="summary-workout-notes" placeholder="<?php echo t('add_workout_thoughts'); ?>"></textarea>
                </div>

                <div class="summary-actions">
                    <button class="save-workout-btn" id="final-save-workout-btn">
                        <i class="fas fa-save"></i> <?php echo t('save_workout'); ?>
                    </button>
                    <a href="workout.php">
                        <button class="save-template-btn">
                            <i class="fas fa-bookmark"></i> <?php echo t('dont_save'); ?>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['active_template_id']) && isset($_SESSION['start_workout_directly']) && $_SESSION['start_workout_directly']): ?>
            console.log("Auto-start detected!");
            const templateId = <?= $_SESSION['active_template_id'] ?>;
            console.log("Template ID:", templateId);
            
            <?php if (isset($_SESSION['skip_template_selection']) && $_SESSION['skip_template_selection']): ?>
                console.log("Skip template selection enabled - starting workout immediately");
                
                fetch(`get_template.php?id=${templateId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Template data loaded successfully, starting workout");
                            workoutState = {
                                templateId: templateId,
                                templateName: data.template.name,
                                exercises: data.exercises.map(ex => ({
                                    ...ex,
                                    completedSets: 0,
                                    sets: Array(parseInt(ex.sets)).fill().map(() => ({
                                        weight: 0,
                                        reps: 0,
                                        rpe: null,
                                        completed: false
                                    }))
                                })),
                                currentExerciseIndex: 0,
                                currentSet: 1,
                                startTime: Date.now(),
                                endTime: null,
                                timerInterval: null,
                                restTimerInterval: null,
                                restTime: parseInt(data.template.rest_time) || 90,
                                totalVolume: 0,
                                totalSets: data.exercises.reduce((acc, ex) => acc + parseInt(ex.sets), 0),
                                completedSets: 0,
                                notes: '',
                                peakWeight: 0,
                                caloriesBurned: 0
                            };
                            
                            workoutState.timerInterval = setInterval(updateWorkoutTimer, 1000);
                            
                            document.getElementById('workout-title').textContent = data.template.name;
                            
                            initializeWorkoutTracking();
                            updateWorkoutStats();
                            
                            goToStep(2);
                        } else {
                            console.error("Auto-start error: Template data could not be loaded", data);
                        }
                    })
                    .catch(error => {
                        console.error("Error auto-starting workout:", error);
                        showNotification("Error auto-starting workout. Please try again.", "error");
                    });
            <?php else: ?>
            console.log("Normal auto-start - selecting template and clicking start");
            const templateElements = document.querySelectorAll('[data-id="' + templateId + '"]');
            console.log("Found template elements:", templateElements.length);
            if (templateElements.length > 0) {
                templateElements[0].click();
                console.log("Template selected");
                
                setTimeout(() => {
                    const startBtn = document.querySelector('.begin-workout-btn');
                    console.log("Start button found:", !!startBtn);
                    if (startBtn) {
                        startBtn.click();
                        console.log("Start button clicked");
                    }
                }, 500);
            }
            <?php endif; ?>
            
            <?php 
                unset($_SESSION['start_workout_directly']);
                unset($_SESSION['skip_template_selection']);
            ?>
            <?php endif; ?>

            let workoutState = {
                templateId: null,
                templateName: '',
                exercises: [],
                currentExerciseIndex: 0,
                currentSet: 1,
                startTime: null,
                endTime: null,
                timerInterval: null,
                restTimerInterval: null,
                restTime: 90,
                totalVolume: 0,
                totalSets: 0,
                completedSets: 0,
                notes: '',
                peakWeight: 0
            };
            

            const gridViewBtn = document.getElementById('gridViewBtn');
            const listViewBtn = document.getElementById('listViewBtn');
            const templateGrid = document.querySelector('.template-grid');
            const templateList = document.querySelector('.template-list');
            const skipRestBtn = document.getElementById('skip-rest-btn');


            if (gridViewBtn && listViewBtn) {
                gridViewBtn.addEventListener('click', function() {
                    gridViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    templateGrid.style.display = 'grid';
                    templateList.style.display = 'none';
                    updateCategoryCounts(); 
                });

                listViewBtn.addEventListener('click', function() {
                    listViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    templateList.style.display = 'block';
                    templateGrid.style.display = 'none';
                    updateCategoryCounts(); 
                });
            }

            const categoryItems = document.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                item.addEventListener('click', function() {
                    categoryItems.forEach(cat => cat.classList.remove('active'));
                    this.classList.add('active');

                    const category = this.dataset.category;
                    filterTemplates();
                });
            });

            const durationFilter = document.getElementById('durationFilter');
            const difficultyFilter = document.getElementById('difficultyFilter');

            if (durationFilter) {
                durationFilter.addEventListener('change', filterTemplates);
            }

            if (difficultyFilter) {
                difficultyFilter.addEventListener('change', filterTemplates);
            }

            const templateSearch = document.getElementById('templateSearch');
            if (templateSearch) {
                templateSearch.addEventListener('input', filterTemplates);
            }

            const templateCards = document.querySelectorAll('.template-card');
            const templateListItems = document.querySelectorAll('.template-list-item');
            const selectedTemplateContainer = document.getElementById('selectedTemplateContainer');

            [...templateCards, ...templateListItems].forEach(template => {
                template.addEventListener('click', function() {
                    const templateId = this.dataset.id;
                    loadTemplateDetails(templateId);
                });
            });

            function filterTemplates() {
                const activeCategory = document.querySelector('.category-item.active')?.dataset.category || 'all';
                const selectedDuration = durationFilter?.value || 'any';
                const selectedDifficulty = difficultyFilter?.value || 'any';
                const searchQuery = templateSearch?.value?.toLowerCase() || '';
                const showGlobal = globalTemplatesToggle?.checked || false;

                console.log("Filtering templates with parameters:", {
                    activeCategory,
                    selectedDuration,
                    selectedDifficulty,
                    searchQuery,
                    showGlobal
                });

                const allTemplates = [
                    ...document.querySelectorAll('.template-card'), 
                    ...document.querySelectorAll('.template-list-item')
                ];
                
                console.log("Filtering templates:", allTemplates.length, "templates found");

                allTemplates.forEach(template => {
                    if (!template) return;
                    
                    let showTemplate = true;
                    
                    const isGlobalTemplate = template.classList.contains('pdw-template-global');
                    if (isGlobalTemplate && !showGlobal) {
                        template.style.display = 'none';
                        return;
                    }

                    if (activeCategory !== 'all') {
                        const templateCategory = template.getAttribute('data-category');
                        if (templateCategory && templateCategory === activeCategory) {  
                        } 
                        else if (activeCategory === 'Strength Training' && template.classList.contains('template-strength')) {
                        } 
                        else if (activeCategory === 'hiit' && template.classList.contains('template-hiit')) {
                        }
                        else if (activeCategory === 'cardio' && template.classList.contains('template-cardio')) {
                        }
                        else {
                            showTemplate = false;
                        }
                    }

                    if (selectedDuration !== 'any') {
                        if (selectedDuration === 'short' && !template.classList.contains('duration-short')) {
                            showTemplate = false;
                        } else if (selectedDuration === 'medium' && !template.classList.contains('duration-medium')) {
                            showTemplate = false;
                        } else if (selectedDuration === 'long' && !template.classList.contains('duration-long')) {
                            showTemplate = false;
                        }
                    }

                    if (selectedDifficulty !== 'any' && !template.classList.contains(selectedDifficulty)) {
                        showTemplate = false;
                    }

                    if (searchQuery) {
                        const templateName = template.querySelector('.template-card-title, .template-list-item-title')?.textContent.toLowerCase() || '';
                        const templateDescription = template.querySelector('.template-card-description, .template-list-item-description')?.textContent.toLowerCase() || '';
                        
                        if (!templateName.includes(searchQuery) && !templateDescription.includes(searchQuery)) {
                            showTemplate = false;
                        }
                    }

                    template.style.display = showTemplate ? '' : 'none';
                });
                
                updateCategoryCounts();
            }

            function loadTemplateDetails(templateId) {
                [...templateCards, ...templateListItems].forEach(template => {
                    template.classList.remove('selected');
                    if (template.dataset.id === templateId) {
                        template.classList.add('selected');
                    }
                });

                selectedTemplateContainer.innerHTML = '<div class="selected-template-placeholder"><i class="fas fa-spinner fa-spin"></i><h3>Loading template...</h3></div>';

                fetch(`get_template.php?id=${templateId}`)
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 500) {
                                console.error('Server error when loading template. Status:', response.status);
                                return response.json().then(errorData => {
                                    throw new Error(errorData.error || `Server error: ${response.status}`);
                                });
                            }
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            renderSelectedTemplate(data.template, data.exercises, data.is_global);
                            showNotification(`Template "${data.template.name}" loaded successfully!`, 'success');
                        } else {
                            throw new Error(data.error || 'Failed to load template');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading template:', error);
                        selectedTemplateContainer.innerHTML = `
                            <div class="selected-template-placeholder">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error loading template</h3>
                                <p>${error.message}</p>
                            </div>
                        `;
                        showNotification(`Error: ${error.message}`, 'error');
                    });
            }

            function renderSelectedTemplate(template, exercises, isGlobal = false) {
                const difficultyLabel = template.difficulty ? template.difficulty.charAt(0).toUpperCase() + template.difficulty.slice(1) : 'Beginner';
                
                let exercisesHtml = '';
                exercises.forEach(exercise => {
                    exercisesHtml += `
                        <div class="selected-template-exercise">
                            <div class="selected-template-exercise-name">
                                <i class="fas fa-dumbbell"></i>
                                ${exercise.exercise_name}
                            </div>
                            <div class="selected-template-exercise-details">
                                ${exercise.sets} sets • ${exercise.rest_time}s rest
                            </div>
                        </div>
                    `;
                });

                const creatorInfo = isGlobal && template.creator ? 
                    `<div class="selected-template-meta-item pdw-global-badge">
                        <i class="fas fa-user-shield"></i>
                        <span>By ${template.creator}</span>
                    </div>` : '';

                const html = `
                    <div class="selected-template ${isGlobal ? 'pdw-global-template' : ''}">
                        <div class="selected-template-header">
                            <h2 class="selected-template-title">${template.name}</h2>
                            <div class="selected-template-meta">
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-stopwatch"></i>
                                    <span>${template.estimated_time} min</span>
                                </div>
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>${exercises.length} exercises</span>
                                </div>
                                <div class="selected-template-meta-item">
                                    <i class="fas fa-bolt"></i>
                                    <span>${difficultyLabel}</span>
                                </div>
                                ${creatorInfo}
                            </div>
                        </div>
                        <div class="selected-template-body">
                            ${template.description ? `<div class="selected-template-description">${template.description}</div>` : ''}
                            <h3 style="margin-bottom: 15px; font-size: 1.1rem;"><i class="fas fa-list-ul"></i> Exercises</h3>
                            <div class="selected-template-exercises">
                                ${exercisesHtml}
                            </div>
                        </div>
                        <div class="selected-template-footer">
                            <button class="begin-workout-btn" data-template-id="${template.id}">
                                <i class="fas fa-play"></i> <?php echo t('begin_workout'); ?>
                            </button>
                            ${!isGlobal ? `
                            <button class="modify-template-btn" data-template-id="${template.id}">
                                <i class="fas fa-edit"></i> <?php echo t('modify_template'); ?>
                            </button>
                            ` : `
                            `}
                        </div>
                    </div>
                `;
                
                selectedTemplateContainer.innerHTML = html;

                const beginWorkoutBtn = selectedTemplateContainer.querySelector('.begin-workout-btn');
                if (beginWorkoutBtn) {
                    beginWorkoutBtn.addEventListener('click', function() {
                        const templateId = this.dataset.templateId;
                        startWorkout(templateId);
                    });
                }

                const modifyTemplateBtn = selectedTemplateContainer.querySelector('.modify-template-btn');
                if (modifyTemplateBtn) {
                    modifyTemplateBtn.addEventListener('click', function() {
                        const templateId = this.dataset.templateId;
                        
                        fetch(`get_template.php?id=${templateId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    localStorage.setItem('edit_template_data', JSON.stringify({
                                        id: templateId,
                                        template: data.template,
                                        exercises: data.exercises
                                    }));
                                    
                                    localStorage.setItem('open_edit_template_modal', 'true');
                                    
                                    window.location.href = 'workout-templates.php';
                                } else {
                                    showNotification('Failed to get template data: ' + data.error, 'error');
                                }
                            })
                            .catch(error => {
                                showNotification('Error: ' + error.message, 'error');
                            });
                    });
                }
            }

            function startWorkout(templateId) {
                fetch(`get_template.php?id=${templateId}`)
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 500) {
                                return response.json().then(errorData => {
                                    throw new Error(errorData.error || `Server error: ${response.status}`);
                                });
                            }
                            throw new Error(`Failed to load template: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) throw new Error(data.error);

                        workoutState = {
                            templateId: templateId,
                            templateName: data.template.name,
                            exercises: data.exercises.map(ex => ({
                                ...ex,
                                completedSets: 0,
                                sets: Array(parseInt(ex.sets)).fill().map(() => ({
                                    weight: 0,
                                    reps: 0,
                                    rpe: null,
                                    completed: false
                                }))
                            })),
                            currentExerciseIndex: 0,
                            currentSet: 1,
                            startTime: Date.now(),
                            endTime: null,
                            timerInterval: null,
                            restTimerInterval: null,
                            restTime: parseInt(data.template.rest_time) || 90,
                            totalVolume: 0,
                            totalSets: data.exercises.reduce((acc, ex) => acc + parseInt(ex.sets), 0),
                            completedSets: 0,
                            notes: '',
                            peakWeight: 0,
                            caloriesBurned: 0,
                            isGlobal: data.is_global || false
                        };

                        workoutState.timerInterval = setInterval(updateWorkoutTimer, 1000);
                        
                        document.getElementById('workout-title').textContent = data.template.name;
                        
                        initializeWorkoutTracking();
                        updateWorkoutStats();
                        goToStep(2);
                    })
                    .catch(error => {
                        console.error("Error starting workout:", error);
                        showNotification(`Failed to start workout: ${error.message}`, 'error');
                    });
            }

            function updateWorkoutTimer() {
                const elapsed = Math.floor((Date.now() - workoutState.startTime) / 1000);
                const hours = String(Math.floor(elapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(elapsed % 60).padStart(2, '0');
                const timeString = `${hours}:${minutes}:${seconds}`;
                
                document.getElementById('workout-timer').textContent = timeString;
                document.getElementById('stats-elapsed-time').textContent = timeString;
            }

            function initializeWorkoutTracking() {
                updateExerciseList();
                updateCurrentExerciseDisplay();
                updateNextExercisePreview();
                initExerciseReordering();
            }
            
            function updateExerciseList() {
                const exerciseList = document.getElementById('exercise-list');
                exerciseList.innerHTML = workoutState.exercises.map((ex, index) => `
                    <div class="pdw-exercise-list-item ${index === workoutState.currentExerciseIndex ? 'current' : ''}" 
                         data-index="${index}" draggable="true">
                        <div class="pdw-exercise-drag-handle"><i class="fas fa-grip-vertical"></i></div>
                        <div class="exercise-status ${index === workoutState.currentExerciseIndex ? 'current' : ''}">${index + 1}</div>
                        <div class="exercise-name">${ex.exercise_name}</div>
                        <div class="exercise-progress">
                            ${ex.completedSets}/${ex.sets.length} sets
                        </div>
                    </div>
                `).join('');
                
                updateReorderButtonsState();
            }
            
            function initExerciseReordering() {
                const moveUpBtn = document.getElementById('move-exercise-up-btn');
                const moveDownBtn = document.getElementById('move-exercise-down-btn');
                
                moveUpBtn.addEventListener('click', moveCurrentExerciseUp);
                moveDownBtn.addEventListener('click', moveCurrentExerciseDown);
                
                const exerciseList = document.getElementById('exercise-list');
                exerciseList.addEventListener('dragstart', handleDragStart);
                exerciseList.addEventListener('dragover', handleDragOver);
                exerciseList.addEventListener('drop', handleDrop);
                exerciseList.addEventListener('dragend', handleDragEnd);
                
                exerciseList.addEventListener('click', function(e) {
                    const item = e.target.closest('.pdw-exercise-list-item');
                    if (item) {
                        const index = parseInt(item.dataset.index);
                        if (index !== workoutState.currentExerciseIndex) {
                            workoutState.currentExerciseIndex = index;
                            workoutState.currentSet = 1;
                            updateCurrentExerciseDisplay();
                            updateNextExercisePreview();
                            updateReorderButtonsState();
                        }
                    }
                });
                
                updateReorderButtonsState();
            }
            
            function updateReorderButtonsState() {
                const moveUpBtn = document.getElementById('move-exercise-up-btn');
                const moveDownBtn = document.getElementById('move-exercise-down-btn');
                
                moveUpBtn.disabled = workoutState.currentExerciseIndex === 0;
                moveDownBtn.disabled = workoutState.currentExerciseIndex === workoutState.exercises.length - 1;
            }
            
            function moveCurrentExerciseUp() {
                if (workoutState.currentExerciseIndex > 0) {
                    const tempExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                    workoutState.exercises[workoutState.currentExerciseIndex] = workoutState.exercises[workoutState.currentExerciseIndex - 1];
                    workoutState.exercises[workoutState.currentExerciseIndex - 1] = tempExercise;
                    
                    workoutState.currentExerciseIndex--;
                    
                    updateExerciseList();
                    updateCurrentExerciseDisplay();
                    updateNextExercisePreview();
                    
                    showNotification('Exercise moved up', 'success');
                }
            }
            
            function moveCurrentExerciseDown() {
                if (workoutState.currentExerciseIndex < workoutState.exercises.length - 1) {
                    const tempExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                    workoutState.exercises[workoutState.currentExerciseIndex] = workoutState.exercises[workoutState.currentExerciseIndex + 1];
                    workoutState.exercises[workoutState.currentExerciseIndex + 1] = tempExercise;
                    
                    workoutState.currentExerciseIndex++;
                    
                    updateExerciseList();
                    updateCurrentExerciseDisplay();
                    updateNextExercisePreview();
                    
                    showNotification('Exercise moved down', 'success');
                }
            }
            
            let draggedItem = null;
            
            function handleDragStart(e) {
                const item = e.target.closest('.pdw-exercise-list-item');
                if (item) {
                    draggedItem = item;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', item.innerHTML);
                    item.classList.add('dragging');
                }
            }
            
            function handleDragOver(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';
                return false;
            }
            
            function handleDrop(e) {
                e.preventDefault();
                if (e.stopPropagation) {
                    e.stopPropagation();
                }
                
                const dropTarget = e.target.closest('.pdw-exercise-list-item');
                if (dropTarget && draggedItem !== dropTarget) {
                    const fromIndex = parseInt(draggedItem.dataset.index);
                    const toIndex = parseInt(dropTarget.dataset.index);
                    
                    const [movedExercise] = workoutState.exercises.splice(fromIndex, 1);
                    workoutState.exercises.splice(toIndex, 0, movedExercise);
                    
                    if (workoutState.currentExerciseIndex === fromIndex) {
                        workoutState.currentExerciseIndex = toIndex;
                    } else if (
                        (fromIndex < workoutState.currentExerciseIndex && toIndex >= workoutState.currentExerciseIndex) ||
                        (fromIndex > workoutState.currentExerciseIndex && toIndex <= workoutState.currentExerciseIndex)
                    ) {
                        workoutState.currentExerciseIndex += fromIndex < toIndex ? -1 : 1;
                    }
                    
                    updateExerciseList();
                    updateCurrentExerciseDisplay();
                    updateNextExercisePreview();
                    
                    showNotification('Exercise order updated', 'success');
                }
                
                return false;
            }
            
            function handleDragEnd() {
                const items = document.querySelectorAll('.pdw-exercise-list-item');
                items.forEach(item => {
                    item.classList.remove('dragging');
                });
                draggedItem = null;
            }
            
            function updateCurrentExerciseDisplay() {
                const currentExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                
                document.getElementById('current-exercise-name').textContent = currentExercise.exercise_name;
                document.getElementById('exercise-target').textContent = `Target: ${currentExercise.target || 'General Exercise'}`;
                document.getElementById('weight-input').value = '';
                document.getElementById('reps-input').value = '';
                
                document.querySelectorAll('.pdw-exercise-list-item').forEach((item, index) => {
                    const exercise = workoutState.exercises[index];
                    item.classList.remove('current', 'completed');
                    item.querySelector('.exercise-status').classList.remove('current', 'completed');
                    
                    item.querySelector('.exercise-progress').textContent = 
                        `${exercise.completedSets}/${exercise.sets.length} sets`;
                    
                    if (index === workoutState.currentExerciseIndex) {
                        item.classList.add('current');
                        item.querySelector('.exercise-status').classList.add('current');
                    } else if (exercise.completedSets === exercise.sets.length) {
                        item.classList.add('completed');
                        item.querySelector('.exercise-status').classList.add('completed');
                        item.querySelector('.exercise-status').innerHTML = '<i class="fas fa-check"></i>';
                    }
                });
                
                updateSetsTable();
                updateReorderButtonsState();
            }

            function updateNextExercisePreview() {
                const nextIndex = workoutState.currentExerciseIndex + 1;
                const nextExerciseCard = document.getElementById('next-exercise-card');
                
                if (nextIndex < workoutState.exercises.length) {
                    const nextEx = workoutState.exercises[nextIndex];
                    nextExerciseCard.innerHTML = `
                        <div class="next-exercise-icon"><i class="fas fa-dumbbell"></i></div>
                        <div class="next-exercise-name">${nextEx.exercise_name}</div>
                        <div class="next-exercise-details">
                            ${nextEx.sets.length} sets • ${nextEx.rest_time || workoutState.restTime}s rest
                        </div>
                    `;
                } else {
                    nextExerciseCard.innerHTML = `
                        <div class="next-exercise-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="next-exercise-name">Workout Complete!</div>
                        <div class="next-exercise-details">Finish strong! 💪</div>
                    `;
                }
            }

            document.getElementById('complete-set-btn').addEventListener('click', () => {
                const weight = parseFloat(document.getElementById('weight-input').value) || 0;
                const reps = parseInt(document.getElementById('reps-input').value) || 0;
                
                if (reps < 1) {
                    showNotification("Please enter at least 1 rep", 'error');
                    return;
                }

                const currentEx = workoutState.exercises[workoutState.currentExerciseIndex];
                currentEx.sets[workoutState.currentSet - 1] = { 
                    weight, 
                    reps,
                    rpe: 3,
                    completed: true 
                };
                currentEx.completedSets++;
                
                workoutState.completedSets++;
                workoutState.totalVolume += weight * reps;
                if (weight > workoutState.peakWeight) {
                    workoutState.peakWeight = weight;
                }

                updateSetsTable();
                updateWorkoutStats();
                updateCurrentExerciseDisplay();
                
                if (workoutState.currentSet < currentEx.sets.length) {
                    workoutState.currentSet++;
                    showRestScreen();
                } else {
                    moveToNextExercise();
                }
            });

            function moveToNextExercise() {
                workoutState.currentExerciseIndex++;
                workoutState.currentSet = 1;
                
                if (workoutState.currentExerciseIndex < workoutState.exercises.length) {
                    updateCurrentExerciseDisplay();
                    updateNextExercisePreview();
                    showRestScreen();
                } else {
                    clearInterval(workoutState.restTimerInterval);
                    document.getElementById('rest-screen').style.display = 'none';
                    endWorkout();
                }
            }

            function endWorkout() {
                console.log("Ending workout and transitioning to summary");
                clearInterval(workoutState.timerInterval);
                clearInterval(workoutState.restTimerInterval);
                
                workoutState.endTime = Date.now();
                const durationMs = workoutState.endTime - workoutState.startTime;
                const durationMinutes = Math.round(durationMs / 60000);
                workoutState.notes = document.getElementById('workout-notes').value;
                
                try {
                    prepareWorkoutSummary(durationMinutes);
                    
                    goToStep(3);
                } catch (error) {
                    console.error("Error preparing workout summary:", error);
                    showNotification("Error generating workout summary. Please try again.", "error");
                }
            }

            function prepareWorkoutSummary(durationMinutes) {
                console.log("Preparing workout summary with duration:", durationMinutes);
                const now = new Date();
                document.getElementById('workout-complete-date').textContent = now.toLocaleDateString() + " at " + now.toLocaleTimeString();
                document.getElementById('summary-volume').textContent = `${workoutState.totalVolume.toFixed(1)} kg`;
                document.getElementById('summary-sets').textContent = `${workoutState.completedSets}`;
                document.getElementById('summary-peak-weight').textContent = `${workoutState.peakWeight} kg`;
                
                const elapsed = Math.floor((workoutState.endTime - workoutState.startTime) / 1000);
                const hours = String(Math.floor(elapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(elapsed % 60).padStart(2, '0');
                const timeString = `${hours}:${minutes}:${seconds}`;
                
                document.getElementById('summary-rest-time').textContent = timeString;
                document.getElementById('summary-workout-notes').value = workoutState.notes || '';
                
                const breakdownList = document.getElementById('exercise-breakdown-list');
                breakdownList.innerHTML = '';
                
                workoutState.exercises.forEach(exercise => {
                    if (exercise.completedSets > 0) {
                        let exerciseVolume = 0;
                        let setDetails = [];
                        
                        exercise.sets.forEach(set => {
                            if (set.completed) {
                                exerciseVolume += set.weight * set.reps;
                                setDetails.push(`${set.weight}kg × ${set.reps}`);
                            }
                        });
                        
                        const exerciseItem = document.createElement('div');
                        exerciseItem.className = 'exercise-breakdown-item';
                        exerciseItem.innerHTML = `
                            <div class="exercise-breakdown-header">
                                <div class="exercise-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="exercise-detail">
                                    <div class="exercise-name">${exercise.exercise_name}</div>
                                    <div class="exercise-sets">${exercise.completedSets} sets completed</div>
                                </div>
                                <div class="exercise-volume">${exerciseVolume.toFixed(1)} kg</div>
                            </div>
                            <div class="exercise-sets-detail">
                                ${setDetails.join(' | ')}
                            </div>
                        `;
                        
                        breakdownList.appendChild(exerciseItem);
                    }
                });
                
                setTimeout(() => {
                    try {
                        createSetPerformanceChart();
                        createVolumeDistributionChart();
                    } catch (error) {
                        console.error("Error creating charts:", error);
                    }
                }, 100);
                
                const saveBtn = document.getElementById('final-save-workout-btn');
                const oldSaveBtn = saveBtn.cloneNode(true);
                saveBtn.parentNode.replaceChild(oldSaveBtn, saveBtn);
                
                document.getElementById('final-save-workout-btn').addEventListener('click', saveWorkout);
            }
            
            function createSetPerformanceChart() {
                const canvas = document.getElementById('set-performance-chart');
                if (canvas.__chart) {
                    canvas.__chart.destroy();
                }
                
                const ctx = canvas.getContext('2d');
                
                const labels = [];
                const data = [];
                
                workoutState.exercises.forEach(exercise => {
                    exercise.sets.forEach((set, index) => {
                        if (set.completed) {
                            labels.push(`${exercise.exercise_name} Set ${index + 1}`);
                            data.push(set.weight * set.reps);
                        }
                    });
                });
                
                canvas.__chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Volume (kg)',
                            data: data,
                            backgroundColor: 'rgba(67, 97, 238, 0.7)',
                            borderColor: 'rgba(67, 97, 238, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)',
                                    maxRotation: 90,
                                    minRotation: 45
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            }
            
            function createVolumeDistributionChart() {
                const canvas = document.getElementById('volume-distribution-chart');
                if (canvas.__chart) {
                    canvas.__chart.destroy();
                }
                
                const ctx = canvas.getContext('2d');
                const labels = [];
                const data = [];
                
                workoutState.exercises.forEach(exercise => {
                    if (exercise.completedSets > 0) {
                        let exerciseVolume = 0;
                        
                        exercise.sets.forEach(set => {
                            if (set.completed) {
                                exerciseVolume += set.weight * set.reps;
                            }
                        });
                        
                        if (exerciseVolume > 0) {
                            labels.push(exercise.exercise_name);
                            data.push(exerciseVolume);
                        }
                    }
                });
                
                if (data.length > 0) {
                    canvas.__chart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: [
                                    'rgba(67, 97, 238, 0.7)',
                                    'rgba(76, 201, 240, 0.7)',
                                    'rgba(247, 37, 133, 0.7)',
                                    'rgba(255, 92, 138, 0.7)',
                                    'rgba(58, 86, 212, 0.7)',
                                    'rgba(6, 214, 160, 0.7)',
                                    'rgba(255, 209, 102, 0.7)',
                                    'rgba(239, 71, 111, 0.7)'
                                ],
                                borderColor: 'rgba(15, 15, 26, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    canvas.getContext('2d').fillStyle = 'rgba(255, 255, 255, 0.7)';
                    canvas.getContext('2d').font = '16px Poppins';
                    canvas.getContext('2d').textAlign = 'center';
                    canvas.getContext('2d').fillText('No volume data available', canvas.width / 2, canvas.height / 2);
                }
            }

            function saveWorkout() {
                let totalRPE = 0;
                let totalRPESets = 0;
                
                workoutState.exercises.forEach(exercise => {
                    exercise.sets.forEach(set => {
                        if (set.completed && set.rpe > 0) {
                            totalRPE += set.rpe;
                            totalRPESets++;
                        }
                    });
                });
                
                const avgRPE = totalRPESets > 0 ? totalRPE / totalRPESets : 3;
                
                const workoutData = {
                    title: workoutState.templateName,
                    type: 'strength',
                    notes: document.getElementById('summary-workout-notes').value,
                    duration_minutes: Math.round((workoutState.endTime - workoutState.startTime) / 60000),
                    template_id: workoutState.templateId,
                    total_volume: workoutState.totalVolume,
                    avg_intensity: avgRPE,
                    calories_burned: workoutState.caloriesBurned || Math.round(calculateCaloriesBurned()),
                    exercises: workoutState.exercises.map(exercise => {
                        if (exercise.completedSets > 0) {
                            let exerciseTotalRPE = 0;
                            let exerciseRPESets = 0;
                            
                            const filteredSets = exercise.sets.filter(set => set.completed);
                            
                            filteredSets.forEach(set => {
                                if (set.rpe > 0) {
                                    exerciseTotalRPE += set.rpe;
                                    exerciseRPESets++;
                                }
                            });
                            
                            const exerciseAvgRPE = exerciseRPESets > 0 ? exerciseTotalRPE / exerciseRPESets : 3;
                            
                            return {
                                name: exercise.exercise_name,
                                sets: filteredSets,
                                avg_rpe: exerciseAvgRPE
                            };
                        }
                        return null;
                    }).filter(ex => ex !== null)
                };
                
                fetch('save_workout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `save_workout=1&workout_data=${encodeURIComponent(JSON.stringify(workoutData))}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Workout saved successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = 'workout-history.php';
                        }, 2000);
                    } else {
                        showNotification(`Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showNotification(`Error: ${error.message}`, 'error');
                });
            }

            function updateSetsTable() {
                const currentEx = workoutState.exercises[workoutState.currentExerciseIndex];
                const tbody = document.getElementById('previous-sets-table');
                tbody.innerHTML = '';
                
                currentEx.sets.forEach((set, index) => {
                    if (set.completed) {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${set.weight} kg</td>
                            <td>${set.reps}</td>
                        `;
                        tbody.appendChild(row);
                    }
                });
            }

            function updateWorkoutStats() {
                document.getElementById('stats-sets-completed').textContent = 
                    `${workoutState.completedSets}/${workoutState.totalSets}`;
                
                document.getElementById('stats-volume').textContent = 
                    `${workoutState.totalVolume.toFixed(1)} kg`;
                
                const caloriesBurned = calculateCaloriesBurned();
                document.getElementById('stats-calories-burned').textContent = 
                    `${Math.round(caloriesBurned)} kcal`;
            }

            function calculateCaloriesBurned() {
                let totalCalories = 0;
                
                workoutState.exercises.forEach(exercise => {
                    let exerciseCalories = 0;
                    exercise.sets.forEach(set => {
                        if (set.completed) {
                            const baseCaloriesPerMinute = 4;
                            const setDurationMinutes = (set.reps * 3.5) / 60;
                            const userWeight = 70;
                            const intensityMultiplier = 1 + (set.weight / userWeight) * 0.5;
                            const setCalories = (baseCaloriesPerMinute * setDurationMinutes * intensityMultiplier) + (set.weight * set.reps * 0.02);
                            
                            exerciseCalories += setCalories;
                        }
                    });
                    
                    totalCalories += exerciseCalories;
                });
                
                workoutState.caloriesBurned = Math.round(totalCalories);
                return totalCalories;
            }

            function calculateIntensity(weight, reps) {
                if (weight === 0) {
                    return 4.0;
                }
                
                const volume = weight * reps;
                
                if (volume < 100) {
                    return 4.0;
                } else if (volume < 300) {
                    return 6.0;
                } else {
                    return 8.0;
                }
            }
                
            let restTimeRemaining = 90;

            skipRestBtn.addEventListener('click', () => {
                clearInterval(workoutState.restTimerInterval);
                document.getElementById('rest-screen').style.display = 'none';
                document.getElementById('current-exercise-container').style.display = 'block';
            });

            document.querySelectorAll('.timer-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.timer-preset-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const restTime = parseInt(this.dataset.time);
                    workoutState.restTime = restTime;
                    
                    const minutes = Math.floor(restTime / 60);
                    const seconds = restTime % 60;
                    document.getElementById('timer-display').textContent = 
                        `${minutes}:${String(seconds).padStart(2, '0')}`;
                });
            });

            function updateRestTimerDisplay() {
                const minutes = Math.floor(restTimeRemaining / 60);
                const seconds = restTimeRemaining % 60;
                document.getElementById('rest-timer-display').textContent = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            const weightInput = document.getElementById('weight-input');
            const repsInput = document.getElementById('reps-input');

            weightInput.addEventListener('input', (e) => {
                if (e.target.value < 0) e.target.value = 0;
            });

            repsInput.addEventListener('input', (e) => {
                if (e.target.value < 0) e.target.value = 0;
            });

            function goToStep(stepNumber) {
                document.querySelectorAll('.step-item').forEach(item => {
                    const itemStep = parseInt(item.dataset.step);
                    item.classList.remove('active', 'completed');
                    
                    if (itemStep < stepNumber) {
                        item.classList.add('completed');
                    } else if (itemStep === stepNumber) {
                        item.classList.add('active');
                    }
                });

                document.querySelectorAll('.step-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                document.getElementById(`step${stepNumber}-content`).classList.add('active');
            }

            function showNotification(message, type = 'success') {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => {
                    notification.remove();
                });

                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }

            function initRPESelection() {
                const rpeButtons = document.querySelectorAll('.rpe-button');
                
                rpeButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        rpeButtons.forEach(btn => btn.classList.remove('selected'));
                        button.classList.add('selected');
                        const selectedRPE = parseInt(button.dataset.rpe);
                        
                        const currentExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                        const setIndex = workoutState.currentSet - 1 >= 0 ? workoutState.currentSet - 1 : 0;
                        
                        if (currentExercise && currentExercise.sets[setIndex]) {
                            currentExercise.sets[setIndex].rpe = selectedRPE;
                        }
                    });
                });
                
                const increaseRestBtn = document.getElementById('increase-rest-btn');
                const decreaseRestBtn = document.getElementById('decrease-rest-btn');
                
                increaseRestBtn.addEventListener('click', () => {
                    restTimeRemaining += 15;
                    updateRestTimerDisplay();
                    showNotification('Added 15 seconds to rest timer', 'success');
                });
                
                decreaseRestBtn.addEventListener('click', () => {
                    restTimeRemaining = Math.max(0, restTimeRemaining - 15);
                    updateRestTimerDisplay();
                    if (restTimeRemaining === 0) {
                        clearInterval(workoutState.restTimerInterval);
                        document.getElementById('rest-screen').style.display = 'none';
                        document.getElementById('current-exercise-container').style.display = 'block';
                    } else {
                        showNotification('Reduced rest timer by 15 seconds', 'success');
                    }
                });
            }
                
            function showRestScreen() {
                document.getElementById('current-exercise-container').style.display = 'none';
                document.getElementById('rest-screen').style.display = 'flex';
                
                const currentExercise = workoutState.exercises[workoutState.currentExerciseIndex];
                restTimeRemaining = parseInt(currentExercise.rest_time) || workoutState.restTime;
                
                updateRestTimerDisplay();
                    
                const rpeButtons = document.querySelectorAll('.rpe-button');
                rpeButtons.forEach(btn => btn.classList.remove('selected'));
                const defaultRpeButton = document.querySelector('.rpe-button[data-rpe="3"]');
                if (defaultRpeButton) {
                    defaultRpeButton.classList.add('selected');
                }
                
                let nextExercise = "";
                if (workoutState.currentSet < workoutState.exercises[workoutState.currentExerciseIndex].sets.length) {
                    nextExercise = `${workoutState.exercises[workoutState.currentExerciseIndex].exercise_name} (Set ${workoutState.currentSet})`;
                } else if (workoutState.currentExerciseIndex + 1 < workoutState.exercises.length) {
                    nextExercise = workoutState.exercises[workoutState.currentExerciseIndex + 1].exercise_name;
                } else {
                    nextExercise = 'Workout Complete!';
                }
                
                document.getElementById('rest-next-exercise').textContent = nextExercise;
                
                if (workoutState.restTimerInterval) {
                    clearInterval(workoutState.restTimerInterval);
                }
                
                workoutState.restTimerInterval = setInterval(() => {
                    restTimeRemaining--;
                    updateRestTimerDisplay();
                    
                    if (restTimeRemaining <= 0) {
                        clearInterval(workoutState.restTimerInterval);
                        document.getElementById('rest-screen').style.display = 'none';
                        document.getElementById('current-exercise-container').style.display = 'block';
                    }
                }, 1000);
            }
            
            document.getElementById('end-workout-btn').addEventListener('click', () => {
                if (confirm('Are you sure you want to end the workout now?')) {
                    endWorkout();
                }
            });
            
            initRPESelection();
            
            const emptyWorkoutCard = document.getElementById('emptyWorkoutCard');
            if (emptyWorkoutCard) {
                emptyWorkoutCard.addEventListener('click', function() {
                    showNotification("Starting empty workout...", 'success');
                    goToStep(2);
                });
            }

            function toggleGlobalTemplates(show) {
                console.log("Toggling global templates:", show);
                const globalTemplates = document.querySelectorAll('.pdw-template-global');
                console.log("Found", globalTemplates.length, "global template elements");
                
                const listView = document.getElementById('listViewBtn').classList.contains('active');
                const gridView = document.getElementById('gridViewBtn').classList.contains('active');
                
                globalTemplates.forEach(template => {
                    if (show) {
                        if (template.classList.contains('template-card') && gridView) {
                            template.style.display = '';
                        } else if (template.classList.contains('template-list-item') && listView) {
                            template.style.display = '';
                        } else if (!gridView && !listView) {
                            template.style.display = '';
                        }
                    } else {
                        template.style.display = 'none';
                    }
                });
                
                const noTemplatesMessage = document.getElementById('noTemplatesMessage');
                if (noTemplatesMessage && show && globalTemplates.length > 0) {
                    noTemplatesMessage.style.display = 'none';
                } else if (noTemplatesMessage && !show) {
                    noTemplatesMessage.style.display = 'block';
                }
                
                if (show) {
                    if (globalTemplates.length > 0) {
                        showNotification('Global templates are now visible', 'success');
                    } else {
                        showNotification('No global templates available', 'info');
                    }
                } else {
                    showNotification('Global templates are now hidden', 'info');
                }
                
                filterTemplates();
                updateCategoryCounts();
            }
            
            function updateCategoryCounts() {
                const activeView = document.getElementById('gridViewBtn').classList.contains('active') ? '.template-card' : '.template-list-item';
                
                const allVisibleTemplates = document.querySelectorAll(`${activeView}:not([style*="display: none"])`);
                document.querySelector('.category-item[data-category="all"] .category-count').textContent = allVisibleTemplates.length;
                
                const strengthTemplates = document.querySelectorAll(`${activeView}.template-strength:not([style*="display: none"])`);
                document.querySelector('.category-item[data-category="Strength Training"] .category-count').textContent = strengthTemplates.length;
            
                const hiitTemplates = document.querySelectorAll(`${activeView}.template-hiit:not([style*="display: none"])`);
                document.querySelector('.category-item[data-category="hiit"] .category-count').textContent = hiitTemplates.length;
                
                const cardioTemplates = document.querySelectorAll(`${activeView}.template-cardio:not([style*="display: none"])`);
                document.querySelector('.category-item[data-category="cardio"] .category-count').textContent = cardioTemplates.length;
            }
            
            const globalTemplatesToggle = document.getElementById('globalTemplatesToggle');
            
            if (globalTemplatesToggle) {
                console.log("Global templates toggle found");
                globalTemplatesToggle.addEventListener('change', function() {
                    console.log("Toggle changed:", this.checked);
                    toggleGlobalTemplates(this.checked);
                });
            } else {
                console.log("Global templates toggle not found - will be initialized later");
            }
            
            setTimeout(function initGlobalTemplatesFeature() {
                console.log("Initializing global templates feature");
                
                const globalTemplatesToggle = document.getElementById('globalTemplatesToggle');
                if (!globalTemplatesToggle) {
                    console.error("Global templates toggle not found");
                    return;
                }
                
                const templatesPanel = document.querySelector('.templates-panel');
                if (!templatesPanel) {
                    console.error("Templates panel not found");
                    return;
                }
                
                let templateGrid = templatesPanel.querySelector('.template-grid');
                let templateList = templatesPanel.querySelector('.template-list');
                
                if (!templateGrid) {
                    console.log("Creating missing template grid");
                    templateGrid = document.createElement('div');
                    templateGrid.className = 'template-grid';
                    templateGrid.style.display = 'none';
                    templatesPanel.querySelector('.panel-content').appendChild(templateGrid);
                }
                
                if (!templateList) {
                    console.log("Creating missing template list");
                    templateList = document.createElement('div');
                    templateList.className = 'template-list';
                    templatesPanel.querySelector('.panel-content').appendChild(templateList);
                }
                
                try {
                    <?php if ($global_templates && is_array($global_templates) && count($global_templates) > 0): ?>
                    console.log("Found <?php echo count($global_templates); ?> global templates to add");
                    
                    <?php foreach ($global_templates as $index => $template): ?>
                    try {
                        <?php 
                        $categoryClasses = 'template-all pdw-template-global';
                        $difficultyClass = strtolower($template['difficulty'] ?: 'beginner');
                        $durationClass = '';
                        
                        if ($template['estimated_time'] < 30) {
                            $durationClass = 'duration-short';
                        } elseif ($template['estimated_time'] < 60) {
                            $durationClass = 'duration-medium';
                        } else {
                            $durationClass = 'duration-long';
                        }

                        if (
                            stripos($template['name'], 'strength') !== false || 
                            stripos($template['description'], 'strength') !== false
                        ) {
                            $categoryClasses .= ' template-strength';
                        }
                        
                        if (
                            stripos($template['name'], 'hiit') !== false || 
                            stripos($template['description'], 'hiit') !== false ||
                            stripos($template['name'], 'interval') !== false || 
                            stripos($template['description'], 'interval') !== false
                        ) {
                            $categoryClasses .= ' template-hiit';
                        }
                        
                        if (
                            stripos($template['name'], 'cardio') !== false || 
                            stripos($template['description'], 'cardio') !== false
                        ) {
                            $categoryClasses .= ' template-cardio';
                        }
                        ?>
                        
                        console.log("Adding template: <?php echo htmlspecialchars($template['name']); ?>");
                        
                        const gridCardId<?php echo $index; ?> = document.createElement('div');
                        gridCardId<?php echo $index; ?>.className = 'template-card <?php echo $categoryClasses; ?> <?php echo $difficultyClass; ?> <?php echo $durationClass; ?>';
                        gridCardId<?php echo $index; ?>.dataset.id = '<?php echo $template['id']; ?>';
                        gridCardId<?php echo $index; ?>.dataset.category = '<?php echo htmlspecialchars($template['category'] ?? ''); ?>';
                        gridCardId<?php echo $index; ?>.style.display = 'none';
                        
                        gridCardId<?php echo $index; ?>.innerHTML = `
                            <div class="template-card-header">
                                <div class="template-card-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                <div class="template-card-meta">
                                    <div class="template-card-meta-item">
                                        <i class="fas fa-stopwatch"></i>
                                        <span><?php echo $template['estimated_time']; ?> min</span>
                                    </div>
                                    <div class="template-card-meta-item">
                                        <i class="fas fa-dumbbell"></i>
                                        <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="template-card-body">
                                <?php if (!empty($template['description'])): ?>
                                    <div class="template-card-description"><?php echo htmlspecialchars(substr($template['description'], 0, 50)) . (strlen($template['description']) > 50 ? '...' : ''); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="template-card-footer">
                                <div class="template-card-action">
                                    <i class="fas fa-chevron-right"></i> <?php echo t('select'); ?>
                                </div>
                            </div>
                        `;
                        
                        const listItemId<?php echo $index; ?> = document.createElement('div');
                        listItemId<?php echo $index; ?>.className = 'template-list-item <?php echo $categoryClasses; ?> <?php echo $difficultyClass; ?> <?php echo $durationClass; ?>';
                        listItemId<?php echo $index; ?>.dataset.id = '<?php echo $template['id']; ?>';
                        listItemId<?php echo $index; ?>.dataset.category = '<?php echo htmlspecialchars($template['category'] ?? ''); ?>';
                        listItemId<?php echo $index; ?>.style.display = 'none';
                        
                        listItemId<?php echo $index; ?>.innerHTML = `
                            <div class="template-list-item-header">
                                <div class="template-list-item-title"><?php echo htmlspecialchars($template['name']); ?></div>
                                <div class="template-list-item-difficulty"><?php echo ucfirst($template['difficulty'] ?: 'Beginner'); ?></div>
                            </div>
                            <div class="template-list-item-meta">
                                <div class="template-list-item-meta-item">
                                    <i class="fas fa-stopwatch"></i>
                                    <span><?php echo $template['estimated_time']; ?> min</span>
                                </div>
                                <div class="template-list-item-meta-item">
                                    <i class="fas fa-dumbbell"></i>
                                    <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                </div>
                                <div class="template-list-item-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($template['creator']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($template['description'])): ?>
                                <div class="template-list-item-description"><?php echo htmlspecialchars(substr($template['description'], 0, 80)) . (strlen($template['description']) > 80 ? '...' : ''); ?></div>
                            <?php endif; ?>
                        `;
                        
                        gridCardId<?php echo $index; ?>.addEventListener('click', function() {
                            const templateId = this.dataset.id;
                            loadTemplateDetails(templateId);
                        });
                        
                        listItemId<?php echo $index; ?>.addEventListener('click', function() {
                            const templateId = this.dataset.id;
                            loadTemplateDetails(templateId);
                        });
                    
                        templateGrid.appendChild(gridCardId<?php echo $index; ?>);
                        templateList.appendChild(listItemId<?php echo $index; ?>);
                        
                        console.log("Successfully added template", <?php echo $index; ?>);
                    } catch (e) {
                        console.error("Error adding template:", e);
                    }
                    <?php endforeach; ?>
                    <?php else: ?>
                    console.log("No global templates found in PHP data");
                    <?php endif; ?>
                    
                    if (globalTemplatesToggle.checked) {
                        toggleGlobalTemplates(true);
                    }
                    
                } catch (error) {
                    console.error("Error initializing global templates:", error);
                }
            }, 1000);
        });
    </script>
</body>
</html> 