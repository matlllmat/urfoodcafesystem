<?php
require_once __DIR__ . '/../config/db.php';

$per_page = 20;
$current_page = max(1, (int)($_GET['p'] ?? 1));

// Count total categories
$count_result = $conn->query("SELECT COUNT(*) as total FROM product_categories");
$total_categories_all = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
$total_pages = $total_categories_all > 0 ? (int) ceil($total_categories_all / $per_page) : 1;
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $per_page;

// Get categories for current page with product counts
$query = "
    SELECT
        pc.category_id,
        pc.category_name,
        pc.description,
        pc.is_active,
        pc.display_order,
        pc.created_at,
        pc.updated_at,
        COUNT(pcm.product_id) as product_count
    FROM product_categories pc
    LEFT JOIN product_category_map pcm ON pc.category_id = pcm.category_id
    GROUP BY pc.category_id
    ORDER BY pc.display_order ASC, pc.category_name ASC
    LIMIT " . (int) $per_page . " OFFSET " . (int) $offset;

$result = $conn->query($query);
$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Stats: total/active/inactive (for current page only for display; for stats cards we use full counts)
$count_active = $conn->query("SELECT COUNT(*) as c FROM product_categories WHERE is_active = 1");
$count_inactive = $conn->query("SELECT COUNT(*) as c FROM product_categories WHERE is_active = 0");
$active_count = $count_active ? (int) $count_active->fetch_assoc()['c'] : 0;
$inactive_count = $count_inactive ? (int) $count_inactive->fetch_assoc()['c'] : 0;
$total_categories = $total_categories_all;
?>

<!-- Product Categories Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Product Categories</h1>
        <p class="text-regular text-gray-600">Create, manage, and organize product categories</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Categories</p>
                    <h3 class="text-title text-2xl text-gray-800"><?php echo $total_categories; ?></h3>
                </div>
                <div class="bg-blue-100 p-2 rounded flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Active</p>
                    <h3 class="text-title text-2xl text-success"><?php echo $active_count; ?></h3>
                </div>
                <div class="bg-green-100 p-2 rounded flex-shrink-0">
                    <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Inactive</p>
                    <h3 class="text-title text-2xl text-gray-500"><?php echo $inactive_count; ?></h3>
                </div>
                <div class="bg-gray-100 p-2 rounded flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <p class="text-regular text-gray-600">Manage your product categories below</p>
            <button
                onclick="openAddCategoryModal()"
                class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors whitespace-nowrap">
                + Add Category
            </button>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <?php if (empty($categories)): ?>
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p class="text-regular text-gray-500">No categories yet</p>
                <p class="text-label text-gray-400 mt-1">Add your first product category to get started</p>
            </div>
        <?php else: ?>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-label text-gray-500 font-medium">Category</th>
                        <th class="text-left px-6 py-3 text-label text-gray-500 font-medium">Description</th>
                        <th class="text-center px-6 py-3 text-label text-gray-500 font-medium">Products</th>
                        <th class="text-center px-6 py-3 text-label text-gray-500 font-medium">Status</th>
                        <th class="text-right px-6 py-3 text-label text-gray-500 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-product text-gray-800"><?php echo htmlspecialchars($cat['category_name']); ?></p>
                                    <p class="text-label text-gray-400"><?php echo htmlspecialchars($cat['category_id']); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-regular text-gray-600"><?php echo htmlspecialchars($cat['description'] ?: '-'); ?></p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    type="button"
                                    data-category-products
                                    data-category-id="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                    data-category-name="<?php echo htmlspecialchars($cat['category_name']); ?>"
                                    class="inline-flex items-center gap-1 text-regular text-blue-600 hover:text-blue-800 hover:underline transition-colors"
                                    title="View & manage products">
                                    <?php echo $cat['product_count']; ?>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($cat['is_active']): ?>
                                    <span class="text-xs px-2 py-1 rounded bg-green-100 text-success">Active</span>
                                <?php else: ?>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-500">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        onclick="openEditCategoryModal('<?php echo $cat['category_id']; ?>', '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['description'] ?? '')); ?>', <?php echo $cat['is_active'] ? 'true' : 'false'; ?>)"
                                        class="text-gray-400 hover:text-gray-600 transition-colors p-1"
                                        title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        onclick="confirmDeleteCategory('<?php echo $cat['category_id']; ?>', '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>')"
                                        class="text-gray-400 hover:text-danger transition-colors p-1"
                                        title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_categories_all > 0): ?>
                <?php
                $base_url = 'main.php?page=product-categories';
                $from = $offset + 1;
                $to = min($offset + $per_page, $total_categories_all);
                ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-600">
                        Showing <?php echo $from; ?>–<?php echo $to; ?> of <?php echo $total_categories_all; ?>
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if ($current_page > 1): ?>
                            <a href="<?php echo $base_url . '&p=' . ($current_page - 1); ?>" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition-colors">Previous</a>
                        <?php endif; ?>
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++):
                            $active = $i === $current_page ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-700 hover:bg-gray-100';
                        ?>
                            <a href="<?php echo $base_url . '&p=' . $i; ?>" class="px-3 py-1.5 border rounded-md text-sm transition-colors <?php echo $active; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <a href="<?php echo $base_url . '&p=' . ($current_page + 1); ?>" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition-colors">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Include Modals -->
<?php require_once 'modals/add-product-category-modal.php'; ?>
<?php require_once 'modals/edit-product-category-modal.php'; ?>

<!-- Category Products Modal -->
<div id="categoryProductsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full max-h-[85vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-lg">Category Products</h2>
                <p class="text-label text-sm text-gray-500" id="cpModalSubtitle">Products in this category</p>
            </div>
            <button onclick="closeCategoryProductsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Product List -->
        <div class="flex-1 overflow-y-auto p-5">
            <div id="cpProductsList">
                <p class="text-center text-gray-400 py-6 text-regular">Loading...</p>
            </div>
        </div>

        <!-- Add Product Section -->
        <div class="p-5 border-t border-gray-200">
            <p class="text-label text-gray-500 mb-2">Add a product</p>
            <div class="flex gap-2">
                <select id="cpAddProductSelect" class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Select a product...</option>
                </select>
                <button
                    onclick="addProductToCurrentCategory()"
                    class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors whitespace-nowrap">
                    Add
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button
                onclick="closeCategoryProductsModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Load JS -->
<script src="js/product-categories.js"></script>
