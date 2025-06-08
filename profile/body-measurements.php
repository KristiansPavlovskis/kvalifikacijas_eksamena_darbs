<?php
require_once 'profile_access_control.php';
require_once 'languages.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php?redirect=profile/quick-workout.php");
    exit;
}

require_once '../assets/db_connection.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
  
    if (isset($_POST['delete']) && isset($_POST['measurement_id'])) {
        $measurement_id = intval($_POST['measurement_id']);
        
        $check_stmt = $conn->prepare("SELECT id FROM body_measurements WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $measurement_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this measurement']);
            exit;
        }
        
        $delete_stmt = $conn->prepare("DELETE FROM body_measurements WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $measurement_id, $user_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Measurement deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting measurement: ' . $conn->error]);
        }
        exit;
    }
    
    if (isset($_POST['measurement_date'])) {
        $date = $_POST['measurement_date'];
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $body_fat = !empty($_POST['body_fat']) ? $_POST['body_fat'] : null;
        $chest = !empty($_POST['chest']) ? $_POST['chest'] : null;
        $arms = !empty($_POST['arms']) ? $_POST['arms'] : null;
        $waist = !empty($_POST['waist']) ? $_POST['waist'] : null;
        $shoulders = !empty($_POST['shoulders']) ? $_POST['shoulders'] : null;
        $legs = !empty($_POST['legs']) ? $_POST['legs'] : null;
        $hips = !empty($_POST['hips']) ? $_POST['hips'] : null;
        $notes = null;
        
        $arm_left_bicep = !empty($_POST['arm_left_bicep']) ? $_POST['arm_left_bicep'] : null;
        $arm_right_bicep = !empty($_POST['arm_right_bicep']) ? $_POST['arm_right_bicep'] : null;
        $arm_left_forearm = !empty($_POST['arm_left_forearm']) ? $_POST['arm_left_forearm'] : null;
        $arm_right_forearm = !empty($_POST['arm_right_forearm']) ? $_POST['arm_right_forearm'] : null;
        $leg_left_quad = !empty($_POST['leg_left_quad']) ? $_POST['leg_left_quad'] : null;
        $leg_right_quad = !empty($_POST['leg_right_quad']) ? $_POST['leg_right_quad'] : null;
        $leg_left_calf = !empty($_POST['leg_left_calf']) ? $_POST['leg_left_calf'] : null;
        $leg_right_calf = !empty($_POST['leg_right_calf']) ? $_POST['leg_right_calf'] : null;
        
        try {
            if (!empty($_POST['measurement_id'])) {
                $measurement_id = intval($_POST['measurement_id']);
                
                $check_stmt = $conn->prepare("SELECT id FROM body_measurements WHERE id = ? AND user_id = ?");
                $check_stmt->bind_param("ii", $measurement_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this measurement']);
                    exit;
                }
                
                $stmt = $conn->prepare("UPDATE body_measurements SET 
                    weight = ?, body_fat = ?, chest = ?, waist = ?, shoulders = ?, hips = ?, notes = ?, measurement_date = ?,
                    arm_left_bicep = ?, arm_right_bicep = ?, arm_left_forearm = ?, arm_right_forearm = ?,
                    leg_left_quad = ?, leg_right_quad = ?, leg_left_calf = ?, leg_right_calf = ? 
                    WHERE id = ? AND user_id = ?");

                $stmt->bind_param("ddddddssddddddddii", 
                    $weight, 
                    $body_fat, 
                    $chest, 
                    $waist, 
                    $shoulders, 
                    $hips, 
                    $notes, 
                    $date,
                    $arm_left_bicep, 
                    $arm_right_bicep, 
                    $arm_left_forearm, 
                    $arm_right_forearm,
                    $leg_left_quad, 
                    $leg_right_quad, 
                    $leg_left_calf, 
                    $leg_right_calf,
                    $measurement_id,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Measurement updated successfully!";
                } else {
                    $response['message'] = "Error updating measurement: " . $conn->error;
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO body_measurements 
                (user_id, weight, body_fat, chest, waist, shoulders, hips, notes, measurement_date,
                arm_left_bicep, arm_right_bicep, arm_left_forearm, arm_right_forearm,
                leg_left_quad, leg_right_quad, leg_left_calf, leg_right_calf) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("iddddddssdddddddd", 
                    $user_id, 
                    $weight, 
                    $body_fat, 
                    $chest, 
                    $waist, 
                    $shoulders, 
                    $hips, 
                    $notes, 
                    $date,
                    $arm_left_bicep, 
                    $arm_right_bicep, 
                    $arm_left_forearm, 
                    $arm_right_forearm,
                    $leg_left_quad, 
                    $leg_right_quad, 
                    $leg_left_calf, 
                    $leg_right_calf
                );
                
                if ($stmt->execute()) {
                    $measurement_id = $conn->insert_id;
                    $response['success'] = true;
                    $response['message'] = "Measurements saved successfully!";
                } else {
                    $response['message'] = "Error saving measurements: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
}

$records_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM body_measurements WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$all_stmt = $conn->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY created_at DESC");
$all_stmt->bind_param("i", $user_id);
$all_stmt->execute();
$all_result = $all_stmt->get_result();
$all_measurements = $all_result->fetch_all(MYSQLI_ASSOC);

if (count($all_measurements) >= 2) {
    $latest = $all_measurements[0];
    $previous = $all_measurements[1];
    
    $calc_percent = function($current, $previous) {
        if ($previous == 0 || $previous === null || $current === null) return 0;
        return (($current - $previous) / abs($previous)) * 100;
    };

    $weight_change = ($latest['weight'] ?? 0) - ($previous['weight'] ?? 0);
    $bodyfat_change = ($latest['body_fat'] ?? 0) - ($previous['body_fat'] ?? 0);
    $chest_change = ($latest['chest'] ?? 0) - ($previous['chest'] ?? 0);
    $waist_change = ($latest['waist'] ?? 0) - ($previous['waist'] ?? 0);
    $shoulders_change = ($latest['shoulders'] ?? 0) - ($previous['shoulders'] ?? 0);
    $hips_change = ($latest['hips'] ?? 0) - ($previous['hips'] ?? 0);
    $arm_left_bicep_change = ($latest['arm_left_bicep'] ?? 0) - ($previous['arm_left_bicep'] ?? 0);
    $arm_right_bicep_change = ($latest['arm_right_bicep'] ?? 0) - ($previous['arm_right_bicep'] ?? 0);
    $arm_left_forearm_change = ($latest['arm_left_forearm'] ?? 0) - ($previous['arm_left_forearm'] ?? 0);
    $arm_right_forearm_change = ($latest['arm_right_forearm'] ?? 0) - ($previous['arm_right_forearm'] ?? 0);
    $leg_left_quad_change = ($latest['leg_left_quad'] ?? 0) - ($previous['leg_left_quad'] ?? 0);
    $leg_right_quad_change = ($latest['leg_right_quad'] ?? 0) - ($previous['leg_right_quad'] ?? 0);
    $leg_left_calf_change = ($latest['leg_left_calf'] ?? 0) - ($previous['leg_left_calf'] ?? 0);
    $leg_right_calf_change = ($latest['leg_right_calf'] ?? 0) - ($previous['leg_right_calf'] ?? 0);

    $weight_change_percent = $calc_percent($latest['weight'], $previous['weight']);
    $bodyfat_change_percent = $calc_percent($latest['body_fat'], $previous['body_fat']);
    $chest_change_percent = $calc_percent($latest['chest'], $previous['chest']);
    $waist_change_percent = $calc_percent($latest['waist'], $previous['waist']);
    $shoulders_change_percent = $calc_percent($latest['shoulders'], $previous['shoulders']);
    $hips_change_percent = $calc_percent($latest['hips'], $previous['hips']);
    $arm_left_bicep_change_percent = $calc_percent($latest['arm_left_bicep'], $previous['arm_left_bicep']);
    $arm_right_bicep_change_percent = $calc_percent($latest['arm_right_bicep'], $previous['arm_right_bicep']);
    $arm_left_forearm_change_percent = $calc_percent($latest['arm_left_forearm'], $previous['arm_left_forearm']);
    $arm_right_forearm_change_percent = $calc_percent($latest['arm_right_forearm'], $previous['arm_right_forearm']);
    $leg_left_quad_change_percent = $calc_percent($latest['leg_left_quad'], $previous['leg_left_quad']);
    $leg_right_quad_change_percent = $calc_percent($latest['leg_right_quad'], $previous['leg_right_quad']);
    $leg_left_calf_change_percent = $calc_percent($latest['leg_left_calf'], $previous['leg_left_calf']);
    $leg_right_calf_change_percent = $calc_percent($latest['leg_right_calf'], $previous['leg_right_calf']);
}

$chart_stmt = $conn->prepare("
    SELECT measurement_date, weight, body_fat, chest, waist, shoulders,
           arm_left_bicep, arm_right_bicep, arm_left_forearm, arm_right_forearm,
           leg_left_quad, leg_right_quad, leg_left_calf, leg_right_calf, hips
    FROM body_measurements 
    WHERE user_id = ? 
    ORDER BY created_at ASC 
    LIMIT 30
");
$chart_stmt->bind_param("i", $user_id);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();
$chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$measurements = $result->fetch_all(MYSQLI_ASSOC);

function getMeasurementPhotos($measurement_id, $conn) {
    return ['front' => null, 'side' => null];
}

$dates = [];
$weight_data = [];
$bodyfat_data = [];
$chest_data = [];
$waist_data = [];
$shoulders_data = [];
$arm_left_data = [];
$arm_right_data = [];

foreach ($chart_data as $row) {
    $dates[] = date('M d', strtotime($row['measurement_date']));
    $weight_data[] = $row['weight'];
    $bodyfat_data[] = $row['body_fat'];
    $chest_data[] = $row['chest'];
    $waist_data[] = $row['waist'];
    $shoulders_data[] = $row['shoulders'];
    $arm_left_data[] = ($row['arm_left_bicep'] + $row['arm_left_forearm']) / 2;
    $arm_right_data[] = ($row['arm_right_bicep'] + $row['arm_right_forearm']) / 2;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="global-profile.css" rel="stylesheet">
    <title><?= t('body_measurements') ?> | <?= t('fitness_dashboard') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="display: block;">
    <div class="bm-container-combine">
    <?php require_once 'sidebar.php'; ?>
    <div class="bm-container">
            <div class="bm-card"> 
                <h2><i class="fas fa-chart-line"></i> <?= t('body_measurements') ?></h2>
                
                <div class="bm-content-layout">
                    <div class="bm-left-content">
                        <h3><i class="fas fa-history"></i> <?= t('measurement_history') ?></h3>
                        <div class="bm-table-responsive">
                            <table class="bm-table">
                                <thead>
                                    <tr>
                                        <th><?= t('date') ?></th>
                                        <th><?= t('weight') ?></th>
                                        <th><?= t('body_fat') ?></th>
                                        <th><?= t('action') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="measurementHistory">
                                    <?php if (!empty($measurements)): ?>
                                        <?php foreach ($measurements as $measurement): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($measurement['measurement_date']) ?></td>
                                                <td><?= $measurement['weight'] ? htmlspecialchars($measurement['weight']) . ' ' . t('kg') : t('na') ?></td>
                                                <td><?= $measurement['body_fat'] ? htmlspecialchars($measurement['body_fat']) . '%' : t('na') ?></td>
                                                <td>
                                                    <button class="bm-btn bm-btn-secondary view-details" data-id="<?= $measurement['id'] ?>" title="<?= t('view_details') ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="bm-btn bm-btn-danger delete-measurement" data-id="<?= $measurement['id'] ?>" title="<?= t('delete') ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center"><?= t('no_measurements') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="bm-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1" class="bm-pagination-link">&laquo; <?= t('first') ?></a>
                                <a href="?page=<?= $page - 1 ?>" class="bm-pagination-link">&lsaquo; <?= t('previous') ?></a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?= $i ?>" class="bm-pagination-link <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="bm-pagination-link"><?= t('next') ?> &rsaquo;</a>
                                <a href="?page=<?= $total_pages ?>" class="bm-pagination-link"><?= t('last') ?> &raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bm-right-content">
                        <h3><?= t('measurement_change_heatmap') ?></h3>
                        <p><?= t('comparing_latest_measurements') ?></p>
                        
                        <div class="bm-heatmap">
                            <?php
                            $changes = [
                                t('weight') => [$weight_change ?? 0, $weight_change_percent ?? 0],
                                t('body_fat') => [$bodyfat_change ?? 0, $bodyfat_change_percent ?? 0],
                                t('chest') => [$chest_change ?? 0, $chest_change_percent ?? 0],
                                t('shoulders') => [$shoulders_change ?? 0, $shoulders_change_percent ?? 0],
                                t('waist') => [$waist_change ?? 0, $waist_change_percent ?? 0],
                                t('hips') => [$hips_change ?? 0, $hips_change_percent ?? 0],
                                t('left_bicep') => [$arm_left_bicep_change ?? 0, $arm_left_bicep_change_percent ?? 0],
                                t('right_bicep') => [$arm_right_bicep_change ?? 0, $arm_right_bicep_change_percent ?? 0],
                                t('left_forearm') => [$arm_left_forearm_change ?? 0, $arm_left_forearm_change_percent ?? 0],
                                t('right_forearm') => [$arm_right_forearm_change ?? 0, $arm_right_forearm_change_percent ?? 0],
                                t('left_quad') => [$leg_left_quad_change ?? 0, $leg_left_quad_change_percent ?? 0],
                                t('right_quad') => [$leg_right_quad_change ?? 0, $leg_right_quad_change_percent ?? 0],
                                t('left_calf') => [$leg_left_calf_change ?? 0, $leg_left_calf_change_percent ?? 0],
                                t('right_calf') => [$leg_right_calf_change ?? 0, $leg_right_calf_change_percent ?? 0]
                            ];
                            
                            foreach ($changes as $label => $change):
                                $value = $change[0] ?? 0;
                                $percent = $change[1] ?? 0;
                                $intensity = min(abs($percent) / 10, 0.5);
                                $sign = '';
                                
                                if ($label === t('weight')) {
                                    $color = 'rgba(100, 100, 100, ' . $intensity . ')';
                                    $sign = $value >= 0 ? '+' : '-';
                                }
                                else if ($label === t('body_fat') || $label === t('waist') || $label === t('hips')) {
                                    if ($value > 0) {
                                        $color = 'rgba(244, 67, 54, ' . $intensity . ')';
                                        $sign = '+';
                                    } else {
                                        $color = 'rgba(76, 175, 80, ' . $intensity . ')';
                                        $sign = '-';
                                    }
                                }
                                else {
                                    if ($value > 0) {
                                        $color = 'rgba(76, 175, 80, ' . $intensity . ')';
                                        $sign = '+';
                                    } else {
                                        $color = 'rgba(244, 67, 54, ' . $intensity . ')';
                                        $sign = '-';
                                    }
                                }
                                
                                $displayValue = $sign . number_format(abs($value), 1);
                            ?>
                            <div class="bm-heatmap-cell" style="background-color: <?= $color ?>">
                                <div class="bm-heatmap-value"><?= $displayValue ?></div>
                                <div class="bm-heatmap-label"><?= $label ?></div>
                                <div class="bm-heatmap-percent"><?= $sign . number_format(abs($percent), 1) ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>
    <div class="bm-fab" id="addMeasurementBtn">
        <i class="fas fa-plus"></i>
    </div>

    <div class="bm-modal" id="measurementModal">
        <div class="bm-modal-content">
            <span class="bm-modal-close">&times;</span>
            <div class="bm-modal-header">
                <h2><i class="fas fa-tape"></i> <?= t('record_measurements') ?></h2>
            </div>
            
            <form id="measurementForm">
                <div class="bm-two-column-layout">
                    <div class="bm-column">
                        <h3><?= t('basic_measurements') ?></h3>
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="measurement_date"><?= t('date') ?></label>
                                <input type="date" id="measurement_date" name="measurement_date" required>
                            </div>
                        </div>
                        
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="weight"><?= t('weight') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="weight" name="weight" required>
                                    <div class="bm-input-group-append"><?= t('kg') ?></div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="body_fat"><?= t('body_fat') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="body_fat" name="body_fat">
                                    <div class="bm-input-group-append">%</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="chest"><?= t('chest') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="chest" name="chest">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="shoulders"><?= t('shoulders') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="shoulders" name="shoulders">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="waist"><?= t('waist') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="waist" name="waist">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="hips"><?= t('hips') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="hips" name="hips">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bm-column">
                        <h3><?= t('detailed_measurements') ?></h3>
                        <div class="bm-form-group-title"><?= t('arms') ?></div>
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="arm_left_bicep"><?= t('left_bicep') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="arm_left_bicep" name="arm_left_bicep">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="arm_right_bicep"><?= t('right_bicep') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="arm_right_bicep" name="arm_right_bicep">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>

                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="arm_left_forearm"><?= t('left_forearm') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="arm_left_forearm" name="arm_left_forearm">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="arm_right_forearm"><?= t('right_forearm') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="arm_right_forearm" name="arm_right_forearm">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bm-form-group-title"><?= t('legs') ?></div>
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="leg_left_quad"><?= t('left_quad') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="leg_left_quad" name="leg_left_quad">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="leg_right_quad"><?= t('right_quad') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="leg_right_quad" name="leg_right_quad">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                        <div class="bm-form-row">
                            <div class="bm-form-control">
                                <label for="leg_left_calf"><?= t('left_calf') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="leg_left_calf" name="leg_left_calf">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                            <div class="bm-form-control">
                                <label for="leg_right_calf"><?= t('right_calf') ?></label>
                                <div class="bm-input-group">
                                    <input type="number" step="0.1" id="leg_right_calf" name="leg_right_calf">
                                    <div class="bm-input-group-append">cm</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bm-modal-footer">
                    <button type="submit" id="saveButton" class="bm-btn"><?= t('save_measurements') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="bm-modal" id="detailsModal">
        <div class="bm-modal-content">
            <span class="bm-modal-close" id="detailsModalClose">&times;</span>
            <div class="bm-modal-header">
                <h2><i class="fas fa-info-circle"></i> <?= t('measurement_details') ?></h2>
            </div>
            
            <div class="bm-two-column-layout">
                <div class="bm-column">
                    <h3><?= t('basic_information') ?></h3>
                    <div class="bm-details-group">
                        <p><strong><?= t('date') ?>:</strong> <span id="detail-date"></span></p>
                        <p><strong><?= t('weight') ?>:</strong> <span id="detail-weight"></span> <?= t('kg') ?></p>
                        <p><strong><?= t('body_fat') ?>:</strong> <span id="detail-bodyfat"></span> %</p>
                        <p><strong><?= t('chest') ?>:</strong> <span id="detail-chest"></span> cm</p>
                        <p><strong><?= t('waist') ?>:</strong> <span id="detail-waist"></span> cm</p>
                        <p><strong><?= t('shoulders') ?>:</strong> <span id="detail-shoulders"></span> cm</p>
                        <p><strong><?= t('hips') ?>:</strong> <span id="detail-hips"></span> cm</p>
                    </div>
                </div>
                <div class="bm-column">
                    <h3><?= t('detailed_measurements') ?></h3>
                    <div class="bm-form-group-title"><?= t('arms') ?></div>
                    <div class="bm-details-group">
                        <p><strong><?= t('left_bicep') ?>:</strong> <span id="detail-left-bicep"></span> cm</p>
                        <p><strong><?= t('right_bicep') ?>:</strong> <span id="detail-right-bicep"></span> cm</p>
                        <p><strong><?= t('left_forearm') ?>:</strong> <span id="detail-left-forearm"></span> cm</p>
                        <p><strong><?= t('right_forearm') ?>:</strong> <span id="detail-right-forearm"></span> cm</p>
                    </div>
                    
                    <div class="bm-form-group-title"><?= t('legs') ?></div>
                    <div class="bm-details-group">
                        <p><strong><?= t('left_quad') ?>:</strong> <span id="detail-left-quad"></span> cm</p>
                        <p><strong><?= t('right_quad') ?>:</strong> <span id="detail-right-quad"></span> cm</p>
                        <p><strong><?= t('left_calf') ?>:</strong> <span id="detail-left-calf"></span> cm</p>
                        <p><strong><?= t('right_calf') ?>:</strong> <span id="detail-right-calf"></span> cm</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bm-confirm-modal" id="deleteConfirmModal">
        <div class="bm-confirm-content">
            <h3><i class="fas fa-exclamation-triangle"></i> <?= t('confirm_deletion') ?></h3>
            <p><?= t('confirm_delete_measurement') ?></p>
            <div class="bm-confirm-actions">
                <button class="bm-btn bm-btn-secondary" id="cancelDelete"><?= t('cancel') ?></button>
                <button class="bm-btn bm-btn-danger" id="confirmDelete"><?= t('delete') ?></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('measurement_date').value = today;
            
            setupModal();
            setupActionButtons();
            setupDeleteConfirmation();

            document.getElementById('addMeasurementBtn').addEventListener('click', function() {
                openModal();
            });
        });
        
        function setupActionButtons() {
            const viewButtons = document.querySelectorAll('.view-details');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const measurementId = this.dataset.id;
                    fetchMeasurementDetails(measurementId);
                });
            });
            
            const deleteButtons = document.querySelectorAll('.delete-measurement');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const measurementId = this.dataset.id;
                    showDeleteConfirmation(measurementId);
                });
            });
            
            document.getElementById('detailsModalClose').addEventListener('click', function() {
                closeDetailsModal();
            });
            
            window.addEventListener('click', function(event) {
                const detailsModal = document.getElementById('detailsModal');
                if (event.target === detailsModal) {
                    closeDetailsModal();
                }
            });
        }
        
        function setupDeleteConfirmation() {
            const confirmModal = document.getElementById('deleteConfirmModal');
            const cancelButton = document.getElementById('cancelDelete');
            const confirmButton = document.getElementById('confirmDelete');
            
            cancelButton.addEventListener('click', function() {
                closeDeleteConfirmation();
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === confirmModal) {
                    closeDeleteConfirmation();
                }
            });
            
            confirmButton.addEventListener('click', function() {
                const measurementId = confirmButton.dataset.id;
                deleteMeasurement(measurementId);
            });
        }
        
        function showDeleteConfirmation(measurementId) {
            const confirmModal = document.getElementById('deleteConfirmModal');
            const confirmButton = document.getElementById('confirmDelete');
            
            confirmButton.dataset.id = measurementId;
       
            confirmModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                confirmModal.classList.add('active');
            }, 10);
        }
        
        function closeDeleteConfirmation() {
            const confirmModal = document.getElementById('deleteConfirmModal');
            confirmModal.classList.remove('active');
            
            setTimeout(() => {
                confirmModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        function deleteMeasurement(measurementId) {
            const formData = new FormData();
            formData.append('delete', 'true');
            formData.append('measurement_id', measurementId);
            
            fetch('body-measurements.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeDeleteConfirmation();
                
                if (data.success) {
                    showToast('Success', data.message, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Error', data.message, 'error');
                }
            })
            .catch(error => {
                closeDeleteConfirmation();
                console.error('Error:', error);
                showToast('Error', 'An error occurred while deleting the measurement', 'error');
            });
        }
        
        function fetchMeasurementDetails(measurementId) {
            fetch('get_measurement.php?id=' + measurementId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayMeasurementDetails(data.measurement);
                    } else {
                        showToast('Error', data.message || 'Failed to load measurement details', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching measurement details:', error);
                    showToast('Error', 'An error occurred while loading measurement details', 'error');
                });
        }
        
        function displayMeasurementDetails(measurement) {
            document.getElementById('detail-date').textContent = measurement.measurement_date || 'N/A';
            document.getElementById('detail-weight').textContent = measurement.weight || 'N/A';
            document.getElementById('detail-bodyfat').textContent = measurement.body_fat || 'N/A';
            document.getElementById('detail-chest').textContent = measurement.chest || 'N/A';
            document.getElementById('detail-waist').textContent = measurement.waist || 'N/A';
            document.getElementById('detail-shoulders').textContent = measurement.shoulders || 'N/A';
            document.getElementById('detail-hips').textContent = measurement.hips || 'N/A';

            document.getElementById('detail-left-bicep').textContent = measurement.arm_left_bicep || 'N/A';
            document.getElementById('detail-right-bicep').textContent = measurement.arm_right_bicep || 'N/A';
            document.getElementById('detail-left-forearm').textContent = measurement.arm_left_forearm || 'N/A';
            document.getElementById('detail-right-forearm').textContent = measurement.arm_right_forearm || 'N/A';
            document.getElementById('detail-left-quad').textContent = measurement.leg_left_quad || 'N/A';
            document.getElementById('detail-right-quad').textContent = measurement.leg_right_quad || 'N/A';
            document.getElementById('detail-left-calf').textContent = measurement.leg_left_calf || 'N/A';
            document.getElementById('detail-right-calf').textContent = measurement.leg_right_calf || 'N/A';
            
            openDetailsModal();
        }
        
        function openDetailsModal() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                detailsModal.classList.add('active');
            }, 10);
        }
        
        function closeDetailsModal() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.classList.remove('active');
            setTimeout(() => {
                detailsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        function setupModal() {
            const modal = document.getElementById('measurementModal');
            const closeBtn = document.querySelector('.bm-modal-close');
            
            closeBtn.addEventListener('click', function() {
                closeModal();
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        function openModal() {
            const modal = document.getElementById('measurementModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            document.getElementById('measurementForm').reset();
            document.getElementById('measurement_date').value = new Date().toISOString().split('T')[0];
        }

        function closeModal() {
            const modal = document.getElementById('measurementModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        document.getElementById('measurementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            formData.append('ajax', 'true');
            
            const weight = formData.get('weight');
            if (!weight) {
                alert('Weight is required');
                return;
            }
            
            if (!formData.get('measurement_date')) {
                formData.set('measurement_date', new Date().toISOString().split('T')[0]);
            }
            
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('body-measurements.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(text);
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('measurementModal');
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    
                    showToast('Success', data.message, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save Measurements';
                    showToast('Error', data.message || 'Failed to save measurements', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveButton.disabled = false;
                saveButton.innerHTML = 'Save Measurements';
                showToast('Error', 'An error occurred while saving measurements. Please try again.', 'error');
            });
        });

        function showToast(title, message, type) {
            const toast = document.createElement('div');
            toast.className = `bm-toast bm-toast-${type}`;
            toast.innerHTML = `
                <div class="bm-toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <strong>${title}</strong>
                    <button class="bm-toast-close">&times;</button>
                </div>
                <div class="bm-toast-body">${message}</div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 5000);
            
            toast.querySelector('.bm-toast-close').addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>