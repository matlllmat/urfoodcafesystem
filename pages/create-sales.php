<?php
require_once __DIR__ . '/../config/db.php';

// Current staff info for receipt
$cashier_name = isset($current_username) ? $current_username : 'Cashier';
$staff_id = isset($current_user_id) ? $current_user_id : '';
?>

<!-- ==================== VIEW 1: PRODUCT SELECTION ==================== -->
<div id="productSelectionView" class="space-y-6 pb-24">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Create Sales</h1>
        <p class="text-regular text-gray-600">Add items to create a new sale</p>
    </div>

    <!-- Category Carousel -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <!-- Left Arrow -->
            <button onclick="scrollCategories(-1)" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Scrollable Categories -->
            <div id="categoryCarousel" class="flex gap-3 overflow-x-auto scroll-smooth flex-1 py-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                <!-- Categories loaded via JS -->
            </div>

            <!-- Right Arrow -->
            <button onclick="scrollCategories(1)" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Search, Sort & Category Filter Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <!-- Search -->
            <div class="relative flex-1 max-w-xs">
                <input
                    type="text"
                    id="productSearchInput"
                    placeholder="Search products..."
                    oninput="searchProducts()"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Sort -->
            <select id="sortDropdown" onchange="sortProducts()" class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                <option value="name_asc">Alphabetical (Asc)</option>
                <option value="name_desc">Alphabetical (Desc)</option>
                <option value="price_asc">Price (Low to High)</option>
                <option value="price_desc">Price (High to Low)</option>
            </select>

            <!-- Category Filter -->
            <select id="categoryDropdown" onchange="filterByCategory(this.value)" class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                <option value="">All Categories</option>
            </select>
        </div>
    </div>

    <!-- Products Container (grouped by category) -->
    <div id="productsContainer" class="space-y-6">
        <!-- Products loaded via JS -->
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <p class="text-regular text-gray-500">Loading products...</p>
        </div>
    </div>

    <!-- Order Summary Bar (sticky bottom) -->
    <div id="orderSummaryBar" class="hidden fixed bottom-0 left-0 right-0 lg:left-64 bg-white border-t-2 border-gray-200 shadow-lg z-10">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <div>
                    <p class="text-label text-gray-500">Order Summary</p>
                </div>
                <div>
                    <p class="text-label text-gray-500 mb-0.5">Number of Products</p>
                    <p id="summaryProductCount" class="text-product text-blue-600">0</p>
                </div>
                <div>
                    <p id="summaryTotal" class="text-title text-xl">&#8369; 0.00</p>
                </div>
            </div>
            <button onclick="showCartView()" class="bg-black text-white px-6 py-2.5 rounded-md text-product hover:bg-gray-800 transition-colors flex items-center gap-2 relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                Check Cart
                <span id="cartBadge" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">0</span>
            </button>
        </div>
    </div>
</div>

