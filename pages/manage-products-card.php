<?php
// Determine status styling
$status_class = '';
switch ($product['status']) {
    case 'Available':
        $status_class = 'bg-green-100 text-success';
        break;
    case 'Unavailable':
        $status_class = 'bg-yellow-100 text-warning';
        break;
    case 'Discontinued':
        $status_class = 'bg-red-100 text-danger';
        break;
}

// Determine profit color
$profit = floatval($product['profit']);
$profit_class = $profit >= 0 ? 'text-success' : 'text-danger';
$margin = floatval($product['profit_margin']);
$is_out_of_stock = intval($product['out_of_stock_count']) > 0 && $product['status'] === 'Available';

// Override status styling if Available but out of stock
if ($is_out_of_stock) {
    $status_class = 'bg-gray-100 text-gray-400 line-through';
}
?>

<div class="product-card bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition-shadow cursor-pointer relative"
    onclick="openProductDetailModal('<?php echo $product['product_id']; ?>')"
    data-product-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>">

    <!-- Status Badge -->
    <div class="absolute top-2 right-2">
        <span class="text-xs px-2 py-1 rounded <?php echo $status_class; ?>">
            <?php echo $product['status']; ?>
        </span>
    </div>

    <!-- Product Image -->
    <div class="aspect-square bg-support rounded-md mb-3 flex items-center justify-center overflow-hidden relative">
        <img
            src="../assets/images/product/<?php echo htmlspecialchars($product['image_filename']); ?>"
            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
            class="w-full h-full object-cover<?php echo ($is_out_of_stock || $product['status'] === 'Unavailable' || $product['status'] === 'Discontinued') ? ' opacity-50' : ''; ?>"
            onerror="this.src='../assets/images/product/default-product.png'" />
        <?php if ($is_out_of_stock): ?>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Out of Stock</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Details -->
    <div>
        <h3 class="text-product text-gray-800 mb-1 truncate" title="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php echo htmlspecialchars($product['product_name']); ?>
        </h3>
        <?php if (!empty($product['primary_category_name'])): ?>
            <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600 mb-1"><?php echo htmlspecialchars($product['primary_category_name']); ?></span>
        <?php endif; ?>

        <!-- Price and Cost -->
        <div class="flex items-center justify-between mb-2">
            <span class="text-regular text-gray-800 font-medium">
                &#8369;<?php echo number_format($product['price'], 2); ?>
            </span>
            <span class="text-label text-gray-500">
                Cost: &#8369;<?php echo number_format($product['calculated_cost'], 2); ?>
            </span>
        </div>

        <!-- Profit and Margin -->
        <div class="flex items-center justify-between">
            <span class="text-label <?php echo $profit_class; ?>">
                Profit: &#8369;<?php echo number_format($profit, 2); ?>
            </span>
            <span class="text-label text-gray-500">
                <?php echo number_format($margin, 1); ?>% margin
            </span>
        </div>

        <!-- Requirements count -->
        <p class="text-label text-gray-400 mt-1">
            <?php echo $product['requirement_count']; ?> ingredient<?php echo $product['requirement_count'] != 1 ? 's' : ''; ?>
        </p>
    </div>
</div>
