<?php
session_start();

$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$username = $logged_in ? $_SESSION["username"] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Learning Hub | Master Your Training</title>
    <link rel="stylesheet" href="../assets/css/learning_hub.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <?php include '../includes/header.php'; ?>
 
    <section class="dynamic-split-section"> 
        <a href="excercises.php">
            <div class="split-side exercise-side">
                <div class="split-overlay"></div>
                <div class="split-content">
                    <div class="split-header">
                        <div class="split-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <h2>Exercise Mastery</h2>
                    </div>
                    
                    <div class="split-elements">
                        <div class="split-quote">
                            <i class="fas fa-quote-left"></i>
                            <p>"The last three or four reps is what makes the muscle grow. This area of pain divides a champion from someone who is not a champion."</p>
                            <span class="quote-author">— Arnold Schwarzenegger</span>
                        </div>
                        
                        <div class="split-stats">
                            <div class="stat-circle">
                                <span class="stat-number">250+</span>
                                <span class="stat-label">EXERCISE GUIDES</span>
                            </div>
                            <div class="stat-circle">
                                <span class="stat-number">98%</span>
                                <span class="stat-label">SUCCESS RATE</span>
                            </div>
                            <div class="stat-circle">
                                <span class="stat-number">24/7</span>
                                <span class="stat-label">SUPPORT</span>
                            </div>
                        </div>
                        
                        <div class="split-features">
                            <div class="feature-item">
                                <i class="fas fa-video"></i>
                                <span>HD Video Guides</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Form Coaching</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-level-up-alt"></i>
                                <span>Progressive Plans</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-heartbeat"></i>
                                <span>Injury Prevention</span>
                            </div>
                        </div>
                        <div class="split-action">
                            <span class="split-button">Explore Exercises</span>
                            <span class="action-detail">Access our complete library of exercise tutorials</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="equipment.php">
            <div class="split-side equipment-side">
                <div class="split-overlay"></div>
                <div class="split-content">
                    <div class="split-header">
                        <div class="split-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h2>Equipment Arsenal</h2>
                    </div>
                    
                    <div class="split-elements">
                        <div class="split-quote">
                            <i class="fas fa-quote-left"></i>
                            <p>"It's not about having the right equipment, it's about having the knowledge to use what you have effectively."</p>
                            <span class="quote-author">— Mark Rippetoe</span>
                        </div>
                        
                        <div class="split-stats">
                            <div class="stat-circle">
                                <span class="stat-number">120+</span>
                                <span class="stat-label">EQUIPMENT GUIDES</span>
                            </div>
                            <div class="stat-circle">
                                <span class="stat-number">5K+</span>
                                <span class="stat-label">ACTIVE USERS</span>
                            </div>
                            <div class="stat-circle">
                                <span class="stat-number">3x</span>
                                <span class="stat-label">EFFICIENCY</span>
                            </div>
                        </div>
                        
                        <div class="split-features">
                            <div class="feature-item">
                                <i class="fas fa-tools"></i>
                                <span>Setup Guides</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-shield-alt"></i>
                                <span>Safety Tips</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-sync-alt"></i>
                                <span>Maintenance</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-cart-plus"></i>
                                <span>Buying Guides</span>
                            </div>
                        </div>
                        
                        <div class="split-action">
                            <span class="split-button">Discover Equipment</span>
                            <span class="action-detail">Learn how to maximize your fitness tools</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </section>
    <section class="training-methodology-section">
        <h2 class="section-title">Training Methodology</h2>
        
        <div class="methodology-container">
            <div class="methodology-wheel">
                <div class="wheel-center">
                    <i class="fas fa-brain center-icon"></i>
                    <span class="center-text">CORE PRINCIPLES</span>
                </div>
                
                <div class="wheel-segment segment-1" data-segment="1">
                    <div class="segment-content">
                        <i class="fas fa-check-double segment-icon"></i>
                        <h3>Proper Form</h3>
                        <p>Master the mechanics for maximum results and safety</p>
                    </div>
                </div>
                
                <div class="wheel-segment segment-2" data-segment="2">
                    <div class="segment-content">
                        <i class="fas fa-dumbbell segment-icon"></i>
                        <h3>Equipment Selection</h3>
                        <p>Choose the right tools for your specific goals</p>
                    </div>
                </div>
                
                <div class="wheel-segment segment-3" data-segment="3">
                    <div class="segment-content">
                        <i class="fas fa-chart-line segment-icon"></i>
                        <h3>Progressive Overload</h3>
                        <p>Systematic advancement for continuous improvement</p>
                    </div>
                </div>
                
                <div class="wheel-segment segment-4" data-segment="4">
                    <div class="segment-content">
                        <i class="fas fa-project-diagram segment-icon"></i>
                        <h3>Workout Structure</h3>
                        <p>Optimize exercise selection and sequencing</p>
                    </div>
                </div>
            </div>
            
            <div class="methodology-detail">
                <div class="detail-content active" id="methodology-1">
                    <div class="detail-header">
                        <i class="fas fa-check-double"></i>
                        <h3>Proper Form</h3>
                    </div>
                    
                    <p>Perfect form is the foundation of effective training. Learn the precise mechanics of each movement to maximize muscle engagement while minimizing injury risk. Our experts break down complex movements into simple, actionable steps.</p>
                    
                    <ul class="detail-benefits">
                        <li><i class="fas fa-check"></i> Dramatically reduces injury risk during training</li>
                        <li><i class="fas fa-check"></i> Increases muscle activation by up to 35%</li>
                        <li><i class="fas fa-check"></i> Improves mind-muscle connection for better results</li>
                        <li><i class="fas fa-check"></i> Enables consistent progression across all exercises</li>
                    </ul>
                    
                    <a href="form-guides.php" class="detail-link">
                        Access form mastery series <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="detail-content" id="methodology-2">
                    <div class="detail-header">
                        <i class="fas fa-dumbbell"></i>
                        <h3>Equipment Selection</h3>
                    </div>
                    
                    <p>Choosing the right equipment transforms your workout efficiency. Our detailed guides help you select tools that match your goals, space constraints, and experience level. Discover how to build an effective arsenal whether at home or in a commercial gym.</p>
                    
                    <ul class="detail-benefits">
                        <li><i class="fas fa-check"></i> Optimize results with goal-specific equipment choices</li>
                        <li><i class="fas fa-check"></i> Save money by investing only in what you'll actually use</li>
                        <li><i class="fas fa-check"></i> Maximize limited space with versatile equipment options</li>
                        <li><i class="fas fa-check"></i> Scale your workout intensity appropriately to your level</li>
                    </ul>
                    
                    <a href="equipment-guides.php" class="detail-link">
                        View equipment selection guides <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="detail-content" id="methodology-3">
                    <div class="detail-header">
                        <i class="fas fa-chart-line"></i>
                        <h3>Progressive Overload</h3>
                    </div>
                    
                    <p>Progressive overload is the scientific principle behind continuous improvement. Learn systematic methods to incrementally challenge your body, triggering adaptation and growth. Our progression frameworks apply to strength, endurance, and hypertrophy goals.</p>
                    
                    <ul class="detail-benefits">
                        <li><i class="fas fa-check"></i> Break through plateaus with strategic loading protocols</li>
                        <li><i class="fas fa-check"></i> Track progress accurately with our specialized tools</li>
                        <li><i class="fas fa-check"></i> Customize progression rates to your recovery capacity</li>
                        <li><i class="fas fa-check"></i> Apply scientific principles to maintain long-term growth</li>
                    </ul>
                    
                    <a href="progression-systems.php" class="detail-link">
                        Master progressive overload <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="detail-content" id="methodology-4">
                    <div class="detail-header">
                        <i class="fas fa-project-diagram"></i>
                        <h3>Workout Structure</h3>
                    </div>
                    
                    <p>Optimal workout design balances exercise selection, sequencing, volume, and frequency. Learn how to structure sessions that maximize efficiency and results while fitting your lifestyle. Our frameworks adapt to different goals and time constraints.</p>
                    
                    <ul class="detail-benefits">
                        <li><i class="fas fa-check"></i> Maximize training efficiency with strategic exercise sequencing</li>
                        <li><i class="fas fa-check"></i> Balance volume and intensity for optimal recovery</li>
                        <li><i class="fas fa-check"></i> Design complementary workouts across your training week</li>
                        <li><i class="fas fa-check"></i> Adapt workout structures as you progress in experience</li>
                    </ul>
                    
                    <a href="workout-design.php" class="detail-link">
                        Explore workout structure guides <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="journey-section">
        <h2 class="section-title">Your Fitness Journey</h2>
        
        <div class="journey-container">
            <div class="journey-timeline">
                <div class="journey-step">
                    <span class="step-level">Level 1</span>
                    <h3>Fundamentals</h3>
                    <p>Master proper form and basic movement patterns. Build a foundation for safe, effective training.</p>
                </div>
                
                <div class="journey-step">
                    <span class="step-level">Level 2</span>
                    <h3>Progression</h3>
                    <p>Implement systematic overload principles. Begin specialized training based on your goals.</p>
                </div>
                
                <div class="journey-step">
                    <span class="step-level">Level 3</span>
                    <h3>Advanced Techniques</h3>
                    <p>Incorporate specialized methodologies. Optimize programming for your specific physiology.</p>
                </div>
                
                <div class="journey-step">
                    <span class="step-level">Level 4</span>
                    <h3>Mastery</h3>
                    <p>Fine-tune all aspects of training. Achieve peak performance through precise programming.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="quickstart-section">
        <h2 class="section-title">Quick Start Guides</h2>
        
        <div class="quickstart-container">
            <div class="quickstart-grid">
                <div class="quickstart-card">
                    <div class="quickstart-header">
                        <i class="fas fa-running quickstart-icon"></i>
                        <h3>Beginner's Pathway</h3>
                    </div>
                    <div class="quickstart-content">
                        <ol class="quickstart-list">
                            <li>Learn the core movement patterns</li>
                            <li>Master proper breathing techniques</li>
                            <li>Establish consistent training schedule</li>
                            <li>Begin with bodyweight fundamentals</li>
                            <li>Focus on form before intensity</li>
                        </ol>
                    </div>
                    <a href="beginner-guide.php" class="quickstart-cta">Start Here</a>
                </div>
                
                <div class="quickstart-card">
                    <div class="quickstart-header">
                        <i class="fas fa-dumbbell quickstart-icon"></i>
                        <h3>Strength Focus</h3>
                    </div>
                    <div class="quickstart-content">
                        <ol class="quickstart-list">
                            <li>Implement compound lift progressions</li>
                            <li>Optimize rest periods for power output</li>
                            <li>Structure periodized strength cycles</li>
                            <li>Incorporate appropriate assistance work</li>
                            <li>Balance volume and intensity correctly</li>
                        </ol>
                    </div>
                    <a href="strength-guide.php" class="quickstart-cta">Build Strength</a>
                </div>
                
                <div class="quickstart-card">
                    <div class="quickstart-header">
                        <i class="fas fa-heartbeat quickstart-icon"></i>
                        <h3>Endurance Development</h3>
                    </div>
                    <div class="quickstart-content">
                        <ol class="quickstart-list">
                            <li>Establish aerobic base training protocols</li>
                            <li>Implement strategic interval training</li>
                            <li>Balance cardio modalities effectively</li>
                            <li>Progress duration and intensity safely</li>
                            <li>Incorporate active recovery methods</li>
                        </ol>
                    </div>
                    <a href="endurance-guide.php" class="quickstart-cta">Build Endurance</a>
                </div>
            </div>
        </div>
    </section>

    <section class="resources-section">
        <h2 class="section-title">Learning Resources</h2>
        
        <div class="resources-container">
            <div class="resources-grid">
                <div class="resource-card">
                    <img src="assets/images/resource1.jpg" alt="Video Tutorials" class="resource-img">
                    <div class="resource-content">
                        <h3>Video Tutorial Library</h3>
                        <p>Comprehensive video guides covering exercises, equipment setup, and training techniques with expert coaching.</p>
                        <a href="video-library.php" class="resource-link">Access videos <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="resource-card">
                    <img src="assets/images/resource2.jpg" alt="Exercise Database" class="resource-img">
                    <div class="resource-content">
                        <h3>Exercise Database</h3>
                        <p>Searchable collection of exercises with detailed instructions, muscle activation guides, and progression options.</p>
                        <a href="exercise-database.php" class="resource-link">Browse exercises <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="resource-card">
                    <img src="assets/images/resource3.jpg" alt="Equipment Guides" class="resource-img">
                    <div class="resource-content">
                        <h3>Equipment Guides</h3>
                        <p>In-depth reviews and instructional guides for gym equipment, covering setup, usage, and maintenance.</p>
                        <a href="equipment-guides.php" class="resource-link">View guides <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="resource-card">
                    <img src="assets/images/resource4.jpg" alt="Training Programs" class="resource-img">
                    <div class="resource-content">
                        <h3>Training Programs</h3>
                        <p>Structured workout programs for various goals, experience levels, and available equipment setups.</p>
                        <a href="programs.php" class="resource-link">Discover programs <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="community-section">
        <h2 class="section-title">Community Showcase</h2>
        
        <div class="community-container">
            <div class="showcase-grid">
                <div class="showcase-card">
                    <img src="assets/images/community1.jpg" alt="Success Story" class="showcase-img">
                    <div class="showcase-content">
                        <h3>Sarah's Transformation</h3>
                        <p>From beginner to competitive powerlifter in 18 months using our progressive strength methodology.</p>
                        <div class="showcase-stats">
                            <div class="stat-item">
                                <span class="stat-value">315lb</span>
                                <span class="stat-label">DEADLIFT</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">225lb</span>
                                <span class="stat-label">SQUAT</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">145lb</span>
                                <span class="stat-label">BENCH</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="showcase-card">
                    <img src="assets/images/community2.jpg" alt="Success Story" class="showcase-img">
                    <div class="showcase-content">
                        <h3>Michael's Home Gym Success</h3>
                        <p>Built an efficient home training setup and achieved his fitness goals with minimal equipment.</p>
                        <div class="showcase-stats">
                            <div class="stat-item">
                                <span class="stat-value">24lbs</span>
                                <span class="stat-label">MUSCLE GAIN</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">18lbs</span>
                                <span class="stat-label">FAT LOSS</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">$600</span>
                                <span class="stat-label">TOTAL COST</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="showcase-card">
                    <img src="assets/images/community3.jpg" alt="Success Story" class="showcase-img">
                    <div class="showcase-content">
                        <h3>Team Challenge Results</h3>
                        <p>Our community 12-week challenge participants saw remarkable improvements in strength and endurance.</p>
                        <div class="showcase-stats">
                            <div class="stat-item">
                                <span class="stat-value">93%</span>
                                <span class="stat-label">COMPLETION</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">27%</span>
                                <span class="stat-label">STRENGTH ↑</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">4.8/5</span>
                                <span class="stat-label">RATING</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/learning_hub.js"></script>
</body>
</html> 