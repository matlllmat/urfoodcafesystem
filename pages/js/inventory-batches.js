// ============================================
// BATCH PRIORITY STATE VARIABLES
// ============================================
let currentPriorityMethod = 'fifo';
let hasExpiryDates = false;
let currentBatches = [];
let isDragging = false;

/**
 * Open batch details modal
 * @param {string} itemId - The inventory item ID
 * @param {string} itemName - The inventory item name
 */
function openBatchModal(itemId, itemName) {
    currentItemId = itemId;
    currentItemName = itemName;

    document.getElementById('modalItemName').textContent = 'Item Details & Batches';
    document.getElementById('modalItemInfo').textContent = 'Item ID: ' + itemId;
    document.getElementById('batchModal').classList.remove('hidden');

    // Initially hide the dispose button until we know if there are expired batches
    const disposeButton = document.querySelector('#batchModal button[onclick="openDisposeModalFromBatchView()"]');
    if (disposeButton) {
        disposeButton.style.display = 'none';
    }

    // Load both item details and batches
    loadItemDetails(itemId);
    loadBatches(itemId);
}

function closeBatchModal() {
    document.getElementById('batchModal').classList.add('hidden');
    currentItemId = null;
    currentItemName = null;

    // If item was modified, reload the page to show changes in cards
    if (itemWasModified) {
        window.location.reload();
    }
}

/**
 * Load batches for an item via AJAX
 * @param {string} itemId - The inventory item ID
 */
function loadBatches(itemId) {
    const batchesList = document.getElementById('batchesList');
    batchesList.innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-regular text-gray-500 mt-2">Loading batches...</p>
        </div>
    `;

    // Fetch batches via AJAX
    fetch('../api/mi-get-batches.php?item_id=' + encodeURIComponent(itemId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentPriorityMethod = data.priority_method || 'fifo';
                hasExpiryDates = data.has_expiry_dates || false;
                currentBatches = data.batches || [];

                // Update priority method radio buttons
                updatePriorityMethodUI(currentPriorityMethod, hasExpiryDates);

                // Display batches
                displayBatches(currentBatches);
            } else {
                batchesList.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-regular text-gray-500">${data.message || 'Error loading batches'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading batches:', error);
            batchesList.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-regular text-danger">Failed to load batches. Please try again.</p>
                </div>
            `;
        });
}

/**
 * Update priority method UI (radio buttons and visibility)
 * @param {string} method - Current priority method (fifo, fefo, manual)
 * @param {boolean} hasExpiry - Whether the item has batches with expiration dates
 */
function updatePriorityMethodUI(method, hasExpiry) {
    // Set the selected radio button
    const radios = document.querySelectorAll('input[name="priorityMethod"]');
    radios.forEach(radio => {
        radio.checked = (radio.value === method);
    });

    // Handle FEFO option visibility
    const fefoOption = document.getElementById('fefoOption');
    const fefoRadio = document.querySelector('input[value="fefo"]');

    if (!hasExpiry) {
        // Disable FEFO if no expiration dates
        fefoOption.classList.add('opacity-50', 'cursor-not-allowed');
        fefoRadio.disabled = true;
        fefoOption.title = 'FEFO is not available because no batches have expiration dates';

        // If currently set to FEFO but no expiry dates, switch to FIFO
        if (method === 'fefo') {
            document.querySelector('input[value="fifo"]').checked = true;
            currentPriorityMethod = 'fifo';
        }
    } else {
        fefoOption.classList.remove('opacity-50', 'cursor-not-allowed');
        fefoRadio.disabled = false;
        fefoOption.title = '';
    }

    // Update info message
    updatePriorityInfoMessage(method);
}

/**
 * Update the info message based on priority method
 * @param {string} method - Priority method
 */
function updatePriorityInfoMessage(method) {
    const infoDiv = document.getElementById('priorityInfoMessage');
    const infoText = document.getElementById('priorityInfoText');

    let message = '';
    if (method === 'fifo') {
        message = 'Batches will be used from oldest to newest based on obtained date.';
    } else if (method === 'fefo') {
        message = 'Batches will be used starting with the nearest expiration date.';
    } else if (method === 'manual') {
        message = 'Drag and drop batches below to set custom priority order.';
    }

    infoText.textContent = message;
    infoDiv.classList.remove('hidden');
}

/**
 * Display batches in the modal
 * @param {Array} batches - Array of batch objects
 */
