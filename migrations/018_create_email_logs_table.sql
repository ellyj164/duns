-- Migration: Create email_logs table
-- This table tracks all emails sent from the system for audit purposes

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User who sent the email',
  `doc_type` varchar(50) DEFAULT NULL COMMENT 'Related document type if applicable',
  `doc_id` int(11) DEFAULT NULL COMMENT 'Related document ID if applicable',
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `cc_emails` text DEFAULT NULL COMMENT 'Comma-separated CC addresses',
  `bcc_emails` text DEFAULT NULL COMMENT 'Comma-separated BCC addresses',
  `subject` varchar(500) NOT NULL,
  `message_body` text DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_size` int(11) DEFAULT NULL COMMENT 'Size in bytes',
  `email_type` varchar(50) DEFAULT 'document' COMMENT 'Type: document, notification, alert, statement, etc.',
  `status` enum('sent','failed','pending','cancelled') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_doc_type_id` (`doc_type`, `doc_id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `fk_email_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Email audit log for all outgoing emails';
