# Database Migrations

This folder contains SQL migration files to update the database schema.

## How to Apply Migrations

Execute the SQL files in numerical order using your MySQL/MariaDB client:

### Using Command Line
```bash
# Apply migration 001
mysql -u your_username -p your_database_name < 001_rename_phone_to_responsible.sql

# Apply migration 002
mysql -u your_username -p your_database_name < 002_add_tin_column.sql

# Apply migration 003
mysql -u your_username -p your_database_name < 003_create_login_attempts_table.sql

# Apply migration 004
mysql -u your_username -p your_database_name < 004_create_ai_chat_logs_table.sql

# Apply migration 005 - RBAC System
mysql -u your_username -p your_database_name < 005_create_rbac_tables.sql

# Apply migration 006 - Activity Logs
mysql -u your_username -p your_database_name < 006_create_activity_logs_table.sql

# Apply migration 007 - Settings Table
mysql -u your_username -p your_database_name < 007_create_settings_table.sql

# Apply migration 008 - Phone Number Column
mysql -u your_username -p your_database_name < 008_add_phone_number_to_clients.sql

# Apply migration 009 - Admin Role Assignments
mysql -u your_username -p your_database_name < 009_assign_admin_roles.sql

# Apply migration 010 - Additional Roles (NEW)
mysql -u your_username -p your_database_name < 010_add_additional_roles.sql

# Apply migration 011 - Populate User Roles (NEW)
mysql -u your_username -p your_database_name < 011_populate_user_roles.sql

# Apply migration 012 - Default Role Column (OPTIONAL)
mysql -u your_username -p your_database_name < 012_add_default_role_to_users.sql

# Apply migration 013 - Upgrade Admin to Super Admin (IMPORTANT)
mysql -u your_username -p your_database_name < 013_upgrade_admin_to_superadmin.sql

# Apply migration 014 - Account Lockout Fields
mysql -u your_username -p your_database_name < 014_add_account_lockout_fields.sql

# Apply migration 015 - Failed Login Attempts Table
mysql -u your_username -p your_database_name < 015_create_failed_login_attempts_table.sql

# Apply migration 016 - Currency Support for Petty Cash
mysql -u your_username -p your_database_name < 016_add_currency_to_petty_cash.sql

# Apply migration 017 - Document Verification System (NEW)
mysql -u your_username -p your_database_name < 017_create_document_verifications_table.sql

# Apply migration 018 - Email Logging System (NEW)
mysql -u your_username -p your_database_name < 018_create_email_logs_table.sql

# Apply migration 019 - Document Templates (NEW)
mysql -u your_username -p your_database_name < 019_add_document_template_settings.sql

# Apply migration 020 - Payment Schedules (NEW)
mysql -u your_username -p your_database_name < 020_create_payment_schedules_table.sql

# Apply migration 021 - Notifications & Alerts (NEW)
mysql -u your_username -p your_database_name < 021_create_notifications_system.sql

# Apply migration 022 - File Attachments (NEW)
mysql -u your_username -p your_database_name < 022_add_receipt_attachments.sql
```

### Using Migration Runner Script (RECOMMENDED)
```bash
# Run all pending migrations automatically
php run_migrations.php
```

