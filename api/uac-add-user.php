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

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$contact = trim($_POST['contact'] ?? '');
$email = trim($_POST['email'] ?? '');
$hire_date = trim($_POST['hire_date'] ?? '');
$permissions_json = $_POST['permissions'] ?? '[]';

// Validation
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Check unique username
$check_stmt = $conn->prepare("SELECT staff_id FROM users WHERE user_name = ?");
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Decode permissions
$permissions = json_decode($permissions_json, true);
if (!is_array($permissions)) {
    $permissions = [];
}

// Generate staff ID (ST-XXXX)
$staff_id = null;
$max_attempts = 10;
for ($i = 0; $i < $max_attempts; $i++) {
    $result = $conn->query("SELECT staff_id FROM users ORDER BY staff_id DESC LIMIT 1");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['staff_id'];

        if (preg_match('/ST-(\d+)/', $last_id, $matches)) {
            $next_num = intval($matches[1]) + 1;
            $staff_id = 'ST-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        } else {
            $staff_id = 'ST-1001';
        }
    } else {
        $staff_id = 'ST-1001';
    }

    $check = $conn->prepare("SELECT staff_id FROM users WHERE staff_id = ?");
    $check->bind_param("s", $staff_id);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {
        $check->close();
        break;
    }
    $check->close();

    if ($i === $max_attempts - 1) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate unique staff ID']);
        exit;
    }
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Handle empty hire_date
$hire_date_value = !empty($hire_date) ? $hire_date : null;

// Start transaction
$conn->begin_transaction();

try {
    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (staff_id, user_name, password, contact, email, hire_date, status, is_super_admin)
        VALUES (?, ?, ?, ?, ?, ?, 'Active', FALSE)
    ");
    $stmt->bind_param("ssssss", $staff_id, $username, $hashed_password, $contact, $email, $hire_date_value);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create user');
    }
    $stmt->close();

    // Insert permissions
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

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'staff_id' => $staff_id
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
