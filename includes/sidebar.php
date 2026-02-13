<?php
/**
 * Sidebar Component
 * Side navigation menu with logo and main navigation links
 *
 * Required variables:
 * - $page (from main.php) - current page identifier
 * - $is_super_admin (from auth-check.php)
 * - $current_user_id (from auth-check.php)
 */

// Menu items configuration with permission mapping
$menu_items = [
    [
        'label' => 'Create Sales',
        'page' => 'create-sales',
        'icon' => 'CS',
        'permission' => 'page.create-sales'
    ],
    [
        'label' => 'Sales History',
        'page' => 'sales-history',
        'icon' => 'SH',
        'permission' => 'page.sales-history'
    ],
    [
        'label' => 'Manage Products',
        'page' => 'manage-products',
        'icon' => 'MP',
        'permission' => 'page.manage-products'
    ],
    [
        'label' => 'Product Categories',
        'page' => 'product-categories',
        'icon' => 'PC',
        'permission' => 'page.product-categories'
    ],
    [
        'label' => 'Manage Inventory',
        'page' => 'manage-inventory',
        'icon' => 'MI',
        'permission' => 'page.manage-inventory'
    ],
    [
        'label' => 'Inventory Trail',
        'page' => 'inventory-trail',
        'icon' => 'IT',
        'permission' => 'page.manage-inventory'
    ],
    [
        'label' => 'Reports',
        'page' => 'reports',
        'icon' => 'R',
        'permission' => 'page.reports'
    ],
    [
        'label' => 'UAC',
        'page' => 'UAC',
        'icon' => 'U',
        'permission' => 'super_admin_only'
    ],
    [
        'label' => 'Profile',
        'page' => 'profile',
        'icon' => 'P',
        'permission' => null // Always accessible
    ]
];

// Fetch current user's page permissions (unless super admin)
$user_page_permissions = [];
if (!$is_super_admin && isset($current_user_id)) {
    $perm_query = $conn->prepare("
        SELECT p.code
        FROM user_permissions up
        JOIN permissions p ON up.permission_id = p.id
        WHERE up.staff_id = ? AND p.code LIKE 'page.%'
    ");
    $perm_query->bind_param("s", $current_user_id);
    $perm_query->execute();
    $perm_result = $perm_query->get_result();
    while ($perm_row = $perm_result->fetch_assoc()) {
        $user_page_permissions[] = $perm_row['code'];
    }
    $perm_query->close();
}

// Filter menu items based on permissions
$visible_menu_items = [];
foreach ($menu_items as $item) {
    if ($item['permission'] === null) {
        // Always visible (Profile)
        $visible_menu_items[] = $item;
    } elseif ($is_super_admin) {
        // Super admins see everything
        $visible_menu_items[] = $item;
    } elseif ($item['permission'] === 'super_admin_only') {
        // Skip UAC for non-super-admins
        continue;
    } elseif (in_array($item['permission'], $user_page_permissions)) {
        // User has this page permission
        $visible_menu_items[] = $item;
    }
}

// Get current page from main.php
$current_page = isset($page) ? $page : '';
?>

<!-- Sidebar -->
<aside
    id="sidebar"
    class="bg-black text-white h-screen w-64 flex-shrink-0 transition-transform duration-300 lg:translate-x-0 -translate-x-full fixed lg:relative z-30"
>
    <!-- Logo Section -->
    <div class="flex items-center justify-center p-4 border-b border-gray-700">
        <img
            src="../assets/images/lightlogo.png"
            alt="UR Foodhub + Café Logo"
            class="h-12 w-auto object-contain"
        />
    </div>

    <!-- Menu Items -->
    <nav class="mt-6">
        <ul class="space-y-2 px-3">
            <?php foreach ($visible_menu_items as $item): ?>
                <?php 
                $is_active = ($current_page === $item['page']);
                $link_class = $is_active 
                    ? 'bg-white text-black' 
                    : 'text-white hover:bg-gray-800';
                ?>
                <li>
                    <a
                        href="main.php?page=<?php echo $item['page']; ?>"
                        data-page="<?php echo $item['page']; ?>"
                        class="flex items-center px-4 py-3 rounded-md transition-colors duration-200 text-product <?php echo $link_class; ?>"
                    >
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

<!-- Overlay for mobile (when sidebar is open) -->
<div
    id="sidebar-overlay"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
    onclick="toggleSidebar()"
></div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    // Toggle sidebar visibility
    sidebar.classList.toggle('-translate-x-full');
    
    // Toggle overlay
    overlay.classList.toggle('hidden');
}

// Close sidebar when clicking on a link (mobile only)
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('#sidebar nav a');
    const isMobile = window.innerWidth < 1024;
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        });
    });
});
</script>