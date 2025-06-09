-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 09, 2025 at 06:36 AM
-- Server version: 9.2.0
-- PHP Version: 8.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gymverse_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `body_measurements`
--

CREATE TABLE `body_measurements` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `body_fat` decimal(4,2) DEFAULT NULL,
  `chest` decimal(4,2) DEFAULT NULL,
  `arm_left_bicep` decimal(5,2) DEFAULT NULL,
  `arm_right_bicep` decimal(5,2) DEFAULT NULL,
  `arm_left_forearm` decimal(5,2) DEFAULT NULL,
  `arm_right_forearm` decimal(5,2) DEFAULT NULL,
  `waist` decimal(4,2) DEFAULT NULL,
  `shoulders` decimal(4,2) DEFAULT NULL,
  `leg_left_quad` decimal(5,2) DEFAULT NULL,
  `leg_right_quad` decimal(5,2) DEFAULT NULL,
  `leg_left_calf` decimal(5,2) DEFAULT NULL,
  `leg_right_calf` decimal(5,2) DEFAULT NULL,
  `hips` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `measurement_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `body_measurements`
--

INSERT INTO `body_measurements` (`id`, `user_id`, `weight`, `body_fat`, `chest`, `arm_left_bicep`, `arm_right_bicep`, `arm_left_forearm`, `arm_right_forearm`, `waist`, `shoulders`, `leg_left_quad`, `leg_right_quad`, `leg_left_calf`, `leg_right_calf`, `hips`, `notes`, `measurement_date`, `created_at`) VALUES
(8, 13, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 'cecewefwe', '2025-05-25', '2025-05-25 10:46:55'),
(9, 13, 14.00, 14.00, 14.00, 14.00, 14.00, 14.00, 14.00, 41.00, 41.00, 14.00, 14.00, 14.00, 14.00, 414.00, NULL, '2025-05-25', '2025-05-25 18:42:08'),
(10, 13, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, 20.00, NULL, '2025-05-25', '2025-05-25 18:43:49'),
(11, 13, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, 55.00, NULL, '2025-05-26', '2025-05-26 19:41:49'),
(13, 13, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, NULL, '2025-05-26', '2025-05-26 20:01:26'),
(14, 13, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, NULL, '2025-05-26', '2025-05-26 20:01:43'),
(15, 13, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, 67.00, NULL, '2025-05-26', '2025-05-26 20:06:49'),
(16, 14, 123.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-05-28', '2025-05-28 11:26:35'),
(17, 14, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, 15.00, NULL, '2025-05-28', '2025-05-28 11:26:43'),
(18, 16, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, 13.00, NULL, '2025-05-30', '2025-05-30 10:13:44'),
(19, 16, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, NULL, '2025-05-30', '2025-05-30 10:13:59'),
(20, 18, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-06-01', '2025-06-01 17:08:29'),
(21, 18, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, NULL, '2025-06-27', '2025-06-01 17:08:44'),
(22, 1, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, NULL, '2025-06-02', '2025-06-02 00:48:24'),
(25, 20, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-06-02', '2025-06-02 05:01:57'),
(26, 20, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, NULL, '2025-06-02', '2025-06-02 05:02:09'),
(27, 21, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-06-02', '2025-06-02 05:14:21'),
(28, 21, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 22.00, 222.00, 222.00, NULL, '2025-06-02', '2025-06-02 05:14:34'),
(29, 23, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-06-02', '2025-06-02 08:06:48'),
(30, 23, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, NULL, '2025-06-02', '2025-06-02 08:06:59'),
(33, 1, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, 66.00, NULL, '2025-06-08', '2025-06-08 10:48:32'),
(34, 1, 66.00, 66.00, 66.00, 56.00, 55.00, 55.00, 55.00, 66.00, 66.00, 55.00, 55.00, 55.00, 55.00, 66.00, NULL, '2025-06-08', '2025-06-08 10:51:00'),
(35, 1, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, 44.00, NULL, '2025-06-08', '2025-06-08 10:56:09'),
(36, 28, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, NULL, '2025-06-08', '2025-06-08 16:20:14'),
(37, 28, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, 33.00, NULL, '2025-06-08', '2025-06-08 16:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `description`, `icon`, `created_at`) VALUES
(1, 'Barbell', 'A long metal bar with weights attached at each end', NULL, '2025-03-14 08:47:03'),
(2, 'Dumbbell', 'A short bar with a weight at each end', NULL, '2025-03-14 08:47:03'),
(3, 'Kettlebell', 'A cast-iron weight with a handle', NULL, '2025-03-14 08:47:03'),
(4, 'Resistance Band', 'Elastic bands that provide resistance', NULL, '2025-03-14 08:47:03'),
(5, 'Machine', 'Fixed exercise equipment with cables or weight stacks', NULL, '2025-03-14 08:47:03'),
(6, 'Cable Machine', 'Adjustable pulley system with weight stack', NULL, '2025-03-14 08:47:03'),
(7, 'Bench', 'Flat or adjustable bench for exercises', NULL, '2025-03-14 08:47:03'),
(8, 'Bodyweight', 'Using your own body weight as resistance', NULL, '2025-03-14 08:47:03'),
(9, 'Medicine Ball', 'Weighted ball used for exercises', NULL, '2025-03-14 08:47:03'),
(10, 'Swiss Ball', 'Large inflatable ball for stability exercises', NULL, '2025-03-14 08:47:03'),
(11, 'Pull-up Bar', 'Horizontal bar for pull-ups and chin-ups', NULL, '2025-03-14 08:47:03'),
(12, 'TRX/Suspension Trainer', 'Straps for bodyweight resistance training', NULL, '2025-03-14 08:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `exercise_type` varchar(255) DEFAULT NULL,
  `equipment` varchar(255) DEFAULT NULL,
  `primary_muscle` varchar(255) DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced') DEFAULT 'intermediate',
  `instructions` text,
  `common_mistakes` text,
  `benefits` text,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `description`, `exercise_type`, `equipment`, `primary_muscle`, `difficulty`, `instructions`, `common_mistakes`, `benefits`, `video_url`, `created_at`, `updated_at`) VALUES
(1, 'Bench Press', 'The bench press is a compound exercise that develops the muscles of the chest. It also involves the triceps and the front deltoids, making it an excellent exercise for developing upper body strength.', 'Strength Training', 'Barbell', 'Chest', 'intermediate', '1. Lie on a flat bench with your feet flat on the floor.\n2. Grip the barbell slightly wider than shoulder-width.\n3. Lower the bar to your mid-chest.\n4. Press the bar upward until your arms are extended.\n5. Repeat for the desired number of repetitions.', '1. Arching the back excessively - keep natural spine alignment.\n2. Bouncing the bar off the chest - maintain control.\n3. Uneven bar path - keep movement straight up and down.\n4. Lifting buttocks off bench - maintain five points of contact.', '1. Builds upper body strength and muscle mass.\n2. Develops pushing power for athletic performance.\n3. Improves chest, shoulder, and triceps muscle definition.\n4. Strengthens stabilizing muscles for better posture.', 'https://www.youtube.com/embed/rT7DgCr-3pg', '2025-04-07 12:01:10', '2025-04-07 12:05:15'),
(2, 'Squat', 'The squat is a compound, full-body exercise that trains primarily the muscles of the thighs, hips, and buttocks. It also strengthens the bones, ligaments, and tendons throughout the lower body.', 'Strength Training', 'Barbell', 'Quadriceps', 'intermediate', '1. Stand with feet shoulder-width apart.\n2. Keep your chest up and back straight.\n3. Bend at the knees and hips to lower your body.\n4. Lower until thighs are parallel to the ground.\n5. Push through your heels to return to standing position.', '1. Knees caving inward - keep knees aligned with toes.\n2. Not going deep enough - aim for at least parallel depth.\n3. Rounding the lower back - maintain neutral spine.\n4. Lifting heels off the floor - keep feet firmly planted.', '1. Builds lower body strength, especially quads, hamstrings, and glutes.\n2. Improves core stability and balance.\n3. Increases functional movement patterns for daily activities.\n4. Boosts hormone production for overall muscle growth.', 'https://www.youtube.com/embed/ultWZbUMPL8', '2025-04-07 12:01:10', '2025-04-07 12:08:33'),
(3, 'Deadlift', 'The deadlift is a weight training exercise in which a loaded barbell is lifted from the ground to hip level. It is one of the three powerlifting exercises, along with the squat and bench press.', 'Strength Training', 'Barbell', 'Lower Back', 'advanced', '1. Stand with feet hip-width apart, barbell over mid-foot.\n2. Bend at hips and knees, grip the bar with hands shoulder-width apart.\n3. Keep back flat, chest up, and arms straight.\n4. Drive through heels and stand up with the weight.\n5. Return the weight to the floor in a controlled manner.', '1. Rounding the back - maintain a flat back throughout the movement.\n2. Starting with the bar too far from body - keep it over midfoot.\n3. Jerking the weight off the floor - apply force gradually.\n4. Looking up excessively - keep neck in neutral position.', '1. Develops posterior chain strength (hamstrings, glutes, back).\n2. Improves grip strength and forearm development.\n3. Increases overall functional strength for daily activities.\n4. Engages more muscles than almost any other exercise.', 'https://www.youtube.com/embed/op9kVnSso6Q', '2025-04-07 12:01:10', '2025-04-07 12:08:33'),
(4, 'Pull-Up', 'The pull-up is a closed-chain exercise where the body is suspended by the hands and pulls up. As a compound exercise, it works multiple muscle groups including the latissimus dorsi and biceps.', 'Bodyweight', 'Pull-up Bar', 'Back', 'advanced', '1. Grip the pull-up bar with palms facing away from you.\n2. Hang with arms fully extended, shoulders engaged.\n3. Pull your body up until chin is over the bar.\n4. Lower with control to starting position.\n5. Repeat for desired number of repetitions.', '1. Insufficient range of motion - go from full hang to chin over bar.\n2. Excessive kipping or swinging - maintain controlled movement.\n3. Poor scapular control - engage shoulder blades properly.\n4. Relying too much on biceps - engage lats and back muscles.', '1. Builds upper back, latissimus dorsi, and bicep strength.\n2. Improves grip strength and forearm development.\n3. Develops functional pulling strength.\n4. Enhances shoulder stability and posture.', 'https://www.youtube.com/embed/eGo4IYlbE5g', '2025-04-07 12:01:10', '2025-04-07 12:08:33'),
(5, 'Push-Up', 'The push-up is a classic bodyweight exercise that builds strength in the chest, shoulders, triceps, and core. It\'s highly versatile and can be modified for different fitness levels.', 'Bodyweight', 'None', 'Chest', 'beginner', '1. Begin in plank position with hands slightly wider than shoulders.\n2. Keep your body in a straight line from head to heels.\n3. Lower your body until chest nearly touches the floor.\n4. Push yourself back up to the starting position.\n5. Repeat while maintaining proper form.', '1. Sagging hips - keep your body in a straight line.\n2. Incomplete range of motion - lower chest to near ground.\n3. Flaring elbows excessively - keep them at about 45 degrees.\n4. Poor head position - maintain neutral neck alignment.', '1. Strengthens chest, shoulders, and triceps muscles.\n2. Improves core stability and upper body endurance.\n3. Enhances functional pushing strength for daily activities.\n4. Can be modified for various fitness levels.', 'https://www.youtube.com/embed/IODxDxX7oi4', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(6, 'Plank', 'The plank is an isometric core exercise that involves maintaining a position similar to a push-up for the maximum possible time. It strengthens the core, shoulders, arms, and glutes.', 'Bodyweight', 'None', 'Core', 'beginner', '1. Begin in forearm plank position, elbows aligned under shoulders.\n2. Keep your body in a straight line from head to heels.\n3. Engage your core, glutes, and quads.\n4. Hold the position while breathing normally.\n5. Hold for desired time, rest, and repeat if desired.', '1. Sagging or hiking hips - maintain a straight line from head to heels.\n2. Holding breath - breathe normally throughout the exercise.\n3. Dropping head - keep neck in neutral position.\n4. Placing hands too far forward - elbows should be under shoulders.', '1. Strengthens core muscles, including abs and lower back.\n2. Improves posture and spinal alignment.\n3. Enhances stability for other exercises and daily movements.\n4. Helps prevent lower back pain and injury.', 'https://www.youtube.com/embed/pSHjTRCQxIw', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(7, 'Lunges', 'Lunges are a lower body exercise that targets the quadriceps, hamstrings, and glutes. They also improve balance, flexibility, and core strength while simulating everyday movement patterns.', 'Bodyweight', 'None', 'Quadriceps', 'beginner', '1. Stand tall with feet hip-width apart.\n2. Step forward with one leg and lower your body.\n3. Descend until both knees form 90-degree angles.\n4. Push through front heel to return to starting position.\n5. Repeat with opposite leg for desired repetitions.', '1. Stepping too short - ensure front knee is at 90-degree angle.\n2. Front knee caving inward - maintain alignment with foot.\n3. Leaning forward excessively - keep torso upright.\n4. Poor balance - engage core throughout movement.', '1. Builds lower body strength in quads, hamstrings, and glutes.\n2. Improves balance, coordination, and stability.\n3. Enhances functional movement for daily activities.\n4. Helps correct muscle imbalances between legs.', 'https://www.youtube.com/embed/QOVaHwm-Q6U', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(8, 'Dumbbell Shoulder Press', 'The dumbbell shoulder press is an upper body compound exercise that targets the deltoid muscles. It also engages the triceps and core, making it excellent for developing shoulder strength and stability.', 'Strength Training', 'Dumbbells', 'Shoulders', 'intermediate', '1. Sit on a bench with back support, holding dumbbells at shoulder height.\n2. Keep wrists stacked over elbows, palms facing forward.\n3. Press dumbbells upward until arms are fully extended overhead.\n4. Lower dumbbells back to shoulder level with control.\n5. Repeat for desired number of repetitions.', '1. Arching lower back - maintain neutral spine.\n2. Using momentum - control the weight throughout.\n3. Incomplete range of motion - aim for full extension.\n4. Uneven pressing - ensure both arms move at same pace.', '1. Strengthens shoulder muscles and upper trapezius.\n2. Improves overhead pressing strength and stability.\n3. Enhances shoulder joint mobility when done properly.\n4. Develops balanced upper body musculature.', 'https://www.youtube.com/embed/qEwKCR5JCog', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(9, 'Bicep Curls', 'The bicep curl is an isolation exercise that targets the biceps brachii muscle. This exercise is effective for developing arm strength and definition in the anterior upper arm.', 'Strength Training', 'Dumbbells', 'Biceps', 'beginner', '1. Stand with feet shoulder-width apart, holding dumbbells at your sides.\n2. Keep elbows close to your torso and palms facing forward.\n3. Curl the weights up toward your shoulders.\n4. Squeeze biceps at the top, then lower with control.\n5. Repeat for desired number of repetitions.', '1. Swinging the body - keep torso still throughout.\n2. Moving elbows - keep them fixed at sides.\n3. Using too much weight - focus on proper form.\n4. Incomplete range of motion - fully extend and contract.', '1. Builds bicep muscle size and strength.\n2. Improves arm endurance for carrying activities.\n3. Enhances grip strength when using proper form.\n4. Complements pulling exercises in a balanced routine.', 'https://www.youtube.com/embed/ykJmrZ5v0Oo', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(10, 'Jumping Jacks', 'Jumping jacks are a full-body cardiovascular exercise that works the heart, lungs, and muscles throughout your body. This classic calisthenic movement improves coordination and burns calories effectively.', 'Cardio', 'None', 'Full Body', 'beginner', '1. Stand upright with feet together and arms at your sides.\n2. Jump to spread feet shoulder-width apart while raising arms overhead.\n3. Jump again to return to starting position.\n4. Repeat at a quick, consistent pace.\n5. Continue for desired duration or repetitions.', '1. Landing heavily - aim for soft, controlled landings.\n2. Limited range of motion - fully extend arms and legs.\n3. Poor posture - maintain an upright position.\n4. Uncontrolled movements - maintain rhythm and form.', '1. Improves cardiovascular fitness and endurance.\n2. Increases calorie burn and metabolic rate.\n3. Enhances coordination and full-body mobility.\n4. Serves as an effective warm-up for more intense exercise.', 'https://www.youtube.com/embed/c4DAnQ6DtF8', '2025-04-07 12:01:10', '2025-04-07 12:10:21'),
(11, 'Russian Twisterss2', 'The Russian twist is a core exercise that targets the obliques and deep abdominal muscles. It involves twisting the torso from side to side while maintaining a seated position with elevated feet.', 'Strength Training', 'None', 'Obliques', 'intermediate', '1. Sit on the floor with knees bent and feet elevated slightly.\r\n2. Lean back to create a 45-degree angle with the floor.\r\n3. Hold hands together or with a weight in front of chest.\r\n4. Twist torso to the right, then to the left.\r\n5. Continue alternating sides for desired repetitions.', '1. Rounding the back - maintain straight spine throughout.\r\n2. Moving too quickly - focus on controlled rotation.\r\n3. Not rotating far enough - aim for full oblique engagement.\r\n4. Lifting feet too high - keep them close to the ground.', '1. Strengthens obliques and rotational core muscles.\r\n2. Improves trunk stability and rotational power.\r\n3. Enhances sports performance for rotational movements.\r\n4. Helps develop a stronger, more defined midsection.', 'https://www.youtube.com/embed/wkD8rjkodUI', '2025-04-07 12:01:10', '2025-05-25 18:29:59'),
(15, 'test', '', 'Strength Training', '', '', 'advanced', '', '', '', '', '2025-05-24 15:35:25', '2025-05-25 18:30:01'),
(17, 'dqqwdqwd', 'qwdqwdqwd', 'strength', '', '', 'intermediate', '', '', '', '', '2025-06-02 00:39:51', '2025-06-02 00:39:51'),
(18, 'jrjrjtrt', 'jrtjrtjrtj', 'cardio', '', '', 'intermediate', '', '', '', '', '2025-06-02 10:55:22', '2025-06-02 10:55:22');

-- --------------------------------------------------------

--
-- Table structure for table `exercise_sets`
--

CREATE TABLE `exercise_sets` (
  `id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `user_id` int NOT NULL,
  `set_number` int NOT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `reps` int DEFAULT NULL,
  `rpe` int DEFAULT NULL,
  `is_warmup` tinyint(1) DEFAULT '0',
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exercise_sets`
--

INSERT INTO `exercise_sets` (`id`, `exercise_id`, `user_id`, `set_number`, `weight`, `reps`, `rpe`, `is_warmup`, `note`, `created_at`) VALUES
(1, 1, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(2, 1, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(3, 1, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(4, 2, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(5, 2, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(6, 2, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(7, 2, 1, 4, 10.00, 8, 7, 0, NULL, '2025-04-16 12:45:54'),
(8, 3, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(9, 3, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(10, 3, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(11, 4, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(12, 4, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(13, 4, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(14, 4, 1, 4, 10.00, 8, 7, 0, NULL, '2025-04-16 12:49:42'),
(15, 5, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 13:02:39'),
(16, 5, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 13:02:39'),
(17, 5, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 13:02:39'),
(18, 6, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(19, 6, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(20, 6, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(21, 7, 1, 1, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(22, 7, 1, 2, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(23, 7, 1, 3, 10.00, 8, 7, 0, NULL, '2025-04-16 13:03:29'),
(24, 8, 1, 1, 100.00, 1, 0, 0, NULL, '2025-04-21 11:37:30'),
(25, 8, 1, 2, 50.00, 12, 0, 0, NULL, '2025-04-21 11:37:30'),
(26, 8, 1, 3, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(27, 9, 1, 1, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(28, 9, 1, 2, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(29, 9, 1, 3, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(30, 10, 1, 1, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(31, 10, 1, 2, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(32, 10, 1, 3, 50.00, 7, 0, 0, NULL, '2025-04-21 11:37:30'),
(33, 11, 1, 1, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(34, 11, 1, 2, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(35, 11, 1, 3, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(36, 12, 1, 1, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(37, 12, 1, 2, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(38, 12, 1, 3, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(39, 13, 1, 1, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(40, 13, 1, 2, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(41, 13, 1, 3, 50.00, 23, 0, 0, NULL, '2025-04-22 07:09:13'),
(42, 66, 1, 1, 118.00, 12, 6, 1, NULL, '2025-05-14 06:47:21'),
(43, 66, 1, 2, 73.00, 12, 8, 0, NULL, '2025-05-14 06:47:21'),
(44, 66, 1, 3, 111.00, 11, 8, 0, NULL, '2025-05-14 06:47:21'),
(45, 67, 1, 1, 82.00, 10, 9, 1, NULL, '2025-05-14 06:47:21'),
(46, 67, 1, 2, 82.00, 11, 6, 0, NULL, '2025-05-14 06:47:21'),
(47, 67, 1, 3, 67.00, 9, 8, 0, NULL, '2025-05-14 06:47:21'),
(48, 68, 1, 1, 60.00, 10, 8, 1, NULL, '2025-05-14 06:47:21'),
(49, 68, 1, 2, 116.00, 9, 9, 0, NULL, '2025-05-14 06:47:21'),
(50, 68, 1, 3, 90.00, 11, 8, 0, NULL, '2025-05-14 06:47:21'),
(51, 69, 1, 1, 60.00, 11, 6, 1, NULL, '2025-05-14 06:47:21'),
(52, 69, 1, 2, 90.00, 10, 6, 0, NULL, '2025-05-14 06:47:21'),
(53, 69, 1, 3, 87.00, 11, 9, 0, NULL, '2025-05-14 06:47:21'),
(54, 70, 1, 1, 97.00, 10, 6, 1, NULL, '2025-05-14 06:47:21'),
(55, 70, 1, 2, 82.00, 8, 8, 0, NULL, '2025-05-14 06:47:21'),
(56, 70, 1, 3, 78.00, 8, 7, 0, NULL, '2025-05-14 06:47:21'),
(57, 71, 1, 1, 65.00, 10, 6, 1, NULL, '2025-05-14 06:47:21'),
(58, 71, 1, 2, 112.00, 9, 9, 0, NULL, '2025-05-14 06:47:21'),
(59, 71, 1, 3, 120.00, 9, 6, 0, NULL, '2025-05-14 06:47:21'),
(60, 72, 1, 1, 72.00, 9, 9, 1, NULL, '2025-05-14 06:47:21'),
(61, 72, 1, 2, 84.00, 8, 6, 0, NULL, '2025-05-14 06:47:21'),
(62, 72, 1, 3, 69.00, 8, 8, 0, NULL, '2025-05-14 06:47:21'),
(63, 81, 1, 1, 80.00, 8, 7, 1, NULL, '2025-05-14 06:49:18'),
(64, 81, 1, 2, 78.00, 12, 7, 0, NULL, '2025-05-14 06:49:18'),
(65, 81, 1, 3, 106.00, 9, 6, 0, NULL, '2025-05-14 06:49:18'),
(66, 82, 1, 1, 118.00, 10, 7, 1, NULL, '2025-05-14 06:49:18'),
(67, 82, 1, 2, 82.00, 11, 9, 0, NULL, '2025-05-14 06:49:18'),
(68, 82, 1, 3, 73.00, 8, 7, 0, NULL, '2025-05-14 06:49:18'),
(69, 83, 1, 1, 67.00, 11, 7, 1, NULL, '2025-05-14 06:49:19'),
(70, 83, 1, 2, 71.00, 8, 6, 0, NULL, '2025-05-14 06:49:19'),
(71, 83, 1, 3, 108.00, 11, 7, 0, NULL, '2025-05-14 06:49:19'),
(72, 84, 1, 1, 93.00, 12, 6, 1, NULL, '2025-05-14 06:49:19'),
(73, 84, 1, 2, 110.00, 8, 9, 0, NULL, '2025-05-14 06:49:19'),
(74, 84, 1, 3, 83.00, 12, 7, 0, NULL, '2025-05-14 06:49:19'),
(75, 80, 1, 1, 65.00, 9, 8, 1, NULL, '2025-05-14 06:49:22'),
(76, 80, 1, 2, 67.00, 8, 7, 0, NULL, '2025-05-14 06:49:22'),
(77, 80, 1, 3, 87.00, 10, 9, 0, NULL, '2025-05-14 06:49:22'),
(78, 73, 1, 1, 67.00, 10, 6, 1, NULL, '2025-05-14 06:49:25'),
(79, 73, 1, 2, 118.00, 8, 9, 0, NULL, '2025-05-14 06:49:25'),
(80, 73, 1, 3, 73.00, 9, 8, 0, NULL, '2025-05-14 06:49:25'),
(81, 74, 1, 1, 73.00, 9, 9, 1, NULL, '2025-05-14 06:49:25'),
(82, 74, 1, 2, 62.00, 10, 6, 0, NULL, '2025-05-14 06:49:25'),
(83, 74, 1, 3, 96.00, 8, 8, 0, NULL, '2025-05-14 06:49:25'),
(84, 75, 1, 1, 93.00, 8, 6, 1, NULL, '2025-05-14 06:49:25'),
(85, 75, 1, 2, 84.00, 12, 7, 0, NULL, '2025-05-14 06:49:25'),
(86, 75, 1, 3, 101.00, 9, 9, 0, NULL, '2025-05-14 06:49:25'),
(87, 76, 1, 1, 113.00, 10, 8, 1, NULL, '2025-05-14 06:49:25'),
(88, 76, 1, 2, 68.00, 12, 6, 0, NULL, '2025-05-14 06:49:25'),
(89, 76, 1, 3, 109.00, 9, 6, 0, NULL, '2025-05-14 06:49:25'),
(90, 77, 1, 1, 119.00, 11, 8, 1, NULL, '2025-05-14 06:49:25'),
(91, 77, 1, 2, 102.00, 9, 8, 0, NULL, '2025-05-14 06:49:25'),
(92, 77, 1, 3, 85.00, 8, 8, 0, NULL, '2025-05-14 06:49:25'),
(93, 78, 1, 1, 103.00, 11, 6, 1, NULL, '2025-05-14 06:49:25'),
(94, 78, 1, 2, 114.00, 10, 6, 0, NULL, '2025-05-14 06:49:25'),
(95, 78, 1, 3, 67.00, 11, 9, 0, NULL, '2025-05-14 06:49:25'),
(96, 79, 1, 1, 81.00, 12, 6, 1, NULL, '2025-05-14 06:49:25'),
(97, 79, 1, 2, 93.00, 10, 7, 0, NULL, '2025-05-14 06:49:25'),
(98, 79, 1, 3, 89.00, 9, 7, 0, NULL, '2025-05-14 06:49:25'),
(99, 48, 1, 1, 111.00, 9, 6, 1, NULL, '2025-05-17 16:42:03'),
(100, 48, 1, 2, 110.00, 9, 9, 0, NULL, '2025-05-17 16:42:03'),
(101, 48, 1, 3, 60.00, 10, 8, 0, NULL, '2025-05-17 16:42:03'),
(102, 49, 1, 1, 67.00, 12, 9, 1, NULL, '2025-05-17 16:42:03'),
(103, 49, 1, 2, 82.00, 8, 6, 0, NULL, '2025-05-17 16:42:03'),
(104, 49, 1, 3, 72.00, 12, 9, 0, NULL, '2025-05-17 16:42:03'),
(108, 86, 13, 1, 94.00, 10, 7, 1, NULL, '2025-05-25 19:48:01'),
(109, 86, 13, 2, 120.00, 8, 9, 0, NULL, '2025-05-25 19:48:01'),
(110, 86, 13, 3, 72.00, 8, 8, 0, NULL, '2025-05-25 19:48:01'),
(111, 87, 13, 1, 103.00, 8, 7, 1, NULL, '2025-05-25 19:48:01'),
(112, 87, 13, 2, 69.00, 11, 7, 0, NULL, '2025-05-25 19:48:01'),
(113, 87, 13, 3, 61.00, 8, 7, 0, NULL, '2025-05-25 19:48:01'),
(114, 88, 13, 1, 94.00, 11, 6, 1, NULL, '2025-05-25 19:48:01'),
(115, 88, 13, 2, 77.00, 12, 6, 0, NULL, '2025-05-25 19:48:01'),
(116, 88, 13, 3, 103.00, 9, 6, 0, NULL, '2025-05-25 19:48:01'),
(117, 89, 14, 1, 64.00, 12, 6, 1, NULL, '2025-05-28 11:25:08'),
(118, 89, 14, 2, 71.00, 12, 8, 0, NULL, '2025-05-28 11:25:08'),
(119, 89, 14, 3, 69.00, 12, 7, 0, NULL, '2025-05-28 11:25:08'),
(120, 94, 15, 1, 73.00, 10, 9, 1, NULL, '2025-05-29 17:23:10'),
(121, 94, 15, 2, 111.00, 9, 7, 0, NULL, '2025-05-29 17:23:10'),
(122, 94, 15, 3, 83.00, 11, 7, 0, NULL, '2025-05-29 17:23:10'),
(123, 95, 16, 1, 70.00, 8, 7, 1, NULL, '2025-05-30 10:12:09'),
(124, 95, 16, 2, 80.00, 10, 7, 0, NULL, '2025-05-30 10:12:09'),
(125, 95, 16, 3, 86.00, 10, 6, 0, NULL, '2025-05-30 10:12:09'),
(126, 96, 16, 1, 77.00, 12, 6, 1, NULL, '2025-05-30 10:12:09'),
(127, 96, 16, 2, 89.00, 9, 7, 0, NULL, '2025-05-30 10:12:09'),
(128, 96, 16, 3, 102.00, 10, 9, 0, NULL, '2025-05-30 10:12:09'),
(129, 97, 16, 1, 61.00, 11, 9, 1, NULL, '2025-05-30 19:22:51'),
(130, 97, 16, 2, 119.00, 11, 8, 0, NULL, '2025-05-30 19:22:51'),
(131, 97, 16, 3, 113.00, 12, 7, 0, NULL, '2025-05-30 19:22:51'),
(132, 98, 16, 1, 91.00, 8, 7, 1, NULL, '2025-05-30 19:22:51'),
(133, 98, 16, 2, 68.00, 8, 7, 0, NULL, '2025-05-30 19:22:51'),
(134, 98, 16, 3, 62.00, 9, 9, 0, NULL, '2025-05-30 19:22:51'),
(135, 99, 16, 1, 93.00, 11, 7, 1, NULL, '2025-05-30 19:25:53'),
(136, 99, 16, 2, 107.00, 10, 8, 0, NULL, '2025-05-30 19:25:53'),
(137, 99, 16, 3, 76.00, 11, 8, 0, NULL, '2025-05-30 19:25:53'),
(138, 100, 16, 1, 94.00, 9, 8, 1, NULL, '2025-05-30 19:26:19'),
(139, 100, 16, 2, 87.00, 12, 7, 0, NULL, '2025-05-30 19:26:19'),
(140, 100, 16, 3, 76.00, 9, 7, 0, NULL, '2025-05-30 19:26:19'),
(141, 101, 17, 1, 109.00, 9, 6, 1, NULL, '2025-05-30 19:30:39'),
(142, 101, 17, 2, 97.00, 9, 7, 0, NULL, '2025-05-30 19:30:39'),
(143, 101, 17, 3, 112.00, 9, 9, 0, NULL, '2025-05-30 19:30:39'),
(144, 102, 17, 1, 90.00, 10, 7, 1, NULL, '2025-05-30 19:30:39'),
(145, 102, 17, 2, 74.00, 8, 8, 0, NULL, '2025-05-30 19:30:39'),
(146, 102, 17, 3, 86.00, 12, 8, 0, NULL, '2025-05-30 19:30:39'),
(147, 103, 17, 1, 76.00, 11, 6, 1, NULL, '2025-05-30 19:30:39'),
(148, 103, 17, 2, 97.00, 9, 6, 0, NULL, '2025-05-30 19:30:39'),
(149, 103, 17, 3, 114.00, 11, 7, 0, NULL, '2025-05-30 19:30:39'),
(150, 104, 17, 1, 61.00, 10, 6, 1, NULL, '2025-05-30 19:34:28'),
(151, 104, 17, 2, 78.00, 9, 7, 0, NULL, '2025-05-30 19:34:28'),
(152, 104, 17, 3, 85.00, 12, 7, 0, NULL, '2025-05-30 19:34:28'),
(153, 105, 17, 1, 85.00, 8, 8, 1, NULL, '2025-05-30 19:34:28'),
(154, 105, 17, 2, 84.00, 11, 9, 0, NULL, '2025-05-30 19:34:28'),
(155, 105, 17, 3, 86.00, 12, 9, 0, NULL, '2025-05-30 19:34:28'),
(156, 106, 17, 1, 63.00, 11, 9, 1, NULL, '2025-05-30 19:34:28'),
(157, 106, 17, 2, 68.00, 8, 6, 0, NULL, '2025-05-30 19:34:28'),
(158, 106, 17, 3, 79.00, 12, 8, 0, NULL, '2025-05-30 19:34:28');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `goal_type` enum('weight','strength','endurance','workout','nutrition') NOT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT '0.00',
  `start_date` date DEFAULT (curdate()),
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed` tinyint(1) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `goals`
--

