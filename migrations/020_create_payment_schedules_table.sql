-- Migration: Create payment_schedules table
-- Enables payment scheduling and partial payment tracking
-- NOTE: This migration requires that the invoices, receipts, clients, and users tables exist

CREATE TABLE IF NOT EXISTS `payment_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL COMMENT 'Related invoice',
  `client_id` int(11) DEFAULT NULL COMMENT 'Related client',
  `total_amount` decimal(15,2) NOT NULL COMMENT 'Total amount to be paid',
  `currency` varchar(10) NOT NULL DEFAULT 'RWF',
  `paid_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Amount paid so far',
  `remaining_amount` decimal(15,2) AS (total_amount - paid_amount) STORED,
  `schedule_type` enum('installment','milestone','custom') DEFAULT 'installment',
  `frequency` enum('weekly','bi-weekly','monthly','quarterly','custom') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `number_of_payments` int(11) DEFAULT NULL,
  `status` enum('active','completed','cancelled','overdue') DEFAULT 'active',
  `auto_generate_receipts` tinyint(1) DEFAULT 1,
  `send_reminders` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  CONSTRAINT `fk_schedule_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedule_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Payment schedules and installment plans';

-- Scheduled payment installments
CREATE TABLE IF NOT EXISTS `payment_installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `receipt_id` int(11) DEFAULT NULL COMMENT 'Generated receipt if paid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_schedule` (`schedule_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_installment_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `payment_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_installment_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add partial payment tracking to invoices (if not exists)
ALTER TABLE `invoices`
ADD COLUMN IF NOT EXISTS `payment_status` enum('unpaid','partially_paid','paid','overdue') DEFAULT 'unpaid' AFTER `status`,
ADD COLUMN IF NOT EXISTS `last_payment_date` date DEFAULT NULL AFTER `amount_paid`,
ADD COLUMN IF NOT EXISTS `payment_terms` varchar(255) DEFAULT NULL AFTER `notes`;
