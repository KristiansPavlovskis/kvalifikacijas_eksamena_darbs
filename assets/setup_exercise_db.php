<?php
// Include database connection
require_once 'db_connection.php';

// Create exercise_types table
$exercise_types_table = "
CREATE TABLE IF NOT EXISTS exercise_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    difficulty VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create exercises table
$exercises_table = "
CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    instructions TEXT,
    muscles_worked TEXT,
    type_id INT,
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    difficulty VARCHAR(50),
    equipment_needed TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES exercise_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create the tables
$created_types_table = false;
$created_exercises_table = false;
$error_message = '';

try {
    // Create exercise_types table
    if (mysqli_query($conn, $exercise_types_table)) {
        $created_types_table = true;
    } else {
        $error_message .= "Error creating exercise_types table: " . mysqli_error($conn) . "<br>";
    }

    // Create exercises table
    if (mysqli_query($conn, $exercises_table)) {
        $created_exercises_table = true;
    } else {
        $error_message .= "Error creating exercises table: " . mysqli_error($conn) . "<br>";
    }

    // Insert sample exercise types if no types exist
    $check_types = mysqli_query($conn, "SELECT COUNT(*) as count FROM exercise_types");
    $types_count = mysqli_fetch_assoc($check_types)['count'];

    if ($types_count == 0) {
        $sample_types = [
            [
                'name' => 'STRENGTH TRAINING',
                'description' => 'Build muscle and gain power',
                'image_url' => 'images/strength-training.jpg',
                'difficulty' => 'Intermediate'
            ],
            [
                'name' => 'CARDIO',
                'description' => 'Improve endurance and heart health',
                'image_url' => 'images/cardio.jpg',
                'difficulty' => 'Beginner'
            ],
            [
                'name' => 'HIIT',
                'description' => 'Burn calories fast with short bursts of effort',
                'image_url' => 'images/hiit.jpg',
                'difficulty' => 'Advanced'
            ],
            [
                'name' => 'BODYWEIGHT',
                'description' => 'Master control of your body',
                'image_url' => 'images/bodyweight.jpg',
                'difficulty' => 'Beginner'
            ],
            [
                'name' => 'STRETCHING',
                'description' => 'Enhance flexibility and recovery',
                'image_url' => 'images/stretching.jpg',
                'difficulty' => 'Beginner'
            ]
        ];

        foreach ($sample_types as $type) {
            $insert_type = "INSERT INTO exercise_types (name, description, image_url, difficulty) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_type);
            mysqli_stmt_bind_param($stmt, 'ssss', $type['name'], $type['description'], $type['image_url'], $type['difficulty']);
            mysqli_stmt_execute($stmt);
        }
    }

    // Insert sample exercises if no exercises exist
    $check_exercises = mysqli_query($conn, "SELECT COUNT(*) as count FROM exercises");
    $exercises_count = mysqli_fetch_assoc($check_exercises)['count'];

    if ($exercises_count == 0) {
        // Get the IDs of the exercise types
        $get_type_ids = mysqli_query($conn, "SELECT id, name FROM exercise_types");
        $type_ids = [];
        while ($row = mysqli_fetch_assoc($get_type_ids)) {
            $type_ids[$row['name']] = $row['id'];
        }

        $sample_exercises = [
            [
                'name' => 'Bench Press',
                'description' => 'Build chest strength and upper body power',
                'instructions' => "1. Lie on a flat bench with your feet flat on the floor.\n2. Grip the barbell slightly wider than shoulder-width.\n3. Lower the bar to your mid-chest.\n4. Press the bar upward until your arms are extended.\n5. Repeat for the desired number of repetitions.",
                'muscles_worked' => 'Chest, Shoulders, Triceps',
                'type_id' => $type_ids['STRENGTH TRAINING'],
                'image_url' => 'images/bench-press.jpg',
                'difficulty' => 'Intermediate',
                'equipment_needed' => 'Bench, Barbell, Weight plates'
            ],
            [
                'name' => 'Squats',
                'description' => 'Develop lower body strength and stability',
                'instructions' => "1. Stand with feet shoulder-width apart.\n2. Keep your chest up and back straight.\n3. Bend at the knees and hips to lower your body.\n4. Lower until thighs are parallel to the ground.\n5. Push through your heels to return to standing position.",
                'muscles_worked' => 'Quadriceps, Hamstrings, Glutes, Lower Back',
                'type_id' => $type_ids['STRENGTH TRAINING'],
                'image_url' => 'images/squats.jpg',
                'difficulty' => 'Hard',
                'equipment_needed' => 'Optional: Barbell, Weight plates'
            ],
            [
                'name' => 'Bicep Curls',
                'description' => 'Isolate and build your biceps',
                'instructions' => "1. Stand with feet shoulder-width apart, holding dumbbells at your sides.\n2. Keep your elbows close to your torso.\n3. Curl the weights up toward your shoulders.\n4. Lower back down with control.\n5. Repeat for the desired number of repetitions.",
                'muscles_worked' => 'Biceps, Forearms',
                'type_id' => $type_ids['STRENGTH TRAINING'],
                'image_url' => 'images/bicep-curls.jpg',
                'difficulty' => 'Easy',
                'equipment_needed' => 'Dumbbells'
            ],
            [
                'name' => 'Running',
                'description' => 'Classic cardiovascular exercise',
                'instructions' => "1. Start at a comfortable pace.\n2. Maintain good posture with a slight forward lean.\n3. Land midfoot, not on heels or toes.\n4. Keep arms at 90 degrees, swinging from the shoulder.\n5. Breathe rhythmically and consistently.",
                'muscles_worked' => 'Legs, Heart, Lungs',
                'type_id' => $type_ids['CARDIO'],
                'image_url' => 'images/running.jpg',
                'difficulty' => 'Moderate',
                'equipment_needed' => 'Running shoes, Optional: Treadmill'
            ],
            [
                'name' => 'Burpees',
                'description' => 'Full-body exercise that combines strength and cardio',
                'instructions' => "1. Start in a standing position.\n2. Drop into a squat position and place hands on the ground.\n3. Kick feet back into a plank position.\n4. Perform a push-up (optional).\n5. Jump feet back to squat position.\n6. Jump up with arms extended overhead.",
                'muscles_worked' => 'Full Body',
                'type_id' => $type_ids['HIIT'],
                'image_url' => 'images/burpees.jpg',
                'difficulty' => 'Hard',
                'equipment_needed' => 'None'
            ]
        ];

        foreach ($sample_exercises as $exercise) {
            $insert_exercise = "INSERT INTO exercises (name, description, instructions, muscles_worked, type_id, image_url, difficulty, equipment_needed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_exercise);
            mysqli_stmt_bind_param($stmt, 'ssssssss', 
                $exercise['name'], 
                $exercise['description'], 
                $exercise['instructions'], 
                $exercise['muscles_worked'], 
                $exercise['type_id'], 
                $exercise['image_url'], 
                $exercise['difficulty'], 
                $exercise['equipment_needed']
            );
            mysqli_stmt_execute($stmt);
        }
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
    <title>GYMVERSE - Exercise Database Setup</title>
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
        
        .status-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .status-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
            font-size: 14px;
        }
        
        .status-success {
            background-color: rgba(0, 204, 102, 0.2);
            color: #00cc66;
        }
        
        .status-error {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
        }
        
        .status-text {
            flex: 1;
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
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-logo">GYMVERSE</div>
        <h1 class="setup-title">EXERCISE DATABASE SETUP</h1>
        
        <div class="status-container">
            <?php if($created_types_table): ?>
            <div class="status-item">
                <div class="status-icon status-success">
                    <i class="fas fa-check"></i>
                </div>
                <div class="status-text">
                    <h3>Exercise Types Table</h3>
                    <p>Successfully created or already exists</p>
                </div>
            </div>
            <?php else: ?>
            <div class="status-item">
                <div class="status-icon status-error">
                    <i class="fas fa-times"></i>
                </div>
                <div class="status-text">
                    <h3>Exercise Types Table</h3>
                    <p>Failed to create</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($created_exercises_table): ?>
            <div class="status-item">
                <div class="status-icon status-success">
                    <i class="fas fa-check"></i>
                </div>
                <div class="status-text">
                    <h3>Exercises Table</h3>
                    <p>Successfully created or already exists</p>
                </div>
            </div>
            <?php else: ?>
            <div class="status-item">
                <div class="status-icon status-error">
                    <i class="fas fa-times"></i>
                </div>
                <div class="status-text">
                    <h3>Exercises Table</h3>
                    <p>Failed to create</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
            <div class="status-item">
                <div class="status-icon status-error">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="status-text">
                    <h3>Error Details</h3>
                    <p><?php echo $error_message; ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="buttons">
            <a href="../excercises.php" class="btn btn-primary">View Exercises</a>
            <a href="../index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html> 