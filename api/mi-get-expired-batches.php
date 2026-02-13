<?php
/**
 * Get expired batches for an inventory item
 * Returns only batches that have passed their expiration date and still have quantity > 0
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if item_id is provided
if (!isset($_GET['item_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

$item_id = $_GET['item_id'];

try {
    // Get all expired batches for this item
    $query = "
        SELECT 
            batch_id,
            inventory_id,
            batch_title,
            initial_quantity,
            current_quantity,
            total_cost,
            obtained_date,
            expiration_date,
            supplier_id
        FROM inventory_batches
        WHERE inventory_id = ?
        AND expiration_date IS NOT NULL
        AND expiration_date <= CURDATE()
        AND current_quantity > 0
        ORDER BY expiration_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'batches' => $batches,
        'count' => count($batches)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>