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
</head>
<body>
    <div class="workout-planner-container">
        <!-- Welcome Screen -->
        <div id="welcomeScreen" class="workout-planner-screen workout-planner-welcome-screen active">
            <h1 class="workout-planner-welcome-title">Workout Planner</h1>
            <div class="workout-planner-welcome-options">
                <button class="workout-planner-btn workout-planner-btn-primary" onclick="showScreen('calendarScreen')">View Calendar</button>
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('presetScreen')">Create Preset</button>
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('historyScreen')">View History</button>
            </div>
        </div>

        <!-- Calendar Screen -->
        <div id="calendarScreen" class="workout-planner-screen">
            <div class="workout-planner-calendar-container">
                <div class="workout-planner-calendar-header">
                    <h2>This Week's Plan</h2>
                    <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('presetScreen')">Create New Preset</button>
                </div>
                <div class="workout-planner-week-days">
                    <div class="workout-planner-week-day">Mon</div>
                    <div class="workout-planner-week-day">Tue</div>
                    <div class="workout-planner-week-day">Wed</div>
                    <div class="workout-planner-week-day">Thu</div>
                    <div class="workout-planner-week-day">Fri</div>
                    <div class="workout-planner-week-day">Sat</div>
                    <div class="workout-planner-week-day">Sun</div>
                </div>
                <div class="workout-planner-week-grid" id="weekGrid">
                    <!-- Days will be added here dynamically -->
                </div>
            </div>
            <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('welcomeScreen')">Back</button>
        </div>

        <!-- Preset Creation Screen -->
        <div id="presetScreen" class="workout-planner-screen">
            <div class="workout-planner-preset-form">
                <h2>Create Workout Preset</h2>
                
                <div class="workout-planner-input-group">
                    <label for="presetName">Preset Name</label>
                    <input type="text" id="presetName" placeholder="E.g., Push Day, Leg Day, Full Body" class="workout-planner-input-field">
                </div>
                
                <div class="workout-planner-exercise-list" id="exerciseList">
                    <!-- Exercise items will be added here -->
                </div>
                
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="addExerciseToPreset()">
                    <i class="fas fa-plus"></i> Add Exercise
                </button>
                
                <button class="workout-planner-btn workout-planner-btn-primary" onclick="savePreset()">Save Preset</button>
            </div>
            <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('calendarScreen')">Back</button>
        </div>

        <!-- Active Workout Screen -->
        <div id="activeWorkoutScreen" class="workout-planner-screen">
            <div class="workout-planner-workout-header">
                <h2 id="currentWorkoutType">Push Day</h2>
                <div class="workout-planner-body-weight-input">
                    <input type="number" id="bodyWeight" placeholder="Body Weight (kg)" step="0.1" class="workout-planner-input">
                </div>
            </div>
            
            <div class="workout-planner-current-exercise">
                <div class="workout-planner-workout-progress">
                    <span>Exercise 1 of 8</span>
                    <span>Set 1 of 3</span>
                </div>
                <div class="workout-planner-progress-bar">
                    <div class="workout-planner-progress-fill" style="width: 30%"></div>
                </div>
                
                <h3 class="workout-planner-exercise-title">Dumbbell Bench Press</h3>
                <div class="workout-planner-set-inputs">
                    <div class="workout-planner-set-input">
                        <label>Set 1</label>
                        <div class="workout-planner-set-input-row">
                            <div class="workout-planner-set-input-field">
                                <label>Weight (kg)</label>
                                <input type="number" placeholder="kg" step="0.5" class="workout-planner-input">
                            </div>
                            <div class="workout-planner-set-input-field">
                                <label>Reps</label>
                                <input type="number" placeholder="Reps" class="workout-planner-input">
                            </div>
                            <div class="workout-planner-set-input-field">
                                <label>RPE</label>
                                <input type="number" placeholder="1-10" min="1" max="10" class="workout-planner-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="workout-planner-action-buttons">
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="previousExercise()">Previous</button>
                <button class="workout-planner-btn workout-planner-btn-primary" onclick="nextExercise()">Next</button>
                <button class="workout-planner-btn workout-planner-btn-primary" onclick="saveWorkout()">Save Workout</button>
            </div>
        </div>

        <!-- History Screen -->
        <div id="historyScreen" class="workout-planner-screen">
            <h2>Workout History</h2>
            <div id="historyContainer">
                <!-- History cards will be added here -->
            </div>
            <button class="workout-planner-btn workout-planner-btn-secondary" onclick="showScreen('welcomeScreen')">Back</button>
        </div>
    </div>

    <!-- Modal for scheduling workout -->
    <div class="workout-planner-modal-overlay" id="scheduleModal">
        <div class="workout-planner-modal">
            <div class="workout-planner-modal-header">
                <h3 class="workout-planner-modal-title">Schedule Workout</h3>
                <button class="workout-planner-modal-close" onclick="closeModal('scheduleModal')">&times;</button>
            </div>
            <div class="workout-planner-modal-body">
                <p>Select a workout preset to schedule:</p>
                <div class="workout-planner-preset-cards" id="presetSelectCards">
                    <!-- Preset cards will be added here -->
                </div>
            </div>
            <div class="workout-planner-modal-footer">
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="closeModal('scheduleModal')">Cancel</button>
                <button class="workout-planner-btn workout-planner-btn-primary" onclick="createNewPreset()">Create New Preset</button>
            </div>
        </div>
    </div>

    <!-- Modal for workout confirmation -->
    <div class="workout-planner-modal-overlay" id="confirmWorkoutModal">
        <div class="workout-planner-modal">
            <div class="workout-planner-modal-header">
                <h3 class="workout-planner-modal-title">Start Workout</h3>
                <button class="workout-planner-modal-close" onclick="closeModal('confirmWorkoutModal')">&times;</button>
            </div>
            <div class="workout-planner-modal-body">
                <p id="confirmWorkoutText">Are you ready to start your workout?</p>
                <div class="workout-planner-input-group">
                    <label for="modalBodyWeight">Your Body Weight Today (kg)</label>
                    <input type="number" id="modalBodyWeight" placeholder="Enter your weight" step="0.1" class="workout-planner-input-field">
                </div>
            </div>
            <div class="workout-planner-modal-footer">
                <button class="workout-planner-btn workout-planner-btn-secondary" onclick="closeModal('confirmWorkoutModal')">Cancel</button>
                <button class="workout-planner-btn workout-planner-btn-primary" id="startWorkoutBtn">Start Workout</button>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="workout-planner-toast" id="toast">Workout saved successfully!</div>

    <script>
        let presets = JSON.parse(localStorage.getItem('workoutPresets')) || [];
        let workoutHistory = JSON.parse(localStorage.getItem('workoutHistory')) || [];
        let currentWorkout = null;
        let currentExerciseIndex = 0;
        let currentSetIndex = 0;
        let selectedDate = null;

        function showScreen(screenId) {
            document.querySelectorAll('.workout-planner-screen').forEach(screen => {
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
                dayCell.className = 'workout-planner-day-cell';
                
                // Highlight today
                if (date.toDateString() === today.toDateString()) {
                    dayCell.classList.add('today');
                }
                
                dayCell.innerHTML = `
                    <div class="workout-planner-day-number">${date.getDate()}</div>
                    <div class="workout-planner-workout-type" data-date="${date.toISOString()}"></div>
                `;
                dayCell.onclick = () => selectDay(date);
                weekGrid.appendChild(dayCell);
            }

            loadPresetsToCalendar();
        }

        function loadPresetsToCalendar() {
            const cells = document.querySelectorAll('.workout-planner-day-cell');
            cells.forEach(cell => {
                const date = cell.querySelector('.workout-planner-workout-type').dataset.date;
                const preset = presets.find(p => p.scheduledDate === date);
                if (preset) {
                    cell.classList.add('has-workout');
                    cell.querySelector('.workout-planner-workout-type').textContent = preset.name;
                }
            });
        }

        function selectDay(date) {
            selectedDate = date;
            
            const cells = document.querySelectorAll('.workout-planner-day-cell');
            cells.forEach(cell => {
                cell.classList.remove('selected');
                if (cell.querySelector('.workout-planner-workout-type').dataset.date === date.toISOString()) {
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
                    card.className = 'workout-planner-preset-card';
                    card.innerHTML = `
                        <h3>${preset.name}</h3>
                        <span class="workout-planner-exercise-count">${preset.exercises.length} exercises</span>
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
            exerciseItem.className = 'workout-planner-exercise-item';
            exerciseItem.innerHTML = `
                <input type="text" placeholder="Exercise name (e.g., Bench Press)" class="workout-planner-exercise-name">
                <div class="workout-planner-exercise-inputs">
                    <input type="number" placeholder="Sets" min="1" value="3" class="workout-planner-sets-count">
                    <button class="workout-planner-btn workout-planner-btn-secondary" onclick="removeExerciseFromPreset(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            exerciseList.appendChild(exerciseItem);
            
            // Focus the new input
            exerciseItem.querySelector('.workout-planner-exercise-name').focus();
        }

        function removeExerciseFromPreset(button) {
            button.closest('.workout-planner-exercise-item').remove();
        }

        function savePreset() {
            const name = document.getElementById('presetName').value;
            if (!name) {
                showToast('Please enter a preset name');
                return;
            }

            const exercises = [];
            document.querySelectorAll('.workout-planner-exercise-item').forEach(item => {
                const exerciseName = item.querySelector('.workout-planner-exercise-name').value;
                const sets = parseInt(item.querySelector('.workout-planner-sets-count').value);
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
            document.querySelector('.workout-planner-exercise-title').textContent = exercise.name;
            
            const setInputs = document.querySelector('.workout-planner-set-inputs');
            setInputs.innerHTML = '';
            
            for (let i = 0; i < exercise.sets.length; i++) {
                const set = exercise.sets[i];
                const setInput = document.createElement('div');
                setInput.className = 'workout-planner-set-input';
                
                // Create recommendation message if it exists
                const recommendationHtml = set.recommendation ? 
                    `<span class="workout-planner-weight-recommendation">↑ Try ${set.recommendation}kg</span>` : '';
                
                setInput.innerHTML = `
                    <label>Set ${i + 1}</label>
                    <div class="workout-planner-set-input-row">
                        <div class="workout-planner-set-input-field">
                            <label>Weight (kg)</label>
                            <input type="number" placeholder="kg" step="0.5" value="${set.weight || ''}" class="workout-planner-input"
                                onchange="updateSetWeight(${i}, this.value)">
                            ${recommendationHtml}
                        </div>
                        <div class="workout-planner-set-input-field">
                            <label>Reps</label>
                            <input type="number" placeholder="Reps" value="${set.reps || ''}" class="workout-planner-input"
                                onchange="updateSetReps(${i}, this.value)">
                        </div>
                        <div class="workout-planner-set-input-field">
                            <label>RPE</label>
                            <input type="number" placeholder="1-10" min="1" max="10" value="${set.rpe || ''}" class="workout-planner-input"
                                onchange="updateSetRPE(${i}, this.value)">
                        </div>
                    </div>
                `;
                setInputs.appendChild(setInput);
            }
            
            document.querySelector('.workout-planner-workout-progress').innerHTML = `
                <span>Exercise ${currentWorkout.currentExerciseIndex + 1} of ${currentWorkout.exercises.length}</span>
                <span>Set ${currentWorkout.currentSetIndex + 1} of ${exercise.sets.length}</span>
            `;

            const totalSets = currentWorkout.exercises.reduce((sum, ex) => sum + ex.sets.length, 0);
            const completedSets = currentWorkout.exercises.slice(0, currentWorkout.currentExerciseIndex)
                .reduce((sum, ex) => sum + ex.sets.length, 0) + currentWorkout.currentSetIndex;
            
            const progress = (completedSets / totalSets) * 100;
            document.querySelector('.workout-planner-progress-fill').style.width = `${progress}%`;
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
                    <div class="workout-planner-history-card">
                        <p style="text-align: center; color: #888;">No workout history yet. Complete your first workout!</p>
                    </div>
                `;
                return;
            }

            workoutHistory.reverse().forEach(workout => {
                const historyCard = document.createElement('div');
                historyCard.className = 'workout-planner-history-card';
                
                const date = new Date(workout.date).toLocaleDateString();
                historyCard.innerHTML = `
                    <div class="workout-planner-history-date">${date} - ${workout.type} (${workout.bodyWeight}kg)</div>
                    ${workout.exercises.map(exercise => `
                        <div class="workout-planner-history-exercise">
                            <strong>${exercise.name}</strong>
                            <div class="workout-planner-sets-list">
                                ${exercise.sets.map((set, index) => `
                                    <div class="workout-planner-set-item">
                                        Set ${index + 1}: ${set.weight || 0}kg × ${set.reps || 0} reps
                                        ${set.rpe ? `<span class="workout-planner-rpe">RPE ${set.rpe}</span>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                            ${exercise.notes ? `<div class="workout-planner-exercise-notes"><em>${exercise.notes}</em></div>` : ''}
                        </div>
                    `).join('')}
                    ${workout.notes ? `<div class="workout-planner-workout-notes"><p><strong>Workout Notes:</strong> ${workout.notes}</p></div>` : ''}
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