# RBAC System - PHP Implementation

This document describes the PHP backend implementation of the Role-Based Access Control (RBAC) system.

## Overview

The RBAC system consists of several PHP files that provide backend functionality for role management, permission checking, activity logging, and settings management.

## PHP Files Created

### 1. `rbac.php` - RBAC Helper Functions

Core RBAC functionality for permission checking and role management.

**Key Functions:**
- `userHasPermission($userId, $permissionName)` - Check if user has a specific permission
- `getUserRoles($userId)` - Get all roles assigned to a user
- `getUserPermissions($userId)` - Get all permissions for a user
- `userHasAnyPermission($userId, $permissions)` - Check if user has any of specified permissions
- `userHasAllPermissions($userId, $permissions)` - Check if user has all specified permissions
- `assignRoleToUser($userId, $roleId)` - Assign a role to a user
- `removeRoleFromUser($userId, $roleId)` - Remove a role from a user
- `getAllRoles()` - Get all available roles
- `getAllPermissions()` - Get all available permissions
- `requirePermission($permissionName, $redirectUrl)` - Require permission or redirect
- `isSuperAdmin($userId)` - Check if user is Super Admin

**Usage Example:**
```php
require_once 'rbac.php';

// Check if user has permission
if (userHasPermission($_SESSION['user_id'], 'create-invoice')) {
    // Allow invoice creation
} else {
    // Deny access
}

// Require permission (redirects if not authorized)
requirePermission('manage-settings');
```

### 2. `activity_logger.php` - Activity Logging Helper

Functions for logging user activities and retrieving audit logs.

**Key Functions:**
- `logActivity($userId, $action, $targetType, $targetId, $details)` - Log a user activity
- `getUserActivityLogs($userId, $limit, $offset)` - Get activity logs for a user
- `getAllActivityLogs($limit, $offset, $action)` - Get all activity logs (admin)
- `getTargetActivityLogs($targetType, $targetId, $limit)` - Get logs for a specific resource
- `countActivityLogs($userId)` - Count total activity logs

**Usage Example:**
```php
require_once 'activity_logger.php';

// Log an activity
logActivity(
    $_SESSION['user_id'],
    'create-invoice',
    'clients',
    $clientId,
    ['invoice_number' => $invoiceNumber, 'amount' => $amount]
);

// Get user activity logs
$logs = getUserActivityLogs($_SESSION['user_id'], 50, 0);
```

### 3. `settings.php` - Settings Management Helper

Functions for managing system-wide settings.

**Key Functions:**
- `getSetting($key, $default)` - Get a setting value
- `setSetting($key, $value)` - Set a setting value
- `getSettings($keys)` - Get multiple settings
- `getAllSettings()` - Get all settings
- `deleteSetting($key)` - Delete a setting
- `settingExists($key)` - Check if setting exists
- `getCompanyInfo()` - Get company information
- `updateCompanyInfo($info)` - Update company information

**Usage Example:**
```php
require_once 'settings.php';

// Get a setting
$companyName = getSetting('company_name', 'Default Company');

// Set a setting
setSetting('tax_rate', '20');

// Get company info
$companyInfo = getCompanyInfo();
```

### 4. `manage_roles.php` - Role Management UI

Admin interface for managing user roles and permissions.

**Features:**
- View all available roles and their descriptions
- View users and their assigned roles
- Assign roles to users
- Remove roles from users
- Visual role badges with color coding

**Access:** Requires `manage-roles` permission

**URL:** `/manage_roles.php`

### 5. `view_activity_logs.php` - Activity Logs Viewer

Admin interface for viewing system activity logs.

**Features:**
- View all system activity logs
- Pagination support (50 logs per page)
- Filter by action type
- Color-coded action badges (create, update, delete, view)
- User information display
- IP address tracking
- Timestamp display

**Access:** Requires `view-audit-logs` permission

**URL:** `/view_activity_logs.php`

### 6. `manage_settings.php` - Settings Management UI

Admin interface for managing system settings.

**Features:**
- Edit company information (name, address, phone, email, website, TIN)
- Configure financial settings (default currency, tax rate)
- Manage branding (company logo URL)
- Live logo preview
- Form validation

**Access:** Requires `manage-settings` permission