function displayBatches(batches) {
    const batchesList = document.getElementById('batchesList');

    if (batches.length === 0) {
        batchesList.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-regular text-gray-500">No batches found for this item</p>
                <p class="text-label text-gray-400 mt-1">Click "Add Batch" to create a new batch</p>
            </div>
        `;
        return;
    }

    // Separate active and depleted batches
    const activeBatches = batches.filter(b => parseFloat(b.current_quantity) > 0);
    const depletedBatches = batches.filter(b => parseFloat(b.current_quantity) <= 0);

    const isManualMode = currentPriorityMethod === 'manual';
    const draggableAttr = isManualMode ? 'draggable="true"' : '';

    let html = `<div id="sortableBatchesList" class="space-y-3">`;

    // Render active batches
    activeBatches.forEach((batch, index) => {
        html += buildBatchCardHTML(batch, index, isManualMode, draggableAttr, false);
    });
    html += '</div>';

    // Render depleted batches in collapsible section
    if (depletedBatches.length > 0) {
        html += `
            <div class="mt-6 border-t border-gray-200 pt-4">
                <button
                    onclick="toggleDepletedBatches()"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <span class="text-regular font-medium text-gray-600">Depleted Batches</span>
                        <span class="text-xs bg-gray-300 text-gray-600 px-2 py-0.5 rounded-full">${depletedBatches.length}</span>
                    </div>
                    <svg id="depletedBatchesArrow" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="depletedBatchesList" class="hidden space-y-3 mt-3">`;

        depletedBatches.forEach((batch, index) => {
            html += buildBatchCardHTML(batch, index, false, '', true);
        });

        html += `
                </div>
            </div>`;
    }

    // Show empty active state if all batches are depleted
    if (activeBatches.length === 0 && depletedBatches.length > 0) {
        const emptyActive = `
            <div class="text-center py-6">
                <p class="text-regular text-gray-500">All batches are depleted</p>
                <p class="text-label text-gray-400 mt-1">Click "Add Batch" to create a new batch</p>
            </div>`;
        html = emptyActive + html.replace('<div id="sortableBatchesList" class="space-y-3"></div>', '');
    }

    batchesList.innerHTML = html;
}

/**
 * Toggle depleted batches section visibility
 */
function toggleDepletedBatches() {
    const list = document.getElementById('depletedBatchesList');
    const arrow = document.getElementById('depletedBatchesArrow');
    if (list.classList.contains('hidden')) {
        list.classList.remove('hidden');
        arrow.style.transform = 'rotate(180deg)';
    } else {
        list.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

/**
 * Build HTML for a single batch card
 * @param {Object} batch - Batch data
 * @param {number} index - Display index
 * @param {boolean} isManualMode - Whether manual priority mode is active
 * @param {string} draggableAttr - Draggable attribute string
 * @param {boolean} isDepleted - Whether this is in the depleted section
 * @returns {string} HTML string
 */
function buildBatchCardHTML(batch, index, isManualMode, draggableAttr, isDepleted) {
    const costPerUnit = batch.initial_quantity > 0 ? (batch.total_cost / batch.initial_quantity) : 0;
    const remainingPercentage = batch.initial_quantity > 0 ? ((batch.current_quantity / batch.initial_quantity) * 100) : 0;

    const isExpired = batch.expiration_date && new Date(batch.expiration_date) < new Date();
    const hasQuantity = parseFloat(batch.current_quantity) > 0;

    let statusClass = 'bg-green-100 text-success';
    let statusText = 'Available';

    if (parseFloat(batch.current_quantity) <= 0) {
        statusClass = 'bg-gray-100 text-gray-600';
        statusText = 'Depleted';
    } else if (isExpired) {
        statusClass = 'bg-red-100 text-danger';
        statusText = 'Expired';
    } else if (batch.expiration_date) {
        const daysUntilExpiry = Math.ceil((new Date(batch.expiration_date) - new Date()) / (1000 * 60 * 60 * 24));
        if (daysUntilExpiry <= 7) {
            statusClass = 'bg-yellow-100 text-warning';
            statusText = `Expires in ${daysUntilExpiry} days`;
        }
    }

    // Depleted cards are styled differently
    const cardOpacity = isDepleted ? 'opacity-60' : '';
    const borderClass = isDepleted ? 'border-gray-200' : (isExpired && hasQuantity ? 'border-red-600 border-2' : 'border-gray-200');
    const dragClass = (!isDepleted && isManualMode) ? 'cursor-move hover:bg-gray-100 transition-colors' : '';

    return `
        <div
            class="bg-gray-50 rounded-lg p-4 border ${borderClass} ${dragClass} ${cardOpacity}"
            data-batch-id="${batch.batch_id}"
            ${!isDepleted ? draggableAttr : ''}
            ${!isDepleted ? 'ondragstart="handleDragStart(event)" ondragover="handleDragOver(event)" ondrop="handleDrop(event)" ondragend="handleDragEnd(event)"' : ''}>
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3 flex-1">
                    ${!isDepleted && isManualMode ? `
                        <div class="drag-handle cursor-grab active:cursor-grabbing">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>
                        <span class="flex items-center justify-center w-8 h-8 bg-black text-white rounded-full text-label font-bold">${index + 1}</span>
                    ` : `
                        <span class="flex items-center justify-center w-8 h-8 ${isDepleted ? 'bg-gray-200 text-gray-500' : 'bg-gray-300 text-gray-700'} rounded-full text-label font-medium">${index + 1}</span>
                    `}
                    <div>
                        <h4 class="text-product ${isDepleted ? 'text-gray-500' : 'text-gray-800'}">${batch.batch_title}</h4>
                        <p class="text-label text-gray-500">Batch ID: ${batch.batch_id}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-1 rounded ${statusClass}">${statusText}</span>

                    <!-- 3-Dots Menu -->
                    <div class="relative">
                        <button
                            onclick="toggleBatchMenu('${batch.batch_id}')"
                            class="p-1 hover:bg-gray-200 rounded transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>

                        <div
                            id="batchMenu-${batch.batch_id}"
                            class="hidden absolute right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10 min-w-[160px]">
                            <button
                                onclick="openEditBatchModal('${batch.batch_id}')"
                                class="w-full text-left px-4 py-2 text-regular text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Batch
                            </button>
                            <button
                                onclick="openAdjustQuantityModal('${batch.batch_id}', ${batch.current_quantity})"
                                class="w-full text-left px-4 py-2 text-regular text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2 border-t border-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                                Correct Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                <div>
                    <p class="text-label text-gray-500 mb-1">Current Qty</p>
                    <p class="text-regular text-gray-800">${parseFloat(batch.current_quantity).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-label text-gray-500 mb-1">Initial Qty</p>
                    <p class="text-regular text-gray-600">${parseFloat(batch.initial_quantity).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-label text-gray-500 mb-1">Cost/Unit</p>
                    <p class="text-regular text-gray-800">₱${costPerUnit.toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Cost</p>
                    <p class="text-regular text-gray-800">₱${parseFloat(batch.total_cost).toFixed(2)}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <p class="text-label text-gray-500 mb-1">Obtained Date</p>
                    <p class="text-regular text-gray-600">${batch.obtained_date}</p>
                </div>
                <div>
                    <p class="text-label text-gray-500 mb-1">Expiration Date</p>
                    <p class="text-regular ${isExpired ? 'text-danger font-medium' : 'text-gray-600'}">${batch.expiration_date || 'N/A'}</p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-2">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-label text-gray-500">Remaining</p>
                    <p class="text-label text-gray-600">${remainingPercentage.toFixed(1)}%</p>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: ${remainingPercentage}%"></div>
                </div>
            </div>

            ${batch.supplier_id ? `<p class="text-label text-gray-500 mb-3">Supplier: ${batch.supplier_id}</p>` : ''}

            ${isExpired && hasQuantity ? `
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <button
                        onclick="event.stopPropagation(); openDisposeSingleBatch('${batch.batch_id}')"
                        style="background-color: #B71C1C; color: #ffffff;"
                        onmouseover="this.style.backgroundColor='#8b0000'"
                        onmouseout="this.style.backgroundColor='#B71C1C'"
                        class="w-full px-4 py-2 rounded-md text-regular transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Dispose This Batch
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Open add batch modal
 */
function openAddBatchForm() {
    if (!currentItemId) {
        showErrorModal('No item selected');
        return;
    }

    // Reset form
    document.getElementById('addBatchForm').reset();

    // Set today's date as default for obtained date
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('addBatchObtainedDate').value = today;

    // Show modal
    document.getElementById('addBatchModal').classList.remove('hidden');
}

/**
 * Save new batch
 */
function saveNewBatch() {
    const batchTitle = document.getElementById('addBatchTitle').value.trim();
    const initialQuantity = document.getElementById('addBatchInitialQuantity').value;
    const totalCost = document.getElementById('addBatchTotalCost').value;
    const obtainedDate = document.getElementById('addBatchObtainedDate').value;
    const expirationDate = document.getElementById('addBatchExpirationDate').value;

    // Validation
    if (!batchTitle) {
        showWarningModal('Please enter a batch title');
        return;
    }
    if (!initialQuantity || parseFloat(initialQuantity) <= 0) {
        showWarningModal('Please enter a valid initial quantity');
        return;
    }
    if (!totalCost || parseFloat(totalCost) < 0) {
        showWarningModal('Please enter a valid total cost');
        return;
    }
    if (!obtainedDate) {
        showWarningModal('Please select an obtained date');
        return;
    }

    // Validate expiration date if provided
    if (expirationDate) {
        const obtained = new Date(obtainedDate);
        const expiration = new Date(expirationDate);

        if (expiration <= obtained) {
            showWarningModal('Expiration date must be after obtained date');
            return;
        }
    }

    // Create FormData
    const formData = new FormData();
    formData.append('inventory_id', currentItemId);
    formData.append('batch_title', batchTitle);
    formData.append('initial_quantity', initialQuantity);
    formData.append('total_cost', totalCost);
    formData.append('obtained_date', obtainedDate);
    if (expirationDate) {
        formData.append('expiration_date', expirationDate);
    }

    // Show loading state
    const saveButton = document.querySelector('#addBatchModal button[onclick="saveNewBatch()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    // Send request
    fetch('../api/mi-add-batch.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Batch added successfully!', function () {
                    closeAddBatchModal();
                    // Reload batches list
                    loadBatches(currentItemId);
                    // Reload item details to update total quantity
                    loadItemDetails(currentItemId);
                    // Mark that item was modified
                    itemWasModified = true;
                });
            } else {
                showErrorModal('Error adding batch: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adding batch:', error);
            showErrorModal('Failed to add batch. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

/**
 * Open edit batch modal
 * @param {string} batchId - The batch ID to edit
 */
function openEditBatchModal(batchId) {
    // Close the batch menu
    const menu = document.getElementById('batchMenu-' + batchId);
    if (menu) {
        menu.classList.add('hidden');
    }

    // Fetch batch details
    fetch('../api/mi-get-batch-details.php?batch_id=' + encodeURIComponent(batchId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const batch = data.batch;

                // Populate form fields
                document.getElementById('editBatchId').value = batch.batch_id;
                document.getElementById('editBatchTitle').value = batch.batch_title;
                document.getElementById('editBatchInitialQuantity').value = batch.initial_quantity;
                document.getElementById('editBatchTotalCost').value = batch.total_cost;
                document.getElementById('editBatchObtainedDate').value = batch.obtained_date;
                document.getElementById('editBatchExpirationDate').value = batch.expiration_date || '';

                // Update cost per unit display
                updateEditCostPerUnit();

                // Show modal
                document.getElementById('editBatchModal').classList.remove('hidden');
            } else {
                showErrorModal('Error loading batch details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error loading batch details:', error);
            showErrorModal('Failed to load batch details. Please try again.');
        });
}

/**
 * Save batch edits
 */
function saveEditBatch() {
    const batchId = document.getElementById('editBatchId').value;
    const batchTitle = document.getElementById('editBatchTitle').value.trim();
    const initialQuantity = document.getElementById('editBatchInitialQuantity').value;
    const totalCost = document.getElementById('editBatchTotalCost').value;
    const obtainedDate = document.getElementById('editBatchObtainedDate').value;
    const expirationDate = document.getElementById('editBatchExpirationDate').value;

    // Validation
    if (!batchTitle) {
        showWarningModal('Please enter a batch title');
        return;
    }
    if (!initialQuantity || parseFloat(initialQuantity) <= 0) {
        showWarningModal('Please enter a valid initial quantity');
        return;
    }
    if (!totalCost || parseFloat(totalCost) < 0) {
        showWarningModal('Please enter a valid total cost');
        return;
    }
    if (!obtainedDate) {
        showWarningModal('Please select an obtained date');
        return;
    }

    // Validate expiration date if provided
    if (expirationDate) {
        const obtained = new Date(obtainedDate);
        const expiration = new Date(expirationDate);

        if (expiration <= obtained) {
            showWarningModal('Expiration date must be after obtained date');
            return;
        }
    }

    // Create FormData
    const formData = new FormData();
    formData.append('batch_id', batchId);
    formData.append('batch_title', batchTitle);
    formData.append('initial_quantity', initialQuantity);
    formData.append('total_cost', totalCost);
    formData.append('obtained_date', obtainedDate);
    if (expirationDate) {
        formData.append('expiration_date', expirationDate);
    }

    // Show loading state
    const saveButton = document.querySelector('#editBatchModal button[onclick="saveEditBatch()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    // Send request
    fetch('../api/mi-update-batch.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Batch updated successfully!', function () {
                    closeEditBatchModal();
                    // Reload batches list
                    loadBatches(currentItemId);
                    // Reload item details to update totals
                    loadItemDetails(currentItemId);
                    // Mark that item was modified
                    itemWasModified = true;
                });
            } else {
                showErrorModal('Error updating batch: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating batch:', error);
            showErrorModal('Failed to update batch. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

/**
 * Toggle batch action menu
 * @param {string} batchId - The batch ID
 */
function toggleBatchMenu(batchId) {
    const menu = document.getElementById('batchMenu-' + batchId);
    const isHidden = menu.classList.contains('hidden');

    // Close all other batch menus
    document.querySelectorAll('[id^="batchMenu-"]').forEach(m => {
        if (m.id !== 'batchMenu-' + batchId) {
            m.classList.add('hidden');
        }
    });

    // Toggle current menu
    if (isHidden) {
        menu.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
    }
}

/**
 * Open dispose modal for a single batch (reuses multi-batch disposal)
 * @param {string} batchId - The batch ID
 */
function openDisposeSingleBatch(batchId) {
    if (!currentItemId) {
        showErrorModal('No item selected');
        return;
    }

    currentDisposeItemId = currentItemId;

    document.getElementById('disposeItemName').textContent = currentItemName;
    document.getElementById('disposalReason').value = 'Items expired and deemed unsafe for consumption. Disposed as per food safety protocols.';

    const list = document.getElementById('expiredBatchesList');
    list.innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-regular text-gray-500 mt-2">Loading batch...</p>
        </div>
    `;
    
    // Fetch the batch details
    fetch('../api/mi-get-batch-details.php?batch_id=' + encodeURIComponent(batchId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const batch = data.batch;
                
                // Display this single batch using the existing displayExpiredBatches function
                displayExpiredBatches([batch]);
            } else {
                list.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-regular text-danger">Failed to load batch details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading batch details:', error);
            list.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-regular text-danger">Failed to load batch details</p>
                </div>
            `;
        });
    
    // Show modal
    document.getElementById('disposeModal').classList.remove('hidden');
}

/**
 * Load item details for the sidebar
 * @param {string} itemId - The inventory item ID
 */
function loadItemDetails(itemId) {
    const sidebar = document.getElementById('itemDetailsSidebar');
    sidebar.innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-regular text-gray-500 mt-2">Loading details...</p>
        </div>
    `;

    // Fetch item details via AJAX
    fetch('../api/mi-get-item-details.php?item_id=' + encodeURIComponent(itemId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayItemDetails(data.item);
            } else {
                sidebar.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-regular text-gray-500">${data.message || 'Error loading item details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading item details:', error);
            sidebar.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-regular text-danger">Failed to load item details. Please try again.</p>
                </div>
            `;
        });
}

/**
 * Display item details in the sidebar
 * @param {Object} item - Item data object
 */
function displayItemDetails(item) {
    const sidebar = document.getElementById('itemDetailsSidebar');

    // Determine stock status styling
    let stockStatusClass = 'bg-green-100 text-success';
    if (item.stock_status === 'Out of Stock') {
        stockStatusClass = 'bg-red-100 text-danger';
    } else if (item.stock_status === 'Low Stock') {
        stockStatusClass = 'bg-yellow-100 text-warning';
    }

    // Build categories display
    let categoriesHtml = '<p class="text-regular text-gray-600">None</p>';
    if (item.all_categories) {
        const categories = item.all_categories.split(', ');
        categoriesHtml = categories.map(cat =>
            `<span class="inline-block bg-gray-200 text-gray-700 px-2 py-1 rounded text-label mr-1 mb-1">${cat}</span>`
        ).join('');
    }

    // Default image if none exists
    const imagePath = item.image_filename
        ? `../assets/images/inventory-item/${item.image_filename}`
        : '../assets/images/inventory-item/default-item.png';

    // Build dynamic expiration warning
    let expirationWarningHtml = '';
    if (item.has_expired == 1 && item.has_expiring_soon == 1) {
        // Both expired and expiring soon
        expirationWarningHtml = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-regular text-danger font-medium">Expired & Expiring Soon</p>
                        <p class="text-label text-gray-600">Some batches have expired and others expire within 7 days</p>
                    </div>
                </div>
            </div>
        `;
    } else if (item.has_expired == 1) {
        // Only expired
        expirationWarningHtml = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-regular text-danger font-medium">Expired Batches</p>
                        <p class="text-label text-gray-600">Some batches have expired and need disposal</p>
                    </div>
                </div>
            </div>
        `;
    } else if (item.has_expiring_soon == 1) {
        // Only expiring soon
        expirationWarningHtml = `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-regular text-warning font-medium">Expiring Soon</p>
                        <p class="text-label text-gray-600">Some batches expire within 7 days</p>
                    </div>
                </div>
            </div>
        `;
    }

    sidebar.innerHTML = `
        <div class="space-y-6">
            <!-- Item Image -->
            <div>
                <img src="${imagePath}" alt="${item.item_name}" 
                     class="w-full h-48 object-cover rounded-lg border border-gray-300"
                     onerror="this.src='../assets/images/inventory-item/default-item.png'">
            </div>

            <!-- Item Name -->
            <div>
                <h3 class="text-product text-gray-800 mb-2">${item.item_name}</h3>
                <p class="text-label text-gray-500">ID: ${item.item_id}</p>
            </div>

            <!-- Stock Status -->
            <div>
                <p class="text-label text-gray-500 mb-2">Stock Status</p>
                <span class="inline-block px-3 py-1 rounded-full text-label ${stockStatusClass}">
                    ${item.stock_status}
                </span>
            </div>

            <!-- Quantity Info -->
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-label text-gray-500 mb-1">Total Quantity</p>
                        <p class="text-product text-gray-800">${parseFloat(item.total_quantity).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Unit</p>
                        <p class="text-product text-gray-800">${item.quantity_unit}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Reorder Level</p>
                        <p class="text-product text-gray-800">${parseFloat(item.reorder_level).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Total Value</p>
                        <p class="text-product text-gray-800">₱${parseFloat(item.total_value).toFixed(2)}</p>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div>
                <p class="text-label text-gray-500 mb-2">Categories</p>
                <div class="flex flex-wrap">
                    ${categoriesHtml}
                </div>
            </div>

            <!-- Expiration Warning (Dynamic) -->
            ${expirationWarningHtml}

            <!-- Last Updated -->
            <div>
                <p class="text-label text-gray-500 mb-1">Last Updated</p>
                <p class="text-regular text-gray-600">${item.updated_at}</p>
            </div>

        </div>
    `;
}

/**
 * Close add batch modal
 */
function closeAddBatchModal() {
    document.getElementById('addBatchModal').classList.add('hidden');
    document.getElementById('addBatchForm').reset();
}

/**
 * Close edit batch modal
 */
function closeEditBatchModal() {
    document.getElementById('editBatchModal').classList.add('hidden');
    document.getElementById('editBatchForm').reset();
}

/**
 * Update cost per unit in edit modal
 */
function updateEditCostPerUnit() {
    const quantity = parseFloat(document.getElementById('editBatchInitialQuantity').value) || 0;
    const totalCost = parseFloat(document.getElementById('editBatchTotalCost').value) || 0;

    const costPerUnit = quantity > 0 ? (totalCost / quantity) : 0;
    document.getElementById('editCostPerUnitDisplay').textContent = '₱' + costPerUnit.toFixed(2);
}



/**
 * Open adjust quantity modal
 * @param {string} batchId - The batch ID
 * @param {number} currentQuantity - Current quantity of the batch
 */
function openAdjustQuantityModal(batchId, currentQuantity) {
    // Close the batch menu
    const menu = document.getElementById('batchMenu-' + batchId);
    if (menu) {
        menu.classList.add('hidden');
    }

    // Reset form first, then set values (reset clears all fields including hidden ones)
    document.getElementById('adjustQuantityForm').reset();

    // Set batch ID and current quantity after reset
    document.getElementById('adjustBatchId').value = batchId;
    document.getElementById('adjustCurrentQuantity').textContent = parseFloat(currentQuantity).toFixed(2);
    document.getElementById('adjustmentType').value = 'add';
    document.getElementById('adjustmentAmount').value = '';
    document.getElementById('adjustmentReason').value = '';

    // Update new quantity display
    updateNewQuantityDisplay();

    // Show modal
    document.getElementById('adjustQuantityModal').classList.remove('hidden');
}

/**
 * Close adjust quantity modal
 */
function closeAdjustQuantityModal() {
    document.getElementById('adjustQuantityModal').classList.add('hidden');
    document.getElementById('adjustQuantityForm').reset();
}

/**
 * Update new quantity display based on adjustment
 */
function updateNewQuantityDisplay() {
    const currentQty = parseFloat(document.getElementById('adjustCurrentQuantity').textContent) || 0;
    const adjustmentType = document.getElementById('adjustmentType').value;
    const adjustmentAmount = parseFloat(document.getElementById('adjustmentAmount').value) || 0;

    let newQty = currentQty;
    if (adjustmentType === 'add') {
        newQty = currentQty + adjustmentAmount;
    } else if (adjustmentType === 'subtract') {
        newQty = currentQty - adjustmentAmount;
    } else if (adjustmentType === 'set') {
        newQty = adjustmentAmount;
    }

    // Ensure not negative
    if (newQty < 0) {
        newQty = 0;
    }

    document.getElementById('newQuantityDisplay').textContent = newQty.toFixed(2);

    // Show warning if new quantity is 0
    const warningDiv = document.getElementById('zeroQuantityWarning');
    if (newQty === 0) {
        warningDiv.classList.remove('hidden');
    } else {
        warningDiv.classList.add('hidden');
    }
}

/**
 * Save quantity adjustment
 */
function saveQuantityAdjustment() {
    const batchId = document.getElementById('adjustBatchId').value;
    const adjustmentType = document.getElementById('adjustmentType').value;
    const adjustmentAmount = document.getElementById('adjustmentAmount').value;
    const adjustmentReason = document.getElementById('adjustmentReason').value.trim();
    const currentQty = parseFloat(document.getElementById('adjustCurrentQuantity').textContent) || 0;

    // Validation
    if (!adjustmentAmount || parseFloat(adjustmentAmount) < 0) {
        showWarningModal('Please enter a valid adjustment amount');
        return;
    }

    if (!adjustmentReason) {
        showWarningModal('Please provide a reason for this adjustment');
        return;
    }

    // Calculate new quantity
    let newQty = currentQty;
    if (adjustmentType === 'add') {
        newQty = currentQty + parseFloat(adjustmentAmount);
    } else if (adjustmentType === 'subtract') {
        newQty = currentQty - parseFloat(adjustmentAmount);
    } else if (adjustmentType === 'set') {
        newQty = parseFloat(adjustmentAmount);
    }

    if (newQty < 0) {
        showWarningModal('Adjustment would result in negative quantity. Please use "Set to Exact Value" option instead.');
        return;
    }

    // Create FormData
    const formData = new FormData();
    formData.append('batch_id', batchId);
    formData.append('adjustment_type', adjustmentType);
    formData.append('adjustment_amount', adjustmentAmount);
    formData.append('new_quantity', newQty);
    formData.append('reason', adjustmentReason);

    // Show loading state
    const saveButton = document.querySelector('#adjustQuantityModal button[onclick="saveQuantityAdjustment()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    // Send request
    fetch('../api/mi-adjust-batch-quantity.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Quantity adjusted successfully!', function () {
                    closeAdjustQuantityModal();
                    // Reload batches list
                    loadBatches(currentItemId);
                    // Reload item details to update totals
                    loadItemDetails(currentItemId);
                    // Mark that item was modified
                    itemWasModified = true;
                });
            } else {
                showErrorModal('Error adjusting quantity: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adjusting quantity:', error);
            showErrorModal('Failed to adjust quantity. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

// Close batch menus when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('[id^="batchMenu-"]') && !e.target.closest('button[onclick^="toggleBatchMenu"]')) {
        document.querySelectorAll('[id^="batchMenu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// ============================================
// BATCH PRIORITY FUNCTIONS
// ============================================

/**
 * Handle priority method change
 * @param {string} method - Selected priority method (fifo, fefo, manual)
 */
function handlePriorityMethodChange(method) {
    if (!currentItemId) return;

    // Don't show confirmation if same as current
    if (method === currentPriorityMethod) {
        return;
    }

    // Method name mapping
    const methodNames = {
        'fifo': 'FIFO (First In, First Out)',
        'fefo': 'FEFO (First Expired, First Out)',
        'manual': 'Manual (Custom Order)'
    };

    // Show confirmation modal
    showItemPriorityConfirmation(
        'Change Batch Priority?',
        `<div class="space-y-3">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <p class="text-sm text-gray-600 mb-1">Item:</p>
                <p class="text-base font-semibold text-gray-800">${currentItemName}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <p class="text-sm text-gray-600 mb-1">Current Priority:</p>
                <p class="text-base font-semibold text-gray-800">${methodNames[currentPriorityMethod]}</p>
            </div>
            <div class="flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-600 mb-1">New Priority:</p>
                <p class="text-base font-semibold text-blue-800">${methodNames[method]}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <p><strong>Note:</strong> This will change how batches are prioritized for this item. Batch orders will be automatically recalculated.</p>
                </div>
            </div>
        </div>`,
        function() {
            // User confirmed - proceed with save
            confirmPriorityMethodChange(method);
        },
        function() {
            // User cancelled - revert radio selection
            updatePriorityMethodUI(currentPriorityMethod, hasExpiryDates);
        }
    );
}

/**
 * Actually change the priority method after confirmation
 * @param {string} method - Selected priority method
 */
function confirmPriorityMethodChange(method) {
    // Show loading state
    const radios = document.querySelectorAll('input[name="priorityMethod"]');
    radios.forEach(radio => radio.disabled = true);

    // Send update to server
    const formData = new FormData();
    formData.append('item_id', currentItemId);
    formData.append('priority_method', method);

    fetch('../api/mi-update-priority-method.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            radios.forEach(radio => radio.disabled = false);

            if (data.success) {
                currentPriorityMethod = method;
                updatePriorityInfoMessage(method);

                // Reload batches to show new order
                loadBatches(currentItemId);

                showSuccessModal('Priority method updated successfully!');
                itemWasModified = true;
            } else {
                showErrorModal('Error updating priority method: ' + (data.message || 'Unknown error'));
                // Revert radio selection
                updatePriorityMethodUI(currentPriorityMethod, hasExpiryDates);
            }
        })
        .catch(error => {
            console.error('Error updating priority method:', error);
            showErrorModal('Failed to update priority method. Please try again.');
            radios.forEach(radio => radio.disabled = false);
            updatePriorityMethodUI(currentPriorityMethod, hasExpiryDates);
        });
}

/**
 * Show confirmation modal for item priority change
 * @param {string} title - Modal title
 * @param {string} message - Modal message (can include HTML)
 * @param {function} onConfirm - Callback when user confirms
 * @param {function} onCancel - Callback when user cancels
 */
function showItemPriorityConfirmation(title, message, onConfirm, onCancel) {
    // Check if modal already exists
    let modal = document.getElementById('itemPriorityConfirmationModal');
    if (modal) {
        modal.remove();
    }

    // Create modal HTML
    const modalHTML = `
        <div id="itemPriorityConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <!-- Modal Header -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">${title}</h3>
                </div>

                <!-- Modal Content -->
                <div class="p-6">
                    ${message}
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        type="button"
                        id="itemPriorityCancelButton"
                        class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button
                        type="button"
                        id="itemPriorityConfirmButton"
                        class="bg-black text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-800 transition-colors">
                        Confirm Change
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Add event listener for confirm button
    document.getElementById('itemPriorityConfirmButton').addEventListener('click', function() {
        closeItemPriorityConfirmation();
        onConfirm();
    });

    // Add event listener for cancel button
    document.getElementById('itemPriorityCancelButton').addEventListener('click', function() {
        closeItemPriorityConfirmation();
        if (onCancel) onCancel();
    });

    // Add ESC key handler
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            closeItemPriorityConfirmation();
            if (onCancel) onCancel();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

/**
 * Close item priority confirmation modal
 */
function closeItemPriorityConfirmation() {
    const modal = document.getElementById('itemPriorityConfirmationModal');
    if (modal) {
        modal.remove();
    }
}

// ============================================
// DRAG AND DROP FUNCTIONS (for Manual Mode)
// ============================================

let draggedElement = null;
let draggedBatchId = null;
let originalBatchOrder = [];

/**
 * Handle drag start
 */
function handleDragStart(e) {
    if (currentPriorityMethod !== 'manual') return;

    draggedElement = e.currentTarget;
    draggedBatchId = draggedElement.getAttribute('data-batch-id');

    // Store original batch order before any changes
    if (originalBatchOrder.length === 0) {
        const list = document.getElementById('sortableBatchesList');
        const items = Array.from(list.children);
        originalBatchOrder = items.map(item => item.getAttribute('data-batch-id'));
    }

    e.currentTarget.style.opacity = '0.5';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.currentTarget.innerHTML);
}

/**
 * Handle drag over
 */
function handleDragOver(e) {
    if (currentPriorityMethod !== 'manual') return;
    if (e.preventDefault) {
        e.preventDefault();
    }

    e.dataTransfer.dropEffect = 'move';

    const targetElement = e.currentTarget;
    if (draggedElement && targetElement !== draggedElement) {
        const list = document.getElementById('sortableBatchesList');
        const items = Array.from(list.children);
        const draggedIndex = items.indexOf(draggedElement);
        const targetIndex = items.indexOf(targetElement);

        if (draggedIndex < targetIndex) {
            targetElement.parentNode.insertBefore(draggedElement, targetElement.nextSibling);
        } else {
            targetElement.parentNode.insertBefore(draggedElement, targetElement);
        }
    }

    return false;
}

/**
 * Handle drop
 */
function handleDrop(e) {
    if (currentPriorityMethod !== 'manual') return;
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    return false;
}

/**
 * Handle drag end - save new order
 */
function handleDragEnd(e) {
    if (currentPriorityMethod !== 'manual') return;

    e.currentTarget.style.opacity = '1';

    // Get new order of batch IDs
    const list = document.getElementById('sortableBatchesList');
    const items = Array.from(list.children);
    const newBatchOrder = items.map(item => item.getAttribute('data-batch-id'));

    // Check if order actually changed
    const orderChanged = JSON.stringify(originalBatchOrder) !== JSON.stringify(newBatchOrder);

    // Save new order to server (passing whether it changed)
    saveBatchOrder(newBatchOrder, orderChanged);

    // Reset original order tracker
    originalBatchOrder = [];
}

/**
 * Save batch order to server
 * @param {Array} batchOrder - Array of batch IDs in new order
 * @param {boolean} orderChanged - Whether the order actually changed
 */
function saveBatchOrder(batchOrder, orderChanged) {
    // If order didn't change, don't show success modal
    if (!orderChanged) {
        return;
    }

    const data = {
        item_id: currentItemId,
        batch_order: batchOrder
    };

    fetch('../api/mi-update-batch-priority.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload batches to update priority numbers
                loadBatches(currentItemId);
                showSuccessModal('Batch priority order updated!');
                itemWasModified = true;
            } else {
                showErrorModal('Error saving batch order: ' + (data.message || 'Unknown error'));
                // Reload to revert changes
                loadBatches(currentItemId);
            }
        })
        .catch(error => {
            console.error('Error saving batch order:', error);
            showErrorModal('Failed to save batch order. Please try again.');
            loadBatches(currentItemId);
        });
}