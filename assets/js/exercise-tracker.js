document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = sessionStorage.getItem('user_logged_in') || document.body.classList.contains('logged-in');
    
    const saveButtons = document.querySelectorAll('.save-exercise');
    const challengeButtons = document.querySelectorAll('.btn-challenge');
    const achievementTemplate = document.getElementById('achievement-template');
    
    initSaveButtons();
    initChallengeButtons();
    initLoadMore();
    initFilters();
    
    function initSaveButtons() {
        if (!isLoggedIn || !saveButtons.length) return;
        
        saveButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const exerciseId = this.getAttribute('data-id');
                
                if (this.classList.contains('saved')) {
                    this.classList.remove('saved');
                    this.querySelector('i').classList.remove('fas');
                    this.querySelector('i').classList.add('far');
                    
                
                    showToast('Exercise removed from your collection');
                } else {
                    this.classList.add('saved');
                    this.querySelector('i').classList.remove('far');
                    this.querySelector('i').classList.add('fas');
                    
                    showToast('Exercise saved to your collection');
                    
                    checkForAchievements('save');
                }
            });
        });
    }
    
    function initChallengeButtons() {
        if (!isLoggedIn || !challengeButtons.length) return;
        
        challengeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const challengeCard = this.closest('.challenge-card');
                
                if (challengeCard.classList.contains('accepted')) {
                    return;
                }
                
                challengeCard.classList.add('accepted');
                this.textContent = 'Challenge Accepted';
                
                
                showToast('Challenge accepted! Track your progress in your profile.');
            });
        });
    }
    
    function initLoadMore() {
        const loadMoreBtn = document.getElementById('load-more');
        if (!loadMoreBtn) return;
        
        loadMoreBtn.addEventListener('click', function() {
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            
            setTimeout(() => {
                this.classList.remove('loading');
                this.classList.add('disabled');
                this.innerHTML = 'No more exercises to load';
            }, 1500);
        });
    }
    
    function initFilters() {
        const filters = document.querySelectorAll('.filter-select');
        const resetBtn = document.getElementById('reset-filters');
        const searchInput = document.getElementById('exercise-search');
        
        if (!filters.length) return;
        
        filters.forEach(filter => {
            filter.addEventListener('change', applyFilters);
        });
        
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
        
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                filters.forEach(filter => {
                    filter.selectedIndex = 0;
                });
                
                if (searchInput) {
                    searchInput.value = '';
                }
                
                applyFilters();
            });
        }
    }
    
    function applyFilters() {
        const cards = document.querySelectorAll('.exercises-card');
        const difficultyFilter = document.getElementById('difficulty-filter');
        const equipmentFilter = document.getElementById('equipment-filter');
        const goalFilter = document.getElementById('goal-filter');
        const searchInput = document.getElementById('exercise-search');
        const resultCount = document.getElementById('result-count');
        
        if (!cards.length) return;
        
        const difficulty = difficultyFilter ? difficultyFilter.value.toLowerCase() : '';
        const equipment = equipmentFilter ? equipmentFilter.value.toLowerCase() : '';
        const goal = goalFilter ? goalFilter.value.toLowerCase() : '';
        const search = searchInput ? searchInput.value.toLowerCase() : '';
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardDifficulty = card.querySelector('.exercises-difficulty-badge').textContent.trim().toLowerCase();
            const cardEquipment = card.querySelector('.equipment-badge') ? 
                card.querySelector('.equipment-badge').textContent.trim().toLowerCase() : '';
            
            const cardGoal = card.getAttribute('data-goal') || '';
            
            const cardTitle = card.querySelector('h2').textContent.toLowerCase();
            const cardDescription = card.querySelector('p').textContent.toLowerCase();
            
            const matchesDifficulty = !difficulty || cardDifficulty.includes(difficulty);
            const matchesEquipment = !equipment || cardEquipment.includes(equipment);
            const matchesGoal = !goal || cardGoal.includes(goal);
            const matchesSearch = !search || 
                cardTitle.includes(search) || 
                cardDescription.includes(search);
            
            if (matchesDifficulty && matchesEquipment && matchesGoal && matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (resultCount) {
            resultCount.textContent = `${visibleCount} exercise${visibleCount !== 1 ? 's' : ''} found`;
        }
        
        showNoResultsMessage(visibleCount === 0);
    }
    
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
    
    function checkForAchievements(actionType) {
        
        if (actionType === 'save') {
            const savedCount = document.querySelectorAll('.save-exercise.saved').length;
            
            if (savedCount === 5) {
                showAchievement('Collector', 'Save 5 exercises to your collection');
            }
        }
    }
    
    function showAchievement(title, description) {
        if (!achievementTemplate) return;
        
        const notification = achievementTemplate.cloneNode(true);
        notification.id = '';
        notification.style.display = 'flex';
        
        notification.querySelector('#achievement-name').textContent = description;
        notification.querySelector('h3').textContent = `${title} Achievement Unlocked!`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        notification.querySelector('.close-notification').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
        
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);
    }
    
function showToast(message) {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
                
                if (toastContainer.children.length === 0) {
                    toastContainer.remove();
                }
            }, 300);
        }, 3000);
    }
}); 