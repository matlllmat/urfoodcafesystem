<!-- Batch Details Modal -->
<div id="batchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl" id="modalItemName">Item Details & Batches</h2>
                <p class="text-regular text-sm text-gray-500" id="modalItemInfo"></p>
            </div>
            <button onclick="closeBatchModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Content - Two Column Layout -->
        <div class="flex-1 overflow-hidden flex">
            <!-- Left Sidebar - Item Details -->
            <div class="w-80 border-r border-gray-200 overflow-y-auto bg-gray-50 p-6">
                <div id="itemDetailsSidebar">
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-regular text-gray-500 mt-2">Loading details...</p>
                    </div>
                </div>
            </div>

            <!-- Right Side - Batches List -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Batch Priority Section -->
                <div class="mb-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Batch Priority</h3>
                        <p class="text-sm text-gray-500 mt-1">Choose how batches should be used when processing orders</p>
                    </div>

                    <!-- Priority Method Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <!-- FIFO Card -->
                        <label class="relative cursor-pointer group">
                            <input
                                type="radio"
                                name="priorityMethod"
                                value="fifo"
                                class="peer sr-only"
                                onchange="handlePriorityMethodChange(this.value)" />
                            <div class="border-2 border-gray-300 peer-checked:border-black peer-checked:bg-gray-50 rounded-lg p-4 transition-all hover:border-gray-400">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold text-gray-800">FIFO</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-black peer-checked:bg-black flex items-center justify-center transition-all">
                                        <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600">First In, First Out</p>
                                <p class="text-xs text-gray-500 mt-1">Oldest batches used first</p>
                            </div>
                        </label>

                        <!-- FEFO Card -->
                        <label id="fefoOption" class="relative cursor-pointer group">
                            <input
                                type="radio"
                                name="priorityMethod"
                                value="fefo"
                                class="peer sr-only"
                                onchange="handlePriorityMethodChange(this.value)" />
                            <div class="border-2 border-gray-300 peer-checked:border-black peer-checked:bg-gray-50 rounded-lg p-4 transition-all hover:border-gray-400 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-semibold text-gray-800">FEFO</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-black peer-checked:bg-black flex items-center justify-center transition-all">
                                        <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600">First Expired, First Out</p>
                                <p class="text-xs text-gray-500 mt-1">Nearest expiry used first</p>
                            </div>
                        </label>

                        <!-- Manual Card -->
                        <label class="relative cursor-pointer group">
                            <input
                                type="radio"
                                name="priorityMethod"
                                value="manual"
                                class="peer sr-only"
                                onchange="handlePriorityMethodChange(this.value)" />
                            <div class="border-2 border-gray-300 peer-checked:border-black peer-checked:bg-gray-50 rounded-lg p-4 transition-all hover:border-gray-400">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                    <span class="font-semibold text-gray-800">Manual</span>
                                    <div class="ml-auto w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-black peer-checked:bg-black flex items-center justify-center transition-all">
                                        <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600">Custom Order</p>
                                <p class="text-xs text-gray-500 mt-1">Drag & drop to reorder</p>
                            </div>
                        </label>
                    </div>

                    <!-- Info message -->
                    <div id="priorityInfoMessage" class="bg-blue-50 border border-blue-200 rounded-lg p-3 hidden">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-blue-800" id="priorityInfoText"></p>
                        </div>
                    </div>
                </div>

                <!-- Batches Section -->
                <div class="mb-4">
                    <h3 class="text-product text-gray-800">Batches</h3>
                    <p class="text-label text-gray-500">Batches are listed in priority order (highest priority first)</p>
                </div>
                <div id="batchesList">
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-regular text-gray-500 mt-2">Loading batches...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-between">
            <div class="flex gap-2">
                <button
                    onclick="openAddBatchForm()"
                    class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                    + Add Batch
                </button>
                <button
                    id="editItemDetailsBtn"
                    onclick="openEditItemModal(currentItemId)"
                    class="bg-white border-2 border-gray-300 text-gray-700 px-4 py-2 rounded-md text-product hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    Edit Item Details
                </button>
            </div>
            <button
                onclick="closeBatchModal()"
                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>