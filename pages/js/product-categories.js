// ============================================
// PRODUCT CATEGORIES - CRUD Operations
// ============================================

// ADD CATEGORY
function openAddCategoryModal() {
    document.getElementById('addCategoryName').value = '';
    document.getElementById('addCategoryDescription').value = '';
    document.getElementById('addCategoryModal').classList.remove('hidden');
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
}

function saveNewCategory() {
    const name = document.getElementById('addCategoryName').value.trim();
    const description = document.getElementById('addCategoryDescription').value.trim();

    if (!name) {
        showWarningModal('Please enter a category name');
        return;
    }

    const saveBtn = document.querySelector('#addCategoryModal button[onclick="saveNewCategory()"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Creating...';
    saveBtn.disabled = true;

    fetch('../api/mp-add-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_name: name, description: description })
    })
        .then(r => r.json())
        .then(data => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;

            if (data.success) {
                showSuccessModal(data.message, function () {
                    closeAddCategoryModal();
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to create category');
            }
        })
        .catch(error => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            console.error('Error:', error);
            showErrorModal('Failed to create category. Please try again.');
        });
}

// EDIT CATEGORY
function openEditCategoryModal(categoryId, name, description, isActive) {
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editCategorySubtitle').textContent = categoryId;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryDescription').value = description;
    document.getElementById('editCategoryActive').checked = isActive;
    document.getElementById('editCategoryModal').classList.remove('hidden');
}

function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').classList.add('hidden');
}

function saveCategoryEdits() {
    const categoryId = document.getElementById('editCategoryId').value;
    const name = document.getElementById('editCategoryName').value.trim();
    const description = document.getElementById('editCategoryDescription').value.trim();
    const isActive = document.getElementById('editCategoryActive').checked;

    if (!name) {
        showWarningModal('Please enter a category name');
        return;
    }

    const saveBtn = document.querySelector('#editCategoryModal button[onclick="saveCategoryEdits()"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    fetch('../api/mp-update-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            category_id: categoryId,
            category_name: name,
            description: description,
            is_active: isActive
        })
    })
        .then(r => r.json())
        .then(data => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;

            if (data.success) {
                showSuccessModal(data.message, function () {
                    closeEditCategoryModal();
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to update category');
            }
        })
        .catch(error => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            console.error('Error:', error);
            showErrorModal('Failed to update category. Please try again.');
        });
}

// DELETE CATEGORY
function confirmDeleteCategory(categoryId, categoryName) {
    showNotificationModal({
        type: 'warning',
        title: 'Delete Category',
        message: 'Are you sure you want to delete "' + categoryName + '"? This action cannot be undone.'
    });

    const buttonsContainer = document.getElementById('notificationButtons');
    buttonsContainer.innerHTML = '';

    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = 'Cancel';
    cancelBtn.className = 'bg-gray-200 text-gray-700 px-6 py-2 rounded-md font-medium hover:bg-gray-300 transition-colors cursor-pointer';
    cancelBtn.onclick = function () { closeNotificationModal(); };

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'px-6 py-2 rounded-md font-medium transition-colors cursor-pointer';
    deleteBtn.style.backgroundColor = '#B71C1C';
    deleteBtn.style.color = '#ffffff';
    deleteBtn.onmouseenter = function () { this.style.backgroundColor = '#8b0000'; };
    deleteBtn.onmouseleave = function () { this.style.backgroundColor = '#B71C1C'; };
    deleteBtn.onclick = function () {
        closeNotificationModal();
        deleteCategory(categoryId);
    };

    buttonsContainer.appendChild(cancelBtn);
    buttonsContainer.appendChild(deleteBtn);
}

function deleteCategory(categoryId) {
    fetch('../api/mp-delete-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: categoryId })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message, function () {
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to delete category');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to delete category. Please try again.');
        });
}

// ============================================
// CATEGORY PRODUCTS - View & Manage
// ============================================

let currentCpCategoryId = null;

function openCategoryProductsModal(categoryId, categoryName) {
    currentCpCategoryId = categoryId;
    document.getElementById('cpModalSubtitle').textContent = 'Products in "' + categoryName + '"';
    document.getElementById('cpProductsList').innerHTML = '<p class="text-center text-gray-400 py-6 text-regular">Loading...</p>';
    document.getElementById('cpAddProductSelect').innerHTML = '<option value="">Select a product...</option>';
    document.getElementById('categoryProductsModal').classList.remove('hidden');
    loadCategoryProducts(categoryId);
}

function closeCategoryProductsModal() {
    document.getElementById('categoryProductsModal').classList.add('hidden');
    currentCpCategoryId = null;
}

