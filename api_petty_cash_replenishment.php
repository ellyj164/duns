<?php
/**
 * api_petty_cash_replenishment.php
 * 
 * API endpoint for petty cash replenishment requests
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
        // Get replenishment requests
        if (isset($_GET['id'])) {
            // Get specific request
            $sql = "SELECT r.*, 
                           u1.username as requested_by_name,
                           u2.username as approved_by_name
                    FROM petty_cash_replenishment r
                    LEFT JOIN users u1 ON r.requested_by = u1.id
                    LEFT JOIN users u2 ON r.approved_by = u2.id
                    WHERE r.id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_GET['id']]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                echo json_encode(['success' => true, 'data' => $request]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Request not found.']);
            }
        } else {
            // Get all requests with filters
            $sql = "SELECT r.*, 
                           u1.username as requested_by_name,
                           u2.username as approved_by_name
                    FROM petty_cash_replenishment r
                    LEFT JOIN users u1 ON r.requested_by = u1.id
                    LEFT JOIN users u2 ON r.approved_by = u2.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (!empty($_GET['status'])) {
                $sql .= " AND r.status = :status";
                $params[':status'] = $_GET['status'];
            }
            
            // Date range filter
            if (!empty($_GET['from'])) {
                $sql .= " AND r.request_date >= :from";
                $params[':from'] = $_GET['from'];
            }
            if (!empty($_GET['to'])) {
                $sql .= " AND r.request_date <= :to";
                $params[':to'] = $_GET['to'];
            }
            
            $sql .= " ORDER BY r.request_date DESC, r.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $requests]);
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action is required.']);
            exit;
        }
        
        switch ($data['action']) {
            case 'create':
                // Validate required fields
                if (empty($data['requested_amount']) || empty($data['justification'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Requested amount and justification are required.']);
                    exit;
                }
                
                // Calculate current balance
                $balanceSql = "SELECT 
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'credit' AND approval_status = 'approved') -
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'debit' AND approval_status = 'approved') as current_balance";
                $balanceStmt = $pdo->query($balanceSql);
                $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                $currentBalance = $balanceResult['current_balance'] ?? 0;
                
                // Insert replenishment request
                $sql = "INSERT INTO petty_cash_replenishment 
                        (request_date, requested_amount, current_balance, justification, requested_by, status)
                        VALUES (:request_date, :requested_amount, :current_balance, :justification, :requested_by, 'pending')";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    ':request_date' => $data['request_date'] ?? date('Y-m-d'),
                    ':requested_amount' => $data['requested_amount'],
                    ':current_balance' => $currentBalance,
                    ':justification' => $data['justification'],
                    ':requested_by' => $_SESSION['user_id']
                ]);
                
                $newId = $pdo->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Replenishment request created successfully.',
                    'id' => $newId,
                    'current_balance' => $currentBalance
                ]);
                break;
                
            case 'approve':
                // Approve replenishment request
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Request ID is required.']);
                    exit;
                }
                
                // Check if request exists and is pending
                $checkSql = "SELECT * FROM petty_cash_replenishment WHERE id = :id AND status = 'pending'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $data['id']]);
                $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$request) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Request not found or already processed.']);
                    exit;
                }
                
                // Update request status
                $sql = "UPDATE petty_cash_replenishment
                        SET status = 'approved',
                            approved_by = :approved_by,
                            approved_at = NOW(),
                            notes = :notes
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':approved_by' => $_SESSION['user_id'],
                    ':notes' => $data['notes'] ?? null,
                    ':id' => $data['id']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Replenishment request approved successfully.'
                ]);
                break;
                
            case 'reject':
                // Reject replenishment request
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Request ID is required.']);
                    exit;
                }
                
                $sql = "UPDATE petty_cash_replenishment
                        SET status = 'rejected',
                            approved_by = :approved_by,
                            approved_at = NOW(),
                            notes = :notes
                        WHERE id = :id AND status = 'pending'";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':approved_by' => $_SESSION['user_id'],
                    ':notes' => $data['rejection_reason'] ?? 'Rejected',
                    ':id' => $data['id']
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Replenishment request rejected successfully.'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Request not found or already processed.']);
                }
                break;
                
            case 'complete':
                // Mark replenishment as completed (money received)
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Request ID is required.']);
                    exit;
                }
                
                // Check if request is approved
                $checkSql = "SELECT * FROM petty_cash_replenishment WHERE id = :id AND status = 'approved'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $data['id']]);
                $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$request) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Request not found or not approved.']);
                    exit;
                }
                
                // Update request status
                $sql = "UPDATE petty_cash_replenishment
                        SET status = 'completed',
                            completed_at = NOW()
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $data['id']]);
                
                // Optionally create a credit transaction in petty_cash
                if (isset($data['create_transaction']) && $data['create_transaction'] === true) {
                    $transSql = "INSERT INTO petty_cash 
                                 (user_id, transaction_date, description, amount, transaction_type, 
                                  reference, approval_status, approved_by, approved_at, is_locked)
                                 VALUES (:user_id, :transaction_date, :description, :amount, 'credit',
                                         :reference, 'approved', :approved_by, NOW(), 1)";
                    $transStmt = $pdo->prepare($transSql);
                    $transStmt->execute([
                        ':user_id' => $_SESSION['user_id'],
                        ':transaction_date' => date('Y-m-d'),
                        ':description' => 'Petty Cash Replenishment - Request #' . $data['id'],
                        ':amount' => $request['requested_amount'],
                        ':reference' => 'REPL-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT),
                        ':approved_by' => $_SESSION['user_id']
                    ]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Replenishment marked as completed successfully.'
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
