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
    $exercises = json_decode($_POST['exercises_json'], true);
    
    if (empty($name) || empty($exercises)) {
        echo json_encode(['success' => false, 'message' => 'Please provide workout name and add exercises']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO workout_templates (name, description, difficulty, estimated_time, user_id, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssis", $name, $description, $difficulty, $estimatedTime, $userId);
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

function searchExercises($query) {
    global $conn;
    
    $searchQuery = "%$query%";
    $stmt = $conn->prepare("SELECT 
                            id, name, description, exercise_type, equipment, 
                            primary_muscle, secondary_muscles, difficulty, 
                            time_required, calories_burned, video_url, 
                            image_url, thumbnail_url 
                            FROM exercises 
                            WHERE name LIKE ? 
                            OR description LIKE ? 
                            OR primary_muscle LIKE ? 
                            OR exercise_type LIKE ?
                            LIMIT 20");
    
    if (!$stmt) {
        error_log('SQL prepare error: ' . $conn->error);
        return ['error' => 'Database error'];
    }

    $stmt->bind_param("ssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    
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

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'search_exercises' && isset($_GET['query'])) {
        $query = $_GET['query'];
        $exercises = searchExercises($query);
        echo json_encode($exercises);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workout Template | FitTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --danger: #e74c3c;
            --warning: #f39c12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            min-height: 100vh;
            display: flex;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            padding-left: 18%;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .workout-creator {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 24px;
            margin-bottom: 30px;
        }
        
        .input-group {
            margin-bottom: 16px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input[type="text"], 
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus, 
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
        }
        
        .radio-option input[type="radio"] {
            margin-right: 5px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #27ae60;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .exercise-list {
            list-style: none;
            margin-top: 16px;
        }
        
        .exercise-item {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exercise-details {
            flex: 1;
        }
        
        .exercise-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .exercise-meta {
            color: #666;
            font-size: 14px;
        }
        
        .exercise-actions {
            display: flex;
            gap: 10px;
        }
        
        .exercise-search {
            margin-bottom: 24px;
        }
        
        .search-results {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .search-item {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-item:hover {
            background: #f5f5f5;
        }
        
        .search-item:last-child {
            border-bottom: none;
        }
        
        .search-item-title {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .search-item-desc {
            font-size: 13px;
            color: #666;
        }
        
        .drag-handle {
            margin-right: 10px;
            color: #aaa;
            cursor: move;
        }
        
        .form-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #eee;
        }
        
        .exercise-empty {
            padding: 30px;
            text-align: center;
            color: #999;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 6px;
        }
        
        .badge-primary {
            background: var(--primary);
            color: white;
        }
        
        .badge-secondary {
            background: #ddd;
            color: #333;
        }
        
        .exercise-settings {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #eee;
        }
        
        .sets-reps {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .sets-reps input {
            width: 80px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
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
    </style>
</head>
<body>
    <?php require_once 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Create Workout Template</h1>
                <p>Build and save your perfect workout routine</p>
            </div>
            <div>
                <button class="btn btn-primary" id="saveWorkout"><i class="fas fa-save"></i> Save Template</button>
            </div>
        </div>
        
        <div class="workout-creator">
            <div class="input-group">
                <label for="workoutName">Workout Name</label>
                <input type="text" id="workoutName" placeholder="E.g., Upper Body Power, Core Blast, Full Body HIIT...">
            </div>
            
            <div class="input-group">
                <label for="workoutDescription">Description (Optional)</label>
                <textarea id="workoutDescription" rows="3" placeholder="Describe your workout, goals, or add any notes..."></textarea>
            </div>
            
            <div class="input-group">
                <label>Difficulty Level</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="beginner" name="difficulty" value="beginner">
                        <label for="beginner">Beginner</label>
                    </div>
                    
                    <div class="radio-option">
                        <input type="radio" id="intermediate" name="difficulty" value="intermediate" checked>
                        <label for="intermediate">Intermediate</label>
                    </div>
                    
                    <div class="radio-option">
                        <input type="radio" id="advanced" name="difficulty" value="advanced">
                        <label for="advanced">Advanced</label>
                    </div>
                </div>
            </div>
            
            <div class="input-group">
                <label for="estimatedTime">Estimated Time (minutes)</label>
                <input type="number" id="estimatedTime" min="5" max="180" value="30">
            </div>
        </div>
        
        <div class="workout-creator">
            <h2>Exercises</h2>
            <p>Search and add exercises to your workout</p>
            
            <div class="exercise-search">
                <div class="input-group">
                    <label for="exerciseSearch">Search Exercises</label>
                    <input type="text" id="exerciseSearch" placeholder="Start typing to search exercises...">
                </div>
                
                <div class="search-results" id="searchResults" style="display: none;">
                </div>
            </div>
            
            <div id="exerciseContainer">
                <div class="exercise-empty" id="emptyState">
                    <i class="fas fa-dumbbell fa-2x"></i>
                    <h3>No exercises added yet</h3>
                    <p>Search and add exercises to create your workout template</p>
                </div>
                
                <ul class="exercise-list" id="exerciseList">
                </ul>
            </div>
            
            <div class="form-footer">
                <div>
                    <button class="btn btn-danger" id="clearAll"><i class="fas fa-trash"></i> Clear All</button>
                </div>
                <div>
                    <button class="btn btn-secondary" id="previewWorkout"><i class="fas fa-eye"></i> Preview</button>
                    <button class="btn btn-primary" id="saveWorkoutBottom"><i class="fas fa-save"></i> Save Template</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script>
        $(document).ready(function() {
            let selectedExercises = [];
            
            const searchInput = $('#exerciseSearch');
            const searchResults = $('#searchResults');
            const exerciseList = $('#exerciseList');
            const emptyState = $('#emptyState');
            const clearAllBtn = $('#clearAll');
            const saveWorkoutBtn = $('#saveWorkout');
            const saveWorkoutBottomBtn = $('#saveWorkoutBottom');
            const previewWorkoutBtn = $('#previewWorkout');
            const toast = $('#toast');
            
            let searchTimeout;
            searchInput.on('input', function() {
                const query = $(this).val().trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchResults.hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    searchExercises(query);
                }, 300);
            });
            
            function searchExercises(query) {
                searchResults.html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
                searchResults.show();
                
                $.ajax({
                    url: '?action=search_exercises',
                    method: 'GET',
                    data: { query: query },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            searchResults.html('<div class="search-item">Error: ' + data.error + '</div>');
                            return;
                        }
                        
                        if (data.length === 0) {
                            searchResults.html('<div class="search-item">No exercises found matching your search.</div>');
                            return;
                        }
                        
                        renderSearchResults(data);
                    },
                    error: function(xhr, status, error) {
                        searchResults.html('<div class="search-item">Error searching exercises. Please try again.</div>');
                        console.error('Search error:', error, xhr.responseText);
                    }
                });
            }
            
            function renderSearchResults(results) {
                searchResults.empty();
                
                results.forEach(function(exercise) {
                    const resultItem = $('<div class="search-item"></div>');
                    resultItem.html(`
                        <div class="search-item-title">${exercise.name}</div>
                        <div class="search-item-desc">
                            <span class="badge badge-primary">${exercise.primary_muscle}</span>
                            <span class="badge badge-secondary">${exercise.equipment}</span>
                            <div>Difficulty: ${exercise.difficulty}</div>
                            <div>Calories Burned: ${exercise.calories_burned}</div>
                            <div>Time Required: ${exercise.time_required} mins</div>
                        </div>
                    `);
                    
                            resultItem.on('click', function() {
                                addExercise(exercise);
                                searchResults.hide();
                                searchInput.val('');
                            });
                            
                            searchResults.append(resultItem);
                        });
                    }
                    
                    function addExercise(exercise) {
                        const exerciseWithSettings = {
                            exercise_id: exercise.id,
                            name: exercise.name,
                            description: exercise.description,
                            exercise_type: exercise.exercise_type,
                            equipment: exercise.equipment,
                            primary_muscle: exercise.primary_muscle,
                            secondary_muscle: exercise.secondary_muscle || '',
                            difficulty: exercise.difficulty,
                            sets: 3,
                            rest_time: 60,
                            notes: '',
                            showSettings: false
                        };
                        
                        selectedExercises.push(exerciseWithSettings);
                        renderExerciseList();
                    }
                                
                    function renderExerciseList() {
            if (selectedExercises.length === 0) {
                emptyState.show();
                exerciseList.hide();
                return;
            }
            
            emptyState.hide();
            exerciseList.show();
            exerciseList.empty();
            
            selectedExercises.forEach(function(exercise, index) {
                const exerciseItem = $('<li class="exercise-item"></li>');
                exerciseItem.html(`
                    <div class="drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    <div class="exercise-details">
                        <div class="exercise-title">${exercise.name}</div>
                        <div class="exercise-meta">
                            <span class="badge badge-primary">${exercise.primary_muscle}</span>
                            <span class="badge badge-secondary">${exercise.equipment}</span>
                            ${exercise.sets} sets
                        </div>
                        
                        ${exercise.showSettings ? `
                            <div class="exercise-settings">
                                <div class="sets-reps">
                                    <div>
                                        <label>Sets</label>
                                        <input type="number" value="${exercise.sets}" min="1" max="20" class="sets-input" data-index="${index}">
                                    </div>
                                    <div>
                                        <label>Rest (sec)</label>
                                        <input type="number" value="${exercise.rest_time}" min="0" max="300" class="rest-input" data-index="${index}">
                                    </div>
                                </div>
                                <div>
                                    <label>Notes</label>
                                    <textarea class="notes-input" data-index="${index}" rows="2">${exercise.notes}</textarea>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="exercise-actions">
                        <button class="btn btn-secondary toggle-settings" data-index="${index}">
                            <i class="fas ${exercise.showSettings ? 'fa-chevron-up' : 'fa-cog'}"></i>
                        </button>
                        <button class="btn btn-danger remove-exercise" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
                
                exerciseList.append(exerciseItem);
            });
            
            $('.toggle-settings').on('click', function() {
                const index = parseInt($(this).data('index'));
                selectedExercises[index].showSettings = !selectedExercises[index].showSettings;
                renderExerciseList();
            });
            
            $('.remove-exercise').on('click', function() {
                const index = parseInt($(this).data('index'));
                selectedExercises.splice(index, 1);
                renderExerciseList();
            });
            
            $('.sets-input').on('change', function() {
                const index = parseInt($(this).data('index'));
                selectedExercises[index].sets = parseInt($(this).val());
            });
            
            $('.rest-input').on('change', function() {
                const index = parseInt($(this).data('index'));
                selectedExercises[index].rest_time = parseInt($(this).val());
            });
            
            $('.notes-input').on('input', function() {
                const index = parseInt($(this).data('index'));
                selectedExercises[index].notes = $(this).val();
            });
            
            new Sortable(exerciseList[0], {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    const item = selectedExercises[evt.oldIndex];
                    selectedExercises.splice(evt.oldIndex, 1);
                    selectedExercises.splice(evt.newIndex, 0, item);
                }
            });
        }
            
            clearAllBtn.on('click', function() {
                if (selectedExercises.length === 0) return;
                
                if (confirm('Are you sure you want to remove all exercises from this workout?')) {
                    selectedExercises = [];
                    renderExerciseList();
                }
            });
            
            previewWorkoutBtn.on('click', function() {
                if (selectedExercises.length === 0) {
                    showToast('Please add exercises to preview your workout', 'error');
                    return;
                }
                
                const workoutName = $('#workoutName').val() || 'Untitled Workout';
    
                let preview = `<strong>Workout: ${workoutName}</strong><br><br>`;
                selectedExercises.forEach(function(ex, i) {
                    preview += `${i+1}. ${ex.name}: ${ex.sets} sets (${ex.rest_time}s rest)<br>`;
                });
                
                const previewModal = $('<div class="modal"></div>').css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0,0,0,0.7)',
                    'display': 'flex',
                    'justify-content': 'center',
                    'align-items': 'center',
                    'z-index': '1000'
                });
                
                const modalContent = $('<div class="modal-content"></div>').css({
                    'background': 'white',
                    'padding': '20px',
                    'border-radius': '10px',
                    'max-width': '600px',
                    'width': '90%',
                    'max-height': '80vh',
                    'overflow-y': 'auto'
                }).html(`
                    <h2 style="margin-bottom:15px;">Workout Preview</h2>
                    <div>${preview}</div>
                    <div style="margin-top:15px; text-align:right;">
                        <button class="btn btn-primary" id="closePreview">Close</button>
                    </div>
                `);
                
                previewModal.append(modalContent);
                $('body').append(previewModal);
                
                $('#closePreview').on('click', function() {
                    previewModal.remove();
                });
                
                previewModal.on('click', function(e) {
                    if (e.target === this) {
                        previewModal.remove();
                    }
                });
            });
            
            function saveWorkout() {
                const workoutName = $('#workoutName').val();
                const workoutDescription = $('#workoutDescription').val();
                const difficulty = $('input[name="difficulty"]:checked').val();
                const estimatedTime = $('#estimatedTime').val();
                
                if (!workoutName) {
                    showToast('Please enter a name for your workout', 'error');
                    return;
                }
                
                if (selectedExercises.length === 0) {
                    showToast('Please add at least one exercise to your workout', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('save_workout', '1');
                formData.append('workout_name', workoutName);
                formData.append('workout_description', workoutDescription);
                formData.append('difficulty', difficulty);
                formData.append('estimated_time', estimatedTime);
                
                const exercisesData = selectedExercises.map(function(ex) {
                    return {
                        exercise_id: ex.exercise_id,
                        sets: ex.sets,
                        rest_time: ex.rest_time,
                        notes: ex.notes
                    };
                });
                
                formData.append('exercises_json', JSON.stringify(exercisesData));
                
                showToast('Saving workout template...', '');
                
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            
                            setTimeout(function() {
                                window.location.href = 'workout-templates.php';
                            }, 1500);
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('Error saving workout: ' + error, 'error');
                        console.error('Save error:', xhr.responseText);
                    }
                });
            }
            
            saveWorkoutBtn.on('click', saveWorkout);
            saveWorkoutBottomBtn.on('click', saveWorkout);
            
            function showToast(message, type) {
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
            
            $(document).on('click', function(e) {
                if (!searchResults.is(e.target) && searchResults.has(e.target).length === 0 && !searchInput.is(e.target)) {
                    searchResults.hide();
                }
            });
            
            function init() {
                renderExerciseList();
            }
            
            init();
        });
    </script>
</body>
</html>