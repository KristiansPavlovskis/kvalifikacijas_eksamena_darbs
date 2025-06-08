<?php

require_once 'profile_access_control.php';
require_once 'languages.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/start-workout.php");
    exit;
}

require_once '../assets/db_connection.php';

if (isset($_POST['save_workout'])) {
    saveWorkoutTemplate();
}

function saveWorkoutTemplate() {
    global $conn;
    
    $name = $_POST['workout_name'];
    $description = $_POST['workout_description'];
    $difficulty = $_POST['difficulty'];
    $estimatedTime = $_POST['estimated_time'];
    $category = $_POST['category'];
    $exercises = json_decode($_POST['exercises_json'], true);
    
    if (empty($name) || empty($exercises)) {
        echo json_encode(['success' => false, 'message' => 'Please provide workout name and add exercises']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO workout_templates (name, description, difficulty, estimated_time, category, user_id, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssisi", $name, $description, $difficulty, $estimatedTime, $category, $userId);
        $stmt->execute();
        
        $templateId = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO workout_template_exercises 
                               (workout_template_id, exercise_id, position, sets, rest_time, notes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($exercises as $index => $exercise) {
            $position = $index + 1;
            $exerciseId = $exercise['exercise_id'];
            $sets = $exercise['sets'];
            $restTime = $exercise['rest_time'];
            $notes = $exercise['notes'];
            
            $stmt->bind_param("iiiiis", $templateId, $exerciseId, $position, $sets, $restTime, $notes);
            $stmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Workout template saved successfully!', 'template_id' => $templateId]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error saving workout: ' . $e->getMessage()]);
        exit;
    }
}

function searchExercises($query, $category = '') {
    global $conn;
    
    $searchQuery = "%$query%";
    $sql = "SELECT 
            id, name, description, exercise_type, equipment, 
            primary_muscle, difficulty, instructions, 
            common_mistakes, benefits, video_url, 
            created_at, updated_at 
            FROM exercises 
            WHERE (name LIKE ? 
            OR description LIKE ? 
            OR primary_muscle LIKE ? 
            OR exercise_type LIKE ?)";
    
    if (!empty($category)) {
        $sql .= " AND exercise_type = ?";
    }
    
    $sql .= " LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('SQL prepare error: ' . $conn->error);
        return ['error' => 'Database error'];
    }

    if (!empty($category)) {
        $stmt->bind_param("sssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $category);
    } else {
        $stmt->bind_param("ssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    }
    
    if (!$stmt->execute()) {
        error_log('SQL execute error: ' . $stmt->error);
        return ['error' => 'Database error'];
    }
    
    $result = $stmt->get_result();
    
    $exercises = [];
    while ($row = $result->fetch_assoc()) {
        $exercises[] = $row;
    }
    
    return $exercises;
}

function getWorkoutTemplates() {
    global $conn;
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id, name, description, difficulty, estimated_time, category, created_at, updated_at 
                          FROM workout_templates 
                          WHERE user_id = ? 
                          ORDER BY updated_at DESC");
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $exerciseStmt = $conn->prepare("SELECT COUNT(*) as count FROM workout_template_exercises WHERE workout_template_id = ?");
        $exerciseStmt->bind_param("i", $row['id']);
        $exerciseStmt->execute();
        $exerciseResult = $exerciseStmt->get_result();
        $exerciseCount = $exerciseResult->fetch_assoc()['count'];
        
        $row['exercise_count'] = $exerciseCount;
        $templates[] = $row;
    }
    
    return $templates;
}

function getAdminWorkoutTemplates() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT wt.id, wt.name, wt.description, wt.difficulty, wt.estimated_time, 
                          wt.category, wt.created_at, wt.updated_at, u.username as creator
                          FROM workout_templates wt
                          JOIN users u ON wt.user_id = u.id
                          JOIN user_roles ur ON u.id = ur.user_id
                          JOIN roles r ON ur.role_id = r.id
                          WHERE r.id = 5
                          ORDER BY wt.updated_at DESC");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $exerciseStmt = $conn->prepare("SELECT COUNT(*) as count FROM workout_template_exercises WHERE workout_template_id = ?");
        $exerciseStmt->bind_param("i", $row['id']);
        $exerciseStmt->execute();
        $exerciseResult = $exerciseStmt->get_result();
        $exerciseCount = $exerciseResult->fetch_assoc()['count'];
        
        $row['exercise_count'] = $exerciseCount;
        $templates[] = $row;
    }
    
    return $templates;
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'search_exercises') {
        $query = $_GET['query'] ?? '';
        $category = $_GET['category'] ?? '';
        $exercises = searchExercises($query, $category);
        echo json_encode($exercises);
        exit;
    }
    
    if ($_GET['action'] === 'get_template') {
        $templateId = $_GET['template_id'] ?? 0;
        $template = getTemplateDetails($templateId);
        echo json_encode($template);
        exit;
    }
    
    if ($_GET['action'] === 'delete_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $templateId = $_POST['template_id'] ?? 0;
        $result = deleteWorkoutTemplate($templateId);
        echo json_encode($result);
        exit;
    }
    
    if ($_GET['action'] === 'update_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $templateId = $_POST['template_id'] ?? 0;
        $result = updateWorkoutTemplate($templateId, $_POST);
        echo json_encode($result);
        exit;
    }
}

