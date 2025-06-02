<?php

require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';
require_once 'languages.php';

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
<html lang="<?= $_SESSION["language"] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?= t('stats_and_prs') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../profile/global-profile.css" rel="stylesheet">
</head>
<body>
    <div class="stats-container">
        <?php include 'sidebar.php'; ?>
        <div class="stats-column-card">
            <div class="stats-card">
                <div class="stats-card-header">
                    <h2><div class="stats-icon-heading"><i class="fas fa-chart-bar"></i></div> <?= t('exercise_popularity') ?></h2>
                </div>
                
                <?php if (empty($popular_exercises)): ?>
                <div class="stats-empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p><?= t('no_exercise_data') ?></p>
                </div>
                <?php else: ?>
                <div class="stats-exercise-stats">
                    <div class="stats-total-count">
                        <div class="stats-count-label"><?= t('total_repetitions') ?></div>
                        <div class="stats-count-value"><?= number_format($total_reps) ?></div>
                    </div>
                    
                    <div class="stats-popularity-list">
                        <?php 
                        $limited_popular_exercises = array_slice($popular_exercises, 0, 6);
                        foreach ($limited_popular_exercises as $index => $exercise): 
                        ?>
                            <?php 
                                $percentage = ($exercise['total_reps'] / $total_reps) * 100;
                            ?>
                            <div class="stats-popularity-item">
                                <div class="stats-popularity-info">
                                    <div class="stats-exercise-name"><?= htmlspecialchars($exercise['exercise_name']) ?></div>
                                    <div class="stats-exercise-count"><?= number_format($exercise['total_reps']) ?> <?= t('reps') ?></div>
                                </div>
                                <div class="stats-popularity-bar-container">
                                    <div class="stats-popularity-bar" style="width: <?= number_format($percentage, 0) ?>%"></div>
                                </div>
                                <div class="stats-percentage"><?= number_format($percentage, 1) ?>%</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-header">
                    <h2><div class="stats-icon-heading"><i class="fas fa-trophy"></i></div> <?= t('personal_records') ?></h2>
                </div>
                
                <?php if (empty($exercise_prs)): ?>
                <div class="stats-empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p><?= t('no_personal_records') ?></p>
                </div>
                <?php else: ?>
                <ul class="stats-pr-list">
                    <?php 
                    $limited_exercise_prs = array_slice($exercise_prs, 0, 10);
                    foreach($limited_exercise_prs as $exercise => $prs): 
                    ?>
                        <li class="stats-pr-item">
                            <div class="stats-pr-exercise"><?= htmlspecialchars($exercise) ?></div>
                            <div class="stats-pr-stats">
                                <?php if (isset($prs['single_rep']) && $prs['single_rep'] !== null): ?>
                                <div>
                                    <div class="stats-pr-value"><?= $prs['single_rep']['weight'] ?? 0 ?> <?= t('kg') ?> × 1</div>
                                    <div class="stats-pr-date"><?= !empty($prs['single_rep']['date']) ? date('M d, Y', strtotime($prs['single_rep']['date'])) : 'N/A' ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($prs['max_volume']) && $prs['max_volume'] !== null): ?>
                                <div>
                                    <div class="stats-pr-value"><?= $prs['max_volume']['weight'] ?? 0 ?> <?= t('kg') ?> × <?= $prs['max_volume']['reps'] ?? 0 ?></div>
                                    <div class="stats-pr-date"><?= !empty($prs['max_volume']['date']) ? date('M d, Y', strtotime($prs['max_volume']['date'])) : t('na') ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-header">
                    <h2><div class="stats-icon-heading"><i class="fas fa-fire"></i></div> <?= t('strength_leaders') ?></h2>
                </div>
                
                <?php if (empty($strength_leaders)): ?>
                <div class="stats-empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <p><?= t('no_strength_data') ?></p>
                </div>
                <?php else: ?>
                <div class="stats-top-exercises">
                    <?php 
                    $limited_strength_leaders = array_slice($strength_leaders, 0, 10);
                    foreach ($limited_strength_leaders as $index => $exercise): 
                    ?>
                    <div class="stats-exercise-row">
                        <div>
                            <span class="stats-rank">#<?= $index + 1 ?></span>
                            <span class="stats-exercise-name"><?= htmlspecialchars($exercise['exercise_name']) ?></span>
                        </div>
                        <div class="stats-pr-value"><?= $exercise['max_weight'] ?> <?= t('kg') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 