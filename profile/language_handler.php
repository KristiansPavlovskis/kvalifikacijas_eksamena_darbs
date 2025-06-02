<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['language'])) {
    $language = $_POST['language'];
    
    if (in_array($language, ['en', 'lv'])) {
        $_SESSION['language'] = $language;
    }
    
    $redirect = $_POST['redirect'] ?? 'profile.php';

    if (!preg_match('/^[a-zA-Z0-9\/_\-\.]+$/', $redirect)) {
        $redirect = 'profile.php';
    }
    
    header("Location: $redirect");
    exit();
}

header("Location: profile.php");
exit();
?> 