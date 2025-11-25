<?php
/**
 * petty_cash_rbac.php
 * 
 * Role-Based Access Control for Petty Cash module
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

/**
 * Check if user has a specific petty cash role
 */
function hasPettyCashRole($userId, $role) {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) as count FROM petty_cash_roles WHERE user_id = :user_id AND role = :role";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $role
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all roles for a user
 */
function getUserPettyCashRoles($userId) {
    global $pdo;
    
    try {
        $sql = "SELECT role FROM petty_cash_roles WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $roles;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Check if user can perform an action on petty cash
 */
function canPerformPettyCashAction($userId, $action) {
    $roles = getUserPettyCashRoles($userId);
    
    // Admin can do everything
    if (in_array('admin', $roles)) {
        return true;
    }
    
    // Define action permissions
    $permissions = [
        'create' => ['cashier', 'admin'],
        'edit' => ['cashier', 'admin'],
        'delete' => ['admin'],
        'approve' => ['approver', 'admin'],
        'view' => ['viewer', 'cashier', 'approver', 'admin'],
        'reconcile' => ['approver', 'admin'],
        'replenish' => ['cashier', 'approver', 'admin'],
        'export' => ['cashier', 'approver', 'admin'],
        'manage_categories' => ['admin'],
        'manage_settings' => ['admin'],
        'manage_roles' => ['admin']
    ];
    
    if (!isset($permissions[$action])) {
        return false;
    }
    
    $allowedRoles = $permissions[$action];
    
    foreach ($roles as $role) {
        if (in_array($role, $allowedRoles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Assign a role to a user
 */
function assignPettyCashRole($userId, $role, $assignedBy) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO petty_cash_roles (user_id, role, assigned_by) 
                VALUES (:user_id, :role, :assigned_by)
                ON DUPLICATE KEY UPDATE assigned_by = :assigned_by, assigned_at = CURRENT_TIMESTAMP";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $role,
            ':assigned_by' => $assignedBy
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Remove a role from a user
 */
function removePettyCashRole($userId, $role) {
    global $pdo;
    
    try {
        $sql = "DELETE FROM petty_cash_roles WHERE user_id = :user_id AND role = :role";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $role
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all users with their petty cash roles
 */
function getAllPettyCashUserRoles() {
    global $pdo;
    
    try {
        $sql = "SELECT u.id, u.username, u.email, 
                       GROUP_CONCAT(pcr.role ORDER BY pcr.role) as roles
                FROM users u
                LEFT JOIN petty_cash_roles pcr ON u.id = pcr.user_id
                GROUP BY u.id, u.username, u.email
                ORDER BY u.username";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse roles into array
        foreach ($users as &$user) {
            $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
        }
        
        return $users;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Check if user is petty cash admin
 */
function isPettyCashAdmin($userId) {
    return hasPettyCashRole($userId, 'admin');
}

/**
 * Require specific petty cash permission (use in API endpoints)
 */
function requirePettyCashPermission($action) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required.']);
        exit;
    }
    
    if (!canPerformPettyCashAction($_SESSION['user_id'], $action)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied. You do not have permission to perform this action.']);
        exit;
    }
}

/**
 * Get permission matrix for display
 */
function getPettyCashPermissionMatrix() {
    return [
        'viewer' => [
            'view' => true,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'approve' => false,
            'reconcile' => false,
            'replenish' => false,
            'export' => false,
            'manage_categories' => false,
            'manage_settings' => false,
            'manage_roles' => false
        ],
        'cashier' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => false,
            'approve' => false,
            'reconcile' => false,
            'replenish' => true,
            'export' => true,
            'manage_categories' => false,
            'manage_settings' => false,
            'manage_roles' => false
        ],
        'approver' => [
            'view' => true,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'approve' => true,
            'reconcile' => true,
            'replenish' => true,
            'export' => true,
            'manage_categories' => false,
            'manage_settings' => false,
            'manage_roles' => false
        ],
        'admin' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'approve' => true,
            'reconcile' => true,
            'replenish' => true,
            'export' => true,
            'manage_categories' => true,
            'manage_settings' => true,
            'manage_roles' => true
        ]
    ];
}

/**
 * Auto-assign default role to user if they don't have any
 */
function ensureUserHasPettyCashRole($userId) {
    $roles = getUserPettyCashRoles($userId);
    
    // If user has no roles, assign cashier as default
    if (empty($roles)) {
        assignPettyCashRole($userId, 'cashier', $userId);
    }
}
?>
