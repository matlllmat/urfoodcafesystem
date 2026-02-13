<!-- Product Detail Modal -->
<div id="productDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl">Product Details</h2>
                <p id="productDetailSubtitle" class="text-label text-gray-500 mt-1"></p>
            </div>
            <button onclick="closeProductDetailModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content - Two Column Layout -->
        <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
            <!-- Left Sidebar - Product Info -->
            <div class="w-full md:w-80 border-b md:border-b-0 md:border-r border-gray-200 overflow-y-auto bg-gray-50 p-6">
                <div id="productDetailsSidebar">
                    <!-- Loading state -->
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-label text-gray-500 mt-2">Loading product details...</p>
                    </div>
                </div>
            </div>

            <!-- Right Side - Recipe / Requirements -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-product text-gray-800">Recipe / Requirements</h3>
                        <p class="text-label text-gray-500">Inventory items needed to make this product</p>
                    </div>
                </div>
                <div id="productRequirementsList">
                    <!-- Loading state -->
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-label text-gray-500 mt-2">Loading requirements...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 flex items-center justify-between">
            <div id="productDetailActions" class="flex items-center gap-2">
                <!-- Action buttons injected by JS -->
            </div>
            <button onclick="closeProductDetailModal()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>
