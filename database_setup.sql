-- =============================
-- STEP 1: CREATE ALL TABLES
-- =============================

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS exercise_sets;
DROP TABLE IF EXISTS workout_exercises;
DROP TABLE IF EXISTS user_favorite_exercises;
DROP TABLE IF EXISTS workouts;
DROP TABLE IF EXISTS workout_templates;
DROP TABLE IF EXISTS exercise_library;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS muscle_groups;
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS body_parts;

-- 1. Create users table (parent table)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Create body_parts table
CREATE TABLE body_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- 3. Create muscle_groups table
CREATE TABLE muscle_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    body_part_id INT,
    FOREIGN KEY (body_part_id) REFERENCES body_parts(id)
);

-- 4. Create equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50)
);

-- 5. Create exercise_library table
CREATE TABLE exercise_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exercise_name VARCHAR(100) NOT NULL,
    description TEXT,
    muscle_group_id INT,
    equipment_id INT,
    difficulty ENUM('beginner', 'intermediate', 'advanced'),
    instructions TEXT,
    video_url VARCHAR(255),
    image_url VARCHAR(255),
    popularity INT DEFAULT 0,
    FOREIGN KEY (muscle_group_id) REFERENCES muscle_groups(id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

-- 6. Create goals table
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    goal_type ENUM('weight', 'strength', 'endurance', 'workout', 'nutrition') NOT NULL,
    target_value DECIMAL(10,2),
    current_value DECIMAL(10,2) DEFAULT 0,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 7. Create workouts table
CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    workout_type VARCHAR(50),
    duration_minutes INT,
    calories_burned INT,
    notes TEXT,
    rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 8. Create workout_exercises table
CREATE TABLE workout_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workout_id INT NOT NULL,
    user_id INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    exercise_order INT,
    notes TEXT,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 9. Create exercise_sets table
CREATE TABLE exercise_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exercise_id INT NOT NULL,
    user_id INT NOT NULL,
    set_number INT NOT NULL,
    weight DECIMAL(6,2),
    reps INT,
    rpe INT,
    is_warmup BOOLEAN DEFAULT FALSE,
    note TEXT,
    FOREIGN KEY (exercise_id) REFERENCES workout_exercises(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 10. Create user_favorite_exercises table
CREATE TABLE user_favorite_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exercise_id) REFERENCES exercise_library(id),
    UNIQUE (user_id, exercise_id)
);

-- 11. Create workout_templates table
CREATE TABLE workout_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'dumbbell',
    color VARCHAR(20) DEFAULT '#4361ee',
    duration_minutes INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exercises JSON,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =============================
-- STEP 2: INSERT SAMPLE DATA
-- =============================

-- 1. Insert sample users
INSERT INTO users (username, password, email, first_name, last_name, gender, weight, height) VALUES 
('john_doe', '$2y$10$7FRZ3WC4jqnzJ4HQnhZ1ReEp9yl00X5niQgIRRg1E4D60M4AsX9v2', 'john@example.com', 'John', 'Doe', 'male', 75.5, 180.0),
('jane_smith', '$2y$10$7FRZ3WC4jqnzJ4HQnhZ1ReEp9yl00X5niQgIRRg1E4D60M4AsX9v2', 'jane@example.com', 'Jane', 'Smith', 'female', 62.0, 165.0),
('mike_johnson', '$2y$10$7FRZ3WC4jqnzJ4HQnhZ1ReEp9yl00X5niQgIRRg1E4D60M4AsX9v2', 'mike@example.com', 'Mike', 'Johnson', 'male', 82.0, 188.0);

-- 2. Insert body parts
INSERT INTO body_parts (name) VALUES 
('Arms'),
('Legs'),
('Chest'),
('Back'),
('Shoulders'),
('Core'),
('Full Body');

-- 3. Insert muscle groups
INSERT INTO muscle_groups (name, body_part_id) VALUES 
('Biceps', 1),
('Triceps', 1),
('Forearms', 1),
('Quadriceps', 2),
('Hamstrings', 2),
('Calves', 2),
('Glutes', 2),
('Pectorals', 3),
('Lats', 4),
('Trapezius', 4),
('Deltoids', 5),
('Abdominals', 6),
('Obliques', 6);

-- 4. Insert equipment
INSERT INTO equipment (name, description, icon) VALUES 
('Barbell', 'A long metal bar with weights attached at each end.', 'fa-dumbbell'),
('Dumbbell', 'A short bar with a weight at each end.', 'fa-dumbbell'),
('Kettlebell', 'A cast-iron or cast steel weight with a handle.', 'fa-dumbbell'),
('Machine', 'Resistance training equipment.', 'fa-cog'),
('Cable', 'A pulley system with adjustable weights.', 'fa-route'),
('Bodyweight', 'Exercises that use your own body weight as resistance.', 'fa-user'),
('Resistance Band', 'Elastic bands that provide resistance.', 'fa-tape'),
('Medicine Ball', 'A weighted ball used for exercise.', 'fa-circle'),
('Bench', 'A padded workout bench.', 'fa-bed');

