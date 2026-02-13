<!-- Edit Batch Modal -->
<div id="editBatchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Edit Batch Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Edit Batch</h2>
                <p class="text-regular text-sm text-gray-500">Update batch information</p>
            </div>
            <button onclick="closeEditBatchModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Edit Batch Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="editBatchForm" class="space-y-6">
                <!-- Warning about Initial Quantity -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-regular text-blue-800 font-medium">Note</p>
                            <p class="text-label text-gray-600">Changing the initial quantity will proportionally adjust the current quantity to maintain accurate inventory levels.</p>
                        </div>
                    </div>
                </div>

                <!-- Batch Title -->
                <div>
                    <label for="editBatchTitle" class="block text-product text-gray-700 mb-2">Batch Title *</label>
                    <input
                        type="text"
                        id="editBatchTitle"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Initial Quantity and Total Cost Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Initial Quantity -->
                    <div>
                        <label for="editBatchInitialQuantity" class="block text-product text-gray-700 mb-2">Initial Quantity *</label>
                        <input
                            type="number"
                            id="editBatchInitialQuantity"
                            required
                            step="0.01"
                            min="0.01"
                            oninput="updateEditCostPerUnit()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>

                    <!-- Total Cost -->
                    <div>
                        <label for="editBatchTotalCost" class="block text-product text-gray-700 mb-2">Total Cost (₱) *</label>
                        <input
                            type="number"
                            id="editBatchTotalCost"
                            required
                            step="0.01"
                            min="0"
                            oninput="updateEditCostPerUnit()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>

                <!-- Cost Per Unit Display (Calculated) -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-product text-gray-700">Cost Per Unit:</span>
                        <span id="editCostPerUnitDisplay" class="text-product text-gray-800">₱0.00</span>
                    </div>
                </div>

                <!-- Dates Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Obtained Date -->
                    <div>
                        <label for="editBatchObtainedDate" class="block text-product text-gray-700 mb-2">Obtained Date *</label>
                        <input
                            type="date"
                            id="editBatchObtainedDate"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>

                    <!-- Expiration Date -->
                    <div>
                        <label for="editBatchExpirationDate" class="block text-product text-gray-700 mb-2">Expiration Date</label>
                        <input
                            type="date"
                            id="editBatchExpirationDate"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>

                <!-- Hidden field for batch ID -->
                <input type="hidden" id="editBatchId">
            </form>
        </div>

        <!-- Edit Batch Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeEditBatchModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveEditBatch()"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>