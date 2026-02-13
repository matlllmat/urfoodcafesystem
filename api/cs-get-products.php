<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get available products with cost, stock status, and category info
$query = "
    SELECT
        p.product_id,
        p.product_name,
        p.image_filename,
        p.price,
        COALESCE(cost_calc.total_cost, 0) as calculated_cost,
        COALESCE(stock_check.out_of_stock_count, 0) as out_of_stock_count,
        COALESCE(req_count.requirement_count, 0) as requirement_count,
        primary_cat.category_id as primary_category_id,
        primary_cat.category_name as primary_category_name,
        COALESCE(all_cats.category_list, '') as all_categories
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
        SELECT product_id, COUNT(*) as requirement_count
        FROM product_requirements
        GROUP BY product_id
    ) req_count ON p.product_id = req_count.product_id
    LEFT JOIN (
        SELECT
            pr.product_id,
            COUNT(CASE WHEN COALESCE(stock.available_qty, 0) < pr.quantity_used THEN 1 END) as out_of_stock_count
        FROM product_requirements pr
        LEFT JOIN (
            SELECT inventory_id, SUM(current_quantity) as available_qty
            FROM inventory_batches
            WHERE expiration_date IS NULL OR expiration_date > CURDATE()
            GROUP BY inventory_id
        ) stock ON pr.inventory_id = stock.inventory_id
        GROUP BY pr.product_id
    ) stock_check ON p.product_id = stock_check.product_id
    LEFT JOIN (
        SELECT pcm.product_id, pc.category_id, pc.category_name
        FROM product_category_map pcm
        JOIN product_categories pc ON pcm.category_id = pc.category_id
        WHERE pcm.is_primary = 1
    ) primary_cat ON p.product_id = primary_cat.product_id
    LEFT JOIN (
        SELECT pcm.product_id, GROUP_CONCAT(pc.category_id) as category_list
        FROM product_category_map pcm
        JOIN product_categories pc ON pcm.category_id = pc.category_id
        GROUP BY pcm.product_id
    ) all_cats ON p.product_id = all_cats.product_id
    WHERE p.status = 'Available'
    ORDER BY p.product_name ASC
";

$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['price'] = floatval($row['price']);
        $row['calculated_cost'] = floatval($row['calculated_cost']);
        $row['out_of_stock_count'] = intval($row['out_of_stock_count']);
        $row['requirement_count'] = intval($row['requirement_count']);
        $row['in_stock'] = ($row['out_of_stock_count'] === 0);
        $products[] = $row;
    }
}

echo json_encode(['success' => true, 'products' => $products]);
$conn->close();
?>
