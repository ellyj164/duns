# Registration Role Assignment Enhancement

## Overview

The user registration process has been enhanced to automatically assign a default "Viewer" role to all new users. This ensures that every user in the system has at least basic read-only access upon registration.

## Changes Made

### Modified Files

1. **regi#s%^&ter.php** (Registration file)
   - Added `require_once 'rbac.php'` to include RBAC helper functions
   - Added code to assign "Viewer" role immediately after user creation
   - Uses `assignRoleToUser()` function from rbac.php

### Implementation Details

After a new user is successfully created in the database:

1. Get the newly created user's ID using `$pdo->lastInsertId()`
2. Query the database for the "Viewer" role ID
3. Call `assignRoleToUser($newUserId, $viewerRole['id'])` to assign the role
4. Continue with email verification process

### Code Changes

```php
// After user insertion
$newUserId = $pdo->lastInsertId();

// Assign default "Viewer" role to new user
$viewerRoleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Viewer' LIMIT 1");
$viewerRoleStmt->execute();
$viewerRole = $viewerRoleStmt->fetch(PDO::FETCH_ASSOC);

if ($viewerRole) {
    assignRoleToUser($newUserId, $viewerRole['id']);
}
```

## Why "Viewer" Role?

The "Viewer" role was chosen as the default for new registrations because:

1. **Security**: It provides minimal permissions (read-only access)
2. **Safety**: Users can view data but cannot modify anything
3. **Flexibility**: Administrators can easily upgrade users to more privileged roles
4. **Compliance**: Follows the principle of least privilege

## Permissions for Viewer Role

Users with the "Viewer" role can:
- View invoices (`view-invoice`)
- View client information (`view-client`)
- View reports (`view-reports`)
- View transaction history (`view-transactions`)

Users with the "Viewer" role **cannot**:
- Create, edit, or delete any records
- Manage users or settings
- Access audit logs
- Perform financial transactions

## Admin Actions Required

After a new user registers:

1. **Email Verification**: User must verify their email address
2. **Role Review**: Admin should review and upgrade role if needed
3. **Permission Check**: Verify the user has appropriate access

### Upgrading User Roles

Administrators can upgrade user roles using SQL or through the admin interface:

```sql
-- Remove existing role
DELETE FROM user_roles WHERE user_id = [USER_ID];

-- Assign new role (e.g., Accountant)
INSERT INTO user_roles (user_id, role_id)
SELECT [USER_ID], id FROM roles WHERE name = 'Accountant';
```

Or using PHP:

```php
require_once 'rbac.php';

// Remove old role
removeRoleFromUser($userId, $oldRoleId);

// Assign new role
$accountantRoleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Accountant'");
$accountantRoleStmt->execute();
$accountantRole = $accountantRoleStmt->fetch(PDO::FETCH_ASSOC);

assignRoleToUser($userId, $accountantRole['id']);
```

## Testing the Changes

### Manual Testing

1. **Register a New User**:
   - Go to the registration page
   - Fill out the form and submit
   - Complete email verification

2. **Verify Role Assignment**:
   ```sql
   SELECT u.username, u.email, r.name as role
   FROM users u
   INNER JOIN user_roles ur ON u.id = ur.user_id
   INNER JOIN roles r ON ur.role_id = r.id
   WHERE u.email = '[NEW_USER_EMAIL]';
   ```
   Should return: `Viewer` role

3. **Test Permissions**:
   - Log in as the new user
   - Verify they can view data
   - Verify they cannot create/edit/delete

### Automated Testing

Run the test script:

```bash
php test_rbac_system.php
```

This will verify:
- Viewer role exists
- Role assignments are working
- RBAC helper functions work correctly

## Fallback Behavior

If the "Viewer" role does not exist in the database:
- User registration will still succeed
- User will have no role assigned
- Admin must manually assign a role later

**Important**: Ensure migration 010 has been applied before using the registration form to guarantee the "Viewer" role exists.

## Migration Dependencies

This feature depends on:
- **Migration 010**: Creates the "Viewer" role
- **Migration 005**: Creates the RBAC tables (roles, permissions, role_permissions, user_roles)
- **rbac.php**: Provides helper functions for role management

## Future Enhancements

Potential improvements:
1. **Configurable Default Role**: Allow admins to configure which role is assigned by default
2. **Role Selection During Registration**: Let users select their intended role (pending admin approval)
3. **Automatic Upgrades**: Automatically upgrade users based on certain criteria
4. **Department-Based Roles**: Assign roles based on department or organization unit

## Troubleshooting

### Issue: New users have no role assigned

**Cause**: Viewer role doesn't exist in database

**Solution**: 
```bash
# Apply migration 010
mysql -u username -p duns < migrations/010_add_additional_roles.sql
```

### Issue: Registration fails with database error

**Cause**: RBAC tables not created

**Solution**:
```bash
# Apply migration 005
mysql -u username -p duns < migrations/005_create_rbac_tables.sql
```

### Issue: Role assignment fails silently

**Cause**: rbac.php not included or assignRoleToUser() function error

**Solution**:
- Check that `require_once 'rbac.php'` is at the top of registration file
- Check PHP error logs for details
- Verify database connection is working

## Security Considerations

1. **Role Verification**: Always verify user roles before granting access to sensitive operations
2. **Session Management**: Store user roles in session after login for performance
3. **Permission Checks**: Use `userHasPermission()` function before allowing any action
4. **Audit Logging**: Log all role assignment changes to activity_logs table
5. **Regular Review**: Periodically review user roles and remove unnecessary privileges

## Related Documentation

- `USER_ROLE_MANAGEMENT_FIX.md` - Overview of role management fixes
- `RBAC_IMPLEMENTATION_GUIDE.md` - Complete RBAC system documentation
- `migrations/README.md` - Migration instructions
- `rbac.php` - RBAC helper functions

## Support

For issues or questions:
1. Check the test script output: `php test_rbac_system.php`
2. Review database logs for errors
3. Verify migrations have been applied correctly
4. Contact the development team

## Change History

- **2025-11-23**: Added automatic "Viewer" role assignment to new user registration
