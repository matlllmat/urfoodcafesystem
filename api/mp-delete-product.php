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

$product_id = trim($_POST['product_id'] ?? '');

if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

// Get image filename before deletion
$img_stmt = $conn->prepare("SELECT image_filename FROM products WHERE product_id = ?");
$img_stmt->bind_param('s', $product_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

if ($img_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$image_filename = $img_result->fetch_assoc()['image_filename'];

// Delete product (product_requirements cascade-deleted via FK)
$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param('s', $product_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Delete product image if not default
    if ($image_filename && $image_filename !== 'default-product.png') {
        $path = __DIR__ . '/../assets/images/product/' . $image_filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}

$stmt->close();
$conn->close();
?>
