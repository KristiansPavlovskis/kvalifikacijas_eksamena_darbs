<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

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

$pageTitle = t('create_template');
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>">
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
                <h1><?php echo t('create_template'); ?></h1>
                <div class="admin-user">
                    <?php include dirname(__DIR__, 2) . '/includes/language-selector.php'; ?>
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span><?php echo t('administration'); ?></span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="admin-content-box">
                    <h2><?php echo t('create_new_workout_template'); ?></h2>
                    <p><?php echo t('create_template_page_description'); ?></p>
                    <a href="/admin/index.php" class="admin-btn"><i class="fas fa-arrow-left"></i> <?php echo t('back_to_dashboard'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
</body>
</html> 