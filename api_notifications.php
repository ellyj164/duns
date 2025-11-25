<?php
/**
 * Notifications API
 * Handles notification operations: fetch, mark as read, create
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

require_once 'db.php';
require_once 'lib/NotificationManager.php';

$notificationManager = new NotificationManager($pdo);
$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get user notifications
                $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
                $limit = intval($_GET['limit'] ?? 50);
                
                $result = $notificationManager->getUserNotifications($user_id, $unreadOnly, $limit);
                echo json_encode($result);
                
            } elseif ($action === 'count') {
                // Get unread count
                $count = $notificationManager->getUnreadCount($user_id);
                echo json_encode(['success' => true, 'count' => $count]);
                
            } elseif ($action === 'check_alerts') {
                // Check and trigger alert rules (admin only)
                require_once 'rbac.php';
                if (!userHasPermission($user_id, 'manage-users')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Permission denied']);
                    exit;
                }
                
                $result = $notificationManager->checkAlertRules();
                echo json_encode($result);
                
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'mark_read') {
                // Mark notification as read
                $notificationId = intval($data['notification_id'] ?? 0);
                if ($notificationId <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                    exit;
                }
                
                $result = $notificationManager->markAsRead($notificationId, $user_id);
                echo json_encode($result);
                
            } elseif ($action === 'mark_all_read') {
                // Mark all notifications as read
                $result = $notificationManager->markAllAsRead($user_id);
                echo json_encode($result);
                
            } elseif ($action === 'create') {
                // Create notification (admin only)
                require_once 'rbac.php';
                if (!userHasPermission($user_id, 'manage-users')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Permission denied']);
                    exit;
                }
                
                $result = $notificationManager->createNotification($data);
                echo json_encode($result);
                
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
