<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$batch_id = $_GET['batch_id'] ?? '';

if (empty($batch_id)) {
    echo json_encode(['success' => false, 'message' => 'Batch ID is required']);
    exit;
}

// Fetch batch details
$stmt = $conn->prepare("
    SELECT 
        batch_id,
        inventory_id,
        batch_title,
        initial_quantity,
        current_quantity,
        total_cost,
        supplier_id,
        obtained_date,
        expiration_date,
        batch_order,
        created_at,
        updated_at
    FROM inventory_batches
    WHERE batch_id = ?
");

$stmt->bind_param("s", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $batch = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'batch' => $batch
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Batch not found'
    ]);
}

$stmt->close();
$conn->close();
?>