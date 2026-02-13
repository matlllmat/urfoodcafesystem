<!-- Edit Product Category Modal -->
<div id="editCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Edit Category</h2>
                <p id="editCategorySubtitle" class="text-label text-gray-500 mt-1"></p>
            </div>
            <button onclick="closeEditCategoryModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <div class="p-6 space-y-4">
            <input type="hidden" id="editCategoryId">
            <div>
                <label for="editCategoryName" class="block text-product text-gray-700 mb-2">Category Name *</label>
                <input
                    type="text"
                    id="editCategoryName"
                    placeholder="e.g., Burgers, Beverages, Desserts"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div>
                <label for="editCategoryDescription" class="block text-product text-gray-700 mb-2">Description</label>
                <textarea
                    id="editCategoryDescription"
                    rows="3"
                    placeholder="Optional description for this category"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black resize-none"></textarea>
            </div>
            <div>
                <label class="block text-product text-gray-700 mb-2">Status</label>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="editCategoryActive" class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black">
                    <span class="ml-2 text-regular text-gray-700">Active</span>
                </label>
                <p class="text-label text-gray-500 mt-1">Inactive categories won't appear when assigning categories to products</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeEditCategoryModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveCategoryEdits()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>
