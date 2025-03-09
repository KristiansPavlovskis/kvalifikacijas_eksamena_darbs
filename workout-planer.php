<?php
// PHP file converted from HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Planner</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
    <style>
        /* Unique workout planner styles */
        .wplaner-container {
            width: 100%;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            overflow: hidden;
            position: relative;
        }
        
        .wplaner-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s, visibility 0.5s;
            overflow-y: auto;
            padding: 20px;
        }
        
        .wplaner-screen.active {
            opacity: 1;
            visibility: visible;
        }
        
        @keyframes wplaner-fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Welcome screen styles */
        .wplaner-welcome-screen {
            text-align: center;
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-welcome-title {
            font-size: 4rem;
            margin-bottom: 40px;
            color: #ff4d4d;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.5);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 3px;
        }
        
        .wplaner-welcome-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 400px;
        }
        
        /* Button styles */
        .wplaner-btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .wplaner-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s;
        }
        
        .wplaner-btn:hover::before {
            left: 100%;
        }
        
        .wplaner-btn-primary {
            background: #ff4d4d;
            color: white;
        }
        
        .wplaner-btn-primary:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .wplaner-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .wplaner-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Calendar styles */
        .wplaner-calendar-container {
            width: 100%;
            max-width: 1000px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .wplaner-calendar-header h2 {
            color: #ff4d4d;
            font-size: 2rem;
            margin: 0;
        }
        
        .wplaner-week-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
            color: #ff4d4d;
        }
        
        .wplaner-week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            min-height: 300px;
        }
        
        .wplaner-day-cell {
            background: rgba(50, 50, 50, 0.6);
            border-radius: 8px;
            padding: 15px;
            min-height: 150px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        
        .wplaner-day-cell:hover {
            background: rgba(60, 60, 60, 0.8);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .wplaner-day-cell.selected {
            border: 2px solid #ff4d4d;
        }
        
        .wplaner-day-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .wplaner-day-date {
            font-weight: bold;
        }
        
        .wplaner-planned-workout {
            background: rgba(255, 77, 77, 0.2);
            border-left: 3px solid #ff4d4d;
            padding: 5px 10px;
            margin-top: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .wplaner-planned-workout:hover {
            background: rgba(255, 77, 77, 0.3);
        }
        
        /* Preset styles */
        .wplaner-preset-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 1000px;
            margin-bottom: 30px;
        }
        
        .wplaner-preset-card {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-preset-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ff4d4d, #ff9b9b);
        }
        
        .wplaner-preset-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .wplaner-preset-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: white;
        }
        
        .wplaner-preset-info {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #cccccc;
        }
        
        .wplaner-preset-exercises {
            margin-top: 15px;
        }
        
        .wplaner-exercise-tag {
            display: inline-block;
            background: rgba(255, 77, 77, 0.2);
            border-radius: 15px;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: #ff4d4d;
        }
        
        /* Preset form styles */
        .wplaner-preset-form {
            width: 100%;
            max-width: 800px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-form-group {
            margin-bottom: 20px;
        }
        
        .wplaner-form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ff4d4d;
        }
        
        .wplaner-form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(20, 20, 20, 0.8);
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .wplaner-form-control:focus {
            border-color: #ff4d4d;
            box-shadow: 0 0 10px rgba(255, 77, 77, 0.3);
            outline: none;
        }
        
        /* Exercise list styles */
        .wplaner-exercise-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .wplaner-exercise-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .wplaner-exercise-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        
        .wplaner-exercise-list::-webkit-scrollbar-thumb {
            background: rgba(255, 77, 77, 0.5);
            border-radius: 10px;
        }
        
        .wplaner-exercise-item {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .wplaner-exercise-item:hover {
            background: rgba(50, 50, 50, 0.8);
            transform: translateX(5px);
        }
        
        .wplaner-exercise-details {
            flex-grow: 1;
        }
        
        .wplaner-exercise-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .wplaner-exercise-meta {
            font-size: 0.8rem;
            color: #cccccc;
        }
        
        .wplaner-exercise-actions {
            display: flex;
            gap: 10px;
        }
        
        .wplaner-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .wplaner-action-btn:hover {
            background: #ff4d4d;
            transform: scale(1.1);
        }
        
        /* Modal styles */
        .wplaner-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .wplaner-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .wplaner-modal {
            width: 90%;
            max-width: 600px;
            background: #1f1f1f;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(20px);
            transition: all 0.3s;
        }
        
        .wplaner-modal-overlay.active .wplaner-modal {
            transform: translateY(0);
        }
        
        .wplaner-modal-header {
            padding: 20px;
            background: #2a2a2a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .wplaner-modal-title {
            font-size: 1.5rem;
            color: #ff4d4d;
            margin: 0;
        }
        
        .wplaner-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .wplaner-modal-close:hover {
            color: #ff4d4d;
            transform: rotate(90deg);
        }
        
        .wplaner-modal-body {
            padding: 20px;
        }
        
        .wplaner-modal-footer {
            padding: 20px;
            background: #2a2a2a;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Toast notification styles */
        .wplaner-toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .wplaner-toast {
            background: #333;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(120%);
            transition: transform 0.3s ease-out;
        }
        
        .wplaner-toast.show {
            transform: translateX(0);
        }
        
        .wplaner-toast-success {
            background: #00cc66;
        }
        
        .wplaner-toast-error {
            background: #ff4d4d;
        }
        
        .wplaner-toast-icon {
            font-size: 1.2rem;
        }
        
        .wplaner-toast-message {
            flex-grow: 1;
        }
        
        .wplaner-toast-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s;
        }
        
        .wplaner-toast-close:hover {
            opacity: 1;
        }
        
        /* Active workout styles */
        .wplaner-active-workout {
            width: 100%;
            max-width: 800px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-current-exercise {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .wplaner-current-exercise::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: #ff4d4d;
        }
        
        .wplaner-timer {
            font-size: 3rem;
            text-align: center;
            margin: 20px 0;
            color: #ff4d4d;
            font-family: 'Koulen', sans-serif;
            text-shadow: 0 0 10px rgba(255, 77, 77, 0.5);
        }
        
        .wplaner-timer-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .wplaner-timer-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .wplaner-timer-btn:hover {
            background: #ff4d4d;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.3);
        }
        
        .wplaner-workout-progress {
            margin-bottom: 30px;
        }
        
        .wplaner-progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .wplaner-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff4d4d, #ff9b9b);
            width: 0;
            transition: width 0.3s ease-out;
        }
        
        /* History screen styles */
        .wplaner-history-container {
            width: 100%;
            max-width: 1000px;
            animation: wplaner-fadeIn 0.8s ease-out;
        }
        
        .wplaner-history-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .wplaner-history-search {
            flex-grow: 1;
            max-width: 400px;
        }
        
        .wplaner-history-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .wplaner-history-card {
            background: rgba(30, 30, 30, 0.8);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }
        
        .wplaner-history-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .wplaner-history-date {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .wplaner-history-workout {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #ff4d4d;
        }
        
        .wplaner-history-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 20px 0;
        }
        
        .wplaner-stat-item {
            text-align: center;
        }
        
        .wplaner-stat-value {
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }
        
        .wplaner-stat-label {
            font-size: 0.8rem;
            color: #cccccc;
        }
        
        .wplaner-history-notes {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-style: italic;
            color: #cccccc;
        }
    </style>
</head>
<body>
    <div class="wplaner-container">
        <!-- Welcome Screen -->
        <div id="welcomeScreen" class="wplaner-screen wplaner-welcome-screen active">
            <h1 class="wplaner-welcome-title">Workout Planner</h1>
            <div class="wplaner-welcome-options">
                <button class="wplaner-btn wplaner-btn-primary" onclick="showScreen('calendarScreen')">View Calendar</button>
                <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('presetScreen')">Create Preset</button>
                <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('historyScreen')">View History</button>
            </div>
        </div>

        <!-- Calendar Screen -->
        <div id="calendarScreen" class="wplaner-screen">
            <div class="wplaner-calendar-container">
                <div class="wplaner-calendar-header">
                    <h2>This Week's Plan</h2>
                    <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('presetScreen')">Create New Preset</button>
                </div>
                <div class="wplaner-week-days">
                    <div class="wplaner-week-day">Mon</div>
                    <div class="wplaner-week-day">Tue</div>
                    <div class="wplaner-week-day">Wed</div>
                    <div class="wplaner-week-day">Thu</div>
                    <div class="wplaner-week-day">Fri</div>
                    <div class="wplaner-week-day">Sat</div>
                    <div class="wplaner-week-day">Sun</div>
                </div>
                <div class="wplaner-week-grid" id="weekGrid">
                    <!-- Days will be added here dynamically -->
                </div>
            </div>
            <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('welcomeScreen')">Back</button>
        </div>

        <!-- Preset Creation Screen -->
        <div id="presetScreen" class="wplaner-screen">
            <div class="wplaner-preset-form">
                <h2>Create Workout Preset</h2>
                
                <div class="wplaner-input-group">
                    <label for="presetName">Preset Name</label>
                    <input type="text" id="presetName" placeholder="E.g., Push Day, Leg Day, Full Body" class="wplaner-input-field">
                </div>
                
                <div class="wplaner-exercise-list" id="exerciseList">
                    <!-- Exercise items will be added here -->
                </div>
                
                <button class="wplaner-btn wplaner-btn-secondary" onclick="addExerciseToPreset()">
                    <i class="fas fa-plus"></i> Add Exercise
                </button>
                
                <button class="wplaner-btn wplaner-btn-primary" onclick="savePreset()">Save Preset</button>
            </div>
            <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('calendarScreen')">Back</button>
        </div>

        <!-- Active Workout Screen -->
        <div id="activeWorkoutScreen" class="wplaner-screen">
            <div class="wplaner-workout-header">
                <h2 id="currentWorkoutType">Push Day</h2>
                <div class="wplaner-body-weight-input">
                    <input type="number" id="bodyWeight" placeholder="Body Weight (kg)" step="0.1" class="wplaner-input">
                </div>
            </div>
            
            <div class="wplaner-current-exercise">
                <div class="wplaner-workout-progress">
                    <span>Exercise 1 of 8</span>
                    <span>Set 1 of 3</span>
                </div>
                <div class="wplaner-progress-bar">
                    <div class="wplaner-progress-fill" style="width: 30%"></div>
                </div>
                
                <h3 class="wplaner-exercise-title">Dumbbell Bench Press</h3>
                <div class="wplaner-set-inputs">
                    <div class="wplaner-set-input">
                        <label>Set 1</label>
                        <div class="wplaner-set-input-row">
                            <div class="wplaner-set-input-field">
                                <label>Weight (kg)</label>
                                <input type="number" placeholder="kg" step="0.5" class="wplaner-input"
                                    onchange="updateSetWeight(${i}, this.value)">
                            </div>
                            <div class="wplaner-set-input-field">
                                <label>Reps</label>
                                <input type="number" placeholder="Reps" class="wplaner-input"
                                    onchange="updateSetReps(${i}, this.value)">
                            </div>
                            <div class="wplaner-set-input-field">
                                <label>RPE</label>
                                <input type="number" placeholder="1-10" min="1" max="10" class="wplaner-input"
                                    onchange="updateSetRPE(${i}, this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wplaner-action-buttons">
                <button class="wplaner-btn wplaner-btn-secondary" onclick="previousExercise()">Previous</button>
                <button class="wplaner-btn wplaner-btn-primary" onclick="nextExercise()">Next</button>
                <button class="wplaner-btn wplaner-btn-primary" onclick="saveWorkout()">Save Workout</button>
            </div>
        </div>

        <!-- History Screen -->
        <div id="historyScreen" class="wplaner-screen">
            <h2>Workout History</h2>
            <div id="historyContainer">
                <!-- History cards will be added here -->
            </div>
            <button class="wplaner-btn wplaner-btn-secondary" onclick="showScreen('welcomeScreen')">Back</button>
        </div>
    </div>

    <!-- Modal for scheduling workout -->
    <div class="wplaner-modal-overlay" id="scheduleModal">
        <div class="wplaner-modal">
            <div class="wplaner-modal-header">
                <h3 class="wplaner-modal-title">Schedule Workout</h3>
                <button class="wplaner-modal-close" onclick="closeModal('scheduleModal')">&times;</button>
            </div>
            <div class="wplaner-modal-body">
                <p>Select a workout preset to schedule:</p>
                <div class="wplaner-preset-cards" id="presetSelectCards">
                    <!-- Preset cards will be added here -->
                </div>
            </div>
            <div class="wplaner-modal-footer">
                <button class="wplaner-btn wplaner-btn-secondary" onclick="closeModal('scheduleModal')">Cancel</button>
                <button class="wplaner-btn wplaner-btn-primary" onclick="createNewPreset()">Create New Preset</button>
            </div>
        </div>
    </div>

    <!-- Modal for workout confirmation -->
    <div class="wplaner-modal-overlay" id="confirmWorkoutModal">
        <div class="wplaner-modal">
            <div class="wplaner-modal-header">
                <h3 class="wplaner-modal-title">Start Workout</h3>
                <button class="wplaner-modal-close" onclick="closeModal('confirmWorkoutModal')">&times;</button>
            </div>
            <div class="wplaner-modal-body">
                <p id="confirmWorkoutText">Are you ready to start your workout?</p>
                <div class="wplaner-input-group">
                    <label for="modalBodyWeight">Your Body Weight Today (kg)</label>
                    <input type="number" id="modalBodyWeight" placeholder="Enter your weight" step="0.1" class="wplaner-input-field">
                </div>
            </div>
            <div class="wplaner-modal-footer">
                <button class="wplaner-btn wplaner-btn-secondary" onclick="closeModal('confirmWorkoutModal')">Cancel</button>
                <button class="wplaner-btn wplaner-btn-primary" id="startWorkoutBtn">Start Workout</button>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="wplaner-toast" id="toast">Workout saved successfully!</div>

    <script>
        let presets = JSON.parse(localStorage.getItem('workoutPresets')) || [];
        let workoutHistory = JSON.parse(localStorage.getItem('workoutHistory')) || [];
        let currentWorkout = null;
        let currentExerciseIndex = 0;
        let currentSetIndex = 0;
        let selectedDate = null;

        function showScreen(screenId) {
            document.querySelectorAll('.wplaner-screen').forEach(screen => {
                screen.classList.remove('active');
            });
            document.getElementById(screenId).classList.add('active');
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Toast notification
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Initialize calendar
        function initializeCalendar() {
            const weekGrid = document.getElementById('weekGrid');
            weekGrid.innerHTML = ''; // Clear existing grid
            
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Start from Monday

            for (let i = 0; i < 7; i++) {
                const date = new Date(startOfWeek);
                date.setDate(startOfWeek.getDate() + i);
                const dayCell = document.createElement('div');
                dayCell.className = 'wplaner-day-cell';
                
                // Highlight today
                if (date.toDateString() === today.toDateString()) {
                    dayCell.classList.add('today');
                }
                
                dayCell.innerHTML = `
                    <div class="wplaner-day-number">${date.getDate()}</div>
                    <div class="wplaner-workout-type" data-date="${date.toISOString()}"></div>
                `;
                dayCell.onclick = () => selectDay(date);
                weekGrid.appendChild(dayCell);
            }

            loadPresetsToCalendar();
        }

        function loadPresetsToCalendar() {
            const cells = document.querySelectorAll('.wplaner-day-cell');
            cells.forEach(cell => {
                const date = cell.querySelector('.wplaner-workout-type').dataset.date;
                const preset = presets.find(p => p.scheduledDate === date);
                if (preset) {
                    cell.classList.add('has-workout');
                    cell.querySelector('.wplaner-workout-type').textContent = preset.name;
                }
            });
        }

        function selectDay(date) {
            selectedDate = date;
            
            const cells = document.querySelectorAll('.wplaner-day-cell');
            cells.forEach(cell => {
                cell.classList.remove('selected');
                if (cell.querySelector('.wplaner-workout-type').dataset.date === date.toISOString()) {
                    cell.classList.add('selected');
                }
            });

            const preset = presets.find(p => p.scheduledDate === date.toISOString());
            if (preset) {
                // Show confirmation modal
                document.getElementById('confirmWorkoutText').textContent = 
                    `Start your "${preset.name}" workout for ${date.toLocaleDateString()}?`;
                
                document.getElementById('startWorkoutBtn').onclick = () => {
                    const bodyWeight = document.getElementById('modalBodyWeight').value;
                    if (!bodyWeight) {
                        alert('Please enter your body weight');
                        return;
                    }
                    closeModal('confirmWorkoutModal');
                    startWorkout(preset, bodyWeight);
                };
                
                openModal('confirmWorkoutModal');
            } else {
                // Schedule a workout
                populatePresetCards();
                openModal('scheduleModal');
            }
        }

        function populatePresetCards() {
            const presetCards = document.getElementById('presetSelectCards');
            presetCards.innerHTML = '';
            
            if (presets.length === 0) {
                presetCards.innerHTML = `
                    <p style="text-align: center; color: #888; padding: 20px;">
                        No presets available. Create your first workout preset!
                    </p>
                `;
                return;
            }
            
            presets.forEach(preset => {
                if (!preset.scheduledDate) { // Only show unscheduled presets
                    const card = document.createElement('div');
                    card.className = 'wplaner-preset-card';
                    card.innerHTML = `
                        <h3>${preset.name}</h3>
                        <span class="wplaner-exercise-count">${preset.exercises.length} exercises</span>
                    `;
                    card.onclick = () => scheduleWorkout(preset);
                    presetCards.appendChild(card);
                }
            });
        }

        function scheduleWorkout(preset) {
            if (selectedDate) {
                preset.scheduledDate = selectedDate.toISOString();
                localStorage.setItem('workoutPresets', JSON.stringify(presets));
                loadPresetsToCalendar();
                closeModal('scheduleModal');
                showToast(`${preset.name} scheduled for ${selectedDate.toLocaleDateString()}`);
            }
        }

        function createNewPreset() {
            closeModal('scheduleModal');
            showScreen('presetScreen');
        }

        function addExerciseToPreset() {
            const exerciseList = document.getElementById('exerciseList');
            const exerciseItem = document.createElement('div');
            exerciseItem.className = 'wplaner-exercise-item';
            exerciseItem.innerHTML = `
                <input type="text" placeholder="Exercise name (e.g., Bench Press)" class="wplaner-exercise-name">
                <div class="wplaner-exercise-inputs">
                    <input type="number" placeholder="Sets" min="1" value="3" class="wplaner-sets-count">
                    <button class="wplaner-btn wplaner-btn-secondary" onclick="removeExerciseFromPreset(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            exerciseList.appendChild(exerciseItem);
            
            // Focus the new input
            exerciseItem.querySelector('.wplaner-exercise-name').focus();
        }

        function removeExerciseFromPreset(button) {
            button.closest('.wplaner-exercise-item').remove();
        }

        function savePreset() {
            const name = document.getElementById('presetName').value;
            if (!name) {
                showToast('Please enter a preset name');
                return;
            }

            const exercises = [];
            document.querySelectorAll('.wplaner-exercise-item').forEach(item => {
                const exerciseName = item.querySelector('.wplaner-exercise-name').value;
                const sets = parseInt(item.querySelector('.wplaner-sets-count').value);
                if (exerciseName && sets) {
                    exercises.push({ name: exerciseName, sets });
                }
            });

            if (exercises.length === 0) {
                showToast('Please add at least one exercise');
                return;
            }

            presets.push({
                name,
                exercises,
                id: Date.now()
            });

            localStorage.setItem('workoutPresets', JSON.stringify(presets));
            showScreen('calendarScreen');
            loadPresetsToCalendar();
            showToast(`Preset "${name}" created successfully`);
        }

        function startWorkout(preset, bodyWeight) {
            // Search history for the last time this preset/exercises were done
            const lastWorkoutData = findLastWorkoutWithExercises(preset.exercises.map(e => e.name));

            currentWorkout = {
                type: preset.name,
                date: new Date().toISOString(),
                bodyWeight: parseFloat(bodyWeight),
                exercises: preset.exercises.map(ex => {
                    // Find this exercise in the last workout if it exists
                    const lastExerciseData = lastWorkoutData ? 
                        lastWorkoutData.exercises.find(e => e.name === ex.name) : null;
                    
                    // Create sets with previous data or defaults
                    return {
                        ...ex,
                        sets: Array(ex.sets).fill().map((_, i) => {
                            const lastSet = lastExerciseData && lastExerciseData.sets[i] 
                                ? lastExerciseData.sets[i] 
                                : null;
                            
                            return {
                                weight: lastSet ? lastSet.weight : 0,
                                reps: lastSet ? lastSet.reps : 0,
                                rpe: lastSet ? lastSet.rpe : 0,
                                recommendation: getWeightRecommendation(lastSet)
                            };
                        })
                    };
                }),
                currentExerciseIndex: 0,
                currentSetIndex: 0,
                notes: ""
            };

            document.getElementById('bodyWeight').value = bodyWeight;
            document.getElementById('currentWorkoutType').textContent = preset.name;
            
            showScreen('activeWorkoutScreen');
            updateWorkoutDisplay();
        }

        function findLastWorkoutWithExercises(exerciseNames) {
            // Reverse to start with most recent
            const sortedHistory = [...workoutHistory].reverse();
            
            // Find the most recent workout that contained any of these exercises
            for (const workout of sortedHistory) {
                // Check if this workout contains any of the exercises we're looking for
                const containsExercises = workout.exercises.some(exercise => 
                    exerciseNames.includes(exercise.name)
                );
                
                if (containsExercises) {
                    return workout;
                }
            }
            
            return null;
        }

        function getWeightRecommendation(lastSet) {
            if (!lastSet || !lastSet.reps || !lastSet.weight) return null;
            
            // If the user did 10+ reps in their last set, recommend increasing weight
            if (lastSet.reps >= 10) {
                // Recommend 5-10% more weight depending on the current weight
                const incrementPercent = lastSet.weight < 10 ? 0.5 : 0.1; // 0.5kg or 10%
                const recommendedWeight = Math.ceil(lastSet.weight * (1 + incrementPercent) * 2) / 2; // Round to nearest 0.5
                return recommendedWeight;
            }
            
            return null;
        }

        function updateWorkoutDisplay() {
            const exercise = currentWorkout.exercises[currentWorkout.currentExerciseIndex];
            document.querySelector('.wplaner-exercise-title').textContent = exercise.name;
            
            const setInputs = document.querySelector('.wplaner-set-inputs');
            setInputs.innerHTML = '';
            
            for (let i = 0; i < exercise.sets.length; i++) {
                const set = exercise.sets[i];
                const setInput = document.createElement('div');
                setInput.className = 'wplaner-set-input';
                
                // Create recommendation message if it exists
                const recommendationHtml = set.recommendation ? 
                    `<span class="wplaner-weight-recommendation">↑ Try ${set.recommendation}kg</span>` : '';
                
                setInput.innerHTML = `
                    <label>Set ${i + 1}</label>
                    <div class="wplaner-set-input-row">
                        <div class="wplaner-set-input-field">
                            <label>Weight (kg)</label>
                            <input type="number" placeholder="kg" step="0.5" value="${set.weight || ''}" class="wplaner-input"
                                onchange="updateSetWeight(${i}, this.value)">
                            ${recommendationHtml}
                        </div>
                        <div class="wplaner-set-input-field">
                            <label>Reps</label>
                            <input type="number" placeholder="Reps" value="${set.reps || ''}" class="wplaner-input"
                                onchange="updateSetReps(${i}, this.value)">
                        </div>
                        <div class="wplaner-set-input-field">
                            <label>RPE</label>
                            <input type="number" placeholder="1-10" min="1" max="10" value="${set.rpe || ''}" class="wplaner-input"
                                onchange="updateSetRPE(${i}, this.value)">
                        </div>
                    </div>
                `;
                setInputs.appendChild(setInput);
            }
            
            document.querySelector('.wplaner-workout-progress').innerHTML = `
                <span>Exercise ${currentWorkout.currentExerciseIndex + 1} of ${currentWorkout.exercises.length}</span>
                <span>Set ${currentWorkout.currentSetIndex + 1} of ${exercise.sets.length}</span>
            `;

            const totalSets = currentWorkout.exercises.reduce((sum, ex) => sum + ex.sets.length, 0);
            const completedSets = currentWorkout.exercises.slice(0, currentWorkout.currentExerciseIndex)
                .reduce((sum, ex) => sum + ex.sets.length, 0) + currentWorkout.currentSetIndex;
            
            const progress = (completedSets / totalSets) * 100;
            document.querySelector('.wplaner-progress-fill').style.width = `${progress}%`;
        }

        function updateSetWeight(setIndex, weight) {
            currentWorkout.exercises[currentWorkout.currentExerciseIndex].sets[setIndex].weight = parseFloat(weight);
        }

        function updateSetReps(setIndex, reps) {
            currentWorkout.exercises[currentWorkout.currentExerciseIndex].sets[setIndex].reps = parseInt(reps);
        }
        
        function updateSetRPE(setIndex, rpe) {
            currentWorkout.exercises[currentWorkout.currentExerciseIndex].sets[setIndex].rpe = parseInt(rpe);
        }

        function nextExercise() {
            if (currentWorkout.currentExerciseIndex < currentWorkout.exercises.length - 1) {
                currentWorkout.currentExerciseIndex++;
                currentWorkout.currentSetIndex = 0;
                updateWorkoutDisplay();
            } else {
                showToast('Last exercise! Save your workout when done.');
            }
        }

        function previousExercise() {
            if (currentWorkout.currentExerciseIndex > 0) {
                currentWorkout.currentExerciseIndex--;
                currentWorkout.currentSetIndex = 0;
                updateWorkoutDisplay();
            }
        }

        function saveWorkout() {
            workoutHistory.push(currentWorkout);
            localStorage.setItem('workoutHistory', JSON.stringify(workoutHistory));
            showScreen('historyScreen');
            loadHistory();
            showToast('Workout saved successfully!');
        }

        function loadHistory() {
            const historyContainer = document.getElementById('historyContainer');
            historyContainer.innerHTML = '';

            if (workoutHistory.length === 0) {
                historyContainer.innerHTML = `
                    <div class="wplaner-history-card">
                        <p style="text-align: center; color: #888;">No workout history yet. Complete your first workout!</p>
                    </div>
                `;
                return;
            }

            workoutHistory.reverse().forEach(workout => {
                const historyCard = document.createElement('div');
                historyCard.className = 'wplaner-history-card';
                
                const date = new Date(workout.date).toLocaleDateString();
                historyCard.innerHTML = `
                    <div class="wplaner-history-date">${date} - ${workout.type} (${workout.bodyWeight}kg)</div>
                    ${workout.exercises.map(exercise => `
                        <div class="wplaner-history-exercise">
                            <strong>${exercise.name}</strong>
                            <div class="wplaner-sets-list">
                                ${exercise.sets.map((set, index) => `
                                    <div class="wplaner-set-item">
                                        Set ${index + 1}: ${set.weight || 0}kg × ${set.reps || 0} reps
                                        ${set.rpe ? `<span class="wplaner-rpe">RPE ${set.rpe}</span>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                            ${exercise.notes ? `<div class="wplaner-exercise-notes"><em>${exercise.notes}</em></div>` : ''}
                        </div>
                    `).join('')}
                    ${workout.notes ? `<div class="wplaner-workout-notes"><p><strong>Workout Notes:</strong> ${workout.notes}</p></div>` : ''}
                `;
                historyContainer.appendChild(historyCard);
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeCalendar();
            loadHistory();
            
            // Add first exercise by default in preset creation
            if (document.getElementById('exerciseList').children.length === 0) {
                addExerciseToPreset();
            }
        });
    </script>
</body>
</html>