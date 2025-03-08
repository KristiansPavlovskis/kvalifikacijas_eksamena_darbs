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

$type_id = intval($_GET['id']);

// Get the exercise type details
$type_query = "SELECT * FROM exercise_types WHERE id = ?";
$type_stmt = mysqli_prepare($conn, $type_query);
mysqli_stmt_bind_param($type_stmt, 'i', $type_id);
mysqli_stmt_execute($type_stmt);
$type_result = mysqli_stmt_get_result($type_stmt);

if (mysqli_num_rows($type_result) === 0) {
    header("Location: excercises.php");
    exit;
}

$type = mysqli_fetch_assoc($type_result);

// Get all exercises for this type
$exercises_query = "SELECT * FROM exercises WHERE type_id = ? ORDER BY name";
$exercises_stmt = mysqli_prepare($conn, $exercises_query);
mysqli_stmt_bind_param($exercises_stmt, 'i', $type_id);
mysqli_stmt_execute($exercises_stmt);
$exercises_result = mysqli_stmt_get_result($exercises_stmt);

$exercises = [];
if (mysqli_num_rows($exercises_result) > 0) {
    while ($row = mysqli_fetch_assoc($exercises_result)) {
        $exercises[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?php echo htmlspecialchars($type['name']); ?> Exercises</title>
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
        
        .exercise-header {
            background: linear-gradient(135deg, #ff4d4d 0%, #ff0000 100%);
            padding: 60px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .exercise-header h1 {
            font-family: 'Koulen', sans-serif;
            font-size: 3.5rem;
            margin: 0;
            letter-spacing: 2px;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
        }
        
        .exercise-header p {
            max-width: 600px;
            margin: 15px auto 0;
            position: relative;
            z-index: 2;
            font-size: 1.1rem;
        }
        
        .exercise-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('images/exercise-pattern.png');
            opacity: 0.05;
            z-index: 1;
        }
        
        .exercise-container {
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
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-label {
            font-weight: bold;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-input {
            padding: 10px 15px;
            border-radius: 50px;
            border: 2px solid rgba(255, 77, 77, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 14px;
            transition: all 0.3s;
            width: 250px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(255, 77, 77, 0.3);
        }
        
        .difficulty-filter {
            display: flex;
            gap: 5px;
        }
        
        .difficulty-btn {
            padding: 8px 15px;
            border-radius: 50px;
            border: none;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .difficulty-btn:hover,
        .difficulty-btn.active {
            background: rgba(255, 77, 77, 0.7);
        }
        
        .exercise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .exercise-card {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 77, 77, 0.2);
        }
        
        .exercise-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .exercise-image::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        }
        
        .exercise-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .exercise-card h2 {
            margin: 0 0 10px;
            font-family: 'Koulen', sans-serif;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }
        
        .exercise-card p {
            margin: 0;
            opacity: 0.8;
            line-height: 1.5;
        }
        
        .exercise-footer {
            padding: 15px 20px;
            background: rgba(255, 77, 77, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .muscles-worked {
            font-size: 12px;
            padding: 10px 0;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .difficulty-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .difficulty-easy {
            background-color: rgba(0, 204, 102, 0.2);
            color: #00cc66;
        }
        
        .difficulty-intermediate, .difficulty-moderate {
            background-color: rgba(255, 167, 0, 0.2);
            color: #ffa700;
        }
        
        .difficulty-advanced, .difficulty-hard {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
        }
        
        .no-exercises {
            text-align: center;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .no-exercises h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .no-exercises p {
            margin-bottom: 25px;
            opacity: 0.8;
        }
        
        .action-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .action-button:hover {
            background-color: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        @media (max-width: 768px) {
            .exercise-grid {
                grid-template-columns: 1fr;
            }
            
            .exercise-header h1 {
                font-size: 2.5rem;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-input {
                width: 100%;
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

    <section class="exercise-header">
        <h1><?php echo htmlspecialchars($type['name']); ?></h1>
        <p><?php echo htmlspecialchars($type['description']); ?></p>
    </section>

    <div class="exercise-container">
        <a href="excercises.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Exercise Types
        </a>

        <div class="filter-section">
            <div class="filter-group">
                <span class="filter-label">Search:</span>
                <input type="text" class="search-input" id="exercise-search" placeholder="Search exercises...">
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Difficulty:</span>
                <div class="difficulty-filter">
                    <button class="difficulty-btn active" data-difficulty="all">All</button>
                    <button class="difficulty-btn" data-difficulty="easy">Easy</button>
                    <button class="difficulty-btn" data-difficulty="intermediate">Medium</button>
                    <button class="difficulty-btn" data-difficulty="hard">Hard</button>
                </div>
            </div>
        </div>

        <?php if (count($exercises) > 0): ?>
            <div class="exercise-grid">
                <?php foreach ($exercises as $exercise): ?>
                    <a href="excerciseDetails.php?id=<?php echo $exercise['id']; ?>" class="exercise-card" data-difficulty="<?php echo strtolower($exercise['difficulty']); ?>">
                        <div class="exercise-image" style="background-image: url('<?php echo !empty($exercise['image_url']) ? $exercise['image_url'] : 'images/default-exercise.jpg'; ?>');">
                        </div>
                        <div class="exercise-content">
                            <h2><?php echo htmlspecialchars($exercise['name']); ?></h2>
                            <p><?php echo htmlspecialchars($exercise['description']); ?></p>
                            <?php if(!empty($exercise['muscles_worked'])): ?>
                                <div class="muscles-worked">
                                    <i class="fas fa-dumbbell"></i> Muscles: <?php echo htmlspecialchars($exercise['muscles_worked']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="exercise-footer">
                            <span class="difficulty-tag difficulty-<?php echo strtolower($exercise['difficulty']); ?>">
                                <?php echo htmlspecialchars($exercise['difficulty']); ?>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-exercises">
                <h3>No exercises found</h3>
                <p>There are no exercises available for this category yet.</p>
                <a href="assets/setup_exercise_db.php" class="action-button">
                    <i class="fas fa-database"></i> Setup Exercise Database
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        document.getElementById('exercise-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterExercises();
        });
        
        // Difficulty filter functionality
        document.querySelectorAll('.difficulty-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.difficulty-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                filterExercises();
            });
        });
        
        function filterExercises() {
            const searchTerm = document.getElementById('exercise-search').value.toLowerCase();
            const activeDifficulty = document.querySelector('.difficulty-btn.active').dataset.difficulty;
            
            document.querySelectorAll('.exercise-card').forEach(card => {
                const title = card.querySelector('h2').innerText.toLowerCase();
                const description = card.querySelector('p').innerText.toLowerCase();
                const difficulty = card.dataset.difficulty;
                
                const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
                const matchesDifficulty = activeDifficulty === 'all' || difficulty === activeDifficulty;
                
                if (matchesSearch && matchesDifficulty) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