INSERT INTO `goals` (`id`, `user_id`, `title`, `description`, `goal_type`, `target_value`, `current_value`, `start_date`, `deadline`, `created_at`, `completed`, `completed_at`) VALUES
(1, 1, 'asd', 'asd', 'strength', 123.00, 106.00, '2025-03-14', '2025-03-16', '2025-03-14 08:51:25', 1, '2025-05-14 14:10:05'),
(4, 1, 'celt 100kg', 'test', 'weight', 100.00, 0.00, '2025-04-10', '2025-04-24', '2025-04-10 09:13:48', 1, '2025-06-02 00:48:33'),
(5, 1, 'Workout 4x per week', 'Complete 4 workouts each week', 'workout', 4.00, 0.00, '2025-04-10', '2025-06-05', '2025-04-10 11:05:30', 0, NULL),
(6, 1, 'fffff', 'fef2f23f', 'endurance', 23.00, 12.00, '2025-05-14', '2025-06-21', '2025-05-14 08:37:25', 0, NULL),
(13, 1, 'fwefwefwe', 'wfwefwefwef', 'strength', 100.00, 77.00, '2025-05-14', '2025-06-18', '2025-05-14 13:21:07', 0, NULL),
(15, 1, 'ff', 'fwfeef', 'endurance', 23.00, 0.00, '2025-05-14', '2025-06-20', '2025-05-14 16:58:38', 0, NULL),
(16, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:58:55', 0, NULL),
(17, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:58:59', 0, NULL),
(18, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:59:00', 0, NULL),
(19, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:59:02', 0, NULL),
(20, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:59:05', 0, NULL),
(21, 1, '222', '2222', 'strength', 2222.00, 0.00, '2025-05-14', '2025-06-13', '2025-05-14 16:59:44', 0, NULL),
(23, 13, '2f2ff2', 'fef', 'strength', 122.00, 12.00, '2025-05-27', '2025-06-30', '2025-05-27 19:55:08', 0, NULL),
(24, 13, '2f2ff2', 'fef', 'strength', 122.00, 12.00, '2025-05-27', '2025-06-30', '2025-05-27 20:25:23', 0, NULL),
(28, 13, '1e2', '12e', 'endurance', 43.00, 43.00, '2025-05-28', '2025-06-27', '2025-05-28 11:05:57', 1, '2025-05-28 11:17:45'),
(29, 13, '1e2', '12e', 'endurance', 43.00, 43.00, '2025-05-28', '2025-06-27', '2025-05-28 11:05:57', 0, NULL),
(30, 13, '1e2', '12e', 'endurance', 43.00, 15.00, '2025-05-28', '2025-06-27', '2025-05-28 11:05:58', 0, NULL),
(31, 13, 'f23', 'f23wqef23e', 'weight', 14.00, 13.00, '2025-05-28', '2025-07-04', '2025-05-28 11:06:16', 0, NULL),
(32, 13, 'fwefwefwefwe', 'fefwefwe', 'strength', 2333.00, 234.00, '2025-05-28', '2025-06-27', '2025-05-28 11:13:13', 1, '2025-05-28 11:16:52'),
(33, 14, 'df22f3ddddd', 'f23f23323f23', 'weight', 25.00, 25.00, '2025-05-28', '2025-06-27', '2025-05-28 18:56:28', 1, '2025-05-28 20:54:42'),
(34, 14, '2efefg3rg', 'g3g3g3g43', 'strength', 111.00, 111.00, '2025-05-28', '2025-06-27', '2025-05-28 20:41:24', 1, '2025-05-28 20:54:23'),
(35, 14, '2efefg3rg', 'g3g3g3g43', 'strength', 111.00, 111.00, '2025-05-28', '2025-06-27', '2025-05-28 20:45:33', 1, '2025-05-28 20:54:32'),
(36, 14, '2efefg3rg', 'g3g3g3g43', 'strength', 111.00, 12.00, '2025-05-28', '2025-06-27', '2025-05-28 20:49:33', 1, '2025-05-28 20:55:27'),
(37, 14, '2efefg3rg', 'g3g3g3g43', 'strength', 111.00, 12.00, '2025-05-28', '2025-06-27', '2025-05-28 20:49:34', 0, NULL),
(38, 14, '2efefg3rg', 'g3g3g3g43', 'strength', 111.00, 12.00, '2025-05-28', '2025-06-27', '2025-05-28 20:49:34', 0, NULL),
(39, 15, 'ffe', 'wefgh4', 'nutrition', 22.00, 37.00, '2025-05-29', '2025-05-31', '2025-05-29 17:51:27', 0, NULL),
(40, 15, 'ttrry', 'yyyr', 'strength', 33.00, 13.00, '2025-05-29', '2025-05-31', '2025-05-29 17:54:57', 0, NULL),
(42, 15, '23r2r3', 'r323r23r', 'weight', 122.00, 12.00, '2025-05-29', '2025-06-06', '2025-05-29 18:22:31', 0, NULL),
(43, 15, '2r332', '2323', 'strength', 123.00, 12.00, '2025-05-29', '2025-06-05', '2025-05-29 18:46:30', 0, NULL),
(44, 15, 'test', 'test', 'strength', 223.00, 12.00, '2025-05-29', '2025-05-31', '2025-05-29 19:00:52', 0, NULL),
(45, 15, 'aas', 'f23f23f', 'strength', 13.00, 12.00, '2025-05-29', '2025-06-07', '2025-05-29 19:01:49', 0, NULL),
(46, 15, '12e12e', '12e12e', 'strength', 144.00, 12.00, '2025-05-29', '2025-06-07', '2025-05-29 19:17:03', 0, NULL),
(47, 15, 'g14g134g134g134g', '134g134g134g134g134g', 'weight', 13.00, 12.00, '2025-05-29', '2025-06-08', '2025-05-29 19:17:21', 0, NULL),
(48, 15, '3f13g13f', '13gf134g134g', 'nutrition', 13.00, 12.50, '2025-05-29', '2025-05-30', '2025-05-29 19:17:53', 0, NULL),
(49, 15, 'advadvs', 'advadfv', 'endurance', 13.00, 12.00, '2025-05-29', '2025-05-30', '2025-05-29 19:19:19', 0, NULL),
(50, 15, 'qfwefqwef', 'qwefqwefqwef', 'strength', 14.00, 12.00, '2025-05-29', '2025-05-31', '2025-05-29 19:42:07', 0, NULL),
(51, 15, 'WEFWEF', 'WEFWEFWEF', 'strength', 14.00, 12.00, '2025-05-29', '2025-05-31', '2025-05-29 20:34:50', 0, NULL),
(52, 15, 'wefwefwef', 'WFWef', 'weight', 14.00, 12.00, '2025-05-29', '2025-05-31', '2025-05-29 20:35:19', 0, NULL),
(53, 15, 'ertfy', 'etwrdtfyg', 'nutrition', 15.00, 0.00, '2025-05-29', '2025-05-31', '2025-05-29 20:36:26', 0, NULL),
(54, 15, '3g4werdafsv', 'q34greafdsvc', 'endurance', 15.00, 0.00, '2025-05-29', '2025-06-07', '2025-05-29 20:36:51', 0, NULL),
(55, 15, 'qwdqwdqwd', 'qwdqwd', 'strength', 22.00, 0.00, '2025-05-29', '2025-05-31', '2025-05-29 20:44:38', 0, NULL),
(56, 15, 'wefe', 'vawvwe', 'endurance', 15.00, 14.00, '2025-05-29', '2025-05-31', '2025-05-29 20:50:57', 0, NULL),
(57, 15, 'dqwq2wd', 'qwdqwdqwd', 'workout', 14.00, 12.00, '2025-05-30', '2025-06-01', '2025-05-30 06:18:34', 0, NULL),
(58, 15, 'fwefw', 'efwefwef', 'strength', 25.00, 23.00, '2025-05-30', '2025-06-08', '2025-05-30 06:27:10', 0, NULL),
(59, 15, 'qwrqwr', 'qwrqwr', 'weight', 15.00, 12.00, '2025-05-30', '2025-05-31', '2025-05-30 06:45:00', 0, NULL),
(60, 15, 'qwdqwd', 'qwdqwd', 'strength', 120.00, 55.00, '2025-05-30', '2025-08-01', '2025-05-30 07:54:33', 0, NULL),
(61, 16, 'ssdfsdf', 'sdfsdf', 'strength', 222.00, 29.00, '2025-05-30', '2025-06-07', '2025-05-30 10:14:36', 1, '2025-05-30 10:15:19'),
(62, 18, 'ygtugy', 'yggty', 'strength', 100.00, 55.00, '2025-05-31', '2025-06-07', '2025-05-31 16:58:44', 1, '2025-06-01 16:52:43'),
(65, 20, 'pacelt 100kg', 'test', 'strength', 100.00, 80.00, '2025-06-02', '2025-07-24', '2025-06-02 05:02:56', 1, '2025-06-02 05:03:24'),
(66, 20, 'dasdasd', 'a', 'strength', 13.00, 12.00, '2025-06-02', '2025-06-26', '2025-06-02 05:03:45', 0, NULL),
(67, 21, 'pacelt 100kg', 'test', 'strength', 100.00, 90.00, '2025-06-02', '2025-06-12', '2025-06-02 05:14:58', 1, '2025-06-02 05:15:14'),
(68, 21, 'wef', 'wefwef', 'weight', 122.00, 12.00, '2025-06-02', '2025-06-26', '2025-06-02 05:15:34', 0, NULL),
(69, 23, 'pacelt 100kg', 'test', 'strength', 100.00, 86.00, '2025-06-02', '2025-06-27', '2025-06-02 08:07:33', 1, '2025-06-02 08:07:53'),
(70, 23, '23r23r', '23r23r', 'strength', 33.00, 12.00, '2025-06-02', '2025-06-27', '2025-06-02 08:08:20', 0, NULL),
(72, 1, 'wfewef', 'fwef', 'weight', 222.00, 12.00, '2025-06-07', '2025-06-28', '2025-06-07 11:56:10', 0, NULL),
(73, 28, 'wefwef', 'wsdfwefwef', 'strength', 222.00, 12.00, '2025-06-08', '2025-08-01', '2025-06-08 11:18:31', 0, NULL),
(74, 28, '34r34r', '45t3', 'strength', 13.00, 13.00, '2025-06-08', '2025-06-20', '2025-06-08 15:57:20', 1, '2025-06-08 15:57:27');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(5, 'super_admin', 'Full system access with all permissions', '2025-04-08 11:55:42', '2025-04-08 11:55:42'),
(10, 'basic_user', 'Standard user with access to basic features', '2025-04-08 11:55:42', '2025-04-08 11:55:42');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_workouts`
--

CREATE TABLE `scheduled_workouts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `day` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `template_id` int DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `workout_type` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `scheduled_workouts`
--

INSERT INTO `scheduled_workouts` (`id`, `user_id`, `day`, `month`, `year`, `template_id`, `template_name`, `workout_type`, `created_at`) VALUES
(1, 1, 1, 5, 2025, 13, 'gest', 'push', '2025-05-07 21:14:47'),
(2, 1, 2, 5, 2025, 7, 'pulk', 'pull', '2025-05-07 21:15:07'),
(3, 1, 3, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:15:13'),
(4, 1, 4, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:15:18'),
(5, 1, 5, 5, 2025, 13, 'gest', 'push', '2025-05-07 21:15:23'),
(6, 1, 6, 5, 2025, 7, 'pulk', 'pull', '2025-05-07 21:15:29'),
(7, 1, 7, 5, 2025, 13, 'gest', 'push', '2025-05-07 21:35:23'),
(8, 1, 8, 5, 2025, 13, 'gest', 'push', '2025-05-07 21:33:13'),
(12, 1, 9, 5, 2025, 8, 'push day', 'pull', '2025-05-07 21:32:59'),
(13, 1, 10, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(14, 1, 11, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(15, 1, 12, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(16, 1, 13, 5, 2025, 7, 'pulk', 'pull', '2025-05-07 21:32:59'),
(17, 1, 14, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(18, 1, 15, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(19, 1, 16, 5, 2025, 8, 'push day', 'pull', '2025-05-07 21:32:59'),
(20, 1, 17, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(21, 1, 18, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(22, 1, 19, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(23, 1, 20, 5, 2025, 7, 'pulk', 'pull', '2025-05-07 21:32:59'),
(24, 1, 21, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(25, 1, 22, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(26, 1, 23, 5, 2025, 8, 'push day', 'pull', '2025-05-07 21:32:59'),
(27, 1, 24, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(28, 1, 25, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(29, 1, 26, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(30, 1, 27, 5, 2025, 7, 'pulk', 'pull', '2025-05-07 21:32:59'),
(31, 1, 28, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(32, 1, 29, 5, 2025, 8, 'push day', 'push', '2025-05-07 21:32:59'),
(33, 1, 30, 5, 2025, 8, 'push day', 'pull', '2025-05-07 21:32:59'),
(34, 1, 31, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-07 21:32:59'),
(40, 13, 1, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(41, 13, 2, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(42, 13, 3, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(43, 13, 4, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(44, 13, 5, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-25 21:27:39'),
(45, 13, 6, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(46, 13, 7, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(47, 13, 8, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(48, 13, 9, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(49, 13, 10, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(50, 13, 11, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(51, 13, 12, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-25 21:27:39'),
(52, 13, 13, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(53, 13, 14, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(54, 13, 15, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(55, 13, 16, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(56, 13, 17, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(57, 13, 18, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(58, 13, 19, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-25 21:27:39'),
(59, 13, 20, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(60, 13, 21, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(61, 13, 22, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(62, 13, 23, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(63, 13, 24, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(64, 13, 25, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(65, 13, 26, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-25 21:27:39'),
(66, 13, 27, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(67, 13, 28, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(68, 13, 29, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(69, 13, 30, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(70, 13, 31, 5, 2025, NULL, '', '', '2025-05-25 21:27:39'),
(195, 14, 1, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(196, 14, 2, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(197, 14, 3, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(198, 14, 4, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(199, 14, 5, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(200, 14, 6, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(201, 14, 7, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(202, 14, 8, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(203, 14, 9, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(204, 14, 10, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(205, 14, 11, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(206, 14, 12, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(207, 14, 13, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(208, 14, 14, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(209, 14, 15, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(210, 14, 16, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(211, 14, 17, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(212, 14, 18, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(213, 14, 19, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(214, 14, 20, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(215, 14, 21, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(216, 14, 22, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(217, 14, 23, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(218, 14, 24, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(219, 14, 25, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(220, 14, 26, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(221, 14, 27, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(222, 14, 28, 5, 2025, 21, 'f3rf3', 'strength training', '2025-05-29 18:44:18'),
(223, 14, 29, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(224, 14, 30, 5, 2025, 20, 'test', 'strength training', '2025-05-29 18:44:18'),
(225, 14, 31, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 18:44:18'),
(319, 15, 1, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(320, 15, 2, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(321, 15, 3, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(322, 15, 4, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(323, 15, 5, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(324, 15, 6, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(325, 15, 7, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(326, 15, 8, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(327, 15, 9, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(328, 15, 10, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(329, 15, 11, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(330, 15, 12, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(331, 15, 13, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(332, 15, 14, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(333, 15, 15, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(334, 15, 16, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(335, 15, 17, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(336, 15, 18, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(337, 15, 19, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(338, 15, 20, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(339, 15, 21, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(340, 15, 22, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(341, 15, 23, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(342, 15, 24, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(343, 15, 25, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(344, 15, 26, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(345, 15, 27, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(346, 15, 28, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(347, 15, 29, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(348, 15, 30, 5, 2025, 22, 'g', 'strength training', '2025-05-29 20:23:41'),
(349, 15, 31, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-29 20:23:41'),
(350, 16, 1, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(351, 16, 2, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(352, 16, 3, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(353, 16, 4, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(354, 16, 5, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(355, 16, 6, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(356, 16, 7, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(357, 16, 8, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(358, 16, 9, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(359, 16, 10, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(360, 16, 11, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(361, 16, 12, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(362, 16, 13, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(363, 16, 14, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(364, 16, 15, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(365, 16, 16, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(366, 16, 17, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(367, 16, 18, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(368, 16, 19, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(369, 16, 20, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:38'),
(370, 16, 21, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:38'),
(371, 16, 22, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:39'),
(372, 16, 23, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:39'),
(373, 16, 24, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:39'),
(374, 16, 25, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:39'),
(375, 16, 26, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:39'),
(376, 16, 27, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:39'),
(377, 16, 28, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:39'),
(378, 16, 29, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:39'),
(379, 16, 30, 5, 2025, 23, 'qwwqeffqwe', 'cardio', '2025-05-30 13:12:39'),
(380, 16, 31, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-30 13:12:39'),
(381, 18, 1, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(382, 18, 2, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(383, 18, 3, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(384, 18, 4, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(385, 18, 5, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(386, 18, 6, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(387, 18, 7, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(388, 18, 8, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(389, 18, 9, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(390, 18, 10, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(391, 18, 11, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(392, 18, 12, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(393, 18, 13, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(394, 18, 14, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(395, 18, 15, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(396, 18, 16, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(397, 18, 17, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(398, 18, 18, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(399, 18, 19, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(400, 18, 20, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(401, 18, 21, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(402, 18, 22, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(403, 18, 23, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(404, 18, 24, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(405, 18, 25, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(406, 18, 26, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(407, 18, 27, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(408, 18, 28, 5, 2025, NULL, 'Rest Day', 'rest', '2025-05-31 19:58:14'),
(409, 18, 29, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(410, 18, 30, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(411, 18, 31, 5, 2025, 24, 'fg', 'cardio', '2025-05-31 19:58:14'),
(412, 18, 1, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(413, 18, 2, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(414, 18, 3, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(415, 18, 4, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(416, 18, 5, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(417, 18, 6, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(418, 18, 7, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(419, 18, 8, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(420, 18, 9, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(421, 18, 10, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(422, 18, 11, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(423, 18, 12, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(424, 18, 13, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(425, 18, 14, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(426, 18, 15, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(427, 18, 16, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(428, 18, 17, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(429, 18, 18, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(430, 18, 19, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(431, 18, 20, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(432, 18, 21, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(433, 18, 22, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(434, 18, 23, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(435, 18, 24, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(436, 18, 25, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(437, 18, 26, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(438, 18, 27, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-01 19:56:38'),
(439, 18, 28, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(440, 18, 29, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(441, 18, 30, 6, 2025, 24, 'fg', 'cardio', '2025-06-01 19:56:38'),
(442, 1, 1, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(443, 1, 2, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(444, 1, 3, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(445, 1, 4, 6, 2025, 9, 'push day', 'custom', '2025-06-02 03:00:54'),
(446, 1, 5, 6, 2025, 25, 'qwrwqr', 'strength training', '2025-06-02 03:00:54'),
(447, 1, 6, 6, 2025, 15, 'testser4', 'strength training', '2025-06-02 03:00:54'),
(448, 1, 7, 6, 2025, 16, 'ffwef', 'strength training', '2025-06-02 03:00:54'),
(449, 1, 8, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(450, 1, 9, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(451, 1, 10, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(452, 1, 11, 6, 2025, 9, 'push day', 'custom', '2025-06-02 03:00:54'),
(453, 1, 12, 6, 2025, 25, 'qwrwqr', 'strength training', '2025-06-02 03:00:54'),
(454, 1, 13, 6, 2025, 15, 'testser4', 'strength training', '2025-06-02 03:00:54'),
(455, 1, 14, 6, 2025, 16, 'ffwef', 'strength training', '2025-06-02 03:00:54'),
(456, 1, 15, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(457, 1, 16, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(458, 1, 17, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(459, 1, 18, 6, 2025, 9, 'push day', 'custom', '2025-06-02 03:00:54'),
(460, 1, 19, 6, 2025, 25, 'qwrwqr', 'strength training', '2025-06-02 03:00:54'),
(461, 1, 20, 6, 2025, 15, 'testser4', 'strength training', '2025-06-02 03:00:54'),
(462, 1, 21, 6, 2025, 16, 'ffwef', 'strength training', '2025-06-02 03:00:54'),
(463, 1, 22, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(464, 1, 23, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(465, 1, 24, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(466, 1, 25, 6, 2025, 9, 'push day', 'custom', '2025-06-02 03:00:54'),
(467, 1, 26, 6, 2025, 25, 'qwrwqr', 'strength training', '2025-06-02 03:00:54'),
(468, 1, 27, 6, 2025, 15, 'testser4', 'strength training', '2025-06-02 03:00:54'),
(469, 1, 28, 6, 2025, 16, 'ffwef', 'strength training', '2025-06-02 03:00:54'),
(470, 1, 29, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(471, 1, 30, 6, 2025, 13, 'gest', 'cardio', '2025-06-02 03:00:54'),
(532, 20, 1, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(533, 20, 2, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(534, 20, 3, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(535, 20, 4, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(536, 20, 5, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(537, 20, 6, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(538, 20, 7, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(539, 20, 8, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(540, 20, 9, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(541, 20, 10, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(542, 20, 11, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(543, 20, 12, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(544, 20, 13, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(545, 20, 14, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(546, 20, 15, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(547, 20, 16, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(548, 20, 17, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(549, 20, 18, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(550, 20, 19, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(551, 20, 20, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(552, 20, 21, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(553, 20, 22, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(554, 20, 23, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(555, 20, 24, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(556, 20, 25, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(557, 20, 26, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:04:55'),
(558, 20, 27, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(559, 20, 28, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(560, 20, 29, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(561, 20, 30, 6, 2025, 28, 'push', 'strength training', '2025-06-02 08:04:55'),
(562, 21, 1, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(563, 21, 2, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(564, 21, 3, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(565, 21, 4, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(566, 21, 5, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(567, 21, 6, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(568, 21, 7, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(569, 21, 8, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(570, 21, 9, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(571, 21, 10, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(572, 21, 11, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(573, 21, 12, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(574, 21, 13, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(575, 21, 14, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(576, 21, 15, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(577, 21, 16, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(578, 21, 17, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(579, 21, 18, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(580, 21, 19, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(581, 21, 20, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(582, 21, 21, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(583, 21, 22, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(584, 21, 23, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(585, 21, 24, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(586, 21, 25, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(587, 21, 26, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(588, 21, 27, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(589, 21, 28, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 08:16:20'),
(590, 21, 29, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(591, 21, 30, 6, 2025, 29, 'push', 'strength training', '2025-06-02 08:16:20'),
(592, 23, 1, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 11:09:19'),
(593, 23, 2, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(594, 23, 3, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(595, 23, 4, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(596, 23, 5, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(597, 23, 6, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(598, 23, 7, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(599, 23, 8, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 11:09:19'),
(600, 23, 9, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(601, 23, 10, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(602, 23, 11, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(603, 23, 12, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(604, 23, 13, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(605, 23, 14, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(606, 23, 15, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 11:09:19'),
(607, 23, 16, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(608, 23, 17, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(609, 23, 18, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(610, 23, 19, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(611, 23, 20, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(612, 23, 21, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(613, 23, 22, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 11:09:19'),
(614, 23, 23, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(615, 23, 24, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(616, 23, 25, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(617, 23, 26, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(618, 23, 27, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(619, 23, 28, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19'),
(620, 23, 29, 6, 2025, NULL, 'Rest Day', 'rest', '2025-06-02 11:09:19'),
(621, 23, 30, 6, 2025, 30, 'test', 'strength training', '2025-06-02 11:09:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `language` varchar(10) DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `language`, `created_at`, `last_active`) VALUES
(1, 'admin', '$2y$12$9Xzc.EKGVn.7lqtwkXuLruMIj/oFZqRw1MVKw7q4YhlCtPCg8HBB6', 'admin@gmail.com', 'lv', '2025-03-14 08:49:10', '2025-06-09 05:46:11'),
(3, 'test', '$2y$12$/zsxhDx7ttr5ccJV0k0i9OkW.3uI0TF9C3mjMFW4QL6bTnyD0lPqS', 'test@gmail.com', 'en', '2025-04-08 21:42:10', '2025-05-21 09:16:57'),
(4, 'kristians', '$2y$12$gGIBxxMOTSqKz6J1WGnE5.cXVkBC84/l7Dzdrw7NgNSPMHYZVm7B6', 'kristians@gmail.com', 'en', '2025-05-05 20:45:14', '2025-05-06 18:30:31'),
(5, 'test1', '$2y$12$Ngo5ghcUmnJRJHnimxPpg.4JvhouXt1IYLwg4PzhVtZBPE7Q.vy22', 'test1@test1.com', 'en', '2025-05-17 08:35:40', '2025-05-17 08:39:34'),
(6, 'testt', '$2y$12$tQTULS/2VAo38cHyvB58IeKgyr.Xafanv2D21mn4qvNw5N59KuInC', 'testt@gmail.com', 'en', '2025-05-21 06:13:11', '2025-05-21 06:14:09'),
(7, 'tester', '$2y$12$pbjTj8ajhnMwEIznu0NVxO4xV1LyMjqTubVIianBEQh0vg6Qr/Ide', 'tester@gmail.com', 'en', '2025-05-21 09:17:24', '2025-05-21 09:17:24'),
(8, 'tester1', '$2y$12$pQAQA5p9D5ZirvM3qTrm7eIbYxqJTxDcwWkeObg3pVusYot7qxSRq', 'tester1@gmail.com', 'en', '2025-05-21 09:24:15', '2025-05-21 10:06:04'),
(9, 'f3f', '$2y$12$n/xvDGQONvU2S1EtGvVAx.sza1TCIN6p8hSHlX/3qg3dgRUUGZBi2', 'pfiu@vwpve.com', 'en', '2025-05-21 18:59:27', '2025-05-21 20:09:19'),
(11, 'username', '$2y$12$E88I17x9gtdDpGM2L5SuRuL30D3TfgUgVNkmmVv46jxsYVF/0xaCG', 'email@email.com', 'en', '2025-05-21 20:13:21', '2025-05-21 20:13:34'),
(13, 'JanisBfwe', '$2y$12$xYXldRJvNBT9ANdh//.EzOaIsJ6jaZ2N3vYtU3zJDT2BY1NNRNDCu', 'janis@example.com', 'en', '2025-05-24 15:36:23', '2025-05-28 11:21:59'),
(14, 'tester10', '$2y$12$cjKOZY.uAmUZxtFHovhL2.HivEZDD0UDyvliGvobKcxnuzIzfJxFC', 'tester10@gmail.com', 'en', '2025-05-28 11:24:02', '2025-05-29 17:08:32'),
(15, 'showcase', '$2y$12$B98.aBgHmyuNXQK0S3t/5eLPafol61003GbpTnoX2VIcFgXpVxER6', 'showcase@gmail.com', 'en', '2025-05-29 17:22:14', '2025-05-30 09:16:38'),
(16, 'rendijs', '$2y$12$LpQbkFZjojfH20sn4qsvVOPOyKrPZ8InB3j06I9i7cn3WxU0aQh.i', 'rendijs@gmail.com', 'en', '2025-05-30 10:07:33', '2025-05-30 18:22:56'),
(17, 'rolands', '$2y$12$a5ypRbc8I0KyO5jZk9bUIePORfrQPdtZLk64Yq1HvqAoTM/B47BD.', 'rolands@gmail.com', 'en', '2025-05-30 19:30:00', '2025-05-31 16:55:20'),
(18, 'sdcfvg', '$2y$12$lIgjPIsPFw3GB5XMkc4h/OtWCDCQYotcoPoWaJ3p9z2SIpBQKryKe', 'ewsrdf@esdxfc.com', 'en', '2025-05-31 16:56:53', '2025-06-01 17:30:37'),
(20, 'tester11', '$2y$12$dL1acSPzUn88RdLPCuEyD.WcxW4j5VFDEa/RpKBBOWm2LmdUBT.k2', 'tester11@hmsil.com', 'en', '2025-06-02 04:54:39', '2025-06-02 05:05:23'),
(21, 'tester12', '$2y$12$1dgykCSTFynuyxy.j1FiGeXzztcuGM0rUVHsOpyVdppUsWpi5RejW', 'tester12@gmail.com', 'en', '2025-06-02 05:11:46', '2025-06-02 05:16:21'),
(22, 'dqfqwfqwf', '$2y$12$r/HhBmC2MmRLfh7UWBfCuusaO/S6zP.PLRntqjZDefMTZ7Yibcrgi', 'wfqwfqwffq@gmail.com', 'en', '2025-06-02 06:42:40', '2025-06-02 06:42:45'),
(23, 'kaspars', '$2y$12$6fhNdwBYDHlR1G.DryeL9OOC03R87CFvUC7lbQpy2ysoGGglszEqy', 'kaspars@gmail.com', 'en', '2025-06-02 08:01:07', '2025-06-02 08:09:22'),
(24, 'wefwefqwefqwefq', '$2y$12$89o3nKsIM7Q8Aa/qFkOwyev6W0aAVL3aDcZgEV1zWb5brjlTMg6YS', 'fqwefwqefwef@gmail.com', 'en', '2025-06-02 10:26:52', '2025-06-02 10:26:52'),
(28, 'beetbreb', '$2y$12$YPJ63SN6aoa0Cc0nZfdxaub5ga4GiEO5/VjIS9HabhL/6vT4qJhE6', 'erev@gmail.com', 'lv', '2025-06-08 11:16:59', '2025-06-08 16:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `created_at`) VALUES
(2, 1, 5, '2025-04-08 11:55:49'),
(3, 3, 10, '2025-04-08 21:42:10'),
(4, 4, 10, '2025-05-05 20:45:14'),
(5, 5, 10, '2025-05-17 08:35:40'),
(6, 6, 10, '2025-05-21 06:13:11'),
(7, 7, 10, '2025-05-21 09:17:24'),
(8, 8, 10, '2025-05-21 09:24:15'),
(15, 13, 10, '2025-05-24 15:36:23'),
(16, 14, 10, '2025-05-28 11:24:02'),
(17, 15, 10, '2025-05-29 17:22:14'),
(18, 16, 10, '2025-05-30 10:07:33'),
(19, 17, 10, '2025-05-30 19:30:00'),
(21, 18, 10, '2025-06-02 00:29:50'),
(22, 19, 10, '2025-06-02 00:50:47'),
(23, 20, 10, '2025-06-02 04:54:39'),
(24, 21, 10, '2025-06-02 05:11:46'),
(25, 22, 10, '2025-06-02 06:42:40'),
(26, 23, 10, '2025-06-02 08:01:07'),
(27, 24, 10, '2025-06-02 10:26:52'),
(28, 25, 10, '2025-06-02 10:48:04'),
(29, 26, 10, '2025-06-08 10:26:30'),
(30, 27, 10, '2025-06-08 10:41:25'),
(31, 28, 10, '2025-06-08 11:16:59');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `workout_type` varchar(50) DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `calories_burned` int DEFAULT NULL,
  `notes` text,
  `rating` int DEFAULT NULL,
  `template_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_volume` decimal(10,2) NOT NULL DEFAULT '0.00',
  `avg_intensity` float DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`id`, `user_id`, `name`, `workout_type`, `duration_minutes`, `calories_burned`, `notes`, `rating`, `template_id`, `created_at`, `total_volume`, `avg_intensity`) VALUES
(6, 1, 'Quick Workout', 'quick_workout', 0, NULL, '', NULL, NULL, '2025-04-16 12:45:54', 560.00, 7),
(7, 1, 'Quick Workout', 'quick_workout', 0, NULL, 'testtt', NULL, NULL, '2025-04-16 12:49:42', 560.00, 7),
(8, 1, 'Quick Workout', 'quick_workout', 1, 13, '0', 4, 0, '2025-04-16 13:02:39', 240.00, 7),
(9, 1, 'Quick Workout', 'quick_workout', 0, 1, 'g3g3g34g34', 5, 0, '2025-04-16 13:03:29', 480.00, 7),
(10, 1, 'test2', 'quick_workout', 1, 5, '', 0, 10, '2025-04-21 11:37:30', 3150.00, 0),
(11, 1, 'push day', 'quick_workout', 0, 2, '', 0, 9, '2025-04-22 07:09:13', 10350.00, 0),
(30, 1, 'pulk', 'strength', 0, 1, '', 3, 7, '2025-05-02 17:13:09', 0.00, 0),
(31, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-02 18:22:26', 0.00, 0),
(32, 1, 'push day', 'strength', 0, 0, '', 3, 9, '2025-05-02 18:23:32', 0.00, 0),
(33, 1, 'pulk', 'strength', 0, 1, '', 3, 7, '2025-05-02 19:12:37', 0.00, 0),
(34, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-02 19:51:55', 0.00, 0),
(35, 1, 'pulk', 'strength', 981, 4907, '', 3, 7, '2025-05-03 12:55:27', 0.00, 0),
(36, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-03 14:21:10', 0.00, 0),
(37, 1, 'pulk', 'strength', 0, 0, '', 3, 7, '2025-05-03 14:33:02', 0.00, NULL),
(38, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-03 15:09:10', 0.00, 0),
(39, 1, 'pulk', 'strength', 0, 0, '', 3, 7, '2025-05-03 15:13:24', 0.00, 0),
(40, 1, 'testtt', 'strength', 0, 0, '', 5, 6, '2025-05-03 15:24:45', 0.00, 0),
(41, 1, 'pulk', 'strength', 0, 0, '', 3, 7, '2025-05-03 15:30:15', 0.00, 0),
(42, 1, 'pulk', 'strength', 0, 0, '', 4, 7, '2025-05-03 16:07:52', 0.00, 0),
(43, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-03 16:09:52', 0.00, 0),
(44, 1, 'test2', 'strength', 0, 0, '', 1, 10, '2025-05-03 16:10:51', 0.00, 0),
(45, 1, 'pulk', 'strength', 0, 0, '', 1, 7, '2025-05-03 16:24:56', 0.00, 0),
(46, 1, 'pulk', 'strength', 0, 0, '', 3, 7, '2025-05-03 17:17:01', 0.00, 0),
(47, 1, 'testtt', 'strength', 0, 0, '', 5, 6, '2025-05-03 17:40:34', 0.00, 0),
(48, 1, 'pulk', 'strength', 0, 0, '', 4, 7, '2025-05-03 17:47:20', 0.00, 0),
(49, 1, 'testtt', 'strength', 0, 0, '', 1, 6, '2025-05-03 17:59:49', 0.00, 0),
(50, 1, 'testtt', 'strength', 0, 0, '', 5, 6, '2025-05-03 19:51:34', 0.00, 0),
(51, 1, 'pulk', 'strength', 0, 0, '', 1, 7, '2025-05-03 19:56:24', 0.00, 0),
(52, 1, 'pulk', 'strength', 1, 6, '', 5, 7, '2025-05-03 20:01:35', 0.00, 0),
(53, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-03 20:10:17', 976.00, 0),
(54, 1, 'push day', 'strength', 0, 1, '', 5, 8, '2025-05-03 20:16:31', 6556.00, 0),
(55, 1, 'testtt', 'strength', 0, 0, '', 3, 6, '2025-05-03 20:30:31', 480.00, 0),
(56, 1, 'pulk', 'strength', 0, 1, NULL, 3, 7, '2025-05-03 20:41:57', 2448.00, 0),
(57, 1, 'testtt', 'strength', 0, 0, NULL, 3, 6, '2025-05-04 09:49:07', 2727.00, 0),
(58, 1, 'pulk', 'strength', 0, 0, NULL, 3, 7, '2025-05-04 09:56:22', 240.00, 0),
(59, 1, 'pulk', 'strength', 0, 0, 'wecwecwecwec', 3, 7, '2025-05-04 10:01:07', 160.00, 0),
(60, 1, 'push day', 'strength', 0, 1, 'frfewfwedwqd', 1, 9, '2025-05-04 10:41:16', 1240.00, 0),
(61, 1, 'testtt', 'strength', 0, 0, 'frfewfwedwqd', 3, 6, '2025-05-04 10:48:15', 300.00, 0),
(62, 1, 'testtt', 'strength', 0, 44, 'esdfuyglihoj', 4, 6, '2025-05-04 13:10:36', 320.00, 0),
(63, 1, 'pulk', 'strength', 0, 108, 'esdfuyglihoj', 1, 7, '2025-05-04 13:11:17', 960.00, 0),
(64, 1, 'pulk', 'strength', 0, 0, 'ververv', 3, 7, '2025-05-04 16:06:02', 432.00, 0),
(65, 1, 'pulk', 'strength', 3, 0, 'is there duration minute 1?', 3, 7, '2025-05-04 16:22:33', 320.00, 0),
(66, 1, 'push day', 'strength', 1, 0, 'qdqwdqwd', 3, 8, '2025-05-04 17:30:28', 2156.00, 0),
(67, 1, 'pulk', 'strength', 0, 18, NULL, 3, 7, '2025-05-04 17:36:22', 432.00, 0),
(68, 1, 'push day', 'strength', 2, 141, '23ef343g34g4g44g', 3, 11, '2025-05-05 17:34:31', 3420.00, 0),
(69, 1, 'push day', 'strength', 0, 460, NULL, 5, 11, '2025-05-05 18:17:54', 3312.00, 0),
(70, 1, 'testtt', 'strength', 1, 21, 'fwefwefwefwefwefwefwef', 3, 6, '2025-05-07 12:31:03', 564.00, 0),
(71, 1, 'tester123', 'strength', 0, 36, 'FF34F344343', 3, 12, '2025-05-11 11:33:41', 864.00, 0),
(72, 1, 'tester123', 'strength', 0, 120, 'GRQGRQGRQGR', 5, 12, '2025-05-11 11:34:33', 864.00, 0),
(74, 13, 'EFW', 'strength', 0, 53, NULL, 3, 19, '2025-05-25 19:47:24', 1296.00, 0),
(75, 14, 'test', 'strength', 0, 12, NULL, 3, 20, '2025-05-28 11:25:04', 288.00, 0),
(76, 14, 'test', 'strength', 0, 549, 'gdhfgh', 4, 20, '2025-05-28 18:15:41', 4513.00, 0),
(77, 15, 'g', 'strength', 0, 6, NULL, 3, 22, '2025-05-29 17:23:04', 144.00, 0),
(78, 16, 'qwwqeffqwe', 'strength', 2, 44, 'gegrw3gerg34wr', 3, 23, '2025-05-30 10:11:54', 1322.00, 0),
(79, 16, 'push day', 'strength', 3, 38, 'qwdqwdqwdqwdqwd', 3, 8, '2025-05-30 18:56:09', 1193.00, 0),
(80, 16, 'qwwqeffqwe', 'strength', 1, 6, NULL, 3, 23, '2025-05-30 19:25:42', 144.00, 0),
(81, 16, 'testser4', 'strength', 0, 6, NULL, 3, 15, '2025-05-30 19:26:15', 144.00, 0),
(82, 17, 'push day', 'strength', 0, 53, NULL, 3, 9, '2025-05-30 19:30:35', 1296.00, 0),
(83, 17, 'push day', 'strength', 0, 53, 'wedwewef', 3, 9, '2025-05-30 19:34:22', 1296.00, 0),
(84, 17, 'testtt', 'strength', 0, 18, NULL, 3, 6, '2025-05-30 19:47:58', 432.00, 0),
(85, 18, 'fg', 'strength', 0, 12, NULL, 3, 24, '2025-05-31 16:57:44', 288.00, 0),
(86, 1, 'gest', 'strength', 0, 24, NULL, 3, 13, '2025-06-01 21:19:15', 576.00, 0),
(87, 1, 'gest', 'strength', 0, 6, NULL, 3, 13, '2025-06-02 00:03:10', 144.00, 0),
(90, 20, 'push', 'strength', 1, 158, 'bija grūti', 3, 28, '2025-06-02 05:00:01', 5061.00, 0),
(91, 21, 'push', 'strength', 1, 462, 'bija gruti', 3, 29, '2025-06-02 05:13:30', 19799.00, 0),
(92, 23, 'test', 'strength', 2, 869, 'bija grūti', 3, 30, '2025-06-02 08:05:00', 33475.00, 0),
(94, 1, 'testtt', 'strength', 0, 612, 'wefwefwef', 3, 6, '2025-06-06 18:58:28', 4320.00, 0),
(95, 1, 'test', 'strength', 1, 160, 'ergergergef', 4, 17, '2025-06-08 10:44:29', 1152.00, 0),
(96, 28, 'wedwed', 'strength', 0, 6, 'qasdasd', 3, 33, '2025-06-08 16:19:51', 144.00, 0),
(97, 1, 'test', 'strength', 0, 24, 'sdfsdf', 3, 17, '2025-06-08 17:03:31', 576.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `workout_exercises`
--

CREATE TABLE `workout_exercises` (
  `id` int NOT NULL,
  `workout_id` int NOT NULL,
  `user_id` int NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `exercise_order` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sets_completed` int DEFAULT '0',
  `total_reps` int DEFAULT '0',
  `total_volume` decimal(10,2) DEFAULT '0.00',
  `avg_rpe` decimal(3,1) DEFAULT '0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_exercises`
--

INSERT INTO `workout_exercises` (`id`, `workout_id`, `user_id`, `exercise_name`, `exercise_order`, `notes`, `created_at`, `sets_completed`, `total_reps`, `total_volume`, `avg_rpe`) VALUES
(1, 6, 1, 'Barbell Bench Press', 1, NULL, '2025-04-16 12:45:54', 3, 24, 240.00, 7.0),
(2, 6, 1, 'Pull-up', 2, NULL, '2025-04-16 12:45:54', 4, 32, 320.00, 7.0),
(3, 7, 1, 'Barbell Bench Press', 1, NULL, '2025-04-16 12:49:42', 3, 24, 240.00, 7.0),
(4, 7, 1, 'Pull-up', 2, NULL, '2025-04-16 12:49:42', 4, 32, 320.00, 7.0),
(5, 8, 1, 'Barbell Bench Press', 1, NULL, '2025-04-16 13:02:39', 3, 24, 240.00, 7.0),
(6, 9, 1, 'Barbell Bench Press', 1, NULL, '2025-04-16 13:03:29', 3, 24, 240.00, 7.0),
(7, 9, 1, 'Pull-up', 2, NULL, '2025-04-16 13:03:29', 3, 24, 240.00, 7.0),
(8, 10, 1, 'Bench Press', 1, NULL, '2025-04-21 11:37:30', 3, 20, 1050.00, 0.0),
(9, 10, 1, 'Deadlift', 2, NULL, '2025-04-21 11:37:30', 3, 21, 1050.00, 0.0),
(10, 10, 1, 'Dumbbell Shoulder Press', 3, NULL, '2025-04-21 11:37:30', 3, 21, 1050.00, 0.0),
(11, 11, 1, 'Bench Press', 1, NULL, '2025-04-22 07:09:13', 3, 69, 3450.00, 0.0),
(12, 11, 1, 'Push-Up', 2, NULL, '2025-04-22 07:09:13', 3, 69, 3450.00, 0.0),
(13, 11, 1, 'Jumping Jacks', 3, NULL, '2025-04-22 07:09:13', 3, 69, 3450.00, 0.0),
(20, 30, 1, 'Pull-Up', 1, NULL, '2025-05-02 17:13:09', 3, 30, 0.00, 3.0),
(21, 31, 1, 'Pull-Up', 1, NULL, '2025-05-02 18:22:26', 3, 24, 0.00, 4.3),
(22, 32, 1, 'Bench Press', 1, NULL, '2025-05-02 18:23:32', 3, 24, 0.00, 5.0),
(23, 32, 1, 'Push-Up', 2, NULL, '2025-05-02 18:23:32', 3, 24, 0.00, 5.0),
(24, 32, 1, 'Jumping Jacks', 3, NULL, '2025-05-02 18:23:32', 3, 24, 0.00, 5.0),
(25, 33, 1, 'Pull-Up', 1, NULL, '2025-05-02 19:12:37', 2, 16, 0.00, 3.0),
(26, 34, 1, 'Pull-Up', 1, NULL, '2025-05-02 19:51:55', 3, 24, 0.00, 3.0),
(27, 35, 1, 'Pull-Up', 1, NULL, '2025-05-03 12:55:27', 3, 24, 0.00, 3.0),
(28, 36, 1, 'Pull-Up', 1, NULL, '2025-05-03 14:21:10', 3, 24, 0.00, 5.0),
(29, 37, 1, 'Pull-Up', 1, NULL, '2025-05-03 14:33:02', 3, 24, 0.00, 2.3),
(30, 38, 1, 'Pull-Up', 1, NULL, '2025-05-03 15:09:10', 3, 24, 0.00, 3.0),
(31, 39, 1, 'Pull-Up', 1, NULL, '2025-05-03 15:13:24', 3, 24, 0.00, 2.3),
(32, 40, 1, 'Pull-Up', 1, NULL, '2025-05-03 15:24:45', 2, 16, 0.00, 2.0),
(33, 41, 1, 'Pull-Up', 1, NULL, '2025-05-03 15:30:15', 3, 24, 0.00, 3.0),
(34, 42, 1, 'Pull-Up', 1, NULL, '2025-05-03 16:07:52', 3, 24, 0.00, 3.0),
(35, 43, 1, 'Pull-Up', 1, NULL, '2025-05-03 16:09:52', 3, 24, 0.00, 3.0),
(36, 44, 1, 'Bench Press', 1, NULL, '2025-05-03 16:10:51', 2, 16, 0.00, 2.0),
(37, 44, 1, 'Deadlift', 2, NULL, '2025-05-03 16:10:51', 1, 8, 0.00, 3.0),
(38, 44, 1, 'Dumbbell Shoulder Press', 3, NULL, '2025-05-03 16:10:51', 3, 24, 0.00, 1.0),
(39, 45, 1, 'Pull-Up', 1, NULL, '2025-05-03 16:24:56', 3, 24, 0.00, 1.7),
(40, 46, 1, 'Pull-Up', 1, NULL, '2025-05-03 17:17:01', 3, 0, 0.00, 3.0),
(41, 47, 1, 'Pull-Up', 1, NULL, '2025-05-03 17:40:34', 2, 16, 0.00, 3.0),
(42, 48, 1, 'Pull-Up', 1, NULL, '2025-05-03 17:47:20', 3, 53, 1555.00, 3.0),
(43, 49, 1, 'Pull-Up', 1, NULL, '2025-05-03 17:59:49', 3, 54, 540.00, 1.0),
(44, 50, 1, 'Pull-Up', 1, NULL, '2025-05-03 19:51:34', 3, 24, 240.00, 3.0),
(45, 51, 1, 'Pull-Up', 1, NULL, '2025-05-03 19:56:24', 3, 24, 240.00, 3.0),
(46, 52, 1, 'Pull-Up', 1, NULL, '2025-05-03 20:01:35', 3, 24, 240.00, 3.0),
(47, 53, 1, 'Pull-Up', 1, NULL, '2025-05-03 20:10:17', 3, 24, 976.00, 3.0),
(48, 54, 1, 'Bench Press', 1, NULL, '2025-05-03 20:16:31', 3, 24, 240.00, 3.0),
(49, 54, 1, 'Push-Up', 2, NULL, '2025-05-03 20:16:31', 3, 34, 6316.00, 3.0),
(50, 55, 1, 'Pull-Up', 1, NULL, '2025-05-03 20:30:31', 3, 24, 480.00, 3.0),
(51, 56, 1, 'Pull-Up', 1, NULL, '2025-05-03 20:41:57', 3, 24, 2448.00, 3.0),
(52, 57, 1, 'Pull-Up', 1, NULL, '2025-05-04 09:49:07', 3, 27, 2727.00, 1.7),
(53, 58, 1, 'Pull-Up', 1, NULL, '2025-05-04 09:56:22', 3, 24, 240.00, 3.0),
(54, 59, 1, 'Pull-Up', 1, NULL, '2025-05-04 10:01:07', 2, 16, 160.00, 3.0),
(55, 60, 1, 'Bench Press', 1, NULL, '2025-05-04 10:41:16', 3, 24, 1240.00, 3.7),
(56, 60, 1, 'Push-Up', 2, NULL, '2025-05-04 10:41:16', 3, 60, 0.00, 1.0),
(57, 60, 1, 'Jumping Jacks', 3, NULL, '2025-05-04 10:41:16', 3, 30, 0.00, 5.0),
(58, 61, 1, 'Pull-Up', 1, NULL, '2025-05-04 10:48:15', 3, 30, 300.00, 3.0),
(59, 62, 1, 'Pull-Up', 1, NULL, '2025-05-04 13:10:36', 3, 24, 320.00, 2.3),
(60, 63, 1, 'Pull-Up', 1, NULL, '2025-05-04 13:11:17', 3, 24, 960.00, 3.0),
(61, 64, 1, 'Pull-Up', 1, NULL, '2025-05-04 16:06:02', 3, 36, 432.00, 0.0),
(62, 65, 1, 'Pull-Up', 1, NULL, '2025-05-04 16:22:33', 3, 25, 320.00, 0.0),
(63, 66, 1, 'Bench Press', 1, NULL, '2025-05-04 17:30:28', 3, 36, 652.00, 0.0),
(64, 66, 1, 'Push-Up', 2, NULL, '2025-05-04 17:30:28', 3, 62, 1504.00, 0.0),
(65, 67, 1, 'Pull-Up', 1, NULL, '2025-05-04 17:36:22', 3, 36, 432.00, 0.0),
(66, 68, 1, 'Pull-Up', 1, NULL, '2025-05-05 17:34:31', 3, 45, 540.00, 0.0),
(67, 68, 1, 'Push-Up', 2, NULL, '2025-05-05 17:34:31', 3, 36, 432.00, 0.0),
(68, 68, 1, 'Plank', 3, NULL, '2025-05-05 17:34:31', 3, 36, 432.00, 0.0),
(69, 68, 1, 'Jumping Jacks', 4, NULL, '2025-05-05 17:34:31', 3, 36, 432.00, 0.0),
(70, 68, 1, 'Bench Press', 5, NULL, '2025-05-05 17:34:31', 3, 36, 432.00, 0.0),
(71, 68, 1, 'Deadlift', 6, NULL, '2025-05-05 17:34:31', 3, 36, 432.00, 0.0),
(72, 68, 1, 'Dumbbell Shoulder Press', 7, NULL, '2025-05-05 17:34:31', 5, 60, 720.00, 0.0),
(73, 69, 1, 'Pull-Up', 1, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(74, 69, 1, 'Push-Up', 2, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(75, 69, 1, 'Plank', 3, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(76, 69, 1, 'Jumping Jacks', 4, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(77, 69, 1, 'Bench Press', 5, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(78, 69, 1, 'Deadlift', 6, NULL, '2025-05-05 18:17:54', 3, 36, 432.00, 3.0),
(79, 69, 1, 'Dumbbell Shoulder Press', 7, NULL, '2025-05-05 18:17:54', 5, 60, 720.00, 3.0),
(80, 70, 1, 'Pull-Up', 1, NULL, '2025-05-07 12:31:03', 3, 36, 564.00, 0.0),
(81, 71, 1, 'Bench Press', 1, NULL, '2025-05-11 11:33:41', 3, 36, 432.00, 0.0),
(82, 71, 1, 'Squat', 2, NULL, '2025-05-11 11:33:41', 3, 36, 432.00, 0.0),
(83, 72, 1, 'Bench Press', 1, NULL, '2025-05-11 11:34:33', 3, 36, 432.00, 3.0),
(84, 72, 1, 'Squat', 2, NULL, '2025-05-11 11:34:33', 3, 36, 432.00, 3.0),
(86, 74, 13, 'Bench Press', 1, NULL, '2025-05-25 19:47:24', 3, 36, 432.00, 0.0),
(87, 74, 13, 'Squat', 2, NULL, '2025-05-25 19:47:24', 3, 36, 432.00, 0.0),
(88, 74, 13, 'Deadlift', 3, NULL, '2025-05-25 19:47:24', 3, 36, 432.00, 0.0),
(89, 75, 14, 'Bench Press', 1, NULL, '2025-05-28 11:25:04', 2, 24, 288.00, 0.0),
(90, 76, 14, 'Bench Press', 1, NULL, '2025-05-28 18:15:41', 3, 46, 764.50, 3.3),
(91, 76, 14, 'Deadlift', 2, NULL, '2025-05-28 18:15:41', 3, 51, 1249.50, 3.0),
(92, 76, 14, 'Squat', 3, NULL, '2025-05-28 18:15:41', 3, 51, 1249.50, 3.0),
(93, 76, 14, 'Dumbbell Shoulder Press', 4, NULL, '2025-05-28 18:15:41', 3, 51, 1249.50, 3.0),
(94, 77, 15, 'Bench Press', 1, NULL, '2025-05-29 17:23:04', 1, 12, 144.00, 0.0),
(95, 78, 16, 'Bench Press', 1, NULL, '2025-05-30 10:11:54', 3, 38, 986.00, 0.0),
(96, 78, 16, 'Squat', 2, NULL, '2025-05-30 10:11:54', 2, 28, 336.00, 0.0),
(97, 79, 16, 'Bench Press', 1, NULL, '2025-05-30 18:56:09', 3, 27, 437.00, 0.0),
(98, 79, 16, 'Push-Up', 2, NULL, '2025-05-30 18:56:09', 3, 42, 588.00, 0.0),
(99, 80, 16, 'Bench Press', 1, NULL, '2025-05-30 19:25:42', 1, 12, 144.00, 0.0),
(100, 81, 16, 'Bench Press', 1, NULL, '2025-05-30 19:26:15', 1, 12, 144.00, 0.0),
(101, 82, 17, 'Bench Press', 1, NULL, '2025-05-30 19:30:35', 3, 36, 432.00, 0.0),
(102, 82, 17, 'Push-Up', 2, NULL, '2025-05-30 19:30:35', 3, 36, 432.00, 0.0),
(103, 82, 17, 'Jumping Jacks', 3, NULL, '2025-05-30 19:30:35', 3, 36, 432.00, 0.0),
(104, 83, 17, 'Bench Press', 1, NULL, '2025-05-30 19:34:22', 3, 36, 432.00, 0.0),
(105, 83, 17, 'Push-Up', 2, NULL, '2025-05-30 19:34:22', 3, 36, 432.00, 0.0),
(106, 83, 17, 'Jumping Jacks', 3, NULL, '2025-05-30 19:34:22', 3, 36, 432.00, 0.0),
(107, 84, 17, 'Pull-Up', 1, NULL, '2025-05-30 19:47:58', 3, 36, 432.00, 0.0),
(108, 85, 18, 'Bench Press', 1, NULL, '2025-05-31 16:57:44', 2, 24, 288.00, 0.0),
(109, 86, 1, 'Bench Press', 1, NULL, '2025-06-01 21:19:15', 4, 48, 576.00, 0.0),
(110, 87, 1, 'Bench Press', 1, NULL, '2025-06-02 00:03:10', 1, 12, 144.00, 0.0),
(118, 90, 20, 'Bench Press', 1, NULL, '2025-06-02 05:00:01', 3, 37, 457.00, 3.0),
(119, 90, 20, 'Squat', 2, NULL, '2025-06-02 05:00:01', 3, 27, 321.00, 3.0),
(120, 90, 20, 'Deadlift', 3, NULL, '2025-06-02 05:00:01', 3, 79, 2589.00, 3.0),
(121, 90, 20, 'Russian Twisterss2', 4, NULL, '2025-06-02 05:00:01', 3, 66, 1694.00, 3.0),
(122, 91, 21, 'Bench Press', 1, NULL, '2025-06-02 05:13:30', 3, 27, 302.00, 3.0),
(123, 91, 21, 'Squat', 2, NULL, '2025-06-02 05:13:30', 3, 38, 17395.00, 3.0),
(124, 91, 21, 'Deadlift', 3, NULL, '2025-06-02 05:13:30', 3, 78, 2102.00, 3.0),
(125, 92, 23, 'Bench Press', 1, NULL, '2025-06-02 08:05:00', 3, 30, 832.00, 3.0),
(126, 92, 23, 'Squat', 2, NULL, '2025-06-02 08:05:00', 3, 99, 3509.00, 3.0),
(127, 92, 23, 'Deadlift', 3, NULL, '2025-06-02 08:05:00', 3, 198, 13310.00, 3.0),
(128, 92, 23, 'Jumping Jacks', 4, NULL, '2025-06-02 08:05:00', 3, 162, 10532.00, 3.0),
(129, 92, 23, 'Push-Up', 5, NULL, '2025-06-02 08:05:00', 3, 126, 5292.00, 3.0),
(132, 94, 1, 'Pull-Up', 1, NULL, '2025-06-06 18:58:28', 3, 360, 4320.00, 3.7),
(133, 95, 1, 'Bench Press', 1, NULL, '2025-06-08 10:44:29', 3, 36, 432.00, 3.0),
(134, 95, 1, 'Bicep Curls', 2, NULL, '2025-06-08 10:44:29', 3, 36, 432.00, 3.0),
(135, 95, 1, 'Deadlift', 3, NULL, '2025-06-08 10:44:29', 2, 24, 288.00, 3.5),
(136, 96, 28, 'Squat', 1, NULL, '2025-06-08 16:19:51', 1, 12, 144.00, 3.0),
(137, 97, 1, 'Bench Press', 1, NULL, '2025-06-08 17:03:31', 3, 36, 432.00, 3.0),
(138, 97, 1, 'Bicep Curls', 2, NULL, '2025-06-08 17:03:31', 1, 12, 144.00, 3.0);

-- --------------------------------------------------------

--
-- Table structure for table `workout_sets`
--

CREATE TABLE `workout_sets` (
  `id` int NOT NULL,
  `workout_exercise_id` int NOT NULL,
  `set_number` int NOT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `reps` int DEFAULT NULL,
  `rpe` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_sets`
--

INSERT INTO `workout_sets` (`id`, `workout_exercise_id`, `set_number`, `weight`, `reps`, `rpe`) VALUES
(1, 20, 1, 0.00, 10, 3),
(2, 20, 2, 0.00, 10, 3),
(3, 20, 3, 0.00, 10, 3),
(4, 21, 1, 0.00, 8, 3),
(5, 21, 2, 0.00, 8, 5),
(6, 21, 3, 0.00, 8, 5),
(7, 22, 1, 0.00, 8, 5),
(8, 22, 2, 0.00, 8, 5),
(9, 22, 3, 0.00, 8, 5),
(10, 23, 1, 0.00, 8, 5),
(11, 23, 2, 0.00, 8, 5),
(12, 23, 3, 0.00, 8, 5),
(13, 24, 1, 0.00, 8, 5),
(14, 24, 2, 0.00, 8, 5),
(15, 24, 3, 0.00, 8, 5),
(16, 25, 1, 0.00, 8, 3),
(17, 25, 2, 0.00, 8, 3),
(18, 26, 1, 0.00, 8, 3),
(19, 26, 2, 0.00, 8, 3),
(20, 26, 3, 0.00, 8, 3),
(21, 27, 1, 0.00, 8, 3),
(22, 27, 2, 0.00, 8, 3),
(23, 27, 3, 0.00, 8, 3),
(24, 28, 1, 0.00, 8, 5),
(25, 28, 2, 0.00, 8, 5),
(26, 28, 3, 0.00, 8, 5),
(27, 29, 1, 0.00, 8, 3),
(28, 29, 2, 0.00, 8, 3),
(29, 29, 3, 0.00, 8, 1),
(30, 30, 1, 0.00, 8, 3),
(31, 30, 2, 0.00, 8, 3),
(32, 30, 3, 0.00, 8, 3),
(33, 31, 1, 0.00, 8, 1),
(34, 31, 2, 0.00, 8, 3),
(35, 31, 3, 0.00, 8, 3),
(36, 32, 1, 0.00, 8, 3),
(37, 32, 2, 0.00, 8, 1),
(38, 33, 1, 0.00, 8, 3),
(39, 33, 2, 0.00, 8, 3),
(40, 33, 3, 0.00, 8, 3),
(41, 34, 1, 0.00, 8, 3),
(42, 34, 2, 0.00, 8, 3),
(43, 34, 3, 0.00, 8, 3),
(44, 35, 1, 0.00, 8, 3),
(45, 35, 2, 0.00, 8, 3),
(46, 35, 3, 0.00, 8, 3),
(47, 36, 1, 0.00, 8, 3),
(48, 36, 2, 0.00, 8, 1),
(49, 37, 1, 0.00, 8, 3),
(50, 38, 1, 0.00, 8, 1),
(51, 38, 2, 0.00, 8, 1),
(52, 38, 3, 0.00, 8, 1),
(53, 39, 1, 0.00, 8, 3),
(54, 39, 2, 0.00, 8, 1),
(55, 39, 3, 0.00, 8, 1),
(56, 40, 1, 0.00, 0, 3),
(57, 40, 2, 0.00, 0, 3),
(58, 40, 3, 0.00, 0, 3),
(59, 41, 1, 0.00, 8, 3),
(60, 41, 2, 0.00, 8, 3),
(61, 42, 1, 30.00, 18, 3),
(62, 42, 2, 32.00, 20, 3),
(63, 42, 3, 25.00, 15, 3),
(64, 43, 1, 10.00, 18, 1),
(65, 43, 2, 10.00, 18, 1),
(66, 43, 3, 10.00, 18, 1),
(67, 44, 1, 10.00, 8, 3),
(68, 44, 2, 10.00, 8, 3),
(69, 44, 3, 10.00, 8, 3),
(70, 45, 1, 10.00, 8, 3),
(71, 45, 2, 10.00, 8, 3),
(72, 45, 3, 10.00, 8, 3),
(73, 46, 1, 10.00, 8, 3),
(74, 46, 2, 10.00, 8, 3),
(75, 46, 3, 10.00, 8, 3),
(76, 47, 1, 10.00, 8, 3),
(77, 47, 2, 10.00, 8, 3),
(78, 47, 3, 102.00, 8, 3),
(79, 48, 1, 10.00, 8, 3),
(80, 48, 2, 10.00, 8, 3),
(81, 48, 3, 10.00, 8, 3),
(82, 49, 1, 10.00, 8, 3),
(83, 49, 2, 10.00, 8, 3),
(84, 49, 3, 342.00, 18, 3),
(85, 50, 1, 20.00, 8, 3),
(86, 50, 2, 20.00, 8, 3),
(87, 50, 3, 20.00, 8, 3),
(88, 51, 1, 102.00, 8, 3),
(89, 51, 2, 102.00, 8, 3),
(90, 51, 3, 102.00, 8, 3),
(91, 52, 1, 101.00, 9, 3),
(92, 52, 2, 101.00, 9, 1),
(93, 52, 3, 101.00, 9, 1),
(94, 53, 1, 10.00, 8, 3),
(95, 53, 2, 10.00, 8, 3),
(96, 53, 3, 10.00, 8, 3),
(97, 54, 1, 10.00, 8, 3),
(98, 54, 2, 10.00, 8, 3),
(99, 55, 1, 40.00, 13, 5),
(100, 55, 2, 60.00, 9, 3),
(101, 55, 3, 90.00, 2, 3),
(102, 56, 1, 0.00, 30, 1),
(103, 56, 2, 0.00, 20, 1),
(104, 56, 3, 0.00, 10, 1),
(105, 57, 1, 0.00, 10, 5),
(106, 57, 2, 0.00, 10, 5),
(107, 57, 3, 0.00, 10, 5),
(108, 58, 1, 10.00, 10, 3),
(109, 58, 2, 10.00, 10, 3),
(110, 58, 3, 10.00, 10, 3),
(111, 59, 1, 0.00, 8, 3),
(112, 59, 2, 0.00, 8, 1),
(113, 59, 3, 40.00, 8, 3),
(114, 60, 1, 40.00, 8, 3),
(115, 60, 2, 40.00, 8, 3),
(116, 60, 3, 40.00, 8, 3),
(117, 61, 1, 12.00, 12, NULL),
(118, 61, 2, 12.00, 12, NULL),
(119, 61, 3, 12.00, 12, NULL),
(120, 62, 1, 12.00, 12, NULL),
(121, 62, 2, 12.00, 12, NULL),
(122, 62, 3, 32.00, 1, NULL),
(123, 63, 1, 12.00, 2, NULL),
(124, 63, 2, 22.00, 22, NULL),
(125, 63, 3, 12.00, 12, NULL),
(126, 64, 1, 22.00, 22, NULL),
(127, 64, 2, 30.00, 30, NULL),
(128, 64, 3, 12.00, 10, NULL),
(129, 65, 1, 12.00, 12, NULL),
(130, 65, 2, 12.00, 12, NULL),
(131, 65, 3, 12.00, 12, NULL),
(132, 66, 1, 12.00, 12, NULL),
(133, 66, 2, 12.00, 12, NULL),
(134, 66, 3, 12.00, 21, NULL),
(135, 67, 1, 12.00, 12, NULL),
(136, 67, 2, 12.00, 12, NULL),
(137, 67, 3, 12.00, 12, NULL),
(138, 68, 1, 12.00, 12, NULL),
(139, 68, 2, 12.00, 12, NULL),
(140, 68, 3, 12.00, 12, NULL),
(141, 69, 1, 12.00, 12, NULL),
(142, 69, 2, 12.00, 12, NULL),
(143, 69, 3, 12.00, 12, NULL),
(144, 70, 1, 12.00, 12, NULL),
(145, 70, 2, 12.00, 12, NULL),
(146, 70, 3, 12.00, 12, NULL),
(147, 71, 1, 12.00, 12, NULL),
(148, 71, 2, 12.00, 12, NULL),
(149, 71, 3, 12.00, 12, NULL),
(150, 72, 1, 12.00, 12, NULL),
(151, 72, 2, 12.00, 12, NULL),
(152, 72, 3, 12.00, 12, NULL),
(153, 72, 4, 12.00, 12, NULL),
(154, 72, 5, 12.00, 12, NULL),
(155, 73, 1, 12.00, 12, 3),
(156, 73, 2, 12.00, 12, 3),
(157, 73, 3, 12.00, 12, 3),
(158, 74, 1, 12.00, 12, 3),
(159, 74, 2, 12.00, 12, 3),
(160, 74, 3, 12.00, 12, 3),
(161, 75, 1, 12.00, 12, 3),
(162, 75, 2, 12.00, 12, 3),
(163, 75, 3, 12.00, 12, 3),
(164, 76, 1, 12.00, 12, 3),
(165, 76, 2, 12.00, 12, 3),
(166, 76, 3, 12.00, 12, 3),
(167, 77, 1, 12.00, 12, 3),
(168, 77, 2, 12.00, 12, 3),
(169, 77, 3, 12.00, 12, 3),
(170, 78, 1, 12.00, 12, 3),
(171, 78, 2, 12.00, 12, 3),
(172, 78, 3, 12.00, 12, 3),
(173, 79, 1, 12.00, 12, 3),
(174, 79, 2, 12.00, 12, 3),
(175, 79, 3, 12.00, 12, 3),
(176, 79, 4, 12.00, 12, 3),
(177, 79, 5, 12.00, 12, 3),
(178, 80, 1, 23.00, 12, NULL),
(179, 80, 2, 12.00, 12, NULL),
(180, 80, 3, 12.00, 12, NULL),
(181, 81, 1, 12.00, 12, NULL),
(182, 81, 2, 12.00, 12, NULL),
(183, 81, 3, 12.00, 12, NULL),
(184, 82, 1, 12.00, 12, NULL),
(185, 82, 2, 12.00, 12, NULL),
(186, 82, 3, 12.00, 12, NULL),
(187, 83, 1, 12.00, 12, 3),
(188, 83, 2, 12.00, 12, 3),
(189, 83, 3, 12.00, 12, 3),
(190, 84, 1, 12.00, 12, 3),
(191, 84, 2, 12.00, 12, 3),
(192, 84, 3, 12.00, 12, 3),
(195, 86, 1, 12.00, 12, NULL),
(196, 86, 2, 12.00, 12, NULL),
(197, 86, 3, 12.00, 12, NULL),
(198, 87, 1, 12.00, 12, NULL),
(199, 87, 2, 12.00, 12, NULL),
(200, 87, 3, 12.00, 12, NULL),
(201, 88, 1, 12.00, 12, NULL),
(202, 88, 2, 12.00, 12, NULL),
(203, 88, 3, 12.00, 12, NULL),
(204, 89, 1, 12.00, 12, NULL),
(205, 89, 2, 12.00, 12, NULL),
(206, 90, 1, 12.00, 12, 4),
(207, 90, 2, 12.00, 17, 3),
(208, 90, 3, 24.50, 17, 3),
(209, 91, 1, 24.50, 17, 3),
(210, 91, 2, 24.50, 17, 3),
(211, 91, 3, 24.50, 17, 3),
(212, 92, 1, 24.50, 17, 3),
(213, 92, 2, 24.50, 17, 3),
(214, 92, 3, 24.50, 17, 3),
(215, 93, 1, 24.50, 17, 3),
(216, 93, 2, 24.50, 17, 3),
(217, 93, 3, 24.50, 17, 3),
(218, 94, 1, 12.00, 12, NULL),
(219, 95, 1, 55.00, 12, NULL),
(220, 95, 2, 13.00, 14, NULL),
(221, 95, 3, 12.00, 12, NULL),
(222, 96, 1, 12.00, 13, NULL),
(223, 96, 2, 12.00, 15, NULL),
(224, 97, 1, 13.00, 13, NULL),
(225, 97, 2, 99.00, 1, NULL),
(226, 97, 3, 13.00, 13, NULL),
(227, 98, 1, 14.00, 14, NULL),
(228, 98, 2, 14.00, 14, NULL),
(229, 98, 3, 14.00, 14, NULL),
(230, 99, 1, 12.00, 12, NULL),
(231, 100, 1, 12.00, 12, NULL),
(232, 101, 1, 12.00, 12, NULL),
(233, 101, 2, 12.00, 12, NULL),
(234, 101, 3, 12.00, 12, NULL),
(235, 102, 1, 12.00, 12, NULL),
(236, 102, 2, 12.00, 12, NULL),
(237, 102, 3, 12.00, 12, NULL),
(238, 103, 1, 12.00, 12, NULL),
(239, 103, 2, 12.00, 12, NULL),
(240, 103, 3, 12.00, 12, NULL),
(241, 104, 1, 12.00, 12, NULL),
(242, 104, 2, 12.00, 12, NULL),
(243, 104, 3, 12.00, 12, NULL),
(244, 105, 1, 12.00, 12, NULL),
(245, 105, 2, 12.00, 12, NULL),
(246, 105, 3, 12.00, 12, NULL),
(247, 106, 1, 12.00, 12, NULL),
(248, 106, 2, 12.00, 12, NULL),
(249, 106, 3, 12.00, 12, NULL),
(250, 107, 1, 12.00, 12, NULL),
(251, 107, 2, 12.00, 12, NULL),
(252, 107, 3, 12.00, 12, NULL),
(253, 108, 1, 12.00, 12, NULL),
(254, 108, 2, 12.00, 12, NULL),
(255, 109, 1, 12.00, 12, NULL),
(256, 109, 2, 12.00, 12, NULL),
(257, 109, 3, 12.00, 12, NULL),
(258, 109, 4, 12.00, 12, NULL),
(259, 110, 1, 12.00, 12, NULL),
(275, 118, 1, 12.00, 12, 3),
(276, 118, 2, 12.00, 12, 3),
(277, 118, 3, 13.00, 13, 3),
(278, 119, 1, 14.00, 14, 3),
(279, 119, 2, 11.00, 11, 3),
(280, 119, 3, 2.00, 2, 3),
(281, 120, 1, 13.00, 13, 3),
(282, 120, 2, 22.00, 22, 3),
(283, 120, 3, 44.00, 44, 3),
(284, 121, 1, 33.00, 33, 3),
(285, 121, 2, 22.00, 22, 3),
(286, 121, 3, 11.00, 11, 3),
(287, 122, 1, 1.00, 2, 3),
(288, 122, 2, 12.00, 13, 3),
(289, 122, 3, 12.00, 12, 3),
(290, 123, 1, 12.00, 12, 3),
(291, 123, 2, 1314.00, 13, 3),
(292, 123, 3, 13.00, 13, 3),
(293, 124, 1, 22.00, 22, 3),
(294, 124, 2, 33.00, 33, 3),
(295, 124, 3, 23.00, 23, 3),
(296, 125, 1, 12.00, 12, 3),
(297, 125, 2, 123.00, 4, 3),
(298, 125, 3, 14.00, 14, 3),
(299, 126, 1, 22.00, 22, 3),
(300, 126, 2, 33.00, 33, 3),
(301, 126, 3, 44.00, 44, 3),
(302, 127, 1, 55.00, 55, 3),
(303, 127, 2, 66.00, 66, 3),
(304, 127, 3, 77.00, 77, 3),
(305, 128, 1, 88.00, 88, 3),
(306, 128, 2, 32.00, 32, 3),
(307, 128, 3, 42.00, 42, 3),
(308, 129, 1, 42.00, 42, 3),
(309, 129, 2, 42.00, 42, 3),
(310, 129, 3, 42.00, 42, 3),
(317, 132, 1, 12.00, 120, 3),
(318, 132, 2, 12.00, 120, 5),
(319, 132, 3, 12.00, 120, 3),
(320, 133, 1, 12.00, 12, 3),
(321, 133, 2, 12.00, 12, 3),
(322, 133, 3, 12.00, 12, 3),
(323, 134, 1, 12.00, 12, 3),
(324, 134, 2, 12.00, 12, 3),
(325, 134, 3, 12.00, 12, 3),
(326, 135, 1, 12.00, 12, 3),
(327, 135, 2, 12.00, 12, 4),
(328, 136, 1, 12.00, 12, 3),
(329, 137, 1, 12.00, 12, 3),
(330, 137, 2, 12.00, 12, 3),
(331, 137, 3, 12.00, 12, 3),
(332, 138, 1, 12.00, 12, 3);

-- --------------------------------------------------------

--
-- Table structure for table `workout_splits`
--

CREATE TABLE `workout_splits` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `data` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_splits`
--

INSERT INTO `workout_splits` (`id`, `user_id`, `name`, `data`, `created_at`, `updated_at`) VALUES
(1, 1, 'bro split', '[{\"type\":\"push\",\"template_id\":8},{\"type\":\"pull\",\"template_id\":7},{\"type\":\"rest\"},{\"type\":\"push\",\"template_id\":8},{\"type\":\"pull\",\"template_id\":8},{\"type\":\"rest\"},{\"type\":\"rest\"}]', '2025-05-07 21:32:59', '2025-05-07 21:32:59'),
(2, 13, 'test', '[{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"strength training\",\"template_id\":19}]', '2025-05-25 18:07:52', '2025-05-25 18:07:52'),
(3, 13, 'test', '[{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"strength training\",\"template_id\":19}]', '2025-05-25 19:25:33', '2025-05-25 19:25:33'),
(4, 13, 'test', '[{\"type\":\"rest\"},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null}]', '2025-05-25 19:48:33', '2025-05-25 19:48:33'),
(5, 13, 'tesintg', '[{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"strength training\",\"template_id\":19},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":19}]', '2025-05-25 21:15:55', '2025-05-25 21:15:55'),
(6, 13, 'cascasc', '[{\"type\":\"rest\"},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null}]', '2025-05-25 21:27:39', '2025-05-25 21:27:39'),
(7, 14, 'qrwr3qwrq', '[{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":20}]', '2025-05-28 14:26:09', '2025-05-28 14:26:09'),
(8, 14, 'fff3f34f34', '[{\"type\":\"rest\"},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null},{\"type\":\"\",\"template_id\":null}]', '2025-05-28 20:35:37', '2025-05-28 20:35:37'),
(9, 14, 'test', '[{\"type\":\"strength training\",\"template_id\":21},{\"type\":\"strength training\",\"template_id\":21},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"rest\"}]', '2025-05-28 20:35:53', '2025-05-28 20:35:53'),
(10, 14, '6stxfcghvjbk', '[{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":21},{\"type\":\"strength training\",\"template_id\":21},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"strength training\",\"template_id\":20},{\"type\":\"rest\"},{\"type\":\"rest\"}]', '2025-05-29 18:44:18', '2025-05-29 18:44:18'),
(11, 15, 'yt', '[{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":22},{\"type\":\"strength training\",\"template_id\":22},{\"type\":\"strength training\",\"template_id\":22},{\"type\":\"strength training\",\"template_id\":22},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":22}]', '2025-05-29 20:23:41', '2025-05-29 20:23:41'),
(12, 16, 'gwerwefgwfeg', '[{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"cardio\",\"template_id\":23},{\"type\":\"cardio\",\"template_id\":23},{\"type\":\"cardio\",\"template_id\":23},{\"type\":\"rest\"},{\"type\":\"rest\"}]', '2025-05-30 13:12:38', '2025-05-30 13:12:38'),
(13, 18, 'dfg', '[{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24}]', '2025-05-31 19:58:14', '2025-05-31 19:58:14'),
(14, 18, 'test', '[{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"cardio\",\"template_id\":24},{\"type\":\"cardio\",\"template_id\":24}]', '2025-06-01 19:56:38', '2025-06-01 19:56:38'),
(15, 1, 'wef', '[{\"type\":\"rest\"},{\"type\":\"cardio\",\"template_id\":13},{\"type\":\"functional\",\"template_id\":26},{\"type\":\"custom\",\"template_id\":7,\"custom_name\":\"pulk\"},{\"type\":\"strength training\",\"template_id\":25},{\"type\":\"strength training\",\"template_id\":15},{\"type\":\"custom\",\"template_id\":8,\"custom_name\":\"push day\"}]', '2025-06-02 03:00:39', '2025-06-02 03:00:39'),
(16, 1, 'dwdwed', '[{\"type\":\"cardio\",\"template_id\":13},{\"type\":\"cardio\",\"template_id\":13},{\"type\":\"custom\",\"template_id\":9,\"custom_name\":\"push day\"},{\"type\":\"strength training\",\"template_id\":25},{\"type\":\"strength training\",\"template_id\":15},{\"type\":\"strength training\",\"template_id\":16},{\"type\":\"cardio\",\"template_id\":13}]', '2025-06-02 03:00:54', '2025-06-02 03:00:54'),
(18, 20, 'test', '[{\"type\":\"strength training\",\"template_id\":28},{\"type\":\"strength training\",\"template_id\":28},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":28},{\"type\":\"strength training\",\"template_id\":28},{\"type\":\"strength training\",\"template_id\":28}]', '2025-06-02 08:04:55', '2025-06-02 08:04:55'),
(19, 21, 'test', '[{\"type\":\"strength training\",\"template_id\":29},{\"type\":\"strength training\",\"template_id\":29},{\"type\":\"strength training\",\"template_id\":29},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"rest\"},{\"type\":\"strength training\",\"template_id\":29}]', '2025-06-02 08:16:20', '2025-06-02 08:16:20'),
(20, 23, 'test', '[{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"strength training\",\"template_id\":30},{\"type\":\"rest\"}]', '2025-06-02 11:09:19', '2025-06-02 11:09:19');

-- --------------------------------------------------------

--
-- Table structure for table `workout_templates`
--

CREATE TABLE `workout_templates` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `difficulty` varchar(50) DEFAULT NULL,
  `estimated_time` int DEFAULT NULL,
  `rest_time` float DEFAULT '1',
  `category` varchar(50) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_templates`
--

INSERT INTO `workout_templates` (`id`, `name`, `description`, `difficulty`, `estimated_time`, `rest_time`, `category`, `user_id`, `created_at`, `updated_at`) VALUES
(6, 'testtt', 'testtt', 'intermediate', 30, 1, NULL, 1, '2025-04-15 10:39:11', '2025-04-15 10:39:11'),
(7, 'pulk', 'tttt', 'intermediate', 30, 1, NULL, 1, '2025-04-15 11:40:14', '2025-04-15 11:40:14'),
(8, 'push day', 'push', 'advanced', 30, 1, NULL, 1, '2025-04-15 16:03:13', '2025-04-15 16:03:13'),
(9, 'push day', 'test', 'advanced', 17, 1, 'Strength Training', 1, '2025-04-16 17:46:38', '2025-04-16 17:46:38'),
(13, 'gest', 'gest', 'advanced', 120, 1, 'cardio', 1, '2025-05-07 15:15:32', '2025-05-07 15:15:59'),
(15, 'testser4', 'setsetser', 'intermediate', 18, 1, 'Strength Training', 1, '2025-05-17 14:28:15', '2025-05-22 22:24:43'),
(16, 'ffwef', 'wefwefwef', 'intermediate', 20, 1, 'Strength Training', 1, '2025-05-22 22:26:30', NULL),
(17, 'test', 'test', 'intermediate', 25, 1, 'Strength Training', 1, '2025-05-24 17:43:03', NULL),
(19, 'EFW', 'EFEF32F', 'intermediate', 18, 1, 'Strength Training', 13, '2025-05-25 13:22:11', '2025-05-25 21:32:20'),
(20, 'test', 'test', 'intermediate', 48, 1, 'Strength Training', 14, '2025-05-28 14:24:27', '2025-05-28 14:24:27'),
(21, 'f3rf3', '3f34f34f3f4', 'intermediate', 6, 1, 'Strength Training', 14, '2025-05-28 15:31:27', '2025-05-28 19:00:01'),
(22, 'g', 'hhu9uh', 'intermediate', 18, 1, 'Strength Training', 15, '2025-05-29 20:22:26', '2025-05-29 20:22:26'),
(23, 'qwwqeffqwe', 'fwefwefwef', 'advanced', 36, 1, 'cardio', 16, '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(24, 'fg', 'rtrttr', 'intermediate', 24, 1, 'cardio', 18, '2025-05-31 19:57:07', '2025-05-31 19:57:07'),
(25, 'qwrwqr', 'qwqr', 'intermediate', 15, 1, 'Strength Training', 1, NULL, NULL),
(26, 'eses', 'eses', 'intermediate', 20, 1, 'Functional', 1, NULL, NULL),
(27, '12qwdqd', 'qwdqdwqw', 'Intermediate', 20, 1, 'Strength Training', 19, '2025-06-02 03:58:31', '2025-06-02 04:02:45'),
(28, 'push', 'test', 'Advanced', 48, 1, 'Strength Training', 20, '2025-06-02 07:57:32', '2025-06-02 07:57:32'),
(29, 'push', 'test', 'Intermediate', 72, 1, 'Strength Training', 21, '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(30, 'test', 'test', 'Advanced', 60, 1, 'Strength Training', 23, '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(31, 'push', 'test', 'Advanced', 72, 1, 'Strength Training', 25, '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(32, 'test', 'test', 'advanced', 17, 1, 'Cardio', 1, NULL, NULL),
(33, 'wedwed', 'dwdqwd', 'Vidējs', 30, 1, 'Strength Training', 28, '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(34, 'qweqweqwe', 'qwqwe', 'Vidējs', 12, 1, 'cardio', 28, '2025-06-08 19:27:40', '2025-06-08 19:27:40'),
(35, 'DELETEE', 'qa', 'intermediate', 15, 1, 'Bodyweight', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workout_template_exercises`
--

CREATE TABLE `workout_template_exercises` (
  `id` int NOT NULL,
  `workout_template_id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `position` int NOT NULL,
  `sets` int DEFAULT '3',
  `rest_time` int DEFAULT '60',
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_template_exercises`
--

INSERT INTO `workout_template_exercises` (`id`, `workout_template_id`, `exercise_id`, `position`, `sets`, `rest_time`, `notes`, `created_at`, `updated_at`) VALUES
(3, 6, 4, 1, 3, 60, '', '2025-04-15 10:39:11', '2025-04-15 10:39:11'),
(4, 7, 4, 1, 3, 60, '', '2025-04-15 11:40:14', '2025-04-15 11:40:14'),
(5, 8, 1, 1, 3, 60, '', '2025-04-15 16:03:13', '2025-04-15 16:03:13'),
(6, 8, 5, 2, 3, 60, '', '2025-04-15 16:03:13', '2025-04-15 16:03:13'),
(32, 13, 1, 1, 4, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(33, 13, 8, 2, 4, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(34, 13, 2, 3, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(35, 13, 9, 4, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(36, 13, 3, 5, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(37, 13, 10, 6, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(38, 13, 4, 7, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(39, 13, 7, 8, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(40, 13, 5, 9, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(41, 13, 6, 10, 3, 180, '', '2025-05-07 15:15:59', '2025-05-07 15:15:59'),
(55, 15, 1, 1, 3, 1, NULL, '2025-05-22 22:24:43', '2025-05-22 22:24:43'),
(56, 15, 2, 2, 3, 1, NULL, '2025-05-22 22:24:43', '2025-05-22 22:24:43'),
(57, 15, 3, 3, 3, 1, NULL, '2025-05-22 22:24:43', '2025-05-22 22:24:43'),
(58, 16, 1, 1, 3, 1, NULL, '2025-05-22 22:26:30', '2025-05-22 22:26:30'),
(59, 16, 8, 2, 3, 1, NULL, '2025-05-22 22:26:30', '2025-05-22 22:26:30'),
(60, 16, 4, 3, 3, 1, NULL, '2025-05-22 22:26:30', '2025-05-22 22:26:30'),
(61, 16, 2, 4, 3, 1, NULL, '2025-05-22 22:26:30', '2025-05-22 22:26:30'),
(62, 17, 1, 1, 3, 1, NULL, '2025-05-24 17:43:03', '2025-05-24 17:43:03'),
(63, 17, 9, 2, 3, 1, NULL, '2025-05-24 17:43:03', '2025-05-24 17:43:03'),
(64, 17, 3, 3, 3, 1, NULL, '2025-05-24 17:43:03', '2025-05-24 17:43:03'),
(65, 17, 8, 4, 3, 1, NULL, '2025-05-24 17:43:03', '2025-05-24 17:43:03'),
(66, 17, 4, 5, 3, 1, NULL, '2025-05-24 17:43:03', '2025-05-24 17:43:03'),
(81, 19, 1, 1, 3, 60, '', '2025-05-25 21:32:20', '2025-05-25 21:32:20'),
(82, 19, 2, 2, 3, 60, '', '2025-05-25 21:32:20', '2025-05-25 21:32:20'),
(83, 19, 3, 3, 3, 60, '', '2025-05-25 21:32:20', '2025-05-25 21:32:20'),
(84, 20, 1, 1, 3, 180, '', '2025-05-28 14:24:27', '2025-05-28 14:24:27'),
(85, 20, 3, 2, 3, 180, '', '2025-05-28 14:24:27', '2025-05-28 14:24:27'),
(86, 20, 2, 3, 3, 180, '', '2025-05-28 14:24:27', '2025-05-28 14:24:27'),
(87, 20, 8, 4, 3, 180, '', '2025-05-28 14:24:27', '2025-05-28 14:24:27'),
(89, 21, 10, 1, 3, 60, '', '2025-05-28 19:00:01', '2025-05-28 19:00:01'),
(90, 22, 1, 1, 3, 60, '', '2025-05-29 20:22:26', '2025-05-29 20:22:26'),
(91, 22, 3, 2, 3, 60, '', '2025-05-29 20:22:26', '2025-05-29 20:22:26'),
(92, 22, 2, 3, 3, 60, '', '2025-05-29 20:22:26', '2025-05-29 20:22:26'),
(93, 23, 1, 1, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(94, 23, 2, 2, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(95, 23, 3, 3, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(96, 23, 8, 4, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(97, 23, 11, 5, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(98, 23, 9, 6, 3, 60, '', '2025-05-30 13:08:59', '2025-05-30 13:08:59'),
(99, 24, 1, 1, 3, 60, '', '2025-05-31 19:57:07', '2025-05-31 19:57:07'),
(100, 24, 2, 2, 3, 60, '', '2025-05-31 19:57:07', '2025-05-31 19:57:07'),
(101, 24, 3, 3, 3, 60, '', '2025-05-31 19:57:07', '2025-05-31 19:57:07'),
(102, 24, 8, 4, 3, 60, '', '2025-05-31 19:57:07', '2025-05-31 19:57:07'),
(103, 25, 1, 1, 3, 1, NULL, '2025-06-01 23:36:13', '2025-06-01 23:36:13'),
(104, 25, 9, 2, 3, 1, NULL, '2025-06-01 23:36:13', '2025-06-01 23:36:13'),
(105, 25, 7, 3, 3, 1, NULL, '2025-06-01 23:36:13', '2025-06-01 23:36:13'),
(106, 26, 1, 1, 3, 1, NULL, '2025-06-01 23:51:06', '2025-06-01 23:51:06'),
(107, 26, 9, 2, 3, 1, NULL, '2025-06-01 23:51:06', '2025-06-01 23:51:06'),
(108, 26, 3, 3, 4, 1, NULL, '2025-06-01 23:51:06', '2025-06-01 23:51:06'),
(109, 26, 10, 4, 3, 1, NULL, '2025-06-01 23:51:06', '2025-06-01 23:51:06'),
(110, 9, 1, 1, 4, 1, NULL, '2025-06-02 03:30:19', '2025-06-02 03:30:19'),
(111, 9, 5, 2, 3, 1, NULL, '2025-06-02 03:30:19', '2025-06-02 03:30:19'),
(112, 9, 10, 3, 3, 1, NULL, '2025-06-02 03:30:19', '2025-06-02 03:30:19'),
(118, 27, 1, 1, 2, 60, '', '2025-06-02 04:02:45', '2025-06-02 04:02:45'),
(119, 27, 2, 2, 2, 60, '', '2025-06-02 04:02:45', '2025-06-02 04:02:45'),
(120, 27, 3, 3, 2, 60, '', '2025-06-02 04:02:45', '2025-06-02 04:02:45'),
(121, 27, 8, 4, 2, 60, '', '2025-06-02 04:02:45', '2025-06-02 04:02:45'),
(122, 27, 11, 5, 2, 60, '', '2025-06-02 04:02:45', '2025-06-02 04:02:45'),
(123, 28, 1, 1, 3, 180, '', '2025-06-02 07:57:32', '2025-06-02 07:57:32'),
(124, 28, 2, 2, 3, 180, '', '2025-06-02 07:57:32', '2025-06-02 07:57:32'),
(125, 28, 3, 3, 3, 180, '', '2025-06-02 07:57:32', '2025-06-02 07:57:32'),
(126, 28, 11, 4, 3, 180, '', '2025-06-02 07:57:32', '2025-06-02 07:57:32'),
(127, 29, 1, 1, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(128, 29, 2, 2, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(129, 29, 3, 3, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(130, 29, 8, 4, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(131, 29, 9, 5, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(132, 29, 15, 6, 3, 180, '', '2025-06-02 08:12:14', '2025-06-02 08:12:14'),
(133, 30, 1, 1, 3, 180, '', '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(134, 30, 2, 2, 3, 180, '', '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(135, 30, 3, 3, 3, 180, '', '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(136, 30, 10, 4, 3, 180, '', '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(137, 30, 5, 5, 3, 180, '', '2025-06-02 11:01:43', '2025-06-02 11:01:43'),
(138, 31, 1, 1, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(139, 31, 2, 2, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(140, 31, 3, 3, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(141, 31, 8, 4, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(142, 31, 9, 5, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(143, 31, 10, 6, 3, 180, '', '2025-06-02 13:48:40', '2025-06-02 13:48:40'),
(144, 32, 1, 1, 3, 1, NULL, '2025-06-02 13:55:04', '2025-06-02 13:55:04'),
(145, 32, 9, 2, 3, 1, NULL, '2025-06-02 13:55:04', '2025-06-02 13:55:04'),
(146, 32, 3, 3, 3, 1, NULL, '2025-06-02 13:55:04', '2025-06-02 13:55:04'),
(147, 33, 2, 1, 3, 60, '', '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(148, 33, 8, 2, 3, 60, '', '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(149, 33, 9, 3, 3, 60, '', '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(150, 33, 11, 4, 3, 60, '', '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(151, 33, 15, 5, 3, 60, '', '2025-06-08 18:58:04', '2025-06-08 18:58:04'),
(152, 34, 1, 1, 3, 60, '', '2025-06-08 19:27:40', '2025-06-08 19:27:40'),
(153, 34, 2, 2, 3, 60, '', '2025-06-08 19:27:40', '2025-06-08 19:27:40'),
(154, 35, 1, 1, 3, 1, NULL, '2025-06-08 19:33:38', '2025-06-08 19:33:38'),
(155, 35, 9, 2, 3, 1, NULL, '2025-06-08 19:33:38', '2025-06-08 19:33:38'),
(156, 35, 3, 3, 3, 1, NULL, '2025-06-08 19:33:38', '2025-06-08 19:33:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `body_measurements`
--
ALTER TABLE `body_measurements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipment_name` (`name`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exercise_sets`
--
ALTER TABLE `exercise_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exercise_id` (`exercise_id`),
  ADD KEY `idx_exercise_sets_user` (`user_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_goals` (`user_id`,`goal_type`),
  ADD KEY `idx_goals_deadline` (`deadline`),
  ADD KEY `idx_goals_completed` (`completed`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `scheduled_workouts`
--
ALTER TABLE `scheduled_workouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`day`,`month`,`year`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workout_user` (`user_id`,`created_at`),
  ADD KEY `idx_workout_date` (`created_at`),
  ADD KEY `idx_workout_type` (`workout_type`);

--
-- Indexes for table `workout_exercises`
--
ALTER TABLE `workout_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_id` (`workout_id`),
  ADD KEY `idx_user_exercises` (`user_id`,`exercise_name`);

--
-- Indexes for table `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_exercise_id` (`workout_exercise_id`);

--
-- Indexes for table `workout_splits`
--
ALTER TABLE `workout_splits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `workout_templates`
--
ALTER TABLE `workout_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workout_template_exercises`
--
ALTER TABLE `workout_template_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_template_id` (`workout_template_id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `body_measurements`
--
ALTER TABLE `body_measurements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `exercise_sets`
--
ALTER TABLE `exercise_sets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `scheduled_workouts`
--
ALTER TABLE `scheduled_workouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=652;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `workout_exercises`
--
ALTER TABLE `workout_exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `workout_sets`
--
ALTER TABLE `workout_sets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=333;

--
-- AUTO_INCREMENT for table `workout_splits`
--
ALTER TABLE `workout_splits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `workout_templates`
--
ALTER TABLE `workout_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `workout_template_exercises`
--
ALTER TABLE `workout_template_exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `body_measurements`
--
ALTER TABLE `body_measurements`
  ADD CONSTRAINT `body_measurements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `exercise_sets`
--
ALTER TABLE `exercise_sets`
  ADD CONSTRAINT `exercise_sets_ibfk_1` FOREIGN KEY (`exercise_id`) REFERENCES `workout_exercises` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exercise_sets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheduled_workouts`
--
ALTER TABLE `scheduled_workouts`
  ADD CONSTRAINT `scheduled_workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_exercises`
--
ALTER TABLE `workout_exercises`
  ADD CONSTRAINT `workout_exercises_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_exercises_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD CONSTRAINT `workout_sets_ibfk_1` FOREIGN KEY (`workout_exercise_id`) REFERENCES `workout_exercises` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_splits`
--
ALTER TABLE `workout_splits`
  ADD CONSTRAINT `workout_splits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_template_exercises`
--
ALTER TABLE `workout_template_exercises`
  ADD CONSTRAINT `workout_template_exercises_ibfk_1` FOREIGN KEY (`workout_template_id`) REFERENCES `workout_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_template_exercises_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
