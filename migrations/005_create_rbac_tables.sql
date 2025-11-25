-- Migration: 005_create_rbac_tables.sql
-- Purpose: Create Role-Based Access Control (RBAC) system tables
-- Date: 2025-11-03

-- Create roles table to store different user roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create permissions table to define specific actions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_permission_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create role_permissions pivot table to link roles to permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
  INDEX `idx_role_id` (`role_id`),
  INDEX `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create user_roles pivot table to assign roles to users
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `description`) VALUES
  ('Super Admin', 'Unrestricted access to all features and settings'),
  ('Admin', 'Full access to application features with some restrictions'),
  ('Accountant', 'Access to financial records and reporting'),
  ('Manager', 'Access to view and manage client records')
AS new_values
ON DUPLICATE KEY UPDATE `description` = new_values.`description`;

-- Insert default permissions
INSERT INTO `permissions` (`name`, `description`) VALUES
  ('create-invoice', 'Create new invoices'),
  ('edit-invoice', 'Edit existing invoices'),
  ('delete-invoice', 'Delete invoices'),
  ('view-invoice', 'View invoices'),
  ('create-client', 'Create new client records'),
  ('edit-client', 'Edit existing client records'),
  ('delete-client', 'Delete client records'),
  ('view-client', 'View client records'),
  ('create-user', 'Create new user accounts'),
  ('edit-user', 'Edit user accounts'),
  ('delete-user', 'Delete user accounts'),
  ('view-user', 'View user accounts'),
  ('view-reports', 'View financial reports and analytics'),
  ('manage-settings', 'Manage application settings'),
  ('view-audit-logs', 'View audit trail and activity logs'),
  ('manage-roles', 'Manage roles and permissions'),
  ('create-receipt', 'Create payment receipts'),
  ('edit-receipt', 'Edit payment receipts'),
  ('view-transactions', 'View transaction history')
AS new_values
ON DUPLICATE KEY UPDATE `description` = new_values.`description`;

-- Assign all permissions to Super Admin role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Super Admin'
AS new_values
ON DUPLICATE KEY UPDATE `role_id` = new_values.`role_id`;

-- Assign most permissions to Admin role (excluding manage-roles)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Admin'
  AND p.name IN ('create-invoice', 'edit-invoice', 'delete-invoice', 'view-invoice',
                 'create-client', 'edit-client', 'delete-client', 'view-client',
                 'create-user', 'edit-user', 'view-user',
                 'view-reports', 'manage-settings', 'view-audit-logs',
                 'create-receipt', 'edit-receipt', 'view-transactions')
AS new_values
ON DUPLICATE KEY UPDATE `role_id` = new_values.`role_id`;

-- Assign financial permissions to Accountant role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Accountant'
  AND p.name IN ('create-invoice', 'edit-invoice', 'view-invoice',
                 'view-client', 'view-reports', 'create-receipt', 'view-transactions')
AS new_values
ON DUPLICATE KEY UPDATE `role_id` = new_values.`role_id`;

-- Assign view and basic edit permissions to Manager role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Manager'
  AND p.name IN ('view-invoice', 'view-client', 'edit-client', 'view-reports', 'view-transactions')
AS new_values
ON DUPLICATE KEY UPDATE `role_id` = new_values.`role_id`;
