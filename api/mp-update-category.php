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
$category_name = trim($data['category_name'] ?? '');
$description = trim($data['description'] ?? '');
$is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;

if (empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

// Check for duplicate name (excluding current category)
$check_stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE category_name = ? AND category_id != ?");
$check_stmt->bind_param('ss', $category_name, $category_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit;
}

$is_active_int = $is_active ? 1 : 0;
$stmt = $conn->prepare("
    UPDATE product_categories
    SET category_name = ?, description = ?, is_active = ?
    WHERE category_id = ?
");
$stmt->bind_param('ssis', $category_name, $description, $is_active_int, $category_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update category']);
}

$conn->close();
?>
