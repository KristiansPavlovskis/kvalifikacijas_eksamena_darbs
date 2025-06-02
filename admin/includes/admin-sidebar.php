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
            <p><?php echo t('administration'); ?></p>
        </div>
    </div>
    
    <div class="sidebar-section">
        <h4 class="section-header"><?php echo t('administration'); ?></h4>
        <ul class="sidebar-nav">
            <li class="nav-item <?php echo isActive('index.php', $current_file); ?>">
                <a href="/admin/index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span><?php echo t('dashboard'); ?></span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('users', $current_dir); ?>">
                <a href="/admin/users/index.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span><?php echo t('active_users'); ?></span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('templates', $current_dir); ?>">
                <a href="/admin/templates/index.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i>
                    <span><?php echo t('templates'); ?></span>
                </a>
            </li>
            <li class="nav-item <?php echo isActive('exercises', $current_dir); ?>">
                <a href="/admin/exercises/index.php" class="nav-link">
                    <i class="fas fa-dumbbell"></i>
                    <span><?php echo t('exercises'); ?></span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h4 class="section-header"><?php echo t('my_fitness'); ?></h4>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="/profile/profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span><?php echo t('profile'); ?></span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h4 class="section-header"><?php echo t('settings'); ?></h4>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="../pages/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php echo t('logout'); ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>

<nav class="mobile-footer-nav">
    <ul class="mobile-nav-items">
        <li class="mobile-nav-item">
            <a href="/admin/index.php" class="mobile-nav-link <?php echo isActive('index.php', $current_file); ?>">
                <i class="fas fa-th-large"></i>
                <span><?php echo t('dashboard'); ?></span>
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="/admin/users/index.php" class="mobile-nav-link <?php echo isActive('users', $current_dir); ?>">
                <i class="fas fa-users"></i>
                <span><?php echo t('active_users'); ?></span>
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="/admin/templates/index.php" class="mobile-nav-link <?php echo isActive('templates', $current_dir); ?>">
                <i class="fas fa-clipboard-list"></i>
                <span><?php echo t('templates'); ?></span>
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="/admin/exercises/index.php" class="mobile-nav-link <?php echo isActive('exercises', $current_dir); ?>">
                <i class="fas fa-dumbbell"></i>
                <span><?php echo t('exercises'); ?></span>
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="#" class="mobile-nav-link toggle-more-menu">
                <i class="fas fa-ellipsis-h"></i>
                <span><?php echo t('more'); ?></span>
            </a>
        </li>
    </ul>
</nav>

<div class="more-menu">
    <ul class="more-menu-items">
        <li class="more-menu-item">
            <a href="/admin/settings.php" class="more-menu-link <?php echo isActive('settings.php', $current_file); ?>">
                <i class="fas fa-cog"></i>
                <span><?php echo t('settings'); ?></span>
            </a>
        </li>
        <li class="more-menu-item">
            <a href="../pages/logout.php" class="more-menu-link">
                <i class="fas fa-sign-out-alt"></i>
                <span><?php echo t('logout'); ?></span>
            </a>
        </li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleMoreBtn = document.querySelector('.toggle-more-menu');
    const moreMenu = document.querySelector('.more-menu');
    
    if (toggleMoreBtn && moreMenu) {
        toggleMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            moreMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!toggleMoreBtn.contains(e.target) && !moreMenu.contains(e.target)) {
                moreMenu.classList.remove('show');
            }
        });
    }
});
</script> 