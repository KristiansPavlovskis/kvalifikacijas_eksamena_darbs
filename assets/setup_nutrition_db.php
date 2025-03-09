<?php
// Include database connection
require_once 'db_connection.php';

// Create nutrition_logs table to track daily food intake
$nutrition_logs_table = "
CREATE TABLE IF NOT EXISTS nutrition_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    meal_name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) NOT NULL,
    carbs DECIMAL(5,2) NOT NULL,
    fat DECIMAL(5,2) NOT NULL,
    meal_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create nutrition_goals table to track user nutrition goals
$nutrition_goals_table = "
CREATE TABLE IF NOT EXISTS nutrition_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    daily_calories INT NOT NULL DEFAULT 2000,
    daily_protein DECIMAL(5,2) NOT NULL DEFAULT 150,
    daily_carbs DECIMAL(5,2) NOT NULL DEFAULT 250,
    daily_fat DECIMAL(5,2) NOT NULL DEFAULT 70,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create food_database table for common foods
$food_database_table = "
CREATE TABLE IF NOT EXISTS food_database (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100) NOT NULL,
    serving_size VARCHAR(50) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) NOT NULL,
    carbs DECIMAL(5,2) NOT NULL,
    fat DECIMAL(5,2) NOT NULL,
    food_category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create saved_recipes table for user recipes
$saved_recipes_table = "
CREATE TABLE IF NOT EXISTS saved_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipe_name VARCHAR(100) NOT NULL,
    recipe_description TEXT,
    ingredients TEXT NOT NULL,
    instructions TEXT NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) NOT NULL,
    carbs DECIMAL(5,2) NOT NULL,
    fat DECIMAL(5,2) NOT NULL,
    prep_time INT NOT NULL,
    cook_time INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create the tables
$tables_created = [];
$error_message = '';

