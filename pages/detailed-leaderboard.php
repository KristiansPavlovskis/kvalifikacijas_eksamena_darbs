<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Detailed Leaderboard";
$bodyClass = "detailed-leaderboard-page dark-theme";

require_once "../assets/db_connection.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: leaderboard.php");
    exit;
}

$category_id = $_GET['id'];

$category_sql = "SELECT * FROM leaderboard_categories WHERE id = ? AND is_active = 1";
$stmt = $conn->prepare($category_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if ($category_result->num_rows === 0) {
    header("Location: leaderboard.php");
    exit;
}

$category = $category_result->fetch_assoc();
$stmt->close();

$time_filter = isset($_GET['time']) ? $_GET['time'] : 'all-time';

$time_clause = "";
$date_range_display = "";
switch ($time_filter) {
    case 'daily':
        $time_clause = "AND DATE(ls.created_at) = CURDATE()";
        $date_range_display = "Today";
        break;
    case 'weekly':
        $time_clause = "AND ls.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $date_range_display = "Past 7 Days";
        break;
    case 'monthly':
        $time_clause = "AND ls.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $date_range_display = "Past 30 Days";
        break;
    case 'all-time':
    default:
        $time_clause = "";
        $date_range_display = "All Time";
        break;
}

$limit = 100; 
$leaderboard_sql = "
    SELECT u.id, u.username, u.profile_image, MAX(ls.value) as best_score
    FROM leaderboard_stats ls
    JOIN users u ON ls.user_id = u.id
    WHERE ls.category_id = ? $time_clause
    GROUP BY u.id, u.username
    ORDER BY best_score DESC
    LIMIT ?
";

$stmt = $conn->prepare($leaderboard_sql);
$stmt->bind_param("ii", $category_id, $limit);
$stmt->execute();
$leaderboard_result = $stmt->get_result();
$leaderboard_data = [];

while ($row = $leaderboard_result->fetch_assoc()) {
    $leaderboard_data[] = $row;
}
$stmt->close();

if (empty($leaderboard_data)) {
    $sample_names = [
        'David Goggins', 'Kristians Pavlovskis', 'Eddie Hall', 'John Doe', 'Sarah Connor',
        'Mike Tyson', 'Jane Smith', 'Arnold Schwarzenegger', 'Lisa Johnson', 'Chris Evans',
        'Emma Watson', 'Hugh Jackman', 'Scarlett Johansson', 'Dwayne Johnson', 'Natalie Portman'
    ];
    
    $score_range = [500, 2000];
    
    for ($i = 0; $i < 15; $i++) {
        $leaderboard_data[] = [
            'id' => $i + 1,
            'username' => $sample_names[$i],
            'profile_image' => null,
            'best_score' => rand($score_range[0], $score_range[0] + ($score_range[1] - $score_range[0]) * (1 - $i/15))
        ];
    }
}

$user_rank = null;
$user_score = null;
if (isset($_SESSION['user_id'])) {
    foreach ($leaderboard_data as $index => $player) {
        if ($player['id'] == $_SESSION['user_id']) {
            $user_rank = $index + 1;
            $user_score = $player['best_score'];
            break;
        }
    }
}
    
include_once dirname(__DIR__) . "/includes/header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<main class="detailed-leaderboard-container">
    <div class="leaderboard-header-bar">
        <div class="back-btn-container">
            <a href="/pages/leaderboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Leaderboards
            </a>
        </div>
        
        <div class="leaderboard-filters">
            <div class="time-period-dropdown">
                <button class="time-period-btn">
                    <i class="fas fa-calendar-alt"></i> <?php echo $date_range_display; ?> <i class="fas fa-chevron-down"></i>
                </button>
                <div class="time-period-options">
                    <a href="?id=<?php echo $category_id; ?>&time=daily" class="<?php echo $time_filter === 'daily' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-day"></i> Daily
                    </a>
                    <a href="?id=<?php echo $category_id; ?>&time=weekly" class="<?php echo $time_filter === 'weekly' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-week"></i> Weekly
                    </a>
                    <a href="?id=<?php echo $category_id; ?>&time=monthly" class="<?php echo $time_filter === 'monthly' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Monthly
                    </a>
                    <a href="?id=<?php echo $category_id; ?>&time=all-time" class="<?php echo $time_filter === 'all-time' ? 'active' : ''; ?>">
                        <i class="fas fa-infinity"></i> All Time
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="detailed-leaderboard-header">
        <div class="leaderboard-title-section">
            <?php 
                $icon = 'fas fa-trophy';
                if ($category['metric_type'] === 'calories_burned') $icon = 'fas fa-fire-alt';
                if ($category['metric_type'] === 'distance_ran') $icon = 'fas fa-running';
                if ($category['metric_type'] === 'weight_lifted') $icon = 'fas fa-dumbbell';
                if ($category['metric_type'] === 'workout_duration') $icon = 'fas fa-stopwatch';
                if ($category['metric_type'] === 'workout_count') $icon = 'fas fa-check-double';
                if ($category['metric_type'] === 'steps') $icon = 'fas fa-shoe-prints';
                if ($category['metric_type'] === 'points') $icon = 'fas fa-star';
            ?>
            <div class="leaderboard-icon">
                <i class="<?php echo $icon; ?>"></i>
            </div>
            <div class="leaderboard-text">
                <h1><?php echo htmlspecialchars($category['name']); ?></h1>
                <p class="leaderboard-description"><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
        </div>
        
        <?php if ($user_rank): ?>
        <div class="user-stats-card">
            <div class="user-rank-display">
                <span class="rank-label">YOUR RANK</span>
                <span class="rank-value">#<?php echo $user_rank; ?></span>
            </div>
            <div class="user-score-display">
                <span class="score-label">YOUR SCORE</span>
                <span class="score-value"><?php echo number_format($user_score); ?> <?php echo htmlspecialchars($category['unit']); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (count($leaderboard_data) > 0): ?>
        <div class="leaderboard-podium-section">
            <h2 class="podium-title">Top Players</h2>
            <div class="top-players-podium">
                <?php 
                if (isset($leaderboard_data[1])): 
                    $player = $leaderboard_data[1];
                    $profile_img = !empty($player['profile_image']) ? '/uploads/profile/' . $player['profile_image'] : '/assets/images/default-profile.png';
                ?>
                <div class="podium-player second-place">
                    <div class="medal-badge">
                        <i class="fas fa-medal"></i>
                        <span>2</span>
                    </div>
                    <div class="player-avatar">
                        <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($player['username']); ?>">
                    </div>
                    <h3 class="player-name"><?php echo htmlspecialchars($player['username']); ?></h3>
                    <div class="player-score"><?php echo number_format($player['best_score']); ?> <?php echo htmlspecialchars($category['unit']); ?></div>
                    <div class="podium-block second"></div>
                </div>
                <?php endif; ?>
                
                <?php 
                if (isset($leaderboard_data[0])): 
                    $player = $leaderboard_data[0];
                    $profile_img = !empty($player['profile_image']) ? '/uploads/profile/' . $player['profile_image'] : '/assets/images/default-profile.png';
                ?>
                <div class="podium-player first-place">
                    <div class="crown-badge">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="medal-badge">
                        <i class="fas fa-medal"></i>
                        <span>1</span>
                    </div>
                    <div class="player-avatar">
                        <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($player['username']); ?>">
                    </div>
                    <h3 class="player-name"><?php echo htmlspecialchars($player['username']); ?></h3>
                    <div class="player-score"><?php echo number_format($player['best_score']); ?> <?php echo htmlspecialchars($category['unit']); ?></div>
                    <div class="podium-block first"></div>
                </div>
                <?php endif; ?>
                
                <?php 
                if (isset($leaderboard_data[2])): 
                    $player = $leaderboard_data[2];
                    $profile_img = !empty($player['profile_image']) ? '/uploads/profile/' . $player['profile_image'] : '/assets/images/default-profile.png';
                ?>
                <div class="podium-player third-place">
                    <div class="medal-badge">
                        <i class="fas fa-medal"></i>
                        <span>3</span>
                    </div>
                    <div class="player-avatar">
                        <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($player['username']); ?>">
                    </div>
                    <h3 class="player-name"><?php echo htmlspecialchars($player['username']); ?></h3>
                    <div class="player-score"><?php echo number_format($player['best_score']); ?> <?php echo htmlspecialchars($category['unit']); ?></div>
                    <div class="podium-block third"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="leaderboard-table-section">
            <h2>Leaderboard Rankings</h2>
            
            <?php if (count($leaderboard_data) > 3): ?>
            <div class="leaderboard-table-wrapper">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th class="rank-column">Rank</th>
                            <th class="player-column">Player</th>
                            <th class="score-column">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            for ($i = 3; $i < count($leaderboard_data); $i++):
                            $player = $leaderboard_data[$i];
                            $profile_img = !empty($player['profile_image']) ? '/uploads/profile/' . $player['profile_image'] : '/assets/images/default-profile.png';
                            $rank = $i + 1;
                            $highlight_class = '';
                            
                            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $player['id']) {
                                $highlight_class = 'highlight-row';
                            }
                        ?>
                        <tr class="<?php echo $highlight_class; ?>">
                            <td class="rank-column"><?php echo $rank; ?></td>
                            <td class="player-column">
                                <div class="player-info-row">
                                    <div class="player-avatar">
                                        <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($player['username']); ?>">
                                    </div>
                                    <div class="player-name"><?php echo htmlspecialchars($player['username']); ?></div>
                                </div>
                            </td>
                            <td class="score-column"><?php echo number_format($player['best_score']); ?> <span class="unit"><?php echo htmlspecialchars($category['unit']); ?></span></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-trophy empty-trophy"></i>
            <h3>No Leaderboard Data Available</h3>
            <p>Be the first to compete in this <?php echo strtolower(htmlspecialchars($category['name'])); ?> leaderboard!</p>
            <?php if ($logged_in): ?>
            <a href="/pages/workout.php" class="cta-button">Start Working Out</a>
            <?php else: ?>
            <a href="/pages/login.php" class="cta-button">Sign In to Compete</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<style>
