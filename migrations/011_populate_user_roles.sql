-- Migration: 011_populate_user_roles.sql
-- Purpose: Populate user_roles table with default role assignments for existing users
-- Date: 2025-11-23

-- Assign Super Admin role to user with email 'niyogushimwaj967@gmail.com'
-- This user requires unrestricted access to all features including settings and role management
-- Upgrades from Admin role (migration 009) to Super Admin for full permissions
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'niyogushimwaj967@gmail.com'
  AND r.name = 'Super Admin'
ON DUPLICATE KEY UPDATE `assigned_at` = `assigned_at`;

-- Assign Accountant role to user 'uzacartine@gmail.com' 
-- This appears to be a key user based on activity logs
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'uzacartine@gmail.com'
  AND r.name = 'Accountant'
ON DUPLICATE KEY UPDATE `assigned_at` = `assigned_at`;

-- Assign Manager role to user 'ellyj164@gmail.com'
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'ellyj164@gmail.com'
  AND r.name = 'Manager'
ON DUPLICATE KEY UPDATE `assigned_at` = `assigned_at`;

-- Note: The username 'ellican' mentioned in migration 009 should be assigned Super Admin role
-- if that user exists in the system
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id AS user_id, r.id AS role_id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.username = 'ellican'
  AND r.name = 'Super Admin'
ON DUPLICATE KEY UPDATE `assigned_at` = `assigned_at`;
