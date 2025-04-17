<?php

require_once 'profile_access_control.php';

if (!isset($user_id)) {
    $user_id = $_SESSION["user_id"] ?? null;
}
if (!isset($username)) {
    $username = $_SESSION["username"] ?? null;
}
if (!isset($email)) {
    $email = $_SESSION["email"] ?? null;
}

if (!isset($roles_string) && isset($_SESSION["user_roles"]) && !empty($_SESSION["user_roles"])) {
    $roles_string = implode(", ", $_SESSION["user_roles"]);
}

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($join_date) && isset($conn)) {
    $join_date_query = "SELECT DATE_FORMAT(created_at, '%M %Y') as join_date FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $join_date_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $join_date = $row['join_date'];
        }
    }
}

$user_level = 1;
$user_xp = "0/100";
$fit_coins = 0;

if (isset($conn) && isset($user_id)) {
}
?>

<style>
    .sidebar {
        width: var(--sidebar-width, 260px);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background-color: var(--dark-surface, #151515);
        color: var(--light, #f5f5f5);
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        transition: all 0.3s ease;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color, #333);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-logo {
        font-family: 'Koulen', sans-serif;
        font-size: 24px;
        color: var(--light, #f5f5f5);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar-logo:before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 20px;
        background: var(--primary, #ff4d4d);
        border-radius: 2px;
    }

    .sidebar-profile {
        padding: 20px;
        border-bottom: 1px solid var(--border-color, #333);
    }

    .sidebar-avatar {
        width: 60px;
        height: 60px;
        background-color: var(--primary, #ff4d4d);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }

    .sidebar-avatar i {
        font-size: 24px;
        color: white;
    }

    .sidebar-user-name {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .sidebar-user-email {
        font-size: 14px;
        color: var(--text-muted, #a0a0a0);
        margin-bottom: 10px;
    }

    .sidebar-user-since {
        font-size: 12px;
        color: var(--text-muted, #a0a0a0);
    }

    .sidebar-nav {
        flex: 1;
        padding: 10px 0;
        overflow-y: auto;
    }

    .sidebar-nav-title {
        color: var(--text-muted, #a0a0a0);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 20px;
        margin: 15px 0 10px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .sidebar-nav-title i {
        transition: transform 0.3s ease;
    }

    .sidebar-nav-items {
        list-style: none;
        padding: 0;
        margin: 0 0 10px 0;
        max-height: 1000px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .sidebar-nav-items.collapsed {
        max-height: 0;
    }

    .sidebar-nav-link {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        color: var(--light, #f5f5f5);
        text-decoration: none;
        transition: all 0.3s ease;
        gap: 12px;
        border-left: 3px solid transparent;
    }

    .sidebar-nav-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .sidebar-nav-link.active {
        background-color: rgba(230, 22, 22, 0.1);
        border-left-color: var(--primary, #ff4d4d);
    }

    .sidebar-nav-link i {
        width: 20px;
        text-align: center;
        font-size: 16px;
        color: var(--text-muted, #a0a0a0);
    }

    .sidebar-nav-link.active i {
        color: var(--primary, #ff4d4d);
    }

    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid var(--border-color, #333);
        display: flex;
        justify-content: space-between;
    }

    .sidebar-footer-button {
        background: transparent;
        border: none;
        color: var(--text-muted, #a0a0a0);
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sidebar-footer-button:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--light, #f5f5f5);
    }

    .user-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid var(--border-color, #333);
    }

    .user-status-item {
        font-size: 12px;
    }

    .collapse-toggle {
        background: transparent;
        border: none;
        color: var(--light, #f5f5f5);
        cursor: pointer;
    }

    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/pages/index.php" class="sidebar-logo">MY FITNESS</a>
        <button class="collapse-toggle" id="toggleSidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="sidebar-user-name"><?= htmlspecialchars($username ?? '') ?></div>
        <div class="sidebar-user-email"><?= htmlspecialchars($email ?? '') ?></div>
        <?php if (isset($join_date)): ?>
        <div class="sidebar-user-since">
            <i class="fas fa-calendar-alt"></i> Member since <?= $join_date ?>
        </div>
        <?php endif; ?>
        <div class="user-status">
            <div class="user-status-item">
                <strong>Level:</strong> <?= $user_level ?>
            </div>
            <div class="user-status-item">
                <strong>XP:</strong> <?= $user_xp ?>
            </div>
            <div class="user-status-item">
                <strong>Fit Coins:</strong> <?= $fit_coins ?>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="dashboard-nav">
            My Fitness <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="dashboard-nav">
            <li class="sidebar-nav-item">
                <a href="profile.php" class="sidebar-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="profile-settings.php" class="sidebar-nav-link <?= $current_page === 'profile-settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Profile Settings
                </a>
            </li>
        </ul>
        
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="workouts-nav">
            Workouts <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="workouts-nav">
            <li class="sidebar-nav-item">
                <a href="active-workout.php" class="sidebar-nav-link <?= $current_page === 'active-workout.php' ? 'active' : '' ?>">
                    <i class="fas fa-play-circle"></i> Active Workout
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="workout-templates.php" class="sidebar-nav-link <?= $current_page === 'workout-templates.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> My Templates
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="workout-history.php" class="sidebar-nav-link <?= $current_page === 'workout-history.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Workout History
                </a>
            </li>
        </ul>
        
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="progress-nav">
            Progress <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="progress-nav">
            <li class="sidebar-nav-item">
                <a href="stats-overview.php" class="sidebar-nav-link <?= $current_page === 'stats-overview.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Stats Overview
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="body-measurements.php" class="sidebar-nav-link <?= $current_page === 'body-measurements.php' ? 'active' : '' ?>">
                    <i class="fas fa-ruler"></i> Body Measurements
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="current-goal.php" class="sidebar-nav-link <?= $current_page === 'current-goal.php' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Setting Goals
                </a>
            </li>
        </ul>
        
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="challenges-nav">
            Challenges <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="challenges-nav">
            <li class="sidebar-nav-item">
                <a href="active-challenges.php" class="sidebar-nav-link <?= $current_page === 'active-challenges.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag"></i> Active Challenges
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="leaderboards.php" class="sidebar-nav-link <?= $current_page === 'leaderboards.php' ? 'active' : '' ?>">
                    <i class="fas fa-medal"></i> Leaderboards
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="create-challenge.php" class="sidebar-nav-link <?= $current_page === 'create-challenge.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i> Create Challenge
                </a>
            </li>
        </ul>
        
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="achievements-nav">
            Achievements <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="achievements-nav">
            <li class="sidebar-nav-item">
                <a href="badges.php" class="sidebar-nav-link <?= $current_page === 'badges.php' ? 'active' : '' ?>">
                    <i class="fas fa-certificate"></i> Badge Collection
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="settings.php" class="sidebar-footer-button">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="../pages/logout.php" class="sidebar-footer-button">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    };

    if (!document.querySelector('.mobile-sidebar-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-sidebar-toggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.profile-header')?.prepend(toggleButton);
        
        toggleButton.addEventListener('click', toggleSidebar);
    }

    const sectionTitles = document.querySelectorAll('.sidebar-nav-title');
    sectionTitles.forEach(title => {
        title.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);
            
            targetSection.classList.toggle('collapsed');
            
            const icon = this.querySelector('i');
            if (targetSection.classList.contains('collapsed')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            }
        });
    });

    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    toggleSidebarBtn.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
        
        const icon = this.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        } else {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
        }
    });

    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 992) {
            const sidebar = document.querySelector('.sidebar');
            const toggleButton = document.querySelector('.mobile-sidebar-toggle');
            
            if (sidebar && toggleButton && 
                !sidebar.contains(event.target) && 
                event.target !== toggleButton) {
                sidebar.classList.remove('active');
            }
        }
    });
});
</script> 