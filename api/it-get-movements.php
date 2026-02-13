<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Filter parameters
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$filter_item = isset($_GET['item']) ? trim($_GET['item']) : '';
$filter_staff = isset($_GET['staff']) ? trim($_GET['staff']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'date_desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(1, min(100, (int)($_GET['per_page'] ?? 20)));

// ==================== STATISTICS (always unfiltered) ====================
$stats_query = "
    SELECT
        COUNT(*) as total_movements,
        COUNT(CASE WHEN DATE(movement_date) = CURDATE() THEN 1 END) as today_movements,
        COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as total_inbound_qty,
        COALESCE(SUM(CASE WHEN quantity > 0 THEN total_value ELSE 0 END), 0) as total_inbound_value,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as inbound_count,
        COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) as total_outbound_qty,
        COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(total_value) ELSE 0 END), 0) as total_outbound_value,
        COUNT(CASE WHEN quantity < 0 THEN 1 END) as outbound_count,
        COALESCE(SUM(total_value), 0) as net_value
    FROM inventory_movements
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_movements' => 0,
    'today_movements' => 0,
    'total_inbound_qty' => 0,
    'total_inbound_value' => 0,
    'inbound_count' => 0,
    'total_outbound_qty' => 0,
    'total_outbound_value' => 0,
    'outbound_count' => 0,
    'net_value' => 0
];

// ==================== MOVEMENTS LIST ====================
$query = "
    SELECT
        m.movement_id,
        m.inventory_id,
        ii.item_name,
        ii.quantity_unit,
        m.batch_id,
        ib.batch_title,
        m.movement_type,
        m.quantity,
        m.old_quantity,
        m.new_quantity,
        m.unit_cost,
        m.total_value,
        m.reference_type,
        m.reference_id,
        m.staff_id,
        u.user_name as staff_name,
        m.movement_date,
        m.reason,
        m.created_at
    FROM inventory_movements m
    LEFT JOIN inventory_items ii ON m.inventory_id = ii.item_id
    LEFT JOIN inventory_batches ib ON m.batch_id = ib.batch_id
    LEFT JOIN users u ON m.staff_id = u.staff_id
";

$where_clauses = [];
$params = [];
$types = '';

// Movement type filter
if (!empty($filter_type)) {
    $where_clauses[] = "m.movement_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

// Item filter
if (!empty($filter_item)) {
    $where_clauses[] = "m.inventory_id = ?";
    $params[] = $filter_item;
    $types .= 's';
}

// Staff filter
if (!empty($filter_staff)) {
    $where_clauses[] = "m.staff_id = ?";
    $params[] = $filter_staff;
    $types .= 's';
}

// Date range filter
if (!empty($date_from)) {
    $where_clauses[] = "DATE(m.movement_date) >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if (!empty($date_to)) {
    $where_clauses[] = "DATE(m.movement_date) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Search by movement ID or item name
if (!empty($search)) {
    $where_clauses[] = "(m.movement_id LIKE ? OR ii.item_name LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Count total (same filters)
$count_query = "SELECT COUNT(*) as total FROM inventory_movements m LEFT JOIN inventory_items ii ON m.inventory_id = ii.item_id";
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
        $query .= " ORDER BY m.movement_date ASC";
        break;
    case 'qty_desc':
        $query .= " ORDER BY ABS(m.quantity) DESC";
        break;
    case 'qty_asc':
        $query .= " ORDER BY ABS(m.quantity) ASC";
        break;
    case 'value_desc':
        $query .= " ORDER BY ABS(m.total_value) DESC";
        break;
    case 'value_asc':
        $query .= " ORDER BY ABS(m.total_value) ASC";
        break;
    default: // date_desc
        $query .= " ORDER BY m.movement_date DESC";
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

$movements = [];
while ($row = $result->fetch_assoc()) {
    $movements[] = $row;
}

// ==================== ITEM LIST (for filter dropdown) ====================
$item_query = "
    SELECT DISTINCT ii.item_id, ii.item_name
    FROM inventory_items ii
    JOIN inventory_movements m ON ii.item_id = m.inventory_id
    ORDER BY ii.item_name ASC
";
$item_result = $conn->query($item_query);
$items = [];
if ($item_result) {
    while ($row = $item_result->fetch_assoc()) {
        $items[] = $row;
    }
}

// ==================== STAFF LIST (for filter dropdown) ====================
$staff_query = "
    SELECT DISTINCT u.staff_id, u.user_name
    FROM users u
    JOIN inventory_movements m ON u.staff_id = m.staff_id
    ORDER BY u.user_name ASC
";
$staff_result = $conn->query($staff_query);
$staff = [];
if ($staff_result) {
    while ($row = $staff_result->fetch_assoc()) {
        $staff[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'movements' => $movements,
    'items' => $items,
    'staff' => $staff,
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
