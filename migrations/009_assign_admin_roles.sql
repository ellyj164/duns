-- Migration: 009_assign_admin_roles.sql
-- Purpose: Assign admin roles to specific users
-- Date: 2025-11-03

-- Assign Super Admin role to user 'ellican'
-- This gives unrestricted access to all features
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.username = 'ellican'
  AND r.name = 'Super Admin'
AS new_values
ON DUPLICATE KEY UPDATE `user_id` = new_values.`user_id`;

-- Assign Admin role to user with email 'niyogushimwaj967@gmail.com'
-- This gives full access with some restrictions
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'niyogushimwaj967@gmail.com'
  AND r.name = 'Admin'
AS new_values
ON DUPLICATE KEY UPDATE `user_id` = new_values.`user_id`;
