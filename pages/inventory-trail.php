<?php
require_once __DIR__ . '/../config/db.php';
?>

<!-- Inventory Trail Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Inventory Trail</h1>
        <p class="text-regular text-gray-600">Track all inventory movements, adjustments, and disposals</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Movements -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Movements</p>
                    <h3 class="text-title text-2xl text-gray-800" id="statTotalMovements">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statTodayMovements"></p>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Inbound -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Inbound</p>
                    <h3 class="text-title text-2xl text-success" id="statTotalInbound">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statInboundCount"></p>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Outbound -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Outbound</p>
                    <h3 class="text-title text-2xl text-danger" id="statTotalOutbound">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statOutboundCount"></p>
                </div>
                <div class="bg-red-100 p-2 rounded">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Net Value Impact -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-label text-gray-500 mb-1">Net Value Impact</p>
                    <h3 class="text-title text-2xl" id="statNetValue">-</h3>
                </div>
                <div class="bg-gray-100 p-2 rounded flex-shrink-0" id="statNetValueIcon">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col gap-4">
            <!-- Row 1: Search + Filters -->
            <div class="flex flex-col sm:flex-row gap-3 flex-1 flex-wrap">
                <!-- Search -->
                <div class="relative flex-1 w-full sm:max-w-xs min-w-0">
                    <input
                        type="text"
                        id="itSearchInput"
                        placeholder="Search ID or item..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Movement Type Filter -->
                <select
                    id="itTypeFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)">
                    <option value="">All Types</option>
                    <option value="initial_stock">Initial Stock</option>
                    <option value="restock">Restock</option>
                    <option value="sale">Sale</option>
                    <option value="disposal">Disposal</option>
                    <option value="adjustment">Adjustment</option>
                </select>

                <!-- Item Filter -->
                <select
                    id="itItemFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)">
                    <option value="">All Items</option>
                </select>

                <!-- Staff Filter -->
                <select
                    id="itStaffFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)">
                    <option value="">All Staff</option>
                </select>

                <!-- Sort -->
                <select
                    id="itSortFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="qty_desc">Quantity (High to Low)</option>
                    <option value="qty_asc">Quantity (Low to High)</option>
                    <option value="value_desc">Value (High to Low)</option>
                    <option value="value_asc">Value (Low to High)</option>
                </select>
            </div>

            <!-- Row 2: Date Range -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <span class="text-label text-gray-500 whitespace-nowrap">Date Range:</span>
                <input
                    type="date"
                    id="itDateFrom"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)" />
                <span class="text-regular text-gray-400">to</span>
                <input
                    type="date"
                    id="itDateTo"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadInventoryTrail(1)" />
                <button
                    onclick="clearDateFilters()"
                    class="text-label text-gray-500 hover:text-gray-700 underline transition-colors">
                    Clear dates
                </button>
            </div>
        </div>
    </div>

    <!-- Movements Table: scrollable on small desktop / tablet -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <!-- Table Header (scrolls with content) -->
            <div class="hidden md:grid grid-cols-11 gap-2 sm:gap-4 px-4 sm:px-6 py-3 bg-gray-50 border-b border-gray-200 text-label text-gray-500 uppercase min-w-[720px]">
                <div class="col-span-2">Movement ID</div>
                <div class="col-span-2">Date & Time</div>
                <div class="col-span-2 min-w-0">Item</div>
                <div class="col-span-1 text-center">Type</div>
                <div class="col-span-1 text-right">Qty</div>
                <div class="col-span-2 text-center">Before → After</div>
                <div class="col-span-1 text-right">Value</div>
            </div>

            <!-- Table Body -->
            <div id="trailTableBody" class="min-w-0">
                <div class="px-4 sm:px-6 py-12 text-center">
                    <p class="text-regular text-gray-400">Loading inventory trail...</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="trailPagination" class="hidden px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600" id="trailPaginationInfo">Showing 0–0 of 0</p>
            <div class="flex items-center gap-2 flex-wrap" id="trailPaginationControls"></div>
        </div>
    </div>
</div>

<!-- ==================== MOVEMENT DETAIL MODAL ==================== -->
<div id="movementDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeMovementDetailModal()" aria-hidden="true"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md sm:max-w-lg mx-auto max-h-[90vh] flex flex-col border border-gray-100">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50/80 rounded-t-2xl shrink-0">
            <h3 class="text-title text-lg text-gray-800">Movement Details</h3>
            <button type="button" onclick="closeMovementDetailModal()" class="p-2 -m-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors touch-manipulation" aria-label="Close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4 sm:py-5" id="movementDetailContent">
            <p class="text-center text-gray-400 py-8 text-regular">Loading details...</p>
        </div>

        <!-- Modal Footer -->
        <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50/50 rounded-b-2xl shrink-0">
            <button type="button" onclick="closeMovementDetailModal()" class="w-full py-3 rounded-xl text-product font-medium bg-gray-900 text-white hover:bg-gray-800 transition-colors touch-manipulation">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ESC Key Handler -->
<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const detailModal = document.getElementById('movementDetailModal');
            if (detailModal && !detailModal.classList.contains('hidden')) {
                closeMovementDetailModal();
            }
        }
    });
</script>

<!-- Load JS -->
<script src="js/inventory-trail.js"></script>
