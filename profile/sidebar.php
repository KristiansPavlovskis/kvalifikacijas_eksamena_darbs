<?php
// Include access control check for profile pages
require_once 'profile_access_control.php';

// Get user information if not already set
if (!isset($user_id)) {
    $user_id = $_SESSION["user_id"] ?? null;
}
if (!isset($username)) {
    $username = $_SESSION["username"] ?? null;
}
if (!isset($email)) {
    $email = $_SESSION["email"] ?? null;
}

// Get user roles as a formatted string if not already set
if (!isset($roles_string) && isset($_SESSION["user_roles"]) && !empty($_SESSION["user_roles"])) {
    $roles_string = implode(", ", $_SESSION["user_roles"]);
}

// Get current page for highlighting active nav item
$current_page = basename($_SERVER['PHP_SELF']);

// Get join date if not set
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
?>

<!-- Sidebar Styles -->
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
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color, #333);
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
        padding: 20px 0;
        overflow-y: auto;
    }

    .sidebar-nav-title {
        color: var(--text-muted, #a0a0a0);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 20px;
        margin-bottom: 10px;
    }

    .sidebar-nav-items {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
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

    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<!-- Sidebar Structure -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/pages/index.php" class="sidebar-logo">GYMVERSE</a>
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
    </div>
    
    <nav class="sidebar-nav">
        <div class="sidebar-nav-title">Dashboard</div>
        <ul class="sidebar-nav-items">
            <li class="sidebar-nav-item">
                <a href="profile.php" class="sidebar-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="workout-analytics.php" class="sidebar-nav-link <?= $current_page === 'workout-analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="current-goal.php" class="sidebar-nav-link <?= $current_page === 'current-goal.php' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Goals
                </a>
            </li>
        </ul>
        
        <div class="sidebar-nav-title">Training</div>
        <ul class="sidebar-nav-items">
            <li class="sidebar-nav-item">
                <a href="quick-workout.php" class="sidebar-nav-link <?= $current_page === 'quick-workout.php' ? 'active' : '' ?>">
                    <i class="fas fa-stopwatch"></i> Quick Workout
                </a>
            </li>
            <!-- <li class="sidebar-nav-item">
                <a href="../workouts.php" class="sidebar-nav-link <?= $current_page === 'workouts.php' ? 'active' : '' ?>">
                    <i class="fas fa-dumbbell"></i> Workouts
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="calories-burned.php" class="sidebar-nav-link <?= $current_page === 'calories-burned.php' ? 'active' : '' ?>">
                    <i class="fas fa-fire"></i> Calories Burned
                </a>
            </li> -->
        </ul>
        
        <!-- <div class="sidebar-nav-title">Nutrition</div>
        <ul class="sidebar-nav-items">
            <li class="sidebar-nav-item">
                <a href="nutrition.php" class="sidebar-nav-link <?= $current_page === 'nutrition.php' ? 'active' : '' ?>">
                    <i class="fas fa-apple-alt"></i> Nutrition Tracker
                </a>
            </li>
        </ul> -->
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

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle functionality
    const toggleSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    };

    // Add mobile toggle button if it doesn't exist
    if (!document.querySelector('.mobile-sidebar-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-sidebar-toggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.profile-header')?.prepend(toggleButton);
        
        toggleButton.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking outside on mobile
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