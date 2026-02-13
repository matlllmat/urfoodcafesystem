/**
 * Show notification modal
 * @param {Object} options - Configuration object
 * @param {string} options.type - Type of notification: 'success', 'error', 'warning', 'info'
 * @param {string} options.title - Modal title
 * @param {string} options.message - Modal message
 * @param {Function} options.onConfirm - Optional callback when OK is clicked
 * @param {boolean} options.autoClose - Auto close after delay (default: false)
 * @param {number} options.autoCloseDelay - Delay in ms before auto close (default: 2000)
 */
function showNotificationModal(options) {
    const {
        type = 'info',
        title = 'Notification',
        message = '',
        onConfirm = null,
        autoClose = false,
        autoCloseDelay = 2000
    } = options;

    const modal = document.getElementById('notificationModal');
    const iconContainer = document.getElementById('notificationIcon');
    const titleElement = document.getElementById('notificationTitle');
    const messageElement = document.getElementById('notificationMessage');
    const okButton = document.getElementById('notificationOkButton');

    // Use proper modal only; if not in DOM, exit quietly (modal is included at top of main.php)
    if (!modal || !iconContainer || !titleElement || !messageElement || !okButton) {
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
        return;
    }

    // Set title and message
    titleElement.textContent = title;
    messageElement.textContent = message;

    // Configure icon and colors based on type
    let iconHTML = '';
    let iconBgClass = '';
    let buttonClass = 'bg-black hover:bg-gray-800';

    switch (type) {
        case 'success':
            iconBgClass = 'bg-green-100';
            buttonClass = 'text-white';
            iconHTML = `
                <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            `;
            break;
        case 'error':
            iconBgClass = 'bg-red-100';
            buttonClass = 'text-white';
            iconHTML = `
                <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
            break;
        case 'warning':
            iconBgClass = 'bg-yellow-100';
            buttonClass = 'text-white';
            iconHTML = `
                <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            `;
            break;
        case 'info':
        default:
            iconBgClass = 'bg-blue-100';
            buttonClass = 'bg-black text-white hover:bg-gray-800';
            iconHTML = `
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            `;
            break;
    }

    // Set icon and styling
    iconContainer.className = `w-16 h-16 rounded-full flex items-center justify-center ${iconBgClass}`;
    iconContainer.innerHTML = iconHTML;

    // Update button styling with inline styles to ensure visibility
    okButton.className = `px-6 py-2 rounded-md font-medium transition-colors cursor-pointer`;

    // Set base colors
    let baseColor, hoverColor;
    switch (type) {
        case 'success':
            baseColor = '#2e7d32'; // var(--color-success)
            hoverColor = '#1b5e20';
            break;
        case 'error':
            baseColor = '#B71C1C'; // var(--color-danger)
            hoverColor = '#8b0000';
            break;
        case 'warning':
            baseColor = '#EDAE49'; // var(--color-warning)
            hoverColor = '#d89b2f';
            break;
        default: // info
            baseColor = '#000000';
            hoverColor = '#374151';
    }

    okButton.style.backgroundColor = baseColor;
    okButton.style.color = '#ffffff';

    // Remove any existing event listeners by cloning and replacing
    const newOkButton = okButton.cloneNode(true);
    okButton.parentNode.replaceChild(newOkButton, okButton);

    // Add hover effect
    newOkButton.addEventListener('mouseenter', () => {
        newOkButton.style.backgroundColor = hoverColor;
    });
    newOkButton.addEventListener('mouseleave', () => {
        newOkButton.style.backgroundColor = baseColor;
    });

    // Set up confirm callback
    newOkButton.addEventListener('click', () => {
        closeNotificationModal();
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
    });

    // Show modal
    modal.classList.remove('hidden');

    // Auto close if enabled
    if (autoClose) {
        setTimeout(() => {
            closeNotificationModal();
            if (onConfirm && typeof onConfirm === 'function') {
                onConfirm();
            }
        }, autoCloseDelay);
    }
}

/**
 * Close notification modal
 */
function closeNotificationModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Shorthand functions for common notification types
 */
function showSuccessModal(message, onConfirm = null, autoClose = false) {
    showNotificationModal({
        type: 'success',
        title: 'Success',
        message: message,
        onConfirm: onConfirm,
        autoClose: autoClose
    });
}

function showErrorModal(message, onConfirm = null) {
    showNotificationModal({
        type: 'error',
        title: 'Error',
        message: message,
        onConfirm: onConfirm
    });
}

function showWarningModal(message, onConfirm = null) {
    showNotificationModal({
        type: 'warning',
        title: 'Warning',
        message: message,
        onConfirm: onConfirm
    });
}

function showInfoModal(message, onConfirm = null) {
    showNotificationModal({
        type: 'info',
        title: 'Information',
        message: message,
        onConfirm: onConfirm
    });
}

// Close on ESC key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('notificationModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeNotificationModal();
        }
    }
});