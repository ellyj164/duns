-- Migration: Create failed login attempts table
-- Date: 2025-11-23
-- Description: This migration creates a table to track failed login attempts for security monitoring

CREATE TABLE IF NOT EXISTS `failed_login_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username_or_email` VARCHAR(255) NOT NULL COMMENT 'The username or email that was attempted',
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP address of the attempt',
  `user_agent` TEXT DEFAULT NULL COMMENT 'Browser/device user agent string',
  `attempt_type` ENUM('password', 'otp') NOT NULL DEFAULT 'password' COMMENT 'Type of authentication that failed',
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the attempt occurred',
  `user_id` INT(11) DEFAULT NULL COMMENT 'User ID if the account exists',
  PRIMARY KEY (`id`),
  KEY `idx_username_or_email` (`username_or_email`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_failed_login_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks failed login attempts for security monitoring';
