<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-history.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];
$message = "";

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
$exercise_filter = isset($_GET['exercise']) ? $_GET['exercise'] : '';

$today = date('Y-m-d');
$date_ranges = [
    'week' => date('Y-m-d', strtotime('-7 days')),
    'month' => date('Y-m-d', strtotime('-30 days')),
    'quarter' => date('Y-m-d', strtotime('-90 days')),
    'year' => date('Y-m-d', strtotime('-365 days')),
];

$query_params = [];
$query_params[] = $user_id;

$date_filter = "";
if ($filter != 'all' && isset($date_ranges[$filter])) {
    $date_filter = " AND DATE(created_at) >= ?";
    $query_params[] = $date_ranges[$filter];
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) FROM workouts WHERE user_id = ?$date_filter";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, str_repeat('i', count($query_params)), ...$query_params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_workouts_count = mysqli_fetch_row($count_result)[0];
$total_pages = ceil($total_workouts_count / $per_page);

$logs_query = "
    SELECT 
        id, name, workout_type, duration_minutes, calories_burned, 
        notes, rating, created_at, total_volume, avg_intensity
    FROM workouts 
    WHERE user_id = ?$date_filter 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?";

$query_params[] = $per_page;
$query_params[] = $offset;

$stmt = mysqli_prepare($conn, $logs_query);
$param_types = str_repeat('i', count($query_params));
mysqli_stmt_bind_param($stmt, $param_types, ...$query_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$workout_logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $exercise_count_query = "SELECT COUNT(*) as count FROM workout_exercises WHERE workout_id = ?";
    $count_stmt = mysqli_prepare($conn, $exercise_count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $row['id']);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    
    $row['exercise_count'] = $count_row['count'];
    $workout_logs[] = $row;
}

$stats_query = "
    SELECT 
        COUNT(*) as workout_count,
        SUM(duration_minutes) as total_duration,
        AVG(duration_minutes) as avg_duration,
        SUM(calories_burned) as total_calories,
        AVG(rating) as avg_rating,
        SUM(total_volume) as total_volume,
        AVG(avg_intensity) as avg_intensity,
        COUNT(DISTINCT DATE(created_at)) as workout_days
    FROM workouts 
    WHERE user_id = ?$date_filter";

$stats_stmt = mysqli_prepare($conn, $stats_query);
$stats_param_types = str_repeat('i', count($query_params) - 2);
mysqli_stmt_bind_param($stats_stmt, $stats_param_types, ...array_slice($query_params, 0, -2));
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

$workout_frequency = 'N/A';
if ($stats['workout_count'] > 1) {
    $frequency_query = "
        SELECT 
            DATEDIFF(MAX(DATE(created_at)), MIN(DATE(created_at))) as date_range
        FROM workouts 
        WHERE user_id = ?$date_filter";
    
    $freq_stmt = mysqli_prepare($conn, $frequency_query);
    mysqli_stmt_bind_param($freq_stmt, $stats_param_types, ...array_slice($query_params, 0, -2));
    mysqli_stmt_execute($freq_stmt);
    $freq_result = mysqli_stmt_get_result($freq_stmt);
    $freq_data = mysqli_fetch_assoc($freq_result);
    
    if ($freq_data['date_range'] > 0) {
        $days_diff = $freq_data['date_range'];
        $workouts_per_week = round(($stats['workout_count'] / $days_diff) * 7, 1);
        $workout_frequency = $workouts_per_week;
    } else {
        $workout_frequency = $stats['workout_count'];
    }
}

$streak_query = "
    WITH workout_dates AS (
        SELECT DISTINCT DATE(created_at) as workout_date
        FROM workouts
        WHERE user_id = ?
        ORDER BY workout_date DESC
    ),
    date_diffs AS (
        SELECT 
            workout_date,
            DATEDIFF(workout_date, 
                    LAG(workout_date) OVER (ORDER BY workout_date DESC)) as days_diff
        FROM workout_dates
    )
    SELECT COUNT(*)
    FROM (
        SELECT workout_date
        FROM date_diffs
        WHERE days_diff = -1 OR days_diff IS NULL
        UNION ALL
        SELECT CURDATE() as workout_date
        WHERE EXISTS (
            SELECT 1 FROM workout_dates 
            WHERE workout_date = CURDATE()
        )
    ) as streak_calc";

$streak_stmt = mysqli_prepare($conn, $streak_query);
mysqli_stmt_bind_param($streak_stmt, "i", $user_id);
mysqli_stmt_execute($streak_stmt);
$streak_result = mysqli_stmt_get_result($streak_stmt);
$current_streak = mysqli_fetch_row($streak_result)[0];

$check_streak_query = "
    SELECT 
        DATEDIFF(CURDATE(), MAX(DATE(created_at))) as days_since_last
    FROM workouts
    WHERE user_id = ?";

$check_streak_stmt = mysqli_prepare($conn, $check_streak_query);
mysqli_stmt_bind_param($check_streak_stmt, "i", $user_id);
mysqli_stmt_execute($check_streak_stmt);
$check_result = mysqli_stmt_get_result($check_streak_stmt);
$days_since = mysqli_fetch_assoc($check_result)['days_since_last'];

if ($days_since > 1) {
    $current_streak = 0;
}

$best_streak_query = "
    WITH workout_dates AS (
        SELECT DISTINCT DATE(created_at) as workout_date
        FROM workouts
        WHERE user_id = ?
        ORDER BY workout_date
    ),
    date_groups AS (
        SELECT 
            workout_date,
            DATEDIFF(workout_date, 
                    DATE_SUB(workout_date, INTERVAL ROW_NUMBER() OVER (ORDER BY workout_date) DAY)) as grp
        FROM workout_dates
    )
    SELECT COUNT(*) as streak_length
    FROM date_groups
    GROUP BY grp
    ORDER BY streak_length DESC
    LIMIT 1";

$best_streak_stmt = mysqli_prepare($conn, $best_streak_query);
mysqli_stmt_bind_param($best_streak_stmt, "i", $user_id);
mysqli_stmt_execute($best_streak_stmt);
$best_streak_result = mysqli_stmt_get_result($best_streak_stmt);
$best_streak_row = mysqli_fetch_assoc($best_streak_result);
$best_streak = $best_streak_row ? $best_streak_row['streak_length'] : 0;

