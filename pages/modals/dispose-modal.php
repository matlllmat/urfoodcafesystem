<!-- Dispose Expired Batches Modal -->
<div id="disposeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Dispose Modal Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Dispose Expired Items</h2>
                <p class="text-regular text-sm text-gray-500" id="disposeItemName"></p>
            </div>
            <button onclick="closeDisposeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Dispose Modal Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-regular text-danger font-medium">Permanent Action</p>
                        <p class="text-label text-gray-700">This will set the quantity of selected batches to zero and record the disposal. This action cannot be undone.</p>
                    </div>
                </div>
            </div>

            <!-- Expired Batches List -->
            <div class="mb-6">
                <h3 class="text-product text-gray-800 mb-3">Expired Batches</h3>
                <div id="expiredBatchesList">
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-regular text-gray-500 mt-2">Loading expired batches...</p>
                    </div>
                </div>
            </div>

            <!-- Disposal Reason -->
            <div>
                <label for="disposalReason" class="block text-product text-gray-700 mb-2">Disposal Reason *</label>
                <textarea
                    id="disposalReason"
                    rows="3"
                    placeholder="e.g., Expired on 2025-01-15. Items showed signs of spoilage and were unsafe for consumption."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black resize-none"></textarea>
                <p class="text-label text-gray-500 mt-1">Please provide a detailed explanation for audit purposes</p>
            </div>
        </div>

        <!-- Dispose Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-between items-center">
            <div class="text-regular text-gray-600">
                <span id="disposeTotalQuantity">0</span> items will be disposed
            </div>
            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="closeDisposeModal()"
                    class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button
                    type="button"
                    onclick="confirmDisposal()"
                    style="background-color: #B71C1C; color: #ffffff;"
                    onmouseover="this.style.backgroundColor='#8b0000'"
                    onmouseout="this.style.backgroundColor='#B71C1C'"
                    class="px-6 py-2 rounded-md text-product transition-colors">
                    Confirm Disposal
                </button>
            </div>
        </div>
    </div>
</div>