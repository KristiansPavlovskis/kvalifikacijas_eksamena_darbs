<?php
/**
 * Exercise Data Population Script
 * This script will populate your database with exercise data for the quick workout feature
 */

// Add this debugging code at the top of the file
session_start();

// Debug exercise data
echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px; border: 1px solid #ddd;'>";
echo "<h3>Debugging Exercise Data</h3>";

require_once '../assets/db_connection.php';

// Check exercise_library table
$result = $conn->query("SELECT COUNT(*) as count FROM exercise_library");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>exercise_library table has " . $row['count'] . " exercises</p>";
} else {
    echo "<p>Error checking exercise_library: " . $conn->error . "</p>";
}

// Check exercises table if it exists
$result = $conn->query("SHOW TABLES LIKE 'exercises'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM exercises");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>exercises table has " . $row['count'] . " exercises</p>";
    } else {
        echo "<p>Error checking exercises: " . $conn->error . "</p>";
    }
}

// Check if fetch_exercises.php file exists
if (file_exists('../get_exercises.php')) {
    echo "<p>get_exercises.php file exists</p>";
} else {
    echo "<p>get_exercises.php file does NOT exist</p>";
}

echo "</div>";

// Database connection settings - modify these to match your environment
$host = "localhost";
$username = "root";
$password = "";
$dbname = "gymverse"; // Change this to your actual database name

// Connect to MySQL
echo "Connecting to database...\n";
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected successfully to $dbname.\n";

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if a table has data
function tableHasData($conn, $tableName) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $tableName");
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Function to execute SQL safely and report errors
function executeSql($conn, $sql, $description) {
    echo "Executing: $description... ";
    if ($conn->query($sql) === TRUE) {
        echo "Success!\n";
        return true;
    } else {
        echo "Error: " . $conn->error . "\n";
        return false;
    }
}

// Start populating data
echo "\n=== STARTING DATA POPULATION ===\n\n";

// Check and populate muscle_groups table
if (tableExists($conn, 'muscle_groups')) {
    if (!tableHasData($conn, 'muscle_groups')) {
        echo "Populating muscle_groups table...\n";
        
        $muscle_groups = [
            ['Biceps', 'Arms'],
            ['Triceps', 'Arms'],
            ['Chest', 'Chest'],
            ['Upper Back', 'Back'],
            ['Lower Back', 'Back'],
            ['Shoulders', 'Shoulders'],
            ['Quadriceps', 'Legs'],
            ['Hamstrings', 'Legs'],
            ['Calves', 'Legs'],
            ['Glutes', 'Legs'],
            ['Abdominals', 'Core'],
            ['Obliques', 'Core'],
            ['Forearms', 'Arms']
        ];
        
        foreach ($muscle_groups as $group) {
            $name = $conn->real_escape_string($group[0]);
            $category = $conn->real_escape_string($group[1]);
            executeSql($conn, 
                "INSERT INTO muscle_groups (name, category) VALUES ('$name', '$category')",
                "Adding muscle group: $name"
            );
        }
    } else {
        echo "muscle_groups table already has data. Skipping.\n";
    }
} else {
    echo "Warning: muscle_groups table does not exist. Creating...\n";
    
    executeSql($conn, 
        "CREATE TABLE muscle_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            category VARCHAR(50) NOT NULL
        )",
        "Creating muscle_groups table"
    );
    
    echo "Please run this script again to populate the newly created table.\n";
}

// Check and populate equipment table
if (tableExists($conn, 'equipment')) {
    if (!tableHasData($conn, 'equipment')) {
        echo "Populating equipment table...\n";
        
        $equipment_items = [
            ['Barbell', 'A long metal bar with weights attached at each end'],
            ['Dumbbell', 'A short bar with a weight at each end'],
            ['Kettlebell', 'A cast-iron weight with a handle'],
            ['Resistance Band', 'Elastic bands that provide resistance'],
            ['Machine', 'Fixed exercise equipment with cables or weight stacks'],
            ['Cable Machine', 'Adjustable pulley system with weight stack'],
            ['Bench', 'Flat or adjustable bench for exercises'],
            ['Bodyweight', 'Using your own body weight as resistance'],
            ['Medicine Ball', 'Weighted ball used for exercises'],
            ['Swiss Ball', 'Large inflatable ball for stability exercises'],
            ['Pull-up Bar', 'Horizontal bar for pull-ups and chin-ups'],
            ['TRX/Suspension Trainer', 'Straps for bodyweight resistance training']
        ];
        
        foreach ($equipment_items as $item) {
            $name = $conn->real_escape_string($item[0]);
            $description = $conn->real_escape_string($item[1]);
            executeSql($conn, 
                "INSERT INTO equipment (name, description) VALUES ('$name', '$description')",
                "Adding equipment: $name"
            );
        }
    } else {
        echo "equipment table already has data. Skipping.\n";
    }
} else {
    echo "Warning: equipment table does not exist. Creating...\n";
    
    executeSql($conn, 
        "CREATE TABLE equipment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description TEXT
        )",
        "Creating equipment table"
    );
    
    echo "Please run this script again to populate the newly created table.\n";
}

