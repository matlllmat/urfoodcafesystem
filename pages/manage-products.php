<?php
require_once __DIR__ . '/../config/db.php';

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Available';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'updated_at_desc';

// Fetch active categories for filter dropdown
$cat_query = "SELECT category_id, category_name FROM product_categories WHERE is_active = 1 ORDER BY category_name ASC";
$cat_result = $conn->query($cat_query);
$filter_categories = [];
if ($cat_result) {
    while ($cat_row = $cat_result->fetch_assoc()) {
        $filter_categories[] = $cat_row;
    }
}

// Build main products query with calculated cost
$query = "
    SELECT
        p.product_id,
        p.product_name,
        p.image_filename,
        p.price,
        p.status,
        p.created_at,
        p.updated_at,

        COALESCE(cost_calc.total_cost, 0) as calculated_cost,
        (p.price - COALESCE(cost_calc.total_cost, 0)) as profit,
        CASE
            WHEN p.price > 0
            THEN ROUND(((p.price - COALESCE(cost_calc.total_cost, 0)) / p.price) * 100, 2)
            ELSE 0
        END as profit_margin,
        COALESCE(req_count.requirement_count, 0) as requirement_count,
        COALESCE(stock_check.out_of_stock_count, 0) as out_of_stock_count,
        primary_cat.category_name as primary_category_name

    FROM products p
    LEFT JOIN (
        SELECT
            pr.product_id,
            SUM(pr.quantity_used * COALESCE(batch_avg.avg_cost_per_unit, 0)) as total_cost
        FROM product_requirements pr
        LEFT JOIN (
            SELECT
                inventory_id,
                CASE
                    WHEN SUM(current_quantity) > 0
                    THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                    ELSE 0
                END as avg_cost_per_unit
            FROM inventory_batches
            WHERE expiration_date IS NULL OR expiration_date > CURDATE()
            GROUP BY inventory_id
        ) batch_avg ON pr.inventory_id = batch_avg.inventory_id
        GROUP BY pr.product_id
    ) cost_calc ON p.product_id = cost_calc.product_id
    LEFT JOIN (
        SELECT product_id, COUNT(*) as requirement_count
        FROM product_requirements
        GROUP BY product_id
    ) req_count ON p.product_id = req_count.product_id
    LEFT JOIN (
        SELECT
            pr.product_id,
            COUNT(CASE WHEN COALESCE(stock.available_qty, 0) = 0 THEN 1 END) as out_of_stock_count
        FROM product_requirements pr
        LEFT JOIN (
            SELECT inventory_id, SUM(current_quantity) as available_qty
            FROM inventory_batches
            WHERE expiration_date IS NULL OR expiration_date > CURDATE()
            GROUP BY inventory_id
        ) stock ON pr.inventory_id = stock.inventory_id
        GROUP BY pr.product_id
    ) stock_check ON p.product_id = stock_check.product_id
    LEFT JOIN (
        SELECT pcm.product_id, pc.category_name
        FROM product_category_map pcm
        JOIN product_categories pc ON pcm.category_id = pc.category_id
        WHERE pcm.is_primary = 1
    ) primary_cat ON p.product_id = primary_cat.product_id
";

// Status filter
$where_clauses = [];
if ($filter_status === 'Available') {
    $where_clauses[] = "p.status = 'Available'";
} elseif ($filter_status === 'Unavailable') {
    $where_clauses[] = "p.status = 'Unavailable'";
} elseif ($filter_status === 'Discontinued') {
    $where_clauses[] = "p.status = 'Discontinued'";
}

// Category filter
if (!empty($filter_category)) {
    $escaped_cat = $conn->real_escape_string($filter_category);
    $where_clauses[] = "p.product_id IN (SELECT product_id FROM product_category_map WHERE category_id = '$escaped_cat')";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sorting
switch ($sort_by) {
    case 'name_asc':
        $query .= " ORDER BY p.product_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.product_name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'profit_asc':
        $query .= " ORDER BY profit ASC";
        break;
    case 'profit_desc':
        $query .= " ORDER BY profit DESC";
        break;
    case 'updated_at_asc':
        $query .= " ORDER BY p.updated_at ASC";
        break;
    default:
        $query .= " ORDER BY p.updated_at DESC";
}

$products_result = $conn->query($query);
$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Stats
$stats_query = "
    SELECT
        COUNT(*) as total_products,
        COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_count,
        COUNT(CASE WHEN status = 'Unavailable' THEN 1 END) as unavailable_count,
        COUNT(CASE WHEN status = 'Discontinued' THEN 1 END) as discontinued_count
    FROM products
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_products' => 0,
    'available_count' => 0,
    'unavailable_count' => 0,
    'discontinued_count' => 0
];

