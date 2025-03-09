<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once 'assets/db_connection.php';

// Get user ID
$user_id = $_SESSION["user_id"];

// Get request data (either GET or POST)
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'GET') {
    $action = $_GET['action'] ?? '';
    $template_id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : null;
} else if ($requestMethod === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $action = $data['action'] ?? '';
    $template_id = isset($data['template_id']) ? (int)$data['template_id'] : null;
} else {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

/**
 * Retrieves all templates for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array List of templates
 */
function listTemplates($conn, $user_id) {
    $query = "SELECT 
        t.id, 
        t.name, 
        t.description, 
        t.created_at, 
        t.last_used,
        t.icon,
        t.color,
        COUNT(te.id) as exercise_count, 
        SEC_TO_TIME(AVG(t.duration_minutes * 60)) as avg_duration,
        (
            SELECT COUNT(*) 
            FROM workouts w 
            WHERE w.template_id = t.id AND w.user_id = ?
        ) as times_used
    FROM workout_templates t
    LEFT JOIN template_exercises te ON t.id = te.template_id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.last_used DESC, t.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    
    $templates = [];
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $templates[] = $row;
    }
    
    return $templates;
}

/**
 * Retrieves a specific template with all its exercises and sets
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $template_id Template ID
 * @return array Template data with exercises
 */
function getTemplate($conn, $user_id, $template_id) {
    // Get template details
    $query = "SELECT * FROM workout_templates WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $template_id, $user_id);
    mysqli_stmt_execute($stmt);
    $template = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if (!$template) {
        return null;
    }
    
    // Get exercises for this template
    $query = "SELECT 
        te.id,
        te.exercise_name,
        te.exercise_order,
        te.notes,
        GROUP_CONCAT(
            CONCAT(ts.set_number, ':', ts.weight, ':', ts.reps, ':', ts.is_warmup)
            ORDER BY ts.set_number
            SEPARATOR ';'
        ) as sets_data
    FROM template_exercises te
    LEFT JOIN template_sets ts ON te.id = ts.exercise_id
    WHERE te.template_id = ?
    GROUP BY te.id
    ORDER BY te.exercise_order";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $template_id);
    mysqli_stmt_execute($stmt);
    
    $exercises = [];
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $sets = [];
        if ($row['sets_data']) {
            foreach (explode(';', $row['sets_data']) as $set_data) {
                list($set_number, $weight, $reps, $is_warmup) = explode(':', $set_data);
                $sets[] = [
                    'set_number' => (int)$set_number,
                    'weight' => (float)$weight,
                    'reps' => (int)$reps,
                    'is_warmup' => (bool)(int)$is_warmup
                ];
            }
        }
        $row['sets'] = $sets;
        unset($row['sets_data']);
        $exercises[] = $row;
    }
    
    $template['exercises'] = $exercises;
    
    return $template;
}

/**
 * Creates a new workout template
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param array $data Template data
 * @return array|bool Created template or false on failure
 */
