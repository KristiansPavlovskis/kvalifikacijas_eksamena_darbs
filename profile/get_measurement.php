<?php
require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to access this resource']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No measurement ID provided']);
    exit;
}

$measurement_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT * 
    FROM body_measurements 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $measurement_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Measurement not found or you do not have permission to view it']);
    exit;
}

$measurement = $result->fetch_assoc();

$photos_stmt = $conn->prepare("
    SELECT photo_path, view_type
    FROM measurement_photos
    WHERE measurement_id = ?
    ORDER BY uploaded_at DESC
");
$photos_stmt->bind_param("i", $measurement_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();

while ($photo = $photos_result->fetch_assoc()) {
    if ($photo['view_type'] === 'front') {
        $measurement['front_photo'] = $photo['photo_path'];
    } else if ($photo['view_type'] === 'side') {
        $measurement['side_photo'] = $photo['photo_path'];
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'measurement' => $measurement]);
$conn->close();
?> 