-- Migration: 013_upgrade_admin_to_superadmin.sql
-- Purpose: Upgrade niyogushimwaj967@gmail.com from Admin to Super Admin role
-- Date: 2025-11-23
-- Note: This migration ensures the user has Super Admin access (unrestricted permissions)
--       including access to settings, role management, and all admin features

-- First, remove any existing Admin role assignment for this user
-- This ensures a clean upgrade to Super Admin
DELETE FROM `user_roles`
WHERE user_id = (SELECT id FROM `users` WHERE email = 'niyogushimwaj967@gmail.com')
  AND role_id = (SELECT id FROM `roles` WHERE name = 'Admin');

-- Assign Super Admin role to user with email 'niyogushimwaj967@gmail.com'
-- Super Admin has unrestricted access to all features and settings
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'niyogushimwaj967@gmail.com'
  AND r.name = 'Super Admin'
ON DUPLICATE KEY UPDATE `assigned_at` = `assigned_at`;