function loadCategoryProducts(categoryId) {
    fetch('../api/pc-get-category-products.php?category_id=' + encodeURIComponent(categoryId))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCategoryProducts(data.products, data.available);
            } else {
                document.getElementById('cpProductsList').innerHTML =
                    '<p class="text-center text-red-500 py-4 text-regular">' + (data.message || 'Failed to load') + '</p>';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            document.getElementById('cpProductsList').innerHTML =
                '<p class="text-center text-red-500 py-4 text-regular">Failed to load products</p>';
        });
}

function renderCategoryProducts(products, available) {
    const container = document.getElementById('cpProductsList');
    const select = document.getElementById('cpAddProductSelect');

    // Render product list
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="text-center py-6">
                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-regular text-gray-400">No products in this category yet</p>
                <p class="text-label text-gray-300 mt-1">Use the dropdown below to add products</p>
            </div>
        `;
    } else {
        let html = '<div class="space-y-2">';
        products.forEach(p => {
            const statusBadge = getStatusBadge(p.status);
            const escapedName = escapeHtml(p.product_name);
            html += `
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-regular text-gray-800 truncate">${escapedName}</p>
                        <p class="text-label text-gray-400">${escapeHtml(p.product_id)}</p>
                    </div>
                    <div class="flex items-center gap-3 ml-3">
                        <span class="text-regular text-gray-700 whitespace-nowrap">&#8369; ${parseFloat(p.price).toFixed(2)}</span>
                        ${statusBadge}
                        <button
                            onclick="removeProductFromCategory('${escapeHtml(p.product_id)}', '${escapedName}')"
                            class="text-gray-400 hover:text-danger transition-colors p-1 flex-shrink-0"
                            title="Remove from category">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // Populate available products dropdown
    select.innerHTML = '<option value="">Select a product...</option>';
    if (available && available.length > 0) {
        available.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.product_id;
            opt.textContent = p.product_name + ' — \u20B1' + parseFloat(p.price).toFixed(2);
            select.appendChild(opt);
        });
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'Available':
            return '<span class="text-xs px-2 py-0.5 rounded bg-green-100 text-success whitespace-nowrap">Available</span>';
        case 'Unavailable':
            return '<span class="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-700 whitespace-nowrap">Unavailable</span>';
        case 'Discontinued':
            return '<span class="text-xs px-2 py-0.5 rounded bg-red-100 text-danger whitespace-nowrap">Discontinued</span>';
        default:
            return '';
    }
}

function addProductToCurrentCategory() {
    if (!currentCpCategoryId) return;

    const select = document.getElementById('cpAddProductSelect');
    const productId = select.value;

    if (!productId) {
        showWarningModal('Please select a product to add');
        return;
    }

    fetch('../api/pc-add-category-product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: currentCpCategoryId, product_id: productId })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadCategoryProducts(currentCpCategoryId);
                updateProductCountInTable(currentCpCategoryId, 1);
            } else {
                showErrorModal(data.message || 'Failed to add product');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showErrorModal('Failed to add product. Please try again.');
        });
}

function removeProductFromCategory(productId, productName) {
    if (!currentCpCategoryId) return;

    showNotificationModal({
        type: 'warning',
        title: 'Remove Product',
        message: 'Remove "' + productName + '" from this category?',
        onConfirm: function () {
            fetch('../api/pc-remove-category-product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category_id: currentCpCategoryId, product_id: productId })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadCategoryProducts(currentCpCategoryId);
                        updateProductCountInTable(currentCpCategoryId, -1);
                    } else {
                        showErrorModal(data.message || 'Failed to remove product');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showErrorModal('Failed to remove product. Please try again.');
                });
        }
    });
}

function updateProductCountInTable(categoryId, delta) {
    const btn = document.querySelector('button[data-category-products][data-category-id="' + categoryId + '"]');
    if (!btn) return;
    const textNodes = Array.from(btn.childNodes).filter(n => n.nodeType === Node.TEXT_NODE);
    if (textNodes.length > 0) {
        const currentCount = parseInt(textNodes[0].textContent.trim(), 10) || 0;
        textNodes[0].textContent = (currentCount + delta).toString();
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Event delegation: Products column button
// Bind immediately so it still works in deployments where optimizers alter script timing.
if (!window.__pcProductsDelegationBound) {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-category-products]');
        if (!btn) return;
        const categoryId = btn.getAttribute('data-category-id');
        const categoryName = btn.getAttribute('data-category-name') || '';
        if (categoryId) {
            openCategoryProductsModal(categoryId, categoryName);
        }
    });
    window.__pcProductsDelegationBound = true;
}

// ESC key handler
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const cpModal = document.getElementById('categoryProductsModal');
        const editModal = document.getElementById('editCategoryModal');
        const addModal = document.getElementById('addCategoryModal');

        if (cpModal && !cpModal.classList.contains('hidden')) {
            closeCategoryProductsModal();
        } else if (editModal && !editModal.classList.contains('hidden')) {
            closeEditCategoryModal();
        } else if (addModal && !addModal.classList.contains('hidden')) {
            closeAddCategoryModal();
        }
    }
});
