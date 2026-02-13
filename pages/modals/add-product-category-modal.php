<!-- Add Product Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-title text-xl">Add Category</h2>
            <button onclick="closeAddCategoryModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <div class="p-6 space-y-4">
            <div>
                <label for="addCategoryName" class="block text-product text-gray-700 mb-2">Category Name *</label>
                <input
                    type="text"
                    id="addCategoryName"
                    placeholder="e.g., Burgers, Beverages, Desserts"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div>
                <label for="addCategoryDescription" class="block text-product text-gray-700 mb-2">Description</label>
                <textarea
                    id="addCategoryDescription"
                    rows="3"
                    placeholder="Optional description for this category"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black resize-none"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeAddCategoryModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveNewCategory()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Create Category
            </button>
        </div>
    </div>
</div>
