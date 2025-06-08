<?php

require_once 'profile_access_control.php';
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-details.php");
    exit;
}

require_once '../assets/db_connection.php';
require_once 'workout_functions.php';
require_once 'languages.php';

$user_id = $_SESSION["user_id"];
$workout_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

if (!$workout_id) {
    header("location: workout-history.php");
    exit;
}

ensureTablesExist($conn);

$debug_check_sets = "SELECT COUNT(*) as count FROM workout_sets ws 
                     INNER JOIN workout_exercises we ON ws.workout_exercise_id = we.id 
                     WHERE we.workout_id = ? AND we.user_id = ?";
$stmt = mysqli_prepare($conn, $debug_check_sets);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sets_count = mysqli_fetch_assoc($result)['count'];

$workout_query = "
    SELECT 
        id, name, workout_type, duration_minutes, calories_burned, 
        notes, rating, created_at, total_volume, avg_intensity,
        DATE_FORMAT(created_at, '%M %e, %Y') as formatted_date,
        DATE_FORMAT(created_at, '%h:%i %p') as formatted_time
    FROM workouts 
    WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $workout_query);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("location: workout-history.php");
    exit;
}

$workout = mysqli_fetch_assoc($result);

$exercises_query = "
    SELECT 
        we.id as exercise_id,
        we.exercise_name,
        we.exercise_order,
        COUNT(ws.id) as sets_count
    FROM workout_exercises we
    LEFT JOIN workout_sets ws ON we.id = ws.workout_exercise_id 
    WHERE we.workout_id = ? AND we.user_id = ?
    GROUP BY we.id
    ORDER BY we.exercise_order ASC";

$stmt = mysqli_prepare($conn, $exercises_query);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$exercises_result = mysqli_stmt_get_result($stmt);

$exercises = [];
while ($row = mysqli_fetch_assoc($exercises_result)) {
    $exercises[] = $row;
}

foreach ($exercises as &$exercise) {
    $sets_query = "
        SELECT 
            id, set_number, weight, reps, rpe
        FROM workout_sets 
        WHERE workout_exercise_id = ?
        ORDER BY set_number ASC";
    
    $stmt = mysqli_prepare($conn, $sets_query);
    mysqli_stmt_bind_param($stmt, "i", $exercise['exercise_id']);
    mysqli_stmt_execute($stmt);
    $sets_result = mysqli_stmt_get_result($stmt);
    
    $exercise['sets'] = [];
    $sets_array = [];
    
    while ($set = mysqli_fetch_assoc($sets_result)) {
        $sets_array[] = $set;
    }
    
    if (count($sets_array) > 1) {
        $first_set_weight = floatval($sets_array[0]['weight']);
        $second_set_weight = floatval($sets_array[1]['weight']);
        
        $is_warmup_set = ($first_set_weight < $second_set_weight * 0.8);
        
        foreach ($sets_array as $index => $set) {
            $set['is_warmup'] = ($index === 0 && $is_warmup_set) ? 1 : 0;
            $exercise['sets'][] = $set;
        }
    } else {
        foreach ($sets_array as $set) {
            $set['is_warmup'] = 0;
            $exercise['sets'][] = $set;
        }
    }
    
}

$prev_workout_query = "
    SELECT 
        id, total_volume
    FROM workouts 
    WHERE user_id = ? 
    AND id < ? 
    AND (workout_type = ? OR (? IS NULL AND workout_type IS NULL)) 
    ORDER BY created_at DESC 
    LIMIT 1";

$stmt = mysqli_prepare($conn, $prev_workout_query);
mysqli_stmt_bind_param($stmt, "iiis", $user_id, $workout_id, $workout['workout_type'], $workout['workout_type']);
mysqli_stmt_execute($stmt);
$prev_result = mysqli_stmt_get_result($stmt);
$prev_workout = mysqli_fetch_assoc($prev_result);

$volume_difference = 0;
$volume_percentage = 0;
if ($prev_workout) {
    $volume_difference = $workout['total_volume'] - $prev_workout['total_volume'];
    if ($prev_workout['total_volume'] > 0) {
        $volume_percentage = round(($volume_difference / $prev_workout['total_volume']) * 100, 1);
    }
}

$similar_workouts_query = "
    SELECT 
        id, name, 
        DATE_FORMAT(created_at, '%b %e, %Y') as workout_date
    FROM workouts 
    WHERE id != ? 
    AND user_id = ? 
    AND (workout_type = ? OR (? IS NULL AND workout_type IS NULL))
    ORDER BY created_at DESC 
    LIMIT 3";