function createRequiredTables() {
    global $conn;
    
    $conn->query("CREATE TABLE IF NOT EXISTS workout_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        difficulty VARCHAR(50),
        estimated_time INT,
        category VARCHAR(50),
        user_id INT,
        created_at DATETIME,
        updated_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS workout_template_exercises (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workout_template_id INT NOT NULL,
        exercise_id INT NOT NULL,
        position INT NOT NULL,
        sets INT DEFAULT 3,
        rest_time INT DEFAULT 60,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (workout_template_id) REFERENCES workout_templates(id) ON DELETE CASCADE,
        FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
    )");
}

createRequiredTables();

$templates = getWorkoutTemplates();
$adminTemplates = getAdminWorkoutTemplates();

function getTemplateDetails($templateId) {
    global $conn;
    
    if (!$templateId) {
        return ['success' => false, 'message' => 'Invalid template ID'];
    }
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT wt.id, wt.name, wt.description, wt.difficulty, wt.estimated_time, 
                          wt.category, wt.created_at, wt.updated_at 
                          FROM workout_templates wt
                          WHERE wt.id = ? AND wt.user_id = ?");
    
    $stmt->bind_param("ii", $templateId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("SELECT wt.id, wt.name, wt.description, wt.difficulty, wt.estimated_time, 
                          wt.category, wt.created_at, wt.updated_at
                          FROM workout_templates wt
                          JOIN users u ON wt.user_id = u.id
                          JOIN user_roles ur ON u.id = ur.user_id
                          JOIN roles r ON ur.role_id = r.id
                          WHERE wt.id = ? AND r.id = 5");
        
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Template not found'];
        }
    }
    
    $template = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT wte.*, e.name, e.primary_muscle, e.equipment
                          FROM workout_template_exercises wte
                          JOIN exercises e ON wte.exercise_id = e.id
                          WHERE wte.workout_template_id = ?
                          ORDER BY wte.position");
    
    $stmt->bind_param("i", $templateId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    $exercises = [];
    while ($row = $result->fetch_assoc()) {
        $exercises[] = [
            'exercise_id' => $row['exercise_id'],
            'name' => $row['name'],
            'sets' => $row['sets'],
            'rest_time' => $row['rest_time'],
            'notes' => $row['notes'],
            'position' => $row['position'],
            'muscle' => $row['primary_muscle'],
            'equipment' => $row['equipment'] ?? 'None'
        ];
    }
    
    $template['exercises'] = $exercises;
    $template['success'] = true;
    
    return $template;
}

function deleteWorkoutTemplate($templateId) {
    global $conn;
    
    if (!$templateId) {
        return ['success' => false, 'message' => 'Invalid template ID'];
    }
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id FROM workout_templates WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $templateId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Template not found or no permission'];
    }
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("DELETE FROM workout_template_exercises WHERE workout_template_id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM workout_templates WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $templateId, $userId);
        $stmt->execute();
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Template deleted successfully'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()];
    }
}

function updateWorkoutTemplate($templateId, $data) {
    global $conn;
    
    if (!$templateId) {
        return ['success' => false, 'message' => 'Invalid template ID'];
    }
    
    $userId = $_SESSION['user_id'];
    $name = $data['workout_name'];
    $description = $data['workout_description'];
    $difficulty = $data['difficulty'];
    $estimatedTime = $data['estimated_time'];
    $category = $data['category'];
    $exercises = json_decode($data['exercises_json'], true);
    
    if (empty($name) || empty($exercises)) {
        return ['success' => false, 'message' => 'Please provide workout name and add exercises'];
    }
    
    $stmt = $conn->prepare("SELECT id FROM workout_templates WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $templateId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Template not found or no permission'];
    }
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("UPDATE workout_templates 
                              SET name = ?, description = ?, difficulty = ?, estimated_time = ?, category = ?, updated_at = NOW() 
                              WHERE id = ? AND user_id = ?");
                              
        $stmt->bind_param("sssissi", $name, $description, $difficulty, $estimatedTime, $category, $templateId, $userId);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM workout_template_exercises WHERE workout_template_id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        
        $stmt = $conn->prepare("INSERT INTO workout_template_exercises 
                               (workout_template_id, exercise_id, position, sets, rest_time, notes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($exercises as $index => $exercise) {
            $position = $index + 1;
            $exerciseId = $exercise['exercise_id'];
            $sets = $exercise['sets'];
            $restTime = $exercise['rest_time'];
            $notes = $exercise['notes'];
            
            $stmt->bind_param("iiiiis", $templateId, $exerciseId, $position, $sets, $restTime, $notes);
            $stmt->execute();
        }
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Workout template updated successfully!'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error updating workout: ' . $e->getMessage()];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION["language"] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('workout_templates'); ?> | FitTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="global-profile.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
</head>
<body>
    <?php require_once 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1><?php echo t('workout_templates'); ?></h1>
            </div>
        </div>
        
        <div class="wt-template-actions">
            <button id="createTemplate" class="wt-action-btn">
                <i class="fas fa-plus"></i> <?php echo t('create_template'); ?>
            </button>
        </div>
        
        <div class="wt-template-view-toggle">
            <button class="wt-toggle-btn active" data-view="my-templates"><?php echo t('my_templates'); ?></button>
            <button class="wt-toggle-btn" data-view="admin-templates"><?php echo t('global_templates'); ?></button>
        </div>
        
        <div class="templates-container">
            <div id="myTemplatesView" class="wt-templates-view active">
                <div class="wt-templates-grid">
                    <?php if (empty($templates)): ?>
                        <div class="no-templates">
                            <p><?php echo t('no_personal_templates'); ?></p>
                            <p><?php echo t('click_create_template'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <div class="wt-template-card" data-id="<?php echo $template['id']; ?>">
                                <div class="wt-template-header">
                                    <h3 class="wt-template-title"><?php echo htmlspecialchars($template['name']); ?></h3>
                                    <div class="wt-template-meta">
                                        <div class="wt-meta-item">
                                            <i class="fas fa-dumbbell"></i>
                                            <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                        </div>
                                        <div class="wt-meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $template['estimated_time']; ?> <?php echo t('minutes'); ?></span>
                                        </div>
                                    </div>
                                    <div class="wt-difficulty-dots">
                                        <?php
                                            $difficultyLevel = 0;
                                            if ($template['difficulty'] === 'beginner') $difficultyLevel = 1;
                                            if ($template['difficulty'] === 'intermediate') $difficultyLevel = 2;
                                            if ($template['difficulty'] === 'advanced') $difficultyLevel = 3;
                                            
                                            for ($i = 1; $i <= 3; $i++) {
                                                echo '<div class="wt-dot ' . ($i <= $difficultyLevel ? 'active' : '') . '"></div>';
                                            }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="wt-template-actions-menu">
                                    <i class="fas fa-ellipsis-v"></i>
                                </div>
                                
                                <div class="wt-template-actions-dropdown">
                                    <div class="wt-template-dropdown-item view-template">
                                        <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                                    </div>
                                    <div class="wt-template-dropdown-item edit-template">
                                        <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                                    </div>
                                    <div class="wt-template-dropdown-item delete template-delete">
                                        <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                                    </div>
                                </div>
                                
                                <div class="wt-last-used">
                                    <?php echo t('last_used'); ?>: <?php echo !empty($template['updated_at']) ? date('M d', strtotime($template['updated_at'])) : t('na'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="adminTemplatesView" class="wt-templates-view">
                <div class="wt-templates-grid">
                    <?php if (empty($adminTemplates)): ?>
                        <div class="no-templates">
                            <p><?php echo t('no_global_templates'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($adminTemplates as $template): ?>
                            <div class="wt-template-card wt-admin-template" data-id="<?php echo $template['id']; ?>">
                                <div class="wt-template-header">
                                    <h3 class="wt-template-title"><?php echo htmlspecialchars($template['name']); ?></h3>
                                    <div class="wt-template-meta">
                                        <div class="wt-meta-item">
                                            <i class="fas fa-dumbbell"></i>
                                            <span><?php echo $template['exercise_count']; ?> <?php echo t('exercises'); ?></span>
                                        </div>
                                        <div class="wt-meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $template['estimated_time']; ?> <?php echo t('minutes'); ?></span>
                                        </div>
                                    </div>
                                    <div class="wt-difficulty-dots">
                                        <?php
                                            $difficultyLevel = 0;
                                            if ($template['difficulty'] === 'beginner') $difficultyLevel = 1;
                                            if ($template['difficulty'] === 'intermediate') $difficultyLevel = 2;
                                            if ($template['difficulty'] === 'advanced') $difficultyLevel = 3;
                                            
                                            for ($i = 1; $i <= 3; $i++) {
                                                echo '<div class="wt-dot ' . ($i <= $difficultyLevel ? 'active' : '') . '"></div>';
                                            }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="wt-template-actions-menu">
                                    <i class="fas fa-ellipsis-v"></i>
                                </div>
                                
                                <div class="wt-template-actions-dropdown">
                                    <div class="wt-template-dropdown-item view-template">
                                        <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                                    </div>
                                </div>
                                
                                <div class="wt-last-used">
                                    <?php echo t('created'); ?>: <?php echo !empty($template['created_at']) ? date('M d', strtotime($template['created_at'])) : t('na'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="wt-modal-overlay" id="createTemplateModal">
        <div class="wt-modal-content">
            <div class="wt-modal-header">
                <h2><?php echo t('create_template'); ?></h2>
                <div class="wt-modal-header-actions">
                    <button class="wt-save-template-btn" id="saveTemplateHeader">
                        <i class="fas fa-save"></i>
                        <?php echo t('save_template'); ?>
                    </button>
                    <button class="wt-modal-close" id="closeModal">&times;</button>
                </div>
            </div>
            
            <div class="wt-mobile-tabs">
                <div class="wt-mobile-tab active" data-tab="details"><?php echo t('details'); ?></div>
                <div class="wt-mobile-tab" data-tab="categories"><?php echo t('categories'); ?></div>
                <div class="wt-mobile-tab" data-tab="exercises"><?php echo t('exercises'); ?></div>
                <div class="wt-mobile-tab" data-tab="selected"><?php echo t('selected'); ?></div>
            </div>
            
            <div class="wt-modal-body">
                <div class="wt-template-details wt-mobile-section active" data-section="details">
                    <div class="wt-input-group">
                        <label for="workoutName"><?php echo t('template_name'); ?></label>
                        <input type="text" id="workoutName" placeholder="<?php echo t('template_name_placeholder'); ?>">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="workoutDescription"><?php echo t('description_optional'); ?></label>
                        <textarea id="workoutDescription" rows="3" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('categories'); ?></label>
                        <div class="wt-categories">
                            <div class="wt-category active" data-category="Strength Training"><?php echo t('strength_training'); ?></div>
                            <div class="wt-category" data-category="cardio"><?php echo t('cardio'); ?></div>
                            <div class="wt-category" data-category="Bodyweight"><?php echo t('bodyweight'); ?></div>
                        </div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('difficulty_level'); ?></label>
                        <input type="range" min="1" max="3" value="2" id="difficultySlider">
                        <div class="wt-slider-labels">
                            <span><?php echo t('beginner'); ?></span>
                            <span><?php echo t('intermediate'); ?></span>
                            <span><?php echo t('advanced'); ?></span>
                        </div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="setsPerExercise"><?php echo t('sets_per_exercise'); ?></label>
                        <input type="number" id="setsPerExercise" min="1" max="10" value="3">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="restTimePerExercise"><?php echo t('rest_time_between_exercises'); ?></label>
                        <input type="number" id="restTimePerExercise" min="0" max="10" value="1" step="0.5">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="estimatedTime"><?php echo t('estimated_time'); ?></label>
                        <input type="number" id="estimatedTime" min="5" max="180" value="45" readonly>
                    </div>
                </div>
                
                <div class="wt-exercise-selector">
                    <div class="wt-categories-column wt-mobile-section" data-section="categories">
                        <h3><?php echo t('categories'); ?></h3>
                        <div class="wt-category-list">
                            <div class="wt-category-item active" data-category="Strength Training">
                                <i class="fas fa-dumbbell"></i>
                                <?php echo t('strength_training'); ?>
                            </div>
                            <div class="wt-category-item" data-category="cardio">
                                <i class="fas fa-running"></i>
                                <?php echo t('cardio'); ?>
                            </div>
                            <div class="wt-category-item" data-category="Bodyweight">
                                <i class="fas fa-user"></i>
                                <?php echo t('bodyweight'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wt-exercises-column wt-mobile-section" data-section="exercises">
                        <div class="wt-exercise-search">
                            <div class="wt-search-container">
                                <i class="fas fa-search wt-search-icon"></i>
                                <input type="text" class="wt-search-input" id="exerciseSearch" placeholder="<?php echo t('search_exercises'); ?>">
                            </div>
                        </div>
                        
                        <div class="wt-exercises-list" id="exercisesGrid">
                        </div>
                    </div>
                    
                    <div class="wt-selected-column wt-mobile-section" data-section="selected">
                        <h3><?php echo t('selected_exercises'); ?></h3>
                        <div id="selectedExercisesList" class="wt-selected-exercises-list">
                            <div class="empty-selection" id="emptySelection">
                                <p><?php echo t('no_exercises_selected'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wt-modal-footer">
                <button class="wt-btn wt-btn-secondary" id="cancelTemplate"><?php echo t('cancel'); ?></button>
            </div>
        </div>
    </div>
    
    <div class="wt-toast" id="toast"></div>
    <div class="wt-modal-overlay" id="viewTemplateModal">
        <div class="wt-modal-content">
            <div class="wt-modal-header">
                <h2 id="viewTemplateTitle"><?php echo t('view_template'); ?></h2>
                <button class="wt-modal-close" id="closeViewModal">&times;</button>
            </div>
            
            <div class="wt-mobile-tabs">
                <div class="wt-mobile-tab active" data-tab="view-details"><?php echo t('details'); ?></div>
                <div class="wt-mobile-tab" data-tab="view-exercises"><?php echo t('exercises'); ?></div>
            </div>
            
            <div class="wt-modal-body" style="height: 700px; overflow-y: auto;">
                <div class="wt-template-details wt-mobile-section active" data-section="view-details">
                    <div class="wt-input-group">
                        <label><?php echo t('template_name'); ?></label>
                        <div class="wt-view-field" id="viewName"></div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('description'); ?></label>
                        <div class="wt-view-field" id="viewDescription"></div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('category'); ?></label>
                        <div class="wt-view-field" id="viewCategory"></div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('difficulty_level'); ?></label>
                        <div class="wt-view-field" id="viewDifficulty"></div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('estimated_time'); ?></label>
                        <div class="wt-view-field" id="viewTime"></div>
                    </div>
                </div>
                
                <div class="wt-exercise-selector">
                    <div class="wt-selected-exercises wt-mobile-section" data-section="view-exercises">
                        <h3><?php echo t('exercises'); ?></h3>
                        <div id="viewExercisesList">
                        </div>
                    </div>
                </div>
            </div>
            <div class="wt-modal-footer">
                <button class="wt-btn wt-btn-secondary" id="closeViewBtn"><?php echo t('close'); ?></button>
            </div>
        </div>
    </div>
    
    <div class="wt-modal-overlay" id="editTemplateModal">
        <div class="wt-modal-content">
            <div class="wt-modal-header">
                <h2><?php echo t('edit_template'); ?></h2>
                <div class="wt-modal-header-actions">
                    <button class="wt-save-template-btn" id="updateTemplateHeader">
                        <i class="fas fa-save"></i>
                        <?php echo t('save_changes'); ?>
                    </button>
                    <button class="wt-modal-close" id="closeEditModal">&times;</button>
                </div>
            </div>
            
            <div class="wt-mobile-tabs">
                <div class="wt-mobile-tab active" data-tab="edit-details"><?php echo t('details'); ?></div>
                <div class="wt-mobile-tab" data-tab="edit-categories"><?php echo t('categories'); ?></div>
                <div class="wt-mobile-tab" data-tab="edit-exercises"><?php echo t('exercises'); ?></div>
                <div class="wt-mobile-tab" data-tab="edit-selected"><?php echo t('selected'); ?></div>
            </div>
            
            <div class="wt-modal-body">
                <div class="wt-template-details wt-mobile-section active" data-section="edit-details">
                    <div class="wt-input-group">
                        <label for="editWorkoutName"><?php echo t('template_name'); ?></label>
                        <input type="text" id="editWorkoutName">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="editWorkoutDescription"><?php echo t('description_optional'); ?></label>
                        <textarea id="editWorkoutDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('categories'); ?></label>
                        <div class="wt-categories">
                            <div class="wt-category" data-category="Strength Training"><?php echo t('strength_training'); ?></div>
                            <div class="wt-category" data-category="cardio"><?php echo t('cardio'); ?></div>
                            <div class="wt-category" data-category="Bodyweight"><?php echo t('bodyweight'); ?></div>
                        </div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label><?php echo t('difficulty_level'); ?></label>
                        <input type="range" min="1" max="3" value="2" id="editDifficultySlider">
                        <div class="wt-slider-labels">
                            <span><?php echo t('beginner'); ?></span>
                            <span><?php echo t('intermediate'); ?></span>
                            <span><?php echo t('advanced'); ?></span>
                        </div>
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="editSetsPerExercise"><?php echo t('sets_per_exercise'); ?></label>
                        <input type="number" id="editSetsPerExercise" min="1" max="10" value="3">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="editRestTimePerExercise"><?php echo t('rest_time_between_exercises'); ?></label>
                        <input type="number" id="editRestTimePerExercise" min="0" max="10" value="1" step="0.5">
                    </div>
                    
                    <div class="wt-input-group">
                        <label for="editEstimatedTime"><?php echo t('estimated_time'); ?></label>
                        <input type="number" id="editEstimatedTime" min="5" max="180" value="45" readonly>
                    </div>
                    
                    <input type="hidden" id="editTemplateId">
                </div>
                
                <div class="wt-exercise-selector">
                    <div class="wt-categories-column wt-mobile-section" data-section="edit-categories">
                        <h3><?php echo t('categories'); ?></h3>
                        <div class="wt-category-list">
                            <div class="wt-category-item active" data-category="Strength Training">
                                <i class="fas fa-dumbbell"></i>
                                <?php echo t('strength_training'); ?>
                            </div>
                            <div class="wt-category-item" data-category="cardio">
                                <i class="fas fa-running"></i>
                                <?php echo t('cardio'); ?>
                            </div>
                            <div class="wt-category-item" data-category="Bodyweight">
                                <i class="fas fa-user"></i>
                                <?php echo t('bodyweight'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wt-exercises-column wt-mobile-section" data-section="edit-exercises">
                        <div class="wt-exercise-search">
                            <div class="wt-search-container">
                                <i class="fas fa-search wt-search-icon"></i>
                                <input type="text" class="wt-search-input" id="editExerciseSearch" placeholder="<?php echo t('search_exercises'); ?>">
                            </div>
                        </div>
                        
                        <div class="wt-exercises-list" id="editExercisesGrid">
                        </div>
                    </div>
                    
                    <div class="wt-selected-column wt-mobile-section" data-section="edit-selected">
                        <h3><?php echo t('selected_exercises'); ?></h3>
                        <div id="editSelectedExercisesList" class="wt-selected-exercises-list">
                            <div class="empty-selection" id="editEmptySelection">
                                <p><?php echo t('no_exercises_selected'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wt-modal-footer">
                <button class="wt-btn wt-btn-secondary" id="cancelEdit"><?php echo t('cancel'); ?></button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let selectedExercises = [];
            let editSelectedExercises = [];
            let currentCategory = 'Strength Training';
            let editCurrentCategory = 'Strength Training';
            let exerciseTime = 1;
            
            const translations = {
                beginner: '<?php echo t('beginner'); ?>',
                intermediate: '<?php echo t('intermediate'); ?>',
                advanced: '<?php echo t('advanced'); ?>',
                minutes: '<?php echo t('minutes'); ?>',
                sets: '<?php echo t('sets'); ?>',
                reps: '<?php echo t('reps'); ?>',
                rest: '<?php echo t('rest'); ?>',
                position: '<?php echo t('position'); ?>',
                sec_rest: '<?php echo t('sec_rest'); ?>',
                notes: '<?php echo t('notes'); ?>',
                edit: '<?php echo t('edit'); ?>',
                loading: '<?php echo t('loading'); ?>',
                no_exercises_found: '<?php echo t('no_exercises_found'); ?>',
                error_loading_exercises: '<?php echo t('error_loading_exercises'); ?>',
                no_equipment: '<?php echo t('no_equipment'); ?>',
                none: '<?php echo t('none'); ?>',
                added: '<?php echo t('added'); ?>',
                to_your_template: '<?php echo t('to_your_template'); ?>',
                no_personal_templates: '<?php echo t('no_personal_templates'); ?>',
                click_create_template: '<?php echo t('click_create_template'); ?>',
                no_description: '<?php echo t('no_description'); ?>',
                cancel: '<?php echo t('cancel'); ?>',
                save: '<?php echo t('save'); ?>'
            };
            
            $('.wt-toggle-btn').on('click', function() {
                const viewType = $(this).data('view');
                
                $('.wt-toggle-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.wt-templates-view').removeClass('active');
                if (viewType === 'my-templates') {
                    $('#myTemplatesView').addClass('active');
                } else if (viewType === 'admin-templates') {
                    $('#adminTemplatesView').addClass('active');
                }
            });
            
            $('.wt-mobile-tab').on('click', function() {
                const tabTarget = $(this).data('tab');
                const tabContainer = $(this).closest('.wt-modal-content');
                
                tabContainer.find('.wt-mobile-tab').removeClass('active');
                $(this).addClass('active');
                
                tabContainer.find('.wt-mobile-section').removeClass('active');
                tabContainer.find(`.wt-mobile-section[data-section="${tabTarget}"]`).addClass('active');
            });
            
            $('<style>').html(`
                .wt-view-field {
                    background: var(--background);
                    padding: 12px;
                    border-radius: 6px;
                    margin-bottom: 5px;
                    min-height: 20px;
                }
                
                .wt-view-exercise {
                    background: var(--background);
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 10px;
                }
                
                .wt-view-exercise-header {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                
                .wt-view-exercise-details {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 15px;
                    font-size: 14px;
                    color: #aaa;
                }
                
                .wt-view-exercise-detail {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .wt-view-exercise-notes {
                    margin-top: 10px;
                    font-style: italic;
                    color: #aaa;
                }
            `).appendTo('head');
            
            loadExercises();
            
            $(document).on('click', '.wt-template-actions-menu', function(e) {
                e.stopPropagation();
                const dropdown = $(this).siblings('.wt-template-actions-dropdown');
                $('.wt-template-actions-dropdown').not(dropdown).removeClass('active');
                dropdown.toggleClass('active');
            });
            
            $(document).on('click', function() {
                $('.wt-template-actions-dropdown').removeClass('active');
            });
            
            $(document).on('click', '.view-template', function() {
                const templateId = $(this).closest('.wt-template-card').data('id');
                viewTemplate(templateId);
            });
            
            $(document).on('click', '.edit-template', function() {
                const templateId = $(this).closest('.wt-template-card').data('id');
                editTemplate(templateId);
            });
            
            $(document).on('click', '.template-delete', function() {
                const templateId = $(this).closest('.wt-template-card').data('id');
                if (confirm('<?php echo t('confirm_delete_template'); ?>')) {
                    deleteTemplate(templateId);
                }
            });
            
            function viewTemplate(templateId) {
                $.ajax({
                    url: '?action=get_template',
                    method: 'GET',
                    data: { template_id: templateId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showViewTemplateModal(data);
                        } else {
                            showToast(data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('<?php echo t('error_loading_template'); ?>', 'error');
                    }
                });
            }
            
            function showViewTemplateModal(template) {
                $('#viewName').text(template.name);
                $('#viewDescription').text(template.description || translations.no_description);
                $('#viewCategory').text(template.category);
                
                let difficulty = translations.intermediate;
                if (template.difficulty === 'beginner') difficulty = translations.beginner;
                if (template.difficulty === 'advanced') difficulty = translations.advanced;
                
                $('#viewDifficulty').text(difficulty);
                $('#viewTime').text(template.estimated_time + ' ' + translations.minutes);
                
                const list = $('#viewExercisesList');
                list.empty();
                
                if (template.exercises.length === 0) {
                    list.html(`<div class="empty-selection"><p><?php echo t('no_exercises_in_template'); ?></p></div>`);
                } else {
                    template.exercises.forEach(function(exercise) {
                        const item = $(`
                            <div class="wt-view-exercise">
                                <div class="wt-view-exercise-header">
                                    <h4>${exercise.name}</h4>
                                    <div class="wt-view-exercise-position">${translations.position}: ${exercise.position}</div>
                                </div>
                                <div class="wt-view-exercise-details">
                                    <div class="wt-view-exercise-detail">
                                        <i class="fas fa-layer-group"></i>
                                        <span>${exercise.sets} ${translations.sets}</span>
                                    </div>
                                    <div class="wt-view-exercise-detail">
                                        <i class="fas fa-stopwatch"></i>
                                        <span>${exercise.rest_time} ${translations.sec_rest}</span>
                                    </div>
                                    <div class="wt-view-exercise-detail">
                                        <i class="fas fa-dumbbell"></i>
                                        <span>${exercise.muscle}</span>
                                    </div>
                                    <div class="wt-view-exercise-detail">
                                        <i class="fas fa-cog"></i>
                                        <span>${exercise.equipment}</span>
                                    </div>
                                </div>
                                ${exercise.notes ? `<div class="wt-view-exercise-notes">${translations.notes}: ${exercise.notes}</div>` : ''}
                            </div>
                        `);
                        
                        list.append(item);
                    });
                }
                
                $('#viewTemplateModal').fadeIn(300);
            }
            
            $('#closeViewModal, #closeViewBtn').on('click', function() {
                $('#viewTemplateModal').fadeOut(300);
            });
            
            function editTemplate(templateId) {
                $.ajax({
                    url: '?action=get_template',
                    method: 'GET',
                    data: { template_id: templateId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showEditTemplateModal(data);
                        } else {
                            showToast(data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('<?php echo t('error_loading_template'); ?>', 'error');
                    }
                });
            }
            
            function showEditTemplateModal(template) {
                $('#editWorkoutName').val(template.name);
                $('#editWorkoutDescription').val(template.description || '');
                $('#editTemplateId').val(template.id);
                
                let difficultyValue = 2;
                if (template.difficulty === 'beginner') difficultyValue = 1;
                if (template.difficulty === 'advanced') difficultyValue = 3;
                $('#editDifficultySlider').val(difficultyValue);
                
                $('.wt-category').removeClass('active');
                $(`.wt-category[data-category="${template.category}"]`).addClass('active');
                
                if (template.exercises.length > 0) {
                    const totalSets = template.exercises.reduce((sum, ex) => sum + parseInt(ex.sets), 0);
                    const totalRestTime = template.exercises.reduce((sum, ex) => sum + parseInt(ex.rest_time), 0);
                    const avgSets = Math.round(totalSets / template.exercises.length);
                    const avgRestTime = totalRestTime / template.exercises.length / 60; 
                    $('#editSetsPerExercise').val(avgSets);
                    $('#editRestTimePerExercise').val(avgRestTime.toFixed(1));
                } else {
                    $('#editSetsPerExercise').val(3);
                    $('#editRestTimePerExercise').val(1);
                }
                
                editSelectedExercises = template.exercises.map(ex => ({
                    exercise_id: ex.exercise_id,
                    name: ex.name,
                    sets: ex.sets,
                    rest_time: ex.rest_time,
                    notes: ex.notes,
                    muscle: ex.muscle,
                    equipment: ex.equipment
                }));
                
                renderEditSelectedExercises();
                loadEditExercises();
                updateEditEstimatedTime();
                
                $('#editTemplateModal').fadeIn(300);
            }
            
            $('#closeEditModal, #cancelEdit').on('click', function() {
                $('#editTemplateModal').fadeOut(300);
            });
            
            function deleteTemplate(templateId) {
                if (confirm('<?php echo t('confirm_delete_template'); ?>')) {
                    $.ajax({
                        url: '?action=delete_template',
                        method: 'POST',
                        data: { template_id: templateId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showToast(response.message, 'success');
                                $(`.wt-template-card[data-id="${templateId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    if ($('.wt-template-card').length === 0) {
                                        $('.wt-templates-grid').html(`
                                            <div class="no-templates">
                                                <p>${translations.no_personal_templates}</p>
                                                <p>${translations.click_create_template}</p>
                                            </div>
                                        `);
                                    }
                                });
                            } else {
                                showToast(response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            showToast('<?php echo t('error_deleting_template'); ?>', 'error');
                        }
                    });
                }
            }
            
            function loadEditExercises(query = '') {
                $('#editExercisesGrid').html(`<div class="loading"><i class="fas fa-spinner fa-spin"></i> ${translations.loading}...</div>`);
                
                $.ajax({
                    url: '?action=search_exercises',
                    method: 'GET',
                    data: { 
                        query: query,
                        category: editCurrentCategory
                    },
                    dataType: 'json',
                    success: function(data) {
                        renderEditExercises(data);
                    },
                    error: function(xhr, status, error) {
                        $('#editExercisesGrid').html('<div class="error"><?php echo t('error_loading_exercises'); ?></div>');
                    }
                });
            }
            
            function renderEditExercises(exercises) {
                const grid = $('#editExercisesGrid');
                grid.empty();
                
                if (exercises.length === 0) {
                    grid.html(`<div class="no-results">${translations.no_exercises_found}</div>`);
                    return;
                }
                
                exercises.forEach(function(exercise) {
                    const item = $(`
                        <div class="wt-exercise-item" data-id="${exercise.id}">
                            <h4>${exercise.name}</h4>
                            <div class="wt-exercise-details">
                                <div><i class="fas fa-dumbbell"></i> ${exercise.primary_muscle}</div>
                                <div><i class="fas fa-cog"></i> ${exercise.equipment || translations.no_equipment}</div>
                            </div>
                        </div>
                    `);
                    
                    item.on('click', function() {
                        addExerciseToEditTemplate(exercise);
                    });
                    
                    grid.append(item);
                });
            }
            
            function addExerciseToEditTemplate(exercise) {
                const exists = editSelectedExercises.some(ex => ex.exercise_id === exercise.id);
                
                if (exists) {
                    showToast('<?php echo t('this_exercise_is_already_in_your_template'); ?>', 'error');
                    return;
                }
                
                const restTimePerExercise = parseInt($('#editRestTimePerExercise').val() * 60) || 60;
                const setsPerExercise = parseInt($('#editSetsPerExercise').val()) || 3;
                
                const exerciseObj = {
                    exercise_id: exercise.id,
                    name: exercise.name,
                    sets: setsPerExercise,
                    rest_time: restTimePerExercise,
                    notes: '',
                    muscle: exercise.primary_muscle,
                    equipment: exercise.equipment || translations.none
                };
                
                editSelectedExercises.push(exerciseObj);
                renderEditSelectedExercises();
                updateEditEstimatedTime();
                
                showToast(`${translations.added} ${exercise.name} ${translations.to_your_template}`, 'success');
            }
            
            function renderEditSelectedExercises() {
                const list = $('#editSelectedExercisesList');
                const empty = $('#editEmptySelection');
                
                if (editSelectedExercises.length === 0) {
                    empty.show();
                    return;
                }
                
                empty.hide();
                list.empty();
                
                editSelectedExercises.forEach(function(exercise, index) {
                    const item = $(`
                        <div class="wt-selected-exercise" data-index="${index}">
                            <div class="wt-exercise-header">
                                <div class="wt-exercise-info">
                                    <div class="wt-drag-handle"><i class="fas fa-grip-vertical"></i></div>
                                    <div class="wt-exercise-name">${index + 1}. ${exercise.name}</div>
                                </div>
                                <div class="wt-exercise-meta">
                                    ${exercise.sets} ${translations.sets} × 12 ${translations.reps}
                                    <br>${translations.rest}: ${exercise.rest_time}s
                                </div>
                            </div>
                            <div class="wt-exercise-controls">
                                <button class="edit-exercise-item"><i class="fas fa-cog"></i></button>
                                <button class="remove-exercise-item"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    `);
                    
                    item.find('.edit-exercise-item').on('click', function() {
                        editExerciseItem(index);
                    });
                    
                    item.find('.remove-exercise-item').on('click', function() {
                        removeEditExercise(index);
                    });
                    
                    list.append(item);
                });
                
                new Sortable(document.getElementById('editSelectedExercisesList'), {
                    handle: '.wt-drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        const item = editSelectedExercises[evt.oldIndex];
                        editSelectedExercises.splice(evt.oldIndex, 1);
                        editSelectedExercises.splice(evt.newIndex, 0, item);
                        renderEditSelectedExercises();
                    }
                });
            }
            
            function editExerciseItem(index) {
                const exercise = editSelectedExercises[index];
                
                const modal = $(`
                    <div class="wt-edit-modal">
                        <div class="wt-edit-modal-content">
                            <h3>${translations.edit} ${exercise.name}</h3>
                            <div class="wt-input-group">
                                <label>${translations.sets}</label>
                                <input type="number" id="editItemSets" value="${exercise.sets}" min="1" max="10">
                            </div>
                            <div class="wt-input-group">
                                <label>${translations.rest_time}</label>
                                <input type="number" id="editItemRest" value="${exercise.rest_time}" min="0" max="300">
                            </div>
                            <div class="wt-input-group">
                                <label>${translations.notes}</label>
                                <textarea id="editItemNotes">${exercise.notes}</textarea>
                            </div>
                            <div class="wt-modal-actions">
                                <button id="cancelEditItem">${translations.cancel}</button>
                                <button id="saveEditItem">${translations.save}</button>
                            </div>
                        </div>
                    </div>
                `).css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0,0,0,0.8)',
                    'z-index': '2000',
                    'display': 'flex',
                    'justify-content': 'center',
                    'align-items': 'center'
                });
                
                modal.find('.wt-edit-modal-content').css({
                    'background': 'var(--card-bg)',
                    'padding': '20px',
                    'border-radius': '10px',
                    'width': '400px'
                });
                
                modal.find('.wt-modal-actions').css({
                    'display': 'flex',
                    'justify-content': 'flex-end',
                    'gap': '10px',
                    'margin-top': '20px'
                });
                
                modal.find('button').css({
                    'padding': '8px 15px',
                    'border-radius': '4px',
                    'border': 'none',
                    'cursor': 'pointer'
                });
                
                modal.find('#saveEditItem').css({
                    'background': 'var(--primary)',
                    'color': 'white'
                });
                
                $('body').append(modal);
                
                modal.find('#cancelEditItem').on('click', function() {
                    modal.remove();
                });
                
                modal.find('#saveEditItem').on('click', function() {
                    editSelectedExercises[index].sets = parseInt(modal.find('#editItemSets').val());
                    editSelectedExercises[index].rest_time = parseInt(modal.find('#editItemRest').val());
                    editSelectedExercises[index].notes = modal.find('#editItemNotes').val();
                    
                    modal.remove();
                    renderEditSelectedExercises();
                });
            }
            
            function removeEditExercise(index) {
                editSelectedExercises.splice(index, 1);
                renderEditSelectedExercises();
                updateEditEstimatedTime();
            }
            
            $('#editTemplateModal .wt-category-item').on('click', function() {
                $('#editTemplateModal .wt-category-item').removeClass('active');
                $(this).addClass('active');
                
                editCurrentCategory = $(this).data('category');
                loadEditExercises();
            });
            
            $('#editExerciseSearch').on('input', function() {
                const query = $(this).val().trim();
                
                if (query.length >= 2 || query.length === 0) {
                    loadEditExercises(query);
                }
            });
            
            $('#editRestTimePerExercise, #editSetsPerExercise').on('change input', function() {
                updateEditEstimatedTime();
                
                const restTimePerExercise = parseInt($('#editRestTimePerExercise').val() * 60) || 60;
                const setsPerExercise = parseInt($('#editSetsPerExercise').val()) || 3;
                
                editSelectedExercises.forEach(function(exercise) {
                    exercise.rest_time = restTimePerExercise;
                    exercise.sets = setsPerExercise;
                });
            });
            
            function updateEditEstimatedTime() {
                const restTimePerExercise = parseFloat($('#editRestTimePerExercise').val()) || 0;
                const setsPerExercise = parseInt($('#editSetsPerExercise').val()) || 3;
                const exerciseCount = editSelectedExercises.length;
                
                if (exerciseCount === 0) {
                    $('#editEstimatedTime').val(0);
                    return;
                }
                
                const totalTime = (exerciseCount * exerciseTime * setsPerExercise) + (exerciseCount * restTimePerExercise * setsPerExercise);
                $('#editEstimatedTime').val(Math.round(totalTime));
            }
            
            $('#createTemplate').on('click', function() {
                $('#createTemplateModal').fadeIn(300);
                loadExercises();
                updateEstimatedTime();
            });
            
            $('#closeModal, #cancelTemplate').on('click', function() {
                $('#createTemplateModal').fadeOut(300);
            });
            
            $('.wt-category-item').on('click', function() {
                $('.wt-category-item').removeClass('active');
                $(this).addClass('active');
                
                currentCategory = $(this).data('category');
                loadExercises();
            });
            
            $('.wt-category').on('click', function() {
                $('.wt-category').removeClass('active');
                $(this).addClass('active');
            });
            
            $('#restTimePerExercise, #setsPerExercise').on('change input', function() {
                updateEstimatedTime();
                
                const restTimePerExercise = parseInt($('#restTimePerExercise').val() * 60) || 60;
                const setsPerExercise = parseInt($('#setsPerExercise').val()) || 3;
                
                selectedExercises.forEach(function(exercise) {
                    exercise.rest_time = restTimePerExercise;
                    exercise.sets = setsPerExercise;
                });
            });
            
            function updateEstimatedTime() {
                const restTimePerExercise = parseFloat($('#restTimePerExercise').val()) || 0;
                const setsPerExercise = parseInt($('#setsPerExercise').val()) || 3;
                const exerciseCount = selectedExercises.length;
                
                if (exerciseCount === 0) {
                    $('#estimatedTime').val(0);
                    return;
                }
                
                const totalTime = (exerciseCount * exerciseTime * setsPerExercise) + (exerciseCount * restTimePerExercise * setsPerExercise);
                $('#estimatedTime').val(Math.round(totalTime));
            }
            
            $('#exerciseSearch').on('input', function() {
                const query = $(this).val().trim();
                
                if (query.length >= 2 || query.length === 0) {
                    loadExercises(query);
                }
            });
            
            function loadExercises(query = '') {
                $('#exercisesGrid').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> <?php echo t('loading'); ?>...</div>');
                
                $.ajax({
                    url: '?action=search_exercises',
                    method: 'GET',
                    data: { 
                        query: query,
                        category: currentCategory
                    },
                    dataType: 'json',
                    success: function(data) {
                        renderExercises(data);
                    },
                    error: function(xhr, status, error) {
                        $('#exercisesGrid').html('<div class="error"><?php echo t('error_loading_exercises'); ?></div>');
                        console.error('Error:', error);
                    }
                });
            }
            
            function renderExercises(exercises) {
                const grid = $('#exercisesGrid');
                grid.empty();
                
                if (exercises.length === 0) {
                    grid.html('<div class="no-results"><?php echo t('no_exercises_found'); ?></div>');
                    return;
                }
                
                exercises.forEach(function(exercise) {
                    const item = $(`
                        <div class="wt-exercise-item" data-id="${exercise.id}">
                            <h4>${exercise.name}</h4>
                            <div class="wt-exercise-details">
                                <div><i class="fas fa-dumbbell"></i> ${exercise.primary_muscle}</div>
                                <div><i class="fas fa-cog"></i> ${exercise.equipment || '<?php echo t('no_equipment'); ?>'}</div>
                            </div>
                        </div>
                    `);
                    
                    item.on('click', function() {
                        addExerciseToTemplate(exercise);
                    });
                    
                    grid.append(item);
                });
            }
            
            function addExerciseToTemplate(exercise) {
                const exists = selectedExercises.some(ex => ex.exercise_id === exercise.id);
                
                if (exists) {
                    showToast('<?php echo t('this_exercise_is_already_in_your_template'); ?>', 'error');
                    return;
                }
                
                const restTimePerExercise = parseInt($('#restTimePerExercise').val() * 60) || 60;
                const setsPerExercise = parseInt($('#setsPerExercise').val()) || 3;
                
                const exerciseObj = {
                    exercise_id: exercise.id,
                    name: exercise.name,
                    sets: setsPerExercise,
                    rest_time: restTimePerExercise,
                    notes: '',
                    muscle: exercise.primary_muscle,
                    equipment: exercise.equipment || '<?php echo t('none'); ?>'
                };
                
                selectedExercises.push(exerciseObj);
                renderSelectedExercises();
                updateEstimatedTime();
                
                showToast(`<?php echo t('added'); ?> ${exercise.name} <?php echo t('to_your_template'); ?>`, 'success');
            }
            
            function renderSelectedExercises() {
                const list = $('#selectedExercisesList');
                const empty = $('#emptySelection');
                
                if (selectedExercises.length === 0) {
                    empty.show();
                    return;
                }
                
                empty.hide();
                list.empty();
                
                selectedExercises.forEach(function(exercise, index) {
                    const item = $(`
                        <div class="wt-selected-exercise" data-index="${index}">
                            <div class="wt-exercise-header">
                                <div class="wt-exercise-info">
                                    <div class="wt-drag-handle"><i class="fas fa-grip-vertical"></i></div>
                                    <div class="wt-exercise-name">${index + 1}. ${exercise.name}</div>
                                </div>
                                <div class="wt-exercise-meta">
                                    ${exercise.sets} <?php echo t('sets'); ?> × 12 <?php echo t('reps'); ?>
                                    <br><?php echo t('rest'); ?>: ${exercise.rest_time}s
                                </div>
                            </div>
                            <div class="wt-exercise-controls">
                                <button class="edit-exercise"><i class="fas fa-cog"></i></button>
                                <button class="remove-exercise"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    `);
                    
                    item.find('.edit-exercise').on('click', function() {
                        editExercise(index);
                    });
                    
                    item.find('.remove-exercise').on('click', function() {
                        removeExercise(index);
                    });
                    
                    list.append(item);
                });
                
                new Sortable(document.getElementById('selectedExercisesList'), {
                    handle: '.wt-drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        const item = selectedExercises[evt.oldIndex];
                        selectedExercises.splice(evt.oldIndex, 1);
                        selectedExercises.splice(evt.newIndex, 0, item);
                        renderSelectedExercises();
                    }
                });
            }
            
            function editExercise(index) {
                const exercise = selectedExercises[index];
                
                const modal = $(`
                    <div class="wt-edit-modal">
                        <div class="wt-edit-modal-content">
                            <h3><?php echo t('edit'); ?> ${exercise.name}</h3>
                            <div class="wt-input-group">
                                <label><?php echo t('sets'); ?></label>
                                <input type="number" id="editSets" value="${exercise.sets}" min="1" max="10">
                            </div>
                            <div class="wt-input-group">
                                <label><?php echo t('rest_time'); ?></label>
                                <input type="number" id="editRest" value="${exercise.rest_time}" min="0" max="300">
                            </div>
                            <div class="wt-input-group">
                                <label><?php echo t('notes'); ?></label>
                                <textarea id="editNotes">${exercise.notes}</textarea>
                            </div>
                            <div class="wt-modal-actions">
                                <button id="cancelEdit"><?php echo t('cancel'); ?></button>
                                <button id="saveEdit"><?php echo t('save'); ?></button>
                            </div>
                        </div>
                    </div>
                `).css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0,0,0,0.8)',
                    'z-index': '2000',
                    'display': 'flex',
                    'justify-content': 'center',
                    'align-items': 'center'
                });
                
                modal.find('.wt-edit-modal-content').css({
                    'background': 'var(--card-bg)',
                    'padding': '20px',
                    'border-radius': '10px',
                    'width': '400px'
                });
                
                modal.find('.wt-modal-actions').css({
                    'display': 'flex',
                    'justify-content': 'flex-end',
                    'gap': '10px',
                    'margin-top': '20px'
                });
                
                modal.find('button').css({
                    'padding': '8px 15px',
                    'border-radius': '4px',
                    'border': 'none',
                    'cursor': 'pointer'
                });
                
                modal.find('#saveEdit').css({
                    'background': 'var(--primary)',
                    'color': 'white'
                });
                
                $('body').append(modal);
                
                modal.find('#cancelEdit').on('click', function() {
                    modal.remove();
                });
                
                modal.find('#saveEdit').on('click', function() {
                    selectedExercises[index].sets = parseInt(modal.find('#editSets').val());
                    selectedExercises[index].rest_time = parseInt(modal.find('#editRest').val());
                    selectedExercises[index].notes = modal.find('#editNotes').val();
                    
                    modal.remove();
                    renderSelectedExercises();
                });
            }
            
            function removeExercise(index) {
                selectedExercises.splice(index, 1);
                renderSelectedExercises();
                updateEstimatedTime();
            }
            
            $('#schedule').on('click', function() {
                alert('<?php echo t('calendar_functionality_would_be_implemented_here'); ?>');
            });
            
            $('#saveTemplateHeader').on('click', function() {
                const name = $('#workoutName').val().trim();
                const description = $('#workoutDescription').val().trim();
                const estimatedTime = $('#estimatedTime').val();
                const difficultyVal = $('#difficultySlider').val();
                
                let difficulty = '<?php echo t('intermediate'); ?>';
                if (difficultyVal == 1) difficulty = '<?php echo t('beginner'); ?>';
                if (difficultyVal == 3) difficulty = '<?php echo t('advanced'); ?>';
                
                const category = $('.wt-category.active').data('category');
                
                if (!name) {
                    showToast('<?php echo t('please_enter_a_template_name'); ?>', 'error');
                    return;
                }
                
                if (selectedExercises.length === 0) {
                    showToast('<?php echo t('please_add_at_least_one_exercise'); ?>', 'error');
                    return;
                }
                
                const data = {
                    save_workout: 1,
                    workout_name: name,
                    workout_description: description,
                    difficulty: difficulty,
                    estimated_time: estimatedTime,
                    category: category,
                    exercises_json: JSON.stringify(selectedExercises)
                };
                
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('<?php echo t('error_saving_template'); ?>', 'error');
                        console.error('Error:', error);
                    }
                });
            });
            
            $('#updateTemplateHeader').on('click', function() {
                const templateId = $('#editTemplateId').val();
                const name = $('#editWorkoutName').val().trim();
                const description = $('#editWorkoutDescription').val().trim();
                const estimatedTime = $('#editEstimatedTime').val();
                const difficultyVal = $('#editDifficultySlider').val();
                
                let difficulty = '<?php echo t('intermediate'); ?>';
                if (difficultyVal == 1) difficulty = '<?php echo t('beginner'); ?>';
                if (difficultyVal == 3) difficulty = '<?php echo t('advanced'); ?>';
                
                const category = $('.wt-category.active').data('category');
                
                if (!name) {
                    showToast('<?php echo t('please_enter_a_template_name'); ?>', 'error');
                    return;
                }
                
                if (editSelectedExercises.length === 0) {
                    showToast('<?php echo t('please_add_at_least_one_exercise'); ?>', 'error');
                    return;
                }
                
                const data = {
                    workout_name: name,
                    workout_description: description,
                    difficulty: difficulty,
                    estimated_time: estimatedTime,
                    category: category,
                    exercises_json: JSON.stringify(editSelectedExercises)
                };
                
                $.ajax({
                    url: '?action=update_template',
                    method: 'POST',
                    data: { ...data, template_id: templateId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            $('#editTemplateModal').fadeOut(300);
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('<?php echo t('error_updating_template'); ?>', 'error');
                    }
                });
            });
            
            function showToast(message, type) {
                const toast = $('#toast');
                toast.text(message);
                toast.removeClass('success error');
                
                if (type) {
                    toast.addClass(type);
                }
                
                toast.fadeIn();
                
                setTimeout(function() {
                    toast.fadeOut();
                }, 3000);
            }
            
            if (localStorage.getItem('open_edit_template_modal') === 'true') {
                const editTemplateData = JSON.parse(localStorage.getItem('edit_template_data') || '{}');
                if (editTemplateData.id) {
                    const templateId = editTemplateData.id;
                    console.log("Opening edit modal for template ID:", templateId);
                    
                    setTimeout(() => {
                        const editBtn = $(`.wt-template-card[data-id="${templateId}"] .edit-template, .wt-admin-template[data-id="${templateId}"] .edit-template`);
                        if (editBtn.length) {
                            editBtn.click();
                            console.log("Edit button clicked successfully");
                        } else {
                            console.log("Edit button not found, trying to open modal manually");
                            editTemplate(templateId);
                        }
                        
                        localStorage.removeItem('open_edit_template_modal');
                        localStorage.removeItem('edit_template_data');
                    }, 500);
                }
            }
        });
    </script>
</body>
</html>