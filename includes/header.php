<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . "/assets/db_connection.php";

$user_roles = [];
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $user_id = $_SESSION["user_id"];
    $sql = "SELECT r.name FROM roles r 
            JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_roles[] = $row['name'];
    }
    $stmt->close();
}

$is_admin = in_array('administrator', $user_roles) || in_array('super_admin', $user_roles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="GYMVERSE - Your ultimate fitness platform for workouts, nutrition, and performance tracking">
    <title><?php echo isset($pageTitle) ? "$pageTitle | GYMVERSE" : "GYMVERSE | Explore The Universe of Fitness"; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="GYMVERSE | Explore The Universe of Fitness">
    <meta property="og:description" content="Your ultimate fitness platform for workouts, nutrition, and performance tracking">
    <meta property="og:image" content="/assets/images/social-share.jpg">
    <meta property="og:url" content="https://gymverse.com">
    <meta property="og:type" content="website">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Animation library -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/utilities.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    
    <!-- Admin styles (only loaded for admin pages) -->
    <?php if (isset($bodyClass) && strpos($bodyClass, 'admin-page') !== false): ?>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <?php endif; ?>
    
    <!-- AOS Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
    
    <style>
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
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .site-header {
            background-color: rgba(10, 10, 10, 0.97);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
        }
        
        .nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .nav-items {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex: 1;
            justify-content: space-between;
            margin-left: 2rem;
        }
        
        .site-header .logo {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
            text-decoration: none;
            font-family: 'Koulen', sans-serif;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(230, 22, 22, 0.5);
            display: flex;
            align-items: center;
        }
        
        .site-header .logo:before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 24px;
            background: var(--primary-color);
            margin-right: 10px;
            border-radius: 2px;
        }
        
        .site-header .nav-list {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .site-header .nav-list a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .site-header .nav-list a:hover {
            color: #fff;
            background-color: rgba(230, 22, 22, 0.1);
        }
        
        .site-header .nav-list a.active {
            color: #fff;
            background-color: rgba(230, 22, 22, 0.15);
        }
        
        .site-header .nav-list a.active:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .site-header .auth-buttons {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .site-header .auth-buttons .btn {
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .site-header .auth-buttons .btn-outline {
            border: 1px solid rgba(230, 22, 22, 0.3);
            background-color: transparent;
        }
        
        .site-header .auth-buttons .btn-outline:hover {
            background-color: rgba(230, 22, 22, 0.1);
            border-color: rgba(230, 22, 22, 0.5);
        }
        
        .site-header .auth-buttons .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 2px 8px rgba(230, 22, 22, 0.3);
        }
        
        .site-header .auth-buttons .btn-primary:hover {
            background: linear-gradient(135deg, #c70000, #8a0000);
            box-shadow: 0 4px 12px rgba(230, 22, 22, 0.5);
            transform: translateY(-1px);
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
        }
        
        .mobile-menu-toggle span {
            display: block;
            width: 25px;
            height: 2px;
            background-color: #fff;
            margin: 5px 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .loading-logo {
            margin-bottom: 20px;
            opacity: 0;
            animation: fade-in 0.5s ease forwards;
        }
        
        .loading-progress {
            width: 200px;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            opacity: 0;
            animation: fade-in 0.5s ease 0.3s forwards;
        }
        
        .progress-bar {
            height: 100%;
            width: 0%;
            background: var(--primary-gradient);
            border-radius: 4px;
            animation: progress 1.5s ease-in-out forwards;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        
        @keyframes fade-in {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .mobile-menu-toggle span {
                background-color: rgba(255, 255, 255, 0.9);
            }
            
            .nav-items {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--dark-bg-surface);
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                border-top: 1px solid var(--border-color);
                border-bottom: 1px solid var(--border-color);
            }
            
            .nav-items.active {
                display: flex;
            }
            
            .site-header .nav-list {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .site-header .nav-list a {
                width: 100%;
                padding: 0.75rem 1rem;
            }
            
            .site-header .auth-buttons {
                flex-direction: column;
                width: 100%;
                gap: 0.75rem;
            }
            
            .site-header .auth-buttons .btn {
                width: 100%;
                justify-content: center;
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <!-- Modern Loading Screen -->
    <div class="loading-screen">
        <div class="loading-content">
            <div class="loading-logo">
                <svg width="200" height="60" viewBox="0 0 200 60">
                    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Koulen, sans-serif" font-size="40" fill="#E61616">GYMVERSE</text>
                </svg>
            </div>
            <div class="loading-progress">
                <div class="progress-bar"></div>
            </div>
        </div>
    </div>

    <!-- Improved Header -->
    <header class="site-header">
        <div class="container">
            <nav class="main-nav" aria-label="Main navigation">
                <div class="nav-content">
                    <a href="/pages/index.php" class="logo" aria-label="GYMVERSE Home">
                        GYMVERSE
                    </a>
                    
                    <button class="mobile-menu-toggle" aria-label="Toggle navigation menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    
                    <div class="nav-items">
                        <ul class="nav-list">
                            <li><a href="/pages/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                            <li><a href="/pages/about.php" <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
                            <li><a href="/pages/workouts.php" <?php echo basename($_SERVER['PHP_SELF']) == 'workouts.php' ? 'class="active"' : ''; ?>>Workouts</a></li>
                            <li><a href="/pages/membership.php" <?php echo basename($_SERVER['PHP_SELF']) == 'membership.php' ? 'class="active"' : ''; ?>>Membership</a></li>
                            <li><a href="/pages/leaderboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'leaderboard.php' || basename($_SERVER['PHP_SELF']) == 'detailed-leaderboard.php' ? 'class="active"' : ''; ?>>Leaderboard</a></li>
                            <li><a href="/pages/contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
                        </ul>
                        
                        <div class="auth-buttons">
                            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                                <?php 
                                $is_admin = false;
                                if(isset($_SESSION["user_roles"])) {
                                    $is_admin = in_array('administrator', $_SESSION["user_roles"]) || in_array('super_admin', $_SESSION["user_roles"]);
                                }
                             
                                $profile_link = $is_admin ? "/admin/index.php" : "/profile/profile.php";
                                ?>
                                <a href="<?php echo $profile_link; ?>" class="btn btn-outline">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?>
                                </a>
                                <a href="/pages/logout.php" class="btn btn-outline">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            <?php else: ?>
                                <a href="/pages/login.php" class="btn btn-outline">Login</a>
                                <a href="/pages/register.php" class="btn btn-primary">Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <script>
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-items').classList.toggle('active');
        });
        
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loadingScreen = document.querySelector('.loading-screen');
                loadingScreen.style.opacity = '0';
                loadingScreen.style.visibility = 'hidden';
            }, 1800);
        });
    </script>
</body>
</html> 