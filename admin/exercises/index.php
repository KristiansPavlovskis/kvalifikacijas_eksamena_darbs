<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$is_admin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND (role_id = 5 OR role_id = 4)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_admin = ($row['count'] > 0);
}

if (!$is_admin) {
    header("Location: ../../pages/access_denied.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query = "SELECT id, name FROM exercises WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND name LIKE ?";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $types .= 's';
}

$countQuery = str_replace("SELECT id, name", "SELECT COUNT(*) as total", $query);

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_exercises = $result->fetch_assoc()['total'];
$total_pages = ceil($total_exercises / $limit);

$query .= " ORDER BY name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$types .= 'i';
$params[] = $offset;
$types .= 'i';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$exercises = $stmt->get_result();

$total_exercises_count = 0;

$stats_sql = "SELECT COUNT(*) as total FROM exercises";
$stats_result = $conn->query($stats_sql);
if ($row = $stats_result->fetch_assoc()) {
    $total_exercises_count = $row['total'];
}

$added_this_week = 0;

$pageTitle = t('exercise_library');
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | GYMVERSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/normalize.css">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="/admin/includes/admin-styles.css">
    <style>
        .exercise-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .exercise-table th,
        .exercise-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .exercise-table th {
            background-color: var(--card-header-bg);
            color: var(--text-color);
            font-weight: 600;
        }
        
        .exercise-table tr:hover {
            background-color: var(--hover-bg);
        }
        
        .checkbox-column {
            width: 40px;
            text-align: center;
        }
        
        .action-column {
            width: 100px;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-inactive {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .edit-btn, .delete-btn {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            background-color: transparent;
            transition: all 0.2s ease;
        }
        
        .edit-btn {
            color: #3498db;
            margin-right: 5px;
        }
        
        .delete-btn {
            color: #e74c3c;
        }
        
        .edit-btn:hover, .delete-btn:hover {
            background-color: rgba(52, 73, 94, 0.1);
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex-grow: 1;
            padding-left: 70px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--input-bg);
            color: var(--text-color);
        }
        
        .search-btn {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .reset-btn {
            padding: 10px 15px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .page-info {
            color: var(--text-secondary);
        }
        
        .page-buttons {
            display: flex;
            gap: 10px;
        }
        
        .page-btn {
            padding: 8px 15px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .page-btn:hover {
            background-color: var(--hover-bg);
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            padding: 10px;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .add-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .add-btn i {
            margin-right: 8px;
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .filter-container {
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-group {
            flex-grow: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .filter-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--input-bg);
            color: var(--text-color);
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1><?php echo t('exercise_library'); ?></h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span><?php echo t('admin'); ?></span>
                </div>
            </div>
            
            <div class="dashboard-container">
                <?php 
                if (isset($_SESSION['message'])) {
                    $messageType = $_SESSION['message']['type'];
                    $messageText = $_SESSION['message']['text'];
                    $alertClass = ($messageType === 'success') ? 'alert-success' : 'alert-danger';
                    
                    echo "<div class='alert {$alertClass}'>{$messageText}</div>";
                    
                    unset($_SESSION['message']);
                }
                ?>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_exercises_count; ?></div>
                        <div class="stat-label"><?php echo t('total_exercises'); ?></div>
                    </div>
                </div>
                
                <div class="actions-bar">
                    <a href="add-exercise.php" class="add-btn">
                        <i class="fas fa-plus"></i> <?php echo t('add_exercise'); ?>
                    </a>
                    
                    <div>
                        <button class="reset-btn" id="bulkDeleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> <?php echo t('delete_selected'); ?>
                        </button>
                    </div>
                </div>
                
                <form action="" method="GET" id="filterForm">
                    <div class="search-container">
                        <input type="text" name="search" class="search-input" placeholder="<?php echo t('search_exercises_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="reset-btn" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> <?php echo t('reset'); ?>
                        </button>
                    </div>
                </form>
                
                <table class="exercise-table">
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th><?php echo t('exercise_name'); ?></th>
                            <th class="action-column"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($exercises->num_rows > 0): ?>
                            <?php while ($exercise = $exercises->fetch_assoc()): ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <input type="checkbox" class="exercise-checkbox" value="<?php echo $exercise['id']; ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($exercise['name']); ?></td>
                                    <td class="action-column">
                                        <a href="edit-exercise.php?id=<?php echo $exercise['id']; ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $exercise['id']; ?>)" class="delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px;"><?php echo t('no_exercises_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <div class="page-info">
                        <?php 
                        $start = min($offset + 1, $total_exercises);
                        $end = min($offset + $limit, $total_exercises);
                        echo t('showing') . " $start " . t('to') . " $end " . t('of') . " $total_exercises " . t('exercises');
                        ?>
                    </div>
                    <div class="page-buttons">
                        <button 
                            onclick="window.location.href='?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>'" 
                            class="page-btn" 
                            <?php echo ($page <= 1) ? 'disabled' : ''; ?>
                        >
                            <?php echo t('previous'); ?>
                        </button>
                        <button 
                            onclick="window.location.href='?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>'" 
                            class="page-btn" 
                            <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>
                        >
                            <?php echo t('next'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.exercise-checkbox');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkDeleteBtn();
            });
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkDeleteBtn);
            });
            
            function updateBulkDeleteBtn() {
                const checkedCount = document.querySelectorAll('.exercise-checkbox:checked').length;
                bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
            }
        
            bulkDeleteBtn.addEventListener('click', function() {
                if (confirm('<?php echo t('confirm_delete_selected_exercises'); ?>')) {
                    const selectedIds = [];
                    document.querySelectorAll('.exercise-checkbox:checked').forEach(checkbox => {
                        selectedIds.push(checkbox.value);
                    });
                    
                    if (selectedIds.length > 0) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'delete-exercises.php';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids';
                        input.value = JSON.stringify(selectedIds);
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            });
        });
        
        function confirmDelete(id) {
            if (confirm('<?php echo t('confirm_delete_exercise'); ?>')) {
                window.location.href = 'delete-exercise.php?id=' + id;
            }
        }
        
        function resetFilters() {
            window.location.href = 'index.php';
        }
    </script>
</body>
</html> 