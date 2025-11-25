-- =========================================================
-- Petty Cash Management Table
-- =========================================================
-- This table tracks petty cash transactions for small 
-- operational expenses and replenishments
-- =========================================================

CREATE TABLE IF NOT EXISTS `petty_cash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User who recorded the transaction',
  `transaction_date` date NOT NULL COMMENT 'Date of the transaction',
  `description` varchar(500) NOT NULL COMMENT 'Purpose/Description of the transaction',
  `amount` decimal(10,2) NOT NULL COMMENT 'Transaction amount',
  `transaction_type` enum('credit','debit') NOT NULL COMMENT 'credit = money added (income), debit = money spent (expense)',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Method used (CASH, BANK, etc.)',
  `reference` varchar(100) DEFAULT NULL COMMENT 'Optional reference number',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `petty_cash_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks petty cash transactions for operational expenses';

-- Create index for faster queries
CREATE INDEX `idx_amount` ON `petty_cash` (`amount`);
