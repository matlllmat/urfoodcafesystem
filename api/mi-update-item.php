<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$item_id = isset($_POST['item_id']) ? trim($_POST['item_id']) : '';
$item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
$quantity_unit = isset($_POST['quantity_unit']) ? trim($_POST['quantity_unit']) : '';
$reorder_level = isset($_POST['reorder_level']) ? trim($_POST['reorder_level']) : '';
$categories_json = isset($_POST['categories']) ? $_POST['categories'] : '[]';
$primary_category = isset($_POST['primary_category']) ? trim($_POST['primary_category']) : '';

// Decode categories
$categories = json_decode($categories_json, true);
if (!is_array($categories)) {
    $categories = [];
}

// Validation
if (empty($item_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

if (empty($item_name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Item name is required'
    ]);
    exit;
}

if (empty($quantity_unit)) {
    echo json_encode([
        'success' => false,
        'message' => 'Quantity unit is required'
    ]);
    exit;
}

if (!is_numeric($reorder_level) || $reorder_level < 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid reorder level is required'
    ]);
    exit;
}

// Validate primary category is in selected categories
if (!empty($primary_category) && !in_array($primary_category, $categories)) {
    echo json_encode([
        'success' => false,
        'message' => 'Primary category must be one of the selected categories'
    ]);
    exit;
}

// Handle image upload if present
$new_image_filename = null;
$old_image_filename = null;

if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['item_image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.'
        ]);
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'message' => 'File size must be less than 5MB'
        ]);
        exit;
    }
    
    // Get old image filename to delete later
    $old_query = "SELECT image_filename FROM inventory_items WHERE item_id = ?";
    $old_stmt = $conn->prepare($old_query);
    $old_stmt->bind_param('s', $item_id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    if ($old_result->num_rows > 0) {
        $old_row = $old_result->fetch_assoc();
        $old_image_filename = $old_row['image_filename'];
    }
    $old_stmt->close();
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_image_filename = 'item_' . $item_id . '_' . time() . '.' . $extension;
    
    // Define upload directory
    $upload_dir = __DIR__ . '/../assets/images/inventory-item/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $upload_path = $upload_dir . $new_image_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload image'
        ]);
        exit;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Update item details
    if ($new_image_filename) {
        // Update with new image
        $update_query = "
            UPDATE inventory_items 
            SET item_name = ?, 
                quantity_unit = ?, 
                reorder_level = ?,
                image_filename = ?,
                updated_at = NOW()
            WHERE item_id = ?
        ";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssdss', $item_name, $quantity_unit, $reorder_level, $new_image_filename, $item_id);
    } else {
        // Update without changing image
        $update_query = "
            UPDATE inventory_items 
            SET item_name = ?, 
                quantity_unit = ?, 
                reorder_level = ?,
                updated_at = NOW()
            WHERE item_id = ?
        ";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssds', $item_name, $quantity_unit, $reorder_level, $item_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update item: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0 && $conn->affected_rows === 0) {
        // Check if item exists
        $check_query = "SELECT item_id FROM inventory_items WHERE item_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('s', $item_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows === 0) {
            throw new Exception('Item not found');
        }
        $check_stmt->close();
    }
    
    $stmt->close();
    
    // Update categories
    // First, delete all existing category associations
    $delete_cat_query = "DELETE FROM item_categories WHERE item_id = ?";
    $delete_stmt = $conn->prepare($delete_cat_query);
    $delete_stmt->bind_param('s', $item_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Then, insert new category associations
    if (!empty($categories)) {
        $insert_cat_query = "INSERT INTO item_categories (item_id, category_id, is_primary) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_cat_query);
        
        foreach ($categories as $category_id) {
            $is_primary = ($category_id === $primary_category) ? 1 : 0;
            $insert_stmt->bind_param('ssi', $item_id, $category_id, $is_primary);
            $insert_stmt->execute();
        }
        
        $insert_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Delete old image if new one was uploaded and old one exists
    if ($new_image_filename && $old_image_filename && $old_image_filename !== 'default-item.png') {
        $old_image_path = __DIR__ . '/../assets/images/inventory-item/' . $old_image_filename;
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Item updated successfully',
        'item_id' => $item_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Delete uploaded image if transaction failed
    if ($new_image_filename) {
        $failed_upload_path = __DIR__ . '/../assets/images/inventory-item/' . $new_image_filename;
        if (file_exists($failed_upload_path)) {
            unlink($failed_upload_path);
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>