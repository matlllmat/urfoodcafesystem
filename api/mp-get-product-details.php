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
        p.product_id,
        p.product_name,
        p.image_filename,
        p.price,
        p.status,
        p.created_at,
        p.updated_at,
        COALESCE(cost_calc.total_cost, 0) as calculated_cost,
        COALESCE(stock_check.out_of_stock_count, 0) as out_of_stock_count
    FROM products p
    LEFT JOIN (
        SELECT
            pr.product_id,
            SUM(pr.quantity_used * COALESCE(batch_avg.avg_cost_per_unit, 0)) as total_cost
        FROM product_requirements pr
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
        GROUP BY pr.product_id
    ) cost_calc ON p.product_id = cost_calc.product_id
    LEFT JOIN (
        SELECT
            pr.product_id,
            COUNT(CASE WHEN COALESCE(stock.available_qty, 0) = 0 THEN 1 END) as out_of_stock_count
        FROM product_requirements pr
        LEFT JOIN (
            SELECT inventory_id, SUM(current_quantity) as available_qty
            FROM inventory_batches
            WHERE expiration_date IS NULL OR expiration_date > CURDATE()
            GROUP BY inventory_id
        ) stock ON pr.inventory_id = stock.inventory_id
        GROUP BY pr.product_id
    ) stock_check ON p.product_id = stock_check.product_id
    WHERE p.product_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $product['profit'] = round($product['price'] - $product['calculated_cost'], 2);
    $product['profit_margin'] = $product['price'] > 0
        ? round((($product['price'] - $product['calculated_cost']) / $product['price']) * 100, 2)
        : 0;

    echo json_encode(['success' => true, 'product' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}

$stmt->close();
$conn->close();
?>
