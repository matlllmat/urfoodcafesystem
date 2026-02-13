<?php
// Check if item has expired batches
$has_expired = ($item['expired_quantity'] > 0);
$border_class = $has_expired ? 'border-red-600 border-2' : 'border-gray-200';
?>

<div class="item-card bg-white rounded-lg border <?php echo $border_class; ?> p-4 hover:shadow-lg transition-shadow cursor-pointer relative"
    onclick="openBatchModal('<?php echo $item['item_id']; ?>', '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>')"
    data-item-name="<?php echo strtolower($item['item_name']); ?>">

    <!-- Expired Badge (Top Priority) -->
    <?php if ($has_expired): ?>
        <div class="absolute top-2 right-2 text-white px-2 py-1 rounded text-xs flex items-center gap-1" style="background-color: #B71C1C;">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>Expired Stock</span>
        </div>
    <!-- Expiring Soon Warning (if no expired stock) -->
    <?php elseif ($item['has_expiring_soon']): ?>
        <div class="absolute top-2 right-2 text-white px-2 py-1 rounded text-xs flex items-center gap-1" style="background-color: #EDAE49;">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>Expiring Soon</span>
        </div>
    <?php endif; ?>

    <!-- Item Image -->
    <div class="aspect-square bg-support rounded-md mb-3 flex items-center justify-center overflow-hidden">
        <img
            src="../assets/images/inventory-item/<?php echo htmlspecialchars($item['image_filename']); ?>"
            alt="<?php echo htmlspecialchars($item['item_name']); ?>"
            class="w-full h-full object-cover"
            onerror="this.src='../assets/images/inventory-item/default-item.png'" />
    </div>

    <!-- Item Details -->
    <div>
        <h3 class="text-product text-gray-800 mb-1 truncate" title="<?php echo htmlspecialchars($item['item_name']); ?>">
            <?php echo htmlspecialchars($item['item_name']); ?>
        </h3>

        <!-- Categories Tags -->
        <?php if ($item['all_categories']): ?>
            <div class="flex flex-wrap gap-1 mb-2">
                <?php
                $item_categories = explode(', ', $item['all_categories']);
                foreach (array_slice($item_categories, 0, 2) as $cat):
                ?>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                        <?php echo htmlspecialchars($cat); ?>
                    </span>
                <?php endforeach; ?>
                <?php if (count($item_categories) > 2): ?>
                    <span class="text-xs text-gray-400">+<?php echo count($item_categories) - 2; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quantity and Status -->
        <div class="mb-2">
            <!-- Available Quantity (Primary Display) -->
            <div class="flex items-center justify-between mb-1">
                <span class="text-regular text-gray-800 font-medium">
                    <?php echo number_format($item['available_quantity'], 2); ?> <?php echo htmlspecialchars($item['quantity_unit']); ?>
                </span>
                <span class="text-xs px-2 py-1 rounded <?php
                    echo $item['stock_status'] == 'In Stock' ? 'bg-green-100 text-success' : 
                         ($item['stock_status'] == 'Low Stock' ? 'bg-yellow-100 text-warning' : 'bg-red-100 text-danger');
                ?>">
                    <?php echo $item['stock_status']; ?>
                </span>
            </div>
            
            <!-- Expired Quantity Warning (if exists) -->
            <?php if ($has_expired): ?>
                <div class="flex items-center gap-1 text-xs text-danger">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span><?php echo number_format($item['expired_quantity'], 2); ?> <?php echo htmlspecialchars($item['quantity_unit']); ?> expired</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Value -->
        <p class="text-label text-gray-500">
            Value: ₱<?php echo number_format($item['total_value'], 2); ?>
        </p>
    </div>
</div>