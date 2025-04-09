<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once dirname(dirname(__DIR__)) . "/assets/db_connection.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /pages/login.php");
    exit;
}

// Check user roles
$user_id = $_SESSION["user_id"];
$sql = "SELECT r.name FROM roles r 
        JOIN user_roles ur ON r.id = ur.role_id 
        WHERE ur.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_roles = [];
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['name'];
}
$stmt->close();

// Check if user has admin or super_admin role
$is_admin = in_array('administrator', $user_roles) || in_array('super_admin', $user_roles);
if (!$is_admin) {
    header("location: /pages/index.php");
    exit;
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - Admin" : "Admin Panel"; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Animation library -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    
    <!-- Basic CSS -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/fontawesome.min.css">
    
    <!-- Core Stylesheets -->
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/utilities.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    
    <!-- Admin Stylesheets -->
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-sidebar.css">
    
    <!-- Additional Head Content -->
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-page">
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <div class="brand-section">
                <a href="/admin/index.php" class="brand">
                    <span class="brand-text">Pro<span class="highlight">table</span></span>
                </a>
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="user-profile">
                <div class="profile-avatar">
                    <!-- <img src="/assets/images/avatar.png" alt="User Avatar" onerror="this.src='/assets/images/default-avatar.png'"> -->
                    <span class="status-dot online"></span>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($_SESSION["username"]); ?></h3>
                    <p>Administrator</p>
                </div>
                <div class="profile-actions">
                    <a href="/admin/profile.php" class="profile-btn"><i class="fas fa-user"></i></a>
                    <a href="/admin/settings.php" class="profile-btn"><i class="fas fa-cog"></i></a>
                    <a href="/pages/index.php" class="profile-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header">MAIN</h4>
                <ul class="sidebar-nav">
                    <li class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <a href="/admin/index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item <?php echo $current_page == 'chat.php' ? 'active' : ''; ?>">
                        <a href="/admin/chat.php" class="nav-link">
                            <i class="fas fa-comments"></i>
                            <span>Chat</span>
                            <?php if (rand(0, 1)): ?><span class="notification-dot"></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'mail.php' ? 'active' : ''; ?>">
                        <a href="/admin/mail.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Mail</span>
                            <?php if (rand(0, 1)): ?><span class="notification-dot green"></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'todo.php' ? 'active' : ''; ?>">
                        <a href="/admin/todo.php" class="nav-link">
                            <i class="fas fa-check-circle"></i>
                            <span>Todo</span>
                            <?php if (rand(0, 1)): ?><span class="notification-dot orange"></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'file_manager.php' ? 'active' : ''; ?>">
                        <a href="/admin/file_manager.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            <span>File Manager</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                        <a href="/admin/calendar.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendar</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header">USER MANAGEMENT</h4>
                <ul class="sidebar-nav">
                    <li class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                        <a href="/admin/users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>All Users</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'roles.php' ? 'active' : ''; ?>">
                        <a href="/admin/roles.php" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Roles & Permissions</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'user-activity.php' ? 'active' : ''; ?>">
                        <a href="/admin/user-activity.php" class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            <span>User Activity</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header">MARKETPLACE</h4>
                <ul class="sidebar-nav">
                    <li class="nav-item <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                        <a href="/admin/products.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'sellers.php' ? 'active' : ''; ?>">
                        <a href="/admin/sellers.php" class="nav-link">
                            <i class="fas fa-user-tie"></i>
                            <span>Sellers</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                        <a href="/admin/orders.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                        <a href="/admin/transactions.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Transactions</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'merch.php' ? 'active' : ''; ?>">
                        <a href="/admin/merch.php" class="nav-link">
                            <i class="fas fa-tshirt"></i>
                            <span>Merchandise</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header">CONTENT & DATA</h4>
                <ul class="sidebar-nav">
                    <li class="nav-item <?php echo $current_page == 'workouts.php' ? 'active' : ''; ?>">
                        <a href="/admin/workouts.php" class="nav-link">
                            <i class="fas fa-dumbbell"></i>
                            <span>Workouts</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'nutrition.php' ? 'active' : ''; ?>">
                        <a href="/admin/nutrition.php" class="nav-link">
                            <i class="fas fa-apple-alt"></i>
                            <span>Nutrition Plans</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                        <a href="/admin/analytics.php" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- New Gamification Section -->
            <div class="sidebar-section">
                <h4 class="section-header">GAMIFICATION</h4>
                <ul class="sidebar-nav">
                    <li class="nav-item <?php echo $current_page == 'leaderboards.php' ? 'active' : ''; ?>">
                        <a href="/admin/leaderboards.php" class="nav-link">
                            <i class="fas fa-trophy"></i>
                            <span>Leaderboards</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'challenges.php' ? 'active' : ''; ?>">
                        <a href="/admin/challenges.php" class="nav-link">
                            <i class="fas fa-flag-checkered"></i>
                            <span>Challenges</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'achievements.php' ? 'active' : ''; ?>">
                        <a href="/admin/achievements.php" class="nav-link">
                            <i class="fas fa-medal"></i>
                            <span>Achievements</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="admin-topbar">
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="topbar-actions">
                    <button class="action-btn"><i class="fas fa-expand"></i></button>
                    <button class="action-btn"><i class="fas fa-th-large"></i></button>
                    <button class="action-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    <button class="action-btn message-btn">
                        <i class="fas fa-envelope"></i>
                        <span class="badge">5</span>
                    </button>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="page-content"> 