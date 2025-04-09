<?php
// Include access control check for profile pages
require_once 'profile_access_control.php';

session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/progress.php");
    exit;
}

// Include database connection
require_once '../assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Initialize arrays for chart data
$bodyweightData = [];
$strengthData = [];
$workoutFrequencyData = [];
$volumeProgressData = [];

// Get bodyweight data if available
if (tableExists($conn, 'body_measurements')) {
    $bodyweight_query = "SELECT measure_date, weight FROM body_measurements 
                        WHERE user_id = ? AND weight IS NOT NULL 
                        ORDER BY measure_date ASC 
                        LIMIT 30";
    $stmt = mysqli_prepare($conn, $bodyweight_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bodyweightData[] = [
            'date' => $row['measure_date'],
            'value' => $row['weight']
        ];
    }
}

// Get strength data (e.g., personal records)
if (tableExists($conn, 'workout_exercises') && tableExists($conn, 'exercise_sets')) {
    // Example: Bench Press progress
    $strength_query = "SELECT 
                        DATE(w.created_at) as workout_date,
                        MAX(es.weight) as max_weight
                      FROM workouts w
                      JOIN workout_exercises we ON w.id = we.workout_id
                      JOIN exercise_sets es ON we.id = es.exercise_id
                      WHERE w.user_id = ? 
                      AND we.exercise_name LIKE '%Bench Press%'
                      AND es.weight > 0
                      GROUP BY DATE(w.created_at)
                      ORDER BY workout_date ASC
                      LIMIT 20";
    
    $stmt = mysqli_prepare($conn, $strength_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $strengthData[] = [
            'date' => $row['workout_date'],
            'value' => $row['max_weight']
        ];
    }
}

// Get workout frequency data
if (tableExists($conn, 'workouts')) {
    $frequency_query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as workout_count
                      FROM workouts
                      WHERE user_id = ? 
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month ASC
                      LIMIT 12";
    
    $stmt = mysqli_prepare($conn, $frequency_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $workoutFrequencyData[] = [
            'month' => $row['month'],
            'value' => $row['workout_count']
        ];
    }
}

// Get volume progress data
if (tableExists($conn, 'workouts') && tableExists($conn, 'workout_exercises') && tableExists($conn, 'exercise_sets')) {
    $volume_query = "SELECT 
                    DATE(w.created_at) as workout_date,
                    SUM(es.weight * es.reps) as total_volume
                  FROM workouts w
                  JOIN workout_exercises we ON w.id = we.workout_id
                  JOIN exercise_sets es ON we.id = es.exercise_id
                  WHERE w.user_id = ? 
                  GROUP BY DATE(w.created_at)
                  ORDER BY workout_date ASC
                  LIMIT 20";
    
    $stmt = mysqli_prepare($conn, $volume_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $volumeProgressData[] = [
            'date' => $row['workout_date'],
            'value' => $row['total_volume']
        ];
    }
}

// Get personal bests
$personalBests = [];
if (tableExists($conn, 'workout_exercises') && tableExists($conn, 'exercise_sets')) {
    $pb_query = "SELECT 
                we.exercise_name,
                MAX(es.weight) as max_weight,
                es.reps as reps_at_max
              FROM workout_exercises we
              JOIN exercise_sets es ON we.id = es.exercise_id
              WHERE we.user_id = ? 
              AND es.weight > 0
              GROUP BY we.exercise_name
              ORDER BY max_weight DESC
              LIMIT 5";
    
    $stmt = mysqli_prepare($conn, $pb_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $personalBests[] = [
            'exercise' => $row['exercise_name'],
            'weight' => $row['max_weight'],
            'reps' => $row['reps_at_max']
        ];
    }
}

// Get workout consistency
$consistency = 0;
$workoutCount = 0;
if (tableExists($conn, 'workouts')) {
    // Get total workouts in the last 30 days
    $consistency_query = "SELECT COUNT(*) as workout_count
                        FROM workouts
                        WHERE user_id = ? 
                        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = mysqli_prepare($conn, $consistency_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $workoutCount = $row['workout_count'];
        $consistency = min(round(($workoutCount / 30) * 100), 100); // Cap at 100%
    }
}

