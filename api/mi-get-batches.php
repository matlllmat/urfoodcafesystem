<?php
/**
 * API Endpoint: Get Batches
 * Fetches all batches for a specific inventory item
 */

header('Content-Type: application/json');

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit();
}

// Get item_id from query parameter
$item_id = isset($_GET['item_id']) ? trim($_GET['item_id']) : '';

if (empty($item_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit();
}

// Connect to database
require_once __DIR__ . '/../config/db.php';

// First, get the item's priority method and check if it has expiration dates
$item_query = "
    SELECT
        ii.priority_method,
        COUNT(CASE WHEN ib.expiration_date IS NOT NULL THEN 1 END) as batches_with_expiry
    FROM inventory_items ii
    LEFT JOIN inventory_batches ib ON ii.item_id = ib.inventory_id
    WHERE ii.item_id = ?
    GROUP BY ii.item_id, ii.priority_method
";

$item_stmt = $conn->prepare($item_query);
$item_stmt->bind_param("s", $item_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$item_data = $item_result ? $item_result->fetch_assoc() : ['priority_method' => 'fifo', 'batches_with_expiry' => 0];
$priority_method = $item_data['priority_method'] ?? 'fifo';
$has_expiry_dates = ($item_data['batches_with_expiry'] ?? 0) > 0;
$item_stmt->close();

// Query to fetch batches for the item, ordered by batch_order
$query = "
    SELECT
        batch_id,
        batch_title,
        initial_quantity,
        current_quantity,
        total_cost,
        supplier_id,
        obtained_date,
        expiration_date,
        batch_order,
        created_at
    FROM inventory_batches
    WHERE inventory_id = ?
    ORDER BY batch_order ASC, obtained_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch batches'
    ]);
    exit();
}

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

// Return success response
echo json_encode([
    'success' => true,
    'batches' => $batches,
    'count' => count($batches),
    'priority_method' => $priority_method,
    'has_expiry_dates' => $has_expiry_dates
]);

$conn->close();
?>