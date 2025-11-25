# RBAC and Feature Implementation Guide

This document provides a comprehensive guide for implementing the new Role-Based Access Control (RBAC) system and additional features added to the DUNS financial management system.

## Overview of Changes

This update includes:

1. **Bug Fix**: PDF generation failure when client phone number is missing
2. **RBAC System**: Complete role-based access control with 4 default roles and 19 permissions
3. **Activity Logs**: Audit trail system to track all user actions
4. **Settings Management**: Centralized configuration storage
5. **Enhanced Client Data**: Added phone_number field to clients table

## Quick Start

### Prerequisites

- MySQL/MariaDB database access
- Backup of your current database
- PHP 7.4 or higher
- PDO extension enabled

### Installation Steps

1. **Backup Your Database**
   ```bash
   mysqldump -u your_username -p duns > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Apply Database Migrations**
   
   Navigate to the migrations directory and apply each migration in order:
   
   ```bash
   cd migrations
   
   # Apply all migrations
   mysql -u your_username -p duns < 005_create_rbac_tables.sql
   mysql -u your_username -p duns < 006_create_activity_logs_table.sql
   mysql -u your_username -p duns < 007_create_settings_table.sql
   mysql -u your_username -p duns < 008_add_phone_number_to_clients.sql
   mysql -u your_username -p duns < 009_assign_admin_roles.sql
   ```

3. **Verify Installation**
   
   Check that all tables were created successfully:
   
   ```sql
   SHOW TABLES LIKE 'roles';
   SHOW TABLES LIKE 'permissions';
   SHOW TABLES LIKE 'role_permissions';
   SHOW TABLES LIKE 'user_roles';
   SHOW TABLES LIKE 'activity_logs';
   SHOW TABLES LIKE 'settings';
   
   -- Verify columns
   DESCRIBE clients;
   ```

## Database Schema

### RBAC Tables

#### roles
Stores different user roles in the system.

| Column      | Type         | Description                    |
|-------------|--------------|--------------------------------|
| id          | INT          | Primary key                    |
| name        | VARCHAR(50)  | Unique role name               |
| description | TEXT         | Role description               |
| created_at  | TIMESTAMP    | Creation timestamp             |

**Default Roles:**
- Super Admin: Unrestricted access to all features
- Admin: Full access with some restrictions
- Accountant: Access to financial records and reporting
- Manager: Access to view and manage client records

#### permissions
Defines specific actions that can be performed.

| Column      | Type          | Description                    |
|-------------|---------------|--------------------------------|
| id          | INT           | Primary key                    |
| name        | VARCHAR(100)  | Unique permission name         |
| description | TEXT          | Permission description         |
| created_at  | TIMESTAMP     | Creation timestamp             |

**Default Permissions:**
- Invoice Management: create-invoice, edit-invoice, delete-invoice, view-invoice
- Client Management: create-client, edit-client, delete-client, view-client
- User Management: create-user, edit-user, delete-user, view-user
- System: view-reports, manage-settings, view-audit-logs, manage-roles
- Receipts: create-receipt, edit-receipt
- Transactions: view-transactions

#### role_permissions
Pivot table linking roles to their permissions.

| Column        | Type | Description                    |
|---------------|------|--------------------------------|
| role_id       | INT  | Foreign key to roles           |
| permission_id | INT  | Foreign key to permissions     |

#### user_roles
Pivot table assigning roles to users.

| Column      | Type      | Description                    |
|-------------|-----------|--------------------------------|
| user_id     | INT       | Foreign key to users           |
| role_id     | INT       | Foreign key to roles           |
| assigned_at | TIMESTAMP | Assignment timestamp           |

### Activity Logs Table

Tracks all user actions in the system for audit purposes.

| Column      | Type          | Description                       |
|-------------|---------------|-----------------------------------|
| id          | INT           | Primary key                       |
| user_id     | INT           | Foreign key to users (nullable)   |
| action      | VARCHAR(255)  | Action performed                  |
| target_type | VARCHAR(100)  | Type of resource affected         |
| target_id   | INT           | ID of affected resource           |
| details     | TEXT          | Additional details in JSON format |
| ip_address  | VARCHAR(45)   | IP address of the user            |
| created_at  | TIMESTAMP     | Action timestamp                  |

**Example Log Entries:**
```json
{
  "action": "create-invoice",
  "target_type": "clients",
  "target_id": 123,
  "details": "{\"invoice_number\": \"INV-001\", \"amount\": 1000}",
  "ip_address": "192.168.1.1"
}
```

### Settings Table

Stores system-wide configuration in key-value format.

| Column     | Type          | Description                    |
|------------|---------------|--------------------------------|
| key        | VARCHAR(100)  | Setting key (primary key)      |
| value      | TEXT          | Setting value                  |
| created_at | TIMESTAMP     | Creation timestamp             |
| updated_at | TIMESTAMP     | Last update timestamp          |

**Default Settings:**
- company_name: FEZA LOGISTICS LTD
- company_address: KN 5 Rd, KG 16 AVe 31, Kigali International Airport, Rwanda
- company_phone: (+250) 788 616 117
- company_email: info@fezalogistics.com
- company_website: www.fezalogistics.com
- company_tin: 121933433
- default_currency: RWF
- tax_rate: 18
- logo_url: https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png

### Enhanced Clients Table

Added `phone_number` column to store client contact information.

**New Column:**
- phone_number: VARCHAR(255), nullable

## Bug Fixes

### PDF Generation Fix

**Issue:** The system was throwing `Warning: Undefined array key "phone_number"` when generating PDFs for clients without a phone number.

**Solution:** Updated line 196 in `print_document.php` to use the null coalescing operator:

```php
// Before
$pdf->Cell(95, 5, 'Phone: ' . $client['phone_number'], 0, 1);

