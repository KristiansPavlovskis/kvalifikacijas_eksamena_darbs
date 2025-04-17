<?php
require_once 'profile_access_control.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/quick-workout.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
  
    if (isset($_POST['measurement_date'])) {
        $date = $_POST['measurement_date'];
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $body_fat = !empty($_POST['body_fat']) ? $_POST['body_fat'] : null;
        $chest = !empty($_POST['chest']) ? $_POST['chest'] : null;
        $arms = !empty($_POST['arms']) ? $_POST['arms'] : null;
        $waist = !empty($_POST['waist']) ? $_POST['waist'] : null;
        $shoulders = !empty($_POST['shoulders']) ? $_POST['shoulders'] : null;
        $legs = !empty($_POST['legs']) ? $_POST['legs'] : null;
        $hips = !empty($_POST['hips']) ? $_POST['hips'] : null;
        $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
        
        try {
            $stmt = $conn->prepare("INSERT INTO body_measurements 
            (user_id, weight, body_fat, chest, waist, shoulders, hips, notes, measurement_date,
            arm_left_bicep, arm_right_bicep, arm_left_forearm, arm_right_forearm,
            leg_left_quad, leg_right_quad, leg_left_calf, leg_right_calf) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("iddddddssddddddddd", 
            $user_id, $weight, $body_fat, $chest, $waist, $shoulders, $hips, $notes, $date,
            $_POST['arm_left_bicep'] ?? null,
            $_POST['arm_right_bicep'] ?? null,
            $_POST['arm_left_forearm'] ?? null,
            $_POST['arm_right_forearm'] ?? null,
            $_POST['leg_left_quad'] ?? null,
            $_POST['leg_right_quad'] ?? null,
            $_POST['leg_left_calf'] ?? null,
            $_POST['leg_right_calf'] ?? null);
            
            if ($stmt->execute()) {
                $measurement_id = $conn->insert_id;
                $response['success'] = true;
                $response['message'] = "Measurements saved successfully!";
                
                if (!empty($_FILES['frontPhoto']['name'])) {
                    $result = savePhoto('frontPhoto', $user_id, $measurement_id, 'front');
                    if (!$result) {
                        $response['message'] .= " (Note: Front photo upload failed)";
                    }
                }
                
                if (!empty($_FILES['sidePhoto']['name'])) {
                    $result = savePhoto('sidePhoto', $user_id, $measurement_id, 'side');
                    if (!$result) {
                        $response['message'] .= " (Note: Side photo upload failed)";
                    }
                }
            } else {
                $response['message'] = "Error saving measurements: " . $conn->error;
            }
        } catch (Exception $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
}

function savePhoto($fileField, $user_id, $measurement_id, $view_type) {
    $userDir = "../uploads/measurements/" . $user_id;
    if (!file_exists($userDir)) {
        mkdir($userDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION);
    $newFilename = $measurement_id . '_' . $view_type . '_' . time() . '.' . $fileExtension;
    $targetPath = $userDir . '/' . $newFilename;
    
    if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $targetPath)) {
        global $conn;
        
        $stmt = $conn->prepare("INSERT INTO measurement_photos (measurement_id, photo_path, view_type, uploaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $measurement_id, $targetPath, $view_type);
        $stmt->execute();
        
        return $targetPath;
    }
    
    return false;
}

$stmt = $conn->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY measurement_date DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$measurements = $result->fetch_all(MYSQLI_ASSOC);

if (count($measurements) >= 2) {
    $latest = $measurements[0];
    $previous = $measurements[1];
    
    $weight_change = ($latest['weight'] ?? 0) - ($previous['weight'] ?? 0);
    $bodyfat_change = ($latest['body_fat'] ?? 0) - ($previous['body_fat'] ?? 0);
    $waist_change = ($latest['waist'] ?? 0) - ($previous['waist'] ?? 0);

    $chest_change = ($latest['chest'] ?? 0) - ($previous['chest'] ?? 0);
    $arms_change = ($latest['arms'] ?? 0) - ($previous['arms'] ?? 0);
    $shoulders_change = ($latest['shoulders'] ?? 0) - ($previous['shoulders'] ?? 0);
    $legs_change = ($latest['legs'] ?? 0) - ($previous['legs'] ?? 0);
    $hips_change = ($latest['hips'] ?? 0) - ($previous['hips'] ?? 0);
    $arm_left_bicep_change = ($latest['arm_left_bicep'] ?? 0) - ($previous['arm_left_bicep'] ?? 0);
    $arm_right_bicep_change = ($latest['arm_right_bicep'] ?? 0) - ($previous['arm_right_bicep'] ?? 0);
    $arm_left_forearm_change = ($latest['arm_left_forearm'] ?? 0) - ($previous['arm_left_forearm'] ?? 0);
    $arm_right_forearm_change = ($latest['arm_right_forearm'] ?? 0) - ($previous['arm_right_forearm'] ?? 0);
    $leg_left_quad_change = ($latest['leg_left_quad'] ?? 0) - ($previous['leg_left_quad'] ?? 0);
    $leg_right_quad_change = ($latest['leg_right_quad'] ?? 0) - ($previous['leg_right_quad'] ?? 0);
    $leg_left_calf_change = ($latest['leg_left_calf'] ?? 0) - ($previous['leg_left_calf'] ?? 0);
    $leg_right_calf_change = ($latest['leg_right_calf'] ?? 0) - ($previous['leg_right_calf'] ?? 0);

    $calc_percent = function($current, $previous) {
        if ($previous === 0 || $previous === null) return 0;
        return (($current - $previous) / $previous) * 100;
    };

    $weight_change_percent = $calc_percent($latest['weight'] ?? 0, $previous['weight'] ?? 0);
    $bodyfat_change_percent = $calc_percent($latest['body_fat'] ?? 0, $previous['body_fat'] ?? 0);
    $waist_change_percent = $calc_percent($latest['waist'] ?? 0, $previous['waist'] ?? 0);
    $chest_change_percent = $calc_percent($latest['chest'] ?? 0, $previous['chest'] ?? 0);
    $arms_change_percent = $calc_percent($latest['arms'] ?? 0, $previous['arms'] ?? 0);
    $shoulders_change_percent = $calc_percent($latest['shoulders'] ?? 0, $previous['shoulders'] ?? 0);
    $legs_change_percent = $calc_percent($latest['legs'] ?? 0, $previous['legs'] ?? 0);
    $hips_change_percent = $calc_percent($latest['hips'] ?? 0, $previous['hips'] ?? 0);


}
$chart_stmt = $conn->prepare("
    SELECT measurement_date, weight, body_fat, chest, arms, waist, shoulders, legs 
    FROM body_measurements 
    WHERE user_id = ? 
    ORDER BY measurement_date ASC 
    LIMIT 30
");
$chart_stmt->bind_param("i", $user_id);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();
$chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);

$dates = [];
$weight_data = [];
$bodyfat_data = [];
$chest_data = [];
$arms_data = [];
$waist_data = [];

foreach ($chart_data as $row) {
    $dates[] = date('M d', strtotime($row['measurement_date']));
    $weight_data[] = $row['weight'];
    $bodyfat_data[] = $row['body_fat'];
    $chest_data[] = $row['chest'];
    $arms_data[] = $row['arms'];
    $waist_data[] = $row['waist'];
}

$photos_stmt = $conn->prepare("
    SELECT mp.photo_path, mp.view_type, m.measurement_date 
    FROM measurement_photos mp
    JOIN body_measurements m ON mp.measurement_id = m.id
    WHERE m.user_id = ?
    ORDER BY m.measurement_date DESC
");
$photos_stmt->bind_param("i", $user_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = $photos_result->fetch_all(MYSQLI_ASSOC);

$front_photos = [];
$side_photos = [];

foreach ($photos as $photo) {
    if ($photo['view_type'] === 'front') {
        $front_photos[] = $photo;
    } else if ($photo['view_type'] === 'side') {
        $side_photos[] = $photo;
    }
}
require_once 'sidebar.php';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Body Measurements | Fitness Tracker</title>
    <style>
        :root {
            --dark-bg: #121212;
            --dark-surface: #1e1e1e;
            --dark-surface-lighter: #2d2d2d;
            --primary-red: #ff3a3a;
            --primary-red-dark: #cc2e2e;
            --text-light: #f5f5f5;
            --text-gray: #b0b0b0;
            --success-green: #4caf50;
            --error-red: #f44336;
            --accent-blue: #2196f3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--text-gray);
            font-size: 1.1rem;
        }
        
        .card {
            background-color: var(--dark-surface);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .card h2 {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: var(--primary-red);
        }
        
        .card h2 i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }
        
        .card h3 {
            margin: 1rem 0;
            color: var(--text-light);
            font-size: 1.2rem;
        }
        
        .card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-red);
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
            }
        }
        
        .form-control {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: var(--dark-surface-lighter);
            color: var(--text-light);
            font-size: 1rem;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-red);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group input {
            padding-right: 2.5rem;
        }
        
        .input-group-append {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            background-color: var(--primary-red);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--primary-red-dark);
        }
        
        .btn-secondary {
            background-color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #444;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #333;
            margin-bottom: 1.5rem;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-gray);
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            color: var(--primary-red);
            border-bottom: 2px solid var(--primary-red);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .chart-container {
            margin: 1.5rem 0;
            height: 300px;
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            background-color: var(--dark-surface-lighter);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background-color: var(--dark-surface-lighter);
            color: var(--text-gray);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .progress-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .progress-indicator span {
            flex: 1;
            margin-right: 0.5rem;
        }
        
        .progress-value {
            font-weight: 600;
        }
        
        .progress-change {
            margin-left: 0.5rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .positive-change {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success-green);
        }
        
        .negative-change {
            background-color: rgba(244, 67, 54, 0.15);
            color: var(--error-red);
        }
        
        .upload-area {
            border: 2px dashed #333;
            padding: 2rem;
            text-align: center;
            border-radius: 4px;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-red);
        }
        
        .upload-area i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-gray);
        }
        
        .photo-comparison {
            display: flex;
            gap: 1rem;
        }
        
        .photo-container {
            flex: 1;
            background-color: var(--dark-surface-lighter);
            border-radius: 4px;
            overflow: hidden;
            aspect-ratio: 3/4;
            position: relative;
        }
        
        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-date {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.5rem;
            background-color: rgba(0, 0, 0, 0.7);
            font-size: 0.9rem;
        }
        
        .calculator-container {
            border-top: 1px solid #333;
            margin-top: 2rem;
            padding-top: 1.5rem;
        }
        
        .result-box {
            background-color: var(--dark-surface-lighter);
            padding: 1.5rem;
            border-radius: 4px;
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .result-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-red);
            margin-bottom: 0.5rem;
        }
        
        .result-label {
            font-size: 0.9rem;
            color: var(--text-gray);
        }
        
        .heatmap {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .heatmap-cell {
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .heatmap-value {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .heatmap-label {
            font-size: 0.8rem;
            color: var(--text-gray);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body>
    <div class="container">
        
        <div class="page-header">
            <h1>Body Measurements</h1>
            <p>Track your physical changes and visualize your progress over time</p>
        </div>
        
        <div class="card-grid">
            <div class="card">
                <h2><i class="fas fa-tape"></i> Record Measurements</h2>
                <form id="measurementForm">
                    <div class="form-row">
                        <div class="form-control">
                            <label for="measurement_date">Date</label>
                            <input type="date" id="measurement_date" name="measurement_date" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-control">
                            <label for="weight">Weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="weight" name="weight" placeholder="Your current weight" required>
                                <div class="input-group-append">kg</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="body_fat">Body Fat Percentage</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="body_fat" name="body_fat" placeholder="Your body fat percentage">
                                <div class="input-group-append">%</div>
                            </div>
                        </div>
                    </div>
                    
                    <h3>Circumference Measurements</h3>
                    <div class="form-row">
                        <div class="form-control">
                            <label for="chest">Chest</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="chest" name="chest" placeholder="Chest circumference">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="shoulders">Shoulders</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="shoulders" name="shoulders" placeholder="Shoulder circumference">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-control">
                            <label for="arm_left_bicep">Left Bicep</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="arm_left_bicep" name="arm_left_bicep">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="arm_right_bicep">Right Bicep</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="arm_right_bicep" name="arm_right_bicep">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="waist">Waist</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="waist" name="waist" placeholder="Waist circumference">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-control">
                            <label for="arm_left_forearm">Left Forearm</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="arm_left_forearm" name="arm_left_forearm">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="arm_right_forearm">Right Forearm</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="arm_right_forearm" name="arm_right_forearm">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-control">
                            <label for="hips">Hips</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="hips" name="hips" placeholder="Hip circumference">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="leg_left_quad">Left Quad</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="leg_left_quad" name="leg_left_quad">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="leg_right_quad">Right Quad</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="leg_right_quad" name="leg_right_quad">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-control">
                            <label for="leg_left_calf">Left Calf</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="leg_left_calf" name="leg_left_calf">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label for="leg_right_calf">Right Calf</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="leg_right_calf" name="leg_right_calf">
                                <div class="input-group-append">cm</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calculator-container">
                        <h3>Don't know your body fat percentage?</h3>
                        <p>Use our calculator to estimate your body fat percentage based on measurements.</p>
                        
                        <div class="form-row">
                            <div class="form-control">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" placeholder="Your age">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-control">
                                <label for="height">Height</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" id="height" name="height" placeholder="Your height">
                                    <div class="input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="form-control">
                                <label for="neck">Neck Circumference</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" id="neck" name="neck" placeholder="Neck circumference">
                                    <div class="input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn" id="calculateBodyFat">Calculate Body Fat %</button>
                        
                        <div class="result-box" id="bodyFatResult" style="display: none;">
                            <div class="result-value" id="bodyFatValue">0.0%</div>
                            <div class="result-label">Estimated Body Fat Percentage</div>
                            <p class="result-info">This is an estimate based on the Navy Body Fat Formula. For more accurate results, consider using calipers or professional body composition testing.</p>
                            <button type="button" class="btn btn-secondary" id="useCalculatedValue">Use This Value</button>
                        </div>
                    </div>
                    
                    <h3>Progress Photos</h3>
                    <div class="form-row">
                        <div class="form-control">
                            <label>Front View</label>
                            <div class="upload-area" id="frontPhotoUpload">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <input type="file" id="frontPhoto" name="frontPhoto" accept="image/*" style="display: none;">
                            </div>
                        </div>
                        <div class="form-control">
                            <label>Side View</label>
                            <div class="upload-area" id="sidePhotoUpload">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <input type="file" id="sidePhoto" name="sidePhoto" accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Add any notes about your measurements or progress..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-block">Save Measurements</button>
                </form>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-chart-line"></i> Progress Tracking</h2>
                
                <div class="tabs">
                    <div class="tab active" data-tab="history">History</div>
                    <div class="tab" data-tab="charts">Charts</div>
                    <div class="tab" data-tab="photos">Photos</div>
                    <div class="tab" data-tab="heatmap">Heatmap</div>
                </div>
                
                <div class="tab-content active" id="history">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Weight</th>
                                    <th>Body Fat</th>
                                    <th>Chest</th>
                                    <th>Arms</th>
                                    <th>Waist</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="measurementHistory">
                                <?php if (!empty($measurements)): ?>
                                    <?php foreach ($measurements as $measurement): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($measurement['measurement_date']) ?></td>
                                        <td><?= $measurement['weight'] ? htmlspecialchars($measurement['weight']) . ' kg' : 'N/A' ?></td>
                                        <td><?= $measurement['body_fat'] ? htmlspecialchars($measurement['body_fat']) . '%' : 'N/A' ?></td>
                                        <td><?= $measurement['chest'] ? htmlspecialchars($measurement['chest']) . ' cm' : 'N/A' ?></td>
                                        <td><?= $measurement['arms'] ? htmlspecialchars($measurement['arms']) . ' cm' : 'N/A' ?></td>
                                        <td><?= $measurement['waist'] ? htmlspecialchars($measurement['waist']) . ' cm' : 'N/A' ?></td>
                                        <td>
                                            <button class="btn btn-secondary view-details" data-id="<?= $measurement['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn edit-measurement" data-id="<?= $measurement['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No measurements recorded yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                                
                <div class="tab-content" id="charts">
                    <div class="form-row">
                        <div class="form-control">
                            <label for="chartType">Chart Type</label>
                            <select id="chartType">
                                <option value="weight">Weight</option>
                                <option value="bodyFat">Body Fat %</option>
                                <option value="measurements">Measurements</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label for="timeRange">Time Range</label>
                            <select id="timeRange">
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="180">Last 180 Days</option>
                                <option value="365">Last Year</option>
                                <option value="all">All Time</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                    
                    <h3>Summary</h3>
                    <div class="progress-indicator">
                        <span>Weight Change</span>
                        <div class="progress-value"><?= isset($weight_change) ? number_format($weight_change, 1) . ' kg' : 'N/A' ?></div>
                        <?php if (isset($weight_change_percent)): ?>
                        <div class="progress-change <?= $weight_change_percent < 0 ? 'negative-change' : 'positive-change' ?>">
                            <?= number_format($weight_change_percent, 1) ?>%
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="progress-indicator">
                        <span>Body Fat Change</span>
                        <div class="progress-value">-1.3%</div>
                        <div class="progress-change negative-change">-6.7%</div>
                    </div>
                    <div class="progress-indicator">
                        <span>Waist Change</span>
                        <div class="progress-value">-2.0 cm</div>
                        <div class="progress-change negative-change">-2.4%</div>
                    </div>
                </div>
                
                <div class="tab-content" id="photos">
                    <div class="form-row">
                        <div class="form-control">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate">
                        </div>
                        <div class="form-control">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate">
                        </div>
                    </div>
                    
                    <h3>Front View Comparison</h3>
                    <div class="photo-comparison">
                        <div class="photo-container">
                            <img src="/api/placeholder/300/400" alt="Front view - start date">
                            <div class="photo-date">Apr 1, 2025</div>
                        </div>
                        <div class="photo-container">
                            <img src="/api/placeholder/300/400" alt="Front view - end date">
                            <div class="photo-date">Apr 15, 2025</div>
                        </div>
                    </div>
                    
                    <h3>Side View Comparison</h3>
                    <div class="photo-comparison">
                        <?php for ($i = 0; $i < 2; $i++): ?>
                        <div class="photo-container">
                            <?php if (isset($front_photos[$i])): ?>
                            <img src="<?= $front_photos[$i]['photo_path'] ?>" alt="Front view">
                            <div class="photo-date">
                                <?= date('M j, Y', strtotime($front_photos[$i]['measurement_date'])) ?>
                            </div>
                            <?php else: ?>
                            <div class="no-photo">No photo available</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="tab-content" id="heatmap">
                    <h3>Measurement Change Heatmap</h3>
                    <p>Showing changes from Apr 1 to Apr 15, 2025</p>
                    
                    <div class="heatmap">
                        <?php
                        $changes = [
                            'Weight' => [$weight_change ?? 0, $weight_change_percent ?? 0],
                            'Body Fat' => [$bodyfat_change ?? 0, $bodyfat_change_percent ?? 0],
                            'Chest' => [$chest_change ?? 0, $chest_change_percent ?? 0],
                            'Left Bicep' => [$arm_left_bicep_change ?? 0, $arm_left_bicep_change_percent ?? 0],
                            'Right Bicep' => [$arm_right_bicep_change ?? 0, $arm_right_bicep_change_percent ?? 0],
                            'Waist' => [$waist_change ?? 0, $waist_change_percent ?? 0],
                            'Left Forearm' => [$arm_left_forearm_change ?? 0, $arm_left_forearm_change_percent ?? 0],
                            'Right Forearm' => [$arm_right_forearm_change ?? 0, $arm_right_forearm_change_percent ?? 0],
                            'Shoulders' => [$shoulders_change ?? 0, $shoulders_change_percent ?? 0],
                            'Hips' => [$hips_change ?? 0, $hips_change_percent ?? 0],
                            'Left Quad' => [$leg_left_quad_change ?? 0, $leg_left_quad_change_percent ?? 0],
                            'Right Quad' => [$leg_right_quad_change ?? 0, $leg_right_quad_change_percent ?? 0],
                            'Left Calf' => [$leg_left_calf_change ?? 0, $leg_left_calf_change_percent?? 0],
                            'Right Calf' => [$leg_right_calf_change ?? 0, $leg_right_calf_change_percent ?? 0]           
                        ];
                        
                        foreach ($changes as $label => $change):
                            $value = $change[0] ?? 0;
                            $percent = $change[1] ?? 0;
                            $intensity = min(abs($percent) / 10, 0.5);
                            $color = $value < 0 ? 'rgba(244, 67, 54, ' : 'rgba(76, 175, 80, ';
                        ?>
                        <div class="heatmap-cell" style="background-color: <?= $color . $intensity . ')' ?>">
                            <div class="heatmap-value"><?= number_format($value, 1) ?></div>
                            <div class="heatmap-label"><?= $label ?></div>
                            <div class="heatmap-percent"><?= number_format($percent, 1) ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('measurement_date').value = today;
            
            initCharts();
            
            setupTabs();
            
            setupPhotoUpload();
            
            setupBodyFatCalculator();
        });
        
        function setupTabs() {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const tabContents = document.querySelectorAll('.tab-content');
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                    
                    if (tabId === 'charts') {
                        updateChart();
                    }
                });
            });
        }
        
        function setupPhotoUpload() {
            const frontUpload = document.getElementById('frontPhotoUpload');
            const sideUpload = document.getElementById('sidePhotoUpload');
            const frontInput = document.getElementById('frontPhoto');
            const sideInput = document.getElementById('sidePhoto');
            
            console.log(frontInput);
            
            frontUpload.addEventListener('click', () => frontInput.click());
            sideUpload.addEventListener('click', () => sideInput.click());
            
            frontInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        frontUpload.innerHTML = '';
                        frontUpload.appendChild(img);
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            sideInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        sideUpload.innerHTML = '';
                        sideUpload.appendChild(img);
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        function setupBodyFatCalculator() {
            const calculateBtn = document.getElementById('calculateBodyFat');
            const resultBox = document.getElementById('bodyFatResult');
            const bodyFatValue = document.getElementById('bodyFatValue');
            const useValueBtn = document.getElementById('useCalculatedValue');
            
            calculateBtn.addEventListener('click', function() {
                const gender = document.getElementById('gender').value;
                const weight = parseFloat(document.getElementById('weight').value);
                const height = parseFloat(document.getElementById('height').value);
                const waist = parseFloat(document.getElementById('waist').value);
                const neck = parseFloat(document.getElementById('neck').value);
                const hips = gender === 'female' ? parseFloat(document.getElementById('hips').value) : 0;
                
                if (!weight || !height || !waist || !neck || (gender === 'female' && !hips)) {
                    alert('Please fill all required fields to calculate body fat percentage.');
                    return;
                }
                
                let bodyFat;
                if (gender === 'male') {
                    bodyFat = 495 / (1.0324 - 0.19077 * Math.log10(waist - neck) + 0.15456 * Math.log10(height)) - 450;
                } else {
                    bodyFat = 495 / (1.29579 - 0.35004 * Math.log10(waist + hips - neck) + 0.22100 * Math.log10(height)) - 450;
                }
                
                bodyFat = Math.round(bodyFat * 10) / 10;
                
                bodyFatValue.textContent = bodyFat.toFixed(1) + '%';
                resultBox.style.display = 'block';
            });
            
            useValueBtn.addEventListener('click', function() {
                const calculatedValue = parseFloat(bodyFatValue.textContent);
                document.getElementById('body_fat').value = calculatedValue;
                resultBox.style.display = 'none';
                
                document.getElementById('body_fat').scrollIntoView({ behavior: 'smooth' });
            });
        }
        
        function initCharts() {
            const ctx = document.getElementById('progressChart').getContext('2d');
            const dates = <?= json_encode($dates) ?>;  
            
            const labels = ['Apr 1', 'Apr 5', 'Apr 10', 'Apr 15'];
            const weightData = [82.0, 81.5, 81.0, 80.5];
            const bodyFatData = [19.5, 19.0, 18.5, 18.2];
            
            window.progressChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Weight (kg)',
                        data: <?= json_encode($weight_data) ?>,
                        backgroundColor: 'rgba(255, 58, 58, 0.1)',
                        borderColor: 'rgba(255, 58, 58, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 58, 58, 1)',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#b0b0b0'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#b0b0b0'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f5f5f5'
                            }
                        }
                    }
                }
            });
        }
        
        function updateChart() {
            const chartType = document.getElementById('chartType').value;
            const timeRange = document.getElementById('timeRange').value;
            
            const labels = ['Apr 1', 'Apr 5', 'Apr 10', 'Apr 15'];
            let datasets = [];
            
            if (chartType === 'weight') {
                datasets = [{
                    label: 'Weight (kg)',
                    data: [82.0, 81.5, 81.0, 80.5],
                    backgroundColor: 'rgba(255, 58, 58, 0.1)',
                    borderColor: 'rgba(255, 58, 58, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(255, 58, 58, 1)',
                    pointRadius: 4
                }];
            } else if (chartType === 'bodyFat') {
                datasets = [{
                    label: 'Body Fat (%)',
                    data: [19.5, 19.0, 18.5, 18.2],
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                    pointRadius: 4
                }];
            } else if (chartType === 'measurements') {
                datasets = [
                    {
                        label: 'Chest (cm)',
                        data: [104, 104.5, 105, 105],
                        borderColor: 'rgba(76, 175, 80, 1)',
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(76, 175, 80, 1)',
                        pointRadius: 4
                    },
                    {
                        label: 'Waist (cm)',
                        data: [84, 83.5, 83, 82],
                        borderColor: 'rgba(255, 58, 58, 1)',
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 58, 58, 1)',
                        pointRadius: 4
                    },
                    {
                        label: 'Arms (cm)',
                        data: [37, 37.5, 37.8, 38],
                        borderColor: 'rgba(33, 150, 243, 1)',
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                        pointRadius: 4
                    }
                ];
            }
            
            window.progressChart.data.datasets = datasets;
            window.progressChart.update();
        }
        
document.getElementById('measurementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    formData.append('ajax', 'true');
    
    const weight = formData.get('weight');
    if (!weight) {
        alert('Weight is required');
        return;
    }
    
    if (!formData.get('measurement_date')) {
        formData.set('measurement_date', new Date().toISOString().split('T')[0]);
    }

    const frontPhotoInput = document.getElementById('frontPhoto');
    const sidePhotoInput = document.getElementById('sidePhoto');
    
    if (frontPhotoInput && frontPhotoInput.files.length > 0) {
        formData.append('frontPhoto', frontPhotoInput.files[0]);
    }
    
    if (sidePhotoInput && sidePhotoInput.files.length > 0) {
        formData.append('sidePhoto', sidePhotoInput.files[0]);
    }
    
    fetch('body-measurements.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to save measurements'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving measurements. Please try again.');
    });
});
    </script>
</body>
</html>