// ============================================
// DISPOSAL FUNCTIONS
// ============================================
let expiredBatchesToDispose = [];

/**
 * Open dispose modal from card quick action
 * @param {string} itemId - The inventory item ID
 * @param {string} itemName - The inventory item name
 */
function openDisposeModalFromCard(itemId, itemName) {
    currentDisposeItemId = itemId;

    document.getElementById('disposeItemName').textContent = itemName;
    document.getElementById('disposalReason').value = 'Items expired and deemed unsafe for consumption. Disposed as per food safety protocols.';

    // Load expired batches
    loadExpiredBatches(itemId);

    // Show modal
    document.getElementById('disposeModal').classList.remove('hidden');
}

/**
 * Open dispose modal from batch details modal
 * Uses the current item already loaded in batch modal
 */
function openDisposeModalFromBatchView() {
    if (!currentItemId) {
        showErrorModal('No item selected');
        return;
    }

    currentDisposeItemId = currentItemId;

    document.getElementById('disposeItemName').textContent = currentItemName;
    document.getElementById('disposalReason').value = 'Items expired and deemed unsafe for consumption. Disposed as per food safety protocols.';

    // Load expired batches
    loadExpiredBatches(currentItemId);

    // Show modal
    document.getElementById('disposeModal').classList.remove('hidden');
}

/**
 * Load expired batches for disposal
 * @param {string} itemId - The inventory item ID
 */
function loadExpiredBatches(itemId) {
    const list = document.getElementById('expiredBatchesList');

    list.innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-regular text-gray-500 mt-2">Loading expired batches...</p>
        </div>
    `;

    // Fetch expired batches
    fetch('../api/mi-get-expired-batches.php?item_id=' + encodeURIComponent(itemId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayExpiredBatches(data.batches);
            } else {
                list.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-regular text-gray-500">${data.message || 'No expired batches found'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading expired batches:', error);
            list.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-regular text-danger">Failed to load expired batches. Please try again.</p>
                </div>
            `;
        });
}

/**
 * Display expired batches in disposal modal
 * @param {Array} batches - Array of expired batch objects
 */
function displayExpiredBatches(batches) {
    const list = document.getElementById('expiredBatchesList');

    // Show/hide the dispose button in the batch modal based on expired batches
    const disposeButtonInBatchModal = document.querySelector('#batchModal button[onclick="openDisposeModalFromBatchView()"]');

    if (batches.length === 0) {
        list.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-regular text-gray-500">No expired batches found</p>
                <p class="text-label text-gray-400 mt-1">All batches are still valid</p>
            </div>
        `;
        expiredBatchesToDispose = [];
        updateDisposeTotalQuantity();

        // Hide dispose button when no expired batches
        if (disposeButtonInBatchModal) {
            disposeButtonInBatchModal.style.display = 'none';
        }

        return;
    }

    // Show dispose button when there are expired batches
    if (disposeButtonInBatchModal) {
        disposeButtonInBatchModal.style.display = 'flex';
    }

    // Store batches for disposal
    expiredBatchesToDispose = batches;

    let html = '<div class="space-y-3">';
    let totalQuantity = 0;

    batches.forEach(batch => {
        totalQuantity += parseFloat(batch.current_quantity);
        const costPerUnit = batch.initial_quantity > 0 ? (batch.total_cost / batch.initial_quantity) : 0;
        const totalValue = (batch.current_quantity / batch.initial_quantity) * batch.total_cost;

        // Calculate days expired
        const expDate = new Date(batch.expiration_date);
        const today = new Date();
        const daysExpired = Math.ceil((today - expDate) / (1000 * 60 * 60 * 24));

        html += `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h4 class="text-product text-gray-800">${batch.batch_title}</h4>
                        <p class="text-label text-gray-500">Batch ID: ${batch.batch_id}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-1 rounded bg-red-100 text-danger">
                            Expired ${daysExpired} day${daysExpired !== 1 ? 's' : ''} ago
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-2">
                    <div>
                        <p class="text-label text-gray-500 mb-1">Quantity</p>
                        <p class="text-regular text-gray-800">${parseFloat(batch.current_quantity).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Expiration Date</p>
                        <p class="text-regular text-danger">${batch.expiration_date}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Cost/Unit</p>
                        <p class="text-regular text-gray-800">₱${costPerUnit.toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-label text-gray-500 mb-1">Value Lost</p>
                        <p class="text-regular text-danger">₱${totalValue.toFixed(2)}</p>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    list.innerHTML = html;

    // Update total quantity display
    updateDisposeTotalQuantity();
}

/**
 * Update the total quantity to be disposed
 */
function updateDisposeTotalQuantity() {
    const totalQty = expiredBatchesToDispose.reduce((sum, batch) => sum + parseFloat(batch.current_quantity), 0);
    document.getElementById('disposeTotalQuantity').textContent = totalQty.toFixed(2);
}

/**
 * Close dispose modal
 */
function closeDisposeModal() {
    document.getElementById('disposeModal').classList.add('hidden');
    document.getElementById('disposalReason').value = '';
    expiredBatchesToDispose = [];
    currentDisposeItemId = null;
}

/**
 * Confirm and process disposal
 */
function confirmDisposal() {
    const reason = document.getElementById('disposalReason').value.trim();

    // Validation
    if (expiredBatchesToDispose.length === 0) {
        showWarningModal('No expired batches to dispose');
        return;
    }

    if (!reason) {
        showWarningModal('Please provide a reason for disposal');
        return;
    }

    if (reason.length < 10) {
        showWarningModal('Please provide a more detailed disposal reason (at least 10 characters)');
        return;
    }

    // Prepare batch IDs
    const batchIds = expiredBatchesToDispose.map(batch => batch.batch_id);

    // Create FormData
    const formData = new FormData();
    formData.append('item_id', currentDisposeItemId);
    formData.append('batch_ids', JSON.stringify(batchIds));
    formData.append('reason', reason);

    // Show loading state
    const confirmButton = document.querySelector('#disposeModal button[onclick="confirmDisposal()"]');
    const originalText = confirmButton.textContent;
    confirmButton.textContent = 'Processing...';
    confirmButton.disabled = true;

    // Send disposal request
    fetch('../api/mi-dispose-batches.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            confirmButton.textContent = originalText;
            confirmButton.disabled = false;

            if (data.success) {
                showSuccessModal(`Successfully disposed ${data.batches_disposed} batch(es)`, function () {
                    closeDisposeModal();

                    // If batch modal is open, reload its data
                    if (currentItemId) {
                        loadBatches(currentItemId);
                        loadItemDetails(currentItemId);
                        itemWasModified = true;
                    }

                    // Reload the page to update cards
                    window.location.reload();
                });
            } else {
                showErrorModal('Error disposing batches: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error disposing batches:', error);
            showErrorModal('Failed to dispose batches. Please try again.');
            confirmButton.textContent = originalText;
            confirmButton.disabled = false;
        });
}