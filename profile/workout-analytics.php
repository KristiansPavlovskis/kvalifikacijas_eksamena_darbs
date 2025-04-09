<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-analytics.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$email = $_SESSION["email"];

$join_date = "N/A";
try {
    $stmt = mysqli_prepare($conn, "SELECT DATE_FORMAT(created_at, '%b %d, %Y') as join_date FROM users WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $join_date = $row['join_date'];
        }
    }
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Workout Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4cc9f0;
            --primary-dark: #3a56d4;
            --secondary: #f72585;
            --secondary-light: #ff5c8a;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --dark: #0f0f1a;
            --dark-card: #1a1a2e;
            --gray-dark: #2b2b3d;
            --gray-light: rgba(255, 255, 255, 0.7);
            --gradient-blue: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            --gradient-purple: linear-gradient(135deg, #9d4edd, #c77dff);
            --gradient-pink: linear-gradient(135deg, #f72585, #ff5c8a);
            --gradient-green: linear-gradient(135deg, #06d6a0, #64dfdf);
            --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(67, 97, 238, 0.05) 0%, transparent 200px),
                radial-gradient(circle at 70% 80%, rgba(67, 97, 238, 0.05) 0%, transparent 200px);
            width: 100%;
            overflow-x: hidden;
        }

        .dashboard {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background-color: var(--dark-card);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px 20px;
            position: fixed;
            width: var(--sidebar-width);
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 25px;
        }

        .sidebar-logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
            text-decoration: none;
            font-family: 'Koulen', sans-serif;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            margin-bottom: 15px;
            position: relative;
            background-color: var(--gray-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar-avatar::after {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            bottom: -2px;
            left: -2px;
            background: var(--gradient-blue);
            z-index: -1;
            border-radius: 50%;
        }

        .sidebar-user-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-user-email {
            font-size: 0.9rem;
            color: var(--gray-light);
            margin-bottom: 15px;
        }

        .sidebar-user-since {
            font-size: 0.85rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .sidebar-nav {
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .sidebar-nav-title {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--gray-light);
            margin-bottom: 15px;
            padding-left: 10px;
        }

        .sidebar-nav-items {
            list-style: none;
        }

        .sidebar-nav-item {
            margin-bottom: 8px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-nav-link:hover, 
        .sidebar-nav-link.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-light);
        }

        .sidebar-nav-link.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 5px 10px rgba(67, 97, 238, 0.3);
        }

        .sidebar-nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-footer-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .sidebar-footer-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Main content styling */
        .main-content {
            flex: 1;
            padding: 30px 40px;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .page-actions {
            display: flex;
            gap: 15px;
        }

        /* Analytics-specific styles */
        .metrics-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .metric-card {
            background-color: var(--dark-card);
            border-radius: 16px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .metric-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            margin-bottom: 15px;
        }

        .metric-icon.workout {
            background: var(--gradient-blue);
        }

        .metric-icon.calories {
            background: var(--gradient-pink);
        }

        .metric-icon.streak {
            background: var(--gradient-purple);
        }

        .metric-icon.goal {
            background: var(--gradient-green);
        }

        .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.95rem;
            color: var(--gray-light);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.03));
            border-radius: 0 0 0 100%;
        }

        .section {
            background-color: var(--dark-card);
            border-radius: 20px;
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--card-shadow);
        }

        .section:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-light);
        }

        .graph-filters {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 50px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--gray-light);
        }

        .filter-btn.active {
            background: var(--gradient-blue);
            color: white;
            box-shadow: 0 5px 10px rgba(67, 97, 238, 0.2);
        }

        .filter-btn:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .section-body {
            padding: 25px 30px;
        }

        .graph-container {
            width: 100%;
            height: 350px;
            position: relative;
        }

        /* Muscle group analysis section */
        .muscle-analysis {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .body-map {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 350px;
        }

        .muscle-stats {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            min-height: 350px;
        }

        /* Workout timeline */
        .workout-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
            border-left: 2px solid var(--primary);
            padding-left: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            background: var(--primary);
            border-radius: 50%;
        }

        .workout-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            transition: var(--transition);
            position: relative;
            margin-bottom: 20px;
        }

        .workout-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateX(10px);
        }

        .workout-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .workout-card p {
            color: var(--gray-light);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        /* Goal tracker */
        .goal-container {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .goal-container h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-green);
            transition: width 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: shine 1.5s infinite;
        }

        /* Recommendations grid */
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .recommendation-card {
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .recommendation-card:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .recommendation-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-light);
        }

        .recommendation-card p {
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        /* Animation keyframes */
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Mobile-only Navigation */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: var(--dark-card);
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 1000;
        }

        .mobile-nav-links {
            display: flex;
            justify-content: space-around;
        }

        .mobile-nav-link {
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .mobile-nav-link.active {
            color: var(--primary);
        }

        .mobile-nav-link i {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .metrics-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .muscle-analysis {
                grid-template-columns: 1fr;
            }

            .mobile-nav {
                display: block;
            }

            .main-content {
                padding-bottom: 70px;
            }
        }

        @media (max-width: 768px) {
            .metrics-container {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .graph-filters {
                align-self: flex-end;
            }
            
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Include the sidebar -->
        <?php require_once 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Workout Analytics</h1>
                <div class="page-actions">
                    <button class="filter-btn active">Week</button>
                    <button class="filter-btn">Month</button>
                    <button class="filter-btn">Year</button>
                </div>
            </div>
            
            <!-- Summary Metrics -->
            <div class="metrics-container">
                <div class="metric-card">
                    <div class="metric-icon workout">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="metric-value">125</div>
                    <div class="metric-label">Workouts Logged</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon calories">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="metric-value">34,567</div>
                    <div class="metric-label">Calories Burned</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon streak">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="metric-value">7</div>
                    <div class="metric-label">Day Streak</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon goal">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="metric-value">60%</div>
                    <div class="metric-label">Goal Progress</div>
                </div>
            </div>
            
            <!-- Performance Trends -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Performance Trends
                    </h2>
                    <div class="graph-filters">
                        <button class="filter-btn active">Week</button>
                        <button class="filter-btn">Month</button>
                        <button class="filter-btn">Year</button>
                    </div>
                </div>
                
                <div class="section-body">
                    <div class="graph-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Muscle Group Analysis -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i> Muscle Group Analysis
                    </h2>
                </div>
                
                <div class="section-body">
                    <div class="muscle-analysis">
                        <div class="body-map">
                            <!-- Body map visualization would go here -->
                            <canvas id="bodyMap"></canvas>
                        </div>
                        <div class="muscle-stats">
                            <canvas id="muscleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Workout History -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Workout History
                    </h2>
                </div>
                
                <div class="section-body">
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
                </div>
            </div>
            
            <!-- Goal Tracker -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-flag"></i> Goal Tracker
                    </h2>
                </div>
                
                <div class="section-body">
                    <div class="goal-container">
                        <h3>Monthly Goal: Run 50km</h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 60%;"></div>
                        </div>
                        <p>30km completed - 60% of goal reached</p>
                    </div>
                </div>
            </div>
            
            <!-- Recommendations -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-lightbulb"></i> Recommendations
                    </h2>
                </div>
                
                <div class="section-body">
                    <div class="recommendations-grid">
                        <div class="recommendation-card">
                            <h3>Add Incline Bench Press</h3>
                            <p>Target your upper chest for better development</p>
                        </div>
                        <div class="recommendation-card">
                            <h3>Don't Skip Leg Day!</h3>
                            <p>It's been 2 weeks since your last leg workout</p>
                        </div>
                        <div class="recommendation-card">
                            <h3>Increase Cardio Sessions</h3>
                            <p>Add 1-2 more cardio sessions per week to improve endurance</p>
                        </div>
                        <div class="recommendation-card">
                            <h3>Try Supersets</h3>
                            <p>Incorporate supersets to increase workout intensity and save time</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div class="mobile-nav">
                <div class="mobile-nav-links">
                    <a href="profile.php" class="mobile-nav-link">
                        <i class="fas fa-home"></i>
                        Home
                    </a>
                    <a href="workout-analytics.php" class="mobile-nav-link active">
                        <i class="fas fa-chart-line"></i>
                        Analytics
                    </a>
                    <a href="quick-workout.php" class="mobile-nav-link">
                        <i class="fas fa-dumbbell"></i>
                        Workout
                    </a>
                    <a href="#" class="mobile-nav-link" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                        Menu
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Calories Burned',
                        data: [450, 380, 420, 390, 450, 500, 420],
                        borderColor: '#4361ee',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(67, 97, 238, 0.1)'
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
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });

            const muscleCtx = document.getElementById('muscleChart').getContext('2d');
            new Chart(muscleCtx, {
                type: 'bar',
                data: {
                    labels: ['Chest', 'Back', 'Legs', 'Shoulders', 'Arms', 'Core'],
                    datasets: [{
                        label: 'Hours Trained',
                        data: [12, 10, 8, 9, 11, 7],
                        backgroundColor: [
                            '#4361ee',
                            '#4cc9f0',
                            '#f72585',
                            '#9d4edd',
                            '#06d6a0',
                            '#ffd166'
                        ]
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
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });

            const bodyMapCtx = document.getElementById('bodyMap').getContext('2d');
            
            bodyMapCtx.strokeStyle = 'rgba(255, 255, 255, 0.5)';
            bodyMapCtx.lineWidth = 2;
            
            bodyMapCtx.beginPath();
            bodyMapCtx.arc(150, 50, 30, 0, Math.PI * 2);
            bodyMapCtx.stroke();
            
            bodyMapCtx.beginPath();
            bodyMapCtx.moveTo(150, 80);
            bodyMapCtx.lineTo(150, 200);
            bodyMapCtx.stroke();
            
            bodyMapCtx.beginPath();
            bodyMapCtx.moveTo(150, 100);
            bodyMapCtx.lineTo(100, 150);
            bodyMapCtx.moveTo(150, 100);
            bodyMapCtx.lineTo(200, 150);
            bodyMapCtx.stroke();
            
            bodyMapCtx.beginPath();
            bodyMapCtx.moveTo(150, 200);
            bodyMapCtx.lineTo(120, 280);
            bodyMapCtx.moveTo(150, 200);
            bodyMapCtx.lineTo(180, 280);
            bodyMapCtx.stroke();
            
            const chestGradient = bodyMapCtx.createRadialGradient(150, 120, 5, 150, 120, 40);
            chestGradient.addColorStop(0, 'rgba(67, 97, 238, 0.7)');
            chestGradient.addColorStop(1, 'rgba(67, 97, 238, 0)');
            bodyMapCtx.fillStyle = chestGradient;
            bodyMapCtx.beginPath();
            bodyMapCtx.ellipse(150, 120, 40, 20, 0, 0, Math.PI * 2);
            bodyMapCtx.fill();
            
            const backGradient = bodyMapCtx.createRadialGradient(150, 150, 5, 150, 150, 35);
            backGradient.addColorStop(0, 'rgba(76, 201, 240, 0.7)');
            backGradient.addColorStop(1, 'rgba(76, 201, 240, 0)');
            bodyMapCtx.fillStyle = backGradient;
            bodyMapCtx.beginPath();
            bodyMapCtx.ellipse(150, 150, 35, 25, 0, 0, Math.PI * 2);
            bodyMapCtx.fill();
            
            const armsGradient = bodyMapCtx.createRadialGradient(125, 125, 0, 125, 125, 30);
            armsGradient.addColorStop(0, 'rgba(247, 37, 133, 0.7)');
            armsGradient.addColorStop(1, 'rgba(247, 37, 133, 0)');
            bodyMapCtx.fillStyle = armsGradient;
            bodyMapCtx.beginPath();
            bodyMapCtx.ellipse(125, 125, 15, 15, Math.PI / 4, 0, Math.PI * 2);
            bodyMapCtx.fill();
            
            const armsGradient2 = bodyMapCtx.createRadialGradient(175, 125, 0, 175, 125, 30);
            armsGradient2.addColorStop(0, 'rgba(247, 37, 133, 0.7)');
            armsGradient2.addColorStop(1, 'rgba(247, 37, 133, 0)');
            bodyMapCtx.fillStyle = armsGradient2;
            bodyMapCtx.beginPath();
            bodyMapCtx.ellipse(175, 125, 15, 15, -Math.PI / 4, 0, Math.PI * 2);
            bodyMapCtx.fill();
            
            const legsGradient = bodyMapCtx.createRadialGradient(150, 230, 5, 150, 230, 50);
            legsGradient.addColorStop(0, 'rgba(6, 214, 160, 0.7)');
            legsGradient.addColorStop(1, 'rgba(6, 214, 160, 0)');
            bodyMapCtx.fillStyle = legsGradient;
            bodyMapCtx.beginPath();
            bodyMapCtx.ellipse(150, 230, 35, 50, 0, 0, Math.PI * 2);
            bodyMapCtx.fill();
            
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                });
            });
            
            const allCards = document.querySelectorAll('.metric-card, .recommendation-card, .workout-card, .goal-container');
            allCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = this.classList.contains('workout-card') ? 
                        'translateX(10px)' : 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.25)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
            
            const menuToggle = document.getElementById('menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const overlay = document.createElement('div');
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.right = '0';
                    overlay.style.bottom = '0';
                    overlay.style.backgroundColor = 'rgba(15, 15, 26, 0.95)';
                    overlay.style.zIndex = '2000';
                    overlay.style.padding = '20px';
                    overlay.style.overflowY = 'auto';
                    
                    overlay.innerHTML = `
                        <div style="display: flex; justify-content: flex-end;">
                            <button id="close-menu" style="background: none; border: none; color: white; font-size: 1.5rem;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div style="margin-top: 30px;">
                            <h2 style="font-size: 1.8rem; margin-bottom: 20px;">GYMVERSE</h2>
                            <nav>
                                <div style="margin-bottom: 30px;">
                                    <h3 style="font-size: 0.8rem; text-transform: uppercase; color: var(--gray-light); margin-bottom: 15px;">Dashboard</h3>
                                    <ul style="list-style: none;">
                                        <li style="margin-bottom: 15px;"><a href="profile.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                        <li style="margin-bottom: 15px;"><a href="workout-analytics.php" style="color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-chart-line"></i> Analytics</a></li>
                                        <li style="margin-bottom: 15px;"><a href="current-goal.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-bullseye"></i> Goals</a></li>
                                    </ul>
                                </div>
                                <div style="margin-bottom: 30px;">
                                    <h3 style="font-size: 0.8rem; text-transform: uppercase; color: var(--gray-light); margin-bottom: 15px;">Training</h3>
                                    <ul style="list-style: none;">
                                        <li style="margin-bottom: 15px;"><a href="quick-workout.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                                        <li style="margin-bottom: 15px;"><a href="../workouts.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                                        <li style="margin-bottom: 15px;"><a href="calories-burned.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-fire"></i> Calories Burned</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h3 style="font-size: 0.8rem; text-transform: uppercase; color: var(--gray-light); margin-bottom: 15px;">Nutrition</h3>
                                    <ul style="list-style: none;">
                                        <li style="margin-bottom: 15px;"><a href="nutrition.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-apple-alt"></i> Nutrition Tracker</a></li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    `;
                    
                    document.body.appendChild(overlay);
                    
                    document.getElementById('close-menu').addEventListener('click', function() {
                        document.body.removeChild(overlay);
                    });
                });
            }
        });
    </script>
</body>
</html>