<?php
// Fetch inventory data
require_once __DIR__ . '/../config/db.php';

// Get all categories for filter
$categories_query = "SELECT * FROM inventory_categories WHERE is_active = TRUE ORDER BY display_order";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get filter parameters
$filter_categories = isset($_GET['categories']) ? $_GET['categories'] : [];
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'updated_at_desc';
$view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'flat'; // 'flat', 'grouped', 'all_grouped'

// Ensure filter_categories is an array
if (!is_array($filter_categories)) {
    $filter_categories = $filter_categories ? [$filter_categories] : [];
}

// Build query to get inventory items with calculated stats
$query = "
    SELECT 
        ii.item_id,
        ii.item_name,
        ii.image_filename,
        ii.quantity_unit,
        ii.reorder_level,
        ii.updated_at,
        
        -- Total quantity (including expired)
        COALESCE(batch_summary.total_quantity, 0) as total_quantity,
        
        -- Available quantity (excluding expired batches)
        COALESCE(batch_summary.available_quantity, 0) as available_quantity,
        
        -- Total value and expiration info
        COALESCE(batch_summary.total_value, 0) as total_value,
        COALESCE(batch_summary.nearest_expiration, NULL) as nearest_expiration,
        
        -- Expired quantity (for display purposes)
        COALESCE(batch_summary.expired_quantity, 0) as expired_quantity,
        
        GROUP_CONCAT(DISTINCT ic.category_id) as category_ids,
        GROUP_CONCAT(DISTINCT CASE WHEN itc.is_primary = 1 THEN ic.category_name END) as primary_category,
        GROUP_CONCAT(DISTINCT ic.category_name ORDER BY itc.is_primary DESC SEPARATOR ', ') as all_categories,
        
        -- Stock status based on AVAILABLE (non-expired) quantity
        CASE 
            WHEN COALESCE(batch_summary.available_quantity, 0) = 0 THEN 'Out of Stock'
            WHEN COALESCE(batch_summary.available_quantity, 0) <= ii.reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status,
        
        CASE
            WHEN batch_summary.nearest_expiration IS NOT NULL 
                AND batch_summary.nearest_expiration <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
            THEN 1
            ELSE 0
        END as has_expiring_soon,
                
        -- NEW: Check if item has expired batches
        CASE
            WHEN batch_summary.has_expired_batches > 0
            THEN 1
            ELSE 0
        END as has_expired
    FROM inventory_items ii
    LEFT JOIN (
        SELECT 
            inventory_id,
            SUM(current_quantity) as total_quantity,
            SUM(CASE 
                WHEN expiration_date IS NULL OR expiration_date > CURDATE() 
                THEN current_quantity 
                ELSE 0 
            END) as available_quantity,
            SUM(CASE 
                WHEN expiration_date IS NOT NULL AND expiration_date <= CURDATE() 
                THEN current_quantity 
                ELSE 0 
            END) as expired_quantity,
            SUM((current_quantity / initial_quantity) * total_cost) as total_value,
            MIN(CASE 
                WHEN expiration_date > CURDATE() OR expiration_date IS NULL
                THEN expiration_date 
                ELSE NULL 
            END) as nearest_expiration,
            SUM(CASE 
                WHEN expiration_date IS NOT NULL AND expiration_date <= CURDATE() AND current_quantity > 0
                THEN 1 
                ELSE 0 
            END) as has_expired_batches
        FROM inventory_batches
        GROUP BY inventory_id
    ) batch_summary ON ii.item_id = batch_summary.inventory_id
    LEFT JOIN item_categories itc ON ii.item_id = itc.item_id
    LEFT JOIN inventory_categories ic ON itc.category_id = ic.category_id
";

