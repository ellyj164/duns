-- Migration: Add Viewer role and enhance permissions for granular RBAC
-- Created: 2025-11-23
-- Purpose: Add Viewer role (read-only) and additional granular permissions

-- Add new permissions for granular control
INSERT INTO `permissions` (`name`, `description`) VALUES
('create-quotation', 'Create new quotations'),
('edit-quotation', 'Edit existing quotations'),
('delete-quotation', 'Delete quotations'),
('view-quotation', 'View quotations'),
('delete-transaction', 'Delete transactions'),
('edit-transaction', 'Edit transactions'),
('create-transaction', 'Create new transactions'),
('export-data', 'Export data and reports'),
('view-dashboard', 'View dashboard and analytics');

-- Add Viewer role (strictly read-only)
INSERT INTO `roles` (`name`, `description`) VALUES
('Viewer', 'Read-only access to view data without modification capabilities');

-- Get the Viewer role ID (it should be 5 if no other roles have been added)
SET @viewer_role_id = (SELECT id FROM roles WHERE name = 'Viewer' LIMIT 1);

-- Assign only view permissions to Viewer role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @viewer_role_id, id FROM permissions WHERE name IN (
    'view-invoice',
    'view-client',
    'view-quotation',
    'view-transactions',
    'view-reports',
    'view-dashboard'
);

-- Update Super Admin to have all new permissions
SET @super_admin_role_id = (SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @super_admin_role_id, id FROM permissions WHERE name IN (
    'create-quotation',
    'edit-quotation',
    'delete-quotation',
    'view-quotation',
    'delete-transaction',
    'edit-transaction',
    'create-transaction',
    'export-data',
    'view-dashboard'
) AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @super_admin_role_id);

-- Update Admin role permissions to include new permissions (except delete-user and manage-roles)
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Admin' LIMIT 1);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @admin_role_id, id FROM permissions WHERE name IN (
    'create-quotation',
    'edit-quotation',
    'delete-quotation',
    'view-quotation',
    'delete-transaction',
    'edit-transaction',
    'create-transaction',
    'export-data',
    'view-dashboard'
) AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @admin_role_id);

-- Update Accountant role to include relevant permissions
SET @accountant_role_id = (SELECT id FROM roles WHERE name = 'Accountant' LIMIT 1);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @accountant_role_id, id FROM permissions WHERE name IN (
    'create-quotation',
    'edit-quotation',
    'view-quotation',
    'edit-transaction',
    'create-transaction',
    'export-data',
    'view-dashboard'
) AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @accountant_role_id);

-- Update Manager role to include view permissions only
SET @manager_role_id = (SELECT id FROM roles WHERE name = 'Manager' LIMIT 1);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @manager_role_id, id FROM permissions WHERE name IN (
    'view-quotation',
    'view-dashboard',
    'export-data'
) AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @manager_role_id);
