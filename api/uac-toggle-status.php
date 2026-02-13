<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$staff_id = trim($_POST['staff_id'] ?? '');

if (empty($staff_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit;
}

// Cannot toggle own status
if ($staff_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own status']);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT staff_id, user_name, status, is_super_admin FROM users WHERE staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Cannot toggle super admin status
if ($user['is_super_admin']) {
    echo json_encode(['success' => false, 'message' => 'Cannot change status of a super administrator']);
    exit;
}

// Toggle status
$new_status = $user['status'] === 'Active' ? 'Deactivated' : 'Active';

$update_stmt = $conn->prepare("UPDATE users SET status = ? WHERE staff_id = ?");
$update_stmt->bind_param("ss", $new_status, $staff_id);

if ($update_stmt->execute()) {
    $action = $new_status === 'Active' ? 'activated' : 'deactivated';
    echo json_encode([
        'success' => true,
        'message' => "User \"{$user['user_name']}\" has been {$action}",
        'new_status' => $new_status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
}

$update_stmt->close();
$conn->close();
?>
