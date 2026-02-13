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
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$contact = trim($_POST['contact'] ?? '');
$email = trim($_POST['email'] ?? '');
$hire_date = trim($_POST['hire_date'] ?? '');
$permissions_json = $_POST['permissions'] ?? '[]';

// Validation
if (empty($staff_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit;
}

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

if (!empty($password) && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Check user exists
$check_stmt = $conn->prepare("SELECT staff_id, is_super_admin FROM users WHERE staff_id = ?");
$check_stmt->bind_param("s", $staff_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $check_stmt->close();
    exit;
}

$existing_user = $check_result->fetch_assoc();
$check_stmt->close();

// Check unique username (exclude current user)
$name_check = $conn->prepare("SELECT staff_id FROM users WHERE user_name = ? AND staff_id != ?");
$name_check->bind_param("ss", $username, $staff_id);
$name_check->execute();
if ($name_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    $name_check->close();
    exit;
}
$name_check->close();

// Decode permissions
$permissions = json_decode($permissions_json, true);
if (!is_array($permissions)) {
    $permissions = [];
}

$hire_date_value = !empty($hire_date) ? $hire_date : null;

// Start transaction
$conn->begin_transaction();

try {
    // Update user details
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users SET user_name = ?, password = ?, contact = ?, email = ?, hire_date = ?
            WHERE staff_id = ?
        ");
        $stmt->bind_param("ssssss", $username, $hashed_password, $contact, $email, $hire_date_value, $staff_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users SET user_name = ?, contact = ?, email = ?, hire_date = ?
            WHERE staff_id = ?
        ");
        $stmt->bind_param("sssss", $username, $contact, $email, $hire_date_value, $staff_id);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to update user');
    }
    $stmt->close();

    // Update permissions (only for non-super-admin users)
    if (!$existing_user['is_super_admin']) {
        // Delete existing page permissions
        $del_stmt = $conn->prepare("
            DELETE up FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.staff_id = ? AND p.code LIKE 'page.%'
        ");
        $del_stmt->bind_param("s", $staff_id);
        if (!$del_stmt->execute()) {
            throw new Exception('Failed to clear existing permissions');
        }
        $del_stmt->close();

        // Insert new permissions
        if (!empty($permissions)) {
            $perm_stmt = $conn->prepare("
                INSERT INTO user_permissions (staff_id, permission_id)
                SELECT ?, id FROM permissions WHERE code = ?
            ");

            foreach ($permissions as $perm_code) {
                $perm_stmt->bind_param("ss", $staff_id, $perm_code);
                if (!$perm_stmt->execute()) {
                    throw new Exception('Failed to assign permission: ' . $perm_code);
                }
            }
            $perm_stmt->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
