<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=nutrition.php");
    exit;
}

$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Nutrition Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
    <style>
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
    <header>
        <a href="profile.php" class="logo">GYMVERSE</a>
        <nav>
            <a href="#">HOME</a>
            <a href="#">ABOUT</a>
            <a href="membership.php">MEMBERSHIP</a>
            <a href="leaderboard.php">LEADERBOARD</a>
            <a href="nutrition.php" class="active">NUTRITION</a>
            <a href="#">CONTACT</a>
            <a href="profile.php" style="margin-left: 20px; background-color: #333; color: white; padding: 8px 15px; border-radius: 5px;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?>
            </a>
            <a href="logout.php" style="margin-left: 10px; background-color: #ff4d4d; color: white; padding: 8px 15px; border-radius: 5px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <div class="nutrition-container">
        <div class="nutrition-header">
            <h1>NUTRITION DASHBOARD</h1>
            <p>Track your daily nutrition, plan your meals, and achieve your fitness goals</p>
        </div>

        <div class="nutrition-grid">
            <!-- Sidebar with user profile and quick stats -->
            <div class="nutrition-sidebar">
                <div class="user-nutrition-profile">
                    <img src="images/default-profile.jpg" alt="Profile Picture">
                    <h3><?php echo htmlspecialchars($username); ?></h3>
                    <p>Goal: Weight Loss</p>
                </div>

                <div class="nutrition-stats">
                    <div class="stat-item">
                        <span>Daily Calorie Goal</span>
                        <span>2000 kcal</span>
                    </div>
                    <div class="stat-item">
                        <span>Protein Goal</span>
                        <span>150g</span>
                    </div>
                    <div class="stat-item">
                        <span>Carbs Goal</span>
                        <span>250g</span>
                    </div>
                    <div class="stat-item">
                        <span>Fat Goal</span>
                        <span>70g</span>
                    </div>
                    <div class="stat-item">
                        <span>Water Goal</span>
                        <span>2500 ml</span>
                    </div>
                </div>

                <button class="add-meal-btn" style="width: 100%">
                    <i class="fas fa-plus"></i> Log New Meal
                </button>

                <!-- Meal Presets/Favorites Section -->
                <div class="meal-favorites">
                    <div class="favorites-header">
                        <h3><i class="fas fa-star"></i> Favorite Meals</h3>
                        <button class="meal-btn" title="Manage Favorites">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                    <div class="favorite-meals">
                        <div class="favorite-meal">
                            <div class="favorite-meal-info">
                                <h4>Protein Smoothie</h4>
                                <div class="favorite-meal-macros">320 kcal • 28g protein</div>
                            </div>
                            <button class="favorite-meal-add" title="Add to Today">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div class="favorite-meal">
                            <div class="favorite-meal-info">
                                <h4>Chicken & Rice</h4>
                                <div class="favorite-meal-macros">450 kcal • 35g protein</div>
                            </div>
                            <button class="favorite-meal-add" title="Add to Today">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div class="favorite-meal">
                            <div class="favorite-meal-info">
                                <h4>Greek Yogurt Bowl</h4>
                                <div class="favorite-meal-macros">280 kcal • 22g protein</div>
                            </div>
                            <button class="favorite-meal-add" title="Add to Today">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div class="favorite-meal">
                            <div class="favorite-meal-info">
                                <h4>Protein Oatmeal</h4>
                                <div class="favorite-meal-macros">350 kcal • 20g protein</div>
                            </div>
                            <button class="favorite-meal-add" title="Add to Today">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="nutrition-main">
                <!-- Nutrition Score Section -->
                <div class="nutrition-score">
                    <div class="score-background"></div>
                    <div class="score-header">
                        <h2><i class="fas fa-award"></i> Nutrition Score</h2>
                        <span>Today, <?php echo date('M d, Y'); ?></span>
                    </div>
                    
                    <div class="score-container">
                        <div class="score-gauge">
                            <div class="score-circle">
                                <div class="score-fill" id="score-fill"></div>
                                <div class="score-value" id="score-value">78</div>
                            </div>
                        </div>
                        
                        <div class="score-details">
                            <p class="score-description">Your nutrition score is <strong>Good</strong>. You're making healthy choices but there's room for improvement.</p>
                            
                            <div class="score-factors">
                                <div class="score-factor">
                                    <div class="factor-icon good">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="factor-text">Protein goals met</div>
                                </div>
                                <div class="score-factor">
                                    <div class="factor-icon good">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="factor-text">Good water intake</div>
                                </div>
                                <div class="score-factor">
                                    <div class="factor-icon bad">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <div class="factor-text">Low fiber intake</div>
                                </div>
                                <div class="score-factor">
                                    <div class="factor-icon bad">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <div class="factor-text">High sodium</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Water Tracker Section -->
                <div class="water-tracker">
                    <div class="water-header">
                        <h2><i class="fas fa-tint"></i> Water Intake</h2>
                        <span id="water-date">Today, <?php echo date('M d, Y'); ?></span>
                    </div>
                    
                    <div class="water-visual">
                        <div class="water-wave" id="water-wave"></div>
                        <div class="water-info">
                            <h3 id="water-amount">0 ml</h3>
                            <p><span id="water-percentage">0%</span> of daily goal</p>
                        </div>
                    </div>
                    
                    <div class="water-controls">
                        <div class="water-buttons">
                            <button class="water-btn" onclick="addWater(250)">
                                <i class="fas fa-glass-water"></i> Glass (250ml)
                            </button>
                            <button class="water-btn" onclick="addWater(500)">
                                <i class="fas fa-bottle-water"></i> Bottle (500ml)
                            </button>
                            <button class="water-btn" onclick="resetWater()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                        
                        <div class="water-custom">
                            <input type="number" id="custom-water" placeholder="ml" min="0" max="2000">
                            <button onclick="addCustomWater()">Add</button>
                        </div>
                    </div>
                </div>

                <!-- Recipe Integration Section -->
                <div class="recipe-section">
                    <div class="recipe-header">
                        <h2><i class="fas fa-utensils"></i> Healthy Recipes</h2>
                        <a href="#" class="recipe-btn">My Saved Recipes</a>
                    </div>
                    
                    <div class="recipe-search">
                        <input type="text" class="recipe-search-input" placeholder="Search for recipes...">
                        <select class="recipe-filter">
                            <option value="all">All Categories</option>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snacks">Snacks</option>
                            <option value="high-protein">High Protein</option>
                            <option value="low-carb">Low Carb</option>
                        </select>
                    </div>
                    
                    <div class="recipe-grid">
                        <!-- Recipe Card 1 -->
                        <div class="recipe-card">
                            <div class="recipe-image" style="background-image: url('https://images.unsplash.com/photo-1546069901-ba9599a7e63c')">
                                <div class="recipe-category">Breakfast</div>
                                <div class="recipe-save" data-recipe="1"><i class="far fa-heart"></i></div>
                            </div>
                            <div class="recipe-content">
                                <h3 class="recipe-title">Protein-Packed Greek Yogurt Bowl</h3>
                                <div class="recipe-meta">
                                    <div><i class="far fa-clock"></i> 10 mins</div>
                                    <div><i class="fas fa-fire"></i> 320 kcal</div>
                                </div>
                                <p class="recipe-description">Start your day with this delicious and nutritious Greek yogurt bowl topped with fresh berries, honey, and nuts.</p>
                                <div class="recipe-macros">
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">28g</div>
                                        <div class="recipe-macro-label">Protein</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">32g</div>
                                        <div class="recipe-macro-label">Carbs</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">12g</div>
                                        <div class="recipe-macro-label">Fat</div>
                                    </div>
                                </div>
                                <div class="recipe-actions">
                                    <button class="recipe-btn" onclick="viewRecipe(1)">View Recipe</button>
                                    <button class="recipe-btn primary" onclick="addToMealLog(1)">Add to Meal Log</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recipe Card 2 -->
                        <div class="recipe-card">
                            <div class="recipe-image" style="background-image: url('https://images.unsplash.com/photo-1546554137-f86b9593a222')">
                                <div class="recipe-category">Lunch</div>
                                <div class="recipe-save saved" data-recipe="2"><i class="fas fa-heart"></i></div>
                            </div>
                            <div class="recipe-content">
                                <h3 class="recipe-title">Grilled Chicken & Avocado Salad</h3>
                                <div class="recipe-meta">
                                    <div><i class="far fa-clock"></i> 20 mins</div>
                                    <div><i class="fas fa-fire"></i> 410 kcal</div>
                                </div>
                                <p class="recipe-description">A fresh and filling salad with grilled chicken, avocado, mixed greens, and a light vinaigrette dressing.</p>
                                <div class="recipe-macros">
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">35g</div>
                                        <div class="recipe-macro-label">Protein</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">15g</div>
                                        <div class="recipe-macro-label">Carbs</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">24g</div>
                                        <div class="recipe-macro-label">Fat</div>
                                    </div>
                                </div>
                                <div class="recipe-actions">
                                    <button class="recipe-btn" onclick="viewRecipe(2)">View Recipe</button>
                                    <button class="recipe-btn primary" onclick="addToMealLog(2)">Add to Meal Log</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recipe Card 3 -->
                        <div class="recipe-card">
                            <div class="recipe-image" style="background-image: url('https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2')">
                                <div class="recipe-category">Dinner</div>
                                <div class="recipe-save" data-recipe="3"><i class="far fa-heart"></i></div>
                            </div>
                            <div class="recipe-content">
                                <h3 class="recipe-title">Baked Salmon with Roasted Vegetables</h3>
                                <div class="recipe-meta">
                                    <div><i class="far fa-clock"></i> 30 mins</div>
                                    <div><i class="fas fa-fire"></i> 490 kcal</div>
                                </div>
                                <p class="recipe-description">Oven-baked salmon fillet served with a colorful mix of roasted vegetables seasoned with herbs.</p>
                                <div class="recipe-macros">
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">42g</div>
                                        <div class="recipe-macro-label">Protein</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">22g</div>
                                        <div class="recipe-macro-label">Carbs</div>
                                    </div>
                                    <div class="recipe-macro">
                                        <div class="recipe-macro-value">26g</div>
                                        <div class="recipe-macro-label">Fat</div>
                                    </div>
                                </div>
                                <div class="recipe-actions">
                                    <button class="recipe-btn" onclick="viewRecipe(3)">View Recipe</button>
                                    <button class="recipe-btn primary" onclick="addToMealLog(3)">Add to Meal Log</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="view-more-btn">View More Recipes</button>
                </div>
                
                <!-- Weekly Meal Planning Section -->
                <div class="meal-planning">
                    <div class="planning-header">
                        <h2><i class="fas fa-calendar-alt"></i> Weekly Meal Plan</h2>
                        <div class="planning-actions">
                            <button class="planning-btn">
                                <i class="fas fa-copy"></i> Copy Last Week
                            </button>
                            <button class="planning-btn primary">
                                <i class="fas fa-save"></i> Save Plan
                            </button>
                        </div>
                    </div>
                    
                    <div class="week-navigation">
                        <div class="week-selector">
                            <button class="week-arrow" id="prev-week">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="week-label" id="week-label">May 6 - May 12, 2023</div>
                            <button class="week-arrow" id="next-week">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="week-view-options">
                            <select id="week-view" class="week-view-select">
                                <option value="week">Full Week</option>
                                <option value="workdays">Workdays</option>
                                <option value="weekend">Weekend</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="week-grid" id="week-grid">
                        <!-- Monday -->
                        <div class="day-column" data-day="monday">
                            <div class="day-header">Monday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-1">
                                    <h4>
                                        Oatmeal with Berries
                                        <span class="planned-meal-type">Breakfast</span>
                                    </h4>
                                    <div class="planned-meal-macros">350 kcal • 12g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Tuesday -->
                        <div class="day-column" data-day="tuesday">
                            <div class="day-header today">Tuesday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-2">
                                    <h4>
                                        Greek Yogurt Bowl
                                        <span class="planned-meal-type">Breakfast</span>
                                    </h4>
                                    <div class="planned-meal-macros">280 kcal • 22g protein</div>
                                </div>
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-3">
                                    <h4>
                                        Chicken Salad
                                        <span class="planned-meal-type lunch">Lunch</span>
                                    </h4>
                                    <div class="planned-meal-macros">420 kcal • 35g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Wednesday -->
                        <div class="day-column" data-day="wednesday">
                            <div class="day-header">Wednesday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-4">
                                    <h4>
                                        Protein Smoothie
                                        <span class="planned-meal-type">Breakfast</span>
                                    </h4>
                                    <div class="planned-meal-macros">320 kcal • 28g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Thursday -->
                        <div class="day-column" data-day="thursday">
                            <div class="day-header">Thursday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Friday -->
                        <div class="day-column" data-day="friday">
                            <div class="day-header">Friday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-5">
                                    <h4>
                                        Salmon with Rice
                                        <span class="planned-meal-type dinner">Dinner</span>
                                    </h4>
                                    <div class="planned-meal-macros">550 kcal • 40g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Saturday -->
                        <div class="day-column" data-day="saturday">
                            <div class="day-header">Saturday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-6">
                                    <h4>
                                        Protein Pancakes
                                        <span class="planned-meal-type">Breakfast</span>
                                    </h4>
                                    <div class="planned-meal-macros">450 kcal • 25g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                        
                        <!-- Sunday -->
                        <div class="day-column" data-day="sunday">
                            <div class="day-header">Sunday</div>
                            <div class="day-content" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                                <div class="planned-meal" draggable="true" ondragstart="drag(event)" id="meal-7">
                                    <h4>
                                        Protein Bar
                                        <span class="planned-meal-type snack">Snack</span>
                                    </h4>
                                    <div class="planned-meal-macros">220 kcal • 20g protein</div>
                                </div>
                                <div class="meal-placeholder">+ Drop meal here</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recipe Detail Modal -->
    <div class="recipe-modal" id="recipe-modal">
        <div class="recipe-modal-content">
            <div class="recipe-modal-close" onclick="closeRecipeModal()"><i class="fas fa-times"></i></div>
            <div class="recipe-detail-header" style="background-image: url('https://images.unsplash.com/photo-1546069901-ba9599a7e63c')">
                <div class="recipe-detail-title">
                    <h2>Protein-Packed Greek Yogurt Bowl</h2>
                    <div class="recipe-detail-meta">
                        <div><i class="far fa-clock"></i> Prep Time: 5 mins</div>
                        <div><i class="fas fa-clock"></i> Cook Time: 5 mins</div>
                        <div><i class="fas fa-utensils"></i> Serves: 1</div>
                        <div><i class="fas fa-fire"></i> 320 calories</div>
                    </div>
                </div>
            </div>
            <div class="recipe-detail-body">
                <div class="recipe-detail-main">
                    <div class="recipe-detail-description">
                        <p>This protein-packed Greek yogurt bowl is perfect for a nutritious breakfast or post-workout snack. It combines the protein power of Greek yogurt with the antioxidant benefits of fresh berries, the sweetness of honey, and the healthy fats from nuts and seeds.</p>
                        <p>Not only is it delicious and satisfying, but it's also quick to prepare - perfect for busy mornings when you need a healthy start to your day!</p>
                    </div>
                    
                    <div class="recipe-instructions">
                        <h3><i class="fas fa-list-ol"></i> Instructions</h3>
                        <ol>
                            <li>Add Greek yogurt to a bowl.</li>
                            <li>Top with fresh berries (blueberries, strawberries, raspberries).</li>
                            <li>Sprinkle with chopped nuts and seeds.</li>
                            <li>Drizzle with honey.</li>
                            <li>Optional: Add a sprinkle of granola or a dash of cinnamon.</li>
                            <li>Enjoy immediately for the best taste and texture.</li>
                        </ol>
                    </div>
                </div>
                
                <div class="recipe-sidebar">
                    <div class="recipe-ingredients">
                        <h3><i class="fas fa-shopping-basket"></i> Ingredients</h3>
                        <ul class="ingredient-list">
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Greek yogurt (plain, full-fat)</span>
                                <span class="ingredient-amount">1 cup</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Mixed berries (fresh or frozen)</span>
                                <span class="ingredient-amount">1/2 cup</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Honey</span>
                                <span class="ingredient-amount">1 tbsp</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Chopped nuts (almonds, walnuts)</span>
                                <span class="ingredient-amount">2 tbsp</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Chia seeds</span>
                                <span class="ingredient-amount">1 tsp</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Granola (optional)</span>
                                <span class="ingredient-amount">2 tbsp</span>
                            </li>
                            <li class="ingredient-item">
                                <input type="checkbox" class="ingredient-checkbox">
                                <span class="ingredient-name">Cinnamon (optional)</span>
                                <span class="ingredient-amount">pinch</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="recipe-nutrition">
                        <h3><i class="fas fa-chart-pie"></i> Nutrition Facts</h3>
                        <div class="nutrition-grid">
                            <div class="nutrition-item">
                                <span class="nutrition-label">Calories</span>
                                <span class="nutrition-value">320 kcal</span>
                            </div>
                            <div class="nutrition-item">
                                <span class="nutrition-label">Protein</span>
                                <span class="nutrition-value">28g</span>
                            </div>
                            <div class="nutrition-item">
                                <span class="nutrition-label">Carbs</span>
                                <span class="nutrition-value">32g</span>
                            </div>
                            <div class="nutrition-item">
                                <span class="nutrition-label">Fat</span>
                                <span class="nutrition-value">12g</span>
                            </div>
                            <div class="nutrition-item">
                                <span class="nutrition-label">Fiber</span>
                                <span class="nutrition-value">5g</span>
                            </div>
                            <div class="nutrition-item">
                                <span class="nutrition-label">Sugar</span>
                                <span class="nutrition-value">22g</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recipe-action-btns">
                        <button class="recipe-action-btn save-recipe-btn">
                            <i class="far fa-heart"></i> Save Recipe
                        </button>
                        <button class="recipe-action-btn add-to-meal-btn">
                            <i class="fas fa-plus"></i> Add to Meals
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Meal Modal (hidden by default) -->
    <div id="addMealModal" style="display: none;">
        <!-- Modal content will be added here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize water tracking
            initWaterTracker();
            
            // Initialize food search
            initFoodSearch();
            
            // Initialize calorie breakdown chart
            initCalorieBreakdownChart();
            
            // Initialize nutrition score
            initNutritionScore();
            
            // Initialize week navigation
            initWeekNavigation();
            
            // Meal tabs functionality
            const mealTabs = document.querySelectorAll('.meal-tab');
            mealTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    mealTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                });
            });

            // Add event listeners to favorite meal buttons
            const favoriteMealButtons = document.querySelectorAll('.favorite-meal-add');
            favoriteMealButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const mealElement = this.closest('.favorite-meal');
                    const mealName = mealElement.querySelector('h4').textContent;
                    const mealMacros = mealElement.querySelector('.favorite-meal-macros').textContent;
                    
                    // Show notification
                    showNotification(`Added ${mealName} to today's meals`);
                    
                    // In a real implementation, this would add the meal to the database
                    // and refresh the meal log
                    console.log(`Added meal: ${mealName} with macros: ${mealMacros}`);
                });
            });

            // Initialize recipe functionality
            initRecipes();
        });

        // Water tracking variables
        let currentWater = 0;
        const waterGoal = 2500; // ml

        function initWaterTracker() {
            // Check if there's saved water data for today
            const today = new Date().toDateString();
            const savedWater = localStorage.getItem('waterIntake');
            const savedDate = localStorage.getItem('waterDate');
            
            if (savedWater && savedDate === today) {
                currentWater = parseInt(savedWater);
            } else {
                // Reset for new day
                localStorage.setItem('waterDate', today);
                localStorage.setItem('waterIntake', '0');
                currentWater = 0;
            }
            
            updateWaterDisplay();
        }

        function addWater(amount) {
            currentWater += amount;
            updateWaterStorage();
            updateWaterDisplay();
        }

        function addCustomWater() {
            const customInput = document.getElementById('custom-water');
            const amount = parseInt(customInput.value);
            
            if (!isNaN(amount) && amount > 0) {
                currentWater += amount;
                updateWaterStorage();
                updateWaterDisplay();
                customInput.value = '';
            }
        }

        function resetWater() {
            currentWater = 0;
            updateWaterStorage();
            updateWaterDisplay();
        }

        function updateWaterStorage() {
            localStorage.setItem('waterIntake', currentWater.toString());
        }

        function updateWaterDisplay() {
            const waterAmount = document.getElementById('water-amount');
            const waterPercentage = document.getElementById('water-percentage');
            const waterWave = document.getElementById('water-wave');
            
            // Update text displays
            waterAmount.textContent = `${currentWater} ml`;
            
            // Calculate and cap percentage
            const percentage = Math.min(Math.round((currentWater / waterGoal) * 100), 100);
            waterPercentage.textContent = `${percentage}%`;
            
            // Update wave height (max 95% to keep wave visible)
            const waveHeight = Math.min(percentage, 95);
            waterWave.style.height = `${waveHeight}%`;
            
            // Change wave color based on progress
            if (percentage < 25) {
                waterWave.style.background = 'linear-gradient(0deg, rgba(0, 153, 255, 0.4), rgba(0, 153, 255, 0.2))';
            } else if (percentage < 50) {
                waterWave.style.background = 'linear-gradient(0deg, rgba(0, 153, 255, 0.6), rgba(0, 153, 255, 0.3))';
            } else if (percentage < 75) {
                waterWave.style.background = 'linear-gradient(0deg, rgba(0, 153, 255, 0.8), rgba(0, 153, 255, 0.4))';
            } else {
                waterWave.style.background = 'linear-gradient(0deg, rgba(0, 153, 255, 1), rgba(0, 153, 255, 0.5))';
            }
        }

        // Nutrition Score functionality
        function initNutritionScore() {
            const scoreValue = document.getElementById('score-value');
            const scoreFill = document.getElementById('score-fill');
            
            // Get the score value
            const score = parseInt(scoreValue.textContent);
            
            // Animate the score fill
            setTimeout(() => {
                scoreFill.style.height = `${score}%`;
            }, 500);
        }

        // Week navigation functionality
        function initWeekNavigation() {
            const prevWeekBtn = document.getElementById('prev-week');
            const nextWeekBtn = document.getElementById('next-week');
            const weekLabel = document.getElementById('week-label');
            const weekViewSelect = document.getElementById('week-view');
            
            // Current week index (0 = current week)
            let currentWeekIndex = 0;
            
            // Update week label
            function updateWeekLabel() {
                // In a real implementation, this would calculate the actual date range
                // based on the current week index
                if (currentWeekIndex === 0) {
                    weekLabel.textContent = "May 6 - May 12, 2023 (Current Week)";
                } else if (currentWeekIndex < 0) {
                    weekLabel.textContent = `May ${6 + (currentWeekIndex * 7)} - May ${12 + (currentWeekIndex * 7)}, 2023`;
                } else {
                    weekLabel.textContent = `May ${6 + (currentWeekIndex * 7)} - May ${12 + (currentWeekIndex * 7)}, 2023`;
                }
            }
            
            // Previous week button
            prevWeekBtn.addEventListener('click', function() {
                currentWeekIndex--;
                updateWeekLabel();
                // In a real implementation, this would load the meal plan for the previous week
                showNotification("Loaded previous week's meal plan");
            });
            
            // Next week button
            nextWeekBtn.addEventListener('click', function() {
                currentWeekIndex++;
                updateWeekLabel();
                // In a real implementation, this would load the meal plan for the next week
                showNotification("Loaded next week's meal plan");
            });
            
            // Week view select
            weekViewSelect.addEventListener('change', function() {
                const view = this.value;
                const dayColumns = document.querySelectorAll('.day-column');
                
                // Show/hide days based on selected view
                if (view === 'workdays') {
                    dayColumns.forEach((column, index) => {
                        column.style.display = index < 5 ? 'block' : 'none';
                    });
                } else if (view === 'weekend') {
                    dayColumns.forEach((column, index) => {
                        column.style.display = index >= 5 ? 'block' : 'none';
                    });
                } else {
                    dayColumns.forEach(column => {
                        column.style.display = 'block';
                    });
                }
            });
        }

        // Drag and drop functionality for meal planning
        function drag(event) {
            event.dataTransfer.setData("text", event.target.id);
        }

        function allowDrop(event) {
            event.preventDefault();
            event.currentTarget.classList.add('drag-over');
        }

        function dragLeave(event) {
            event.currentTarget.classList.remove('drag-over');
        }

        function drop(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');
            
            const data = event.dataTransfer.getData("text");
            const draggedElement = document.getElementById(data);
            
            // Check if the drop target is a day-content div
            if (event.target.classList.contains('day-content')) {
                event.target.insertBefore(draggedElement, event.target.querySelector('.meal-placeholder'));
            } else if (event.target.classList.contains('meal-placeholder')) {
                event.target.parentNode.insertBefore(draggedElement, event.target);
            } else {
                // Find the closest day-content parent
                const dayContent = event.target.closest('.day-content');
                if (dayContent) {
                    dayContent.insertBefore(draggedElement, dayContent.querySelector('.meal-placeholder'));
                }
            }
            
            // Show notification
            const dayName = event.target.closest('.day-column').getAttribute('data-day');
            const mealName = draggedElement.querySelector('h4').textContent.trim().split('\n')[0].trim();
            showNotification(`Moved ${mealName} to ${dayName}`);
        }

        // Simple notification function
        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-check-circle"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Style the notification
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.backgroundColor = 'rgba(0, 204, 102, 0.9)';
            notification.style.color = 'white';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '5px';
            notification.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
            notification.style.zIndex = '1000';
            notification.style.transition = 'all 0.3s ease';
            notification.style.transform = 'translateY(20px)';
            notification.style.opacity = '0';
            
            // Add to document
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateY(20px)';
                notification.style.opacity = '0';
                
                // Remove from DOM after animation completes
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Food database for autocomplete (in a real implementation, this would come from the server)
        const foodDatabase = [
            { name: "Chicken Breast", calories: 165, protein: 31, carbs: 0, fat: 3.6, serving: "100g" },
            { name: "Brown Rice", calories: 112, protein: 2.6, carbs: 23, fat: 0.9, serving: "100g cooked" },
            { name: "Broccoli", calories: 34, protein: 2.8, carbs: 7, fat: 0.4, serving: "100g" },
            { name: "Salmon", calories: 206, protein: 22, carbs: 0, fat: 13, serving: "100g" },
            { name: "Avocado", calories: 240, protein: 3, carbs: 12, fat: 22, serving: "1 medium" },
            { name: "Greek Yogurt", calories: 59, protein: 10, carbs: 3.6, fat: 0.4, serving: "100g" },
            { name: "Banana", calories: 105, protein: 1.3, carbs: 27, fat: 0.4, serving: "1 medium" },
            { name: "Oatmeal", calories: 71, protein: 2.5, carbs: 12, fat: 1.5, serving: "100g cooked" },
            { name: "Egg", calories: 78, protein: 6.3, carbs: 0.6, fat: 5.3, serving: "1 large" },
            { name: "Sweet Potato", calories: 86, protein: 1.6, carbs: 20, fat: 0.1, serving: "100g" },
            { name: "Spinach", calories: 23, protein: 2.9, carbs: 3.6, fat: 0.4, serving: "100g" },
            { name: "Almonds", calories: 576, protein: 21, carbs: 22, fat: 49, serving: "100g" },
            { name: "Quinoa", calories: 120, protein: 4.4, carbs: 21, fat: 1.9, serving: "100g cooked" },
            { name: "Blueberries", calories: 57, protein: 0.7, carbs: 14, fat: 0.3, serving: "100g" },
            { name: "Cottage Cheese", calories: 98, protein: 11, carbs: 3.4, fat: 4.3, serving: "100g" }
        ];

        // Food search functionality
        function initFoodSearch() {
            const searchInput = document.getElementById('food-search');
            const searchResults = document.getElementById('search-results');
            
            // Add event listener for input changes
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                
                // Clear results if query is empty
                if (query === '') {
                    searchResults.innerHTML = '';
                    searchResults.classList.remove('active');
                    return;
                }
                
                // Filter food database based on query
                const filteredFoods = foodDatabase.filter(food => 
                    food.name.toLowerCase().includes(query)
                );
                
                // Display results
                if (filteredFoods.length > 0) {
                    searchResults.innerHTML = '';
                    filteredFoods.forEach(food => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'food-result-item';
                        resultItem.innerHTML = `
                            <div class="food-result-name">${food.name} (${food.serving})</div>
                            <div class="food-result-macros">
                                <div class="food-result-macro">
                                    <i class="fas fa-fire"></i> ${food.calories} kcal
                                </div>
                                <div class="food-result-macro">
                                    <i class="fas fa-drumstick-bite"></i> ${food.protein}g
                                </div>
                                <div class="food-result-macro">
                                    <i class="fas fa-bread-slice"></i> ${food.carbs}g
                                </div>
                                <div class="food-result-macro">
                                    <i class="fas fa-cheese"></i> ${food.fat}g
                                </div>
                            </div>
                        `;
                        
                        // Add click event to add food to meal log
                        resultItem.addEventListener('click', function() {
                            // In a real implementation, this would open a modal to select portion size
                            // and meal type before adding to the log
                            showNotification(`Added ${food.name} to your meal log`);
                            searchInput.value = '';
                            searchResults.innerHTML = '';
                            searchResults.classList.remove('active');
                        });
                        
                        searchResults.appendChild(resultItem);
                    });
                    searchResults.classList.add('active');
                } else {
                    searchResults.innerHTML = '<div class="food-result-item">No foods found matching your search</div>';
                    searchResults.classList.add('active');
                }
            });
            
            // Close search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('active');
                }
            });
            
            // Show results again when focusing on input
            searchInput.addEventListener('focus', function() {
                if (this.value.trim() !== '') {
                    searchResults.classList.add('active');
                }
            });
        }

        // Calorie breakdown chart
        function initCalorieBreakdownChart() {
            const ctx = document.getElementById('breakdown-canvas').getContext('2d');
            
            // Sample data - in a real implementation, this would come from the database
            const data = {
                labels: ['Breakfast', 'Lunch', 'Dinner', 'Snacks'],
                datasets: [{
                    data: [350, 420, 180, 50],
                    backgroundColor: ['#FF6B6B', '#4ECDC4', '#FFD166', '#6A0572'],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            };
            
            // Create the chart
            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} kcal (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Recipe functionality
        function initRecipes() {
            // Toggle save recipe (heart icon)
            const saveButtons = document.querySelectorAll('.recipe-save');
            saveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent triggering parent click events
                    this.classList.toggle('saved');
                    
                    const recipeId = this.getAttribute('data-recipe');
                    const isSaved = this.classList.contains('saved');
                    
                    // Update heart icon
                    this.innerHTML = isSaved ? '<i class="fas fa-heart"></i>' : '<i class="far fa-heart"></i>';
                    
                    // Show notification
                    if (isSaved) {
                        showNotification(`Recipe saved to your favorites`);
                    } else {
                        showNotification(`Recipe removed from favorites`);
                    }
                    
                    // In a real implementation, this would update the saved status in the database
                    console.log(`Recipe ${recipeId} ${isSaved ? 'saved' : 'unsaved'}`);
                });
            });
            
            // Make recipe cards clickable to view details
            const recipeCards = document.querySelectorAll('.recipe-card');
            recipeCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a button or save heart
                    if (e.target.tagName === 'BUTTON' || e.target.closest('.recipe-save')) {
                        return;
                    }
                    
                    // Get recipe ID from the save button
                    const saveButton = this.querySelector('.recipe-save');
                    const recipeId = saveButton ? saveButton.getAttribute('data-recipe') : '1';
                    
                    viewRecipe(recipeId);
                });
            });
        }

        // View recipe details
        function viewRecipe(recipeId) {
            // In a real implementation, this would fetch the recipe details from the database
            console.log(`Viewing recipe ${recipeId}`);
            
            // Show recipe modal
            const modal = document.getElementById('recipe-modal');
            modal.classList.add('active');
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';
        }

        // Close recipe modal
        function closeRecipeModal() {
            const modal = document.getElementById('recipe-modal');
            modal.classList.remove('active');
            
            // Re-enable body scrolling
            document.body.style.overflow = 'auto';
        }

        // Add recipe to meal log
        function addToMealLog(recipeId) {
            // In a real implementation, this would add the recipe to the user's meal log
            console.log(`Adding recipe ${recipeId} to meal log`);
            
            // Show notification
            showNotification('Recipe added to your meal log');
        }
    </script>
</body>
</html> 