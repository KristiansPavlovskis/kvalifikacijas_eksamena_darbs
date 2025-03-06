<?php
include 'assets/connect_db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Equipment</title>
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

    <h1 class="title">GYM EQUIPMENT</h1>

    <div class="equipment-filters">
        <button class="equipment-filter-btn active" data-category="all">ALL</button>
        <?php
        // Get all categories from the database
        $sql_categories = "SELECT * FROM equipment_categories";
        $result_categories = mysqli_query($savienojums, $sql_categories);
        
        if (mysqli_num_rows($result_categories) > 0) {
            while($category = mysqli_fetch_assoc($result_categories)) {
                echo '<button class="equipment-filter-btn" data-category="' . $category['id'] . '">' . $category['category_name'] . '</button>';
            }
        }
        ?>
    </div>
    
    <div class="equipment-grid">
        <?php
        // Get all equipment from the database
        $sql_equipment = "SELECT * FROM equipment";
        $result_equipment = mysqli_query($savienojums, $sql_equipment);
        
        if (mysqli_num_rows($result_equipment) > 0) {
            while($equipment = mysqli_fetch_assoc($result_equipment)) {
                // Get all categories for this equipment
                $sql_mapping = "SELECT ec.id, ec.category_name 
                               FROM equipment_categories ec 
                               JOIN equipment_category_mapping ecm ON ec.id = ecm.category_id 
                               WHERE ecm.equipment_id = " . $equipment['id'];
                $result_mapping = mysqli_query($savienojums, $sql_mapping);
                
                $categories = array();
                while ($category = mysqli_fetch_assoc($result_mapping)) {
                    $categories[] = $category['id'];
                }
                
                // Create a data-categories attribute as a comma-separated list of category IDs
                $categories_string = implode(',', $categories);
                
                // Output the equipment card
                echo '<a href="equipmentDetails.php?id=' . $equipment['id'] . '" class="equipment-card-link">';
                echo '<div class="equipment-card" data-categories="' . $categories_string . '">';
                echo '<span class="experience-tag equipment-' . strtolower($equipment['experience_level']) . '">' . $equipment['experience_level'] . '</span>';
                echo '<h3 class="equipment-name">' . $equipment['name'] . '</h3>';
                echo '<div class="equipment-icon">' . $equipment['icon'] . '</div>';
                echo '<div class="equipment-recommendations">' . $equipment['recommendations'] . '</div>';
                echo '<div class="equipment-muscles">' . $equipment['muscles_targeted'] . '</div>';
                echo '</div>';
                echo '</a>';
            }
        } else {
            echo '<p>No equipment found.</p>';
        }
        ?>
    </div>

    <script>
        const filterButtons = document.querySelectorAll('.equipment-filter-btn');
        const equipmentCards = document.querySelectorAll('.equipment-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                button.classList.add('active');
                
                const category = button.getAttribute('data-category');
                
                // Filter equipment cards
                equipmentCards.forEach(card => {
                    if (category === 'all') {
                        card.style.display = 'block';
                    } else {
                        const categories = card.getAttribute('data-categories').split(',');
                        if (categories.includes(category)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>