#!/usr/bin/env php
<?php
/**
 * Alert Checker Cron Job
 * Checks and triggers alert rules
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/NotificationManager.php';

$timestamp = date('Y-m-d H:i:s');
echo "[$timestamp] Starting alert check...\n";

try {
    $notificationManager = new NotificationManager($pdo);
    $result = $notificationManager->checkAlertRules();
    
    if ($result['success']) {
        echo "[$timestamp] Alert check completed. {$result['triggered_count']} alert(s) triggered.\n";
        exit(0);
    } else {
        echo "[$timestamp] ERROR: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "[$timestamp] EXCEPTION: " . $e->getMessage() . "\n";
    exit(1);
}
