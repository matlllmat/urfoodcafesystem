// ============================================
// PRODUCTS MAIN JS - CRUD Operations & Modals
// ============================================

// Cached inventory items for recipe dropdowns
let cachedInventoryItems = null;
// Cached product categories for dropdowns
let cachedProductCategories = null;

// ============================================
// INVENTORY ITEMS LOADER (for dropdowns)
// ============================================

function loadInventoryItemsForDropdown() {
    if (cachedInventoryItems) return Promise.resolve(cachedInventoryItems);

    return fetch('../api/mp-get-inventory-items.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cachedInventoryItems = data.items;
                return data.items;
            }
            return [];
        })
        .catch(error => {
            console.error('Error loading inventory items:', error);
            return [];
        });
}

// ============================================
// PRODUCT DETAIL MODAL
// ============================================

function openProductDetailModal(productId) {
    currentProductId = productId;
    productWasModified = false;

    const modal = document.getElementById('productDetailModal');
    modal.classList.remove('hidden');

    // Reset to loading state
    document.getElementById('productDetailActions').innerHTML = '';
    document.getElementById('productDetailsSidebar').innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-label text-gray-500 mt-2">Loading product details...</p>
        </div>
    `;
    document.getElementById('productRequirementsList').innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-label text-gray-500 mt-2">Loading requirements...</p>
        </div>
    `;

    // Load all in parallel
    loadProductDetails(productId);
    loadProductRequirements(productId);
    loadProductCategoriesForDetail(productId);
}

function closeProductDetailModal() {
    document.getElementById('productDetailModal').classList.add('hidden');
    currentProductId = null;
    if (productWasModified) {
        window.location.reload();
    }
}

function loadProductDetails(productId) {
    fetch('../api/mp-get-product-details.php?product_id=' + encodeURIComponent(productId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProductDetails(data.product);
            } else {
                showErrorModal(data.message || 'Failed to load product details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to load product details. Please try again.');
        });
}

function displayProductDetails(product) {
    document.getElementById('productDetailSubtitle').textContent = product.product_id;

    // Status styling
    const isOutOfStock = parseInt(product.out_of_stock_count) > 0 && product.status === 'Available';
    let statusClass = '';
    if (isOutOfStock) {
        statusClass = 'bg-gray-100 text-gray-400 line-through';
    } else {
        switch (product.status) {
            case 'Available':
                statusClass = 'bg-green-100 text-success';
                break;
            case 'Unavailable':
                statusClass = 'bg-yellow-100 text-warning';
                break;
            case 'Discontinued':
                statusClass = 'bg-red-100 text-danger';
                break;
        }
    }

    const profit = parseFloat(product.profit);
    const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
    const margin = parseFloat(product.profit_margin);

    const createdDate = new Date(product.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const updatedDate = new Date(product.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

    const sidebar = document.getElementById('productDetailsSidebar');
    sidebar.innerHTML = `
        <!-- Product Image -->
        <div class="aspect-square bg-support rounded-md mb-4 flex items-center justify-center overflow-hidden">
            <img
                src="../assets/images/product/${escapeHtml(product.image_filename)}"
                alt="${escapeHtml(product.product_name)}"
                class="w-full h-full object-cover"
                onerror="this.src='../assets/images/product/default-product.png'" />
        </div>

        <!-- Product Name & Status -->
        <h3 class="text-product text-gray-800 text-lg mb-2">${escapeHtml(product.product_name)}</h3>
        <div class="flex items-center gap-2 mb-4">
            <span class="inline-block text-xs px-2 py-1 rounded ${statusClass}">${escapeHtml(product.status)}</span>
            ${isOutOfStock ? '<span class="inline-block text-xs px-2 py-1 rounded bg-red-600 text-white font-semibold">Out of Stock</span>' : ''}
        </div>

        <!-- Categories -->
        <div class="mb-4">
            <p class="text-label text-gray-500 mb-1">Categories</p>
            <div id="productDetailCategories">
                <p class="text-label text-gray-400">Loading...</p>
            </div>
        </div>

        <!-- Financial Info -->
        <div class="space-y-3 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-3">
                <p class="text-label text-gray-500 mb-1">Selling Price</p>
                <p class="text-title text-xl text-gray-800">&#8369;${parseFloat(product.price).toFixed(2)}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3">
                <p class="text-label text-gray-500 mb-1">Cost</p>
                <p class="text-regular text-gray-800 font-medium">&#8369;${parseFloat(product.calculated_cost).toFixed(2)}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-label text-gray-500 mb-1">Profit</p>
                        <p class="text-regular font-medium ${profitClass}">&#8369;${profit.toFixed(2)}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-label text-gray-500 mb-1">Margin</p>
                        <p class="text-regular font-medium ${profitClass}">${margin.toFixed(1)}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="text-label text-gray-500 space-y-1">
            <p>Created: ${createdDate}</p>
            <p>Updated: ${updatedDate}</p>
        </div>
    `;

    // Inject action buttons into the sticky footer
    document.getElementById('productDetailActions').innerHTML = `
        <button
            onclick="openEditProductModal('${escapeHtml(product.product_id)}')"
            class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit Product
        </button>
        <button
            onclick="confirmDeleteProduct('${escapeHtml(product.product_id)}', '${escapeHtml(product.product_name)}')"
            class="bg-white border border-red-300 text-danger px-4 py-2 rounded-md text-product hover:bg-red-50 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Delete Product
        </button>
    `;
}

function loadProductCategoriesForDetail(productId) {
    fetch('../api/mp-get-product-categories.php?product_id=' + encodeURIComponent(productId))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                displayProductCategoriesInDetail(data.categories, data.primary_category);
            }
        })
        .catch(error => {
            console.error('Error loading product categories:', error);
        });
}

