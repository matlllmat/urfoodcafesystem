<!-- Edit Item Modal -->
<div id="editItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Edit Modal Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Edit Item Details</h2>
                <p class="text-regular text-sm text-gray-500">Update inventory item information</p>
            </div>
            <button onclick="closeEditItemModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Edit Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="editItemForm" class="space-y-6">
                <!-- Image Upload -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Item Image</label>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <img id="editItemImagePreview" src="" alt="Item preview" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                        </div>
                        <div class="flex-1">
                            <input type="file" id="editItemImageInput" accept="image/*" class="hidden">
                            <button
                                type="button"
                                onclick="document.getElementById('editItemImageInput').click()"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-regular hover:bg-gray-50 transition-colors">
                                Choose Image
                            </button>
                            <p class="text-label text-gray-500 mt-2">Recommended: Square image, at least 400x400px</p>
                            <p class="text-label text-gray-500">Formats: JPG, PNG, WEBP</p>
                        </div>
                    </div>
                </div>

                <!-- Item Name -->
                <div>
                    <label for="editItemName" class="block text-product text-gray-700 mb-2">Item Name *</label>
                    <input
                        type="text"
                        id="editItemName"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Categories -->
                <div>
                    <label class="block text-product text-gray-700 mb-2">Categories</label>
                    <div id="editCategoriesContainer" class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
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

                <!-- Quantity Unit -->
                <div>
                    <label for="editQuantityUnit" class="block text-product text-gray-700 mb-2">Quantity Unit *</label>
                    <input
                        type="text"
                        id="editQuantityUnit"
                        required
                        placeholder="e.g., pcs, kg, liters"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Reorder Level -->
                <div>
                    <label for="editReorderLevel" class="block text-product text-gray-700 mb-2">Reorder Level *</label>
                    <input
                        type="number"
                        id="editReorderLevel"
                        required
                        step="0.01"
                        min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-label text-gray-500 mt-1">Alert when quantity falls below this level</p>
                </div>

                <!-- Hidden field for item ID -->
                <input type="hidden" id="editItemId">
            </form>
        </div>

        <!-- Edit Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeEditItemModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveItemEdits()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>