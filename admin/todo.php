<?php

$pageTitle = "Todo";
$additionalHead = '<link rel="stylesheet" href="/assets/css/todo.css">';

include_once 'includes/sidebar.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$tasks = [
    [
        'id' => 1,
        'title' => 'Review new user registrations',
        'description' => 'Check and approve new user accounts created in the last 24 hours',
        'due_date' => '2023-06-20',
        'priority' => 'high',
        'completed' => false,
        'project' => 'User Management',
        'created_at' => '2023-06-15 09:30:00'
    ],
    [
        'id' => 2,
        'title' => 'Update product pricing',
        'description' => 'Adjust pricing for premium subscription plans based on new strategy',
        'due_date' => '2023-06-18',
        'priority' => 'high',
        'completed' => false,
        'project' => 'Marketplace',
        'created_at' => '2023-06-14 14:15:00'
    ],
    [
        'id' => 3,
        'title' => 'Fix login issue on mobile devices',
        'description' => 'Investigate and resolve the authentication error reported by mobile users',
        'due_date' => '2023-06-17',
        'priority' => 'critical',
        'completed' => false,
        'project' => 'Technical',
        'created_at' => '2023-06-16 10:45:00'
    ],
    [
        'id' => 4,
        'title' => 'Prepare monthly analytics report',
        'description' => 'Compile user engagement and conversion metrics for the management meeting',
        'due_date' => '2023-06-25',
        'priority' => 'medium',
        'completed' => false,
        'project' => 'Analytics',
        'created_at' => '2023-06-12 16:20:00'
    ],
    [
        'id' => 5,
        'title' => 'Send newsletter to subscribers',
        'description' => 'Draft and schedule the monthly newsletter with new feature announcements',
        'due_date' => '2023-06-15',
        'priority' => 'medium',
        'completed' => true,
        'project' => 'Marketing',
        'created_at' => '2023-06-10 11:30:00'
    ],
    [
        'id' => 6,
        'title' => 'Onboard new team members',
        'description' => 'Complete orientation process for new hires in the development team',
        'due_date' => '2023-06-14',
        'priority' => 'high',
        'completed' => true,
        'project' => 'HR',
        'created_at' => '2023-06-09 09:00:00'
    ],
    [
        'id' => 7,
        'title' => 'Update privacy policy',
        'description' => 'Review and update our privacy policy to comply with new regulations',
        'due_date' => '2023-06-30',
        'priority' => 'low',
        'completed' => false,
        'project' => 'Legal',
        'created_at' => '2023-06-08 13:45:00'
    ]
];

$filteredTasks = [];
foreach ($tasks as $task) {
    if ($filter === 'all' || 
        ($filter === 'active' && !$task['completed']) || 
        ($filter === 'completed' && $task['completed'])) {
        $filteredTasks[] = $task;
    }
}

$projects = array_unique(array_column($tasks, 'project'));
sort($projects);

$priorities = ['critical', 'high', 'medium', 'low'];

$selectedTaskId = isset($_GET['task']) ? intval($_GET['task']) : null;
$selectedTask = null;
if ($selectedTaskId) {
    foreach ($tasks as $task) {
        if ($task['id'] == $selectedTaskId) {
            $selectedTask = $task;
            break;
        }
    }
}

$newTaskMode = isset($_GET['new']) && $_GET['new'] == '1';
?>

