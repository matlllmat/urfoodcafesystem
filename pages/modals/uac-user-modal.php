<!-- User Modal (Add / Edit) -->
<div id="uacUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-title text-xl" id="uacModalTitle">Add New User</h2>
                <p class="text-regular text-sm text-gray-500" id="uacModalSubtitle">Create a new user account and assign module access</p>
            </div>
            <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="uacUserForm" class="space-y-6">
                <input type="hidden" id="uacFormMode" value="add">
                <input type="hidden" id="uacStaffId" value="">

                <!-- Staff ID (shown only in edit mode) -->
                <div id="uacStaffIdDisplay" class="hidden">
                    <label class="block text-product text-gray-700 mb-2">Staff ID</label>
                    <input
                        type="text"
                        id="uacStaffIdField"
                        disabled
                        class="w-full px-4 py-2 border border-gray-200 rounded-md text-regular bg-gray-50 text-gray-500">
                </div>

                <!-- Username -->
                <div>
                    <label for="uacUsername" class="block text-product text-gray-700 mb-2">Username *</label>
                    <input
                        type="text"
                        id="uacUsername"
                        required
                        placeholder="e.g., JohnDoe"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Password -->
                <div>
                    <label for="uacPassword" class="block text-product text-gray-700 mb-2">
                        Password <span id="uacPasswordRequired">*</span>
                    </label>
                    <input
                        type="password"
                        id="uacPassword"
                        placeholder="Enter password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    <p id="uacPasswordHint" class="text-label text-gray-500 mt-1 hidden">Leave blank to keep current password</p>
                </div>

                <!-- Contact and Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="uacContact" class="block text-product text-gray-700 mb-2">Contact</label>
                        <input
                            type="text"
                            id="uacContact"
                            placeholder="e.g., 09123456789"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label for="uacEmail" class="block text-product text-gray-700 mb-2">Email</label>
                        <input
                            type="email"
                            id="uacEmail"
                            placeholder="e.g., user@email.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>

                <!-- Hire Date -->
                <div>
                    <label for="uacHireDate" class="block text-product text-gray-700 mb-2">Hire Date</label>
                    <input
                        type="date"
                        id="uacHireDate"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                </div>

                <!-- Module Access Section -->
                <div class="border-t border-gray-200 pt-6">
                    <label class="block text-product text-gray-700 mb-2">Module Access</label>
                    <p class="text-label text-gray-500 mb-4">Select which pages this user can access. Profile is always accessible.</p>

                    <!-- Super admin notice -->
                    <div id="uacSuperAdminNotice" class="hidden mb-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
                        <p class="text-regular text-sm text-gray-600">Super administrators automatically have full access to all modules.</p>
                    </div>

                    <div id="uacPermissionsContainer" class="space-y-3">
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.create-sales" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Create Sales</span>
                                <p class="text-label text-gray-500">Access the sales transaction page</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.sales-history" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Sales History</span>
                                <p class="text-label text-gray-500">View past transactions and void sales</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.manage-products" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Manage Products</span>
                                <p class="text-label text-gray-500">Create, edit, and manage products</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.product-categories" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Product Categories</span>
                                <p class="text-label text-gray-500">Manage product categories</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.manage-inventory" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Manage Inventory</span>
                                <p class="text-label text-gray-500">Manage inventory items and batches</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.inventory-trail" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Inventory Trail</span>
                                <p class="text-label text-gray-500">View inventory movement history</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="page.reports" class="w-4 h-4 rounded border-gray-300 uac-permission-checkbox">
                            <div>
                                <span class="text-regular text-gray-800">Reports</span>
                                <p class="text-label text-gray-500">View system reports</p>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeUserModal()"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-product hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button
                type="button"
                onclick="saveUser()"
                id="uacSaveButton"
                class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                Create User
            </button>
        </div>
    </div>
</div>
