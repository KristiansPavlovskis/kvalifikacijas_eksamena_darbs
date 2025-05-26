<?php
session_start();
require_once '../assets/db_connection.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$current_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$current_user->execute([$user_id]);
$user = $current_user->fetch(PDO::FETCH_ASSOC);

$current_weight = $user['weight'] ?? 0;
$goal_weight = $user['goal_weight'] ?? $current_weight;
$initial_weight = $user['initial_weight'] ?? $current_weight;

$exercise_frequency = $pdo->prepare("
    SELECT 
        exercise_name,
        SUM(total_reps) as total_reps
    FROM workout_exercises
    WHERE user_id = ?
    GROUP BY exercise_name
    ORDER BY total_reps DESC
    LIMIT 10
");
$exercise_frequency->execute([$user_id]);
$popular_exercises = $exercise_frequency->fetchAll(PDO::FETCH_ASSOC);

$total_exercises_query = $pdo->prepare("
    SELECT SUM(total_reps) as total
    FROM workout_exercises
    WHERE user_id = ?
");
$total_exercises_query->execute([$user_id]);
$total_reps = $total_exercises_query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$weight_progress = 0;
if ($goal_weight != $initial_weight) {
    $weight_progress = (($initial_weight - $current_weight) / ($initial_weight - $goal_weight)) * 100;
}
$weight_progress = max(0, min(100, $weight_progress));

$exercise_prs = [];

$unique_exercises = $pdo->prepare("
    SELECT DISTINCT exercise_name
    FROM workout_exercises
    WHERE user_id = ?
    ORDER BY exercise_name
");
$unique_exercises->execute([$user_id]);
$exercises = $unique_exercises->fetchAll(PDO::FETCH_COLUMN);

foreach ($exercises as $exercise_name) {
    $max_single = $pdo->prepare("
        SELECT 
            ws.weight, 
            ws.reps,
            we.exercise_name,
            w.created_at as date
        FROM workout_sets ws
        JOIN workout_exercises we ON ws.workout_exercise_id = we.id
        JOIN workouts w ON we.workout_id = w.id
        WHERE we.exercise_name = ? 
        AND we.user_id = ?
        AND ws.reps = 1
        AND ws.weight > 0
        ORDER BY ws.weight DESC
        LIMIT 1
    ");
    $max_single->execute([$exercise_name, $user_id]);
    $single_pr = $max_single->fetch(PDO::FETCH_ASSOC);

    $max_volume = $pdo->prepare("
        SELECT 
            ws.weight, 
            ws.reps,
            we.exercise_name,
            (ws.weight * ws.reps) as volume,
            w.created_at as date
        FROM workout_sets ws
        JOIN workout_exercises we ON ws.workout_exercise_id = we.id
        JOIN workouts w ON we.workout_id = w.id
        WHERE we.exercise_name = ? 
        AND we.user_id = ?
        AND ws.weight > 0
        AND ws.reps > 0
        ORDER BY (ws.weight * ws.reps) DESC
        LIMIT 1
    ");
    $max_volume->execute([$exercise_name, $user_id]);
    $volume_pr = $max_volume->fetch(PDO::FETCH_ASSOC);

    if ($single_pr || $volume_pr) {
        $exercise_prs[$exercise_name] = [
            'single_rep' => $single_pr ?: null,
            'max_volume' => $volume_pr ?: null
        ];
    }
}

$top_exercises = $pdo->prepare("
    SELECT 
        we.exercise_name,
        MAX(ws.weight) as max_weight
    FROM workout_sets ws
    JOIN workout_exercises we ON ws.workout_exercise_id = we.id
    WHERE we.user_id = ?
    GROUP BY we.exercise_name
    ORDER BY max_weight DESC
    LIMIT 5
");
$top_exercises->execute([$user_id]);
$strength_leaders = $top_exercises->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Stats & PRs</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        :root {
            --bg-color: #1a1a1a;
            --card-bg: #242424;
            --text-color: #e0e0e0;
            --text-muted: #a0a0a0;
            --border-color: #333333;
            --hover-bg: #2a2a2a;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
              
        .column-card {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 25px;
            width: 100%;
            padding: 20px;
        }
        
        h1, h2, h3 {
            font-weight: 600;
            color: var(--text-color);
        }

        h1 {
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
        }

        .icon-heading {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            background-color: rgba(230, 22, 22, 0.12);
            border-radius: 50%;
            color: var(--primary-color);
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 25px;
            flex: 1;
            min-width: 350px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
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
            margin: 20px auto;
        }

        .progress-ring circle {
            fill: none;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease-in-out;
        }

        #progressCircle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 1s ease;
        }

        .progress-text {
            position: absolute;
            text-align: center;
        }

        .progress-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-label {
            font-size: 14px;
            color: #455a64;
        }

        .weight-stats {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .weight-items {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-top: 20px;
        }

        .weight-item {
            text-align: center;
            background-color: var(--bg-color);
            padding: 15px;
            border-radius: 8px;
            min-width: 120px;
            border: 1px solid var(--border-color);
        }

        .weight-label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .weight-value {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-color);
        }

        .pr-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .pr-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }

        .pr-item:hover {
            background-color: var(--hover-bg);
        }

        .pr-item:last-child {
            border-bottom: none;
        }

        .pr-exercise {
            font-weight: 500;
        }

        .pr-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pr-value {
            font-weight: 600;
            color: var(--primary-color);
        }

        .pr-date {
            font-size: 12px;
            color: var(--text-muted);
        }

        .top-exercises {
            margin-top: 20px;
        }

        .exercise-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            background-color: var(--bg-color);
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .rank {
            font-weight: 600;
            min-width: 30px;
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.4;
        }

        circle:first-child {
            stroke: var(--border-color);
        }

        .exercise-stats {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .total-count {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .count-label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .count-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .popularity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .popularity-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 12px;
            border-radius: 8px;
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }

        .popularity-item:hover {
            transform: translateY(-2px);
        }

        .popularity-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exercise-name {
            font-weight: 500;
        }

        .exercise-count {
            font-size: 14px;
            color: var(--text-muted);
        }

        .popularity-bar-container {
            width: 100%;
            height: 8px;
            background-color: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .popularity-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
            transition: width 0.6s ease-in-out;
        }

        .percentage {
            text-align: right;
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .column-card {
                flex-direction: column;
            }

            .card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="column-card">
            <div class="card">
                <div class="card-header">
                    <h2><div class="icon-heading"><i class="fas fa-chart-bar"></i></div> Exercise Popularity</h2>
                </div>
                
                <?php if (empty($popular_exercises)): ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p>No exercise data found yet. Start logging your workouts!</p>
                </div>
                <?php else: ?>
                <div class="exercise-stats">
                    <div class="total-count">
                        <div class="count-label">Total Repetitions Performed</div>
                        <div class="count-value"><?= number_format($total_reps) ?></div>
                    </div>
                    
                    <div class="popularity-list">
                        <?php foreach ($popular_exercises as $index => $exercise): ?>
                            <?php 
                                $percentage = ($exercise['total_reps'] / $total_reps) * 100;
                            ?>
                            <div class="popularity-item">
                                <div class="popularity-info">
                                    <div class="exercise-name"><?= htmlspecialchars($exercise['exercise_name']) ?></div>
                                    <div class="exercise-count"><?= number_format($exercise['total_reps']) ?> reps</div>
                                </div>
                                <div class="popularity-bar-container">
                                    <div class="popularity-bar" style="width: <?= number_format($percentage, 0) ?>%"></div>
                                </div>
                                <div class="percentage"><?= number_format($percentage, 1) ?>%</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><div class="icon-heading"><i class="fas fa-trophy"></i></div> Personal Records</h2>
                </div>
                
                <?php if (empty($exercise_prs)): ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p>No personal records found yet. Start logging your workouts to track your PRs!</p>
                </div>
                <?php else: ?>
                <ul class="pr-list">
                    <?php foreach($exercise_prs as $exercise => $prs): ?>
                        <li class="pr-item">
                            <div class="pr-exercise"><?= htmlspecialchars($exercise) ?></div>
                            <div class="pr-stats">
                                <?php if (isset($prs['single_rep']) && $prs['single_rep'] !== null): ?>
                                <div>
                                    <div class="pr-value"><?= $prs['single_rep']['weight'] ?? 0 ?> kg × 1</div>
                                    <div class="pr-date"><?= !empty($prs['single_rep']['date']) ? date('M d, Y', strtotime($prs['single_rep']['date'])) : 'N/A' ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($prs['max_volume']) && $prs['max_volume'] !== null): ?>
                                <div>
                                    <div class="pr-value"><?= $prs['max_volume']['weight'] ?? 0 ?> kg × <?= $prs['max_volume']['reps'] ?? 0 ?></div>
                                    <div class="pr-date"><?= !empty($prs['max_volume']['date']) ? date('M d, Y', strtotime($prs['max_volume']['date'])) : 'N/A' ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><div class="icon-heading"><i class="fas fa-fire"></i></div> Strength Leaders</h2>
                </div>
                
                <?php if (empty($strength_leaders)): ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p>No strength data found yet. Start logging your workouts!</p>
                </div>
                <?php else: ?>
                <div class="top-exercises">
                    <?php foreach ($strength_leaders as $index => $exercise): ?>
                    <div class="exercise-row">
                        <div>
                            <span class="rank">#<?= $index + 1 ?></span>
                            <span class="exercise-name"><?= htmlspecialchars($exercise['exercise_name']) ?></span>
                        </div>
                        <div class="pr-value"><?= $exercise['max_weight'] ?> kg</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 