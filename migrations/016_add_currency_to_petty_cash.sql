-- Migration: Add currency support to petty cash
-- Date: 2025-11-23
-- Description: Adds currency fields to petty cash tables for multi-currency support

-- Add currency to petty_cash table
ALTER TABLE `petty_cash`
ADD COLUMN `currency` VARCHAR(3) DEFAULT 'RWF' COMMENT 'Currency code (USD, EUR, RWF, etc.)' AFTER `amount`,
ADD INDEX `idx_currency` (`currency`);

-- Update existing records to have RWF as default currency
UPDATE `petty_cash` SET `currency` = 'RWF' WHERE `currency` IS NULL;

-- Add currency to petty_cash_float_settings
ALTER TABLE `petty_cash_float_settings`
ADD COLUMN `currency` VARCHAR(3) DEFAULT 'RWF' COMMENT 'Base currency for float' AFTER `initial_float`;

-- Update existing settings to have RWF as default currency
UPDATE `petty_cash_float_settings` SET `currency` = 'RWF' WHERE `currency` IS NULL;

-- Add currency to petty_cash_reconciliation
ALTER TABLE `petty_cash_reconciliation`
ADD COLUMN `currency` VARCHAR(3) DEFAULT 'RWF' COMMENT 'Currency for reconciliation' AFTER `reconciliation_date`;

-- Add currency to petty_cash_replenishment
ALTER TABLE `petty_cash_replenishment`
ADD COLUMN `currency` VARCHAR(3) DEFAULT 'RWF' COMMENT 'Currency for replenishment' AFTER `requested_amount`;

-- Create currency exchange rates table for petty cash
CREATE TABLE IF NOT EXISTS `petty_cash_exchange_rates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `from_currency` VARCHAR(3) NOT NULL COMMENT 'Source currency',
  `to_currency` VARCHAR(3) NOT NULL COMMENT 'Target currency',
  `rate` DECIMAL(10,6) NOT NULL COMMENT 'Exchange rate',
  `effective_date` DATE NOT NULL COMMENT 'Date when rate is effective',
  `updated_by` INT(11) NOT NULL COMMENT 'User who set the rate',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_currency_pair_date` (`from_currency`, `to_currency`, `effective_date`),
  KEY `idx_from_currency` (`from_currency`),
  KEY `idx_to_currency` (`to_currency`),
  KEY `idx_effective_date` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Exchange rates for petty cash multi-currency transactions';

-- Insert default exchange rates (PLACEHOLDER VALUES - MUST BE UPDATED REGULARLY)
-- IMPORTANT: These rates are approximate and will become outdated quickly
-- Implement an automatic exchange rate update system or update these values regularly
-- Consider using an API like exchangerate-api.com or currencylayer.com for automatic updates
INSERT INTO `petty_cash_exchange_rates` (`from_currency`, `to_currency`, `rate`, `effective_date`, `updated_by`) VALUES
('USD', 'RWF', 1300.00, CURDATE(), 1),  -- Update from National Bank of Rwanda
('EUR', 'RWF', 1400.00, CURDATE(), 1),  -- Update from National Bank of Rwanda
('RWF', 'USD', 0.00077, CURDATE(), 1),  -- Update from National Bank of Rwanda
('RWF', 'EUR', 0.00071, CURDATE(), 1),  -- Update from National Bank of Rwanda
('USD', 'EUR', 0.92, CURDATE(), 1),      -- Update from ECB or other reliable source
('EUR', 'USD', 1.09, CURDATE(), 1);      -- Update from ECB or other reliable source
