<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/calories-burned.php");
    exit;
}

// Include database connection
require_once '../assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Fetch total calories burned from all workouts
$total_calories_query = "SELECT SUM(calories_burned) as total_calories FROM workouts WHERE user_id = ?";
$total_calories_stmt = mysqli_prepare($conn, $total_calories_query);
mysqli_stmt_bind_param($total_calories_stmt, "i", $user_id);
mysqli_stmt_execute($total_calories_stmt);
$total_calories_result = mysqli_stmt_get_result($total_calories_stmt);
$total_calories = mysqli_fetch_assoc($total_calories_result)['total_calories'] ?? 0;

// Fetch calories burned per workout type
$workout_type_query = "SELECT workout_type, SUM(calories_burned) as calories FROM workouts 
                       WHERE user_id = ? GROUP BY workout_type ORDER BY calories DESC";
$workout_type_stmt = mysqli_prepare($conn, $workout_type_query);
mysqli_stmt_bind_param($workout_type_stmt, "i", $user_id);
mysqli_stmt_execute($workout_type_stmt);
$workout_type_result = mysqli_stmt_get_result($workout_type_stmt);
$workout_types = [];
while ($row = mysqli_fetch_assoc($workout_type_result)) {
    $workout_types[] = $row;
}

// Fetch calories burned per day for the last 7 days
$daily_query = "SELECT DATE(completed_at) as workout_date, SUM(calories_burned) as calories 
                FROM workouts WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY DATE(completed_at) ORDER BY workout_date";
$daily_stmt = mysqli_prepare($conn, $daily_query);
mysqli_stmt_bind_param($daily_stmt, "i", $user_id);
mysqli_stmt_execute($daily_stmt);
$daily_result = mysqli_stmt_get_result($daily_stmt);
$daily_calories = [];
$daily_dates = [];

// Get 7 days, fill in missing days with 0 calories
$one_week_ago = new DateTime();
$one_week_ago->modify('-6 days');
$dates_with_calories = [];

// Fill the dataset with dates from the database
while ($row = mysqli_fetch_assoc($daily_result)) {
    $dates_with_calories[date('Y-m-d', strtotime($row['workout_date']))] = $row['calories'];
}

// Create arrays for chart.js with all 7 days
for ($i = 0; $i < 7; $i++) {
    $date = clone $one_week_ago;
    $date->modify("+$i days");
    $date_str = $date->format('Y-m-d');
    $daily_dates[] = $date->format('D');
    $daily_calories[] = isset($dates_with_calories[$date_str]) ? $dates_with_calories[$date_str] : 0;
}

// Get week total
$week_total = array_sum($daily_calories);

// Fetch recent workouts
$recent_workouts_query = "SELECT workout_name, workout_type, duration_minutes, calories_burned, completed_at 
                          FROM workouts WHERE user_id = ? ORDER BY completed_at DESC LIMIT 5";
$recent_workouts_stmt = mysqli_prepare($conn, $recent_workouts_query);
mysqli_stmt_bind_param($recent_workouts_stmt, "i", $user_id);
mysqli_stmt_execute($recent_workouts_stmt);
$recent_workouts_result = mysqli_stmt_get_result($recent_workouts_stmt);
$recent_workouts = [];
while ($row = mysqli_fetch_assoc($recent_workouts_result)) {
    $recent_workouts[] = $row;
}

// Calculate average and max calories per workout
$avg_max_query = "SELECT AVG(calories_burned) as avg_calories, MAX(calories_burned) as max_calories 
                  FROM workouts WHERE user_id = ?";
$avg_max_stmt = mysqli_prepare($conn, $avg_max_query);
mysqli_stmt_bind_param($avg_max_stmt, "i", $user_id);
mysqli_stmt_execute($avg_max_stmt);
$avg_max_result = mysqli_stmt_get_result($avg_max_stmt);
$avg_max = mysqli_fetch_assoc($avg_max_result);
$avg_calories = round($avg_max['avg_calories'] ?? 0);
$max_calories = $avg_max['max_calories'] ?? 0;

// Prepare data for charts
$daily_colors = array_fill(0, 7, 'rgba(255, 77, 77, 0.5)');
$workout_type_names = [];
$workout_type_calories = [];
$workout_type_colors = [];

$color_palette = [
    'rgba(255, 77, 77, 0.7)',
    'rgba(255, 167, 0, 0.7)',
    'rgba(0, 204, 102, 0.7)',
    'rgba(0, 153, 255, 0.7)',
    'rgba(153, 51, 255, 0.7)'
];

foreach ($workout_types as $index => $type) {
    $workout_type_names[] = $type['workout_type'];
    $workout_type_calories[] = $type['calories'];
    $workout_type_colors[] = $color_palette[$index % count($color_palette)];
}

