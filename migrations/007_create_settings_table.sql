-- Migration: 007_create_settings_table.sql
-- Purpose: Create settings table for system-wide configuration
-- Date: 2025-11-03

-- Create settings table to store system-wide configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
  ('company_name', 'FEZA LOGISTICS LTD'),
  ('company_address', 'KN 5 Rd, KG 16 AVe 31, Kigali International Airport, Rwanda'),
  ('company_phone', '(+250) 788 616 117'),
  ('company_email', 'info@fezalogistics.com'),
  ('company_website', 'www.fezalogistics.com'),
  ('company_tin', '121933433'),
  ('default_currency', 'RWF'),
  ('tax_rate', '18'),
  ('logo_url', 'https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png')
AS new_values
ON DUPLICATE KEY UPDATE `value` = new_values.`value`;
