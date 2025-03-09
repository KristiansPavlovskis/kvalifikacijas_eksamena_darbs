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
        }
        
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
            transition: background-color 0.2s;
        }
        
        .qw-btn:hover {
            background-color: #4a4a4a;
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

        <!-- Quick Add Exercise Section -->
        <div class="qw-quick-add">
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
        </div>

        <!-- Current Workout Section -->
        <div class="qw-current-workout">
            <h2>Current Workout <span id="workoutDuration"></span></h2>
            <div id="exerciseList" class="qw-exercise-list"></div>
            
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

                    <!-- Exercise History Modal (Hidden) -->
                    <div class="qw-history-modal">
                        <div class="qw-history-modal-header">
                            <h3>Exercise History</h3>
                            <button class="qw-btn qw-btn-icon qw-close-history-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="qw-history-content">
                            <div class="qw-history-loading">
                                <i class="fas fa-spinner fa-spin"></i> Loading history...
                            </div>
                            <div class="qw-history-list"></div>
                        </div>
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

            <!-- History Item Template (Hidden) -->
            <template id="historyItemTemplate">
                <div class="qw-history-item">
                    <div class="qw-history-item-header">
                        <span class="qw-history-date"></span>
                        <span class="qw-history-workout"></span>
                    </div>
                    <div class="qw-history-sets"></div>
                </div>
            </template>
        </div>

        <!-- Workout Summary Section -->
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
            </div>
            
            <div class="qw-finish-workout">
                <button id="finishWorkout" class="qw-btn qw-btn-success">
                    <i class="fas fa-check"></i> Finish Workout
                </button>
            </div>
        </div>

        <!-- Template Management Section -->
        <div class="qw-templates-section" id="templatesSection">
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

        <!-- Template Modal -->
        <div id="templateModal" class="qw-modal">
            <div class="qw-modal-content qw-template-modal">
                <div class="qw-modal-header">
                    <h2>Save Workout Template</h2>
                    <button class="qw-modal-close" onclick="closeModal('templateModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="qw-modal-body">
                    <form id="templateForm">
                        <div class="qw-form-group">
                            <label for="templateName">Template Name</label>
                            <input type="text" id="templateName" required placeholder="e.g., Full Body Strength">
                        </div>
                        <div class="qw-form-group">
                            <label for="templateDescription">Description</label>
                            <textarea id="templateDescription" rows="3" placeholder="What's this workout for?"></textarea>
                        </div>
                        <div class="qw-form-row">
                            <div class="qw-form-group qw-form-half">
                                <label for="templateDuration">Duration (minutes)</label>
                                <input type="number" id="templateDuration" min="10" value="60">
                            </div>
                            <div class="qw-form-group qw-form-half">
                                <label for="templateIcon">Icon</label>
                                <select id="templateIcon">
                                    <option value="dumbbell">Dumbbell</option>
                                    <option value="running">Running</option>
                                    <option value="heartbeat">Cardio</option>
                                    <option value="bolt">Power</option>
                                    <option value="user">Upper Body</option>
                                    <option value="child">Lower Body</option>
                                    <option value="apple-alt">Health</option>
                                </select>
                            </div>
                        </div>
                        <div class="qw-form-group">
                            <label for="templateColor">Color</label>
                            <div class="qw-color-options">
                                <span class="qw-color-option" data-color="#4361ee" style="background-color: #4361ee;"></span>
                                <span class="qw-color-option" data-color="#3a0ca3" style="background-color: #3a0ca3;"></span>
                                <span class="qw-color-option" data-color="#7209b7" style="background-color: #7209b7;"></span>
                                <span class="qw-color-option" data-color="#f72585" style="background-color: #f72585;"></span>
                                <span class="qw-color-option" data-color="#e63946" style="background-color: #e63946;"></span>
                                <span class="qw-color-option" data-color="#fb8500" style="background-color: #fb8500;"></span>
                                <span class="qw-color-option" data-color="#2a9d8f" style="background-color: #2a9d8f;"></span>
                                <span class="qw-color-option" data-color="#38b000" style="background-color: #38b000;"></span>
                            </div>
                            <input type="hidden" id="templateColor" value="#4361ee">
                        </div>
                        <div class="qw-template-preview">
                            <h3>Included Exercises</h3>
                            <div id="templateExercisesList" class="qw-template-exercises-list"></div>
                        </div>
                    </form>
                </div>
                <div class="qw-modal-footer">
                    <button class="qw-btn qw-btn-secondary" onclick="closeModal('templateModal')">Cancel</button>
                    <button class="qw-btn qw-btn-primary" id="saveTemplateBtn">Save Template</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="finishWorkoutModal" class="qw-modal">
        <div class="qw-modal-content">
            <h2>Finish Workout</h2>
            <form id="workoutSummaryForm">
                <div class="qw-form-group">
                    <label>Workout Name</label>
                    <input type="text" id="workoutName" required>
                </div>
                <div class="qw-form-group">
                    <label>How do you feel?</label>
                    <div class="qw-rating">
                        <i class="far fa-face-frown" data-rating="1"></i>
                        <i class="far fa-face-meh" data-rating="2"></i>
                        <i class="far fa-face-smile" data-rating="3"></i>
                        <i class="far fa-face-grin" data-rating="4"></i>
                        <i class="far fa-face-grin-stars" data-rating="5"></i>
                    </div>
                </div>
                <div class="qw-form-group">
                    <label>Notes</label>
                    <textarea id="workoutNotes" rows="3"></textarea>
                </div>
                <div class="qw-modal-actions">
                    <button type="button" class="qw-btn qw-btn-secondary" onclick="closeModal('finishWorkoutModal')">Cancel</button>
                    <button type="submit" class="qw-btn qw-btn-success">Save Workout</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RPE Guide Modal (Hidden) -->
    <div id="rpeGuideModal" class="qw-modal">
        <div class="qw-modal-content qw-rpe-guide">
            <div class="qw-modal-header">
                <h2>RPE Guide</h2>
                <button class="qw-modal-close" onclick="closeModal('rpeGuideModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="qw-rpe-guide-content">
                <p class="qw-rpe-intro">Rate of Perceived Exertion (RPE) measures how hard a set feels on a scale of 1-10.</p>
                <div class="qw-rpe-scale" id="rpeScale"></div>
            </div>
        </div>
    </div>

    <!-- Rest Timer Modal (Hidden) -->
    <div id="restTimerModal" class="qw-modal qw-rest-modal">
        <div class="qw-modal-content">
            <div class="qw-rest-timer-display">
                <span id="restTimerDisplay">01:30</span>
            </div>
            <div class="qw-rest-actions">
                <button class="qw-btn qw-btn-danger" id="cancelRestBtn">
                    Cancel
                </button>
                <button class="qw-btn qw-btn-success" id="finishRestBtn">
                    Finish
                </button>
            </div>
        </div>
    </div>

    <script>
        // Workout Timer
        let startTime = null;
        let timerInterval = null;
        let isTimerRunning = false;
        let totalSeconds = 0;

        // Rest Timer
        let restTimerInterval = null;
        let restSeconds = 0;
        const DEFAULT_REST_TIME = 90; // 90 seconds default rest time

        // Current Workout Data
        let currentWorkout = {
            exercises: [],
            startTime: null,
            endTime: null,
            duration: 0,
            totalVolume: 0,
            totalSets: 0
        };

        let categories = {
            muscle_groups: [],
            equipment: []
        };

        // Initialize exercise functionality
        let rpeGuidelines = [];
        let activeRestTimer = null;
        let activeRestInterval = null;

        // Template variables
        let currentTemplateId = null;
        let templates = [];

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimers();
            initializeSearchFunctionality();
            initializeExerciseHandling();
            initializeWorkoutSummary();
            initializeTemplates();
        });

        // Timer Functions
        function initializeTimers() {
            document.getElementById('startTimer').addEventListener('click', startWorkoutTimer);
            document.getElementById('pauseTimer').addEventListener('click', pauseWorkoutTimer);
            document.getElementById('resetTimer').addEventListener('click', resetWorkoutTimer);
            document.getElementById('startRest').addEventListener('click', startRestTimer);
        }

        function startWorkoutTimer() {
            if (!isTimerRunning) {
                if (!startTime) startTime = new Date();
                isTimerRunning = true;
                document.getElementById('startTimer').style.display = 'none';
                document.getElementById('pauseTimer').style.display = 'inline-block';
                
                timerInterval = setInterval(() => {
                    totalSeconds++;
                    updateTimerDisplay();
                }, 1000);
            }
        }

        function pauseWorkoutTimer() {
            if (isTimerRunning) {
                isTimerRunning = false;
                clearInterval(timerInterval);
                document.getElementById('startTimer').style.display = 'inline-block';
                document.getElementById('pauseTimer').style.display = 'none';
            }
        }

        function resetWorkoutTimer() {
            clearInterval(timerInterval);
            isTimerRunning = false;
            totalSeconds = 0;
            startTime = null;
            updateTimerDisplay();
            document.getElementById('startTimer').style.display = 'inline-block';
            document.getElementById('pauseTimer').style.display = 'none';
        }

        function updateTimerDisplay() {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            document.getElementById('timer').textContent = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function startRestTimer() {
            if (restTimerInterval) clearInterval(restTimerInterval);
            restSeconds = DEFAULT_REST_TIME;
            updateRestTimerDisplay();
            
            restTimerInterval = setInterval(() => {
                if (restSeconds > 0) {
                    restSeconds--;
                    updateRestTimerDisplay();
                } else {
                    clearInterval(restTimerInterval);
                    notifyRestComplete();
                }
            }, 1000);
        }

        function updateRestTimerDisplay() {
            const minutes = Math.floor(restSeconds / 60);
            const seconds = restSeconds % 60;
            document.getElementById('restTimer').textContent = 
                `Rest: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function notifyRestComplete() {
            // Play sound and show notification
            const notification = new Notification('Rest Complete!', {
                body: 'Time for your next set!',
                icon: '/path/to/icon.png'
            });
        }

        // Exercise Handling Functions
        function initializeExerciseHandling() {
            document.querySelector('.qw-quick-categories').addEventListener('click', handleCategoryClick);
            document.getElementById('exerciseSearch').addEventListener('input', handleExerciseSearch);
            document.getElementById('exerciseList').addEventListener('click', handleExerciseListClick);
        }

        function handleCategoryClick(event) {
            const button = event.target.closest('.qw-category-btn');
            if (!button) return;

            const category = button.dataset.category;
            // Fetch and display exercises based on category
            fetchExercisesByCategory(category);
        }

        function handleExerciseSearch(event) {
            const searchTerm = event.target.value.trim();
            if (searchTerm.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            // Perform search and show results
            searchExercises(searchTerm);
        }

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
            
            // Handle history button
            if (event.target.closest('.qw-history-btn')) {
                showExerciseHistory(exerciseItem);
                return;
            }
            
            // Handle add set button
            if (event.target.closest('.qw-add-set-btn')) {
                const setsContainer = exerciseItem.querySelector('.qw-sets-list');
                addSetToExercise(setsContainer);
                return;
            }
            
            // Handle recommend button
            if (event.target.closest('.qw-recommend-btn')) {
                recommendSets(exerciseItem);
                return;
            }
            
            // Handle RPE help
            if (event.target.closest('.qw-rpe-help')) {
                showRpeGuide();
                return;
            }
            
            // Handle start rest timer
            if (event.target.closest('.qw-start-rest-btn')) {
                startSetRestTimer();
                return;
            }
            
            // Handle close history button
            if (event.target.closest('.qw-close-history-btn')) {
                closeExerciseHistory(exerciseItem);
                return;
            }
            
            // Handle set input changes
            if (event.target.classList.contains('qw-weight-input') ||
                event.target.classList.contains('qw-reps-input')) {
                updateExerciseVolume(exerciseItem);
                return;
            }
        }

        async function addExerciseToWorkout(exerciseName) {
            const template = document.getElementById('exerciseTemplate');
            const clone = template.content.cloneNode(true);
            
            // Set exercise name
            clone.querySelector('.qw-exercise-name').textContent = exerciseName;
            
            // Hide search results if visible
            document.getElementById('searchResults').innerHTML = '';
            
            // Add to DOM
            const exerciseList = document.getElementById('exerciseList');
            exerciseList.appendChild(clone);
            
            // Get the newly added exercise item
            const exerciseItem = exerciseList.lastElementChild;
            
            // Add first set automatically
            const setsContainer = exerciseItem.querySelector('.qw-sets-list');
            addSetToExercise(setsContainer);
            
            // Try to get recommendations
            try {
                const response = await fetch(`manage_sets.php?action=recommendations&exercise=${encodeURIComponent(exerciseName)}`);
                const data = await response.json();
                
                if (data.personal_bests && data.personal_bests.max_weight) {
                    // Update personal best
                    const pbValue = exerciseItem.querySelector('.qw-pb-value');
                    pbValue.textContent = `${data.personal_bests.max_weight}kg × ${data.personal_bests.max_reps}`;
                }
                
                // If this is a new exercise, add recommended sets
                if (data.recommended_sets && data.recommended_sets.length > 0) {
                    // Clear existing sets
                    setsContainer.innerHTML = '';
                    
                    // Add recommended sets
                    data.recommended_sets.forEach(set => {
                        addSetToExercise(setsContainer, set);
                    });
                }
            } catch (error) {
                console.error('Error getting recommendations:', error);
            }
            
            updateWorkoutSummary();
        }

        function addSetToExercise(setsContainer, setData = null) {
            const template = document.getElementById('setTemplate');
            const clone = template.content.cloneNode(true);
            
            const setNumber = setsContainer.children.length + 1;
            clone.querySelector('.qw-set-number').textContent = `Set ${setNumber}`;
            
            // If we have set data, populate the inputs
            if (setData) {
                clone.querySelector('.qw-weight-input').value = setData.weight || '';
                clone.querySelector('.qw-reps-input').value = setData.reps || '';
                
                // Mark warmup sets
                if (setData.is_warmup) {
                    const setItem = clone.querySelector('.qw-set-item');
                    setItem.classList.add('qw-warmup-set');
                    setItem.setAttribute('title', 'Warm-up Set');
                }
                
                // Add note if available
                if (setData.note) {
                    const setItem = clone.querySelector('.qw-set-item');
                    const note = document.createElement('div');
                    note.className = 'qw-set-note';
                    note.textContent = setData.note;
                    setItem.appendChild(note);
                }
            }
            
            setsContainer.appendChild(clone);
            updateExerciseVolume(setsContainer.closest('.qw-exercise-item'));
            updateWorkoutSummary();
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
                exerciseItem.remove();
                updateWorkoutSummary();
            }
        }

        async function toggleFavorite(exerciseItem) {
            const btn = exerciseItem.querySelector('.qw-favorite-btn');
            const icon = btn.querySelector('i');
            const isFavorite = icon.classList.contains('fas');
            const exerciseName = exerciseItem.querySelector('.qw-exercise-name').textContent;
            
            // Toggle icon
            icon.classList.toggle('far', isFavorite);
            icon.classList.toggle('fas', !isFavorite);
            
            // Update database (would need to implement a manage_favorites.php endpoint)
            try {
                const response = await fetch('manage_favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        exercise_name: exerciseName,
                        action: isFavorite ? 'remove' : 'add'
                    })
                });
                
                if (!response.ok) {
                    // Revert icon if failed
                    icon.classList.toggle('far', !isFavorite);
                    icon.classList.toggle('fas', isFavorite);
                    throw new Error('Failed to update favorite');
                }
            } catch (error) {
                console.error('Error updating favorite:', error);
            }
        }

        async function showExerciseHistory(exerciseItem) {
            const historyModal = exerciseItem.querySelector('.qw-history-modal');
            const historyList = historyModal.querySelector('.qw-history-list');
            const loadingIndicator = historyModal.querySelector('.qw-history-loading');
            const exerciseName = exerciseItem.querySelector('.qw-exercise-name').textContent;
            
            // Show modal and loading indicator
            historyModal.classList.add('active');
            loadingIndicator.style.display = 'flex';
            historyList.innerHTML = '';
            
            try {
                const response = await fetch(`manage_sets.php?action=history&exercise=${encodeURIComponent(exerciseName)}`);
                const data = await response.json();
                
                loadingIndicator.style.display = 'none';
                
                if (!data.history || data.history.length === 0) {
                    historyList.innerHTML = '<div class="qw-no-history">No history found for this exercise</div>';
                    return;
                }
                
                data.history.forEach(workout => {
                    const template = document.getElementById('historyItemTemplate');
                    const clone = template.content.cloneNode(true);
                    
                    clone.querySelector('.qw-history-date').textContent = new Date(workout.workout_date).toLocaleDateString();
                    clone.querySelector('.qw-history-workout').textContent = workout.workout_name;
                    
                    const setsContainer = clone.querySelector('.qw-history-sets');
                    workout.sets.forEach(set => {
                        const setEl = document.createElement('div');
                        setEl.className = 'qw-history-set';
                        setEl.innerHTML = `
                            <span class="qw-history-set-number">Set ${set.set_number}</span>
                            <span class="qw-history-weight">${set.weight} kg</span>
                            <span class="qw-history-reps">${set.reps} reps</span>
                            ${set.rpe > 0 ? `<span class="qw-history-rpe">RPE ${set.rpe}</span>` : ''}
                        `;
                        setsContainer.appendChild(setEl);
                    });
                    
                    historyList.appendChild(clone);
                });
            } catch (error) {
                console.error('Error fetching exercise history:', error);
                loadingIndicator.style.display = 'none';
                historyList.innerHTML = '<div class="qw-error">Failed to load history</div>';
            }
        }

        function closeExerciseHistory(exerciseItem) {
            const historyModal = exerciseItem.querySelector('.qw-history-modal');
            historyModal.classList.remove('active');
        }

        async function recommendSets(exerciseItem) {
            const exerciseName = exerciseItem.querySelector('.qw-exercise-name').textContent;
            const setsContainer = exerciseItem.querySelector('.qw-sets-list');
            
            try {
                const response = await fetch(`manage_sets.php?action=recommendations&exercise=${encodeURIComponent(exerciseName)}`);
                const data = await response.json();
                
                if (!data.recommended_sets || data.recommended_sets.length === 0) {
                    alert('No recommendations available for this exercise');
                    return;
                }
                
                if (confirm('This will replace your current sets with recommended ones. Continue?')) {
                    // Clear existing sets
                    setsContainer.innerHTML = '';
                    
                    // Add recommended sets
                    data.recommended_sets.forEach(set => {
                        addSetToExercise(setsContainer, set);
                    });
                    
                    // Update personal best if available
                    if (data.personal_bests && data.personal_bests.max_weight) {
                        const pbValue = exerciseItem.querySelector('.qw-pb-value');
                        pbValue.textContent = `${data.personal_bests.max_weight}kg × ${data.personal_bests.max_reps}`;
                    }
                }
            } catch (error) {
                console.error('Error getting recommendations:', error);
                alert('Failed to get recommendations');
            }
        }

        async function showRpeGuide() {
            if (rpeGuidelines.length === 0) {
                try {
                    const response = await fetch('manage_sets.php?action=rpe');
                    const data = await response.json();
                    rpeGuidelines = data.rpe_guidelines;
                } catch (error) {
                    console.error('Error fetching RPE guidelines:', error);
                    rpeGuidelines = [
                        {rpe: 10, description: 'Maximum effort', feeling: 'Very hard', reps_in_reserve: 0},
                        {rpe: 9, description: '1 rep left in tank', feeling: 'Hard', reps_in_reserve: 1},
                        {rpe: 8, description: '2 reps left in tank', feeling: 'Challenging', reps_in_reserve: 2},
                        {rpe: 7, description: '3 reps left in tank', feeling: 'Moderate', reps_in_reserve: 3}
                    ];
                }
            }
            
            // Populate RPE scale
            const rpeScale = document.getElementById('rpeScale');
            rpeScale.innerHTML = rpeGuidelines.map(guide => `
                <div class="qw-rpe-row">
                    <div class="qw-rpe-number">${guide.rpe}</div>
                    <div class="qw-rpe-description">
                        <div class="qw-rpe-title">${guide.feeling}</div>
                        <div class="qw-rpe-subtitle">${guide.description}</div>
                    </div>
                    <div class="qw-rpe-rir">RIR: ${guide.reps_in_reserve}</div>
                </div>
            `).join('');
            
            // Show modal
            document.getElementById('rpeGuideModal').style.display = 'block';
        }

        function startSetRestTimer() {
            // Default rest time: 90 seconds
            const restSeconds = 90;
            document.getElementById('restTimerDisplay').textContent = formatTime(restSeconds);
            
            // Show modal
            const modal = document.getElementById('restTimerModal');
            modal.style.display = 'block';
            
            // Start countdown
            let remainingSeconds = restSeconds;
            activeRestTimer = {
                startTime: new Date(),
                duration: restSeconds
            };
            
            // Clear any existing interval
            if (activeRestInterval) clearInterval(activeRestInterval);
            
            // Set up new interval
            activeRestInterval = setInterval(() => {
                remainingSeconds--;
                
                if (remainingSeconds <= 0) {
                    clearInterval(activeRestInterval);
                    completeRestTimer();
                } else {
                    document.getElementById('restTimerDisplay').textContent = formatTime(remainingSeconds);
                }
            }, 1000);
            
            // Set up button handlers
            document.getElementById('cancelRestBtn').onclick = () => {
                clearInterval(activeRestInterval);
                modal.style.display = 'none';
            };
            
            document.getElementById('finishRestBtn').onclick = () => {
                clearInterval(activeRestInterval);
                modal.style.display = 'none';
            };
        }

        function completeRestTimer() {
            // Play sound and show notification
            try {
                const audio = new Audio('assets/rest-complete.mp3');
                audio.play();
            } catch (e) {
                console.error('Could not play sound:', e);
            }
            
            // Try to use browser notifications
            if (Notification.permission === "granted") {
                new Notification('Rest Complete!', {
                    body: 'Time for your next set!',
                    icon: 'assets/favicon.png'
                });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        new Notification('Rest Complete!', {
                            body: 'Time for your next set!',
                            icon: 'assets/favicon.png'
                        });
                    }
                });
            }
            
            // Hide modal
            document.getElementById('restTimerModal').style.display = 'none';
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        // Workout Summary Functions
        function initializeWorkoutSummary() {
            document.getElementById('finishWorkout').addEventListener('click', showFinishWorkoutModal);
            document.getElementById('workoutSummaryForm').addEventListener('submit', handleWorkoutComplete);
        }

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

        function showFinishWorkoutModal() {
            document.getElementById('finishWorkoutModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function handleWorkoutComplete(event) {
            event.preventDefault();
            
            // Gather workout data
            const workoutData = {
                name: document.getElementById('workoutName').value,
                notes: document.getElementById('workoutNotes').value,
                rating: document.querySelector('.qw-rating i.active')?.dataset.rating || 3,
                exercises: gatherExerciseData(),
                duration: totalSeconds,
                startTime: startTime,
                endTime: new Date()
            };

            // Save workout data
            saveWorkout(workoutData);
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

        async function saveWorkout(workoutData) {
            try {
                const response = await fetch('save_workout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(workoutData)
                });

                if (response.ok) {
                    window.location.href = 'workout-summary.php?id=' + await response.text();
                } else {
                    throw new Error('Failed to save workout');
                }
            } catch (error) {
                console.error('Error saving workout:', error);
                alert('Failed to save workout. Please try again.');
            }
        }

        // Initialize exercise functionality
        async function initializeSearchFunctionality() {
            // Load categories
            await loadCategories();
            
            // Set up event listeners
            document.getElementById('exerciseSearch').addEventListener('input', debounce(handleExerciseSearch, 300));
            document.querySelector('.qw-quick-categories').addEventListener('click', handleCategoryClick);
            document.getElementById('filterBtn').addEventListener('click', toggleCategoriesPanel);
            
            // Load initial exercises (recent)
            loadExercises('recent');
        }

        // Debounce function to prevent too many API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Load categories from server
        async function loadCategories() {
            try {
                const response = await fetch('get_exercises.php?action=categories');
                const data = await response.json();
                categories = data;
                
                // Populate category grids
                populateCategoryGrids();
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Populate category grids
        function populateCategoryGrids() {
            const bodyPartGrid = document.getElementById('bodyPartGrid');
            const equipmentGrid = document.getElementById('equipmentGrid');
            
            // Group muscle groups by body part
            const bodyParts = {};
            categories.muscle_groups.forEach(mg => {
                if (!bodyParts[mg.body_part]) {
                    bodyParts[mg.body_part] = [];
                }
                bodyParts[mg.body_part].push(mg);
            });
            
            // Create body part sections
            Object.entries(bodyParts).forEach(([bodyPart, muscles]) => {
                const section = document.createElement('div');
                section.className = 'qw-category-section';
                section.innerHTML = `
                    <h4>${bodyPart}</h4>
                    <div class="qw-category-items">
                        ${muscles.map(m => `
                            <button class="qw-category-item" data-type="muscle" data-id="${m.id}">
                                ${m.name}
                            </button>
                        `).join('')}
                    </div>
                `;
                bodyPartGrid.appendChild(section);
            });
            
            // Create equipment grid
            equipmentGrid.innerHTML = categories.equipment.map(eq => `
                <button class="qw-category-item" data-type="equipment" data-id="${eq.id}">
                    <i class="fas ${eq.icon || 'fa-dumbbell'}"></i>
                    ${eq.name}
                </button>
            `).join('');
        }

        // Handle exercise search
        async function handleExerciseSearch(event) {
            const searchTerm = event.target.value.trim();
            if (searchTerm.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`get_exercises.php?action=search&search=${encodeURIComponent(searchTerm)}`);
                const data = await response.json();
                displaySearchResults(data.exercises);
            } catch (error) {
                console.error('Error searching exercises:', error);
            }
        }

        // Display search results
        function displaySearchResults(exercises) {
            const resultsContainer = document.getElementById('searchResults');
            
            if (!exercises || exercises.length === 0) {
                resultsContainer.innerHTML = '<div class="qw-no-results">No exercises found</div>';
                return;
            }
            
            resultsContainer.innerHTML = exercises.map(exercise => `
                <div class="qw-search-result" onclick="addExerciseToWorkout('${exercise.exercise_name}')">
                    <div class="qw-result-main">
                        <h4>${exercise.exercise_name}</h4>
                        <span class="qw-result-meta">
                            <i class="fas fa-layer-group"></i> ${exercise.muscle_group_name}
                            ${exercise.equipment_name ? `<i class="fas fa-dumbbell"></i> ${exercise.equipment_name}` : ''}
                        </span>
                    </div>
                    ${exercise.user_stats ? `
                        <div class="qw-result-stats">
                            ${exercise.user_stats.personal_best_weight ? `
                                <span class="qw-stat">
                                    <i class="fas fa-trophy"></i>
                                    ${exercise.user_stats.personal_best_weight}kg
                                </span>
                            ` : ''}
                            ${exercise.user_stats.times_performed ? `
                                <span class="qw-stat">
                                    <i class="fas fa-check"></i>
                                    ${exercise.user_stats.times_performed}x
                                </span>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        // Handle category click
        async function handleCategoryClick(event) {
            const button = event.target.closest('.qw-category-btn');
            if (!button) return;
            
            // Update active state
            document.querySelectorAll('.qw-category-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            const category = button.dataset.category;
            if (category === 'categories') {
                document.getElementById('categoriesPanel').classList.add('active');
                return;
            }
            
            await loadExercises(category);
        }

        // Load exercises based on category
        async function loadExercises(category) {
            try {
                const response = await fetch(`get_exercises.php?action=${category}`);
                const data = await response.json();
                displayExerciseResults(data.exercises, category);
            } catch (error) {
                console.error('Error loading exercises:', error);
            }
        }

        // Display exercise results
        function displayExerciseResults(exercises, category) {
            const resultsContainer = document.getElementById('exerciseResults');
            
            if (!exercises || exercises.length === 0) {
                resultsContainer.innerHTML = `<div class="qw-no-results">No ${category} exercises found</div>`;
                return;
            }
            
            resultsContainer.innerHTML = `
                <div class="qw-results-grid">
                    ${exercises.map(exercise => `
                        <div class="qw-exercise-card" onclick="addExerciseToWorkout('${exercise.exercise_name}')">
                            <div class="qw-exercise-card-header">
                                <h4>${exercise.exercise_name}</h4>
                                ${category === 'recent' ? `
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
                                        PB: ${exercise.max_weight}kg × ${Math.round(exercise.avg_reps)}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Toggle categories panel
        function toggleCategoriesPanel() {
            const panel = document.getElementById('categoriesPanel');
            panel.classList.toggle('active');
        }

        // Format time ago
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

        // Initialize template functionality
        async function initializeTemplates() {
            document.getElementById('createTemplateBtn').addEventListener('click', showCreateTemplateModal);
            document.getElementById('saveTemplateBtn').addEventListener('click', saveTemplate);
            
            document.querySelector('.qw-color-options').addEventListener('click', function(e) {
                const colorOption = e.target.closest('.qw-color-option');
                if (colorOption) {
                    // Remove active class from all options
                    document.querySelectorAll('.qw-color-option').forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    colorOption.classList.add('active');
                    
                    // Update hidden input
                    document.getElementById('templateColor').value = colorOption.dataset.color;
                }
            });
            
            // Load templates on page load
            await loadTemplates();
        }

        // Load templates from server
        async function loadTemplates() {
            try {
                const templatesContainer = document.getElementById('templatesContainer');
                templatesContainer.innerHTML = '<div class="qw-templates-loading"><i class="fas fa-spinner fa-spin"></i> Loading templates...</div>';
                
                const response = await fetch('manage_templates.php?action=list');
                const data = await response.json();
                
                templates = data.templates || [];
                
                if (templates.length === 0) {
                    templatesContainer.innerHTML = `
                        <div class="qw-no-templates">
                            <p>You don't have any workout templates yet.</p>
                            <p>Templates make it easy to quickly start your favorite workouts.</p>
                            <button class="qw-btn qw-btn-primary" onclick="showCreateTemplateModal()">
                                <i class="fas fa-plus"></i> Create Your First Template
                            </button>
                        </div>
                    `;
                    return;
                }
                
                templatesContainer.innerHTML = `
                    <div class="qw-templates-grid">
                        ${templates.map(template => `
                            <div class="qw-template-card" style="border-color: ${template.color}">
                                <div class="qw-template-icon" style="background-color: ${template.color}">
                                    <i class="fas fa-${template.icon}"></i>
                                </div>
                                <div class="qw-template-content">
                                    <h3>${template.name}</h3>
                                    <p>${template.description || 'No description'}</p>
                                    <div class="qw-template-meta">
                                        <span><i class="fas fa-dumbbell"></i> ${template.exercise_count} exercises</span>
                                        <span><i class="far fa-clock"></i> ${template.avg_duration || '~60 min'}</span>
                                    </div>
                                </div>
                                <div class="qw-template-actions">
                                    <button class="qw-btn qw-btn-primary qw-start-template-btn" data-id="${template.id}">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <button class="qw-btn qw-btn-icon qw-edit-template-btn" data-id="${template.id}" title="Edit Template">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="qw-btn qw-btn-icon qw-delete-template-btn" data-id="${template.id}" title="Delete Template">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                // Add event listeners to template action buttons
                document.querySelectorAll('.qw-start-template-btn').forEach(btn => {
                    btn.addEventListener('click', () => startTemplate(btn.dataset.id));
                });
                
                document.querySelectorAll('.qw-edit-template-btn').forEach(btn => {
                    btn.addEventListener('click', () => editTemplate(btn.dataset.id));
                });
                
                document.querySelectorAll('.qw-delete-template-btn').forEach(btn => {
                    btn.addEventListener('click', () => deleteTemplate(btn.dataset.id));
                });
                
            } catch (error) {
                console.error('Error loading templates:', error);
                document.getElementById('templatesContainer').innerHTML = '<div class="qw-error">Failed to load templates. Please try again.</div>';
            }
        }

        // Show template creation modal
        function showCreateTemplateModal() {
            // Reset form
            document.getElementById('templateForm').reset();
            document.querySelectorAll('.qw-color-option').forEach(opt => opt.classList.remove('active'));
            document.querySelector('.qw-color-option[data-color="#4361ee"]').classList.add('active');
            document.getElementById('templateColor').value = '#4361ee';
            
            // Set template ID to null (creating a new template)
            currentTemplateId = null;
            
            // Show current exercises in preview
            updateTemplateExercisesPreview();
            
            // Show modal
            document.getElementById('templateModal').style.display = 'block';
        }

        // Update template exercises preview
        function updateTemplateExercisesPreview() {
            const exercisesList = document.getElementById('templateExercisesList');
            const exercises = Array.from(document.querySelectorAll('.qw-exercise-item')).map(item => {
                return {
                    name: item.querySelector('.qw-exercise-name').textContent,
                    sets: Array.from(item.querySelectorAll('.qw-set-item')).length
                };
            });
            
            if (exercises.length === 0) {
                exercisesList.innerHTML = '<p class="qw-no-exercises">No exercises added yet. Add exercises to your workout first.</p>';
                return;
            }
            
            exercisesList.innerHTML = exercises.map(exercise => `
                <div class="qw-template-exercise-item">
                    <span class="qw-template-exercise-name">${exercise.name}</span>
                    <span class="qw-template-exercise-sets">${exercise.sets} sets</span>
                </div>
            `).join('');
        }

        // Save template
        async function saveTemplate() {
            const name = document.getElementById('templateName').value.trim();
            if (!name) {
                alert('Please enter a template name');
                return;
            }
            
            // Get exercises from current workout
            const exercises = Array.from(document.querySelectorAll('.qw-exercise-item')).map(item => {
                return {
                    name: item.querySelector('.qw-exercise-name').textContent,
                    notes: item.querySelector('.qw-exercise-notes textarea').value,
                    sets: Array.from(item.querySelectorAll('.qw-set-item')).map(setItem => {
                        const weight = parseFloat(setItem.querySelector('.qw-weight-input').value) || 0;
                        const reps = parseInt(setItem.querySelector('.qw-reps-input').value) || 0;
                        const isWarmup = setItem.classList.contains('qw-warmup-set');
                        
                        return {
                            weight,
                            reps,
                            is_warmup: isWarmup ? 1 : 0
                        };
                    })
                };
            });
            
            if (exercises.length === 0) {
                alert('Please add at least one exercise to your workout before saving as a template');
                return;
            }
            
            // Prepare template data
            const templateData = {
                action: currentTemplateId ? 'update' : 'create',
                template_id: currentTemplateId,
                name,
                description: document.getElementById('templateDescription').value,
                duration_minutes: parseInt(document.getElementById('templateDuration').value) || 60,
                icon: document.getElementById('templateIcon').value,
                color: document.getElementById('templateColor').value,
                exercises
            };
            
            try {
                // Show saving indicator
                const saveBtn = document.getElementById('saveTemplateBtn');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                saveBtn.disabled = true;
                
                // Send to server
                const response = await fetch('manage_templates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(templateData)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Close modal
                    closeModal('templateModal');
                    
                    // Reload templates
                    await loadTemplates();
                    
                    // Show success message
                    showToast('Template saved successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Failed to save template');
                }
            } catch (error) {
                console.error('Error saving template:', error);
                alert('Failed to save template: ' + error.message);
            } finally {
                // Reset save button
                const saveBtn = document.getElementById('saveTemplateBtn');
                saveBtn.innerHTML = 'Save Template';
                saveBtn.disabled = false;
            }
        }

        // Edit template
        async function editTemplate(templateId) {
            try {
                const response = await fetch(`manage_templates.php?action=get&template_id=${templateId}`);
                const data = await response.json();
                
                if (!response.ok || !data.template) {
                    throw new Error(data.error || 'Failed to load template');
                }
                
                const template = data.template;
                
                // Set form values
                document.getElementById('templateName').value = template.name;
                document.getElementById('templateDescription').value = template.description || '';
                document.getElementById('templateDuration').value = template.duration_minutes || 60;
                document.getElementById('templateIcon').value = template.icon || 'dumbbell';
                
                // Set color
                document.querySelectorAll('.qw-color-option').forEach(opt => opt.classList.remove('active'));
                const colorOption = document.querySelector(`.qw-color-option[data-color="${template.color}"]`);
                if (colorOption) {
                    colorOption.classList.add('active');
                } else {
                    document.querySelector('.qw-color-option[data-color="#4361ee"]').classList.add('active');
                }
                document.getElementById('templateColor').value = template.color || '#4361ee';
                
                // Set current template ID
                currentTemplateId = template.id;
                
                // Clear current workout
                document.getElementById('exerciseList').innerHTML = '';
                
                // Add exercises from template
                template.exercises.forEach(exercise => {
                    const exerciseItem = addExerciseToWorkoutFromTemplate(exercise);
                });
                
                // Update template exercises preview
                updateTemplateExercisesPreview();
                
                // Show modal
                document.getElementById('templateModal').style.display = 'block';
                
            } catch (error) {
                console.error('Error loading template for editing:', error);
                alert('Failed to load template for editing');
            }
        }

        // Add exercise from template
        function addExerciseToWorkoutFromTemplate(exercise) {
            const template = document.getElementById('exerciseTemplate');
            const clone = template.content.cloneNode(true);
            
            // Set exercise name
            clone.querySelector('.qw-exercise-name').textContent = exercise.exercise_name;
            
            // Set notes if available
            if (exercise.notes) {
                clone.querySelector('.qw-exercise-notes textarea').value = exercise.notes;
            }
            
            // Add to DOM
            const exerciseList = document.getElementById('exerciseList');
            exerciseList.appendChild(clone);
            
            // Get the newly added exercise item
            const exerciseItem = exerciseList.lastElementChild;
            
            // Add sets
            const setsContainer = exerciseItem.querySelector('.qw-sets-list');
            
            if (exercise.sets && exercise.sets.length > 0) {
                exercise.sets.forEach(set => {
                    addSetToExercise(setsContainer, {
                        weight: set.weight,
                        reps: set.reps,
                        is_warmup: set.is_warmup
                    });
                });
            } else {
                // Add a default set if none exist
                addSetToExercise(setsContainer);
            }
            
            updateWorkoutSummary();
            return exerciseItem;
        }

        // Delete template
        async function deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }
            
            try {
                const response = await fetch('manage_templates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        template_id: templateId
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Reload templates
                    await loadTemplates();
                    
                    // Show success message
                    showToast('Template deleted successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Failed to delete template');
                }
            } catch (error) {
                console.error('Error deleting template:', error);
                alert('Failed to delete template: ' + error.message);
            }
        }

        // Start workout from template
        async function startTemplate(templateId) {
            try {
                const response = await fetch(`manage_templates.php?action=use&template_id=${templateId}`);
                const data = await response.json();
                
                if (!response.ok || !data.template) {
                    throw new Error(data.error || 'Failed to load template');
                }
                
                const template = data.template;
                
                // Clear current workout
                document.getElementById('exerciseList').innerHTML = '';
                
                // Reset timer
                resetWorkoutTimer();
                
                // Add exercises from template
                template.exercises.forEach(exercise => {
                    addExerciseToWorkoutFromTemplate(exercise);
                });
                
                // Show success message
                showToast(`Started "${template.name}" workout!`, 'success');
                
                // Scroll to workout section
                document.querySelector('.qw-current-workout').scrollIntoView({ behavior: 'smooth' });
                
                // Start the timer automatically
                startWorkoutTimer();
                
            } catch (error) {
                console.error('Error starting template workout:', error);
                alert('Failed to start workout from template');
            }
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            // Remove any existing toasts
            const existingToast = document.querySelector('.qw-toast');
            if (existingToast) {
                existingToast.remove();
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `qw-toast qw-toast-${type}`;
            toast.innerHTML = `
                <div class="qw-toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                </div>
                <div class="qw-toast-content">${message}</div>
                <button class="qw-toast-close"><i class="fas fa-times"></i></button>
            `;
            
            // Add to document
            document.body.appendChild(toast);
            
            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
            
            // Close button
            toast.querySelector('.qw-toast-close').addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            });
        }
    </script>
</body>
</html> 