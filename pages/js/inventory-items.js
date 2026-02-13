/**
 * Open edit item modal
 * @param {string} itemId - The inventory item ID
 */
function openEditItemModal(itemId) {
    itemWasModified = false; // Reset the flag

    // Fetch current item data to populate the form
    fetch('../api/mi-get-item-details.php?item_id=' + encodeURIComponent(itemId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.item;

                // Populate form fields
                document.getElementById('editItemId').value = item.item_id;
                document.getElementById('editItemName').value = item.item_name;
                document.getElementById('editQuantityUnit').value = item.quantity_unit;
                document.getElementById('editReorderLevel').value = item.reorder_level;

                // Set image preview
                const imagePath = item.image_filename
                    ? `../assets/images/inventory-item/${item.image_filename}`
                    : '../assets/images/inventory-item/default-item.png';
                document.getElementById('editItemImagePreview').src = imagePath;

                // Load categories
                loadEditCategories(item.item_id);

                // Show modal
                document.getElementById('editItemModal').classList.remove('hidden');
            } else {
                alert('Error loading item details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error loading item details:', error);
            alert('Failed to load item details. Please try again.');
        });
}

/**
 * Close edit item modal
 */
function closeEditItemModal() {
    document.getElementById('editItemModal').classList.add('hidden');
    // Reset form
    document.getElementById('editItemForm').reset();
    document.getElementById('editItemImageInput').value = '';
}

/**
 * Save item edits
 */
