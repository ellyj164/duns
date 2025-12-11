-- Migration: Create User Preferences Table
-- This table stores user-specific preferences like theme, language, dashboard layout

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `preference_key` VARCHAR(100) NOT NULL,
  `preference_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_preference` (`user_id`, `preference_key`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default preferences for existing users
INSERT INTO `user_preferences` (`user_id`, `preference_key`, `preference_value`)
SELECT `id`, 'theme', 'light'
FROM `users`
WHERE `id` NOT IN (SELECT `user_id` FROM `user_preferences` WHERE `preference_key` = 'theme');

INSERT INTO `user_preferences` (`user_id`, `preference_key`, `preference_value`)
SELECT `id`, 'language', 'en'
FROM `users`
WHERE `id` NOT IN (SELECT `user_id` FROM `user_preferences` WHERE `preference_key` = 'language');
