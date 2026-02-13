<?php
/**
 * Header Component
 * Top navigation bar with menu toggle, fullscreen, and user menu
 * 
 * Required variables:
 * - $current_username (from auth-check.php)
 */

if (!isset($current_username)) {
    $current_username = 'User';
}
?>

<header class="bg-white border-b border-gray-200 h-16 sticky top-0 right-0 z-20 transition-all duration-300" id="header">
    <div class="h-full flex items-center justify-between px-6">
        <!-- Left side - Hamburger menu -->
        <button
            onclick="toggleSidebar()"
            class="p-2 rounded-md hover:bg-gray-100 transition-colors"
            aria-label="Toggle sidebar"
        >
            <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>

        <!-- Right side - Fullscreen & Username -->
        <div class="flex items-center gap-4">
            <!-- Fullscreen toggle -->
            <button
                onclick="toggleFullscreen()"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors"
                aria-label="Toggle fullscreen"
                id="fullscreen-btn"
            >
                <!-- Enter fullscreen icon (default) -->
                <svg
                    id="fullscreen-enter-icon"
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                </svg>
                <!-- Exit fullscreen icon (hidden) -->
                <svg
                    id="fullscreen-exit-icon"
                    class="hidden"
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" />
                </svg>
            </button>

            <!-- Username dropdown -->
            <div class="relative">
                <button 
                    onclick="toggleUserMenu()"
                    class="flex items-center gap-2 px-3 py-2 rounded-md hover:bg-gray-100 transition-colors text-product"
                    id="user-menu-btn"
                >
                    <span class="text-sm"><?php echo htmlspecialchars($current_username); ?></span>
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div 
                    id="user-menu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg"
                >
                    <a
                        href="../auth/logout.php"
                        class="block px-4 py-2 text-sm text-regular hover:bg-gray-100 transition-colors"
                    >
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Toggle user menu dropdown
function toggleUserMenu() {
    const menu = document.getElementById('user-menu');
    menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('user-menu');
    const button = document.getElementById('user-menu-btn');
    
    if (!button.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

// Fullscreen toggle
function toggleFullscreen() {
    const enterIcon = document.getElementById('fullscreen-enter-icon');
    const exitIcon = document.getElementById('fullscreen-exit-icon');
    
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            enterIcon.classList.add('hidden');
            exitIcon.classList.remove('hidden');
        }).catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            enterIcon.classList.remove('hidden');
            exitIcon.classList.add('hidden');
        }).catch(err => {
            console.error('Error attempting to exit fullscreen:', err);
        });
    }
}

// Listen for fullscreen changes (e.g., pressing ESC)
document.addEventListener('fullscreenchange', function() {
    const enterIcon = document.getElementById('fullscreen-enter-icon');
    const exitIcon = document.getElementById('fullscreen-exit-icon');
    
    if (document.fullscreenElement) {
        enterIcon.classList.add('hidden');
        exitIcon.classList.remove('hidden');
    } else {
        enterIcon.classList.remove('hidden');
        exitIcon.classList.add('hidden');
    }
});
</script>
