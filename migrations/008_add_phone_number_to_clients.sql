-- Migration: 008_add_phone_number_to_clients.sql
-- Purpose: Add phone_number column to clients table
-- Date: 2025-11-03

-- Add phone_number column to clients table
-- This is needed for PDF generation to properly display client phone numbers
ALTER TABLE `clients` 
ADD COLUMN IF NOT EXISTS `phone_number` VARCHAR(255) NULL DEFAULT NULL
AFTER `client_name`;

-- Add index for better query performance
CREATE INDEX IF NOT EXISTS `idx_phone_number` ON `clients` (`phone_number`);
