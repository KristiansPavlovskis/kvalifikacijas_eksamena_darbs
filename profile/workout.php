<?php
require_once 'profile_access_control.php';
require_once '../assets/db_connection.php';
require_once 'workout_functions.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/workout.php");
    exit;
}

function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

if (isMobile()) {
    $params = [];
    if (!empty($_GET)) {
        $params = $_GET;
    } elseif (!empty($_POST)) {
        $params = $_POST;
    }
    
    $redirect = 'mobile-workout.php';
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    
    header("location: $redirect");
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
    <title>Start Workout - GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4cc9f0;
            --primary-dark: #3a56d4;
            --secondary: #f72585;
            --secondary-light: #ff5c8a;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --dark: #0f0f1a;
            --dark-card: #1a1a2e;
            --gray-dark: #2b2b3d;
            --gray-light: rgba(255, 255, 255, 0.7);
        }

        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        #desktopView, #mobileView {
            display: none;
        }

        @media screen and (min-width: 784px) {
            #desktopView {
                display: block !important;
            }
            #mobileView {
                display: none !important;
            }
        }

        @media screen and (max-width: 783px) {
            #desktopView {
                display: none !important;
            }
            #mobileView {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div id="desktopView">
        <?php include 'desktop-workout.php'; ?>
    </div>

    <div id="mobileView">
        <?php include 'mobile-workout.php'; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function handleViewSwitch() {
                const desktopView = document.getElementById('desktopView');
                const mobileView = document.getElementById('mobileView');
                
                if (window.innerWidth >= 784) {
                    desktopView.style.display = 'block';
                    mobileView.style.display = 'none';
                } else {
                    desktopView.style.display = 'none';
                    mobileView.style.display = 'block';
                }
            }

            handleViewSwitch();
            window.addEventListener('resize', handleViewSwitch);
        });
    </script>
</body>
</html>