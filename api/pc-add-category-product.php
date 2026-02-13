<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$category_id = isset($input['category_id']) ? trim($input['category_id']) : '';
$product_id = isset($input['product_id']) ? trim($input['product_id']) : '';

if (empty($category_id) || empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Category ID and Product ID are required']);
    exit;
}

// Check if mapping already exists
$check_stmt = $conn->prepare("SELECT 1 FROM product_category_map WHERE product_id = ? AND category_id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}
$check_stmt->bind_param("ss", $product_id, $category_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    $check_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Product is already in this category']);
    exit;
}
$check_stmt->close();

// Insert mapping
$stmt = $conn->prepare("INSERT INTO product_category_map (product_id, category_id, is_primary) VALUES (?, ?, FALSE)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ss", $product_id, $category_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product added to category']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
