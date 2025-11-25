<?php
/**
 * Activity Logging Helper Functions
 * 
 * This file provides functions to log user activities for audit trail
 * Use: require_once 'activity_logger.php';
 */

require_once 'db.php';

/**
 * Log a user activity
 * 
 * @param int $userId User ID (can be null for system actions)
 * @param string $action Action performed (e.g., 'create-invoice', 'delete-client')
 * @param string $targetType Type of resource affected (e.g., 'clients', 'invoices')
 * @param int $targetId ID of the affected resource
 * @param array $details Additional details in associative array format
 * @return bool True on success, false on failure
 */
function logActivity($userId, $action, $targetType = null, $targetId = null, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs 
            (user_id, action, target_type, target_id, details, ip_address)
            VALUES (:user_id, :action, :target_type, :target_id, :details, :ip_address)
        ");
        
        // Convert details array to JSON if provided
        $detailsJson = null;
        if ($details !== null && is_array($details)) {
            $detailsJson = json_encode($details);
        } elseif ($details !== null && is_string($details)) {
            $detailsJson = $details;
        }
        
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $detailsJson,
            'ip_address' => $ipAddress
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Activity Logging Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get activity logs for a specific user
 * 
 * @param int $userId User ID
 * @param int $limit Maximum number of logs to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of activity log objects
 */
function getUserActivityLogs($userId, $limit = 50, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.username, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.user_id = :user_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get User Activity Logs Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all activity logs (admin only)
 * 
 * @param int $limit Maximum number of logs to retrieve
 * @param int $offset Offset for pagination
 * @param string $action Filter by specific action (optional)
 * @return array Array of activity log objects
 */
function getAllActivityLogs($limit = 100, $offset = 0, $action = null) {
    global $pdo;
    
    try {
        $sql = "
            SELECT al.*, u.username, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
        ";
        
        if ($action !== null) {
            $sql .= " WHERE al.action = :action";
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        
        if ($action !== null) {
            $stmt->bindValue('action', $action, PDO::PARAM_STR);
        }
        
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get All Activity Logs Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get activity logs for a specific target
 * 
 * @param string $targetType Type of resource
 * @param int $targetId ID of the resource
 * @param int $limit Maximum number of logs to retrieve
 * @return array Array of activity log objects
 */
function getTargetActivityLogs($targetType, $targetId, $limit = 50) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.username, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.target_type = :target_type AND al.target_id = :target_id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue('target_type', $targetType, PDO::PARAM_STR);
        $stmt->bindValue('target_id', $targetId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get Target Activity Logs Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Count total activity logs (for pagination)
 * 
 * @param int $userId Optional user ID to filter by
 * @return int Total count of logs
 */
function countActivityLogs($userId = null) {
    global $pdo;
    
    try {
        if ($userId !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM activity_logs");
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    } catch (PDOException $e) {
        error_log("Count Activity Logs Error: " . $e->getMessage());
        return 0;
    }
}
?>
