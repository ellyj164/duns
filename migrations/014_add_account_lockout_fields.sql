-- Migration: Add account lockout fields to users table
-- Date: 2025-11-23
-- Description: Adds fields to track failed login attempts and account lockout status

ALTER TABLE `users` 
ADD COLUMN `failed_login_attempts` INT DEFAULT 0 COMMENT 'Number of consecutive failed login attempts',
ADD COLUMN `last_failed_attempt_at` DATETIME DEFAULT NULL COMMENT 'Timestamp of the last failed login attempt',
ADD COLUMN `locked_until` DATETIME DEFAULT NULL COMMENT 'Timestamp until which the account is locked',
ADD COLUMN `locked_by_admin` TINYINT(1) DEFAULT 0 COMMENT 'Flag indicating if account was locked by admin';

-- Create index for faster queries on lockout status
CREATE INDEX `idx_locked_until` ON `users` (`locked_until`);
CREATE INDEX `idx_failed_login_attempts` ON `users` (`failed_login_attempts`);
