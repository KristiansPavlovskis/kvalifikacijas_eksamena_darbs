<?php
session_start();

require_once '../assets/db_connection.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: excercises.php");
    exit;
}

$exercise_id = intval($_GET['id']);

$exercise_query = "SELECT * FROM exercises WHERE id = ?";
$exercise_stmt = mysqli_prepare($conn, $exercise_query);
mysqli_stmt_bind_param($exercise_stmt, 'i', $exercise_id);
mysqli_stmt_execute($exercise_stmt);
$exercise_result = mysqli_stmt_get_result($exercise_stmt);

if (mysqli_num_rows($exercise_result) === 0) {
    header("Location: excercises.php");
    exit;
}

$exercise = mysqli_fetch_assoc($exercise_result);

$instructions = [];
if (!empty($exercise['instructions'])) {
    $instructions = explode("\n", $exercise['instructions']);
}

$common_mistakes = [];
if (!empty($exercise['common_mistakes'])) {
    $common_mistakes = explode("\n", $exercise['common_mistakes']);
} else {
    $common_mistakes = [
        "Poor Form - Using improper form can reduce effectiveness and increase injury risk.",
        "Too Much Weight - Using weights that are too heavy often leads to compromised form.",
        "Inadequate Range of Motion - Not completing the full range of motion reduces effectiveness.",
        "Holding Breath - Remember to breathe properly throughout the exercise."
    ];
}

$benefits = [];
if (!empty($exercise['benefits'])) {
    $benefits = explode("\n", $exercise['benefits']);
} else {
    $benefits = [
        "Builds muscle strength and endurance",
        "Improves functional fitness",
        "Enhances joint stability",
        "Increases metabolic rate"
    ];
}

$muscle_data = [];
if (!empty($exercise['muscle_diagram_data'])) {
    $muscle_data = json_decode($exercise['muscle_diagram_data'], true);
}

