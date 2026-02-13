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
    SELECT pcm.category_id, pcm.is_primary, pc.category_name
    FROM product_category_map pcm
    JOIN product_categories pc ON pcm.category_id = pc.category_id
    WHERE pcm.product_id = ?
    ORDER BY pcm.is_primary DESC, pc.display_order ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $product_id);
$stmt->execute();
$result = $stmt->get_result();

$categories = [];
$primary_category = '';
while ($row = $result->fetch_assoc()) {
    $row['is_primary'] = (bool)$row['is_primary'];
    if ($row['is_primary']) {
        $primary_category = $row['category_id'];
    }
    $categories[] = $row;
}

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'primary_category' => $primary_category
]);

$stmt->close();
$conn->close();
?>
