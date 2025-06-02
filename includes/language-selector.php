<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

if (isset($_POST['change_language'])) {
    $_SESSION['language'] = $_POST['language'];
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="language-selector">
    <form method="POST" action="" id="language-form">
        <select name="language" id="language-select" onchange="this.form.submit()">
            <option value="en" <?php echo ($_SESSION['language'] === 'en') ? 'selected' : ''; ?>><?php echo t('english'); ?></option>
            <option value="lv" <?php echo ($_SESSION['language'] === 'lv') ? 'selected' : ''; ?>><?php echo t('latvian'); ?></option>
        </select>
        <input type="hidden" name="change_language" value="1">
    </form>
</div>

<style>
    .language-selector {
        margin: 0 15px;
    }
    
    .language-selector select {
        background-color: var(--dark-bg-surface);
        color: white;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
    }
    
    .language-selector select:focus {
        outline: none;
        border-color: var(--accent-color);
    }
</style> 