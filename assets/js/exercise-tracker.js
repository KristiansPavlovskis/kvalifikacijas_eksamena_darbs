document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in (we'll use session storage for demo)
    const isLoggedIn = sessionStorage.getItem('user_logged_in') || document.body.classList.contains('logged-in');
    
    // Elements
    const saveButtons = document.querySelectorAll('.save-exercise');
    const challengeButtons = document.querySelectorAll('.btn-challenge');
    const achievementTemplate = document.getElementById('achievement-template');
    
    // Initialize
    initSaveButtons();
    initChallengeButtons();
    initLoadMore();
    initFilters();
    
    /**
     * Save exercise to user's collection
     */
    function initSaveButtons() {
        if (!isLoggedIn || !saveButtons.length) return;
        
        saveButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const exerciseId = this.getAttribute('data-id');
                
                // Toggle saved state
                if (this.classList.contains('saved')) {
                    this.classList.remove('saved');
                    this.querySelector('i').classList.remove('fas');
                    this.querySelector('i').classList.add('far');
                    
                    // If we had an API, we'd call it here to remove from saved
                    // saveExercise(exerciseId, false);
                    
                    showToast('Exercise removed from your collection');
                } else {
                    this.classList.add('saved');
                    this.querySelector('i').classList.remove('far');
                    this.querySelector('i').classList.add('fas');
                    
                    // If we had an API, we'd call it here to save
                    // saveExercise(exerciseId, true);
                    
                    showToast('Exercise saved to your collection');
                    
                    // Check if this completes an achievement
                    checkForAchievements('save');
                }
            });
        });
    }
    
    /**
     * Handle challenge acceptance
     */
    function initChallengeButtons() {
        if (!isLoggedIn || !challengeButtons.length) return;
        
        challengeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const challengeCard = this.closest('.challenge-card');
                
                // If already accepted, do nothing
                if (challengeCard.classList.contains('accepted')) {
                    return;
                }
                
                // Mark as accepted
                challengeCard.classList.add('accepted');
                this.textContent = 'Challenge Accepted';
                
                // If we had an API, we'd call it here
                // acceptChallenge(challengeId);
                
                showToast('Challenge accepted! Track your progress in your profile.');
            });
        });
    }
    
    /**
     * Handle load more button
     */
    function initLoadMore() {
        const loadMoreBtn = document.getElementById('load-more');
        if (!loadMoreBtn) return;
        
        loadMoreBtn.addEventListener('click', function() {
            // In a real implementation, this would load more exercises via AJAX
            // For demo, we'll just disable the button
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            
            setTimeout(() => {
                this.classList.remove('loading');
                this.classList.add('disabled');
                this.innerHTML = 'No more exercises to load';
            }, 1500);
        });
    }
    
    /**
     * Handle filters
     */
    function initFilters() {
        const filters = document.querySelectorAll('.filter-select');
        const resetBtn = document.getElementById('reset-filters');
        const searchInput = document.getElementById('exercise-search');
        
        if (!filters.length) return;
        
        // Add change event to each filter
        filters.forEach(filter => {
            filter.addEventListener('change', applyFilters);
        });
        
        // Add search event
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
        
        // Reset filters
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                // Reset all filter selects
                filters.forEach(filter => {
                    filter.selectedIndex = 0;
                });
                
                // Clear search
                if (searchInput) {
                    searchInput.value = '';
                }
                
                // Apply (reset) filters
                applyFilters();
            });
        }
    }
    
    /**
     * Apply filters and search to exercise cards
     */
    function applyFilters() {
        const cards = document.querySelectorAll('.exercises-card');
        const difficultyFilter = document.getElementById('difficulty-filter');
        const equipmentFilter = document.getElementById('equipment-filter');
        const goalFilter = document.getElementById('goal-filter');
        const searchInput = document.getElementById('exercise-search');
        const resultCount = document.getElementById('result-count');
        
        if (!cards.length) return;
        
        // Get filter values
        const difficulty = difficultyFilter ? difficultyFilter.value.toLowerCase() : '';
        const equipment = equipmentFilter ? equipmentFilter.value.toLowerCase() : '';
        const goal = goalFilter ? goalFilter.value.toLowerCase() : '';
        const search = searchInput ? searchInput.value.toLowerCase() : '';
        
        let visibleCount = 0;
        
        // Filter cards
        cards.forEach(card => {
            const cardDifficulty = card.querySelector('.exercises-difficulty-badge').textContent.trim().toLowerCase();
            const cardEquipment = card.querySelector('.equipment-badge') ? 
                card.querySelector('.equipment-badge').textContent.trim().toLowerCase() : '';
            
            // We would need to add goal data to cards for this filter to work
            const cardGoal = card.getAttribute('data-goal') || '';
            
            const cardTitle = card.querySelector('h2').textContent.toLowerCase();
            const cardDescription = card.querySelector('p').textContent.toLowerCase();
            
            // Check if card matches all applied filters
            const matchesDifficulty = !difficulty || cardDifficulty.includes(difficulty);
            const matchesEquipment = !equipment || cardEquipment.includes(equipment);
            const matchesGoal = !goal || cardGoal.includes(goal);
            const matchesSearch = !search || 
                cardTitle.includes(search) || 
                cardDescription.includes(search);
            
            // Show/hide based on filter match
            if (matchesDifficulty && matchesEquipment && matchesGoal && matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update result count
        if (resultCount) {
            resultCount.textContent = `${visibleCount} exercise${visibleCount !== 1 ? 's' : ''} found`;
        }
        
        // Show/hide no results message
        showNoResultsMessage(visibleCount === 0);
    }
    
    /**
     * Show/hide no results message
     */
    function showNoResultsMessage(show) {
        let noResultsMessage = document.querySelector('.no-results-message');
        
        if (show) {
            if (!noResultsMessage) {
                noResultsMessage = document.createElement('div');
                noResultsMessage.className = 'no-results-message';
                noResultsMessage.innerHTML = `
                    <h3>No exercises match your filters</h3>
                    <p>Try adjusting your search criteria or resetting the filters.</p>
                `;
                
                const exerciseCards = document.querySelector('.exercises-cards');
                if (exerciseCards) {
                    exerciseCards.after(noResultsMessage);
                }
            }
            noResultsMessage.style.display = 'block';
        } else if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
    }
    
    /**
     * Check for achievements based on user actions
     */
    function checkForAchievements(actionType) {
        // We'd normally check against server data
        // This is simplified for demo purposes
        
        if (actionType === 'save') {
            // Count saved exercises (in a real app, this would come from the server)
            const savedCount = document.querySelectorAll('.save-exercise.saved').length;
            
            if (savedCount === 5) {
                showAchievement('Collector', 'Save 5 exercises to your collection');
            }
        }
    }
    
    /**
     * Show achievement notification
     */
    function showAchievement(title, description) {
        if (!achievementTemplate) return;
        
        // Clone the template
        const notification = achievementTemplate.cloneNode(true);
        notification.id = '';
        notification.style.display = 'flex';
        
        // Set content
        notification.querySelector('#achievement-name').textContent = description;
        notification.querySelector('h3').textContent = `${title} Achievement Unlocked!`;
        
        // Add to document
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Add close handler
        notification.querySelector('.close-notification').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
        
        // Auto close after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);
    }
    
    /**
     * Show toast notification
     */
    function showToast(message) {
        // Check if toast container exists, create if not
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
                
                // Remove container if empty
                if (toastContainer.children.length === 0) {
                    toastContainer.remove();
                }
            }, 300);
        }, 3000);
    }
}); 