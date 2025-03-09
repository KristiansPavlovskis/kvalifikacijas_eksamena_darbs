-- Create muscle groups table
CREATE TABLE IF NOT EXISTS muscle_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    body_part VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create exercise library table
CREATE TABLE IF NOT EXISTS exercise_library (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exercise_name VARCHAR(100) NOT NULL,
    alternative_names TEXT,
    description TEXT,
    muscle_group_id INT,
    equipment_id INT,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate',
    popularity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (muscle_group_id) REFERENCES muscle_groups(id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id),
    UNIQUE KEY unique_exercise (exercise_name)
);

-- Create user favorite exercises table
CREATE TABLE IF NOT EXISTS user_favorite_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exercise_id) REFERENCES exercise_library(id),
    UNIQUE KEY unique_favorite (user_id, exercise_id)
);

-- Insert basic muscle groups
INSERT INTO muscle_groups (name, body_part) VALUES
('Chest', 'Upper Body'),
('Upper Back', 'Upper Body'),
('Lower Back', 'Upper Body'),
('Shoulders', 'Upper Body'),
('Biceps', 'Arms'),
('Triceps', 'Arms'),
('Forearms', 'Arms'),
('Abs', 'Core'),
('Obliques', 'Core'),
('Lower Abs', 'Core'),
('Quads', 'Lower Body'),
('Hamstrings', 'Lower Body'),
('Calves', 'Lower Body'),
('Glutes', 'Lower Body');

-- Insert basic equipment
INSERT INTO equipment (name, icon) VALUES
('Barbell', 'fa-weight-hanging'),
('Dumbbell', 'fa-dumbbell'),
('Kettlebell', 'fa-bell'),
('Machine', 'fa-robot'),
('Cable', 'fa-grip-lines-vertical'),
('Bodyweight', 'fa-child'),
('Resistance Band', 'fa-wave-square'),
('Smith Machine', 'fa-vector-square'),
('Medicine Ball', 'fa-circle'),
('Foam Roller', 'fa-circle-notch');

-- Insert some common exercises
INSERT INTO exercise_library (exercise_name, muscle_group_id, equipment_id, difficulty, popularity) VALUES
('Bench Press', (SELECT id FROM muscle_groups WHERE name = 'Chest'), (SELECT id FROM equipment WHERE name = 'Barbell'), 'intermediate', 100),
('Squat', (SELECT id FROM muscle_groups WHERE name = 'Quads'), (SELECT id FROM equipment WHERE name = 'Barbell'), 'intermediate', 100),
('Deadlift', (SELECT id FROM muscle_groups WHERE name = 'Lower Back'), (SELECT id FROM equipment WHERE name = 'Barbell'), 'intermediate', 100),
('Pull-up', (SELECT id FROM muscle_groups WHERE name = 'Upper Back'), (SELECT id FROM equipment WHERE name = 'Bodyweight'), 'intermediate', 90),
('Push-up', (SELECT id FROM muscle_groups WHERE name = 'Chest'), (SELECT id FROM equipment WHERE name = 'Bodyweight'), 'beginner', 90),
('Dumbbell Row', (SELECT id FROM muscle_groups WHERE name = 'Upper Back'), (SELECT id FROM equipment WHERE name = 'Dumbbell'), 'beginner', 85),
('Shoulder Press', (SELECT id FROM muscle_groups WHERE name = 'Shoulders'), (SELECT id FROM equipment WHERE name = 'Dumbbell'), 'intermediate', 85),
('Bicep Curl', (SELECT id FROM muscle_groups WHERE name = 'Biceps'), (SELECT id FROM equipment WHERE name = 'Dumbbell'), 'beginner', 80),
('Tricep Extension', (SELECT id FROM muscle_groups WHERE name = 'Triceps'), (SELECT id FROM equipment WHERE name = 'Cable'), 'beginner', 80),
('Leg Press', (SELECT id FROM muscle_groups WHERE name = 'Quads'), (SELECT id FROM equipment WHERE name = 'Machine'), 'beginner', 75),
('Lat Pulldown', (SELECT id FROM muscle_groups WHERE name = 'Upper Back'), (SELECT id FROM equipment WHERE name = 'Cable'), 'beginner', 75),
('Romanian Deadlift', (SELECT id FROM muscle_groups WHERE name = 'Hamstrings'), (SELECT id FROM equipment WHERE name = 'Barbell'), 'intermediate', 70),
('Plank', (SELECT id FROM muscle_groups WHERE name = 'Abs'), (SELECT id FROM equipment WHERE name = 'Bodyweight'), 'beginner', 70),
('Calf Raise', (SELECT id FROM muscle_groups WHERE name = 'Calves'), (SELECT id FROM equipment WHERE name = 'Machine'), 'beginner', 65),
('Face Pull', (SELECT id FROM muscle_groups WHERE name = 'Shoulders'), (SELECT id FROM equipment WHERE name = 'Cable'), 'beginner', 65);

-- Add indexes for performance
CREATE INDEX idx_exercise_search ON exercise_library(exercise_name, alternative_names(100));
CREATE INDEX idx_exercise_popularity ON exercise_library(popularity);
CREATE INDEX idx_workout_exercises_user ON workout_exercises(user_id, created_at);
CREATE INDEX idx_exercise_sets_exercise ON exercise_sets(exercise_id, created_at); 