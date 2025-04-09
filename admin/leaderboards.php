<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin
// if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
//     header("location: ../pages/login.php");
//     exit;
// }

// Include database connection
require_once "../assets/db_connection.php";

// Create leaderboard_categories table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS leaderboard_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    metric_type VARCHAR(50) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    reset_frequency ENUM('daily', 'weekly', 'monthly', 'never') NOT NULL DEFAULT 'never',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    die("Error creating table: " . $conn->error);
}

// Create leaderboard_stats table if it doesn't exist
$create_stats_table_sql = "CREATE TABLE IF NOT EXISTS leaderboard_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES leaderboard_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($create_stats_table_sql)) {
    die("Error creating stats table: " . $conn->error);
}

// Handle form submissions
$message = "";
$message_class = "";

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $delete_sql = "DELETE FROM leaderboard_categories WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Leaderboard category deleted successfully.";
        $message_class = "success";
    } else {
        $message = "Error deleting leaderboard category: " . $conn->error;
        $message_class = "danger";
    }
    $stmt->close();
}

// Process create/update request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create' || $_POST['action'] == 'update') {
            // Collect form data
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $metric_type = trim($_POST['metric_type']);
            $unit = trim($_POST['unit']);
            $reset_frequency = trim($_POST['reset_frequency']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if (empty($name) || empty($description) || empty($metric_type) || empty($unit)) {
                $message = "Please fill in all required fields.";
                $message_class = "danger";
            } else {
                if ($_POST['action'] == 'create') {
                    // Create new leaderboard category
                    $sql = "INSERT INTO leaderboard_categories (name, description, metric_type, unit, reset_frequency, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssi", $name, $description, $metric_type, $unit, $reset_frequency, $is_active);
                    
                    if ($stmt->execute()) {
                        $message = "New leaderboard category created successfully.";
                        $message_class = "success";
                    } else {
                        $message = "Error creating leaderboard category: " . $conn->error;
                        $message_class = "danger";
                    }
                } else if ($_POST['action'] == 'update' && isset($_POST['id'])) {
                    // Update existing leaderboard category
                    $id = $_POST['id'];
                    $sql = "UPDATE leaderboard_categories SET name = ?, description = ?, metric_type = ?, 
                            unit = ?, reset_frequency = ?, is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssii", $name, $description, $metric_type, $unit, $reset_frequency, $is_active, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Leaderboard category updated successfully.";
                        $message_class = "success";
                    } else {
                        $message = "Error updating leaderboard category: " . $conn->error;
                        $message_class = "danger";
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Fetch leaderboard categories for display
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT * FROM leaderboard_categories WHERE 1=1";

// Add filters
if (!empty($search)) {
    $search_term = "%{$search}%";
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
}

if ($status_filter == 'active') {
    $sql .= " AND is_active = 1";
} else if ($status_filter == 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);

// Bind search parameters if needed
if (!empty($search)) {
    $stmt->bind_param("ss", $search_term, $search_term);
}

$stmt->execute();
$result = $stmt->get_result();
$leaderboard_categories = [];

while ($row = $result->fetch_assoc()) {
    $leaderboard_categories[] = $row;
}
$stmt->close();

// Get available metric types for dropdown
$metric_types = [
    'calories_burned' => 'Calories Burned',
    'distance_ran' => 'Distance Ran',
    'weight_lifted' => 'Weight Lifted',
    'workout_duration' => 'Workout Duration',
    'workout_count' => 'Workout Count',
    'steps' => 'Steps',
    'points' => 'Points',
    'xp' => 'XP'
];

// Get reset frequency options for dropdown
$reset_frequencies = [
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'never' => 'Never (All-time)'
];

// Get category details for edit mode
$edit_mode = false;
$edit_category = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_sql = "SELECT * FROM leaderboard_categories WHERE id = ?";
    $stmt = $conn->prepare($edit_sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

// Include admin header
$page_title = 'Leaderboard Management'; 
?>

<div class="admin-container">
    <?php include_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content white-theme">
        <div class="admin-header">
            <h1><i class="fas fa-trophy"></i> Leaderboard Management</h1>
            <p>Create, edit, and manage leaderboard categories for your fitness community.</p>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-panel">
            <div class="panel-navigation">
                <a href="#" class="tab-btn active" data-tab="leaderboards">Leaderboards</a>
                <a href="#" class="tab-btn" data-tab="create">
                    <?php echo $edit_mode ? 'Edit Leaderboard' : 'Create New Leaderboard'; ?>
                </a>
            </div>
            
            <div class="tab-content active" id="leaderboards-tab">
                <div class="filter-controls">
                    <form method="GET" action="" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search leaderboards..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="leaderboards.php" class="btn btn-secondary">Reset</a>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Metric Type</th>
                                <th>Unit</th>
                                <th>Reset Frequency</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaderboard_categories)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No leaderboard categories found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($leaderboard_categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <?php 
                                            $metric_name = isset($metric_types[$category['metric_type']]) 
                                                ? $metric_types[$category['metric_type']] 
                                                : $category['metric_type'];
                                            echo htmlspecialchars($metric_name);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($category['unit']); ?></td>
                                    <td>
                                        <?php 
                                            $frequency_name = isset($reset_frequencies[$category['reset_frequency']]) 
                                                ? $reset_frequencies[$category['reset_frequency']] 
                                                : $category['reset_frequency'];
                                            echo htmlspecialchars($frequency_name);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="leaderboards.php?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="leaderboards.php?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this leaderboard category?');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                        <a href="../pages/detailed-leaderboard.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="tab-content" id="create-tab">
                <form method="POST" action="" class="admin-form">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Leaderboard Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_mode ? htmlspecialchars($edit_category['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="metric_type">Metric Type *</label>
                            <select id="metric_type" name="metric_type" required>
                                <option value="">Select Metric Type</option>
                                <?php foreach ($metric_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo $edit_mode && $edit_category['metric_type'] == $value ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="unit">Unit of Measurement *</label>
                            <input type="text" id="unit" name="unit" required
                                   value="<?php echo $edit_mode ? htmlspecialchars($edit_category['unit']) : ''; ?>"
                                   placeholder="e.g., kg, km, points, etc.">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="reset_frequency">Reset Frequency *</label>
                            <select id="reset_frequency" name="reset_frequency" required>
                                <option value="">Select Reset Frequency</option>
                                <?php foreach ($reset_frequencies as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo $edit_mode && $edit_category['reset_frequency'] == $value ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="4" required><?php 
                            echo $edit_mode ? htmlspecialchars($edit_category['description']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo $edit_mode && $edit_category['is_active'] == 1 ? 'checked' : ''; ?>>
                            Active (visible to users)
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_mode ? 'Update Leaderboard' : 'Create Leaderboard'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="leaderboards.php" class="btn btn-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
    
    // Auto-switch to create/edit tab if in edit mode
    <?php if ($edit_mode): ?>
    tabBtns.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));
    
    const createTab = document.querySelector('[data-tab="create"]');
    if (createTab) {
        createTab.classList.add('active');
        document.getElementById('create-tab').classList.add('active');
    }
    <?php endif; ?>
    
    // Auto-populate unit based on metric type selection
    const metricTypeSelect = document.getElementById('metric_type');
    const unitInput = document.getElementById('unit');
    
    if (metricTypeSelect && unitInput) {
        metricTypeSelect.addEventListener('change', function() {
            const metricType = this.value;
            
            // Only auto-populate if unit is empty or hasn't been manually changed
            if (!unitInput.value || unitInput.getAttribute('data-auto-filled') === 'true') {
                let defaultUnit = '';
                
                switch (metricType) {
                    case 'calories_burned':
                        defaultUnit = 'kcal';
                        break;
                    case 'distance_ran':
                        defaultUnit = 'km';
                        break;
                    case 'weight_lifted':
                        defaultUnit = 'kg';
                        break;
                    case 'workout_duration':
                        defaultUnit = 'min';
                        break;
                    case 'workout_count':
                        defaultUnit = 'workouts';
                        break;
                    case 'steps':
                        defaultUnit = 'steps';
                        break;
                    case 'points':
                        defaultUnit = 'pts';
                        break;
                    case 'xp':
                        defaultUnit = 'XP';
                        break;
                }
                
                unitInput.value = defaultUnit;
                unitInput.setAttribute('data-auto-filled', 'true');
            }
        });
        
        // Mark when user manually changes the unit
        unitInput.addEventListener('input', function() {
            if (this.value !== '') {
                this.setAttribute('data-auto-filled', 'false');
            }
        });
    }
});
</script>

<style>
/* Admin white theme styles */
.admin-content.white-theme {
    background-color: #fff;
    color: #333;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.admin-header {
    margin-bottom: 20px;
    border-bottom: 2px solid #f2f2f2;
    padding-bottom: 15px;
}

.admin-header h1 {
    font-size: 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-header p {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.admin-panel {
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.panel-navigation {
    display: flex;
    background-color: #f7f7f7;
    border-bottom: 1px solid #e1e1e1;
}

.tab-btn {
    padding: 12px 20px;
    font-weight: 600;
    color: #666;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
}

.tab-btn:hover {
    color: #333;
    background-color: #f0f0f0;
}

.tab-btn.active {
    color: #0056b3;
    border-bottom-color: #0056b3;
    background-color: #fff;
}

.tab-content {
    display: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

/* Table styles */
.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 14px;
}

.admin-table th {
    background-color: #f7f7f7;
    color: #333;
    font-weight: 600;
    text-align: left;
    padding: 12px 15px;
    border-bottom: 2px solid #e1e1e1;
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e1e1e1;
    vertical-align: middle;
}

.admin-table tr:hover {
    background-color: #f9f9f9;
}

.admin-table .actions {
    display: flex;
    gap: 5px;
    justify-content: flex-start;
    flex-wrap: wrap;
}

/* Filter controls */
.filter-controls {
    background-color: #f7f7f7;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.filter-form .form-group {
    flex: 1;
    min-width: 200px;
}

.filter-form input, .filter-form select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* Form styles */
.admin-form {
    max-width: 800px;
    margin: 0 auto;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -10px;
    margin-left: -10px;
}

.form-group {
    margin-bottom: 15px;
    padding-right: 10px;
    padding-left: 10px;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

@media (max-width: 768px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.admin-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.admin-form input[type="text"],
.admin-form select,
.admin-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.admin-form input[type="text"]:focus,
.admin-form select:focus,
.admin-form textarea:focus {
    border-color: #007bff;
    outline: none;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

.form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-badge.inactive {
    background-color: #f5e9e9;
    color: #c62828;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-primary {
    background-color: #007bff;
    color: #fff;
}

.btn-primary:hover {
    background-color: #0069d9;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-danger {
    background-color: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn i {
    margin-right: 5px;
}

/* Alert */
.alert {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Empty state */
.text-center {
    text-align: center;
}
</style>
