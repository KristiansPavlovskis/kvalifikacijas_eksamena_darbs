<?php
require_once 'profile_access_control.php';


if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/settings.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_profile"])) {
        $first_name = trim($_POST["first_name"]);
        $last_name = trim($_POST["last_name"]);
        $email = trim($_POST["email"]);
        $bio = trim($_POST["bio"]);
        
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $email, $bio, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Profile updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating profile: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
    
    if (isset($_POST["change_password"])) {
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];
        
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($current_password, $user["password"])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Password changed successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error changing password: " . mysqli_error($conn);
                    $message_type = "error";
                }
            } else {
                $message = "New passwords do not match!";
                $message_type = "error";
            }
        } else {
            $message = "Current password is incorrect!";
            $message_type = "error";
        }
    }
    
    if (isset($_POST["update_notifications"])) {
        $email_notifications = isset($_POST["email_notifications"]) ? 1 : 0;
        $workout_reminders = isset($_POST["workout_reminders"]) ? 1 : 0;
        $goal_updates = isset($_POST["goal_updates"]) ? 1 : 0;
        
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'user_preferences'");
        if (mysqli_num_rows($result) == 0) {
            $create_table = "CREATE TABLE user_preferences (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                email_notifications BOOLEAN DEFAULT TRUE,
                workout_reminders BOOLEAN DEFAULT TRUE,
                goal_updates BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";
            mysqli_query($conn, $create_table);
        }
        
        $query = "SELECT id FROM user_preferences WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $query = "UPDATE user_preferences SET email_notifications = ?, workout_reminders = ?, goal_updates = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiii", $email_notifications, $workout_reminders, $goal_updates, $user_id);
        } else {
            $query = "INSERT INTO user_preferences (user_id, email_notifications, workout_reminders, goal_updates) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiii", $user_id, $email_notifications, $workout_reminders, $goal_updates);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Notification preferences updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating notification preferences: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$preferences = [
    "email_notifications" => true,
    "workout_reminders" => true,
    "goal_updates" => true
];

