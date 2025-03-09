<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/nutrition.php");
    exit;
}

// Include database connection
require_once '../assets/db_connection.php';

$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Nutrition Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../lietotaja-view.css">
    <style>
        /* Common profile section styles */
        .prof-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        
        .prof-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 30px;
            background: linear-gradient(135deg, #4361ee, #4cc9f0);
            border-radius: 16px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .prof-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 40%;
            background: rgba(255, 255, 255, 0.1);
            transform: skewX(-15deg);
            transform-origin: top right;
        }
        
        .prof-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 10px;
        }
        
        .prof-nav::-webkit-scrollbar {
            display: none;
        }
        
        .prof-nav-item {
            padding: 12px 24px;
            background-color: #1E1E1E;
            color: white;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .prof-nav-item:hover, .prof-nav-item.active {
            background-color: #4361ee;
            transform: translateY(-3px);
        }
        
        .prof-nav-item i {
            font-size: 1.2rem;
        }
        
        .prof-section {
            margin-bottom: 30px;
            background-color: #1E1E1E;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .prof-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }
        
        .prof-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .prof-section-title i {
            color: #4361ee;
        }
        
        @media (max-width: 768px) {
            .prof-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 20px;
            }
            
            .prof-user-info {
                width: 100%;
                justify-content: flex-start;
            }
            
            .prof-stats {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 15px;
            }
        }
        
        /* Nutrition page specific styles */
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
            --success: #00cc66;
            --warning: #ffa700;
            --info: #0099ff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }

        .nutrition-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .nutrition-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .nutrition-header h1 {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .nutrition-header p {
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
        }

        .nutrition-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .nutrition-sidebar {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            height: fit-content;
        }

        .user-nutrition-profile {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .user-nutrition-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 2px solid var(--primary);
        }

        .nutrition-stats {
            margin-bottom: 1.5rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nutrition-main {
            display: grid;
            gap: 2rem;
        }

        .macro-tracking {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
        }

        .macro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .macro-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }

        .macro-card h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .water-fill {
            height: 100%;
            background: linear-gradient(90deg, #0099ff, #66c2ff);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .meal-log {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
        }

        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .add-meal-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .add-meal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.3);
        }

        .meal-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meal-tab {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .meal-tab.active {
            background: var(--primary);
            color: white;
        }

        .meal-entry {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .meal-info h4 {
            margin-bottom: 0.25rem;
        }

        .meal-macros {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .meal-actions {
            display: flex;
            gap: 0.5rem;
        }

        .meal-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: color 0.3s;
        }

        .meal-btn:hover {
            color: var(--primary);
        }

        .nutrition-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .chart-card {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Water Tracker Styles */
        .water-tracker {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .water-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .water-header h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .water-header i {
            color: var(--info);
        }

        .water-visual {
            position: relative;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .water-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(0deg, rgba(0, 153, 255, 0.8), rgba(0, 153, 255, 0.4));
            border-radius: 0 0 10px 10px;
            transition: height 0.5s ease;
        }

        .water-wave::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230099ff' fill-opacity='0.8' d='M0,192L48,176C96,160,192,128,288,133.3C384,139,480,181,576,186.7C672,192,768,160,864,154.7C960,149,1056,171,1152,165.3C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: 100% 20px;
            animation: wave 8s linear infinite;
        }

        @keyframes wave {
            0% {
                background-position-x: 0;
            }
            100% {
                background-position-x: 1440px;
            }
        }

        .water-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            z-index: 1;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .water-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .water-info p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .water-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .water-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .water-btn {
            background: rgba(0, 153, 255, 0.2);
            color: var(--info);
            border: 1px solid rgba(0, 153, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .water-btn:hover {
            background: rgba(0, 153, 255, 0.3);
            transform: translateY(-2px);
        }

        .water-custom {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .water-custom input {
            width: 60px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
            border-radius: 5px;
            color: white;
            text-align: center;
        }

        .water-custom button {
            background: var(--info);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .water-custom button:hover {
            background: #0077cc;
        }

        /* Meal Presets/Favorites Styles */
        .meal-favorites {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .favorites-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .favorites-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .favorites-header i {
            color: var(--warning);
        }

        .favorite-meals {
            display: grid;
            gap: 0.75rem;
        }

        .favorite-meal {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .favorite-meal:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .favorite-meal-info h4 {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .favorite-meal-macros {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .favorite-meal-add {
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .favorite-meal-add:hover {
            transform: scale(1.2);
        }

        /* Nutritional Insights Styles */
        .nutritional-insights {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .insights-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .insights-header i {
            color: var(--success);
        }

        .insights-container {
            display: grid;
            gap: 1rem;
        }

        .insight-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: transform 0.3s;
        }

        .insight-card:hover {
            transform: translateY(-3px);
        }

        .insight-icon {
            background: rgba(0, 204, 102, 0.1);
            color: var(--success);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .insight-card.warning .insight-icon {
            background: rgba(255, 167, 0, 0.1);
            color: var(--warning);
        }

        .insight-card.info .insight-icon {
            background: rgba(0, 153, 255, 0.1);
            color: var(--info);
        }

        .insight-content h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .insight-content p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.4;
        }

        /* Food Search Styles */
        .food-search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .food-search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .food-search-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.2);
        }

        .food-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.2rem;
        }

        .food-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 0 10px 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 10;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .food-search-results.active {
            display: block;
        }

        .food-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .food-result-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .food-result-item:last-child {
            border-bottom: none;
        }

        .food-result-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .food-result-macros {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            gap: 1rem;
        }

        .food-result-macro {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .food-result-macro i {
            font-size: 0.7rem;
            color: var(--primary);
        }

        /* Calorie Breakdown Chart Styles */
        .calorie-breakdown {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .breakdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .breakdown-header h3 {
            font-size: 1rem;
            font-weight: 500;
        }

        .breakdown-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .breakdown-chart {
            width: 120px;
            height: 120px;
            position: relative;
        }

        .breakdown-legend {
            flex: 1;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 0.5rem;
        }

        .legend-text {
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .legend-value {
            color: rgba(255, 255, 255, 0.7);
        }

        .breakfast-color {
            background-color: #FF6B6B;
        }

        .lunch-color {
            background-color: #4ECDC4;
        }

        .dinner-color {
            background-color: #FFD166;
        }

        .snacks-color {
            background-color: #6A0572;
        }

        /* Nutrition Score Styles */
        .nutrition-score {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .score-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .score-header h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .score-header i {
            color: var(--primary);
        }

        .score-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        .score-gauge {
            width: 150px;
            height: 150px;
            position: relative;
        }

        .score-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .score-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            position: relative;
            z-index: 2;
        }

        .score-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 78%;
            background: linear-gradient(0deg, rgba(255, 77, 77, 0.2), rgba(255, 77, 77, 0.05));
            border-radius: 0 0 75px 75px;
            transition: height 1s ease;
        }

        .score-details {
            flex: 1;
        }

        .score-description {
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .score-factors {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .score-factor {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .factor-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .factor-icon.good {
            background: rgba(0, 204, 102, 0.1);
            color: var(--success);
        }

        .factor-icon.bad {
            background: rgba(255, 77, 77, 0.1);
            color: var(--primary);
        }

        .factor-text {
            font-size: 0.9rem;
        }

        .score-background {
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 77, 77, 0.05) 0%, rgba(255, 77, 77, 0) 70%);
            z-index: 0;
        }

        /* Weekly Meal Planning Styles */
        .meal-planning {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .planning-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .planning-header h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .planning-header i {
            color: var(--warning);
        }

        .planning-actions {
            display: flex;
            gap: 1rem;
        }

        .planning-btn {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .planning-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .planning-btn.primary {
            background: rgba(255, 77, 77, 0.2);
            color: var(--primary);
        }

        .planning-btn.primary:hover {
            background: rgba(255, 77, 77, 0.3);
        }

        .week-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .week-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .week-arrow {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .week-arrow:hover {
            color: white;
        }

        .week-label {
            font-weight: 500;
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .day-column {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .day-header {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
            text-align: center;
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .day-header.today {
            background: rgba(255, 77, 77, 0.2);
            color: var(--primary);
        }

        .day-content {
            min-height: 200px;
            padding: 0.75rem;
        }

        .planned-meal {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            cursor: move;
            transition: all 0.3s;
            border-left: 3px solid var(--primary);
        }

        .planned-meal:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .planned-meal h4 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
        }

        .planned-meal-type {
            font-size: 0.7rem;
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            background: rgba(255, 77, 77, 0.2);
            color: var(--primary);
        }

        .planned-meal-type.lunch {
            background: rgba(0, 153, 255, 0.2);
            color: var(--info);
        }

        .planned-meal-type.dinner {
            background: rgba(255, 167, 0, 0.2);
            color: var(--warning);
        }

        .planned-meal-type.snack {
            background: rgba(102, 45, 255, 0.2);
            color: #662dff;
        }

        .planned-meal-macros {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .meal-placeholder {
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.9rem;
        }

        .drag-over {
            background: rgba(255, 77, 77, 0.1);
        }

        @media (max-width: 1200px) {
            .week-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .week-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .score-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .score-factors {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .week-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Recipe Integration Styles */
        .recipe-section {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .recipe-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .recipe-header h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recipe-header i {
            color: var(--info);
        }

        .recipe-search {
            display: flex;
            margin-bottom: 1.5rem;
            gap: 0.5rem;
        }

        .recipe-search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: white;
            font-size: 0.95rem;
        }

        .recipe-search-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .recipe-filter {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: white;
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .recipe-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .recipe-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .recipe-category {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .recipe-save {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .recipe-save:hover {
            background: rgba(255, 77, 77, 0.9);
        }

        .recipe-save.saved {
            background: rgba(255, 77, 77, 0.9);
        }

        .recipe-content {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .recipe-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: white;
        }

        .recipe-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .recipe-meta div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recipe-meta i {
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        .recipe-description {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            flex: 1;
        }

        .recipe-macros {
            display: flex;
            justify-content: space-between;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
        }

        .recipe-macro {
            text-align: center;
        }

        .recipe-macro-value {
            font-weight: 600;
            color: white;
        }

        .recipe-macro-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }

        .recipe-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
        }

        .recipe-btn {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .recipe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .recipe-btn.primary {
            background: rgba(0, 153, 255, 0.2);
            color: var(--info);
            margin-left: 0.5rem;
        }

        .recipe-btn.primary:hover {
            background: rgba(0, 153, 255, 0.3);
        }

        .view-more-btn {
            display: block;
            width: 200px;
            margin: 0 auto;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            font-weight: 500;
        }

        .view-more-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Recipe Modal Styles */
        .recipe-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .recipe-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .recipe-modal-content {
            background: #1a1a1a;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .recipe-modal.active .recipe-modal-content {
            transform: scale(1);
        }

        .recipe-modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .recipe-modal-close:hover {
            background: rgba(255, 77, 77, 0.8);
            transform: rotate(90deg);
        }

        .recipe-detail-header {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
        }

        .recipe-detail-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 70%;
            background: linear-gradient(to top, rgba(26, 26, 26, 1), rgba(26, 26, 26, 0));
        }

        .recipe-detail-title {
            padding: 2rem;
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .recipe-detail-title h2 {
            font-size: 2rem;
            color: white;
            margin-bottom: 0.5rem;
        }

        .recipe-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .recipe-detail-meta div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recipe-detail-body {
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        .recipe-detail-main {
            color: rgba(255, 255, 255, 0.9);
        }

        .recipe-detail-description {
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .recipe-instructions {
            margin-bottom: 2rem;
        }

        .recipe-instructions h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recipe-instructions ol {
            padding-left: 1.5rem;
        }

        .recipe-instructions li {
            margin-bottom: 1rem;
            padding-left: 0.5rem;
        }

        .recipe-sidebar {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
        }

        .recipe-ingredients {
            margin-bottom: 1.5rem;
        }

        .recipe-ingredients h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ingredient-list {
            list-style: none;
            padding: 0;
        }

        .ingredient-item {
            display: flex;
            align-items: flex-start;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ingredient-item:last-child {
            border-bottom: none;
        }

        .ingredient-checkbox {
            margin-right: 0.75rem;
            margin-top: 0.25rem;
        }

        .ingredient-name {
            color: rgba(255, 255, 255, 0.9);
            flex: 1;
        }

        .ingredient-amount {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .recipe-nutrition {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 1rem;
        }

        .recipe-nutrition h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nutrition-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .nutrition-item {
            display: flex;
            justify-content: space-between;
        }

        .nutrition-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .nutrition-value {
            color: white;
            font-weight: 500;
        }

        .recipe-action-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .recipe-action-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .recipe-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .add-to-meal-btn {
            background: var(--info);
        }

        @media (max-width: 768px) {
            .recipe-detail-body {
                grid-template-columns: 1fr;
            }
            
            .recipe-sidebar {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="logo">
            <a href="../index.php">
                <i class="fas fa-dumbbell"></i>
                <span>GYMVERSE</span>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../workouts.php"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                <li><a href="../excercises.php"><i class="fas fa-running"></i> Exercises</a></li>
                <li><a href="../quick-workout.php"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                <li><a class="active" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="prof-container">
        <!-- Profile Header -->
        <div class="prof-header">
            <div>
                <h1><i class="fas fa-apple-alt"></i> Nutrition Tracking</h1>
                <p>Monitor your nutrition habits and maximize your fitness results</p>
            </div>
            <div class="prof-stats">
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= isset($total_calories_consumed) ? number_format($total_calories_consumed) : 0 ?></div>
                    <div class="prof-stat-label">Total Calories</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= isset($meals_tracked) ? $meals_tracked : 0 ?></div>
                    <div class="prof-stat-label">Meals Tracked</div>
                </div>
                <div class="prof-stat-item">
                    <div class="prof-stat-value"><?= isset($streak_days) ? $streak_days : 0 ?></div>
                    <div class="prof-stat-label">Day Streak</div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="prof-nav">
            <a href="profile.php" class="prof-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="calories-burned.php" class="prof-nav-item">
                <i class="fas fa-fire"></i> Calories Burned
            </a>
            <a href="current-goal.php" class="prof-nav-item">
                <i class="fas fa-bullseye"></i> Goals
            </a>
            <a href="nutrition.php" class="prof-nav-item active">
                <i class="fas fa-apple-alt"></i> Nutrition
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-chart-line"></i> Progress
            </a>
            <a href="#" class="prof-nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>

        <!-- Continue with the rest of the nutrition page content -->
        <!-- ... existing content ... -->
    </div>
</body>
</html> 