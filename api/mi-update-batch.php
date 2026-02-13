<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$batch_id = $_POST['batch_id'] ?? '';
$batch_title = trim($_POST['batch_title'] ?? '');
$initial_quantity = $_POST['initial_quantity'] ?? '';
$total_cost = $_POST['total_cost'] ?? '';
$obtained_date = $_POST['obtained_date'] ?? '';
$expiration_date = $_POST['expiration_date'] ?? null;

// Validation
if (empty($batch_id) || empty($batch_title) || empty($initial_quantity) || empty($total_cost) || empty($obtained_date)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (!is_numeric($initial_quantity) || floatval($initial_quantity) <= 0) {
    echo json_encode(['success' => false, 'message' => 'Initial quantity must be a positive number']);
    exit;
}

if (!is_numeric($total_cost) || floatval($total_cost) < 0) {
    echo json_encode(['success' => false, 'message' => 'Total cost must be a non-negative number']);
    exit;
}

// Validate dates
$obtained_date_obj = DateTime::createFromFormat('Y-m-d', $obtained_date);
if (!$obtained_date_obj) {
    echo json_encode(['success' => false, 'message' => 'Invalid obtained date format']);
    exit;
}

if (!empty($expiration_date)) {
    $expiration_date_obj = DateTime::createFromFormat('Y-m-d', $expiration_date);
    if (!$expiration_date_obj) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration date format']);
        exit;
    }
    
    if ($expiration_date_obj <= $obtained_date_obj) {
        echo json_encode(['success' => false, 'message' => 'Expiration date must be after obtained date']);
        exit;
    }
} else {
    $expiration_date = null;
}

// Verify batch exists and get current quantities
$check_batch = $conn->prepare("SELECT initial_quantity, current_quantity FROM inventory_batches WHERE batch_id = ?");
$check_batch->bind_param("s", $batch_id);
$check_batch->execute();
$batch_result = $check_batch->get_result();

if ($batch_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Batch not found']);
    exit;
}

$current_batch = $batch_result->fetch_assoc();
$old_initial_quantity = $current_batch['initial_quantity'];
$current_quantity = $current_batch['current_quantity'];

// If initial quantity changed, adjust current quantity proportionally
$new_current_quantity = $current_quantity;
if ($old_initial_quantity != $initial_quantity && $old_initial_quantity > 0) {
    // Calculate the proportion used
    $proportion_remaining = $current_quantity / $old_initial_quantity;
    $new_current_quantity = $initial_quantity * $proportion_remaining;
}

// Update batch
$stmt = $conn->prepare("
    UPDATE inventory_batches 
    SET batch_title = ?,
        initial_quantity = ?,
        current_quantity = ?,
        total_cost = ?,
        obtained_date = ?,
        expiration_date = ?
    WHERE batch_id = ?
");

$stmt->bind_param(
    "sddssss",
    $batch_title,
    $initial_quantity,
    $new_current_quantity,
    $total_cost,
    $obtained_date,
    $expiration_date,
    $batch_id
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Batch updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update batch: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>