<div class="todo-container">
    <div class="todo-sidebar">
        <button class="new-task-btn" onclick="location.href='?new=1'">
            <i class="fas fa-plus"></i> New Task
        </button>
        
        <div class="todo-filters">
            <h4>Filters</h4>
            <ul class="filter-list">
                <li class="<?php echo $filter == 'all' ? 'active' : ''; ?>">
                    <a href="?filter=all">
                        <i class="fas fa-tasks"></i>
                        <span>All Tasks</span>
                        <span class="task-count"><?php echo count($tasks); ?></span>
                    </a>
                </li>
                <li class="<?php echo $filter == 'active' ? 'active' : ''; ?>">
                    <a href="?filter=active">
                        <i class="fas fa-clock"></i>
                        <span>Active</span>
                        <span class="task-count"><?php echo count(array_filter($tasks, function($task) { return !$task['completed']; })); ?></span>
                    </a>
                </li>
                <li class="<?php echo $filter == 'completed' ? 'active' : ''; ?>">
                    <a href="?filter=completed">
                        <i class="fas fa-check-circle"></i>
                        <span>Completed</span>
                        <span class="task-count"><?php echo count(array_filter($tasks, function($task) { return $task['completed']; })); ?></span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="todo-projects">
            <h4>Projects</h4>
            <ul class="project-list">
                <?php foreach ($projects as $project): ?>
                    <li>
                        <a href="?filter=<?php echo $filter; ?>&project=<?php echo urlencode($project); ?>">
                            <span class="project-color" style="background-color: <?php echo generateProjectColor($project); ?>"></span>
                            <span><?php echo htmlspecialchars($project); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="todo-statistics">
            <h4>Statistics</h4>
            <div class="stats-item">
                <div class="stats-info">
                    <span>Completed</span>
                    <span class="stats-value"><?php echo count(array_filter($tasks, function($task) { return $task['completed']; })); ?>/<?php echo count($tasks); ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo count($tasks) > 0 ? (count(array_filter($tasks, function($task) { return $task['completed']; })) / count($tasks) * 100) : 0; ?>%"></div>
                </div>
            </div>
            <div class="stats-item">
                <div class="stats-info">
                    <span>High Priority</span>
                    <span class="stats-value"><?php echo count(array_filter($tasks, function($task) { return $task['priority'] === 'high' || $task['priority'] === 'critical'; })); ?></span>
                </div>
            </div>
            <div class="stats-item">
                <div class="stats-info">
                    <span>Due Soon</span>
                    <span class="stats-value"><?php echo count(array_filter($tasks, function($task) { 
                        return !$task['completed'] && (strtotime($task['due_date']) - time()) / (60 * 60 * 24) <= 3; 
                    })); ?></span>
                </div>
            </div>
        </div>
    </div>
    
        <!-- Todo Content Area -->
    <div class="todo-content">
        <?php if ($newTaskMode || $selectedTask): ?>
            <!-- Task Form (New/Edit) -->
            <div class="task-form-container">
                <div class="form-header">
                    <h2><?php echo $newTaskMode ? 'Create New Task' : 'Edit Task'; ?></h2>
                    <button class="close-form" onclick="location.href='?filter=<?php echo $filter; ?>'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form class="task-form" action="#" method="post">
                    <?php if (!$newTaskMode): ?>
                    <input type="hidden" name="task_id" value="<?php echo $selectedTask['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="task_title">Title</label>
                        <input type="text" id="task_title" name="task_title" class="form-control" 
                               value="<?php echo $selectedTask ? htmlspecialchars($selectedTask['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="task_description">Description</label>
                        <textarea id="task_description" name="task_description" class="form-control" rows="4"><?php echo $selectedTask ? htmlspecialchars($selectedTask['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="task_project">Project</label>
                            <select id="task_project" name="task_project" class="form-control">
                                <?php foreach ($projects as $project): ?>
                                <option value="<?php echo htmlspecialchars($project); ?>" <?php echo $selectedTask && $selectedTask['project'] === $project ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project); ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="new">+ Add New Project</option>
                            </select>
                        </div>
                        
                        <div class="form-group half">
                            <label for="task_priority">Priority</label>
                            <select id="task_priority" name="task_priority" class="form-control">
                                <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo $priority; ?>" <?php echo $selectedTask && $selectedTask['priority'] === $priority ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($priority); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="task_due_date">Due Date</label>
                            <input type="date" id="task_due_date" name="task_due_date" class="form-control" 
                                   value="<?php echo $selectedTask ? $selectedTask['due_date'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="task_status">Status</label>
                            <select id="task_status" name="task_status" class="form-control">
                                <option value="0" <?php echo $selectedTask && !$selectedTask['completed'] ? 'selected' : ''; ?>>Active</option>
                                <option value="1" <?php echo $selectedTask && $selectedTask['completed'] ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <?php if (!$newTaskMode): ?>
                        <button type="button" class="btn btn-danger">Delete Task</button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?php echo $newTaskMode ? 'Create Task' : 'Update Task'; ?></button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Task List -->
            <div class="task-list-container">
                <div class="task-list-header">
                    <div class="list-title">
                        <h2><?php echo ucfirst($filter); ?> Tasks</h2>
                        <span class="task-count-badge"><?php echo count($filteredTasks); ?></span>
                    </div>
                    
                    <div class="list-actions">
                        <div class="search-container">
                            <input type="text" class="search-input" placeholder="Search tasks...">
                            <button class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <div class="sort-options">
                            <select class="sort-select">
                                <option value="due_date">Sort by Due Date</option>
                                <option value="priority">Sort by Priority</option>
                                <option value="created">Sort by Created Date</option>
                                <option value="title">Sort by Title</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="task-list">
                    <?php if (empty($filteredTasks)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas <?php echo $filter === 'completed' ? 'fa-check-circle' : 'fa-tasks'; ?>"></i>
                        </div>
                        <h3>No <?php echo $filter; ?> tasks found</h3>
                        <p><?php echo $filter === 'completed' ? 'Tasks you complete will appear here.' : 'Add some tasks to get started.'; ?></p>
                        <?php if ($filter !== 'completed'): ?>
                        <button class="btn btn-primary" onclick="location.href='?new=1'">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                        <?php foreach ($filteredTasks as $task): ?>
                            <div class="task-item <?php echo $task['completed'] ? 'completed' : ''; ?>" 
                                 data-priority="<?php echo $task['priority']; ?>"
                                 data-project="<?php echo htmlspecialchars($task['project']); ?>">
                                <div class="task-checkbox">
                                    <input type="checkbox" id="task-<?php echo $task['id']; ?>" 
                                           <?php echo $task['completed'] ? 'checked' : ''; ?>>
                                    <label for="task-<?php echo $task['id']; ?>"></label>
                                </div>
                                
                                <div class="task-content">
                                    <a href="?filter=<?php echo $filter; ?>&task=<?php echo $task['id']; ?>" class="task-title">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </a>
                                    
                                    <div class="task-meta">
                                        <span class="task-project" style="background-color: <?php echo generateProjectColor($task['project']); ?>">
                                            <?php echo htmlspecialchars($task['project']); ?>
                                        </span>
                                        
                                        <span class="task-due-date <?php echo isDueSoon($task['due_date']) && !$task['completed'] ? 'due-soon' : ''; ?>">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo formatDueDate($task['due_date']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="task-priority <?php echo $task['priority']; ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </div>
                                
                                <div class="task-actions">
                                    <button class="btn-icon" title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="btn-icon" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.task-checkbox input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskItem = this.closest('.task-item');
            if (this.checked) {
                taskItem.classList.add('completed');
            } else {
                taskItem.classList.remove('completed');
            }
        });
    });
    
    const projectSelect = document.getElementById('task_project');
    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                const newProject = prompt('Enter new project name:');
                if (newProject) {
                    const newOption = document.createElement('option');
                    newOption.value = newProject;
                    newOption.text = newProject;
                    
                    this.insertBefore(newOption, this.options[this.options.length - 1]);
                    
                    this.value = newProject;
                } else {
                    this.selectedIndex = 0;
                }
            }
        });
    }
    
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const taskItems = document.querySelectorAll('.task-item');
            
            taskItems.forEach(task => {
                const title = task.querySelector('.task-title').textContent.toLowerCase();
                if (title.includes(searchTerm)) {
                    task.style.display = '';
                } else {
                    task.style.display = 'none';
                }
            });
        });
    }
    
    const sortSelect = document.querySelector('.sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            const taskList = document.querySelector('.task-list');
            const tasks = Array.from(taskList.querySelectorAll('.task-item'));
            
            tasks.sort((a, b) => {
                if (sortBy === 'priority') {
                    const priorityOrder = { 'critical': 0, 'high': 1, 'medium': 2, 'low': 3 };
                    return priorityOrder[a.dataset.priority] - priorityOrder[b.dataset.priority];
                } else if (sortBy === 'title') {
                    return a.querySelector('.task-title').textContent.localeCompare(b.querySelector('.task-title').textContent);
                }
                return 0;
            });
            
            tasks.forEach(task => task.remove());
            
            tasks.forEach(task => taskList.appendChild(task));
        });
    }
});
</script>

<?php
function generateProjectColor($project) {
    $hash = md5($project);
    $hue = hexdec(substr($hash, 0, 2)) % 360;
    return "hsl($hue, 70%, 50%)";
}

function isDueSoon($dueDate) {
    $daysUntilDue = (strtotime($dueDate) - time()) / (60 * 60 * 24);
    return $daysUntilDue <= 3 && $daysUntilDue >= 0;
}

function formatDueDate($dueDate) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    if ($dueDate === $today) {
        return 'Today';
    } elseif ($dueDate === $tomorrow) {
        return 'Tomorrow';
    } else {
        return date('M d', strtotime($dueDate));
    }
}
?>
 