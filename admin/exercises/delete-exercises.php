<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

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

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['selected_exercises']) || !is_array($_POST['selected_exercises']) || empty($_POST['selected_exercises'])) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => t('no_exercises_selected')
    ];
    header("Location: index.php");
    exit;
}

$selected_exercises = array_map('intval', $_POST['selected_exercises']);
$placeholders = implode(',', array_fill(0, count($selected_exercises), '?'));
$types = str_repeat('i', count($selected_exercises));

$check_sql = "SELECT COUNT(*) as count FROM exercises WHERE id IN ($placeholders)";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param($types, ...$selected_exercises);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$count = $check_result->fetch_assoc()['count'];

if ($count != count($selected_exercises)) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => t('some_exercises_not_found')
    ];
    header("Location: index.php");
    exit;
}

$delete_sql = "DELETE FROM exercises WHERE id IN ($placeholders)";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param($types, ...$selected_exercises);

if ($delete_stmt->execute()) {
    $deleted_count = $conn->affected_rows;
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => sprintf(t('exercises_deleted_successfully'), $deleted_count)
    ];
} else {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => t('error_deleting_exercises') . ": " . $conn->error
    ];
}

header("Location: index.php");
exit;
?> 