/* Dark Theme Styles for Detailed Leaderboard */
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

body.dark-theme {
    background-color: var(--dark-bg);
    color: var(--text-color);
}

.detailed-leaderboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header Bar with Back Button and Filters */
.leaderboard-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.back-btn {
    display: inline-flex;
    align-items: center;
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 8px 15px;
    border-radius: 20px;
    background-color: rgba(230, 22, 22, 0.1);
}

.back-btn:hover {
    background-color: rgba(230, 22, 22, 0.2);
    color: var(--primary-color);
}

.back-btn i {
    margin-right: 8px;
}

/* Time Period Filter */
.leaderboard-filters {
    display: flex;
    align-items: center;
}

.time-period-dropdown {
    position: relative;
}

.time-period-btn {
    display: flex;
    align-items: center;
    background-color: rgba(230, 22, 22, 0.1);
    color: var(--text-color);
    border: none;
    padding: 8px 15px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.time-period-btn i:first-child {
    margin-right: 8px;
}

.time-period-btn i:last-child {
    margin-left: 8px;
    font-size: 12px;
}

.time-period-options {
    position: absolute;
    top: 100%;
    right: 0;
    width: 180px;
    background-color: var(--dark-bg-surface);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    padding: 10px 0;
    margin-top: 5px;
    z-index: 10;
    display: none;
    border: 1px solid var(--border-color);
}

.time-period-dropdown:hover .time-period-options {
    display: block;
}

.time-period-options a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.time-period-options a:hover {
    background-color: rgba(230, 22, 22, 0.1);
}

.time-period-options a.active {
    background-color: rgba(230, 22, 22, 0.15);
    color: var(--primary-color);
}

.time-period-options a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Leaderboard Title Section */
.detailed-leaderboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.leaderboard-title-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.leaderboard-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    box-shadow: 0 4px 12px rgba(230, 22, 22, 0.3);
}

