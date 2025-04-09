<?php
session_start();

$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$username = $logged_in ? $_SESSION["username"] : "";
$userId = $logged_in ? $_SESSION["user_id"] : 0;

require_once "../assets/db_connection.php";

$timeFilter = isset($_GET['time']) ? $_GET['time'] : 'weekly';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$categories = [];
$activeLeaderboards = [];

$table_check = $conn->query("SHOW TABLES LIKE 'leaderboard_categories'");
$table_exists = $table_check->num_rows > 0;

if ($table_exists) {
    $sql = "SELECT id, name, description, metric_type, unit FROM leaderboard_categories 
            WHERE is_active = 1 ORDER BY name ASC";
    
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $activeLeaderboards[] = $row;
            if (!in_array($row['metric_type'], $categories)) {
                $categories[] = $row['metric_type'];
            }
        }
        $result->free();
    }
}

function getLeaderboardData($conn, $leaderboardId, $timeFilter, $limit = 10) {
    $leaderboard = null;
    $sql = "SELECT * FROM leaderboard_categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $leaderboardId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $leaderboard = $row;
    }
    $stmt->close();
    
    if (!$leaderboard) {
        return null;
    }
    
    $time_clause = "";
    switch ($timeFilter) {
        case 'daily':
            $time_clause = "AND DATE(ls.created_at) = CURDATE()";
            break;
        case 'weekly':
            $time_clause = "AND ls.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'monthly':
            $time_clause = "AND ls.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'all-time':
        default:
            $time_clause = "";
            break;
    }
    
    $sql = "SELECT u.id, u.username as name, u.profile_image as avatar, MAX(ls.value) as value
            FROM leaderboard_stats ls
            JOIN users u ON ls.user_id = u.id
            WHERE ls.category_id = ? $time_clause
            GROUP BY u.id, u.username
            ORDER BY value DESC
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $leaderboardId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        if (empty($row['avatar'])) {
            $row['avatar'] = '/assets/images/avatars/default.jpg';
        } else {
            $row['avatar'] = '/uploads/profile/' . $row['avatar'];
        }
        $users[] = $row;
    }
    $stmt->close();
    
    if (empty($users)) {
    $users = [
        ['id' => 1, 'name' => 'David Goggins', 'value' => rand(800, 1500), 'avatar' => '/assets/images/avatars/user1.jpg'],
        ['id' => 2, 'name' => 'Kristians Pavlovskis', 'value' => rand(700, 1600), 'avatar' => '/assets/images/avatars/user2.jpg'],
            ['id' => 3, 'name' => 'Eddie Hall', 'value' => rand(750, 1400), 'avatar' => '/assets/images/avatars/user3.jpg']
        ];
    }
    
    usort($users, function($a, $b) {
        return $b['value'] - $a['value'];
    });
    
    foreach ($users as $index => &$user) {
        $user['rank'] = $index + 1;
    }
    
    return [
        'leaderboard' => $leaderboard,
        'users' => $users,
        'timeFrame' => $timeFilter
    ];
}

function getMetricUnit($metricType) {
    $units = [
        'calories_burned' => 'kcal',
        'distance_ran' => 'km',
        'weight_lifted' => 'kg',
        'workout_duration' => 'min',
        'workout_count' => 'workouts',
        'steps' => 'steps',
        'points' => 'pts',
        'xp' => 'XP'
    ];
    
    return isset($units[$metricType]) ? $units[$metricType] : '';
}

function getRankClass($rank) {
    switch ($rank) {
        case 1: return 'gold';
        case 2: return 'silver';
        case 3: return 'bronze';
        default: return '';
    }
}

