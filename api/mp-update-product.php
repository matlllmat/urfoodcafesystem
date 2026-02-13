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

$product_id = trim($_POST['product_id'] ?? '');
$product_name = trim($_POST['product_name'] ?? '');
$price = $_POST['price'] ?? '';
$status = $_POST['status'] ?? 'Available';
$requirements_json = $_POST['requirements'] ?? '[]';
$categories_json = $_POST['categories'] ?? '[]';
$primary_category = trim($_POST['primary_category'] ?? '');

// Validation
if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

if (empty($product_name)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a product name']);
    exit;
}

if (!is_numeric($price) || floatval($price) < 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid price']);
    exit;
}

$valid_statuses = ['Available', 'Unavailable', 'Discontinued'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Decode requirements
$requirements = json_decode($requirements_json, true);
if (!is_array($requirements)) {
    $requirements = [];
}

// Decode categories
$categories = json_decode($categories_json, true);
if (!is_array($categories)) {
    $categories = [];
}

// Check for duplicate inventory items
$inventory_ids = array_map(function($r) { return $r['inventory_id']; }, $requirements);
if (count($inventory_ids) !== count(array_unique($inventory_ids))) {
    echo json_encode(['success' => false, 'message' => 'Duplicate ingredients detected. Each ingredient can only be added once.']);
    exit;
}

// Get current image filename
$current_image = 'default-product.png';
$img_stmt = $conn->prepare("SELECT image_filename FROM products WHERE product_id = ?");
$img_stmt->bind_param('s', $product_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
if ($img_result->num_rows > 0) {
    $current_image = $img_result->fetch_assoc()['image_filename'];
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Handle image upload
$image_filename = $current_image;
$old_image_to_delete = null;

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = $_FILES['product_image']['type'];

    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, and WEBP are allowed']);
        exit;
    }

    if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
        exit;
    }

    $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
    $image_filename = $product_id . '_' . time() . '.' . $file_extension;
    $upload_path = __DIR__ . '/../assets/images/product/' . $image_filename;

    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }

    // Mark old image for deletion after successful commit
    if ($current_image !== 'default-product.png') {
        $old_image_to_delete = $current_image;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Update product
    $stmt = $conn->prepare("
        UPDATE products
        SET product_name = ?, image_filename = ?, price = ?, status = ?
        WHERE product_id = ?
    ");
    $stmt->bind_param("ssdss", $product_name, $image_filename, $price, $status, $product_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update product');
    }

    // Delete existing requirements
    $del_stmt = $conn->prepare("DELETE FROM product_requirements WHERE product_id = ?");
    $del_stmt->bind_param("s", $product_id);

    if (!$del_stmt->execute()) {
        throw new Exception('Failed to update requirements');
    }

    // Insert new requirements
    if (!empty($requirements)) {
        $req_stmt = $conn->prepare("
            INSERT INTO product_requirements (product_id, inventory_id, quantity_used)
            VALUES (?, ?, ?)
        ");

        foreach ($requirements as $req) {
            $inv_id = $req['inventory_id'];
            $qty = floatval($req['quantity_used']);

            if (empty($inv_id) || $qty <= 0) continue;

            $req_stmt->bind_param("ssd", $product_id, $inv_id, $qty);

            if (!$req_stmt->execute()) {
                throw new Exception('Failed to add requirement for ' . $inv_id);
            }
        }
    }

    // Delete existing category mappings
    $del_cat_stmt = $conn->prepare("DELETE FROM product_category_map WHERE product_id = ?");
    $del_cat_stmt->bind_param("s", $product_id);

    if (!$del_cat_stmt->execute()) {
        throw new Exception('Failed to update categories');
    }

    // Insert new category mappings
    if (!empty($categories)) {
        $cat_stmt = $conn->prepare("
            INSERT INTO product_category_map (product_id, category_id, is_primary)
            VALUES (?, ?, ?)
        ");

        foreach ($categories as $cat_id) {
            $is_primary = ($cat_id === $primary_category) ? 1 : 0;
            $cat_stmt->bind_param("ssi", $product_id, $cat_id, $is_primary);

            if (!$cat_stmt->execute()) {
                throw new Exception('Failed to assign category ' . $cat_id);
            }
        }
    }

    $conn->commit();

    // Delete old image after successful commit
    if ($old_image_to_delete) {
        $old_path = __DIR__ . '/../assets/images/product/' . $old_image_to_delete;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();

    // Delete newly uploaded image if transaction failed
    if ($image_filename !== $current_image && $image_filename !== 'default-product.png') {
        $upload_path = __DIR__ . '/../assets/images/product/' . $image_filename;
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
