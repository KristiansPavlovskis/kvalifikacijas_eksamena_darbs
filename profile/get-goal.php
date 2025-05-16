<?php
session_start();
require_once "../assets/db_connection.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid goal ID"]);
    exit;
}

$goal_id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];

$query = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $goal_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $row["deadline"] = date("Y-m-d", strtotime($row["deadline"]));
    
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Goal not found"]);
} 