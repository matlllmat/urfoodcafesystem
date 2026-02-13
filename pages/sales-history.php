<?php
require_once __DIR__ . '/../config/db.php';
?>

<!-- Sales History Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Sales History</h1>
        <p class="text-regular text-gray-600">View past transactions, receipts, and manage voided sales</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today's Sales -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Today's Sales</p>
                    <h3 class="text-title text-2xl text-gray-800" id="statTodaySales">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statTodayRevenue"></p>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Revenue</p>
                    <h3 class="text-title text-2xl text-success" id="statTotalRevenue">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statCompletedCount"></p>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Profit</p>
                    <h3 class="text-title text-2xl" id="statTotalProfit">-</h3>
                    <p class="text-xs text-gray-400 mt-1" id="statAvgOrder"></p>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Voided Sales -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-label text-gray-500 mb-1">Voided Sales</p>
                    <h3 class="text-title text-2xl text-danger" id="statVoidedCount">-</h3>
                </div>
                <div class="bg-red-100 p-2 rounded flex-shrink-0">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col gap-4">
            <!-- Row 1: Search + Filters -->
            <div class="flex flex-col sm:flex-row gap-3 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <input
                        type="text"
                        id="shSearchInput"
                        placeholder="Search sale ID..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Status Filter -->
                <select
                    id="shStatusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadSalesHistory(1)">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="voided">Voided</option>
                </select>

                <!-- Cashier Filter -->
                <select
                    id="shCashierFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadSalesHistory(1)">
                    <option value="">All Cashiers</option>
                </select>

                <!-- Sort -->
                <select
                    id="shSortFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadSalesHistory(1)">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="amount_desc">Amount (High to Low)</option>
                    <option value="amount_asc">Amount (Low to High)</option>
                    <option value="profit_desc">Profit (High to Low)</option>
                    <option value="profit_asc">Profit (Low to High)</option>
                </select>
            </div>

            <!-- Row 2: Date Range -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <span class="text-label text-gray-500 whitespace-nowrap">Date Range:</span>
                <input
                    type="date"
                    id="shDateFrom"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadSalesHistory(1)" />
                <span class="text-regular text-gray-400">to</span>
                <input
                    type="date"
                    id="shDateTo"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="loadSalesHistory(1)" />
                <button
                    onclick="clearDateFilters()"
                    class="text-label text-gray-500 hover:text-gray-700 underline transition-colors">
                    Clear dates
                </button>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-200 text-label text-gray-500 uppercase">
            <div class="col-span-2">Sale ID</div>
            <div class="col-span-3">Date & Time</div>
            <div class="col-span-2">Cashier</div>
            <div class="col-span-1 text-center">Items</div>
            <div class="col-span-1 text-right">Total</div>
            <div class="col-span-1 text-right">Profit</div>
            <div class="col-span-2 text-center">Status</div>
        </div>

        <!-- Table Body -->
        <div id="salesTableBody">
            <div class="px-6 py-12 text-center">
                <p class="text-regular text-gray-400">Loading sales history...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="salesPagination" class="hidden px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600" id="salesPaginationInfo">Showing 0–0 of 0</p>
            <div class="flex items-center gap-2 flex-wrap" id="salesPaginationControls"></div>
        </div>
    </div>
</div>

<!-- ==================== RECEIPT MODAL ==================== -->
<div id="shReceiptModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeShReceiptModal()"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-title text-lg">Sale Receipt</h3>
            <button onclick="closeShReceiptModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Scrollable Receipt Content -->
        <div class="flex-1 overflow-y-auto px-6 py-4" id="shReceiptContent">
            <p class="text-center text-gray-400 py-8 text-regular">Loading receipt...</p>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex gap-3">
                <button onclick="printShReceipt()" class="flex-1 bg-black text-white py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                    Print Receipt
                </button>
                <button onclick="closeShReceiptModal()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md text-product hover:bg-gray-50 transition-colors">
                    Close
                </button>
            </div>
            <!-- Void link (only visible for completed sales, set by JS) -->
            <div id="shVoidLinkContainer" class="hidden mt-3 pt-3 border-t border-gray-100 text-center">
                <button
                    onclick="openVoidFromReceipt()"
                    class="text-xs text-gray-400 hover:text-danger transition-colors">
                    Void this sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== VOID CONFIRM MODAL ==================== -->
<div id="shVoidModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeVoidModal()"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm mx-4">
        <div class="p-6">
            <!-- Warning Icon -->
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 rounded-full flex items-center justify-center bg-red-100">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>

            <h3 class="text-title text-xl text-center text-gray-800 mb-2">Void Sale</h3>
            <p class="text-regular text-center text-gray-600 mb-2">
                Are you sure you want to void sale <span id="voidSaleIdLabel" class="font-semibold"></span>?
            </p>
            <p class="text-label text-center text-gray-400 mb-6">
                This action cannot be undone. The sale will be marked as voided but inventory will not be restored.
            </p>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button
                    onclick="closeVoidModal()"
                    class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md text-product hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button
                    id="confirmVoidBtn"
                    onclick="confirmVoidSale()"
                    class="flex-1 py-2 rounded-md text-product text-white transition-colors"
                    style="background-color: #B71C1C;"
                    onmouseenter="this.style.backgroundColor='#8b0000'"
                    onmouseleave="this.style.backgroundColor='#B71C1C'">
                    Void Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ESC Key Handler -->
<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const receiptModal = document.getElementById('shReceiptModal');
            const voidModal = document.getElementById('shVoidModal');

            if (voidModal && !voidModal.classList.contains('hidden')) {
                closeVoidModal();
            } else if (receiptModal && !receiptModal.classList.contains('hidden')) {
                closeShReceiptModal();
            }
        }
    });
</script>

<!-- Load JS -->
<script src="js/sales-history.js"></script>