-- 5. Insert exercises
INSERT INTO exercise_library (exercise_name, description, muscle_group_id, equipment_id, difficulty, instructions, popularity) VALUES 
('Barbell Bench Press', 'A compound exercise that targets the chest, shoulders, and triceps.', 8, 1, 'intermediate', '1. Lie on a flat bench with feet on the ground\n2. Grip the barbell slightly wider than shoulder-width\n3. Lower the bar to your chest\n4. Press the weight back up to the starting position', 100),
('Barbell Squat', 'A compound exercise that primarily targets the quadriceps, hamstrings, and glutes.', 4, 1, 'intermediate', '1. Place the barbell on your upper back\n2. Feet shoulder-width apart\n3. Lower your body until thighs are parallel to the ground\n4. Push through your heels to return to starting position', 95),
('Deadlift', 'A compound exercise that targets the back, glutes, and hamstrings.', 4, 1, 'advanced', '1. Stand with feet hip-width apart\n2. Bend at the hips and knees to grip the barbell\n3. Lift the bar by extending your hips and knees\n4. Lower the bar back to the ground under control', 90),
('Pull-up', 'A bodyweight exercise that targets the back and biceps.', 9, 6, 'intermediate', '1. Hang from a pull-up bar with palms facing away from you\n2. Pull your body up until your chin clears the bar\n3. Lower yourself under control', 85),
('Push-up', 'A bodyweight exercise that targets the chest, shoulders, and triceps.', 8, 6, 'beginner', '1. Start in a plank position with hands slightly wider than shoulder-width\n2. Lower your body until your chest nearly touches the floor\n3. Push yourself back up to the starting position', 80),
('Bicep Curl', 'An isolation exercise for the biceps.', 1, 2, 'beginner', '1. Stand with a dumbbell in each hand, arms at your sides\n2. Curl the weights up to your shoulders\n3. Lower the weights back to the starting position', 75),
('Tricep Extension', 'An isolation exercise for the triceps.', 2, 2, 'beginner', '1. Stand or sit with a dumbbell held overhead\n2. Lower the weight behind your head by bending your elbow\n3. Extend your arm back to the starting position', 70),
('Leg Press', 'A compound exercise that targets the quadriceps, hamstrings, and glutes.', 4, 4, 'beginner', '1. Sit on the leg press machine with feet on the platform\n2. Press the platform away by extending your knees\n3. Return to starting position by bending your knees', 65),
('Lat Pulldown', 'A compound exercise that targets the back and biceps.', 9, 5, 'beginner', '1. Sit at a lat pulldown machine with a wide grip on the bar\n2. Pull the bar down to your chest\n3. Slowly return the bar to the starting position', 60),
('Shoulder Press', 'A compound exercise that targets the shoulders and triceps.', 11, 2, 'intermediate', '1. Sit or stand with a dumbbell in each hand at shoulder height\n2. Press the weights overhead\n3. Lower the weights back to shoulder height', 55);

-- 6. Insert goals
INSERT INTO goals (user_id, title, description, goal_type, target_value, current_value, deadline) VALUES 
(1, 'Bench Press 100kg', 'Increase my bench press max to 100kg', 'strength', 100, 85, '2023-12-31'),
(1, 'Lose Weight', 'Get down to 70kg', 'weight', 70, 75.5, '2023-09-30'),
(2, 'Run 10km', 'Be able to run 10km without stopping', 'endurance', 10, 7, '2023-10-15'),
(2, 'Weekly Workouts', 'Complete 4 workouts every week', 'workout', 4, 2, '2023-08-31'),
(3, 'Daily Protein', 'Consume 150g of protein daily', 'nutrition', 150, 120, '2023-09-01');

-- 7. Insert workouts
INSERT INTO workouts (user_id, name, workout_type, duration_minutes, calories_burned, rating) VALUES 
(1, 'Monday Chest Day', 'strength', 65, 450, 4),
(1, 'Leg Day Workout', 'strength', 75, 550, 5),
(2, 'HIIT Training', 'cardio', 30, 380, 4),
(2, 'Full Body Strength', 'strength', 60, 420, 3),
(3, 'Upper Body Focus', 'strength', 50, 350, 4);

