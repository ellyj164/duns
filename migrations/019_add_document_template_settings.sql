-- Migration: Add document template settings
-- This allows per-company or per-user template customization

CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `template_code` varchar(50) NOT NULL COMMENT 'Unique code: classic, modern, minimal, elegant, corporate',
  `doc_type` varchar(50) NOT NULL COMMENT 'invoice, receipt, quotation, etc.',
  `template_file` varchar(255) DEFAULT NULL COMMENT 'PHP template file name',
  `preview_image` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `settings_json` text DEFAULT NULL COMMENT 'JSON settings for colors, fonts, layout',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template_doc` (`template_code`, `doc_type`),
  KEY `idx_doc_type` (`doc_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Document template configurations';

-- Company-specific template preferences
CREATE TABLE IF NOT EXISTS `company_template_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL COMMENT 'NULL for global default',
  `doc_type` varchar(50) NOT NULL,
  `template_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_doc` (`company_id`, `doc_type`),
  KEY `idx_template_id` (`template_id`),
  CONSTRAINT `fk_template_pref` FOREIGN KEY (`template_id`) REFERENCES `document_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default templates
INSERT INTO `document_templates` (`template_name`, `template_code`, `doc_type`, `is_default`, `is_active`) VALUES
('Classic Professional', 'classic', 'invoice', 1, 1),
('Modern Minimalist', 'modern', 'invoice', 0, 1),
('Elegant Corporate', 'elegant', 'invoice', 0, 1),
('Bold Contemporary', 'bold', 'invoice', 0, 1),
('Clean Simple', 'simple', 'invoice', 0, 1),
('Classic Professional', 'classic', 'receipt', 1, 1),
('Modern Minimalist', 'modern', 'receipt', 0, 1),
('Elegant Corporate', 'elegant', 'receipt', 0, 1),
('Classic Professional', 'classic', 'quotation', 1, 1),
('Modern Minimalist', 'modern', 'quotation', 0, 1),
('Elegant Corporate', 'elegant', 'quotation', 0, 1);