$home_alternatives = [
    "Bodyweight Version",
    "Resistance Bands",
    "Household Items",
    "Modified Version"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - <?php echo htmlspecialchars($exercise['name']); ?></title>
    <link rel="stylesheet" href="lietotaja-view.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #e53935;
            --primary-light: #ff6f60;
            --primary-dark: #ab000d;
            --secondary: #333;
            --dark: #121212;
            --darker: #0A0A0A;
            --card-bg: #1e1e1e;
            --element-bg: #252525;
            --element-hover: #2d2d2d;
            --border-color: #333;
            --text-primary: #f5f5f5;
            --text-secondary: #b3b3b3;
            --text-muted: #757575;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 8px 15px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 15px 30px rgba(0, 0, 0, 0.4);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 15px;
            --spacing-xs: 5px;
            --spacing-sm: 10px;
            --spacing-md: 20px;
            --spacing-lg: 30px;
            --spacing-xl: 40px;
            --transition-standard: all 0.3s ease;
        }
        
        body {
            background-color: var(--dark);
            color: var(--text-primary);
            font-family: 'Poppins', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        .exercise-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }
        
        /* Page Header and Navigation */
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: linear-gradient(90deg, rgba(229, 57, 53, 0.1), transparent);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 50px;
            margin-bottom: var(--spacing-lg);
            transition: var(--transition-standard);
            border: 1px solid rgba(229, 57, 53, 0.3);
            font-weight: 500;
        }
        
        .back-button:hover {
            background: rgba(229, 57, 53, 0.2);
            transform: translateX(-5px);
        }
        
        .back-button i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        /* Hero Section Styling */
        .exercise-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            background: linear-gradient(145deg, var(--card-bg), var(--darker));
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .exercise-image {
            height: 100%;
            min-height: 450px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        
        .exercise-image::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
            pointer-events: none;
        }
        
        .exercise-info {
            padding: var(--spacing-xl);
            display: flex;
            flex-direction: column;
        }
        
        .exercise-title {
            font-family: 'Koulen', sans-serif;
            font-size: 3rem;
            margin: 0 0 var(--spacing-md);
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.1;
            position: relative;
            display: inline-block;
        }
        
        .exercise-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 60px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .exercise-description {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: var(--spacing-lg);
            color: var(--text-secondary);
        }
        
        .exercise-type {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(229, 57, 53, 0.2);
            color: var(--primary-light);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .exercise-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: var(--spacing-md);
        }
        
        .stat-card {
            background: var(--element-bg);
            padding: 15px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition-standard);
        }
        
        .stat-card:hover {
            background: var(--element-hover);
            transform: translateY(-5px);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-title {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .difficulty-indicator {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: var(--spacing-md) 0;
        }
        
        .difficulty-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            flex-grow: 1;
            overflow: hidden;
            position: relative;
        }
        
        .difficulty-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #ffc107, #e53935);
            border-radius: 4px;
            position: absolute;
            left: 0;
            top: 0;
            transition: width 1s ease-in-out;
        }
        
        .difficulty-text {
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .exercise-easy {
            color: #4caf50;
        }
        
        .exercise-intermediate, .exercise-moderate {
            color: #ffc107;
        }
        
        .exercise-advanced, .exercise-hard {
            color: #e53935;
        }
        
        .exercise-tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            overflow: hidden;
            margin-bottom: 0;
            position: sticky;
            top: 0;
            z-index: 10;
            border: 1px solid var(--border-color);
            border-bottom: none;
        }
        
        .tab-button {
            padding: 15px 25px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            cursor: pointer;
            flex: 1;
            transition: var(--transition-standard);
            position: relative;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .tab-button:hover {
            color: var(--text-primary);
            background: var(--element-bg);
        }
        
        .tab-button.active {
            color: var(--primary);
            background: var(--element-bg);
        }
        
        .tab-button.active:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
        }
        
        .tab-button i {
            font-size: 16px;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            background: var(--card-bg);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-title {
            font-family: 'Koulen', sans-serif;
            font-size: 1.8rem;
            margin: 0 0 var(--spacing-md);
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .instructions-list {
            list-style-type: none;
            counter-reset: step-counter;
            padding: 0;
        }
        
        .instruction-step {
            counter-increment: step-counter;
            position: relative;
            padding: var(--spacing-md) var(--spacing-xl) var(--spacing-md) 60px;
            margin-bottom: var(--spacing-md);
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            transition: var(--transition-standard);
            border: 1px solid var(--border-color);
        }
        
        .instruction-step:hover {
            background: var(--element-hover);
            transform: translateX(5px);
        }
        
        .instruction-step::before {
            content: counter(step-counter);
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            background: rgba(229, 57, 53, 0.2);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .instruction-text {
            line-height: 1.7;
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: var(--spacing-lg);
        }
        
        .action-button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: var(--transition-standard);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .primary-button {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(229, 57, 53, 0.3);
        }
        
        .primary-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(229, 57, 53, 0.4);
        }
        
        .secondary-button {
            background: var(--element-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .secondary-button:hover {
            background: var(--element-hover);
            transform: translateY(-3px);
        }

        .mistakes-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .mistake-item {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border-left: 4px solid #e53935;
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            transition: var(--transition-standard);
        }
        
        .mistake-item:hover {
            background: var(--element-hover);
            transform: translateX(5px);
        }
        
        .mistake-icon {
            color: #e53935;
            font-size: 1.5rem;
            margin-top: 3px;
        }
        
        .mistake-content {
            flex: 1;
        }
        
        .mistake-title {
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
            color: var(--text-primary);
        }
        
        .mistake-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .muscle-diagram {
            display: flex;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        
        .diagram-container {
            flex: 1;
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 400px;
            border: 1px solid var(--border-color);
        }
        
        .muscle-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .muscle-card {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border-left: 4px solid;
            transition: var(--transition-standard);
        }
        
        .muscle-card:hover {
            transform: translateX(5px);
            background: var(--element-hover);
        }
        
        .muscle-card.primary {
            border-color: #e53935;
        }
        
        .muscle-card.secondary {
            border-color: #4caf50;
        }
        
        .muscle-card.tertiary {
            border-color: #2196f3;
        }
        
        .muscle-name {
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .muscle-name.primary {
            color: #ff6f60;
        }
        
        .muscle-name.secondary {
            color: #81c784;
        }
        
        .muscle-name.tertiary {
            color: #64b5f6;
        }
        
        .muscle-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .equipment-guide {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--spacing-lg);
        }
        
        .equipment-image {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 250px;
            border: 1px solid var(--border-color);
        }
        
        .equipment-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .equipment-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .equipment-section {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
        }
        
        .equipment-section h3 {
            color: var(--primary-light);
            margin-top: 0;
            margin-bottom: var(--spacing-sm);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .equipment-section p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.95rem;
        }
        
        .alternative-equipment {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }
        
        .alt-equipment-tag {
            padding: 5px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50px;
            font-size: 0.9rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .variations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-md);
        }
        
        .variation-card {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: var(--transition-standard);
        }
        
        .variation-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-sm);
        }
        
        .variation-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .variation-difficulty {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .difficulty-easy {
            color: #4caf50;
        }
        
        .difficulty-moderate {
            color: #ffc107;
        }
        
        .difficulty-hard {
            color: #e53935;
        }
        
        .variation-content {
            padding: var(--spacing-md);
        }
        
        .variation-title {
            font-weight: 600;
            margin: 0 0 var(--spacing-xs);
            font-size: 1.1rem;
        }
        
        .variation-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .variation-benefits {
            font-size: 0.9rem;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .progress-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--spacing-lg);
        }
        
        .personal-records {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
        }
        
        .record-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .record-item:last-child {
            border-bottom: none;
        }
        
        .record-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .record-value {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .record-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .charts-container {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            height: 300px;
            position: relative;
        }
        
        .progress-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            flex-direction: column;
            gap: 20px;
        }
        
        .progress-placeholder i {
            font-size: 3rem;
            color: var(--text-muted);
        }
        
        .progress-placeholder p {
            color: var(--text-secondary);
            margin: 0;
            text-align: center;
        }
        
        .social-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }
        
        .community-rating {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .rating-value {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            line-height: 1;
        }
        
        .rating-text {
            color: var(--text-secondary);
            margin-top: var(--spacing-xs);
        }
        
        .rating-stars {
            margin: var(--spacing-sm) 0;
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .rating-count {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .user-reviews {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            max-height: 300px;
            overflow-y: auto;
        }
        
        .related-exercises-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }
        
        .related-exercise-card {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            overflow: hidden;
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition-standard);
            border: 1px solid var(--border-color);
        }
        
        .related-exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-sm);
        }
        
        .related-exercise-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .related-exercise-content {
            padding: var(--spacing-md);
        }
        
        .related-exercise-title {
            font-size: 1.1rem;
            margin: 0 0 5px;
            font-weight: 600;
        }
        
        .related-exercise-type {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-sm);
        }
        
        .related-exercise-muscles {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: var(--spacing-sm);
        }
        
        .muscle-chip {
            padding: 2px 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .video-section {
            margin-bottom: var(--spacing-lg);
        }
        
        .exercise-video {
            width: 100%;
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-md);
        }
        
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-thumbnails {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
            overflow-x: auto;
            padding-bottom: var(--spacing-sm);
        }
        
        .video-thumbnail {
            width: 120px;
            height: 80px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition-standard);
            flex-shrink: 0;
            border: 2px solid transparent;
        }
        
        .video-thumbnail.active {
            border-color: var(--primary);
        }
        
        .video-thumbnail:hover {
            transform: translateY(-3px);
        }
        
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        @media (max-width: 992px) {
            .exercise-header, 
            .muscle-diagram, 
            .equipment-guide,
            .progress-section,
            .social-section {
                grid-template-columns: 1fr;
            }
            
            .exercise-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                justify-content: flex-start;
            }
            
            .tab-button {
                flex: 0 0 auto;
                white-space: nowrap;
            }
        }
        
        @media (max-width: 768px) {
            .exercise-header {
                grid-template-columns: 1fr;
            }
            
            .exercise-image {
                height: 250px;
            }
            
            .exercise-title {
                font-size: 2.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .exercise-detail-container {
                padding: var(--spacing-md);
            }
            
            .variations-grid,
            .related-exercises-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .exercise-tabs {
                flex-direction: column;
                border-radius: var(--radius-lg);
            }
            
            .tab-button {
                text-align: left;
                justify-content: flex-start;
            }
            
            .tab-button.active:after {
                width: 3px;
                height: 100%;
                left: 0;
                top: 0;
            }
        }
        
        .benefit-item {
            border-left: 4px solid #4caf50;
        }
        
        .benefit-icon {
            color: #4caf50;
            font-size: 1.5rem;
            margin-top: 3px;
        }
        
        .equipment-guide {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--spacing-lg);
        }
        
        .equipment-image {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 250px;
            border: 1px solid var(--border-color);
        }
        
        .equipment-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .equipment-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .equipment-section {
            background: var(--element-bg);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
        }
        
        .equipment-section h3 {
            color: var(--primary-light);
            margin-top: 0;
            margin-bottom: var(--spacing-sm);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .equipment-section p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.95rem;
        }
        
        .alternative-equipment {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }
        
        .alt-equipment-tag {
            padding: 5px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50px;
            font-size: 0.9rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <div class="exercise-detail-container">
        <a href="excercises.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Exercise Library
        </a>

        <div class="exercise-header">
            <div class="exercise-image" style="background-image: url('<?php echo !empty($exercise['image_url']) ? $exercise['image_url'] : '../assets/images/exercise-placeholder.jpg'; ?>');">
            </div>
            <div class="exercise-info">
                <span class="exercise-type"><?php echo htmlspecialchars($exercise['exercise_type'] ?? 'Strength Training'); ?></span>
                <h1 class="exercise-title"><?php echo htmlspecialchars($exercise['name']); ?></h1>
                <p class="exercise-description"><?php echo htmlspecialchars($exercise['description']); ?></p>
                
                <div class="difficulty-indicator">
                    <span class="difficulty-text exercise-<?php echo strtolower($exercise['difficulty'] ?? 'beginner'); ?>">
                        <?php echo htmlspecialchars($exercise['difficulty'] ?? 'Beginner'); ?>
                    </span>
                    <div class="difficulty-bar">
                        <?php
                        $difficultyPercentage = 33;
                        if (strtolower($exercise['difficulty'] ?? '') === 'intermediate' || strtolower($exercise['difficulty'] ?? '') === 'moderate') {
                            $difficultyPercentage = 66;
                        } elseif (strtolower($exercise['difficulty'] ?? '') === 'advanced' || strtolower($exercise['difficulty'] ?? '') === 'hard') {
                            $difficultyPercentage = 100;
                        }
                        ?>
                        <div class="difficulty-fill" style="width: <?php echo $difficultyPercentage; ?>%;"></div>
                    </div>
                </div>
                
                <div class="exercise-stats">
                    <?php if(!empty($exercise['muscles_worked'])): ?>
                    <div class="stat-card">
                        <div class="stat-title">TARGET MUSCLES</div>
                        <div class="stat-value"><?php echo htmlspecialchars($exercise['muscles_worked']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($exercise['equipment_needed'])): ?>
                    <div class="stat-card">
                        <div class="stat-title">EQUIPMENT</div>
                        <div class="stat-value"><?php echo htmlspecialchars($exercise['equipment_needed']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="stat-card">
                        <div class="stat-title">EXERCISE TYPE</div>
                        <div class="stat-value"><?php echo htmlspecialchars($exercise['exercise_type'] ?? 'Strength Exercise'); ?></div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="workout-planer.php?add=<?php echo $exercise_id; ?>" class="action-button primary-button">
                        <i class="fas fa-plus"></i> Add to Workout
                    </a>
                    <button class="action-button secondary-button" onclick="saveExercise(<?php echo $exercise_id; ?>)">
                        <i class="fas fa-bookmark"></i> Save Exercise
                    </button>
                </div>
            </div>
        </div>

        <div class="exercise-tabs">
            <button class="tab-button active" data-tab="instructions"><i class="fas fa-list-ol"></i> Instructions</button>
            <button class="tab-button" data-tab="muscles"><i class="fas fa-dumbbell"></i> Muscle Activation</button>
            <button class="tab-button" data-tab="equipment"><i class="fas fa-cogs"></i> Equipment Guide</button>
            <button class="tab-button" data-tab="variations"><i class="fas fa-random"></i> Variations</button>
            <button class="tab-button" data-tab="progress"><i class="fas fa-chart-line"></i> Progress</button>
            <button class="tab-button" data-tab="social"><i class="fas fa-users"></i> Community</button>
            <button class="tab-button" data-tab="related"><i class="fas fa-th"></i> Related</button>
        </div>
        
        <div id="instructions" class="tab-content active">
            <h2 class="section-title"><i class="fas fa-list-ol"></i> Exercise Instructions</h2>
            
            <?php if(count($instructions) > 0): ?>
            <div class="instructions-list">
                <?php foreach($instructions as $instruction): 
                    $instruction = trim($instruction);
                    if(!empty($instruction)):
                ?>
                <div class="instruction-step">
                    <div class="instruction-text"><?php echo htmlspecialchars($instruction); ?></div>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            <?php else: ?>
            <div class="instructions-list">
                <div class="instruction-step">
                    <div class="instruction-text">Set up properly with the appropriate weight and equipment.</div>
                </div>
                <div class="instruction-step">
                    <div class="instruction-text">Maintain proper form throughout the exercise, focusing on controlled movements.</div>
                </div>
                <div class="instruction-step">
                    <div class="instruction-text">Keep proper breathing techniques - exhale during exertion, inhale during the return movement.</div>
                </div>
                <div class="instruction-step">
                    <div class="instruction-text">Complete your desired number of repetitions with good form.</div>
                </div>
            </div>
            <?php endif; ?>
            
            <h2 class="section-title" style="margin-top: 30px;"><i class="fas fa-exclamation-triangle"></i> Common Mistakes</h2>
            <div class="mistakes-list">
                <?php foreach($common_mistakes as $mistake): ?>
                <div class="mistake-item">
                    <div class="mistake-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="mistake-content">
                        <div class="mistake-title"><?php echo htmlspecialchars($mistake); ?></div>
                        <div class="mistake-description">Avoid this common error to maximize results and prevent injury.</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h2 class="section-title" style="margin-top: 30px;"><i class="fas fa-check-circle"></i> Exercise Benefits</h2>
            <div class="mistakes-list benefits-list">
                <?php foreach($benefits as $benefit): ?>
                <div class="mistake-item benefit-item">
                    <div class="mistake-icon benefit-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="mistake-content">
                        <div class="mistake-title"><?php echo htmlspecialchars($benefit); ?></div>
                        <div class="mistake-description">Consistently performing this exercise can help you achieve these benefits.</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div id="muscles" class="tab-content">
            <h2 class="section-title"><i class="fas fa-dumbbell"></i> Muscle Activation</h2>
            
            <div class="muscle-diagram">
                <div class="diagram-container"> 
                    <img src="../assets/images/muscle-diagram-placeholder.jpg" alt="Muscle Activation Diagram" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                </div>
                <div class="muscle-info">
                    <?php 
                    $primary_muscles = [];
                    $secondary_muscles = [];
                    
                    if (!empty($exercise['muscles_worked'])) {
                        $primary_muscles = array_map('trim', explode(',', $exercise['muscles_worked']));
                    }
                    
                    if (!empty($exercise['secondary_muscles'])) {
                        $secondary_muscles = array_map('trim', explode(',', $exercise['secondary_muscles'] ?? ''));
                    }
                    
                    if (!empty($primary_muscles)):
                        foreach($primary_muscles as $muscle):
                    ?>
                    <div class="muscle-card primary">
                        <div class="muscle-name primary"><i class="fas fa-fire"></i> <?php echo htmlspecialchars(ucfirst($muscle)); ?></div>
                        <div class="muscle-description">Primary target muscle with high activation during this exercise.</div>
                    </div>
                    <?php 
                        endforeach;
                    endif;
                     
                    if (!empty($secondary_muscles)):
                        foreach($secondary_muscles as $muscle):
                    ?>
                    <div class="muscle-card secondary">
                        <div class="muscle-name secondary"><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(ucfirst($muscle)); ?></div>
                        <div class="muscle-description">Secondary muscle that assists during this movement pattern.</div>
                    </div>
                    <?php 
                        endforeach;
                    endif;
                        
                    if (empty($primary_muscles) && empty($secondary_muscles)):
                    ?>
                    <div class="muscle-card primary">
                        <div class="muscle-name primary"><i class="fas fa-fire"></i> Primary Muscle</div>
                        <div class="muscle-description">The main muscle targeted by this exercise with highest activation.</div>
                    </div>
                    <div class="muscle-card secondary">
                        <div class="muscle-name secondary"><i class="fas fa-bolt"></i> Secondary Muscle</div>
                        <div class="muscle-description">Supporting muscles that assist in the movement pattern.</div>
                    </div>
                    <div class="muscle-card tertiary">
                        <div class="muscle-name tertiary"><i class="fas fa-star"></i> Stabilizer</div>
                        <div class="muscle-description">Muscles that help maintain proper form and body position.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2 class="section-title" style="margin-top: 30px;"><i class="fas fa-book-medical"></i> Anatomy Details</h2>
            <p>This exercise effectively targets specific muscle fibers, promoting strength and hypertrophy development when performed correctly. The movement pattern engages multiple joints and muscle groups in a coordinated effort, making it an efficient addition to your training program.</p>
            
            <p>For maximum muscle activation, focus on the mind-muscle connection by consciously engaging the target muscles throughout the entire movement. Controlled tempos, especially during the eccentric (lowering) phase, can further enhance muscle fiber recruitment and growth stimulus.</p>
        </div>
        
        <div id="equipment" class="tab-content">
            <h2 class="section-title"><i class="fas fa-cogs"></i> Equipment Guide</h2>
            
            <div class="equipment-guide">
                <div class="equipment-image">
                    <img src="../assets/images/equipment-placeholder.jpg" alt="Exercise Equipment" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                </div>
                <div class="equipment-details">
                    <div class="equipment-section">
                        <h3><i class="fas fa-info-circle"></i> Equipment Needed</h3>
                        <p><?php echo !empty($exercise['equipment_needed']) ? htmlspecialchars($exercise['equipment_needed']) : 'Standard gym equipment is required for this exercise.'; ?></p>
                    </div>
                    
                    <div class="equipment-section">
                        <h3><i class="fas fa-tools"></i> Setup & Adjustments</h3>
                        <p>Ensure proper equipment setup before beginning. Adjust seat height, weight stacks, or other components to fit your body proportions. Proper setup is crucial for safety and optimal muscle targeting.</p>
                    </div>
                    
                    <div class="equipment-section">
                        <h3><i class="fas fa-shield-alt"></i> Safety Considerations</h3>
                        <p>Always use a weight that allows you to maintain proper form throughout the movement. Consider using a spotter for heavy lifts or when trying a new weight. Keep the equipment clean and properly maintained.</p>
                    </div>
                    
                    <div class="equipment-section">
                        <h3><i class="fas fa-home"></i> Home Alternatives</h3>
                        <p>If you don't have access to a gym, consider these alternatives:</p>
                        <div class="alternative-equipment">
                            <?php foreach($home_alternatives as $alternative): ?>
                            <span class="alt-equipment-tag"><?php echo htmlspecialchars($alternative); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="variations" class="tab-content">
            <h2 class="section-title"><i class="fas fa-random"></i> Exercise Variations</h2>
            
            <div class="variations-grid">
                <div class="variation-card">
                    <div class="variation-image" style="background-image: url('../assets/images/variation-placeholder.jpg');">
                        <span class="variation-difficulty difficulty-easy">Easy</span>
                    </div>
                    <div class="variation-content">
                        <h3 class="variation-title">Beginner Variation</h3>
                        <p class="variation-description">A simplified version of the main exercise, perfect for beginners or those with mobility limitations.</p>
                        <p class="variation-benefits"><i class="fas fa-check-circle"></i> Great for learning proper form</p>
                    </div>
                </div>
                
                <div class="variation-card">
                    <div class="variation-image" style="background-image: url('../assets/images/variation-placeholder.jpg');">
                        <span class="variation-difficulty difficulty-moderate">Moderate</span>
                    </div>
                    <div class="variation-content">
                        <h3 class="variation-title">Standard Variation</h3>
                        <p class="variation-description">The classic version of this exercise as performed in most gym settings.</p>
                        <p class="variation-benefits"><i class="fas fa-check-circle"></i> Balanced muscle development</p>
                    </div>
                </div>
                
                <div class="variation-card">
                    <div class="variation-image" style="background-image: url('../assets/images/variation-placeholder.jpg');">
                        <span class="variation-difficulty difficulty-hard">Advanced</span>
                    </div>
                    <div class="variation-content">
                        <h3 class="variation-title">Advanced Variation</h3>
                        <p class="variation-description">A more challenging version for experienced lifters looking for greater intensity.</p>
                        <p class="variation-benefits"><i class="fas fa-check-circle"></i> Maximizes muscle recruitment</p>
                    </div>
                </div>
                
                <div class="variation-card">
                    <div class="variation-image" style="background-image: url('../assets/images/variation-placeholder.jpg');">
                        <span class="variation-difficulty difficulty-moderate">Moderate</span>
                    </div>
                    <div class="variation-content">
                        <h3 class="variation-title">Alternative Grip/Stance</h3>
                        <p class="variation-description">Changing your grip or stance can target different aspects of the same muscle groups.</p>
                        <p class="variation-benefits"><i class="fas fa-check-circle"></i> Targets different muscle fibers</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="progress" class="tab-content">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Progress Tracking</h2>
            
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <div class="progress-section">
                <div class="personal-records">
                    <h3 style="margin-top: 0;"><i class="fas fa-trophy"></i> Your Personal Records</h3>
                    <div class="record-item">
                        <div>
                            <div class="record-label">Max Weight</div>
                            <div class="record-date">Achieved on May 15, 2023</div>
                        </div>
                        <div class="record-value">135 lbs</div>
                    </div>
                    <div class="record-item">
                        <div>
                            <div class="record-label">Max Reps</div>
                            <div class="record-date">Achieved on June 22, 2023</div>
                        </div>
                        <div class="record-value">15 reps</div>
                    </div>
                    <div class="record-item">
                        <div>
                            <div class="record-label">Volume PR</div>
                            <div class="record-date">Achieved on July 10, 2023</div>
                        </div>
                        <div class="record-value">1,800 lbs</div>
                    </div>
                    <div class="record-item">
                        <div>
                            <div class="record-label">Consistency</div>
                            <div class="record-date">All-time</div>
                        </div>
                        <div class="record-value">24 workouts</div>
                    </div>
                </div>
                
                <div class="charts-container">
                    <h3 style="margin-top: 0;"><i class="fas fa-chart-bar"></i> Performance History</h3>
                    <div class="progress-placeholder">
                        <i class="fas fa-chart-line"></i>
                        <p>Your progress charts will appear here after you log workouts with this exercise</p>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3 style="margin-top: 0;"><i class="fas fa-bullseye"></i> Set New Goals</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                    <div class="equipment-section">
                        <h4 style="margin-top: 0; color: var(--text-primary);">Weight Goal</h4>
                        <input type="number" placeholder="Target weight (lbs)" style="width: 100%; padding: 10px; background: var(--element-hover); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                    </div>
                    <div class="equipment-section">
                        <h4 style="margin-top: 0; color: var(--text-primary);">Rep Goal</h4>
                        <input type="number" placeholder="Target reps" style="width: 100%; padding: 10px; background: var(--element-hover); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                    </div>
                    <div class="equipment-section">
                        <h4 style="margin-top: 0; color: var(--text-primary);">Achieve By</h4>
                        <input type="date" style="width: 100%; padding: 10px; background: var(--element-hover); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                    </div>
                </div>
                <button class="action-button primary-button" style="width: 100%; margin-top: 15px;">Set Goals</button>
            </div>
            
            <?php else: ?>
            <div style="text-align: center; padding: 50px 20px;">
                <i class="fas fa-user-lock" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                <h3 style="margin-top: 0; color: var(--text-primary);">Track Your Progress</h3>
                <p style="max-width: 500px; margin: 0 auto 20px; color: var(--text-secondary);">Login to track your personal records, set goals, and visualize your progress with this exercise over time.</p>
                <a href="login.php" class="action-button primary-button" style="display: inline-flex; margin: 0 auto;">Login to Track Progress</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="social" class="tab-content">
            <h2 class="section-title"><i class="fas fa-users"></i> Community & Reviews</h2>
            
            <div class="social-section">
                <div class="community-rating">
                    <p class="rating-value">4.8</p>
                    <div class="rating-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="rating-text">Community Rating</p>
                    <p class="rating-count">Based on 124 reviews</p>
                </div>
                
                <div class="user-reviews">
                    <h3 style="margin-top: 0;"><i class="fas fa-comment-alt"></i> User Reviews</h3>
                    
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <div style="margin-bottom: 20px;">
                        <textarea placeholder="Share your experience with this exercise..." style="width: 100%; padding: 10px; background: var(--element-hover); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px; min-height: 80px; resize: vertical;"></textarea>
                        <div style="display: flex; align-items: center; margin-top: 10px; gap: 10px;">
                            <div class="rating-stars" style="font-size: 1rem; color: var(--text-muted); cursor: pointer;">
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                            <button class="action-button primary-button" style="margin-left: auto;">Post Review</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="background: var(--element-hover); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <p style="margin: 0; font-weight: 600;">Mike J.</p>
                            <div style="color: #ffc107; font-size: 0.9rem;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p style="margin: 5px 0 0; font-size: 0.9rem; color: var(--text-secondary);">Great exercise for building strength. I've seen significant improvements in just a few weeks.</p>
                    </div>
                    
                    <div style="background: var(--element-hover); padding: 15px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <p style="margin: 0; font-weight: 600;">Sarah T.</p>
                            <div style="color: #ffc107; font-size: 0.9rem;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                        </div>
                        <p style="margin: 5px 0 0; font-size: 0.9rem; color: var(--text-secondary);">Really effective, but be careful with form. Watch the tutorial video before attempting.</p>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3><i class="fas fa-share-alt"></i> Share This Exercise</h3>
                <div style="display: flex; gap: 10px;">
                    <button class="action-button secondary-button" style="flex: 1;"><i class="fab fa-facebook"></i> Facebook</button>
                    <button class="action-button secondary-button" style="flex: 1;"><i class="fab fa-twitter"></i> Twitter</button>
                    <button class="action-button secondary-button" style="flex: 1;"><i class="fas fa-envelope"></i> Email</button>
                    <button class="action-button secondary-button" style="flex: 1;"><i class="fas fa-link"></i> Copy Link</button>
                </div>
            </div>
        </div>
        
        <div id="related" class="tab-content">
            <h2 class="section-title"><i class="fas fa-th"></i> Related Exercises</h2>
            
            <div class="related-exercises-grid">
                <a href="#" class="related-exercise-card">
                    <div class="related-exercise-image" style="background-image: url('../assets/images/exercise-placeholder.jpg');"></div>
                    <div class="related-exercise-content">
                        <h3 class="related-exercise-title">Similar Exercise 1</h3>
                        <p class="related-exercise-type">Strength Training</p>
                        <div class="related-exercise-muscles">
                            <span class="muscle-chip">Chest</span>
                            <span class="muscle-chip">Triceps</span>
                        </div>
                    </div>
                </a>
                
                <a href="#" class="related-exercise-card">
                    <div class="related-exercise-image" style="background-image: url('../assets/images/exercise-placeholder.jpg');"></div>
                    <div class="related-exercise-content">
                        <h3 class="related-exercise-title">Similar Exercise 2</h3>
                        <p class="related-exercise-type">Strength Training</p>
                        <div class="related-exercise-muscles">
                            <span class="muscle-chip">Back</span>
                            <span class="muscle-chip">Biceps</span>
                        </div>
                    </div>
                </a>
                
                <a href="#" class="related-exercise-card">
                    <div class="related-exercise-image" style="background-image: url('../assets/images/exercise-placeholder.jpg');"></div>
                    <div class="related-exercise-content">
                        <h3 class="related-exercise-title">Similar Exercise 3</h3>
                        <p class="related-exercise-type">Strength Training</p>
                        <div class="related-exercise-muscles">
                            <span class="muscle-chip">Shoulders</span>
                            <span class="muscle-chip">Core</span>
                        </div>
                    </div>
                </a>
                
                <a href="#" class="related-exercise-card">
                    <div class="related-exercise-image" style="background-image: url('../assets/images/exercise-placeholder.jpg');"></div>
                    <div class="related-exercise-content">
                        <h3 class="related-exercise-title">Similar Exercise 4</h3>
                        <p class="related-exercise-type">Strength Training</p>
                        <div class="related-exercise-muscles">
                            <span class="muscle-chip">Legs</span>
                            <span class="muscle-chip">Core</span>
                        </div>
                    </div>
                </a>
            </div>
            
            <h2 class="section-title" style="margin-top: 30px;"><i class="fas fa-arrow-up"></i> Progression Path</h2>
            <p>As you master this exercise, consider advancing to these more challenging variations to continue your strength development:</p>
            
            <div style="display: flex; align-items: center; margin-top: 20px;">
                <div style="flex: 1; text-align: center; padding: 15px; background: var(--element-bg); border-radius: 8px; margin-right: 10px;">
                    <h4 style="margin: 0;">Beginner</h4>
                    <p style="font-size: 0.9rem; margin: 5px 0; color: var(--text-secondary);">Current Exercise</p>
                </div>
                <i class="fas fa-arrow-right" style="margin: 0 15px; color: var(--text-muted);"></i>
                <div style="flex: 1; text-align: center; padding: 15px; background: var(--element-bg); border-radius: 8px; margin-right: 10px;">
                    <h4 style="margin: 0;">Intermediate</h4>
                    <p style="font-size: 0.9rem; margin: 5px 0; color: var(--text-secondary);">Next Level</p>
                </div>
                <i class="fas fa-arrow-right" style="margin: 0 15px; color: var(--text-muted);"></i>
                <div style="flex: 1; text-align: center; padding: 15px; background: var(--element-bg); border-radius: 8px;">
                    <h4 style="margin: 0;">Advanced</h4>
                    <p style="font-size: 0.9rem; margin: 5px 0; color: var(--text-secondary);">Master Level</p>
                </div>
            </div>
        </div>

        <?php if(!empty($exercise['video_url'])): ?>
        <div class="video-section">
            <h2 class="section-title"><i class="fas fa-video"></i> Exercise Video Tutorial</h2>
            <div class="exercise-video">
                <div class="video-wrapper">
                    <iframe src="<?php echo htmlspecialchars($exercise['video_url']); ?>" allowfullscreen></iframe>
                </div>
            </div>
            
            <div class="video-thumbnails">
                <div class="video-thumbnail active">
                    <img src="../assets/images/video-thumb-1.jpg" alt="Main View">
                </div>
                <div class="video-thumbnail">
                    <img src="../assets/images/video-thumb-2.jpg" alt="Side View">
                </div>
                <div class="video-thumbnail">
                    <img src="../assets/images/video-thumb-3.jpg" alt="Form Tips">
                </div>
                <div class="video-thumbnail">
                    <img src="../assets/images/video-thumb-4.jpg" alt="Common Mistakes">
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    <script src="../assets/js/exercise-details.js"></script>
</body>
</html>