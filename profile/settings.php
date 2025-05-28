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
        $new_username = trim($_POST["username"]);
        $email = trim($_POST["email"]);
        
        if ($new_username !== $username) {
            $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $message = "Username already exists! Please choose a different one.";
                $message_type = "error";
            } else {
                $_SESSION["username"] = $new_username;
                $username = $new_username;
            }
        }
        
        if (empty($message)) {
            $query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $new_username, $email, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Profile updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating profile: " . mysqli_error($conn);
                $message_type = "error";
            }
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
    
    if (isset($_POST["delete_account"])) {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            session_destroy();
            header("location: ../login.php?message=Account deleted successfully");
            exit;
        } else {
            $message = "Error deleting account: " . mysqli_error($conn);
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
    <link href="../assets/css/variables.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
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
    <div class="dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Settings</h1>
            </div>
            
            <?php if (!empty($message) && $message_type === "success") { ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Success!</strong> <?php echo $message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <?php if (!empty($message) && $message_type === "error") { ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error!</strong> <?php echo $message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <div class="settings-tabs" id="settings-tabs">
                <div class="settings-tab active" data-tab="profile">
                    <i class="fas fa-user"></i> Profile
                </div>
                <div class="settings-tab" data-tab="security">
                    <i class="fas fa-lock"></i> Security
                </div>
                <div class="settings-tab" data-tab="account">
                    <i class="fas fa-user-cog"></i> Account
                </div>
            </div>
            
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
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter your username">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email">
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
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter your new password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your new password" required>
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
            
            <div class="tab-content" id="account-tab">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">
                            <i class="fas fa-user-cog"></i> Account Management
                        </h2>
                    </div>
                    <div class="settings-card-body">
                        <div class="form-group">
                            <label>Delete Account</label>
                            <p style="color: var(--text-muted); margin-bottom: 15px;">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <button class="btn btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal" id="deleteAccountModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" style="color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> Delete Account
                </h2>
            </div>
            <div class="modal-body">
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>
                </div>
                <p>Are you sure you want to delete your account? This will permanently remove all your data including workout history, goals, and personal information.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" action="">
                    <button type="submit" name="delete_account" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </button>
                </form>
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
            
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    if (sidebar.classList.contains('active')) {
                        mobileMenuToggle.innerHTML = '<i class="fas fa-times"></i>';
                    } else {
                        mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                    }
                });
            }
            
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
        });
    </script>
</body>
</html> 