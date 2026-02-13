<?php
/**
 * Save default batch priority method for new items
 * This DOES NOT affect existing items, only new items added in the future
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get POST data
    $priority_method = $_POST['priority_method'] ?? null;

    // Validation
    if (!in_array($priority_method, ['fifo', 'fefo', 'manual'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid priority method. Must be fifo, fefo, or manual']);
        exit;
    }

    // Update or insert default priority setting
    $query = "
        INSERT INTO inventory_settings (setting_key, setting_value)
        VALUES ('default_priority_method', ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $priority_method, $priority_method);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Default priority method saved successfully. New items will use ' . strtoupper($priority_method) . ' by default.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save default priority: ' . $conn->error
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
