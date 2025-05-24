<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$is_admin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND (role_id = 5 OR role_id = 4)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_admin = ($row['count'] > 0);
}

if (!$is_admin) {
    header("Location: ../../pages/access_denied.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['ids'])) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'No exercises specified for deletion.'
    ];
    header("Location: index.php");
    exit;
}

$ids = json_decode($_POST['ids'], true);

if (empty($ids) || !is_array($ids)) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Invalid exercise data.'
    ];
    header("Location: index.php");
    exit;
}

$success_count = 0;
$error_count = 0;

foreach ($ids as $id) {
    $delete_sql = "DELETE FROM exercises WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $success_count++;
    } else {
        $error_count++;
    }
}

if ($error_count == 0) {
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => $success_count . ' exercise(s) deleted successfully.'
    ];
} else {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => $success_count . ' exercise(s) deleted successfully. ' . $error_count . ' failed.'
    ];
}

header("Location: index.php");
exit;
?> 