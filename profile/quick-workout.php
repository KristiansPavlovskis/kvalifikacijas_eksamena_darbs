<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/quick-workout.php");
    exit;
}

require_once '../assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Fetch user's exercise history (fix for lines 22-24)
try {
    if (tableExists($conn, 'workouts') && tableExists($conn, 'workout_exercises') && tableExists($conn, 'exercise_sets')) {
        $exercise_history_query = "SELECT DISTINCT 
                                IF(we.exercise_name IS NOT NULL, we.exercise_name, 'Custom Exercise') as exercise_name, 
                                MAX(es.weight) as weight, 
                                AVG(es.reps) as reps, 
                                COUNT(es.id) as sets
                            FROM workouts w
                            LEFT JOIN workout_exercises we ON w.id = we.workout_id
                            LEFT JOIN exercise_sets es ON we.id = es.exercise_id
                            WHERE w.user_id = ? 
                            GROUP BY exercise_name
                            ORDER BY MAX(w.created_at) DESC 
                            LIMIT 50";
        $stmt = mysqli_prepare($conn, $exercise_history_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare exercise history query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $exercise_history = mysqli_stmt_get_result($stmt);
    } else {
        $exercise_history = false;
    }
} catch (Exception $e) {
    error_log("Error fetching exercise history: " . $e->getMessage());
    $exercise_history = false;
}

// Fetch common exercises
try {
    if (tableExists($conn, 'exercise_library')) {
        $common_exercises_query = "SELECT exercise_name, 
                              el.muscle_group_id as muscle_group, 
                              el.equipment_id as equipment_needed 
                              FROM exercise_library el
                              ORDER BY popularity DESC 
                              LIMIT 20";
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

// Fetch user's favorite exercises
try {
    if (tableExists($conn, 'user_favorite_exercises') && tableExists($conn, 'exercise_library')) {
        $favorites_query = "SELECT el.exercise_name 
                        FROM user_favorite_exercises uf
                        JOIN exercise_library el ON uf.exercise_id = el.id
                        WHERE uf.user_id = ?";
        $stmt = mysqli_prepare($conn, $favorites_query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare favorites query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $favorites = mysqli_stmt_get_result($stmt);
    } else {
        $favorites = false;
    }
} catch (Exception $e) {
    error_log("Error fetching favorite exercises: " . $e->getMessage());
    $favorites = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Quick Workout</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
    <style>
        /* Navbar styles */
        .navbar {
            background-color: #1E1E1E;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1000;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
            font-family: 'Koulen', sans-serif;
            font-size: 1.5rem;
        }

        .navbar .logo i {
            color: #FF4D4D;
            font-size: 1.8rem;
        }

        .navbar nav ul {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .navbar nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .navbar nav ul li a i {
            font-size: 1.1rem;
        }

        .navbar nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .navbar nav ul li a.active {
            background-color: #FF4D4D;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .navbar nav ul {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 1rem;
                gap: 10px;
            }

            .navbar nav ul li a {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
        
        /* Quick workout styles */
        .qw-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-family: 'Poppins', sans-serif;
        }

        /* Steps navigation */
        .qw-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .qw-steps::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .qw-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex-grow: 1;
        }

        .qw-step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .qw-step.active .qw-step-circle {
            background-color: #FF4D4D;
            color: white;
            box-shadow: 0 0 10px rgba(255, 77, 77, 0.5);
        }

        .qw-step.completed .qw-step-circle {
            background-color: #28a745;
            color: white;
        }

        .qw-step-label {
            font-weight: 500;
            color: #555;
            margin-top: 8px;
        }

        .qw-step.active .qw-step-label {
            color: #FF4D4D;
            font-weight: 600;
        }

        .qw-step.completed .qw-step-label {
            color: #28a745;
        }

        /* Step content containers */
        .qw-step-content {
            display: none;
        }

        .qw-step-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Step headers */
        .qw-step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 15px;
        }

        .qw-step-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }

        .qw-step-instructions {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-left: 4px solid #FF4D4D;
            border-radius: 4px;
            color: #555;
        }

        /* Timer Section (Step 2) */
        .qw-timer-section {
            display: flex;
            justify-content: space-between;
            background-color: #1e1e1e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: white;
        }

        .qw-workout-timer {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qw-workout-timer span {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .qw-timer-controls {
            display: flex;
            gap: 10px;
        }

        /* Navigation buttons */
        .qw-step-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }

        /* Button styles */
        .qw-btn {
            background-color: #3a3a3a;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .qw-btn:hover {
            background-color: #4a4a4a;
            transform: translateY(-2px);
        }

        .qw-btn-primary {
            background-color: #FF4D4D;
        }

        .qw-btn-primary:hover {
            background-color: #ff6b6b;
        }

        .qw-btn-secondary {
            background-color: #3a3a3a;
        }

        .qw-btn-secondary:hover {
            background-color: #4a4a4a;
        }

        .qw-btn-warning {
            background-color: #ffa200;
        }

        .qw-btn-warning:hover {
            background-color: #ffb733;
        }

        .qw-btn-danger {
            background-color: #dc3545;
        }

        .qw-btn-danger:hover {
            background-color: #e25563;
        }

        .qw-btn-success {
            background-color: #28a745;
        }

        .qw-btn-success:hover {
            background-color: #2fd152;
        }

        .qw-btn-large {
            padding: 12px 24px;
            font-size: 1.1rem;
        }

        .qw-btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            justify-content: center;
        }

        /* Exercise selection (Step 1) */
        .qw-search-container {
            margin-bottom: 20px;
        }

        .qw-search-wrapper {
            display: flex;
            margin-bottom: 15px;
        }

        .qw-search-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px 0 0 6px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .qw-search-input:focus {
            border-color: #FF4D4D;
            box-shadow: 0 0 0 2px rgba(255, 77, 77, 0.2);
        }

        .qw-search-filter-btn {
            background-color: #FF4D4D;
            color: white;
            border: none;
            border-radius: 0 6px 6px 0;
            padding: 0 15px;
            cursor: pointer;
        }

        .qw-quick-categories {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .qw-category-btn {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            color: #555;
        }

        .qw-category-btn:hover {
            background-color: #f9f9f9;
            transform: translateY(-2px);
        }

        .qw-category-btn.active {
            background-color: #FF4D4D;
            color: white;
            border-color: #FF4D4D;
        }

        .qw-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .qw-exercise-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .qw-exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .qw-exercise-card-header h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        /* Selected exercises */
        .qw-selected-exercises {
            margin-top: 30px;
        }

        .qw-selected-exercises h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
        }

        .qw-selected-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 5px;
        }

        .qw-selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .qw-selected-item:last-child {
            border-bottom: none;
        }

        .qw-selected-name {
            font-weight: 500;
        }

        /* Current workout (Step 2) */
        .qw-exercise-list {
            margin-top: 20px;
        }

        .qw-exercise-item {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 15px;
        }

        .qw-exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .qw-exercise-header h3 {
            margin: 0;
            color: #333;
        }

        .qw-exercise-actions {
            display: flex;
            gap: 8px;
        }

        .qw-sets-header {
            display: grid;
            grid-template-columns: 60px 1fr 1fr 1fr 60px;
            gap: 10px;
            padding: 10px 15px;
            background-color: #f5f5f5;
            border-radius: 6px;
            font-weight: 500;
            color: #555;
            margin-bottom: 10px;
        }

        .qw-set-item {
            display: grid;
            grid-template-columns: 60px 1fr 1fr 1fr 60px;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 8px;
            background-color: #f9f9f9;
            align-items: center;
        }

        .qw-set-item:hover {
            background-color: #f2f2f2;
        }

        .qw-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .qw-weight-input-wrapper, .qw-rpe-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .qw-unit {
            position: absolute;
            right: 10px;
            color: #777;
            pointer-events: none;
        }
        
        /* Template management (moved to step 3) */
        .qw-templates-section {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="logo">
            <a href="../index.php">
                <i class="fas fa-dumbbell"></i>
                <span>GYMVERSE</span>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../workouts.php"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                <li><a href="../excercises.php"><i class="fas fa-running"></i> Exercises</a></li>
                <li><a href="../quick-workout.php" class="active"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="qw-container">
        <!-- Step navigation -->
        <div class="qw-steps">
            <div class="qw-step active" id="step1">
                <div class="qw-step-circle">1</div>
                <div class="qw-step-label">Select Exercises</div>
            </div>
            <div class="qw-step" id="step2">
                <div class="qw-step-circle">2</div>
                <div class="qw-step-label">Perform Workout</div>
            </div>
            <div class="qw-step" id="step3">
                <div class="qw-step-circle">3</div>
                <div class="qw-step-label">Finish & Save</div>
            </div>
        </div>

        <!-- Step 1: Select Exercises -->
        <div class="qw-step-content active" id="step1-content">
            <div class="qw-step-header">
                <h2>Select Exercises for Your Workout</h2>
            </div>
            
            <div class="qw-step-instructions">
                <p><i class="fas fa-info-circle"></i> Start by selecting the exercises you want to include in your workout. You can search for specific exercises or browse by category.</p>
            </div>

            <div class="qw-search-container">
                <div class="qw-search-wrapper">
                    <input type="text" id="exerciseSearch" class="qw-search-input" placeholder="Search exercises...">
                    <button class="qw-search-filter-btn" id="filterBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <div id="searchResults" class="qw-search-results"></div>
            </div>
            
            <div class="qw-quick-categories">
                <button class="qw-category-btn active" data-category="recent">
                    <i class="fas fa-history"></i> Recent
                </button>
                <button class="qw-category-btn" data-category="favorites">
                    <i class="fas fa-star"></i> Favorites
                </button>
                <button class="qw-category-btn" data-category="popular">
                    <i class="fas fa-fire"></i> Popular
                </button>
                <button class="qw-category-btn" data-category="categories">
                    <i class="fas fa-th-large"></i> Categories
                </button>
            </div>

            <!-- Exercise Categories Panel -->
            <div class="qw-categories-panel" id="categoriesPanel">
                <div class="qw-categories-section">
                    <h3>Body Parts</h3>
                    <div class="qw-category-grid" id="bodyPartGrid"></div>
                </div>
                <div class="qw-categories-section">
                    <h3>Equipment</h3>
                    <div class="qw-category-grid" id="equipmentGrid"></div>
                </div>
            </div>

            <!-- Exercise Results -->
            <div class="qw-exercise-results" id="exerciseResults"></div>
            
            <!-- Selected exercises preview -->
            <div class="qw-selected-exercises" id="selectedExercisesContainer">
                <h3>Selected Exercises <span id="selectedCount">(0)</span></h3>
                <div class="qw-selected-list" id="selectedExercisesList">
                    <div class="qw-empty-selection">No exercises selected yet. Click on exercises above to add them.</div>
                </div>
            </div>
            
            <div class="qw-step-navigation">
                <div></div> <!-- Empty div for alignment -->
                <button id="goToStep2" class="qw-btn qw-btn-primary qw-btn-large">
                    Start Workout <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Perform Workout -->
        <div class="qw-step-content" id="step2-content">
            <div class="qw-step-header">
                <h2>Perform Your Workout</h2>
                <span id="workoutDuration" class="qw-workout-duration">00:00:00</span>
            </div>
            
            <div class="qw-step-instructions">
                <p><i class="fas fa-info-circle"></i> Add sets as you perform each exercise. Track your weights, reps, and RPE. Use the rest timer between sets.</p>
            </div>

            <!-- Timer Section -->
            <div class="qw-timer-section">
                <div class="qw-workout-timer">
                    <span id="timer">00:00:00</span>
                    <div class="qw-timer-controls">
                        <button id="startTimer" class="qw-btn qw-btn-primary"><i class="fas fa-play"></i></button>
                        <button id="pauseTimer" class="qw-btn qw-btn-warning" style="display: none;"><i class="fas fa-pause"></i></button>
                        <button id="resetTimer" class="qw-btn qw-btn-danger"><i class="fas fa-redo"></i></button>
                    </div>
                </div>
                <div class="qw-rest-timer">
                    <span id="restTimer">Rest: 00:00</span>
                    <button id="startRest" class="qw-btn qw-btn-secondary">Start Rest Timer</button>
                </div>
            </div>

            <!-- Current Workout Section -->
            <div id="exerciseList" class="qw-exercise-list"></div>
            
            <div class="qw-step-navigation">
                <button id="backToStep1" class="qw-btn qw-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Exercises
                </button>
                <button id="goToStep3" class="qw-btn qw-btn-primary qw-btn-large">
                    Complete Workout <i class="fas fa-check"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Finish & Save -->
        <div class="qw-step-content" id="step3-content">
            <div class="qw-step-header">
                <h2>Workout Summary</h2>
            </div>
            
            <div class="qw-step-instructions">
                <p><i class="fas fa-info-circle"></i> Review your workout details below. You can save this workout or save it as a template for future use.</p>
            </div>

            <!-- Workout Summary -->
            <div class="qw-workout-summary">
                <div class="qw-summary-stats">
                    <div class="qw-stat">
                        <span class="qw-stat-label">Exercises</span>
                        <span class="qw-stat-value" id="totalExercises">0</span>
                    </div>
                    <div class="qw-stat">
                        <span class="qw-stat-label">Total Sets</span>
                        <span class="qw-stat-value" id="totalSets">0</span>
                    </div>
                    <div class="qw-stat">
                        <span class="qw-stat-label">Volume (kg)</span>
                        <span class="qw-stat-value" id="totalVolume">0</span>
                    </div>
                    <div class="qw-stat">
                        <span class="qw-stat-label">Duration</span>
                        <span class="qw-stat-value" id="summaryDuration">00:00:00</span>
                    </div>
                </div>
                
                <form id="workoutSummaryForm" class="qw-form">
                    <div class="qw-form-group">
                        <label for="workoutName">Workout Name</label>
                        <input type="text" id="workoutName" class="qw-input" placeholder="e.g., Quick Leg Day">
                    </div>
                    <div class="qw-form-group">
                        <label for="workoutNotes">Notes</label>
                        <textarea id="workoutNotes" class="qw-input" rows="3" placeholder="How did this workout feel?"></textarea>
                    </div>
                    <div class="qw-form-group">
                        <label>Rate this workout</label>
                        <div class="qw-rating">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                    </div>
                </form>
                
                <div class="qw-step-navigation">
                    <button id="backToStep2" class="qw-btn qw-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Workout
                    </button>
                    <div>
                        <button id="saveAsTemplate" class="qw-btn qw-btn-secondary">
                            <i class="fas fa-save"></i> Save as Template
                        </button>
                        <button id="finishWorkout" class="qw-btn qw-btn-success qw-btn-large">
                            <i class="fas fa-check"></i> Finish Workout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Management Section (Hidden initially) -->
        <div class="qw-templates-section" id="templatesSection" style="display: none;">
            <div class="qw-templates-header">
                <h2>Workout Templates</h2>
                <button id="createTemplateBtn" class="qw-btn qw-btn-primary">
                    <i class="fas fa-plus"></i> Create Template
                </button>
            </div>
            
            <div class="qw-templates-container" id="templatesContainer">
                <div class="qw-templates-loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading templates...
                </div>
            </div>
        </div>
        
        <!-- Exercise Template (Hidden) -->
        <template id="exerciseTemplate">
            <div class="qw-exercise-item">
                <div class="qw-exercise-header">
                    <h3 class="qw-exercise-name"></h3>
                    <div class="qw-exercise-actions">
                        <button class="qw-btn qw-btn-icon qw-history-btn" title="View History">
                            <i class="fas fa-history"></i>
                        </button>
                        <button class="qw-btn qw-btn-icon qw-favorite-btn" title="Add to Favorites">
                            <i class="far fa-star"></i>
                        </button>
                        <button class="qw-btn qw-btn-icon qw-remove-btn" title="Remove Exercise">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="qw-personal-bests-bar">
                    <span class="qw-pb-badge" title="Personal Best">
                        <i class="fas fa-trophy"></i> <span class="qw-pb-value">--</span>
                    </span>
                    <span class="qw-volume-badge" title="Total Volume">
                        <i class="fas fa-chart-line"></i> <span class="qw-volume-value">0</span> kg
                    </span>
                </div>
                
                <div class="qw-sets-container">
                    <div class="qw-sets-header">
                        <span class="qw-set-col">Set</span>
                        <span class="qw-weight-col">Weight (kg)</span>
                        <span class="qw-reps-col">Reps</span>
                        <span class="qw-rpe-col">RPE</span>
                        <span class="qw-actions-col"></span>
                    </div>
                    <div class="qw-sets-list"></div>
                    <div class="qw-sets-actions">
                        <button class="qw-btn qw-btn-secondary qw-add-set-btn">
                            <i class="fas fa-plus"></i> Add Set
                        </button>
                        <button class="qw-btn qw-btn-outline qw-recommend-btn">
                            <i class="fas fa-magic"></i> Recommend
                        </button>
                    </div>
                </div>
                
                <div class="qw-exercise-notes">
                    <textarea placeholder="Add notes for this exercise..." rows="2"></textarea>
                </div>
            </div>
        </template>

        <!-- Set Template (Hidden) -->
        <template id="setTemplate">
            <div class="qw-set-item">
                <span class="qw-set-number"></span>
                <div class="qw-weight-input-wrapper">
                    <input type="number" class="qw-input qw-weight-input" placeholder="0" step="2.5" min="0">
                    <span class="qw-unit">kg</span>
                </div>
                <input type="number" class="qw-input qw-reps-input" placeholder="0" min="0">
                <div class="qw-rpe-input-wrapper">
                    <input type="number" class="qw-input qw-rpe-input" placeholder="RPE" min="1" max="10">
                    <span class="qw-rpe-help" title="Click for RPE help">
                        <i class="fas fa-question-circle"></i>
                    </span>
                </div>
                <div class="qw-set-actions">
                    <button class="qw-btn qw-btn-icon qw-remove-set-btn" title="Remove Set">
                        <i class="fas fa-times"></i>
                    </button>
                    <button class="qw-btn qw-btn-icon qw-start-rest-btn" title="Start Rest Timer">
                        <i class="fas fa-stopwatch"></i>
                    </button>
                </div>
            </div>
        </template>

        <!-- Selected Exercise Template (Hidden) -->
        <template id="selectedExerciseTemplate">
            <div class="qw-selected-item">
                <span class="qw-selected-name"></span>
                <button class="qw-btn qw-btn-icon qw-remove-selected-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>

    <script>
        // Variables
        let startTime = null;
        let totalSeconds = 0;
        let timerInterval = null;
        let activeRestTimer = null;
        let activeRestInterval = null;
        let selectedExercises = [];
        let rpeGuidelines = [];
        let templates = [];

        // Initialize the application when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Step navigation
            initializeStepNavigation();
            
            // Exercise selection (Step 1)
            document.getElementById('exerciseSearch').addEventListener('input', handleExerciseSearch);
            document.querySelector('.qw-quick-categories').addEventListener('click', handleCategoryClick);
            document.getElementById('exerciseResults').addEventListener('click', handleExerciseClick);
            document.getElementById('selectedExercisesList').addEventListener('click', handleRemoveSelectedExercise);
            
            // Timer and workout (Step 2)
            document.getElementById('startTimer').addEventListener('click', startWorkoutTimer);
            document.getElementById('pauseTimer').addEventListener('click', pauseWorkoutTimer);
            document.getElementById('resetTimer').addEventListener('click', resetWorkoutTimer);
            document.getElementById('startRest').addEventListener('click', startRestTimer);
            document.getElementById('exerciseList').addEventListener('click', handleExerciseListClick);
            
            // Workout summary (Step 3)
            document.getElementById('workoutSummaryForm').addEventListener('submit', handleWorkoutComplete);
            document.querySelector('.qw-rating').addEventListener('click', handleRatingClick);
            document.getElementById('saveAsTemplate').addEventListener('click', saveWorkoutAsTemplate);
            document.getElementById('finishWorkout').addEventListener('click', finishWorkout);
            
            // Load initial exercises
            loadExercises('recent');
        });

        // Step Navigation Functions
        function initializeStepNavigation() {
            document.getElementById('goToStep2').addEventListener('click', function() {
                if (selectedExercises.length === 0) {
                    alert('Please select at least one exercise before starting your workout.');
                    return;
                }
                goToStep(2);
                setupWorkout();
            });
            
            document.getElementById('backToStep1').addEventListener('click', function() {
                goToStep(1);
            });
            
            document.getElementById('goToStep3').addEventListener('click', function() {
                goToStep(3);
                updateWorkoutSummary();
                document.getElementById('summaryDuration').textContent = document.getElementById('timer').textContent;
            });
            
            document.getElementById('backToStep2').addEventListener('click', function() {
                goToStep(2);
            });
        }

        function goToStep(stepNumber) {
            // Update step indicators
            document.querySelectorAll('.qw-step').forEach((step, index) => {
                if (index + 1 < stepNumber) {
                    step.classList.remove('active');
                    step.classList.add('completed');
                } else if (index + 1 === stepNumber) {
                    step.classList.add('active');
                    step.classList.remove('completed');
                } else {
                    step.classList.remove('active', 'completed');
                }
            });
            
            // Show the correct step content
            document.querySelectorAll('.qw-step-content').forEach((content, index) => {
                content.classList.toggle('active', index + 1 === stepNumber);
            });
            
            // Special actions for specific steps
            if (stepNumber === 2 && !timerInterval) {
                startWorkoutTimer();
            }
        }

        // Exercise Selection Functions (Step 1)
        async function loadExercises(category) {
            try {
                // Show loading state
                document.getElementById('exerciseResults').innerHTML = '<div class="qw-loading"><i class="fas fa-spinner fa-spin"></i> Loading exercises...</div>';
                
                // Update active category button
                document.querySelectorAll('.qw-category-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.category === category);
                });
                
                // Close categories panel if it's open
                document.getElementById('categoriesPanel').classList.remove('active');
                
                // Fetch exercises
                const response = await fetch(`get_exercises.php?action=${category}`);
                const data = await response.json();
                
                // Display results
                displayExerciseResults(data.exercises || [], category);
            } catch (error) {
                console.error('Error loading exercises:', error);
                document.getElementById('exerciseResults').innerHTML = '<div class="qw-error">Failed to load exercises. Please try again.</div>';
            }
        }

        function displayExerciseResults(exercises, category) {
            const resultsContainer = document.getElementById('exerciseResults');
            
            if (!exercises || exercises.length === 0) {
                resultsContainer.innerHTML = `<div class="qw-no-results">No ${category} exercises found</div>`;
                return;
            }
            
            resultsContainer.innerHTML = `
                <div class="qw-results-grid">
                    ${exercises.map(exercise => `
                        <div class="qw-exercise-card" data-name="${exercise.exercise_name}">
                            <div class="qw-exercise-card-header">
                                <h4>${exercise.exercise_name}</h4>
                                ${category === 'recent' && exercise.last_used ? `
                                    <span class="qw-last-used">
                                        ${formatTimeAgo(new Date(exercise.last_used))}
                                    </span>
                                ` : ''}
                            </div>
                            <div class="qw-exercise-card-body">
                                ${exercise.muscle_group_name ? `
                                    <span class="qw-tag">
                                        <i class="fas fa-layer-group"></i>
                                        ${exercise.muscle_group_name}
                                    </span>
                                ` : ''}
                                ${exercise.equipment_name ? `
                                    <span class="qw-tag">
                                        <i class="fas fa-dumbbell"></i>
                                        ${exercise.equipment_name}
                                    </span>
                                ` : ''}
                            </div>
                            ${exercise.max_weight ? `
                                <div class="qw-exercise-card-footer">
                                    <span class="qw-pb">
                                        <i class="fas fa-trophy"></i>
                                        PB: ${exercise.max_weight}kg × ${Math.round(exercise.avg_reps || 0)}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function handleExerciseClick(event) {
            const exerciseCard = event.target.closest('.qw-exercise-card');
            if (!exerciseCard) return;
            
            const exerciseName = exerciseCard.dataset.name;
            addToSelectedExercises(exerciseName);
        }

        function addToSelectedExercises(exerciseName) {
            // Don't add duplicates
            if (selectedExercises.includes(exerciseName)) {
                return;
            }
            
            // Add to the array
            selectedExercises.push(exerciseName);
            
            // Update the display
            updateSelectedExercisesDisplay();
        }

        function updateSelectedExercisesDisplay() {
            const container = document.getElementById('selectedExercisesList');
            const countSpan = document.getElementById('selectedCount');
            
            // Update count
            countSpan.textContent = `(${selectedExercises.length})`;
            
            if (selectedExercises.length === 0) {
                container.innerHTML = '<div class="qw-empty-selection">No exercises selected yet. Click on exercises above to add them.</div>';
                return;
            }
            
            // Clear the container
            container.innerHTML = '';
            
            // Add each selected exercise
            selectedExercises.forEach(exercise => {
                const template = document.getElementById('selectedExerciseTemplate');
                const clone = template.content.cloneNode(true);
                
                clone.querySelector('.qw-selected-name').textContent = exercise;
                clone.querySelector('.qw-remove-selected-btn').dataset.name = exercise;
                
                container.appendChild(clone);
            });
        }

        function handleRemoveSelectedExercise(event) {
            const removeBtn = event.target.closest('.qw-remove-selected-btn');
            if (!removeBtn) return;
            
            const exerciseName = removeBtn.dataset.name;
            selectedExercises = selectedExercises.filter(name => name !== exerciseName);
            
            updateSelectedExercisesDisplay();
        }

        // Workout Setup (Step 2)
        function setupWorkout() {
            // Clear previous workout
            document.getElementById('exerciseList').innerHTML = '';
            
            // Add each selected exercise
            selectedExercises.forEach(exerciseName => {
                addExerciseToWorkout(exerciseName);
            });
            
            // Start the timer if it's not already running
            if (!timerInterval) {
                startWorkoutTimer();
            }
        }

        async function addExerciseToWorkout(exerciseName) {
            const template = document.getElementById('exerciseTemplate');
            const clone = template.content.cloneNode(true);
            
            // Set exercise name
            clone.querySelector('.qw-exercise-name').textContent = exerciseName;
            
            // Add to DOM
            const exerciseList = document.getElementById('exerciseList');
            exerciseList.appendChild(clone);
            
            // Get the newly added exercise item
            const exerciseItem = exerciseList.lastElementChild;
            
            // Add first set automatically
            const setsContainer = exerciseItem.querySelector('.qw-sets-list');
            addSetToExercise(setsContainer);
            
            // Try to get recommendations and personal bests
            try {
                const response = await fetch(`manage_sets.php?action=recommendations&exercise=${encodeURIComponent(exerciseName)}`);
                const data = await response.json();
                
                if (data.personal_bests && data.personal_bests.max_weight) {
                    // Update personal best
                    const pbValue = exerciseItem.querySelector('.qw-pb-value');
                    pbValue.textContent = `${data.personal_bests.max_weight}kg × ${data.personal_bests.max_reps}`;
                }
            } catch (error) {
                console.error('Error getting recommendations:', error);
            }
        }

        // Timer Functions
        function startWorkoutTimer() {
            if (timerInterval) return;
            
            // Hide start button, show pause button
            document.getElementById('startTimer').style.display = 'none';
            document.getElementById('pauseTimer').style.display = 'flex';
            
            if (!startTime) {
                startTime = new Date();
                totalSeconds = 0;
            } else if (!totalSeconds) {
                // If timer was reset, update start time
                startTime = new Date();
            }
            
            timerInterval = setInterval(updateTimer, 1000);
        }

        function pauseWorkoutTimer() {
            if (!timerInterval) return;
            
            // Hide pause button, show start button
            document.getElementById('pauseTimer').style.display = 'none';
            document.getElementById('startTimer').style.display = 'flex';
            
            clearInterval(timerInterval);
            timerInterval = null;
        }

        function resetWorkoutTimer() {
            pauseWorkoutTimer();
            
            totalSeconds = 0;
            startTime = null;
            document.getElementById('timer').textContent = '00:00:00';
        }

        function updateTimer() {
            totalSeconds++;
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds - hours * 3600) / 60);
            const seconds = totalSeconds - hours * 3600 - minutes * 60;
            
            const timeString = 
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');
                
            document.getElementById('timer').textContent = timeString;
            document.getElementById('workoutDuration').textContent = timeString;
        }

        // Set Functions
        function addSetToExercise(setsContainer, setData = null) {
            const template = document.getElementById('setTemplate');
            const clone = template.content.cloneNode(true);
            
            const setNumber = setsContainer.children.length + 1;
            clone.querySelector('.qw-set-number').textContent = `Set ${setNumber}`;
            
            // If we have set data, populate the inputs
            if (setData) {
                clone.querySelector('.qw-weight-input').value = setData.weight || '';
                clone.querySelector('.qw-reps-input').value = setData.reps || '';
                clone.querySelector('.qw-rpe-input').value = setData.rpe || '';
            }
            
            setsContainer.appendChild(clone);
            updateExerciseVolume(setsContainer.closest('.qw-exercise-item'));
            updateWorkoutSummary();
        }

        // Utility Functions
        function formatTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + 'y ago';
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + 'mo ago';
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + 'd ago';
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + 'h ago';
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + 'm ago';
            
            return 'just now';
        }

        // Handle exercise list actions
        function handleExerciseListClick(event) {
            const exerciseItem = event.target.closest('.qw-exercise-item');
            if (!exerciseItem) return;
            
            // Handle favorite button
            if (event.target.closest('.qw-favorite-btn')) {
                toggleFavorite(exerciseItem);
                return;
            }
            
            // Handle remove button
            if (event.target.closest('.qw-remove-btn')) {
                removeExercise(exerciseItem);
                return;
            }
            
            // Handle add set button
            if (event.target.closest('.qw-add-set-btn')) {
                const setsContainer = exerciseItem.querySelector('.qw-sets-list');
                addSetToExercise(setsContainer);
                return;
            }
            
            // Handle remove set button
            if (event.target.closest('.qw-remove-set-btn')) {
                const setItem = event.target.closest('.qw-set-item');
                if (setItem && confirm('Remove this set?')) {
                    setItem.remove();
                    updateExerciseVolume(exerciseItem);
                }
                return;
            }
            
            // Handle set input changes
            if (event.target.classList.contains('qw-weight-input') ||
                event.target.classList.contains('qw-reps-input') ||
                event.target.classList.contains('qw-rpe-input')) {
                updateExerciseVolume(exerciseItem);
                return;
            }
        }

        function updateExerciseVolume(exerciseItem) {
            let totalVolume = 0;
            const sets = exerciseItem.querySelectorAll('.qw-set-item');
            
            sets.forEach(set => {
                const weight = parseFloat(set.querySelector('.qw-weight-input').value) || 0;
                const reps = parseInt(set.querySelector('.qw-reps-input').value) || 0;
                totalVolume += weight * reps;
            });
            
            exerciseItem.querySelector('.qw-volume-value').textContent = Math.round(totalVolume);
            updateWorkoutSummary();
        }

        function removeExercise(exerciseItem) {
            if (confirm('Are you sure you want to remove this exercise?')) {
                const exerciseName = exerciseItem.querySelector('.qw-exercise-name').textContent;
                
                // Remove from selectedExercises array
                selectedExercises = selectedExercises.filter(name => name !== exerciseName);
                
                // Remove from DOM
                exerciseItem.remove();
                
                updateWorkoutSummary();
            }
        }

        // Start rest timer
        function startRestTimer() {
            alert('Rest timer started! 90 seconds countdown');
            // In a real implementation, this would show a modal with a countdown timer
        }

        // Rating functionality
        function handleRatingClick(event) {
            const star = event.target.closest('.fa-star');
            if (!star) return;
            
            const rating = parseInt(star.dataset.rating);
            const stars = document.querySelectorAll('.qw-rating .fa-star');
            
            stars.forEach((s, index) => {
                s.classList.remove('fas', 'far');
                s.classList.add(index < rating ? 'fas' : 'far');
            });
        }

        // Workout Summary
        function updateWorkoutSummary() {
            const exercises = document.querySelectorAll('.qw-exercise-item');
            let totalSets = 0;
            let totalVolume = 0;

            exercises.forEach(exercise => {
                const sets = exercise.querySelectorAll('.qw-set-item');
                totalSets += sets.length;
                
                sets.forEach(set => {
                    const weight = parseFloat(set.querySelector('.qw-weight-input').value) || 0;
                    const reps = parseInt(set.querySelector('.qw-reps-input').value) || 0;
                    totalVolume += weight * reps;
                });
            });

            document.getElementById('totalExercises').textContent = exercises.length;
            document.getElementById('totalSets').textContent = totalSets;
            document.getElementById('totalVolume').textContent = Math.round(totalVolume);
        }

        // Complete workout
        function finishWorkout() {
            const workoutData = {
                name: document.getElementById('workoutName').value || 'Quick Workout',
                notes: document.getElementById('workoutNotes').value || '',
                duration: totalSeconds,
                exercises: gatherExerciseData()
            };
            
            alert('Workout completed and saved!');
            console.log('Workout data:', workoutData);
            
            // In a real implementation, this would send the data to the server
            // window.location.href = 'workout-summary.php?id=123';
        }

        function gatherExerciseData() {
            const exercises = [];
            document.querySelectorAll('.qw-exercise-item').forEach(exerciseElement => {
                const exercise = {
                    name: exerciseElement.querySelector('.qw-exercise-name').textContent,
                    notes: exerciseElement.querySelector('.qw-exercise-notes textarea').value,
                    sets: []
                };

                exerciseElement.querySelectorAll('.qw-set-item').forEach(setElement => {
                    exercise.sets.push({
                        weight: parseFloat(setElement.querySelector('.qw-weight-input').value) || 0,
                        reps: parseInt(setElement.querySelector('.qw-reps-input').value) || 0,
                        rpe: parseInt(setElement.querySelector('.qw-rpe-input').value) || 0
                    });
                });

                exercises.push(exercise);
            });

            return exercises;
        }

        // Template functions
        function saveWorkoutAsTemplate() {
            alert('Workout template saved!');
            // This would normally show a modal for naming and configuring the template
        }

        // Rest API functions would be implemented below
        // These would handle the actual server communications
    </script>
</body>
</html> 