-- 8. Insert workout exercises
INSERT INTO workout_exercises (workout_id, user_id, exercise_name, exercise_order) VALUES 
(1, 1, 'Barbell Bench Press', 1),
(1, 1, 'Push-up', 2),
(1, 1, 'Tricep Extension', 3),
(2, 1, 'Barbell Squat', 1),
(2, 1, 'Leg Press', 2),
(2, 1, 'Deadlift', 3),
(3, 2, 'Burpees', 1),
(3, 2, 'Mountain Climbers', 2),
(3, 2, 'Jump Rope', 3),
(4, 2, 'Push-up', 1),
(4, 2, 'Barbell Squat', 2),
(4, 2, 'Pull-up', 3),
(5, 3, 'Shoulder Press', 1),
(5, 3, 'Bicep Curl', 2),
(5, 3, 'Lat Pulldown', 3);

-- 9. Insert exercise sets
INSERT INTO exercise_sets (exercise_id, user_id, set_number, weight, reps, rpe) VALUES 
(1, 1, 1, 60, 12, 7),
(1, 1, 2, 70, 10, 8),
(1, 1, 3, 80, 8, 9),
(2, 1, 1, 0, 15, 6),
(2, 1, 2, 0, 15, 7),
(2, 1, 3, 0, 12, 8),
(3, 1, 1, 15, 12, 6),
(3, 1, 2, 15, 12, 7),
(4, 1, 1, 80, 10, 7),
(4, 1, 2, 90, 8, 8),
(4, 1, 3, 100, 6, 9),
(5, 1, 1, 150, 12, 7),
(5, 1, 2, 170, 10, 8),
(6, 1, 1, 100, 8, 8),
(6, 1, 2, 110, 6, 9),
(10, 2, 1, 0, 15, 6),
(10, 2, 2, 0, 12, 7),
(11, 2, 1, 50, 10, 7),
(11, 2, 2, 60, 8, 8),
(12, 2, 1, 0, 6, 8),
(12, 2, 2, 0, 5, 9),
(13, 3, 1, 15, 12, 7),
(13, 3, 2, 20, 10, 8),
(14, 3, 1, 12, 12, 6),
(14, 3, 2, 15, 10, 7),
(15, 3, 1, 50, 12, 7),
(15, 3, 2, 60, 10, 8);

-- 10. Insert user favorite exercises
INSERT INTO user_favorite_exercises (user_id, exercise_id) VALUES 
(1, 1),
(1, 3),
(1, 6),
(2, 4),
(2, 5),
(2, 9),
(3, 2),
(3, 7),
(3, 10);

-- 11. Insert workout templates
INSERT INTO workout_templates (user_id, name, description, icon, color, duration_minutes, exercises) VALUES 
(1, 'Push Day', 'Focus on pushing exercises for chest, shoulders, and triceps', 'dumbbell', '#4361ee', 60, '[{"name":"Barbell Bench Press","sets":[{"weight":60,"reps":12},{"weight":70,"reps":10},{"weight":80,"reps":8}]},{"name":"Shoulder Press","sets":[{"weight":15,"reps":12},{"weight":20,"reps":10}]},{"name":"Tricep Extension","sets":[{"weight":15,"reps":12},{"weight":15,"reps":12}]}]'),
(1, 'Pull Day', 'Focus on pulling exercises for back and biceps', 'dumbbell', '#7209b7', 60, '[{"name":"Pull-up","sets":[{"weight":0,"reps":8},{"weight":0,"reps":6}]},{"name":"Lat Pulldown","sets":[{"weight":50,"reps":12},{"weight":60,"reps":10}]},{"name":"Bicep Curl","sets":[{"weight":12,"reps":12},{"weight":15,"reps":10}]}]'),
(2, 'Full Body Workout', 'Complete full body routine', 'user', '#2a9d8f', 75, '[{"name":"Barbell Squat","sets":[{"weight":50,"reps":10},{"weight":60,"reps":8}]},{"name":"Push-up","sets":[{"weight":0,"reps":15},{"weight":0,"reps":12}]},{"name":"Pull-up","sets":[{"weight":0,"reps":6},{"weight":0,"reps":5}]},{"name":"Shoulder Press","sets":[{"weight":15,"reps":12},{"weight":20,"reps":10}]}]');

-- =============================
-- STEP 3: ADD ADDITIONAL INDEXES AND CONSTRAINTS
-- =============================

-- Add index for faster queries on commonly searched columns
CREATE INDEX idx_exercise_name ON exercise_library(exercise_name);
CREATE INDEX idx_workout_date ON workouts(created_at);
CREATE INDEX idx_user_exercises ON workout_exercises(user_id, exercise_name);
CREATE INDEX idx_user_goals ON goals(user_id, goal_type);

-- Add last_used column to user_favorite_exercises to track when exercises were last used
ALTER TABLE user_favorite_exercises ADD COLUMN last_used TIMESTAMP NULL;

