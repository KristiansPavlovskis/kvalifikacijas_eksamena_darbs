<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

session_start();
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

$pageTitle = "Create Template";
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
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1>Create Template</h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="admin-content-box">
                    <h2>Create New Workout Template</h2>
                    <p>This page will contain a form to create a new workout template.</p>
                    <a href="/admin/index.php" class="admin-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
</body>
</html> 