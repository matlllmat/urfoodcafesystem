<!-- Add Product Modal -->
<div id="addProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Add New Product</h2>
                <p class="text-regular text-sm text-gray-500">Create a new product with its recipe</p>
            </div>
            <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="addProductForm" class="space-y-6">
                <!-- Image Upload -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Product Image</label>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <img id="addProductImagePreview" src="../assets/images/product/default-product.png" alt="Product preview" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                        </div>
                        <div class="flex-1">
                            <input type="file" id="addProductImageInput" accept="image/*" class="hidden">
                            <button
                                type="button"
                                onclick="document.getElementById('addProductImageInput').click()"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-regular hover:bg-gray-50 transition-colors">
                                Choose Image
                            </button>
                            <p class="text-label text-gray-500 mt-2">Optional - Recommended: Square image, at least 400x400px</p>
                            <p class="text-label text-gray-500">Formats: JPG, PNG, WEBP (Max 5MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Product Name -->
                <div>
                    <label for="addProductName" class="block text-product text-gray-700 mb-2">Product Name *</label>
                    <input
                        type="text"
                        id="addProductName"
                        required
                        placeholder="e.g., Original Coffee, Overload Burger"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Price and Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="addProductPrice" class="block text-product text-gray-700 mb-2">Selling Price (&#8369;) *</label>
                        <input
                            type="number"
                            id="addProductPrice"
                            required
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label for="addProductStatus" class="block text-product text-gray-700 mb-2">Status *</label>
                        <select
                            id="addProductStatus"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                            <option value="Discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>

                <!-- Categories -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Categories * (Select at least one)</label>
                    <div id="addProductCategoriesContainer" class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                        <p class="text-label text-gray-500">Loading categories...</p>
                    </div>
                    <p class="text-label text-gray-500 mt-2">Select one or more categories. Mark one as primary.</p>
                </div>

                <!-- Recipe Requirements Section -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-product text-gray-700">Recipe Requirements</label>
                    </div>
                    <p class="text-label text-gray-500 mb-4">Add inventory items needed to make this product</p>

                    <!-- Requirements Rows Container -->
                    <div id="addProductRequirements" class="space-y-3">
                        <!-- Dynamic rows will be added here -->
                    </div>

                    <!-- Add Requirement Button -->
                    <button type="button" onclick="addRequirementRow('add')"
                        class="mt-3 text-regular text-gray-600 hover:text-gray-800 flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Ingredient
                    </button>

                    <!-- Cost Preview -->
                    <div id="addProductCostPreview" class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-regular text-gray-700">Estimated Cost:</span>
                            <span id="addProductEstimatedCost" class="text-regular text-gray-800 font-medium">&#8369;0.00</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-label text-gray-500">Estimated Profit:</span>
                            <span id="addProductEstimatedProfit" class="text-label text-gray-600">&#8369;0.00</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeAddProductModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveNewProduct()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Create Product
            </button>
        </div>
    </div>
</div>
