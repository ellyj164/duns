-- Migration: Create notifications and alerts system
-- Enables automated alerts, reminders, and system notifications

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'Recipient user (NULL for system-wide)',
  `type` varchar(50) NOT NULL COMMENT 'Type: alert, reminder, info, warning, error',
  `category` varchar(50) NOT NULL COMMENT 'Category: invoice, payment, petty_cash, security, system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL COMMENT 'Link to related resource',
  `action_label` varchar(100) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Related entity type',
  `related_id` int(11) DEFAULT NULL COMMENT 'Related entity ID',
  `metadata` text DEFAULT NULL COMMENT 'Additional JSON data',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Auto-delete after this date',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='User notifications and system alerts';

-- Alert rules configuration
CREATE TABLE IF NOT EXISTS `alert_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `alert_type` varchar(50) NOT NULL COMMENT 'overdue_invoice, low_balance, pending_approval, etc.',
  `condition_json` text NOT NULL COMMENT 'JSON condition configuration',
  `action_json` text NOT NULL COMMENT 'JSON action configuration (email, notification, etc.)',
  `is_active` tinyint(1) DEFAULT 1,
  `frequency` enum('realtime','hourly','daily','weekly') DEFAULT 'daily',
  `last_triggered` timestamp NULL DEFAULT NULL,
  `trigger_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_alert_type` (`alert_type`),
  CONSTRAINT `fk_alert_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Automated alert rules and triggers';

-- Notification preferences per user
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `in_app_enabled` tinyint(1) DEFAULT 1,
  `frequency` enum('instant','daily_digest','weekly_digest') DEFAULT 'instant',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_category` (`user_id`, `category`),
  CONSTRAINT `fk_notif_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default alert rules
INSERT INTO `alert_rules` (`name`, `description`, `alert_type`, `condition_json`, `action_json`, `is_active`, `frequency`, `created_by`) VALUES
('Overdue Invoices', 'Alert when invoices are overdue by more than 7 days', 'overdue_invoice', 
 '{"days_overdue": 7, "min_amount": 0}', 
 '{"notification": true, "email": true, "recipients": ["manager", "accountant"]}', 
 1, 'daily', 1),
 
('Low Petty Cash Balance', 'Alert when petty cash balance falls below threshold', 'low_balance',
 '{"threshold_amount": 10000, "currency": "RWF"}',
 '{"notification": true, "email": true, "recipients": ["admin", "accountant"]}',
 1, 'daily', 1),
 
('Pending Approvals', 'Alert for pending petty cash approvals', 'pending_approval',
 '{"pending_days": 2}',
 '{"notification": true, "email": true, "recipients": ["approver", "manager"]}',
 1, 'daily', 1),
 
('Large Transactions', 'Alert for transactions above threshold', 'large_transaction',
 '{"threshold_amount": 500000, "currency": "RWF"}',
 '{"notification": true, "email": true, "recipients": ["admin", "manager"]}',
 1, 'realtime', 1);
