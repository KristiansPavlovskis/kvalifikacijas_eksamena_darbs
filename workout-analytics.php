<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// PHP file converted from HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Analytics</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        :root {
            --primary-red: #FF4D4D;
            --dark-bg: #0A0A0A;
            --gray-1: #1E1E1E;
            --gray-2: #292929;
            --text-primary: #FFFFFF;
            --text-secondary: #A9A9A9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Header Section */
        .header-summary {
            background: linear-gradient(180deg, var(--dark-bg) 0%, rgba(30, 30, 30, 0.95) 100%);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .metrics-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 77, 77, 0.1);
            transition: transform 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
        }

        .metric-icon {
            color: var(--primary-red);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section {
            margin-bottom: 3rem;
            background: var(--gray-1);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary-red);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Performance Graphs */
        .graph-container {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            height: 300px;
        }

        .graph-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-btn {
            background: var(--gray-2);
            border: none;
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary-red);
            color: var(--text-primary);
        }

        /* Muscle Group Analysis */
        .muscle-analysis {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .body-map {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .muscle-stats {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
        }

        /* Workout History */
        .workout-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
            border-left: 2px solid var(--primary-red);
            padding-left: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            background: var(--primary-red);
            border-radius: 50%;
        }

        .workout-card {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .workout-card:hover {
            transform: translateX(10px);
            border-left: 3px solid var(--primary-red);
        }

        /* Goal Tracker */
        .goal-container {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-red);
            width: 60%;
            transition: width 1s ease;
        }

        /* Recommendations */
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .recommendation-card {
            background: var(--gray-2);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 77, 77, 0.1);
            transition: all 0.3s ease;
        }

        .recommendation-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .metrics-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .muscle-analysis {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .metrics-container {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 1rem;
            }

            .graph-filters {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-header">
        <div class="logo-section">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <nav class="nav-links">
            <a href="workout-analytics.php" class="nav-link active">Total Workouts</a>
            <a href="calories-burned.php" class="nav-link">Calories Burned</a>
            <a href="current-goal.php" class="nav-link">Current Goal</a>
            <a href="workout-planer.php" class="nav-link">Plan</a>
            <a href="logout.php" class="nav-link nav-link-logout">Logout</a>
        </nav>
    </div>

    <!-- Header Summary -->
    <header class="header-summary">
        <div class="metrics-container">
            <div class="metric-card">
                <i class="fas fa-dumbbell metric-icon"></i>
                <div class="metric-value">125</div>
                <div class="metric-label">Workouts Logged</div>
            </div>
            <div class="metric-card">
                <i class="fas fa-fire metric-icon"></i>
                <div class="metric-value">34,567</div>
                <div class="metric-label">Calories Burned</div>
            </div>
            <div class="metric-card">
                <i class="fas fa-bolt metric-icon"></i>
                <div class="metric-value">7</div>
                <div class="metric-label">Day Streak</div>
            </div>
            <div class="metric-card">
                <i class="fas fa-bullseye metric-icon"></i>
                <div class="metric-value">60%</div>
                <div class="metric-label">Goal Progress</div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Performance Graphs -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i>
                    Performance Trends
                </h2>
                <div class="graph-filters">
                    <button class="filter-btn active">Week</button>
                    <button class="filter-btn">Month</button>
                    <button class="filter-btn">Year</button>
                </div>
            </div>
            <div class="graph-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </section>

        <!-- Muscle Group Analysis -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-user"></i>
                    Muscle Group Analysis
                </h2>
            </div>
            <div class="muscle-analysis">
                <div class="body-map">
                    <!-- Body map visualization would go here -->
                    <canvas id="bodyMap"></canvas>
                </div>
                <div class="muscle-stats">
                    <canvas id="muscleChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Workout History -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Workout History
                </h2>
            </div>
            <div class="workout-timeline">
                <div class="timeline-item">
                    <div class="workout-card">
                        <h3>Upper Body Strength</h3>
                        <p>Today - 45 minutes</p>
                        <p>450 calories burned</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="workout-card">
                        <h3>HIIT Cardio</h3>
                        <p>Yesterday - 30 minutes</p>
                        <p>380 calories burned</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Goal Tracker -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-flag"></i>
                    Goal Tracker
                </h2>
            </div>
            <div class="goal-container">
                <h3>Monthly Goal: Run 50km</h3>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p>30km completed - 60% of goal reached</p>
            </div>
        </section>

        <!-- Recommendations -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-lightbulb"></i>
                    Recommendations
                </h2>
            </div>
            <div class="recommendations-grid">
                <div class="recommendation-card">
                    <h3>Add Incline Bench Press</h3>
                    <p>Target your upper chest for better development</p>
                </div>
                <div class="recommendation-card">
                    <h3>Don't Skip Leg Day!</h3>
                    <p>It's been 2 weeks since your last leg workout</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Initialize charts and animations
        document.addEventListener('DOMContentLoaded', () => {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Calories Burned',
                        data: [450, 380, 420, 390, 450, 500, 420],
                        borderColor: '#FF4D4D',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(255, 77, 77, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#A9A9A9'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#A9A9A9'
                            }
                        }
                    }
                }
            });

            // Muscle Chart
            const muscleCtx = document.getElementById('muscleChart').getContext('2d');
            new Chart(muscleCtx, {
                type: 'bar',
                data: {
                    labels: ['Chest', 'Back', 'Legs', 'Shoulders', 'Arms', 'Core'],
                    datasets: [{
                        label: 'Hours Trained',
                        data: [12, 10, 8, 9, 11, 7],
                        backgroundColor: '#FF4D4D'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#A9A9A9'
                            }
                        },x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#A9A9A9'
                            }
                        }
                    }
                }
            });

            // Add click handlers for filter buttons
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    btn.classList.add('active');
                    
                    // Here you would typically update the chart data based on the selected timeframe
                    // For demonstration, we'll just console.log the selected timeframe
                    console.log(`Selected timeframe: ${btn.textContent}`);
                });
            });

            // Add hover effects for metric cards
            const metricCards = document.querySelectorAll('.metric-card');
            metricCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                    card.style.borderColor = 'var(--primary-red)';
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.borderColor = 'rgba(255, 77, 77, 0.1)';
                });
            });

            // Add click handlers for workout cards
            const workoutCards = document.querySelectorAll('.workout-card');
            workoutCards.forEach(card => {
                card.addEventListener('click', () => {
                    // Here you would typically show more workout details
                    // For demonstration, we'll just toggle a class
                    card.classList.toggle('expanded');
                });
            });

            // Initialize body map visualization
            const bodyMapCtx = document.getElementById('bodyMap').getContext('2d');
            // This would typically be a more complex visualization
            // For demonstration, we'll just draw a simple placeholder
            bodyMapCtx.fillStyle = 'rgba(255, 77, 77, 0.2)';
            bodyMapCtx.fillRect(50, 50, 200, 300);

            // Add smooth scroll behavior for navigation
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>