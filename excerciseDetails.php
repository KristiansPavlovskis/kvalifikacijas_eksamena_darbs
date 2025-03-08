<?php
// Start session
session_start();

// Include database connection
require_once 'assets/db_connection.php';

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: excercises.php");
    exit;
}

$exercise_id = intval($_GET['id']);

// Get the exercise details
$exercise_query = "SELECT e.*, t.name AS type_name FROM exercises e 
                  LEFT JOIN exercise_types t ON e.type_id = t.id 
                  WHERE e.id = ?";
$exercise_stmt = mysqli_prepare($conn, $exercise_query);
mysqli_stmt_bind_param($exercise_stmt, 'i', $exercise_id);
mysqli_stmt_execute($exercise_stmt);
$exercise_result = mysqli_stmt_get_result($exercise_stmt);

if (mysqli_num_rows($exercise_result) === 0) {
    header("Location: excercises.php");
    exit;
}

$exercise = mysqli_fetch_assoc($exercise_result);

// Format instructions as array if they're stored as a string with newlines
$instructions = [];
if (!empty($exercise['instructions'])) {
    $instructions = explode("\n", $exercise['instructions']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?php echo htmlspecialchars($exercise['name']); ?></title>
    <link rel="stylesheet" href="lietotaja-view.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
        }
        
        body {
            background-color: var(--dark);
            color: var(--light);
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .exercise-detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            margin-bottom: 30px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        .back-button i {
            margin-right: 8px;
        }
        
        .exercise-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .exercise-image {
            height: 100%;
            min-height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .exercise-info {
            padding: 40px;
        }
        
        .exercise-title {
            font-family: 'Koulen', sans-serif;
            font-size: 3rem;
            margin: 0 0 20px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.1;
        }
        
        .exercise-description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .exercise-type {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255, 77, 77, 0.2);
            color: var(--primary);
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .exercise-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .difficulty-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .difficulty-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            flex-grow: 1;
            overflow: hidden;
            position: relative;
        }
        
        .difficulty-fill {
            height: 100%;
            background: linear-gradient(90deg, #00cc66, #ffa700, #ff4d4d);
            border-radius: 4px;
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .difficulty-text {
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .exercise-easy {
            color: #00cc66;
        }
        
        .exercise-intermediate, .exercise-moderate {
            color: #ffa700;
        }
        
        .exercise-advanced, .exercise-hard {
            color: #ff4d4d;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-family: 'Koulen', sans-serif;
            font-size: 1.8rem;
            margin: 0 0 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary);
            font-size: 1.4rem;
        }
        
        .instructions-list {
            list-style-type: none;
            counter-reset: step-counter;
            padding: 0;
        }
        
        .instruction-step {
            counter-increment: step-counter;
            position: relative;
            padding-left: 50px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .instruction-step:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .instruction-step::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 36px;
            height: 36px;
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .instruction-text {
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        .muscles-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .muscle-tag {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px 15px;
            border-radius: 50px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .muscle-tag:hover {
            background: rgba(255, 77, 77, 0.2);
            transform: translateY(-5px);
        }
        
        .related-exercises {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .related-exercise-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
        }
        
        .related-exercise-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }
        
        .related-exercise-image {
            height: 120px;
            background-size: cover;
            background-position: center;
        }
        
        .related-exercise-content {
            padding: 15px;
        }
        
        .related-exercise-title {
            font-size: 1.1rem;
            margin: 0 0 5px;
            font-weight: bold;
        }
        
        .related-exercise-type {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .exercise-video {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .primary-button {
            background-color: var(--primary);
            color: white;
        }
        
        .primary-button:hover {
            background-color: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .secondary-button {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .secondary-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }
        
        @media (max-width: 768px) {
            .exercise-header {
                grid-template-columns: 1fr;
            }
            
            .exercise-image {
                height: 250px;
            }
            
            .exercise-title {
                font-size: 2.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">GYMVERSE</a>
        <nav>
            <a href="index.php">HOME</a>
            <a href="#">ABOUT</a>
            <a href="membership.php">MEMBERSHIP</a>
            <a href="leaderboard.php">LEADERBOARD</a>
            <a href="nutrition.php">NUTRITION</a>
            <a href="#">CONTACT</a>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="profile.php" style="margin-left: 15px;">PROFILE</a>
                <a href="logout.php" style="margin-left: 10px; background-color: #ff4d4d; color: white; padding: 8px 15px; border-radius: 5px;">LOGOUT</a>
            <?php else: ?>
                <a href="login.php" style="margin-left: 15px; background-color: #333; color: white; padding: 8px 15px; border-radius: 5px;">LOGIN</a>
                <a href="register.php" style="margin-left: 10px; background-color: #ff4d4d; color: white; padding: 8px 15px; border-radius: 5px;">REGISTER</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="exercise-detail-container">
        <a href="excerciseType.php?id=<?php echo $exercise['type_id']; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to <?php echo htmlspecialchars($exercise['type_name']); ?> Exercises
        </a>

        <div class="exercise-header">
            <div class="exercise-image" style="background-image: url('<?php echo !empty($exercise['image_url']) ? $exercise['image_url'] : 'images/default-exercise.jpg'; ?>');">
            </div>
            <div class="exercise-info">
                <span class="exercise-type"><?php echo htmlspecialchars($exercise['type_name']); ?></span>
                <h1 class="exercise-title"><?php echo htmlspecialchars($exercise['name']); ?></h1>
                <p class="exercise-description"><?php echo htmlspecialchars($exercise['description']); ?></p>
                
                <div class="difficulty-indicator">
                    <span class="difficulty-text exercise-<?php echo strtolower($exercise['difficulty']); ?>">
                        <?php echo htmlspecialchars($exercise['difficulty']); ?>
                    </span>
                    <div class="difficulty-bar">
                        <?php
                        $difficultyPercentage = 33;
                        if (strtolower($exercise['difficulty']) === 'intermediate' || strtolower($exercise['difficulty']) === 'moderate') {
                            $difficultyPercentage = 66;
                        } elseif (strtolower($exercise['difficulty']) === 'advanced' || strtolower($exercise['difficulty']) === 'hard') {
                            $difficultyPercentage = 100;
                        }
                        ?>
                        <div class="difficulty-fill" style="width: <?php echo $difficultyPercentage; ?>%;"></div>
                    </div>
                </div>
                
                <div class="exercise-stats">
                    <?php if(!empty($exercise['muscles_worked'])): ?>
                    <div class="stat-card">
                        <div class="stat-title">TARGET MUSCLES</div>
                        <div class="stat-value"><?php echo htmlspecialchars($exercise['muscles_worked']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($exercise['equipment_needed'])): ?>
                    <div class="stat-card">
                        <div class="stat-title">EQUIPMENT</div>
                        <div class="stat-value"><?php echo htmlspecialchars($exercise['equipment_needed']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="workout-planer.php" class="action-button primary-button">
                        <i class="fas fa-plus"></i> Add to Workout
                    </a>
                    <button class="action-button secondary-button" onclick="saveExercise()">
                        <i class="fas fa-bookmark"></i> Save Exercise
                    </button>
                </div>
            </div>
        </div>

        <?php if(count($instructions) > 0): ?>
        <div class="content-section">
            <h2 class="section-title"><i class="fas fa-list-ol"></i> Exercise Instructions</h2>
            <div class="instructions-list">
                <?php foreach($instructions as $instruction): 
                    $instruction = trim($instruction);
                    if(!empty($instruction)):
                ?>
                <div class="instruction-step">
                    <div class="instruction-text"><?php echo htmlspecialchars($instruction); ?></div>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($exercise['muscles_worked'])): ?>
        <div class="content-section">
            <h2 class="section-title"><i class="fas fa-dumbbell"></i> Muscles Worked</h2>
            <div class="muscles-section">
                <?php 
                $muscles = explode(',', $exercise['muscles_worked']);
                foreach($muscles as $muscle): 
                    $muscle = trim($muscle);
                    if(!empty($muscle)):
                ?>
                <div class="muscle-tag"><?php echo htmlspecialchars($muscle); ?></div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($exercise['video_url'])): ?>
        <div class="content-section">
            <h2 class="section-title"><i class="fas fa-video"></i> Exercise Video</h2>
            <div class="exercise-video">
                <div class="video-wrapper">
                    <iframe src="<?php echo htmlspecialchars($exercise['video_url']); ?>" allowfullscreen></iframe>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function saveExercise() {
            alert('Exercise saved to your favorites!');
            const button = document.querySelector('.secondary-button');
            button.innerHTML = '<i class="fas fa-check"></i> Saved';
            button.style.backgroundColor = 'rgba(0, 204, 102, 0.2)';
            button.style.color = '#00cc66';
            button.disabled = true;
        }
    </script>
</body>
</html>