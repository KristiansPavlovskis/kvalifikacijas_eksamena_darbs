<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$exercise_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $exercise_type = isset($_POST['exercise_type']) ? trim($_POST['exercise_type']) : null;
    $equipment = isset($_POST['equipment']) ? trim($_POST['equipment']) : null;
    $primary_muscle = isset($_POST['primary_muscle']) ? trim($_POST['primary_muscle']) : null;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : null;
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : null;
    $common_mistakes = isset($_POST['common_mistakes']) ? trim($_POST['common_mistakes']) : null;
    $benefits = isset($_POST['benefits']) ? trim($_POST['benefits']) : null;
    $video_url = isset($_POST['video_url']) ? trim($_POST['video_url']) : null;
    
    if (empty($name)) {
        $error = t('exercise_name_required');
    } else {
        $update_sql = "UPDATE exercises SET 
            name = ?, 
            description = ?, 
            exercise_type = ?, 
            equipment = ?, 
            primary_muscle = ?,
            difficulty = ?,
            instructions = ?,
            common_mistakes = ?,
            benefits = ?,
            video_url = ?
            WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssssssi", 
            $name, 
            $description, 
            $exercise_type, 
            $equipment, 
            $primary_muscle,
            $difficulty,
            $instructions,
            $common_mistakes,
            $benefits,
            $video_url,
            $exercise_id
        );
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => t('exercise_updated_successfully')
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = t('error_updating_exercise') . ": " . $conn->error;
        }
    }
} else {
    $sql = "SELECT * FROM exercises WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exercise_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: index.php");
        exit;
    }
    
    $exercise = $result->fetch_assoc();
    
    $name = $exercise['name'];
    $description = $exercise['description'];
    $exercise_type = $exercise['exercise_type'];
    $equipment = $exercise['equipment'];
    $primary_muscle = $exercise['primary_muscle'];
    $difficulty = $exercise['difficulty'];
    $instructions = $exercise['instructions'];
    $common_mistakes = $exercise['common_mistakes'];
    $benefits = $exercise['benefits'];
    $video_url = $exercise['video_url'];
}

$pageTitle = t('edit_exercise');
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | GYMVERSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="/admin/includes/admin-styles.css">
    <style>
        .form-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--input-bg);
            color: var(--text-color);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .optional-field::after {
            content: " (optional)";
            font-size: 14px;
            color: #6c757d;
            font-weight: normal;
        }
        
        .error-message {
            color: #e74c3c;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .save-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .cancel-btn {
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1><?php echo t('edit_exercise'); ?>: <?php echo htmlspecialchars($name); ?></h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span><?php echo t('admin'); ?></span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="form-container">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="edit-exercise.php?id=<?php echo $exercise_id; ?>" method="POST" id="exerciseForm" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="name" class="form-label"><?php echo t('exercise_name'); ?></label>
                            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>">
                            <div class="error-message" id="nameError"><?php echo t('exercise_name_required'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label optional-field"><?php echo t('description'); ?></label>
                            <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="exercise_type" class="form-label optional-field"><?php echo t('exercise_type'); ?></label>
                            <select id="exercise_type" name="exercise_type" class="form-select">
                                <option value=""><?php echo t('select_type'); ?></option>
                                <option value="strength" <?php echo ($exercise_type == 'strength') ? 'selected' : ''; ?>><?php echo t('strength'); ?></option>
                                <option value="cardio" <?php echo ($exercise_type == 'cardio') ? 'selected' : ''; ?>><?php echo t('cardio'); ?></option>
                                <option value="flexibility" <?php echo ($exercise_type == 'flexibility') ? 'selected' : ''; ?>><?php echo t('flexibility'); ?></option>
                                <option value="balance" <?php echo ($exercise_type == 'balance') ? 'selected' : ''; ?>><?php echo t('balance'); ?></option>
                                <option value="plyometric" <?php echo ($exercise_type == 'plyometric') ? 'selected' : ''; ?>><?php echo t('plyometric'); ?></option>
                                <option value="functional" <?php echo ($exercise_type == 'functional') ? 'selected' : ''; ?>><?php echo t('functional'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="equipment" class="form-label optional-field"><?php echo t('equipment'); ?></label>
                            <input type="text" id="equipment" name="equipment" class="form-input" value="<?php echo htmlspecialchars($equipment ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="primary_muscle" class="form-label optional-field"><?php echo t('primary_muscle'); ?></label>
                            <select id="primary_muscle" name="primary_muscle" class="form-select">
                                <option value=""><?php echo t('select_muscle_group'); ?></option>
                                <option value="chest" <?php echo ($primary_muscle == 'chest') ? 'selected' : ''; ?>><?php echo t('chest'); ?></option>
                                <option value="back" <?php echo ($primary_muscle == 'back') ? 'selected' : ''; ?>><?php echo t('back'); ?></option>
                                <option value="shoulders" <?php echo ($primary_muscle == 'shoulders') ? 'selected' : ''; ?>><?php echo t('shoulders'); ?></option>
                                <option value="arms" <?php echo ($primary_muscle == 'arms') ? 'selected' : ''; ?>><?php echo t('arms'); ?></option>
                                <option value="legs" <?php echo ($primary_muscle == 'legs') ? 'selected' : ''; ?>><?php echo t('legs'); ?></option>
                                <option value="core" <?php echo ($primary_muscle == 'core') ? 'selected' : ''; ?>><?php echo t('core'); ?></option>
                                <option value="full_body" <?php echo ($primary_muscle == 'full_body') ? 'selected' : ''; ?>><?php echo t('full_body'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="difficulty" class="form-label optional-field"><?php echo t('difficulty'); ?></label>
                            <select id="difficulty" name="difficulty" class="form-select">
                                <option value=""><?php echo t('select_difficulty'); ?></option>
                                <option value="beginner" <?php echo ($difficulty == 'beginner') ? 'selected' : ''; ?>><?php echo t('beginner'); ?></option>
                                <option value="intermediate" <?php echo ($difficulty == 'intermediate') ? 'selected' : ''; ?>><?php echo t('intermediate'); ?></option>
                                <option value="advanced" <?php echo ($difficulty == 'advanced') ? 'selected' : ''; ?>><?php echo t('advanced'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="instructions" class="form-label optional-field"><?php echo t('instructions'); ?></label>
                            <textarea id="instructions" name="instructions" class="form-textarea"><?php echo htmlspecialchars($instructions ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="common_mistakes" class="form-label optional-field"><?php echo t('common_mistakes'); ?></label>
                            <textarea id="common_mistakes" name="common_mistakes" class="form-textarea"><?php echo htmlspecialchars($common_mistakes ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="benefits" class="form-label optional-field"><?php echo t('benefits'); ?></label>
                            <textarea id="benefits" name="benefits" class="form-textarea"><?php echo htmlspecialchars($benefits ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="video_url" class="form-label optional-field"><?php echo t('video_url'); ?></label>
                            <input type="text" id="video_url" name="video_url" class="form-input" value="<?php echo htmlspecialchars($video_url ?? ''); ?>">
                        </div>
                        
                        <div class="button-container">
                            <button type="submit" class="save-btn"><?php echo t('save_changes'); ?></button>
                            <a href="index.php" class="cancel-btn"><?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            let isValid = true;
            const nameInput = document.getElementById('name');
            const nameError = document.getElementById('nameError');
            
            nameInput.classList.remove('error');
            nameError.style.display = 'none';
            
            if (nameInput.value.trim() === '') {
                nameInput.classList.add('error');
                nameError.style.display = 'block';
                isValid = false;
            }
            
            return isValid;
        }
    </script>
</body>
</html> 