// Add category filter if specific categories are selected
$where_clauses = [];
if (!empty($filter_categories) && $view_mode !== 'all_grouped' && $view_mode !== 'flat') {
    $escaped_categories = array_map(function ($cat) use ($conn) {
        return "'" . $conn->real_escape_string($cat) . "'";
    }, $filter_categories);
    $where_clauses[] = "itc.category_id IN (" . implode(", ", $escaped_categories) . ")";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " GROUP BY ii.item_id, ii.item_name, ii.image_filename, ii.quantity_unit, ii.reorder_level, ii.updated_at, 
            batch_summary.total_quantity, batch_summary.available_quantity, batch_summary.expired_quantity,
            batch_summary.total_value, batch_summary.nearest_expiration, batch_summary.has_expired_batches";

// Add status filter using HAVING (after GROUP BY)
if ($filter_status) {
    if ($filter_status == 'In Stock') {
        $query .= " HAVING COALESCE(batch_summary.available_quantity, 0) > ii.reorder_level";
    } elseif ($filter_status == 'Low Stock') {
        $query .= " HAVING COALESCE(batch_summary.available_quantity, 0) <= ii.reorder_level AND COALESCE(batch_summary.available_quantity, 0) > 0";
    } elseif ($filter_status == 'Out of Stock') {
        $query .= " HAVING COALESCE(batch_summary.available_quantity, 0) = 0";
    }
}

// Add sorting
switch ($sort_by) {
    case 'name_asc':
        $query .= " ORDER BY ii.item_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY ii.item_name DESC";
        break;
    case 'quantity_asc':
        $query .= " ORDER BY available_quantity ASC";
        break;
    case 'quantity_desc':
        $query .= " ORDER BY available_quantity DESC";
        break;
    case 'updated_at_asc':
        $query .= " ORDER BY ii.updated_at ASC";
        break;
    default:
        $query .= " ORDER BY ii.updated_at DESC";
}

$items_result = $conn->query($query);
$items = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Calculate stats
$stats_query = "
    SELECT 
        COUNT(DISTINCT ii.item_id) as total_items,
        COUNT(DISTINCT CASE 
            WHEN COALESCE(batch_totals.available_qty, 0) > 0
            THEN ii.item_id
        END) as total_available_items,
        COALESCE(SUM(
            CASE 
                WHEN ib.expiration_date IS NULL OR ib.expiration_date > CURDATE()
                THEN (ib.current_quantity / ib.initial_quantity) * ib.total_cost
                ELSE 0
            END
        ), 0) as total_inventory_value,
        COUNT(DISTINCT CASE 
            WHEN batch_totals.available_qty <= ii.reorder_level 
            AND batch_totals.available_qty > 0 
            THEN ii.item_id 
        END) as low_stock_count,
        COUNT(DISTINCT CASE 
            WHEN COALESCE(batch_totals.available_qty, 0) = 0 
            THEN ii.item_id 
        END) as out_of_stock_count
    FROM inventory_items ii
    LEFT JOIN inventory_batches ib ON ii.item_id = ib.inventory_id
    LEFT JOIN (
        SELECT 
            inventory_id,
            SUM(current_quantity) as total_qty,
            SUM(CASE 
                WHEN expiration_date IS NULL OR expiration_date > CURDATE()
                THEN current_quantity
                ELSE 0
            END) as available_qty
        FROM inventory_batches
        GROUP BY inventory_id
    ) batch_totals ON ii.item_id = batch_totals.inventory_id
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_items' => 0,
    'total_available_items' => 0,
    'total_inventory_value' => 0,
    'low_stock_count' => 0,
    'out_of_stock_count' => 0
];

// Determine how to display items based on view mode
$grouped_items = [];
$flat_items = [];

if ($view_mode === 'flat') {
    // Flat view - no grouping
    $flat_items = $items;
} elseif ($view_mode === 'all_grouped') {
    // Group all items by ALL their categories
    foreach ($items as $item) {
        if ($item['all_categories']) {
            $item_categories = explode(', ', $item['all_categories']);
            foreach ($item_categories as $cat_name) {
                $cat_name = trim($cat_name);
                if (!isset($grouped_items[$cat_name])) {
                    $grouped_items[$cat_name] = [];
                }
                $grouped_items[$cat_name][] = $item;
            }
        } else {
            // Items with no categories
            if (!isset($grouped_items['Uncategorized'])) {
                $grouped_items['Uncategorized'] = [];
            }
            $grouped_items['Uncategorized'][] = $item;
        }
    }
} else {
    // Group by selected categories
    // Items can appear in multiple categories
    foreach ($items as $item) {
        $item_category_ids = $item['category_ids'] ? explode(',', $item['category_ids']) : [];
        $item_categories = $item['all_categories'] ? explode(', ', $item['all_categories']) : [];

        // Match category IDs to names
        $category_map = [];
        $category_ids_array = explode(',', $item['category_ids'] ?? '');
        $category_names_array = explode(', ', $item['all_categories'] ?? '');

        for ($i = 0; $i < count($category_ids_array); $i++) {
            if (isset($category_names_array[$i])) {
                $category_map[trim($category_ids_array[$i])] = trim($category_names_array[$i]);
            }
        }

        // Add item to each selected category it belongs to
        foreach ($filter_categories as $selected_cat_id) {
            if (isset($category_map[$selected_cat_id])) {
                $cat_name = $category_map[$selected_cat_id];
                if (!isset($grouped_items[$cat_name])) {
                    $grouped_items[$cat_name] = [];
                }
                $grouped_items[$cat_name][] = $item;
            }
        }
    }
}
?>

