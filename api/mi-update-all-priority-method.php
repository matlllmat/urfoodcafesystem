<?php
/**
 * Update priority method for ALL inventory items (bulk action)
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
    // Get POST data
    $priority_method = $_POST['priority_method'] ?? null;

    // Validation
    if (!in_array($priority_method, ['fifo', 'fefo', 'manual'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid priority method. Must be fifo, fefo, or manual']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update all items
        $update_query = "UPDATE inventory_items SET priority_method = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("s", $priority_method);
        $update_stmt->execute();

        $affected_items = $update_stmt->affected_rows;

        // Recalculate batch orders for all items
        if ($priority_method === 'fifo') {
            // Order by obtained_date (oldest first) for each item
            $reorder_query = "
                UPDATE inventory_batches ib
                INNER JOIN (
                    SELECT
                        batch_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY inventory_id
                            ORDER BY obtained_date ASC, created_at ASC
                        ) as new_order
                    FROM inventory_batches
                ) ranked ON ib.batch_id = ranked.batch_id
                SET ib.batch_order = ranked.new_order
            ";
            $conn->query($reorder_query);
        } elseif ($priority_method === 'fefo') {
            // Order by expiration_date (nearest expiration first) for each item
            $reorder_query = "
                UPDATE inventory_batches ib
                INNER JOIN (
                    SELECT
                        batch_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY inventory_id
                            ORDER BY
                                CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END,
                                expiration_date ASC,
                                obtained_date ASC
                        ) as new_order
                    FROM inventory_batches
                ) ranked ON ib.batch_id = ranked.batch_id
                SET ib.batch_order = ranked.new_order
            ";
            $conn->query($reorder_query);
        }
        // For manual, keep existing batch_order

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "Priority method updated for all $affected_items items"
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