function displayProductCategoriesInDetail(categories, primaryCategory) {
    const container = document.getElementById('productDetailCategories');
    if (!container) return;

    if (!categories || categories.length === 0) {
        container.innerHTML = '<p class="text-label text-gray-400">No categories assigned</p>';
        return;
    }

    let html = '<div class="flex flex-wrap gap-1">';
    categories.forEach(cat => {
        const isPrimary = cat.category_id === primaryCategory;
        if (isPrimary) {
            html += `<span class="text-xs px-2 py-1 rounded bg-black text-white">${escapeHtml(cat.category_name)}</span>`;
        } else {
            html += `<span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600">${escapeHtml(cat.category_name)}</span>`;
        }
    });
    html += '</div>';
    container.innerHTML = html;
}

function loadProductRequirements(productId) {
    fetch('../api/mp-get-product-requirements.php?product_id=' + encodeURIComponent(productId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProductRequirements(data.requirements, data.total_cost);
            } else {
                showErrorModal(data.message || 'Failed to load requirements');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to load requirements. Please try again.');
        });
}

function displayProductRequirements(requirements, totalCost) {
    const container = document.getElementById('productRequirementsList');

    if (requirements.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-regular text-gray-500">No requirements defined</p>
                <p class="text-label text-gray-400 mt-1">Edit this product to add recipe ingredients</p>
            </div>
        `;
        return;
    }

    let html = '<div class="space-y-3">';

    requirements.forEach(req => {
        const unitCost = parseFloat(req.unit_cost);
        const lineTotal = parseFloat(req.line_total);
        const availableStock = parseFloat(req.available_stock);
        const isOutOfStock = availableStock === 0;

        html += `
            <div class="bg-white rounded-lg border ${isOutOfStock ? 'border-red-300' : 'border-gray-200'} p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <h4 class="text-product text-gray-800">${escapeHtml(req.item_name)}</h4>
                        ${isOutOfStock ? '<span class="text-xs bg-red-600 text-white px-1.5 py-0.5 rounded font-semibold">No Stock</span>' : ''}
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">${escapeHtml(req.inventory_id)}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-label">
                    <div>
                        <span class="text-gray-500">Qty Used</span>
                        <p class="text-gray-800 font-medium">${parseFloat(req.quantity_used).toFixed(2)} ${escapeHtml(req.quantity_unit)}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Unit Cost</span>
                        <p class="text-gray-800 font-medium">&#8369;${unitCost.toFixed(2)}/${escapeHtml(req.quantity_unit)}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-500">Line Total</span>
                        <p class="text-gray-800 font-medium">&#8369;${lineTotal.toFixed(2)}</p>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';

    // Total Cost Summary
    html += `
        <div class="mt-4 bg-gray-50 rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-product text-gray-700">Total Cost</span>
                <span class="text-product text-gray-800 font-medium">&#8369;${parseFloat(totalCost).toFixed(2)}</span>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

// ============================================
// ADD PRODUCT MODAL
// ============================================

function openAddProductModal() {
    document.getElementById('addProductForm').reset();
    document.getElementById('addProductImagePreview').src = '../assets/images/product/default-product.png';
    document.getElementById('addProductImageInput').value = '';
    document.getElementById('addProductRequirements').innerHTML = '';
    document.getElementById('addProductEstimatedCost').innerHTML = '&#8369;0.00';
    document.getElementById('addProductEstimatedProfit').innerHTML = '&#8369;0.00';

    document.getElementById('addProductModal').classList.remove('hidden');

    // Preload inventory items and categories
    loadInventoryItemsForDropdown();
    loadProductCategoriesForDropdown().then(() => {
        displayProductCategoryCheckboxes('add');
    });
}

function closeAddProductModal() {
    document.getElementById('addProductModal').classList.add('hidden');
}

function saveNewProduct() {
    const productName = document.getElementById('addProductName').value.trim();
    const price = document.getElementById('addProductPrice').value;
    const status = document.getElementById('addProductStatus').value;
    const imageInput = document.getElementById('addProductImageInput');

    // Validation
    if (!productName) {
        showWarningModal('Please enter a product name');
        return;
    }
    if (!price || parseFloat(price) < 0) {
        showWarningModal('Please enter a valid price');
        return;
    }

    // Gather categories
    const categoryData = gatherProductCategories('add');
    if (categoryData === null) return;

    // Gather requirements
    const requirements = gatherRequirements('add');
    if (requirements === null) return; // Validation failed

    // Build FormData
    const formData = new FormData();
    formData.append('product_name', productName);
    formData.append('price', price);
    formData.append('status', status);
    formData.append('requirements', JSON.stringify(requirements));
    formData.append('categories', JSON.stringify(categoryData.categories));
    formData.append('primary_category', categoryData.primary_category);
    if (imageInput.files.length > 0) {
        formData.append('product_image', imageInput.files[0]);
    }

    // Loading state
    const saveBtn = document.querySelector('#addProductModal button[onclick="saveNewProduct()"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Creating...';
    saveBtn.disabled = true;

    fetch('../api/mp-add-product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;

            if (data.success) {
                showSuccessModal(data.message, function () {
                    closeAddProductModal();
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to create product');
            }
        })
        .catch(error => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            console.error('Error:', error);
            showErrorModal('Failed to create product. Please try again.');
        });
}

// ============================================
// EDIT PRODUCT MODAL
// ============================================

function openEditProductModal(productId) {
    const modal = document.getElementById('editProductModal');
    document.getElementById('editProductForm').reset();
    document.getElementById('editProductRequirements').innerHTML = '';

    // Show modal
    modal.classList.remove('hidden');

    // Load data
    Promise.all([
        fetch('../api/mp-get-product-details.php?product_id=' + encodeURIComponent(productId)).then(r => r.json()),
        fetch('../api/mp-get-product-requirements.php?product_id=' + encodeURIComponent(productId)).then(r => r.json()),
        fetch('../api/mp-get-product-categories.php?product_id=' + encodeURIComponent(productId)).then(r => r.json()),
        loadInventoryItemsForDropdown(),
        loadProductCategoriesForDropdown()
    ])
        .then(([detailsData, reqData, catData]) => {
            if (!detailsData.success) {
                showErrorModal(detailsData.message || 'Failed to load product');
                closeEditProductModal();
                return;
            }

            const product = detailsData.product;

            // Populate fields
            document.getElementById('editProductId').value = product.product_id;
            document.getElementById('editProductSubtitle').textContent = 'Editing: ' + product.product_id;
            document.getElementById('editProductName').value = product.product_name;
            document.getElementById('editProductPrice').value = product.price;
            document.getElementById('editProductStatus').value = product.status;
            document.getElementById('editProductImagePreview').src =
                '../assets/images/product/' + product.image_filename;

            // Populate categories
            const selectedCats = catData.success ? catData.categories.map(c => c.category_id) : [];
            const primaryCat = catData.success ? catData.primary_category : '';
            displayProductCategoryCheckboxes('edit', selectedCats, primaryCat);

            // Populate requirements
            if (reqData.success && reqData.requirements.length > 0) {
                reqData.requirements.forEach(req => {
                    addRequirementRow('edit', {
                        inventory_id: req.inventory_id,
                        quantity_used: req.quantity_used
                    });
                });
            }

            updateCostPreview('edit');
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to load product details');
            closeEditProductModal();
        });
}

function closeEditProductModal() {
    document.getElementById('editProductModal').classList.add('hidden');
}

function saveProductEdits() {
    const productId = document.getElementById('editProductId').value;
    const productName = document.getElementById('editProductName').value.trim();
    const price = document.getElementById('editProductPrice').value;
    const status = document.getElementById('editProductStatus').value;
    const imageInput = document.getElementById('editProductImageInput');

    // Validation
    if (!productName) {
        showWarningModal('Please enter a product name');
        return;
    }
    if (!price || parseFloat(price) < 0) {
        showWarningModal('Please enter a valid price');
        return;
    }

    // Gather categories
    const categoryData = gatherProductCategories('edit');
    if (categoryData === null) return;

    // Gather requirements
    const requirements = gatherRequirements('edit');
    if (requirements === null) return;

    // Build FormData
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('product_name', productName);
    formData.append('price', price);
    formData.append('status', status);
    formData.append('requirements', JSON.stringify(requirements));
    formData.append('categories', JSON.stringify(categoryData.categories));
    formData.append('primary_category', categoryData.primary_category);
    if (imageInput.files.length > 0) {
        formData.append('product_image', imageInput.files[0]);
    }

    // Loading state
    const saveBtn = document.querySelector('#editProductModal button[onclick="saveProductEdits()"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    fetch('../api/mp-update-product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;

            if (data.success) {
                productWasModified = true;
                showSuccessModal(data.message, function () {
                    closeEditProductModal();
                    // Refresh the detail modal sidebar
                    loadProductDetails(productId);
                    loadProductRequirements(productId);
                    loadProductCategoriesForDetail(productId);
                });
            } else {
                showErrorModal(data.message || 'Failed to update product');
            }
        })
        .catch(error => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            console.error('Error:', error);
            showErrorModal('Failed to update product. Please try again.');
        });
}

// ============================================
// DELETE PRODUCT
// ============================================

function confirmDeleteProduct(productId, productName) {
    // Create a confirmation using the notification modal pattern
    showNotificationModal({
        type: 'warning',
        title: 'Delete Product',
        message: 'Are you sure you want to delete "' + productName + '"? This will also remove all recipe requirements. This action cannot be undone.',
        onConfirm: function () {
            deleteProduct(productId);
        }
    });

    // Override the button area to add Cancel + Confirm
    const buttonsContainer = document.getElementById('notificationButtons');
    const okBtn = document.getElementById('notificationOkButton');

    // Replace buttons with Cancel + Delete
    buttonsContainer.innerHTML = '';

    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = 'Cancel';
    cancelBtn.className = 'bg-gray-200 text-gray-700 px-6 py-2 rounded-md font-medium hover:bg-gray-300 transition-colors cursor-pointer';
    cancelBtn.onclick = function () {
        closeNotificationModal();
    };

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'px-6 py-2 rounded-md font-medium transition-colors cursor-pointer';
    deleteBtn.style.backgroundColor = '#B71C1C';
    deleteBtn.style.color = '#ffffff';
    deleteBtn.onmouseenter = function () { this.style.backgroundColor = '#8b0000'; };
    deleteBtn.onmouseleave = function () { this.style.backgroundColor = '#B71C1C'; };
    deleteBtn.onclick = function () {
        closeNotificationModal();
        deleteProduct(productId);
    };

    buttonsContainer.appendChild(cancelBtn);
    buttonsContainer.appendChild(deleteBtn);
}

function deleteProduct(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('../api/mp-delete-product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message, function () {
                    closeProductDetailModal();
                    productWasModified = false; // Already reloading
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to delete product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to delete product. Please try again.');
        });
}

// ============================================
// REQUIREMENT ROWS (Dynamic Add/Remove)
// ============================================

function addRequirementRow(mode, existingData = null) {
    const container = document.getElementById(mode + 'ProductRequirements');

    if (!cachedInventoryItems) {
        loadInventoryItemsForDropdown().then(() => {
            addRequirementRow(mode, existingData);
        });
        return;
    }

    const row = document.createElement('div');
    row.className = 'requirement-row flex items-center gap-2';

    // Build select options
    let options = '<option value="">Select ingredient...</option>';
    cachedInventoryItems.forEach(item => {
        const selected = existingData && existingData.inventory_id === item.item_id ? 'selected' : '';
        options += `<option value="${escapeHtml(item.item_id)}" data-unit="${escapeHtml(item.quantity_unit)}" data-cost="${item.unit_cost}" ${selected}>${escapeHtml(item.item_name)}</option>`;
    });

    const qtyValue = existingData ? parseFloat(existingData.quantity_used) : '';

    row.innerHTML = `
        <select class="req-inventory-select flex-1 px-3 py-2 border border-gray-300 rounded-md text-regular text-sm focus:outline-none focus:ring-2 focus:ring-black" onchange="onRequirementSelectChange(this, '${mode}')">
            ${options}
        </select>
        <input type="number" class="req-quantity-input w-24 px-3 py-2 border border-gray-300 rounded-md text-regular text-sm focus:outline-none focus:ring-2 focus:ring-black" step="0.01" min="0.01" placeholder="Qty" value="${qtyValue}" oninput="updateCostPreview('${mode}')">
        <span class="req-unit-label text-label text-gray-500 w-12">${existingData && existingData.inventory_id ? getUnitForItem(existingData.inventory_id) : ''}</span>
        <button type="button" onclick="removeRequirementRow(this, '${mode}')" class="text-gray-400 hover:text-danger transition-colors p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    `;

    container.appendChild(row);
    updateCostPreview(mode);
}

function onRequirementSelectChange(select, mode) {
    const row = select.closest('.requirement-row');
    const unitLabel = row.querySelector('.req-unit-label');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value) {
        unitLabel.textContent = selectedOption.getAttribute('data-unit') || '';
    } else {
        unitLabel.textContent = '';
    }

    updateCostPreview(mode);
}

