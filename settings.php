<?php
/**
 * Settings Management Helper Functions
 * 
 * This file provides functions to manage system settings
 * Use: require_once 'settings.php';
 */

require_once 'db.php';

/**
 * Get a setting value by key
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed Setting value or default
 */
function getSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['value'] : $default;
    } catch (PDOException $e) {
        error_log("Get Setting Error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set a setting value
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool True on success, false on failure
 */
function setSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (`key`, `value`) 
            VALUES (:key, :value)
            AS new_values
            ON DUPLICATE KEY UPDATE `value` = new_values.`value`
        ");
        
        $stmt->execute([
            'key' => $key,
            'value' => $value
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Set Setting Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get multiple settings at once
 * 
 * @param array $keys Array of setting keys
 * @return array Associative array of key => value pairs
 */
function getSettings($keys) {
    global $pdo;
    
    try {
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
        $stmt->execute($keys);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Get Settings Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all settings
 * 
 * @return array Associative array of all settings
 */
function getAllSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings ORDER BY `key`");
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Get All Settings Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete a setting
 * 
 * @param string $key Setting key
 * @return bool True on success, false on failure
 */
function deleteSetting($key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM settings WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
        return true;
    } catch (PDOException $e) {
        error_log("Delete Setting Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a setting exists
 * 
 * @param string $key Setting key
 * @return bool True if exists, false otherwise
 */
function settingExists($key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM settings WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Setting Exists Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get company information from settings
 * 
 * @return array Associative array of company info
 */
function getCompanyInfo() {
    $keys = [
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'company_tin',
        'logo_url'
    ];
    
    return getSettings($keys);
}

/**
 * Update company information
 * 
 * @param array $info Associative array of company information
 * @return bool True on success, false on failure
 */
function updateCompanyInfo($info) {
    $success = true;
    
    foreach ($info as $key => $value) {
        if (!setSetting($key, $value)) {
            $success = false;
        }
    }
    
    return $success;
}
?>