// JSON for chart data
$daily_chart_data = json_encode([
    'labels' => $daily_dates,
    'datasets' => [[
        'label' => 'Calories Burned',
        'data' => $daily_calories,
        'backgroundColor' => $daily_colors,
        'borderWidth' => 1
    ]]
]);

$workout_type_chart_data = json_encode([
    'labels' => $workout_type_names,
    'datasets' => [[
        'label' => 'Calories by Workout Type',
        'data' => $workout_type_calories,
        'backgroundColor' => $workout_type_colors,
        'borderWidth' => 1
    ]]
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Calories Burned</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Common profile section styles */
        .prof-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        
        .prof-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 30px;
            background: linear-gradient(135deg, #4361ee, #4cc9f0);
            border-radius: 16px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .prof-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 40%;
            background: rgba(255, 255, 255, 0.1);
            transform: skewX(-15deg);
            transform-origin: top right;
        }
        
        .prof-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 10px;
        }
        
        .prof-nav::-webkit-scrollbar {
            display: none;
        }
        
        .prof-nav-item {
            padding: 12px 24px;
            background-color: #1E1E1E;
            color: white;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .prof-nav-item:hover, .prof-nav-item.active {
            background-color: #4361ee;
            transform: translateY(-3px);
        }
        
        .prof-nav-item i {
            font-size: 1.2rem;
        }
        
        .prof-section {
            margin-bottom: 30px;
            background-color: #1E1E1E;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .prof-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }
        
        .prof-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .prof-section-title i {
            color: #4361ee;
        }
        
        @media (max-width: 768px) {
            .prof-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 20px;
            }
            
            .prof-user-info {
                width: 100%;
                justify-content: flex-start;
            }
            
            .prof-stats {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 15px;
            }
        }
        
        /* Calories Burned Page Styles with unique cal- prefix */
        :root {
            --cal-primary: #ff4d4d;
            --cal-secondary: #333;
            --cal-dark: #0A0A0A;
            --cal-light: #f5f5f5;
            --cal-success: #00cc66;
            --cal-warning: #ffa700;
            --cal-info: #0099ff;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cal-dark);
            color: var(--cal-light);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .cal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cal-logo {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            color: var(--cal-primary);
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.3);
            letter-spacing: 2px;
            margin: 0;
        }
        
        .cal-nav {
            display: flex;
            gap: 20px;
        }
        
        .cal-nav-link {
            color: var(--cal-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .cal-nav-link:hover {
            background: rgba(255, 77, 77, 0.1);
            color: var(--cal-primary);
        }
        
        .cal-nav-link.active {
            background: var(--cal-primary);
            color: white;
        }
        
        .cal-page-title {
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
            color: var(--cal-primary);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
        }
        
        .cal-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .cal-stat-card {
            background: rgba(30, 30, 30, 0.8);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .cal-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .cal-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--cal-primary);
        }
        
        .cal-stat-icon {
            font-size: 2.5rem;
            color: var(--cal-primary);
            margin-bottom: 15px;
        }
        
        .cal-stat-value {
            font-size: 2.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
            font-family: 'Koulen', sans-serif;
        }
        
        .cal-stat-label {
            font-size: 1rem;
            color: #999;
            font-weight: 500;
        }
        
        .cal-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .cal-chart-card {
            background: rgba(20, 20, 20, 0.7);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .cal-chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--cal-primary), #ff9b9b);
        }
        
        .cal-chart-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            font-weight: 600;
        }
        
        .cal-chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .cal-recent-workouts {
            background: rgba(20, 20, 20, 0.7);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }
        
        .cal-recent-workouts::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--cal-primary), #ff9b9b);
        }
        
        .cal-section-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cal-section-title i {
            color: var(--cal-primary);
        }
        
        .cal-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cal-table th {
            text-align: left;
            padding: 12px;
            color: #999;
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cal-table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .cal-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .cal-workout-type {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .cal-type-strength {
            background: rgba(255, 77, 77, 0.1);
            color: var(--cal-primary);
        }
        
        .cal-type-cardio {
            background: rgba(0, 153, 255, 0.1);
            color: var(--cal-info);
        }
        
        .cal-type-yoga {
            background: rgba(0, 204, 102, 0.1);
            color: var(--cal-success);
        }
        
        .cal-type-hiit {
            background: rgba(255, 167, 0, 0.1);
            color: var(--cal-warning);
        }
        
        .cal-calories {
            font-weight: 600;
            color: var(--cal-primary);
        }
        
        .cal-date {
            color: #999;
            font-size: 0.9rem;
        }
        
        .cal-duration {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cal-duration i {
            color: #999;
        }
        
        .cal-empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .cal-empty-icon {
            font-size: 3rem;
            color: rgba(255, 77, 77, 0.2);
            margin-bottom: 20px;
        }
        
        .cal-empty-message {
            font-size: 1.2rem;
            color: #999;
            margin-bottom: 30px;
        }
        
        .cal-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .cal-btn-primary {
            background: var(--cal-primary);
            color: white;
        }
        
        .cal-btn-primary:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        @media (max-width: 768px) {
            .cal-chart-grid {
                grid-template-columns: 1fr;
            }
            
            .cal-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .cal-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="logo">
            <a href="../index.php">
                <i class="fas fa-dumbbell"></i>
                <span>GYMVERSE</span>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../workouts.php"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                <li><a href="../excercises.php"><i class="fas fa-running"></i> Exercises</a></li>
                <li><a href="../quick-workout.php"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                <li><a class="active" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="prof-container">
        <!-- Profile Header -->
        <div class="prof-header">
            <div>
                <h1><i class="fas fa-fire"></i> Calories Burned</h1>
                <p>Track your calorie burn and optimize your workouts</p>
            </div>
            <div class="prof-stats">
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= number_format($total_calories) ?></div>
                    <div class="prof-stat-label">Total Calories</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= number_format($avg_calories) ?></div>
                    <div class="prof-stat-label">Avg per Workout</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= number_format($max_calories) ?></div>
                    <div class="prof-stat-label">Best Workout</div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="prof-nav">
            <a href="profile.php" class="prof-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="calories-burned.php" class="prof-nav-item active">
                <i class="fas fa-fire"></i> Calories Burned
            </a>
            <a href="current-goal.php" class="prof-nav-item">
                <i class="fas fa-bullseye"></i> Goals
            </a>
            <a href="nutrition.php" class="prof-nav-item">
                <i class="fas fa-apple-alt"></i> Nutrition
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-chart-line"></i> Progress
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>

        <!-- Continue with the rest of the calories burned page content -->
        <h1 class="cal-page-title">Calories Burned Dashboard</h1>

        <div class="cal-stats-grid">
            <div class="cal-stat-card">
                <div class="cal-stat-icon"><i class="fas fa-fire"></i></div>
                <div class="cal-stat-value"><?php echo number_format($total_calories); ?></div>
                <div class="cal-stat-label">Total Calories Burned</div>
            </div>
            
            <div class="cal-stat-card">
                <div class="cal-stat-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="cal-stat-value"><?php echo number_format($week_total); ?></div>
                <div class="cal-stat-label">Calories Burned This Week</div>
            </div>
            
            <div class="cal-stat-card">
                <div class="cal-stat-icon"><i class="fas fa-calculator"></i></div>
                <div class="cal-stat-value"><?php echo number_format($avg_calories); ?></div>
                <div class="cal-stat-label">Avg Calories Per Workout</div>
            </div>
            
            <div class="cal-stat-card">
                <div class="cal-stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="cal-stat-value"><?php echo number_format($max_calories); ?></div>
                <div class="cal-stat-label">Most Calories In One Workout</div>
            </div>
        </div>

        <div class="cal-chart-grid">
            <div class="cal-chart-card">
                <h3 class="cal-chart-title">Daily Calories Burned (Last 7 Days)</h3>
                <div class="cal-chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            
            <div class="cal-chart-card">
                <h3 class="cal-chart-title">Calories by Workout Type</h3>
                <div class="cal-chart-container">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="cal-recent-workouts">
            <h2 class="cal-section-title"><i class="fas fa-history"></i> Recent Workouts</h2>
            
            <?php if (empty($recent_workouts)): ?>
                <div class="cal-empty-state">
                    <div class="cal-empty-icon"><i class="fas fa-fire-alt"></i></div>
                    <p class="cal-empty-message">No workouts logged yet. Start tracking your calories!</p>
                    <a href="workout-planer.php" class="cal-btn cal-btn-primary">
                        <i class="fas fa-dumbbell"></i> Plan a Workout
                    </a>
                </div>
            <?php else: ?>
                <table class="cal-table">
                    <thead>
                        <tr>
                            <th>Workout</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Calories</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_workouts as $workout): 
                            $type_class = 'cal-type-strength';
                            switch (strtolower($workout['workout_type'])) {
                                case 'cardio': $type_class = 'cal-type-cardio'; break;
                                case 'yoga': $type_class = 'cal-type-yoga'; break;
                                case 'hiit': $type_class = 'cal-type-hiit'; break;
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($workout['workout_name']); ?></td>
                            <td><span class="cal-workout-type <?php echo $type_class; ?>"><?php echo htmlspecialchars($workout['workout_type']); ?></span></td>
                            <td>
                                <span class="cal-duration">
                                    <i class="far fa-clock"></i>
                                    <?php echo $workout['duration_minutes']; ?> min
                                </span>
                            </td>
                            <td><span class="cal-calories"><?php echo number_format($workout['calories_burned']); ?> kcal</span></td>
                            <td><span class="cal-date"><?php echo date('M d, Y', strtotime($workout['completed_at'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Daily calories chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: <?php echo $daily_chart_data; ?>,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#cccccc'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#cccccc'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Workout type chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: <?php echo $workout_type_chart_data; ?>,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#cccccc',
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 