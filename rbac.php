<?php
/**
 * RBAC Helper Functions
 * 
 * This file provides helper functions for Role-Based Access Control (RBAC)
 * Use: require_once 'rbac.php';
 */

require_once 'db.php';

/**
 * Check if a user has a specific permission
 * 
 * @param int $userId User ID
 * @param string $permissionName Permission name (e.g., 'create-invoice')
 * @return bool True if user has permission, false otherwise
 */
function userHasPermission($userId, $permissionName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_permission
            FROM user_roles ur
            INNER JOIN role_permissions rp ON ur.role_id = rp.role_id
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :user_id AND p.name = :permission_name
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'permission_name' => $permissionName
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['has_permission'] > 0;
    } catch (PDOException $e) {
        error_log("RBAC Permission Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all roles assigned to a user
 * 
 * @param int $userId User ID
 * @return array Array of role objects with id, name, and description
 */
function getUserRoles($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.description
            FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
            ORDER BY r.name
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Get User Roles Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all permissions for a user (from all their roles)
 * 
 * @param int $userId User ID
 * @return array Array of permission objects with id, name, and description
 */
function getUserPermissions($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id, p.name, p.description
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id
            ORDER BY p.name
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Get User Permissions Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has any of the specified permissions
 * 
 * @param int $userId User ID
 * @param array $permissions Array of permission names
 * @return bool True if user has at least one permission
 */
function userHasAnyPermission($userId, $permissions) {
    foreach ($permissions as $permission) {
        if (userHasPermission($userId, $permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all specified permissions
 * 
 * @param int $userId User ID
 * @param array $permissions Array of permission names
 * @return bool True if user has all permissions
 */
function userHasAllPermissions($userId, $permissions) {
    foreach ($permissions as $permission) {
        if (!userHasPermission($userId, $permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Assign a role to a user
 * 
 * @param int $userId User ID
 * @param int $roleId Role ID
 * @return bool True on success, false on failure
 */
function assignRoleToUser($userId, $roleId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_roles (user_id, role_id)
            VALUES (:user_id, :role_id)
            ON DUPLICATE KEY UPDATE user_id = user_id
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Assign Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a role from a user
 * 
 * @param int $userId User ID
 * @param int $roleId Role ID
 * @return bool True on success, false on failure
 */
function removeRoleFromUser($userId, $roleId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM user_roles 
            WHERE user_id = :user_id AND role_id = :role_id
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Remove Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all available roles in the system
 * 
 * @return array Array of role objects
 */
function getAllRoles() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Get All Roles Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all available permissions in the system
 * 
 * @return array Array of permission objects
 */
function getAllPermissions() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, name, description FROM permissions ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Get All Permissions Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Require a specific permission or redirect to access denied page
 * 
 * @param string $permissionName Permission name
 * @param string $redirectUrl URL to redirect to if permission denied (default: index.php)
 */
function requirePermission($permissionName, $redirectUrl = 'index.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    
    if (!userHasPermission($_SESSION['user_id'], $permissionName)) {
        $_SESSION['error_message'] = "Access denied. You don't have permission to access this resource.";
        header("Location: " . $redirectUrl);
        exit;
    }
}

/**
 * Check if user is a Super Admin
 * 
 * @param int $userId User ID
 * @return bool True if user is Super Admin
 */
function isSuperAdmin($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as is_super_admin
            FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = :user_id AND r.name = 'Super Admin'
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['is_super_admin'] > 0;
    } catch (PDOException $e) {
        error_log("RBAC Is Super Admin Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new role
 * 
 * @param string $name Role name
 * @param string $description Role description
 * @return int|bool Role ID on success, false on failure
 */
function createRole($name, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO roles (name, description)
            VALUES (:name, :description)
        ");
        
        $stmt->execute([
            'name' => $name,
            'description' => $description
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("RBAC Create Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing role
 * 
 * @param int $roleId Role ID
 * @param string $name Role name
 * @param string $description Role description
 * @return bool True on success, false on failure
 */
function updateRole($roleId, $name, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE roles 
            SET name = :name, description = :description
            WHERE id = :role_id
        ");
        
        $stmt->execute([
            'role_id' => $roleId,
            'name' => $name,
            'description' => $description
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Update Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a role
 * 
 * @param int $roleId Role ID
 * @return bool True on success, false on failure
 */
function deleteRole($roleId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :role_id");
        $stmt->execute(['role_id' => $roleId]);
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Delete Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get permissions for a specific role
 * 
 * @param int $roleId Role ID
 * @return array Array of permission objects
 */
function getRolePermissions($roleId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.description
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.name
        ");
        
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Get Role Permissions Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Assign a permission to a role
 * 
 * @param int $roleId Role ID
 * @param int $permissionId Permission ID
 * @return bool True on success, false on failure
 */
function assignPermissionToRole($roleId, $permissionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO role_permissions (role_id, permission_id)
            VALUES (:role_id, :permission_id)
        ");
        
        $stmt->execute([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Assign Permission Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a permission from a role
 * 
 * @param int $roleId Role ID
 * @param int $permissionId Permission ID
 * @return bool True on success, false on failure
 */
function removePermissionFromRole($roleId, $permissionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM role_permissions 
            WHERE role_id = :role_id AND permission_id = :permission_id
        ");
        
        $stmt->execute([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Remove Permission Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update all permissions for a role (replaces existing permissions)
 * 
 * @param int $roleId Role ID
 * @param array $permissionIds Array of permission IDs
 * @return bool True on success, false on failure
 */
function updateRolePermissions($roleId, $permissionIds) {
    global $pdo;
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Remove all existing permissions for this role
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $roleId]);
        
        // Add new permissions
        if (!empty($permissionIds)) {
            $stmt = $pdo->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                VALUES (:role_id, :permission_id)
            ");
            
            foreach ($permissionIds as $permissionId) {
                $stmt->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("RBAC Update Role Permissions Error: " . $e->getMessage());
        return false;
    }
}
?>
