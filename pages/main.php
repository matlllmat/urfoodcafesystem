<?php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../config/db.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'create-sales';

$allowed_pages = [
    'create-sales',
    'sales-history',
    'manage-inventory',
    'inventory-trail',
    'manage-products',
    'product-categories',
    'profile',
    'reports',
    'UAC'
];

if (!in_array($page, $allowed_pages)) {
    $page = 'create-sales';
}

// Page permission mapping
$page_permission_map = [
    'create-sales' => 'page.create-sales',
    'sales-history' => 'page.sales-history',
    'manage-products' => 'page.manage-products',
    'product-categories' => 'page.product-categories',
    'manage-inventory' => 'page.manage-inventory',
    'inventory-trail' => 'page.inventory-trail',
    'reports' => 'page.reports',
    'UAC' => 'super_admin_only',
    'profile' => null // Always accessible
];

// Check page access permission
$has_access = false;
$required_permission = isset($page_permission_map[$page]) ? $page_permission_map[$page] : null;

if ($required_permission === null) {
    // Always accessible (profile)
    $has_access = true;
} elseif ($is_super_admin) {
    // Super admins have access to everything
    $has_access = true;
} elseif ($required_permission === 'super_admin_only') {
    // UAC is super admin only
    $has_access = false;
} else {
    // Check user's permissions
    $access_stmt = $conn->prepare("
        SELECT 1 FROM user_permissions up
        JOIN permissions p ON up.permission_id = p.id
        WHERE up.staff_id = ? AND p.code = ?
        LIMIT 1
    ");
    $access_stmt->bind_param("ss", $current_user_id, $required_permission);
    $access_stmt->execute();
    $has_access = $access_stmt->get_result()->num_rows > 0;
    $access_stmt->close();
}

$page_file = __DIR__ . '/' . $page . '.php';

$page_titles = [
    'create-sales' => 'Create Sales',
    'sales-history' => 'Sales History',
    'manage-inventory' => 'Manage Inventory',
    'inventory-trail' => 'Inventory Trail',
    'manage-products' => 'Manage Products',
    'product-categories' => 'Product Categories',
    'profile' => 'Profile',
    'reports' => 'Reports',
    'UAC' => 'User Access Control'
];

$page_title = isset($page_titles[$page]) ? $page_titles[$page] : 'Dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - UrFood Cafe System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="icon" type="image/png" href="../assets/images/darklogo.png">
</head>

<body class="bg-gray-50">
    <!-- Notification modal (early in DOM so all page scripts can use it) -->
    <?php include __DIR__ . '/../includes/notification-modal.php'; ?>

    <!-- Page Loading Overlay - Visible by default -->
    <div id="pageLoadingOverlay" class="page-loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Loading...</div>
    </div>

    <div class="flex h-screen overflow-hidden">

        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <!-- Page Contents shall appear here -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6" id="mainContent">
                <div class="max-w-7xl mx-auto">
                    <?php
                    if (!$has_access) {
                        // Access denied
                        echo '<div class="flex items-center justify-center h-64">';
                        echo '<div class="text-center">';
                        echo '<svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>';
                        echo '<h2 class="text-title text-xl text-gray-700 mb-2">Access Denied</h2>';
                        echo '<p class="text-regular text-gray-500">You do not have permission to access this page.</p>';
                        echo '<a href="main.php?page=profile" class="inline-block mt-4 text-regular text-blue-600 hover:underline">Go to Profile</a>';
                        echo '</div></div>';
                    } elseif (file_exists($page_file)) {
                        include $page_file;
                    } else {
                        // If the page not found
                        echo '<div class="bg-white rounded-lg shadow p-6">';
                        echo '<h1 class="text-title text-2xl mb-4">Page Not Found</h1>';
                        echo '<p class="text-regular">The requested page could not be found.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </main>

        </div>

    </div>

    <script>
        // Hide loading overlay and show content when page is ready
        window.addEventListener('load', function() {
            const overlay = document.getElementById('pageLoadingOverlay');
            const mainContent = document.getElementById('mainContent');

            if (overlay && mainContent) {
                // Small delay to ensure everything is rendered
                setTimeout(function() {
                    overlay.classList.add('hidden');
                    mainContent.classList.add('loaded');
                }, 100);
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebar) {
                sidebar.classList.toggle('-translate-x-full');
            }

            if (overlay) {
                overlay.classList.toggle('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo $page; ?>';
            const navLinks = document.querySelectorAll('[data-page]');

            navLinks.forEach(link => {
                if (link.dataset.page === currentPage) {
                    link.classList.add('bg-blue-50', 'text-blue-600', 'border-l-4', 'border-blue-600');
                    link.classList.remove('text-gray-700');
                }
            });
        });

        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profile-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            const profileButton = document.getElementById('profile-button');
            const dropdown = document.getElementById('profile-dropdown');

            if (dropdown && profileButton && !profileButton.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
    <script src="../includes/notification-modal.js"></script>
</body>
</html>