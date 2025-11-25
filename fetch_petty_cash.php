<?php
/**
 * fetch_petty_cash.php
 * 
 * API endpoint to retrieve petty cash transactions with filtering capabilities
 * Supports date range, transaction type, and search filters
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

try {
    // Build SQL query with filters including new fields
    $sql = "SELECT pc.id, pc.user_id, pc.transaction_date, pc.description, pc.beneficiary, pc.purpose,
                   pc.amount, pc.transaction_type, pc.category_id, pc.payment_method, pc.reference,
                   pc.receipt_path, pc.approval_status, pc.approved_by, pc.approved_at, pc.is_locked, pc.notes,
                   pc.created_at, pc.updated_at,
                   c.name as category_name, c.icon as category_icon, c.color as category_color,
                   u.username as user_name,
                   ap.username as approver_name
            FROM petty_cash pc
            LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
            LEFT JOIN users u ON pc.user_id = u.id
            LEFT JOIN users ap ON pc.approved_by = ap.id
            WHERE 1=1";
    $params = [];
    
    // Date range filters
    if (!empty($_GET['from'])) {
        $sql .= " AND DATE(pc.transaction_date) >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND DATE(pc.transaction_date) <= :to";
        $params[':to'] = $_GET['to'];
    }
    
    // Transaction type filter
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $sql .= " AND pc.transaction_type = :type";
        $params[':type'] = $_GET['type'];
    }
    
    // Search query (searches description, reference, payment method)
    if (!empty(trim($_GET['q']))) {
        $searchQuery = '%' . trim($_GET['q']) . '%';
        $sql .= " AND (pc.description LIKE :searchQuery1 
                      OR pc.reference LIKE :searchQuery2 
                      OR pc.payment_method LIKE :searchQuery3
                      OR DATE_FORMAT(pc.transaction_date, '%Y-%m-%d') LIKE :searchQuery4
                      OR DATE_FORMAT(pc.transaction_date, '%d/%m/%Y') LIKE :searchQuery5)";
        
        $params[':searchQuery1'] = $searchQuery;
        $params[':searchQuery2'] = $searchQuery;
        $params[':searchQuery3'] = $searchQuery;
        $params[':searchQuery4'] = $searchQuery;
        $params[':searchQuery5'] = $searchQuery;
        
        // Check if search query is numeric for amount search
        if (is_numeric(trim($_GET['q']))) {
            $sql .= " OR pc.amount = :searchQueryNumeric";
            $params[':searchQueryNumeric'] = (float)trim($_GET['q']);
        }
    }
    
    // Order by date descending (newest first)
    $sql .= " ORDER BY pc.transaction_date DESC, pc.id DESC";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
