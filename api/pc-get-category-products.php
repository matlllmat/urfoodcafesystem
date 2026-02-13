<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$category_id = isset($_GET['category_id']) ? trim($_GET['category_id']) : '';

if (empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

// Products IN this category
$in_query = "
    SELECT p.product_id, p.product_name, p.price, p.status, p.image_filename
    FROM products p
    JOIN product_category_map pcm ON p.product_id = pcm.product_id
    WHERE pcm.category_id = ?
    ORDER BY p.product_name ASC
";
$in_stmt = $conn->prepare($in_query);
if (!$in_stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}
$in_stmt->bind_param("s", $category_id);
$in_stmt->execute();
$in_result = $in_stmt->get_result();

$products = [];
while ($row = $in_result->fetch_assoc()) {
    $products[] = $row;
}
$in_stmt->close();

// Products NOT in this category (available to add)
$out_query = "
    SELECT p.product_id, p.product_name, p.price, p.status
    FROM products p
    WHERE p.product_id NOT IN (
        SELECT pcm.product_id FROM product_category_map pcm WHERE pcm.category_id = ?
    )
    ORDER BY p.product_name ASC
";
$out_stmt = $conn->prepare($out_query);
if (!$out_stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}
$out_stmt->bind_param("s", $category_id);
$out_stmt->execute();
$out_result = $out_stmt->get_result();

$available = [];
while ($row = $out_result->fetch_assoc()) {
    $available[] = $row;
}
$out_stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products,
    'available' => $available
]);

$conn->close();
?>
