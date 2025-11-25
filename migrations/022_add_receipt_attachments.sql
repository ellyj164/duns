-- Migration: Add receipt/document attachments support
-- Enables file uploads for receipts, invoices, and petty cash transactions
-- NOTE: This migration requires that the users and petty_cash tables exist

CREATE TABLE IF NOT EXISTS `document_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL COMMENT 'Type: petty_cash, invoice, receipt, client, etc.',
  `entity_id` int(11) NOT NULL COMMENT 'ID of the related entity',
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL COMMENT 'Size in bytes',
  `mime_type` varchar(100) NOT NULL,
  `file_type` varchar(50) NOT NULL COMMENT 'Category: receipt, invoice_copy, contract, id_document, etc.',
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Whether file is publicly accessible',
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_file_type` (`file_type`),
  CONSTRAINT `fk_attachment_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File attachments for various entities';

-- Add attachment support to petty_cash table (if column doesn't exist)
ALTER TABLE `petty_cash`
ADD COLUMN IF NOT EXISTS `has_attachments` tinyint(1) DEFAULT 0 AFTER `notes`,
ADD COLUMN IF NOT EXISTS `attachment_count` int(11) DEFAULT 0 AFTER `has_attachments`;

-- Create uploads directory structure hint
-- Actual directory creation should be done via PHP or shell script
-- mkdir -p uploads/receipts uploads/invoices uploads/petty_cash uploads/documents
