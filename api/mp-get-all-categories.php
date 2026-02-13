<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "
    SELECT
        pc.category_id,
        pc.category_name,
        pc.description,
        pc.is_active,
        pc.display_order,
        pc.created_at,
        pc.updated_at,
        COUNT(pcm.product_id) as product_count
    FROM product_categories pc
    LEFT JOIN product_category_map pcm ON pc.category_id = pcm.category_id
    GROUP BY pc.category_id
    ORDER BY pc.display_order ASC, pc.category_name ASC
";

$result = $conn->query($query);
$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['is_active'] = (bool)$row['is_active'];
        $categories[] = $row;
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
$conn->close();
?>
