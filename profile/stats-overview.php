<?php
session_start();
require_once '../assets/db_connection.php';

$user_id = $_SESSION['user_id'] ?? 1;
$current_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$current_user->execute([$user_id]);
$user = $current_user->fetch(PDO::FETCH_ASSOC);

$measurements = $pdo->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY measurement_date DESC LIMIT 1");
$measurements->execute([$user_id]);
$current_measurements = $measurements->fetch(PDO::FETCH_ASSOC);

$prs_query = $pdo->prepare("
    SELECT exercise_name, pr_type, weight, pr_date 
    FROM exercise_prs 
    WHERE user_id = ?
");
$prs_query->execute([$user_id]);
$prs_data = $prs_query->fetchAll(PDO::FETCH_ASSOC);

$all_prs = [];
foreach($prs_data as $pr) {
    $all_prs[$pr['exercise_name']][$pr['pr_type']] = $pr;
}

$focus_metrics = $pdo->prepare("SELECT * FROM user_focus_metrics WHERE user_id = ?");
$focus_metrics->execute([$user_id]);
$focus = $focus_metrics->fetch(PDO::FETCH_ASSOC);

$milestones = $pdo->prepare("SELECT * FROM user_milestones WHERE user_id = ? ORDER BY milestone_date DESC");
$milestones->execute([$user_id]);
$user_milestones = $milestones->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack | Your Personal Progress Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary: #5271ff;
            --primary-dark: #3a57e8;
            --secondary: #4ecdc4;
            --dark: #1f2d3d;
            --light: #f7f9fc;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(31, 45, 61, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f0f4f8;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1, h2, h3 {
            font-weight: 600;
        }

        h1 {
            color: var(--dark);
            font-size: 28px;
        }

        h2 {
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h3 {
            font-size: 18px;
            color: #455a64;
        }

        .icon-heading {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            background-color: rgba(82, 113, 255, 0.12);
            border-radius: 50%;
            color: var(--primary);
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(31, 45, 61, 0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .progress-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-text {
            position: absolute;
            text-align: center;
        }

        .progress-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-label {
            font-size: 14px;
            color: #78909c;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-label {
            font-size: 14px;
            color: #78909c;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }

        .pr-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .pr-icon {
            font-size: 18px;
            margin-right: 15px;
        }

        .all-time {
            color: #f1c40f;
        }

        .recent {
            color: var(--success);
        }

        .volume {
            color: var(--primary);
        }

        .pr-details {
            flex: 1;
        }

        .pr-name {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .pr-date {
            font-size: 12px;
            color: #78909c;
        }

        .pr-weight {
            font-weight: 600;
            font-size: 18px;
        }

        .timeline {
            position: relative;
            margin: 20px 0;
            padding-left: 30px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 2px;
            background: #ddd;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-marker {
            position: absolute;
            left: -39px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 10px;
            box-shadow: 0 0 0 4px white;
        }

        .timeline-date {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .timeline-content {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
        }

        .timeline-achievement {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .timeline-achievement i {
            margin-right: 8px;
            color: var(--primary);
        }

        .progress-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .progress-table th {
            text-align: left;
            font-weight: 600;
            padding: 10px;
            color: #78909c;
            font-size: 14px;
        }

        .progress-table td {
            padding: 15px 10px;
            background: #f8fafc;
        }

        .progress-table tr td:first-child {
            border-radius: 8px 0 0 8px;
            font-weight: 500;
        }

        .progress-table tr td:last-child {
            border-radius: 0 8px 8px 0;
            font-weight: 600;
        }

        .change-positive {
            color: var(--success);
        }

        .change-negative {
            color: var(--danger);
        }

        .muscle-map-container {
            position: relative;
            height: 350px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
        }

        .muscle-silhouette {
            height: 100%;
        }

        .muscle-measurements {
            margin-top: 20px;
        }

        .muscle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .muscle-name {
            font-weight: 500;
        }

        .muscle-progress {
            font-weight: 600;
            color: var(--success);
        }

        .focus-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .focus-metric {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            border-radius: 8px;
            padding: 20px;
            color: white;
            text-align: center;
        }

        .focus-label {
            font-size: 14px;
            opacity: 0.85;
            margin-bottom: 5px;
        }

        .focus-value {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .focus-change {
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 100px;
            display: inline-block;
        }

        .chart-container {
            height: 250px;
            margin-top: 20px;
            position: relative;
        }

        .lift-chart-row {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }

        .lift-chart {
            flex: 3;
        }

        .lift-stats {
            flex: 2;
        }

        .thumbnail-container {
            position: relative;
            margin-top: 15px;
            overflow: hidden;
            border-radius: 8px;
        }

        .video-thumbnail {
            width: 100%;
            border-radius: 8px;
            transition: transform 0.3s;
        }

        .thumbnail-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px 15px 15px;
            color: white;
        }

        .thumbnail-btn {
            display: inline-block;
            background: rgba(255,255,255,0.25);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
            transition: background 0.2s;
        }

        .thumbnail-btn:hover {
            background: rgba(255,255,255,0.4);
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .lift-chart-row {
                flex-direction: column;
            }
            
            .focus-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    <div class="container">
        <header>
            <h1>Welcome, <?= htmlspecialchars($user['first_name'] ?? 'User') ?>!</h1>
            <div>
                <span>Last updated: <?= date('F j, Y') ?></span>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h2><div class="icon-heading"><i class="fas fa-chart-line"></i></div> Your Fitness Snapshot</h2>
            </div>
            
            <div class="grid">
                <div>
                    <h3>Current Weight vs Goal</h3>
                    <div class="progress-container">
                        <?php
                        $current_weight = $current_measurements['weight'] ?? 0;
                        $goal_weight = $user['goal_weight'] ?? $current_weight;
                        $initial_weight = $user['initial_weight'] ?? $current_weight;
                        
                        $progress = 0;
                        if ($goal_weight != $initial_weight) {
                            $progress = (($initial_weight - $current_weight) / ($initial_weight - $goal_weight)) * 100;
                        }
                        $progress = max(0, min(100, $progress));
                        ?>
                        <svg class="progress-ring" width="200" height="200">
                            <circle cx="100" cy="100" r="85" stroke="#e6e9f0" stroke-width="12" fill="none" />
                            <circle id="progressCircle" cx="100" cy="100" r="85" stroke="#5271ff" stroke-width="12" 
                                  stroke-dasharray="534" 
                                  stroke-dashoffset="<?= 534 - (534 * $progress) / 100 ?>"/>
                        </svg>
                        <div class="progress-text">
                            <div class="progress-value"><?= $current_weight ?> lbs</div>
                            <div class="progress-label"><?= number_format($progress, 0) ?>% to goal</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3>Body Recomposition</h3>
                    <div class="chart-container">
                        <canvas id="bodyRecompChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><div class="icon-heading"><i class="fas fa-dumbbell"></i></div> Your Power Lifts</h2>
            </div>
            
            <?php foreach(['Squat', 'Bench', 'Deadlift'] as $lift): ?>
            <div class="lift-chart-row">
                <div class="lift-chart">
                    <h3><?= $lift ?> Progress</h3>
                    <div class="chart-container">
                        <canvas id="<?= strtolower($lift) ?>Chart"></canvas>
                    </div>
                </div>
                <div class="lift-stats">
                    <h3>PR Breakdown</h3>
                    <?php foreach(['all_time', 'recent', 'volume'] as $type): ?>
                    <div class="pr-item">
                        <div class="pr-icon <?= $type ?>">
                            <?= match($type) {
                                'all_time' => '★',
                                'recent' => '↑',
                                'volume' => '🔥'
                            } ?>
                        </div>
                        <div class="pr-details">
                            <div class="pr-name"><?= ucfirst(str_replace('_', ' ', $type)) ?> PR</div>
                            <?php if(isset($all_prs[$lift][$type])): ?>
                            <div class="pr-date"><?= date('M Y', strtotime($all_prs[$lift][$type]['pr_date'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="pr-weight">
                            <?= isset($all_prs[$lift][$type]) ? $all_prs[$lift][$type]['weight'].' lbs' : 'N/A' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><div class="icon-heading"><i class="fas fa-bullseye"></i></div> Your Focus Metrics</h2>
            </div>
            <div class="focus-metrics">
                <?php foreach(['metric_1', 'metric_2', 'metric_3'] as $metric): ?>
                <div class="focus-metric">
                    <?php if(!empty($focus[$metric])): 
                        $metric_name = strtolower($focus[$metric]);
                    ?>
                    <div class="focus-label"><?= ucfirst(str_replace('_', ' ', $focus[$metric])) ?></div>
                    <div class="focus-value">
                        <?php if(isset($current_measurements[$metric_name])): ?>
                            <?= $metric_name == 'body_fat' ? $current_measurements[$metric_name].'%' : $current_measurements[$metric_name].' in' ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><div class="icon-heading"><i class="fas fa-trophy"></i></div> Your Milestones</h2>
            </div>
            <div class="timeline">
                <?php foreach($user_milestones as $milestone): ?>
                <div class="timeline-item">
                    <div class="timeline-marker">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-date">
                        <?= date('F j, Y', strtotime($milestone['milestone_date'])) ?>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-achievement">
                            <i class="fas fa-medal"></i>
                            <strong><?= htmlspecialchars($milestone['milestone_title']) ?></strong>
                        </div>
                        <p><?= htmlspecialchars($milestone['milestone_description']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bodyRecompCtx = document.getElementById('bodyRecompChart').getContext('2d');
            const bodyRecompChart = new Chart(bodyRecompCtx, {
                type: 'line',
                data: {
                    labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [
                        {
                            label: 'Weight (lbs)',
                            data: [195, 192, 190, 188, 185, 184, 185],
                            borderColor: '#5271ff',
                            tension: 0.3,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Body Fat %',
                            data: [19, 18.2, 17.5, 16.3, 15.4, 14.8, 14.5],
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Weight (lbs)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Body Fat %'
                            },
                            min: 0,
                            max: 25
                        }
                    }
                }
            });
            
            const squatCtx = document.getElementById('squatChart').getContext('2d');
            const squatChart = new Chart(squatCtx, {
                type: 'line',
                data: {
                    labels: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [{
                        label: '1RM (lbs)',
                        data: [275, 285, 295, 300, 305, 315],
                        borderColor: '#5271ff',
                        backgroundColor: 'rgba(82, 113, 255, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Weight (lbs)'
                            },
                            min: 250
                        }
                    }
                }
            });
            
            const benchCtx = document.getElementById('benchChart').getContext('2d');
            const benchChart = new Chart(benchCtx, {
                type: 'line',
                data: {
                    labels: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [{
                        label: '1RM (lbs)',
                        data: [225, 230, 245, 245, 240, 245],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Weight (lbs)'
                            },
                            min: 200
                        }
                    }
                }
            });
            
            const deadliftCtx = document.getElementById('deadliftChart').getContext('2d');
            const deadliftChart = new Chart(deadliftCtx, {
                type: 'line',
                data: {
                    labels: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [{
                        label: '1RM (lbs)',
                        data: [365, 375, 385, 405, 400, 405],
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Weight (lbs)'
                            },
                            min: 350
                        }
                    }
                }
            });
        });

        function toggleMilestone(id) {
            const content = document.getElementById(`milestone-${id}`);
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        }
        
        function updateFocusMetrics() {
            alert('Your focus metrics have been updated!');
        }
        
        function compareFormVideos(exerciseId) {
            alert('Opening form comparison for exercise #' + exerciseId);
        }
    </script>
</body>
</html>