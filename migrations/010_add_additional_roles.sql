-- Migration: 010_add_additional_roles.sql
-- Purpose: Add additional user roles (Cashier, Approver, Viewer) to the roles table
-- Date: 2025-11-23

-- Insert additional roles to support more granular access control
INSERT INTO `roles` (`name`, `description`) VALUES
  ('Cashier', 'Access to cash management and payment processing'),
  ('Approver', 'Access to approve and review transactions and documents'),
  ('Viewer', 'Read-only access to view reports and data')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Insert permissions for Cashier role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Cashier'
  AND p.name IN ('view-invoice', 'view-client', 'create-receipt', 'view-transactions')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Insert permissions for Approver role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Approver'
  AND p.name IN ('view-invoice', 'edit-invoice', 'view-client', 'view-reports', 
                 'view-transactions', 'view-audit-logs')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Insert permissions for Viewer role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Viewer'
  AND p.name IN ('view-invoice', 'view-client', 'view-reports', 'view-transactions')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;