function saveItemEdits() {
    const itemId = document.getElementById('editItemId').value;
    const itemName = document.getElementById('editItemName').value;
    const quantityUnit = document.getElementById('editQuantityUnit').value;
    const reorderLevel = document.getElementById('editReorderLevel').value;
    const imageInput = document.getElementById('editItemImageInput');

    // Validation
    if (!itemName.trim()) {
        showWarningModal('Please enter an item name');
        return;
    }
    if (!quantityUnit.trim()) {
        showWarningModal('Please enter a quantity unit');
        return;
    }
    if (!reorderLevel || parseFloat(reorderLevel) < 0) {
        showWarningModal('Please enter a valid reorder level');
        return;
    }

    // Create FormData for file upload
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('item_name', itemName);
    formData.append('quantity_unit', quantityUnit);
    formData.append('reorder_level', reorderLevel);

    // Add image if selected
    if (imageInput.files.length > 0) {
        formData.append('item_image', imageInput.files[0]);
    }

    // Get selected categories
    const categoryCheckboxes = document.querySelectorAll('.edit-category-checkbox:checked');
    const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value);

    // Get primary category
    const primaryCategoryRadio = document.querySelector('input[name="primary_category"]:checked');
    const primaryCategory = primaryCategoryRadio ? primaryCategoryRadio.value : '';

    formData.append('categories', JSON.stringify(selectedCategories));
    formData.append('primary_category', primaryCategory);

    // Show loading state
    const saveButton = document.querySelector('#editItemModal button[onclick="saveItemEdits()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    // Send update request
    fetch('../api/mi-update-item.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Item updated successfully!', function () {
                    // This runs when user clicks OK
                    itemWasModified = true; // Mark that changes were saved
                    closeEditItemModal();
                    // Page will reload in closeBatchModal function
                });
            } else {
                showErrorModal('Error updating item: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating item:', error);
            showErrorModal('Failed to update item. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

/**
 * Load categories for the edit modal
 * @param {string} itemId - The inventory item ID
 */
function loadEditCategories(itemId) {
    const container = document.getElementById('editCategoriesContainer');

    container.innerHTML = `
        <div class="text-center py-4">
            <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-label text-gray-500 mt-2">Loading categories...</p>
        </div>
    `;

    // Fetch all categories and item's current categories
    Promise.all([
        fetch('../api/mi-get-categories.php').then(r => r.json()),
        fetch('../api/mi-get-item-categories.php?item_id=' + encodeURIComponent(itemId)).then(r => r.json())
    ])
        .then(([categoriesData, itemCategoriesData]) => {
            if (categoriesData.success && itemCategoriesData.success) {
                displayEditCategories(categoriesData.categories, itemCategoriesData.categories, itemCategoriesData.primary_category);
            } else {
                container.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-regular text-danger">Failed to load categories</p>
                </div>
            `;
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-regular text-danger">Failed to load categories</p>
            </div>
        `;
        });
}

/**
 * Display categories in the edit modal
 * @param {Array} allCategories - All available categories
 * @param {Array} selectedCategories - Currently selected category IDs
 * @param {string} primaryCategory - Primary category ID
 */
function displayEditCategories(allCategories, selectedCategories, primaryCategory) {
    const container = document.getElementById('editCategoriesContainer');

    if (allCategories.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-regular text-gray-500">No categories available</p>
            </div>
        `;
        return;
    }

    let html = '<div class="space-y-2">';

    allCategories.forEach(category => {
        const isChecked = selectedCategories.includes(category.category_id);
        const isPrimary = category.category_id === primaryCategory;

        html += `
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                <label class="flex items-center flex-1 cursor-pointer">
                    <input
                        type="checkbox"
                        class="edit-category-checkbox w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                        value="${category.category_id}"
                        ${isChecked ? 'checked' : ''}
                        onchange="handleEditCategoryChange(this)">
                    <span class="ml-2 text-regular text-gray-700">${category.category_name}</span>
                </label>
                <label class="flex items-center cursor-pointer ml-4" title="Set as primary category">
                    <input
                        type="radio"
                        name="primary_category"
                        value="${category.category_id}"
                        class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                        ${isPrimary ? 'checked' : ''}
                        ${!isChecked ? 'disabled' : ''}
                        data-category-id="${category.category_id}">
                    <span class="ml-1 text-label text-gray-500">Primary</span>
                </label>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Handle category checkbox change in edit modal
 * @param {HTMLElement} checkbox - The checkbox element
 */
function handleEditCategoryChange(checkbox) {
    const categoryId = checkbox.value;
    const primaryRadio = document.querySelector(`input[name="primary_category"][value="${categoryId}"]`);

    if (checkbox.checked) {
        // Enable the primary radio button
        if (primaryRadio) {
            primaryRadio.disabled = false;
        }
    } else {
        // Disable and uncheck the primary radio button
        if (primaryRadio) {
            primaryRadio.disabled = true;
            primaryRadio.checked = false;
        }

        // If this was the only checked category, uncheck all primary radios
        const checkedCategories = document.querySelectorAll('.edit-category-checkbox:checked');
        if (checkedCategories.length === 0) {
            document.querySelectorAll('input[name="primary_category"]').forEach(radio => {
                radio.checked = false;
            });
        }
    }
}

/**
 * Open add item modal
 */
function openAddItemModal() {
    // Reset form
    document.getElementById('addItemForm').reset();

    // Reset image preview to default
    document.getElementById('addItemImagePreview').src = '../assets/images/inventory-item/default-item.png';

    // Reset checkboxes
    document.querySelectorAll('.add-item-category-checkbox').forEach(cb => {
        cb.checked = false;
    });
    document.querySelectorAll('input[name="add_primary_category"]').forEach(radio => {
        radio.checked = false;
        radio.disabled = true;
    });

    // Hide/show initial batch section
    document.getElementById('addInitialBatchSection').classList.add('hidden');
    document.getElementById('addInitialBatchCheckbox').checked = false;

    // Load categories
    loadAddItemCategories();

    // Show modal
    document.getElementById('addItemModal').classList.remove('hidden');
}

/**
 * Close add item modal
 */
function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
    document.getElementById('addItemForm').reset();
}

/**
 * Load categories for add item modal
 */
function loadAddItemCategories() {
    const container = document.getElementById('addItemCategoriesContainer');

    container.innerHTML = `
        <div class="text-center py-4">
            <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-label text-gray-500 mt-2">Loading categories...</p>
        </div>
    `;

    fetch('../api/mi-get-categories.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                displayAddItemCategories(data.categories);
            } else {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-regular text-danger">Failed to load categories</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            container.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-regular text-danger">Failed to load categories</p>
                </div>
            `;
        });
}

/**
 * Save new item
 */
function saveNewItem() {
    const itemName = document.getElementById('addItemName').value.trim();
    const quantityUnit = document.getElementById('addItemQuantityUnit').value.trim();
    const reorderLevel = document.getElementById('addItemReorderLevel').value;
    const imageInput = document.getElementById('addItemImageInput');

    // Validation
    if (!itemName) {
        showWarningModal('Please enter an item name');
        return;
    }
    if (!quantityUnit) {
        showWarningModal('Please enter a quantity unit');
        return;
    }
    if (!reorderLevel || parseFloat(reorderLevel) < 0) {
        showWarningModal('Please enter a valid reorder level (can be 0)');
        return;
    }

    // Get selected categories
    const categoryCheckboxes = document.querySelectorAll('.add-item-category-checkbox:checked');
    const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value);

    // Validate at least one category
    if (selectedCategories.length === 0) {
        showWarningModal('Please select at least one category');
        return;
    }

    // Get primary category
    const primaryCategoryRadio = document.querySelector('input[name="add_primary_category"]:checked');
    const primaryCategory = primaryCategoryRadio ? primaryCategoryRadio.value : '';

    // Create FormData
    const formData = new FormData();
    formData.append('item_name', itemName);
    formData.append('quantity_unit', quantityUnit);
    formData.append('reorder_level', reorderLevel);
    formData.append('categories', JSON.stringify(selectedCategories));
    formData.append('primary_category', primaryCategory);

    // Add image if selected
    if (imageInput.files.length > 0) {
        formData.append('item_image', imageInput.files[0]);
    }

    // Check if adding initial batch
    const addInitialBatch = document.getElementById('addInitialBatchCheckbox').checked;
    formData.append('add_initial_batch', addInitialBatch ? '1' : '0');

    if (addInitialBatch) {
        const batchTitle = document.getElementById('addItemBatchTitle').value.trim();
        const batchQuantity = document.getElementById('addItemBatchInitialQuantity').value;
        const batchCost = document.getElementById('addItemBatchTotalCost').value;
        const batchObtainedDate = document.getElementById('addItemBatchObtainedDate').value;
        const batchExpirationDate = document.getElementById('addItemBatchExpirationDate').value;

        // Validate batch fields
        if (!batchTitle) {
            showWarningModal('Please enter a batch title');
            return;
        }
        if (!batchQuantity || parseFloat(batchQuantity) <= 0) {
            showWarningModal('Please enter a valid batch quantity');
            return;
        }
        if (!batchCost || parseFloat(batchCost) < 0) {
            showWarningModal('Please enter a valid batch cost');
            return;
        }
        if (!batchObtainedDate) {
            showWarningModal('Please select batch obtained date');
            return;
        }

        // Validate expiration date if provided
        if (batchExpirationDate) {
            const obtained = new Date(batchObtainedDate);
            const expiration = new Date(batchExpirationDate);

            if (expiration <= obtained) {
                showWarningModal('Batch expiration date must be after obtained date');
                return;
            }
        }

        formData.append('batch_title', batchTitle);
        formData.append('batch_initial_quantity', batchQuantity);
        formData.append('batch_total_cost', batchCost);
        formData.append('batch_obtained_date', batchObtainedDate);
        if (batchExpirationDate) {
            formData.append('batch_expiration_date', batchExpirationDate);
        }
    }

    // Show loading state
    const saveButton = document.querySelector('#addItemModal button[onclick="saveNewItem()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Creating...';
    saveButton.disabled = true;

    // Send request
    fetch('../api/mi-add-item.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Item created successfully!', function () {
                    closeAddItemModal();
                    window.location.reload();
                });
            } else {
                showErrorModal('Error creating item: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error creating item:', error);
            showErrorModal('Failed to create item. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

/**
 * Display categories in add item modal
 * @param {Array} categories - All available categories
 */
function displayAddItemCategories(categories) {
    const container = document.getElementById('addItemCategoriesContainer');

    if (categories.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-regular text-gray-500">No categories available</p>
            </div>
        `;
        return;
    }

    let html = '<div class="space-y-2">';

    categories.forEach(category => {
        html += `
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                <label class="flex items-center flex-1 cursor-pointer">
                    <input
                        type="checkbox"
                        class="add-item-category-checkbox w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                        value="${category.category_id}"
                        onchange="handleAddItemCategoryChange(this)">
                    <span class="ml-2 text-regular text-gray-700">${category.category_name}</span>
                </label>
                <label class="flex items-center cursor-pointer ml-4" title="Set as primary category">
                    <input
                        type="radio"
                        name="add_primary_category"
                        value="${category.category_id}"
                        class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                        disabled
                        data-category-id="${category.category_id}">
                    <span class="ml-1 text-label text-gray-500">Primary</span>
                </label>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Handle category checkbox change in add item modal
 * @param {HTMLElement} checkbox - The checkbox element
 */
function handleAddItemCategoryChange(checkbox) {
    const categoryId = checkbox.value;
    const primaryRadio = document.querySelector(`input[name="add_primary_category"][value="${categoryId}"]`);

    if (checkbox.checked) {
        // Enable the primary radio button
        if (primaryRadio) {
            primaryRadio.disabled = false;
        }
    } else {
        // Disable and uncheck the primary radio button
        if (primaryRadio) {
            primaryRadio.disabled = true;
            primaryRadio.checked = false;
        }

        // If this was the only checked category, uncheck all primary radios
        const checkedCategories = document.querySelectorAll('.add-item-category-checkbox:checked');
        if (checkedCategories.length === 0) {
            document.querySelectorAll('input[name="add_primary_category"]').forEach(radio => {
                radio.checked = false;
            });
        }
    }
}

/**
 * Toggle initial batch section
 */
function toggleInitialBatchSection() {
    const checkbox = document.getElementById('addInitialBatchCheckbox');
    const section = document.getElementById('addInitialBatchSection');

    if (checkbox.checked) {
        section.classList.remove('hidden');
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('addItemBatchObtainedDate').value = today;
    } else {
        section.classList.add('hidden');
    }
}

/**
 * Update cost per unit in add item modal
 */
function updateAddItemCostPerUnit() {
    const quantity = parseFloat(document.getElementById('addItemBatchInitialQuantity').value) || 0;
    const totalCost = parseFloat(document.getElementById('addItemBatchTotalCost').value) || 0;

    const costPerUnit = quantity > 0 ? (totalCost / quantity) : 0;
    document.getElementById('addItemCostPerUnitDisplay').textContent = '₱' + costPerUnit.toFixed(2);
}

// Handle image preview for edit item
const editImageInput = document.getElementById('editItemImageInput');
if (editImageInput) {
    editImageInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or WEBP)');
                e.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size must be less than 5MB');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('editItemImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}