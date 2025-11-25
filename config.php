<?php
/**
 * Configuration File
 * Contains sensitive configuration values
 * DO NOT commit this file with real values to version control
 */

// Document verification secret key
// IMPORTANT: Change this to a unique random string in production
// You can generate one using: php -r "echo bin2hex(random_bytes(32));"
define('DOCUMENT_VERIFICATION_SECRET', getenv('DOC_VERIFICATION_SECRET') ?: 'CHANGE_THIS_IN_PRODUCTION_' . hash('sha256', __DIR__ . 'FEZA_LOGISTICS'));

// Email settings
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@fezalogistics.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Feza Logistics');
define('MAIL_REPLY_TO', getenv('MAIL_REPLY_TO') ?: 'info@fezalogistics.com');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('ACCOUNT_LOCKOUT_DURATION', 24); // Hours
define('MAX_LOGIN_ATTEMPTS', 3);

// Application settings
define('APP_NAME', 'Feza Logistics Financial Management');
define('APP_VERSION', '2.1.0');
define('DEFAULT_CURRENCY', 'RWF');

// Supported currencies
define('SUPPORTED_CURRENCIES', ['USD', 'EUR', 'GBP', 'RWF']);

// Timezone
date_default_timezone_set('Africa/Kigali');
