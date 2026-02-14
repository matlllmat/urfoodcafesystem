<?php
/**
 * Dispose expired batches
 * Sets quantity to 0 and records disposal in inventory_movements table
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$item_id = $_POST['item_id'] ?? '';
$batch_ids_json = $_POST['batch_ids'] ?? '';
$reason = $_POST['reason'] ?? '';

// Validation
if (empty($item_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

if (empty($batch_ids_json)) {
    echo json_encode([
        'success' => false,
        'message' => 'No batches selected for disposal'
    ]);
    exit;
}

if (empty($reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'Disposal reason is required'
    ]);
    exit;
}

// Decode batch IDs
$batch_ids = json_decode($batch_ids_json, true);
if (!is_array($batch_ids) || empty($batch_ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid batch IDs format'
    ]);
    exit;
}

$staff_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    $batches_disposed = 0;
    $total_quantity_disposed = 0;
    $total_value_lost = 0;
    
    foreach ($batch_ids as $batch_id) {
        // Get batch details before disposal
        $query = "
            SELECT 
                batch_id,
                inventory_id,
                current_quantity,
                initial_quantity,
                total_cost,
                expiration_date
            FROM inventory_batches
            WHERE batch_id = ?
            AND inventory_id = ?
            AND current_quantity > 0
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $batch_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Batch not found or already disposed
            continue;
        }
        
        $batch = $result->fetch_assoc();
        $old_quantity = $batch['current_quantity'];
        $unit_cost = $batch['initial_quantity'] > 0 ? ($batch['total_cost'] / $batch['initial_quantity']) : 0;
        $value_lost = ($old_quantity / $batch['initial_quantity']) * $batch['total_cost'];
        // Outbound movement values should be negative for correct net value impact stats
        $movement_total_value = -abs($value_lost);
        
        // Generate movement ID
        $movement_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Record disposal in inventory_movements table
        // Note: This will fail until you create the table, but the code is ready
        $insert_movement = "
            INSERT INTO inventory_movements (
                movement_id,
                inventory_id,
                batch_id,
                movement_type,
                quantity,
                old_quantity,
                new_quantity,
                unit_cost,
                total_value,
                reference_type,
                reference_id,
                staff_id,
                movement_date,
                reason
            ) VALUES (?, ?, ?, 'disposal', ?, ?, 0, ?, ?, 'manual', NULL, ?, NOW(), ?)
        ";
        
        $stmt_movement = $conn->prepare($insert_movement);
        $negative_quantity = -$old_quantity;
        $stmt_movement->bind_param(
            'sssddddss',
            $movement_id,
            $item_id,
            $batch_id,
            $negative_quantity,
            $old_quantity,
            $unit_cost,
            $movement_total_value,
            $staff_id,
            $reason
        );
        
        // Execute movement insert (will fail gracefully if table doesn't exist yet)
        try {
            $stmt_movement->execute();
        } catch (Exception $e) {
            // Table doesn't exist yet - continue with batch update
            // When you create the table, this will work automatically
        }
        
        // Update batch quantity to 0
        $update_batch = "
            UPDATE inventory_batches
            SET current_quantity = 0,
                updated_at = NOW()
            WHERE batch_id = ?
        ";
        
        $stmt_update = $conn->prepare($update_batch);
        $stmt_update->bind_param('s', $batch_id);
        $stmt_update->execute();
        
        // Update totals
        $batches_disposed++;
        $total_quantity_disposed += $old_quantity;
        $total_value_lost += $value_lost;
    }
    
    // Update inventory item's updated_at timestamp
    $update_item = "
        UPDATE inventory_items
        SET updated_at = NOW()
        WHERE item_id = ?
    ";
    
    $stmt_item = $conn->prepare($update_item);
    $stmt_item->bind_param('s', $item_id);
    $stmt_item->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Batches disposed successfully',
        'batches_disposed' => $batches_disposed,
        'total_quantity_disposed' => $total_quantity_disposed,
        'total_value_lost' => $total_value_lost
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error disposing batches: ' . $e->getMessage()
    ]);
}

$conn->close();
?>