-- Update the last_used based on the latest workout
UPDATE user_favorite_exercises ufe
JOIN exercise_library el ON ufe.exercise_id = el.id
JOIN workout_exercises we ON el.exercise_name = we.exercise_name AND ufe.user_id = we.user_id
JOIN workouts w ON we.workout_id = w.id
SET ufe.last_used = w.created_at
WHERE w.created_at = (
    SELECT MAX(w2.created_at)
    FROM workouts w2
    JOIN workout_exercises we2 ON w2.id = we2.workout_id
    WHERE we2.exercise_name = el.exercise_name AND w2.user_id = ufe.user_id
);

-- Add a view for easier access to workout statistics
CREATE OR REPLACE VIEW user_workout_stats AS
SELECT 
    u.id AS user_id,
    u.username,
    COUNT(w.id) AS total_workouts,
    SUM(w.duration_minutes) AS total_minutes,
    SUM(w.calories_burned) AS total_calories,
    AVG(w.rating) AS avg_rating,
    MAX(w.created_at) AS last_workout_date
FROM users u
LEFT JOIN workouts w ON u.id = w.user_id
GROUP BY u.id, u.username;

-- Add a view for exercise performance statistics
CREATE OR REPLACE VIEW exercise_performance_stats AS
SELECT 
    we.exercise_name,
    we.user_id,
    COUNT(DISTINCT we.workout_id) AS times_performed,
    MAX(es.weight) AS max_weight,
    AVG(es.reps) AS avg_reps,
    SUM(es.weight * es.reps) / COUNT(DISTINCT we.id) AS avg_volume_per_session
FROM workout_exercises we
JOIN exercise_sets es ON we.id = es.exercise_id
GROUP BY we.exercise_name, we.user_id;

-- Create a trigger to update user's last_active when they complete a workout
DELIMITER //
CREATE TRIGGER update_user_last_active AFTER INSERT ON workouts
FOR EACH ROW
BEGIN
    UPDATE users
    SET last_active = NOW()
    WHERE id = NEW.user_id;
END //
DELIMITER ;

-- Create a trigger to update goal completion status when current_value reaches target_value
DELIMITER //
CREATE TRIGGER update_goal_completion BEFORE UPDATE ON goals
FOR EACH ROW
BEGIN
    IF NEW.current_value >= NEW.target_value AND NEW.completed = FALSE THEN
        SET NEW.completed = TRUE;
        SET NEW.completed_at = NOW();
    END IF;
END //
DELIMITER ;

-- Add a procedure to reset all tables (useful for testing)
DELIMITER //
CREATE PROCEDURE reset_database()
BEGIN
    SET FOREIGN_KEY_CHECKS = 0;
    
    TRUNCATE TABLE exercise_sets;
    TRUNCATE TABLE workout_exercises;
    TRUNCATE TABLE user_favorite_exercises;
    TRUNCATE TABLE workouts;
    TRUNCATE TABLE workout_templates;
    TRUNCATE TABLE goals;
    TRUNCATE TABLE exercise_library;
    TRUNCATE TABLE equipment;
    TRUNCATE TABLE muscle_groups;
    TRUNCATE TABLE body_parts;
    TRUNCATE TABLE users;
    
    SET FOREIGN_KEY_CHECKS = 1;
END //
DELIMITER ;

-- Add a function to calculate a user's streak (consecutive days with workouts)
DELIMITER //
CREATE FUNCTION calculate_user_streak(user_id_param INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE streak INT DEFAULT 0;
    DECLARE current_date DATE;
    DECLARE workout_date DATE;
    DECLARE done BOOLEAN DEFAULT FALSE;
    
    DECLARE workout_cursor CURSOR FOR
        SELECT DATE(created_at) as workout_day
        FROM workouts
        WHERE user_id = user_id_param
        ORDER BY created_at DESC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET current_date = CURDATE();
    
    OPEN workout_cursor;
    
    read_loop: LOOP
        FETCH workout_cursor INTO workout_date;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        IF workout_date = current_date THEN
            SET streak = streak + 1;
            SET current_date = DATE_SUB(current_date, INTERVAL 1 DAY);
        ELSEIF workout_date = DATE_SUB(current_date, INTERVAL 1 DAY) THEN
            SET streak = streak + 1;
            SET current_date = workout_date;
        ELSE
            LEAVE read_loop;
        END IF;
    END LOOP;
    
    CLOSE workout_cursor;
    
    RETURN streak;
END //
DELIMITER ;

-- Final message to confirm completion
SELECT 'Database setup complete. The fitness tracking app database has been successfully initialized with tables, sample data, indexes, views, triggers, and procedures.' AS 'Setup Complete'; 