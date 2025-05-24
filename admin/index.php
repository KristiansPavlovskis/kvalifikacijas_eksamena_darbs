<?php
require_once dirname(__DIR__) . '/assets/db_connection.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../pages/login.php");
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
    header("Location: ../pages/access_denied.php");
    exit;
}

$active_users_query = "SELECT COUNT(*) as count FROM users";
$active_users_result = $conn->query($active_users_query);
$active_users = $active_users_result->fetch_assoc()['count'];

$templates_query = "SELECT COUNT(*) as count FROM workout_templates";
$templates_result = $conn->query($templates_query);
$total_templates = $templates_result->fetch_assoc()['count'];

$exercises_query = "SELECT COUNT(*) as count FROM exercises";
$exercises_result = $conn->query($exercises_query);
$available_exercises = $exercises_result->fetch_assoc()['count'];

$pageTitle = "Admin Dashboard";
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="en">
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
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1>Admin Dashboard</h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon users-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h2><?php echo $active_users; ?></h2>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon templates-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h2><?php echo $total_templates; ?></h2>
                            <p>Total Templates</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon exercises-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="stat-info">
                            <h2><?php echo $available_exercises; ?></h2>
                            <p>Available Exercises</p>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-cards">
                        <a href="users/index.php" class="action-card">
                            <div class="action-icon user-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Add User</h3>
                            <p>Create new user account</p>
                        </a>
                        
                        <a href="templates/index.php" class="action-card">
                            <div class="action-icon template-icon">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <h3>Create Template</h3>
                            <p>Create new workout template</p>
                        </a>
                        
                        <a href="exercises/index.php" class="action-card">
                            <div class="action-icon exercise-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h3>Add Exercise</h3>
                            <p>Add new exercise to database</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
</body>
</html> 