function removeRequirementRow(button, mode) {
    button.closest('.requirement-row').remove();
    updateCostPreview(mode);
}

function getUnitForItem(itemId) {
    if (!cachedInventoryItems) return '';
    const item = cachedInventoryItems.find(i => i.item_id === itemId);
    return item ? item.quantity_unit : '';
}

function getCostForItem(itemId) {
    if (!cachedInventoryItems) return 0;
    const item = cachedInventoryItems.find(i => i.item_id === itemId);
    return item ? parseFloat(item.unit_cost) : 0;
}

function updateCostPreview(mode) {
    const rows = document.querySelectorAll('#' + mode + 'ProductRequirements .requirement-row');
    let totalCost = 0;

    rows.forEach(row => {
        const select = row.querySelector('.req-inventory-select');
        const qtyInput = row.querySelector('.req-quantity-input');

        if (select.value && qtyInput.value) {
            const cost = getCostForItem(select.value);
            const qty = parseFloat(qtyInput.value) || 0;
            totalCost += cost * qty;
        }
    });

    const costEl = document.getElementById(mode + 'ProductEstimatedCost');
    const profitEl = document.getElementById(mode + 'ProductEstimatedProfit');

    if (costEl) {
        costEl.innerHTML = '&#8369;' + totalCost.toFixed(2);
    }

    if (profitEl) {
        const priceInput = document.getElementById(mode + 'ProductPrice');
        const price = priceInput ? parseFloat(priceInput.value) || 0 : 0;
        const profit = price - totalCost;
        profitEl.innerHTML = '&#8369;' + profit.toFixed(2);
        profitEl.className = 'text-label ' + (profit >= 0 ? 'text-success' : 'text-danger');
    }
}

