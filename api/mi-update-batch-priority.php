<?php
/**
 * Update batch priority order (for manual mode)
 * Receives an array of batch IDs in their new priority order
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get POST data (expecting JSON)
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    $item_id = $data['item_id'] ?? null;
    $batch_order = $data['batch_order'] ?? null; // Array of batch IDs in order

    // Validation
    if (empty($item_id)) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }

    if (!is_array($batch_order) || empty($batch_order)) {
        echo json_encode(['success' => false, 'message' => 'Batch order array is required']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update batch_order for each batch
        $update_query = "UPDATE inventory_batches SET batch_order = ? WHERE batch_id = ? AND inventory_id = ?";
        $update_stmt = $conn->prepare($update_query);

        foreach ($batch_order as $index => $batch_id) {
            $order = $index + 1; // Start from 1
            $update_stmt->bind_param("iss", $order, $batch_id, $item_id);
            $update_stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Batch priority order updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>