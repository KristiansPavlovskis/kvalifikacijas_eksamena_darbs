<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';

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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'No exercise specified for editing.'
    ];
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
        $error = "Exercise name is required.";
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
                'text' => 'Exercise updated successfully.'
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = "Error updating exercise: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM exercises WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exercise_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Exercise not found.'
    ];
    header("Location: index.php");
    exit;
}

$exercise = $result->fetch_assoc();

$pageTitle = "Edit Exercise";
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="en">
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
                <h1>Edit Exercise</h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <div class="form-container">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="edit-exercise.php?id=<?php echo $exercise_id; ?>" method="POST">
                        <div class="form-group">
                            <label for="name" class="form-label">Exercise Name</label>
                            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($exercise['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="exercise_type" class="form-label">Exercise Type</label>
                            <select id="exercise_type" name="exercise_type" class="form-select">
                                <option value="">Select Exercise Type</option>
                                <option value="strength" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'strength') ? 'selected' : ''; ?>>Strength</option>
                                <option value="cardio" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'cardio') ? 'selected' : ''; ?>>Cardio</option>
                                <option value="flexibility" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'flexibility') ? 'selected' : ''; ?>>Flexibility</option>
                                <option value="balance" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'balance') ? 'selected' : ''; ?>>Balance</option>
                                <option value="plyometric" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'plyometric') ? 'selected' : ''; ?>>Plyometric</option>
                                <option value="functional" <?php echo (isset($exercise['exercise_type']) && $exercise['exercise_type'] == 'functional') ? 'selected' : ''; ?>>Functional</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="difficulty" class="form-label">Difficulty Level</label>
                            <select id="difficulty" name="difficulty" class="form-select">
                                <option value="">Select Difficulty</option>
                                <option value="beginner" <?php echo (isset($exercise['difficulty']) && $exercise['difficulty'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo (isset($exercise['difficulty']) && $exercise['difficulty'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo (isset($exercise['difficulty']) && $exercise['difficulty'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                <option value="expert" <?php echo (isset($exercise['difficulty']) && $exercise['difficulty'] == 'expert') ? 'selected' : ''; ?>>Expert</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label optional-field">Description</label>
                            <textarea id="description" name="description" class="form-textarea"><?php echo isset($exercise['description']) ? htmlspecialchars($exercise['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="equipment" class="form-label optional-field">Equipment</label>
                            <input type="text" id="equipment" name="equipment" class="form-input" value="<?php echo isset($exercise['equipment']) ? htmlspecialchars($exercise['equipment']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="primary_muscle" class="form-label optional-field">Primary Muscle Group</label>
                            <input type="text" id="primary_muscle" name="primary_muscle" class="form-input" value="<?php echo isset($exercise['primary_muscle']) ? htmlspecialchars($exercise['primary_muscle']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="instructions" class="form-label optional-field">Instructions</label>
                            <textarea id="instructions" name="instructions" class="form-textarea"><?php echo isset($exercise['instructions']) ? htmlspecialchars($exercise['instructions']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="common_mistakes" class="form-label optional-field">Common Mistakes</label>
                            <textarea id="common_mistakes" name="common_mistakes" class="form-textarea"><?php echo isset($exercise['common_mistakes']) ? htmlspecialchars($exercise['common_mistakes']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="benefits" class="form-label optional-field">Benefits</label>
                            <textarea id="benefits" name="benefits" class="form-textarea"><?php echo isset($exercise['benefits']) ? htmlspecialchars($exercise['benefits']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="video_url" class="form-label optional-field">Video URL</label>
                            <input type="url" id="video_url" name="video_url" class="form-input" placeholder="https://..." value="<?php echo isset($exercise['video_url']) ? htmlspecialchars($exercise['video_url']) : ''; ?>">
                        </div>
                        
                        <div class="button-container">
                            <button type="submit" class="save-btn">
                                <i class="fas fa-save"></i> Save Exercise
                            </button>
                            <a href="index.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 