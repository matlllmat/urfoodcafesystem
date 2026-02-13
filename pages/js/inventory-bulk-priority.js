/**
 * Default Priority Management
 * Handle setting default priority method for new inventory items
 */

const BULK_PRIORITY_METHOD_NAMES = {
    'fifo': 'FIFO - First In, First Out',
    'fefo': 'FEFO - First Expired, First Out',
    'manual': 'Manual - Custom Order'
};

/**
 * Open default priority modal
 */
function openDefaultPriorityModal() {
    const currentDefaultEl = document.getElementById('currentDefaultMethod');
    if (currentDefaultEl) {
        currentDefaultEl.textContent = 'Loading...';
    }

    // Show modal first so user sees it while we load
    document.getElementById('bulkPriorityModal').classList.remove('hidden');

    // Load current default setting
    fetch('../api/mi-get-default-priority.php')
        .then(response => response.json())
        .then(data => {
            const currentMethod = (data.success && data.default_method) ? data.default_method : 'fifo';
            if (currentDefaultEl) {
                currentDefaultEl.textContent = BULK_PRIORITY_METHOD_NAMES[currentMethod] || BULK_PRIORITY_METHOD_NAMES.fifo;
            }
            const radio = document.querySelector(`input[name="bulkPriorityMethod"][value="${currentMethod}"]`);
            if (radio) {
                radio.checked = true;
            } else {
                document.querySelector('input[name="bulkPriorityMethod"][value="fifo"]').checked = true;
            }
        })
        .catch(error => {
            console.error('Error loading default priority:', error);
            if (currentDefaultEl) {
                currentDefaultEl.textContent = BULK_PRIORITY_METHOD_NAMES.fifo;
            }
            document.querySelector('input[name="bulkPriorityMethod"][value="fifo"]').checked = true;
        });
}

/**
 * Close default priority modal
 */
function closeDefaultPriorityModal() {
    document.getElementById('bulkPriorityModal').classList.add('hidden');
}

/**
 * Save default priority - only affects new items
 */
function saveDefaultPriority() {
    const selectedMethod = document.querySelector('input[name="bulkPriorityMethod"]:checked').value;
    const currentMethod = document.getElementById('currentDefaultMethod').textContent.split(' - ')[0];

    // Method name mapping
    const methodNames = {
        'fifo': 'FIFO (First In, First Out)',
        'fefo': 'FEFO (First Expired, First Out)',
        'manual': 'Manual (Custom Order)'
    };

    // Show confirmation modal
    showConfirmationModal(
        'Change Default Priority?',
        `<div class="space-y-3">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <p class="text-sm text-gray-600 mb-1">Current Default:</p>
                <p class="text-base font-semibold text-gray-800">${currentMethod}</p>
            </div>
            <div class="flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-600 mb-1">New Default:</p>
                <p class="text-base font-semibold text-blue-800">${methodNames[selectedMethod]}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <p><strong>Note:</strong> This only affects new items you add in the future. Existing items remain unchanged.</p>
                </div>
            </div>
        </div>`,
        function() {
            // User confirmed - proceed with save
            confirmSaveDefaultPriority(selectedMethod);
        }
    );
}

/**
 * Actually save the default priority after confirmation
 */
function confirmSaveDefaultPriority(selectedMethod) {
    // Show loading state
    const saveButton = document.querySelector('#bulkPriorityModal button[onclick="saveDefaultPriority()"]');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    // Send request
    const formData = new FormData();
    formData.append('priority_method', selectedMethod);

    fetch('../api/mi-save-default-priority.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            saveButton.textContent = originalText;
            saveButton.disabled = false;

            if (data.success) {
                showSuccessModal('Default priority setting saved! New items will use ' + selectedMethod.toUpperCase() + ' by default.', function () {
                    closeDefaultPriorityModal();
                });
            } else {
                showErrorModal('Error saving default priority: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving default priority:', error);
            showErrorModal('Failed to save default priority. Please try again.');
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        });
}

/**
 * Apply selected priority method to ALL inventory items (including manual)
 */
function applyPriorityToAllItems() {
    const selectedMethod = document.querySelector('input[name="bulkPriorityMethod"]:checked');
    if (!selectedMethod) return;
    const method = selectedMethod.value;

    const methodNames = {
        'fifo': 'FIFO (First In, First Out)',
        'fefo': 'FEFO (First Expired, First Out)',
        'manual': 'Manual (Custom Order)'
    };

    showConfirmationModal(
        'Apply to All Items?',
        `<div class="space-y-3">
            <p class="text-sm text-gray-700">This will set the priority method to <strong>${methodNames[method]}</strong> for <strong>every inventory item</strong>, including those currently using manual order.</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                <p class="font-medium">Batch order will be recalculated</p>
                <p class="mt-1">For FIFO/FEFO, batch order is updated automatically. For Manual, existing order is kept.</p>
            </div>
            <p class="text-sm text-gray-600">Do you want to continue?</p>
        </div>`,
        function () {
            confirmApplyPriorityToAll(method);
        }
    );
}

/**
 * Call API to update all items' priority method
 */
function confirmApplyPriorityToAll(selectedMethod) {
    const btn = document.getElementById('applyToAllPriorityBtn');
    if (!btn) return;
    const originalText = btn.textContent;
    btn.textContent = 'Applying...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('priority_method', selectedMethod);

    fetch('../api/mi-update-all-priority-method.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.textContent = originalText;
            btn.disabled = false;
            if (data.success) {
                showSuccessModal(data.message || 'Priority applied to all items.', function () {
                    closeDefaultPriorityModal();
                    window.location.reload();
                });
            } else {
                showErrorModal(data.message || 'Failed to apply priority to all items.');
            }
        })
        .catch(error => {
            console.error('Error applying priority to all:', error);
            showErrorModal('Failed to apply priority to all items. Please try again.');
            btn.textContent = originalText;
            btn.disabled = false;
        });
}

/**
 * Show confirmation modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message (can include HTML)
 * @param {function} onConfirm - Callback when user confirms
 */
function showConfirmationModal(title, message, onConfirm) {
    // Check if modal already exists
    let modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.remove();
    }

    // Create modal HTML
    const modalHTML = `
        <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
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
                        onclick="closeConfirmationModal()"
                        class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button
                        type="button"
                        id="confirmButton"
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
    document.getElementById('confirmButton').addEventListener('click', function() {
        closeConfirmationModal();
        onConfirm();
    });

    // Add ESC key handler
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            closeConfirmationModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

/**
 * Close confirmation modal
 */
function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.remove();
    }
}

// Event delegation: Apply to All Items button (avoids "applyPriorityToAllItems is not defined" when script load order differs)
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-apply-priority-to-all]')) {
            applyPriorityToAllItems();
        }
    });
});

// Handle ESC key for bulk priority modal
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('bulkPriorityModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeDefaultPriorityModal();
        }
    }
});