**URL:** `/manage_settings.php`

## Integration with Existing Files

The following existing files have been updated to integrate RBAC and activity logging:

### `insert_client.php`
- Added activity logging for client creation
- Logs: `create-client` action with client details

### `delete_client.php`
- Added activity logging for client deletion
- Logs: `delete-client` action with client name and reg_no

## Using RBAC in Your Code

### Step 1: Include Required Files

```php
require_once 'rbac.php';
require_once 'activity_logger.php';
```

### Step 2: Check Permissions

```php
// Method 1: Simple check
if (userHasPermission($_SESSION['user_id'], 'create-invoice')) {
    // Allow action
} else {
    // Deny action
    $_SESSION['error_message'] = "Access denied.";
    header('Location: index.php');
    exit;
}

// Method 2: Require permission (automatic redirect)
requirePermission('manage-settings');
```

### Step 3: Log Activities

```php
// Log activity after action completes
logActivity(
    $_SESSION['user_id'],
    'update-client',
    'clients',
    $clientId,
    ['field_updated' => 'amount', 'new_value' => $newAmount]
);
```

## Available Permissions

The system includes 19 default permissions:

**Invoice Management:**
- `create-invoice` - Create new invoices
- `edit-invoice` - Edit existing invoices
- `delete-invoice` - Delete invoices
- `view-invoice` - View invoices

**Client Management:**
- `create-client` - Create new client records
- `edit-client` - Edit existing client records
- `delete-client` - Delete client records
- `view-client` - View client records

**User Management:**
- `create-user` - Create new user accounts
- `edit-user` - Edit user accounts
- `delete-user` - Delete user accounts
- `view-user` - View user accounts

**System Features:**
- `view-reports` - View financial reports and analytics
- `manage-settings` - Manage application settings
- `view-audit-logs` - View audit trail and activity logs
- `manage-roles` - Manage roles and permissions

**Receipts:**
- `create-receipt` - Create payment receipts
- `edit-receipt` - Edit payment receipts

**Transactions:**
- `view-transactions` - View transaction history

## Default Roles

The system includes 4 default roles:

1. **Super Admin**
   - Has ALL permissions
   - Can manage roles and permissions
   - Unrestricted access

2. **Admin**
   - Has most permissions except `manage-roles`
   - Can manage users, clients, invoices, and settings
   - Cannot modify role structure

3. **Accountant**
   - Financial focus permissions
   - Can create/edit invoices and receipts
   - Can view clients and reports
   - Cannot delete or manage users

4. **Manager**
   - View and basic edit permissions
   - Can view invoices, clients, and reports
   - Can edit clients
   - Cannot create/delete

## Adding New Pages with RBAC

To create a new page with RBAC:

```php
<?php
session_start();
require_once 'db.php';
require_once 'rbac.php';
require_once 'activity_logger.php';

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check permission
requirePermission('your-permission-name');

// Your page logic here

// Log activity when action completes
logActivity($_SESSION['user_id'], 'your-action', 'target-type', $targetId, $details);
?>
```

## Security Best Practices

1. **Always check permissions** before allowing actions
2. **Log all sensitive operations** for audit trail
3. **Use requirePermission()** for pages that need specific access
4. **Never expose user IDs** in URLs or forms without validation
5. **Validate all input** before processing
6. **Use transactions** for critical operations
7. **Log failures** as well as successes

## Testing RBAC

1. **Log in as different users** with different roles
2. **Try accessing restricted pages** to verify access control
3. **Check activity logs** to ensure actions are logged
4. **Verify permission checks** work correctly
5. **Test role assignments** through the manage_roles.php interface

## Troubleshooting

### Permission Check Returns False
- Verify the user has been assigned a role
- Check the role has the required permission
- Verify migrations 005 and 009 were applied correctly

### Activity Logs Not Appearing
- Check database connection
- Verify migration 006 was applied
- Check PHP error logs for any exceptions

### Settings Not Saving
- Check user has `manage-settings` permission
- Verify migration 007 was applied
- Check database write permissions

## Support

For issues or questions:
1. Check the RBAC_IMPLEMENTATION_GUIDE.md
2. Review database migration status
3. Check PHP error logs
4. Verify user permissions in manage_roles.php
