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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'No exercise specified for deletion.'
    ];
    header("Location: index.php");
    exit;
}

$exercise_id = $_GET['id'];

$check_sql = "SELECT name FROM exercises WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $exercise_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION["message"] = [
        "type" => "error",
        "text" => "Exercise not found."
    ];
    header("Location: index.php");
    exit;
}

$exercise_name = $check_result->fetch_assoc()["name"];

$delete_sql = "DELETE FROM exercises WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $exercise_id);

if ($delete_stmt->execute()) {
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Exercise deleted successfully.'
    ];
} else {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Error deleting exercise: ' . $conn->error
    ];
}

header("Location: index.php");
exit;
?> 