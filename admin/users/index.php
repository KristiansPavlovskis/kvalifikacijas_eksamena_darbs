<?php
require_once dirname(__DIR__, 2) . '/assets/db_connection.php';
require_once dirname(__DIR__, 2) . '/profile/languages.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$is_superadmin = false;

$sql = "SELECT COUNT(*) as count FROM user_roles WHERE user_id = ? AND role_id = 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_superadmin = ($row['count'] > 0);
}

if (!$is_superadmin) {
    header("Location: ../../pages/access_denied.php");
    exit;
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE u.username LIKE ? OR u.email LIKE ? OR u.id LIKE ?";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}

$count_sql = "SELECT COUNT(*) as total FROM users u $search_condition";
$count_stmt = $conn->prepare($count_sql);

if (!empty($search_params)) {
    $count_stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

$users_sql = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.created_at as registration_date,
        u.last_active,
        (SELECT COUNT(*) FROM workout_templates wt WHERE wt.user_id = u.id) as templates_count,
        (SELECT COUNT(*) FROM workouts w WHERE w.user_id = u.id) as workouts_count,
        (SELECT GROUP_CONCAT(r.name SEPARATOR ', ') FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = u.id) as roles
    FROM users u
    $search_condition
    ORDER BY u.id DESC
    LIMIT ? OFFSET ?
";

$users_stmt = $conn->prepare($users_sql);

if (!empty($search_params)) {
    $param_types = str_repeat('s', count($search_params)) . 'ii';
    $params = array_merge($search_params, [$limit, $offset]);
    $users_stmt->bind_param($param_types, ...$params);
} else {
    $users_stmt->bind_param('ii', $limit, $offset);
}

$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = [];

while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

$pageTitle = t('user_management');
$bodyClass = "admin-page";
?>

<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : 'en'; ?>">
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
        .admin-table td {
            max-width: 100px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .user-email {
            word-break: break-all;
            max-width: 180px;
            display: inline-block;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow: auto;
        }
        
        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: var(--dark-bg-surface);
            border-radius: 0.75rem;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background-color: var(--dark-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            font-size: 0.875rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-cancel {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .alert {
            padding: 1rem;
            margin: 1rem 1.5rem;
            border-radius: 0.5rem;
        }
        
        .alert-success {
            background-color: rgba(44, 198, 146, 0.1);
            border: 1px solid rgba(44, 198, 146, 0.3);
            color: #2cc692;
        }
        
        .alert-error {
            background-color: rgba(230, 22, 22, 0.1);
            border: 1px solid rgba(230, 22, 22, 0.3);
            color: #e61616;
        }
        
        .alert ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-table {
                display: none;
            }
            
            .mobile-user-cards {
                display: block;
            }
            
            .user-card {
                background-color: var(--dark-bg-surface);
                border-radius: 0.75rem;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .user-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 0.5rem;
            }
            
            .user-card-info {
                margin-left: 0.5rem;
                flex: 1;
            }
            
            .user-card-meta {
                color: var(--text-muted);
                font-size: 0.75rem;
                margin-top: 0.5rem;
            }
            
            .user-card-stats {
                display: flex;
                justify-content: space-between;
                padding: 0.75rem 0;
                border-top: 1px solid var(--border-color);
                border-bottom: 1px solid var(--border-color);
                margin: 0.75rem 0;
            }
            
            .user-card-stat {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .stat-number {
                font-size: 1.25rem;
                font-weight: 700;
            }
            
            .stat-label {
                font-size: 0.75rem;
                color: var(--text-muted);
            }
            
            .user-card-actions {
                display: flex;
                gap: 0.5rem;
            }
            
            .user-card-action {
                flex: 1;
                text-align: center;
                padding: 0.75rem;
                border-radius: 0.5rem;
                background-color: var(--dark-accent);
                color: var(--text-color);
                text-decoration: none;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .edit-action {
                background-color: rgba(44, 198, 146, 0.2);
                color: #2cc692;
            }
            
            .delete-action {
                background-color: rgba(230, 22, 22, 0.2);
                color: #e61616;
            }
        }
    
        @media (min-width: 769px) {
            .mobile-user-cards {
                display: none;
            }
            
            .admin-table {
                display: table;
            }
            
            .admin-table th, 
            .admin-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .admin-table .user-name {
                font-size: 0.875rem;
            }
            
            .admin-table .user-email {
                font-size: 0.75rem;
            }
            
            .admin-table .user-avatar {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">
    <div class="admin-wrapper">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-topbar">
                <h1><?php echo t('user_management'); ?></h1>
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                    <span><?php echo t('admin'); ?></span>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_messages'])): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php 
                        foreach ($_SESSION['error_messages'] as $error) {
                            echo "<li>" . htmlspecialchars($error) . "</li>";
                        }
                        unset($_SESSION['error_messages']);
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-container">
                <div class="admin-toolbar">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <form action="" method="GET">
                            <input type="text" class="search-input" name="search" placeholder="<?php echo t('search_users_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                    <div class="toolbar-actions">
                        <a href="#" class="add-btn" id="openAddUserModal">
                            <i class="fas fa-plus"></i>
                            <span><?php echo t('add_user'); ?></span>
                        </a>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>ID</th>
                            <th><?php echo t('user'); ?></th>
                            <th><?php echo t('registration'); ?></th>
                            <th><?php echo t('last_active'); ?></th>
                            <th><?php echo t('templates'); ?></th>
                            <th><?php echo t('workouts'); ?></th>
                            <th><?php echo t('type'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                </td>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            <?php echo substr($user['username'], 0, 1); ?>
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['registration_date'])); ?></td>
                                <td><?php echo $user['last_active'] ? date('Y-m-d', strtotime($user['last_active'])) : t('never'); ?></td>
                                <td><?php echo $user['templates_count']; ?></td>
                                <td><?php echo $user['workouts_count']; ?></td>
                                <td>
                                    <?php 
                                    $roles = $user['roles'] ?? '';
                                    $is_premium = strpos($roles, 'premium') !== false;
                                    ?>
                                    <span class="user-type <?php echo $is_premium ? 'premium' : ''; ?>">
                                        <?php echo $is_premium ? t('premium') : t('basic'); ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view-btn" data-user-id="<?php echo $user['id']; ?>" title="<?php echo t('view_details'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="action-btn edit-btn" title="<?php echo t('edit_user'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete-btn" data-user-id="<?php echo $user['id']; ?>" title="<?php echo t('delete_user'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 2rem;"><?php echo t('no_users_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="mobile-user-cards">
        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-card-header">
                    <div class="user-cell">
                        <div class="user-avatar">
                            <?php echo substr($user['username'], 0, 1); ?>
                        </div>
                        <div class="user-card-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            <div class="user-card-meta">
                                <?php echo t('registered'); ?>: <?php echo date('M d, Y', strtotime($user['registration_date'])); ?><br>
                                <?php echo t('last_active'); ?>: <?php echo $user['last_active'] ? date('M d, Y', strtotime($user['last_active'])) : t('never'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="user-card-stats">
                    <div class="user-card-stat">
                        <div class="stat-number"><?php echo $user['workouts_count']; ?></div>
                        <div class="stat-label"><?php echo t('workouts'); ?></div>
                    </div>
                    <div class="user-card-stat">
                        <div class="stat-number"><?php echo $user['templates_count']; ?></div>
                        <div class="stat-label"><?php echo t('templates'); ?></div>
                    </div>
                </div>
                
                <div class="user-card-actions">
                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="user-card-action edit-action">
                        <i class="fas fa-edit"></i>
                        <span><?php echo t('edit_user'); ?></span>
                    </a>
                    <button class="user-card-action delete-action" data-user-id="<?php echo $user['id']; ?>">
                        <i class="fas fa-trash"></i>
                        <span><?php echo t('delete'); ?></span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($users)): ?>
            <div class="user-card">
                <p style="text-align: center; padding: 2rem;"><?php echo t('no_users_found'); ?></p>
            </div>
        <?php endif; ?>
    </div>
                <div class="pagination">
                    <div class="pagination-info">
                        <?php echo t('showing'); ?> <?php echo min(($page - 1) * $limit + 1, $total_users); ?>-<?php echo min($page * $limit, $total_users); ?> <?php echo t('of'); ?> <?php echo $total_users; ?> <?php echo t('users'); ?>
                    </div>
                    <div class="pagination-buttons">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4 && $start_page > 1) {
                            $start_page = max(1, $end_page - 4);
                        }
                        ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="user-detail-modal" id="userDetailModal">
        <div class="user-detail-content">
            <div class="user-detail-header">
                <h2 id="modalUserEmail"><?php echo t('loading'); ?>...</h2>
                <div class="user-detail-info">
                    <div id="modalRegistered"><?php echo t('registered'); ?>: <?php echo t('loading'); ?>...</div>
                    <div id="modalLastLogin"><?php echo t('last_active'); ?>: <?php echo t('loading'); ?>...</div>
                </div>
            </div>
            <div class="user-detail-stats">
                <div class="stat-item">
                    <div class="stat-value" id="modalWorkouts">0</div>
                    <div class="stat-label"><?php echo t('workouts'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="modalTemplates">0</div>
                    <div class="stat-label"><?php echo t('templates'); ?></div>
                </div>
               
            </div>
            <div class="user-detail-actions">
                <a href="#" class="action-btn-full edit-user-btn" id="modalEditBtn">
                    <i class="fas fa-edit"></i>
                    <span><?php echo t('edit_user'); ?></span>
                </a>
                <button class="action-btn-full delete-user-btn" id="modalDeleteBtn">
                    <i class="fas fa-trash"></i>
                    <span><?php echo t('delete_account'); ?></span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?php echo t('add_user'); ?></h2>
            </div>
            <form id="addUserForm" method="post" action="process-add-user.php">
                <div class="form-group">
                    <label for="username"><?php echo t('username'); ?></label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email"><?php echo t('email_address'); ?></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password"><?php echo t('password'); ?></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="repeat_password"><?php echo t('confirm_new_password'); ?></label>
                    <input type="password" class="form-control" id="repeat_password" name="repeat_password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="closeAddUserModal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn-submit"><?php echo t('add_user'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="/assets/js/admin-sidebar.js"></script>
    <script>
        const viewButtons = document.querySelectorAll('.view-btn');
        const userDetailModal = document.getElementById('userDetailModal');
        const modalEditBtn = document.getElementById('modalEditBtn');
        const modalDeleteBtn = document.getElementById('modalDeleteBtn');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                const userId = button.getAttribute('data-user-id');
                openUserDetailModal(userId);
            });
        });
        
        userDetailModal.addEventListener('click', (event) => {
            if (event.target === userDetailModal) {
                closeUserDetailModal();
            }
        });
        
        function openUserDetailModal(userId) {
            const userRow = document.querySelector(`.view-btn[data-user-id="${userId}"]`).closest('tr');
            const userName = userRow.querySelector('.user-name').textContent;
            const userEmail = userRow.querySelector('.user-email').textContent;
            const userRegistration = userRow.cells[3].textContent;
            const userLastActive = userRow.cells[4].textContent;
            const userWorkouts = userRow.cells[6].textContent;
            const userTemplates = userRow.cells[5].textContent;
            
            document.getElementById('modalUserEmail').textContent = userEmail;
            document.getElementById('modalUserEmail').innerHTML = `${userName} <span class="user-status-pill active"><?php echo t('active'); ?></span>`;
            document.getElementById('modalRegistered').textContent = `<?php echo t('registered'); ?>: ${userRegistration}`;
            document.getElementById('modalLastLogin').textContent = `<?php echo t('last_active'); ?>: ${userLastActive}`;
            document.getElementById('modalWorkouts').textContent = userWorkouts;
            document.getElementById('modalTemplates').textContent = userTemplates;
            
            modalEditBtn.href = `edit-user.php?id=${userId}`;
            modalDeleteBtn.setAttribute('data-user-id', userId);
            
            userDetailModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeUserDetailModal() {
            userDetailModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        const deleteButtons = document.querySelectorAll('.delete-btn, .delete-action, #modalDeleteBtn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const userId = button.getAttribute('data-user-id');
                if (confirm('<?php echo t('confirm_delete_account_message'); ?>')) {
                    deleteUser(userId);
                }
            });
        });
        
        function deleteUser(userId) {
            const formData = new FormData();
            formData.append('user_id', userId);
            
            fetch('delete-user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                
                    if (userDetailModal.classList.contains('active')) {
                        closeUserDetailModal();
                    }
                    
                    window.location.reload();
                } else {
                    alert('<?php echo t('error'); ?>: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo t('error_deleting_account'); ?>');
            });
        }
        
        document.querySelectorAll('.delete-action').forEach(button => {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
        });
        
        document.querySelectorAll('.delete-action').forEach(button => {
            button.addEventListener('click', () => {
                const userId = button.getAttribute('data-user-id');
                if (confirm('<?php echo t('confirm_delete_account_message'); ?>')) {
                    deleteUser(userId);
                }
            });
        });
        
        const selectAllCheckbox = document.getElementById('select-all');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        
        selectAllCheckbox.addEventListener('change', () => {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
        
        const addUserModal = document.getElementById('addUserModal');
        const openAddUserModalBtn = document.getElementById('openAddUserModal');
        const closeAddUserModalBtn = document.getElementById('closeAddUserModal');
        
        openAddUserModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addUserModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        closeAddUserModalBtn.addEventListener('click', () => {
            addUserModal.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        addUserModal.addEventListener('click', (event) => {
            if (event.target === addUserModal) {
                addUserModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        const addUserForm = document.getElementById('addUserForm');
        addUserForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password').value;
            const repeatPassword = document.getElementById('repeat_password').value;
            
            if (password !== repeatPassword) {
                e.preventDefault();
                alert('<?php echo t('passwords_dont_match'); ?>');
            }
        });
    </script>
</body>
</html> 