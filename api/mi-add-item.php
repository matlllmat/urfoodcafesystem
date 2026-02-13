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
$item_name = trim($_POST['item_name'] ?? '');
$quantity_unit = trim($_POST['quantity_unit'] ?? '');
$reorder_level = $_POST['reorder_level'] ?? '';
$categories_json = $_POST['categories'] ?? '';
$primary_category = $_POST['primary_category'] ?? '';
$add_initial_batch = ($_POST['add_initial_batch'] ?? '0') === '1';

// Validation
if (empty($item_name) || empty($quantity_unit) || $reorder_level === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (!is_numeric($reorder_level) || floatval($reorder_level) < 0) {
    echo json_encode(['success' => false, 'message' => 'Reorder level must be a non-negative number']);
    exit;
}

// Decode categories
$categories = json_decode($categories_json, true);
if (!is_array($categories) || empty($categories)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one category']);
    exit;
}

// Generate item ID
$item_id = null;
$max_attempts = 10;
for ($i = 0; $i < $max_attempts; $i++) {
    $result = $conn->query("SELECT item_id FROM inventory_items WHERE item_id LIKE 'ITEM%' ORDER BY item_id DESC LIMIT 1");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['item_id'];

        if (preg_match('/ITEM(\d+)/', $last_id, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $item_id = 'ITEM' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        } else {
            $item_id = 'ITEM001';
        }
    } else {
        $item_id = 'ITEM001';
    }
    
    $check_stmt = $conn->prepare("SELECT item_id FROM inventory_items WHERE item_id = ?");
    $check_stmt->bind_param("s", $item_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        break;
    }
    
    if ($i === $max_attempts - 1) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate unique item ID']);
        exit;
    }
}

// Handle image upload
$image_filename = 'default-item.png';
if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = $_FILES['item_image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, and WEBP are allowed']);
        exit;
    }
    
    if ($_FILES['item_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
        exit;
    }
    
    $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $image_filename = $item_id . '_' . time() . '.' . $file_extension;
    $upload_path = __DIR__ . '/../assets/images/inventory-item/' . $image_filename;
    
    if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

// Get default priority method from settings
$default_priority = 'fifo'; // fallback default
$settings_query = "SELECT setting_value FROM inventory_settings WHERE setting_key = 'default_priority_method'";
$settings_result = $conn->query($settings_query);
if ($settings_result && $settings_result->num_rows > 0) {
    $settings_row = $settings_result->fetch_assoc();
    $default_priority = $settings_row['setting_value'];
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert item with default priority
    $stmt = $conn->prepare("
        INSERT INTO inventory_items
        (item_id, item_name, image_filename, quantity_unit, reorder_level, priority_method)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssds", $item_id, $item_name, $image_filename, $quantity_unit, $reorder_level, $default_priority);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create item');
    }
    
    // Insert categories
    $cat_stmt = $conn->prepare("
        INSERT INTO item_categories (item_id, category_id, is_primary) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($categories as $category_id) {
        $is_primary = ($category_id === $primary_category) ? 1 : 0;
        $cat_stmt->bind_param("ssi", $item_id, $category_id, $is_primary);
        
        if (!$cat_stmt->execute()) {
            throw new Exception('Failed to assign categories');
        }
    }
    
    // Add initial batch if requested
    if ($add_initial_batch) {
        $batch_title = trim($_POST['batch_title'] ?? '');
        $batch_initial_quantity = $_POST['batch_initial_quantity'] ?? '';
        $batch_total_cost = $_POST['batch_total_cost'] ?? '';
        $batch_obtained_date = $_POST['batch_obtained_date'] ?? '';
        $batch_expiration_date = $_POST['batch_expiration_date'] ?? null;
        
        if (empty($batch_title) || empty($batch_initial_quantity) || empty($batch_total_cost) || empty($batch_obtained_date)) {
            throw new Exception('Incomplete batch information');
        }
        
        // Generate batch ID
        $batch_id = null;
        for ($i = 0; $i < $max_attempts; $i++) {
            $result = $conn->query("SELECT batch_id FROM inventory_batches ORDER BY batch_id DESC LIMIT 1");
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $last_id = $row['batch_id'];
                
                if (preg_match('/BATCH(\d+)/', $last_id, $matches)) {
                    $next_num = intval($matches[1]) + 1;
                    $batch_id = 'BATCH' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                } else {
                    $batch_id = 'BATCH001';
                }
            } else {
                $batch_id = 'BATCH001';
            }
            
            $check_batch = $conn->prepare("SELECT batch_id FROM inventory_batches WHERE batch_id = ?");
            $check_batch->bind_param("s", $batch_id);
            $check_batch->execute();
            
            if ($check_batch->get_result()->num_rows === 0) {
                break;
            }
        }
        
        $batch_stmt = $conn->prepare("
            INSERT INTO inventory_batches 
            (batch_id, inventory_id, batch_title, initial_quantity, current_quantity, total_cost, obtained_date, expiration_date, batch_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        
        $current_quantity = $batch_initial_quantity;
        
        $batch_stmt->bind_param(
            "sssdddss",
            $batch_id,
            $item_id,
            $batch_title,
            $batch_initial_quantity,
            $current_quantity,
            $batch_total_cost,
            $batch_obtained_date,
            $batch_expiration_date
        );
        
        if (!$batch_stmt->execute()) {
            throw new Exception('Failed to create initial batch');
        }

        // Log initial stock movement
        try {
            $movement_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $mv_quantity = floatval($batch_initial_quantity);
            $mv_old_qty = 0;
            $mv_new_qty = floatval($batch_initial_quantity);
            $mv_unit_cost = floatval($batch_initial_quantity) > 0 ? (floatval($batch_total_cost) / floatval($batch_initial_quantity)) : 0;
            $mv_total_value = floatval($batch_total_cost);
            $mv_staff_id = $_SESSION['user_id'];
            $mv_reason = 'Initial stock for new item: ' . $item_name;

            $mv_stmt = $conn->prepare("
                INSERT INTO inventory_movements (
                    movement_id, inventory_id, batch_id, movement_type, quantity,
                    old_quantity, new_quantity, unit_cost, total_value,
                    reference_type, staff_id, movement_date, reason
                ) VALUES (?, ?, ?, 'initial_stock', ?, ?, ?, ?, ?, 'manual', ?, NOW(), ?)
            ");
            $mv_stmt->bind_param(
                'sssdddddss',
                $movement_id,
                $item_id,
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
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item created successfully',
        'item_id' => $item_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Delete uploaded image if transaction failed
    if ($image_filename !== 'default-item.png') {
        $upload_path = __DIR__ . '/../assets/images/inventory-item/' . $image_filename;
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>