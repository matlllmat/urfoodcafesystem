<!-- Add Batch Modal -->
<div id="addBatchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Add Batch Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Add New Batch</h2>
                <p class="text-regular text-sm text-gray-500">Add a new inventory batch</p>
            </div>
            <button onclick="closeAddBatchModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Add Batch Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="addBatchForm" class="space-y-6">
                <!-- Warning for Expiration -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-regular text-warning font-medium">Important Reminder</p>
                            <p class="text-label text-gray-600">Please always include expiration dates for perishable food items to ensure proper inventory management and food safety.</p>
                        </div>
                    </div>
                </div>

                <!-- Batch Title -->
                <div>
                    <label for="addBatchTitle" class="block text-product text-gray-700 mb-2">Batch Title *</label>
                    <input
                        type="text"
                        id="addBatchTitle"
                        required
                        placeholder="e.g., Fresh Tomatoes - January 2024"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Initial Quantity and Total Cost Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Initial Quantity -->
                    <div>
                        <label for="addBatchInitialQuantity" class="block text-product text-gray-700 mb-2">Initial Quantity *</label>
                        <input
                            type="number"
                            id="addBatchInitialQuantity"
                            required
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>

                    <!-- Total Cost -->
                    <div>
                        <label for="addBatchTotalCost" class="block text-product text-gray-700 mb-2">Total Cost (₱) *</label>
                        <input
                            type="number"
                            id="addBatchTotalCost"
                            required
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>

                <!-- Cost Per Unit Display (Calculated) -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-product text-gray-700">Cost Per Unit:</span>
                        <span id="costPerUnitDisplay" class="text-product text-gray-800">₱0.00</span>
                    </div>
                </div>

                <!-- Dates Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Obtained Date -->
                    <div>
                        <label for="addBatchObtainedDate" class="block text-product text-gray-700 mb-2">Obtained Date *</label>
                        <input
                            type="date"
                            id="addBatchObtainedDate"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>

                    <!-- Expiration Date -->
                    <div>
                        <label for="addBatchExpirationDate" class="block text-product text-gray-700 mb-2">Expiration Date</label>
                        <input
                            type="date"
                            id="addBatchExpirationDate"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                        <p class="text-label text-gray-500 mt-1">Optional but recommended</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add Batch Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeAddBatchModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveNewBatch()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Add Batch
            </button>
        </div>
    </div>
</div>