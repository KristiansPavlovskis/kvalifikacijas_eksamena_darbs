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
    WITH exercise_groups AS (
        SELECT 
            exercise_name,
            CASE
                WHEN exercise_name LIKE '%bench%' OR 
                     exercise_name LIKE '%chest%' OR 
                     exercise_name LIKE '%fly%' OR 
                     exercise_name LIKE '%press%' THEN 'Chest'
                WHEN exercise_name LIKE '%squat%' OR 
                     exercise_name LIKE '%leg%' OR 
                     exercise_name LIKE '%lunge%' OR 
                     exercise_name LIKE '%quad%' OR
                     exercise_name LIKE '%hamstring%' THEN 'Legs'
                WHEN exercise_name LIKE '%deadlift%' OR 
                     exercise_name LIKE '%back%' OR 
                     exercise_name LIKE '%row%' OR 
                     exercise_name LIKE '%pull%' THEN 'Back'
                WHEN exercise_name LIKE '%shoulder%' OR 
                     exercise_name LIKE '%delt%' OR 
                     exercise_name LIKE '%military%' OR 
                     exercise_name LIKE '%overhead%' THEN 'Shoulders'
                WHEN exercise_name LIKE '%bicep%' OR 
                     exercise_name LIKE '%curl%' THEN 'Biceps'
                WHEN exercise_name LIKE '%tricep%' OR 
                     exercise_name LIKE '%extension%' THEN 'Triceps'
                WHEN exercise_name LIKE '%ab%' OR 
                     exercise_name LIKE '%core%' OR 
                     exercise_name LIKE '%crunch%' OR 
                     exercise_name LIKE '%plank%' THEN 'Core'
                WHEN exercise_name LIKE '%cardio%' OR 
                     exercise_name LIKE '%run%' OR 
                     exercise_name LIKE '%bike%' OR 
                     exercise_name LIKE '%treadmill%' OR
                     exercise_name LIKE '%elliptical%' THEN 'Cardio'
                ELSE 'Other'
            END as muscle_group,
            COUNT(*) as count
        FROM workout_exercises
        WHERE user_id = ?
        GROUP BY exercise_name
    )
    SELECT 
        muscle_group,
        SUM(count) as total
    FROM exercise_groups
    GROUP BY muscle_group
    ORDER BY total DESC";

$muscle_stmt = mysqli_prepare($conn, $muscle_groups_query);
mysqli_stmt_bind_param($muscle_stmt, "i", $user_id);
mysqli_stmt_execute($muscle_stmt);
$muscle_result = mysqli_stmt_get_result($muscle_stmt);

