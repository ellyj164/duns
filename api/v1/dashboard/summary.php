<?php
/**
 * API Endpoint: Dashboard Summary
 * Returns dashboard summary data for mobile/external apps
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

// Check authentication (simplified)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../../../db.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Get total revenue
    $stmt = $pdo->query("
        SELECT SUM(amount) as total_revenue 
        FROM transactions 
        WHERE transaction_type = 'credit'
    ");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
    
    // Get outstanding amount
    $stmt = $pdo->query("
        SELECT SUM(amount) as outstanding 
        FROM transactions 
        WHERE status = 'unpaid'
    ");
    $outstandingAmount = $stmt->fetch(PDO::FETCH_ASSOC)['outstanding'] ?? 0;
    
    // Get total clients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
    $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent transactions
    $stmt = $pdo->query("
        SELECT 
            t.*,
            c.name as client_name
        FROM transactions t
        LEFT JOIN clients c ON t.client_id = c.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get revenue by currency
    $stmt = $pdo->query("
        SELECT 
            currency,
            SUM(amount) as total
        FROM transactions
        WHERE transaction_type = 'credit'
        GROUP BY currency
    ");
    $revenueByCurrency = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate monthly growth
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as revenue
        FROM transactions
        WHERE transaction_type = 'credit'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
        GROUP BY month
        ORDER BY month DESC
        LIMIT 2
    ");
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $growthRate = 0;
    if (count($monthlyData) == 2) {
        $currentMonth = floatval($monthlyData[0]['revenue']);
        $previousMonth = floatval($monthlyData[1]['revenue']);
        if ($previousMonth > 0) {
            $growthRate = (($currentMonth - $previousMonth) / $previousMonth) * 100;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => floatval($totalRevenue),
            'outstanding_amount' => floatval($outstandingAmount),
            'total_clients' => intval($totalClients),
            'growth_rate' => round($growthRate, 2),
            'revenue_by_currency' => $revenueByCurrency,
            'recent_transactions' => $recentTransactions
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