$featuredLeaderboards = [];
if (!empty($activeLeaderboards)) {
    $featuredCount = min(count($activeLeaderboards), 4);
    for ($i = 0; $i < $featuredCount; $i++) {
        $data = getLeaderboardData($conn, $activeLeaderboards[$i]['id'], $timeFilter, 3);
        if ($data) {
            $featuredLeaderboards[] = $data;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Leaderboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom Leaderboard Styles -->
    <style>
        :root {
            --primary-color: #e61616;
            --primary-gradient: linear-gradient(135deg, #e61616, #9c0000);
            --primary-hover: #c70000;
            --dark-bg: #0a0a0a;
            --dark-bg-surface: #151515;
            --dark-accent: #222222;
            --text-color: #f5f5f5;
            --text-muted: #a0a0a0;
            --border-color: #333333;
            
            --gold: #e61616;       /* Changed to red */
            --silver: #7a7a7a;     /* Kept as silver but darker */
            --bronze: #8d5b28;     /* Kept as bronze but darker */
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Improved Title Section */
        .leaderboard-title-section {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        
        .leaderboard-title-section::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .leaderboard-title {
            font-size: 48px;
            font-weight: 800;
            margin: 0 0 10px;
            color: #fff;
            letter-spacing: 2px;
            position: relative;
            display: inline-block;
            text-shadow: 0 2px 10px rgba(230, 22, 22, 0.3);
        }
        
        .leaderboard-subtitle {
            font-size: 16px;
            font-weight: 400;
            color: var(--text-muted);
            margin: 0;
            letter-spacing: 1px;
        }
        
        /* Enhanced Controls */
        .leaderboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .search-container {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }
        
        .leaderboard-search-bar {
            width: 100%;
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            padding: 14px 20px;
            color: var(--text-color);
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .leaderboard-search-bar:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(230, 22, 22, 0.2);
        }
        
        .leaderboard-search-bar::placeholder {
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .search-btn {
            position: absolute;
            right: 4px;
            top: 4px;
            background: var(--primary-gradient);
            border: none;
            color: #fff;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(230, 22, 22, 0.3);
        }
        
        /* Time Filters */
        .leaderboard-time-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .leaderboard-time-filter {
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .leaderboard-time-filter:hover {
            background-color: rgba(230, 22, 22, 0.1);
            border-color: rgba(230, 22, 22, 0.5);
            transform: translateY(-2px);
        }
        
        .leaderboard-time-filter.active {
            background: var(--primary-gradient);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 10px rgba(230, 22, 22, 0.3);
        }
        
        /* Category Filters */
        .leaderboard-category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 40px;
            justify-content: center;
        }
        
        .leaderboard-category-filter {
            background-color: var(--dark-bg-surface);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .leaderboard-category-filter:hover {
            background-color: rgba(230, 22, 22, 0.1);
            border-color: rgba(230, 22, 22, 0.3);
            transform: translateY(-2px);
        }
        
        .leaderboard-category-filter.active {
            background-color: rgba(230, 22, 22, 0.15);
            border-color: var(--primary-color);
            color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .leaderboard-category-filter.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        /* Improved Stats Container */
        .leaderboard-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        /* Enhanced Card Design */
        .leaderboard-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .leaderboard-stat-card {
            background-color: var(--dark-bg-surface);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .leaderboard-stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            border-color: var(--primary-color);
        }
        
        /* Card Header */
        .leaderboard-stat-header {
            background: var(--primary-gradient);
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .leaderboard-stat-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.6s ease;
        }
        
        .leaderboard-stat-card:hover .leaderboard-stat-header::before {
            opacity: 1;
        }
        
        .leaderboard-stat-title {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .leaderboard-stat-title i {
            font-size: 18px;
            width: 32px;
            height: 32px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .leaderboard-time-badge {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Graph Area */
        .leaderboard-stat-graph {
            padding: 25px;
            display: flex;
            height: 190px;
            justify-content: space-around;
            align-items: flex-end;
            position: relative;
            flex-grow: 1;
        }
        
        .leaderboard-stat-graph::before {
            content: '';
            position: absolute;
            left: 25px;
            right: 25px;
            bottom: 80px;
            height: 1px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .leaderboard-stat-bar {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            width: 30%;
            height: 100%;
            position: relative;
        }
        
        .leaderboard-player-info {
            position: absolute;
            bottom: -75px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 5px;
        }
        
        .leaderboard-rank {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background-color: var(--dark-accent);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .leaderboard-rank.gold {
            background-color: var(--gold);
            color: #fff;
        }
        
        .leaderboard-rank.silver {
            background-color: var(--silver);
            color: #fff;
        }
        
        .leaderboard-rank.bronze {
            background-color: var(--bronze);
            color: #fff;
        }
        
        .leaderboard-player-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            text-align: center;
        }
        
        .leaderboard-player-value {
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
        }
        
        .leaderboard-bar {
            width: 40px;
            background: linear-gradient(to top, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            border-radius: 6px 6px 0 0;
            transition: height 1s ease;
            position: relative;
            overflow: hidden;
        }
        
        .leaderboard-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.2), transparent);
            border-radius: 6px 6px 0 0;
        }
        
        .leaderboard-bar.gold {
            background: linear-gradient(to top, var(--gold), rgba(230, 22, 22, 0.5));
            box-shadow: 0 0 15px rgba(230, 22, 22, 0.3);
        }
        
        .leaderboard-bar.silver {
            background: linear-gradient(to top, var(--silver), rgba(122, 122, 122, 0.5));
        }
        
        .leaderboard-bar.bronze {
            background: linear-gradient(to top, var(--bronze), rgba(141, 91, 40, 0.5));
        }
        
        /* Card Footer */
        .leaderboard-card-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            margin-top: auto;
        }
        
        .view-all-btn {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .leaderboard-stat-card:hover .view-all-btn {
            background-color: rgba(230, 22, 22, 0.1);
        }
        
        .view-all-btn i {
            transition: transform 0.3s ease;
        }
        
        .leaderboard-stat-card:hover .view-all-btn i {
            transform: translateX(4px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            background-color: var(--dark-bg-surface);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 60px 30px;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background-color: var(--dark-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: rgba(255, 255, 255, 0.3);
        }
        
        .empty-state h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-color);
        }
        
        .empty-state p {
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto;
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .leaderboard-stat-card {
            animation: fadeIn 0.6s ease backwards;
        }
        
        /* Apply animation delay to cards in sequence */
        .leaderboard-stats-container > a:nth-child(1) .leaderboard-stat-card { animation-delay: 0.1s; }
        .leaderboard-stats-container > a:nth-child(2) .leaderboard-stat-card { animation-delay: 0.2s; }
        .leaderboard-stats-container > a:nth-child(3) .leaderboard-stat-card { animation-delay: 0.3s; }
        .leaderboard-stats-container > a:nth-child(4) .leaderboard-stat-card { animation-delay: 0.4s; }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .leaderboard-title {
                font-size: 40px;
            }
            
            .leaderboard-stats-container {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .leaderboard-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-container {
                max-width: none;
            }
            
            .leaderboard-time-filters {
                justify-content: center;
            }
            
            .leaderboard-title {
                font-size: 36px;
            }
            
            .leaderboard-category-filters {
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 20px 15px;
            }
            
            .leaderboard-title {
                font-size: 30px;
            }
            
            .leaderboard-stats-container {
                grid-template-columns: 1fr;
            }
            
            .leaderboard-category-filter {
                padding: 8px 14px;
                font-size: 13px;
            }
            
            .leaderboard-time-filter {
                font-size: 13px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body class="leaderboard-page">
    <?php require_once '../includes/header.php'; ?>

    <div class="container">
        <div class="leaderboard-title-section" data-aos="fade-down">
            <h1 class="leaderboard-title">LEADERBOARD</h1>
            <h2 class="leaderboard-subtitle">YOUR FITNESS COMMUNITY</h2>
        </div>

        <div class="leaderboard-controls" data-aos="fade-up">
            <div class="search-container">
                <input type="text" class="leaderboard-search-bar" id="userSearch" placeholder="SEARCH USERS..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="button" id="searchBtn" class="search-btn"><i class="fas fa-search"></i></button>
            </div>
            
            <div class="leaderboard-time-filters">
                <button class="leaderboard-time-filter <?php echo $timeFilter === 'daily' ? 'active' : ''; ?>" data-filter="daily">
                    <i class="fas fa-calendar-day"></i> DAILY
                </button>
                <button class="leaderboard-time-filter <?php echo $timeFilter === 'weekly' ? 'active' : ''; ?>" data-filter="weekly">
                    <i class="fas fa-calendar-week"></i> WEEKLY
                </button>
                <button class="leaderboard-time-filter <?php echo $timeFilter === 'monthly' ? 'active' : ''; ?>" data-filter="monthly">
                    <i class="fas fa-calendar-alt"></i> MONTHLY
                </button>
                <button class="leaderboard-time-filter <?php echo $timeFilter === 'all-time' ? 'active' : ''; ?>" data-filter="all-time">
                    <i class="fas fa-infinity"></i> ALL-TIME
                </button>
            </div>
        </div>

        <div class="leaderboard-category-filters" data-aos="fade-up" data-aos-delay="100">
            <button class="leaderboard-category-filter <?php echo $categoryFilter === 'all' ? 'active' : ''; ?>" data-category="all">
                <i class="fas fa-trophy"></i> ALL
            </button>
            
            <?php foreach($categories as $category): ?>
            <button class="leaderboard-category-filter <?php echo $categoryFilter === $category ? 'active' : ''; ?>" data-category="<?php echo $category; ?>">
                <?php 
                    $icon = 'fas fa-chart-bar';
                    if ($category === 'calories_burned') $icon = 'fas fa-fire-alt';
                    if ($category === 'distance_ran') $icon = 'fas fa-running';
                    if ($category === 'weight_lifted') $icon = 'fas fa-dumbbell';
                    if ($category === 'workout_duration') $icon = 'fas fa-stopwatch';
                    if ($category === 'workout_count') $icon = 'fas fa-check-double';
                    if ($category === 'steps') $icon = 'fas fa-shoe-prints';
                    if ($category === 'points') $icon = 'fas fa-star';
                ?>
                <i class="<?php echo $icon; ?>"></i> <?php echo strtoupper(str_replace('_', ' ', $category)); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($featuredLeaderboards)): ?>
            <div class="empty-state" data-aos="fade-up" data-aos-delay="150">
                <div class="empty-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>No Leaderboards Available</h3>
                <p>No active leaderboards found. Check back later or contact an administrator.</p>
            </div>
        <?php else: ?>
            <div class="leaderboard-stats-container" data-aos="fade-up" data-aos-delay="150">
                <?php foreach($featuredLeaderboards as $data): ?>
                    <a href="detailed-leaderboard.php?id=<?php echo $data['leaderboard']['id']; ?>&time=<?php echo $timeFilter; ?>" class="leaderboard-card-link">
                        <div class="leaderboard-stat-card" data-aos="fade-up" data-aos-delay="150">
                            <div class="leaderboard-stat-header">
                                <h3 class="leaderboard-stat-title">
                                    <?php 
                                        $icon = 'fas fa-chart-bar';
                                        if ($data['leaderboard']['metric_type'] === 'calories_burned') $icon = 'fas fa-fire-alt';
                                        if ($data['leaderboard']['metric_type'] === 'distance_ran') $icon = 'fas fa-running';
                                        if ($data['leaderboard']['metric_type'] === 'weight_lifted') $icon = 'fas fa-dumbbell';
                                        if ($data['leaderboard']['metric_type'] === 'workout_duration') $icon = 'fas fa-stopwatch';
                                        if ($data['leaderboard']['metric_type'] === 'workout_count') $icon = 'fas fa-check-double';
                                        if ($data['leaderboard']['metric_type'] === 'steps') $icon = 'fas fa-shoe-prints';
                                        if ($data['leaderboard']['metric_type'] === 'points') $icon = 'fas fa-star';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars($data['leaderboard']['name']); ?>
                                </h3>
                                <div class="leaderboard-time-badge">
                                    <?php echo ucfirst($timeFilter); ?>
                                </div>
                            </div>
                            
                            <div class="leaderboard-stat-graph">
                                <?php 
                                $maxValue = 0;
                                foreach ($data['users'] as $user) {
                                    if ($user['value'] > $maxValue) $maxValue = $user['value'];
                                }
                                ?>
                                
                                <?php foreach($data['users'] as $user): ?>
                                    <?php $heightPercentage = $maxValue > 0 ? ($user['value'] / $maxValue * 100) : 0; ?>
                                    <div class="leaderboard-stat-bar">
                                        <div class="leaderboard-player-info">
                                            <span class="leaderboard-rank <?php echo getRankClass($user['rank']); ?>">
                                                <?php echo $user['rank']; ?>
                                            </span>
                                            <span class="leaderboard-player-name"><?php echo htmlspecialchars($user['name']); ?></span>
                                            <span class="leaderboard-player-value">
                                                <?php echo number_format($user['value']); ?> 
                                                <?php echo htmlspecialchars($data['leaderboard']['unit']); ?>
                                            </span>
                                        </div>
                                        <div class="leaderboard-bar <?php echo getRankClass($user['rank']); ?>" style="height: <?php echo $heightPercentage; ?>%"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="leaderboard-card-footer">
                                <span class="view-all-btn">View All <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        const timeFilters = document.querySelectorAll('.leaderboard-time-filter');
        timeFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                const timeValue = this.getAttribute('data-filter');
                window.location.href = updateQueryStringParameter(window.location.href, 'time', timeValue);
            });
        });
        
        const categoryFilters = document.querySelectorAll('.leaderboard-category-filter');
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                const categoryValue = this.getAttribute('data-category');
                window.location.href = updateQueryStringParameter(window.location.href, 'category', categoryValue);
            });
        });
        
        const searchBtn = document.getElementById('searchBtn');
        const searchInput = document.getElementById('userSearch');
        
        searchBtn.addEventListener('click', function() {
            const searchValue = searchInput.value.trim();
            if (searchValue) {
                window.location.href = updateQueryStringParameter(window.location.href, 'search', searchValue);
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
        
        function updateQueryStringParameter(uri, key, value) {
            const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            const separator = uri.indexOf('?') !== -1 ? "&" : "?";
            
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
                return uri + separator + key + "=" + value;
            }
        }
    });
    </script>
</body>
</html> 