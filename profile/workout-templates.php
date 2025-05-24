<?php

require_once 'profile_access_control.php';

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
            primary_muscle, secondary_muscles, difficulty, 
            time_required, calories_burned, video_url, 
            image_url, thumbnail_url 
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

function getTemplateDetails($templateId) {
    global $conn;
    
    if (!$templateId) {
        return ['success' => false, 'message' => 'Invalid template ID'];
    }
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id, name, description, difficulty, estimated_time, category, created_at, updated_at 
                          FROM workout_templates 
                          WHERE id = ? AND user_id = ?");
    
    $stmt->bind_param("ii", $templateId, $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Template not found'];
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Templates | FitTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--dark);
            color: #fff;
            min-height: 100vh;
            line-height: 1.6;
            display: flex;
            font-family: 'Poppins', sans-serif;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 28px;
            color: #fff;
            margin-bottom: 8px;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: var(--card-bg);
            border: none;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: var(--card-highlight);
        }
        
        .action-btn i {
            font-size: 1.2em;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .template-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.2s;
            position: relative;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .template-header {
            margin-bottom: 15px;
        }
        
        .template-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .template-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #aaa;
            font-size: 14px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .difficulty-dots {
            display: flex;
            gap: 5px;
        }
        
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #aaa;
        }
        
        .dot.active {
            background: var(--primary);
        }
        
        .template-actions-menu {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            color: #aaa;
        }
        
        .template-actions-menu:hover {
            color: #fff;
        }
        
        .template-actions-dropdown {
            position: absolute;
            top: 40px;
            right: 15px;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 10;
            display: none;
        }
        
        .template-actions-dropdown.active {
            display: block;
        }
        
        .template-dropdown-item {
            padding: 12px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .template-dropdown-item:hover {
            background: var(--card-highlight);
        }
        
        .template-dropdown-item:first-child {
            border-radius: 8px 8px 0 0;
        }
        
        .template-dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        .template-dropdown-item.delete {
            color: var(--danger);
        }
        
        .last-used {
            margin-top: 15px;
            font-size: 13px;
            color: #aaa;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        
        .modal-content {
            width: 90%;
            max-width: 1100px;
            height: 85vh;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .modal-header {
            padding: 10px 10px 10px 20px;
            border-bottom: 1px solid #394150;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            color: #fff;
            font-size: 22px;
        }
        
        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .save-template-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .save-template-btn:hover {
            background: #2980b9;
        }
        
        .save-template-btn i {
            font-size: 14px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #aaa;
            font-size: 22px;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #fff;
        }
        
        .modal-body {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        .template-details {
            width: 35%;
            padding: 20px;
            border-right: 1px solid #394150;
            overflow-y: auto;
        }
        
        .exercise-selector {
            width: 65%;
            display: flex;
            flex-direction: row; 
        }
        
        .categories-column {
            width: 25%;
            border-right: 1px solid #394150;
            padding: 15px;
            overflow-y: auto;
            background-color: var(--card-bg);
        }
        
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        
        .category-item {
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }
        
        .category-item:hover {
            background-color: var(--card-highlight);
        }
        
        .category-item.active {
            background-color: var(--primary);
        }
        
        .category-item i {
            font-size: 1.1em;
        }
        
        .exercises-column {
            width: 35%;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #394150;
            background-color: var(--background);
        }
        
        .exercise-search {
            padding: 15px;
            border-bottom: 1px solid #394150;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #394150;
            background: var(--card-bg);
            color: #fff;
            padding-left: 40px;
        }
        
        .search-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .exercises-list {
            padding: 15px;
            overflow-y: auto;
            height: calc(100% - 70px);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .exercise-item {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .exercise-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .exercise-item h4 {
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .exercise-details {
            display: flex;
            gap: 12px;
            font-size: 14px;
            color: #aaa;
        }
        
        .exercise-details div {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .selected-column {
            width: 40%;
            padding: 15px;
            overflow-y: auto;
            background-color: var(--background);
        }
        
        .selected-exercises-list {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto;
            max-height: 650px;
        }
        
        .selected-exercise {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .exercise-header {
            margin-bottom: 10px;
        }
        
        .exercise-name {
            font-weight: 600;
        }
        
        .exercise-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .exercise-meta {
            color: #aaa;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .drag-handle {
            color: #aaa;
            cursor: move;
        }
        
        .exercise-controls {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            justify-content: flex-end;
        }
        
        .exercise-controls button {
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
            font-size: 16px;
        }
        
        .exercise-controls button:hover {
            color: #fff;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
        }
        
        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #394150;
            background: var(--background);
            color: #fff;
        }
        
        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .category {
            background: var(--background);
            border-radius: 20px;
            padding: 5px 12px;
            cursor: pointer;
        }
        
        .category.active {
            background: var(--primary);
            color: white;
        }
        
        .difficulty-slider {
            margin-bottom: 20px;
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            color: #aaa;
            font-size: 14px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #394150;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-secondary {
            background: #394150;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary:hover {
            background: #4a546a;
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            z-index: 1000;
            display: none;
        }
        
        .toast.success {
            background: var(--secondary);
        }
        
        .toast.error {
            background: var(--danger);
        }
        
        .view-field {
            background: var(--background);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            min-height: 20px;
        }
        
        .view-exercise {
            background: var(--background);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .view-exercise-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .view-exercise-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 14px;
            color: #aaa;
        }
        
        .view-exercise-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-exercise-notes {
            margin-top: 10px;
            font-style: italic;
            color: #aaa;
        }
        
        .mobile-tabs {
            display: none;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .main-content {
                padding: 15px;
                width: 100%;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 15px;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 5px;
            }
            
            .template-actions {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
            
            .templates-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .template-card {
                margin-bottom: 0;
                padding: 15px;
                border-radius: 8px;
            }
            
            .template-card:hover {
                transform: none;
            }
            
            .template-title {
                font-size: 16px;
            }
            
            .template-meta {
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
            }
            
            .modal-content {
                width: 100%;
                height: 100%;
                max-width: none;
                border-radius: 0;
            }
            
            .modal-header {
                padding: 15px;
            }
            
            .modal-header h2 {
                font-size: 18px;
            }
            
            .modal-body {
                flex-direction: column;
                height: calc(100% - 120px);
                overflow-y: auto;
            }
            
            .template-details {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #394150;
                padding: 15px;
            }
            
            .exercise-selector {
                width: 100%;
                flex-direction: column;
            }
            
            .categories-column {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #394150;
                padding: 15px;
            }
            
            .category-list {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .category-item {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .exercises-column {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #394150;
                max-height: 100%;
            }
            
            .selected-column {
                width: 100%;
                padding: 15px;
            }

            .exercise-item {
                padding: 12px;
            }
            
            .exercise-item h4 {
                font-size: 15px;
            }
            
            .exercise-details {
                font-size: 13px;
                flex-wrap: wrap;
            }
            
            .toast {
                left: 20px;
                right: 20px;
                width: calc(100% - 40px);
                text-align: center;
            }
            
            #viewTemplateModal .modal-body,
            #editTemplateModal .modal-body {
                flex-direction: column;
            }
            
            #viewTemplateModal .template-details,
            #editTemplateModal .template-details {
                width: 100%;
                border-right: none;
            }
            
            #viewTemplateModal .exercise-selector,
            #editTemplateModal .exercise-selector {
                width: 100%;
            }
            
            .edit-modal-content {
                width: 90% !important;
                max-width: none !important;
            }
            
            .mobile-tabs {
                display: none;
            }
            
            @media (max-width: 768px) {
                .mobile-tabs {
                    display: flex;
                    justify-content: space-around;
                    background: var(--card-highlight);
                    position: sticky;
                    top: 0;
                    z-index: 10;
                    width: 100%;
                    border-bottom: 1px solid #394150;
                }
                
                .mobile-tab {
                    flex: 1;
                    text-align: center;
                    padding: 12px;
                    cursor: pointer;
                    font-weight: 500;
                    color: #aaa;
                }
                
                .mobile-tab.active {
                    color: white;
                    border-bottom: 2px solid var(--primary);
                }
                
                .mobile-section {
                    display: none;
                }
                
                .mobile-section.active {
                    display: block;
                }
            }
        }
    </style>
</head>
<body>
    <?php require_once 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Workout Templates</h1>
            </div>
        </div>
        
        <div class="template-actions">
            <button id="createTemplate" class="action-btn">
                <i class="fas fa-plus"></i> Create Template
            </button>
        </div>
        
        <div class="templates-grid">
            <?php if (empty($templates)): ?>
                <div class="no-templates">
                    <p>You haven't created any workout templates yet.</p>
                    <p>Click "Create Template" to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="template-card" data-id="<?php echo $template['id']; ?>">
                        <div class="template-header">
                            <h3 class="template-title"><?php echo htmlspecialchars($template['name']); ?></h3>
                            <div class="template-meta">
                                <div class="meta-item">
                                    <i class="fas fa-dumbbell"></i>
                                    <span><?php echo $template['exercise_count']; ?> exercises</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $template['estimated_time']; ?> mins</span>
                                </div>
                            </div>
                            <div class="difficulty-dots">
                                <?php
                                    $difficultyLevel = 0;
                                    if ($template['difficulty'] === 'beginner') $difficultyLevel = 1;
                                    if ($template['difficulty'] === 'intermediate') $difficultyLevel = 2;
                                    if ($template['difficulty'] === 'advanced') $difficultyLevel = 3;
                                    
                                    for ($i = 1; $i <= 3; $i++) {
                                        echo '<div class="dot ' . ($i <= $difficultyLevel ? 'active' : '') . '"></div>';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="template-actions-menu">
                            <i class="fas fa-ellipsis-v"></i>
                        </div>
                        
                        <div class="template-actions-dropdown">
                            <div class="template-dropdown-item view-template">
                                <i class="fas fa-eye"></i> View
                            </div>
                            <div class="template-dropdown-item edit-template">
                                <i class="fas fa-edit"></i> Edit
                            </div>
                            <div class="template-dropdown-item delete template-delete">
                                <i class="fas fa-trash"></i> Delete
                            </div>
                        </div>
                        
                        <div class="last-used">
                            Last used: <?php echo date('M d', strtotime($template['updated_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal-overlay" id="createTemplateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Template</h2>
                <div class="modal-header-actions">
                    <button class="save-template-btn" id="saveTemplateHeader">
                        <i class="fas fa-save"></i>
                        Save Template
                    </button>
                    <button class="modal-close" id="closeModal">&times;</button>
                </div>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-tab="details">Details</div>
                <div class="mobile-tab" data-tab="categories">Categories</div>
                <div class="mobile-tab" data-tab="exercises">Exercises</div>
                <div class="mobile-tab" data-tab="selected">Selected</div>
            </div>
            
            <div class="modal-body">
                <div class="template-details mobile-section active" data-section="details">
                    <div class="input-group">
                        <label for="workoutName">Template Name</label>
                        <input type="text" id="workoutName" placeholder="E.g., Upper Body Power, Core Blast...">
                    </div>
                    
                    <div class="input-group">
                        <label for="workoutDescription">Description (Optional)</label>
                        <textarea id="workoutDescription" rows="3" placeholder="Describe your workout, goals, or add any notes..."></textarea>
                    </div>
                    
                    <div class="input-group">
                        <label>Categories</label>
                        <div class="categories">
                            <div class="category active" data-category="Strength Training">Strength Training</div>
                            <div class="category" data-category="cardio">Cardio</div>
                            <div class="category" data-category="Bodyweight">Bodyweight</div>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Difficulty Level</label>
                        <input type="range" min="1" max="3" value="2" id="difficultySlider">
                        <div class="slider-labels">
                            <span>Beginner</span>
                            <span>Intermediate</span>
                            <span>Advanced</span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="setsPerExercise">Sets Per Exercise</label>
                        <input type="number" id="setsPerExercise" min="1" max="10" value="3">
                    </div>
                    
                    <div class="input-group">
                        <label for="restTimePerExercise">Rest Time Between Exercises (minutes)</label>
                        <input type="number" id="restTimePerExercise" min="0" max="10" value="1" step="0.5">
                    </div>
                    
                    <div class="input-group">
                        <label for="estimatedTime">Estimated Time (minutes)</label>
                        <input type="number" id="estimatedTime" min="5" max="180" value="45" readonly>
                    </div>
                </div>
                
                <div class="exercise-selector">
                    <div class="categories-column mobile-section" data-section="categories">
                        <h3>Categories</h3>
                        <div class="category-list">
                            <div class="category-item active" data-category="Strength Training">
                                <i class="fas fa-dumbbell"></i>
                                Strength Training
                            </div>
                            <div class="category-item" data-category="cardio">
                                <i class="fas fa-running"></i>
                                Cardio
                            </div>
                            <div class="category-item" data-category="Bodyweight">
                                <i class="fas fa-user"></i>
                                Flexibility
                            </div>
                        </div>
                    </div>
                    
                    <div class="exercises-column mobile-section" data-section="exercises">
                        <div class="exercise-search">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" id="exerciseSearch" placeholder="Search exercises...">
                            </div>
                        </div>
                        
                        <div class="exercises-list" id="exercisesGrid">
                        </div>
                    </div>
                    
                    <div class="selected-column mobile-section" data-section="selected">
                        <h3>Selected Exercises</h3>
                        <div id="selectedExercisesList" class="selected-exercises-list">
                            <div class="empty-selection" id="emptySelection">
                                <p>No exercises selected yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelTemplate">Cancel</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    <div class="modal-overlay" id="viewTemplateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="viewTemplateTitle">View Template</h2>
                <button class="modal-close" id="closeViewModal">&times;</button>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-tab="view-details">Details</div>
                <div class="mobile-tab" data-tab="view-exercises">Exercises</div>
            </div>
            
            <div class="modal-body">
                <div class="template-details mobile-section active" data-section="view-details">
                    <div class="input-group">
                        <label>Template Name</label>
                        <div class="view-field" id="viewName"></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Description</label>
                        <div class="view-field" id="viewDescription"></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Category</label>
                        <div class="view-field" id="viewCategory"></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Difficulty Level</label>
                        <div class="view-field" id="viewDifficulty"></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Estimated Time</label>
                        <div class="view-field" id="viewTime"></div>
                    </div>
                </div>
                
                <div class="exercise-selector">
                    <div class="selected-exercises mobile-section" data-section="view-exercises">
                        <h3>Exercises</h3>
                        <div id="viewExercisesList">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeViewBtn">Close</button>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="editTemplateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Template</h2>
                <div class="modal-header-actions">
                    <button class="save-template-btn" id="updateTemplateHeader">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <button class="modal-close" id="closeEditModal">&times;</button>
                </div>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-tab="edit-details">Details</div>
                <div class="mobile-tab" data-tab="edit-categories">Categories</div>
                <div class="mobile-tab" data-tab="edit-exercises">Exercises</div>
                <div class="mobile-tab" data-tab="edit-selected">Selected</div>
            </div>
            
            <div class="modal-body">
                <div class="template-details mobile-section active" data-section="edit-details">
                    <div class="input-group">
                        <label for="editWorkoutName">Template Name</label>
                        <input type="text" id="editWorkoutName">
                    </div>
                    
                    <div class="input-group">
                        <label for="editWorkoutDescription">Description (Optional)</label>
                        <textarea id="editWorkoutDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="input-group">
                        <label>Categories</label>
                        <div class="categories">
                            <div class="category" data-category="Strength Training">Strength Training</div>
                            <div class="category" data-category="cardio">Cardio</div>
                            <div class="category" data-category="Bodyweight">Bodyweight</div>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Difficulty Level</label>
                        <input type="range" min="1" max="3" value="2" id="editDifficultySlider">
                        <div class="slider-labels">
                            <span>Beginner</span>
                            <span>Intermediate</span>
                            <span>Advanced</span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="editSetsPerExercise">Sets Per Exercise</label>
                        <input type="number" id="editSetsPerExercise" min="1" max="10" value="3">
                    </div>
                    
                    <div class="input-group">
                        <label for="editRestTimePerExercise">Rest Time Between Exercises (minutes)</label>
                        <input type="number" id="editRestTimePerExercise" min="0" max="10" value="1" step="0.5">
                    </div>
                    
                    <div class="input-group">
                        <label for="editEstimatedTime">Estimated Time (minutes)</label>
                        <input type="number" id="editEstimatedTime" min="5" max="180" value="45" readonly>
                    </div>
                    
                    <input type="hidden" id="editTemplateId">
                </div>
                
                <div class="exercise-selector">
                    <div class="categories-column mobile-section" data-section="edit-categories">
                        <h3>Categories</h3>
                        <div class="category-list">
                            <div class="edit-category-item active" data-category="Strength Training">
                                <i class="fas fa-dumbbell"></i>
                                Strength Training
                            </div>
                            <div class="edit-category-item" data-category="cardio">
                                <i class="fas fa-running"></i>
                                Cardio
                            </div>
                            <div class="edit-category-item" data-category="Bodyweight">
                                <i class="fas fa-user"></i>
                                Flexibility
                            </div>
                        </div>
                    </div>
                    
                    <div class="exercises-column mobile-section" data-section="edit-exercises">
                        <div class="exercise-search">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" id="editExerciseSearch" placeholder="Search exercises...">
                            </div>
                        </div>
                        
                        <div class="exercises-list" id="editExercisesGrid">
                        </div>
                    </div>
                    
                    <div class="selected-column mobile-section" data-section="edit-selected">
                        <h3>Selected Exercises</h3>
                        <div id="editSelectedExercisesList" class="selected-exercises-list">
                            <div class="empty-selection" id="editEmptySelection">
                                <p>No exercises selected yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelEdit">Cancel</button>
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
            
            $('.mobile-tab').on('click', function() {
                const tabTarget = $(this).data('tab');
                const tabContainer = $(this).closest('.modal-content');
                
                tabContainer.find('.mobile-tab').removeClass('active');
                $(this).addClass('active');
                
                tabContainer.find('.mobile-section').removeClass('active');
                tabContainer.find(`.mobile-section[data-section="${tabTarget}"]`).addClass('active');
            });
            
            $('<style>').html(`
                .view-field {
                    background: var(--background);
                    padding: 12px;
                    border-radius: 6px;
                    margin-bottom: 5px;
                    min-height: 20px;
                }
                
                .view-exercise {
                    background: var(--background);
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 10px;
                }
                
                .view-exercise-header {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                
                .view-exercise-details {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 15px;
                    font-size: 14px;
                    color: #aaa;
                }
                
                .view-exercise-detail {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .view-exercise-notes {
                    margin-top: 10px;
                    font-style: italic;
                    color: #aaa;
                }
            `).appendTo('head');
            
            loadExercises();
            
            $(document).on('click', '.template-actions-menu', function(e) {
                e.stopPropagation();
                const dropdown = $(this).siblings('.template-actions-dropdown');
                $('.template-actions-dropdown').not(dropdown).removeClass('active');
                dropdown.toggleClass('active');
            });
            
            $(document).on('click', function() {
                $('.template-actions-dropdown').removeClass('active');
            });
            
            $(document).on('click', '.view-template', function() {
                const templateId = $(this).closest('.template-card').data('id');
                viewTemplate(templateId);
            });
            
            $(document).on('click', '.edit-template', function() {
                const templateId = $(this).closest('.template-card').data('id');
                editTemplate(templateId);
            });
            
            $(document).on('click', '.template-delete', function() {
                const templateId = $(this).closest('.template-card').data('id');
                deleteTemplate(templateId);
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
                        showToast('Error loading template', 'error');
                    }
                });
            }
            
            function showViewTemplateModal(template) {
                $('#viewName').text(template.name);
                $('#viewDescription').text(template.description || 'No description');
                $('#viewCategory').text(template.category);
                
                let difficulty = 'Intermediate';
                if (template.difficulty === 'beginner') difficulty = 'Beginner';
                if (template.difficulty === 'advanced') difficulty = 'Advanced';
                
                $('#viewDifficulty').text(difficulty);
                $('#viewTime').text(template.estimated_time + ' minutes');
                
                const list = $('#viewExercisesList');
                list.empty();
                
                if (template.exercises.length === 0) {
                    list.html('<div class="empty-selection"><p>No exercises in this template</p></div>');
                } else {
                    template.exercises.forEach(function(exercise) {
                        const item = $(`
                            <div class="view-exercise">
                                <div class="view-exercise-header">
                                    <h4>${exercise.name}</h4>
                                    <div class="view-exercise-position">Position: ${exercise.position}</div>
                                </div>
                                <div class="view-exercise-details">
                                    <div class="view-exercise-detail">
                                        <i class="fas fa-layer-group"></i>
                                        <span>${exercise.sets} sets</span>
                                    </div>
                                    <div class="view-exercise-detail">
                                        <i class="fas fa-stopwatch"></i>
                                        <span>${exercise.rest_time} sec rest</span>
                                    </div>
                                    <div class="view-exercise-detail">
                                        <i class="fas fa-dumbbell"></i>
                                        <span>${exercise.muscle}</span>
                                    </div>
                                    <div class="view-exercise-detail">
                                        <i class="fas fa-cog"></i>
                                        <span>${exercise.equipment}</span>
                                    </div>
                                </div>
                                ${exercise.notes ? `<div class="view-exercise-notes">Notes: ${exercise.notes}</div>` : ''}
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
                        showToast('Error loading template', 'error');
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
                
                $('.category').removeClass('active');
                $(`.category[data-category="${template.category}"]`).addClass('active');
                
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
            
            $('#updateTemplate').on('click', function() {
                const templateId = $('#editTemplateId').val();
                const name = $('#editWorkoutName').val().trim();
                const description = $('#editWorkoutDescription').val().trim();
                const estimatedTime = $('#editEstimatedTime').val();
                const difficultyVal = $('#editDifficultySlider').val();
                
                let difficulty = 'intermediate';
                if (difficultyVal == 1) difficulty = 'beginner';
                if (difficultyVal == 3) difficulty = 'advanced';
                
                const category = $('.category.active').data('category');
                
                if (!name) {
                    showToast('Please enter a template name', 'error');
                    return;
                }
                
                if (editSelectedExercises.length === 0) {
                    showToast('Please add at least one exercise', 'error');
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
                        showToast('Error updating template', 'error');
                    }
                });
            });
            
            function deleteTemplate(templateId) {
                if (confirm('Are you sure you want to delete this template?')) {
                    $.ajax({
                        url: '?action=delete_template',
                        method: 'POST',
                        data: { template_id: templateId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showToast(response.message, 'success');
                                $(`.template-card[data-id="${templateId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    if ($('.template-card').length === 0) {
                                        $('.templates-grid').html(`
                                            <div class="no-templates">
                                                <p>You haven't created any workout templates yet.</p>
                                                <p>Click "Create Template" to get started!</p>
                                            </div>
                                        `);
                                    }
                                });
                            } else {
                                showToast(response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            showToast('Error deleting template', 'error');
                        }
                    });
                }
            }
            
            function loadEditExercises(query = '') {
                $('#editExercisesGrid').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
                
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
                        $('#editExercisesGrid').html('<div class="error">Error loading exercises</div>');
                    }
                });
            }
            
            function renderEditExercises(exercises) {
                const grid = $('#editExercisesGrid');
                grid.empty();
                
                if (exercises.length === 0) {
                    grid.html('<div class="no-results">No exercises found</div>');
                    return;
                }
                
                exercises.forEach(function(exercise) {
                    const item = $(`
                        <div class="exercise-item" data-id="${exercise.id}">
                            <h4>${exercise.name}</h4>
                            <div class="exercise-details">
                                <div><i class="fas fa-dumbbell"></i> ${exercise.primary_muscle}</div>
                                <div><i class="fas fa-cog"></i> ${exercise.equipment || 'No equipment'}</div>
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
                    showToast('This exercise is already in your template', 'error');
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
                    equipment: exercise.equipment || 'None'
                };
                
                editSelectedExercises.push(exerciseObj);
                renderEditSelectedExercises();
                updateEditEstimatedTime();
                
                showToast(`Added ${exercise.name} to your template`, 'success');
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
                        <div class="selected-exercise" data-index="${index}">
                            <div class="exercise-header">
                                <div class="exercise-info">
                                    <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                                    <div class="exercise-name">${index + 1}. ${exercise.name}</div>
                                </div>
                                <div class="exercise-meta">
                                    ${exercise.sets} sets × 12 reps
                                    <br>Rest: ${exercise.rest_time}s
                                </div>
                            </div>
                            <div class="exercise-controls">
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
                    handle: '.drag-handle',
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
                    <div class="edit-modal">
                        <div class="edit-modal-content">
                            <h3>Edit ${exercise.name}</h3>
                            <div class="input-group">
                                <label>Sets</label>
                                <input type="number" id="editItemSets" value="${exercise.sets}" min="1" max="10">
                            </div>
                            <div class="input-group">
                                <label>Rest Time (seconds)</label>
                                <input type="number" id="editItemRest" value="${exercise.rest_time}" min="0" max="300">
                            </div>
                            <div class="input-group">
                                <label>Notes</label>
                                <textarea id="editItemNotes">${exercise.notes}</textarea>
                            </div>
                            <div class="modal-actions">
                                <button id="cancelEditItem">Cancel</button>
                                <button id="saveEditItem">Save</button>
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
                
                modal.find('.edit-modal-content').css({
                    'background': 'var(--card-bg)',
                    'padding': '20px',
                    'border-radius': '10px',
                    'width': '400px'
                });
                
                modal.find('.modal-actions').css({
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
            
            $('#editTemplateModal .edit-category-item').on('click', function() {
                $('#editTemplateModal .edit-category-item').removeClass('active');
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
            
            $('.category-item').on('click', function() {
                $('.category-item').removeClass('active');
                $(this).addClass('active');
                
                currentCategory = $(this).data('category');
                loadExercises();
            });
            
            $('.category').on('click', function() {
                $('.category').removeClass('active');
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
                $('#exercisesGrid').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
                
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
                        $('#exercisesGrid').html('<div class="error">Error loading exercises</div>');
                        console.error('Error:', error);
                    }
                });
            }
            
            function renderExercises(exercises) {
                const grid = $('#exercisesGrid');
                grid.empty();
                
                if (exercises.length === 0) {
                    grid.html('<div class="no-results">No exercises found</div>');
                    return;
                }
                
                exercises.forEach(function(exercise) {
                    const item = $(`
                        <div class="exercise-item" data-id="${exercise.id}">
                            <h4>${exercise.name}</h4>
                            <div class="exercise-details">
                                <div><i class="fas fa-dumbbell"></i> ${exercise.primary_muscle}</div>
                                <div><i class="fas fa-cog"></i> ${exercise.equipment || 'No equipment'}</div>
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
                    showToast('This exercise is already in your template', 'error');
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
                    equipment: exercise.equipment || 'None'
                };
                
                selectedExercises.push(exerciseObj);
                renderSelectedExercises();
                updateEstimatedTime();
                
                showToast(`Added ${exercise.name} to your template`, 'success');
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
                        <div class="selected-exercise" data-index="${index}">
                            <div class="exercise-header">
                                <div class="exercise-info">
                                    <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                                    <div class="exercise-name">${index + 1}. ${exercise.name}</div>
                                </div>
                                <div class="exercise-meta">
                                    ${exercise.sets} sets × 12 reps
                                    <br>Rest: ${exercise.rest_time}s
                                </div>
                            </div>
                            <div class="exercise-controls">
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
                    handle: '.drag-handle',
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
                    <div class="edit-modal">
                        <div class="edit-modal-content">
                            <h3>Edit ${exercise.name}</h3>
                            <div class="input-group">
                                <label>Sets</label>
                                <input type="number" id="editSets" value="${exercise.sets}" min="1" max="10">
                            </div>
                            <div class="input-group">
                                <label>Rest Time (seconds)</label>
                                <input type="number" id="editRest" value="${exercise.rest_time}" min="0" max="300">
                            </div>
                            <div class="input-group">
                                <label>Notes</label>
                                <textarea id="editNotes">${exercise.notes}</textarea>
                            </div>
                            <div class="modal-actions">
                                <button id="cancelEdit">Cancel</button>
                                <button id="saveEdit">Save</button>
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
                
                modal.find('.edit-modal-content').css({
                    'background': 'var(--card-bg)',
                    'padding': '20px',
                    'border-radius': '10px',
                    'width': '400px'
                });
                
                modal.find('.modal-actions').css({
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
                alert('Calendar functionality would be implemented here');
            });
            
            $('#saveTemplateHeader').on('click', function() {
                const name = $('#workoutName').val().trim();
                const description = $('#workoutDescription').val().trim();
                const estimatedTime = $('#estimatedTime').val();
                const difficultyVal = $('#difficultySlider').val();
                
                let difficulty = 'intermediate';
                if (difficultyVal == 1) difficulty = 'beginner';
                if (difficultyVal == 3) difficulty = 'advanced';
                
                const category = $('.category.active').data('category');
                
                if (!name) {
                    showToast('Please enter a template name', 'error');
                    return;
                }
                
                if (selectedExercises.length === 0) {
                    showToast('Please add at least one exercise', 'error');
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
                        showToast('Error saving template', 'error');
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
                
                let difficulty = 'intermediate';
                if (difficultyVal == 1) difficulty = 'beginner';
                if (difficultyVal == 3) difficulty = 'advanced';
                
                const category = $('.category.active').data('category');
                
                if (!name) {
                    showToast('Please enter a template name', 'error');
                    return;
                }
                
                if (editSelectedExercises.length === 0) {
                    showToast('Please add at least one exercise', 'error');
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
                        showToast('Error updating template', 'error');
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
                        const editBtn = $(`.template-card[data-id="${templateId}"] .edit-btn, .template-list-item[data-id="${templateId}"] .edit-btn`);
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