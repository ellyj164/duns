<?php
/**
 * api_petty_cash_settings.php
 * 
 * API endpoint for petty cash float settings management
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';
require_once 'petty_cash_rbac.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get current settings
        $sql = "SELECT * FROM petty_cash_float_settings ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->query($sql);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Return default settings if none exist
            $settings = [
                'initial_float' => 100000.00,
                'max_limit' => 500000.00,
                'replenishment_threshold' => 50000.00,
                'approval_threshold' => 50000.00,
                'daily_limit' => null,
                'monthly_limit' => null
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        
    } elseif ($method === 'POST') {
        // Check permission - only admin can modify settings
        requirePettyCashPermission('manage_settings');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate numeric fields
        $numericFields = ['initial_float', 'max_limit', 'replenishment_threshold', 'approval_threshold', 'daily_limit', 'monthly_limit'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && !is_numeric($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "$field must be numeric."]);
                exit;
            }
        }
        
        // Update or insert settings
        $sql = "INSERT INTO petty_cash_float_settings 
                (initial_float, max_limit, replenishment_threshold, approval_threshold, daily_limit, monthly_limit, updated_by)
                VALUES (:initial_float, :max_limit, :replenishment_threshold, :approval_threshold, :daily_limit, :monthly_limit, :updated_by)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':initial_float' => $data['initial_float'] ?? 100000.00,
            ':max_limit' => $data['max_limit'] ?? null,
            ':replenishment_threshold' => $data['replenishment_threshold'] ?? null,
            ':approval_threshold' => $data['approval_threshold'] ?? 50000.00,
            ':daily_limit' => $data['daily_limit'] ?? null,
            ':monthly_limit' => $data['monthly_limit'] ?? null,
            ':updated_by' => $_SESSION['user_id']
        ]);
        
        // Log activity
        logActivity($_SESSION['user_id'], 'update-petty-cash-settings', 'petty_cash_float_settings', $pdo->lastInsertId(), json_encode($data));
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully.'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
