<?php
/**
 * Get default batch priority method for new items
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

try {
    // Get default priority setting
    $query = "SELECT setting_value FROM inventory_settings WHERE setting_key = 'default_priority_method'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $default_method = $row['setting_value'];
    } else {
        // If not found, insert default and return it
        $insert = "INSERT INTO inventory_settings (setting_key, setting_value) VALUES ('default_priority_method', 'fifo')";
        $conn->query($insert);
        $default_method = 'fifo';
    }

    echo json_encode([
        'success' => true,
        'default_method' => $default_method
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
