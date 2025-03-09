<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once 'assets/db_connection.php';

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
while ($row = mysqli_fetch_assoc($daily_result)) {
    $daily_calories[] = $row['calories'];
    $daily_dates[] = date('D', strtotime($row['workout_date']));
}

// Fill in missing days with zeros
$past_days = 7;
$all_dates = [];
$all_calories = [];
for ($i = $past_days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day = date('D', strtotime($date));
    $all_dates[] = $day;
    
    $found = false;
    foreach ($daily_dates as $index => $daily_date) {
        if ($daily_date == $day) {
            $all_calories[] = (int)$daily_calories[$index];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $all_calories[] = 0;
    }
}

// Fetch recent workouts with calories data
$recent_workouts_query = "SELECT id, workout_name, workout_type, duration_minutes, calories_burned, completed_at, notes
                         FROM workouts WHERE user_id = ? ORDER BY completed_at DESC LIMIT 5";
$recent_workouts_stmt = mysqli_prepare($conn, $recent_workouts_query);
mysqli_stmt_bind_param($recent_workouts_stmt, "i", $user_id);
mysqli_stmt_execute($recent_workouts_stmt);
$recent_workouts_result = mysqli_stmt_get_result($recent_workouts_stmt);
$recent_workouts = [];
while ($row = mysqli_fetch_assoc($recent_workouts_result)) {
    $recent_workouts[] = $row;
}

// Calculate average calories burned per workout
$avg_query = "SELECT AVG(calories_burned) as avg_calories FROM workouts WHERE user_id = ?";
$avg_stmt = mysqli_prepare($conn, $avg_query);
mysqli_stmt_bind_param($avg_stmt, "i", $user_id);
mysqli_stmt_execute($avg_stmt);
$avg_result = mysqli_stmt_get_result($avg_stmt);
$avg_calories = round(mysqli_fetch_assoc($avg_result)['avg_calories'] ?? 0);

// Calculate most calories burned in a single workout
$max_query = "SELECT MAX(calories_burned) as max_calories FROM workouts WHERE user_id = ?";
$max_stmt = mysqli_prepare($conn, $max_query);
mysqli_stmt_bind_param($max_stmt, "i", $user_id);
mysqli_stmt_execute($max_stmt);
$max_result = mysqli_stmt_get_result($max_stmt);
$max_calories = mysqli_fetch_assoc($max_result)['max_calories'] ?? 0;

// Get the JSON data for charts
$daily_data_json = json_encode($all_calories);
$daily_labels_json = json_encode($all_dates);

// Get JSON data for workout type pie chart
$workout_type_labels = [];
$workout_type_data = [];
foreach ($workout_types as $type) {
    $workout_type_labels[] = $type['workout_type'];
    $workout_type_data[] = (int)$type['calories'];
}
$workout_type_labels_json = json_encode($workout_type_labels);
$workout_type_data_json = json_encode($workout_type_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Calories Burned Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
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
            <a href="calories-burned.php" class="nav-link active">Calories Burned</a>
            <a href="current-goal.php" class="nav-link">Current Goal</a>
            <a href="workout-planer.php" class="nav-link">Plan</a>
            <a href="logout.php" class="nav-link nav-link-logout">Logout</a>
        </nav>
    </header>

    <main class="calories-dashboard">
        <div class="calories-dashboard-header">
            <h1 class="calories-dashboard-title">CALORIES BURNED DASHBOARD</h1>
            <p class="calories-dashboard-subtitle">Track your energy expenditure across different workout types and see your progress over time.</p>
        </div>
        
        <div class="calories-stats-grid">
            <div class="calories-stat-card">
                <div class="calories-stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="calories-stat-value"><?php echo number_format($total_calories); ?></div>
                <div class="calories-stat-label">Total Calories Burned</div>
                <div class="calories-info-badge">
                    All Time
                    <span class="calories-tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="calories-tooltip-text">Total calories burned across all your workouts since you started tracking.</span>
                    </span>
                </div>
            </div>
            
            <div class="calories-stat-card">
                <div class="calories-stat-icon calories-stat-icon-blue">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="calories-stat-value"><?php echo number_format(array_sum($all_calories)); ?></div>
                <div class="calories-stat-label">Calories This Week</div>
                <div class="calories-info-badge">
                    Last 7 Days
                    <span class="calories-tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="calories-tooltip-text">Total calories burned in the last 7 days.</span>
                    </span>
                </div>
            </div>
            
            <div class="calories-stat-card">
                <div class="calories-stat-icon calories-stat-icon-orange">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="calories-stat-value"><?php echo number_format($avg_calories); ?></div>
                <div class="calories-stat-label">Avg. Calories Per Workout</div>
                <div class="calories-info-badge">
                    Overall Average
                    <span class="calories-tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="calories-tooltip-text">The average number of calories you burn during a typical workout session.</span>
                    </span>
                </div>
            </div>
            
            <div class="calories-stat-card">
                <div class="calories-stat-icon calories-stat-icon-green">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="calories-stat-value"><?php echo number_format($max_calories); ?></div>
                <div class="calories-stat-label">Most Calories in One Workout</div>
                <div class="calories-info-badge">
                    Personal Best
                    <span class="calories-tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="calories-tooltip-text">Your record for the most calories burned in a single workout session.</span>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="calories-chart-grid">
            <div class="calories-chart-card">
                <div class="calories-chart-header">
                    <div class="calories-chart-title">Daily Calories Burned (Last 7 Days)</div>
                </div>
                <div class="calories-chart-container">
                    <canvas id="dailyCaloriesChart"></canvas>
                </div>
            </div>
            
            <div class="calories-chart-card">
                <div class="calories-chart-header">
                    <div class="calories-chart-title">Calories by Workout Type</div>
                </div>
                <div class="calories-chart-container">
                    <canvas id="workoutTypeChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="calories-recent-workouts">
            <h2><i class="fas fa-history"></i> Recent Workouts</h2>
            <div class="calories-workout-list">
                <?php if (count($recent_workouts) > 0): ?>
                    <?php foreach ($recent_workouts as $workout): ?>
                        <div class="calories-workout-item">
                            <div class="calories-workout-details">
                                <h3>
                                    <?php 
                                    $type_class = 'calories-type-cardio';
                                    if (strtolower($workout['workout_type']) == 'strength') {
                                        $type_class = 'calories-type-strength';
                                    } elseif (strtolower($workout['workout_type']) == 'hiit') {
                                        $type_class = 'calories-type-hiit';
                                    } elseif (strtolower($workout['workout_type']) == 'flexibility') {
                                        $type_class = 'calories-type-flexibility';
                                    }
                                    ?>
                                    <span class="calories-workout-type-badge <?php echo $type_class; ?>"><?php echo htmlspecialchars($workout['workout_type']); ?></span>
                                    <?php echo htmlspecialchars($workout['workout_name']); ?>
                                </h3>
                                <div class="calories-workout-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($workout['completed_at'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo $workout['duration_minutes']; ?> mins</span>
                                </div>
                            </div>
                            <div class="calories-workout-calories">
                                <i class="fas fa-fire"></i> <?php echo number_format($workout['calories_burned']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No workout data available yet. Start tracking your workouts to see your calories burned!</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="calories-actions-row">
            <a href="workout-planer.php" class="calories-action-button primary">
                <i class="fas fa-plus-circle"></i> Log New Workout
            </a>
            <a href="workout-analytics.php" class="calories-action-button">
                <i class="fas fa-chart-bar"></i> View Full Analytics
            </a>
            <a href="profile.php" class="calories-action-button">
                <i class="fas fa-user"></i> Back to Profile
            </a>
        </div>
    </main>
    
    <script>
        // Chart color configurations
        const chartColors = {
            red: 'rgb(255, 77, 77)',
            orange: 'rgb(255, 167, 0)',
            blue: 'rgb(0, 153, 255)',
            green: 'rgb(0, 204, 102)',
            purple: 'rgb(102, 45, 255)',
            redTransparent: 'rgba(255, 77, 77, 0.2)',
            blueTransparent: 'rgba(0, 153, 255, 0.2)'
        };
        
        // Daily calories chart
        const dailyCaloriesCtx = document.getElementById('dailyCaloriesChart').getContext('2d');
        const dailyCaloriesChart = new Chart(dailyCaloriesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $daily_labels_json; ?>,
                datasets: [{
                    label: 'Calories Burned',
                    data: <?php echo $daily_data_json; ?>,
                    backgroundColor: chartColors.redTransparent,
                    borderColor: chartColors.red,
                    borderWidth: 2,
                    borderRadius: 5,
                    hoverBackgroundColor: chartColors.red,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString() + ' calories';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                if (value >= 1000) {
                                    return (value / 1000) + 'k';
                                }
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Workout type chart
        const workoutTypeLabels = <?php echo $workout_type_labels_json ?? '[]'; ?>;
        const workoutTypeData = <?php echo $workout_type_data_json ?? '[]'; ?>;
        
        // Only create the chart if we have data
        if (workoutTypeLabels.length > 0) {
            const workoutTypeCtx = document.getElementById('workoutTypeChart').getContext('2d');
            const workoutTypeChart = new Chart(workoutTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: workoutTypeLabels,
                    datasets: [{
                        data: workoutTypeData,
                        backgroundColor: [
                            chartColors.red,
                            chartColors.blue,
                            chartColors.orange,
                            chartColors.green,
                            chartColors.purple
                        ],
                        borderColor: '#0A0A0A',
                        borderWidth: 2,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    size: 12
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: '#333',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const percentage = Math.round((value / workoutTypeData.reduce((a, b) => a + b, 0)) * 100);
                                    return `${label}: ${value.toLocaleString()} cal (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Display a message if no data is available
            document.getElementById('workoutTypeChart').parentNode.innerHTML = 
                '<div style="height: 100%; display: flex; align-items: center; justify-content: center; text-align: center;">' +
                '<p>No workout type data available yet.<br>Log workouts to see your data.</p>' +
                '</div>';
        }
    </script>
</body>
</html> 