<?php
// Start session
session_start();

// PHP file converted from HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
   
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen">
        <div class="loading-text">LOADING...</div>
        <div class="loading-subtext">GET READY TO CHANGE YOUR DESTINY</div>
    </div>

    <!-- Main Content -->
    <header>
        <a href="<?php echo isset($_SESSION["loggedin"]) ? 'profile.php' : 'index.php'; ?>" class="logo">GYMVERSE</a>
        <nav>
            <a href="#">HOME</a>
            <a href="#">ABOUT</a>
            <a href="membership.php">MEMBERSHIP</a>
            <a href="leaderboard.php">LEADERBOARD</a>
            <a href="nutrition.php">NUTRITION</a>
            <a href="#">CONTACT</a>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="profile.php" class="auth-button-login">PROFILE</a>
                <a href="logout.php" class="auth-button-logout">LOGOUT</a>
            <?php else: ?>
                <a href="login.php" class="auth-button-login">LOGIN</a>
                <a href="register.php" class="auth-button-register">REGISTER</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <h1>GYMVERSE</h1>
        <p>EXPLORE THE VAST UNIVERSE OF FITNESS</p>
        <div class="scroll-indicator">↓</div>
    </section>
   
      
    </head>
  
        <section class="feature-cards">
        <div class="welcome-text">
            WELCOME TO GYMVERSE! EXPLORE WORKOUTS, LEARN ABOUT EQUIPMENT,<br>
            OR DIVE INTO TRAINING PLANS TAILORED JUST FOR YOU.
        </div>
        
        <a href="workouts.php" class="feature-card">
            <img src="images/image 8.png" alt="Workouts">
            <div class="card-content">
                <h2>WORKOUTS</h2>
                <p>EXPLORE PERSONALIZED<br>WORKOUT PLANS<br>AND ROUTINES.</p>
            </div>
        </a>
        
        <a href="nutrition.php" class="feature-card">
            <img src="images/image (1).png" alt="Nutrition">
            <div class="card-content">
                <h2>NUTRITION</h2>
                <p>LEARN ABOUT MEAL PLANS<br>AND FITNESS TIPS<br>TO FUEL YOUR JOURNEY</p>
            </div>
        </a>
        
        <a href="leaderboard.php" class="feature-card">
            <img src="images/image (2).png" alt="Leaderboard">
            <div class="card-content">
                <h2>LEADERBOARD</h2>
                <p>COMPETE WITH FRIENDS<br>AND TRACK YOUR PROGRESS</p>
            </div>
        </a>
        
        <a href="membership.php" class="feature-card">
            <img src="images/image (3).png" alt="Membership">
            <div class="card-content">
                <h2>MEMBERSHIP</h2>
                <p>TRAINING PLAN TAILORED<br>JUST FOR YOU</p>
            </div>
        </a>
    </section>
    
        <!-- Workout Types Section -->
    <section class="workout-types">
        <div class="workout-buttons">
            <a href="excerciseType.php">
            <button class="workout-button skewed"><span>STRENGTH TRAINING</span></button>
        </a>
            <a href="excerciseType.php">
            <button class="workout-button skewed"><span>HIIT</span></button>
        </a>
            <a href="excerciseType.php">
            <button class="workout-button skewed"><span>FAT BURN</span></button>
        </a>
        </div>
        <button class="see-all">SEE ALL WORKOUTS</button>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <h2 class="stats-title">MOST CALORIES BURNED THIS WEEK</h2>
        <div class="leaderboard">
            <div class="podium">
                <div class="podium-3"></div>
                <div class="podium-name">
                    <h3>DAVID GOGGINS</h3>
                    <p>2,450 kcal</p>
                </div>
            </div>
            <div class="podium">
                <div class="podium-1"></div>
                <div class="podium-name">
                    <h3>KRISTIANS PAVLOVSKIS</h3>
                    <p>3,200 kcal</p>
                </div>
            </div>
            <div class="podium">
                <div class="podium-2"></div>
                <div class="podium-name">
                    <h3>EDDIE HALL</h3>
                    <p>2,800 kcal</p>
                </div>
            </div>
        </div>
        <a href="leaderboard.php"><button class="see-all">SEE LEADERBOARD</button></a>
    </section>

    <!-- Meals Section -->
    <section class="meals-section">
        <div class="meal-grid">
            <div class="meal-card">
                <img src="images/k_Photo_Series_2020-01-Power-Hour-keto_Power-Hour-How-I-Prep-a-Week-of-Easy-Keto-Meals_009.jpg" alt="Meal 1">
                <div class="meal-overlay">
                    <h3>MEAL PLAN 1</h3>
                    <p>Discover healthy recipes</p>
                </div>
            </div>
            <div class="meal-card">
                <img src="images/keto-diet-list-of-what-to-eat-and-7-day-sample-menu-alt-1440x810.jpg" alt="Meal 2">
                <div class="meal-overlay">
                    <h3>MEAL PLAN 2</h3>
                    <p>Discover healthy recipes</p>
                </div>
            </div>
            <div class="meal-card">
                <img src="images/keto-meal-prep-2.jpg" alt="Meal 3">
                <div class="meal-overlay">
                    <h3>MEAL PLAN 3</h3>
                    <p>Discover healthy recipes</p>
                </div>
            </div>
        </div>
       <a href="nutrition.php"><button class="see-all">SEE THE BEST MEALS</button></a>
    </section>

    <!-- Membership Section -->
    <section class="membership-section">
        <div class="membership-container">
            <div class="membership-card">
                <div class="membership-content">
                    <h2 class="membership-title">BECOME A MEMBER TODAY</h2>
                    <p class="membership-price">FOR ONLY $14.99/MONTH</p>
                    <div class="membership-tiers">
                        <div class="tier-card">
                            <h3 class="tier-title">BASIC</h3>
                            <ul class="tier-features">
                                <li>✓ Custom Workout Plans</li>
                                <li>✓ Nutrition Guidance</li>
                                <li>✓ Progress Tracking</li>
                            </ul>
                            <button class="tier-button">SELECT PLAN</button>
                        </div>
                        <div class="tier-card">
                            <h3 class="tier-title">PRO</h3>
                            <ul class="tier-features">
                                <li>✓ Custom Workout Plans</li>
                                <li>✓ Nutrition Guidance</li>
                                <li>✓ Progress Tracking</li>
                            </ul>
                            <button class="tier-button">SELECT PLAN</button>
                        </div>
                        <div class="tier-card">
                            <h3 class="tier-title">ELITE</h3>
                            <ul class="tier-features">
                                <li>✓ Custom Workout Plans</li>
                                <li>✓ Nutrition Guidance</li>
                                <li>✓ Progress Tracking</li>
                            </ul>
                            <button class="tier-button">SELECT PLAN</button>
                        </div>
                    </div>
                    <a href="membership.php"><button class="view-all-memberships">VIEW ALL MEMBERSHIPS</button></a>
                </div>
            </div>
        </div>
    </section>
    
        <!-- About Section -->
        <section class="about-section">
            <div class="about-content">
                <h2>ABOUT US</h2>
                <h3>GYMVERSE</h3>
                <p>WE ARE A TEAM WHOSE GOAL IS TO REVOLUTIONIZE THE MODERN SPORT WORLD, BY GIVING EVERYONE EASY ACCESS TO THE CORRECT KNOWLEDGE OF GYM EQUIPMENT, THEIR MUSCLE ANATOMY, AND AN OVERALL GOAL FOR PEOPLE TO STRIVE TOWARDS</p>
                <div class="contact-buttons">
                    <a href="#" class="contact-button">CALL US<br>+370 27 666 666</a>
                    <a href="#" class="contact-button">LOCATION<br>NOT CURRENTLY AVAILABLE</a>
                    <a href="#" class="contact-button">AVAILABLE CONTACT HOURS<br>FROM 8:00 TILL 18:00</a>
                </div>
            </div>
        </section>
    
        <!-- Footer -->
        <footer>
            <div class="footer-content">
                <div class="subscribe-form">
                    <input type="email" placeholder="ENTER EMAIL ADDRESS...">
                    <button>SUBSCRIBE</button>
                </div>
                
                <div class="footer-sections">
                    <div class="footer-section">
                        <h4>GYMVERSE</h4>
                        <p>SPORT WEBSITE FOR YOUR<br>HEALTH AND BEST INTEREST</p>
                        <a href="#">READ MORE...</a>
                    </div>
                    
                    <div class="footer-section">
                        <h4>DISCOVER</h4>
                        <a href="#">WORKOUTS</a>
                        <a href="#">NUTRITION</a>
                        <a href="#">MEMBERSHIP</a>
                        <a href="#">LEADERBOARD</a>
                    </div>
                    
                    <div class="footer-section">
                        <h4>ABOUT</h4>
                        <a href="#">ABOUT US</a>
                        <a href="#">CONTACT US</a>
                    </div>
                    
                    <div class="footer-section">
                        <h4>SOCIAL</h4>
                        <a href="#">INSTAGRAM</a>
                        <a href="#">FACEBOOK</a>
                        <a href="#">LINKEDIN</a>
                        <a href="#">TIKTOK</a>
                    </div>
                </div>
                
                <div class="partners">
                    <span>OUR PARTNERS:</span>
                    <a href="#">COMPANY 1</a>
                    <a href="#">COMPANY 2</a>
                    <a href="#">COMPANY 3</a>
                    <a href="#">COMPANY 4</a>
                    <a href="#">SEE ALL</a>
                </div>
                
                <div class="copyright">
                    COPYRIGHT 2024. ALL RIGHTS RESERVED
                </div>
            </div>
        </footer>
    
        <script> 
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const loadingScreen = document.querySelector('.loading-screen');
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 50);  //500
                }, 200);    //2000
            });
        </script> 
    <script> 
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loadingScreen = document.querySelector('.loading-screen');
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 50); //500
            }, 200);    //2000
        });
    </script>
</body>
</html> 