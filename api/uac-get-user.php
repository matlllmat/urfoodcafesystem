<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only super admins can access UAC APIs
if (!isset($_SESSION['is_super_admin']) || !$_SESSION['is_super_admin']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$staff_id = trim($_GET['staff_id'] ?? '');

if (empty($staff_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit;
}

// Fetch user details
$stmt = $conn->prepare("SELECT staff_id, user_name, contact, email, hire_date, status, is_super_admin FROM users WHERE staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's page permissions
$perm_stmt = $conn->prepare("
    SELECT p.code
    FROM user_permissions up
    JOIN permissions p ON up.permission_id = p.id
    WHERE up.staff_id = ? AND p.code LIKE 'page.%'
    ORDER BY p.code
");
$perm_stmt->bind_param("s", $staff_id);
$perm_stmt->execute();
$perm_result = $perm_stmt->get_result();

$permissions = [];
while ($row = $perm_result->fetch_assoc()) {
    $permissions[] = $row['code'];
}
$perm_stmt->close();

echo json_encode([
    'success' => true,
    'user' => $user,
    'permissions' => $permissions
]);

$conn->close();
?>
