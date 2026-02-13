/**
 * UAC (User Access Control) JavaScript
 * Handles user management and permission assignment
 */

// ========== Modal Functions ==========

function openAddUserModal() {
    const modal = document.getElementById('uacUserModal');
    document.getElementById('uacFormMode').value = 'add';
    document.getElementById('uacStaffId').value = '';
    document.getElementById('uacModalTitle').textContent = 'Add New User';
    document.getElementById('uacModalSubtitle').textContent = 'Create a new user account and assign module access';
    document.getElementById('uacSaveButton').textContent = 'Create User';

    // Show/hide fields for add mode
    document.getElementById('uacStaffIdDisplay').classList.add('hidden');
    document.getElementById('uacPasswordRequired').textContent = '*';
    document.getElementById('uacPasswordHint').classList.add('hidden');
    document.getElementById('uacPassword').setAttribute('placeholder', 'Enter password');

    // Hide super admin notice
    document.getElementById('uacSuperAdminNotice').classList.add('hidden');

    // Enable all permission checkboxes
    const checkboxes = document.querySelectorAll('.uac-permission-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });

    // Reset form
    document.getElementById('uacUserForm').reset();
    document.getElementById('uacFormMode').value = 'add';

    // Show modal
    modal.classList.remove('hidden');
}

function openEditUserModal(staffId) {
    const modal = document.getElementById('uacUserModal');
    document.getElementById('uacFormMode').value = 'edit';
    document.getElementById('uacStaffId').value = staffId;
    document.getElementById('uacModalTitle').textContent = 'Edit User';
    document.getElementById('uacModalSubtitle').textContent = 'Update user details and module access';
    document.getElementById('uacSaveButton').textContent = 'Save Changes';

    // Show staff ID field
    document.getElementById('uacStaffIdDisplay').classList.remove('hidden');
    document.getElementById('uacStaffIdField').value = staffId;

    // Password is optional for edit
    document.getElementById('uacPasswordRequired').textContent = '';
    document.getElementById('uacPasswordHint').classList.remove('hidden');
    document.getElementById('uacPassword').setAttribute('placeholder', 'Leave blank to keep current');

    // Fetch user data
    fetch('../api/uac-get-user.php?staff_id=' + encodeURIComponent(staffId))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('uacUsername').value = user.user_name || '';
                document.getElementById('uacPassword').value = '';
                document.getElementById('uacContact').value = user.contact || '';
                document.getElementById('uacEmail').value = user.email || '';
                document.getElementById('uacHireDate').value = user.hire_date || '';

                // Handle super admin permissions display
                const isSuperAdmin = user.is_super_admin == 1;
                const notice = document.getElementById('uacSuperAdminNotice');
                const checkboxes = document.querySelectorAll('.uac-permission-checkbox');

                if (isSuperAdmin) {
                    notice.classList.remove('hidden');
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                        cb.disabled = true;
                    });
                } else {
                    notice.classList.add('hidden');
                    const userPermissions = data.permissions || [];
                    checkboxes.forEach(cb => {
                        cb.checked = userPermissions.includes(cb.value);
                        cb.disabled = false;
                    });
                }

                // Show modal
                modal.classList.remove('hidden');
            } else {
                showErrorModal(data.message || 'Failed to load user data');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showErrorModal('Failed to connect to server');
        });
}

function closeUserModal() {
    document.getElementById('uacUserModal').classList.add('hidden');
}

// ========== Save User ==========

function saveUser() {
    const mode = document.getElementById('uacFormMode').value;
    const username = document.getElementById('uacUsername').value.trim();
    const password = document.getElementById('uacPassword').value;
    const contact = document.getElementById('uacContact').value.trim();
    const email = document.getElementById('uacEmail').value.trim();
    const hireDate = document.getElementById('uacHireDate').value;

    // Validation
    if (!username) {
        showErrorModal('Please enter a username');
        return;
    }

    if (mode === 'add' && !password) {
        showErrorModal('Please enter a password');
        return;
    }

    if (password && password.length < 6) {
        showErrorModal('Password must be at least 6 characters');
        return;
    }

    // Gather selected permissions
    const checkboxes = document.querySelectorAll('.uac-permission-checkbox:not(:disabled)');
    const permissions = [];
    checkboxes.forEach(cb => {
        if (cb.checked) {
            permissions.push(cb.value);
        }
    });

    // Build form data
    const formData = new FormData();
    formData.append('username', username);
    formData.append('contact', contact);
    formData.append('email', email);
    formData.append('hire_date', hireDate);
    formData.append('permissions', JSON.stringify(permissions));

    if (password) {
        formData.append('password', password);
    }

    let url = '';
    if (mode === 'add') {
        url = '../api/uac-add-user.php';
    } else {
        url = '../api/uac-update-user.php';
        formData.append('staff_id', document.getElementById('uacStaffId').value);
    }

    // Disable save button
    const saveBtn = document.getElementById('uacSaveButton');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;

            if (data.success) {
                closeUserModal();
                showSuccessModal(data.message || 'User saved successfully', function () {
                    location.reload();
                }, true);
            } else {
                showErrorModal(data.message || 'Failed to save user');
            }
        })
        .catch(err => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            console.error('Error:', err);
            showErrorModal('Failed to connect to server');
        });
}

// ========== Toggle User Status ==========

function toggleUserStatus(staffId, currentStatus, username) {
    const newStatus = currentStatus === 'Active' ? 'Deactivated' : 'Active';
    const action = currentStatus === 'Active' ? 'deactivate' : 'activate';

    showWarningModal(
        `Are you sure you want to ${action} user "${username}"?`,
        function () {
            const formData = new FormData();
            formData.append('staff_id', staffId);

            fetch('../api/uac-toggle-status.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal(data.message || `User ${action}d successfully`, function () {
                            location.reload();
                        }, true);
                    } else {
                        showErrorModal(data.message || `Failed to ${action} user`);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showErrorModal('Failed to connect to server');
                });
        }
    );
}

// ========== Filter Users ==========

function filterUsers() {
    const searchTerm = document.getElementById('uacSearchInput').value.toLowerCase().trim();
    const statusFilter = document.getElementById('uacStatusFilter').value;
    const rows = document.querySelectorAll('.uac-user-row');

    rows.forEach(row => {
        const username = row.getAttribute('data-username') || '';
        const staffId = (row.getAttribute('data-staff-id') || '').toLowerCase();
        const status = row.getAttribute('data-status') || '';

        const matchesSearch = !searchTerm || username.includes(searchTerm) || staffId.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}
