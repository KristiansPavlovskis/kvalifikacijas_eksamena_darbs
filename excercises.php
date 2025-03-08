<?php
// Start session
session_start();

// Include database connection
require_once 'assets/db_connection.php';

// Fetch all exercise types from the database
$sql = "SELECT * FROM exercise_types ORDER BY name";
$result = mysqli_query($conn, $sql);
$exercise_types = [];

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $exercise_types[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Exercises</title>
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
        
        .search-section {
            margin-bottom: 40px;
            position: relative;
        }
        
        .search-bar {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border-radius: 50px;
            border: 2px solid rgba(255, 77, 77, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(255, 77, 77, 0.3);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        
        .exercise-cards {
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
            position: relative;
        }
        
        .exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 77, 77, 0.2);
        }
        
        .exercise-card-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .exercise-card-image::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        }
        
        .exercise-card-content {
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
        
        .exercise-card-footer {
            padding: 15px 20px;
            background: rgba(255, 77, 77, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .difficulty-badge {
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
        
        .difficulty-intermediate {
            background-color: rgba(255, 167, 0, 0.2);
            color: #ffa700;
        }
        
        .difficulty-advanced, .difficulty-hard {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
        }
        
        .exercise-card-footer .icon {
            color: var(--primary);
        }
        
        .no-types-message {
            text-align: center;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .no-types-message h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .no-types-message p {
            margin-bottom: 25px;
            opacity: 0.8;
        }
        
        .setup-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .setup-button:hover {
            background-color: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        @media (max-width: 768px) {
            .exercise-cards {
                grid-template-columns: 1fr;
            }
            
            .exercise-header h1 {
                font-size: 2.5rem;
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
        <h1>EXERCISE LIBRARY</h1>
        <p>Discover a wide range of exercises to help you reach your fitness goals. From strength training to cardio, we've got you covered.</p>
    </section>

    <div class="exercise-container">
        <div class="search-section">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-bar" id="exercise-search" placeholder="Search exercise types...">
        </div>

        <?php if (count($exercise_types) > 0): ?>
            <div class="exercise-cards">
                <?php foreach ($exercise_types as $type): ?>
                    <a href="excerciseType.php?id=<?php echo $type['id']; ?>" class="exercise-card">
                        <div class="exercise-card-image" style="background-image: url('<?php echo !empty($type['image_url']) ? $type['image_url'] : 'images/default-exercise.jpg'; ?>');">
                        </div>
                        <div class="exercise-card-content">
                            <h2><?php echo htmlspecialchars($type['name']); ?></h2>
                            <p><?php echo htmlspecialchars($type['description']); ?></p>
                        </div>
                        <div class="exercise-card-footer">
                            <span class="difficulty-badge difficulty-<?php echo strtolower($type['difficulty']); ?>">
                                <?php echo htmlspecialchars($type['difficulty']); ?>
                            </span>
                            <i class="fas fa-chevron-right icon"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-types-message">
                <h3>No exercise types found</h3>
                <p>It looks like the exercise database needs to be set up first.</p>
                <a href="assets/setup_exercise_db.php" class="setup-button">
                    <i class="fas fa-database"></i> Setup Exercise Database
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        document.getElementById('exercise-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.exercise-card');
            
            cards.forEach(card => {
                const title = card.querySelector('h2').innerText.toLowerCase();
                const description = card.querySelector('p').innerText.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>