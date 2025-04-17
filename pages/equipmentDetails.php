<?php
include 'assets/connect_db.php';

$equipment_id = isset($_GET['id']) ? $_GET['id'] : 1; 

$sql_equipment = "SELECT * FROM equipment WHERE id = $equipment_id";
$result_equipment = mysqli_query($savienojums, $sql_equipment);

if (mysqli_num_rows($result_equipment) > 0) {
    $equipment = mysqli_fetch_assoc($result_equipment);
} else {
    header("Location: equipment.php");
    exit();
}

$sql_features = "SELECT * FROM equipment_features WHERE equipment_id = $equipment_id";
$result_features = mysqli_query($savienojums, $sql_features);

$sql_safety = "SELECT * FROM equipment_safety WHERE equipment_id = $equipment_id";
$result_safety = mysqli_query($savienojums, $sql_safety);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $equipment['name']; ?> - Equipment Details</title>
    <link rel="stylesheet" href="lietotaja-view.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet"> 
</head>
<body>
    <header>
        <a href="profile.php" class="logo">GYMVERSE</a>
        <nav>
            <a href="index.php">HOME</a>
            <a href="about.php">ABOUT</a>
            <a href="membership.php">MEMBERSHIP</a>
            <a href="leaderboard.php">LEADERBOARD</a>
            <a href="nutrition.php">NUTRITION</a>
            <a href="contact.php">CONTACT</a>
        </nav>
    </header>

    <div class="container">
        <h1 class="equipment-title"><?php echo $equipment['name']; ?></h1>

        <div class="equipment-content">
            <img src="<?php echo $equipment['image_path']; ?>" alt="<?php echo $equipment['name']; ?>" class="equipment-image">
            
            <div class="equipment-info">
                <span class="equipmentDetail-experience-tag"><?php echo $equipment['experience_level']; ?></span>

                <div class="info-section">
                    <h2>Overview</h2>
                    <p><?php echo $equipment['overview']; ?></p>
                </div>

                <div class="info-section">
                    <h2>Key Features</h2>
                    <div class="features-list">
                        <?php
                        if (mysqli_num_rows($result_features) > 0) {
                            while($feature = mysqli_fetch_assoc($result_features)) {
                                echo '<div class="equipmentDetail-feature-card">';
                                echo '<h3>' . $feature['feature_name'] . '</h3>';
                                echo '<p>' . $feature['feature_value'] . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No features available.</p>';
                        }
                        ?>
                    </div>
                </div>

                <div class="safety-tips">
                    <h2>Correct Usage & Safety</h2>
                    <ul class="safety-list">
                        <?php
                        if (mysqli_num_rows($result_safety) > 0) {
                            while($tip = mysqli_fetch_assoc($result_safety)) {
                                echo '<li>' . $tip['safety_tip'] . '</li>';
                            }
                        } else {
                            echo '<li>No safety tips available.</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    
    <div class="muscle-visualization">
        <h2>Target Muscles</h2>
        <div class="view-controls">
            <button class="view-button active" onclick="toggleView('front')">Front View</button>
            <button class="view-button" onclick="toggleView('back')">Back View</button>
        </div>
        <svg viewBox="0 0 400 600" xmlns="http://www.w3.org/2000/svg">
            <g id="human-front">
            <circle cx="200" cy="60" r="40" fill="#333"/>      
            <rect x="185" y="100" width="30" height="30" fill="#333"/>
            <path d="M150 130 L250 130 L260 300 L140 300 Z" fill="#333"/>
              
            <g id="arms">
                <path id="deltoids" d="M150 130 L110 160 L120 180 L150 160 Z" fill="#333"/>
                <path id="deltoids-right" d="M250 130 L290 160 L280 180 L250 160 Z" fill="#333"/>
                
                <rect id="biceps" x="115" y="180" width="25" height="60" fill="#333"/>
                <rect id="biceps-right" x="260" y="180" width="25" height="60" fill="#333"/>
                
                <rect id="forearms" x="110" y="240" width="30" height="70" fill="#333"/>
                <rect id="forearms-right" x="260" y="240" width="30" height="70" fill="#333"/>
            </g>
              
              <g id="legs">
                <path id="quadriceps" d="M140 300 L180 300 L175 400 L145 400 Z" fill="#ff4444"/>
                <path id="quadriceps-right" d="M220 300 L260 300 L255 400 L225 400 Z" fill="#ff4444"/>
                
                <path id="calves" d="M145 400 L175 400 L170 500 L150 500 Z" fill="#ff4444"/>
                <path id="calves-right" d="M225 400 L255 400 L250 500 L230 500 Z" fill="#ff4444"/>
              </g>
            </g>
          
            <g id="human-back" transform="translate(0, 0)" style="display: none">
              <circle cx="200" cy="60" r="40" fill="#333"/>
              
              <rect x="185" y="100" width="30" height="30" fill="#333"/>
              
              <path d="M150 130 L250 130 L260 300 L140 300 Z" fill="#333"/>
              
              <path id="upper-back" d="M160 140 L240 140 L235 200 L165 200 Z" fill="#333"/>
              <path id="lower-back" d="M165 200 L235 200 L240 280 L160 280 Z" fill="#333"/>
              
              <g id="back-arms">
                <path id="triceps" d="M150 130 L110 160 L120 180 L150 160 Z" fill="#333"/>
                <path id="triceps-right" d="M250 130 L290 160 L280 180 L250 160 Z" fill="#333"/>
                
                <rect x="115" y="180" width="25" height="60" fill="#333"/>
                <rect x="260" y="180" width="25" height="60" fill="#333"/>
                
                <rect x="110" y="240" width="30" height="70" fill="#333"/>
                <rect x="260" y="240" width="30" height="70" fill="#333"/>
              </g>
              
              <g id="back-legs">
                <path id="hamstrings" d="M140 300 L180 300 L175 400 L145 400 Z" fill="#ff4444"/>
                <path id="hamstrings-right" d="M220 300 L260 300 L255 400 L225 400 Z" fill="#ff4444"/>
                
                <path id="calves-back" d="M145 400 L175 400 L170 500 L150 500 Z" fill="#ff4444"/>
                <path id="calves-back-right" d="M225 400 L255 400 L250 500 L230 500 Z" fill="#ff4444"/>
              </g>
            </g>
          </svg>
    </div>
</div>
    <script>
        function toggleView(view) {
            const frontView = document.getElementById('human-front');
            const backView = document.getElementById('human-back');
            const buttons = document.querySelectorAll('.view-button');
            
            if (view === 'front') {
                frontView.style.display = 'block';
                backView.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                frontView.style.display = 'none';
                backView.style.display = 'block';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
    </script>
</body>
</html>