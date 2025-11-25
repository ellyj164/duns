# User Role Management Database Fix

## Overview

This document describes the fixes applied to resolve user role management issues in the DUNS financial management system.

## Problems Addressed

1. **Empty user_roles table**: The `user_roles` table had only 1 entry, leaving most users without role assignments
2. **Limited role options**: Only 4 basic roles were available (Super Admin, Admin, Accountant, Manager)
3. **No default role assignments**: New and existing users had no clear role assignment process

## Solution Summary

Four database migrations have been created to fix these issues:

1. **Migration 010**: Adds three new roles (Cashier, Approver, Viewer) with appropriate permissions
2. **Migration 011**: Populates the user_roles table with default assignments for existing users
3. **Migration 012** (Optional): Adds a default_role_id column to the users table for convenience
4. **Migration 013** (Important): Upgrades niyogushimwaj967@gmail.com from Admin to Super Admin for full access

## Database Changes

### New Roles Added (Migration 010)

| Role     | Description                                      | Permissions                                                      |
|----------|--------------------------------------------------|------------------------------------------------------------------|
| Cashier  | Access to cash management and payment processing | view-invoice, view-client, create-receipt, view-transactions     |
| Approver | Access to approve and review transactions        | view-invoice, edit-invoice, view-client, view-reports, view-transactions, view-audit-logs |
| Viewer   | Read-only access to view reports and data        | view-invoice, view-client, view-reports, view-transactions       |

### User Role Assignments (Migration 011)

The following default assignments are made:

| User Email                     | Assigned Role | Rationale                                    |
|--------------------------------|---------------|----------------------------------------------|
| niyogushimwaj967@gmail.com     | Super Admin   | Upgraded to Super Admin for unrestricted access to all features (migration 013) |
| uzacartine@gmail.com           | Accountant    | Based on activity logs showing financial ops  |
| ellyj164@gmail.com             | Manager       | Assigned manager role for client management   |
| ellican (username)             | Super Admin   | If exists, assigned highest privilege level   |

### Optional Users Table Enhancement (Migration 012)

Adds a `default_role_id` column to the `users` table:
- **Type**: INT, nullable
- **Purpose**: Quick reference to a user's primary role
- **Foreign Key**: References `roles(id)` with ON DELETE SET NULL
- **Note**: The `user_roles` pivot table remains the authoritative source for role assignments

## Installation Instructions

### Prerequisites

- MySQL/MariaDB database access
- Backup your database before applying migrations
- Ensure migrations 001-009 have been applied

### Step 1: Backup Database

```bash
mysqldump -u your_username -p duns > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Apply Migrations

```bash
cd migrations

# Apply migration 010 - Add new roles
mysql -u your_username -p duns < 010_add_additional_roles.sql

# Apply migration 011 - Populate user roles
mysql -u your_username -p duns < 011_populate_user_roles.sql

# Optional: Apply migration 012 - Add default role column
mysql -u your_username -p duns < 012_add_default_role_to_users.sql

# Important: Apply migration 013 - Upgrade to Super Admin
mysql -u your_username -p duns < 013_upgrade_admin_to_superadmin.sql
```

### Step 3: Verify Installation

```sql
-- Check all roles exist (should show 7 roles)
SELECT * FROM `roles` ORDER BY id;

-- Verify user role assignments (should show multiple entries)
SELECT 
    u.id, 
    u.username, 
    u.email, 
    r.name as role_name, 
    ur.assigned_at
FROM `users` u
LEFT JOIN `user_roles` ur ON u.id = ur.user_id
LEFT JOIN `roles` r ON ur.role_id = r.id
ORDER BY u.id, r.name;

-- Check that user_roles table is populated
SELECT COUNT(*) as total_assignments FROM `user_roles`;
-- Should return at least 3-4 assignments

-- Verify permissions for new roles
SELECT 
    r.name as role, 
    COUNT(p.id) as permission_count
FROM `roles` r
LEFT JOIN `role_permissions` rp ON r.id = rp.role_id
LEFT JOIN `permissions` p ON rp.permission_id = p.id
WHERE r.name IN ('Cashier', 'Approver', 'Viewer')
GROUP BY r.name;
```

## Usage Examples

### Checking User Roles in PHP

```php
<?php
require_once 'rbac.php';

// Get all roles for a user
$roles = getUserRoles($userId);
foreach ($roles as $role) {
    echo "Role: " . $role['name'] . " - " . $role['description'] . "\n";
}

// Check if user has a specific permission
if (userHasPermission($userId, 'create-invoice')) {
    // Allow invoice creation
} else {
    // Deny access
    echo "You don't have permission to create invoices.";
}
?>
```

### Assigning Roles to New Users

```sql
-- Assign Viewer role to a new user
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'newuser@example.com'
  AND r.name = 'Viewer';

-- Assign multiple roles to a user
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'poweruser@example.com'
  AND r.name IN ('Cashier', 'Approver');
```

### Updating User Roles

```sql
-- Remove all roles from a user
DELETE FROM `user_roles` WHERE user_id = 123;

