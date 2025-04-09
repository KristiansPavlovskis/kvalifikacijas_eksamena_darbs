/**
 * Exercise Details Page JavaScript
 * Handles interactive functionality for the exercise details page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation
    initializeTabNavigation();
    
    // Video Thumbnails
    initializeVideoThumbnails();
    
    // Save Exercise Button
    initializeSaveButton();
    
    // Difficulty Bar Animation
    animateDifficultyBar();
    
    // Initialize Rating Stars
    initializeRatingStars();
});

/**
 * Initialize tab navigation functionality
 */
function initializeTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Get the tab to show
            const tabToShow = button.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            document.getElementById(tabToShow).classList.add('active');
            
            // Save the active tab to session storage
            sessionStorage.setItem('activeExerciseTab', tabToShow);
        });
    });
    
    // Load the saved tab or default to instructions
    const savedTab = sessionStorage.getItem('activeExerciseTab');
    if (savedTab) {
        const savedTabButton = document.querySelector(`.tab-button[data-tab="${savedTab}"]`);
        if (savedTabButton) {
            savedTabButton.click();
        }
    }
}

/**
 * Initialize video thumbnails for switching videos
 */
function initializeVideoThumbnails() {
    const videoThumbnails = document.querySelectorAll('.video-thumbnail');
    
    if (videoThumbnails.length === 0) return;
    
    videoThumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            // Remove active class from all thumbnails
            videoThumbnails.forEach(thumb => thumb.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            thumbnail.classList.add('active');
            
            // In a real application, would change the video source here
            // For this demo, we're just changing the active state
            
            // Example of changing a video source:
            // const videoIframe = document.querySelector('.video-wrapper iframe');
            // const newSrc = thumbnail.getAttribute('data-video-src');
            // if (videoIframe && newSrc) {
            //     videoIframe.src = newSrc;
            // }
        });
    });
}

/**
 * Initialize save button functionality
 */
function initializeSaveButton() {
    // This is handled in the main PHP file via the saveExercise function
    // This function can be used for additional save button functionality
}

/**
 * Animate the difficulty bar on page load
 */
function animateDifficultyBar() {
    const difficultyFill = document.querySelector('.difficulty-fill');
    
    if (difficultyFill) {
        // Get the width from inline style
        const targetWidth = difficultyFill.style.width;
        
        // Start at 0 and animate to target width
        difficultyFill.style.width = '0%';
        
        // Use setTimeout to allow the DOM to update before animation
        setTimeout(() => {
            difficultyFill.style.width = targetWidth;
        }, 100);
    }
}

/**
 * Initialize rating stars for user reviews
 */
function initializeRatingStars() {
    const ratingStars = document.querySelectorAll('.rating-stars .far.fa-star');
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            // Highlight stars up to the hovered one
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.remove('far');
                ratingStars[i].classList.add('fas');
            }
        });
        
        star.addEventListener('mouseout', () => {
            // Reset stars to original state
            ratingStars.forEach(s => {
                s.classList.remove('fas');
                s.classList.add('far');
            });
        });
        
        star.addEventListener('click', () => {
            // Set rating
            const rating = index + 1;
            
            // In a real application, would send this rating to the server
            console.log(`User rated: ${rating} stars`);
            
            // Keep the stars filled after click
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.remove('far');
                ratingStars[i].classList.add('fas');
            }
            
            // Reset stars after index
            for (let i = index + 1; i < ratingStars.length; i++) {
                ratingStars[i].classList.remove('fas');
                ratingStars[i].classList.add('far');
            }
        });
    });
}

/**
 * Save an exercise to user favorites
 * This function is called from the HTML
 */
function saveExercise(exerciseId) {
    // Show an alert
    alert("Exercise saved to your favorites!");
    
    // Update the button text and style
    const saveButton = document.querySelector('.action-button.secondary-button');
    if (saveButton) {
        saveButton.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
        saveButton.style.backgroundColor = '#e60000';
        saveButton.style.color = 'white';
    }
    
    // In a real application, would send the save request to the server
    console.log(`Saved exercise ID: ${exerciseId}`);
    
    // Disable the button to prevent multiple saves
    if (saveButton) {
        saveButton.disabled = true;
    }
}