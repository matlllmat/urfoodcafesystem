<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sale_id = isset($_GET['sale_id']) ? trim($_GET['sale_id']) : '';

if (empty($sale_id)) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

// Get sale info with cashier name
$sale_stmt = $conn->prepare("
    SELECT s.*, u.user_name as cashier_name
    FROM sales s
    JOIN users u ON s.staff_id = u.staff_id
    WHERE s.sale_id = ?
");
$sale_stmt->bind_param("s", $sale_id);
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();

if ($sale_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$sale = $sale_result->fetch_assoc();

// Get sale details with product info
$detail_stmt = $conn->prepare("
    SELECT sd.*, p.product_name, p.product_id as product_code
    FROM sale_details sd
    JOIN products p ON sd.product_id = p.product_id
    WHERE sd.sale_id = ?
    ORDER BY sd.sale_detail_id ASC
");
$detail_stmt->bind_param("s", $sale_id);
$detail_stmt->execute();
$detail_result = $detail_stmt->get_result();

$details = [];
while ($row = $detail_result->fetch_assoc()) {
    $details[] = $row;
}

$sale_stmt->close();
$detail_stmt->close();

// Get ingredients (product requirements) for each sale detail
$ing_stmt = $conn->prepare("
    SELECT pr.inventory_id, ii.item_name, ii.quantity_unit, pr.quantity_used
    FROM product_requirements pr
    JOIN inventory_items ii ON pr.inventory_id = ii.item_id
    WHERE pr.product_id = ?
    ORDER BY ii.item_name ASC
");

if ($ing_stmt) {
    foreach ($details as &$detail) {
        $detail['ingredients'] = [];
        $ing_stmt->bind_param("s", $detail['product_id']);
        $ing_stmt->execute();
        $ing_result = $ing_stmt->get_result();
        while ($ing = $ing_result->fetch_assoc()) {
            $detail['ingredients'][] = $ing;
        }
    }
    unset($detail);
    $ing_stmt->close();
}

echo json_encode([
    'success' => true,
    'sale' => $sale,
    'details' => $details
]);

$conn->close();
?>
