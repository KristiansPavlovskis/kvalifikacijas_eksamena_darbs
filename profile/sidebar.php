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

<link href="global-profile.css" rel="stylesheet">

<aside class="profile-sidebar">
    <div class="profile-sidebar-profile">
        <div class="profile-sidebar-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="profile-sidebar-user-name"><?= htmlspecialchars($username ?? '') ?></div>
        <div class="profile-sidebar-user-email"><?= htmlspecialchars($email ?? '') ?></div>
    </div>
    
    <nav class="profile-sidebar-nav">
        <div class="profile-sidebar-nav-title" data-toggle="collapse" data-target="dashboard-nav">
            My Fitness <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="profile-sidebar-nav-items" id="dashboard-nav">
            <li class="profile-sidebar-nav-item">
                <a href="profile.php" class="profile-sidebar-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
        </ul>
        
        <div class="profile-sidebar-nav-title" data-toggle="collapse" data-target="workouts-nav">
            Workouts <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="profile-sidebar-nav-items" id="workouts-nav">
            <li class="profile-sidebar-nav-item">
                <a href="workout.php" class="profile-sidebar-nav-link <?= $current_page === 'workout.php' ? 'active' : '' ?>">
                    <i class="fas fa-play-circle"></i> Active Workout
                </a>
            </li>
            <li class="profile-sidebar-nav-item">
                <a href="workout-templates.php" class="profile-sidebar-nav-link <?= $current_page === 'workout-templates.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> My Templates
                </a>
            </li>
            <li class="profile-sidebar-nav-item">
                <a href="workout-history.php" class="profile-sidebar-nav-link <?= $current_page === 'workout-history.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Workout History
                </a>
            </li>
        </ul>
        
        <div class="profile-sidebar-nav-title" data-toggle="collapse" data-target="progress-nav">
            Progress <i class="fas fa-chevron-down"></i>
        </div>
        <ul class="profile-sidebar-nav-items" id="progress-nav">
            <li class="profile-sidebar-nav-item">
                <a href="stats-overviews.php" class="profile-sidebar-nav-link <?= $current_page === 'stats-overviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Stats Overview
                </a>
            </li>
            <li class="profile-sidebar-nav-item">
                <a href="body-measurements.php" class="profile-sidebar-nav-link <?= $current_page === 'body-measurements.php' ? 'active' : '' ?>">
                    <i class="fas fa-ruler"></i> Body Measurements
                </a>
            </li>
            <li class="profile-sidebar-nav-item">
                <a href="current-goal.php" class="profile-sidebar-nav-link <?= $current_page === 'current-goal.php' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Setting Goals
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="profile-sidebar-footer">
        <a href="settings.php" class="profile-sidebar-footer-button">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="../pages/logout.php" class="profile-sidebar-footer-button">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<nav class="profile-mobile-nav">
    <a href="profile.php" class="profile-mobile-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="workout.php" class="profile-mobile-nav-item <?= $current_page === 'workout.php' ? 'active' : '' ?>">
        <i class="fas fa-play-circle"></i>
        <span>Workout</span>
    </a>
    <a href="workout-templates.php" class="profile-mobile-nav-item <?= $current_page === 'workout-templates.php' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i>
        <span>Templates</span>
    </a>
    <a href="#" class="profile-mobile-nav-item" id="moreBtn">
        <i class="fas fa-ellipsis-h"></i>
        <span>More</span>
    </a>
</nav>

<div class="profile-more-menu-overlay" id="moreMenuOverlay">
    <div class="profile-more-menu-container">
        <div class="profile-more-menu-header">
            <div class="profile-more-menu-title">More Options</div>
            <button class="profile-more-menu-close" id="closeMoreMenu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="profile-more-menu-items">
            <a href="workout-history.php" class="profile-more-menu-item">
                <i class="fas fa-history"></i>
                <span>Workout History</span>
            </a>
            <a href="stats-overviews.php" class="profile-more-menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Stats Overview</span>
            </a>
            <a href="body-measurements.php" class="profile-more-menu-item">
                <i class="fas fa-ruler"></i>
                <span>Body Measurements</span>
            </a>
            <a href="current-goal.php" class="profile-more-menu-item">
                <i class="fas fa-bullseye"></i>
                <span>Setting Goals</span>
            </a>
            <a href="settings.php" class="profile-more-menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../pages/logout.php" class="profile-more-menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleSidebar = () => {
        const sidebar = document.querySelector('.profile-sidebar');
        sidebar.classList.toggle('active');
    };

    if (!document.querySelector('.profile-mobile-sidebar-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'profile-mobile-sidebar-toggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.profile-header')?.prepend(toggleButton);
        
        toggleButton.addEventListener('click', toggleSidebar);
    }

    const sectionTitles = document.querySelectorAll('.profile-sidebar-nav-title');
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
            const sidebar = document.querySelector('.profile-sidebar');
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
            const sidebar = document.querySelector('.profile-sidebar');
            const toggleButton = document.querySelector('.profile-mobile-sidebar-toggle');
            
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