function gatherRequirements(mode) {
    const rows = document.querySelectorAll('#' + mode + 'ProductRequirements .requirement-row');
    const requirements = [];
    const inventoryIds = [];

    for (const row of rows) {
        const select = row.querySelector('.req-inventory-select');
        const qtyInput = row.querySelector('.req-quantity-input');
        const inventoryId = select.value;
        const qty = parseFloat(qtyInput.value);

        if (!inventoryId && !qtyInput.value) continue; // Empty row, skip
        if (!inventoryId) {
            showWarningModal('Please select an ingredient for all requirement rows');
            return null;
        }
        if (!qty || qty <= 0) {
            showWarningModal('Please enter a valid quantity for all ingredients');
            return null;
        }

        if (inventoryIds.includes(inventoryId)) {
            showWarningModal('Duplicate ingredient detected: ' + select.options[select.selectedIndex].text + '. Each ingredient can only be added once.');
            return null;
        }

        inventoryIds.push(inventoryId);
        requirements.push({ inventory_id: inventoryId, quantity_used: qty });
    }

    return requirements;
}

// ============================================
// IMAGE PREVIEW HANDLERS
// ============================================

document.getElementById('addProductImageInput').addEventListener('change', function (e) {
    handleImagePreview(e, 'addProductImagePreview');
});

