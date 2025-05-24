<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$is_superadmin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_superadmin = ($row['count'] > 0);
}

if (!$is_superadmin) {
    header("Location: ../../pages/access_denied.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $repeat_password = $_POST["repeat_password"];
    
    $errors = [];
    
    $check_username = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username already taken";
    }
    
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered";
    }

    if ($password !== $repeat_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;
            
            $check_role_sql = "SELECT id FROM roles WHERE id = 1";
            $check_role_stmt = $conn->prepare($check_role_sql);
            $check_role_stmt->execute();
            $role_result = $check_role_stmt->get_result();
            
            if ($role_result->num_rows > 0) {
                $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)";
                $role_stmt = $conn->prepare($role_sql);
                $role_stmt->bind_param("i", $new_user_id);
                $role_stmt->execute();
            } else {
                $find_role_sql = "SELECT id FROM roles ORDER BY id LIMIT 1";
                $find_role_stmt = $conn->prepare($find_role_sql);
                $find_role_stmt->execute();
                $find_role_result = $find_role_stmt->get_result();
                
                if ($find_role_result->num_rows > 0) {
                    $first_role = $find_role_result->fetch_assoc();
                    $role_id = $first_role['id'];
                    
                    $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                    $role_stmt = $conn->prepare($role_sql);
                    $role_stmt->bind_param("ii", $new_user_id, $role_id);
                    $role_stmt->execute();
                }
            }
            
            $_SESSION['success_message'] = "User added successfully";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Error creating user: " . $conn->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
} 