// Check and populate exercise_library table
if (tableExists($conn, 'exercise_library')) {
    if (!tableHasData($conn, 'exercise_library')) {
        echo "Populating exercise_library table...\n";
        
        // Get muscle group IDs
        $muscle_groups_result = $conn->query("SELECT id, name FROM muscle_groups");
        $muscle_groups = [];
        while ($row = $muscle_groups_result->fetch_assoc()) {
            $muscle_groups[$row['name']] = $row['id'];
        }
        
        // Get equipment IDs
        $equipment_result = $conn->query("SELECT id, name FROM equipment");
        $equipment = [];
        while ($row = $equipment_result->fetch_assoc()) {
            $equipment[$row['name']] = $row['id'];
        }
        
        $exercises = [
            // Chest exercises
            ['Barbell Bench Press', $muscle_groups['Chest'] ?? 1, $equipment['Barbell'] ?? 1, 'intermediate', 
             'Lie on a bench, grip the barbell with hands slightly wider than shoulder-width, lower to chest and press back up.'],
            ['Push-up', $muscle_groups['Chest'] ?? 1, $equipment['Bodyweight'] ?? 8, 'beginner', 
             'Start in a plank position with hands shoulder-width apart, lower your body until your chest nearly touches the floor, then push back up.'],
            ['Dumbbell Fly', $muscle_groups['Chest'] ?? 1, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Lie on a bench holding dumbbells above your chest, then lower them out to the sides with elbows slightly bent.'],
            ['Cable Crossover', $muscle_groups['Chest'] ?? 1, $equipment['Cable Machine'] ?? 6, 'intermediate', 
             'Stand between cable machines, hold handles and bring arms together in front of you in a hugging motion.'],
            
            // Back exercises
            ['Pull-up', $muscle_groups['Upper Back'] ?? 4, $equipment['Pull-up Bar'] ?? 11, 'intermediate', 
             'Hang from a bar with palms facing away, pull your body up until chin clears the bar, then lower back down.'],
            ['Bent Over Row', $muscle_groups['Upper Back'] ?? 4, $equipment['Barbell'] ?? 1, 'intermediate', 
             'Bend at the hips, keeping back straight, pull barbell to your lower chest, then lower it back down.'],
            ['Lat Pulldown', $muscle_groups['Upper Back'] ?? 4, $equipment['Machine'] ?? 5, 'beginner', 
             'Sit at machine, grip bar wider than shoulder-width, pull down to chest level, then return to starting position.'],
            ['Deadlift', $muscle_groups['Lower Back'] ?? 5, $equipment['Barbell'] ?? 1, 'advanced', 
             'Stand with feet hip-width apart, bend to grip barbell, keeping back straight, lift by extending knees and hips.'],
            
            // Arm exercises
            ['Bicep Curl', $muscle_groups['Biceps'] ?? 1, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Hold dumbbells at sides, palms forward, curl weights up toward shoulders, then lower back down.'],
            ['Tricep Extension', $muscle_groups['Triceps'] ?? 2, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Hold dumbbell with both hands above head, lower it behind your head by bending elbows, then extend arms to starting position.'],
            ['Hammer Curl', $muscle_groups['Biceps'] ?? 1, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Hold dumbbells with palms facing each other, curl weights up toward shoulders, then lower back down.'],
            ['Tricep Pushdown', $muscle_groups['Triceps'] ?? 2, $equipment['Cable Machine'] ?? 6, 'beginner', 
             'Stand at cable machine with high pulley, push handlebar down by extending arms, keeping elbows at sides.'],
            
            // Leg exercises
            ['Squat', $muscle_groups['Quadriceps'] ?? 7, $equipment['Barbell'] ?? 1, 'intermediate', 
             'Place barbell on upper back, feet shoulder-width apart, bend knees and hips to lower body, then push back up.'],
            ['Leg Press', $muscle_groups['Quadriceps'] ?? 7, $equipment['Machine'] ?? 5, 'beginner', 
             'Sit in machine with feet on platform, push platform away by extending knees, then return to starting position.'],
            ['Romanian Deadlift', $muscle_groups['Hamstrings'] ?? 8, $equipment['Barbell'] ?? 1, 'intermediate', 
             'Hold barbell in front of thighs, hinge at hips while keeping back straight, lower bar along legs, then return to standing.'],
            ['Calf Raise', $muscle_groups['Calves'] ?? 9, $equipment['Machine'] ?? 5, 'beginner', 
             'Stand on edge of platform with heels hanging off, raise up onto toes, then lower heels below platform level.'],
            
            // Shoulder exercises
            ['Overhead Press', $muscle_groups['Shoulders'] ?? 6, $equipment['Barbell'] ?? 1, 'intermediate', 
             'Hold barbell at shoulder height, press weight overhead until arms are straight, then lower back to shoulders.'],
            ['Lateral Raise', $muscle_groups['Shoulders'] ?? 6, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Hold dumbbells at sides, raise arms out to sides until parallel to floor, then lower back down.'],
            ['Front Raise', $muscle_groups['Shoulders'] ?? 6, $equipment['Dumbbell'] ?? 2, 'beginner', 
             'Hold dumbbells in front of thighs, raise arms forward until parallel to floor, then lower back down.'],
            
            // Core exercises
            ['Crunch', $muscle_groups['Abdominals'] ?? 11, $equipment['Bodyweight'] ?? 8, 'beginner', 
             'Lie on back with knees bent, hands behind head, lift shoulders off floor by contracting abs, then lower back down.'],
            ['Plank', $muscle_groups['Abdominals'] ?? 11, $equipment['Bodyweight'] ?? 8, 'beginner', 
             'Hold a push-up position with body straight, weight on forearms and toes, keeping core tight.'],
            ['Russian Twist', $muscle_groups['Obliques'] ?? 12, $equipment['Medicine Ball'] ?? 9, 'beginner', 
             'Sit on floor with knees bent, lean back slightly, twist torso to alternating sides while holding weight.'],
            ['Hanging Leg Raise', $muscle_groups['Abdominals'] ?? 11, $equipment['Pull-up Bar'] ?? 11, 'intermediate', 
             'Hang from bar, raise legs until parallel to floor or higher, then lower back down under control.']
        ];
        
        foreach ($exercises as $exercise) {
            $name = $conn->real_escape_string($exercise[0]);
            $muscle_id = $exercise[1];
            $equipment_id = $exercise[2];
            $difficulty = $conn->real_escape_string($exercise[3]);
            $instructions = $conn->real_escape_string($exercise[4]);
            
            executeSql($conn, 
                "INSERT INTO exercise_library (exercise_name, muscle_group_id, equipment_id, difficulty, instructions) VALUES ('$name', $muscle_id, $equipment_id, '$difficulty', '$instructions')",
                "Adding exercise: $name"
            );
        }
    } else {
        echo "exercise_library table already has data. Skipping.\n";
    }
} else {
    echo "Warning: exercise_library table does not exist. Creating...\n";
    
    executeSql($conn, 
        "CREATE TABLE exercise_library (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exercise_name VARCHAR(100) NOT NULL,
            muscle_group_id INT,
            equipment_id INT,
            difficulty ENUM('beginner', 'intermediate', 'advanced'),
            instructions TEXT,
            FOREIGN KEY (muscle_group_id) REFERENCES muscle_groups(id),
            FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        )",
        "Creating exercise_library table"
    );
    
    echo "Please run this script again to populate the newly created table.\n";
}

// Check and populate exercises table (if different from exercise_library)
if (tableExists($conn, 'exercises') && $conn->query("DESCRIBE exercises")->num_rows > 0) {
    if (!tableHasData($conn, 'exercises')) {
        echo "Populating exercises table from exercise_library...\n";
        
        // This query assumes exercises table has similar structure to exercise_library
        // Modify as needed to match your actual schema
        executeSql($conn, 
            "INSERT INTO exercises (name, muscle_group_id, equipment_id, difficulty, instructions)
             SELECT exercise_name, muscle_group_id, equipment_id, difficulty, instructions
             FROM exercise_library",
            "Copying data from exercise_library to exercises"
        );
    } else {
        echo "exercises table already has data. Skipping.\n";
    }
}

// Final message
echo "\n=== DATA POPULATION COMPLETE ===\n";
echo "Exercise data has been successfully added to your database.\n";
echo "The quick workout feature should now show exercises when searching.\n";

// Close connection
$conn->close();
?> 