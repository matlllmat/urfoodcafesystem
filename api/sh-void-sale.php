<?php
error_reporting(0);
ini_set('display_errors', 0);

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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$sale_id = isset($input['sale_id']) ? trim($input['sale_id']) : '';

if (empty($sale_id)) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

// Verify sale exists and is not already voided
$check_stmt = $conn->prepare("SELECT sale_id, status FROM sales WHERE sale_id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit;
}
$check_stmt->bind_param("s", $sale_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$sale = $check_result->fetch_assoc();
$check_stmt->close();

if ($sale['status'] === 'voided') {
    echo json_encode(['success' => false, 'message' => 'This sale has already been voided']);
    exit;
}

// Void the sale
$staff_id = $_SESSION['user_id'];
$void_stmt = $conn->prepare("
    UPDATE sales
    SET status = 'voided', voided_at = NOW(), voided_by = ?
    WHERE sale_id = ?
");
if (!$void_stmt) {
    echo json_encode(['success' => false, 'message' => 'Void query error: ' . $conn->error]);
    exit;
}
$void_stmt->bind_param("ss", $staff_id, $sale_id);

if ($void_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Sale ' . $sale_id . ' has been voided successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to void sale: ' . $void_stmt->error
    ]);
}

$void_stmt->close();
$conn->close();
?>
