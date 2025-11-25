<?php
/**
 * api_petty_cash_analytics.php
 * 
 * API endpoint for petty cash analytics and reports
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
        $report_type = $_GET['type'] ?? 'summary';
        
        switch ($report_type) {
            case 'summary':
                // Overall summary statistics
                $sql = "SELECT 
                        (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                         WHERE transaction_type = 'credit' AND approval_status = 'approved') as total_credit,
                        (SELECT COALESCE(SUM(amount), 0) FROM petty_cash 
                         WHERE transaction_type = 'debit' AND approval_status = 'approved') as total_debit,
                        (SELECT COUNT(*) FROM petty_cash WHERE approval_status = 'pending') as pending_count,
                        (SELECT COUNT(*) FROM petty_cash WHERE approval_status = 'approved') as approved_count";
                $stmt = $pdo->query($sql);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $summary['current_balance'] = $summary['total_credit'] - $summary['total_debit'];
                
                echo json_encode(['success' => true, 'data' => $summary]);
                break;
                
            case 'category_breakdown':
                // Spending by category
                $params = [];
                $sql = "SELECT c.name, c.icon, c.color, 
                               COALESCE(SUM(pc.amount), 0) as total_spent,
                               COUNT(pc.id) as transaction_count
                        FROM petty_cash_categories c
                        LEFT JOIN petty_cash pc ON c.id = pc.category_id 
                            AND pc.transaction_type = 'debit' 
                            AND pc.approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from']) || !empty($_GET['to'])) {
                    $conditions = [];
                    if (!empty($_GET['from'])) {
                        $conditions[] = "pc.transaction_date >= :from";
                        $params[':from'] = $_GET['from'];
                    }
                    if (!empty($_GET['to'])) {
                        $conditions[] = "pc.transaction_date <= :to";
                        $params[':to'] = $_GET['to'];
                    }
                    if (!empty($conditions)) {
                        $sql .= " AND " . implode(' AND ', $conditions);
                    }
                }
                
                $sql .= " GROUP BY c.id, c.name, c.icon, c.color
                         ORDER BY total_spent DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $categories]);
                break;
                
            case 'daily_usage':
                // Daily spending trend
                $params = [];
                $sql = "SELECT 
                        DATE(transaction_date) as date,
                        SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit,
                        SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit,
                        COUNT(*) as transaction_count
                        FROM petty_cash
                        WHERE approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from'])) {
                    $sql .= " AND transaction_date >= :from";
                    $params[':from'] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $sql .= " AND transaction_date <= :to";
                    $params[':to'] = $_GET['to'];
                }
                
                $sql .= " GROUP BY DATE(transaction_date)
                         ORDER BY date ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $daily]);
                break;
                
            case 'weekly_usage':
                // Weekly spending trend
                $params = [];
                $sql = "SELECT 
                        YEAR(transaction_date) as year,
                        WEEK(transaction_date) as week,
                        DATE_SUB(transaction_date, INTERVAL WEEKDAY(transaction_date) DAY) as week_start,
                        SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit,
                        SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit,
                        COUNT(*) as transaction_count
                        FROM petty_cash
                        WHERE approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from'])) {
                    $sql .= " AND transaction_date >= :from";
                    $params[':from'] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $sql .= " AND transaction_date <= :to";
                    $params[':to'] = $_GET['to'];
                }
                
                $sql .= " GROUP BY year, week, week_start
                         ORDER BY year ASC, week ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $weekly = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $weekly]);
                break;
                
            case 'monthly_totals':
                // Monthly totals by category
                $params = [];
                $sql = "SELECT 
                        YEAR(pc.transaction_date) as year,
                        MONTH(pc.transaction_date) as month,
                        DATE_FORMAT(pc.transaction_date, '%Y-%m') as month_key,
                        c.name as category_name,
                        SUM(pc.amount) as total,
                        COUNT(pc.id) as count
                        FROM petty_cash pc
                        LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
                        WHERE pc.transaction_type = 'debit' 
                        AND pc.approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from'])) {
                    $sql .= " AND pc.transaction_date >= :from";
                    $params[':from'] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $sql .= " AND pc.transaction_date <= :to";
                    $params[':to'] = $_GET['to'];
                }
                
                $sql .= " GROUP BY year, month, month_key, c.name
                         ORDER BY year DESC, month DESC, total DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $monthly]);
                break;
                
            case 'top_spenders':
                // Top users by spending amount
                $params = [];
                $sql = "SELECT 
                        u.id, u.username, u.email,
                        COUNT(pc.id) as transaction_count,
                        SUM(CASE WHEN pc.transaction_type = 'debit' THEN pc.amount ELSE 0 END) as total_spent,
                        SUM(CASE WHEN pc.transaction_type = 'credit' THEN pc.amount ELSE 0 END) as total_added
                        FROM users u
                        INNER JOIN petty_cash pc ON u.id = pc.user_id
                        WHERE pc.approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from'])) {
                    $sql .= " AND pc.transaction_date >= :from";
                    $params[':from'] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $sql .= " AND pc.transaction_date <= :to";
                    $params[':to'] = $_GET['to'];
                }
                
                $sql .= " GROUP BY u.id, u.username, u.email
                         ORDER BY total_spent DESC
                         LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $topSpenders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $topSpenders]);
                break;
                
            case 'balance_history':
                // Running balance over time - calculate in PHP for MySQL 8+ compatibility
                $params = [];
                $sql = "SELECT 
                        id,
                        transaction_date,
                        transaction_type,
                        amount
                        FROM petty_cash
                        WHERE approval_status = 'approved'";
                
                // Date range filter
                if (!empty($_GET['from'])) {
                    $sql .= " AND transaction_date >= :from";
                    $params[':from'] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $sql .= " AND transaction_date <= :to";
                    $params[':to'] = $_GET['to'];
                }
                
                $sql .= " ORDER BY transaction_date ASC, id ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate running balance in PHP
                $runningBalance = 0;
                $history = [];
                foreach ($transactions as $trans) {
                    $runningBalance += ($trans['transaction_type'] === 'credit') ? 
                                       floatval($trans['amount']) : -floatval($trans['amount']);
                    $history[] = [
                        'transaction_date' => $trans['transaction_date'],
                        'transaction_type' => $trans['transaction_type'],
                        'amount' => $trans['amount'],
                        'running_balance' => $runningBalance
                    ];
                }
                
                echo json_encode(['success' => true, 'data' => $history]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid report type.']);
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
