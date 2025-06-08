<?php
require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$template_id = null;
$auto_start = false;

if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
    $template_id = $_POST['template_id'];
    $auto_start = isset($_POST['auto_start']) && $_POST['auto_start'] == 1;
}

if ($template_id === null && isset($_GET['template_id']) && !empty($_GET['template_id'])) {
    $template_id = $_GET['template_id'];
    $auto_start = isset($_GET['auto_start']) && $_GET['auto_start'] == 1;
}

if ($template_id !== null) {
    $_SESSION['active_template_id'] = $template_id;
    
    if ($auto_start) {
        $_SESSION['start_workout_directly'] = true;
        $_SESSION['skip_template_selection'] = true;
    }
}

try {
    if (tableExists($conn, 'workout_templates')) {
        $templates_query = "SELECT wt.*, COUNT(wte.id) as exercise_count 
                          FROM workout_templates wt
                          LEFT JOIN workout_template_exercises wte ON wt.id = wte.workout_template_id
                          WHERE wt.user_id = ?
                          GROUP BY wt.id
                          ORDER BY wt.created_at DESC";
        $stmt = mysqli_prepare($conn, $templates_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $templates = mysqli_stmt_get_result($stmt);
    } else {
        $templates = false;
    }
} catch (Exception $e) {
    error_log("Error fetching workout templates: " . $e->getMessage());
    $templates = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Start Workout - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="global-profile.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }

        .app-container {
            display: flex;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            padding-left: 0;
            width: 100%;
            overflow-x: hidden;
        }

        #desktopView {
            display: none !important;
        }
        
        #mobileView {
            display: block !important;
        }
        
        @media screen and (min-width: 1086px) {
            #desktopView {
                display: block !important;
            }
            
            #mobileView {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php require_once 'sidebar.php'; ?>
    
    <div class="main-content" style="padding:0 !important;">
        <div id="desktopView">
            <?php include 'desktop-workout.php'; ?>
        </div>

        <div id="mobileView">
            <?php include 'mobile-workout.php'; ?>
        </div>
    </div>
</div>
</body>
</html>