-- Migration: 012_add_default_role_to_users.sql
-- Purpose: Add default_role_id column to users table for new user registration
-- Date: 2025-11-23
-- Note: This is optional - the user_roles pivot table is the primary mechanism for role assignment
--       This column is just for convenience when creating new users

-- Add default_role_id column to users table
ALTER TABLE `users` 
ADD COLUMN `default_role_id` INT NULL AFTER `password_hash`,
ADD CONSTRAINT `fk_users_default_role` 
  FOREIGN KEY (`default_role_id`) REFERENCES `roles`(`id`) 
  ON DELETE SET NULL;

-- Create an index for performance
ALTER TABLE `users` 
ADD INDEX `idx_default_role_id` (`default_role_id`);

-- Update existing users to have a default role based on their current role in user_roles
-- This sets the default_role_id to match their first assigned role (if any)
UPDATE `users` u
INNER JOIN `user_roles` ur ON u.id = ur.user_id
SET u.default_role_id = ur.role_id
WHERE u.default_role_id IS NULL
ORDER BY ur.assigned_at ASC;
