<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission - only admins can manage roles
if (!isset($_SESSION['user_id']) || !canPerformPettyCashAction($_SESSION['user_id'], 'manage_roles')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Role Management - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8f9fc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #111827, #374151); color: white; padding: 30px 20px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 20px; font-weight: 600; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; margin: 2px; }
        .badge-admin { background: #fef3c7; color: #92400e; }
        .badge-approver { background: #d1fae5; color: #065f46; }
        .badge-cashier { background: #dbeafe; color: #1e40af; }
        .badge-viewer { background: #e0e7ff; color: #4338ca; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; max-width: 600px; width: 90%; padding: 30px; border-radius: 12px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 20px; font-weight: 600; }
        .close { cursor: pointer; font-size: 24px; color: #6b7280; }
        .close:hover { color: #374151; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        .permission-matrix { margin-top: 20px; }
        .permission-matrix table { font-size: 14px; }
        .permission-matrix th { background: #f3f4f6; }
        .permission-matrix td { text-align: center; }
        .check-icon { color: var(--success); font-weight: bold; }
        .cross-icon { color: #9ca3af; }
        .info-box { background: #eff6ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .role-card { background: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary); }
        .role-card h3 { font-size: 16px; margin-bottom: 8px; }
        .role-card p { font-size: 14px; color: #6b7280; margin-bottom: 8px; }
        .role-card ul { margin-left: 20px; font-size: 14px; color: #4b5563; }
        .empty-state { text-align: center; padding: 40px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>👥 Petty Cash Role Management</h1>
            <p>Manage user roles and permissions for petty cash system</p>
        </div>
    </div>

    <div class="container">
        <!-- Role Descriptions -->
        <div class="card">
            <div class="card-title" style="margin-bottom: 15px;">Available Roles</div>
            
            <div class="role-card" style="border-left-color: #f59e0b;">
                <h3>🔑 Admin</h3>
                <p>Full system access with all permissions</p>
                <ul>
                    <li>Manage system settings and float configuration</li>
                    <li>Assign and remove user roles</li>
                    <li>Manage categories and spending rules</li>
                    <li>Full access to all features</li>
                </ul>
            </div>

            <div class="role-card" style="border-left-color: #10b981;">
                <h3>✅ Approver</h3>
                <p>Can review and approve transactions</p>
                <ul>
                    <li>Approve or reject petty cash requests</li>
                    <li>Perform reconciliations</li>
                    <li>Create and approve replenishment requests</li>
                    <li>View all transactions and reports</li>
                </ul>
            </div>

            <div class="role-card" style="border-left-color: #3b82f6;">
                <h3>💼 Cashier</h3>
                <p>Can create and manage transactions</p>
                <ul>
                    <li>Create new petty cash transactions</li>
                    <li>Edit own transactions</li>
                    <li>Upload receipts</li>
                    <li>Request replenishments</li>
                </ul>
            </div>

            <div class="role-card" style="border-left-color: #8b5cf6;">
                <h3>👁️ Viewer</h3>
                <p>Read-only access to view data</p>
                <ul>
                    <li>View transactions and reports</li>
                    <li>Access analytics dashboard</li>
                    <li>Export data for review</li>
                </ul>
            </div>
        </div>

        <!-- User Roles Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">User Roles</div>
                <button class="btn btn-primary" onclick="openAssignModal()">+ Assign Role</button>
            </div>

            <table id="usersTable">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Roles</th>
                        <th>Assigned Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersList">
                    <tr><td colspan="5" style="text-align: center; padding: 40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Permission Matrix -->
        <div class="card">
            <div class="card-title" style="margin-bottom: 20px;">Permission Matrix</div>
            <div class="permission-matrix">
                <table>
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Viewer</th>
                            <th>Cashier</th>
                            <th>Approver</th>
                            <th>Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>View Transactions</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Create Transactions</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Edit Transactions</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Delete Transactions</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Approve Transactions</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Reconciliation</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Request Replenishment</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Export Data</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Manage Categories</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Manage Settings</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                        <tr>
                            <td>Manage Roles</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="cross-icon">✗</td>
                            <td class="check-icon">✓</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Assign Role</div>
                <span class="close" onclick="closeAssignModal()">&times;</span>
            </div>
            <form id="assignForm" onsubmit="assignRole(event)">
                <div class="info-box">
                    <strong>Note:</strong> Users can have multiple roles. Each role grants cumulative permissions.
                </div>

                <div class="form-group">
                    <label>User ID *</label>
                    <input type="number" id="userId" required placeholder="Enter user ID" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select id="roleSelect" required>
                        <option value="">Select a role</option>
                        <option value="viewer">👁️ Viewer - Read-only access</option>
                        <option value="cashier">💼 Cashier - Create transactions</option>
                        <option value="approver">✅ Approver - Approve & reconcile</option>
                        <option value="admin">🔑 Admin - Full access</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeAssignModal()" style="background: #e5e7eb; color: #374151;">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let users = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });

        async function loadUsers() {
            try {
                const response = await fetch('api_petty_cash_roles.php');
                const result = await response.json();

                if (result.success) {
                    users = result.data;
                    renderUsers();
                }
            } catch (error) {
                console.error('Error loading users:', error);
                showError('Failed to load users');
            }
        }

        function renderUsers() {
            const tbody = document.getElementById('usersList');

            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No users with petty cash roles</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => {
                const roleBadges = user.roles.map(role => {
                    const badgeClass = `badge-${role}`;
                    const icons = { admin: '🔑', approver: '✅', cashier: '💼', viewer: '👁️' };
                    return `<span class="badge ${badgeClass}">${icons[role] || ''} ${role}</span>`;
                }).join('');

                return `
                    <tr>
                        <td>${user.user_id}</td>
                        <td>${user.username || 'User ' + user.user_id}</td>
                        <td>${roleBadges || '-'}</td>
                        <td>${formatDate(user.assigned_date)}</td>
                        <td>
                            ${user.roles.map(role => `
                                <button class="btn btn-danger btn-sm" onclick="removeRole(${user.user_id}, '${role}')">
                                    Remove ${role}
                                </button>
                            `).join(' ')}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openAssignModal() {
            document.getElementById('assignModal').classList.add('show');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('show');
            document.getElementById('assignForm').reset();
        }

        async function assignRole(event) {
            event.preventDefault();

            const data = {
                action: 'assign',
                user_id: parseInt(document.getElementById('userId').value),
                role: document.getElementById('roleSelect').value
            };

            try {
                const response = await fetch('api_petty_cash_roles.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Role assigned successfully');
                    closeAssignModal();
                    loadUsers();
                } else {
                    showError(result.error || 'Failed to assign role');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred');
            }
        }

        async function removeRole(userId, role) {
            if (!confirm(`Are you sure you want to remove the ${role} role from user ${userId}?`)) {
                return;
            }

            try {
                const response = await fetch('api_petty_cash_roles.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'remove',
                        user_id: userId,
                        role: role
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Role removed successfully');
                    loadUsers();
                } else {
                    showError(result.error || 'Failed to remove role');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred');
            }
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function showSuccess(message) {
            alert('✓ ' + message);
        }

        function showError(message) {
            alert('✗ ' + message);
        }
    </script>
</body>
</html>
