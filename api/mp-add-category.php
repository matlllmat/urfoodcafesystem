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
$category_name = trim($data['category_name'] ?? '');
$description = trim($data['description'] ?? '');

if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

// Check for duplicate name
$check_stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE category_name = ?");
$check_stmt->bind_param('s', $category_name);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit;
}

// Generate category ID (PCAT-XXX)
$category_id = 'PCAT-001';
$result = $conn->query("SELECT category_id FROM product_categories ORDER BY category_id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $last_id = $result->fetch_assoc()['category_id'];
    if (preg_match('/PCAT-(\d+)/', $last_id, $matches)) {
        $next_num = intval($matches[1]) + 1;
        $category_id = 'PCAT-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
    }
}

// Get next display order
$order_result = $conn->query("SELECT MAX(display_order) as max_order FROM product_categories");
$display_order = 1;
if ($order_result && $row = $order_result->fetch_assoc()) {
    $display_order = intval($row['max_order']) + 1;
}

$stmt = $conn->prepare("
    INSERT INTO product_categories (category_id, category_name, description, display_order)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param('sssi', $category_id, $category_name, $description, $display_order);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Category created successfully',
        'category_id' => $category_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create category']);
}

$conn->close();
?>
