<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Filter parameters
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_cashier = isset($_GET['cashier']) ? trim($_GET['cashier']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'date_desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(1, min(100, (int)($_GET['per_page'] ?? 20)));

// ==================== STATISTICS ====================
// Total completed sales
$stats_query = "
    SELECT
        COUNT(*) as total_sales,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN profit ELSE 0 END), 0) as total_profit,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN total_price ELSE NULL END), 0) as avg_order_value,
        COUNT(CASE WHEN status = 'voided' THEN 1 END) as voided_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
    FROM sales
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_sales' => 0,
    'total_revenue' => 0,
    'total_profit' => 0,
    'avg_order_value' => 0,
    'voided_count' => 0,
    'completed_count' => 0
];

// Today's sales
$today_query = "
    SELECT
        COUNT(*) as today_sales,
        COALESCE(SUM(total_price), 0) as today_revenue
    FROM sales
    WHERE sale_date = CURDATE() AND status = 'completed'
";
$today_result = $conn->query($today_query);
$today = $today_result ? $today_result->fetch_assoc() : ['today_sales' => 0, 'today_revenue' => 0];

$stats['today_sales'] = $today['today_sales'];
$stats['today_revenue'] = $today['today_revenue'];

// ==================== SALES LIST ====================
$query = "
    SELECT
        s.sale_id,
        s.staff_id,
        s.sale_date,
        s.sale_time,
        s.total_price,
        s.total_cost,
        s.profit,
        s.amount_paid,
        s.change_amount,
        s.status,
        s.voided_at,
        s.voided_by,
        s.created_at,
        u.user_name as cashier_name,
        (SELECT COUNT(*) FROM sale_details sd WHERE sd.sale_id = s.sale_id) as item_count,
        vu.user_name as voided_by_name
    FROM sales s
    JOIN users u ON s.staff_id = u.staff_id
    LEFT JOIN users vu ON s.voided_by = vu.staff_id
";

$where_clauses = [];
$params = [];
$types = '';

// Status filter
if ($filter_status === 'completed') {
    $where_clauses[] = "s.status = 'completed'";
} elseif ($filter_status === 'voided') {
    $where_clauses[] = "s.status = 'voided'";
}

// Cashier filter
if (!empty($filter_cashier)) {
    $where_clauses[] = "s.staff_id = ?";
    $params[] = $filter_cashier;
    $types .= 's';
}

// Date range filter
if (!empty($date_from)) {
    $where_clauses[] = "s.sale_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if (!empty($date_to)) {
    $where_clauses[] = "s.sale_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Search by sale ID
if (!empty($search)) {
    $where_clauses[] = "s.sale_id LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Count total (same filters)
$count_query = "SELECT COUNT(*) as total FROM sales s JOIN users u ON s.staff_id = u.staff_id";
if (!empty($where_clauses)) {
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_count = (int) $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = $total_count > 0 ? (int) ceil($total_count / $per_page) : 1;
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Sorting
switch ($sort_by) {
    case 'date_asc':
        $query .= " ORDER BY s.sale_date ASC, s.sale_time ASC";
        break;
    case 'amount_desc':
        $query .= " ORDER BY s.total_price DESC";
        break;
    case 'amount_asc':
        $query .= " ORDER BY s.total_price ASC";
        break;
    case 'profit_desc':
        $query .= " ORDER BY s.profit DESC";
        break;
    case 'profit_asc':
        $query .= " ORDER BY s.profit ASC";
        break;
    default: // date_desc
        $query .= " ORDER BY s.sale_date DESC, s.sale_time DESC";
}

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}

// ==================== CASHIER LIST (for filter dropdown) ====================
$cashier_query = "
    SELECT DISTINCT u.staff_id, u.user_name
    FROM users u
    JOIN sales s ON u.staff_id = s.staff_id
    ORDER BY u.user_name ASC
";
$cashier_result = $conn->query($cashier_query);
$cashiers = [];
while ($row = $cashier_result->fetch_assoc()) {
    $cashiers[] = $row;
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'sales' => $sales,
    'cashiers' => $cashiers,
    'pagination' => [
        'total_count' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ]
]);

$stmt->close();
$conn->close();
?>