-- Change user's role
DELETE FROM `user_roles` WHERE user_id = 123;
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT 123, id FROM `roles` WHERE name = 'Accountant';

-- Add an additional role to a user
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT 123, id FROM `roles` WHERE name = 'Viewer'
ON DUPLICATE KEY UPDATE user_id = user_id;
```

## Architecture Notes

### Many-to-Many Relationship

The system uses a **many-to-many relationship** between users and roles via the `user_roles` pivot table:

```
users (1) ←→ (many) user_roles (many) ←→ (1) roles
```

This design allows:
- A user to have multiple roles
- A role to be assigned to multiple users
- Flexible permission management through the combination of roles

### Permission Inheritance

Permissions are inherited from roles through the `role_permissions` table:

```
users → user_roles → roles → role_permissions → permissions
```

### Optional Default Role

The optional `default_role_id` column in the `users` table:
- Provides quick reference to primary role
- Useful for UI display and default selections
- Does NOT replace the `user_roles` pivot table
- Can be NULL if user has no default role

## Testing the System

### Test User Permissions

```php
<?php
// Test script: test_permissions.php
require_once 'db.php';
require_once 'rbac.php';

$testUserId = 11; // Cartine (should have Accountant role)

echo "Testing permissions for user ID: $testUserId\n\n";

// Get user roles
$roles = getUserRoles($testUserId);
echo "Roles:\n";
foreach ($roles as $role) {
    echo "  - " . $role['name'] . "\n";
}
echo "\n";

// Get user permissions
$permissions = getUserPermissions($testUserId);
echo "Permissions:\n";
foreach ($permissions as $perm) {
    echo "  - " . $perm['name'] . "\n";
}
echo "\n";

// Test specific permissions
$testPermissions = [
    'create-invoice',
    'delete-client',
    'view-reports',
    'manage-roles'
];

echo "Permission Checks:\n";
foreach ($testPermissions as $perm) {
    $has = userHasPermission($testUserId, $perm);
    $status = $has ? "✓ HAS" : "✗ NO";
    echo "  $status $perm\n";
}
?>
```

## Rollback Instructions

If you need to rollback these changes:

### Rollback Migration 012 (Optional Column)

```sql
ALTER TABLE `users` DROP FOREIGN KEY `fk_users_default_role`;
ALTER TABLE `users` DROP INDEX `idx_default_role_id`;
ALTER TABLE `users` DROP COLUMN `default_role_id`;
```

### Rollback Migration 011 (User Assignments)

```sql
-- Remove role assignments added by this migration
DELETE ur FROM `user_roles` ur
INNER JOIN `users` u ON ur.user_id = u.id
WHERE u.email IN ('uzacartine@gmail.com', 'ellyj164@gmail.com');

-- Keep only original assignments for niyogushimwaj967@gmail.com and ellican
```

### Rollback Migration 010 (New Roles)

```sql
-- Remove new roles and their permissions
DELETE FROM `role_permissions` WHERE role_id IN (
  SELECT id FROM `roles` WHERE name IN ('Cashier', 'Approver', 'Viewer')
);
DELETE FROM `user_roles` WHERE role_id IN (
  SELECT id FROM `roles` WHERE name IN ('Cashier', 'Approver', 'Viewer')
);
DELETE FROM `roles` WHERE name IN ('Cashier', 'Approver', 'Viewer');
```

## Troubleshooting

### Issue: Migration 011 doesn't assign roles

**Cause**: User emails or usernames don't match exactly

**Solution**: 
```sql
-- Check actual user data
SELECT id, username, email FROM users;

-- Update migration with correct values and re-run
```

### Issue: Foreign key constraint fails

**Cause**: Migration 005 (RBAC tables) not applied

**Solution**: Apply migrations in order (001-009 first, then 010-012)

### Issue: Duplicate key errors

**Cause**: Migration already applied

**Solution**: All migrations use `ON DUPLICATE KEY UPDATE` and are idempotent - safe to re-run

## Security Considerations

1. **Role Assignment**: Only Super Admin should be able to assign roles
2. **Permission Checks**: Always verify permissions before allowing actions
3. **Audit Logging**: All role changes should be logged to activity_logs table
4. **Default Roles**: Set appropriate default roles for new user registrations
5. **Least Privilege**: Assign users the minimum roles needed for their work

## Next Steps

After applying these migrations:

1. **Update Registration Form**: Modify user registration to assign a default role (e.g., Viewer)
2. **Admin UI**: Create an interface for managing user roles
3. **Documentation**: Update user guides with role descriptions
4. **Training**: Educate users on the new role system
5. **Audit**: Review all user assignments and adjust as needed

## Related Documentation

- `migrations/README.md` - Complete migration documentation
- `RBAC_IMPLEMENTATION_GUIDE.md` - Full RBAC system guide
- `rbac.php` - RBAC helper functions

## Support

For issues or questions:
1. Check the verification queries above
2. Review migration files in `migrations/` directory
3. Check database logs for error messages
4. Contact the development team

## Change History

- **2025-11-23**: Created migrations 010, 011, 012 to fix user role management issues
- **2025-11-03**: Initial RBAC system implemented (migrations 005, 009)