$stmt = mysqli_prepare($conn, $similar_workouts_query);
mysqli_stmt_bind_param($stmt, "iiss", $workout_id, $user_id, $workout['workout_type'], $workout['workout_type']);
mysqli_stmt_execute($stmt);
$similar_result = mysqli_stmt_get_result($stmt);

$similar_workouts = [];
while ($similar = mysqli_fetch_assoc($similar_result)) {
    $similar_workouts[] = $similar;
}

?>

<!DOCTYPE html>
<html lang="<?= $_SESSION["language"] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('workout_details') ?> - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="global-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>
<body>
    <div class="wd-dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="wd-main-content">
            <div class="wd-page-header">
                <h1 class="wd-page-title"><?= t('workout_details') ?></h1>
                <div class="wd-header-actions">
                    <a href="workout-history.php" class="wd-btn">
                        <i class="fas fa-arrow-left"></i> <?= t('back') ?>
                    </a>
                </div>
            </div>

            <div class="wd-workout-details-container">
                <div class="wd-workout-main">
                    <div class="wd-workout-header">
                        <h2 class="wd-workout-title">
                            <?= htmlspecialchars($workout['name']) ?>
                            <i class="fas fa-dumbbell"></i>
                        </h2>
                        <div class="wd-workout-meta">
                            <?= $workout['formatted_date'] ?> • <?= $workout['formatted_time'] ?>
                        </div>
                        
                        <div class="wd-workout-stats">
                            <div class="wd-stat-card">
                                <div class="wd-stat-value"><?= $workout['duration_minutes'] ?> <?= t('min') ?></div>
                                <div class="wd-stat-label"><?= t('duration') ?></div>
                            </div>
                            <div class="wd-stat-card">
                                <div class="wd-stat-value"><?= number_format($workout['total_volume']) ?> <?= t('kg') ?></div>
                                <div class="wd-stat-label"><?= t('volume') ?></div>
                            </div>
                            <div class="wd-stat-card">
                                <div class="wd-stat-value"><?= $workout['calories_burned'] ?> kcal</div>
                                <div class="wd-stat-label"><?= t('calories') ?></div>
                            </div>
                            <div class="wd-stat-card">
                                <div class="wd-stat-value"><?= count($exercises) > 0 ? array_sum(array_column($exercises, 'sets_count')) : 0 ?></div>
                                <div class="wd-stat-label"><?= t('sets') ?></div>
                            </div>
                        </div>
                        
                        <div class="wd-rating">
                            <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>
                        </div>
                        
                        <?php if (!empty($workout['notes'])): ?>
                        <div class="wd-workout-notes">
                            <div class="wd-notes-label"><?= t('notes') ?></div>
                            <p><?= nl2br(htmlspecialchars($workout['notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wd-exercise-section">
                        <?php foreach ($exercises as $index => $exercise): ?>
                        <div class="wd-exercise-card">
                            <div class="wd-exercise-header" onclick="toggleExercise(<?= $index ?>)">
                                <h3 class="wd-exercise-title"><?= htmlspecialchars($exercise['exercise_name']) ?></h3>
                                <i id="exercise-icon-<?= $index ?>" class="fas <?= $index === 0 ? 'fa-chevron-up' : 'fa-chevron-down' ?>"></i>
                            </div>
                            <div id="exercise-body-<?= $index ?>" class="wd-exercise-body" style="<?= $index === 0 ? '' : 'display: none;' ?>">
                                <?php if (!empty($exercise['sets'])): ?>
                                <table class="wd-sets-table">
                                    <thead>
                                        <tr>
                                            <th><?= t('set') ?></th>
                                            <th><?= t('weight') ?></th>
                                            <th><?= t('reps') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exercise['sets'] as $set): ?>
                                        <tr <?= $set['is_warmup'] ? 'class="wd-warmup-set"' : '' ?>>
                                            <td><?= $set['set_number'] ?></td>
                                            <td><?= $set['weight'] ?> <?= t('kg') ?></td>
                                            <td><?= $set['reps'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p><?= t('no_sets_recorded') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($exercises)): ?>
                        <div class="wd-exercise-card">
                            <div class="wd-exercise-body" style="text-align: center; padding: 24px;">
                                <p><?= t('no_exercises_recorded') ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="wd-workout-sidebar">
                    <div class="wd-sidebar-section">
                        <h3 class="wd-section-title"><?= t('previous_performance') ?></h3>
                        
                        <div class="wd-volume-comparison">
                            <div class="wd-volume-box">
                                <div class="wd-volume-label"><?= t('total_volume') ?></div>
                                <div class="wd-volume-value"><?= number_format($workout['total_volume']) ?> <?= t('kg') ?></div>
                            </div>
                            
                            <?php if ($prev_workout): ?>
                            <div class="wd-volume-box">
                                <div class="wd-volume-label"><?= t('previous') ?></div>
                                <div class="wd-volume-value"><?= number_format($prev_workout['total_volume']) ?> <?= t('kg') ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($prev_workout): ?>
                        <div class="wd-volume-difference <?= $volume_difference >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fas fa-<?= $volume_difference >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= number_format(abs($volume_difference)) ?> <?= t('kg') ?> (<?= $volume_percentage ?>%)
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($similar_workouts)): ?>
                        <h4 style="margin-top: 20px; margin-bottom: 12px;"><?= t('similar_workouts') ?></h4>
                        <ul class="wd-similar-list">
                            <?php foreach ($similar_workouts as $similar): ?>
                            <li class="wd-similar-item" onclick="window.location.href='workout-details.php?id=<?= $similar['id'] ?>'">
                                <div class="wd-similar-title"><?= htmlspecialchars($similar['name']) ?></div>
                                <div class="wd-similar-date"><?= $similar['workout_date'] ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wd-sidebar-section">
                        <h3 class="wd-section-title"><?= t('export_data') ?></h3>
                        <button class="wd-btn wd-pdf-btn" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleExercise(index) {
            const body = document.getElementById(`exercise-body-${index}`);
            const icon = document.getElementById(`exercise-icon-${index}`);
            
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                body.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        function loadJsPDF() {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js";
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        async function exportToPDF() {
            try {
                if (typeof window.jspdf === 'undefined') {
                    await loadJsPDF();
                }
                
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                let y = 20;
                
                doc.setFontSize(22);
                doc.text('<?= addslashes($workout['name']) ?>', 105, y, { align: 'center' });
                y += 10;
                
                doc.setFontSize(12);
                doc.text('<?= $workout['formatted_date'] ?> • <?= $workout['formatted_time'] ?>', 105, y, { align: 'center' });
                y += 20;
                
                doc.setFontSize(16);
                doc.text('<?= addslashes(t('workout_stats')) ?>', 20, y);
                y += 10;
                
                doc.setFontSize(12);
                doc.text('<?= addslashes(t('duration')) ?>: <?= $workout['duration_minutes'] ?> <?= addslashes(t('min')) ?>', 20, y);
                y += 8;
                
                doc.text('<?= addslashes(t('total_volume')) ?>: <?= number_format($workout['total_volume']) ?> <?= addslashes(t('kg')) ?>', 20, y);
                y += 8;
                
                doc.text('<?= addslashes(t('calories_burned')) ?>: <?= $workout['calories_burned'] ?> kcal', 20, y);
                y += 8;
                
                doc.text('<?= addslashes(t('rating')) ?>: <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>', 20, y);
                y += 20;
                
                doc.setFontSize(16);
                doc.text('<?= addslashes(t('exercises')) ?>', 20, y);
                y += 10;
                
                <?php foreach ($exercises as $index => $exercise): ?>
                if (y > 250) {
                    doc.addPage();
                    y = 20;
                }
                
                doc.setFontSize(14);
                doc.text('<?= $index + 1 ?>. <?= addslashes($exercise['exercise_name']) ?>', 20, y);
                y += 8;
                
                <?php if (!empty($exercise['sets'])): ?>
                <?php foreach ($exercise['sets'] as $set): ?>
                doc.setFontSize(10);
                doc.text('<?= addslashes(t('set')) ?> <?= $set['set_number'] ?><?= $set['is_warmup'] ? ' (' . addslashes(t('warmup')) . ')' : '' ?>: <?= $set['weight'] ?> <?= addslashes(t('kg')) ?> × <?= $set['reps'] ?> <?= addslashes(t('reps')) ?> (RPE: <?= $set['rpe'] ?>)', 30, y);
                y += 6;
                
                if (y > 270) {
                    doc.addPage();
                    y = 20;
                }
                <?php endforeach; ?>
                <?php else: ?>
                doc.setFontSize(10);
                doc.text('<?= addslashes(t('no_sets_recorded')) ?>', 30, y);
                y += 8;
                <?php endif; ?>
                
                y += 4; 
                <?php endforeach; ?>
                
                doc.save('workout_<?= $workout_id ?>.pdf');
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('<?= addslashes(t('error_generating_pdf')) ?>');
            }
        }
        
        loadJsPDF().catch(err => console.warn('Failed to preload jsPDF:', err));
    </script>
</body>
</html> 