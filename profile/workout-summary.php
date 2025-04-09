<?php
// Include access control check for profile pages
require_once 'profile_access_control.php';

session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-summary.php");
    exit;
}

require_once '../assets/db_connection.php';

// Get workout ID from URL
$workout_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION["user_id"];

// Fetch workout details
$workout_query = "SELECT * FROM workouts WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $workout_query);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$workout = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$workout) {
    header("location: profile.php");
    exit;
}

// Fetch exercises and sets
$exercises_query = "SELECT 
    we.*,
    GROUP_CONCAT(
        CONCAT(es.weight, ':', es.reps, ':', es.rpe)
        ORDER BY es.set_number
        SEPARATOR ';'
    ) as sets_data
    FROM workout_exercises we
    LEFT JOIN exercise_sets es ON we.id = es.exercise_id
    WHERE we.workout_id = ? AND we.user_id = ?
    GROUP BY we.id
    ORDER BY we.exercise_order";

$stmt = mysqli_prepare($conn, $exercises_query);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$exercises_result = mysqli_stmt_get_result($stmt);

$exercises = [];
while ($row = mysqli_fetch_assoc($exercises_result)) {
    $sets = [];
    if ($row['sets_data']) {
        foreach (explode(';', $row['sets_data']) as $set_data) {
            list($weight, $reps, $rpe) = explode(':', $set_data);
            $sets[] = [
                'weight' => $weight,
                'reps' => $reps,
                'rpe' => $rpe
            ];
        }
    }
    $row['sets'] = $sets;
    $exercises[] = $row;
}

// Calculate workout statistics
$total_volume = 0;
$total_sets = 0;
$max_weight = 0;
$total_reps = 0;

foreach ($exercises as $exercise) {
    foreach ($exercise['sets'] as $set) {
        $total_volume += $set['weight'] * $set['reps'];
        $total_sets++;
        $total_reps += $set['reps'];
        $max_weight = max($max_weight, $set['weight']);
    }
}

$duration_hours = floor($workout['duration_seconds'] / 3600);
$duration_minutes = floor(($workout['duration_seconds'] % 3600) / 60);
$duration_seconds = $workout['duration_seconds'] % 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Workout Summary</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
</head>
<body>
    <div class="ws-container">
        <header class="ws-header">
            <h1 class="ws-logo">GYMVERSE</h1>
            <nav class="ws-nav">
                <a href="profile.php" class="ws-nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="current-goal.php" class="ws-nav-link"><i class="fas fa-bullseye"></i> Goals</a>
                <a href="workout-planer.php" class="ws-nav-link"><i class="fas fa-dumbbell"></i> Workouts</a>
                <a href="calories-burned.php" class="ws-nav-link"><i class="fas fa-fire"></i> Calories</a>
                <a href="nutrition.php" class="ws-nav-link"><i class="fas fa-apple-alt"></i> Nutrition</a>
                <a href="logout.php" class="ws-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <div class="ws-content">
            <div class="ws-summary-header">
                <h1><?php echo htmlspecialchars($workout['workout_name']); ?></h1>
                <div class="ws-meta">
                    <span><i class="far fa-calendar"></i> <?php echo date('F j, Y', strtotime($workout['created_at'])); ?></span>
                    <span><i class="far fa-clock"></i> <?php 
                        echo sprintf('%02d:%02d:%02d', $duration_hours, $duration_minutes, $duration_seconds); 
                    ?></span>
                    <span class="ws-rating">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fa<?php echo $i <= $workout['rating'] ? 's' : 'r'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </span>
                </div>
            </div>

            <div class="ws-stats-grid">
                <div class="ws-stat-card">
                    <div class="ws-stat-icon"><i class="fas fa-dumbbell"></i></div>
                    <div class="ws-stat-value"><?php echo number_format($total_volume); ?> kg</div>
                    <div class="ws-stat-label">Total Volume</div>
                </div>
                
                <div class="ws-stat-card">
                    <div class="ws-stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="ws-stat-value"><?php echo $total_sets; ?></div>
                    <div class="ws-stat-label">Total Sets</div>
                </div>
                
                <div class="ws-stat-card">
                    <div class="ws-stat-icon"><i class="fas fa-redo"></i></div>
                    <div class="ws-stat-value"><?php echo $total_reps; ?></div>
                    <div class="ws-stat-label">Total Reps</div>
                </div>
                
                <div class="ws-stat-card">
                    <div class="ws-stat-icon"><i class="fas fa-weight-hanging"></i></div>
                    <div class="ws-stat-value"><?php echo $max_weight; ?> kg</div>
                    <div class="ws-stat-label">Max Weight</div>
                </div>
            </div>

            <div class="ws-exercises-section">
                <h2>Exercises</h2>
                <?php foreach ($exercises as $exercise): ?>
                    <div class="ws-exercise-card">
                        <div class="ws-exercise-header">
                            <h3><?php echo htmlspecialchars($exercise['exercise_name']); ?></h3>
                            <?php if ($exercise['notes']): ?>
                                <div class="ws-exercise-notes">
                                    <i class="far fa-sticky-note"></i>
                                    <?php echo htmlspecialchars($exercise['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ws-sets-grid">
                            <div class="ws-set-header">
                                <span>Set</span>
                                <span>Weight</span>
                                <span>Reps</span>
                                <span>RPE</span>
                            </div>
                            <?php foreach ($exercise['sets'] as $index => $set): ?>
                                <div class="ws-set-row">
                                    <span><?php echo $index + 1; ?></span>
                                    <span><?php echo $set['weight']; ?> kg</span>
                                    <span><?php echo $set['reps']; ?></span>
                                    <span class="ws-rpe">RPE <?php echo $set['rpe']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($workout['notes']): ?>
                <div class="ws-notes-section">
                    <h2>Workout Notes</h2>
                    <div class="ws-notes-content">
                        <?php echo nl2br(htmlspecialchars($workout['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="ws-actions">
                <a href="quick-workout.php" class="ws-btn ws-btn-primary">
                    <i class="fas fa-plus"></i> Start New Workout
                </a>
                <a href="workout-planer.php" class="ws-btn ws-btn-secondary">
                    <i class="fas fa-calendar-alt"></i> Plan Next Workout
                </a>
            </div>
        </div>
    </div>
</body>
</html> 