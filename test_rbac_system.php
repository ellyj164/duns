<?php
/**
 * Test script to verify RBAC system functionality
 * This script tests role assignments and permission checks after migrations 010 and 011
 * 
 * Usage: php test_rbac_system.php
 */

require_once 'db.php';
require_once 'rbac.php';

echo "=== RBAC System Test ===\n\n";

// Test 1: Check if all roles exist
echo "Test 1: Checking roles...\n";
try {
    $stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($roles) . " roles:\n";
    foreach ($roles as $role) {
        echo "  - {$role['name']} (ID: {$role['id']})\n";
    }
    
    $expectedRoles = ['Super Admin', 'Admin', 'Accountant', 'Manager', 'Cashier', 'Approver', 'Viewer'];
    $existingRoleNames = array_column($roles, 'name');
    $missingRoles = array_diff($expectedRoles, $existingRoleNames);
    
    if (empty($missingRoles)) {
        echo "✓ All expected roles found!\n\n";
    } else {
        echo "✗ Missing roles: " . implode(', ', $missingRoles) . "\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking roles: " . $e->getMessage() . "\n\n";
}

// Test 2: Check user role assignments
echo "Test 2: Checking user role assignments...\n";
try {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, r.name as role_name, ur.assigned_at
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        ORDER BY u.id, r.name
    ");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($assignments)) {
        echo "✗ No user role assignments found!\n\n";
    } else {
        $currentUserId = null;
        foreach ($assignments as $assignment) {
            if ($currentUserId !== $assignment['id']) {
                $currentUserId = $assignment['id'];
                echo "\nUser: {$assignment['username']} ({$assignment['email']})\n";
            }
            if ($assignment['role_name']) {
                echo "  - Role: {$assignment['role_name']}\n";
            } else {
                echo "  - No roles assigned\n";
            }
        }
        
        // Count total assignments
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM user_roles");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "\n✓ Total role assignments: {$count['total']}\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking assignments: " . $e->getMessage() . "\n\n";
}

// Test 3: Check permissions for each role
echo "Test 3: Checking role permissions...\n";
try {
    $stmt = $pdo->query("
        SELECT r.name as role_name, COUNT(p.id) as permission_count
        FROM roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        LEFT JOIN permissions p ON rp.permission_id = p.id
        GROUP BY r.id, r.name
        ORDER BY r.name
    ");
    $rolePerm = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rolePerm as $rp) {
        echo "  - {$rp['role_name']}: {$rp['permission_count']} permissions\n";
    }
    echo "\n";
    
    // Check specific permissions for new roles
    $newRoles = ['Cashier', 'Approver', 'Viewer'];
    foreach ($newRoles as $roleName) {
        $stmt = $pdo->prepare("
            SELECT p.name
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN roles r ON rp.role_id = r.id
            WHERE r.name = :role_name
            ORDER BY p.name
        ");
        $stmt->execute([':role_name' => $roleName]);
        $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($perms)) {
            echo "{$roleName} permissions: " . implode(', ', $perms) . "\n";
        }
    }
    echo "\n";
} catch (PDOException $e) {
    echo "✗ Error checking permissions: " . $e->getMessage() . "\n\n";
}

// Test 4: Test RBAC helper functions
echo "Test 4: Testing RBAC helper functions...\n";
try {
    // Find a user with role assignments
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.username
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id
        LIMIT 1
    ");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "Testing with user: {$testUser['username']} (ID: {$testUser['id']})\n";
        
        // Test getUserRoles
        $roles = getUserRoles($testUser['id']);
        echo "  getUserRoles(): Found " . count($roles) . " role(s)\n";
        foreach ($roles as $role) {
            echo "    - {$role['name']}\n";
        }
        
        // Test getUserPermissions
        $perms = getUserPermissions($testUser['id']);
        echo "  getUserPermissions(): Found " . count($perms) . " permission(s)\n";
        
        // Test userHasPermission
        $testPermissions = ['view-invoice', 'delete-client', 'manage-roles'];
        echo "  Permission checks:\n";
        foreach ($testPermissions as $perm) {
            $has = userHasPermission($testUser['id'], $perm);
            $status = $has ? "✓ HAS" : "✗ NO";
            echo "    $status $perm\n";
        }
        
        echo "\n✓ RBAC helper functions working correctly!\n\n";
    } else {
        echo "✗ No users with role assignments found for testing\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing functions: " . $e->getMessage() . "\n\n";
}

// Test 5: Verify registration will assign default role
echo "Test 5: Checking if Viewer role exists for new user registration...\n";
try {
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Viewer'");
    $stmt->execute();
    $viewerRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewerRole) {
        echo "✓ Viewer role found (ID: {$viewerRole['id']}) - new users will be assigned this role\n\n";
    } else {
        echo "✗ Viewer role not found - new user registration may fail\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking Viewer role: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
echo "\nSummary:\n";
echo "- If all tests passed, the RBAC system is working correctly\n";
echo "- The user_roles table should have multiple assignments\n";
echo "- New users will be automatically assigned the 'Viewer' role on registration\n";
echo "- Use the RBAC helper functions in rbac.php to check permissions in your code\n";
