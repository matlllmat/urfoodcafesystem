<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get item_id from query parameter
$item_id = isset($_GET['item_id']) ? trim($_GET['item_id']) : '';

if (empty($item_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

// Query to get item's categories
$query = "
    SELECT ic.category_id, ic.is_primary
    FROM item_categories ic
    WHERE ic.item_id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('s', $item_id);
$stmt->execute();
$result = $stmt->get_result();

$categories = [];
$primary_category = null;

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category_id'];
    if ($row['is_primary']) {
        $primary_category = $row['category_id'];
    }
}

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'primary_category' => $primary_category
]);

$stmt->close();
$conn->close();
?>