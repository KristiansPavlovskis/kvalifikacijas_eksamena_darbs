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
                <a href="profile.php" class="auth-button-login">PROFILE</a>
                <a href="logout.php" class="auth-button-logout">LOGOUT</a>
            <?php else: ?>
                <a href="login.php" class="auth-button-login">LOGIN</a>
                <a href="register.php" class="auth-button-register">REGISTER</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="exercises-container">
        <div class="exercises-search-section">
            <i class="fas fa-search exercises-search-icon"></i>
            <input type="text" class="exercises-search-bar" id="exercise-search" placeholder="Search exercise types...">
        </div>

        <?php if (count($exercise_types) > 0): ?>
            <div class="exercises-cards">
                <?php foreach ($exercise_types as $type): ?>
                    <a href="excerciseType.php?id=<?php echo $type['id']; ?>" class="exercises-card">
                        <div class="exercises-card-image" style="background-image: url('<?php echo !empty($type['image_url']) ? $type['image_url'] : 'images/default-exercise.jpg'; ?>');">
                        </div>
                        <div class="exercises-card-content">
                            <h2><?php echo htmlspecialchars($type['name']); ?></h2>
                            <p><?php echo htmlspecialchars($type['description']); ?></p>
                        </div>
                        <div class="exercises-card-footer">
                            <span class="exercises-difficulty-badge exercises-difficulty-<?php echo strtolower($type['difficulty']); ?>">
                                <?php echo htmlspecialchars($type['difficulty']); ?>
                            </span>
                            <i class="fas fa-chevron-right exercises-icon"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="exercises-no-types-message">
                <h3>No exercise types found</h3>
                <p>It looks like the exercise database needs to be set up first.</p>
                <a href="assets/setup_exercise_db.php" class="exercises-setup-button">
                    <i class="fas fa-database"></i> Setup Exercise Database
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        document.getElementById('exercise-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.exercises-card');
            
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