<?php
/**
 * api_petty_cash_receipt_upload.php
 * 
 * API endpoint for uploading receipts/attachments
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

// Define upload directory
$uploadDir = __DIR__ . '/uploads/petty_cash_receipts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate transaction_id
        if (empty($_POST['transaction_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
            exit;
        }
        
        $transactionId = intval($_POST['transaction_id']);
        
        // Check if transaction exists
        $checkSql = "SELECT id FROM petty_cash WHERE id = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $transactionId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transaction not found.']);
            exit;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
            exit;
        }
        
        $file = $_FILES['receipt'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileType = $file['type'];
        
        // Validate file type (images and PDFs only)
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only images and PDFs are allowed.']);
            exit;
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($fileSize > $maxSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit.']);
            exit;
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'receipt_' . $transactionId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $newFileName;
        
        // Move uploaded file
        if (!move_uploaded_file($fileTmp, $filePath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
            exit;
        }
        
        // Insert receipt record
        $sql = "INSERT INTO petty_cash_receipts (transaction_id, file_name, file_path, file_size, file_type, uploaded_by)
                VALUES (:transaction_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':file_name' => $fileName,
            ':file_path' => 'uploads/petty_cash_receipts/' . $newFileName,
            ':file_size' => $fileSize,
            ':file_type' => $fileType,
            ':uploaded_by' => $_SESSION['user_id']
        ]);
        
        $receiptId = $pdo->lastInsertId();
        
        // Update petty_cash table with receipt path (for backward compatibility)
        $updateSql = "UPDATE petty_cash SET receipt_path = :receipt_path WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':receipt_path' => 'uploads/petty_cash_receipts/' . $newFileName,
            ':id' => $transactionId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Receipt uploaded successfully.',
            'receipt_id' => $receiptId,
            'file_path' => 'uploads/petty_cash_receipts/' . $newFileName,
            'file_name' => $fileName
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get receipts for a transaction
        if (empty($_GET['transaction_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
            exit;
        }
        
        $sql = "SELECT id, transaction_id, file_name, file_path, file_size, file_type, created_at
                FROM petty_cash_receipts
                WHERE transaction_id = :transaction_id
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':transaction_id' => $_GET['transaction_id']]);
        $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $receipts
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete receipt
        parse_str(file_get_contents('php://input'), $data);
        
        if (empty($data['receipt_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Receipt ID is required.']);
            exit;
        }
        
        // Get receipt info
        $sql = "SELECT file_path FROM petty_cash_receipts WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $data['receipt_id']]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receipt) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Receipt not found.']);
            exit;
        }
        
        // Delete file from filesystem
        $fullPath = __DIR__ . '/' . $receipt['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Delete database record
        $deleteSql = "DELETE FROM petty_cash_receipts WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([':id' => $data['receipt_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Receipt deleted successfully.'
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