$result = mysqli_query($conn, "SHOW TABLES LIKE 'user_preferences'");
if (mysqli_num_rows($result) > 0) {
    $query = "SELECT * FROM user_preferences WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $pref = mysqli_fetch_assoc($result);
        $preferences["email_notifications"] = $pref["email_notifications"];
        $preferences["workout_reminders"] = $pref["workout_reminders"];
        $preferences["goal_updates"] = $pref["goal_updates"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
    <style>
        :root {
            /* Define consistent variables */
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #4cc9f0;
            --danger: #e63946;
            --dark-bg: #121212;
            --dark-card: #1E1E1E;
            --dark-card-hover: #2a2a2a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --text-muted: #777777;
            --border-color: rgba(255, 255, 255, 0.1);
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition-standard: all 0.3s ease;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 6px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: var(--dark-bg);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-card);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-logo {
            font-family: 'Koulen', sans-serif;
            font-size: 1.8rem;
            color: var(--text-light);
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .sidebar-profile {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }
        
        .sidebar-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-user-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px;
        }
        
        .sidebar-user-email {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0 0 10px;
        }
        
        .sidebar-user-since {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .sidebar-nav {
            padding: 20px;
            flex: 1;
        }
        
        .sidebar-nav-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin: 0 0 15px;
        }
        
        .sidebar-nav-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav-item {
            margin-bottom: 5px;
        }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition-standard);
        }
        
        .sidebar-nav-link:hover {
            background-color: var(--dark-card-hover);
        }
        
        .sidebar-nav-link.active {
            background-color: var(--primary);
        }
        
        .sidebar-nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .sidebar-footer-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            background-color: rgba(230, 57, 70, 0.1);
            color: #e63946;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-standard);
        }
        
        .sidebar-footer-button:hover {
            background-color: rgba(230, 57, 70, 0.2);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        .page-header {
            margin-bottom: 25px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-light);
        }
        
        /* Alert styles */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .alert-success {
            background-color: rgba(0, 204, 102, 0.1);
            border-left: 4px solid #00cc66;
        }
        
        .alert-error {
            background-color: rgba(230, 57, 70, 0.1);
            border-left: 4px solid #e63946;
        }
        
        .alert-icon {
            font-size: 1.2rem;
        }
        
        .alert-success .alert-icon {
            color: #00cc66;
        }
        
        .alert-error .alert-icon {
            color: #e63946;
        }
        
        .alert-content {
            flex: 1;
        }
        
        /* Settings specific styles */
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 5px;
        }
        
        .settings-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .settings-tab {
            padding: 12px 20px;
            background-color: var(--dark-card);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition-standard);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .settings-tab:hover {
            background-color: var(--dark-card-hover);
            transform: translateY(-2px);
        }
        
        .settings-tab.active {
            background-color: var(--primary);
            color: white;
        }
        
        .settings-tab i {
            font-size: 1.1rem;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .settings-card {
            background-color: var(--dark-card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        
        .settings-card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .settings-card-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-card-title i {
            color: var(--primary);
        }
        
        .settings-card-body {
            padding: 30px;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-light);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition-standard);
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-standard);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d62f3c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Toggle switch */
        .switch-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .switch-label {
            font-weight: 500;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Radio options */
        .radio-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 5px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .radio-option-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: var(--dark-card);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-header {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <!-- Mobile menu toggle button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main dashboard layout -->
    <div class="dashboard">
        <!-- Include the sidebar -->
        <?php require_once 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Settings</h1>
            </div>
            
            <?php if (!empty($success_message)) { ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Success!</strong> <?php echo $success_message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <?php if (!empty($error_message)) { ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error!</strong> <?php echo $error_message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <!-- Settings tabs -->
            <div class="settings-tabs" id="settings-tabs">
                <div class="settings-tab active" data-tab="profile">
                    <i class="fas fa-user"></i> Profile
                </div>
                <div class="settings-tab" data-tab="security">
                    <i class="fas fa-lock"></i> Security
                </div>
                <div class="settings-tab" data-tab="notifications">
                    <i class="fas fa-bell"></i> Notifications
                </div>
                <div class="settings-tab" data-tab="privacy">
                    <i class="fas fa-shield-alt"></i> Privacy
                </div>
                <div class="settings-tab" data-tab="account">
                    <i class="fas fa-user-cog"></i> Account
                </div>
            </div>
            
            <!-- Profile settings -->
            <div class="tab-content active" id="profile-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-user-circle"></i> Profile Information
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($profile_data['fullname'] ?? ''); ?>" placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION["email"] ?? ''); ?>" placeholder="Enter your email">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" class="form-control">
                                        <option value="" <?php echo empty($profile_data['gender']) ? 'selected' : ''; ?>>Select gender</option>
                                        <option value="M" <?php echo ($profile_data['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                        <option value="F" <?php echo ($profile_data['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        <option value="O" <?php echo ($profile_data['gender'] ?? '') === 'O' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="birthdate">Date of Birth</label>
                                    <input type="date" id="birthdate" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($profile_data['birthdate'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="height">Height (cm)</label>
                                    <input type="number" id="height" name="height" class="form-control" value="<?php echo htmlspecialchars($profile_data['height'] ?? ''); ?>" placeholder="Enter your height">
                                </div>
                                
                                <div class="form-group">
                                    <label for="weight">Weight (kg)</label>
                                    <input type="number" id="weight" name="weight" class="form-control" value="<?php echo htmlspecialchars($profile_data['weight'] ?? ''); ?>" placeholder="Enter your weight">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="fitness_level">Fitness Level</label>
                                <select id="fitness_level" name="fitness_level" class="form-control">
                                    <option value="" <?php echo empty($profile_data['fitness_level']) ? 'selected' : ''; ?>>Select fitness level</option>
                                    <option value="Beginner" <?php echo ($profile_data['fitness_level'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="Intermediate" <?php echo ($profile_data['fitness_level'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="Advanced" <?php echo ($profile_data['fitness_level'] ?? '') === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Security settings -->
            <div class="tab-content" id="security-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-lock"></i> Change Password
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter your new password">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your new password">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Notification settings -->
            <div class="tab-content" id="notifications-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-bell"></i> Notification Preferences
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="switch-container">
                                <span class="switch-label">Workout Reminders</span>
                                <label class="switch">
                                    <input type="checkbox" name="workout_reminders" <?php echo ($notification_settings['workout_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="switch-container">
                                <span class="switch-label">Goal Updates</span>
                                <label class="switch">
                                    <input type="checkbox" name="goal_updates" <?php echo ($notification_settings['goal_updates'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="switch-container">
                                <span class="switch-label">Achievement Alerts</span>
                                <label class="switch">
                                    <input type="checkbox" name="achievement_alerts" <?php echo ($notification_settings['achievement_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="switch-container">
                                <span class="switch-label">Email Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" name="email_notifications" <?php echo ($notification_settings['email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_notifications" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Preferences
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Privacy settings -->
            <div class="tab-content" id="privacy-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-shield-alt"></i> Privacy Settings
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label for="profile_visibility">Profile Visibility</label>
                                <div class="radio-container">
                                    <div class="radio-option">
                                        <input type="radio" id="visibility_private" name="profile_visibility" value="private" <?php echo ($privacy_settings['profile_visibility'] ?? 'private') === 'private' ? 'checked' : ''; ?>>
                                        <label for="visibility_private" class="radio-option-label">
                                            <i class="fas fa-lock"></i> Private
                                        </label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="visibility_friends" name="profile_visibility" value="friends" <?php echo ($privacy_settings['profile_visibility'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                        <label for="visibility_friends" class="radio-option-label">
                                            <i class="fas fa-user-friends"></i> Friends Only
                                        </label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="visibility_public" name="profile_visibility" value="public" <?php echo ($privacy_settings['profile_visibility'] ?? '') === 'public' ? 'checked' : ''; ?>>
                                        <label for="visibility_public" class="radio-option-label">
                                            <i class="fas fa-globe"></i> Public
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="switch-container">
                                <span class="switch-label">Share Workout Data with Friends</span>
                                <label class="switch">
                                    <input type="checkbox" name="share_workout_data" <?php echo ($privacy_settings['share_workout_data'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="switch-container">
                                <span class="switch-label">Allow Fitness Insights & Recommendations</span>
                                <label class="switch">
                                    <input type="checkbox" name="allow_fitness_insights" <?php echo ($privacy_settings['allow_fitness_insights'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_privacy" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Privacy Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Account settings -->
            <div class="tab-content" id="account-tab">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">
                            <i class="fas fa-user-cog"></i> Account Management
                        </h2>
                    </div>
                    <div class="settings-card-body">
                        <div class="form-group">
                            <label>Export Your Data</label>
                            <p style="color: var(--gray-light); margin-bottom: 15px;">Download all your workout history, goals, and personal data.</p>
                            <button class="btn btn-secondary" id="exportDataBtn">
                                <i class="fas fa-file-export"></i> Export Data
                            </button>
                        </div>
                        
                        <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.05); margin: 30px 0;">
                        
                        <div class="form-group">
                            <label>Delete Account</label>
                            <p style="color: var(--gray-light); margin-bottom: 15px;">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <button class="btn btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal" id="deleteAccountModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background-color: var(--dark-card); border-radius: 16px; width: 90%; max-width: 500px; overflow: hidden; box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);">
            <div style="padding: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                <h2 style="color: var(--danger); font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Delete Account
                </h2>
            </div>
            <div style="padding: 25px;">
                <p style="margin-bottom: 25px;">Are you sure you want to delete your account? This action will permanently remove all your data and cannot be undone.</p>
                <div style="display: flex; justify-content: flex-end; gap: 15px;">
                    <button class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                    <form method="POST" action="">
                        <button type="submit" name="delete_account" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.settings-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');
                    
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === `${tabId}-tab`) {
                            content.classList.add('active');
                        }
                    });
                });
            });
            
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    mobileMenuToggle.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
            
            const deleteAccountBtn = document.getElementById('deleteAccountBtn');
            const deleteAccountModal = document.getElementById('deleteAccountModal');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            
            deleteAccountBtn.addEventListener('click', () => {
                deleteAccountModal.style.display = 'flex';
            });
            
            cancelDeleteBtn.addEventListener('click', () => {
                deleteAccountModal.style.display = 'none';
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === deleteAccountModal) {
                    deleteAccountModal.style.display = 'none';
                }
            });
            
            const exportDataBtn = document.getElementById('exportDataBtn');
            exportDataBtn.addEventListener('click', () => {
                alert('Your data export has been initiated. You will receive a download link shortly.');
            });
        });
    </script>
</body>
</html> 