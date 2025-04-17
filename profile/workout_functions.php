<?php
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

function ensureTablesExist($conn) {
    $tables = ['workouts', 'workout_exercises', 'exercise_sets'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        if (!tableExists($conn, $table)) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        if (in_array('workouts', $missingTables)) {
            $sql = "CREATE TABLE workouts (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                workout_type VARCHAR(50) DEFAULT NULL,
                duration_minutes INT DEFAULT NULL,
                calories_burned INT DEFAULT NULL,
                notes TEXT,
                rating INT DEFAULT NULL,
                template_id INT DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                total_volume DECIMAL(10,2) DEFAULT '0.00',
                avg_intensity DECIMAL(3,1) DEFAULT '0.0',
                PRIMARY KEY (id),
                KEY idx_workout_user (user_id, created_at),
                KEY idx_workout_date (created_at),
                KEY idx_workout_type (workout_type),
                CONSTRAINT workouts_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
        
        if (in_array('workout_exercises', $missingTables)) {
            $sql = "CREATE TABLE workout_exercises (
                id INT NOT NULL AUTO_INCREMENT,
                workout_id INT NOT NULL,
                user_id INT NOT NULL,
                exercise_name VARCHAR(100) NOT NULL,
                exercise_order INT DEFAULT NULL,
                notes TEXT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                sets_completed INT DEFAULT '0',
                total_reps INT DEFAULT '0',
                total_volume DECIMAL(10,2) DEFAULT '0.00',
                avg_rpe DECIMAL(3,1) DEFAULT '0.0',
                PRIMARY KEY (id),
                KEY workout_id (workout_id),
                KEY idx_user_exercises (user_id, exercise_name),
                CONSTRAINT workout_exercises_ibfk_1 FOREIGN KEY (workout_id) REFERENCES workouts (id) ON DELETE CASCADE,
                CONSTRAINT workout_exercises_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
        
        if (in_array('exercise_sets', $missingTables)) {
            $sql = "CREATE TABLE exercise_sets (
                id INT NOT NULL AUTO_INCREMENT,
                exercise_id INT NOT NULL,
                user_id INT NOT NULL,
                set_number INT NOT NULL,
                weight DECIMAL(6,2) DEFAULT NULL,
                reps INT DEFAULT NULL,
                rpe INT DEFAULT NULL,
                is_warmup TINYINT(1) DEFAULT '0',
                note TEXT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY exercise_id (exercise_id),
                KEY idx_exercise_sets_user (user_id),
                CONSTRAINT exercise_sets_ibfk_1 FOREIGN KEY (exercise_id) REFERENCES workout_exercises (id) ON DELETE CASCADE,
                CONSTRAINT exercise_sets_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
            mysqli_query($conn, $sql);
        }
    }
}

function saveWorkoutToDatabase($conn, $userId, $workoutData) {
    try {
        mysqli_begin_transaction($conn);

        $total_volume = 0;
        $total_rpe = 0;
        $total_rpe_sets = 0;
        
        foreach ($workoutData['exercises'] as $exercise) {
            if (!isset($exercise['sets']) || !is_array($exercise['sets'])) {
                continue;
            }
            
            foreach ($exercise['sets'] as $set) {
                $total_volume += floatval($set['weight']) * intval($set['reps']);
                if (isset($set['rpe']) && $set['rpe'] > 0) {
                    $total_rpe += floatval($set['rpe']);
                    $total_rpe_sets++;
                }
            }
        }
        
        $avg_intensity = $total_rpe_sets > 0 ? round($total_rpe / $total_rpe_sets, 1) : 0;

        $duration_minutes = round(floatval($workoutData['duration'] ?? 0) / 60, 2);
        $calories_burned = calculateCaloriesBurned($duration_minutes, $avg_intensity);

        $rating = isset($workoutData['rating']) ? intval($workoutData['rating']) : null;
        
        $template_id = isset($workoutData['template_id']) ? intval($workoutData['template_id']) : null;

        $workout_query = "INSERT INTO workouts (
            user_id, 
            name,
            workout_type,
            duration_minutes,
            calories_burned,
            notes,
            rating,
            template_id,
            total_volume,
            avg_intensity,
            created_at
        ) VALUES (?, ?, 'quick_workout', ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $workout_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare workout query: " . mysqli_error($conn));
        }

        $workout_name = $workoutData['name'] ?? 'Quick Workout';
        $notes = $workoutData['notes'] ?? '';
        $total_volume_ref = $total_volume;
        $avg_intensity_ref = $avg_intensity;

        $rating_ref = $rating !== null ? $rating : 0;
        $template_id_ref = $template_id !== null ? $template_id : 0;

        mysqli_stmt_bind_param($stmt, "isdisdddd", 
            $userId,
            $workout_name,
            $duration_minutes,
            $calories_burned,
            $notes,
            $rating_ref,
            $template_id_ref,
            $total_volume_ref,
            $avg_intensity_ref
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Workout save failed: " . mysqli_error($conn));
        }
        
        $workout_id = mysqli_insert_id($conn);

        foreach ($workoutData['exercises'] as $exercise_index => $exercise) {
            if (!isset($exercise['sets']) || !is_array($exercise['sets'])) {
                continue;
            }
            
            $exercise_total_reps = 0;
            $exercise_total_volume = 0;
            $exercise_total_rpe = 0;
            $exercise_rpe_sets = 0;
            
            foreach ($exercise['sets'] as $set) {
                $exercise_total_reps += intval($set['reps']);
                $exercise_total_volume += floatval($set['weight']) * intval($set['reps']);
                if (isset($set['rpe']) && $set['rpe'] > 0) {
                    $exercise_total_rpe += floatval($set['rpe']);
                    $exercise_rpe_sets++;
                }
            }
            
            $exercise_avg_rpe = $exercise_rpe_sets > 0 ? round($exercise_total_rpe / $exercise_rpe_sets, 1) : 0;
            $sets_completed = count($exercise['sets']);
            $exercise_order = $exercise_index + 1;
            $exercise_name = $exercise['name'];
            $exercise_total_volume_ref = $exercise_total_volume;
            $exercise_avg_rpe_ref = $exercise_avg_rpe;

            $exercise_query = "INSERT INTO workout_exercises (
                workout_id,
                user_id,
                exercise_name,
                exercise_order,
                sets_completed,
                total_reps,
                total_volume,
                avg_rpe,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = mysqli_prepare($conn, $exercise_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare exercise query: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "iisiiidd",
                $workout_id,
                $userId,
                $exercise_name,
                $exercise_order,
                $sets_completed,
                $exercise_total_reps,
                $exercise_total_volume_ref,
                $exercise_avg_rpe_ref
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Exercise save failed: " . mysqli_error($conn));
            }
            
            $exercise_id = mysqli_insert_id($conn);

            foreach ($exercise['sets'] as $set_index => $set) {
                $set_query = "INSERT INTO exercise_sets (
                    exercise_id,
                    user_id,
                    set_number,
                    weight,
                    reps,
                    rpe,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

                $stmt = mysqli_prepare($conn, $set_query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare set query: " . mysqli_error($conn));
                }

                $set_number = $set_index + 1;
                $weight = floatval($set['weight']);
                $reps = intval($set['reps']);
                $rpe = isset($set['rpe']) ? intval($set['rpe']) : 0;

                mysqli_stmt_bind_param($stmt, "iiidii",
                    $exercise_id,
                    $userId,
                    $set_number,
                    $weight,
                    $reps,
                    $rpe
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Set save failed: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);
        return $workout_id;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

function calculateCaloriesBurned($duration_minutes, $intensity) {
    $base_rate = 5;
    
    $intensity_multiplier = 1 + ($intensity / 5);
    
    $calories = round($base_rate * $duration_minutes * $intensity_multiplier);
    
    return $calories;
}
?> 