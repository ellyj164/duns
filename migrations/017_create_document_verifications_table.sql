-- Migration: Create document_verifications table
-- This table tracks all generated documents for verification purposes

CREATE TABLE IF NOT EXISTS `document_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(50) NOT NULL COMMENT 'Type: invoice, receipt, quotation, purchase_order, delivery_note, statement, report',
  `doc_id` int(11) NOT NULL COMMENT 'ID in the source table',
  `doc_number` varchar(100) NOT NULL COMMENT 'Document reference number',
  `barcode_id` varchar(100) NOT NULL COMMENT 'Unique barcode identifier',
  `verification_hash` varchar(255) NOT NULL COMMENT 'Secure verification hash',
  `doc_amount` decimal(15,2) DEFAULT NULL COMMENT 'Document total amount',
  `doc_currency` varchar(10) DEFAULT NULL COMMENT 'Currency code',
  `issue_date` datetime NOT NULL COMMENT 'When the document was issued',
  `issuer_user_id` int(11) NOT NULL COMMENT 'User who generated the document',
  `status` enum('active','cancelled','void','revised') DEFAULT 'active' COMMENT 'Current document status',
  `client_id` int(11) DEFAULT NULL COMMENT 'Related client/customer ID',
  `company_id` int(11) DEFAULT NULL COMMENT 'For multi-company support',
  `metadata` text DEFAULT NULL COMMENT 'Additional JSON metadata',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doc` (`doc_type`, `doc_id`),
  UNIQUE KEY `unique_barcode` (`barcode_id`),
  KEY `idx_doc_type` (`doc_type`),
  KEY `idx_doc_number` (`doc_number`),
  KEY `idx_status` (`status`),
  KEY `idx_issuer` (`issuer_user_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_issue_date` (`issue_date`),
  CONSTRAINT `fk_doc_issuer` FOREIGN KEY (`issuer_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_doc_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Document verification tracking for barcodes and QR codes';

-- Add index for verification lookups
CREATE INDEX idx_verification_hash ON document_verifications(verification_hash);
CREATE INDEX idx_composite_lookup ON document_verifications(doc_type, doc_id, status);
