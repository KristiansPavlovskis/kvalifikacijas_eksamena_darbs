<?php
// Include database connection
require_once 'db_connection.php';

// Create users table
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'images/default-profile.jpg',
    weight DECIMAL(5,2) DEFAULT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    age INT DEFAULT NULL,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    fitness_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create workouts table to track workout sessions
$workouts_table = "
CREATE TABLE IF NOT EXISTS workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_name VARCHAR(100) NOT NULL,
    workout_type VARCHAR(50) NOT NULL,
    duration_minutes INT NOT NULL,
    calories_burned INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create goals table to track user fitness goals
$goals_table = "
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_type ENUM('weight', 'workout', 'strength', 'endurance', 'nutrition') NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    goal_description TEXT,
    target_value DECIMAL(10,2),
    current_value DECIMAL(10,2),
    start_date DATE NOT NULL,
    target_date DATE NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create weight_history table to track user weight changes
$weight_history_table = "
CREATE TABLE IF NOT EXISTS weight_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    recorded_at DATE NOT NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create the tables
$tables_created = [];
$error_message = '';

try {
    // Check if users table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create users table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $users_table)) {
            $tables_created[] = 'users';
        } else {
            $error_message .= "Error creating users table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'users (already exists)';
    }
    
    // Check if workouts table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'workouts'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create workouts table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $workouts_table)) {
            $tables_created[] = 'workouts';
        } else {
            $error_message .= "Error creating workouts table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'workouts (already exists)';
    }
    
    // Check if goals table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'goals'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create goals table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $goals_table)) {
            $tables_created[] = 'goals';
        } else {
            $error_message .= "Error creating goals table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'goals (already exists)';
    }
    
    // Check if weight_history table exists
    $table_exists = false;
    try {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'weight_history'");
        $table_exists = mysqli_num_rows($result) > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }

    // Create weight_history table if it doesn't exist
    if (!$table_exists) {
        if (mysqli_query($conn, $weight_history_table)) {
            $tables_created[] = 'weight_history';
        } else {
            $error_message .= "Error creating weight_history table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $tables_created[] = 'weight_history (already exists)';
    }

    // Insert a demo user if the users table is empty
    $check_users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    $users_count = mysqli_fetch_assoc($check_users)['count'];

    if ($users_count == 0) {
        // Create a demo user with a hashed password ('password123')
        $username = 'demo_user';
        $email = 'demo@gymverse.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $insert_user = "INSERT INTO users (username, email, password, weight, height, age, gender, fitness_level) 
                        VALUES (?, ?, ?, 78.5, 180, 28, 'male', 'intermediate')";
        $stmt = mysqli_prepare($conn, $insert_user);
        mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $password);
        mysqli_stmt_execute($stmt);
        $demo_user_id = mysqli_insert_id($conn);
        
        // Add some sample workouts for the demo user
        $sample_workouts = [
            [
                'workout_name' => 'Morning Run',
                'workout_type' => 'Cardio',
                'duration_minutes' => 45,
                'calories_burned' => 450,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'notes' => 'Great morning run, felt energized!'
            ],
            [
                'workout_name' => 'Chest Day',
                'workout_type' => 'Strength',
                'duration_minutes' => 60,
                'calories_burned' => 380,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'notes' => 'Increased bench press weight by 5kg'
            ],
            [
                'workout_name' => 'HIIT Session',
                'workout_type' => 'HIIT',
                'duration_minutes' => 30,
                'calories_burned' => 320,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'notes' => 'Intense session, need to improve recovery time'
            ],
            [
                'workout_name' => 'Leg Day',
                'workout_type' => 'Strength',
                'duration_minutes' => 75,
                'calories_burned' => 520,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'notes' => 'Focused on squats and deadlifts'
            ],
            [
                'workout_name' => 'Swimming',
                'workout_type' => 'Cardio',
                'duration_minutes' => 40,
                'calories_burned' => 380,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'notes' => 'Worked on freestyle technique'
            ]
        ];
        
        foreach ($sample_workouts as $workout) {
            $insert_workout = "INSERT INTO workouts (user_id, workout_name, workout_type, duration_minutes, calories_burned, completed_at, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_workout);
            mysqli_stmt_bind_param($stmt, 'issiiss', 
                $demo_user_id, 
                $workout['workout_name'], 
                $workout['workout_type'], 
                $workout['duration_minutes'], 
                $workout['calories_burned'], 
                $workout['completed_at'], 
                $workout['notes']
            );
            mysqli_stmt_execute($stmt);
        }
        
        // Add some sample goals for the demo user
        $sample_goals = [
            [
                'goal_type' => 'weight',
                'goal_name' => 'Weight Loss',
                'goal_description' => 'Lose 5kg by summer',
                'target_value' => 73.5,
                'current_value' => 78.5,
                'start_date' => date('Y-m-d', strtotime('-30 days')),
                'target_date' => date('Y-m-d', strtotime('+60 days'))
            ],
            [
                'goal_type' => 'strength',
                'goal_name' => 'Bench Press PR',
                'goal_description' => 'Achieve 100kg bench press',
                'target_value' => 100,
                'current_value' => 85,
                'start_date' => date('Y-m-d', strtotime('-45 days')),
                'target_date' => date('Y-m-d', strtotime('+45 days'))
            ],
            [
                'goal_type' => 'endurance',
                'goal_name' => '10K Run',
                'goal_description' => 'Complete a 10K run in under 50 minutes',
                'target_value' => 50,
                'current_value' => 58,
                'start_date' => date('Y-m-d', strtotime('-20 days')),
                'target_date' => date('Y-m-d', strtotime('+40 days'))
            ]
        ];
        
        foreach ($sample_goals as $goal) {
            $insert_goal = "INSERT INTO goals (user_id, goal_type, goal_name, goal_description, target_value, current_value, start_date, target_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_goal);
            mysqli_stmt_bind_param($stmt, 'isssddsss', 
                $demo_user_id, 
                $goal['goal_type'], 
                $goal['goal_name'], 
                $goal['goal_description'], 
                $goal['target_value'], 
                $goal['current_value'], 
                $goal['start_date'], 
                $goal['target_date']
            );
            mysqli_stmt_execute($stmt);
        }
        
        // Add sample weight history
        $sample_weights = [
            ['weight' => 82.0, 'days_ago' => 60],
            ['weight' => 81.2, 'days_ago' => 50],
            ['weight' => 80.5, 'days_ago' => 40],
            ['weight' => 79.8, 'days_ago' => 30],
            ['weight' => 79.3, 'days_ago' => 20],
            ['weight' => 78.9, 'days_ago' => 10],
            ['weight' => 78.5, 'days_ago' => 0]
        ];
        
        foreach ($sample_weights as $weight_data) {
            $recorded_at = date('Y-m-d', strtotime('-' . $weight_data['days_ago'] . ' days'));
            $insert_weight = "INSERT INTO weight_history (user_id, weight, recorded_at) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_weight);
            mysqli_stmt_bind_param($stmt, 'ids', $demo_user_id, $weight_data['weight'], $recorded_at);
            mysqli_stmt_execute($stmt);
        }
        
        $tables_created[] = 'Demo user and sample data created';
    }

} catch (Exception $e) {
    $error_message .= "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - User Database Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
            --success: #00cc66;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: var(--dark);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .setup-container {
            width: 100%;
            max-width: 700px;
            background: rgba(25, 25, 25, 0.9);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.5);
            padding: 40px;
            position: relative;
        }
        
        .setup-logo {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-size: 2.5rem;
            letter-spacing: 4px;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.7);
            font-family: 'Koulen', sans-serif;
        }
        
        .setup-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
            font-size: 1.5rem;
        }
        
        .setup-content p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .status-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .status-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: bold;
        }
        
        .status-list {
            list-style-type: none;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .status-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
            font-size: 12px;
        }
        
        .status-success {
            color: #00cc66;
        }
        
        .status-error {
            color: #ff4d4d;
        }
        
        .demo-credentials {
            background: linear-gradient(to right, rgba(255, 77, 77, 0.2), rgba(255, 77, 77, 0.1));
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
        }
        
        .demo-credentials h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .credential-item {
            margin-bottom: 8px;
            display: flex;
        }
        
        .credential-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }
        
        .error-message {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-logo">GYMVERSE</div>
        <h1 class="setup-title">USER DATABASE SETUP</h1>
        
        <div class="setup-content">
            <p>This script has set up the necessary database tables for user accounts and fitness tracking features:</p>
            
            <div class="status-container">
                <h2 class="status-title">Setup Results</h2>
                <ul class="status-list">
                    <?php foreach($tables_created as $table): ?>
                    <li class="status-item">
                        <i class="fas fa-check-circle status-icon status-success"></i>
                        <span><?php echo htmlspecialchars($table); ?></span>
                    </li>
                    <?php endforeach; ?>
                    
                    <?php if(!empty($error_message)): ?>
                    <li class="status-item">
                        <i class="fas fa-exclamation-circle status-icon status-error"></i>
                        <span>Errors occurred during setup</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="demo-credentials">
                <h3><i class="fas fa-user-circle"></i> Demo User Credentials</h3>
                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span>demo@gymverse.com</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span>password123</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Username:</span>
                    <span>demo_user</span>
                </div>
            </div>
            
            <?php if(!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="buttons">
                <a href="../login.php" class="btn btn-primary">Go to Login Page</a>
                <a href="../index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html> 