document.getElementById('editProductImageInput').addEventListener('change', function (e) {
    handleImagePreview(e, 'editProductImagePreview');
});

function handleImagePreview(event, previewId) {
    const file = event.target.files[0];
    if (!file) return;

    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showWarningModal('Invalid image type. Only JPG, PNG, and WEBP are allowed.');
        event.target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showWarningModal('Image size must be less than 5MB');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById(previewId).src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// ============================================
// PRODUCT CATEGORIES (for add/edit modals)
// ============================================

function loadProductCategoriesForDropdown() {
    if (cachedProductCategories) return Promise.resolve(cachedProductCategories);

    return fetch('../api/mp-get-categories.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                cachedProductCategories = data.categories;
                return data.categories;
            }
            return [];
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            return [];
        });
}

function displayProductCategoryCheckboxes(mode, selectedCategories = [], primaryCategory = '') {
    const container = document.getElementById(mode + 'ProductCategoriesContainer');

    if (!cachedProductCategories || cachedProductCategories.length === 0) {
        container.innerHTML = '<p class="text-label text-gray-500">No categories available. Create categories first.</p>';
        return;
    }

    let html = '<div class="space-y-2">';

    cachedProductCategories.forEach(cat => {
        const isChecked = selectedCategories.includes(cat.category_id);
        const isPrimary = cat.category_id === primaryCategory;

        html += `
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                <label class="flex items-center flex-1 cursor-pointer">
                    <input
                        type="checkbox"
                        class="${mode}-product-category-checkbox w-4 h-4 text-black border-gray-300 rounded"
                        value="${escapeHtml(cat.category_id)}"
                        ${isChecked ? 'checked' : ''}
                        onchange="handleProductCategoryChange(this, '${mode}')">
                    <span class="ml-2 text-regular text-gray-700">${escapeHtml(cat.category_name)}</span>
                </label>
                <label class="flex items-center cursor-pointer ml-4">
                    <input
                        type="radio"
                        name="${mode}_primary_product_category"
                        value="${escapeHtml(cat.category_id)}"
                        ${!isChecked ? 'disabled' : ''}
                        ${isPrimary ? 'checked' : ''}>
                    <span class="ml-1 text-label text-gray-500">Primary</span>
                </label>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function handleProductCategoryChange(checkbox, mode) {
    const categoryId = checkbox.value;
    const primaryRadio = document.querySelector(`input[name="${mode}_primary_product_category"][value="${categoryId}"]`);

    if (checkbox.checked) {
        if (primaryRadio) primaryRadio.disabled = false;
        // Auto-select primary if it's the first one checked
        const anyPrimaryChecked = document.querySelector(`input[name="${mode}_primary_product_category"]:checked`);
        if (!anyPrimaryChecked && primaryRadio) primaryRadio.checked = true;
    } else {
        if (primaryRadio) {
            primaryRadio.disabled = true;
            if (primaryRadio.checked) {
                primaryRadio.checked = false;
                // Auto-select another checked category as primary
                const remainingChecked = document.querySelector(`.${mode}-product-category-checkbox:checked`);
                if (remainingChecked) {
                    const nextRadio = document.querySelector(`input[name="${mode}_primary_product_category"][value="${remainingChecked.value}"]`);
                    if (nextRadio) nextRadio.checked = true;
                }
            }
        }
    }
}

function gatherProductCategories(mode) {
    const checkboxes = document.querySelectorAll(`.${mode}-product-category-checkbox:checked`);
    const selectedCategories = Array.from(checkboxes).map(cb => cb.value);

    if (selectedCategories.length === 0) {
        showWarningModal('Please select at least one category');
        return null;
    }

    const primaryRadio = document.querySelector(`input[name="${mode}_primary_product_category"]:checked`);
    const primaryCategory = primaryRadio ? primaryRadio.value : selectedCategories[0];

    return { categories: selectedCategories, primary_category: primaryCategory };
}

// ============================================
// UTILITY
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add price input listeners for live cost preview
document.getElementById('addProductPrice').addEventListener('input', function () {
    updateCostPreview('add');
});
document.getElementById('editProductPrice').addEventListener('input', function () {
    updateCostPreview('edit');
});
