<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];

$period = isset($_GET['period']) ? $_GET['period'] : 'week';
$template = isset($_GET['template_id']) ? $_GET['template_id'] : (isset($_GET['template']) ? $_GET['template'] : 'all');
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

$custom_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$custom_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$today = date('Y-m-d');
$start_date = '';
$previous_start_date = '';
$end_date = $today; 

switch ($period) {
    case 'custom':
        if ($custom_start_date && $custom_end_date) {
            $start_date = $custom_start_date;
            $end_date = $custom_end_date;
            
            $date_diff = (strtotime($custom_end_date) - strtotime($custom_start_date)) / (60 * 60 * 24);
            $previous_start_date = date('Y-m-d', strtotime("$custom_start_date -$date_diff days"));
            $previous_end_date = date('Y-m-d', strtotime("$custom_start_date -1 day"));
        } else {
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $previous_start_date = date('Y-m-d', strtotime('-60 days'));
        }
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $previous_start_date = date('Y-m-d', strtotime('-14 days'));
        break;
    case 'lastweek':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d', strtotime('-1 days'));
        $previous_start_date = date('Y-m-d', strtotime('-14 days'));
        $previous_end_date = date('Y-m-d', strtotime('-8 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $previous_start_date = date('Y-m-d', strtotime('-60 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        $previous_start_date = date('Y-m-d', strtotime('-180 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        $previous_start_date = date('Y-m-d', strtotime('-730 days'));
        break;
    default:
        $start_date = null;
        $previous_start_date = null;
        break;
}

$workouts_query = "
    SELECT 
        id, name, workout_type, duration_minutes, calories_burned, 
        notes, rating, created_at, total_volume, template_id
    FROM workouts 
    WHERE user_id = ?";

$params = [$user_id];
$types = "i";

if ($start_date) {
    $workouts_query .= " AND DATE(created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if ($period != 'all') {
    $workouts_query .= " AND DATE(created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if ($template !== 'all') {
    $workouts_query .= " AND template_id = ?";
    $params[] = $template;
    $types .= "i";
}

if ($rating > 0) {
    $workouts_query .= " AND rating >= ?";
    $params[] = $rating;
    $types .= "i";
}

$workouts_query .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $workouts_query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
if (!mysqli_stmt_execute($stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query execution error: ' . mysqli_stmt_error($stmt)]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Result error: ' . mysqli_error($conn)]);
    exit;
}

$workouts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $volumeTrend = mt_rand(-100, 100);
    $row['volume_trend'] = [
        'value' => abs($volumeTrend),
        'direction' => $volumeTrend >= 0 ? 'up' : 'down'
    ];
    
    $workouts[] = $row;
}

$current_stats_query = "
    SELECT 
        COUNT(*) as total_workouts,
        AVG(duration_minutes) as avg_duration,
        SUM(total_volume) as total_volume,
        SUM(calories_burned) as total_calories,
        AVG(rating) as avg_rating
    FROM workouts 
    WHERE user_id = ?";

$current_params = [$user_id];
$current_types = "i";

if ($start_date) {
    $current_stats_query .= " AND DATE(created_at) >= ?";
    $current_params[] = $start_date;
    $current_types .= "s";
}

if ($period != 'all') {
    $current_stats_query .= " AND DATE(created_at) <= ?";
    $current_params[] = $end_date;
    $current_types .= "s";
}

if ($template !== 'all') {
    $current_stats_query .= " AND template_id = ?";
    $current_params[] = $template;
    $current_types .= "i";
}

if ($rating > 0) {
    $current_stats_query .= " AND rating >= ?";
    $current_params[] = $rating;
    $current_types .= "i";
}

$current_stats_stmt = mysqli_prepare($conn, $current_stats_query);
if (!$current_stats_stmt) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Stats query error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($current_stats_stmt, $current_types, ...$current_params);
if (!mysqli_stmt_execute($current_stats_stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Stats execution error: ' . mysqli_stmt_error($current_stats_stmt)]);
    exit;
}

$current_stats_result = mysqli_stmt_get_result($current_stats_stmt);
if (!$current_stats_result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Stats result error: ' . mysqli_error($conn)]);
    exit;
}

$current_stats = mysqli_fetch_assoc($current_stats_result);
if (!$current_stats) {
    $current_stats = [
        'total_workouts' => 0,
        'avg_duration' => 0,
        'total_volume' => 0,
        'total_calories' => 0,
        'avg_rating' => 0
    ];
}

if ($start_date && $previous_start_date) {
    $previous_stats_query = "
        SELECT 
            COUNT(*) as total_workouts,
            AVG(duration_minutes) as avg_duration,
            SUM(total_volume) as total_volume,
            SUM(calories_burned) as total_calories
        FROM workouts 
        WHERE user_id = ? AND DATE(created_at) >= ? AND DATE(created_at) < ?";

    $previous_params = [$user_id, $previous_start_date, $start_date];
    $previous_types = "iss";

    if (isset($previous_end_date)) {
        $previous_stats_query .= " AND DATE(created_at) <= ?";
        $previous_params[] = $previous_end_date;
        $previous_types .= "s";
    }

    if ($template !== 'all') {
        $previous_stats_query .= " AND template_id = ?";
        $previous_params[] = $template;
        $previous_types .= "i";
    }

    if ($rating > 0) {
        $previous_stats_query .= " AND rating >= ?";
        $previous_params[] = $rating;
        $previous_types .= "i";
    }

    $previous_stats_stmt = mysqli_prepare($conn, $previous_stats_query);
    mysqli_stmt_bind_param($previous_stats_stmt, $previous_types, ...$previous_params);
    mysqli_stmt_execute($previous_stats_stmt);
    $previous_stats_result = mysqli_stmt_get_result($previous_stats_stmt);
    $previous_stats = mysqli_fetch_assoc($previous_stats_result);

    $trends = [
        'workouts' => (int)$current_stats['total_workouts'] - (int)$previous_stats['total_workouts'],
        'duration' => round($current_stats['avg_duration'] - $previous_stats['avg_duration']),
        'volume' => round($current_stats['total_volume'] - $previous_stats['total_volume']),
        'calories' => round($current_stats['total_calories'] - $previous_stats['total_calories'])
    ];
} else {
    $trends = null;
}

$formatted_summary = [
    'total_workouts' => (int)$current_stats['total_workouts'],
    'avg_duration' => $current_stats['avg_duration'] !== null ? round($current_stats['avg_duration']) : 0,
    'total_volume' => $current_stats['total_volume'] !== null ? round($current_stats['total_volume']) : 0,
    'total_calories' => $current_stats['total_calories'] !== null ? round($current_stats['total_calories']) : 0,
    'avg_rating' => $current_stats['avg_rating'] !== null ? round($current_stats['avg_rating'], 1) : 0
];

if ($trends) {
    $formatted_summary['trend'] = [
        'workouts' => (int)$trends['workouts'],
        'duration' => round($trends['duration']),
        'volume' => round($trends['volume']),
        'calories' => round($trends['calories'])
    ];
}

$response = [
    'period' => $period,
    'template' => $template,
    'rating' => $rating,
    'summary' => $formatted_summary,
    'workouts' => $workouts
];

header('Content-Type: application/json');
echo json_encode($response);
?> 