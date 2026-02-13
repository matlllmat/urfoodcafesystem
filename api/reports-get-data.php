<?php
ob_start();
session_start();
header('Content-Type: application/json');

// Catch ALL errors including fatal — output JSON instead of blank 500
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal: ' . $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line']
        ]);
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once __DIR__ . '/../config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Helper: safe prepare with JSON error on failure
function safe_prepare($conn, $query, $label = 'Query') {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "$label failed: " . $conn->error
        ]);
        exit;
    }
    return $stmt;
}

// ==================== PERIOD CALCULATION ====================
$period = isset($_GET['period']) ? trim($_GET['period']) : 'this_month';

$today = date('Y-m-d');

switch ($period) {
    case 'today':
        $current_from = $today;
        $current_to = $today;
        $prev_from = date('Y-m-d', strtotime('-1 day'));
        $prev_to = date('Y-m-d', strtotime('-1 day'));
        $prev_label = 'yesterday';
        break;
    case 'this_week':
        $current_from = date('Y-m-d', strtotime('monday this week'));
        $current_to = $today;
        $prev_from = date('Y-m-d', strtotime('monday last week'));
        $prev_to = date('Y-m-d', strtotime('sunday last week'));
        $prev_label = 'last week';
        break;
    case 'this_month':
        $current_from = date('Y-m-01');
        $current_to = $today;
        $prev_from = date('Y-m-01', strtotime('first day of last month'));
        $prev_to = date('Y-m-t', strtotime('first day of last month'));
        $prev_label = 'last month';
        break;
    case 'this_year':
        $current_from = date('Y-01-01');
        $current_to = $today;
        $prev_from = date('Y-01-01', strtotime('-1 year'));
        $prev_to = date('Y-12-31', strtotime('-1 year'));
        $prev_label = 'last year';
        break;
    case 'custom':
        $current_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : date('Y-m-01');
        $current_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : $today;
        // Previous period = same length before the start
        $days_diff = (strtotime($current_to) - strtotime($current_from)) / 86400;
        $prev_to = date('Y-m-d', strtotime($current_from . ' -1 day'));
        $prev_from = date('Y-m-d', strtotime($prev_to . ' -' . (int)$days_diff . ' days'));
        $prev_label = 'previous period';
        break;
    default:
        $current_from = date('Y-m-01');
        $current_to = $today;
        $prev_from = date('Y-m-01', strtotime('first day of last month'));
        $prev_to = date('Y-m-t', strtotime('first day of last month'));
        $prev_label = 'last month';
}

// ==================== KPI: CURRENT PERIOD ====================
$kpi_query = "
    SELECT
        COUNT(*) as transactions,
        COALESCE(SUM(total_price), 0) as revenue,
        COALESCE(SUM(profit), 0) as profit,
        COALESCE(AVG(total_price), 0) as avg_order,
        COALESCE(SUM(total_cost), 0) as total_cost
    FROM sales
    WHERE status = 'completed' AND sale_date BETWEEN ? AND ?
