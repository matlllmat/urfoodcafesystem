<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get active product categories that have at least one available product
$query = "
    SELECT
        pc.category_id,
        pc.category_name,
        COUNT(DISTINCT p.product_id) as product_count
    FROM product_categories pc
    JOIN product_category_map pcm ON pc.category_id = pcm.category_id
    JOIN products p ON pcm.product_id = p.product_id AND p.status = 'Available'
    WHERE pc.is_active = 1
    GROUP BY pc.category_id, pc.category_name
    ORDER BY pc.display_order ASC, pc.category_name ASC
";

$result = $conn->query($query);
$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['product_count'] = intval($row['product_count']);
        $categories[] = $row;
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
$conn->close();
?>
