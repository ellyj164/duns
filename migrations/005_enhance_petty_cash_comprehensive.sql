-- =========================================================
-- Comprehensive Petty Cash Enhancement Migration
-- =========================================================
-- This migration adds all tables and columns needed for the
-- comprehensive petty cash management system
-- =========================================================

-- 1. Enhance existing petty_cash table
ALTER TABLE `petty_cash`
ADD COLUMN `category_id` INT(11) DEFAULT NULL COMMENT 'Expense category' AFTER `transaction_type`,
ADD COLUMN `beneficiary` VARCHAR(200) DEFAULT NULL COMMENT 'Person/entity receiving payment' AFTER `description`,
ADD COLUMN `purpose` VARCHAR(500) DEFAULT NULL COMMENT 'Detailed payment purpose' AFTER `beneficiary`,
ADD COLUMN `receipt_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to receipt file' AFTER `reference`,
ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' COMMENT 'Approval status' AFTER `receipt_path`,
ADD COLUMN `approved_by` INT(11) DEFAULT NULL COMMENT 'User who approved' AFTER `approval_status`,
ADD COLUMN `approved_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Approval timestamp' AFTER `approved_by`,
ADD COLUMN `is_locked` TINYINT(1) DEFAULT 0 COMMENT 'Lock after approval' AFTER `approved_at`,
ADD COLUMN `notes` TEXT DEFAULT NULL COMMENT 'Additional notes' AFTER `is_locked`,
ADD INDEX `idx_category_id` (`category_id`),
ADD INDEX `idx_approval_status` (`approval_status`),
ADD INDEX `idx_approved_by` (`approved_by`),
ADD INDEX `idx_is_locked` (`is_locked`);

