-- Create workout_templates table
CREATE TABLE IF NOT EXISTS workout_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_minutes INT DEFAULT 60,
    icon VARCHAR(50) DEFAULT 'dumbbell',
    color VARCHAR(20) DEFAULT '#4361ee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create template_exercises table
CREATE TABLE IF NOT EXISTS template_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    exercise_order INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES workout_templates(id) ON DELETE CASCADE
);

-- Create template_sets table
CREATE TABLE IF NOT EXISTS template_sets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exercise_id INT NOT NULL,
    set_number INT NOT NULL,
    weight DECIMAL(6,2) DEFAULT 0,
    reps INT DEFAULT 0,
    is_warmup TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exercise_id) REFERENCES template_exercises(id) ON DELETE CASCADE
);

-- Add template_id column to workouts table if it doesn't exist
ALTER TABLE workouts 
ADD COLUMN IF NOT EXISTS template_id INT NULL,
ADD FOREIGN KEY IF NOT EXISTS (template_id) REFERENCES workout_templates(id);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_template_user ON workout_templates(user_id, last_used);
CREATE INDEX IF NOT EXISTS idx_template_exercises ON template_exercises(template_id, exercise_order);
CREATE INDEX IF NOT EXISTS idx_template_sets ON template_sets(exercise_id, set_number);

-- Insert some sample template data
INSERT INTO workout_templates (user_id, name, description, duration_minutes, icon, color) 
VALUES 
(1, 'Full Body Strength', 'A complete full body workout focusing on compound movements', 60, 'dumbbell', '#4361ee'),
(1, 'Upper Body Focus', 'Chest, back, shoulders and arms workout', 45, 'user', '#2a9d8f'),
(1, 'Lower Body Power', 'Legs and core strength training', 50, 'bolt', '#e63946');

-- Insert exercises for Full Body template
SET @template_id = LAST_INSERT_ID() - 2;

INSERT INTO template_exercises (template_id, exercise_name, exercise_order, notes)
VALUES
(@template_id, 'Barbell Squat', 1, 'Focus on depth and keeping chest up'),
(@template_id, 'Bench Press', 2, 'Control the weight on the way down'),
(@template_id, 'Deadlift', 3, 'Maintain neutral spine'),
(@template_id, 'Pull-up', 4, 'Full range of motion'),
(@template_id, 'Shoulder Press', 5, 'Don\'t arch back');

-- Insert sets for exercises
SET @squat_id = LAST_INSERT_ID();
INSERT INTO template_sets (exercise_id, set_number, weight, reps, is_warmup)
VALUES
(@squat_id, 1, 45, 10, 1),
(@squat_id, 2, 95, 5, 1),
(@squat_id, 3, 135, 5, 0),
(@squat_id, 4, 135, 5, 0),
(@squat_id, 5, 135, 5, 0);

SET @bench_id = @squat_id + 1;
INSERT INTO template_sets (exercise_id, set_number, weight, reps, is_warmup)
VALUES
(@bench_id, 1, 45, 10, 1),
(@bench_id, 2, 75, 5, 1),
(@bench_id, 3, 95, 5, 0),
(@bench_id, 4, 95, 5, 0),
(@bench_id, 5, 95, 5, 0);

SET @deadlift_id = @squat_id + 2;
INSERT INTO template_sets (exercise_id, set_number, weight, reps, is_warmup)
VALUES
(@deadlift_id, 1, 95, 5, 1),
(@deadlift_id, 2, 135, 3, 1),
(@deadlift_id, 3, 185, 5, 0),
(@deadlift_id, 4, 185, 5, 0),
(@deadlift_id, 5, 185, 5, 0);

SET @pullup_id = @squat_id + 3;
INSERT INTO template_sets (exercise_id, set_number, weight, reps, is_warmup)
VALUES
(@pullup_id, 1, 0, 5, 0),
(@pullup_id, 2, 0, 5, 0),
(@pullup_id, 3, 0, 5, 0);

SET @press_id = @squat_id + 4;
INSERT INTO template_sets (exercise_id, set_number, weight, reps, is_warmup)
VALUES
(@press_id, 1, 45, 10, 1),
(@press_id, 2, 65, 5, 0),
(@press_id, 3, 65, 5, 0),
(@press_id, 4, 65, 5, 0); 