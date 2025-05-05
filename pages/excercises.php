<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "GYMVERSE - Exercise Library";
$bodyClass = "exercises-page";

$additionalHead = <<<HTML
    <meta name="keywords" content="fitness, workouts, gym, exercises, training, muscle groups, workout techniques">
    <link rel="stylesheet" href="/assets/css/muscle-map.css">
    <style>
        body {
            background-color: #121212;
            color: #f5f5f5;
        }
        .navbar {
            background-color: #1e1e1e !important;
            border-bottom: 1px solid #333;
        }
        .footer {
            background-color: #1e1e1e !important;
            border-top: 1px solid #333;
        }
        .nav-link, .navbar-brand {
            color: #f5f5f5 !important;
        }
        .nav-link:hover {
            color: #e53935 !important;
        }
        .navbar-toggler {
            border-color: #333;
        }
        .dropdown-menu {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        .dropdown-item {
            color: #f5f5f5;
        }
        .dropdown-item:hover {
            background-color: #252525;
            color: #e53935;
        }
    </style>
HTML;

$additionalScripts = <<<HTML
    <script src="/assets/js/pages/exercises.js"></script>
    <script src="/assets/js/muscle-map.js"></script>
    <script src="/assets/js/exercise-tracker.js"></script>
    <script src="/assets/js/quick-view.js"></script>
HTML;

require_once '../includes/header.php';

require_once '../assets/db_connection.php';

$exercisesQuery = "SELECT * FROM exercises ORDER BY name ASC";
$exercisesResult = mysqli_query($conn, $exercisesQuery);
$totalExercises = mysqli_num_rows($exercisesResult);

$typesQuery = "SELECT DISTINCT exercise_type FROM exercises ORDER BY exercise_type ASC";
$typesResult = mysqli_query($conn, $typesQuery);

if (!$typesResult) {
    error_log("Error fetching exercise types: " . mysqli_error($conn));
    $typesResult = false;
}

$musclesQuery = "SELECT DISTINCT primary_muscle FROM exercises ORDER BY primary_muscle ASC";
$musclesResult = mysqli_query($conn, $musclesQuery);

if (!$musclesResult) {
    error_log("Error fetching muscles: " . mysqli_error($conn));
    $musclesResult = false;
}

$equipmentQuery = "SELECT DISTINCT equipment FROM exercises ORDER BY equipment ASC";
$equipmentResult = mysqli_query($conn, $equipmentQuery);

if (!$equipmentResult) {
    error_log("Error fetching equipment: " . mysqli_error($conn));
    $equipmentResult = false;
}

$user_achievements = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_achievements'");
    if (mysqli_num_rows($table_check) > 0) {
        $sql = "SELECT * FROM user_achievements WHERE user_id = ? AND achievement_type = 'exercise'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $user_achievements[] = $row;
        }
    }
}

$trending_exercises = [5, 10, 15]; 
?>