function createTemplate($conn, $user_id, $data) {
    if (!isset($data['name']) || !isset($data['exercises']) || empty($data['exercises'])) {
        return false;
    }
    
    try {
        mysqli_begin_transaction($conn);
        
        // Insert template
        $query = "INSERT INTO workout_templates (
            user_id,
            name,
            description,
            duration_minutes,
            icon,
            color,
            created_at,
            last_used
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = mysqli_prepare($conn, $query);
        $description = $data['description'] ?? '';
        $duration = $data['duration_minutes'] ?? 60;
        $icon = $data['icon'] ?? 'dumbbell';
        $color = $data['color'] ?? '#4361ee';
        
        mysqli_stmt_bind_param($stmt, "ississ", 
            $user_id,
            $data['name'],
            $description,
            $duration,
            $icon,
            $color
        );
        
        mysqli_stmt_execute($stmt);
        $template_id = mysqli_insert_id($conn);
        
        // Insert exercises
        foreach ($data['exercises'] as $index => $exercise) {
            $query = "INSERT INTO template_exercises (
                template_id,
                exercise_name,
                exercise_order,
                notes
            ) VALUES (?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $query);
            $notes = $exercise['notes'] ?? '';
            
            mysqli_stmt_bind_param($stmt, "isis", 
                $template_id,
                $exercise['name'],
                $index + 1,
                $notes
            );
            
            mysqli_stmt_execute($stmt);
            $exercise_id = mysqli_insert_id($conn);
            
            // Insert sets for this exercise
            if (isset($exercise['sets']) && !empty($exercise['sets'])) {
                foreach ($exercise['sets'] as $set_index => $set) {
                    $query = "INSERT INTO template_sets (
                        exercise_id,
                        set_number,
                        weight,
                        reps,
                        is_warmup
                    ) VALUES (?, ?, ?, ?, ?)";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    $is_warmup = $set['is_warmup'] ?? ($set_index < 2 ? 1 : 0);
                    
                    mysqli_stmt_bind_param($stmt, "iidii", 
                        $exercise_id,
                        $set_index + 1,
                        $set['weight'],
                        $set['reps'],
                        $is_warmup
                    );
                    
                    mysqli_stmt_execute($stmt);
                }
            }
        }
        
        mysqli_commit($conn);
        
        // Return the created template
        return getTemplate($conn, $user_id, $template_id);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Updates an existing workout template
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $template_id Template ID
 * @param array $data Template data
 * @return array|bool Updated template or false on failure
 */
