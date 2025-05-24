<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

 
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$is_superadmin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_superadmin = ($row['count'] > 0);
}

if (!$is_superadmin) {
    header("Location: ../../pages/access_denied.php");
    exit;
}

$templates_query = "SELECT wt.*, 
                    COUNT(DISTINCT w.user_id) as user_count,
                    (SELECT COUNT(*) FROM workout_template_exercises WHERE workout_template_id = wt.id) as exercise_count
                    FROM workout_templates wt
                    LEFT JOIN workouts w ON wt.id = w.template_id
                    INNER JOIN users u ON wt.user_id = u.id
                    INNER JOIN user_roles ur ON u.id = ur.user_id
                    WHERE ur.role_id = 5
                    GROUP BY wt.id
                    ORDER BY user_count DESC";
$templates_result = $conn->query($templates_query);

if (!$templates_result) {
    echo "Error in query: " . $conn->error;
}

$exercises_query = "SELECT * FROM exercises ORDER BY name ASC";
$exercises_result = $conn->query($exercises_query);

$exercises_data = array();
if ($exercises_result) {
    while ($exercise = $exercises_result->fetch_assoc()) {
        $exercises_data[] = $exercise;
    }
}

$pageTitle = "Template Management";
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle; ?> | GYMVERSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="/admin/includes/admin-styles.css">
    <style>
        .templates-container {
            padding: 20px;
        }
        
        .templates-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-dropdown {
            background-color: var(--secondary-dark);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .template-card {
            background-color: var(--dark-bg-surface);
            border-radius: 10px;
            padding: 20px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .template-card .status {
            display: none;
        }
        
        .template-card h3 {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .template-info {
            margin-bottom: 15px;
        }
        
        .template-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .template-info-item .label {
            color: var(--text-muted);
        }
        
        .template-users {
            display: none;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }

         .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            width: 90%;
            max-width: 1100px;
            height: 85vh;
            background: var(--dark-bg);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--dark-bg-surface);
        }
        
        .modal-header h2 {
            color: #fff;
            font-size: 22px;
            margin: 0;
        }
        
        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #fff;
        }
        
        .save-template-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .save-template-btn:hover {
            background-color: var(--accent-color);
            opacity: 0.9;
        }
        
        .mobile-tabs {
            display: flex;
            background-color: var(--dark-bg-surface);
            border-bottom: 1px solid var(--border-color);
            padding: 10px;
            gap: 15px;
        }
        
        .mobile-tab {
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            background-color: var(--dark-bg);
        }
        
        .mobile-tab.active {
            background-color: var(--accent-color);
        }
        
        .modal-body {
            flex: 1;
            overflow: hidden;
            display: flex;
        }
        
        .template-form {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .template-details {
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }
        
        .form-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 20px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .input-group input[type="text"],
        .input-group input[type="number"],
        .input-group textarea {
            width: 100%;
            padding: 12px;
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: white;
            font-size: 14px;
        }
        
        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .category {
            padding: 8px 15px;
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .category.active {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .difficulty-slider-container {
            position: relative;
            padding: 10px 0;
        }
        
        .difficulty-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-color);
            color: white;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
        }
        
        .difficulty-slider-container:hover .difficulty-value {
            opacity: 1;
        }
        
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: var(--dark-bg-surface);
            outline: none;
            margin: 15px 0;
            cursor: pointer;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--accent-color);
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        
        input[type="range"]::-webkit-slider-thumb:active {
            transform: scale(0.95);
        }
        
        input[type="range"]::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, var(--accent-color) var(--progress), var(--dark-bg-surface) var(--progress));
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding: 0 12px;
        }
        
        .slider-labels span {
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
            cursor: pointer;
            transition: color 0.2s;
            position: relative;
            padding-top: 15px;
        }
        
        .slider-labels span::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 8px;
            background-color: var(--text-muted);
        }
        
        .slider-labels span.active {
            color: var(--accent-color);
            font-weight: 500;
        }
        
        .slider-labels span.active::before {
            background-color: var(--accent-color);
        }
        
        @media (max-width: 768px) {
            .slider-labels span {
                font-size: 10px;
                transform: rotate(-45deg);
                white-space: nowrap;
                padding-top: 20px;
            }
            
            .slider-labels span::before {
                height: 15px;
            }
        }
        
        .exercise-workflow {
            display: flex;
            height: 100%;
            overflow: hidden;
            max-height: 60vh;
        }
        
        .exercise-categories {
            width: 250px;
            border-right: 1px solid var(--border-color);
            background-color: var(--dark-bg-surface);
            overflow-y: auto;
            height: 100%;
        }
        
        .exercise-categories h4 {
            padding: 15px;
            margin: 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .category-list {
            padding: 0;
        }
        
        .category-item, .edit-category-item {
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s;
        }
        
        .category-item i, .edit-category-item i {
            color: var(--text-muted);
        }
        
        .category-item:hover, .edit-category-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .category-item.active, .edit-category-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid var(--accent-color);
        }
        
        .exercise-browser {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .search-container {
            position: relative;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-icon {
            position: absolute;
            left: 25px;
            top: 25px;
            color: var(--text-muted);
        }
        
        .search-input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: white;
        }
        
        .exercises-grid {
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            padding: 15px;
        }
        
        .exercise-item {
            background-color: var(--dark-bg-surface);
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
            height: auto;
        }
        
        .exercise-item:hover {
            transform: translateY(-3px);
        }
        
        .exercise-image {
            height: 70px;
            background-color: var(--secondary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .exercise-image i {
            font-size: 24px;
            color: var(--text-muted);
        }
        
        .exercise-name {
            padding: 10px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            color: #fff;
            background: var(--dark-bg-surface);
            word-break: break-word;
            display: block;
        }
        
        .selected-exercises {
            width: 300px;
            border-left: 1px solid var(--border-color);
            background-color: var(--dark-bg-surface);
            overflow-y: auto;
            height: 100%;
        }
        
        .selected-exercises h4 {
            padding: 15px;
            margin: 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .selected-exercises-list {
            padding: 15px;
        }
        
        .selected-exercise {
            background-color: var(--dark-bg);
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 12px;
            position: relative;
        }
        
        .selected-exercise-name {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .selected-exercise-details {
            font-size: 14px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .selected-exercise-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
        }
        
        .selected-exercise-actions button {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        .selected-exercise-actions button:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .exercise-settings {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--dark-bg-surface);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10;
            min-width: 200px;
            margin-top: 5px;
        }

        .exercise-settings.active {
            display: block;
        }

        .exercise-settings h5 {
            margin: 0 0 10px 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .exercise-settings input[type="number"] {
            width: 100%;
            padding: 8px;
            background: var(--dark-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: white;
            margin-bottom: 10px;
        }

        .exercise-settings .settings-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .exercise-settings .settings-actions button {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }

        .exercise-settings .settings-actions .save-settings {
            background: var(--accent-color);
            color: white;
        }

        .exercise-settings .settings-actions .cancel-settings {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }
        
        .empty-selection {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
        }
        
        .loading {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background-color: var(--dark-bg-surface);
        }
        .create-btn{
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .mobile-section {
            display: none;
            height: 100%;
            overflow: hidden;
        }
        
        .mobile-section.active {
            display: block;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        @media (max-width: 1024px) {
            .exercise-workflow {
                flex-direction: column;
            }
            
            .exercise-categories, .selected-exercises {
                width: 100%;
                height: 200px;
            }
            
            .selected-exercises {
                border-left: none;
                border-top: 1px solid var(--border-color);
            }
        }
        
        .exercise-section-title {
            padding: 10px 15px;
            margin: 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .templates-grid {
                grid-template-columns: 1fr;
            }
            
            .form-columns {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                height: 95vh;
                max-width: none;
            }
            
            .modal-header h2 {
                font-size: 18px;
            }
            
            .mobile-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding: 10px 5px;
                gap: 8px;
            }
            
            .mobile-tab {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .exercises-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 8px;
            }
            
            .exercise-item {
                height: 110px;
            }
            
            .exercise-image {
                height: 60px;
            }
            
            .exercise-name {
                padding: 5px;
                font-size: 12px;
                line-height: 1.2;
            }
            
            .exercise-categories, .selected-exercises {
                height: 180px;
            }
            
            .selected-exercise {
                padding: 8px;
            }
            
            .selected-exercise-actions {
                position: relative;
                top: 0;
                right: 0;
                justify-content: flex-end;
                margin-top: 8px;
            }
            
            .category-item, .edit-category-item {
                padding: 8px 10px;
            }
            
            .admin-wrapper {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                max-width: none;
                position: static;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .templates-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .filters {
                flex-direction: column;
                width: 100%;
            }
            
            .filter-dropdown {
                width: 100%;
            }
            
            .btn {
                padding: 12px;
            }
            
            .search-container {
                padding: 10px;
            }
            
            .search-icon {
                top: 20px;
            }
            
            .action-btn, .selected-exercise-actions button {
                min-width: 40px;
                min-height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .input-group input[type="text"],
            .input-group input[type="number"],
            .input-group textarea,
            .search-input {
                padding: 14px;
                font-size: 16px;
            }

            .modal-footer {
                padding: 15px;
                flex-direction: column;
                gap: 10px;
            }
            
            .modal-footer .btn {
                width: 100%;
                padding: 14px;
            }
            
            .mobile-section.active {
                max-height: 65vh;
            }
            
            .modal-header-actions {
                gap: 8px;
            }
            
            .save-template-btn {
                padding: 8px 10px;
            }
            
            .save-template-btn .btn-text {
                display: none;
            }
            
            .template-card {
                padding: 15px;
            }
            
            .create-btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
            
            .modal-content {
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
            
            .admin-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 10px;
            }
            
            .admin-topbar h1 {
                font-size: 20px;
                margin: 0;
            }
        }
        
        @media (max-width: 480px) {
            .exercise-workflow {
                max-height: 400px;
            }
            
            .exercise-categories, .selected-exercises {
                height: 150px;
            }
            
            .exercise-image {
                height: 50px;
            }
            
            .exercise-item {
                height: 90px;
            }
            
            .exercises-grid {
                grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
                gap: 6px;
                padding: 10px;
            }
            
            .category-item, .edit-category-item {
                padding: 6px 8px;
                font-size: 13px;
            }
            
            .modal-header h2 {
                font-size: 16px;
            }
            
            .template-info-item {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 12px;
            }
            
            .template-info-item .label {
                margin-bottom: 3px;
            }
            
            input[type="number"] {
                -webkit-appearance: none;
                -moz-appearance: textfield;
            }
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1>Template Management</h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="templates-container">
                <div class="templates-header">
                    <h2>All Templates</h2>
                    <button class="create-btn" id="openCreateModal">
                        <i class="fas fa-plus"></i>
                        Create Template
                    </button>
                </div>
                
                <div class="filters">
                    <select class="filter-dropdown" id="categoryFilter">
                        <option value="all">All Categories</option>
                        <option value="fitness">Fitness</option>
                        <option value="cardio">Cardio</option>
                        <option value="strength">Strength</option>
                    </select>
                    
                    <select class="filter-dropdown" id="difficultyFilter">
                        <option value="all">All Difficulties</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    
                    <select class="filter-dropdown" id="sortFilter">
                        <option value="popular">Most Popular</option>
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
                
                <div class="templates-grid">
                    <?php 
                    if ($templates_result && $templates_result->num_rows > 0) {
                        while($template = $templates_result->fetch_assoc()) {
                            $status_class = 'active';
                            $status_text = 'Active';
                            $user_count = number_format($template['user_count']);
                            $created_date = date('M j, Y', strtotime($template['created_at']));
                            
                            $category = !empty($template['category']) ? $template['category'] : 'Not specified';
                            $difficulty = !empty($template['difficulty']) ? $template['difficulty'] : 'Not specified';
                    ?>
                    <div class="template-card" data-id="<?php echo $template['id']; ?>">
                        <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                        
                        <div class="template-info">
                            <div class="template-info-item">
                                <span class="label">Category:</span>
                                <span><?php echo htmlspecialchars($category); ?></span>
                            </div>
                            
                            <div class="template-info-item">
                                <span class="label">Exercises:</span>
                                <span><?php echo $template['exercise_count']; ?></span>
                            </div>
                            
                            <div class="template-info-item">
                                <span class="label">Difficulty:</span>
                                <span><?php echo htmlspecialchars($difficulty); ?></span>
                            </div>
                            
                            <div class="template-info-item">
                                <span class="label">Created:</span>
                                <span><?php echo $created_date; ?></span>
                            </div>
                        </div>
                        
                        <div class="template-actions">
                            <div class="action-btn edit-btn" data-action="edit" data-id="<?php echo $template['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="action-btn delete-btn" data-action="delete" data-id="<?php echo $template['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </div>
                        </div>
                    </div>
                    <?php 
                        }
                    } else {
                    ?>
                    <div class="empty-state">
                        <p>No templates found. Create a new template to get started.</p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="createTemplateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Template</h2>
                <div class="modal-header-actions">
                    <button class="save-template-btn" id="saveTemplateHeader">
                        <i class="fas fa-save"></i>
                        <span class="btn-text">Save</span>
                    </button>
                    <button class="modal-close" id="closeModal">&times;</button>
                </div>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-tab="details">Details</div>
                <div class="mobile-tab" data-tab="exercises">Exercises</div>
            </div>
            
            <div class="modal-body">
                <div class="template-form">
                    <div class="template-details mobile-section active" data-section="details">
                        <div class="form-columns">
                            <div class="column-left">
                                <div class="input-group">
                                    <label for="workoutName">Template Name</label>
                                    <input type="text" id="workoutName" placeholder="E.g., Upper Body Power, Core Blast...">
                                </div>
                                
                                <div class="input-group">
                                    <label for="workoutDescription">Description (Optional)</label>
                                    <textarea id="workoutDescription" rows="3" placeholder="Describe your workout, goals, or add any notes..."></textarea>
                                </div>
                                
                                <div class="input-group">
                                    <label>Category</label>
                                    <div class="categories">
                                        <div class="category active" data-category="Strength Training">Strength Training</div>
                                        <div class="category" data-category="cardio">Cardio</div>
                                        <div class="category" data-category="Bodyweight">Bodyweight</div>
                                    </div>
                                </div>
                                
                                <div class="input-group">
                                    <label>Difficulty Level</label>
                                    <div class="difficulty-slider-container">
                                        <input type="range" min="0" max="5" value="2" id="difficultySlider" step="1">
                                        <div class="difficulty-value"></div>
                                        <div class="slider-labels">
                                            <span>Beginner</span>
                                            <span>Easy</span>
                                            <span>Medium</span>
                                            <span>Intermediate</span>
                                            <span>Hard</span>
                                            <span>Expert</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="column-right">
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
                        </div>
                    </div>
                    
                    <div class="mobile-section" data-section="exercises">
                        <h3 class="exercise-section-title">Select Exercises</h3>
                        <div class="exercise-workflow">
                            <div class="exercise-categories">
                                <h4>Categories</h4>
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
                                        Bodyweight
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exercise-browser">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="exerciseSearch" placeholder="Search exercises..." class="search-input">
                                </div>
                                <div id="exercisesGrid" class="exercises-grid">
                                    <div class="loading">Loading exercises...</div>
                                </div>
                            </div>
                            
                            <div class="selected-exercises">
                                <h4>Selected Exercises</h4>
                                <div id="selectedExercisesList" class="selected-exercises-list">
                                    <div class="empty-selection" id="emptySelection">
                                        <p>No exercises selected yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="saveTemplate">Save Template</button>
                <button class="btn btn-secondary" id="cancelTemplate">Cancel</button>
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
                        <span class="btn-text">Save</span>
                    </button>
                    <button class="modal-close" id="closeEditModal">&times;</button>
                </div>
            </div>
            
            <div class="mobile-tabs">
                <div class="mobile-tab active" data-tab="edit-details">Details</div>
                <div class="mobile-tab" data-tab="edit-exercises">Exercises</div>
            </div>
            
            <div class="modal-body">
                <div class="template-form">
                    <div class="template-details mobile-section active" data-section="edit-details">
                        <div class="form-columns">
                            <div class="column-left">
                                <div class="input-group">
                                    <label for="editWorkoutName">Template Name</label>
                                    <input type="text" id="editWorkoutName">
                                </div>
                                
                                <div class="input-group">
                                    <label for="editWorkoutDescription">Description (Optional)</label>
                                    <textarea id="editWorkoutDescription" rows="3"></textarea>
                                </div>
                                
                                <div class="input-group">
                                    <label>Category</label>
                                    <div class="categories">
                                        <div class="category" data-category="Strength Training">Strength Training</div>
                                        <div class="category" data-category="cardio">Cardio</div>
                                        <div class="category" data-category="Bodyweight">Bodyweight</div>
                                    </div>
                                </div>
                                
                                <div class="input-group">
                                    <label>Difficulty Level</label>
                                    <div class="difficulty-slider-container">
                                        <input type="range" min="0" max="5" value="2" id="editDifficultySlider" step="1">
                                        <div class="difficulty-value"></div>
                                        <div class="slider-labels">
                                            <span>Beginner</span>
                                            <span>Easy</span>
                                            <span>Medium</span>
                                            <span>Intermediate</span>
                                            <span>Hard</span>
                                            <span>Expert</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="column-right">
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
                            </div>
                        </div>
                        
                        <input type="hidden" id="editTemplateId">
                    </div>
                    
                    <div class="mobile-section" data-section="edit-exercises">
                        <h3 class="exercise-section-title">Select Exercises</h3>
                        <div class="exercise-workflow">
                            <div class="exercise-categories">
                                <h4>Categories</h4>
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
                                        Bodyweight
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exercise-browser">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="editExerciseSearch" placeholder="Search exercises..." class="search-input">
                                </div>
                                <div id="editExercisesGrid" class="exercises-grid">
                                    <div class="loading">Loading exercises...</div>
                                </div>
                            </div>
                            
                            <div class="selected-exercises">
                                <h4>Selected Exercises</h4>
                                <div id="editSelectedExercisesList" class="selected-exercises-list">
                                    <div class="empty-selection" id="editEmptySelection">
                                        <p>No exercises selected yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="updateTemplate">Save Changes</button>
                <button class="btn btn-secondary" id="cancelEdit">Cancel</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allExercises = <?php echo json_encode($exercises_data); ?>;
            
            let selectedExercises = [];
            let editSelectedExercises = [];
            let currentCategory = 'Strength Training';
            let editCurrentCategory = 'Strength Training';
            
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            
            initializeTabs();
            
            loadExercises(currentCategory);
            
            initializeCategorySelectors();
            
            document.getElementById('openCreateModal').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('createTemplateModal').classList.add('active');
                document.body.style.overflow = 'hidden';
                
                if (isMobile) {
                    setTimeout(() => {
                        const modalBody = document.querySelector('#createTemplateModal .modal-body');
                        if (modalBody) modalBody.scrollTop = 0;
                    }, 100);
                }
            });
            
            document.getElementById('closeModal').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('createTemplateModal').classList.remove('active');
                document.body.style.overflow = '';
            });
            
            document.getElementById('cancelTemplate').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('createTemplateModal').classList.remove('active');
                document.body.style.overflow = '';
            });
            
            document.getElementById('closeEditModal').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('editTemplateModal').classList.remove('active');
                document.body.style.overflow = '';
            });
            
            document.getElementById('cancelEdit').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('editTemplateModal').classList.remove('active');
                document.body.style.overflow = '';
            });
            
            document.getElementById('saveTemplate').addEventListener('click', saveTemplate);
            document.getElementById('saveTemplateHeader').addEventListener('click', saveTemplate);
            document.getElementById('updateTemplate').addEventListener('click', updateTemplate);
            document.getElementById('updateTemplateHeader').addEventListener('click', updateTemplate);
            
            document.getElementById('difficultySlider').addEventListener('input', updateEstimatedTime);
            document.getElementById('setsPerExercise').addEventListener('input', updateEstimatedTime);
            document.getElementById('restTimePerExercise').addEventListener('input', updateEstimatedTime);
            document.getElementById('editDifficultySlider').addEventListener('input', updateEditEstimatedTime);
            document.getElementById('editSetsPerExercise').addEventListener('input', updateEditEstimatedTime);
            document.getElementById('editRestTimePerExercise').addEventListener('input', updateEditEstimatedTime);
            
            document.getElementById('exerciseSearch').addEventListener('input', function() {
                filterExercises(this.value.toLowerCase());
            });
            
            document.getElementById('editExerciseSearch').addEventListener('input', function() {
                filterEditExercises(this.value.toLowerCase());
            });
            
            if (isMobile) {
                const searchInputs = document.querySelectorAll('.search-input');
                searchInputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        setTimeout(() => {
                            this.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 300);
                    });
                    
                    input.addEventListener('input', function() {
                        if (this.value) {
                            if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('search-clear')) {
                                const clearBtn = document.createElement('button');
                                clearBtn.className = 'search-clear';
                                clearBtn.innerHTML = '×';
                                clearBtn.style.position = 'absolute';
                                clearBtn.style.right = '15px';
                                clearBtn.style.top = '25px';
                                clearBtn.style.background = 'none';
                                clearBtn.style.border = 'none';
                                clearBtn.style.color = 'white';
                                clearBtn.style.fontSize = '20px';
                                
                                clearBtn.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    this.value = '';
                                    clearBtn.remove();
                                    
                                    const inputEvent = new Event('input');
                                    this.dispatchEvent(inputEvent);
                                });
                                
                                this.parentNode.appendChild(clearBtn);
                            }
                        } else {
                            const clearBtn = this.parentNode.querySelector('.search-clear');
                            if (clearBtn) clearBtn.remove();
                        }
                    });
                });
            }
            
            document.querySelectorAll('#createTemplateModal .category').forEach(cat => {
                cat.addEventListener('click', function() {
                    document.querySelectorAll('#createTemplateModal .category').forEach(c => {
                        c.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            document.querySelectorAll('#editTemplateModal .category').forEach(cat => {
                cat.addEventListener('click', function() {
                    document.querySelectorAll('#editTemplateModal .category').forEach(c => {
                        c.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            document.querySelectorAll('.action-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const action = this.getAttribute('data-action');
                    const templateId = this.getAttribute('data-id');
                    
                    if (action === 'edit') {
                        loadTemplateData(templateId);
                    } else if (action === 'delete') {
                        if (confirm('Are you sure you want to delete this template?')) {
                            deleteTemplate(templateId);
                        }
                    } else if (action === 'analytics') {
                        alert('Analytics feature coming soon!');
                    }
                });
            });
            
            function initializeTabs() {
                const tabs = document.querySelectorAll('#createTemplateModal .mobile-tab');
                const sections = document.querySelectorAll('#createTemplateModal .mobile-section');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        const targetSection = this.getAttribute('data-tab');
                        
                        tabs.forEach(t => t.classList.remove('active'));
                        sections.forEach(s => s.classList.remove('active'));
                        
                        this.classList.add('active');
                        document.querySelector(`#createTemplateModal .mobile-section[data-section="${targetSection}"]`).classList.add('active');
                    });
                });
                
                const editTabs = document.querySelectorAll('#editTemplateModal .mobile-tab');
                const editSections = document.querySelectorAll('#editTemplateModal .mobile-section');
                
                editTabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        const targetSection = this.getAttribute('data-tab');
                        
                        editTabs.forEach(t => t.classList.remove('active'));
                        editSections.forEach(s => s.classList.remove('active'));
                        
                        this.classList.add('active');
                        document.querySelector(`#editTemplateModal .mobile-section[data-section="${targetSection}"]`).classList.add('active');
                    });
                });
            }
            
            function initializeCategorySelectors() {
                const categoryItems = document.querySelectorAll('#createTemplateModal .category-item');
                categoryItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const category = this.getAttribute('data-category');
                        
                        categoryItems.forEach(i => i.classList.remove('active'));
                        this.classList.add('active');
                        
                        currentCategory = category;
                        loadExercises(category);
                    });
                });
                
                const editCategoryItems = document.querySelectorAll('#editTemplateModal .edit-category-item');
                editCategoryItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const category = this.getAttribute('data-category');
                        
                        editCategoryItems.forEach(i => i.classList.remove('active'));
                        this.classList.add('active');
                        
                        editCurrentCategory = category;
                        loadEditExercises(category);
                    });
                });
            }
            
            function loadExercises(category) {
                const exercisesGrid = document.getElementById('exercisesGrid');
                exercisesGrid.innerHTML = '<div class="loading">Loading exercises...</div>';
                
                const exercises = getExercisesByCategory(category);
                renderExerciseGrid(exercises, exercisesGrid, false);
            }
            
            function loadEditExercises(category) {
                const exercisesGrid = document.getElementById('editExercisesGrid');
                exercisesGrid.innerHTML = '<div class="loading">Loading exercises...</div>';
                
                const exercises = getExercisesByCategory(category);
                renderExerciseGrid(exercises, exercisesGrid, true);
            }
            
            function getExercisesByCategory(category) {
                return allExercises;
            }
            
            function renderExerciseGrid(exercises, container, isEdit) {
                container.innerHTML = '';
                
                if (exercises.length === 0) {
                    container.innerHTML = '<div class="empty-state">No exercises found for this category</div>';
                    return;
                }
                
                exercises.forEach(exercise => {
                    const exerciseItem = document.createElement('div');
                    exerciseItem.className = 'exercise-item';
                    exerciseItem.setAttribute('data-id', exercise.id);
                    exerciseItem.setAttribute('data-name', exercise.name);
                    
                    exerciseItem.innerHTML = `
                        <div class="exercise-image">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="exercise-name">${exercise.name}</div>
                    `;
                    
                    if (isMobile) {
                        exerciseItem.addEventListener('touchstart', function(e) {
                            this.style.opacity = '0.7';
                        }, { passive: true });
                        
                        exerciseItem.addEventListener('touchend', function(e) {
                            this.style.opacity = '1';
                            
                            if (isEdit) {
                                addToEditSelected(exercise);
                            } else {
                                addToSelected(exercise);
                            }
                        });
                    } else {
                        exerciseItem.addEventListener('click', function() {
                            if (isEdit) {
                                addToEditSelected(exercise);
                            } else {
                                addToSelected(exercise);
                            }
                        });
                    }
                    
                    container.appendChild(exerciseItem);
                });
            }
            
            function addToSelected(exercise) {
                if (selectedExercises.some(ex => ex.id === exercise.id)) {
                    alert('Exercise already added');
                    return;
                }
                
                selectedExercises.push(exercise);
                renderSelectedExercises();
                updateEstimatedTime();
            }
            
            function addToEditSelected(exercise) {
                if (editSelectedExercises.some(ex => ex.id === exercise.id)) {
                    alert('Exercise already added');
                    return;
                }
                
                editSelectedExercises.push(exercise);
                renderEditSelectedExercises();
                updateEditEstimatedTime();
            }
            
            function renderSelectedExercises() {
                const container = document.getElementById('selectedExercisesList');
            
                container.innerHTML = '';
                
                if (selectedExercises.length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-selection';
                    emptyDiv.id = 'emptySelection';
                    emptyDiv.innerHTML = '<p>No exercises selected yet</p>';
                    container.appendChild(emptyDiv);
                    return;
                }
                
                selectedExercises.forEach((exercise, index) => {
                    const exerciseElement = document.createElement('div');
                    exerciseElement.className = 'selected-exercise';
                    exerciseElement.innerHTML = `
                        <div class="selected-exercise-name">${exercise.name}</div>
                        <div class="selected-exercise-details">
                            Sets: <span class="sets-count">${exercise.sets || document.getElementById('setsPerExercise').value}</span>
                        </div>
                        <div class="selected-exercise-actions">
                            <button class="settings-btn" data-index="${index}"><i class="fas fa-cog"></i></button>
                            <button class="move-up" data-index="${index}" ${index === 0 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                            <button class="move-down" data-index="${index}" ${index === selectedExercises.length - 1 ? 'disabled' : ''}><i class="fas fa-arrow-down"></i></button>
                            <button class="remove" data-index="${index}"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="exercise-settings">
                            <h5>Exercise Settings</h5>
                            <input type="number" class="custom-sets" min="1" max="20" value="${exercise.sets || document.getElementById('setsPerExercise').value}">
                            <div class="settings-actions">
                                <button class="cancel-settings">Cancel</button>
                                <button class="save-settings">Save</button>
                            </div>
                        </div>
                    `;
                    
                    const settingsBtn = exerciseElement.querySelector('.settings-btn');
                    const settingsPanel = exerciseElement.querySelector('.exercise-settings');
                    const customSetsInput = exerciseElement.querySelector('.custom-sets');
                    const saveSettingsBtn = exerciseElement.querySelector('.save-settings');
                    const cancelSettingsBtn = exerciseElement.querySelector('.cancel-settings');
                    
                    settingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        document.querySelectorAll('.exercise-settings.active').forEach(panel => {
                            if (panel !== settingsPanel) {
                                panel.classList.remove('active');
                            }
                        });
                        settingsPanel.classList.toggle('active');
                    });
                    
                    saveSettingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const newSets = parseInt(customSetsInput.value);
                        if (newSets >= 1 && newSets <= 20) {
                            selectedExercises[index].sets = newSets;
                            exerciseElement.querySelector('.sets-count').textContent = newSets;
                            settingsPanel.classList.remove('active');
                            updateEstimatedTime();
                        } else {
                            alert('Please enter a valid number of sets (1-20)');
                        }
                    });
                    
                    cancelSettingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        settingsPanel.classList.remove('active');
                        customSetsInput.value = selectedExercises[index].sets || document.getElementById('setsPerExercise').value;
                    });
                    
                    document.addEventListener('click', function(e) {
                        if (!settingsPanel.contains(e.target) && !settingsBtn.contains(e.target)) {
                            settingsPanel.classList.remove('active');
                        }
                    });
                    
                    exerciseElement.querySelector('.remove').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        selectedExercises.splice(idx, 1);
                        renderSelectedExercises();
                        updateEstimatedTime();
                    });
                    
                    exerciseElement.querySelector('.move-up').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        if (idx > 0) {
                            const temp = selectedExercises[idx];
                            selectedExercises[idx] = selectedExercises[idx - 1];
                            selectedExercises[idx - 1] = temp;
                            renderSelectedExercises();
                        }
                    });
                    
                    exerciseElement.querySelector('.move-down').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        if (idx < selectedExercises.length - 1) {
                            const temp = selectedExercises[idx];
                            selectedExercises[idx] = selectedExercises[idx + 1];
                            selectedExercises[idx + 1] = temp;
                            renderSelectedExercises();
                        }
                    });
                    
                    container.appendChild(exerciseElement);
                });
            }
            
            function renderEditSelectedExercises() {
                const container = document.getElementById('editSelectedExercisesList');
                
                container.innerHTML = '';
                
                if (editSelectedExercises.length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-selection';
                    emptyDiv.id = 'editEmptySelection';
                    emptyDiv.innerHTML = '<p>No exercises selected yet</p>';
                    container.appendChild(emptyDiv);
                    return;
                }
                
                editSelectedExercises.forEach((exercise, index) => {
                    const exerciseElement = document.createElement('div');
                    exerciseElement.className = 'selected-exercise';
                    exerciseElement.innerHTML = `
                        <div class="selected-exercise-name">${exercise.name}</div>
                        <div class="selected-exercise-details">
                            Sets: <span class="sets-count">${exercise.sets || document.getElementById('editSetsPerExercise').value}</span>
                        </div>
                        <div class="selected-exercise-actions">
                            <button class="settings-btn" data-index="${index}"><i class="fas fa-cog"></i></button>
                            <button class="move-up" data-index="${index}" ${index === 0 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                            <button class="move-down" data-index="${index}" ${index === editSelectedExercises.length - 1 ? 'disabled' : ''}><i class="fas fa-arrow-down"></i></button>
                            <button class="remove" data-index="${index}"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="exercise-settings">
                            <h5>Exercise Settings</h5>
                            <input type="number" class="custom-sets" min="1" max="20" value="${exercise.sets || document.getElementById('editSetsPerExercise').value}">
                            <div class="settings-actions">
                                <button class="cancel-settings">Cancel</button>
                                <button class="save-settings">Save</button>
                            </div>
                        </div>
                    `;
                    
                    const settingsBtn = exerciseElement.querySelector('.settings-btn');
                    const settingsPanel = exerciseElement.querySelector('.exercise-settings');
                    const customSetsInput = exerciseElement.querySelector('.custom-sets');
                    const saveSettingsBtn = exerciseElement.querySelector('.save-settings');
                    const cancelSettingsBtn = exerciseElement.querySelector('.cancel-settings');
                    
                    settingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        document.querySelectorAll('.exercise-settings.active').forEach(panel => {
                            if (panel !== settingsPanel) {
                                panel.classList.remove('active');
                            }
                        });
                        settingsPanel.classList.toggle('active');
                    });
                    
                    saveSettingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const newSets = parseInt(customSetsInput.value);
                        if (newSets >= 1 && newSets <= 20) {
                            editSelectedExercises[index].sets = newSets;
                            exerciseElement.querySelector('.sets-count').textContent = newSets;
                            settingsPanel.classList.remove('active');
                            updateEditEstimatedTime();
                        } else {
                            alert('Please enter a valid number of sets (1-20)');
                        }
                    });
                    
                    cancelSettingsBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        settingsPanel.classList.remove('active');
                        customSetsInput.value = editSelectedExercises[index].sets || document.getElementById('editSetsPerExercise').value;
                    });
                    
                    document.addEventListener('click', function(e) {
                        if (!settingsPanel.contains(e.target) && !settingsBtn.contains(e.target)) {
                            settingsPanel.classList.remove('active');
                        }
                    });
                    
                    exerciseElement.querySelector('.remove').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        editSelectedExercises.splice(idx, 1);
                        renderEditSelectedExercises();
                        updateEditEstimatedTime();
                    });
                    
                    exerciseElement.querySelector('.move-up').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        if (idx > 0) {
                            const temp = editSelectedExercises[idx];
                            editSelectedExercises[idx] = editSelectedExercises[idx - 1];
                            editSelectedExercises[idx - 1] = temp;
                            renderEditSelectedExercises();
                        }
                    });
                    
                    exerciseElement.querySelector('.move-down').addEventListener('click', function() {
                        const idx = parseInt(this.getAttribute('data-index'));
                        if (idx < editSelectedExercises.length - 1) {
                            const temp = editSelectedExercises[idx];
                            editSelectedExercises[idx] = editSelectedExercises[idx + 1];
                            editSelectedExercises[idx + 1] = temp;
                            renderEditSelectedExercises();
                        }
                    });
                    
                    container.appendChild(exerciseElement);
                });
            }
            
            function filterExercises(query) {
                const exerciseItems = document.querySelectorAll('#exercisesGrid .exercise-item');
                
                exerciseItems.forEach(item => {
                    const name = item.getAttribute('data-name').toLowerCase();
                    if (name.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
            
            function filterEditExercises(query) {
                const exerciseItems = document.querySelectorAll('#editExercisesGrid .exercise-item');
                
                exerciseItems.forEach(item => {
                    const name = item.getAttribute('data-name').toLowerCase();
                    if (name.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
            
            function updateEstimatedTime() {
                const sets = parseInt(document.getElementById('setsPerExercise').value) || 3;
                const restTime = parseFloat(document.getElementById('restTimePerExercise').value) || 1;
                const exerciseCount = selectedExercises.length;
                const difficultyLevel = parseInt(document.getElementById('difficultySlider').value);
                
                let timePerExercise = 1; 
                
                const difficultyMultipliers = [0.7, 0.8, 0.9, 1.0, 1.2, 1.4];
                timePerExercise *= difficultyMultipliers[difficultyLevel];
                
                const totalTime = Math.round(exerciseCount * (sets * timePerExercise + (sets - 1) * restTime));
                document.getElementById('estimatedTime').value = totalTime > 0 ? totalTime : 0;
            }
            
            function updateEditEstimatedTime() {
                const sets = parseInt(document.getElementById('editSetsPerExercise').value) || 3;
                const restTime = parseFloat(document.getElementById('editRestTimePerExercise').value) || 1;
                const exerciseCount = editSelectedExercises.length;
                const difficultyLevel = parseInt(document.getElementById('editDifficultySlider').value);
                
                let timePerExercise = 1; 
                
                const difficultyMultipliers = [0.7, 0.8, 0.9, 1.0, 1.2, 1.4];
                timePerExercise *= difficultyMultipliers[difficultyLevel];
                
                const totalTime = Math.round(exerciseCount * (sets * timePerExercise + (sets - 1) * restTime));
                document.getElementById('editEstimatedTime').value = totalTime > 0 ? totalTime : 0;
            }
            
            function saveTemplate() {
                const templateName = document.getElementById('workoutName').value;
                const description = document.getElementById('workoutDescription').value;
                const categoryElement = document.querySelector('#createTemplateModal .category.active');
                const category = categoryElement ? categoryElement.getAttribute('data-category') : '';
                const difficultyLevel = document.getElementById('difficultySlider').value;
                const sets = document.getElementById('setsPerExercise').value;
                const restTime = document.getElementById('restTimePerExercise').value;
                const estimatedTime = document.getElementById('estimatedTime').value;
                
                if (!templateName) {
                    alert('Please enter a template name');
                    return;
                }
                
                if (selectedExercises.length === 0) {
                    alert('Please add at least one exercise');
                    return;
                }
                
                const difficultyMap = {
                    0: 'beginner',
                    1: 'easy',
                    2: 'medium',
                    3: 'intermediate',
                    4: 'hard',
                    5: 'expert'
                };
                
                const templateData = {
                    name: templateName,
                    description: description,
                    category: category,
                    difficulty: difficultyMap[difficultyLevel],
                    estimated_time: estimatedTime,
                    sets_per_exercise: sets,
                    rest_time: restTime,
                    user_id: <?php echo $user_id; ?>,
                    exercises: selectedExercises.map((ex, index) => ({
                        exercise_id: ex.id,
                        position: index + 1,
                        sets: sets,
                        rest_time: restTime
                    }))
                };
                
                console.log('Sending template data:', templateData);
                
                fetch('/admin/api/save_template.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(templateData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            console.error('Server response:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        alert('Template saved successfully!');
                        document.getElementById('createTemplateModal').classList.remove('active');
                        document.body.style.overflow = '';
                        
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to save template'));
                    }
                })
                .catch(error => {
                    console.error('Error saving template:', error);
                    alert('Failed to save template. Please try again. Error: ' + error.message);
                });
            }
            
            function loadTemplateData(templateId) {
                document.getElementById('editTemplateModal').classList.add('active');
                document.body.style.overflow = 'hidden';
                
                editSelectedExercises = [];
                
                fetch(`/admin/api/get_template.php?id=${templateId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to load template data');
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error('Received non-JSON response:', text);
                            throw new Error('Invalid response format from server');
                        });
                    }
                })
                .then(data => {
                    console.log('Template data loaded:', data);
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    document.getElementById('editTemplateId').value = data.id;
                    document.getElementById('editWorkoutName').value = data.name;
                    document.getElementById('editWorkoutDescription').value = data.description || '';
                    
                    let difficultyValue = 2;
                    if (data.difficulty === 'beginner') difficultyValue = 1;
                    if (data.difficulty === 'advanced') difficultyValue = 3;
                    document.getElementById('editDifficultySlider').value = difficultyValue;
                    
                    document.getElementById('editSetsPerExercise').value = data.sets_per_exercise || 3;
                    document.getElementById('editRestTimePerExercise').value = data.rest_time || 1;
                    document.getElementById('editEstimatedTime').value = data.estimated_time || 45;
                    
                    const categories = document.querySelectorAll('#editTemplateModal .category');
                    categories.forEach(c => {
                        c.classList.remove('active');
                        if (c.getAttribute('data-category') === data.category) {
                            c.classList.add('active');
                        }
                    });
                    
                    if (!document.querySelector('#editTemplateModal .category.active')) {
                        categories[0].classList.add('active');
                    }
                    
                    if (data.exercises && data.exercises.length > 0) {
                        editSelectedExercises = data.exercises;
                        renderEditSelectedExercises();
                    }
                    
                    const activeCategoryElem = document.querySelector('#editTemplateModal .edit-category-item.active');
                    const activeCategory = activeCategoryElem ? activeCategoryElem.getAttribute('data-category') : 'Strength Training';
                    loadEditExercises(activeCategory);
                })
                .catch(error => {
                    console.error('Error loading template data:', error);
                    alert('Failed to load template data: ' + error.message);
                    document.getElementById('editTemplateModal').classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            function updateTemplate() {
                const templateId = document.getElementById('editTemplateId').value;
                const templateName = document.getElementById('editWorkoutName').value;
                const description = document.getElementById('editWorkoutDescription').value;
                const categoryElement = document.querySelector('#editTemplateModal .category.active');
                const category = categoryElement ? categoryElement.getAttribute('data-category') : '';
                const difficultyLevel = document.getElementById('editDifficultySlider').value;
                const sets = document.getElementById('editSetsPerExercise').value;
                const restTime = document.getElementById('editRestTimePerExercise').value;
                const estimatedTime = document.getElementById('editEstimatedTime').value;
                
                if (!templateName) {
                    alert('Please enter a template name');
                    return;
                }
                
                if (editSelectedExercises.length === 0) {
                    alert('Please add at least one exercise');
                    return;
                }
                
                const difficultyMap = {
                    0: 'beginner',
                    1: 'easy',
                    2: 'medium',
                    3: 'intermediate',
                    4: 'hard',
                    5: 'expert'
                };
                
                const templateData = {
                    id: templateId,
                    name: templateName,
                    description: description,
                    category: category,
                    difficulty: difficultyMap[difficultyLevel],
                    estimated_time: estimatedTime,
                    sets_per_exercise: sets,
                    rest_time: restTime,
                    exercises: editSelectedExercises.map((ex, index) => ({
                        exercise_id: ex.id,
                        position: index + 1,
                        sets: sets,
                        rest_time: restTime
                    }))
                };
                
                console.log('Sending template update data:', templateData);
                
                fetch('/admin/api/update_template.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(templateData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            console.error('Server response:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        alert('Template updated successfully!');
                        document.getElementById('editTemplateModal').classList.remove('active');
                        document.body.style.overflow = '';
                
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update template'));
                    }
                })
                .catch(error => {
                    console.error('Error updating template:', error);
                    alert('Failed to update template. Please try again. Error: ' + error.message);
                });
            }
            
            function deleteTemplate(templateId) {
                console.log('Deleting template with ID:', templateId);
                
                fetch(`/admin/api/delete_template.php?id=${templateId}`, {
                    method: 'DELETE'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to delete template');
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error('Received non-JSON response:', text);
                            return { success: true }; 
                        });
                    }
                })
                .then(data => {
                    console.log('Template deleted successfully', data);
                    const templateCard = document.querySelector(`.template-card[data-id="${templateId}"]`);
                    if (templateCard) {
                        templateCard.remove();
                    }
                    alert('Template deleted successfully!');
                })
                .catch(error => {
                    console.error('Error deleting template:', error);
                    alert('Failed to delete template: ' + error.message);
                });
            }
    
            initializeDifficultySlider('difficultySlider');
            initializeDifficultySlider('editDifficultySlider');
            
            function initializeDifficultySlider(sliderId) {
                const slider = document.getElementById(sliderId);
                const container = slider.closest('.difficulty-slider-container');
                const valueDisplay = container.querySelector('.difficulty-value');
                const labels = container.querySelectorAll('.slider-labels span');
                
                const difficultyLabels = ['Beginner', 'Easy', 'Medium', 'Intermediate', 'Hard', 'Expert'];
                
                function updateSlider() {
                    const value = parseInt(slider.value);
                    const percent = (value / 5) * 100;
                    
                    slider.style.setProperty('--progress', `${percent}%`);
                    
                    valueDisplay.textContent = difficultyLabels[value];
            
                    labels.forEach((label, index) => {
                        if (index === value) {
                            label.classList.add('active');
                        } else {
                            label.classList.remove('active');
                        }
                    });
                }
                
                labels.forEach((label, index) => {
                    label.addEventListener('click', () => {
                        slider.value = index;
                        updateSlider();
                        slider.dispatchEvent(new Event('input'));
                    });
                });
                
                slider.addEventListener('input', updateSlider);
                
                updateSlider();
            }
        });
    </script>
</body>
</html> 