.leaderboard-text h1 {
    font-size: 30px;
    margin: 0 0 5px 0;
    color: #fff;
    font-weight: 700;
}

.leaderboard-description {
    color: var(--text-muted);
    margin: 0;
    max-width: 600px;
}

/* User Stats Card */
.user-stats-card {
    background: linear-gradient(135deg, rgba(230, 22, 22, 0.1), rgba(230, 22, 22, 0.2));
    border-radius: 8px;
    padding: 15px 20px;
    display: flex;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(230, 22, 22, 0.3);
}

.user-rank-display, .user-score-display {
    display: flex;
    flex-direction: column;
}

.rank-label, .score-label {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 5px;
}

.rank-value, .score-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

/* Podium Section */
.leaderboard-podium-section {
    margin-bottom: 50px;
}

.podium-title {
    text-align: center;
    font-size: 24px;
    margin-bottom: 30px;
    color: #fff;
    position: relative;
}

.podium-title:after {
    content: '';
    display: block;
    width: 80px;
    height: 3px;
    background: var(--primary-gradient);
    margin: 10px auto 0;
    border-radius: 3px;
}

.top-players-podium {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 10px;
    height: 400px;
    position: relative;
    padding: 0 20px;
}

.podium-player {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: calc(100% / 3 - 20px);
    max-width: 200px;
    padding-bottom: 120px;
}

.player-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--dark-accent);
    z-index: 2;
}

.podium-player.first-place .player-avatar {
    width: 90px;
    height: 90px;
    border: 4px solid var(--primary-color);
    box-shadow: 0 0 15px rgba(230, 22, 22, 0.5);
}

.player-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.player-name {
    margin: 10px 0 5px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    color: #fff;
}

.podium-player.first-place .player-name {
    font-size: 18px;
}

.player-score {
    font-size: 14px;
    color: var(--text-muted);
    text-align: center;
}

.podium-player.first-place .player-score {
    color: var(--primary-color);
    font-weight: 600;
}

/* Podium blocks */
.podium-block {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: var(--dark-accent);
    border-radius: 8px 8px 0 0;
    z-index: 1;
}