";
$stmt = safe_prepare($conn, $kpi_query, 'KPI query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$current_kpi = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ==================== KPI: PREVIOUS PERIOD ====================
$stmt = safe_prepare($conn, $kpi_query, 'KPI prev query');
$stmt->bind_param("ss", $prev_from, $prev_to);
$stmt->execute();
$prev_kpi = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate percentage changes
function pct_change($current, $previous) {
    $current = floatval($current);
    $previous = floatval($previous);
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

$kpi = [
    'revenue' => floatval($current_kpi['revenue']),
    'revenue_change' => pct_change($current_kpi['revenue'], $prev_kpi['revenue']),
    'profit' => floatval($current_kpi['profit']),
    'profit_change' => pct_change($current_kpi['profit'], $prev_kpi['profit']),
    'transactions' => intval($current_kpi['transactions']),
    'transactions_change' => pct_change($current_kpi['transactions'], $prev_kpi['transactions']),
    'avg_order' => floatval($current_kpi['avg_order']),
    'avg_order_change' => pct_change($current_kpi['avg_order'], $prev_kpi['avg_order']),
    'total_cost' => floatval($current_kpi['total_cost']),
    'prev_label' => $prev_label
];

// ==================== DAILY TREND ====================
$trend_query = "
    SELECT
        sale_date,
        COALESCE(SUM(total_price), 0) as revenue,
        COALESCE(SUM(profit), 0) as profit,
        COUNT(*) as transactions
    FROM sales
    WHERE status = 'completed' AND sale_date BETWEEN ? AND ?
    GROUP BY sale_date
    ORDER BY sale_date ASC
";
$stmt = safe_prepare($conn, $trend_query, 'Daily trend query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$trend_result = $stmt->get_result();

$daily_trend = [];
while ($row = $trend_result->fetch_assoc()) {
    $daily_trend[] = [
        'date' => $row['sale_date'],
        'revenue' => floatval($row['revenue']),
        'profit' => floatval($row['profit']),
        'transactions' => intval($row['transactions'])
    ];
}
$stmt->close();

// ==================== SALES BY HOUR ====================
$hour_query = "
    SELECT
        HOUR(sale_time) as hour,
        COUNT(*) as transactions,
        COALESCE(SUM(total_price), 0) as revenue
    FROM sales
    WHERE status = 'completed' AND sale_date BETWEEN ? AND ?
    GROUP BY HOUR(sale_time)
    ORDER BY hour ASC
";
$stmt = safe_prepare($conn, $hour_query, 'Hourly sales query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$hour_result = $stmt->get_result();

$hourly_sales = [];
while ($row = $hour_result->fetch_assoc()) {
    $hourly_sales[] = [
        'hour' => intval($row['hour']),
        'transactions' => intval($row['transactions']),
        'revenue' => floatval($row['revenue'])
    ];
}
$stmt->close();

// ==================== TOP PRODUCTS ====================
$top_products_query = "
    SELECT
        p.product_name,
        SUM(sd.quantity) as total_qty,
        SUM(sd.subtotal) as total_revenue,
        SUM(sd.subtotal) - SUM(sd.cost_per_unit * sd.quantity) as total_profit
    FROM sale_details sd
    JOIN sales s ON sd.sale_id = s.sale_id
    JOIN products p ON sd.product_id = p.product_id
    WHERE s.status = 'completed' AND s.sale_date BETWEEN ? AND ?
    GROUP BY sd.product_id, p.product_name
    ORDER BY total_revenue DESC
    LIMIT 10
";
$stmt = safe_prepare($conn, $top_products_query, 'Top products query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$top_result = $stmt->get_result();

$top_products = [];
while ($row = $top_result->fetch_assoc()) {
    $top_products[] = [
        'name' => $row['product_name'],
        'qty' => intval($row['total_qty']),
        'revenue' => floatval($row['total_revenue']),
        'profit' => floatval($row['total_profit'])
    ];
}
$stmt->close();

// ==================== CATEGORY BREAKDOWN ====================
$category_query = "
    SELECT
        pc.category_name,
        COALESCE(SUM(sd.subtotal), 0) as total_revenue
    FROM sale_details sd
    JOIN sales s ON sd.sale_id = s.sale_id
    JOIN product_category_map pcm ON sd.product_id = pcm.product_id AND pcm.is_primary = 1
    JOIN product_categories pc ON pcm.category_id = pc.category_id
    WHERE s.status = 'completed' AND s.sale_date BETWEEN ? AND ?
    GROUP BY pc.category_id, pc.category_name
    ORDER BY total_revenue DESC
";
$stmt = safe_prepare($conn, $category_query, 'Category breakdown query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$cat_result = $stmt->get_result();

$category_breakdown = [];
while ($row = $cat_result->fetch_assoc()) {
    $category_breakdown[] = [
        'category' => $row['category_name'],
        'revenue' => floatval($row['total_revenue'])
    ];
}
$stmt->close();

// ==================== STAFF PERFORMANCE ====================
$staff_query = "
    SELECT
        u.user_name as cashier,
        COUNT(*) as transactions,
        COALESCE(SUM(s.total_price), 0) as revenue,
        COALESCE(SUM(s.profit), 0) as profit
    FROM sales s
    JOIN users u ON s.staff_id = u.staff_id
    WHERE s.status = 'completed' AND s.sale_date BETWEEN ? AND ?
    GROUP BY s.staff_id, u.user_name
    ORDER BY revenue DESC
";
$stmt = safe_prepare($conn, $staff_query, 'Staff performance query');
$stmt->bind_param("ss", $current_from, $current_to);
$stmt->execute();
$staff_result = $stmt->get_result();

$staff_performance = [];
while ($row = $staff_result->fetch_assoc()) {
    $staff_performance[] = [
        'cashier' => $row['cashier'],
        'transactions' => intval($row['transactions']),
        'revenue' => floatval($row['revenue']),
        'profit' => floatval($row['profit'])
    ];
}
$stmt->close();

// ==================== LOW STOCK ALERTS ====================
$low_stock_query = "
    SELECT
        ii.item_id,
        ii.item_name,
        ii.quantity_unit,
        ii.reorder_level,
        COALESCE(SUM(ib.current_quantity), 0) as current_stock
    FROM inventory_items ii
    LEFT JOIN inventory_batches ib ON ii.item_id = ib.inventory_id
        AND (ib.expiration_date IS NULL OR ib.expiration_date >= CURDATE())
    GROUP BY ii.item_id, ii.item_name, ii.quantity_unit, ii.reorder_level
    HAVING COALESCE(SUM(ib.current_quantity), 0) <= ii.reorder_level
    ORDER BY (COALESCE(SUM(ib.current_quantity), 0) / GREATEST(ii.reorder_level, 1)) ASC
    LIMIT 10
";
$low_stock_result = $conn->query($low_stock_query);

$low_stock = [];
if ($low_stock_result) {
    while ($row = $low_stock_result->fetch_assoc()) {
        $low_stock[] = [
            'item_id' => $row['item_id'],
            'name' => $row['item_name'],
            'unit' => $row['quantity_unit'],
            'current' => floatval($row['current_stock']),
            'reorder_level' => floatval($row['reorder_level'])
        ];
    }
}

// ==================== EXPIRING SOON BATCHES ====================
$expiring_query = "
    SELECT
        ib.batch_id,
        ii.item_name,
        ib.batch_title,
        ib.current_quantity,
        ii.quantity_unit,
        ib.expiration_date,
        DATEDIFF(ib.expiration_date, CURDATE()) as days_left
    FROM inventory_batches ib
    JOIN inventory_items ii ON ib.inventory_id = ii.item_id
    WHERE ib.expiration_date IS NOT NULL
      AND ib.current_quantity > 0
      AND ib.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY ib.expiration_date ASC
    LIMIT 10
";
$expiring_result = $conn->query($expiring_query);

$expiring_soon = [];
if ($expiring_result) {
    while ($row = $expiring_result->fetch_assoc()) {
        $expiring_soon[] = [
            'batch_id' => $row['batch_id'],
            'item_name' => $row['item_name'],
            'batch_title' => $row['batch_title'],
            'quantity' => floatval($row['current_quantity']),
            'unit' => $row['quantity_unit'],
            'expiration_date' => $row['expiration_date'],
            'days_left' => intval($row['days_left'])
        ];
    }
}

// ==================== RESPONSE ====================
echo json_encode([
    'success' => true,
    'period' => [
        'current_from' => $current_from,
        'current_to' => $current_to,
        'prev_from' => $prev_from,
        'prev_to' => $prev_to,
        'prev_label' => $prev_label
    ],
    'kpi' => $kpi,
    'daily_trend' => $daily_trend,
    'hourly_sales' => $hourly_sales,
    'top_products' => $top_products,
    'category_breakdown' => $category_breakdown,
    'staff_performance' => $staff_performance,
    'low_stock' => $low_stock,
    'expiring_soon' => $expiring_soon
]);

$conn->close();
?>
