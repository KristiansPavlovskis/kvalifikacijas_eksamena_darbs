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

if (isset($conn) && isset($user_id)) {
}
?>

<style>
    .sidebar {
        width: var(--sidebar-width, 260px);
        height: 100vh;
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
            position: fixed;
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
    
    .mobile-nav {
        display: none;
    }
    
    .more-menu-overlay {
        display: none;
    }
    
    @media (max-width: 992px) {
        .mobile-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: var(--dark-surface, #151515);
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-muted, #a0a0a0);
            text-decoration: none;
            padding: 8px 0;
            width: 25%;
            font-size: 12px;
        }
        
        .mobile-nav-item i {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .mobile-nav-item.active {
            color: var(--primary, #ff4d4d);
        }
        
        .more-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1001;
            display: none;
            flex-direction: column;
            padding: 20px;
            overflow-y: auto;
        }
        
        .more-menu-container {
            background-color: var(--dark-surface, #151515);
            border-radius: 8px;
            padding: 20px;
            max-width: 500px;
            margin: auto;
            width: 100%;
        }
        
        .more-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color, #333);
            padding-bottom: 10px;
        }
        
        .more-menu-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--light, #f5f5f5);
        }
        
        .more-menu-close {
            background: transparent;
            border: none;
            color: var(--light, #f5f5f5);
            font-size: 20px;
            cursor: pointer;
        }
        
        .more-menu-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .more-menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            text-decoration: none;
            color: var(--light, #f5f5f5);
        }
        
        .more-menu-item i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--primary, #ff4d4d);
        }
    }
</style>

<aside class="sidebar">
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="sidebar-user-name"><?= htmlspecialchars($username ?? '') ?></div>
        <div class="sidebar-user-email"><?= htmlspecialchars($email ?? '') ?></div>
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
        </ul>
        
        <div class="sidebar-nav-title" data-toggle="collapse" data-target="workouts-nav">
            Workouts <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="sidebar-nav-items" id="workouts-nav">
            <li class="sidebar-nav-item">
                <a href="workout.php" class="sidebar-nav-link <?= $current_page === 'workout.php' ? 'active' : '' ?>">
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
                <a href="stats-overviews.php" class="sidebar-nav-link <?= $current_page === 'stats-overviews.php' ? 'active' : '' ?>">
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

<nav class="mobile-nav">
    <a href="profile.php" class="mobile-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="workout.php" class="mobile-nav-item <?= $current_page === 'workout.php' ? 'active' : '' ?>">
        <i class="fas fa-play-circle"></i>
        <span>Workout</span>
    </a>
    <a href="workout-templates.php" class="mobile-nav-item <?= $current_page === 'workout-templates.php' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i>
        <span>Templates</span>
    </a>
    <a href="#" class="mobile-nav-item" id="moreBtn">
        <i class="fas fa-ellipsis-h"></i>
        <span>More</span>
    </a>
</nav>

<div class="more-menu-overlay" id="moreMenuOverlay">
    <div class="more-menu-container">
        <div class="more-menu-header">
            <div class="more-menu-title">More Options</div>
            <button class="more-menu-close" id="closeMoreMenu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="more-menu-items">
            <a href="workout-history.php" class="more-menu-item">
                <i class="fas fa-history"></i>
                <span>Workout History</span>
            </a>
            <a href="stats-overviews.php" class="more-menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Stats Overview</span>
            </a>
            <a href="body-measurements.php" class="more-menu-item">
                <i class="fas fa-ruler"></i>
                <span>Body Measurements</span>
            </a>
            <a href="current-goal.php" class="more-menu-item">
                <i class="fas fa-bullseye"></i>
                <span>Setting Goals</span>
            </a>
            <a href="settings.php" class="more-menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../pages/logout.php" class="more-menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

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
    if (toggleSidebarBtn) { 
        toggleSidebarBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return; 
            
            sidebar.classList.toggle('collapsed');
            
            const icon = this.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
            }
        });
    }

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
    
    const moreBtn = document.getElementById('moreBtn');
    const moreMenuOverlay = document.getElementById('moreMenuOverlay');
    const closeMoreMenu = document.getElementById('closeMoreMenu');
    
    if (moreBtn && moreMenuOverlay && closeMoreMenu) {
        moreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            moreMenuOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
        
        closeMoreMenu.addEventListener('click', function() {
            moreMenuOverlay.style.display = 'none';
            document.body.style.overflow = '';
        });
        
        moreMenuOverlay.addEventListener('click', function(e) {
            if (e.target === moreMenuOverlay) {
                moreMenuOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
    
    function adjustContentPadding() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent && window.innerWidth <= 992) {
            mainContent.style.paddingBottom = '70px';
        } else if (mainContent) {
            mainContent.style.paddingBottom = '';
        }
    }
    
    adjustContentPadding();
    window.addEventListener('resize', adjustContentPadding);
});
</script> 