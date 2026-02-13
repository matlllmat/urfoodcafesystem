<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$product_id = isset($_GET['product_id']) ? trim($_GET['product_id']) : '';

if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$query = "
    SELECT
        pr.product_id,
        pr.inventory_id,
        pr.quantity_used,
        ii.item_name,
        ii.quantity_unit,
        COALESCE(batch_avg.avg_cost_per_unit, 0) as unit_cost,
        ROUND(pr.quantity_used * COALESCE(batch_avg.avg_cost_per_unit, 0), 2) as line_total,
        COALESCE(stock.available_qty, 0) as available_stock
    FROM product_requirements pr
    JOIN inventory_items ii ON pr.inventory_id = ii.item_id
    LEFT JOIN (
        SELECT
            inventory_id,
            CASE
                WHEN SUM(current_quantity) > 0
                THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                ELSE 0
            END as avg_cost_per_unit
        FROM inventory_batches
        WHERE expiration_date IS NULL OR expiration_date > CURDATE()
        GROUP BY inventory_id
    ) batch_avg ON pr.inventory_id = batch_avg.inventory_id
    LEFT JOIN (
        SELECT inventory_id, SUM(current_quantity) as available_qty
        FROM inventory_batches
        WHERE expiration_date IS NULL OR expiration_date > CURDATE()
        GROUP BY inventory_id
    ) stock ON pr.inventory_id = stock.inventory_id
    WHERE pr.product_id = ?
    ORDER BY ii.item_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $product_id);
$stmt->execute();
$result = $stmt->get_result();

$requirements = [];
$total_cost = 0;
while ($row = $result->fetch_assoc()) {
    $requirements[] = $row;
    $total_cost += floatval($row['line_total']);
}

echo json_encode([
    'success' => true,
    'requirements' => $requirements,
    'total_cost' => round($total_cost, 2)
]);

$stmt->close();
$conn->close();
?>
