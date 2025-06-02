<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = null;
if (!isset($conn)) {
    $db_paths = [
        '../assets/db_connection.php',
        '../config/db_connect.php',
        '../assets/php/db_connection.php',
        '../includes/db_connection.php',
        '../config/db.php'
    ];
    
    $connected = false;
    foreach ($db_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $connected = isset($conn) && $conn !== null;
            if ($connected) {
                break;
            }
        }
    }
    
    if (!$connected) {
        error_log("Database connection failed in profile_access_control.php");
        $has_access = true;
    }
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $current_page = basename($_SERVER['PHP_SELF']);
    header("location: ../pages/login.php?redirect=profile/" . $current_page);
    exit;
}

$has_access = false;
$user_id = $_SESSION["user_id"] ?? 0;

if (!isset($_SESSION["user_roles"]) && isset($conn) && $conn !== null) {
    try {
        $sql = "SELECT r.name FROM roles r 
                JOIN user_roles ur ON r.id = ur.role_id 
                WHERE ur.user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_roles = [];
            
            while ($row = $result->fetch_assoc()) {
                $user_roles[] = $row['name'];
            }
            
            $_SESSION["user_roles"] = $user_roles;
        } else {
            $_SESSION["user_roles"] = [];
        }
    } catch (Exception $e) {
        $_SESSION["user_roles"] = [];
        error_log("Error fetching user roles: " . $e->getMessage());
    }
} else {
    $user_roles = $_SESSION["user_roles"] ?? [];
}

$allowed_roles = ['basic_user', 'premium_user', 'super_admin', 'administrator', 'personal_trainer', 
                 'verified_user', 'beta_tester', 'community_leader', 'nutrition_expert', 'form_checker'];

if (!empty($user_roles)) {
    foreach ($user_roles as $role) {
        if (in_array($role, $allowed_roles)) {
            $has_access = true;
            break;
        }
    }
}

if (empty($user_roles) && isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $has_access = true;
    
    if (isset($conn) && $conn !== null && $user_id > 0) {
        try {
            $check_sql = "SELECT COUNT(*) as role_count FROM user_roles WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if ($check_stmt) {
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                
                if (isset($row['role_count']) && $row['role_count'] == 0) {
                    $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, 10)";
                    $role_stmt = $conn->prepare($role_sql);
                    
                    if ($role_stmt) {
                        $role_stmt->bind_param("i", $user_id);
                        $role_stmt->execute();
                        
                        $_SESSION["user_roles"] = ['basic_user'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error assigning basic_user role: " . $e->getMessage());
        }
    }
}

if (!$has_access) {
    header("location: ../pages/access_denied.php");
    exit;
}
