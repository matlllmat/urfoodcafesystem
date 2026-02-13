<?php
/**
 * Update inventory item priority method
 * Updates how batches should be prioritized for an item (FIFO, FEFO, or Manual)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to output, only log them

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get POST data
    $item_id = $_POST['item_id'] ?? null;
    $priority_method = $_POST['priority_method'] ?? null;

    // Validation
    if (empty($item_id)) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }

    if (!in_array($priority_method, ['fifo', 'fefo', 'manual'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid priority method. Must be fifo, fefo, or manual']);
        exit;
    }

    // Check if item exists
    $check_query = "SELECT item_id FROM inventory_items WHERE item_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $item_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    // Update priority method
    $update_query = "UPDATE inventory_items SET priority_method = ? WHERE item_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ss", $priority_method, $item_id);

    if ($update_stmt->execute()) {
        // If switching to FIFO or FEFO, automatically recalculate batch_order
        if ($priority_method === 'fifo') {
            // Order by obtained_date (oldest first)
            $reorder_query = "
                UPDATE inventory_batches ib
                INNER JOIN (
                    SELECT
                        batch_id,
                        ROW_NUMBER() OVER (ORDER BY obtained_date ASC, created_at ASC) as new_order
                    FROM inventory_batches
                    WHERE inventory_id = ?
                ) ranked ON ib.batch_id = ranked.batch_id
                SET ib.batch_order = ranked.new_order
            ";
            $reorder_stmt = $conn->prepare($reorder_query);
            $reorder_stmt->bind_param("s", $item_id);
            $reorder_stmt->execute();
        } elseif ($priority_method === 'fefo') {
            // Order by expiration_date (nearest expiration first), NULL dates go last
            $reorder_query = "
                UPDATE inventory_batches ib
                INNER JOIN (
                    SELECT
                        batch_id,
                        ROW_NUMBER() OVER (
                            ORDER BY
                                CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END,
                                expiration_date ASC,
                                obtained_date ASC
                        ) as new_order
                    FROM inventory_batches
                    WHERE inventory_id = ?
                ) ranked ON ib.batch_id = ranked.batch_id
                SET ib.batch_order = ranked.new_order
            ";
            $reorder_stmt = $conn->prepare($reorder_query);
            $reorder_stmt->bind_param("s", $item_id);
            $reorder_stmt->execute();
        }
        // For manual, keep existing batch_order

        echo json_encode([
            'success' => true,
            'message' => 'Priority method updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update priority method: ' . $conn->error
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>