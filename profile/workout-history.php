<?php

require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout-history.php");
    exit;
}

require_once '../assets/db_connection.php';
require_once 'languages.php';

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
    <title><?= t('workout_history') ?> - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../profile/styles.css">
    <link rel="stylesheet" href="../profile/global-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="wh-dashboard">
    <?php include 'sidebar.php'; ?>

    <div class="wh-main-content">
        <div class="wh-page-header">
            <h1 class="wh-page-title"><?= t('workout_history') ?></h1>
            <div style="display: flex; gap: 16px; align-items: center;">
                <button class="wh-export-btn">
                    <i class="fas fa-file-export"></i> <?= t('export_data') ?>
                </button>
            </div>
        </div>

        <div class="wh-desktop-container">
            <div class="wh-desktop-main">
                
                <div style="display: grid; grid-template-columns: 250px 1fr; gap: 24px;">
                    <div>
                        <div class="wh-filters">
                            <h3><?= t('filters') ?></h3>
                            <div class="wh-filter-group">
                                <label class="wh-filter-label"><?= t('templates') ?></label>
                                <select class="wh-filter-select" id="template-filter">
                                    <option value="all"><?= t('all_templates') ?></option>
                                    
                                </select>
                            </div>
                            
                            <div class="wh-filter-group">
                                <label class="wh-filter-label"><?= t('rating') ?></label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="range" min="0" max="5" value="0" step="1" class="wh-rating-slider" id="rating-filter">
                                    <span id="rating-value"><?= t('all') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wh-summary-stats">
                            <h3><?= t('summary_stats') ?></h3>
                            <div class="wh-stat-row">
                                <span class="wh-stat-label"><?= t('total_workouts') ?></span>
                                <span class="wh-stat-value"><?= $stats['workout_count'] ?? 0 ?></span>
                            </div>
                            <div class="wh-stat-row">
                                <span class="wh-stat-label"><?= t('avg_duration') ?></span>
                                <span class="wh-stat-value"><?= isset($stats['avg_duration']) ? round($stats['avg_duration'], 1) : 0 ?> <?= t('min') ?></span>
                            </div>
                            <div class="wh-stat-row">
                                <span class="wh-stat-label"><?= t('total_volume') ?></span>
                                <span class="wh-stat-value"><?= isset($stats['total_volume']) ? number_format($stats['total_volume'], 1) : 0 ?> <?= t('kg') ?></span>
                            </div>
                            <div class="wh-stat-row">
                                <span class="wh-stat-label"><?= t('total_calories') ?></span>
                                <span class="wh-stat-value"><?= isset($stats['total_calories']) ? number_format($stats['total_calories']) : 0 ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="wh-workout-table">
                            <div class="wh-table-header">
                                <div><?= t('date') ?></div>
                                <div><?= t('workout_name') ?></div>
                                <div><?= t('duration') ?></div>
                                <div><?= t('volume') ?></div>
                                <div><?= t('calories') ?></div>
                                <div><?= t('rating') ?></div>
                                <div><?= t('action') ?></div>
                            </div>

                            <?php if (!empty($workout_logs)): ?>
                                <?php foreach ($workout_logs as $workout): ?>
                                    <div class="wh-workout-row">
                                        <div><?= date('M d, Y', strtotime($workout['created_at'])) ?></div>
                                        <div><?= htmlspecialchars($workout['name']) ?></div>
                                        <div><?= $workout['duration_minutes'] ?> <?= t('min') ?></div>
                                        <div><?= $workout['total_volume'] ?> <?= t('kg') ?></div>
                                        <div><?= $workout['calories_burned'] ?></div>
                                        <div class="wh-star-rating">
                                            <?= str_repeat('★', $workout['rating']) . str_repeat('☆', 5 - $workout['rating']) ?>
                                        </div>
                                        <div>
                                            <a href="workout-details.php?id=<?= $workout['id'] ?>" class="wh-view-btn"><?= t('view') ?></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="wh-pagination">
                            <div class="wh-page-item">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="wh-page-item active">1</div>
                            <div class="wh-page-item">2</div>
                            <div class="wh-page-item">3</div>
                            <div class="wh-page-item">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="wh-mobile-container">
            <div class="wh-period-tabs">
                <div class="wh-period-tab active"><?= t('this_week') ?></div>
                <div class="wh-period-tab"><?= t('last_week') ?></div>
                <div class="wh-period-tab"><?= t('this_month') ?></div>
                <div class="wh-period-tab"><?= t('custom') ?></div>
            </div>
            
            <div class="wh-stats-grid">
                <div class="wh-stats-card">
                    <div class="wh-stats-value">12</div>
                    <div class="wh-stats-label"><?= t('total_workouts') ?></div>
                </div>
                <div class="wh-stats-card">
                    <div class="wh-stats-value">45m</div>
                    <div class="wh-stats-label"><?= t('avg_duration') ?></div>
                </div>
                <div class="wh-stats-card">
                    <div class="wh-stats-value">2.4t</div>
                    <div class="wh-stats-label"><?= t('total_volume') ?></div>
                </div>
                <div class="wh-stats-card">
                    <div class="wh-stats-value">8.2k</div>
                    <div class="wh-stats-label"><?= t('calories') ?></div>
                </div>
            </div>
            
            <?php if (!empty($workout_logs)): ?>
                <?php foreach ($workout_logs as $key => $workout): ?>
                    <?php if ($key <= 1): ?>
                    <div class="wh-workout-card">
                        <div class="wh-workout-header">
                            <div>
                                <div class="wh-workout-title">
                                    <?= $key == 0 ? t('upper_body_strength') : t('leg_day') ?>
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="wh-workout-date">
                                    <?= $key == 0 ? 'May 6, 2025' : 'May 5, 2025' ?> • 
                                    <?= $key == 0 ? '50' : '65' ?><?= t('min') ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wh-workout-meta">
                            <div class="wh-workout-volume">
                                <?= t('volume') ?>: <?= $key == 0 ? '850' : '1200' ?><?= t('kg') ?>
                            </div>
                        </div>
                        
                        <div class="wh-star-rating">
                            <?= $key == 0 ? '★★★★☆' : '★★★☆☆' ?>
                        </div>

                        <?php if ($key == 1): ?>
                        <div class="wh-workout-notes">
                            <b><?= t('notes') ?>:</b> <?= t('notes_felt_strong') ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="wh-card-actions">
                            <button class="wh-card-btn wh-secondary-btn"><?= t('view_details') ?></button>
                            <button class="wh-icon-btn">
                                <i class="fas fa-share"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="wh-workout-card">
                    <p style="text-align: center;"><?= t('no_workout_history_found') ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="exportModal" class="wh-modal">
        <div class="wh-modal-content">
            <h3 style="margin-top: 0;"><?= t('export_workout_data') ?></h3>
            <p><?= t('select_time_period') ?></p>
            
            <div style="margin: 20px 0;">
                <div class="wh-export-option">
                    <label>
                        <input type="radio" name="exportPeriod" value="week" checked> <?= t('latest_week') ?>
                    </label>
                </div>
                <div class="wh-export-option">
                    <label>
                        <input type="radio" name="exportPeriod" value="month"> <?= t('latest_month') ?>
                    </label>
                </div>
                <div class="wh-export-option">
                    <label>
                        <input type="radio" name="exportPeriod" value="year"> <?= t('latest_year') ?>
                    </label>
                </div>
                <div class="wh-export-option">
                    <label>
                        <input type="radio" name="exportPeriod" value="all"> <?= t('all_time') ?>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                <button id="cancelExport" class="wh-card-btn wh-secondary-btn" style="flex: 0 0 auto; padding: 10px 16px;"><?= t('cancel') ?></button>
                <button id="confirmExport" class="wh-card-btn wh-primary-btn" style="flex: 0 0 auto; padding: 10px 16px;"><?= t('export_pdf') ?></button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.wh-toggle-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                });
            });
        
            const periodTabs = document.querySelectorAll('.wh-period-tab');
            periodTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    periodTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    const period = this.textContent.trim().toLowerCase().replace(/\s/g, '-');
                    loadMobileWorkouts(period);
                });
            });
            
            function loadMobileWorkouts(period, customDates = null) {
                const mobileContainer = document.querySelector('.wh-mobile-container');
                const workoutCards = mobileContainer.querySelectorAll('.wh-workout-card:not(.wh-stats-card)');
                
                workoutCards.forEach(card => card.remove());
                
                const loadingEl = document.createElement('div');
                loadingEl.className = 'wh-workout-card';
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
                            noDataEl.className = 'wh-workout-card';
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
                        total_calories: 0
                    };
                }

                const elements = {
                    totalWorkouts: document.querySelector('.wh-stats-grid .wh-stats-card:nth-child(1) .wh-stats-value'),
                    avgDuration: document.querySelector('.wh-stats-grid .wh-stats-card:nth-child(2) .wh-stats-value'),
                    totalVolume: document.querySelector('.wh-stats-grid .wh-stats-card:nth-child(3) .wh-stats-value'),
                    totalCalories: document.querySelector('.wh-stats-grid .wh-stats-card:nth-child(4) .wh-stats-value')
                };

                const values = {
                    totalWorkouts: Math.round(Number(summary.total_workouts) || 0),
                    avgDuration: Math.round(Number(summary.avg_duration) || 0),
                    totalVolume: Math.round(Number(summary.total_volume) || 0),
                    totalCalories: Math.round(Number(summary.total_calories) || 0)
                };

                if (elements.totalWorkouts) elements.totalWorkouts.textContent = `${values.totalWorkouts}`;
                if (elements.avgDuration) elements.avgDuration.textContent = `${values.avgDuration}m`;
                if (elements.totalVolume) elements.totalVolume.textContent = `${values.totalVolume.toLocaleString()}kg`;
                if (elements.totalCalories) elements.totalCalories.textContent = `${values.totalCalories.toLocaleString()}kcal`;
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
                card.className = 'wh-workout-card';
            
                const date = new Date(workout.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                card.innerHTML = `
                    <div class="wh-workout-header">
                        <div>
                            <div class="wh-workout-title">
                                ${workout.name}
                                <i class="fas fa-dumbbell"></i>
                            </div>
                            <div class="wh-workout-date">
                                ${formattedDate} • ${workout.duration_minutes}min
                            </div>
                        </div>
                    </div>
                    
                    <div class="wh-workout-meta">
                        <div class="wh-workout-volume">
                            Volume: ${workout.total_volume}kg
                        </div>
                    </div>
                    
                    <div class="wh-star-rating">
                        ${'★'.repeat(workout.rating)}${'☆'.repeat(5 - workout.rating)}
                    </div>
                    
                    ${workout.notes ? `<div class="wh-workout-notes"><b>Notes:</b> ${workout.notes}</div>` : ''}
                    
                    <div class="wh-card-actions">
                        <button class="wh-card-btn wh-secondary-btn" onclick="viewWorkoutDetails(${workout.id})">View Details</button>
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
                const dateSelectSpan = document.querySelector('.wh-date-select span');
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
                
                const tableBody = document.querySelector('.wh-workout-table');
                if (!tableBody) {
                    console.error('Workout table not found');
                    return;
                }
                
                const headerRow = tableBody.querySelector('.wh-table-header');
                const workoutRows = tableBody.querySelectorAll('.wh-workout-row');
                
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
                            
                            const pagination = document.querySelector('.wh-pagination');
                            if (pagination) {
                                pagination.style.display = 'none';
                            }
                        } else {
                            data.workouts.forEach(workout => {
                                const row = createWorkoutRow(workout);
                                tableBody.appendChild(row);
                            });
                            
                            initPagination(data.workouts.length, data.total_count || data.workouts.length);
                            
                            const pagination = document.querySelector('.wh-pagination');
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
                        
                        const pagination = document.querySelector('.wh-pagination');
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
                    totalWorkouts: document.querySelector('.wh-summary-stats .wh-stat-row:nth-child(1) .wh-stat-value'),
                    avgDuration: document.querySelector('.wh-summary-stats .wh-stat-row:nth-child(2) .wh-stat-value'),
                    totalVolume: document.querySelector('.wh-summary-stats .wh-stat-row:nth-child(3) .wh-stat-value'),
                    totalCalories: document.querySelector('.wh-summary-stats .wh-stat-row:nth-child(4) .wh-stat-value')
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
                row.className = 'wh-workout-row';
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
                    <div class="wh-star-rating">
                        ${'★'.repeat(workout.rating)}${'☆'.repeat(5 - workout.rating)}
                    </div>
                    <div>
                        <a href="workout-details.php?id=${workout.id}" class="wh-view-btn">View</a>
                    </div>
                `;
                
                return row;
            }
            
            let currentPage = 1;
            const ROWS_PER_PAGE = 10;
            
            function initPagination(displayedRows, totalRows = displayedRows) {
                const totalPages = Math.ceil(totalRows / ROWS_PER_PAGE);
                const paginationContainer = document.querySelector('.wh-pagination');
                
                if (!paginationContainer) {
                    console.error('Pagination container not found');
                    return;
                }
            
                paginationContainer.innerHTML = '';
                
                const prevButton = document.createElement('div');
                prevButton.className = 'wh-page-item';
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
                    pageItem.className = 'wh-page-item';
                    if (i === currentPage) pageItem.classList.add('active');
                    pageItem.textContent = i;
                    pageItem.addEventListener('click', () => goToPage(i));
                    paginationContainer.appendChild(pageItem);
                }
                
                const nextButton = document.createElement('div');
                nextButton.className = 'wh-page-item';
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
                
                const rows = document.querySelectorAll('.wh-workout-row');
                const start = (page - 1) * ROWS_PER_PAGE;
                const end = start + ROWS_PER_PAGE;
                
                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });

                const pageItems = document.querySelectorAll('.wh-page-item');
                pageItems.forEach(item => {
                    if (!item.querySelector('i')) {
                        item.classList.remove('active');
                        if (parseInt(item.textContent) === page) {
                            item.classList.add('active');
                        }
                    }
                });
                
                const prevButton = document.querySelector('.wh-pagination .wh-page-item:first-child');
                const nextButton = document.querySelector('.wh-pagination .wh-page-item:last-child');
                
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
        
            const dateSelect = document.querySelector('.wh-date-select');
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
                            
                            const statsValues = document.querySelectorAll('.wh-stat-value');
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

            const exportButtons = document.querySelectorAll('.wh-export-btn');
            const exportModal = document.getElementById('exportModal');
            const confirmExport = document.getElementById('confirmExport');
            const cancelExport = document.getElementById('cancelExport');
            const exportOptions = document.querySelectorAll('.wh-export-option');

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
                    <div id="customDateModal" class="wh-modal" style="display: flex;">
                        <div class="wh-modal-content">
                            <h3 style="margin-top: 0;"><?= t('select_date_range') ?></h3>
                            
                            <div style="margin: 20px 0;">
                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; margin-bottom: 8px; color: var(--gray-light);"><?= t('start_date') ?></label>
                                    <input type="date" id="customStartDate" style="width: 100%; padding: 10px; border-radius: 6px; background: var(--dark-bg); border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 8px; color: var(--gray-light);"><?= t('end_date') ?></label>
                                    <input type="date" id="customEndDate" style="width: 100%; padding: 10px; border-radius: 6px; background: var(--dark-bg); border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                                <button id="cancelCustomDate" class="wh-card-btn wh-secondary-btn" style="flex: 0 0 auto; padding: 10px 16px;"><?= t('cancel') ?></button>
                                <button id="applyCustomDate" class="wh-card-btn wh-primary-btn" style="flex: 0 0 auto; padding: 10px 16px;"><?= t('apply') ?></button>
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
            
            if (document.querySelector('.wh-period-tab.active')) {
                const activePeriod = document.querySelector('.wh-period-tab.active').textContent.trim().toLowerCase().replace(/\s/g, '-');
                loadMobileWorkouts(activePeriod);
            }
        });
    </script>
</body>
</html>