// Average profit margin
$margin_query = "
    SELECT
        AVG(
            CASE
                WHEN p.price > 0
                THEN ((p.price - COALESCE(cost_calc.total_cost, 0)) / p.price) * 100
                ELSE 0
            END
        ) as avg_profit_margin
    FROM products p
    LEFT JOIN (
        SELECT
            pr.product_id,
            SUM(pr.quantity_used * COALESCE(batch_avg.avg_cost_per_unit, 0)) as total_cost
        FROM product_requirements pr
        LEFT JOIN (
            SELECT
                inventory_id,
                CASE
                    WHEN SUM(current_quantity) > 0
                    THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                    ELSE 0
                END as avg_cost_per_unit
            FROM inventory_batches
            WHERE expiration_date IS NULL OR expiration_date > CURDATE()
            GROUP BY inventory_id
        ) batch_avg ON pr.inventory_id = batch_avg.inventory_id
        GROUP BY pr.product_id
    ) cost_calc ON p.product_id = cost_calc.product_id
";
$margin_result = $conn->query($margin_query);
$avg_margin = $margin_result ? floatval($margin_result->fetch_assoc()['avg_profit_margin']) : 0;
?>

<!-- Manage Products Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Manage Products</h1>
        <p class="text-regular text-gray-600">Create, view, and manage your cafe products and recipes</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Products -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Products</p>
                    <h3 class="text-title text-2xl text-gray-800"><?php echo $stats['total_products']; ?></h3>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Profit Margin -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Avg Profit Margin</p>
                    <h3 class="text-title text-2xl <?php echo $avg_margin >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo number_format($avg_margin, 1); ?>%
                    </h3>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Available Products -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Available Products</p>
                    <h3 class="text-title text-2xl text-success"><?php echo $stats['available_count']; ?></h3>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unavailable Products -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-label text-gray-500 mb-1">Unavailable Products</p>
                    <h3 class="text-title text-2xl text-danger"><?php echo $stats['unavailable_count'] + $stats['discontinued_count']; ?></h3>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 text-warning"><?php echo $stats['unavailable_count']; ?> Unavailable</span>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-danger"><?php echo $stats['discontinued_count']; ?> Discontinued</span>
                    </div>
                </div>
                <div class="bg-red-100 p-2 rounded flex-shrink-0">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Search and Filters -->
            <div class="flex flex-col sm:flex-row gap-3 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <input
                        type="text"
                        id="mpSearchInput"
                        placeholder="Search products..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Status Filter -->
                <select
                    id="mpStatusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="applyProductFilters()">
                    <option value="">All Status</option>
                    <option value="Available" <?php echo $filter_status == 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Unavailable" <?php echo $filter_status == 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    <option value="Discontinued" <?php echo $filter_status == 'Discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                </select>

                <!-- Category Filter -->
                <select
                    id="mpCategoryFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="applyProductFilters()">
                    <option value="">All Categories</option>
                    <?php foreach ($filter_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo $filter_category == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Sort -->
                <select
                    id="mpSortFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="applyProductFilters()">
                    <option value="updated_at_desc" <?php echo $sort_by == 'updated_at_desc' ? 'selected' : ''; ?>>Recently Updated</option>
                    <option value="updated_at_asc" <?php echo $sort_by == 'updated_at_asc' ? 'selected' : ''; ?>>Least Updated</option>
                    <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    <option value="profit_asc" <?php echo $sort_by == 'profit_asc' ? 'selected' : ''; ?>>Profit (Low to High)</option>
                    <option value="profit_desc" <?php echo $sort_by == 'profit_desc' ? 'selected' : ''; ?>>Profit (High to Low)</option>
                </select>
            </div>

            <!-- Action Button -->
            <div class="flex gap-2">
                <button
                    onclick="openAddProductModal()"
                    class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors whitespace-nowrap">
                    + Add Product
                </button>
            </div>
        </div>
    </div>

    <!-- Product Cards -->
    <?php if (empty($products)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <p class="text-regular text-gray-500">No products found</p>
            <p class="text-label text-gray-400 mt-1">Try adjusting your filters or add a new product</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($products as $product): ?>
                <?php include 'manage-products-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Include Modals -->
<?php require_once 'modals/product-detail-modal.php'; ?>
<?php require_once 'modals/add-product-modal.php'; ?>
<?php require_once 'modals/edit-product-modal.php'; ?>

<!-- Shared State & ESC Key Handler -->
<script>
    let currentProductId = null;
    let currentProductName = null;
    let productWasModified = false;

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const editProductModal = document.getElementById('editProductModal');
            const addProductModal = document.getElementById('addProductModal');
            const productDetailModal = document.getElementById('productDetailModal');

            if (editProductModal && !editProductModal.classList.contains('hidden')) {
                closeEditProductModal();
            } else if (addProductModal && !addProductModal.classList.contains('hidden')) {
                closeAddProductModal();
            } else if (productDetailModal && !productDetailModal.classList.contains('hidden')) {
                closeProductDetailModal();
            }
        }
    });
</script>

<!-- Load JS Modules -->
<script src="js/products-main.js"></script>
<script src="js/products-filters.js"></script>