try {
    // Check if nutrition_logs table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'nutrition_logs'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create nutrition_logs table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $nutrition_logs_table)) {
            $tables_created[] = 'nutrition_logs';
        } else {
            $error_message .= "Error creating nutrition_logs table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'nutrition_logs (already exists)';
    }
    
    // Check if nutrition_goals table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'nutrition_goals'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create nutrition_goals table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $nutrition_goals_table)) {
            $tables_created[] = 'nutrition_goals';
        } else {
            $error_message .= "Error creating nutrition_goals table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'nutrition_goals (already exists)';
    }
    
    // Check if food_database table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'food_database'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create food_database table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $food_database_table)) {
            $tables_created[] = 'food_database';
        } else {
            $error_message .= "Error creating food_database table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'food_database (already exists)';
    }
    
    // Check if saved_recipes table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'saved_recipes'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create saved_recipes table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $saved_recipes_table)) {
            $tables_created[] = 'saved_recipes';
        } else {
            $error_message .= "Error creating saved_recipes table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'saved_recipes (already exists)';
    }

    // Insert sample food data if the food_database table is empty
    $check_foods = mysqli_query($conn, "SELECT COUNT(*) as count FROM food_database");
    $foods_count = mysqli_fetch_assoc($check_foods)['count'];

    if ($foods_count == 0) {
        // Sample foods
        $sample_foods = [
            [
                'food_name' => 'Chicken Breast',
                'serving_size' => '100g',
                'calories' => 165,
                'protein' => 31,
                'carbs' => 0,
                'fat' => 3.6,
                'food_category' => 'Protein'
            ],
            [
                'food_name' => 'Brown Rice',
                'serving_size' => '100g cooked',
                'calories' => 112,
                'protein' => 2.6,
                'carbs' => 23,
                'fat' => 0.9,
                'food_category' => 'Carbs'
            ],
            [
                'food_name' => 'Broccoli',
                'serving_size' => '100g',
                'calories' => 34,
                'protein' => 2.8,
                'carbs' => 7,
                'fat' => 0.4,
                'food_category' => 'Vegetables'
            ],
            [
                'food_name' => 'Salmon',
                'serving_size' => '100g',
                'calories' => 206,
                'protein' => 22,
                'carbs' => 0,
                'fat' => 13,
                'food_category' => 'Protein'
            ],
            [
                'food_name' => 'Avocado',
                'serving_size' => '1 medium',
                'calories' => 240,
                'protein' => 3,
                'carbs' => 12,
                'fat' => 22,
                'food_category' => 'Healthy Fats'
            ],
            [
                'food_name' => 'Greek Yogurt',
                'serving_size' => '100g',
                'calories' => 59,
                'protein' => 10,
                'carbs' => 3.6,
                'fat' => 0.4,
                'food_category' => 'Dairy'
            ],
            [
                'food_name' => 'Banana',
                'serving_size' => '1 medium',
                'calories' => 105,
                'protein' => 1.3,
                'carbs' => 27,
                'fat' => 0.4,
                'food_category' => 'Fruits'
            ],
            [
                'food_name' => 'Oatmeal',
                'serving_size' => '100g cooked',
                'calories' => 71,
                'protein' => 2.5,
                'carbs' => 12,
                'fat' => 1.5,
                'food_category' => 'Carbs'
            ],
            [
                'food_name' => 'Egg',
                'serving_size' => '1 large',
                'calories' => 78,
                'protein' => 6.3,
                'carbs' => 0.6,
                'fat' => 5.3,
                'food_category' => 'Protein'
            ],
            [
                'food_name' => 'Sweet Potato',
                'serving_size' => '100g',
                'calories' => 86,
                'protein' => 1.6,
                'carbs' => 20,
                'fat' => 0.1,
                'food_category' => 'Carbs'
            ]
        ];
        
        foreach ($sample_foods as $food) {
            $insert_food = "INSERT INTO food_database (food_name, serving_size, calories, protein, carbs, fat, food_category) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_food);
            mysqli_stmt_bind_param($stmt, 'ssiddds', 
                $food['food_name'], 
                $food['serving_size'], 
                $food['calories'], 
                $food['protein'], 
                $food['carbs'], 
                $food['fat'], 
                $food['food_category']
            );
            mysqli_stmt_execute($stmt);
        }
    }

    // Check if there's a demo user and add nutrition goals if needed
    $check_demo_user = mysqli_query($conn, "SELECT id FROM users WHERE username = 'demo_user' LIMIT 1");
    if (mysqli_num_rows($check_demo_user) > 0) {
        $demo_user = mysqli_fetch_assoc($check_demo_user);
        $demo_user_id = $demo_user['id'];
        
        // Check if demo user has nutrition goals
        $check_goals = mysqli_query($conn, "SELECT id FROM nutrition_goals WHERE user_id = $demo_user_id LIMIT 1");
        if (mysqli_num_rows($check_goals) == 0) {
            // Add nutrition goals for demo user
            $insert_goals = "INSERT INTO nutrition_goals (user_id, daily_calories, daily_protein, daily_carbs, daily_fat) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_goals);
            $calories = 2200;
            $protein = 165;
            $carbs = 275;
            $fat = 73;
            mysqli_stmt_bind_param($stmt, 'idddd', $demo_user_id, $calories, $protein, $carbs, $fat);
            mysqli_stmt_execute($stmt);
        }
        
        // Add sample nutrition logs for demo user
        $check_logs = mysqli_query($conn, "SELECT COUNT(*) as count FROM nutrition_logs WHERE user_id = $demo_user_id");
        $logs_count = mysqli_fetch_assoc($check_logs)['count'];
        
        if ($logs_count == 0) {
            // Sample meals for the past 3 days
            $sample_meals = [
                // Today
                [
                    'meal_type' => 'breakfast',
                    'meal_name' => 'Oatmeal with Banana',
                    'calories' => 350,
                    'protein' => 12,
                    'carbs' => 65,
                    'fat' => 5,
                    'meal_date' => date('Y-m-d')
                ],
                [
                    'meal_type' => 'lunch',
                    'meal_name' => 'Chicken Salad',
                    'calories' => 420,
                    'protein' => 35,
                    'carbs' => 25,
                    'fat' => 18,
                    'meal_date' => date('Y-m-d')
                ],
                // Yesterday
                [
                    'meal_type' => 'breakfast',
                    'meal_name' => 'Protein Smoothie',
                    'calories' => 320,
                    'protein' => 28,
                    'carbs' => 45,
                    'fat' => 4,
                    'meal_date' => date('Y-m-d', strtotime('-1 day'))
                ],
                [
                    'meal_type' => 'lunch',
                    'meal_name' => 'Salmon with Rice',
                    'calories' => 550,
                    'protein' => 40,
                    'carbs' => 45,
                    'fat' => 22,
                    'meal_date' => date('Y-m-d', strtotime('-1 day'))
                ],
                [
                    'meal_type' => 'dinner',
                    'meal_name' => 'Vegetable Stir Fry',
                    'calories' => 380,
                    'protein' => 15,
                    'carbs' => 40,
                    'fat' => 18,
                    'meal_date' => date('Y-m-d', strtotime('-1 day'))
                ],
                // 2 days ago
                [
                    'meal_type' => 'breakfast',
                    'meal_name' => 'Eggs and Toast',
                    'calories' => 380,
                    'protein' => 22,
                    'carbs' => 30,
                    'fat' => 18,
                    'meal_date' => date('Y-m-d', strtotime('-2 days'))
                ],
                [
                    'meal_type' => 'lunch',
                    'meal_name' => 'Turkey Sandwich',
                    'calories' => 450,
                    'protein' => 30,
                    'carbs' => 48,
                    'fat' => 15,
                    'meal_date' => date('Y-m-d', strtotime('-2 days'))
                ],
                [
                    'meal_type' => 'dinner',
                    'meal_name' => 'Pasta with Chicken',
                    'calories' => 620,
                    'protein' => 42,
                    'carbs' => 75,
                    'fat' => 12,
                    'meal_date' => date('Y-m-d', strtotime('-2 days'))
                ]
            ];
            
            foreach ($sample_meals as $meal) {
                $insert_meal = "INSERT INTO nutrition_logs (user_id, meal_type, meal_name, calories, protein, carbs, fat, meal_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_meal);
                mysqli_stmt_bind_param($stmt, 'issiddds', 
                    $demo_user_id, 
                    $meal['meal_type'], 
                    $meal['meal_name'], 
                    $meal['calories'], 
                    $meal['protein'], 
                    $meal['carbs'], 
                    $meal['fat'], 
                    $meal['meal_date']
                );
                mysqli_stmt_execute($stmt);
            }
        }
    }

} catch (Exception $e) {
    $error_message .= "Error: " . $e->getMessage() . "<br>";
}

// Output setup results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        li:last-child {
            border-bottom: none;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Nutrition Database Setup</h1>
        
        <?php if (!empty($tables_created)): ?>
            <div class="success">
                <p>Database setup completed successfully!</p>
            </div>
            <h2>Tables Created/Verified:</h2>
            <ul>
                <?php foreach ($tables_created as $table): ?>
                    <li><?php echo htmlspecialchars($table); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error">
                <p>Errors occurred during setup:</p>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <a href="/" class="back-link">← Back to Home</a>
    </div>
</body>
</html> 