document.addEventListener('DOMContentLoaded', function() {

    const muscleMap = document.getElementById('interactive-muscle-map');
    if (!muscleMap) return;

    loadMuscleSvg();

    const selectedMuscleTitle = document.getElementById('selected-muscle');
    const muscleDescription = document.getElementById('muscle-description');
    let activeMuscleName = null;

    function loadMuscleSvg() {
        fetch('/assets/images/muscle-map.svg')
            .then(response => response.text())
            .then(svgContent => {
                muscleMap.innerHTML = svgContent;
                
                setupMuscleClickEvents();
                
                setupMuscleHoverEvents();
            })
            .catch(error => {
                console.error('Error loading muscle map:', error);
                muscleMap.innerHTML = '<div class="error-message">Failed to load muscle map. Please try again later.</div>';
            });
    }

    function setupMuscleClickEvents() {
        const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
        
        muscleGroups.forEach(muscle => {
            muscle.addEventListener('click', function() {
                const muscleName = this.getAttribute('data-name');
                const displayName = this.getAttribute('data-display-name') || muscleName;
                
                if (activeMuscleName === muscleName) {
                    deselectMuscle(muscleGroups);
                    activeMuscleName = null;
                    
                    selectedMuscleTitle.textContent = 'Select a muscle group';
                    muscleDescription.textContent = 'Click on any muscle group to filter exercises.';
                    
                    resetExerciseFilters();
                } else {    
                    deselectMuscle(muscleGroups);
                    this.classList.add('active');
                    activeMuscleName = muscleName;
                    
                    const muscleInfo = getMuscleInfo(muscleName);
                    updateMuscleInfo(muscleInfo);
                    
                    filterExercisesByMuscle(muscleName);
                }
            });
        });
    }
    
    function setupMuscleHoverEvents() {
        const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
        const hoverLabel = document.getElementById('hover-label');
        
        if (!hoverLabel) return;
        
        muscleGroups.forEach(muscle => {
            muscle.addEventListener('mouseenter', function() {
                const displayName = this.getAttribute('data-display-name') || this.getAttribute('data-name');
                hoverLabel.textContent = displayName;
                hoverLabel.style.fontSize = '14px';
            });
            
            muscle.addEventListener('mouseleave', function() {
                hoverLabel.textContent = '';
                hoverLabel.style.fontSize = '0';
            });
        });
    }
    
    function deselectMuscle(muscleGroups) {
        muscleGroups.forEach(m => m.classList.remove('active'));
    }

    function getMuscleInfo(muscleName) {
        const muscleData = {
            'chest': {
                name: 'Chest (Pectoralis)',
                description: 'Targeting the chest helps with pushing movements and upper body strength. Filter to see chest exercises.',
                exercises: ['Bench Press', 'Push-ups', 'Chest Fly', 'Dips']
            },
            'back': {
                name: 'Back (Latissimus Dorsi)',
                description: 'Back muscles support pulling movements and posture. Filter to see back exercises.',
                exercises: ['Pull-ups', 'Rows', 'Lat Pulldowns', 'Deadlifts']
            },
            'shoulders': {
                name: 'Shoulders (Deltoids)',
                description: 'Strong shoulders improve arm mobility and support overhead movements. Filter to see shoulder exercises.',
                exercises: ['Overhead Press', 'Lateral Raises', 'Front Raises', 'Face Pulls']
            },
            'biceps': {
                name: 'Biceps',
                description: 'Biceps help with pulling and lifting movements. Filter to see bicep exercises.',
                exercises: ['Bicep Curls', 'Hammer Curls', 'Chin-ups', 'Preacher Curls']
            },
            'forearms': {
                name: 'Forearms',
                description: 'Strong forearms improve grip strength and wrist stability. Filter to see forearm exercises.',
                exercises: ['Wrist Curls', 'Reverse Curls', 'Farmer\'s Walk', 'Plate Pinches']
            },
            'abs': {
                name: 'Abdominals',
                description: 'Core muscles stabilize your body and help with everyday movements. Filter to see ab exercises.',
                exercises: ['Crunches', 'Planks', 'Leg Raises', 'Russian Twists']
            },
            'obliques': {
                name: 'Obliques',
                description: 'Obliques help with torso rotation and lateral bending. Filter to see oblique exercises.',
                exercises: ['Russian Twists', 'Side Planks', 'Wood Choppers', 'Bicycle Crunches']
            },
            'quads': {
                name: 'Quadriceps',
                description: 'Quads are essential for walking, running, and jumping. Filter to see quad exercises.',
                exercises: ['Squats', 'Leg Press', 'Lunges', 'Leg Extensions']
            },
            'calves': {
                name: 'Calves',
                description: 'Calf muscles help with ankle movement and stabilization. Filter to see calf exercises.',
                exercises: ['Calf Raises', 'Jump Rope', 'Seated Calf Raises', 'Box Jumps']
            }
        };
        
        return muscleData[muscleName.toLowerCase()] || {
            name: muscleName.charAt(0).toUpperCase() + muscleName.slice(1),
            description: 'Filter exercises for this muscle group.',
            exercises: []
        };
    }

    function updateMuscleInfo(muscleInfo) {
        selectedMuscleTitle.textContent = muscleInfo.name;
        muscleDescription.textContent = muscleInfo.description;
    }
    
    function filterExercisesByMuscle(muscleName) {
        const exerciseCards = document.querySelectorAll('.exercises-card');
        const resultCount = document.getElementById('result-count');
        let visibleCount = 0;
        
        exerciseCards.forEach(card => {
            const cardMuscles = card.getAttribute('data-muscles') || '';
            
            const matches = cardMuscles.toLowerCase().includes(muscleName.toLowerCase());
            
            if (matches) {
                card.style.display = '';
                visibleCount++;
                
                const muscleTags = card.querySelectorAll('.muscle-tag');
                muscleTags.forEach(tag => {
                    if (tag.textContent.toLowerCase().includes(muscleName.toLowerCase())) {
                        tag.classList.add('active');
                    } else {
                        tag.classList.remove('active');
                    }
                });
            } else {
                card.style.display = 'none';
            }
        });
        
        if (resultCount) {
            resultCount.textContent = `${visibleCount} exercise${visibleCount !== 1 ? 's' : ''} found`;
        }
        
        showNoResultsMessage(visibleCount === 0, muscleName);
    }
    
    function resetExerciseFilters() {
        const exerciseCards = document.querySelectorAll('.exercises-card');
        const resultCount = document.getElementById('result-count');
        const muscleTags = document.querySelectorAll('.muscle-tag');
        
        exerciseCards.forEach(card => {
            card.style.display = '';
        });
        
        muscleTags.forEach(tag => {
            tag.classList.remove('active');
        });
        
        if (resultCount) {
            resultCount.textContent = `${exerciseCards.length} exercise${exerciseCards.length !== 1 ? 's' : ''} found`;
        }
        
        hideNoResultsMessage();
    }
    
    function showNoResultsMessage(show, muscleName) {
        let noResultsMessage = document.querySelector('.no-results-message');
        
        if (show) {
            if (!noResultsMessage) {
                noResultsMessage = document.createElement('div');
                noResultsMessage.className = 'no-results-message';
                
                const muscleInfo = getMuscleInfo(muscleName);
                const muscleName_display = muscleInfo.name;
                
                noResultsMessage.innerHTML = `
                    <h3>No exercises found for ${muscleName_display}</h3>
                    <p>Try selecting a different muscle group or check back later for more exercises.</p>
                    <button id="clear-muscle-filter" class="btn btn-outline">Clear Filter</button>
                `;
                
                const exerciseCards = document.querySelector('.exercises-cards');
                if (exerciseCards) {
                    exerciseCards.after(noResultsMessage);
                    
                    document.getElementById('clear-muscle-filter').addEventListener('click', function() {
                        const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
                        deselectMuscle(muscleGroups);
                        activeMuscleName = null;
                        
                        selectedMuscleTitle.textContent = 'Select a muscle group';
                        muscleDescription.textContent = 'Click on any muscle group to filter exercises.';
                        
                        resetExerciseFilters();
                    });
                }
            }
            noResultsMessage.style.display = 'block';
        } else if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
    }
    
    function hideNoResultsMessage() {
        const noResultsMessage = document.querySelector('.no-results-message');
        if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
    }
    
    function syncWithOtherFilters() {
        const otherFilters = document.querySelectorAll('.filter-select');
        const searchInput = document.getElementById('exercise-search');
        const resetButton = document.getElementById('reset-filters');
        
        if (otherFilters) {
            otherFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    if (this.value && activeMuscleName) {
                        const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
                        deselectMuscle(muscleGroups);
                        activeMuscleName = null;
                        
                        selectedMuscleTitle.textContent = 'Select a muscle group';
                        muscleDescription.textContent = 'Click on any muscle group to filter exercises.';
                    }
                });
            });
        }
        
        if (searchInput) {
                searchInput.addEventListener('input', function() {
                    if (this.value && activeMuscleName) {
                        const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
                        deselectMuscle(muscleGroups);
                        activeMuscleName = null;
                        
                    selectedMuscleTitle.textContent = 'Select a muscle group';
                    muscleDescription.textContent = 'Click on any muscle group to filter exercises.';
                }
            });
        }
        
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                if (activeMuscleName) {
                    const muscleGroups = muscleMap.querySelectorAll('.muscle-group');
                    deselectMuscle(muscleGroups);
                    activeMuscleName = null;
                    
                    selectedMuscleTitle.textContent = 'Select a muscle group';
                    muscleDescription.textContent = 'Click on any muscle group to filter exercises.';
                }
            });
        }
    }
    
    syncWithOtherFilters();
}); 