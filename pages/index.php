<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Explore The Universe of Fitness";
$bodyClass = "home loading";

$additionalHead = <<<HTML
    <meta name="keywords" content="fitness, workouts, gym, training, nutrition, health">
HTML;

require_once '../includes/header.php';
?>

<main>
    <section class="hero">
        <div class="hero-particles"></div>
        <div class="hero-backdrop"></div>
        <div class="container">
            <div class="hero-content" data-aos="fade-up">
                <div class="accent-badge">Premium Fitness Experience</div>
                <h1 class="hero-title">Elevate Your <span class="gradient-text">Potential</span></h1>
                <p class="hero-subtitle">Discover a new approach to fitness that transforms your body and mindset through personalized training and community support.</p>
                <div class="hero-cta">
                    <a href="membership.php" class="btn btn-glow">
                        <span class="btn-text">Begin the Journey</span>
                        <span class="btn-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                            </svg>
                        </span>
                    </a>
                    <a href="workouts.php" class="btn btn-outline-glow">Explore Programs</a>
                </div>
                
                <div class="achievement-badges">
                    <div class="achievement">
                        <div class="achievement-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 15.8c-3.8 0-6.8-3-6.8-6.8s3-6.8 6.8-6.8 6.8 3 6.8 6.8-3 6.8-6.8 6.8zm0-12.6c-3.2 0-5.8 2.6-5.8 5.8s2.6 5.8 5.8 5.8 5.8-2.6 5.8-5.8-2.6-5.8-5.8-5.8z" fill="currentColor"/>
                                <path d="M12 8v4l3 3 1-1-2.5-2.5V8z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="achievement-info">
                            <span class="achievement-value">10K<sup>+</sup></span>
                            <span class="achievement-label">Active Members</span>
                        </div>
                    </div>
                    
                    <div class="achievement">
                        <div class="achievement-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="achievement-info">
                            <span class="achievement-value">98<sup>%</sup></span>
                            <span class="achievement-label">Success Rate</span>
                        </div>
                    </div>
                    
                    <div class="achievement">
                        <div class="achievement-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="achievement-info">
                            <span class="achievement-value">5<sup>★</sup></span>
                            <span class="achievement-label">Rating</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hero-visual" data-aos="fade-left" data-aos-delay="200">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="hero-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop" 
                         alt="Fitness athlete in training" width="600" height="400" fetchpriority="high" class="hero-image">
                    <div class="image-overlay"></div>
                    <div class="pulse-circle"></div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z" fill="currentColor"/>
                            <path d="M16 10v-5h-5v2h3v3z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="stats-content">
                        <h3>Progress Tracking</h3>
                        <div class="stats-bar">
                            <div class="stats-progress" style="width: 78%"></div>
                        </div>
                        <span class="stats-value">78% improvement</span>
                    </div>
                </div>
                <div class="feature-badge feature-badge-1">
                    <span class="pulse-dot"></span>
                    <span>Strength</span>
                </div>
                <div class="feature-badge feature-badge-2">
                    <span class="pulse-dot"></span>
                    <span>Endurance</span>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator" aria-hidden="true">
            <span>Scroll to discover</span>
            <svg viewBox="0 0 24 24" width="24" height="24">
                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z" fill="currentColor"/>
            </svg>
        </div>
    </section>
    
    <section class="feature-showcase">
        <div class="container">
            <div class="section-intro" data-aos="fade-up">
                <div class="label-badge">Exclusive Benefits</div>
                <h2>Transform Your Fitness Journey</h2>
                <p>Discover our revolutionary approach that adapts to your goals and keeps you motivated throughout your fitness evolution.</p>
            </div>
            
            <div class="feature-orbit" data-aos="zoom-in-up">
                <div class="orbit-center">
                    <div class="pulse-rings">
                        <div class="ring ring-1"></div>
                        <div class="ring ring-2"></div>
                        <div class="ring ring-3"></div>
                    </div>
                    <div class="center-content">
                        <span class="center-icon">
                        <svg viewBox="0 0 24 24" width="36" height="36">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                                <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="center-label">GYMVERSE</span>
                    </div>
                </div>
                
                <a href="workouts.php" class="orbit-feature orbit-feature-1" data-aos="fade-up" data-aos-delay="100">
                    <div class="orbit-feature-icon">
                        <svg viewBox="0 0 24 24" width="30" height="30">
                            <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14 4.14 5.57 2 7.71 3.43 9.14 2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22 14.86 20.57 16.29 22 18.43 19.86 19.86 18.43 18.43 16.29z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="orbit-feature-content">
                        <h3>Smart Workouts</h3>
                        <p>AI-driven routines adapt to your progress for optimal results</p>
                        <span class="orbit-link">Explore <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/></svg></span>
                    </div>
                </a>
                
                <a href="nutrition.php" class="orbit-feature orbit-feature-2" data-aos="fade-up" data-aos-delay="200">
                    <div class="orbit-feature-icon">
                        <svg viewBox="0 0 24 24" width="30" height="30">
                            <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="orbit-feature-content">
                        <h3>Nutrition Science</h3>
                        <p>Personalized meal plans that complement your training goals</p>
                        <span class="orbit-link">Explore <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/></svg></span>
                    </div>
                </a>
                
                <a href="leaderboard.php" class="orbit-feature orbit-feature-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="orbit-feature-icon">
                        <svg viewBox="0 0 24 24" width="30" height="30">
                            <path d="M7.5 21H2v-3h5.5v3zM14.75 21h-5.5v-3h5.5v3zM22 21h-5.5v-3H22v3zM20 3h-4l-2-2h-4L8 3H4v3h16V3zM5 8h14l-1.4 7h-11.2L5 8z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="orbit-feature-content">
                        <h3>Community Synergy</h3>
                        <p>Connect, compete and celebrate achievements with fellow members</p>
                        <span class="orbit-link">Explore <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/></svg></span>
                    </div>
                </a>
                
                <a href="membership.php" class="orbit-feature orbit-feature-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="orbit-feature-icon">
                        <svg viewBox="0 0 24 24" width="30" height="30">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="orbit-feature-content">
                        <h3>Elite Access</h3>
                        <p>Unlock premium features and 1-on-1 coaching with trainers</p>
                        <span class="orbit-link">Explore <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/></svg></span>
                    </div>
                </a>
            </div>
            
            <div class="feature-spotlight" data-aos="fade-up">
                <div class="spotlight-text">
                    <span class="spotlight-number">98%</span>
                    <p>of our members report significant results within the first 30 days</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="workout-showcase">
        <div class="workout-backdrop">
            <div class="backdrop-shape shape-left"></div>
            <div class="backdrop-shape shape-right"></div>
        </div>
        
        <div class="container">
            <div class="section-intro text-center" data-aos="fade-up">
                <div class="label-badge">Expert Programs</div>
                <h2>Discover Your Optimal Training</h2>
                <p>Scientifically designed workouts for every level and goal, constantly updated by fitness professionals.</p>
            </div>
            
            <div class="workout-slider-wrapper" data-aos="fade-up">
                <div class="workout-slider">
                    <div class="workout-slide">
                        <div class="workout-card">
                            <div class="workout-card-media">
                                <img src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?q=80&w=1470&auto=format&fit=crop" 
                                     alt="Strength Training" loading="lazy">
                                <div class="card-media-overlay"></div>
                                <div class="card-media-tag">
                                    <span class="tag-icon">★</span>
                                    <span>Most Popular</span>
                                </div>
                                <div class="difficulty">
                                    <span class="difficulty-level">Intermediate</span>
                                    <div class="difficulty-indicator">
                                        <span class="difficulty-dot active"></span>
                                        <span class="difficulty-dot active"></span>
                                        <span class="difficulty-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="workout-card-body">
                                <div class="workout-meta">
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" fill="currentColor"/>
                                        </svg>
                                        <span>45-60 min</span>
                                    </div>
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z" fill="currentColor"/>
                                        </svg>
                                        <span>3x Weekly</span>
                                    </div>
                                </div>
                                <h3>Strength Mastery</h3>
                                <p>Build functional muscle, boost metabolism, and increase power with compound movements.</p>
                                <div class="workout-features">
                                    <span class="workout-feature">Muscle Gain</span>
                                    <span class="workout-feature">Power</span>
                                    <span class="workout-feature">Stability</span>
                                </div>
                                <a href="exercise-type.php?type=strength" class="btn btn-sm">View Program</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="workout-slide">
                        <div class="workout-card">
                            <div class="workout-card-media">
                                <img src="https://images.unsplash.com/photo-1599058917212-d750089bc07e?q=80&w=1469&auto=format&fit=crop" 
                                     alt="HIIT Workout" loading="lazy">
                                <div class="card-media-overlay"></div>
                                <div class="card-media-tag">
                                    <span class="tag-icon">🔥</span>
                                    <span>Trending Now</span>
                                </div>
                                <div class="difficulty">
                                    <span class="difficulty-level">Advanced</span>
                                    <div class="difficulty-indicator">
                                        <span class="difficulty-dot active"></span>
                                        <span class="difficulty-dot active"></span>
                                        <span class="difficulty-dot active"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="workout-card-body">
                                <div class="workout-meta">
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" fill="currentColor"/>
                                        </svg>
                                        <span>20-30 min</span>
                                    </div>
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z" fill="currentColor"/>
                                        </svg>
                                        <span>4x Weekly</span>
                                    </div>
                                </div>
                                <h3>HIIT Revolution</h3>
                                <p>Maximize calorie burn and cardiovascular health with high-intensity interval training.</p>
                                <div class="workout-features">
                                    <span class="workout-feature">Fat Loss</span>
                                    <span class="workout-feature">Cardio</span>
                                    <span class="workout-feature">Intensity</span>
                                </div>
                                <a href="exercise-type.php?type=hiit" class="btn btn-sm">View Program</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="workout-slide">
                        <div class="workout-card">
                            <div class="workout-card-media">
                                <img src="https://images.unsplash.com/photo-1434596922112-19c563067271?q=80&w=1470&auto=format&fit=crop" 
                                     alt="Fat Burn Workout" loading="lazy">
                                <div class="card-media-overlay"></div>
                                <div class="card-media-tag">
                                    <span class="tag-icon">⚡</span>
                                    <span>Fast Results</span>
                                </div>
                                <div class="difficulty">
                                    <span class="difficulty-level">Beginner</span>
                                    <div class="difficulty-indicator">
                                        <span class="difficulty-dot active"></span>
                                        <span class="difficulty-dot"></span>
                                        <span class="difficulty-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="workout-card-body">
                                <div class="workout-meta">
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" fill="currentColor"/>
                                        </svg>
                                        <span>30-45 min</span>
                                    </div>
                                    <div class="meta-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z" fill="currentColor"/>
                                        </svg>
                                        <span>3x Weekly</span>
                                    </div>
                                </div>
                                <h3>Metabolic Sculpt</h3>
                                <p>Targeted fat-burning workouts combined with resistance training for total body transformation.</p>
                                <div class="workout-features">
                                    <span class="workout-feature">Weight Loss</span>
                                    <span class="workout-feature">Toning</span>
                                    <span class="workout-feature">Endurance</span>
                                </div>
                                <a href="exercise-type.php?type=fatburn" class="btn btn-sm">View Program</a>
                            </div>
                        </div>
                    </div>
                    </div>
                
                <div class="slider-controls">
                    <button type="button" class="slider-arrow slider-prev" aria-label="Previous slide">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="currentColor"/>
                        </svg>
                    </button>
                    <div class="slider-dots">
                        <button type="button" class="slider-dot active" aria-label="Go to slide 1"></button>
                        <button type="button" class="slider-dot" aria-label="Go to slide 2"></button>
                        <button type="button" class="slider-dot" aria-label="Go to slide 3"></button>
                    </div>
                    <button type="button" class="slider-arrow slider-next" aria-label="Next slide">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" fill="currentColor"/>
                        </svg>
                    </button>
                    </div>
            </div>
            
            <div class="workout-explore" data-aos="fade-up">
                <a href="workouts.php" class="btn btn-primary-gradient">
                    <span>Discover All Programs</span>
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section class="app-showcase">
        <div class="app-backdrop"></div>
        <div class="container">
            <div class="app-showcase-wrapper" data-aos="fade-up">
                <div class="app-content">
                    <div class="app-badge">Mobile Experience</div>
                    <h2>GYMVERSE in Your Pocket</h2>
                    <p>Take your fitness journey anywhere with our premium mobile experience, featuring offline workouts, real-time tracking, and personalized coaching.</p>
                    
                    <div class="app-features-list">
                        <div class="app-feature-item">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                                    <path d="M17 12h-4V7h-2v7h6v-2z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>Workout Timer</h4>
                                <p>Track your sets, reps and rest periods efficiently</p>
                            </div>
                        </div>
                        
                        <div class="app-feature-item">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M12 14.5v.5h1.5v-1.5H12V14z" fill="currentColor"/>
                                    <path d="M9 10h1.5v3H9z" fill="currentColor"/>
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 15.5h-4.5v-6H9v.5h1.5v-1H9V8H5v10h7v.5zm7-5H14v.5h1.5v-1H14V10h-1.5v7H19v-3.5z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>Progress Analytics</h4>
                                <p>Visual dashboards to track your improvements</p>
                            </div>
                        </div>
                        
                        <div class="app-feature-item">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M17.21 9L12.83 2.44c-.19-.29-.58-.27-.78.01L6.79 9H2c-.55 0-1 .45-1 1 0 .09.01.18.04.27l2.54 9.27c.23.84 1 1.46 1.92 1.46h13c.92 0 1.69-.62 1.93-1.46l2.54-9.27L23 10c0-.55-.45-1-1-1h-4.79zM9 9l3-4.4L15 9H9zm3 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>Nutrition Scanner</h4>
                                <p>Scan foods to log accurate nutritional data</p>
                            </div>
                        </div>
                        
                        <div class="app-feature-item">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M18 11v2h4v-2h-4zM16 17.61c-.96.71-2.21 1.65-3.33 2.39-.4.26-.87.26-1.27 0-1.12-.74-2.37-1.68-3.33-2.39C7.37 17.13 6 15.74 6 14.22V6.88C6 6.37 6.42 6 7 6h1c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2h1c.58 0 1 .37 1 .88v7.34c0 1.52-1.37 2.91-2 3.39zM12 4c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm6 6h-1v-.8c0-.99-.59-1.2-1-1.2h-2.6v6.53c0 .38.31.47.59.28l.01-.01c.43-.29 1.12-.82 1.66-1.24.13-.09.28-.15.43-.15s.3.06.43.15c.54.43 1.24.95 1.66 1.24.29.19.59.09.59-.28V10z" fill="currentColor"/>
                                    <path d="M2 15v2h4v-2H2z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>Workout Library</h4>
                                <p>Access hundreds of exercises with video guides</p>
                            </div>
                        </div>
                        
                        <div class="app-feature-item">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>Smart Reminders</h4>
                                <p>AI-powered notifications to keep you on track</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="app-download-buttons">
                        <a href="#" class="app-download-button app-store">
                            <span class="download-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M17.05 11.27C17.01 8.27 19.5 6.88 19.6 6.8c-1.46-2.14-3.73-2.4-4.54-2.44-1.9-.2-3.74 1.14-4.7 1.14-.98 0-2.46-1.13-4.08-1.1-2.06.04-4 1.23-5.06 3.05-2.18 3.8-.56 9.37 1.53 12.45 1.04 1.47 2.27 3.1 3.87 3.05 1.57-.07 2.15-.98 4.04-.98 1.88 0 2.42.98 4.04.94 1.67-.03 2.73-1.47 3.73-2.96 1.2-1.7 1.68-3.36 1.7-3.46-.04-.01-3.23-1.25-3.26-4.97zm-3.07-9.13c.85-1.02 1.42-2.42 1.26-3.85-1.22.05-2.73.84-3.6 1.87-.77.91-1.45 2.4-1.28 3.8 1.37.1 2.77-.7 3.62-1.82z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="download-content">
                                <span class="small-text">Download on the</span>
                                <span class="large-text">App Store</span>
                            </span>
                        </a>
                        
                        <a href="#" class="app-download-button google-play">
                            <span class="download-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22">
                                    <path d="M4.97 3.16c-.1.03-.17.08-.22.15L3.09 7.96l1.66 1.66c.39-.39.97-.38 1.36 0 .39.39.39 1.03 0 1.42-.39.39-1.03.39-1.42 0L3.04 9.36l1.66 3.26 1.52.81.28-.49c.4-.7 1.1-.87 1.62-.47.54.41.6 1.39.05 1.88l-.26.29 1.52.8 6.49-11.62c-.47-.24-1.83-.99-3.48-1.84-2.15-1.1-4.68-2.35-5.47-2.72zm10.94 6.97c-.38-.38-.39-.96 0-1.35.4-.4 1.05-.4 1.43 0 .39.39.39 1.02 0 1.41-.39.39-1.04.39-1.43-.06zm-4.94-4.31c-.36-.22-.6-.58-.59-1 0-.41.35-.77.95-.77.38 0 .7.16.93.4.23.25.29.56.29.86-.05.31-.25.57-.53.73-.3.15-.66.19-.96-.01-.02-.01-.04-.03-.09-.21z" fill="currentColor"/>
                                    <path d="M17.03 12.98c.39.39.39 1.02 0 1.41-.39.39-1.04.39-1.43 0-.38-.38-.38-.96 0-1.35.4-.39 1.05-.39 1.43-.06z" fill="currentColor"/>
                                    <path d="M19.98 3.99c-.04.15-.06.26-.09.33l-6.83 12.19 1.41.75 6.86-12.21.05-.13c-.74-.28-1.4-.46-1.4-.93zm-2.96-1.2c-.57.02-2.07.84-3.82 1.72-1.76.88-3.75 1.89-4.34 2.17l6.49 11.61 1.39-.73-4.5-8.35 4.82-4.31c-.02-.01-.04-.02-.04-.11zm-9.94 15.35c-.29.29-.68.44-1.07.44s-.77-.15-1.07-.44L3 16.23l1.64-3.2-.48-.43c-.34-.3-.48-.8-.34-1.32.15-.53.59-.82 1.06-.82.32 0 .61.11.82.31l.5.43 1.62-3.19-1.06-.56c-.35-.2-.53-.53-.53-.85 0-.31.15-.62.51-.84.54-.31 1.16-.14 1.41.09.11.09.2.19.28.29l11.17 20.07-9.94-7.13z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="download-content">
                                <span class="small-text">GET IT ON</span>
                                <span class="large-text">Google Play</span>
                            </span>
                        </a>
                    </div>
                </div>
                
                <div class="app-visual" data-aos="fade-left">
                    <div class="phone-wrapper">
                        <div class="phone-frame">
                            <div class="phone-screen">
                                <img src="https://img.freepik.com/free-psd/premium-mobile-phone-screen-mockup-template_53876-65749.jpg" alt="GYMVERSE mobile app on smartphone" loading="lazy">
                            </div>
                            <div class="phone-notch"></div>
                            <div class="phone-buttons"></div>
                        </div>
                        <div class="phone-reflection"></div>
                    </div>
                    
                    <div class="floating-element floating-stat stat-1">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2zm-1 8V6h2v4h3l-4 4-4-4h3zm6 7H7v-2h10v2z" fill="currentColor"/>
                        </svg>
                        <span>2M+ Downloads</span>
                    </div>
                    
                    <div class="floating-element floating-stat stat-2">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="currentColor"/>
                        </svg>
                        <span>4.9 Rating</span>
                    </div>
                    
                    <div class="app-feature-aura"></div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="testimonials-showcase">
        <div class="testimonial-pattern"></div>
        <div class="container">
            <div class="section-intro text-center" data-aos="fade-up">
                <div class="label-badge label-badge-light">Success Stories</div>
                <h2>Real Results from GYMVERSE Members</h2>
                <p>Join thousands who have transformed their lives through our science-backed approach to fitness.</p>
            </div>
            
            <div class="testimonial-cards" data-aos="fade-up">
                <div class="testimonial-card">
                    <div class="testimonial-card-header">
                        <div class="testimonial-avatar">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sarah J." loading="lazy">
                            <div class="avatar-status verified-badge" title="Verified Member">
                                <svg viewBox="0 0 24 24" width="14" height="14">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                                </svg>
                            </div>
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Sarah Johnson</h4>
                            <div class="author-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                                    </svg>
                                    Member for 2 years
                                </span>
                                <span class="meta-item highlight">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/>
                                    </svg>
                                    Lost 25kg
                                </span>
                            </div>
                        </div>
                        <div class="testimonial-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        <blockquote>
                            <p>"After trying countless fitness programs, GYMVERSE was the only one that truly clicked for me. The personalized approach combined with the supportive community made all the difference. I've not only lost weight but gained confidence and energy I never thought possible."</p>
                        </blockquote>
                    </div>
                    <div class="testimonial-progress">
                        <div class="progress-wrapper">
                            <div class="progress-label">Progress</div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width:85%"></div>
                            </div>
                            <div class="progress-value">85%</div>
                        </div>
                    </div>
                    <div class="testimonial-badge">Weight Loss</div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-card-header">
                        <div class="testimonial-avatar">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Michael T." loading="lazy">
                            <div class="avatar-status verified-badge" title="Verified Member">
                                <svg viewBox="0 0 24 24" width="14" height="14">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                                </svg>
                            </div>
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Michael Thomas</h4>
                            <div class="author-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                                    </svg>
                                    Member for 1 year
                                </span>
                                <span class="meta-item highlight">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14 4.14 5.57 2 7.71 3.43 9.14 2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22 14.86 20.57 16.29 22 18.43 19.86 19.86 18.43 18.43 16.29z" fill="currentColor"/>
                                    </svg>
                                    Gained 12kg muscle
                                </span>
                            </div>
                        </div>
                        <div class="testimonial-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        <blockquote>
                            <p>"The nutrition guidance completely transformed my relationship with food. I'm eating better, feeling stronger, and have significantly more energy throughout the day. My workouts have improved dramatically and I've gained muscle mass I never thought possible for my body type."</p>
                        </blockquote>
                    </div>
                    <div class="testimonial-progress">
                        <div class="progress-wrapper">
                            <div class="progress-label">Progress</div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width:92%"></div>
                            </div>
                            <div class="progress-value">92%</div>
                        </div>
                    </div>
                    <div class="testimonial-badge">Muscle Growth</div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-card-header">
                        <div class="testimonial-avatar">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Jennifer K." loading="lazy">
                            <div class="avatar-status verified-badge" title="Verified Member">
                                <svg viewBox="0 0 24 24" width="14" height="14">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                                </svg>
                            </div>
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Jennifer Khan</h4>
                            <div class="author-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                                    </svg>
                                    Member for 2 years
                                </span>
                                <span class="meta-item highlight">
                                    <svg viewBox="0 0 24 24" width="12" height="12">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                                    </svg>
                                    Elite Member
                                </span>
                            </div>
                        </div>
                        <div class="testimonial-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        <blockquote>
                            <p>"As a busy professional, I needed an efficient and effective fitness solution. GYMVERSE delivers exactly that with workouts I can do anywhere and meal plans that fit my hectic schedule. The app's reminders and progress tracking help me stay accountable even on my most challenging days."</p>
                        </blockquote>
                    </div>
                    <div class="testimonial-progress">
                        <div class="progress-wrapper">
                            <div class="progress-label">Progress</div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width:88%"></div>
                            </div>
                            <div class="progress-value">88%</div>
                        </div>
                    </div>
                    <div class="testimonial-badge">Fitness Lifestyle</div>
                </div>
            </div>
            
            <div class="join-cta" data-aos="fade-up">
                <div class="cta-content">
                    <h3>Ready to Write Your Success Story?</h3>
                    <p>Join thousands of members who have already transformed their lives with GYMVERSE.</p>
                    <a href="register.php" class="btn btn-cta">
                        <span>Start Your Journey Today</span>
                        <span class="btn-glow-effect"></span>
                    </a>
                </div>
                <div class="cta-decoration">
                    <div class="decoration-element element-1"></div>
                    <div class="decoration-element element-2"></div>
                    <div class="decoration-element element-3"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?> 