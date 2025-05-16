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

$today = date('Y-m-d');
$start_date = '';

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        break;
    default:
        $start_date = null;
}

$workouts_query = "
    SELECT 
        id, name, workout_type, duration_minutes, calories_burned, 
        notes, rating, created_at, total_volume
    FROM workouts 
    WHERE user_id = ?";

$params = [$user_id];
$types = "i";

if ($start_date) {
    $workouts_query .= " AND DATE(created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

$workouts_query .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $workouts_query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$workouts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $workouts[] = $row;
}

$summary_query = "
    SELECT 
        COUNT(*) as total_workouts,
        AVG(duration_minutes) as avg_duration,
        SUM(total_volume) as total_volume,
        AVG(rating) as avg_rating
    FROM workouts 
    WHERE user_id = ?";

$summary_params = [$user_id];
$summary_types = "i";

if ($start_date) {
    $summary_query .= " AND DATE(created_at) >= ?";
    $summary_params[] = $start_date;
    $summary_types .= "s";
}

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, $summary_types, ...$summary_params);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);

$formatted_summary = [
    'total_workouts' => (int)$summary['total_workouts'],
    'avg_duration' => round($summary['avg_duration'], 1),
    'total_volume' => round($summary['total_volume'], 1),
    'avg_rating' => round($summary['avg_rating'], 1)
];

$response = [
    'period' => $period,
    'summary' => $formatted_summary,
    'workouts' => $workouts
];

header('Content-Type: application/json');
echo json_encode($response);
?> 