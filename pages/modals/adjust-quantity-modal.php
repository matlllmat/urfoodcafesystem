<!-- Adjust Quantity Modal -->
<div id="adjustQuantityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Adjust Quantity Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Adjust Batch Quantity</h2>
                <p class="text-regular text-sm text-gray-500">Reconcile system quantity with physical inventory</p>
            </div>
            <button onclick="closeAdjustQuantityModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Adjust Quantity Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="adjustQuantityForm" class="space-y-6">
                <!-- Warning -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-regular text-danger font-medium">Important Warning</p>
                            <p class="text-label text-gray-700">Only use this feature to correct the system when your physical inventory count does not match what's recorded. This will directly modify the current quantity without creating a transaction record. For normal inventory usage, use the proper consumption/usage tracking features.</p>
                        </div>
                    </div>
                </div>

                <!-- Current Quantity Display -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-product text-gray-700">Current System Quantity:</span>
                        <span id="adjustCurrentQuantity" class="text-product text-gray-800 text-xl font-semibold">0.00</span>
                    </div>
                </div>

                <!-- Adjustment Type -->
                <div>
                    <label for="adjustmentType" class="block text-product text-gray-700 mb-2">Adjustment Type *</label>
                    <select
                        id="adjustmentType"
                        required
                        onchange="updateNewQuantityDisplay()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="add">Add to Current Quantity</option>
                        <option value="subtract">Subtract from Current Quantity</option>
                        <option value="set">Set to Exact Value</option>
                    </select>
                </div>

                <!-- Adjustment Amount -->
                <div>
                    <label for="adjustmentAmount" class="block text-product text-gray-700 mb-2">Amount *</label>
                    <input
                        type="number"
                        id="adjustmentAmount"
                        required
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        oninput="updateNewQuantityDisplay()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-label text-gray-500 mt-1">Enter the amount to adjust or the exact value to set</p>
                </div>

                <!-- New Quantity Preview -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-product text-gray-700">New Quantity Will Be:</span>
                        <span id="newQuantityDisplay" class="text-product text-blue-800 text-xl font-semibold">0.00</span>
                    </div>
                </div>

                <!-- Zero Quantity Warning -->
                <div id="zeroQuantityWarning" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-label text-gray-700">This adjustment will set the quantity to zero, marking the batch as depleted.</p>
                    </div>
                </div>

                <!-- Reason -->
                <div>
                    <label for="adjustmentReason" class="block text-product text-gray-700 mb-2">Reason for Adjustment *</label>
                    <textarea
                        id="adjustmentReason"
                        required
                        rows="3"
                        placeholder="e.g., Physical count shows 10 units instead of recorded 12 units. Discrepancy due to spillage."
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black resize-none"></textarea>
                    <p class="text-label text-gray-500 mt-1">Please provide a detailed explanation for audit purposes</p>
                </div>

                <!-- Hidden field for batch ID -->
                <input type="hidden" id="adjustBatchId">
            </form>
        </div>

        <!-- Adjust Quantity Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeAdjustQuantityModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveQuantityAdjustment()"
                class="text-white px-6 py-2 rounded-md text-product transition-colors"
                style="background-color: #B71C1C;"
                onmouseenter="this.style.backgroundColor='#8b0000'"
                onmouseleave="this.style.backgroundColor='#B71C1C'">
                Confirm Adjustment
            </button>
        </div>
    </div>
</div>