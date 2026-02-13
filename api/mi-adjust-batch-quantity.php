<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$batch_id = $_POST['batch_id'] ?? '';
$adjustment_type = $_POST['adjustment_type'] ?? '';
$adjustment_amount = $_POST['adjustment_amount'] ?? '';
$new_quantity = $_POST['new_quantity'] ?? '';
$reason = trim($_POST['reason'] ?? '');

// Validation
if (empty($batch_id) || empty($adjustment_type) || ($adjustment_amount === '') || ($new_quantity === '') || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (!in_array($adjustment_type, ['add', 'subtract', 'set'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid adjustment type']);
    exit;
}

if (!is_numeric($adjustment_amount) || floatval($adjustment_amount) < 0) {
    echo json_encode(['success' => false, 'message' => 'Adjustment amount must be a non-negative number']);
    exit;
}

if (!is_numeric($new_quantity) || floatval($new_quantity) < 0) {
    echo json_encode(['success' => false, 'message' => 'New quantity must be a non-negative number']);
    exit;
}

// Verify batch exists and get inventory_id + cost info
$check_batch = $conn->prepare("SELECT batch_id, inventory_id, current_quantity, initial_quantity, total_cost FROM inventory_batches WHERE batch_id = ?");
$check_batch->bind_param("s", $batch_id);
$check_batch->execute();
$batch_result = $check_batch->get_result();

if ($batch_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Batch not found']);
    exit;
}

$current_batch = $batch_result->fetch_assoc();
$old_quantity = $current_batch['current_quantity'];
$initial_quantity = $current_batch['initial_quantity'];
$inventory_id = $current_batch['inventory_id'];
$total_cost = $current_batch['total_cost'];
$unit_cost = $initial_quantity > 0 ? ($total_cost / $initial_quantity) : 0;

// Validate that new quantity doesn't exceed initial quantity (optional warning)
if (floatval($new_quantity) > $initial_quantity) {
    // You can choose to allow this or not - currently allowing with a note in the response
    $warning_message = 'Note: New quantity exceeds initial quantity. This may indicate an issue.';
}

// Update batch quantity
$stmt = $conn->prepare("
    UPDATE inventory_batches 
    SET current_quantity = ?
    WHERE batch_id = ?
");

$stmt->bind_param("ds", $new_quantity, $batch_id);

if ($stmt->execute()) {
    // Log the adjustment in inventory_movements
    try {
        $movement_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $quantity_change = floatval($new_quantity) - floatval($old_quantity);
        $movement_value = abs($quantity_change) * $unit_cost;
        if ($quantity_change < 0) {
            $movement_value = -$movement_value;
        }
        $staff_id = $_SESSION['user_id'];

        $mv_stmt = $conn->prepare("
            INSERT INTO inventory_movements (
                movement_id, inventory_id, batch_id, movement_type, quantity,
                old_quantity, new_quantity, unit_cost, total_value,
                reference_type, staff_id, movement_date, reason
            ) VALUES (?, ?, ?, 'adjustment', ?, ?, ?, ?, ?, 'manual', ?, NOW(), ?)
        ");
        $mv_stmt->bind_param(
            'sssdddddss',
            $movement_id,
            $inventory_id,
            $batch_id,
            $quantity_change,
            $old_quantity,
            $new_quantity,
            $unit_cost,
            $movement_value,
            $staff_id,
            $reason
        );
        $mv_stmt->execute();
        $mv_stmt->close();
    } catch (Exception $e) {
        // Movement logging is non-critical - continue even if table doesn't exist yet
    }

    $response = [
        'success' => true,
        'message' => 'Quantity adjusted successfully',
        'old_quantity' => $old_quantity,
        'new_quantity' => $new_quantity,
        'adjustment_type' => $adjustment_type,
        'adjustment_amount' => $adjustment_amount
    ];
    
    if (isset($warning_message)) {
        $response['warning'] = $warning_message;
    }
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to adjust quantity: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>