<?php
require_once 'profile_access_control.php';
require_once 'languages.php';

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
        $language = trim($_POST["language"]);
        
        if ($new_username !== $username) {
            $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $message = t("username_exists");
                $message_type = "error";
            } else {
                $_SESSION["username"] = $new_username;
                $username = $new_username;
            }
        }
        
        if (empty($message)) {
            $query = "UPDATE users SET username = ?, email = ?, language = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssi", $new_username, $email, $language, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["language"] = $language;
                $message = t("profile_updated");
                $message_type = "success";
            } else {
                $message = t("error_updating_profile") . " " . mysqli_error($conn);
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
                    $message = t("password_changed");
                    $message_type = "success";
                } else {
                    $message = t("error_changing_password") . " " . mysqli_error($conn);
                    $message_type = "error";
                }
            } else {
                $message = t("passwords_dont_match");
                $message_type = "error";
            }
        } else {
            $message = t("current_password_incorrect");
            $message_type = "error";
        }
    }
    
    if (isset($_POST["delete_account"])) {
        $delete_measurements = "DELETE FROM body_measurements WHERE user_id = ?";
        $stmt_measurements = mysqli_prepare($conn, $delete_measurements);
        mysqli_stmt_bind_param($stmt_measurements, "i", $user_id);
        mysqli_stmt_execute($stmt_measurements);

        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            session_destroy();
            header("location: ../pages/login.php");
            exit;
        } else {
            $message = t("error_deleting_account") . " " . mysqli_error($conn);
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

$current_language = $user['language'] ?? 'en';
$_SESSION["language"] = $current_language;
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?php echo t('settings'); ?></title>
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
                <h1 class="settings-page-title"><?php echo t('settings'); ?></h1>
            </div>
            
            <?php if (!empty($message) && $message_type === "success") { ?>
                <div class="settings-alert settings-alert-success">
                    <div class="settings-alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="settings-alert-content">
                        <strong><?php echo t('success'); ?>!</strong> <?php echo $message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <?php if (!empty($message) && $message_type === "error") { ?>
                <div class="settings-alert settings-alert-error">
                    <div class="settings-alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="settings-alert-content">
                        <strong><?php echo t('error'); ?>!</strong> <?php echo $message; ?>
                    </div>
                </div>
            <?php } ?>
            
            <div class="settings-tabs" id="settings-tabs">
                <div class="settings-tab active" data-tab="profile">
                    <i class="fas fa-user"></i> <?php echo t('profile'); ?>
                </div>
                <div class="settings-tab" data-tab="security">
                    <i class="fas fa-lock"></i> <?php echo t('security'); ?>
                </div>
                <div class="settings-tab" data-tab="account">
                    <i class="fas fa-user-cog"></i> <?php echo t('account'); ?>
                </div>
            </div>
            
            <div class="settings-tab-content active" id="profile-tab">
                <form method="POST" action="">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2 class="settings-card-title">
                                <i class="fas fa-user-circle"></i> <?php echo t('profile_information'); ?>
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="settings-form-group">
                                <label for="username"><?php echo t('username'); ?></label>
                                <input type="text" id="username" name="username" class="settings-form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="<?php echo t('enter_username'); ?>">
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="email"><?php echo t('email_address'); ?></label>
                                <input type="email" id="email" name="email" class="settings-form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="<?php echo t('enter_email'); ?>">
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="language"><?php echo t('language'); ?></label>
                                <select id="language" name="language" class="settings-form-control">
                                    <option value="en" <?php echo ($current_language == 'en') ? 'selected' : ''; ?>><?php echo t('english'); ?></option>
                                    <option value="lv" <?php echo ($current_language == 'lv') ? 'selected' : ''; ?>><?php echo t('latvian'); ?></option>
                                </select>
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" name="update_profile" class="settings-btn settings-btn-primary">
                                    <i class="fas fa-save"></i> <?php echo t('save_changes'); ?>
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
                                <i class="fas fa-lock"></i> <?php echo t('change_password'); ?>
                            </h2>
                        </div>
                        <div class="settings-card-body">
                            <div class="settings-form-group">
                                <label for="current_password"><?php echo t('current_password'); ?></label>
                                <input type="password" id="current_password" name="current_password" class="settings-form-control" placeholder="<?php echo t('enter_current_password'); ?>" required>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="new_password"><?php echo t('new_password'); ?></label>
                                <input type="password" id="new_password" name="new_password" class="settings-form-control" placeholder="<?php echo t('enter_new_password'); ?>" required>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="confirm_password"><?php echo t('confirm_new_password'); ?></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="settings-form-control" placeholder="<?php echo t('confirm_new_password_placeholder'); ?>" required>
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" name="change_password" class="settings-btn settings-btn-primary">
                                    <i class="fas fa-key"></i> <?php echo t('update_password'); ?>
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
                            <i class="fas fa-user-cog"></i> <?php echo t('account_management'); ?>
                        </h2>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-form-group">
                            <label><?php echo t('delete_account'); ?></label>
                            <p style="color: var(--text-muted); margin-bottom: 15px;"><?php echo t('delete_account_description'); ?></p>
                            <button class="settings-btn settings-btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> <?php echo t('delete_account'); ?>
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
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('delete_account'); ?>
                </h2>
            </div>
            <div class="settings-modal-body">
                <div class="settings-alert settings-alert-error" style="margin-bottom: 20px;">
                    <div class="settings-alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="settings-alert-content">
                        <strong><?php echo t('warning'); ?>!</strong> <?php echo t('action_cannot_be_undone'); ?>
                    </div>
                </div>
                <p><?php echo t('confirm_delete_account_message'); ?></p>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn settings-btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <form method="POST" action="">
                    <button type="submit" name="delete_account" class="settings-btn settings-btn-danger">
                        <i class="fas fa-trash-alt"></i> <?php echo t('delete_account'); ?>
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