<!-- Manage Inventory Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Manage Inventory</h1>
        <p class="text-regular text-gray-600">Track and manage your inventory items and batches</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Inventory Value -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Inventory Value</p>
                    <h3 class="text-title text-2xl text-gray-800">₱<?php echo number_format($stats['total_inventory_value'], 2); ?></h3>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Available Items -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Available Items</p>
                    <h3 class="text-title text-2xl text-gray-800"><?php echo $stats['total_available_items']; ?></h3>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Low Stock Items</p>
                    <h3 class="text-title text-2xl text-warning"><?php echo $stats['low_stock_count']; ?></h3>
                </div>
                <div class="bg-yellow-100 p-2 rounded">
                    <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Out of Stock Items -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Out of Stock Items</p>
                    <h3 class="text-title text-2xl text-danger"><?php echo $stats['out_of_stock_count']; ?></h3>
                </div>
                <div class="bg-red-100 p-2 rounded">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="relative flex-1 min-w-[180px] max-w-xs">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search items..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular text-gray-800 focus:outline-none focus:ring-2 focus:ring-black" />
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Sort -->
            <select
                id="sortFilter"
                class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                onchange="applyFilters()">
                <option value="updated_at_desc" <?php echo $sort_by == 'updated_at_desc' ? 'selected' : ''; ?>>Recently Updated</option>
                <option value="updated_at_asc" <?php echo $sort_by == 'updated_at_asc' ? 'selected' : ''; ?>>Least Updated</option>
                <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                <option value="quantity_asc" <?php echo $sort_by == 'quantity_asc' ? 'selected' : ''; ?>>Quantity (Low to High)</option>
                <option value="quantity_desc" <?php echo $sort_by == 'quantity_desc' ? 'selected' : ''; ?>>Quantity (High to Low)</option>
            </select>

            <!-- Category Filter (Checkbox Dropdown) -->
            <div class="relative">
                <button
                    type="button"
                    id="categoryDropdownButton"
                    onclick="toggleCategoryDropdown()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black bg-white flex items-center gap-2 min-w-[160px] justify-between">
                    <span id="categoryButtonText">
                        <?php
                        if ($view_mode === 'flat') {
                            echo 'No Category Grouping';
                        } elseif ($view_mode === 'all_grouped') {
                            echo 'All Categories';
                        } elseif (!empty($filter_categories)) {
                            echo count($filter_categories) . ' Categories';
                        } else {
                            echo 'Select Categories';
                        }
                        ?>
                    </span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div
                    id="categoryDropdownMenu"
                    class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10 min-w-[220px] max-h-[300px] overflow-y-auto">
                    <div class="p-2">
                        <!-- All Categories Option -->
                        <label class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input
                                type="checkbox"
                                id="allCategoriesCheckbox"
                                class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                                <?php echo $view_mode === 'all_grouped' ? 'checked' : ''; ?>
                                onchange="handleAllCategoriesChange(this)" />
                            <span class="ml-2 text-regular text-gray-700 font-medium">All Categories</span>
                        </label>

                        <!-- No Category Grouping Option -->
                        <label class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input
                                type="checkbox"
                                id="noCategoryCheckbox"
                                class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                                <?php echo $view_mode === 'flat' ? 'checked' : ''; ?>
                                onchange="handleNoCategoryChange(this)" />
                            <span class="ml-2 text-regular text-gray-700 font-medium">No Category Grouping</span>
                        </label>

                        <div class="border-t border-gray-200 my-2"></div>

                        <!-- Individual Categories -->
                        <?php foreach ($categories as $cat): ?>
                            <label class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input
                                    type="checkbox"
                                    class="category-checkbox w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                                    value="<?php echo $cat['category_id']; ?>"
                                    <?php echo in_array($cat['category_id'], $filter_categories) ? 'checked' : ''; ?>
                                    onchange="handleCategoryCheckboxChange()" />
                                <span class="ml-2 text-regular text-gray-700"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Status Filter -->
            <select
                id="statusFilter"
                class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                onchange="applyFilters()">
                <option value="">All Status</option>
                <option value="In Stock" <?php echo $filter_status == 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="Low Stock" <?php echo $filter_status == 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="Out of Stock" <?php echo $filter_status == 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>

            <!-- Spacer to push action buttons to the right -->
            <div class="flex-1"></div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button
                    onclick="openDefaultPriorityModal()"
                    class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-product hover:bg-gray-50 transition-colors whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="hidden sm:inline">Default Priority</span>
                    <span class="sm:hidden">Priority</span>
                </button>
                <button
                    onclick="openAddItemModal()"
                    class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors whitespace-nowrap">
                    + Add Item
                </button>
            </div>
        </div>
    </div>

    <!-- Inventory Items Display -->
    <?php if (empty($flat_items) && empty($grouped_items)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="text-regular text-gray-500">No inventory items found</p>
            <p class="text-label text-gray-400 mt-1">Try adjusting your filters or add a new item</p>
        </div>
    <?php elseif ($view_mode === 'flat'): ?>
        <!-- Flat View -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($flat_items as $item): ?>
                <?php include 'manage-inventory-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Grouped View -->
        <div class="space-y-6">
            <?php foreach ($grouped_items as $category_name => $category_items): ?>
                <div>
                    <!-- Category Header -->
                    <h2 class="text-title text-lg text-gray-800 mb-4 flex items-center">
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><?php echo htmlspecialchars($category_name); ?></span>
                        <span class="ml-2 text-label text-gray-500"><?php echo count($category_items); ?> items</span>
                    </h2>

                    <!-- Items Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($category_items as $item): ?>
                            <?php include 'manage-inventory-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>  <!-- Close main "space-y-6" div -->

<!-- Include All Modals -->
<?php require_once 'modals/batch-modal.php'; ?>
<?php require_once 'modals/edit-item-modal.php'; ?>
<?php require_once 'modals/add-item-modal.php'; ?>
<?php require_once 'modals/add-batch-modal.php'; ?>
<?php require_once 'modals/edit-batch-modal.php'; ?>
<?php require_once 'modals/adjust-quantity-modal.php'; ?>
<?php require_once 'modals/dispose-modal.php'; ?>
<?php require_once 'modals/bulk-priority-modal.php'; ?>

<script>
    // ============================================
    // SHARED STATE VARIABLES
    // ============================================
    let currentItemId = null;
    let currentItemName = null;
    let itemWasModified = false;
    let currentDisposeItemId = null;

    // ============================================
    // CONSOLIDATED ESC KEY HANDLER
    // ============================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const bulkPriorityModal = document.getElementById('bulkPriorityModal');
            const disposeModal = document.getElementById('disposeModal');
            const adjustModal = document.getElementById('adjustQuantityModal');
            const editBatchModal = document.getElementById('editBatchModal');
            const addBatchModal = document.getElementById('addBatchModal');
            const editItemModal = document.getElementById('editItemModal');
            const addItemModal = document.getElementById('addItemModal');
            const batchModal = document.getElementById('batchModal');

            if (bulkPriorityModal && !bulkPriorityModal.classList.contains('hidden')) {
                closeDefaultPriorityModal();
            } else if (disposeModal && !disposeModal.classList.contains('hidden')) {
                closeDisposeModal();
            } else if (adjustModal && !adjustModal.classList.contains('hidden')) {
                closeAdjustQuantityModal();
            } else if (editBatchModal && !editBatchModal.classList.contains('hidden')) {
                closeEditBatchModal();
            } else if (addBatchModal && !addBatchModal.classList.contains('hidden')) {
                closeAddBatchModal();
            } else if (editItemModal && !editItemModal.classList.contains('hidden')) {
                closeEditItemModal();
            } else if (addItemModal && !addItemModal.classList.contains('hidden')) {
                closeAddItemModal();
            } else if (batchModal && !batchModal.classList.contains('hidden')) {
                closeBatchModal();
            }
        }
    });
</script>

<script>
    // ============================================
    // UTILITY FUNCTIONS (ADD BATCH COST CALCULATION)
    // ============================================
    document.getElementById('addBatchInitialQuantity').addEventListener('input', updateCostPerUnit);
    document.getElementById('addBatchTotalCost').addEventListener('input', updateCostPerUnit);

    function updateCostPerUnit() {
        const quantity = parseFloat(document.getElementById('addBatchInitialQuantity').value) || 0;
        const totalCost = parseFloat(document.getElementById('addBatchTotalCost').value) || 0;
        const costPerUnit = quantity > 0 ? (totalCost / quantity) : 0;
        document.getElementById('costPerUnitDisplay').textContent = '₱' + costPerUnit.toFixed(2);
    }
</script>

<script>
    // ============================================
    // ADD ITEM IMAGE PREVIEW
    // ============================================
    document.getElementById('addItemImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or WEBP)');
                e.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size must be less than 5MB');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('addItemImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<!-- Load JS Modules -->
<script src="js/inventory-filter.js"></script>
<script src="js/inventory-items.js"></script>
<script src="js/inventory-batches.js"></script>
<script src="js/inventory-disposal.js"></script>
<script src="js/inventory-bulk-priority.js"></script>