// JSON encode chart data for JavaScript
$bodyweightJSON = json_encode($bodyweightData);
$strengthJSON = json_encode($strengthData);
$workoutFrequencyJSON = json_encode($workoutFrequencyData);
$volumeProgressJSON = json_encode($volumeProgressData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Progress Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
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
            color: white;
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
            margin: 0;
        }
        
        .prof-section-title i {
            color: #4361ee;
        }
        
        /* Progress tracking specific styles */
        .progress-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .progress-card {
            background-color: #252525;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }
        
        .progress-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .progress-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        
        .progress-card-icon {
            color: #4361ee;
        }
        
        .progress-card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .progress-chart-container {
            width: 100%;
            height: 200px;
        }
        
        .radial-progress {
            position: relative;
            width: 140px;
            height: 140px;
        }
        
        .radial-progress canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .radial-progress-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 700;
        }
        
        .progress-large-value {
            font-size: 3rem;
            font-weight: 700;
            color: #4361ee;
            margin: 10px 0;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #aaa;
            margin-top: 5px;
        }
        
        .personal-bests {
            margin-top: 15px;
            width: 100%;
        }
        
        .personal-best-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .personal-best-item:last-child {
            border-bottom: none;
        }
        
        .personal-best-exercise {
            font-weight: 500;
        }
        
        .personal-best-value {
            font-weight: 600;
            color: #4361ee;
        }
        
        .consistency-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 15px;
        }
        
        .calendar-day {
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 4px;
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .calendar-day.active {
            background-color: rgba(67, 97, 238, 0.7);
        }
        
        .progress-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: none;
        }
        
        .progress-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .progress-tab {
            padding: 10px 20px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .progress-tab.active {
            background-color: #4361ee;
        }
        
        .progress-tab-content {
            display: none;
        }
        
        .progress-tab-content.active {
            display: block;
        }
        
        .no-data-message {
            text-align: center;
            padding: 30px;
            color: #aaa;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .no-data-message i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .prof-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 20px;
            }
            
            .prof-stats {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="prof-container">
        <!-- Profile Header -->
        <div class="prof-header">
            <div>
                <h1><i class="fas fa-chart-line"></i> Progress Tracking</h1>
                <p>Monitor your fitness journey and celebrate your achievements</p>
            </div>
            <div class="prof-stats">
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $workoutCount ?></div>
                    <div class="prof-stat-label">Workouts (30 days)</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= count($personalBests) ?></div>
                    <div class="prof-stat-label">Personal Bests</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= $consistency ?>%</div>
                    <div class="prof-stat-label">Consistency</div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="prof-nav">
            <a href="profile.php" class="prof-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="calories-burned.php" class="prof-nav-item">
                <i class="fas fa-fire"></i> Calories Burned
            </a>
            <a href="current-goal.php" class="prof-nav-item">
                <i class="fas fa-bullseye"></i> Goals
            </a>
            <a href="nutrition.php" class="prof-nav-item">
                <i class="fas fa-apple-alt"></i> Nutrition
            </a>
            <a href="progress.php" class="prof-nav-item active">
                <i class="fas fa-chart-line"></i> Progress
            </a>
            <a href="settings.php" class="prof-nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>

        <!-- Progress Dashboard -->
        <div class="progress-dashboard">
            <!-- Workout Consistency Card -->
            <div class="progress-card">
                <div class="progress-card-header">
                    <h3 class="progress-card-title">
                        <i class="fas fa-calendar-check progress-card-icon"></i>
                        Workout Consistency
                    </h3>
                </div>
                <div class="progress-card-body">
                    <div class="radial-progress" id="consistencyChart">
                        <div class="radial-progress-value"><?= $consistency ?>%</div>
                    </div>
                    <div class="progress-label">Last 30 days</div>
                </div>
            </div>
            
            <!-- Body Measurements Card -->
            <div class="progress-card">
                <div class="progress-card-header">
                    <h3 class="progress-card-title">
                        <i class="fas fa-weight progress-card-icon"></i>
                        Body Weight
                    </h3>
                </div>
                <div class="progress-card-body">
                    <?php if(count($bodyweightData) > 0): ?>
                        <div class="progress-chart-container">
                            <canvas id="bodyweightChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-weight"></i>
                            <p>No bodyweight data recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Personal Bests Card -->
            <div class="progress-card">
                <div class="progress-card-header">
                    <h3 class="progress-card-title">
                        <i class="fas fa-trophy progress-card-icon"></i>
                        Personal Bests
                    </h3>
                </div>
                <div class="progress-card-body">
                    <?php if(count($personalBests) > 0): ?>
                        <div class="personal-bests">
                            <?php foreach($personalBests as $pb): ?>
                                <div class="personal-best-item">
                                    <div class="personal-best-exercise"><?= htmlspecialchars($pb['exercise']) ?></div>
                                    <div class="personal-best-value"><?= $pb['weight'] ?>kg × <?= $pb['reps'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-trophy"></i>
                            <p>No personal bests recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Volume Progress Card -->
            <div class="progress-card">
                <div class="progress-card-header">
                    <h3 class="progress-card-title">
                        <i class="fas fa-dumbbell progress-card-icon"></i>
                        Volume Progress
                    </h3>
                </div>
                <div class="progress-card-body">
                    <?php if(count($volumeProgressData) > 0): ?>
                        <div class="progress-chart-container">
                            <canvas id="volumeChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-dumbbell"></i>
                            <p>No workout volume data recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Progress Tracking -->
        <div class="prof-section">
            <div class="prof-section-header">
                <h2 class="prof-section-title">
                    <i class="fas fa-chart-line"></i> Detailed Progress Tracking
                </h2>
            </div>
            
            <div class="progress-tabs">
                <div class="progress-tab active" data-tab="strength">Strength Progress</div>
                <div class="progress-tab" data-tab="workout">Workout Frequency</div>
                <div class="progress-tab" data-tab="measurements">Body Measurements</div>
                <div class="progress-tab" data-tab="achievements">Achievements</div>
            </div>
            
            <!-- Strength Progress Tab -->
            <div class="progress-tab-content active" id="strength-tab">
                <?php if(count($strengthData) > 0): ?>
                    <div class="progress-chart-container" style="height: 300px;">
                        <canvas id="strengthProgressChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-line"></i>
                        <p>No strength progression data available yet</p>
                        <p>Start tracking your workouts to see your strength progress over time</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Workout Frequency Tab -->
            <div class="progress-tab-content" id="workout-tab">
                <?php if(count($workoutFrequencyData) > 0): ?>
                    <div class="progress-chart-container" style="height: 300px;">
                        <canvas id="workoutFrequencyChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-calendar-alt"></i>
                        <p>No workout frequency data available yet</p>
                        <p>Log your workouts to see your training consistency</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Body Measurements Tab -->
            <div class="progress-tab-content" id="measurements-tab">
                <div class="no-data-message">
                    <i class="fas fa-ruler"></i>
                    <p>No body measurement data available yet</p>
                    <p>Start tracking your measurements to monitor your physical progress</p>
                </div>
            </div>
            
            <!-- Achievements Tab -->
            <div class="progress-tab-content" id="achievements-tab">
                <div class="no-data-message">
                    <i class="fas fa-medal"></i>
                    <p>Achievements system coming soon</p>
                    <p>Track your workouts to unlock achievements and rewards</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.progress-tab');
            const tabContents = document.querySelectorAll('.progress-tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabName + '-tab').classList.add('active');
                });
            });
            
            // Consistency chart
            const consistencyValue = <?= $consistency ?>;
            const consistencyCanvas = document.createElement('canvas');
            consistencyCanvas.width = 140;
            consistencyCanvas.height = 140;
            document.getElementById('consistencyChart').appendChild(consistencyCanvas);
            
            const consistencyCtx = consistencyCanvas.getContext('2d');
            
            // Draw background circle
            consistencyCtx.beginPath();
            consistencyCtx.arc(70, 70, 60, 0, 2 * Math.PI);
            consistencyCtx.lineWidth = 12;
            consistencyCtx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
            consistencyCtx.stroke();
            
            // Draw progress arc
            const progressAngle = (consistencyValue / 100) * 2 * Math.PI;
            consistencyCtx.beginPath();
            consistencyCtx.arc(70, 70, 60, -Math.PI / 2, progressAngle - Math.PI / 2);
            consistencyCtx.lineWidth = 12;
            consistencyCtx.strokeStyle = '#4361ee';
            consistencyCtx.stroke();
            
            // Bodyweight chart
            const bodyweightData = <?= $bodyweightJSON ?>;
            if (bodyweightData.length > 0) {
                const bodyweightCtx = document.getElementById('bodyweightChart').getContext('2d');
                new Chart(bodyweightCtx, {
                    type: 'line',
                    data: {
                        labels: bodyweightData.map(d => d.date),
                        datasets: [{
                            label: 'Body Weight (kg)',
                            data: bodyweightData.map(d => d.value),
                            borderColor: '#4361ee',
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)',
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Volume chart
            const volumeData = <?= $volumeProgressJSON ?>;
            if (volumeData.length > 0) {
                const volumeCtx = document.getElementById('volumeChart').getContext('2d');
                new Chart(volumeCtx, {
                    type: 'bar',
                    data: {
                        labels: volumeData.map(d => d.date),
                        datasets: [{
                            label: 'Total Volume (kg)',
                            data: volumeData.map(d => d.value),
                            backgroundColor: 'rgba(67, 97, 238, 0.7)',
                            borderColor: '#4361ee',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)',
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Strength progress chart
            const strengthData = <?= $strengthJSON ?>;
            if (strengthData.length > 0) {
                const strengthCtx = document.getElementById('strengthProgressChart').getContext('2d');
                new Chart(strengthCtx, {
                    type: 'line',
                    data: {
                        labels: strengthData.map(d => d.date),
                        datasets: [{
                            label: 'Bench Press (kg)',
                            data: strengthData.map(d => d.value),
                            borderColor: '#4361ee',
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)',
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Workout frequency chart
            const frequencyData = <?= $workoutFrequencyJSON ?>;
            if (frequencyData.length > 0) {
                const frequencyCtx = document.getElementById('workoutFrequencyChart').getContext('2d');
                new Chart(frequencyCtx, {
                    type: 'bar',
                    data: {
                        labels: frequencyData.map(d => d.month),
                        datasets: [{
                            label: 'Workouts per Month',
                            data: frequencyData.map(d => d.value),
                            backgroundColor: 'rgba(67, 97, 238, 0.7)',
                            borderColor: '#4361ee',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html> 