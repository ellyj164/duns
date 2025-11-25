<?php
/**
 * api_petty_cash_approval.php
 * 
 * API endpoint for petty cash approval workflow
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get pending approvals
        $status = $_GET['status'] ?? 'pending';
        
        $sql = "SELECT pc.*, u.username as requester_name, c.name as category_name
                FROM petty_cash pc
                LEFT JOIN users u ON pc.user_id = u.id
                LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
                WHERE pc.approval_status = :status
                ORDER BY pc.transaction_date DESC, pc.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => $status]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $transactions
        ]);
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action is required.']);
            exit;
        }
        
        switch ($data['action']) {
            case 'approve':
                // Validate required fields
                if (empty($data['transaction_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
                    exit;
                }
                
                // Check if transaction exists and is pending
                $checkSql = "SELECT * FROM petty_cash WHERE id = :id AND approval_status = 'pending'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $data['transaction_id']]);
                $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$transaction) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Transaction not found or already processed.']);
                    exit;
                }
                
                // Update approval status
                $sql = "UPDATE petty_cash
                        SET approval_status = 'approved',
                            approved_by = :approved_by,
                            approved_at = NOW(),
                            is_locked = 1
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':approved_by' => $_SESSION['user_id'],
                    ':id' => $data['transaction_id']
                ]);
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], 'approve-petty-cash', 'petty_cash', $data['transaction_id'], 
                               json_encode(['amount' => $transaction['amount'], 'description' => $transaction['description']]));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction approved successfully.'
                ]);
                break;
                
            case 'reject':
                // Validate required fields
                if (empty($data['transaction_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
                    exit;
                }
                
                // Check if transaction exists and is pending
                $checkSql = "SELECT * FROM petty_cash WHERE id = :id AND approval_status = 'pending'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $data['transaction_id']]);
                $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$transaction) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Transaction not found or already processed.']);
                    exit;
                }
                
                // Update approval status
                $sql = "UPDATE petty_cash
                        SET approval_status = 'rejected',
                            approved_by = :approved_by,
                            approved_at = NOW(),
                            notes = :notes
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':approved_by' => $_SESSION['user_id'],
                    ':notes' => $data['rejection_reason'] ?? 'Rejected',
                    ':id' => $data['transaction_id']
                ]);
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], 'reject-petty-cash', 'petty_cash', $data['transaction_id'],
                               json_encode(['amount' => $transaction['amount'], 'reason' => $data['rejection_reason'] ?? 'N/A']));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction rejected successfully.'
                ]);
                break;
                
            case 'bulk_approve':
                // Validate required fields
                if (empty($data['transaction_ids']) || !is_array($data['transaction_ids'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Transaction IDs array is required.']);
                    exit;
                }
                
                $approved = 0;
                foreach ($data['transaction_ids'] as $transactionId) {
                    $sql = "UPDATE petty_cash
                            SET approval_status = 'approved',
                                approved_by = :approved_by,
                                approved_at = NOW(),
                                is_locked = 1
                            WHERE id = :id AND approval_status = 'pending'";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':approved_by' => $_SESSION['user_id'],
                        ':id' => $transactionId
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $approved++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "$approved transaction(s) approved successfully."
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
                break;
        }
        
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
