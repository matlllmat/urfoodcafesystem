<!-- Default Priority Modal -->
<div id="bulkPriorityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Default Batch Priority</h2>
                    <p class="text-sm text-gray-500 mt-1">Set the default priority method for new inventory items</p>
                </div>
                <button onclick="closeDefaultPriorityModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="p-6">
            <form id="bulkPriorityForm">
                <!-- Current Default Setting Display -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Current default setting</p>
                            <p class="text-lg font-semibold text-gray-800 mt-1" id="currentDefaultMethod">Loading...</p>
                        </div>
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Priority Method Selection -->
                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 mb-3 block">Select New Default Priority Method:</label>
                    <div class="space-y-3">
                        <!-- FIFO Option -->
                        <label class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-gray-400 transition-colors">
                            <input
                                type="radio"
                                name="bulkPriorityMethod"
                                value="fifo"
                                class="peer sr-only"
                                checked />
                            <div class="peer-checked:border-black peer-checked:bg-gray-50 absolute inset-0 border-2 rounded-lg pointer-events-none"></div>
                            <div class="flex items-start gap-3 relative z-10">
                                <svg class="w-6 h-6 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <span class="text-base font-semibold text-gray-800">FIFO - First In, First Out</span>
                                    <p class="text-sm text-gray-600 mt-1">Oldest batches are used first based on obtained date. Best for non-perishable items.</p>
                                </div>
                            </div>
                        </label>

                        <!-- FEFO Option -->
                        <label class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-gray-400 transition-colors">
                            <input
                                type="radio"
                                name="bulkPriorityMethod"
                                value="fefo"
                                class="peer sr-only" />
                            <div class="peer-checked:border-black peer-checked:bg-gray-50 absolute inset-0 border-2 rounded-lg pointer-events-none"></div>
                            <div class="flex items-start gap-3 relative z-10">
                                <svg class="w-6 h-6 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div class="flex-1">
                                    <span class="text-base font-semibold text-gray-800">FEFO - First Expired, First Out</span>
                                    <p class="text-sm text-gray-600 mt-1">Batches nearest to expiration are used first. Best for perishable items like dairy and produce.</p>
                                </div>
                            </div>
                        </label>

                        <!-- Manual Option -->
                        <label class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-gray-400 transition-colors">
                            <input
                                type="radio"
                                name="bulkPriorityMethod"
                                value="manual"
                                class="peer sr-only" />
                            <div class="peer-checked:border-black peer-checked:bg-gray-50 absolute inset-0 border-2 rounded-lg pointer-events-none"></div>
                            <div class="flex items-start gap-3 relative z-10">
                                <svg class="w-6 h-6 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                                <div class="flex-1">
                                    <span class="text-base font-semibold text-gray-800">Manual - Custom Order</span>
                                    <p class="text-sm text-gray-600 mt-1">You can manually drag and drop batches to set custom priority. Best when you need specific control.</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Info: Default only affects new items -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">Save Default: only affects new items</p>
                            <p class="mt-1">Use &quot;Save Default Setting&quot; below to set the priority method for items you add in the future. Existing items stay unchanged.</p>
                        </div>
                    </div>
                </div>

                <!-- Apply to All Items -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-amber-800 flex-1">
                            <p class="font-medium">Apply to all items now</p>
                            <p class="mt-1">Use &quot;Apply to All Items&quot; to set the selected priority method for every inventory item, including those currently on manual order. Batch order will be recalculated for FIFO/FEFO.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-end gap-3 sticky bottom-0">
            <button
                type="button"
                onclick="closeDefaultPriorityModal()"
                class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveDefaultPriority()"
                class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-50 hover:border-gray-400 transition-colors">
                Save Default Setting
            </button>
            <button
                type="button"
                id="applyToAllPriorityBtn"
                data-apply-priority-to-all
                class="bg-black text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-800 transition-colors">
                Apply to All Items
            </button>
        </div>
    </div>
</div>
