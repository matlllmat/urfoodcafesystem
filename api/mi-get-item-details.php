<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get item_id from query parameter
$item_id = isset($_GET['item_id']) ? trim($_GET['item_id']) : '';

if (empty($item_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

// Query to get item details with calculated stats
$query = "
    SELECT 
        ii.item_id,
        ii.item_name,
        ii.image_filename,
        ii.quantity_unit,
        ii.reorder_level,
        ii.updated_at,
        COALESCE(batch_summary.total_quantity, 0) as total_quantity,
        COALESCE(batch_summary.total_value, 0) as total_value,
        COALESCE(batch_summary.nearest_expiration, NULL) as nearest_expiration,
        GROUP_CONCAT(DISTINCT ic.category_name ORDER BY itc.is_primary DESC SEPARATOR ', ') as all_categories,
        CASE 
            WHEN COALESCE(batch_summary.total_quantity, 0) = 0 THEN 'Out of Stock'
            WHEN COALESCE(batch_summary.total_quantity, 0) <= ii.reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status,
        CASE
            WHEN batch_summary.nearest_expiration IS NOT NULL 
                AND batch_summary.nearest_expiration <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
            THEN 1
            ELSE 0
        END as has_expiring_soon
    FROM inventory_items ii
    LEFT JOIN (
        SELECT 
            inventory_id,
            SUM(current_quantity) as total_quantity,
            SUM((current_quantity / initial_quantity) * total_cost) as total_value,
            MIN(expiration_date) as nearest_expiration
        FROM inventory_batches
        GROUP BY inventory_id
    ) batch_summary ON ii.item_id = batch_summary.inventory_id
    LEFT JOIN item_categories itc ON ii.item_id = itc.item_id
    LEFT JOIN inventory_categories ic ON itc.category_id = ic.category_id
    WHERE ii.item_id = ?
    GROUP BY ii.item_id, ii.item_name, ii.image_filename, ii.quantity_unit, ii.reorder_level, ii.updated_at,
             batch_summary.total_quantity, batch_summary.total_value, batch_summary.nearest_expiration
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('s', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found'
    ]);
}

$stmt->close();
$conn->close();
?>