This script will:
- Track applied migrations
- Run only pending migrations
- Handle errors gracefully
- Provide detailed output
```

### Using phpMyAdmin or Similar Tools
1. Log in to your database management tool
2. Select the `duns` database
3. Go to the SQL tab
4. Copy and paste the contents of each migration file
5. Execute them in order (001, 002, 003)

## Migration Details

### 001_rename_phone_to_responsible.sql
- **Purpose**: Renames the `phone_number` column to `Responsible` in the `clients` table
- **Impact**: All references to `phone_number` should now use `Responsible`

### 002_add_tin_column.sql
- **Purpose**: Adds a `TIN` (Tax Identification Number) column to the `clients` table
- **Type**: VARCHAR(9) - accepts up to 9 numeric digits
- **Index**: Creates an index on the TIN column for faster lookups

### 003_create_login_attempts_table.sql
- **Purpose**: Creates a new table to track login attempts for security monitoring
- **Features**: Stores device, IP address, location, and country code for each login
- **Foreign Key**: Links to the `users` table via `user_id`

### 004_create_ai_chat_logs_table.sql
- **Purpose**: Creates a new table to log all AI assistant interactions for security and auditing
- **Features**: Tracks user queries, AI responses, SQL queries executed, and execution metrics
- **Foreign Key**: Links to the `users` table via `user_id`
- **Indexes**: Optimized for session-based queries and user history lookups

### 005_create_rbac_tables.sql
- **Purpose**: Implements a complete Role-Based Access Control (RBAC) system
- **Tables Created**: `roles`, `permissions`, `role_permissions`, `user_roles`
- **Default Roles**: Super Admin, Admin, Accountant, Manager
- **Default Permissions**: 19 different permissions for granular access control
- **Foreign Keys**: Links to the `users` table via `user_id`

### 006_create_activity_logs_table.sql
- **Purpose**: Creates an audit trail table to track all user actions in the system
- **Features**: Tracks action type, target resource, details, IP address, and timestamp
- **Foreign Key**: Links to the `users` table via `user_id` (with SET NULL on delete)
- **Indexes**: Optimized for querying by user, action, target, and date

### 007_create_settings_table.sql
- **Purpose**: Creates a settings table for system-wide configuration
- **Features**: Key-value storage for company information, logo, currency, and tax rates
- **Default Settings**: Pre-populated with FEZA LOGISTICS company information
- **Type**: Simple key-value store with automatic timestamp tracking

### 008_add_phone_number_to_clients.sql
- **Purpose**: Adds a `phone_number` column to the `clients` table
- **Type**: VARCHAR(255), nullable
- **Impact**: Enables PDF generation to display client phone numbers without errors
- **Index**: Creates an index for better query performance

### 009_assign_admin_roles.sql
- **Purpose**: Assigns admin roles to specific users
- **Users**: Assigns Super Admin role to 'ellican' and Admin role to 'niyogushimwaj967@gmail.com'
- **Impact**: Enables role-based access control for specified administrators

## Important Notes

⚠️ **Before Running Migrations**:
1. **Backup your database** - Always create a backup before running migrations
2. **Test on development** - Test migrations on a development/staging environment first
3. **Check dependencies** - Ensure the `users` table exists before running migration 003
4. **Application updates** - The application code has been updated to work with these schema changes

## Rollback Instructions

If you need to rollback the changes:

### Rollback 001
```sql
ALTER TABLE `clients` CHANGE COLUMN `Responsible` `phone_number` VARCHAR(20) DEFAULT NULL;
```

### Rollback 002
```sql
ALTER TABLE `clients` DROP INDEX `idx_tin`;
ALTER TABLE `clients` DROP COLUMN `TIN`;
```

### Rollback 003
```sql
DROP TABLE IF EXISTS `login_attempts`;
```

### Rollback 004
```sql
DROP TABLE IF EXISTS `ai_chat_logs`;
```

### Rollback 005
```sql
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
```

### Rollback 006
```sql
DROP TABLE IF EXISTS `activity_logs`;
```

### Rollback 007
```sql
DROP TABLE IF EXISTS `settings`;
```

### Rollback 008
```sql
ALTER TABLE `clients` DROP INDEX IF EXISTS `idx_phone_number`;
ALTER TABLE `clients` DROP COLUMN IF EXISTS `phone_number`;
```

### Rollback 009
```sql
-- Remove role assignments
DELETE ur FROM `user_roles` ur
INNER JOIN `users` u ON ur.user_id = u.id
WHERE u.username = 'ellican' OR u.email = 'niyogushimwaj967@gmail.com';
```

## Support

For issues or questions, contact the development team.

### 010_add_additional_roles.sql
- **Purpose**: Adds three new user roles: Cashier, Approver, and Viewer
- **Impact**: Expands role-based access control with more granular permissions
- **New Roles**:
  - **Cashier**: Access to cash management and payment processing (view-invoice, view-client, create-receipt, view-transactions)
  - **Approver**: Access to approve and review transactions (view-invoice, edit-invoice, view-client, view-reports, view-transactions, view-audit-logs)
  - **Viewer**: Read-only access to view reports and data (view-invoice, view-client, view-reports, view-transactions)
- **Safe to Run**: Uses `ON DUPLICATE KEY UPDATE` so it's idempotent

### 011_populate_user_roles.sql
- **Purpose**: Populates the user_roles table with default role assignments for existing users
- **Impact**: Ensures all existing users have appropriate roles assigned
- **Assignments**:
  - niyogushimwaj967@gmail.com → Admin role
  - uzacartine@gmail.com → Accountant role
  - ellyj164@gmail.com → Manager role
  - ellican (if exists) → Super Admin role
- **Safe to Run**: Uses `ON DUPLICATE KEY UPDATE` to avoid duplicate assignments
- **Important**: This migration ensures the user_roles table is not empty

### 012_add_default_role_to_users.sql (OPTIONAL)
- **Purpose**: Adds a default_role_id column to the users table for convenience
- **Impact**: Provides a quick reference for a user's primary role
- **Note**: This is optional - the user_roles pivot table is the primary mechanism for role management
- **Features**:
  - Adds `default_role_id` column with foreign key to roles table
  - Updates existing users with their first assigned role as default
  - Useful for user registration forms to pre-select a default role

### 013_upgrade_admin_to_superadmin.sql (IMPORTANT)
- **Purpose**: Upgrades niyogushimwaj967@gmail.com from Admin to Super Admin role
- **Impact**: Grants unrestricted access to all features including settings and role management
- **Why Needed**: Admin role excludes `manage-roles` permission, preventing access to certain admin features
- **Changes**:
  - Removes existing Admin role assignment
  - Assigns Super Admin role with full permissions
  - Ensures immediate access to all admin features upon login
- **Safe to Run**: Uses ON DUPLICATE KEY UPDATE and idempotent DELETE

## Rollback Instructions

### Rollback 010
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

### Rollback 011
```sql
-- Remove role assignments added by this migration
-- Keep Super Admin and original Admin assignment, remove others
DELETE ur FROM `user_roles` ur
INNER JOIN `users` u ON ur.user_id = u.id
WHERE u.email IN ('uzacartine@gmail.com', 'ellyj164@gmail.com');
```

### Rollback 012
```sql
-- Remove default_role_id column
ALTER TABLE `users` DROP FOREIGN KEY `fk_users_default_role`;
ALTER TABLE `users` DROP INDEX `idx_default_role_id`;
ALTER TABLE `users` DROP COLUMN `default_role_id`;
```

### Rollback 013
```sql
-- Revert Super Admin back to Admin role
DELETE FROM `user_roles`
WHERE user_id = (SELECT id FROM `users` WHERE email = 'niyogushimwaj967@gmail.com')
  AND role_id = (SELECT id FROM `roles` WHERE name = 'Super Admin');

