<?php
/**
 * api_petty_cash_reconciliation.php
 * 
 * API endpoint for petty cash reconciliation
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
        // Get reconciliation records
        if (isset($_GET['id'])) {
            // Get specific reconciliation
            $sql = "SELECT r.*, u.username as reconciled_by_name
                    FROM petty_cash_reconciliation r
                    LEFT JOIN users u ON r.reconciled_by = u.id
                    WHERE r.id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_GET['id']]);
            $reconciliation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reconciliation) {
                echo json_encode(['success' => true, 'data' => $reconciliation]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Reconciliation not found.']);
            }
        } else {
            // Get all reconciliations with filters
            $sql = "SELECT r.*, u.username as reconciled_by_name
                    FROM petty_cash_reconciliation r
                    LEFT JOIN users u ON r.reconciled_by = u.id
                    WHERE 1=1";
            $params = [];
            
            // Date range filter
            if (!empty($_GET['from'])) {
                $sql .= " AND r.reconciliation_date >= :from";
                $params[':from'] = $_GET['from'];
            }
            if (!empty($_GET['to'])) {
                $sql .= " AND r.reconciliation_date <= :to";
                $params[':to'] = $_GET['to'];
            }
            
            // Status filter
            if (!empty($_GET['status'])) {
                $sql .= " AND r.status = :status";
                $params[':status'] = $_GET['status'];
            }
            
            $sql .= " ORDER BY r.reconciliation_date DESC, r.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $reconciliations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $reconciliations]);
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
                if (empty($data['reconciliation_date']) || !isset($data['actual_balance'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Reconciliation date and actual balance are required.']);
                    exit;
                }
                
                // Calculate expected balance
                $balanceSql = "SELECT 
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'credit' 
                                 AND approval_status = 'approved'
                                 AND transaction_date <= :date) -
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'debit' 
                                 AND approval_status = 'approved'
                                 AND transaction_date <= :date) as expected_balance";
                $balanceStmt = $pdo->prepare($balanceSql);
                $balanceStmt->execute([':date' => $data['reconciliation_date']]);
                $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                $expectedBalance = $balanceResult['expected_balance'] ?? 0;
                
                $actualBalance = floatval($data['actual_balance']);
                $difference = $actualBalance - $expectedBalance;
                
                // Insert reconciliation record
                $sql = "INSERT INTO petty_cash_reconciliation 
                        (reconciliation_date, expected_balance, actual_balance, difference, explanation, reconciled_by, status)
                        VALUES (:reconciliation_date, :expected_balance, :actual_balance, :difference, :explanation, :reconciled_by, :status)";
                $stmt = $pdo->prepare($sql);
                
                $status = (abs($difference) < 0.01) ? 'resolved' : 'pending';
                
                $stmt->execute([
                    ':reconciliation_date' => $data['reconciliation_date'],
                    ':expected_balance' => $expectedBalance,
                    ':actual_balance' => $actualBalance,
                    ':difference' => $difference,
                    ':explanation' => $data['explanation'] ?? null,
                    ':reconciled_by' => $_SESSION['user_id'],
                    ':status' => $status
                ]);
                
                $newId = $pdo->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reconciliation recorded successfully.',
                    'id' => $newId,
                    'expected_balance' => $expectedBalance,
                    'difference' => $difference,
                    'status' => $status
                ]);
                break;
                
            case 'update':
                // Update reconciliation (mainly for explanation and status)
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Reconciliation ID is required.']);
                    exit;
                }
                
                $sql = "UPDATE petty_cash_reconciliation
                        SET explanation = :explanation,
                            status = :status
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    ':id' => $data['id'],
                    ':explanation' => $data['explanation'] ?? null,
                    ':status' => $data['status'] ?? 'pending'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reconciliation updated successfully.'
                ]);
                break;
                
            case 'calculate_expected':
                // Calculate expected balance for a given date
                if (empty($data['date'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Date is required.']);
                    exit;
                }
                
                $balanceSql = "SELECT 
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'credit' 
                                 AND approval_status = 'approved'
                                 AND transaction_date <= :date) as total_credit,
                                (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                                 WHERE transaction_type = 'debit' 
                                 AND approval_status = 'approved'
                                 AND transaction_date <= :date) as total_debit";
                $balanceStmt = $pdo->prepare($balanceSql);
                $balanceStmt->execute([':date' => $data['date']]);
                $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                
                $totalCredit = floatval($balanceResult['total_credit'] ?? 0);
                $totalDebit = floatval($balanceResult['total_debit'] ?? 0);
                $expectedBalance = $totalCredit - $totalDebit;
                
                echo json_encode([
                    'success' => true,
                    'expected_balance' => $expectedBalance,
                    'total_credit' => $totalCredit,
                    'total_debit' => $totalDebit
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
