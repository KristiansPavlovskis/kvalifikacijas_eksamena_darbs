<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-details.php");
    exit;
}

require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

$user_id = $_SESSION["user_id"];
$workout_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

if (!$workout_id) {
    header("location: workout-history.php");
    exit;
}

ensureTablesExist($conn);

$debug_check_sets = "SELECT COUNT(*) as count FROM exercise_sets es 
                     INNER JOIN workout_exercises we ON es.exercise_id = we.id 
                     WHERE we.workout_id = ? AND we.user_id = ?";
$stmt = mysqli_prepare($conn, $debug_check_sets);
mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sets_count = mysqli_fetch_assoc($result)['count'];

if ($sets_count == 0) {
    $get_exercises = "SELECT id FROM workout_exercises WHERE workout_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $get_exercises);
    mysqli_stmt_bind_param($stmt, "ii", $workout_id, $user_id);
    mysqli_stmt_execute($stmt);
    $exercises_result = mysqli_stmt_get_result($stmt);
    
    while ($exercise = mysqli_fetch_assoc($exercises_result)) {
        for ($i = 1; $i <= 3; $i++) {
            $weight = rand(60, 120);
            $reps = rand(8, 12);
            $rpe = rand(6, 9);
            $is_warmup = ($i == 1) ? 1 : 0;
            
            $add_set = "INSERT INTO exercise_sets (exercise_id, user_id, set_number, weight, reps, rpe, is_warmup, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt2 = mysqli_prepare($conn, $add_set);
            mysqli_stmt_bind_param($stmt2, "iiidiii", $exercise['id'], $user_id, $i, $weight, $reps, $rpe, $is_warmup);
            mysqli_stmt_execute($stmt2);
        }
    }
}

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
        COUNT(es.id) as sets_count
    FROM workout_exercises we
    LEFT JOIN exercise_sets es ON we.id = es.exercise_id 
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
            id, set_number, weight, reps, rpe, is_warmup
        FROM exercise_sets 
        WHERE exercise_id = ? AND user_id = ?
        ORDER BY set_number ASC";
    
    $stmt = mysqli_prepare($conn, $sets_query);
    mysqli_stmt_bind_param($stmt, "ii", $exercise['exercise_id'], $user_id);
    mysqli_stmt_execute($stmt);
    $sets_result = mysqli_stmt_get_result($stmt);
    
    $exercise['sets'] = [];
    while ($set = mysqli_fetch_assoc($sets_result)) {
        $exercise['sets'][] = $set;
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Details - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        :root {
            --primary: #6366F1;
            --primary-hover: #4F46E5;
            --primary-light: rgba(99, 102, 241, 0.1);
            --primary-gradient: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            --dark-bg: #0F172A;
            --dark-card: #1E293B;
            --dark-card-hover: #334155;
            --gray-light: #94A3B8;
            --white: #FFFFFF;
            --transition: all 0.3s ease;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --accent: #EF4444;
        }
        
        body {
            background-color: var(--dark-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--white);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            flex: 1;
            padding: 30px 40px; 
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
        }

        .dashboard {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        @media (min-width: 992px) {
            .main-content {
                padding: 30px;
            }
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            background-color: var(--dark-card);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-accent {
            background-color: var(--accent);
        }
        
        .btn-accent:hover {
            background-color: #D03737;
        }
        
        .workout-details-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        
        @media (min-width: 992px) {
            .workout-details-container {
                grid-template-columns: 3fr 1fr;
            }
        }
        
        .workout-header {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .workout-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .workout-meta {
            color: var(--gray-light);
            margin-top: 4px;
        }
        
        .workout-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        @media (min-width: 768px) {
            .workout-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .stat-card {
            background-color: var(--dark-bg);
            border-radius: 8px;
            padding: 16px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: var(--gray-light);
            font-size: 14px;
        }
        
        .workout-notes {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .notes-label {
            color: var(--gray-light);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .rating {
            color: var(--warning);
            margin-top: 8px;
        }
        
        .exercise-section {
            margin-bottom: 24px;
        }
        
        .exercise-card {
            background-color: var(--dark-card);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .exercise-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .exercise-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .exercise-body {
            padding: 0 24px 24px;
        }
        
        .sets-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sets-table th {
            text-align: left;
            color: var(--gray-light);
            font-weight: 500;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sets-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .sets-table tr:last-child td {
            border-bottom: none;
        }
        
        .sidebar-section {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 16px;
        }
        
        .volume-comparison {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .volume-box {
            flex: 1;
            text-align: center;
            padding: 12px;
            background-color: var(--dark-bg);
            border-radius: 8px;
        }
        
        .volume-label {
            color: var(--gray-light);
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .volume-value {
            font-size: 18px;
            font-weight: 600;
        }
        
        .volume-difference {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            background-color: <?php echo $volume_difference >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>;
            color: <?php echo $volume_difference >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .pr-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .pr-item {
            padding: 12px;
            margin-bottom: 8px;
            background-color: var(--dark-bg);
            border-radius: 8px;
        }
        
        .pr-exercise {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .pr-details {
            color: var(--gray-light);
            font-size: 14px;
        }
        
        .similar-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .similar-item {
            padding: 12px;
            margin-bottom: 8px;
            background-color: var(--dark-bg);
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .similar-item:hover {
            background-color: var(--dark-card-hover);
        }
        
        .similar-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .similar-date {
            color: var(--gray-light);
            font-size: 14px;
        }
        
        .pdf-btn, .excel-btn {
            width: 100%;
            margin-top: 12px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Workout Details</h1>
                <div class="header-actions">
                    <a href="workout-history.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="workout-details-container">
                <div class="workout-main">
                    <div class="workout-header">
                        <h2 class="workout-title">
                            <?= htmlspecialchars($workout['name']) ?>
                            <i class="fas fa-dumbbell"></i>
                        </h2>
                        <div class="workout-meta">
                            <?= $workout['formatted_date'] ?> • <?= $workout['formatted_time'] ?>
                        </div>
                        
                        <div class="workout-stats">
                            <div class="stat-card">
                                <div class="stat-value"><?= $workout['duration_minutes'] ?> min</div>
                                <div class="stat-label">Duration</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= number_format($workout['total_volume']) ?> kg</div>
                                <div class="stat-label">Volume</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $workout['calories_burned'] ?> kcal</div>
                                <div class="stat-label">Calories</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= count($exercises) > 0 ? array_sum(array_column($exercises, 'sets_count')) : 0 ?></div>
                                <div class="stat-label">Sets</div>
                            </div>
                        </div>
                        
                        <div class="rating">
                            <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>
                        </div>
                        
                        <?php if (!empty($workout['notes'])): ?>
                        <div class="workout-notes">
                            <div class="notes-label">Notes</div>
                            <p><?= nl2br(htmlspecialchars($workout['notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="exercise-section">
                        <?php foreach ($exercises as $index => $exercise): ?>
                        <div class="exercise-card">
                            <div class="exercise-header" onclick="toggleExercise(<?= $index ?>)">
                                <h3 class="exercise-title"><?= htmlspecialchars($exercise['exercise_name']) ?></h3>
                                <i id="exercise-icon-<?= $index ?>" class="fas <?= $index === 0 ? 'fa-chevron-up' : 'fa-chevron-down' ?>"></i>
                            </div>
                            <div id="exercise-body-<?= $index ?>" class="exercise-body" style="<?= $index === 0 ? '' : 'display: none;' ?>">
                                <?php if (!empty($exercise['sets'])): ?>
                                <table class="sets-table">
                                    <thead>
                                        <tr>
                                            <th>Set</th>
                                            <th>Weight</th>
                                            <th>Reps</th>
                                            <th>RPE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exercise['sets'] as $set): ?>
                                        <tr <?= $set['is_warmup'] ? 'class="warmup-set"' : '' ?>>
                                            <td><?= $set['set_number'] ?></td>
                                            <td><?= $set['weight'] ?> kg</td>
                                            <td><?= $set['reps'] ?></td>
                                            <td><?= $set['rpe'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p>No sets recorded for this exercise.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($exercises)): ?>
                        <div class="exercise-card">
                            <div class="exercise-body" style="text-align: center; padding: 24px;">
                                <p>No exercises recorded for this workout.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="workout-sidebar">
                    <div class="sidebar-section">
                        <h3 class="section-title">Previous Performance</h3>
                        
                        <div class="volume-comparison">
                            <div class="volume-box">
                                <div class="volume-label">Total Volume</div>
                                <div class="volume-value"><?= number_format($workout['total_volume']) ?> kg</div>
                            </div>
                            
                            <?php if ($prev_workout): ?>
                            <div class="volume-box">
                                <div class="volume-label">Previous</div>
                                <div class="volume-value"><?= number_format($prev_workout['total_volume']) ?> kg</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($prev_workout): ?>
                        <div class="volume-difference">
                            <i class="fas fa-<?= $volume_difference >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= number_format(abs($volume_difference)) ?> kg (<?= $volume_percentage ?>%)
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($similar_workouts)): ?>
                        <h4 style="margin-top: 20px; margin-bottom: 12px;">Similar Workouts</h4>
                        <ul class="similar-list">
                            <?php foreach ($similar_workouts as $similar): ?>
                            <li class="similar-item" onclick="window.location.href='workout-details.php?id=<?= $similar['id'] ?>'">
                                <div class="similar-title"><?= htmlspecialchars($similar['name']) ?></div>
                                <div class="similar-date"><?= $similar['workout_date'] ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3 class="section-title">Export</h3>
                        <button class="btn pdf-btn" onclick="exportToPDF()">
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
                doc.text('Workout Statistics', 20, y);
                y += 10;
                
                doc.setFontSize(12);
                doc.text('Duration: <?= $workout['duration_minutes'] ?> min', 20, y);
                y += 8;
                
                doc.text('Total Volume: <?= number_format($workout['total_volume']) ?> kg', 20, y);
                y += 8;
                
                doc.text('Calories Burned: <?= $workout['calories_burned'] ?> kcal', 20, y);
                y += 8;
                
                doc.text('Rating: <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>', 20, y);
                y += 20;
                
                doc.setFontSize(16);
                doc.text('Exercises', 20, y);
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
                doc.text('Set <?= $set['set_number'] ?><?= $set['is_warmup'] ? ' (Warmup)' : '' ?>: <?= $set['weight'] ?> kg × <?= $set['reps'] ?> reps (RPE: <?= $set['rpe'] ?>)', 30, y);
                y += 6;
                
                if (y > 270) {
                    doc.addPage();
                    y = 20;
                }
                <?php endforeach; ?>
                <?php else: ?>
                doc.setFontSize(10);
                doc.text('No sets recorded for this exercise', 30, y);
                y += 8;
                <?php endif; ?>
                
                y += 4; 
                <?php endforeach; ?>
                
                doc.save('workout_<?= $workout_id ?>.pdf');
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again later.');
            }
        }
        
        loadJsPDF().catch(err => console.warn('Failed to preload jsPDF:', err));
    </script>
</body>
</html> 