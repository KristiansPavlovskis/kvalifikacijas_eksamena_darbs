<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once dirname(dirname(__DIR__)) . "/config/db_connection.php";

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
    <link rel="stylesheet" href="/assets/css/admin-header.css">
    
    <!-- Additional Head Content -->
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-page">
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-header">
        <div class="admin-header-container">
            <a class="navbar-brand" href="/admin/index.php">
                <i class="fas fa-shield-alt"></i>
            </a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="/admin/index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- User Management Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userManagementDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-users"></i> User Management
                        </a>
                        <div class="dropdown-menu" aria-labelledby="userManagementDropdown">
                            <div class="section-title">User Management</div>
                            <a class="dropdown-item" href="/admin/users.php">
                                <i class="fas fa-user-friends"></i> All Users
                            </a>
                            <a class="dropdown-item" href="/admin/roles.php">
                                <i class="fas fa-user-shield"></i> Roles & Permissions
                            </a>
                            <a class="dropdown-item" href="/admin/user-activity.php">
                                <i class="fas fa-chart-line"></i> User Activity
                            </a>
                        </div>
                    </li>
                    
                    <!-- Marketplace Management Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="marketplaceDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-store"></i> Marketplace
                        </a>
                        <div class="dropdown-menu" aria-labelledby="marketplaceDropdown">
                            <div class="section-title">Marketplace Management</div>
                            <a class="dropdown-item" href="/admin/products.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                            <a class="dropdown-item" href="/admin/sellers.php">
                                <i class="fas fa-user-tie"></i> Sellers
                            </a>
                            <a class="dropdown-item" href="/admin/orders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                            <a class="dropdown-item" href="/admin/transactions.php">
                                <i class="fas fa-money-bill-wave"></i> Transactions
                            </a>
                        </div>
                    </li>
                    
                    <!-- Competitions & Challenges Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="competitionsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-trophy"></i> Competitions
                        </a>
                        <div class="dropdown-menu" aria-labelledby="competitionsDropdown">
                            <div class="section-title">Competitions & Challenges</div>
                            <a class="dropdown-item" href="/admin/achievements.php">
                                <i class="fas fa-medal"></i> Achievements
                            </a>
                            <a class="dropdown-item" href="/admin/challenges.php">
                                <i class="fas fa-flag"></i> Challenges
                            </a>
                            <a class="dropdown-item" href="/admin/leaderboards.php">
                                <i class="fas fa-crown"></i> Leaderboards
                            </a>
                        </div>
                    </li>
                    
                    <!-- Content & Data Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="contentDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-database"></i> Content & Data
                        </a>
                        <div class="dropdown-menu" aria-labelledby="contentDropdown">
                            <div class="section-title">Content & Data Management</div>
                            <a class="dropdown-item" href="/admin/workouts.php">
                                <i class="fas fa-dumbbell"></i> Workouts
                            </a>
                            <a class="dropdown-item" href="/admin/nutrition.php">
                                <i class="fas fa-apple-alt"></i> Nutrition Plans
                            </a>
                            <a class="dropdown-item" href="/admin/analytics.php">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </div>
                    </li>
                </ul>
                
                <div class="nav-user-section">
                    <div class="nav-user-info">
                        <i class="fas fa-user-shield"></i>
                        <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    </div>
                    <a class="nav-link" href="/pages/index.php">
                        <i class="fas fa-home"></i> Main Site
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4"> 