<?php
require_once __DIR__ . '/../config/db.php';

// Super admin gate check
if (!$is_super_admin) {
    echo '<div class="flex items-center justify-center h-64">';
    echo '<div class="text-center">';
    echo '<svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>';
    echo '<h2 class="text-title text-xl text-gray-700 mb-2">Access Denied</h2>';
    echo '<p class="text-regular text-gray-500">Only super administrators can access this page.</p>';
    echo '</div></div>';
    return;
}

// Fetch all users
$users_query = "
    SELECT
        u.staff_id,
        u.user_name,
        u.contact,
        u.email,
        u.hire_date,
        u.status,
        u.is_super_admin,
        u.created_at,
        GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') as permission_codes
    FROM users u
    LEFT JOIN user_permissions up ON u.staff_id = up.staff_id
    LEFT JOIN permissions p ON up.permission_id = p.id AND p.code LIKE 'page.%'
    GROUP BY u.staff_id
    ORDER BY u.is_super_admin DESC, u.created_at ASC
";
$users_result = $conn->query($users_query);
$users = [];
if ($users_result) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Stats
$total_users = count($users);
$active_count = 0;
$deactivated_count = 0;
foreach ($users as $u) {
    if ($u['status'] === 'Active') $active_count++;
    else $deactivated_count++;
}

// Module permission mapping for display
$module_labels = [
    'page.create-sales' => 'Create Sales',
    'page.sales-history' => 'Sales History',
    'page.manage-products' => 'Manage Products',
    'page.product-categories' => 'Product Categories',
    'page.manage-inventory' => 'Manage Inventory',
    'page.inventory-trail' => 'Inventory Trail',
    'page.reports' => 'Reports'
];
?>

<!-- UAC Page -->
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">User Access Control</h1>
        <p class="text-regular text-gray-600">Manage user accounts and assign module access</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Total Users -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Total Users</p>
                    <h3 class="text-title text-2xl text-gray-800"><?php echo $total_users; ?></h3>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Active Users</p>
                    <h3 class="text-title text-2xl text-success"><?php echo $active_count; ?></h3>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Deactivated Users -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Deactivated Users</p>
                    <h3 class="text-title text-2xl text-danger"><?php echo $deactivated_count; ?></h3>
                </div>
                <div class="bg-red-100 p-2 rounded">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Search and Filters -->
            <div class="flex flex-col sm:flex-row gap-3 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <input
                        type="text"
                        id="uacSearchInput"
                        placeholder="Search users..."
                        oninput="filterUsers()"
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Status Filter -->
                <select
                    id="uacStatusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black"
                    onchange="filterUsers()">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Deactivated">Deactivated</option>
                </select>
            </div>

            <!-- Action Button -->
            <div class="flex gap-2">
                <button
                    onclick="openAddUserModal()"
                    class="bg-black text-white px-4 py-2 rounded-md text-product hover:bg-gray-800 transition-colors whitespace-nowrap">
                    + Add User
                </button>
            </div>
        </div>
    </div>

    <!-- User Table -->
    <?php if (empty($users)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-regular text-gray-500">No users found</p>
            <p class="text-label text-gray-400 mt-1">Add a user to get started</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="uacUserTable">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Staff ID</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Hire Date</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Module Access</th>
                            <th class="text-left px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-right px-6 py-3 text-label text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors uac-user-row"
                                data-staff-id="<?php echo htmlspecialchars($user['staff_id']); ?>"
                                data-username="<?php echo htmlspecialchars(strtolower($user['user_name'])); ?>"
                                data-status="<?php echo htmlspecialchars($user['status']); ?>">
                                <td class="px-6 py-4 text-regular text-gray-800 whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['staff_id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-regular text-gray-800"><?php echo htmlspecialchars($user['user_name']); ?></span>
                                        <?php if ($user['is_super_admin']): ?>
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-black text-white">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-regular text-gray-600 whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['contact'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-regular text-gray-600 whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['email'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-regular text-gray-600 whitespace-nowrap">
                                    <?php echo $user['hire_date'] ? date('M d, Y', strtotime($user['hire_date'])) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-regular text-gray-600 whitespace-nowrap">
                                    <?php if ($user['is_super_admin']): ?>
                                        <span class="text-label text-gray-500 italic">Full Access</span>
                                    <?php elseif (!empty($user['permission_codes'])): ?>
                                        <?php
                                        $codes = array_filter(array_map('trim', explode(', ', $user['permission_codes'])), function($c) use ($module_labels) {
                                            return isset($module_labels[$c]);
                                        });
                                        $count = count($codes);
                                        $total = count($module_labels);
                                        ?>
                                        <span class="text-regular text-gray-700"><?php echo $count; ?> of <?php echo $total; ?> modules</span>
                                    <?php else: ?>
                                        <span class="text-label text-gray-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-success">Active</span>
                                    <?php else: ?>
                                        <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-danger">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            onclick="openEditUserModal('<?php echo htmlspecialchars($user['staff_id']); ?>')"
                                            class="p-2 rounded-md hover:bg-gray-100 transition-colors text-gray-600 hover:text-gray-800"
                                            title="Edit user">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <?php if (!$user['is_super_admin'] && $user['staff_id'] !== $current_user_id): ?>
                                            <button
                                                onclick="toggleUserStatus('<?php echo htmlspecialchars($user['staff_id']); ?>', '<?php echo htmlspecialchars($user['status']); ?>', '<?php echo htmlspecialchars($user['user_name']); ?>')"
                                                class="p-2 rounded-md hover:bg-gray-100 transition-colors <?php echo $user['status'] === 'Active' ? 'text-danger hover:text-red-800' : 'text-success hover:text-green-800'; ?>"
                                                title="<?php echo $user['status'] === 'Active' ? 'Deactivate user' : 'Activate user'; ?>">
                                                <?php if ($user['status'] === 'Active'): ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Include Modal -->
<?php require_once 'modals/uac-user-modal.php'; ?>

<!-- ESC Key Handler -->
<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('uacUserModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeUserModal();
            }
        }
    });
</script>

<!-- Load JS -->
<script src="js/uac.js"></script>
