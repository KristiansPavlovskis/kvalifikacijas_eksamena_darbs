<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_error'] = t("error");
    header("Location: index.php");
    exit;
}

$edit_user_id = intval($_GET['id']);

$user_sql = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.created_at,
        u.last_active
    FROM users u
    WHERE u.id = ?
";

$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $edit_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    $_SESSION['admin_error'] = t("no_users_found");
    header("Location: index.php");
    exit;
}

$user = $user_result->fetch_assoc();

$roles_sql = "
    SELECT r.id, r.name, ur.user_id
    FROM roles r
    LEFT JOIN user_roles ur ON r.id = ur.role_id AND ur.user_id = ?
    ORDER BY r.name
";

$roles_stmt = $conn->prepare($roles_sql);
$roles_stmt->bind_param("i", $edit_user_id);
$roles_stmt->execute();
$roles_result = $roles_stmt->get_result();

$all_roles = [];
$user_roles = [];

while ($role = $roles_result->fetch_assoc()) {
    $all_roles[] = $role;
    if (isset($role['user_id']) && $role['user_id'] == $edit_user_id) {
        $user_roles[] = $role['id'];
    }
}

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = isset($_POST['roles']) ? intval($_POST['roles']) : null;
    
    if (empty($username)) {
        $errors[] = t("username_required");
    }
    
    if (empty($email)) {
        $errors[] = t("email_required");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t("invalid_email");
    }

    if (empty($role_id)) {
        $errors[] = t("role_required");
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $username, $email, $edit_user_id);
            $update_stmt->execute();
            
            $delete_roles_sql = "DELETE FROM user_roles WHERE user_id = ?";
            $delete_roles_stmt = $conn->prepare($delete_roles_sql);
            $delete_roles_stmt->bind_param("i", $edit_user_id);
            $delete_roles_stmt->execute();
            
            $insert_roles_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
            $insert_roles_stmt = $conn->prepare($insert_roles_sql);
            $insert_roles_stmt->bind_param("ii", $edit_user_id, $role_id);
            $insert_roles_stmt->execute();
            
            $conn->commit();
            $success_message = t("user_updated");
            
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            
            $roles_stmt->execute();
            $roles_result = $roles_stmt->get_result();
            
            $all_roles = [];
            $user_roles = [];
            
            while ($role = $roles_result->fetch_assoc()) {
                $all_roles[] = $role;
                if (isset($role['user_id']) && $role['user_id'] == $edit_user_id) {
                    $user_roles[] = $role['id'];
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = t("error_updating_user") . " " . $e->getMessage();
        }
    }
}

$pageTitle = t("edit_user");
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | GYMVERSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="/admin/includes/admin-styles.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--dark-accent);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-color);
            font-size: 0.9375rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23a0a0a0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: rgba(230, 22, 22, 0.2);
            color: #ff5630;
        }
        
        .alert-success {
            background-color: rgba(44, 198, 146, 0.2);
            color: #2cc692;
        }
        
        .user-avatar-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .user-avatar-edit {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--users-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .user-info-edit h2 {
            margin: 0 0 0.5rem;
        }
        
        .user-info-edit p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-secondary {
            background-color: var(--dark-accent);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background-color: var(--dark-bg);
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1><?php echo t("edit_user"); ?></h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span><?php echo t("admin"); ?></span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="admin-content-box">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-avatar-section">
                        <div class="user-avatar-edit">
                            <?php echo substr($user['username'], 0, 1); ?>
                        </div>
                        <div class="user-info-edit">
                            <h2><?php echo t("edit_user_details"); ?></h2>
                            <p><?php echo t("user_id"); ?>: #<?php echo $user['id']; ?> | <?php echo t("registered"); ?>: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username" class="form-label"><?php echo t("username"); ?></label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label"><?php echo t("email_address"); ?></label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label"><?php echo t("last_active"); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user['last_active'] ? date('M d, Y H:i', strtotime($user['last_active'])) : t('never'); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo t("user_role"); ?></label>
                            <div class="checkbox-group">
                                <?php foreach ($all_roles as $role): ?>
                                    <div class="checkbox-item">
                                        <input type="radio" 
                                               id="role_<?php echo $role['id']; ?>" 
                                               name="roles" 
                                               value="<?php echo $role['id']; ?>" 
                                               <?php echo isset($role['user_id']) ? 'checked' : ''; ?> 
                                               required>
                                        <label for="role_<?php echo $role['id']; ?>"><?php echo ucfirst($role['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <a href="index.php" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                <span><?php echo t("back_to_list"); ?></span>
                            </a>
                            <button type="submit" class="admin-btn">
                                <i class="fas fa-save"></i>
                                <span><?php echo t("save_changes"); ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
</body>
</html> 