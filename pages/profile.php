<?php
require_once __DIR__ . '/../config/db.php';

// Fetch current user's full details
$stmt = $conn->prepare("
    SELECT staff_id, user_name, contact, email, hire_date, status, is_super_admin, created_at
    FROM users
    WHERE staff_id = ?
");
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's permissions
$perm_stmt = $conn->prepare("
    SELECT p.code, p.description
    FROM user_permissions up
    JOIN permissions p ON up.permission_id = p.id
    WHERE up.staff_id = ?
    ORDER BY p.code
");
$perm_stmt->bind_param("s", $current_user_id);
$perm_stmt->execute();
$perm_result = $perm_stmt->get_result();
$permissions = [];
while ($row = $perm_result->fetch_assoc()) {
    $permissions[] = $row;
}
$perm_stmt->close();

// Module permission labels
$module_labels = [
    'page.create-sales' => 'Create Sales',
    'page.manage-products' => 'Manage Products',
    'page.product-categories' => 'Product Categories',
    'page.manage-inventory' => 'Manage Inventory',
    'page.reports' => 'Reports'
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl text-title mb-2">Profile</h1>
        <p class="text-regular text-gray-600">Your account information</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <!-- Avatar -->
                <div class="w-20 h-20 rounded-full bg-black text-white flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-semibold">
                        <?php echo strtoupper(substr($user['user_name'], 0, 2)); ?>
                    </span>
                </div>

                <h2 class="text-title text-xl text-gray-800 mb-1">
                    <?php echo htmlspecialchars($user['user_name']); ?>
                </h2>
                <p class="text-label text-gray-500 mb-3"><?php echo htmlspecialchars($user['staff_id']); ?></p>

                <!-- Status & Role Badges -->
                <div class="flex items-center justify-center gap-2">
                    <?php if ($user['status'] === 'Active'): ?>
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-success">Active</span>
                    <?php else: ?>
                        <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-danger">Deactivated</span>
                    <?php endif; ?>

                    <?php if ($user['is_super_admin']): ?>
                        <span class="text-xs px-2 py-1 rounded-full bg-black text-white">Super Admin</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Details Section -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Account Information -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-title text-lg text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Account Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Username -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Username</p>
                        <p class="text-regular text-gray-800"><?php echo htmlspecialchars($user['user_name']); ?></p>
                    </div>

                    <!-- Staff ID -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Staff ID</p>
                        <p class="text-regular text-gray-800"><?php echo htmlspecialchars($user['staff_id']); ?></p>
                    </div>

                    <!-- Email -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Email</p>
                        <p class="text-regular text-gray-800"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
                    </div>

                    <!-- Contact -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Contact</p>
                        <p class="text-regular text-gray-800"><?php echo htmlspecialchars($user['contact'] ?? '-'); ?></p>
                    </div>

                    <!-- Hire Date -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Hire Date</p>
                        <p class="text-regular text-gray-800">
                            <?php echo $user['hire_date'] ? date('F d, Y', strtotime($user['hire_date'])) : '-'; ?>
                        </p>
                    </div>

                    <!-- Account Created -->
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-label text-gray-500 mb-1">Account Created</p>
                        <p class="text-regular text-gray-800">
                            <?php echo $user['created_at'] ? date('F d, Y', strtotime($user['created_at'])) : '-'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Module Access -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-title text-lg text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Module Access
                </h3>

                <?php if ($user['is_super_admin']): ?>
                    <p class="text-regular text-gray-600 mb-3">As a Super Admin, you have full access to all modules.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach ($module_labels as $code => $label): ?>
                            <div class="flex items-center gap-2 p-2 bg-green-50 rounded-md">
                                <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-regular text-gray-700"><?php echo $label; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="flex items-center gap-2 p-2 bg-green-50 rounded-md">
                            <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-regular text-gray-700">User Access Control</span>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                    // Build a lookup of granted page permissions
                    $granted = [];
                    foreach ($permissions as $p) {
                        $granted[$p['code']] = true;
                    }
                    ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach ($module_labels as $code => $label): ?>
                            <?php $has = isset($granted[$code]); ?>
                            <div class="flex items-center gap-2 p-2 <?php echo $has ? 'bg-green-50' : 'bg-gray-50'; ?> rounded-md">
                                <?php if ($has): ?>
                                    <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                <?php endif; ?>
                                <span class="text-regular <?php echo $has ? 'text-gray-700' : 'text-gray-400'; ?>">
                                    <?php echo $label; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
