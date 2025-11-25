# User Role Management - Quick Reference

## TL;DR

**Problem**: user_roles table was empty, limited roles available, no default role assignment, admin user lacked Super Admin access
**Solution**: 4 migrations + registration enhancement = fully functional RBAC system with proper admin access

## Quick Deploy

```bash
# 1. Backup
mysqldump -u username -p duns > backup.sql

# 2. Apply migrations
mysql -u username -p duns < migrations/010_add_additional_roles.sql
mysql -u username -p duns < migrations/011_populate_user_roles.sql
mysql -u username -p duns < migrations/012_add_default_role_to_users.sql  # optional
mysql -u username -p duns < migrations/013_upgrade_admin_to_superadmin.sql  # important

# 3. Test
php test_rbac_system.php
```

## Available Roles

| Role | Access Level | Permissions Count |
|------|-------------|-------------------|
| Super Admin | Full | All |
| Admin | High | Most (no manage-roles) |
| Accountant | Medium | Financial operations |
| Manager | Medium | View & manage clients |
| Cashier | Low | Cash & payments |
| Approver | Medium | Review & approve |
| Viewer | Read-only | View only |

## New User Registration

**Before**: No role assigned
**After**: Automatically gets "Viewer" role (read-only access)

## Quick Permission Check

```php
require_once 'rbac.php';

// Check permission
if (userHasPermission($userId, 'create-invoice')) {
    // Allow
} else {
    // Deny
}
```

## Assign Role to User

```php
require_once 'rbac.php';

// Get role ID
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$stmt->execute(['Accountant']);
$role = $stmt->fetch();

// Assign role
assignRoleToUser($userId, $role['id']);
```

## Common SQL Queries

```sql
-- View all user roles
SELECT u.username, r.name as role
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id;

-- View user permissions
SELECT DISTINCT p.name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN role_permissions rp ON ur.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.id = [USER_ID];

-- Assign role to user
INSERT INTO user_roles (user_id, role_id)
VALUES ([USER_ID], [ROLE_ID]);

-- Remove role from user
DELETE FROM user_roles 
WHERE user_id = [USER_ID] AND role_id = [ROLE_ID];
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| New user has no role | Apply migration 010 to create Viewer role |
| Permission check returns false | Verify user has role assigned in user_roles table |
| Migration fails | Check if migrations 001-009 are applied first |
| Registration fails | Ensure rbac.php is included and Viewer role exists |

## Files Changed

**Created (11)**:
- migrations/010_add_additional_roles.sql
- migrations/011_populate_user_roles.sql
- migrations/012_add_default_role_to_users.sql
- migrations/013_upgrade_admin_to_superadmin.sql
- test_rbac_system.php
- test_migrations.sh
- USER_ROLE_MANAGEMENT_FIX.md
- REGISTRATION_ROLE_ASSIGNMENT.md
- IMPLEMENTATION_SUMMARY_USER_ROLES.md
- QUICK_REFERENCE_USER_ROLES.md
- migrations/README.md (updated)

**Modified (1)**:
- regi#s%^&ter.php

## Key Functions (rbac.php)

```php
// Check permission
userHasPermission($userId, $permissionName)

// Get user roles
getUserRoles($userId)

// Get user permissions
getUserPermissions($userId)

// Assign role
assignRoleToUser($userId, $roleId)

// Remove role
removeRoleFromUser($userId, $roleId)

// Check any permissions
userHasAnyPermission($userId, $permissionArray)

// Check all permissions
userHasAllPermissions($userId, $permissionArray)
```

## Default User Assignments

| User | Email | Role |
|------|-------|------|
| Joseph | niyogushimwaj967@gmail.com | Super Admin (upgraded via migration 013) |
| Cartine | uzacartine@gmail.com | Accountant |
| Mark | ellyj164@gmail.com | Manager |
| ellican | (if exists) | Super Admin |

## Role Permissions Matrix

| Permission | Super Admin | Admin | Accountant | Manager | Cashier | Approver | Viewer |
|-----------|-------------|-------|------------|---------|---------|----------|--------|
| create-invoice | ✓ | ✓ | ✓ | | | | |
| edit-invoice | ✓ | ✓ | ✓ | | | ✓ | |
| delete-invoice | ✓ | ✓ | | | | | |
| view-invoice | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create-client | ✓ | ✓ | | | | | |
| edit-client | ✓ | ✓ | | ✓ | | | |
| delete-client | ✓ | ✓ | | | | | |
| view-client | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create-user | ✓ | ✓ | | | | | |
| edit-user | ✓ | ✓ | | | | | |
| delete-user | ✓ | | | | | | |
| view-user | ✓ | ✓ | | | | | |
| view-reports | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ |
| manage-settings | ✓ | ✓ | | | | | |
| view-audit-logs | ✓ | ✓ | | | | ✓ | |
| manage-roles | ✓ | | | | | | |
| create-receipt | ✓ | ✓ | ✓ | | ✓ | | |
| edit-receipt | ✓ | ✓ | ✓ | | | | |
| view-transactions | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

## Security Best Practices

1. ✅ Always check permissions before sensitive operations
2. ✅ Use `userHasPermission()` instead of checking roles directly
3. ✅ Log all role assignment changes to activity_logs
4. ✅ Review user roles quarterly
5. ✅ Follow principle of least privilege
6. ✅ Store roles in session after login for performance
7. ✅ Validate session on every request

## Need More Info?

- **Full Guide**: USER_ROLE_MANAGEMENT_FIX.md
- **Registration**: REGISTRATION_ROLE_ASSIGNMENT.md
- **Summary**: IMPLEMENTATION_SUMMARY_USER_ROLES.md
- **Migrations**: migrations/README.md
- **RBAC System**: RBAC_IMPLEMENTATION_GUIDE.md

## Status

✅ **Complete** - Ready for production deployment
- All tests passed
- Code review approved
- Security scan clear
- Documentation complete
