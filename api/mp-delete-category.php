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

$data = json_decode(file_get_contents('php://input'), true);
$category_id = trim($data['category_id'] ?? '');

if (empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

// Delete category. product_category_map has ON DELETE CASCADE, so assigned products are unlinked automatically.
$stmt = $conn->prepare("DELETE FROM product_categories WHERE category_id = ?");
$stmt->bind_param('s', $category_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
}

$conn->close();
?>
