<!-- Add Item Modal -->
<div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Add Item Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Add New Inventory Item</h2>
                <p class="text-regular text-sm text-gray-500">Create a new item in your inventory</p>
            </div>
            <button onclick="closeAddItemModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Add Item Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="addItemForm" class="space-y-6">
                <!-- Image Upload -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Item Image</label>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <img id="addItemImagePreview" src="../assets/images/inventory-item/default-item.png" alt="Item preview" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                        </div>
                        <div class="flex-1">
                            <input type="file" id="addItemImageInput" accept="image/*" class="hidden">
                            <button
                                type="button"
                                onclick="document.getElementById('addItemImageInput').click()"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-regular hover:bg-gray-50 transition-colors">
                                Choose Image
                            </button>
                            <p class="text-label text-gray-500 mt-2">Optional - Recommended: Square image, at least 400x400px</p>
                            <p class="text-label text-gray-500">Formats: JPG, PNG, WEBP (Max 5MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Item Name -->
                <div>
                    <label for="addItemName" class="block text-product text-gray-700 mb-2">Item Name *</label>
                    <input
                        type="text"
                        id="addItemName"
                        required
                        placeholder="e.g., Fresh Tomatoes, Cooking Oil, Paper Cups"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Categories -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Categories * (Select at least one)</label>
                    <div id="addItemCategoriesContainer" class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                        <div class="text-center py-4">
                            <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-label text-gray-500 mt-2">Loading categories...</p>
                        </div>
                    </div>
                    <p class="text-label text-gray-500 mt-2">Select one or more categories. Mark one as primary.</p>
                </div>

                <!-- Quantity Unit and Reorder Level -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Quantity Unit -->
                    <div>
                        <label for="addItemQuantityUnit" class="block text-product text-gray-700 mb-2">Quantity Unit *</label>
                        <input
                            type="text"
                            id="addItemQuantityUnit"
                            required
                            placeholder="e.g., pcs, kg, liters, boxes"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>

                    <!-- Reorder Level -->
                    <div>
                        <label for="addItemReorderLevel" class="block text-product text-gray-700 mb-2">Reorder Level *</label>
                        <input
                            type="number"
                            id="addItemReorderLevel"
                            required
                            step="0.01"
                            min="0"
                            value="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                        <p class="text-label text-gray-500 mt-1">Alert when quantity falls below this level (can be 0)</p>
                    </div>
                </div>

                <!-- Optional Initial Batch -->
                <div class="border-t border-gray-200 pt-6">
                    <label class="flex items-center cursor-pointer mb-4">
                        <input
                            type="checkbox"
                            id="addInitialBatchCheckbox"
                            onchange="toggleInitialBatchSection()"
                            class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black">
                        <span class="ml-2 text-product text-gray-700">Add initial batch (optional)</span>
                    </label>

                    <!-- Initial Batch Section (Hidden by default) -->
                    <div id="addInitialBatchSection" class="hidden space-y-4 bg-gray-50 p-4 rounded-lg">
                        <p class="text-label text-gray-600">Add the first batch of inventory for this item</p>

                        <!-- Batch Title -->
                        <div>
                            <label for="addItemBatchTitle" class="block text-product text-gray-700 mb-2">Batch Title</label>
                            <input
                                type="text"
                                id="addItemBatchTitle"
                                placeholder="e.g., Initial Stock - January 2024"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                        </div>

                        <!-- Quantity and Cost -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="addItemBatchInitialQuantity" class="block text-product text-gray-700 mb-2">Initial Quantity</label>
                                <input
                                    type="number"
                                    id="addItemBatchInitialQuantity"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0.00"
                                    oninput="updateAddItemCostPerUnit()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                            </div>

                            <div>
                                <label for="addItemBatchTotalCost" class="block text-product text-gray-700 mb-2">Total Cost (₱)</label>
                                <input
                                    type="number"
                                    id="addItemBatchTotalCost"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    oninput="updateAddItemCostPerUnit()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>

                        <!-- Cost Per Unit Display -->
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-regular text-gray-700">Cost Per Unit:</span>
                                <span id="addItemCostPerUnitDisplay" class="text-regular text-gray-800">₱0.00</span>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="addItemBatchObtainedDate" class="block text-product text-gray-700 mb-2">Obtained Date</label>
                                <input
                                    type="date"
                                    id="addItemBatchObtainedDate"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                            </div>

                            <div>
                                <label for="addItemBatchExpirationDate" class="block text-product text-gray-700 mb-2">Expiration Date</label>
                                <input
                                    type="date"
                                    id="addItemBatchExpirationDate"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add Item Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeAddItemModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveNewItem()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Create Item
            </button>
        </div>
    </div>
</div>
