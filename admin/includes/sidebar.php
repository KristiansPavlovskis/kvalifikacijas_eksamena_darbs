<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__DIR__)) . "/assets/db_connection.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /pages/login.php");
    exit;
}

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

$is_admin = in_array('administrator', $user_roles) || in_array('super_admin', $user_roles);
if (!$is_admin) {
    header("location: /pages/index.php");
    exit;
}

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
    
    <style>
        .admin-sidebar {
            transition: width 0.3s ease;
            overflow-y: auto;
        }
        
        .admin-sidebar.collapsed {
            width: 80px;
        }
        
        .admin-sidebar.collapsed .brand-text,
        .admin-sidebar.collapsed .profile-info,
        .admin-sidebar.collapsed .section-header,
        .admin-sidebar.collapsed .nav-link span {
            display: none;
        }
        
        .admin-sidebar.collapsed .nav-item {
            text-align: center;
        }
        
        .admin-sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .section-toggle {
            transition: transform 0.3s ease;
        }
        
        .sidebar-nav {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-nav.collapsed {
            max-height: 0;
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-wrapper">
        <div class="admin-sidebar">
            <div class="brand-section">
                <a href="/admin/index.php" class="brand">
                    <span class="brand-text">Gym<span class="highlight">Verse</span></span>
                </a>
                <button class="sidebar-toggle" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="user-profile">
                <div class="profile-avatar">
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
                <h4 class="section-header" data-toggle="collapse" data-target="overview-nav">
                    OVERVIEW
                    <i class="fas fa-chevron-down section-toggle"></i>
                </h4>
                <ul class="sidebar-nav" id="overview-nav">
                    <li class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <a href="/admin/index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Site Statistics</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'active-users.php' ? 'active' : ''; ?>">
                        <a href="/admin/active-users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Active Users</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'signups.php' ? 'active' : ''; ?>">
                        <a href="/admin/signups.php" class="nav-link">
                            <i class="fas fa-user-plus"></i>
                            <span>New Sign-ups</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'workout-stats.php' ? 'active' : ''; ?>">
                        <a href="/admin/workout-stats.php" class="nav-link">
                            <i class="fas fa-dumbbell"></i>
                            <span>Workout Completions</span>
                        </a>
                    </li>
                </ul>
            </div>    
            <div class="sidebar-section">
                <h4 class="section-header" data-toggle="collapse" data-target="user-management-nav">
                    USER MANAGEMENT
                    <i class="fas fa-chevron-down section-toggle"></i>
                </h4>
                <ul class="sidebar-nav" id="user-management-nav">
                    <li class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                        <a href="/admin/users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>User List</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header" data-toggle="collapse" data-target="content-management-nav">
                    CONTENT MANAGEMENT
                    <i class="fas fa-chevron-down section-toggle"></i>
                </h4>
                <ul class="sidebar-nav" id="content-management-nav">
                    <li class="nav-item <?php echo $current_page == 'exercise-library.php' ? 'active' : ''; ?>">
                        <a href="/admin/exercise-library.php" class="nav-link">
                            <i class="fas fa-running"></i>
                            <span>Exercise Library</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'equipment-guide.php' ? 'active' : ''; ?>">
                        <a href="/admin/equipment-guide.php" class="nav-link">
                            <i class="fas fa-dumbbell"></i>
                            <span>Equipment Guide</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-header" data-toggle="collapse" data-target="gamification-nav">
                    GAMIFICATION CONTROLS
                    <i class="fas fa-chevron-down section-toggle"></i>
                </h4>
                <ul class="sidebar-nav" id="gamification-nav">
                    <li class="nav-item <?php echo $current_page == 'achievements.php' ? 'active' : ''; ?>">
                        <a href="/admin/achievements.php" class="nav-link">
                            <i class="fas fa-medal"></i>
                            <span>Achievement System</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
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
            
            <div class="page-content">
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toggleSidebarBtn = document.getElementById('toggleSidebar');
                    toggleSidebarBtn.addEventListener('click', function() {
                        const sidebar = document.querySelector('.admin-sidebar');
                        sidebar.classList.toggle('collapsed');
                        
                        const icon = this.querySelector('i');
                        if (sidebar.classList.contains('collapsed')) {
                            icon.classList.remove('fa-bars');
                            icon.classList.add('fa-bars-staggered');
                        } else {
                            icon.classList.remove('fa-bars-staggered');
                            icon.classList.add('fa-bars');
                        }
                    });
                    
                    const sectionHeaders = document.querySelectorAll('.section-header');
                    sectionHeaders.forEach(header => {
                        header.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const targetSection = document.getElementById(targetId);
                            
                            targetSection.classList.toggle('collapsed');
                            
                            const icon = this.querySelector('.section-toggle');
                            if (targetSection.classList.contains('collapsed')) {
                                icon.classList.remove('fa-chevron-down');
                                icon.classList.add('fa-chevron-right');
                            } else {
                                icon.classList.remove('fa-chevron-right');
                                icon.classList.add('fa-chevron-down');
                            }
                        });
                    });
                });
            </script> 