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
$inventory_id = $_POST['inventory_id'] ?? '';
$batch_title = trim($_POST['batch_title'] ?? '');
$initial_quantity = $_POST['initial_quantity'] ?? '';
$total_cost = $_POST['total_cost'] ?? '';
$obtained_date = $_POST['obtained_date'] ?? '';
$expiration_date = $_POST['expiration_date'] ?? null;

// Validation
if (empty($inventory_id) || empty($batch_title) || empty($initial_quantity) || empty($total_cost) || empty($obtained_date)) {
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

// Verify inventory item exists
$check_item = $conn->prepare("SELECT item_id FROM inventory_items WHERE item_id = ?");
$check_item->bind_param("s", $inventory_id);
$check_item->execute();
if ($check_item->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Inventory item not found']);
    exit;
}

// Generate batch ID
$batch_id = null;
$max_attempts = 10;
for ($i = 0; $i < $max_attempts; $i++) {
    // Get the latest batch ID
    $result = $conn->query("SELECT batch_id FROM inventory_batches ORDER BY batch_id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['batch_id'];
        
        // Extract number from last ID (e.g., BATCH001 -> 001)
        if (preg_match('/BATCH(\d+)/', $last_id, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $batch_id = 'BATCH' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        } else {
            $batch_id = 'BATCH001';
        }
    } else {
        $batch_id = 'BATCH001';
    }
    
    // Check if this ID already exists
    $check_stmt = $conn->prepare("SELECT batch_id FROM inventory_batches WHERE batch_id = ?");
    $check_stmt->bind_param("s", $batch_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        break; // ID is unique, we can use it
    }
    
    // If we're on the last attempt and still no unique ID
    if ($i === $max_attempts - 1) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate unique batch ID']);
        exit;
    }
}

// Get the highest batch_order for this inventory item
$order_stmt = $conn->prepare("SELECT MAX(batch_order) as max_order FROM inventory_batches WHERE inventory_id = ?");
$order_stmt->bind_param("s", $inventory_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$batch_order = 0;
if ($order_result && $order_result->num_rows > 0) {
    $order_row = $order_result->fetch_assoc();
    $batch_order = ($order_row['max_order'] ?? 0) + 1;
}
$order_stmt->close();

// Insert new batch
$stmt = $conn->prepare("
    INSERT INTO inventory_batches 
    (batch_id, inventory_id, batch_title, initial_quantity, current_quantity, total_cost, obtained_date, expiration_date, batch_order) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$current_quantity = $initial_quantity; // Initially, current = initial

$stmt->bind_param(
    "sssddsssi",
    $batch_id,
    $inventory_id,
    $batch_title,
    $initial_quantity,
    $current_quantity,
    $total_cost,
    $obtained_date,
    $expiration_date,
    $batch_order
);

if ($stmt->execute()) {
    // Log restock movement
    try {
        $movement_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $mv_quantity = floatval($initial_quantity);
        $mv_old_qty = 0;
        $mv_new_qty = floatval($initial_quantity);
        $mv_unit_cost = floatval($initial_quantity) > 0 ? (floatval($total_cost) / floatval($initial_quantity)) : 0;
        $mv_total_value = floatval($total_cost);
        $mv_staff_id = $_SESSION['user_id'];
        $mv_reason = 'New batch added: ' . $batch_title;

        $mv_stmt = $conn->prepare("
            INSERT INTO inventory_movements (
                movement_id, inventory_id, batch_id, movement_type, quantity,
                old_quantity, new_quantity, unit_cost, total_value,
                reference_type, staff_id, movement_date, reason
            ) VALUES (?, ?, ?, 'restock', ?, ?, ?, ?, ?, 'manual', ?, NOW(), ?)
        ");
        $mv_stmt->bind_param(
            'sssdddddss',
            $movement_id,
            $inventory_id,
            $batch_id,
            $mv_quantity,
            $mv_old_qty,
            $mv_new_qty,
            $mv_unit_cost,
            $mv_total_value,
            $mv_staff_id,
            $mv_reason
        );
        $mv_stmt->execute();
        $mv_stmt->close();
    } catch (Exception $e) {
        // Movement logging is non-critical
    }

    echo json_encode([
        'success' => true,
        'message' => 'Batch added successfully',
        'batch_id' => $batch_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add batch: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>