<div class="container exercises-container">
    <h1 class="page-title">Exercise Library</h1>
    
    <div class="exercise-filter-layout">
        <div class="filters-column">
            <div class="search-container">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="exerciseSearch" class="search-input" placeholder="Search exercises...">
            </div>
            
            <div class="filter-results-count">
                <p>Showing <span id="result-count"><?php echo $totalExercises; ?></span> results of <?php echo $totalExercises; ?> items.</p>
                <button id="resetAllFilters" class="reset-btn"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>
            
            <div class="filter-group">
                <div class="filter-header">
                    <h3><i class="fas fa-dumbbell"></i> Exercise Type</h3>
                    <button class="clear-filter-btn" data-filter="type">Clear</button>
                </div>
                <div class="filter-options">
                    <?php if ($typesResult && mysqli_num_rows($typesResult) > 0): ?>
                        <?php while($type = mysqli_fetch_assoc($typesResult)): ?>
                            <?php if (!empty($type['exercise_type'])): ?>
                            <div class="filter-option">
                                <input type="checkbox" id="type-<?php echo htmlspecialchars($type['exercise_type']); ?>" 
                                       class="filter-checkbox" data-filter="type" 
                                       value="<?php echo htmlspecialchars($type['exercise_type']); ?>">
                                <label for="type-<?php echo htmlspecialchars($type['exercise_type']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($type['exercise_type'])); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No exercise types available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="filter-group">
                <div class="filter-header">
                    <h3><i class="fas fa-running"></i> Muscle Group</h3>
                    <button class="clear-filter-btn" data-filter="muscle">Clear</button>
                </div>
                <div class="filter-options">
                    <?php if ($musclesResult && mysqli_num_rows($musclesResult) > 0): ?>
                        <?php while($muscle = mysqli_fetch_assoc($musclesResult)): ?>
                            <?php if (!empty($muscle['primary_muscle'])): ?>
                            <div class="filter-option">
                                <input type="checkbox" id="muscle-<?php echo htmlspecialchars($muscle['primary_muscle']); ?>" 
                                       class="filter-checkbox" data-filter="muscle" 
                                       value="<?php echo htmlspecialchars($muscle['primary_muscle']); ?>">
                                <label for="muscle-<?php echo htmlspecialchars($muscle['primary_muscle']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($muscle['primary_muscle'])); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No muscle groups available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="filter-group">
                <div class="filter-header">
                    <h3><i class="fas fa-cogs"></i> Equipment</h3>
                    <button class="clear-filter-btn" data-filter="equipment">Clear</button>
                </div>
                <div class="filter-options">
                    <?php if ($equipmentResult && mysqli_num_rows($equipmentResult) > 0): ?>
                        <?php while($equip = mysqli_fetch_assoc($equipmentResult)): ?>
                            <?php if (!empty($equip['equipment'])): ?>
                            <div class="filter-option">
                                <input type="checkbox" id="equipment-<?php echo htmlspecialchars($equip['equipment']); ?>" 
                                       class="filter-checkbox" data-filter="equipment" 
                                       value="<?php echo htmlspecialchars($equip['equipment']); ?>">
                                <label for="equipment-<?php echo htmlspecialchars($equip['equipment']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($equip['equipment'])); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No equipment options available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="filter-group">
                <div class="filter-header">
                    <h3><i class="fas fa-chart-line"></i> Difficulty</h3>
                    <button class="clear-filter-btn" data-filter="difficulty">Clear</button>
                </div>
                <div class="filter-options">
                    <div class="filter-option">
                        <input type="checkbox" id="difficulty-beginner" class="filter-checkbox" data-filter="difficulty" value="beginner">
                        <label for="difficulty-beginner">Beginner</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="difficulty-intermediate" class="filter-checkbox" data-filter="difficulty" value="intermediate">
                        <label for="difficulty-intermediate">Intermediate</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="difficulty-advanced" class="filter-checkbox" data-filter="difficulty" value="advanced">
                        <label for="difficulty-advanced">Advanced</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="results-column">
            <div class="results-header">
                <div class="filtering-by">
                    <span><i class="fas fa-filter"></i> Filtering by:</span>
                    <div id="active-filters" class="active-filters-container"></div>
                </div>
                <div class="sort-container">
                    <select id="sort-exercises" class="sort-dropdown">
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="difficulty-asc">Difficulty (Easiest First)</option>
                        <option value="difficulty-desc">Difficulty (Hardest First)</option>
                    </select>
                </div>
            </div>
            
            <div id="exercise-results" class="exercise-results-grid">
                <?php if (mysqli_num_rows($exercisesResult) > 0): ?>
                    <?php 
                    $patterns = [
                        'linear-gradient(45deg, var(--bg-element) 25%, transparent 25%, transparent 75%, var(--bg-element) 75%)',
                        'linear-gradient(60deg, rgba(255,255,255,.05) 25%, transparent 25%)',
                        'radial-gradient(circle, var(--bg-element-hover) 1px, transparent 1px)'
                    ];
                    ?>
                    
                    <?php while($exercise = mysqli_fetch_assoc($exercisesResult)): 
                        $randomPattern = $patterns[array_rand($patterns)];
                    ?>
                        <div class="exercise-card" 
                             data-type="<?= htmlspecialchars($exercise['exercise_type'] ?? '') ?>" 
                             data-difficulty="<?= strtolower(htmlspecialchars($exercise['difficulty'] ?? '')) ?>"
                             data-equipment="<?= htmlspecialchars($exercise['equipment'] ?? '') ?>"
                             data-muscle="<?= htmlspecialchars($exercise['primary_muscle'] ?? '') ?>">
                            
                            <div class="exercise-image" style="background-image: <?= $randomPattern ?>; background-size: 10px 10px;">
                                <img src="../assets/images/exercise-placeholder.jpg" alt="<?= htmlspecialchars($exercise['name']) ?>">
                                <?php if (in_array($exercise['id'], $trending_exercises)): ?>
                                    <div class="trending-badge">
                                        <i class="fas fa-fire"></i> Popular
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="exercise-details">
                                <div class="exercise-header">
                                    <h3 class="exercise-title"><?= htmlspecialchars($exercise['name']) ?></h3>
                                    <div class="exercise-year difficulty-<?= strtolower(htmlspecialchars($exercise['difficulty'] ?? 'beginner')) ?>">
                                        <?= ucfirst(htmlspecialchars($exercise['difficulty'] ?? '')) ?>
                                    </div>
                                </div>
                                
                                <div class="exercise-price">
                                    <div class="muscle-badge">
                                        <i class="fas fa-dumbbell"></i> <?= htmlspecialchars(ucfirst($exercise['primary_muscle'] ?? '')) ?>
                                    </div>
                                </div>
                                
                                <div class="exercise-specs">
                                    <div class="spec-row">
                                        <div class="spec-label">Type:</div>
                                        <div class="spec-value"><?= htmlspecialchars(ucfirst($exercise['exercise_type'] ?? '')) ?></div>
                                    </div>
                                    
                                    <div class="spec-row">
                                        <div class="spec-label">Equipment:</div>
                                        <div class="spec-value"><?= htmlspecialchars(ucfirst($exercise['equipment'] ?? '')) ?></div>
                                    </div>
                                    
                                    <div class="spec-row">
                                        <div class="spec-label">Muscle:</div>
                                        <div class="spec-value"><?= htmlspecialchars(ucfirst($exercise['primary_muscle'] ?? '')) ?></div>
                                    </div>
                                    
                                    <div class="spec-row">
                                        <div class="spec-label">Difficulty:</div>
                                        <div class="spec-value"><?= htmlspecialchars(ucfirst($exercise['difficulty'] ?? '')) ?></div>
                                    </div>
                                </div>
                                
                                <a href="excerciseDetails.php?id=<?= $exercise['id'] ?>" class="view-details-btn">
                                    <i class="fas fa-info-circle"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p><i class="fas fa-exclamation-circle"></i> No exercises found. Please try different filters.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="no-results-message" class="no-results" style="display: none;">
                <p><i class="fas fa-exclamation-circle"></i> No exercises found. Please try different filters.</p>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/muscle-map.css">
<script src="../assets/js/muscle-map.js"></script>
<script src="../assets/js/exercises.js"></script>

<?php
require_once '../includes/footer.php';
?>