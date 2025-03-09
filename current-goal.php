<?php
// Initialize the session
session_start();

// Check if the user is not logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=current-goal.php");
    exit;
}

// Include database connection
require_once 'assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Fetch active goals for the user
$goals_query = "SELECT * FROM goals WHERE user_id = ? AND completed = 0 ORDER BY target_date ASC";
$goals_stmt = mysqli_prepare($conn, $goals_query);
mysqli_stmt_bind_param($goals_stmt, "i", $user_id);
mysqli_stmt_execute($goals_stmt);
$goals_result = mysqli_stmt_get_result($goals_stmt);
$active_goals = [];
while ($row = mysqli_fetch_assoc($goals_result)) {
    $active_goals[] = $row;
}

// Fetch completed goals for the user
$completed_query = "SELECT * FROM goals WHERE user_id = ? AND completed = 1 ORDER BY completed_date DESC LIMIT 5";
$completed_stmt = mysqli_prepare($conn, $completed_query);
mysqli_stmt_bind_param($completed_stmt, "i", $user_id);
mysqli_stmt_execute($completed_stmt);
$completed_result = mysqli_stmt_get_result($completed_stmt);
$completed_goals = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_goals[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Current Goals</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lietotaja-view.css">
    <style>
        /* Current Goals Page Styles with unique cgoal- prefix */
        :root {
            --cgoal-primary: #ff4d4d;
            --cgoal-secondary: #333;
            --cgoal-dark: #0A0A0A;
            --cgoal-light: #f5f5f5;
            --cgoal-success: #00cc66;
            --cgoal-warning: #ffa700;
            --cgoal-info: #0099ff;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cgoal-dark);
            color: var(--cgoal-light);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .cgoal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cgoal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cgoal-logo {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            color: var(--cgoal-primary);
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.3);
            letter-spacing: 2px;
            margin: 0;
        }
        
        .cgoal-nav {
            display: flex;
            gap: 20px;
        }
        
        .cgoal-nav-link {
            color: var(--cgoal-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .cgoal-nav-link:hover {
            background: rgba(255, 77, 77, 0.1);
            color: var(--cgoal-primary);
        }
        
        .cgoal-nav-link.active {
            background: var(--cgoal-primary);
            color: white;
        }
        
        .cgoal-page-title {
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
            color: var(--cgoal-primary);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
        }
        
        .cgoal-section {
            background: rgba(20, 20, 20, 0.7);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .cgoal-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--cgoal-primary), #ff9b9b);
        }
        
        .cgoal-section-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cgoal-section-title i {
            color: var(--cgoal-primary);
        }
        
        .cgoal-goal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .cgoal-goal-card {
            background: rgba(30, 30, 30, 0.8);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: all 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .cgoal-goal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .cgoal-goal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .cgoal-goal-title {
            font-size: 1.5rem;
            margin: 0 0 10px 0;
            color: white;
            font-weight: 600;
        }
        
        .cgoal-goal-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cgoal-type-strength {
            background: rgba(255, 77, 77, 0.2);
            color: var(--cgoal-primary);
        }
        
        .cgoal-type-cardio {
            background: rgba(0, 153, 255, 0.2);
            color: var(--cgoal-info);
        }
        
        .cgoal-type-weight {
            background: rgba(255, 167, 0, 0.2);
            color: var(--cgoal-warning);
        }
        
        .cgoal-type-habit {
            background: rgba(0, 204, 102, 0.2);
            color: var(--cgoal-success);
        }
        
        .cgoal-goal-description {
            margin-bottom: 20px;
            color: #cccccc;
        }
        
        .cgoal-goal-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #999;
        }
        
        .cgoal-goal-dates {
            display: flex;
            gap: 10px;
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 20px;
        }
        
        .cgoal-goal-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cgoal-goal-date i {
            color: var(--cgoal-primary);
        }
        
        .cgoal-progress-container {
            margin-top: auto;
        }
        
        .cgoal-progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .cgoal-progress-percentage {
            font-weight: 600;
            color: var(--cgoal-primary);
        }
        
        .cgoal-progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .cgoal-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cgoal-primary), #ff9b9b);
            border-radius: 4px;
            transition: width 0.5s ease-out;
        }
        
        .cgoal-goal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .cgoal-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        .cgoal-btn-primary {
            background: var(--cgoal-primary);
            color: white;
        }
        
        .cgoal-btn-primary:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .cgoal-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .cgoal-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .cgoal-btn-success {
            background: var(--cgoal-success);
            color: white;
        }
        
        .cgoal-btn-success:hover {
            background: #00b359;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 204, 102, 0.3);
        }
        
        .cgoal-completed-section {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 40px;
            margin-top: 40px;
        }
        
        .cgoal-completed-goal {
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .cgoal-completed-goal:hover {
            opacity: 1;
        }
        
        .cgoal-completed-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .cgoal-completed-badge {
            background: var(--cgoal-success);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cgoal-completed-date {
            color: #999;
            font-size: 0.85rem;
        }
        
        .cgoal-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cgoal-empty-icon {
            font-size: 3rem;
            color: rgba(255, 77, 77, 0.2);
            margin-bottom: 20px;
        }
        
        .cgoal-empty-message {
            font-size: 1.2rem;
            color: #999;
            margin-bottom: 30px;
        }
        
        .cgoal-form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .cgoal-form-group {
            margin-bottom: 25px;
        }
        
        .cgoal-form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ddd;
        }
        
        .cgoal-form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(20, 20, 20, 0.8);
            color: white;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        
        .cgoal-form-control:focus {
            border-color: var(--cgoal-primary);
            box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.2);
            outline: none;
        }
        
        .cgoal-form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ff4d4d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
        }
        
        .cgoal-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .cgoal-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .cgoal-modal {
            width: 90%;
            max-width: 500px;
            background: #1f1f1f;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(20px);
            transition: all 0.3s;
        }
        
        .cgoal-modal-overlay.active .cgoal-modal {
            transform: translateY(0);
        }
        
        .cgoal-modal-header {
            padding: 20px;
            background: #2a2a2a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cgoal-modal-title {
            font-size: 1.5rem;
            margin: 0;
            color: white;
        }
        
        .cgoal-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cgoal-modal-close:hover {
            color: var(--cgoal-primary);
            transform: rotate(90deg);
        }
        
        .cgoal-modal-body {
            padding: 20px;
        }
        
        .cgoal-modal-footer {
            padding: 20px;
            background: #2a2a2a;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .cgoal-goal-grid {
                grid-template-columns: 1fr;
            }
            
            .cgoal-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .cgoal-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="cgoal-container">
        <header class="cgoal-header">
            <h1 class="cgoal-logo">GYMVERSE</h1>
            <nav class="cgoal-nav">
                <a href="profile.php" class="cgoal-nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="current-goal.php" class="cgoal-nav-link active"><i class="fas fa-bullseye"></i> Goals</a>
                <a href="workout-planer.php" class="cgoal-nav-link"><i class="fas fa-dumbbell"></i> Workouts</a>
                <a href="calories-burned.php" class="cgoal-nav-link"><i class="fas fa-fire"></i> Calories</a>
                <a href="nutrition.php" class="cgoal-nav-link"><i class="fas fa-apple-alt"></i> Nutrition</a>
                <a href="logout.php" class="cgoal-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <h1 class="cgoal-page-title">Your Fitness Goals</h1>

        <section class="cgoal-section">
            <h2 class="cgoal-section-title"><i class="fas fa-rocket"></i> Active Goals</h2>
            
            <?php if (empty($active_goals)): ?>
                <div class="cgoal-empty-state">
                    <div class="cgoal-empty-icon"><i class="fas fa-bullseye"></i></div>
                    <p class="cgoal-empty-message">You don't have any active goals yet.</p>
                    <button class="cgoal-btn cgoal-btn-primary" onclick="openAddGoalModal()">
                        <i class="fas fa-plus"></i> Set Your First Goal
                    </button>
                </div>
            <?php else: ?>
                <div class="cgoal-goal-grid">
                    <?php foreach ($active_goals as $goal): ?>
                        <div class="cgoal-goal-card">
                            <div class="cgoal-goal-header">
                                <h3 class="cgoal-goal-title"><?php echo htmlspecialchars($goal['title']); ?></h3>
                                <?php
                                $type_class = '';
                                switch ($goal['type']) {
                                    case 'strength': $type_class = 'cgoal-type-strength'; break;
                                    case 'cardio': $type_class = 'cgoal-type-cardio'; break;
                                    case 'weight': $type_class = 'cgoal-type-weight'; break;
                                    case 'habit': $type_class = 'cgoal-type-habit'; break;
                                    default: $type_class = 'cgoal-type-strength';
                                }
                                ?>
                                <span class="cgoal-goal-type <?php echo $type_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($goal['type'])); ?>
                                </span>
                            </div>
                            
                            <p class="cgoal-goal-description"><?php echo htmlspecialchars($goal['description']); ?></p>
                            
                            <div class="cgoal-goal-dates">
                                <div class="cgoal-goal-date">
                                    <i class="far fa-calendar-plus"></i>
                                    <span>Started: <?php echo date('M d, Y', strtotime($goal['created_at'])); ?></span>
                                </div>
                                <div class="cgoal-goal-date">
                                    <i class="far fa-calendar-check"></i>
                                    <span>Target: <?php echo date('M d, Y', strtotime($goal['target_date'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="cgoal-progress-container">
                                <div class="cgoal-progress-info">
                                    <span>Progress:</span>
                                    <span class="cgoal-progress-percentage"><?php echo $goal['progress']; ?>%</span>
                                </div>
                                <div class="cgoal-progress-bar">
                                    <div class="cgoal-progress-fill" style="width: <?php echo $goal['progress']; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="cgoal-goal-actions">
                                <button class="cgoal-btn cgoal-btn-secondary" onclick="updateProgress(<?php echo $goal['id']; ?>)">
                                    <i class="fas fa-chart-line"></i> Update
                                </button>
                                <button class="cgoal-btn cgoal-btn-success" onclick="markCompleted(<?php echo $goal['id']; ?>)">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button class="cgoal-btn cgoal-btn-primary" onclick="openAddGoalModal()">
                        <i class="fas fa-plus"></i> Add New Goal
                    </button>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($completed_goals)): ?>
        <section class="cgoal-section">
            <h2 class="cgoal-section-title"><i class="fas fa-trophy"></i> Completed Goals</h2>
            
            <div class="cgoal-goal-grid">
                <?php foreach ($completed_goals as $goal): ?>
                    <div class="cgoal-goal-card cgoal-completed-goal">
                        <div class="cgoal-completed-header">
                            <span class="cgoal-completed-badge">Completed</span>
                            <span class="cgoal-completed-date">
                                <i class="fas fa-check-circle"></i>
                                <?php echo date('M d, Y', strtotime($goal['completed_date'])); ?>
                            </span>
                        </div>
                        
                        <h3 class="cgoal-goal-title"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        
                        <p class="cgoal-goal-description"><?php echo htmlspecialchars($goal['description']); ?></p>
                        
                        <div class="cgoal-goal-meta">
                            <span>
                                <i class="fas fa-calendar-alt"></i>
                                Started: <?php echo date('M d, Y', strtotime($goal['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- Add Goal Modal -->
    <div class="cgoal-modal-overlay" id="addGoalModal">
        <div class="cgoal-modal">
            <div class="cgoal-modal-header">
                <h3 class="cgoal-modal-title">Set a New Goal</h3>
                <button class="cgoal-modal-close" onclick="closeModal('addGoalModal')">&times;</button>
            </div>
            <div class="cgoal-modal-body">
                <form id="addGoalForm" action="add_goal.php" method="post">
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="goalTitle">Goal Title</label>
                        <input type="text" id="goalTitle" name="title" class="cgoal-form-control" placeholder="E.g., Bench Press 100kg" required>
                    </div>
                    
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="goalType">Goal Type</label>
                        <select id="goalType" name="type" class="cgoal-form-control cgoal-form-select" required>
                            <option value="strength">Strength</option>
                            <option value="cardio">Cardio</option>
                            <option value="weight">Weight</option>
                            <option value="habit">Habit</option>
                        </select>
                    </div>
                    
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="goalDescription">Description</label>
                        <textarea id="goalDescription" name="description" class="cgoal-form-control" rows="4" placeholder="Describe your goal in detail..."></textarea>
                    </div>
                    
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="goalTargetDate">Target Date</label>
                        <input type="date" id="goalTargetDate" name="target_date" class="cgoal-form-control" required>
                    </div>
                </form>
            </div>
            <div class="cgoal-modal-footer">
                <button class="cgoal-btn cgoal-btn-secondary" onclick="closeModal('addGoalModal')">Cancel</button>
                <button class="cgoal-btn cgoal-btn-primary" onclick="document.getElementById('addGoalForm').submit()">Set Goal</button>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div class="cgoal-modal-overlay" id="updateProgressModal">
        <div class="cgoal-modal">
            <div class="cgoal-modal-header">
                <h3 class="cgoal-modal-title">Update Goal Progress</h3>
                <button class="cgoal-modal-close" onclick="closeModal('updateProgressModal')">&times;</button>
            </div>
            <div class="cgoal-modal-body">
                <form id="updateProgressForm" action="update_progress.php" method="post">
                    <input type="hidden" id="progressGoalId" name="goal_id">
                    
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="progressValue">Current Progress (%)</label>
                        <input type="number" id="progressValue" name="progress" class="cgoal-form-control" min="0" max="99" required>
                    </div>
                    
                    <div class="cgoal-form-group">
                        <label class="cgoal-form-label" for="progressNotes">Progress Notes</label>
                        <textarea id="progressNotes" name="notes" class="cgoal-form-control" rows="4" placeholder="Add notes about your progress..."></textarea>
                    </div>
                </form>
            </div>
            <div class="cgoal-modal-footer">
                <button class="cgoal-btn cgoal-btn-secondary" onclick="closeModal('updateProgressModal')">Cancel</button>
                <button class="cgoal-btn cgoal-btn-primary" onclick="document.getElementById('updateProgressForm').submit()">Update</button>
            </div>
        </div>
    </div>

    <script>
        function openAddGoalModal() {
            document.getElementById('addGoalModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function updateProgress(goalId) {
            document.getElementById('progressGoalId').value = goalId;
            document.getElementById('updateProgressModal').classList.add('active');
        }
        
        function markCompleted(goalId) {
            if (confirm('Are you sure you want to mark this goal as completed?')) {
                window.location.href = 'complete_goal.php?id=' + goalId;
            }
        }
        
        // Set minimum date for target date input to today
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        
        document.getElementById('goalTargetDate').min = `${yyyy}-${mm}-${dd}`;
    </script>
</body>
</html> 