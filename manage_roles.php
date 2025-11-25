<?php
session_start();
require_once 'db.php';
require_once 'rbac.php';
require_once 'activity_logger.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if user has permission to manage roles
if (!userHasPermission($_SESSION['user_id'], 'manage-roles')) {
    $_SESSION['error_message'] = "Access denied. You don't have permission to manage roles.";
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_role') {
        $name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['role_description'] ?? '');
        
        if ($name) {
            $roleId = createRole($name, $description);
            if ($roleId) {
                $message = "Role created successfully!";
                logActivity($_SESSION['user_id'], 'create-role', 'roles', $roleId, ['name' => $name]);
            } else {
                $error = "Failed to create role. Role name may already exist.";
            }
        } else {
            $error = "Role name is required.";
        }
    } elseif ($action === 'update_role') {
        $roleId = $_POST['role_id'] ?? 0;
        $name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['role_description'] ?? '');
        
        if ($roleId && $name) {
            if (updateRole($roleId, $name, $description)) {
                $message = "Role updated successfully!";
                logActivity($_SESSION['user_id'], 'update-role', 'roles', $roleId, ['name' => $name]);
            } else {
                $error = "Failed to update role.";
            }
        } else {
            $error = "Invalid role data.";
        }
    } elseif ($action === 'delete_role') {
        $roleId = $_POST['role_id'] ?? 0;
        
        if ($roleId) {
            if (deleteRole($roleId)) {
                $message = "Role deleted successfully!";
                logActivity($_SESSION['user_id'], 'delete-role', 'roles', $roleId);
            } else {
                $error = "Failed to delete role. Role may be assigned to users.";
            }
        }
    } elseif ($action === 'update_permissions') {
        $roleId = $_POST['role_id'] ?? 0;
        $permissionIds = $_POST['permissions'] ?? [];
        
        if ($roleId) {
            if (updateRolePermissions($roleId, $permissionIds)) {
                $message = "Permissions updated successfully!";
                logActivity($_SESSION['user_id'], 'update-permissions', 'roles', $roleId, ['permission_count' => count($permissionIds)]);
            } else {
                $error = "Failed to update permissions.";
            }
        }
    } elseif ($action === 'assign_role') {
        $userId = $_POST['user_id'] ?? 0;
        $roleId = $_POST['role_id'] ?? 0;
        
        if ($userId && $roleId) {
            if (assignRoleToUser($userId, $roleId)) {
                $message = "Role assigned successfully!";
                logActivity($_SESSION['user_id'], 'assign-role', 'users', $userId, ['role_id' => $roleId]);
            } else {
                $error = "Failed to assign role.";
            }
        }
    } elseif ($action === 'remove_role') {
        $userId = $_POST['user_id'] ?? 0;
        $roleId = $_POST['role_id'] ?? 0;
        
        if ($userId && $roleId) {
            if (removeRoleFromUser($userId, $roleId)) {
                $message = "Role removed successfully!";
                logActivity($_SESSION['user_id'], 'remove-role', 'users', $userId, ['role_id' => $roleId]);
            } else {
                $error = "Failed to remove role.";
            }
        }
    }
}

