<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once 'config/db_connect.php';

// Fetch additional user information 
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$email = $_SESSION["email"];
$join_date = "";

// Use the PDO connection that's defined in db_connection.php
try {
    $sql = "SELECT created_at FROM users WHERE id = :id";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
        if($stmt->execute()) {
            if($row = $stmt->fetch()) {
                $join_date = date("M d, Y", strtotime($row["created_at"]));
            }
        }
    }
} catch(PDOException $e) {
    // In production, you should log this error instead of displaying it
    // echo "Error: " . $e->getMessage();
}

// PHP file converted from HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Profile Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="top-header">
        <div class="logo-section">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <nav class="nav-links">
            <a href="workout-analytics.php" class="nav-link">Total Workouts</a>
            <a href="calories-burned.php" class="nav-link">Calories Burned</a>
            <a href="current-goal.php" class="nav-link">Current Goal</a>
            <a href="workout-planer.php" class="nav-link">Plan</a>
            <a href="logout.php" class="nav-link nav-link-logout">Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <!-- Welcome Section -->
        <section class="section profile-welcome-section">
            <div>
                <h1 class="profile-welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                <p class="profile-welcome-text">Ready to crush your fitness goals today?</p>
            </div>
            <div class="profile-welcome-cta">
                <a href="workout-planer.php" class="profile-start-button">START WORKOUT</a>
            </div>
        </section>

        <!-- Account Information Section -->
        <section class="section profile-section">
            <h2 class="profile-section-title">
                <i class="fas fa-user-circle"></i>
                Account Information
            </h2>
            
            <div class="profile-grid">
                <div class="profile-card">
                    <h3 class="profile-card-title">Profile Details</h3>
                    <div class="profile-field">
                        <label class="profile-label">Username</label>
                        <p class="profile-value"><?php echo htmlspecialchars($username); ?></p>
                    </div>
                    <div class="profile-field">
                        <label class="profile-label">Email</label>
                        <p class="profile-value profile-value-normal"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div class="profile-field">
                        <label class="profile-label">Member Since</label>
                        <p class="profile-value profile-value-normal"><?php echo !empty($join_date) ? $join_date : "N/A"; ?></p>
                    </div>
                </div>
                
                <div class="profile-card">
                    <h3 class="profile-card-title">Account Settings</h3>
                    <div>
                        <a href="#" class="profile-settings-link">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="#" class="profile-settings-link">
                            <i class="fas fa-cog"></i> Edit Profile
                        </a>
                        <a href="#" class="profile-settings-link">
                            <i class="fas fa-bell"></i> Notification Settings
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">
                <i class="fas fa-dumbbell"></i>
                Workout Analytics – "Your Training at a Glance"
            </h2>
            <div class="body-visual">
                <div class="body-image">
                    <!-- Body outline would go here -->
                    <canvas id="bodyCanvas"></canvas>
                </div>
                <div class="pie-chart">
                    <canvas id="workoutTypesChart"></canvas>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value">100KG</div>
                    <div class="stat-label">BENCH</div>
                    <div class="stat-extra">TOP 0.7%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">250KG</div>
                    <div class="stat-label">SQUAT</div>
                    <div class="stat-extra">TOP 0.3%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">150KG</div>
                    <div class="stat-label">DEADLIFT</div>
                    <div class="stat-extra">TOP 1.7%</div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">
                <i class="fas fa-utensils"></i>
                Nutrition & Meal Tracker – "Fuel Your Progress"
            </h2>
            <div class="tile-grid">
                <div class="tile">
                    <canvas id="macrosChart"></canvas>
                </div>
                <div class="tile">
                    <div class="nutrition-stats">
                        <h3>Nutrition Activity Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">Calories Burned</div>
                                <div class="stat-value">2,500</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Calories Consumed</div>
                                <div class="stat-value">2,200</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">
                <i class="fas fa-trophy"></i>
                Achievements – "Celebrate Your Wins"
            </h2>
            <div class="achievements-grid">
                <div class="achievement-card">
                    <i class="fas fa-medal fa-2x"></i>
                    <h3>First Mile</h3>
                    <p>Complete your first mile run</p>
                </div>
                <div class="achievement-card">
                    <i class="fas fa-fire fa-2x"></i>
                    <h3>Calorie Crusher</h3>
                    <p>Burn 1000 calories in a day</p>
                </div>
                <div class="achievement-card">
                    <i class="fas fa-dumbbell fa-2x"></i>
                    <h3>Strength Master</h3>
                    <p>Lift 100kg in any exercise</p>
                </div>
                <div class="achievement-card">
                    <i class="fas fa-clock fa-2x"></i>
                    <h3>Early Bird</h3>
                    <p>Complete 5 morning workouts</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i>
                Progress Tracker – "Your Goals, Your Journey"
            </h2>
            <div class="progress-bars">
                <div class="progress-bar">
                    <h3>Weight Goal</h3>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: 65%;"></div>
                    </div>
                    <p>65% Complete</p>
                </div>
                <div class="progress-bar">
                    <h3>Strength Goal</h3>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: 80%;"></div>
                    </div>
                    <p>80% Complete</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Initialize charts
        const workoutTypes = {
            labels: ['Cardio', 'Strength', 'HIIT', 'Other'],
            datasets: [{
                data: [30, 40, 20, 10],
                backgroundColor: ['#FF4D4D', '#FF6B6B', '#FF8787', '#FFA5A5']
            }]
        };

        const macros = {
            labels: ['Protein', 'Carbs', 'Fats'],
            datasets: [{
                data: [35, 45, 20],
                backgroundColor: ['#FF4D4D', '#FF6B6B', '#FF8787']
            }]
        };

        // Add workout types pie chart
        const workoutCtx = document.getElementById('workoutTypesChart').getContext('2d');
        new Chart(workoutCtx, {
            type: 'pie',
            data: workoutTypes,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });

        // Add macros pie chart
        const macrosCtx = document.getElementById('macrosChart').getContext('2d');
        new Chart(macrosCtx, {
            type: 'pie',
            data: macros,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });

        // Add animations for achievements
        document.querySelectorAll('.achievement-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.05)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>