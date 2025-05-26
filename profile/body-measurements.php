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
  
    if (isset($_POST['delete']) && isset($_POST['measurement_id'])) {
        $measurement_id = intval($_POST['measurement_id']);
        
        $check_stmt = $conn->prepare("SELECT id FROM body_measurements WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $measurement_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this measurement']);
            exit;
        }
        
        $delete_stmt = $conn->prepare("DELETE FROM body_measurements WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $measurement_id, $user_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Measurement deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting measurement: ' . $conn->error]);
        }
        exit;
    }
    
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
        $notes = null;
        
        $arm_left_bicep = !empty($_POST['arm_left_bicep']) ? $_POST['arm_left_bicep'] : null;
        $arm_right_bicep = !empty($_POST['arm_right_bicep']) ? $_POST['arm_right_bicep'] : null;
        $arm_left_forearm = !empty($_POST['arm_left_forearm']) ? $_POST['arm_left_forearm'] : null;
        $arm_right_forearm = !empty($_POST['arm_right_forearm']) ? $_POST['arm_right_forearm'] : null;
        $leg_left_quad = !empty($_POST['leg_left_quad']) ? $_POST['leg_left_quad'] : null;
        $leg_right_quad = !empty($_POST['leg_right_quad']) ? $_POST['leg_right_quad'] : null;
        $leg_left_calf = !empty($_POST['leg_left_calf']) ? $_POST['leg_left_calf'] : null;
        $leg_right_calf = !empty($_POST['leg_right_calf']) ? $_POST['leg_right_calf'] : null;
        
        try {
            if (!empty($_POST['measurement_id'])) {
                $measurement_id = intval($_POST['measurement_id']);
                
                $check_stmt = $conn->prepare("SELECT id FROM body_measurements WHERE id = ? AND user_id = ?");
                $check_stmt->bind_param("ii", $measurement_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this measurement']);
                    exit;
                }
                
                $stmt = $conn->prepare("UPDATE body_measurements SET 
                    weight = ?, body_fat = ?, chest = ?, waist = ?, shoulders = ?, hips = ?, notes = ?, measurement_date = ?,
                    arm_left_bicep = ?, arm_right_bicep = ?, arm_left_forearm = ?, arm_right_forearm = ?,
                    leg_left_quad = ?, leg_right_quad = ?, leg_left_calf = ?, leg_right_calf = ? 
                    WHERE id = ? AND user_id = ?");

                $stmt->bind_param("ddddddssddddddddii", 
                    $weight, 
                    $body_fat, 
                    $chest, 
                    $waist, 
                    $shoulders, 
                    $hips, 
                    $notes, 
                    $date,
                    $arm_left_bicep, 
                    $arm_right_bicep, 
                    $arm_left_forearm, 
                    $arm_right_forearm,
                    $leg_left_quad, 
                    $leg_right_quad, 
                    $leg_left_calf, 
                    $leg_right_calf,
                    $measurement_id,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Measurement updated successfully!";
                } else {
                    $response['message'] = "Error updating measurement: " . $conn->error;
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO body_measurements 
                (user_id, weight, body_fat, chest, waist, shoulders, hips, notes, measurement_date,
                arm_left_bicep, arm_right_bicep, arm_left_forearm, arm_right_forearm,
                leg_left_quad, leg_right_quad, leg_left_calf, leg_right_calf) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("iddddddssdddddddd", 
                    $user_id, 
                    $weight, 
                    $body_fat, 
                    $chest, 
                    $waist, 
                    $shoulders, 
                    $hips, 
                    $notes, 
                    $date,
                    $arm_left_bicep, 
                    $arm_right_bicep, 
                    $arm_left_forearm, 
                    $arm_right_forearm,
                    $leg_left_quad, 
                    $leg_right_quad, 
                    $leg_left_calf, 
                    $leg_right_calf
                );
                
                if ($stmt->execute()) {
                    $measurement_id = $conn->insert_id;
                    $response['success'] = true;
                    $response['message'] = "Measurements saved successfully!";
                } else {
                    $response['message'] = "Error saving measurements: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
}

$records_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM body_measurements WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$all_stmt = $conn->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY created_at DESC");
$all_stmt->bind_param("i", $user_id);
$all_stmt->execute();
$all_result = $all_stmt->get_result();
$all_measurements = $all_result->fetch_all(MYSQLI_ASSOC);

if (count($all_measurements) >= 2) {
    $latest = $all_measurements[0];
    $previous = $all_measurements[1];
    
    $calc_percent = function($current, $previous) {
        if ($previous == 0 || $previous === null || $current === null) return 0;
        return (($current - $previous) / abs($previous)) * 100;
    };

    $weight_change = ($latest['weight'] ?? 0) - ($previous['weight'] ?? 0);
    $bodyfat_change = ($latest['body_fat'] ?? 0) - ($previous['body_fat'] ?? 0);
    $chest_change = ($latest['chest'] ?? 0) - ($previous['chest'] ?? 0);
    $waist_change = ($latest['waist'] ?? 0) - ($previous['waist'] ?? 0);
    $shoulders_change = ($latest['shoulders'] ?? 0) - ($previous['shoulders'] ?? 0);
    $hips_change = ($latest['hips'] ?? 0) - ($previous['hips'] ?? 0);
    $arm_left_bicep_change = ($latest['arm_left_bicep'] ?? 0) - ($previous['arm_left_bicep'] ?? 0);
    $arm_right_bicep_change = ($latest['arm_right_bicep'] ?? 0) - ($previous['arm_right_bicep'] ?? 0);
    $arm_left_forearm_change = ($latest['arm_left_forearm'] ?? 0) - ($previous['arm_left_forearm'] ?? 0);
    $arm_right_forearm_change = ($latest['arm_right_forearm'] ?? 0) - ($previous['arm_right_forearm'] ?? 0);
    $leg_left_quad_change = ($latest['leg_left_quad'] ?? 0) - ($previous['leg_left_quad'] ?? 0);
    $leg_right_quad_change = ($latest['leg_right_quad'] ?? 0) - ($previous['leg_right_quad'] ?? 0);
    $leg_left_calf_change = ($latest['leg_left_calf'] ?? 0) - ($previous['leg_left_calf'] ?? 0);
    $leg_right_calf_change = ($latest['leg_right_calf'] ?? 0) - ($previous['leg_right_calf'] ?? 0);

    $weight_change_percent = $calc_percent($latest['weight'], $previous['weight']);
    $bodyfat_change_percent = $calc_percent($latest['body_fat'], $previous['body_fat']);
    $chest_change_percent = $calc_percent($latest['chest'], $previous['chest']);
    $waist_change_percent = $calc_percent($latest['waist'], $previous['waist']);
    $shoulders_change_percent = $calc_percent($latest['shoulders'], $previous['shoulders']);
    $hips_change_percent = $calc_percent($latest['hips'], $previous['hips']);
    $arm_left_bicep_change_percent = $calc_percent($latest['arm_left_bicep'], $previous['arm_left_bicep']);
    $arm_right_bicep_change_percent = $calc_percent($latest['arm_right_bicep'], $previous['arm_right_bicep']);
    $arm_left_forearm_change_percent = $calc_percent($latest['arm_left_forearm'], $previous['arm_left_forearm']);
    $arm_right_forearm_change_percent = $calc_percent($latest['arm_right_forearm'], $previous['arm_right_forearm']);
    $leg_left_quad_change_percent = $calc_percent($latest['leg_left_quad'], $previous['leg_left_quad']);
    $leg_right_quad_change_percent = $calc_percent($latest['leg_right_quad'], $previous['leg_right_quad']);
    $leg_left_calf_change_percent = $calc_percent($latest['leg_left_calf'], $previous['leg_left_calf']);
    $leg_right_calf_change_percent = $calc_percent($latest['leg_right_calf'], $previous['leg_right_calf']);
}

$chart_stmt = $conn->prepare("
    SELECT measurement_date, weight, body_fat, chest, waist, shoulders,
           arm_left_bicep, arm_right_bicep, arm_left_forearm, arm_right_forearm,
           leg_left_quad, leg_right_quad, leg_left_calf, leg_right_calf, hips
    FROM body_measurements 
    WHERE user_id = ? 
    ORDER BY created_at ASC 
    LIMIT 30
");
$chart_stmt->bind_param("i", $user_id);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();
$chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$measurements = $result->fetch_all(MYSQLI_ASSOC);

function getMeasurementPhotos($measurement_id, $conn) {
    return ['front' => null, 'side' => null];
}

$dates = [];
$weight_data = [];
$bodyfat_data = [];
$chest_data = [];
$waist_data = [];
$shoulders_data = [];
$arm_left_data = [];
$arm_right_data = [];

foreach ($chart_data as $row) {
    $dates[] = date('M d', strtotime($row['measurement_date']));
    $weight_data[] = $row['weight'];
    $bodyfat_data[] = $row['body_fat'];
    $chest_data[] = $row['chest'];
    $waist_data[] = $row['waist'];
    $shoulders_data[] = $row['shoulders'];
    $arm_left_data[] = ($row['arm_left_bicep'] + $row['arm_left_forearm']) / 2;
    $arm_right_data[] = ($row['arm_right_bicep'] + $row['arm_right_forearm']) / 2;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <title>Body Measurements | Fitness Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            
        }

        .container {
            display: flex;
            min-height: 100vh;
            background-color: var(--card-bg);
            width: 100%;
            color: var(--text-light);
        }

        .card-grid {
            flex: 1;
            padding: 1.5rem;
            background-color: var(--bg-color);
            min-height: 100vh;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            width: 100%;
            height: 650px; 
        }
        
        .left-content {
            flex: 1;
            min-width: 300px;
            height: 670px;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            margin: 1rem;
            position: relative;
            backdrop-filter: blur(10px);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.05));
        }
        
        .right-content {
            flex: 1;
            min-width: 300px;
            height: 670px;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            margin: 1rem;
            position: relative;
            backdrop-filter: blur(10px);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.05));
        }
        
        .left-content:hover, .right-content:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .left-content::before, .right-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-red-dark));
            border-radius: 12px 12px 0 0;
            opacity: 0.8;
        }
        
        .content-layout {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            height: auto;
            min-height: 100%;
            border-radius: 16px;
        }
        
        .two-column-layout {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .column {
            flex: 1;
            min-width: 280px;
        }
        
        .form-group-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--accent-color);
            margin: 1rem 0 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            display: flex;
            flex-direction: row;
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
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            color: var(--text-light);
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
                padding: 0.5rem;
            }
            
            .content-layout {
                flex-direction: column;
            }
            
            .left-content, .right-content {
                min-width: auto;
                margin: 0.5rem 0;
                padding: 1rem;
            }
            
            .card {
                padding: 1rem;
                height: auto;
                min-height: calc(100vh - 2rem);
            }
            
            .card h2 {
                font-size: 1.3rem;
            }
            
            .container-combine {
                flex-direction: column;
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
                gap: 0.5rem;
            }
            
            .photo-comparison {
                flex-direction: column;
            }
            
            .photo-container {
                margin-bottom: 1rem;
            }
            
            .heatmap-cell {
                padding: 0.5rem;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex-grow: 1;
                text-align: center;
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            
            .modal-content {
                margin: 10px;
                padding: 15px;
            }
            
            .fab {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
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
        
        @media (max-width: 768px) {
            input, select, textarea, button {
                font-size: 16px;
                padding: 0.8rem;
            }
            
            .btn {
                padding: 0.8rem 1rem;
                width: 100%;
            }
            
            label {
                font-size: 1rem;
            }
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
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .tab {
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tab:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .tab.active {
            background: var(--primary-red);
            color: white;
            border-color: transparent;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .chart-container {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .table-responsive {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 0.5rem;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 0.5rem;
        }
        
        th {
            background: rgba(0, 0, 0, 0.3);
            color: var(--accent-color);
            font-weight: 600;
            padding: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.05);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
            
            table {
                width: 100%;
                display: block;
                max-width: 100%;
            }
            
            thead, tbody, th, td, tr { 
                display: block; 
            }
            
            thead tr { 
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr {
                margin-bottom: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 4px;
            }
            
            td { 
                border: none;
                position: relative;
                padding-left: 50%; 
                text-align: right;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            td:before { 
                position: absolute;
                top: 0.75rem;
                left: 0.5rem;
                width: 45%; 
                padding-right: 10px; 
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--accent-color);
            }
            
            tr td:nth-of-type(1):before { content: "Date"; }
            tr td:nth-of-type(2):before { content: "Weight"; }
            tr td:nth-of-type(3):before { content: "Body Fat"; }
            tr td:nth-of-type(4):before { content: "Actions"; }
            
            td:last-child {
                border-bottom: none;
            }
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .progress-indicator {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .progress-indicator:hover {
            background: rgba(0, 0, 0, 0.25);
            transform: translateX(2px);
        }
        
        .left-content h3, .right-content h3 {
            color: var(--accent-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }
        
        .left-content h3::after, .right-content h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--primary-red);
            border-radius: 1px;
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
        
        @media (max-width: 600px) {
            .heatmap {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 0.3rem;
            }
            
            .heatmap-value {
                font-size: 1rem;
            }
            
            .heatmap-label {
                font-size: 0.7rem;
            }
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @media (max-width: 840px) {
            .modal-content {
                max-width: 90%;
                margin: 30px auto;
                max-height: 80vh;
                overflow-y: auto;
            }
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: var(--text-gray);
            cursor: pointer;
        }
        
        .modal-header {
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-footer {
            border-top: 1px solid #333;
            padding-top: 15px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        @media (max-width: 600px) {
            .modal-footer {
                flex-direction: column;
                gap: 10px;
            }
            
            .modal-nav {
                justify-content: space-between;
                width: 100%;
            }
            
            #saveButton {
                width: 100%;
            }
        }
        
        .modal-nav {
            display: flex;
            gap: 10px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #333;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            font-weight: bold;
        }
        
        .step.active {
            background-color: var(--primary-red);
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            color: white;
            display: flex !important; 
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            z-index: 900;
            transition: transform 0.3s, background-color 0.3s;
        }
        
        .fab:hover {
            transform: scale(1.1);
            background-color: var(--secondary);
        }

        .no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-gray);
            font-style: italic;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            min-width: 300px;
            background-color: var(--dark-surface);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1100;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }
        
        @media (max-width: 600px) {
            .toast {
                bottom: 70px;
                right: 20px;
                left: 20px;
                min-width: auto;
                width: auto;
            }
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast-header {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .toast-header i {
            margin-right: 10px;
        }
        
        .toast-header strong {
            flex: 1;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 20px;
            cursor: pointer;
        }
        
        .toast-body {
            padding: 15px;
        }
        
        .toast-success {
            border-left: 4px solid var(--success-green);
        }
        
        .toast-success .toast-header i {
            color: var(--success-green);
        }
        
        .toast-error {
            border-left: 4px solid var(--error-red);
        }
        
        .toast-error .toast-header i {
            color: var(--error-red);
        }
        
        .modal {
            transition: background-color 0.3s;
        }
        
        .modal-content {
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            color: var(--text-light);
        }
        
        .modal.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        
        .step {
            transition: background-color 0.3s;
        }
        
        .step-content {
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .step-content.active {
            opacity: 1;
            transform: translateX(0);
        }
        .container-combine{
            display: flex;
            flex-direction: row;

            overflow-y: hidden;
        }
        
        @media (max-width: 1024px) {
            .container-combine {
                flex-direction: column;
                overflow-y: visible;
            }
            
            .content-layout {
                flex-direction: column;
            }

            .card{
                height: 100%;
            }
        }

        @media (max-width: 480px) {
            .card h2 {
                font-size: 1.2rem;
            }
            
            .card h3 {
                font-size: 1rem;
            }
            
            .form-group-title {
                font-size: 0.9rem;
            }
            
            .heatmap {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .create-measurement-btn {
            background-color: var(--primary-red);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .create-measurement-btn:hover {
            background-color: var(--primary-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .create-measurement-btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .create-measurement-btn {
                width: 100%;
                justify-content: center;
                padding: 0.8rem;
            }
        }
        
        .details-group {
            /* background: rgba(0, 0, 0, 0.2); */
            border-radius: 8px;
            /* padding: 1rem;
            margin-bottom: 1.5rem; */
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .details-group p {
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .details-group p:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .details-group p strong {
            color: var(--accent-color);
        }
        
        .notes-section, .photo-section {
            margin-top: 2rem;
        }
        
        .notes-content {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1rem;
            min-height: 80px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            white-space: pre-line;
        }
        
        .detail-photo {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .detail-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .photo-label {
            text-align: center;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 0 0 4px 4px;
            font-size: 0.9rem;
        }
        
        td .btn {
            padding: 0.5rem;
            margin-right: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        td .btn-secondary {
            background-color: #2c3e50;
        }
        
        td .btn-secondary:hover {
            background-color: #34495e;
            transform: translateY(-2px);
        }
        
        td .btn-primary {
            background-color: var(--primary-red);
        }
        
        td .btn-primary:hover {
            background-color: var(--primary-red-dark);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            td .btn {
                padding: 0.4rem;
                margin-bottom: 0.25rem;
                display: inline-block;
            }
        }
        
        .btn-danger {
            background-color: var(--error-red);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .pagination-link {
            display: inline-block;
            padding: 0.5rem 0.75rem;
            background-color: var(--dark-surface-lighter);
            color: var(--text-light);
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .pagination-link:hover {
            background-color: var(--primary-red-dark);
            transform: translateY(-2px);
        }
        
        .pagination-link.active {
            background-color: var(--primary-red);
            color: white;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .pagination {
                gap: 0.3rem;
            }
            
            .pagination-link {
                padding: 0.4rem 0.6rem;
                font-size: 0.9rem;
            }
        }
        
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1001;
        }
        
        .confirm-content {
            background-color: var(--card-bg);
            max-width: 400px;
            margin: 100px auto;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .confirm-modal.active .confirm-content {
            transform: scale(1);
            opacity: 1;
        }
        
        .confirm-modal h3 {
            color: var(--primary-red);
            margin-bottom: 1rem;
        }
        
        .confirm-modal p {
            margin-bottom: 1.5rem;
        }
        
        .confirm-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .confirm-content {
                max-width: 90%;
                margin: 50px auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-combine">
    <?php require_once 'sidebar.php'; ?>
    <div class="container">
            <div class="card"> 
                <h2><i class="fas fa-chart-line"></i> Body Measurements</h2>
                
                <div class="content-layout">
                    <div class="left-content">
                        <h3><i class="fas fa-history"></i> Measurement History</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Weight</th>
                                        <th>Body Fat</th>
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
                                                <td>
                                                    <button class="btn btn-secondary view-details" data-id="<?= $measurement['id'] ?>" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-danger delete-measurement" data-id="<?= $measurement['id'] ?>" title="Delete Measurement">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No measurements recorded yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1" class="pagination-link">&laquo; First</a>
                                <a href="?page=<?= $page - 1 ?>" class="pagination-link">&lsaquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?= $i ?>" class="pagination-link <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="pagination-link">Next &rsaquo;</a>
                                <a href="?page=<?= $total_pages ?>" class="pagination-link">Last &raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="right-content">
                        <h3>Measurement Change Heatmap</h3>
                        <p>Comparing your latest measurements with previous</p>
                        
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
                                $sign = '';
                                
                                if ($label === 'Weight') {
                                    $color = 'rgba(100, 100, 100, ' . $intensity . ')';
                                    $sign = $value >= 0 ? '+' : '-';
                                }
                                else if ($label === 'Body Fat' || $label === 'Waist' || $label === 'Hips') {
                                    if ($value > 0) {
                                        $color = 'rgba(244, 67, 54, ' . $intensity . ')';
                                        $sign = '+';
                                    } else {
                                        $color = 'rgba(76, 175, 80, ' . $intensity . ')';
                                        $sign = '-';
                                    }
                                }
                                else {
                                    if ($value > 0) {
                                        $color = 'rgba(76, 175, 80, ' . $intensity . ')';
                                        $sign = '+';
                                    } else {
                                        $color = 'rgba(244, 67, 54, ' . $intensity . ')';
                                        $sign = '-';
                                    }
                                }
                                
                                $displayValue = $sign . number_format(abs($value), 1);
                            ?>
                            <div class="heatmap-cell" style="background-color: <?= $color ?>">
                                <div class="heatmap-value"><?= $displayValue ?></div>
                                <div class="heatmap-label"><?= $label ?></div>
                                <div class="heatmap-percent"><?= $sign . number_format(abs($percent), 1) ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>
    <div class="fab" id="addMeasurementBtn">
        <i class="fas fa-plus"></i>
    </div>

    <div class="modal" id="measurementModal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-tape"></i> Record Measurements</h2>
            </div>
            
            <form id="measurementForm">
                <div class="two-column-layout">
                    <div class="column">
                        <h3>Basic Measurements</h3>
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
                                <label for="waist">Waist</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" id="waist" name="waist" placeholder="Waist circumference">
                                    <div class="input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="form-control">
                                <label for="hips">Hips</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" id="hips" name="hips" placeholder="Hip circumference">
                                    <div class="input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column">
                        <h3>Detailed Measurements</h3>
                        <div class="form-group-title">Arms</div>
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
                        
                        <div class="form-group-title">Legs</div>
                        <div class="form-row">
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
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" id="saveButton" class="btn">Save Measurements</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <span class="modal-close" id="detailsModalClose">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Measurement Details</h2>
            </div>
            
            <div class="two-column-layout">
                <div class="column">
                    <h3>Basic Information</h3>
                    <div class="details-group">
                        <p><strong>Date:</strong> <span id="detail-date"></span></p>
                        <p><strong>Weight:</strong> <span id="detail-weight"></span> kg</p>
                        <p><strong>Body Fat:</strong> <span id="detail-bodyfat"></span> %</p>
                        <p><strong>Chest:</strong> <span id="detail-chest"></span> cm</p>
                        <p><strong>Waist:</strong> <span id="detail-waist"></span> cm</p>
                        <p><strong>Shoulders:</strong> <span id="detail-shoulders"></span> cm</p>
                        <p><strong>Hips:</strong> <span id="detail-hips"></span> cm</p>
                    </div>
                </div>
                <div class="column">
                    <h3>Detailed Measurements</h3>
                    <div class="form-group-title">Arms</div>
                    <div class="details-group">
                        <p><strong>Left Bicep:</strong> <span id="detail-left-bicep"></span> cm</p>
                        <p><strong>Right Bicep:</strong> <span id="detail-right-bicep"></span> cm</p>
                        <p><strong>Left Forearm:</strong> <span id="detail-left-forearm"></span> cm</p>
                        <p><strong>Right Forearm:</strong> <span id="detail-right-forearm"></span> cm</p>
                    </div>
                    
                    <div class="form-group-title">Legs</div>
                    <div class="details-group">
                        <p><strong>Left Quad:</strong> <span id="detail-left-quad"></span> cm</p>
                        <p><strong>Right Quad:</strong> <span id="detail-right-quad"></span> cm</p>
                        <p><strong>Left Calf:</strong> <span id="detail-left-calf"></span> cm</p>
                        <p><strong>Right Calf:</strong> <span id="detail-right-calf"></span> cm</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="confirm-modal" id="deleteConfirmModal">
        <div class="confirm-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p>Are you sure you want to delete this measurement? This action cannot be undone.</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" id="cancelDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('measurement_date').value = today;
            
            setupModal();
            setupActionButtons();
            setupDeleteConfirmation();

            document.getElementById('addMeasurementBtn').addEventListener('click', function() {
                openModal();
            });
        });
        
        function setupActionButtons() {
            const viewButtons = document.querySelectorAll('.view-details');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const measurementId = this.dataset.id;
                    fetchMeasurementDetails(measurementId);
                });
            });
            
            const deleteButtons = document.querySelectorAll('.delete-measurement');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const measurementId = this.dataset.id;
                    showDeleteConfirmation(measurementId);
                });
            });
            
            document.getElementById('detailsModalClose').addEventListener('click', function() {
                closeDetailsModal();
            });
            
            window.addEventListener('click', function(event) {
                const detailsModal = document.getElementById('detailsModal');
                if (event.target === detailsModal) {
                    closeDetailsModal();
                }
            });
        }
        
        function setupDeleteConfirmation() {
            const confirmModal = document.getElementById('deleteConfirmModal');
            const cancelButton = document.getElementById('cancelDelete');
            const confirmButton = document.getElementById('confirmDelete');
            
            cancelButton.addEventListener('click', function() {
                closeDeleteConfirmation();
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === confirmModal) {
                    closeDeleteConfirmation();
                }
            });
            
            confirmButton.addEventListener('click', function() {
                const measurementId = confirmButton.dataset.id;
                deleteMeasurement(measurementId);
            });
        }
        
        function showDeleteConfirmation(measurementId) {
            const confirmModal = document.getElementById('deleteConfirmModal');
            const confirmButton = document.getElementById('confirmDelete');
            
            confirmButton.dataset.id = measurementId;
       
            confirmModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                confirmModal.classList.add('active');
            }, 10);
        }
        
        function closeDeleteConfirmation() {
            const confirmModal = document.getElementById('deleteConfirmModal');
            confirmModal.classList.remove('active');
            
            setTimeout(() => {
                confirmModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        function deleteMeasurement(measurementId) {
            const formData = new FormData();
            formData.append('delete', 'true');
            formData.append('measurement_id', measurementId);
            
            fetch('body-measurements.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeDeleteConfirmation();
                
                if (data.success) {
                    showToast('Success', data.message, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Error', data.message, 'error');
                }
            })
            .catch(error => {
                closeDeleteConfirmation();
                console.error('Error:', error);
                showToast('Error', 'An error occurred while deleting the measurement', 'error');
            });
        }
        
        function fetchMeasurementDetails(measurementId) {
            fetch('get_measurement.php?id=' + measurementId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayMeasurementDetails(data.measurement);
                    } else {
                        showToast('Error', data.message || 'Failed to load measurement details', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching measurement details:', error);
                    showToast('Error', 'An error occurred while loading measurement details', 'error');
                });
        }
        
        function displayMeasurementDetails(measurement) {
            document.getElementById('detail-date').textContent = measurement.measurement_date || 'N/A';
            document.getElementById('detail-weight').textContent = measurement.weight || 'N/A';
            document.getElementById('detail-bodyfat').textContent = measurement.body_fat || 'N/A';
            document.getElementById('detail-chest').textContent = measurement.chest || 'N/A';
            document.getElementById('detail-waist').textContent = measurement.waist || 'N/A';
            document.getElementById('detail-shoulders').textContent = measurement.shoulders || 'N/A';
            document.getElementById('detail-hips').textContent = measurement.hips || 'N/A';

            document.getElementById('detail-left-bicep').textContent = measurement.arm_left_bicep || 'N/A';
            document.getElementById('detail-right-bicep').textContent = measurement.arm_right_bicep || 'N/A';
            document.getElementById('detail-left-forearm').textContent = measurement.arm_left_forearm || 'N/A';
            document.getElementById('detail-right-forearm').textContent = measurement.arm_right_forearm || 'N/A';
            document.getElementById('detail-left-quad').textContent = measurement.leg_left_quad || 'N/A';
            document.getElementById('detail-right-quad').textContent = measurement.leg_right_quad || 'N/A';
            document.getElementById('detail-left-calf').textContent = measurement.leg_left_calf || 'N/A';
            document.getElementById('detail-right-calf').textContent = measurement.leg_right_calf || 'N/A';
            
            openDetailsModal();
        }
        
        function openDetailsModal() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                detailsModal.classList.add('active');
            }, 10);
        }
        
        function closeDetailsModal() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.classList.remove('active');
            setTimeout(() => {
                detailsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        function setupModal() {
            const modal = document.getElementById('measurementModal');
            const closeBtn = document.querySelector('.modal-close');
            
            closeBtn.addEventListener('click', function() {
                closeModal();
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        function openModal() {
            const modal = document.getElementById('measurementModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            document.getElementById('measurementForm').reset();
            document.getElementById('measurement_date').value = new Date().toISOString().split('T')[0];
        }

        function closeModal() {
            const modal = document.getElementById('measurementModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
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
            
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('body-measurements.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(text);
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('measurementModal');
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    
                    showToast('Success', data.message, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save Measurements';
                    showToast('Error', data.message || 'Failed to save measurements', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveButton.disabled = false;
                saveButton.innerHTML = 'Save Measurements';
                showToast('Error', 'An error occurred while saving measurements. Please try again.', 'error');
            });
        });

        function showToast(title, message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <strong>${title}</strong>
                    <button class="toast-close">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 5000);
            
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>