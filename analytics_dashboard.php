<?php
/**
 * Analytics Dashboard
 * Advanced data visualization and business intelligence
 */

require_once 'header.php';

// Check if user has permission to view analytics
$canViewAnalytics = true; // Default to true, can be restricted via RBAC later

if (!$canViewAnalytics) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Get date range from query parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Fetch analytics data
$analyticsData = [];

try {
    // Revenue trends by month (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as total_revenue,
            COUNT(*) as transaction_count
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $analyticsData['revenue_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Expense breakdown by category (petty cash)
    $stmt = $pdo->query("
        SELECT 
            pc.category,
            SUM(pc.amount) as total_amount,
            COUNT(*) as count
        FROM petty_cash pc
        WHERE pc.transaction_type = 'expense'
        AND pc.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY pc.category
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $analyticsData['expense_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top clients by revenue
    $stmt = $pdo->query("
        SELECT 
            c.name as client_name,
            SUM(t.amount) as total_revenue,
            COUNT(t.id) as transaction_count
        FROM clients c
        LEFT JOIN transactions t ON c.id = t.client_id
        WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY c.id
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $analyticsData['top_clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Currency distribution
    $stmt = $pdo->query("
        SELECT 
            currency,
            SUM(amount) as total_amount,
            COUNT(*) as count
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY currency
    ");
    $analyticsData['currency_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Failed to fetch analytics data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Feza Logistics</title>
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .analytics-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .analytics-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin: 0;
        }
        
        .date-range-picker {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-range-picker input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--white-color);
            color: var(--text-color);
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-card h2 {
            color: var(--text-color);
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .kpi-card h3 {
            font-size: 0.9rem;
            margin: 0 0 10px 0;
            opacity: 0.9;
        }
        
        .kpi-card .value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        
        .btn-refresh {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-refresh:hover {
            background: var(--primary-hover);
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <div class="analytics-header">
            <h1>📊 Analytics Dashboard</h1>
            <div class="date-range-picker">
                <input type="date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                <span>to</span>
                <input type="date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                <button class="btn-refresh" onclick="refreshAnalytics()">Refresh</button>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <h3>Total Revenue (Last 12 Months)</h3>
                <p class="value" id="total-revenue">Loading...</p>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3>Active Clients</h3>
                <p class="value" id="active-clients">Loading...</p>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3>Avg Transaction Value</h3>
                <p class="value" id="avg-transaction">Loading...</p>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <h3>Growth Rate</h3>
                <p class="value" id="growth-rate">Loading...</p>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="chart-grid">
            <div class="chart-card">
                <h2>Revenue Trends (Last 12 Months)</h2>
                <div class="chart-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h2>Top 10 Clients by Revenue</h2>
                <div class="chart-wrapper">
                    <canvas id="topClientsChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h2>Expense Breakdown by Category</h2>
                <div class="chart-wrapper">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h2>Currency Distribution</h2>
                <div class="chart-wrapper">
                    <canvas id="currencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Analytics data from PHP
        const analyticsData = <?php echo json_encode($analyticsData); ?>;
        
        // Revenue Trends Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: analyticsData.revenue_trends.map(item => item.month),
                datasets: [{
                    label: 'Revenue',
                    data: analyticsData.revenue_trends.map(item => parseFloat(item.total_revenue)),
                    borderColor: '#0052cc',
                    backgroundColor: 'rgba(0, 82, 204, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Top Clients Chart
        const topClientsCtx = document.getElementById('topClientsChart').getContext('2d');
        const topClientsChart = new Chart(topClientsCtx, {
            type: 'bar',
            data: {
                labels: analyticsData.top_clients.map(item => item.client_name),
                datasets: [{
                    label: 'Revenue',
                    data: analyticsData.top_clients.map(item => parseFloat(item.total_revenue)),
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Expense Breakdown Chart
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: analyticsData.expense_breakdown.map(item => item.category || 'Uncategorized'),
                datasets: [{
                    data: analyticsData.expense_breakdown.map(item => parseFloat(item.total_amount)),
                    backgroundColor: [
                        '#0052cc', '#10b981', '#f59e0b', '#ef4444', 
                        '#8b5cf6', '#ec4899', '#14b8a6', '#f97316',
                        '#06b6d4', '#84cc16'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Currency Distribution Chart
        const currencyCtx = document.getElementById('currencyChart').getContext('2d');
        const currencyChart = new Chart(currencyCtx, {
            type: 'pie',
            data: {
                labels: analyticsData.currency_distribution.map(item => item.currency),
                datasets: [{
                    data: analyticsData.currency_distribution.map(item => parseFloat(item.total_amount)),
                    backgroundColor: ['#0052cc', '#10b981', '#f59e0b', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Calculate and display KPIs
        function updateKPIs() {
            const totalRevenue = analyticsData.revenue_trends.reduce((sum, item) => sum + parseFloat(item.total_revenue), 0);
            document.getElementById('total-revenue').textContent = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'RWF'
            }).format(totalRevenue);
            
            const activeClients = analyticsData.top_clients.length;
            document.getElementById('active-clients').textContent = activeClients;
            
            const avgTransaction = totalRevenue / analyticsData.revenue_trends.reduce((sum, item) => sum + parseInt(item.transaction_count), 0);
            document.getElementById('avg-transaction').textContent = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'RWF'
            }).format(avgTransaction || 0);
            
            // Calculate growth rate (comparing last month to previous month)
            if (analyticsData.revenue_trends.length >= 2) {
                const lastMonth = parseFloat(analyticsData.revenue_trends[analyticsData.revenue_trends.length - 1].total_revenue);
                const prevMonth = parseFloat(analyticsData.revenue_trends[analyticsData.revenue_trends.length - 2].total_revenue);
                const growthRate = prevMonth > 0 ? ((lastMonth - prevMonth) / prevMonth * 100) : 0;
                document.getElementById('growth-rate').textContent = growthRate.toFixed(1) + '%';
            } else {
                document.getElementById('growth-rate').textContent = 'N/A';
            }
        }
        
        function refreshAnalytics() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            window.location.href = `analytics_dashboard.php?start_date=${startDate}&end_date=${endDate}`;
        }
        
        // Initialize
        updateKPIs();
    </script>
</body>
</html>