// After
$pdf->Cell(95, 5, 'Phone: ' . ($client['phone_number'] ?? 'N/A'), 0, 1);
```

This ensures that:
- If a phone number exists, it's displayed
- If no phone number exists, "N/A" is shown instead
- The PDF generation continues without errors

## Usage Examples

### Checking User Permissions

```php
<?php
require 'db.php';

function userHasPermission($userId, $permissionName) {
    global $pdo;
    
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
    
    $result = $stmt->fetch();
    return $result['has_permission'] > 0;
}

// Usage
if (userHasPermission($_SESSION['user_id'], 'create-invoice')) {
    // Allow invoice creation
} else {
    // Deny access
    echo "You don't have permission to create invoices.";
}
?>
```

### Logging User Actions

```php
<?php
require 'db.php';

function logActivity($userId, $action, $targetType = null, $targetId = null, $details = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs 
        (user_id, action, target_type, target_id, details, ip_address)
        VALUES (:user_id, :action, :target_type, :target_id, :details, :ip_address)
    ");
    
    $stmt->execute([
        'user_id' => $userId,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'details' => $details ? json_encode($details) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

// Usage
logActivity(
    $_SESSION['user_id'],
    'create-invoice',
    'clients',
    $clientId,
    ['invoice_number' => $invoiceNumber, 'amount' => $amount]
);
?>
```

### Retrieving Settings

```php
<?php
require 'db.php';

function getSetting($key, $default = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = :key");
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch();
    
    return $result ? $result['value'] : $default;
}

function setSetting($key, $value) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO settings (`key`, `value`) 
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE `value` = :value
    ");
    
    $stmt->execute(['key' => $key, 'value' => $value]);
}

// Usage
$companyName = getSetting('company_name', 'Default Company');
$taxRate = getSetting('tax_rate', '18');

// Update setting
setSetting('tax_rate', '20');
?>
```

## Admin User Configuration

### Default Administrators

The system has been configured with two administrative users:

1. **Super Admin**
   - Username: `ellican`
   - Access: Unrestricted access to all features and settings
   - Can manage roles and permissions

2. **Admin**
   - Email: `niyogushimwaj967@gmail.com`
   - Access: Full access to application features with some restrictions
   - Cannot manage roles and permissions

### Adding More Admin Users

To assign roles to additional users:

```sql
-- Find user ID
SELECT id, username, email FROM users WHERE email = 'user@example.com';

-- Assign role
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'user@example.com'
  AND r.name = 'Accountant';
```

## Security Considerations

1. **Password Protection**: All admin functions should require password re-verification
2. **Session Management**: Implement session timeout for inactive users
3. **Audit Trail**: All sensitive actions are logged in the activity_logs table
4. **Input Validation**: Always validate and sanitize user inputs
5. **SQL Injection**: Use prepared statements (already implemented with PDO)
6. **XSS Protection**: Escape output when displaying user-generated content

## Maintenance

### Regular Tasks

1. **Clean Old Logs**: Periodically archive or delete old activity logs
   ```sql
   DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

2. **Review Permissions**: Regularly audit user roles and permissions
   ```sql
   SELECT u.username, u.email, r.name as role
   FROM users u
   INNER JOIN user_roles ur ON u.id = ur.user_id
   INNER JOIN roles r ON ur.role_id = r.id;
   ```

3. **Backup**: Regular database backups are crucial
   ```bash
   mysqldump -u username -p duns > backup_$(date +%Y%m%d).sql
   ```

## Troubleshooting

### Issue: Migrations fail to apply

**Solution**: 
- Check that you're running migrations in order
- Verify that the users table exists before running migration 005
- Check database user permissions

### Issue: PDF still shows errors

**Solution**:
- Clear PHP opcache if enabled
- Verify that the print_document.php file was updated correctly
- Check that the phone_number column exists in the clients table

### Issue: Users can't see their roles

**Solution**:
- Verify migrations 005 and 009 were applied successfully
- Check that the username/email matches exactly in the database
- Run this query to verify role assignments:
  ```sql
  SELECT u.username, r.name 
  FROM users u
  LEFT JOIN user_roles ur ON u.id = ur.user_id
  LEFT JOIN roles r ON ur.role_id = r.id;
  ```

## Future Enhancements

Potential improvements for the RBAC system:

1. **UI for Role Management**: Admin interface to manage roles and permissions
2. **Role Hierarchy**: Parent-child relationships between roles
3. **Temporary Access**: Time-limited permission assignments
4. **Multi-tenancy**: Support for multiple organizations
5. **API Access Control**: Extend RBAC to API endpoints
6. **Advanced Audit**: More detailed logging with before/after values
7. **Settings UI**: Admin panel for managing system settings

## Support

For questions or issues:
1. Check the migrations/README.md file
2. Review this implementation guide
3. Contact the development team

## Change Log

**Version 1.0 - 2025-11-03**
- Fixed PDF generation bug (phone_number undefined array key)
- Implemented complete RBAC system with 4 roles and 19 permissions
- Added activity logging system for audit trail
- Created settings table for centralized configuration
- Added phone_number column to clients table
- Assigned admin roles to ellican and niyogushimwaj967@gmail.com