// Get all users with their roles
$usersStmt = $pdo->query("
    SELECT u.id, u.username, u.email, u.first_name, u.last_name
    FROM users u
    ORDER BY u.username
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all roles
$roles = getAllRoles();

// Get all permissions
$allPermissions = getAllPermissions();

// Build user roles map
$userRoles = [];
foreach ($users as $user) {
    $userRoles[$user['id']] = getUserRoles($user['id']);
}

// Build role permissions map
$rolePermissions = [];
foreach ($roles as $role) {
    $rolePermissions[$role['id']] = getRolePermissions($role['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0052cc;
            --primary-hover: #0041a3;
            --secondary-color: #f4f7f6;
            --text-color: #333;
            --border-color: #dee2e6;
            --white-color: #fff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            padding: 20px;
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white-color);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--secondary-color);
            font-weight: 600;
            color: var(--text-color);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-right: 5px;
            margin-bottom: 5px;
            background-color: var(--primary-color);
            color: white;
        }

        .role-badge.super-admin {
            background-color: #dc3545;
        }

        .role-badge.admin {
            background-color: #0052cc;
        }

        .role-badge.accountant {
            background-color: #28a745;
        }

        .role-badge.manager {
            background-color: #ffc107;
            color: #333;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-small {
            padding: 4px 10px;
            font-size: 12px;
        }

        select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            margin-right: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.3em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Role Management</h1>
        <p style="color: #666; margin-bottom: 30px;">Manage user roles and permissions</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Create New Role Section -->
        <div class="section">
            <h2>Create New Role</h2>
            <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 6px;">
                <input type="hidden" name="action" value="create_role">
                <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Role Name *</label>
                        <input type="text" name="role_name" required style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                        <input type="text" name="role_description" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>Manage Roles & Permissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th style="width: 50%;">Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): 
                        $currentPermissions = $rolePermissions[$role['id']] ?? [];
                        $permissionIds = array_column($currentPermissions, 'id');
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                            <td>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="update_permissions">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; max-height: 200px; overflow-y: auto; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                        <?php foreach ($allPermissions as $permission): ?>
                                            <label style="display: flex; align-items: center; font-size: 13px; cursor: pointer;">
                                                <input type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" 
                                                    <?php echo in_array($permission['id'], $permissionIds) ? 'checked' : ''; ?>
                                                    style="margin-right: 6px;">
                                                <span title="<?php echo htmlspecialchars($permission['description']); ?>">
                                                    <?php echo htmlspecialchars($permission['name']); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-small" style="margin-top: 8px;">Update Permissions</button>
                                </form>
                            </td>
                            <td>
                                <button onclick='editRole(<?php echo json_encode(['id' => $role['id'], 'name' => $role['name'], 'description' => $role['description']]); ?>)' 
                                    class="btn btn-primary btn-small">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this role? This will remove all assignments.');">
                                    <input type="hidden" name="action" value="delete_role">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit Role Modal (hidden by default) -->
        <div id="editRoleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
                <h2 style="margin-top: 0;">Edit Role</h2>
                <form method="POST" id="editRoleForm">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Role Name *</label>
                        <input type="text" name="role_name" id="edit_role_name" required style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                        <textarea name="role_description" id="edit_role_description" rows="3" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeEditModal()" class="btn btn-danger">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section">
            <h2>Available Roles</h2>
            <p style="color: #666; margin-bottom: 15px; font-size: 14px;">Below is a list of all roles in the system with their descriptions.</p>
            <table>
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>User Role Assignments</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Current Roles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                <small style="color: #666;">@<?php echo htmlspecialchars($user['username']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php 
                                $currentRoles = $userRoles[$user['id']] ?? [];
                                if (empty($currentRoles)): ?>
                                    <em style="color: #999;">No roles assigned</em>
                                <?php else: 
                                    foreach ($currentRoles as $role): 
                                        $roleClass = strtolower(str_replace(' ', '-', $role['name']));
                                ?>
                                    <span class="role-badge <?php echo $roleClass; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </span>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="assign_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role_id" required>
                                            <option value="">Select Role</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['id']; ?>">
                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-small">Assign</button>
                                    </form>
                                    
                                    <?php if (!empty($currentRoles)): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role_id" required>
                                                <option value="">Select Role to Remove</option>
                                                <?php foreach ($currentRoles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>">
                                                        <?php echo htmlspecialchars($role['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-danger btn-small">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editRole(roleData) {
            document.getElementById('edit_role_id').value = roleData.id;
            document.getElementById('edit_role_name').value = roleData.name;
            document.getElementById('edit_role_description').value = roleData.description;
            document.getElementById('editRoleModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editRoleModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('editRoleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
