<?php
/**
 * File Upload API
 * Handles file uploads for receipts, invoices, and other documents
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

require_once 'db.php';
require_once 'lib/FileUploadHandler.php';

$fileHandler = new FileUploadHandler($pdo);
$user_id = $_SESSION['user_id'];

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload':
            // Validate required parameters
            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No file provided']);
                exit;
            }
            
            $entityType = $_POST['entity_type'] ?? '';
            $entityId = intval($_POST['entity_id'] ?? 0);
            $fileType = $_POST['file_type'] ?? 'general';
            $description = $_POST['description'] ?? null;
            
            if (empty($entityType) || $entityId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid entity type or ID']);
                exit;
            }
            
            // Upload file
            $result = $fileHandler->uploadFile(
                $_FILES['file'],
                $entityType,
                $entityId,
                $fileType,
                $user_id,
                $description
            );
            
            if ($result['success']) {
                // Log activity
                if (file_exists(__DIR__ . '/activity_logger.php')) {
                    require_once 'activity_logger.php';
                    if (function_exists('logActivity')) {
                        logActivity($user_id, 'upload-attachment', $entityType, $entityId,
                                   json_encode(['filename' => $result['original_name']]));
                    }
                }
            }
            
            echo json_encode($result);
            break;
            
        case 'list':
            // Get attachments for entity
            $entityType = $_GET['entity_type'] ?? '';
            $entityId = intval($_GET['entity_id'] ?? 0);
            
            if (empty($entityType) || $entityId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid entity type or ID']);
                exit;
            }
            
            $result = $fileHandler->getAttachments($entityType, $entityId);
            echo json_encode($result);
            break;
            
        case 'delete':
            // Delete attachment
            $data = json_decode(file_get_contents('php://input'), true);
            $attachmentId = intval($data['attachment_id'] ?? 0);
            
            if ($attachmentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid attachment ID']);
                exit;
            }
            
            $result = $fileHandler->deleteAttachment($attachmentId, $user_id);
            
            if ($result['success']) {
                // Log activity
                if (file_exists(__DIR__ . '/activity_logger.php')) {
                    require_once 'activity_logger.php';
                    if (function_exists('logActivity')) {
                        logActivity($user_id, 'delete-attachment', 'attachment', $attachmentId, null);
                    }
                }
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
