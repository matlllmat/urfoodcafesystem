<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$in_stock_only = isset($_GET['in_stock_only']) && $_GET['in_stock_only'] === '1';

$query = "
    SELECT
        ii.item_id,
        ii.item_name,
        ii.quantity_unit,
        COALESCE(batch_avg.avg_cost_per_unit, 0) as unit_cost,
        COALESCE(batch_avg.available_stock, 0) as available_stock
    FROM inventory_items ii
    LEFT JOIN (
        SELECT
            inventory_id,
            CASE
                WHEN SUM(current_quantity) > 0
                THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                ELSE 0
            END as avg_cost_per_unit,
            SUM(current_quantity) as available_stock
        FROM inventory_batches
        WHERE expiration_date IS NULL OR expiration_date > CURDATE()
        GROUP BY inventory_id
    ) batch_avg ON ii.item_id = batch_avg.inventory_id
" . ($in_stock_only ? "    WHERE COALESCE(batch_avg.available_stock, 0) > 0\n" : "") . "
    ORDER BY ii.item_name ASC
";

$result = $conn->query($query);
$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode(['success' => true, 'items' => $items]);
$conn->close();
?>