-- Re-assign Admin role
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'niyogushimwaj967@gmail.com'
  AND r.name = 'Admin';
```

## Testing the Changes

After applying migrations 010 and 011, verify the changes:

```sql
-- Check that new roles were created
SELECT * FROM `roles`;

-- Verify user role assignments
SELECT u.id, u.username, u.email, r.name as role_name, ur.assigned_at
FROM `users` u
LEFT JOIN `user_roles` ur ON u.id = ur.user_id
LEFT JOIN `roles` r ON ur.role_id = r.id
ORDER BY u.id, r.name;

-- Check permissions for new roles
SELECT r.name as role, p.name as permission
FROM `roles` r
INNER JOIN `role_permissions` rp ON r.id = rp.role_id
INNER JOIN `permissions` p ON rp.permission_id = p.id
WHERE r.name IN ('Cashier', 'Approver', 'Viewer')
ORDER BY r.name, p.name;

-- Verify the user_roles table is no longer empty
SELECT COUNT(*) as total_assignments FROM `user_roles`;
```

## Summary of User Role Management Fix

**Problem**: 
- The `user_roles` table was nearly empty (only 1 entry)
- Only 4 basic roles existed (Super Admin, Admin, Accountant, Manager)
- No default role assignments for most users

**Solution**:
1. **Migration 010**: Added 3 new roles (Cashier, Approver, Viewer) with appropriate permissions
2. **Migration 011**: Populated user_roles table with assignments for all existing users
3. **Migration 012** (Optional): Added default_role_id column to users table for convenience

**Result**:
- 7 total roles now available (Super Admin, Admin, Accountant, Manager, Cashier, Approver, Viewer)
- All existing users have role assignments in the user_roles table
- The RBAC system is fully functional with proper role-permission mappings
- Optional default_role_id column provides quick reference for primary role
