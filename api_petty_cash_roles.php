<?php
/**
 * api_petty_cash_roles.php
 * 
 * API endpoint for managing petty cash user roles
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';
require_once 'petty_cash_rbac.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get users with their roles
        if (isset($_GET['user_id'])) {
            // Get roles for specific user
            $roles = getUserPettyCashRoles($_GET['user_id']);
            echo json_encode([
                'success' => true,
                'data' => $roles
            ]);
        } elseif (isset($_GET['permission_matrix'])) {
            // Get permission matrix
            $matrix = getPettyCashPermissionMatrix();
            echo json_encode([
                'success' => true,
                'data' => $matrix
            ]);
        } else {
            // Get all users with their roles
            $users = getAllPettyCashUserRoles();
            echo json_encode([
                'success' => true,
                'data' => $users
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Only admins can manage roles
        requirePettyCashPermission('manage_roles');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action is required.']);
            exit;
        }
        
        switch ($data['action']) {
            case 'assign':
                // Assign role to user
                if (empty($data['user_id']) || empty($data['role'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'User ID and role are required.']);
                    exit;
                }
                
                // Validate role
                $validRoles = ['viewer', 'cashier', 'approver', 'admin'];
                if (!in_array($data['role'], $validRoles)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid role.']);
                    exit;
                }
                
                $success = assignPettyCashRole($data['user_id'], $data['role'], $_SESSION['user_id']);
                
                if ($success) {
                    // Log activity
                    if (function_exists('logActivity')) {
                        logActivity($_SESSION['user_id'], 'assign-petty-cash-role', 'users', $data['user_id'],
                                   json_encode(['role' => $data['role']]));
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Role assigned successfully.'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed to assign role.']);
                }
                break;
                
            case 'remove':
                // Remove role from user
                if (empty($data['user_id']) || empty($data['role'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'User ID and role are required.']);
                    exit;
                }
                
                $success = removePettyCashRole($data['user_id'], $data['role']);
                
                if ($success) {
                    // Log activity
                    if (function_exists('logActivity')) {
                        logActivity($_SESSION['user_id'], 'remove-petty-cash-role', 'users', $data['user_id'],
                                   json_encode(['role' => $data['role']]));
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Role removed successfully.'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed to remove role.']);
                }
                break;
                
            case 'update':
                // Update user's roles (replace all)
                if (empty($data['user_id']) || !isset($data['roles'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'User ID and roles array are required.']);
                    exit;
                }
                
                // Remove all existing roles
                $deleteSql = "DELETE FROM petty_cash_roles WHERE user_id = :user_id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute([':user_id' => $data['user_id']]);
                
                // Add new roles
                $validRoles = ['viewer', 'cashier', 'approver', 'admin'];
                $assignedRoles = [];
                
                foreach ($data['roles'] as $role) {
                    if (in_array($role, $validRoles)) {
                        assignPettyCashRole($data['user_id'], $role, $_SESSION['user_id']);
                        $assignedRoles[] = $role;
                    }
                }
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], 'update-petty-cash-roles', 'users', $data['user_id'],
                               json_encode(['roles' => $assignedRoles]));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Roles updated successfully.',
                    'assigned_roles' => $assignedRoles
                ]);
                break;
                
            case 'check_permission':
                // Check if user has permission for an action
                if (empty($data['user_id']) || empty($data['permission'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'User ID and permission are required.']);
                    exit;
                }
                
                $hasPermission = canPerformPettyCashAction($data['user_id'], $data['permission']);
                
                echo json_encode([
                    'success' => true,
                    'has_permission' => $hasPermission
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
                break;
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
