<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Query to get all active categories
$query = "
    SELECT category_id, category_name, display_order
    FROM inventory_categories
    WHERE is_active = TRUE
    ORDER BY display_order ASC, category_name ASC
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode([
    'success' => true,
    'categories' => $categories
]);

$conn->close();
?>