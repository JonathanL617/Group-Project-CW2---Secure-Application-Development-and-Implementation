
 // assets/js/manageAccount.js

// Create Admin Account
function createAdminAccount() {
    const form = document.getElementById('createAdminForm');

    if (!form) return;

    // Generate a new Admin ID
    fetch('../../app/controller/SuperAdminController.php?action=generateAdminId')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to generate Admin ID');
            }
            return response.json();
        })
        .then(idData => {
            if (!idData.id) {
                throw new Error('Invalid Admin ID');
            }

            const formData = new FormData(form);
            formData.append('admin_id', idData.id);

            return fetch(
                '../../app/controller/SuperAdminController.php?action=create',
                {
                    method: 'POST',
                    body: formData
                }
            );
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to create Admin account');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                loadAdminAccounts();
                updateCounters();

                const modalElement =
                    document.getElementById('createAdminModal');

                const modal =
                    bootstrap.Modal.getInstance(modalElement);

                if (modal) {
                    modal.hide();
                }

                form.reset();

                alert('Admin account created successfully!');
            } else {
                alert(result.message || 'Failed to create admin account');
            }
        })
        .catch(error => {
            console.error('Create Admin error:', error);
            alert('An error occurred while creating the admin account');
        });
}


// XSS Prevention - Display Admin data as plain text
function createAdminCell(row, value) {
    const cell = document.createElement('td');

    // Prevent HTML and JavaScript execution
    cell.textContent = value ?? '';

    row.appendChild(cell);
    return cell;
}


// Safely display table messages
function showAdminTableMessage(tableBody, message, isError = false) {
    if (!tableBody) return;

    const row = document.createElement('tr');
    const cell = document.createElement('td');

    cell.colSpan = 8;

    cell.className = isError
        ? 'text-center text-danger'
        : 'text-center text-muted';

    cell.textContent = message;

    row.appendChild(cell);
    tableBody.replaceChildren(row);
}


// Load Admin Accounts - XSS Prevention
function loadAdminAccounts() {
    fetch('../../app/controller/SuperAdminController.php?action=getAdmin')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch Admin accounts');
            }

            return response.json();
        })
        .then(data => {
            const tableBody =
                document.getElementById('adminAccountsTable');

            if (!tableBody) return;

            // Clear table safely instead of innerHTML
            tableBody.replaceChildren();

            if (!Array.isArray(data)) {
                throw new Error('Invalid Admin account data');
            }

            if (data.length === 0) {
                showAdminTableMessage(
                    tableBody,
                    'No admin accounts found.'
                );
                return;
            }

            data.forEach(admin => {
                const row = document.createElement('tr');

                // Checkbox column
                const checkboxCell = document.createElement('td');
                const checkbox = document.createElement('input');

                checkbox.type = 'checkbox';

                checkboxCell.appendChild(checkbox);
                row.appendChild(checkboxCell);

                // XSS Prevention - Display information safely
                createAdminCell(row, admin.id);
                createAdminCell(row, admin.username);
                createAdminCell(row, admin.email);
                createAdminCell(row, admin.dob);
                createAdminCell(row, admin.registered_by);

                // Status column
                const statusCell = document.createElement('td');
                const statusBadge = document.createElement('span');

                statusBadge.className =
                    admin.status === 'active'
                        ? 'badge bg-success'
                        : 'badge bg-danger';

                // Display status as plain text
                statusBadge.textContent = admin.status ?? '';

                statusCell.appendChild(statusBadge);
                row.appendChild(statusCell);

                // Action buttons
                const actionCell = document.createElement('td');

                // Activate / Deactivate button
                const toggleButton = document.createElement('button');

                toggleButton.type = 'button';

                toggleButton.className =
                    admin.status === 'active'
                        ? 'btn btn-sm btn-warning me-1'
                        : 'btn btn-sm btn-success me-1';

                toggleButton.textContent =
                    admin.status === 'active'
                        ? 'Deactivate'
                        : 'Activate';

                // Use addEventListener instead of inline onclick
                toggleButton.addEventListener('click', () => {
                    toggleAdminStatus(admin.id, admin.status);
                });

                // Delete button
                const deleteButton = document.createElement('button');

                deleteButton.type = 'button';
                deleteButton.className = 'btn btn-sm btn-danger';
                deleteButton.textContent = 'Delete';

                // Safe event handling
                deleteButton.addEventListener('click', () => {
                    if (typeof deleteAdmin === 'function') {
                        deleteAdmin(admin.id);
                    } else {
                        console.error('deleteAdmin function is not defined');
                        alert('Delete function is unavailable.');
                    }
                });

                actionCell.appendChild(toggleButton);
                actionCell.appendChild(deleteButton);

                row.appendChild(actionCell);
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Load Admin accounts error:', error);

            const tableBody =
                document.getElementById('adminAccountsTable');

            showAdminTableMessage(
                tableBody,
                'Error loading admin accounts.',
                true
            );
        });
}


// Toggle Admin Status
function toggleAdminStatus(adminId, currentStatus) {
    fetch('../../app/controller/SuperAdminController.php?action=toggleStatus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            adminId: adminId,
            status: currentStatus === 'active'
                ? 'inactive'
                : 'active'
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to update Admin status');
            }

            return response.json();
        })
        .then(data => {
            if (data.success) {
                loadAdminAccounts();
                updateCounters();
            } else {
                alert('Failed to update Admin status.');
            }
        })
        .catch(error => {
            console.error('Toggle Admin status error:', error);
            alert('An error occurred while updating Admin status.');
        });
}


// Update Dashboard Counters
function updateCounters() {
    fetch('../../app/controller/SuperAdminController.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load counters');
            }

            return response.json();
        })
        .then(data => {
            document.getElementById('totalAdmins').textContent =
                data.total ?? 0;

            document.getElementById('activeAdmins').textContent =
                data.active ?? 0;

            document.getElementById('inActiveAdmins').textContent =
                data.inactive ?? 0;

            document.getElementById('registeredByCurrent').textContent =
                data.registered_by_current ?? 0;
        })
        .catch(error => {
            console.error('Update counters error:', error);
        });
}


// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadAdminAccounts();
    updateCounters();

    // Refresh every 30 seconds
    setInterval(() => {
        loadAdminAccounts();
        updateCounters();
    }, 30000);
});
