<?php
/**
 * api_petty_cash_integration.php
 * 
 * API endpoint for integrating petty cash with main expenses, accounting, and ledger
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
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action is required.']);
            exit;
        }
        
        switch ($data['action']) {
            case 'sync_to_expenses':
                // Sync approved petty cash transactions to main expenses table
                syncToExpenses($pdo, $data);
                break;
                
            case 'post_to_ledger':
                // Post approved petty cash transactions to general ledger
                postToLedger($pdo, $data);
                break;
                
            case 'link_to_invoice':
                // Link petty cash transaction to an invoice
                linkToInvoice($pdo, $data);
                break;
                
            case 'get_sync_status':
                // Get sync status of transactions
                getSyncStatus($pdo, $data);
                break;
                
            case 'auto_sync':
                // Auto-sync all approved transactions
                autoSync($pdo);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
                break;
        }
        
    } elseif ($method === 'GET') {
        // Get sync configuration or status
        if (isset($_GET['transaction_id'])) {
            getSyncStatusForTransaction($pdo, $_GET['transaction_id']);
        } else {
            getSyncSummary($pdo);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Integration error: ' . $e->getMessage()
    ]);
}

// Integration Functions

function syncToExpenses($pdo, $data) {
    // Check if transactions table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'transactions'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Transactions table does not exist.'
        ]);
        return;
    }
    
    $transactionIds = $data['transaction_ids'] ?? [];
    
    if (empty($transactionIds)) {
        // Sync all approved, unsynced transactions
        $sql = "SELECT * FROM petty_cash 
                WHERE approval_status = 'approved' 
                AND (notes NOT LIKE '%synced_to_expenses%' OR notes IS NULL)";
        $stmt = $pdo->query($sql);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Sync specific transactions
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        $sql = "SELECT * FROM petty_cash WHERE id IN ($placeholders) AND approval_status = 'approved'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($transactionIds);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $synced = 0;
    $errors = [];
    
    foreach ($transactions as $trans) {
        try {
            // Insert into transactions table (adapt to your schema)
            // This is a generic example - adjust field names as needed
            $insertSql = "INSERT INTO transactions 
                         (date, description, amount, type, reference, created_by)
                         VALUES (:date, :description, :amount, :type, :reference, :created_by)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                ':date' => $trans['transaction_date'],
                ':description' => 'Petty Cash: ' . $trans['description'],
                ':amount' => $trans['amount'],
                ':type' => $trans['transaction_type'],
                ':reference' => $trans['reference'] ?? 'PC-' . $trans['id'],
                ':created_by' => $trans['user_id']
            ]);
            
            // Mark as synced
            $updateSql = "UPDATE petty_cash 
                         SET notes = CONCAT(COALESCE(notes, ''), '\nsynced_to_expenses:', :expense_id, ' on ', NOW())
                         WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':expense_id' => $pdo->lastInsertId(),
                ':id' => $trans['id']
            ]);
            
            $synced++;
        } catch (PDOException $e) {
            $errors[] = ['transaction_id' => $trans['id'], 'error' => $e->getMessage()];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$synced transaction(s) synced to expenses.",
        'synced_count' => $synced,
        'errors' => $errors
    ]);
}

function postToLedger($pdo, $data) {
    // This is a placeholder for general ledger integration
    // Implement based on your accounting system structure
    
    $transactionIds = $data['transaction_ids'] ?? [];
    
    if (empty($transactionIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Transaction IDs are required.']);
        return;
    }
    
    $posted = 0;
    $errors = [];
    
    foreach ($transactionIds as $transId) {
        try {
            // Get transaction details
            $sql = "SELECT * FROM petty_cash WHERE id = :id AND approval_status = 'approved'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $transId]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trans) {
                $errors[] = ['transaction_id' => $transId, 'error' => 'Transaction not found or not approved.'];
                continue;
            }
            
            // Post to ledger (implement your ledger logic here)
            // Example: Create journal entries
            
            // Mark as posted
            $updateSql = "UPDATE petty_cash 
                         SET notes = CONCAT(COALESCE(notes, ''), '\nposted_to_ledger on ', NOW())
                         WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([':id' => $transId]);
            
            $posted++;
        } catch (PDOException $e) {
            $errors[] = ['transaction_id' => $transId, 'error' => $e->getMessage()];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$posted transaction(s) posted to ledger.",
        'posted_count' => $posted,
        'errors' => $errors
    ]);
}

function linkToInvoice($pdo, $data) {
    if (empty($data['transaction_id']) || empty($data['invoice_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Transaction ID and Invoice ID are required.']);
        return;
    }
    
    // Update transaction with invoice link
    $sql = "UPDATE petty_cash 
            SET notes = CONCAT(COALESCE(notes, ''), '\nlinked_to_invoice:', :invoice_id)
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':invoice_id' => $data['invoice_id'],
        ':id' => $data['transaction_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction linked to invoice successfully.'
    ]);
}

function getSyncStatus($pdo, $data) {
    $transactionIds = $data['transaction_ids'] ?? [];
    
    if (empty($transactionIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Transaction IDs are required.']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
    $sql = "SELECT id, notes FROM petty_cash WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($transactionIds);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $status = [];
    foreach ($transactions as $trans) {
        $status[$trans['id']] = [
            'synced_to_expenses' => strpos($trans['notes'] ?? '', 'synced_to_expenses') !== false,
            'posted_to_ledger' => strpos($trans['notes'] ?? '', 'posted_to_ledger') !== false,
            'linked_to_invoice' => strpos($trans['notes'] ?? '', 'linked_to_invoice') !== false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);
}

function getSyncStatusForTransaction($pdo, $transactionId) {
    $sql = "SELECT id, notes FROM petty_cash WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $transactionId]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trans) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Transaction not found.']);
        return;
    }
    
    $status = [
        'synced_to_expenses' => strpos($trans['notes'] ?? '', 'synced_to_expenses') !== false,
        'posted_to_ledger' => strpos($trans['notes'] ?? '', 'posted_to_ledger') !== false,
        'linked_to_invoice' => strpos($trans['notes'] ?? '', 'linked_to_invoice') !== false
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);
}

function getSyncSummary($pdo) {
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN notes LIKE '%synced_to_expenses%' THEN 1 ELSE 0 END) as synced_to_expenses,
            SUM(CASE WHEN notes LIKE '%posted_to_ledger%' THEN 1 ELSE 0 END) as posted_to_ledger
            FROM petty_cash";
    $stmt = $pdo->query($sql);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $summary
    ]);
}

function autoSync($pdo) {
    // Auto-sync all approved transactions that haven't been synced
    $sql = "SELECT * FROM petty_cash 
            WHERE approval_status = 'approved' 
            AND (notes NOT LIKE '%synced_to_expenses%' OR notes IS NULL)
            LIMIT 100";
    $stmt = $pdo->query($sql);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $synced = 0;
    $errors = [];
    
    foreach ($transactions as $trans) {
        try {
            // Check if transactions table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'transactions'");
            if ($tableCheck->rowCount() > 0) {
                // Insert into transactions table
                $insertSql = "INSERT INTO transactions 
                             (date, description, amount, type, reference, created_by)
                             VALUES (:date, :description, :amount, :type, :reference, :created_by)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':date' => $trans['transaction_date'],
                    ':description' => 'Petty Cash: ' . $trans['description'],
                    ':amount' => $trans['amount'],
                    ':type' => $trans['transaction_type'],
                    ':reference' => $trans['reference'] ?? 'PC-' . $trans['id'],
                    ':created_by' => $trans['user_id']
                ]);
                
                // Mark as synced
                $updateSql = "UPDATE petty_cash 
                             SET notes = CONCAT(COALESCE(notes, ''), '\nauto_synced on ', NOW())
                             WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([':id' => $trans['id']]);
                
                $synced++;
            }
        } catch (PDOException $e) {
            $errors[] = ['transaction_id' => $trans['id'], 'error' => $e->getMessage()];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Auto-sync completed. $synced transaction(s) synced.",
        'synced_count' => $synced,
        'errors' => $errors
    ]);
}
?>