$current_month = date('Y-m');
$month_workouts_query = "
    SELECT 
        DAY(created_at) as day,
        COUNT(*) as count,
        SUM(duration_minutes) as total_duration
    FROM workouts
    WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY DAY(created_at)";

$month_stmt = mysqli_prepare($conn, $month_workouts_query);
mysqli_stmt_bind_param($month_stmt, "is", $user_id, $current_month);
mysqli_stmt_execute($month_stmt);
$month_result = mysqli_stmt_get_result($month_stmt);

$month_workouts = [];
while ($day = mysqli_fetch_assoc($month_result)) {
    $month_workouts[$day['day']] = [
        'count' => $day['count'],
        'duration' => $day['total_duration']
    ];
}

$common_exercises_query = "
    SELECT 
        exercise_name,
        COUNT(*) as frequency
    FROM workout_exercises
    WHERE user_id = ?
    GROUP BY exercise_name
    ORDER BY frequency DESC
    LIMIT 5";

$common_ex_stmt = mysqli_prepare($conn, $common_exercises_query);
mysqli_stmt_bind_param($common_ex_stmt, "i", $user_id);
mysqli_stmt_execute($common_ex_stmt);
$common_ex_result = mysqli_stmt_get_result($common_ex_stmt);

$common_exercises = [];
while ($ex = mysqli_fetch_assoc($common_ex_result)) {
    $common_exercises[] = $ex;
}

$personal_records_query = "
    SELECT 
        we.exercise_name,
        MAX(es.weight) as max_weight,
        MAX(es.reps) as max_reps,
        MAX(es.weight * es.reps) as max_volume_set,
        DATE(MAX(w.created_at)) as record_date
    FROM exercise_sets es
    JOIN workout_exercises we ON es.exercise_id = we.id
    JOIN workouts w ON we.workout_id = w.id
    WHERE es.user_id = ? AND es.is_warmup = 0
    GROUP BY we.exercise_name
    ORDER BY max_weight DESC
    LIMIT 5";

$pr_stmt = mysqli_prepare($conn, $personal_records_query);
mysqli_stmt_bind_param($pr_stmt, "i", $user_id);
mysqli_stmt_execute($pr_stmt);
$pr_result = mysqli_stmt_get_result($pr_stmt);

$personal_records = [];
while ($pr = mysqli_fetch_assoc($pr_result)) {
    $personal_records[] = $pr;
}

$workout_types_query = "
    SELECT 
        IFNULL(workout_type, 'Other') as type,
        COUNT(*) as count
    FROM workouts
    WHERE user_id = ?$date_filter
    GROUP BY workout_type
    ORDER BY count DESC";

$types_stmt = mysqli_prepare($conn, $workout_types_query);
mysqli_stmt_bind_param($types_stmt, $stats_param_types, ...array_slice($query_params, 0, -2));
mysqli_stmt_execute($types_stmt);
$types_result = mysqli_stmt_get_result($types_stmt);

$workout_types = [];
while ($type = mysqli_fetch_assoc($types_result)) {
    $workout_types[] = $type;
}

$volume_trend_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') as workout_date,
        SUM(total_volume) as volume
    FROM workouts
    WHERE user_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
    ORDER BY workout_date ASC";

$volume_stmt = mysqli_prepare($conn, $volume_trend_query);
mysqli_stmt_bind_param($volume_stmt, "i", $user_id);
mysqli_stmt_execute($volume_stmt);
$volume_result = mysqli_stmt_get_result($volume_stmt);

$volume_data = [];
while ($vol = mysqli_fetch_assoc($volume_result)) {
    $volume_data[] = $vol;
}

$intensity_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') as workout_date,
        avg_intensity
    FROM workouts
    WHERE user_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ORDER BY workout_date ASC";

$intensity_stmt = mysqli_prepare($conn, $intensity_query);
mysqli_stmt_bind_param($intensity_stmt, "i", $user_id);
mysqli_stmt_execute($intensity_stmt);
$intensity_result = mysqli_stmt_get_result($intensity_stmt);

$intensity_data = [];
while ($int = mysqli_fetch_assoc($intensity_result)) {
    $intensity_data[] = $int;
}

$chart_data = [
    'types' => json_encode($workout_types),
    'volume' => json_encode($volume_data),
    'intensity' => json_encode($intensity_data)
];

$muscle_groups_query = "
    SELECT 
        e.primary_muscle as muscle_group,
        COUNT(*) as total
    FROM workout_exercises we
    JOIN exercises e ON e.name = we.exercise_name
    WHERE we.user_id = ?
    GROUP BY e.primary_muscle
    ORDER BY total DESC";

$muscle_stmt = mysqli_prepare($conn, $muscle_groups_query);
mysqli_stmt_bind_param($muscle_stmt, "i", $user_id);
mysqli_stmt_execute($muscle_stmt);
$muscle_result = mysqli_stmt_get_result($muscle_stmt);

$muscle_groups = [];
while ($mg = mysqli_fetch_assoc($muscle_result)) {
    if (!empty($mg['muscle_group'])) {
        $muscle_groups[] = $mg;
    }
}

