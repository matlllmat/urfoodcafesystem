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

$product_name = trim($_POST['product_name'] ?? '');
$price = $_POST['price'] ?? '';
$status = $_POST['status'] ?? 'Available';
$requirements_json = $_POST['requirements'] ?? '[]';
$categories_json = $_POST['categories'] ?? '[]';
$primary_category = trim($_POST['primary_category'] ?? '');

// Validation
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

// Check for duplicate inventory items in requirements
$inventory_ids = array_map(function($r) { return $r['inventory_id']; }, $requirements);
if (count($inventory_ids) !== count(array_unique($inventory_ids))) {
    echo json_encode(['success' => false, 'message' => 'Duplicate ingredients detected. Each ingredient can only be added once.']);
    exit;
}

// Generate product ID (PROD-XXXX)
$product_id = null;
$max_attempts = 10;
for ($i = 0; $i < $max_attempts; $i++) {
    $result = $conn->query("SELECT product_id FROM products ORDER BY product_id DESC LIMIT 1");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['product_id'];

        if (preg_match('/PROD-(\d+)/', $last_id, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $product_id = 'PROD-' . $next_num;
        } else {
            $product_id = 'PROD-1001';
        }
    } else {
        $product_id = 'PROD-1001';
    }

    $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $check_stmt->bind_param("s", $product_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows === 0) {
        break;
    }

    if ($i === $max_attempts - 1) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate unique product ID']);
        exit;
    }
}

// Handle image upload
$image_filename = 'default-product.png';
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
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert product
    $stmt = $conn->prepare("
        INSERT INTO products (product_id, product_name, image_filename, price, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssds", $product_id, $product_name, $image_filename, $price, $status);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create product');
    }

    // Insert requirements
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

    // Insert category mappings
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

    echo json_encode([
        'success' => true,
        'message' => 'Product created successfully',
        'product_id' => $product_id
    ]);

} catch (Exception $e) {
    $conn->rollback();

    // Delete uploaded image if transaction failed
    if ($image_filename !== 'default-product.png') {
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