-- 2. Create petty_cash_categories table
CREATE TABLE IF NOT EXISTS `petty_cash_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Category name',
  `description` VARCHAR(500) DEFAULT NULL COMMENT 'Category description',
  `max_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum amount per transaction',
  `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Icon name or emoji',
  `color` VARCHAR(20) DEFAULT NULL COMMENT 'Display color',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Active status',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Petty cash expense categories';

-- Insert default categories
INSERT INTO `petty_cash_categories` (`name`, `description`, `max_amount`, `icon`, `color`) VALUES
('Fuel', 'Fuel and transportation costs', 50000.00, '⛽', '#f59e0b'),
('Office Supplies', 'Stationery, paper, and office materials', 20000.00, '📎', '#3b82f6'),
('Transport', 'Public transport, taxi, and travel expenses', 30000.00, '🚗', '#10b981'),
('Maintenance', 'Repairs and maintenance costs', 100000.00, '🔧', '#ef4444'),
('Refreshments', 'Tea, coffee, snacks for meetings', 15000.00, '☕', '#8b5cf6'),
('Utilities', 'Water, electricity, internet top-ups', 25000.00, '💡', '#06b6d4'),
('Communication', 'Phone airtime, postage, courier', 10000.00, '📞', '#ec4899'),
('Cleaning', 'Cleaning supplies and services', 20000.00, '🧹', '#14b8a6'),
('Miscellaneous', 'Other small expenses', 50000.00, '📦', '#6b7280');

-- 3. Create petty_cash_float_settings table
CREATE TABLE IF NOT EXISTS `petty_cash_float_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `initial_float` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Starting balance',
  `max_limit` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum cash limit',
  `replenishment_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT 'Alert when below this amount',
  `approval_threshold` DECIMAL(10,2) DEFAULT 50000.00 COMMENT 'Amount requiring approval',
  `daily_limit` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum daily spending',
  `monthly_limit` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum monthly spending',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'Last user who updated settings',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Petty cash float configuration';

-- Insert default settings
INSERT INTO `petty_cash_float_settings` (`initial_float`, `max_limit`, `replenishment_threshold`, `approval_threshold`) 
VALUES (100000.00, 500000.00, 50000.00, 50000.00);

-- 4. Create petty_cash_reconciliation table
CREATE TABLE IF NOT EXISTS `petty_cash_reconciliation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` DATE NOT NULL COMMENT 'Date of reconciliation',
  `expected_balance` DECIMAL(10,2) NOT NULL COMMENT 'Calculated balance',
  `actual_balance` DECIMAL(10,2) NOT NULL COMMENT 'Physical count',
  `difference` DECIMAL(10,2) NOT NULL COMMENT 'Discrepancy amount',
  `explanation` TEXT DEFAULT NULL COMMENT 'Explanation for difference',
  `reconciled_by` INT(11) NOT NULL COMMENT 'User who performed reconciliation',
  `status` ENUM('pending', 'resolved', 'escalated') DEFAULT 'pending' COMMENT 'Reconciliation status',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reconciliation_date` (`reconciliation_date`),
  KEY `idx_reconciled_by` (`reconciled_by`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Petty cash reconciliation records';

-- 5. Create petty_cash_replenishment table
CREATE TABLE IF NOT EXISTS `petty_cash_replenishment` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_date` DATE NOT NULL COMMENT 'Date of request',
  `requested_amount` DECIMAL(10,2) NOT NULL COMMENT 'Amount requested',
  `current_balance` DECIMAL(10,2) NOT NULL COMMENT 'Balance at time of request',
  `justification` TEXT NOT NULL COMMENT 'Reason for replenishment',
  `requested_by` INT(11) NOT NULL COMMENT 'User requesting replenishment',
  `approved_by` INT(11) DEFAULT NULL COMMENT 'User who approved',
  `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending' COMMENT 'Request status',
  `approved_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Approval timestamp',
  `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Completion timestamp',
  `notes` TEXT DEFAULT NULL COMMENT 'Additional notes',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request_date` (`request_date`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Petty cash replenishment requests';

-- 6. Create petty_cash_roles table
CREATE TABLE IF NOT EXISTS `petty_cash_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'User ID',
  `role` ENUM('cashier', 'approver', 'viewer', 'admin') NOT NULL COMMENT 'Role type',
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'User who assigned role',
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`, `role`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role` (`role`),
  CONSTRAINT `petty_cash_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='User roles for petty cash management';

-- 7. Enhance activity_logs for petty cash audit trail
-- (activity_logs table already exists, just ensure it can handle petty cash events)

-- 8. Create petty_cash_edit_history table
CREATE TABLE IF NOT EXISTS `petty_cash_edit_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11) NOT NULL COMMENT 'Petty cash transaction ID',
  `edited_by` INT(11) NOT NULL COMMENT 'User who made edit',
  `field_name` VARCHAR(100) NOT NULL COMMENT 'Field that was edited',
  `old_value` TEXT DEFAULT NULL COMMENT 'Previous value',
  `new_value` TEXT DEFAULT NULL COMMENT 'New value',
  `edit_reason` VARCHAR(500) DEFAULT NULL COMMENT 'Reason for edit',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_edited_by` (`edited_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `petty_cash_edit_history_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `petty_cash` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Edit history for audit trail';

-- Add foreign key for category if not exists
ALTER TABLE `petty_cash`
ADD CONSTRAINT `petty_cash_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `petty_cash_categories` (`id`) ON DELETE SET NULL;

-- Add foreign key for approver if not exists  
ALTER TABLE `petty_cash`
ADD CONSTRAINT `petty_cash_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Create uploads directory structure indicator table
CREATE TABLE IF NOT EXISTS `petty_cash_receipts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11) NOT NULL COMMENT 'Petty cash transaction ID',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Original file name',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Stored file path',
  `file_size` INT(11) NOT NULL COMMENT 'File size in bytes',
  `file_type` VARCHAR(50) NOT NULL COMMENT 'MIME type',
  `ocr_text` TEXT DEFAULT NULL COMMENT 'OCR extracted text',
  `uploaded_by` INT(11) NOT NULL COMMENT 'User who uploaded',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `petty_cash_receipts_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `petty_cash` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Receipt attachments for petty cash transactions';

-- =========================================================
-- Migration Complete
-- =========================================================
