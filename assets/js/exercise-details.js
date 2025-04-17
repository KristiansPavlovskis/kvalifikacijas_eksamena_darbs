
document.addEventListener('DOMContentLoaded', function() {
    initializeTabNavigation();
    
    initializeVideoThumbnails();
    
    initializeSaveButton();
    
    animateDifficultyBar();
    
    initializeRatingStars();
});

function initializeTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabToShow = button.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            button.classList.add('active');
            document.getElementById(tabToShow).classList.add('active');
            
            sessionStorage.setItem('activeExerciseTab', tabToShow);
        });
    });
    
    const savedTab = sessionStorage.getItem('activeExerciseTab');
    if (savedTab) {
        const savedTabButton = document.querySelector(`.tab-button[data-tab="${savedTab}"]`);
        if (savedTabButton) {
            savedTabButton.click();
        }
    }
}

function initializeVideoThumbnails() {
    const videoThumbnails = document.querySelectorAll('.video-thumbnail');
    
    if (videoThumbnails.length === 0) return;
    
    videoThumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            videoThumbnails.forEach(thumb => thumb.classList.remove('active'));
            
            thumbnail.classList.add('active');
            
        });
    });
}

    function initializeSaveButton() {
}

function animateDifficultyBar() {
    const difficultyFill = document.querySelector('.difficulty-fill');
    
    if (difficultyFill) {
        const targetWidth = difficultyFill.style.width;
        
        difficultyFill.style.width = '0%';
        
        setTimeout(() => {
            difficultyFill.style.width = targetWidth;
        }, 100);
    }
}

function initializeRatingStars() {
    const ratingStars = document.querySelectorAll('.rating-stars .far.fa-star');
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.remove('far');
                ratingStars[i].classList.add('fas');
            }
        });
        
        star.addEventListener('mouseout', () => {
            ratingStars.forEach(s => {
                s.classList.remove('fas');
                s.classList.add('far');
            });
        });
        
        star.addEventListener('click', () => {
            const rating = index + 1;
            
            console.log(`User rated: ${rating} stars`);
            
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.remove('far');
                ratingStars[i].classList.add('fas');
            }
            
            for (let i = index + 1; i < ratingStars.length; i++) {
                ratingStars[i].classList.remove('fas');
                ratingStars[i].classList.add('far');
            }
        });
    });
}


function saveExercise(exerciseId) {
    alert("Exercise saved to your favorites!");
    
    const saveButton = document.querySelector('.action-button.secondary-button');
    if (saveButton) {
        saveButton.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
        saveButton.style.backgroundColor = '#e60000';
        saveButton.style.color = 'white';
    }

    console.log(`Saved exercise ID: ${exerciseId}`);
    
    if (saveButton) {
        saveButton.disabled = true;
    }
}