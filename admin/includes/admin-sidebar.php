<?php
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$current_path = dirname($_SERVER['PHP_SELF']);

function isActive($page, $current_file, $current_dir = '') {
    global $current_path;
    
    if ($page === 'index.php' && $current_file === 'index.php') {
        return $current_path === '/admin' ? 'active' : '';
    }
    
    if (!empty($current_dir) && $current_dir === $page) {
        return 'active';
    }
    
    if ($page === $current_file && empty($current_dir)) {
        return 'active';
    }
    
    return '';
}
?>

<div class="admin-sidebar">
    <div class="brand-section">
        <a href="/admin/index.php" class="brand">
            <span class="brand-text">GYM<span class="highlight">VERSE</span></span>
        </a>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="user-profile">
        <div class="profile-avatar">
            <div class="avatar-text"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
            <div class="status-dot online"></div>
        </div>
        <div class="profile-info">
            <h3><?php echo $_SESSION["username"]; ?></h3>
            <p>Super Admin</p>
        </div>
    </div>
    
    <div class="sidebar-section">
        <h4 class="section-header">Management</h4>
        <ul class="sidebar-nav">
            <li class="nav-item <?php echo isActive('index.php', $current_file); ?>">
                <a href="/admin/index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('users', $current_dir); ?>">
                <a href="/admin/users/index.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('templates', $current_dir); ?>">
                <a href="/admin/templates/index.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Templates</span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('exercises', $current_dir); ?>">
                <a href="/admin/exercises/index.php" class="nav-link">
                    <i class="fas fa-dumbbell"></i>
                    <span>Exercises</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h4 class="section-header">System</h4>
        <ul class="sidebar-nav">
            <li class="nav-item <?php echo isActive('settings.php', $current_file); ?>">
                <a href="/admin/settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div> 