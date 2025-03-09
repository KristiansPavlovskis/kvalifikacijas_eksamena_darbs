# GYMVERSE Database Structure

This document outlines the database structure for the GYMVERSE fitness tracking application. The database is designed to efficiently store user data, workouts, exercises, goals, and more to power the fitness tracking functionality of the application.

## Setup Instructions

### Option 1: Using the Setup Script

1. Ensure you have PHP installed on your system
2. Update the database connection settings in `setup_database.php` if needed:
   ```php
   $host = "localhost";
   $username = "root";
   $password = "";
   $dbname = "gymverse";
   ```
3. Run the setup script from the command line:
   ```
   php setup_database.php
   ```
4. The script will create the database, all required tables, and populate them with sample data

### Option 2: Manual SQL Import

1. Create a database named `gymverse` in your MySQL server
2. Import the `database_setup.sql` file using phpMyAdmin or the MySQL command line:
   ```
   mysql -u username -p gymverse < database_setup.sql
   ```

## Database Tables Overview

The database consists of 11 main tables:

1. **users** - Stores user account information
2. **body_parts** - Categorizes body parts for exercise grouping
3. **muscle_groups** - Lists muscle groups associated with body parts
4. **equipment** - Catalogs exercise equipment types
5. **exercise_library** - Contains a library of exercises with details
6. **goals** - Tracks user fitness goals
7. **workouts** - Records completed workouts
8. **workout_exercises** - Links exercises to workouts
9. **exercise_sets** - Stores individual sets for each exercise
10. **user_favorite_exercises** - Tracks user's favorite exercises
11. **workout_templates** - Stores user-created workout templates

## Table Relationships

```
users
 ├── goals
 ├── workouts
 │    └── workout_exercises
 │         └── exercise_sets
 ├── user_favorite_exercises
 └── workout_templates

body_parts
 └── muscle_groups
      └── exercise_library
           ├── user_favorite_exercises
           └── (referenced by) workout_exercises

equipment
 └── exercise_library
```

## Detailed Table Structure

### 1. users

Stores user account and profile information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| username | VARCHAR(50) | Unique username |
| password | VARCHAR(255) | Hashed password |
| email | VARCHAR(100) | Unique email address |
| first_name | VARCHAR(50) | User's first name |
| last_name | VARCHAR(50) | User's last name |
| date_of_birth | DATE | User's birth date |
| gender | ENUM | 'male', 'female', 'other' |
| weight | DECIMAL(5,2) | User's weight in kg |
| height | DECIMAL(5,2) | User's height in cm |
| profile_image | VARCHAR(255) | Path to profile image |
| created_at | TIMESTAMP | Account creation date |
| last_active | TIMESTAMP | Last activity timestamp |

### 2. body_parts

Categorizes different body parts for exercise grouping.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| name | VARCHAR(50) | Body part name (e.g., Arms, Legs) |

### 3. muscle_groups

Lists specific muscle groups within body parts.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| name | VARCHAR(50) | Muscle group name (e.g., Biceps) |
| body_part_id | INT | Foreign key to body_parts.id |

### 4. equipment

Catalogs different types of exercise equipment.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| name | VARCHAR(50) | Equipment name |
| description | TEXT | Equipment description |
| icon | VARCHAR(50) | Font Awesome icon class |

### 5. exercise_library

Contains a comprehensive library of exercises.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| exercise_name | VARCHAR(100) | Exercise name |
| description | TEXT | Exercise description |
| muscle_group_id | INT | Foreign key to muscle_groups.id |
| equipment_id | INT | Foreign key to equipment.id |
| difficulty | ENUM | 'beginner', 'intermediate', 'advanced' |
| instructions | TEXT | Step-by-step instructions |
| video_url | VARCHAR(255) | Exercise demonstration video URL |
| image_url | VARCHAR(255) | Exercise image URL |
| popularity | INT | Popularity ranking (higher = more popular) |

### 6. goals

Tracks user-created fitness goals.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users.id |
| title | VARCHAR(100) | Goal title |
| description | TEXT | Goal description |
| goal_type | ENUM | 'weight', 'strength', 'endurance', 'workout', 'nutrition' |
| target_value | DECIMAL(10,2) | Target value to achieve |
| current_value | DECIMAL(10,2) | Current progress value |
| deadline | DATE | Goal deadline date |
| created_at | TIMESTAMP | Goal creation date |
| completed | BOOLEAN | Goal completion status |
| completed_at | TIMESTAMP | Completion date (if completed) |

### 7. workouts

Records user workout sessions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users.id |
| name | VARCHAR(100) | Workout name |
| workout_type | VARCHAR(50) | Type of workout |
| duration_minutes | INT | Workout duration in minutes |
| calories_burned | INT | Estimated calories burned |
| notes | TEXT | User notes about the workout |
| rating | INT | User rating of the workout |
| created_at | TIMESTAMP | Workout date and time |

### 8. workout_exercises

Links exercises to workout sessions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| workout_id | INT | Foreign key to workouts.id |
| user_id | INT | Foreign key to users.id |
| exercise_name | VARCHAR(100) | Name of exercise performed |
| exercise_order | INT | Order of exercise in the workout |
| notes | TEXT | Notes specific to this exercise |

### 9. exercise_sets

Stores individual sets for each exercise.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| exercise_id | INT | Foreign key to workout_exercises.id |
| user_id | INT | Foreign key to users.id |
| set_number | INT | Set number (1, 2, 3, etc.) |
| weight | DECIMAL(6,2) | Weight used (in kg) |
| reps | INT | Number of repetitions |
| rpe | INT | Rate of Perceived Exertion (1-10) |
| is_warmup | BOOLEAN | Whether this is a warmup set |
| note | TEXT | Notes specific to this set |

### 10. user_favorite_exercises

Tracks user's favorite exercises.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users.id |
| exercise_id | INT | Foreign key to exercise_library.id |
| added_at | TIMESTAMP | When exercise was favorited |
| last_used | TIMESTAMP | When exercise was last used |

### 11. workout_templates

Stores user-created workout templates.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users.id |
| name | VARCHAR(100) | Template name |
| description | TEXT | Template description |
| icon | VARCHAR(50) | Font Awesome icon class |
| color | VARCHAR(20) | Color hex code for UI |
| duration_minutes | INT | Estimated duration |
| created_at | TIMESTAMP | Creation date |
| exercises | JSON | JSON structure of exercises and sets |

## Additional Database Objects

### Views

1. **user_workout_stats** - Aggregated user workout statistics
2. **exercise_performance_stats** - Performance statistics for exercises

### Triggers

1. **update_user_last_active** - Updates user's last active timestamp when they complete a workout
2. **update_goal_completion** - Automatically marks goals as completed when target is reached

### Stored Procedures and Functions

1. **reset_database()** - Procedure to reset all tables (useful for testing)
2. **calculate_user_streak()** - Function to calculate a user's workout streak

## Sample Data

The setup script includes sample data for all tables, including:
- 3 user accounts
- 7 body parts and 13 muscle groups
- 9 equipment types
- 10 exercises in the exercise library
- 5 user goals
- 5 sample workouts with exercises and sets
- User favorites and workout templates

## Maintenance and Backup

It's recommended to:
1. Regularly backup the database
2. Run database optimizations periodically
3. Check for and update any outdated indexes

For any database-related questions or issues, please contact the development team. 