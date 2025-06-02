<?php
require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to access this resource']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No measurement ID provided']);
    exit;
}

$measurement_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("
        SELECT * 
        FROM body_measurements 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $measurement_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Measurement not found or you do not have permission to view it']);
        exit;
    }

    $measurement = $result->fetch_assoc();
    
    $check_table_stmt = $conn->prepare("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'measurement_photos'
    ");
    $check_table_stmt->execute();
    $check_result = $check_table_stmt->get_result();
    $table_exists = $check_result->fetch_assoc()['table_exists'] > 0;
    
    if ($table_exists) {
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
    }

    echo json_encode(['success' => true, 'measurement' => $measurement]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 