.podium-block.first {
    height: 120px;
    background: var(--primary-gradient);
}

.podium-block.second {
    height: 90px;
    background: linear-gradient(135deg, #7a7a7a, #555555);
}

.podium-block.third {
    height: 70px;
    background: linear-gradient(135deg, #8d5b28, #6b4420);
}

/* Medal badges */
.medal-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--dark-bg-surface);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid;
    z-index: 3;
}

.first-place .medal-badge {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.second-place .medal-badge {
    border-color: #7a7a7a;
    color: #7a7a7a;
}

.third-place .medal-badge {
    border-color: #8d5b28;
    color: #8d5b28;
}

.medal-badge span {
    position: absolute;
    font-size: 12px;
    font-weight: 700;
}

.crown-badge {
    position: absolute;
    top: -35px;
    left: 50%;
    transform: translateX(-50%);
    color: var(--primary-color);
    font-size: 24px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    z-index: 3;
}

/* Leaderboard Table Section */
.leaderboard-table-section {
    background-color: var(--dark-bg-surface);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--border-color);
}

.leaderboard-table-section h2 {
    font-size: 20px;
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
    color: #fff;
}

.leaderboard-table-wrapper {
    overflow-x: auto;
}

.leaderboard-table {
    width: 100%;
    border-collapse: collapse;
}

.leaderboard-table th {
    text-align: left;
    padding: 12px 15px;
    background-color: rgba(230, 22, 22, 0.1);
    color: var(--text-muted);
    font-weight: 500;
    font-size: 14px;
}

.leaderboard-table th:first-child {
    border-radius: 8px 0 0 8px;
}

.leaderboard-table th:last-child {
    border-radius: 0 8px 8px 0;
}

.leaderboard-table td {
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-color);
}

.leaderboard-table tr:last-child td {
    border-bottom: none;
}

.leaderboard-table .rank-column {
    width: 70px;
    font-weight: 600;
    color: var(--text-muted);
}

.leaderboard-table .player-column {
    width: auto;
}

.leaderboard-table .score-column {
    width: 150px;
    text-align: right;
    font-weight: 600;
}

.leaderboard-table .unit {
    color: var(--text-muted);
    font-weight: normal;
    font-size: 13px;
}

.player-info-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.player-info-row .player-avatar {
    width: 40px;
    height: 40px;
    border: 2px solid var(--dark-accent);
}

/* Highlight current user's row */
.leaderboard-table tr.highlight-row {
    background-color: rgba(230, 22, 22, 0.1);
}

.leaderboard-table tr.highlight-row td {
    border-bottom: 1px solid rgba(230, 22, 22, 0.3);
}

.leaderboard-table tr.highlight-row td.rank-column {
    color: var(--primary-color);
}

/* Empty State */
.no-data-message {
    text-align: center;
    padding: 60px 20px;
    background-color: var(--dark-bg-surface);
    border-radius: 12px;
    margin-top: 40px;
    border: 1px solid var(--border-color);
}

.empty-trophy {
    font-size: 50px;
    color: #444;
    margin-bottom: 20px;
    opacity: 0.7;
}

.no-data-message h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: var(--text-color);
}

.no-data-message p {
    color: var(--text-muted);
    max-width: 500px;
    margin: 0 auto 30px;
}

.cta-button {
    display: inline-block;
    background: var(--primary-gradient);
    color: #fff;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 30px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(230, 22, 22, 0.3);
    background: linear-gradient(135deg, #c70000, #8a0000);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .detailed-leaderboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .leaderboard-title-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .leaderboard-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .top-players-podium {
        height: 350px;
        gap: 5px;
    }
    
    .podium-player {
        width: calc(100% / 3 - 10px);
    }
    
    .player-avatar {
        width: 60px;
        height: 60px;
    }
    
    .podium-player.first-place .player-avatar {
        width: 70px;
        height: 70px;
    }
    
    .player-name {
        font-size: 14px;
    }
    
    .player-score {
        font-size: 12px;
    }
}

@media (max-width: 576px) {
    .leaderboard-header-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .time-period-dropdown {
        width: 100%;
    }
    
    .time-period-btn {
        width: 100%;
        justify-content: space-between;
    }
    
    .time-period-options {
        width: 100%;
    }
    
    .top-players-podium {
        height: 300px;
    }
    
    .player-avatar {
        width: 50px;
        height: 50px;
    }
    
    .podium-player.first-place .player-avatar {
        width: 60px;
        height: 60px;
    }
    
    .player-name {
        font-size: 12px;
    }
    
    .rank-column, .score-column {
        text-align: center;
    }
}
</style>

<?php include_once dirname(__DIR__) . "/includes/footer.php"; ?> 