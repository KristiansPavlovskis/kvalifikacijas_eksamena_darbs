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
    <link href="global-profile.css" rel="stylesheet">
</head>
<body style="display: block;">
    <div class="settings-dashboard">
        <?php require_once 'sidebar.php'; ?>
        
        <div class="settings-main-content">
            <div class="settings-page-header">
                <h1 class="settings-page-title">Settings</h1>
            </div>
            
            <?php if (!empty($message) && $message_type === "success") { ?>
                <div class="settings-alert settings-alert-success">
                    <div class="settings-alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="settings-alert-content">
                        <strong>Success!</strong> <?php echo $message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <?php if (!empty($message) && $message_type === "error") { ?>
                <div class="settings-alert settings-alert-error">
                    <div class="settings-alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="settings-alert-content">
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
            
            <div class="settings-tab-content active" id="profile-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-user-circle"></i> Profile Information
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="settings-form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="settings-form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter your username">
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="settings-form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email">
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" name="update_profile" class="settings-btn settings-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="settings-tab-content" id="security-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-lock"></i> Change Password
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="settings-form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="settings-form-control" placeholder="Enter your current password" required>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="settings-form-control" placeholder="Enter your new password" required>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="settings-form-control" placeholder="Confirm your new password" required>
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" name="change_password" class="settings-btn settings-btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="settings-tab-content" id="account-tab">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">
                            <i class="fas fa-user-cog"></i> Account Management
                        </h2>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-form-group">
                            <label>Delete Account</label>
                            <p style="color: var(--text-muted); margin-bottom: 15px;">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <button class="settings-btn settings-btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="settings-modal" id="deleteAccountModal">
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <h2 class="settings-modal-title" style="color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> Delete Account
                </h2>
            </div>
            <div class="settings-modal-body">
                <div class="settings-alert settings-alert-error" style="margin-bottom: 20px;">
                    <div class="settings-alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="settings-alert-content">
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>
                </div>
                <p>Are you sure you want to delete your account? This will permanently remove all your data including workout history, goals, and personal information.</p>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn settings-btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" action="">
                    <button type="submit" name="delete_account" class="settings-btn settings-btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.settings-tab');
            const tabContents = document.querySelectorAll('.settings-tab-content');
            
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