$muscle_groups = [];
while ($mg = mysqli_fetch_assoc($muscle_result)) {
    $muscle_groups[] = $mg;
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        }
        
        body {
            background-color: var(--dark-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--white);
            line-height: 1.6;
        }
        
        /* Main layout */
        .main-content {
            padding: 2rem;
            padding-left: 18%;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 50px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .page-actions .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.25);
            transition: var(--transition);
            transform: translateY(0);
        }
        
        .page-actions .btn-primary:hover {
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            background-color: var(--dark-card);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .filter-label {
            font-size: 0.9rem;
            color: var(--gray-light);
        }
        
        .filter-select {
            background-color: var(--dark-bg);
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1000px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background-color: var(--dark-card);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            opacity: 0;
            z-index: -1;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card:hover::before {
            opacity: 0.05;
        }
        
        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--white);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 1px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .data-card {
            background-color: var(--dark-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        
        .data-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .data-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .data-title i {
            color: var(--primary);
        }
        
        .data-body {
            padding: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .chart-container-sm {
            height: 250px;
        }
        
        .content-main {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .history-list {
            background-color: var(--dark-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        
        .history-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .history-title i {
            color: var(--primary);
        }
        
        .filter-controls {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            background-color: var(--dark-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray-light);
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .history-body {
            padding: 0;
            max-height: 600px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--dark-bg);
        }
        
        .history-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .history-body::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }
        
        .history-body::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 20px;
        }
        
        .workout-item {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: background-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .workout-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: var(--transition);
        }
        
        .workout-item:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        
        .workout-item:hover::before {
            opacity: 1;
        }
        
        .workout-item:last-child {
            border-bottom: none;
        }
        
        .workout-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .workout-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .workout-title .workout-type {
            font-size: 0.8rem;
            padding: 4px 8px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            display: inline-block;
        }
        
        .workout-date {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-light);
            font-size: 0.9rem;
        }
        
        .workout-date-day {
            height: 40px;
            width: 40px;
            background: var(--primary-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            font-family: 'Koulen', sans-serif;
            color: var(--white);
        }
        
        .workout-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: var(--gray-light);
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .workout-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .workout-meta-item i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .workout-actions {
            display: flex;
            gap: 10px;
        }

        .workout-action-btn {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray-light);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .workout-action-btn:hover {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .content-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .pr-item {
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 3px solid var(--primary);
            transition: var(--transition);
        }

        .pr-item:hover {
            background-color: rgba(255, 255, 255, 0.07);
            transform: translateX(5px);
        }

        .pr-exercise {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .pr-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            font-family: 'Koulen', sans-serif;
        }

        .pr-date {
            font-size: 0.8rem;
            color: var(--gray-light);
        }

        .common-exercise {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .common-exercise:last-child {
            border-bottom: none;
        }

        .exercise-name {
            font-weight: 500;
        }

        .exercise-count {
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.8rem;
            position: relative;
            background-color: rgba(255, 255, 255, 0.03);
            color: var(--gray-light);
        }

        .calendar-day.has-workout {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .calendar-day.has-workout::after {
            content: '';
            position: absolute;
            bottom: 5px;
            width: 4px;
            height: 4px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        .calendar-day.today {
            border: 1px solid var(--primary);
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 0.7rem;
            color: var(--gray-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: var(--dark-card);
            color: var(--gray-light);
            font-weight: 500;
            transition: var(--transition);
        }

        .page-link:hover,
        .page-link.active {
            background-color: var(--primary);
            color: var(--white);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--gray-light);
            opacity: 0.5;
            margin-bottom: 20px;
        }

        .empty-text {
            color: var(--gray-light);
            margin-bottom: 20px;
        }

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--dark-card);
            color: var(--white);
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .main-content {
                padding-left: 2rem;
            }

            .workout-item {
                flex-direction: column;
                gap: 15px;
            }

            .workout-actions {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Workout History</h1>
            <div class="page-actions">
                <a href="add-workout.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Workout
                </a>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Show:</span>
                <select class="filter-select" id="time-filter">
                    <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="week" <?= $filter == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="quarter" <?= $filter == 'quarter' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>Last Year</option>
                </select>
            </div>
            <div class="filter-group">
                <span class="filter-label">Exercise:</span>
                <select class="filter-select" id="exercise-filter">
                    <option value="">All Exercises</option>
                    <?php foreach ($common_exercises as $ex): ?>
                        <option value="<?= htmlspecialchars($ex['exercise_name']) ?>" <?= $exercise_filter == $ex['exercise_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ex['exercise_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="content-main">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="stat-value"><?= $stats['workout_count'] ?></div>
                        <div class="stat-label">Workouts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_volume'] ?></div>
                        <div class="stat-label">Total Volume (kg)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="stat-value"><?= round($stats['avg_intensity'], 1) ?></div>
                        <div class="stat-label">Avg Intensity</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?= $current_streak ?></div>
                        <div class="stat-label">Current Streak</div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-chart-line"></i> Workout Trends</h3>
                    </div>
                    <div class="data-body">
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-chart-pie"></i> Workout Types</h3>
                    </div>
                    <div class="data-body">
                        <div class="chart-container chart-container-sm">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-chart-bar"></i> Muscle Groups</h3>
                    </div>
                    <div class="data-body">
                        <div class="chart-container chart-container-sm">
                            <canvas id="muscleChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="history-list">
                    <div class="history-header">
                        <h3 class="history-title"><i class="fas fa-history"></i> Recent Workouts</h3>
                        <div class="filter-controls">
                            <button class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>" data-filter="all">All</button>
                            <button class="filter-btn <?= $filter == 'week' ? 'active' : '' ?>" data-filter="week">Week</button>
                            <button class="filter-btn <?= $filter == 'month' ? 'active' : '' ?>" data-filter="month">Month</button>
                        </div>
                    </div>
                    <div class="history-body">
                        <?php if (empty($workout_logs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <h4 class="empty-text">No workouts found</h4>
                                <a href="add-workout.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Your First Workout
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($workout_logs as $workout): ?>
                                <div class="workout-item">
                                    <div class="workout-details">
                                        <div class="workout-title">
                                            <?= htmlspecialchars($workout['name']) ?>
                                            <?php if ($workout['workout_type']): ?>
                                                <span class="workout-type"><?= htmlspecialchars($workout['workout_type']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="workout-date">
                                            <div class="workout-date-day">
                                                <?= date('d', strtotime($workout['created_at'])) ?>
                                            </div>
                                            <div>
                                                <?= date('F j, Y', strtotime($workout['created_at'])) ?>
                                                <span class="workout-time"><?= date('g:i A', strtotime($workout['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="workout-meta">
                                            <div class="workout-meta-item tooltip" title="Duration">
                                                <i class="fas fa-clock"></i>
                                                <?= $workout['duration_minutes'] ? $workout['duration_minutes'] . ' min' : 'N/A' ?>
                                            </div>
                                            <div class="workout-meta-item tooltip" title="Exercises">
                                                <i class="fas fa-dumbbell"></i>
                                                <?= $workout['exercise_count'] ?> exercises
                                            </div>
                                            <div class="workout-meta-item tooltip" title="Total Volume">
                                                <i class="fas fa-weight-hanging"></i>
                                                <?= $workout['total_volume'] ?> kg
                                            </div>
                                            <div class="workout-meta-item tooltip" title="Rating">
                                                <i class="fas fa-star"></i>
                                                <?= $workout['rating'] ? str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) : 'Not rated' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="workout-actions">
                                        <button class="workout-action-btn tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="workout-action-btn tooltip" title="Edit Workout">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="workout-action-btn tooltip" title="Delete Workout">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&exercise=<?= $exercise_filter ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>&filter=<?= $filter ?>&exercise=<?= $exercise_filter ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&exercise=<?= $exercise_filter ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-sidebar">
                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-trophy"></i> Personal Records</h3>
                    </div>
                    <div class="data-body">
                        <?php if (empty($personal_records)): ?>
                            <p style="color: var(--gray-light); text-align: center;">No records yet</p>
                        <?php else: ?>
                            <?php foreach ($personal_records as $pr): ?>
                                <div class="pr-item">
                                    <div class="pr-exercise">
                                        <span><?= htmlspecialchars($pr['exercise_name']) ?></span>
                                        <span class="pr-value"><?= $pr['max_weight'] ?> kg</span>
                                    </div>
                                    <div class="pr-meta">
                                        <span>Max Reps: <?= $pr['max_reps'] ?></span>
                                        <span class="pr-date"><?= date('M j, Y', strtotime($pr['record_date'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-chart-bar"></i> Most Common Exercises</h3>
                    </div>
                    <div class="data-body">
                        <?php if (empty($common_exercises)): ?>
                            <p style="color: var(--gray-light); text-align: center;">No exercises yet</p>
                        <?php else: ?>
                            <?php foreach ($common_exercises as $ex): ?>
                                <div class="common-exercise">
                                    <span class="exercise-name"><?= htmlspecialchars($ex['exercise_name']) ?></span>
                                    <span class="exercise-count"><?= $ex['frequency'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-calendar-alt"></i> Workout Calendar</h3>
                    </div>
                    <div class="data-body">
                        <div class="calendar-header">
                            <span>S</span>
                            <span>M</span>
                            <span>T</span>
                            <span>W</span>
                            <span>T</span>
                            <span>F</span>
                            <span>S</span>
                        </div>
                        <div class="calendar">
                            <?php
                            $first_day = date('w', strtotime($current_month . '-01'));
                            $days_in_month = date('t', strtotime($current_month . '-01'));
                            $today = date('j');
                            
                            for ($i = 0; $i < $first_day; $i++) {
                                echo '<div class="calendar-day"></div>';
                            }
                            
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $has_workout = isset($month_workouts[$day]);
                                $is_today = ($day == $today) && ($current_month == date('Y-m'));
                                
                                $class = 'calendar-day';
                                if ($has_workout) $class .= ' has-workout';
                                if ($is_today) $class .= ' today';
                                
                                echo '<div class="' . $class . '">' . $day . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-header">
                        <h3 class="data-title"><i class="fas fa-chart-line"></i> Additional Stats</h3>
                    </div>
                    <div class="data-body">
                        <div class="stat-item">
                            <div class="stat-label">Workout Frequency</div>
                            <div class="stat-value"><?= $workout_frequency ?> per week</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Best Streak</div>
                            <div class="stat-value"><?= $best_streak ?> days</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Avg Workout Duration</div>
                            <div class="stat-value"><?= round($stats['avg_duration'], 1) ?> min</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Calories Burned</div>
                            <div class="stat-value"><?= $stats['total_calories'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'Workout Volume (kg)',
                        data: <?= $chart_data['volume'] ?>,
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y',
                        parsing: {
                            xAxisKey: 'workout_date',
                            yAxisKey: 'volume'
                        }
                    },
                    {
                        label: 'Avg Intensity',
                        data: <?= $chart_data['intensity'] ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y1',
                        parsing: {
                            xAxisKey: 'workout_date',
                            yAxisKey: 'avg_intensity'
                        }
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM d'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Volume (kg)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        }
                    },
                    y1: {
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Intensity'
                        },
                        grid: {
                            drawOnChartArea: false,
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        min: 0,
                        max: 10
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#FFFFFF'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($workout_types, 'type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($workout_types, 'count')) ?>,
                    backgroundColor: [
                        '#6366F1',
                        '#8B5CF6',
                        '#EC4899',
                        '#F43F5E',
                        '#F59E0B',
                        '#10B981',
                        '#3B82F6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#FFFFFF'
                        }
                    }
                },
                cutout: '70%'
            }
        });

        const muscleCtx = document.getElementById('muscleChart').getContext('2d');
        const muscleChart = new Chart(muscleCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($muscle_groups, 'muscle_group')) ?>,
                datasets: [{
                    label: 'Exercises',
                    data: <?= json_encode(array_column($muscle_groups, 'total')) ?>,
                    backgroundColor: '#6366F1',
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFFFFF'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#FFFFFF'
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

        document.getElementById('time-filter').addEventListener('change', function() {
            const filter = this.value;
            const exercise = document.getElementById('exercise-filter').value;
            window.location.href = `?filter=${filter}&exercise=${exercise}`;
        });

        document.getElementById('exercise-filter').addEventListener('change', function() {
            const exercise = this.value;
            const filter = document.getElementById('time-filter').value;
            window.location.href = `?filter=${filter}&exercise=${exercise}`;
        });

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                window.location.href = `?filter=${filter}`;
            });
        });
    </script>
</body>
</html>