$chart_data['muscles'] = json_encode($muscle_groups);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout History - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../profile/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        
        body {
            background-color: var(--dark);
            font-family: 'Poppins', sans-serif;
            color: white;
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
      
        .export-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .desktop-container {
            display: none;
        }
        
        @media (min-width: 992px) {
            .desktop-container {
            display: grid;
                gap: 24px;
            }
            
            .desktop-main {
                display: flex;
                flex-direction: column;
            }
        }
        
        .view-toggle {
            display: flex;
            gap: 8px;
        }
        
        .toggle-btn {
            width: 40px;
            height: 40px;
            background-color: var(--dark-card);
            border: none;
            border-radius: 6px;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .toggle-btn.active {
            background-color: var(--accent);
            color: white;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .date-label {
            color: var(--gray-light);
        }
        
        .date-select {
            position: relative;
            background-color: var(--dark-card);
            border-radius: 6px;
            padding: 10px 16px;
            width: 200px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .date-selection {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .week-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            margin-bottom: 12px;
        }
        
        .week-day {
            color: var(--gray-light);
            font-size: 14px;
            padding: 8px 0;
        }
        
        .filters {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .filter-group {
            margin-bottom: 16px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            color: var(--gray-light);
        }
        
        .filter-select {
            width: 100%;
            background-color: var(--dark-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 10px 12px;
            border-radius: 6px;
        }
        
        .rating-slider {
            width: 100%;
            margin-top: 8px;
        }
        
        .summary-stats {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--gray-light);
        }
        
        .stat-value {
            font-weight: 600;
        }
        
        .workout-table {
            background-color: var(--dark-card);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 0.8fr 2fr 0.8fr 0.8fr 0.8fr 0.8fr 0.5fr;
            padding: 16px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .workout-row {
            display: grid;
            grid-template-columns: 0.8fr 2fr 0.8fr 0.8fr 0.8fr 0.8fr 0.5fr;
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            align-items: center;
        }
        
        .workout-row:last-child {
            border-bottom: none;
        }
        
        .star-rating {
            color: #F59E0B;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .page-item {
            width: 40px;
            height: 40px;
            background-color: var(--dark-card);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .page-item.active {
            background-color: var(--accent);
        }
        
        .progress-card {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .progress-title {
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .chart-container {
            background-color: var(--dark-bg);
            border-radius: 8px;
            height: 180px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 16px;
            margin-bottom: 12px;
            color: var(--gray-light);
        }
        
        .mobile-container {
            display: block;
        }
        
        @media (min-width: 992px) {
            .mobile-container {
                display: none;
            }
        }
        
        .period-tabs {
            display: flex;
            background-color: var(--dark-card);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .period-tab {
            flex: 1;
            text-align: center;
            padding: 16px 12px;
            cursor: pointer;
        }
        
        .period-tab.active {
            background-color: var(--accent);
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stats-card {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        
        .stats-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stats-value .trend {
            font-size: 14px;
            margin-left: 4px;
        }
        
        .stats-label {
            color: var(--gray-light);
            font-size: 14px;
        }
        
        .trend-up {
            color: #10B981;
        }
        
        .trend-down {
            color: #EF4444;
        }
        
        .workout-card {
            background-color: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .workout-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .workout-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .workout-date {
            color: var(--gray-light);
            font-size: 14px;
        }

        .workout-meta {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .workout-volume {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .workout-notes {
            margin-top: 16px;
            color: var(--gray-light);
            font-size: 14px;
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        
        .card-btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .primary-btn {
            background-color: var(--accent);
            color: white;
        }
        
        .secondary-btn {
            background-color: var(--dark-bg);
            color: white;
        }
        
        .icon-btn {
            width: 44px;
            background-color: var(--dark-bg);
            color: white;
            border: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .view-btn {
            background-color: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            text-align: center;
            transition: background-color 0.2s ease;
        }
        
        .view-btn:hover {
            background-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <div class="dashboard">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Workout History</h1>
            <div style="display: flex; gap: 16px; align-items: center;">
                <button class="export-btn">
                    <i class="fas fa-file-export"></i> Export Data
                </button>
            </div>
        </div>

        <div class="desktop-container">
            <div class="desktop-main">
                
                <div style="display: grid; grid-template-columns: 250px 1fr; gap: 24px;">
                    <div>
                        <div class="filters">
                            <h3>Filters</h3>
                            <div class="filter-group">
                                <label class="filter-label">Templates</label>
                                <select class="filter-select" id="template-filter">
                                    <option value="all">All Templates</option>
                                    
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Rating</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="range" min="0" max="5" value="0" step="1" class="rating-slider" id="rating-filter">
                                    <span id="rating-value">All</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="summary-stats">
                            <h3>Summary Stats</h3>
                            <div class="stat-row">
                                <span class="stat-label">Total Workouts</span>
                                <span class="stat-value"><?= $stats['workout_count'] ?? 0 ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Avg. Duration</span>
                                <span class="stat-value"><?= isset($stats['avg_duration']) ? round($stats['avg_duration'], 1) : 0 ?> min</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Total Volume</span>
                                <span class="stat-value"><?= isset($stats['total_volume']) ? number_format($stats['total_volume'], 1) : 0 ?> kg</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Total Calories</span>
                                <span class="stat-value"><?= isset($stats['total_calories']) ? number_format($stats['total_calories']) : 0 ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="workout-table">
                            <div class="table-header">
                                <div>Date</div>
                                <div>Workout Name</div>
                                <div>Duration</div>
                                <div>Volume</div>
                                <div>Calories</div>
                                <div>Rating</div>
                                <div>Action</div>
                            </div>

                            <?php if (!empty($workout_logs)): ?>
                                <?php foreach ($workout_logs as $workout): ?>
                                    <div class="workout-row">
                                        <div><?= date('M d, Y', strtotime($workout['created_at'])) ?></div>
                                        <div><?= htmlspecialchars($workout['name']) ?></div>
                                        <div><?= $workout['duration_minutes'] ?> min</div>
                                        <div><?= $workout['total_volume'] ?> kg</div>
                                        <div><?= $workout['calories_burned'] ?></div>
                                        <div class="star-rating">
                                            <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>
                                        </div>
                                        <div>
                                            <a href="workout-details.php?id=<?= $workout['id'] ?>" class="view-btn">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="pagination">
                            <div class="page-item">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="page-item active">1</div>
                            <div class="page-item">2</div>
                            <div class="page-item">3</div>
                            <div class="page-item">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mobile-container">
            <div class="period-tabs">
                <div class="period-tab active">This Week</div>
                <div class="period-tab">Last Week</div>
                <div class="period-tab">This Month</div>
                <div class="period-tab">Custom</div>
            </div>
            
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stats-value">
                        12<span class="trend trend-up">↑2</span>
                    </div>
                    <div class="stats-label">Total Workouts</div>
                </div>
                <div class="stats-card">
                    <div class="stats-value">
                        45m<span class="trend trend-down">↓5m</span>
                    </div>
                    <div class="stats-label">Avg Duration</div>
                </div>
                <div class="stats-card">
                    <div class="stats-value">
                        2.4t<span class="trend trend-up">↑0.2</span>
                    </div>
                    <div class="stats-label">Total Volume</div>
                </div>
                <div class="stats-card">
                    <div class="stats-value">
                        8.2k<span class="trend trend-up">↑1.1k</span>
                    </div>
                    <div class="stats-label">Calories</div>
                </div>
            </div>
            
            <?php if (!empty($workout_logs)): ?>
                <?php foreach ($workout_logs as $key => $workout): ?>
                    <?php if ($key <= 1): ?>
                    <div class="workout-card">
                        <div class="workout-header">
                            <div>
                                <div class="workout-title">
                                    <?= $key == 0 ? 'Upper Body Strength' : 'Leg Day' ?>
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="workout-date">
                                    <?= $key == 0 ? 'May 6, 2025' : 'May 5, 2025' ?> • 
                                    <?= $key == 0 ? '50' : '65' ?>min
                                </div>
                            </div>
                        </div>
                        
                        <div class="workout-meta">
                            <div class="workout-volume">
                                Volume: <?= $key == 0 ? '850kg' : '1200kg' ?>
                                <span class="trend <?= $key == 0 ? 'trend-up' : 'trend-down' ?>">
                                    <?= $key == 0 ? '↑50kg' : '↓100kg' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="star-rating">
                            <?= $key == 0 ? '★★★★☆' : '★★★☆☆' ?>
                        </div>

                        <?php if ($key == 1): ?>
                        <div class="workout-notes">
                            <b>Notes:</b> Felt strong today. Increased weight on squats.
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-actions">
                            <button class="card-btn secondary-btn">View Details</button>
                            <button class="icon-btn">
                                <i class="fas fa-share"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="workout-card">
                    <p style="text-align: center;">No workout history found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="exportModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: var(--dark-card); width: 90%; max-width: 400px; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-lg);">
            <h3 style="margin-top: 0;">Export Workout Data</h3>
            <p>Select the time period for your workout data export:</p>
            
            <div style="margin: 20px 0;">
                <div class="export-option" style="padding: 12px; margin-bottom: 8px; border-radius: 8px; background-color: var(--dark-bg); cursor: pointer;">
                    <label>
                        <input type="radio" name="exportPeriod" value="week" checked> Latest Week
                    </label>
                </div>
                <div class="export-option" style="padding: 12px; margin-bottom: 8px; border-radius: 8px; background-color: var(--dark-bg); cursor: pointer;">
                    <label>
                        <input type="radio" name="exportPeriod" value="month"> Latest Month
                    </label>
                </div>
                <div class="export-option" style="padding: 12px; margin-bottom: 8px; border-radius: 8px; background-color: var(--dark-bg); cursor: pointer;">
                    <label>
                        <input type="radio" name="exportPeriod" value="year"> Latest Year
                    </label>
                </div>
                <div class="export-option" style="padding: 12px; margin-bottom: 8px; border-radius: 8px; background-color: var(--dark-bg); cursor: pointer;">
                    <label>
                        <input type="radio" name="exportPeriod" value="all"> All Time
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                <button id="cancelExport" class="card-btn secondary-btn" style="flex: 0 0 auto; padding: 10px 16px;">Cancel</button>
                <button id="confirmExport" class="card-btn primary-btn" style="flex: 0 0 auto; padding: 10px 16px;">Export PDF</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.toggle-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                });
            });
        
            const periodTabs = document.querySelectorAll('.period-tab');
            periodTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    periodTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    const period = this.textContent.trim().toLowerCase().replace(/\s/g, '-');
                    loadMobileWorkouts(period);
                });
            });
            
            function loadMobileWorkouts(period, customDates = null) {
                const mobileContainer = document.querySelector('.mobile-container');
                const workoutCards = mobileContainer.querySelectorAll('.workout-card:not(.stats-card)');
                
                workoutCards.forEach(card => card.remove());
                
                const loadingEl = document.createElement('div');
                loadingEl.className = 'workout-card';
                loadingEl.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><p>Loading workouts...</p></div>';
                mobileContainer.appendChild(loadingEl);
                
                let apiPeriod;
                let extraParams = '';
                
                const today = new Date();
                const currentDay = today.getDay();
                
                switch(period) {
                    case 'this-week': 
                        const thisWeekMonday = new Date(today);
                        const daysToSubtract = currentDay === 0 ? 6 : currentDay - 1;
                        thisWeekMonday.setDate(today.getDate() - daysToSubtract);
                        
                        apiPeriod = 'custom'; 
                        extraParams = `&start_date=${formatDate(thisWeekMonday)}&end_date=${formatDate(today)}`;
                        break;
                        
                    case 'last-week':
                        const lastWeekMonday = new Date(today);
                        const daysToLastMonday = currentDay === 0 ? 13 : currentDay + 6;
                        lastWeekMonday.setDate(today.getDate() - daysToLastMonday);
                        
                        const lastWeekSunday = new Date(lastWeekMonday);
                        lastWeekSunday.setDate(lastWeekMonday.getDate() + 6);
                        
                        apiPeriod = 'custom';
                        extraParams = `&start_date=${formatDate(lastWeekMonday)}&end_date=${formatDate(lastWeekSunday)}`;
                        break;
                        
                    case 'this-month':
                        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        
                        apiPeriod = 'custom';
                        extraParams = `&start_date=${formatDate(firstDayOfMonth)}&end_date=${formatDate(today)}`;
                        break;
                        
                    case 'custom':
                        if (customDates) {
                            apiPeriod = 'custom';
                            extraParams = `&start_date=${customDates.startDate}&end_date=${customDates.endDate}`;
                        } else {
                            loadingEl.remove();
                            window.setupCustomDateRange();
                            return;
                        }
                        break;
                        
                    default: 
                        apiPeriod = 'week';
                }
                
                function formatDate(date) {
                    return date.toISOString().split('T')[0];
                }
                
                fetch(`get-workouts.php?period=${apiPeriod}${extraParams}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Invalid JSON response:', text);
                                throw new Error('Invalid server response');
                            }
                        });
                    })
                    .then(data => {
                        loadingEl.remove();
                        
                        updateMobileStats(data.summary);
                        
                        if (!data.workouts || data.workouts.length === 0) {
                            const noDataEl = document.createElement('div');
                            noDataEl.className = 'workout-card';
                            noDataEl.innerHTML = '<p style="text-align: center;">No workouts found.</p>';
                            mobileContainer.appendChild(noDataEl);
                        } else {
                            data.workouts.forEach(workout => {
                                const card = createWorkoutCard(workout);
                                mobileContainer.appendChild(card);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching workouts:', error);
                        loadingEl.innerHTML = '<p style="text-align: center; color: var(--danger);">Error loading workouts. Please try again.</p>';
                        
                        updateMobileStats(null);
                    });
            }
            
            function updateMobileStats(summary) {
                if (!summary) {
                    summary = {
                        total_workouts: 0,
                        avg_duration: 0,
                        total_volume: 0,
                        total_calories: 0,
                        trend: {
                            workouts: 0,
                            duration: 0,
                            volume: 0,
                            calories: 0
                        }
                    };
                }

                const elements = {
                    totalWorkouts: document.querySelector('.stats-grid .stats-card:nth-child(1) .stats-value'),
                    avgDuration: document.querySelector('.stats-grid .stats-card:nth-child(2) .stats-value'),
                    totalVolume: document.querySelector('.stats-grid .stats-card:nth-child(3) .stats-value'),
                    totalCalories: document.querySelector('.stats-grid .stats-card:nth-child(4) .stats-value')
                };

                const values = {
                    totalWorkouts: Math.round(Number(summary.total_workouts) || 0),
                    avgDuration: Math.round(Number(summary.avg_duration) || 0),
                    totalVolume: Math.round(Number(summary.total_volume) || 0),
                    totalCalories: Math.round(Number(summary.total_calories) || 0)
                };

                if (summary.trend) {
                    const trends = {
                        workouts: Math.round(Number(summary.trend.workouts) || 0),
                        duration: Math.round(Number(summary.trend.duration) || 0),
                        volume: Math.round(Number(summary.trend.volume) || 0),
                        calories: Math.round(Number(summary.trend.calories) || 0)
                    };

                    elements.totalWorkouts.innerHTML = `${values.totalWorkouts}<span class="trend ${trends.workouts >= 0 ? 'trend-up' : 'trend-down'}">
                        ${trends.workouts >= 0 ? '↑' : '↓'}${Math.abs(trends.workouts)}</span>`;
                    
                    elements.avgDuration.innerHTML = `${values.avgDuration}m<span class="trend ${trends.duration >= 0 ? 'trend-up' : 'trend-down'}">
                        ${trends.duration >= 0 ? '↑' : '↓'}${Math.abs(trends.duration)}m</span>`;
                    
                    elements.totalVolume.innerHTML = `${values.totalVolume.toLocaleString()}kg<span class="trend ${trends.volume >= 0 ? 'trend-up' : 'trend-down'}">
                        ${trends.volume >= 0 ? '↑' : '↓'}${Math.abs(trends.volume)}kg</span>`;
                    
                    elements.totalCalories.innerHTML = `${values.totalCalories.toLocaleString()}kcal<span class="trend ${trends.calories >= 0 ? 'trend-up' : 'trend-down'}">
                        ${trends.calories >= 0 ? '↑' : '↓'}${Math.abs(trends.calories)}kcal</span>`;
                } else {
                    elements.totalWorkouts.textContent = values.totalWorkouts;
                    elements.avgDuration.textContent = `${values.avgDuration}m`;
                    elements.totalVolume.textContent = `${values.totalVolume.toLocaleString()}kg`;
                    elements.totalCalories.textContent = `${values.totalCalories.toLocaleString()}kcal`;
                }
            }
            
            function formatWeight(weight) {
                if (weight >= 1000) {
                    return (weight / 1000).toFixed(1) + 't';
                }
                return weight + 'kg';
            }
            
            function formatCalories(calories) {
                if (calories >= 1000) {
                    return (calories / 1000).toFixed(1) + 'k';
                }
                return calories;
            }
            
            function createWorkoutCard(workout) {
                const card = document.createElement('div');
                card.className = 'workout-card';
            
                const date = new Date(workout.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                const volumeTrend = workout.volume_trend || { value: 0, direction: 'up' };
                const trendClass = volumeTrend.direction === 'up' ? 'trend-up' : 'trend-down';
                const trendArrow = volumeTrend.direction === 'up' ? '↑' : '↓';
                
                card.innerHTML = `
                    <div class="workout-header">
                        <div>
                            <div class="workout-title">
                                ${workout.name}
                                <i class="fas fa-dumbbell"></i>
                            </div>
                            <div class="workout-date">
                                ${formattedDate} • ${workout.duration_minutes}min
                            </div>
                        </div>
                    </div>
                    
                    <div class="workout-meta">
                        <div class="workout-volume">
                            Volume: ${workout.total_volume}kg
                            <span class="trend ${trendClass}">
                                ${trendArrow}${volumeTrend.value}kg
                            </span>
                        </div>
                    </div>
                    
                    <div class="star-rating">
                        ${'★'.repeat(workout.rating)}${'☆'.repeat(5 - workout.rating)}
                    </div>
                    
                    ${workout.notes ? `<div class="workout-notes"><b>Notes:</b> ${workout.notes}</div>` : ''}
                    
                    <div class="card-actions">
                        <button class="card-btn secondary-btn" onclick="viewWorkoutDetails(${workout.id})">View Details</button>
                    </div>
                `;
                
                return card;
            }
            
            try {
                fetch('get-templates.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(templates => {
                        const templateFilter = document.getElementById('template-filter');
                        if (templateFilter) {
                            templates.forEach(template => {
                                const option = document.createElement('option');
                                option.value = template.id;
                                option.textContent = template.name;
                                templateFilter.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading templates:', error);
                    });
            } catch (e) {
                console.error('Error in template loading:', e);
            }
            
            const templateFilter = document.getElementById('template-filter');
            const ratingFilter = document.getElementById('rating-filter');
            const ratingValue = document.getElementById('rating-value');
            if (ratingFilter && ratingValue) {
                ratingFilter.addEventListener('input', function() {
                    const value = parseInt(this.value);
                    ratingValue.textContent = value === 0 ? 'All' : '★'.repeat(value);
                });
            }
            
            if (templateFilter) {
                templateFilter.addEventListener('change', function() {
                    loadWorkouts();
                });
            }
            
            if (ratingFilter) {
                ratingFilter.addEventListener('change', function() {
                    loadWorkouts();
                });
            }
            
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }
            
            if (ratingFilter) {
                const debouncedInput = debounce(function() {
                    loadWorkouts();
                }, 300);
                
                ratingFilter.addEventListener('input', function() {
                    const value = parseInt(this.value);
                    if (ratingValue) {
                        ratingValue.textContent = value === 0 ? 'All' : '★'.repeat(value);
                    }
                    debouncedInput();
                });
            }
            
            function loadWorkouts() {
                const template = templateFilter ? templateFilter.value : 'all';
                const rating = ratingFilter ? ratingFilter.value : 0;
                
                let period = 'month';
                const dateSelectSpan = document.querySelector('.date-select span');
                if (dateSelectSpan) {
                    const dateFilter = dateSelectSpan.textContent;
                    
                    switch(dateFilter) {
                        case 'Last 7 Days': period = 'week'; break;
                        case 'Last 30 Days': period = 'month'; break;
                        case 'Last 90 Days': period = '90days'; break;
                        case 'This Year': period = 'year'; break;
                        default: period = 'all';
                    }
                }
                
                const tableBody = document.querySelector('.workout-table');
                if (!tableBody) {
                    console.error('Workout table not found');
                    return;
                }
                
                const headerRow = tableBody.querySelector('.table-header');
                const workoutRows = tableBody.querySelectorAll('.workout-row');
                
                workoutRows.forEach(row => row.remove());
                
                const loadingRow = document.createElement('div');
                loadingRow.className = 'loading-row';
                loadingRow.style.padding = '20px';
                loadingRow.style.textAlign = 'center';
                loadingRow.style.gridColumn = '1 / -1';
                loadingRow.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading workouts...';
                tableBody.appendChild(loadingRow);
                
                let url = `get-workouts.php?period=${period}`;
                if (template !== 'all') {
                    url += `&template_id=${template}`;
                }
                if (parseInt(rating) > 0) {
                    url += `&rating=${rating}`;
                }
                
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Invalid JSON response:', text);
                                throw new Error('Invalid server response');
                            }
                        });
                    })
                    .then(data => {
                        const loadingElement = tableBody.querySelector('.loading-row');
                        if (loadingElement) {
                            loadingElement.remove();
                        }

                        const existingMessages = tableBody.querySelectorAll('.no-results-row, .error-row');
                        existingMessages.forEach(msg => msg.remove());
                        updateSummaryStats(data.summary);
                        
                        if (!data.workouts || data.workouts.length === 0) {
                            const noResultsRow = document.createElement('div');
                            noResultsRow.className = 'no-results-row';
                            noResultsRow.style.padding = '20px';
                            noResultsRow.style.textAlign = 'center';
                            noResultsRow.style.gridColumn = '1 / -1';
                            noResultsRow.innerHTML = '<p>No workouts found.</p>';
                            tableBody.appendChild(noResultsRow);
                            
                            const pagination = document.querySelector('.pagination');
                            if (pagination) {
                                pagination.style.display = 'none';
                            }
                        } else {
                            data.workouts.forEach(workout => {
                                const row = createWorkoutRow(workout);
                                tableBody.appendChild(row);
                            });
                            
                            initPagination(data.workouts.length, data.total_count || data.workouts.length);
                            
                            const pagination = document.querySelector('.pagination');
                            if (pagination) {
                                pagination.style.display = 'flex';
                            }
                            
                            goToPage(1);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading workouts:', error);
                        
                        const loadingElement = tableBody.querySelector('.loading-row');
                        if (loadingElement) {
                            loadingElement.remove();
                        }

                        const existingMessages = tableBody.querySelectorAll('.no-results-row, .error-row');
                        existingMessages.forEach(msg => msg.remove());
                        
                        const errorRow = document.createElement('div');
                        errorRow.className = 'error-row';
                        errorRow.style.padding = '20px';
                        errorRow.style.textAlign = 'center';
                        errorRow.style.gridColumn = '1 / -1';
                        errorRow.style.color = 'var(--danger)';
                        errorRow.innerHTML = '<p>Error loading workouts. Please try again later.</p>';
                        tableBody.appendChild(errorRow);
                        
                        const pagination = document.querySelector('.pagination');
                        if (pagination) {
                            pagination.style.display = 'none';
                        }
                        
                        updateSummaryStats(null);
                    });
            }
            
            function updateSummaryStats(summary) {
                if (!summary) {
                    summary = {
                        total_workouts: 0,
                        avg_duration: 0,
                        total_volume: 0,
                        total_calories: 0
                    };
                }

                const elements = {
                    totalWorkouts: document.querySelector('.summary-stats .stat-row:nth-child(1) .stat-value'),
                    avgDuration: document.querySelector('.summary-stats .stat-row:nth-child(2) .stat-value'),
                    totalVolume: document.querySelector('.summary-stats .stat-row:nth-child(3) .stat-value'),
                    totalCalories: document.querySelector('.summary-stats .stat-row:nth-child(4) .stat-value')
                };

                const values = {
                    totalWorkouts: Math.round(Number(summary.total_workouts) || 0),
                    avgDuration: Math.round(Number(summary.avg_duration) || 0),
                    totalVolume: Math.round(Number(summary.total_volume) || 0),
                    totalCalories: Math.round(Number(summary.total_calories) || 0)
                };

                if (elements.totalWorkouts) elements.totalWorkouts.textContent = `${values.totalWorkouts}`;
                if (elements.avgDuration) elements.avgDuration.textContent = `${values.avgDuration} min`;
                if (elements.totalVolume) elements.totalVolume.textContent = `${values.totalVolume.toLocaleString()} kg`;
                if (elements.totalCalories) elements.totalCalories.textContent = `${values.totalCalories.toLocaleString()} kcal`;
            }
            
            function createWorkoutRow(workout) {
                const row = document.createElement('div');
                row.className = 'workout-row';
                row.dataset.id = workout.id;
                row.dataset.template = workout.template_id || '';
                row.dataset.rating = workout.rating || 0;
                
                const date = new Date(workout.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                row.innerHTML = `
                    <div>${formattedDate}</div>
                    <div>${workout.name}</div>
                    <div>${workout.duration_minutes} min</div>
                    <div>${workout.total_volume} kg</div>
                    <div>${workout.calories_burned || 0}</div>
                    <div class="star-rating">
                        ${'★'.repeat(workout.rating)}${'☆'.repeat(5 - workout.rating)}
                    </div>
                    <div>
                        <a href="workout-details.php?id=${workout.id}" class="view-btn">View</a>
                    </div>
                `;
                
                return row;
            }
            
            let currentPage = 1;
            const ROWS_PER_PAGE = 10;
            
            function initPagination(displayedRows, totalRows = displayedRows) {
                const totalPages = Math.ceil(totalRows / ROWS_PER_PAGE);
                const paginationContainer = document.querySelector('.pagination');
                
                if (!paginationContainer) {
                    console.error('Pagination container not found');
                    return;
                }
            
                paginationContainer.innerHTML = '';
                
                const prevButton = document.createElement('div');
                prevButton.className = 'page-item';
                prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prevButton.addEventListener('click', () => {
                    if (currentPage > 1) {
                        goToPage(currentPage - 1);
                    }
                });
                paginationContainer.appendChild(prevButton);
                
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                if (endPage - startPage < 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    const pageItem = document.createElement('div');
                    pageItem.className = 'page-item';
                    if (i === currentPage) pageItem.classList.add('active');
                    pageItem.textContent = i;
                    pageItem.addEventListener('click', () => goToPage(i));
                    paginationContainer.appendChild(pageItem);
                }
                
                const nextButton = document.createElement('div');
                nextButton.className = 'page-item';
                nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
                nextButton.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        goToPage(currentPage + 1);
                    }
                });
                paginationContainer.appendChild(nextButton);
                
                paginationContainer.style.display = totalPages <= 1 ? 'none' : 'flex';
            }
            
            function goToPage(page) {
                currentPage = page;
                
                const rows = document.querySelectorAll('.workout-row');
                const start = (page - 1) * ROWS_PER_PAGE;
                const end = start + ROWS_PER_PAGE;
                
                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });

                const pageItems = document.querySelectorAll('.page-item');
                pageItems.forEach(item => {
                    if (!item.querySelector('i')) {
                        item.classList.remove('active');
                        if (parseInt(item.textContent) === page) {
                            item.classList.add('active');
                        }
                    }
                });
                
                const prevButton = document.querySelector('.pagination .page-item:first-child');
                const nextButton = document.querySelector('.pagination .page-item:last-child');
                
                if (prevButton) {
                    prevButton.style.opacity = page === 1 ? '0.5' : '1';
                    prevButton.style.cursor = page === 1 ? 'default' : 'pointer';
                }
                
                if (nextButton) {
                    const totalPages = Math.ceil(rows.length / ROWS_PER_PAGE);
                    nextButton.style.opacity = page === totalPages ? '0.5' : '1';
                    nextButton.style.cursor = page === totalPages ? 'default' : 'pointer';
                }
            }
        
            const dateSelect = document.querySelector('.date-select');
            if (dateSelect) {
                dateSelect.addEventListener('click', function() {
                    const dropdown = document.createElement('div');
                    dropdown.className = 'date-dropdown';
                    dropdown.style.position = 'absolute';
                    dropdown.style.top = (dateSelect.offsetTop + dateSelect.offsetHeight) + 'px';
                    dropdown.style.left = dateSelect.offsetLeft + 'px';
                    dropdown.style.width = dateSelect.offsetWidth + 'px';
                    dropdown.style.backgroundColor = 'var(--dark-card)';
                    dropdown.style.borderRadius = '6px';
                    dropdown.style.boxShadow = 'var(--shadow)';
                    dropdown.style.zIndex = '100';
                    
                    const options = [
                        { text: 'Last 7 Days', value: 'week' },
                        { text: 'Last 30 Days', value: 'month' },
                        { text: 'Last 90 Days', value: '90days' },
                        { text: 'This Year', value: 'year' },
                        { text: 'All Time', value: 'all' }
                    ];
                    
                    options.forEach(option => {
                        const item = document.createElement('div');
                        item.textContent = option.text;
                        item.style.padding = '10px 16px';
                        item.style.cursor = 'pointer';
                        
                        item.addEventListener('mouseover', function() {
                            this.style.backgroundColor = 'var(--dark-bg)';
                        });
                        
                        item.addEventListener('mouseout', function() {
                            this.style.backgroundColor = '';
                        });
                        
                        item.addEventListener('click', function() {
                            dateSelect.querySelector('span').textContent = option.text;
                            document.body.removeChild(dropdown);
                            
                            const statsValues = document.querySelectorAll('.stat-value');
                            statsValues.forEach(stat => {
                                stat.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                            });
                            
                            loadWorkouts();
                        });
                        
                        dropdown.appendChild(item);
                    });
                    
                    document.body.appendChild(dropdown);
                    
                    document.addEventListener('click', function closeDropdown(e) {
                        if (!dropdown.contains(e.target) && e.target !== dateSelect) {
                            if (document.body.contains(dropdown)) {
                                document.body.removeChild(dropdown);
                            }
                            document.removeEventListener('click', closeDropdown);
                        }
                    });
                });
            }

            const exportButtons = document.querySelectorAll('.export-btn');
            const exportModal = document.getElementById('exportModal');
            const confirmExport = document.getElementById('confirmExport');
            const cancelExport = document.getElementById('cancelExport');
            const exportOptions = document.querySelectorAll('.export-option');

            exportButtons.forEach(button => {
                button.addEventListener('click', function() {
                    exportModal.style.display = 'flex';
                });
            });

            exportOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = option.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });

            cancelExport.addEventListener('click', function() {
                exportModal.style.display = 'none';
            });

            confirmExport.addEventListener('click', function() {
                const selectedPeriod = document.querySelector('input[name="exportPeriod"]:checked').value;
                exportWorkoutData(selectedPeriod);
                exportModal.style.display = 'none';
            });

            exportModal.addEventListener('click', function(e) {
                if (e.target === exportModal) {
                    exportModal.style.display = 'none';
                }
            });

            function exportWorkoutData(period) {
                const loadingOverlay = document.createElement('div');
                loadingOverlay.style.position = 'fixed';
                loadingOverlay.style.top = '0';
                loadingOverlay.style.left = '0';
                loadingOverlay.style.width = '100%';
                loadingOverlay.style.height = '100%';
                loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.7)';
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.alignItems = 'center';
                loadingOverlay.style.justifyContent = 'center';
                loadingOverlay.style.zIndex = '2000';
                
                const loadingSpinner = document.createElement('div');
                loadingSpinner.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: white;"></i>';
                loadingOverlay.appendChild(loadingSpinner);
                
                document.body.appendChild(loadingOverlay);

                fetch(`export-workouts.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF();
                        
                        doc.setFontSize(20);
                        doc.text('Workout History Report', 105, 15, { align: 'center' });
                        
                        doc.setFontSize(12);
                        let periodText = '';
                        switch(period) {
                            case 'week': periodText = 'Latest Week'; break;
                            case 'month': periodText = 'Latest Month'; break;
                            case 'year': periodText = 'Latest Year'; break;
                            default: periodText = 'All Time';
                        }
                        doc.text(`Period: ${periodText}`, 105, 25, { align: 'center' });
                        doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 105, 32, { align: 'center' });
                        
                        doc.setFontSize(16);
                        doc.text('Workout Summary', 14, 45);
                        
                        doc.setFontSize(12);
                        doc.text(`Total Workouts: ${data.summary.total_workouts}`, 14, 55);
                        doc.text(`Average Duration: ${data.summary.avg_duration} minutes`, 14, 62);
                        doc.text(`Total Volume: ${data.summary.total_volume} kg`, 14, 69);
                        doc.text(`Average Rating: ${data.summary.avg_rating}/5`, 14, 76);
                        
                        doc.setFontSize(16);
                        doc.text('Workout List', 14, 90);
                        
                        doc.setFontSize(10);
                        doc.setTextColor(100);
                        doc.text('Date', 14, 100);
                        doc.text('Workout', 50, 100);
                        doc.text('Duration', 115, 100);
                        doc.text('Volume', 140, 100);
                        doc.text('Rating', 165, 100);
                        
                        doc.setTextColor(0);
                        let y = 107;
                        
                        data.workouts.forEach((workout, index) => {
                            if (y > 280) {
                                doc.addPage();
                                y = 20;
                                
                                doc.setFontSize(10);
                                doc.setTextColor(100);
                                doc.text('Date', 14, y);
                                doc.text('Workout', 50, y);
                                doc.text('Duration', 115, y);
                                doc.text('Volume', 140, y);
                                doc.text('Rating', 165, y);
                                doc.setTextColor(0);
                                y += 7;
                            }
                            
                            const date = new Date(workout.created_at);
                            const formattedDate = date.toLocaleDateString();
                            
                            doc.text(formattedDate, 14, y);
                            doc.text(workout.name.substring(0, 30), 50, y);
                            doc.text(`${workout.duration_minutes} min`, 115, y);
                            doc.text(`${workout.total_volume} kg`, 140, y);
                            doc.text('★'.repeat(workout.rating) + '☆'.repeat(5 - workout.rating), 165, y);
                            
                            if (index < data.workouts.length - 1) {
                                doc.setDrawColor(200);
                                doc.line(14, y + 3, 190, y + 3);
                            }
                            
                            y += 10;
                        });
                        
                        doc.save(`workout-history-${period}.pdf`);
                        
                        document.body.removeChild(loadingOverlay);
                    })
                    .catch(error => {
                        console.error('Error exporting data:', error);
                        alert('There was an error exporting your workout data. Please try again.');
                        document.body.removeChild(loadingOverlay);
                    });
            }
            
            window.repeatWorkout = function(workoutId) {
                alert(`Repeating workout #${workoutId}. This would navigate to a new workout page.`);
            };
            
            window.viewWorkoutDetails = function(workoutId) {
                window.location.href = `workout-details.php?id=${workoutId}`;
            };
            
            window.shareWorkout = function(workoutId) {
                alert(`Sharing workout #${workoutId}. This would open a share modal.`);
            };
          
            window.setupCustomDateRange = function() {
                const modalContent = `
                    <div id="customDateModal" class="modal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
                        <div class="modal-content" style="background-color: var(--dark-card); width: 90%; max-width: 400px; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-lg);">
                            <h3 style="margin-top: 0;">Select Date Range</h3>
                            
                            <div style="margin: 20px 0;">
                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; margin-bottom: 8px; color: var(--gray-light);">Start Date</label>
                                    <input type="date" id="customStartDate" style="width: 100%; padding: 10px; border-radius: 6px; background: var(--dark-bg); border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 8px; color: var(--gray-light);">End Date</label>
                                    <input type="date" id="customEndDate" style="width: 100%; padding: 10px; border-radius: 6px; background: var(--dark-bg); border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                                <button id="cancelCustomDate" class="card-btn secondary-btn" style="flex: 0 0 auto; padding: 10px 16px;">Cancel</button>
                                <button id="applyCustomDate" class="card-btn primary-btn" style="flex: 0 0 auto; padding: 10px 16px;">Apply</button>
                            </div>
                        </div>
                    </div>
                `;
                
                const modalContainer = document.createElement('div');
                modalContainer.innerHTML = modalContent;
                document.body.appendChild(modalContainer.firstElementChild);
                
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                
                document.getElementById('customStartDate').valueAsDate = thirtyDaysAgo;
                document.getElementById('customEndDate').valueAsDate = today;
                
                document.getElementById('cancelCustomDate').addEventListener('click', function() {
                    document.getElementById('customDateModal').remove();
                });
                
                document.getElementById('applyCustomDate').addEventListener('click', function() {
                    const startDate = document.getElementById('customStartDate').value;
                    const endDate = document.getElementById('customEndDate').value;
                    
                    if (!startDate || !endDate) {
                        alert('Please select both start and end dates');
                        return;
                    }
                    
                    loadMobileWorkouts('custom', {startDate, endDate});
                    document.getElementById('customDateModal').remove();
                });
                
                document.getElementById('customDateModal').addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.remove();
                    }
                });
            };
            
            loadWorkouts();
            
            if (document.querySelector('.period-tab.active')) {
                const activePeriod = document.querySelector('.period-tab.active').textContent.trim().toLowerCase().replace(/\s/g, '-');
                loadMobileWorkouts(activePeriod);
            }
        });
    </script>
</body>
</html>