function updateTemplate($conn, $user_id, $template_id, $data) {
    // Check if template exists and belongs to user
    $query = "SELECT id FROM workout_templates WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $template_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        return false;
    }
    
    try {
        mysqli_begin_transaction($conn);
        
        // Update template
        $query = "UPDATE workout_templates SET 
            name = ?,
            description = ?,
            duration_minutes = ?,
            icon = ?,
            color = ?
        WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $duration = $data['duration_minutes'] ?? 60;
        $icon = $data['icon'] ?? 'dumbbell';
        $color = $data['color'] ?? '#4361ee';
        
        mysqli_stmt_bind_param($stmt, "ssissi", 
            $name,
            $description,
            $duration,
            $icon,
            $color,
            $template_id
        );
        
        mysqli_stmt_execute($stmt);
        
        // If we're updating exercises, delete all existing ones and add new ones
        if (isset($data['exercises']) && is_array($data['exercises'])) {
            // Get IDs of existing exercises
            $query = "SELECT id FROM template_exercises WHERE template_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $template_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $exercise_ids = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $exercise_ids[] = $row['id'];
            }
            
            // Delete all sets for these exercises
            if (!empty($exercise_ids)) {
                $ids_str = implode(',', $exercise_ids);
                mysqli_query($conn, "DELETE FROM template_sets WHERE exercise_id IN ($ids_str)");
            }
            
            // Delete all exercises
            $query = "DELETE FROM template_exercises WHERE template_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $template_id);
            mysqli_stmt_execute($stmt);
            
            // Insert new exercises
            foreach ($data['exercises'] as $index => $exercise) {
                $query = "INSERT INTO template_exercises (
                    template_id,
                    exercise_name,
                    exercise_order,
                    notes
                ) VALUES (?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $query);
                $notes = $exercise['notes'] ?? '';
                
                mysqli_stmt_bind_param($stmt, "isis", 
                    $template_id,
                    $exercise['name'],
                    $index + 1,
                    $notes
                );
                
                mysqli_stmt_execute($stmt);
                $exercise_id = mysqli_insert_id($conn);
                
                // Insert sets for this exercise
                if (isset($exercise['sets']) && !empty($exercise['sets'])) {
                    foreach ($exercise['sets'] as $set_index => $set) {
                        $query = "INSERT INTO template_sets (
                            exercise_id,
                            set_number,
                            weight,
                            reps,
                            is_warmup
                        ) VALUES (?, ?, ?, ?, ?)";
                        
                        $stmt = mysqli_prepare($conn, $query);
                        $is_warmup = $set['is_warmup'] ?? ($set_index < 2 ? 1 : 0);
                        
                        mysqli_stmt_bind_param($stmt, "iidii", 
                            $exercise_id,
                            $set_index + 1,
                            $set['weight'],
                            $set['reps'],
                            $is_warmup
                        );
                        
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
        
        mysqli_commit($conn);
        
        // Return the updated template
        return getTemplate($conn, $user_id, $template_id);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Deletes a workout template
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $template_id Template ID
 * @return bool Success or failure
 */
function deleteTemplate($conn, $user_id, $template_id) {
    // Check if template exists and belongs to user
    $query = "SELECT id FROM workout_templates WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $template_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        return false;
    }
    
    try {
        mysqli_begin_transaction($conn);
        
        // Get IDs of existing exercises
        $query = "SELECT id FROM template_exercises WHERE template_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $template_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $exercise_ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $exercise_ids[] = $row['id'];
        }
        
        // Delete all sets for these exercises
        if (!empty($exercise_ids)) {
            $ids_str = implode(',', $exercise_ids);
            mysqli_query($conn, "DELETE FROM template_sets WHERE exercise_id IN ($ids_str)");
        }
        
        // Delete all exercises
        $query = "DELETE FROM template_exercises WHERE template_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $template_id);
        mysqli_stmt_execute($stmt);
        
        // Delete the template
        $query = "DELETE FROM workout_templates WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $template_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Updates last_used timestamp for a template
 * 
 * @param mysqli $conn Database connection
 * @param int $template_id Template ID
 * @return bool Success or failure
 */
function updateLastUsed($conn, $template_id) {
    $query = "UPDATE workout_templates SET last_used = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $template_id);
    return mysqli_stmt_execute($stmt);
}

// Handle different actions
$response = [];

switch($action) {
    case 'list':
        $response['templates'] = listTemplates($conn, $user_id);
        break;
    
    case 'get':
        if (!$template_id) {
            http_response_code(400);
            $response['error'] = 'Template ID is required';
            break;
        }
        
        $template = getTemplate($conn, $user_id, $template_id);
        
        if (!$template) {
            http_response_code(404);
            $response['error'] = 'Template not found';
            break;
        }
        
        $response['template'] = $template;
        break;
    
    case 'create':
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Method not allowed';
            break;
        }
        
        $template = createTemplate($conn, $user_id, $data);
        
        if (!$template) {
            http_response_code(400);
            $response['error'] = 'Failed to create template';
            break;
        }
        
        $response['template'] = $template;
        $response['message'] = 'Template created successfully';
        break;
    
    case 'update':
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Method not allowed';
            break;
        }
        
        if (!$template_id) {
            http_response_code(400);
            $response['error'] = 'Template ID is required';
            break;
        }
        
        $template = updateTemplate($conn, $user_id, $template_id, $data);
        
        if (!$template) {
            http_response_code(400);
            $response['error'] = 'Failed to update template';
            break;
        }
        
        $response['template'] = $template;
        $response['message'] = 'Template updated successfully';
        break;
    
    case 'delete':
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Method not allowed';
            break;
        }
        
        if (!$template_id) {
            http_response_code(400);
            $response['error'] = 'Template ID is required';
            break;
        }
        
        $success = deleteTemplate($conn, $user_id, $template_id);
        
        if (!$success) {
            http_response_code(400);
            $response['error'] = 'Failed to delete template';
            break;
        }
        
        $response['success'] = true;
        $response['message'] = 'Template deleted successfully';
        break;
    
    case 'use':
        if (!$template_id) {
            http_response_code(400);
            $response['error'] = 'Template ID is required';
            break;
        }
        
        // Get the template to return to the client
        $template = getTemplate($conn, $user_id, $template_id);
        
        if (!$template) {
            http_response_code(404);
            $response['error'] = 'Template not found';
            break;
        }
        
        // Update last_used timestamp
        updateLastUsed($conn, $template_id);
        
        $response['template'] = $template;
        break;
    
    default:
        http_response_code(400);
        $response['error'] = 'Invalid action';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 