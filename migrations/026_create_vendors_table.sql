-- Migration: Create Vendors Table
-- This table stores vendor/supplier information

CREATE TABLE IF NOT EXISTS `vendors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vendor_name` VARCHAR(255) NOT NULL,
  `contact_person` VARCHAR(255),
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `address` TEXT,
  `tin` VARCHAR(50),
  `bank_account` VARCHAR(100),
  `bank_name` VARCHAR(100),
  `payment_terms` VARCHAR(50) DEFAULT 'Net 30',
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `notes` TEXT,
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_name` (`vendor_name`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_vendors_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
