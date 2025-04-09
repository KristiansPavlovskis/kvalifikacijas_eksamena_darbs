document.addEventListener('DOMContentLoaded', function() {
    initExerciseSearch();
});

function initExerciseSearch() {
    const searchInput = document.getElementById('exercise-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.exercises-card');
        
        cards.forEach(card => {
            const title = card.querySelector('h2').innerText.toLowerCase();
            const description = card.querySelector('p').innerText.toLowerCase();
            
            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
} 