<!-- ==================== VIEW 2: CART PAGE ==================== -->
<div id="cartView" class="hidden space-y-6">
    <!-- Cart Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl text-title mb-2">Cart Page</h1>
            <p class="text-regular text-gray-600">The order is to be processed</p>
        </div>
        <!-- Manual Value Mode Toggle -->
        <div class="flex items-center gap-3 flex-shrink-0">
            <span class="text-label text-gray-500">Manual Value Mode</span>
            <button id="manualModeToggle" onclick="toggleManualMode()"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors bg-gray-300"
                role="switch" aria-checked="false">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
            </button>
        </div>
    </div>

    <!-- Order Summary Card + Cart Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Store Info + Summary + Amount Paid -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex flex-col items-center text-center mb-5">
                <img src="../assets/images/darklogo.png" alt="Logo" class="h-20 w-auto mb-3" />
                <h3 class="text-product text-lg">UR Foodhub + Cafe Inventory System</h3>
            </div>

            <div class="border-t border-gray-200 pt-4 space-y-3">
                <h4 class="text-product text-lg mb-3">Order Summary</h4>
                <div class="flex justify-between items-center">
                    <span class="text-title text-lg">Grand Total</span>
                    <span id="cartGrandTotal" class="text-title text-xl">&#8369; 0.00</span>
                </div>
                <div class="flex justify-between text-regular">
                    <span class="text-gray-500">Number of Products</span>
                    <span id="cartProductCount" class="text-product">0</span>
                </div>
            </div>

            <!-- Amount Paid (emphasized) -->
            <div class="mt-5 bg-gray-50 border border-gray-300 rounded-lg p-4">
                <label class="text-title text-base block mb-2">Amount Paid <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 font-semibold text-lg">&#8369;</span>
                    <input
                        type="number"
                        id="amountPaidInput"
                        min="0"
                        step="0.01"
                        value="0"
                        oninput="calculateChange()"
                        class="w-full pl-9 pr-4 py-3 border-2 border-gray-300 rounded-md text-right text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-black focus:border-black bg-white" />
                </div>
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300">
                    <span class="text-product text-gray-600">Change</span>
                    <span id="cartChange" class="text-title text-lg">&#8369; 0.00</span>
                </div>
            </div>

            <div class="mt-5">
                <button onclick="confirmOrder()" id="confirmOrderBtn" class="w-full bg-black text-white px-4 py-3 rounded-md text-product hover:bg-gray-800 transition-colors">
                    Confirm Order
                </button>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="lg:col-span-2">
            <!-- Cart Items Header -->
            <div class="grid grid-cols-12 gap-2 px-4 py-3 text-label text-gray-500 uppercase tracking-wide border-b border-gray-200">
                <div class="col-span-4">Product Name</div>
                <div class="col-span-3 text-center">QTY</div>
                <div class="col-span-2 text-center">Price</div>
                <div class="col-span-3 text-right">Total</div>
            </div>

            <!-- Cart Items Container -->
            <div id="cartItemsContainer" class="space-y-2 mt-3">
                <!-- Cart items rendered via JS -->
            </div>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="flex items-center justify-between pt-2 pb-4">
        <button onclick="showProductView()" class="flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-md text-product text-gray-700 hover:bg-gray-100 hover:border-gray-400 hover:shadow-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Products
        </button>
        <button onclick="confirmResetTransaction()" class="flex items-center gap-2 px-4 py-2.5 border border-red-300 rounded-md text-product text-red-600 hover:bg-red-100 hover:border-red-400 hover:shadow-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reset Transaction
        </button>
    </div>
</div>

<!-- ==================== RESET CONFIRMATION MODAL ==================== -->
<div id="resetConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeResetConfirmModal()"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6">
        <div class="text-center">
            <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-red-100 mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-title text-lg mb-2">Reset Transaction?</h3>
            <p class="text-regular text-gray-500 mb-6">This will clear all items in the cart and any manual overrides. This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="closeResetConfirmModal()" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-md text-product text-gray-700 hover:bg-gray-100 transition-colors">
                    Cancel
                </button>
                <button onclick="closeResetConfirmModal(); resetTransaction();" class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-md text-product hover:bg-red-700 transition-colors">
                    Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== RECEIPT MODAL ==================== -->
<div id="receiptModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeReceiptModal()"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-title text-lg">Order Receipt</h3>
            <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body (scrollable) -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div id="receiptContent">
                <!-- Receipt content rendered via JS -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-200 space-y-3">
            <button onclick="printReceipt()" class="w-full bg-black text-white px-4 py-2.5 rounded-md text-product hover:bg-gray-800 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Receipt
            </button>
            <button onclick="closeReceiptAndGoToProducts()" class="w-full border border-gray-300 px-4 py-2.5 rounded-md text-product hover:bg-gray-50 transition-colors">
                &larr; Back to Products
            </button>
        </div>
    </div>
</div>

<!-- Hidden data for JS -->
<input type="hidden" id="currentStaffId" value="<?php echo htmlspecialchars($staff_id); ?>" />
<input type="hidden" id="currentCashierName" value="<?php echo htmlspecialchars($cashier_name); ?>" />

<!-- Load JS -->
<script src="js/create-sales.js"></script>
