<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission
if (!isset($_SESSION['user_id']) || !canPerformPettyCashAction($_SESSION['user_id'], 'view')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Analytics - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8f9fc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); }
        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #111827, #374151); color: white; padding: 30px 20px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; }
        .stat-label { color: #6b7280; font-size: 14px; }
        .stat-change { font-size: 13px; margin-top: 5px; }
        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--danger); }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 20px; font-weight: 600; }
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); }
        .chart-container { position: relative; height: 300px; }
        .filters { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filters label { font-weight: 500; }
        .filters input, .filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .legend { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px; }
        .legend-item { display: flex; align-items: center; gap: 5px; font-size: 14px; }
        .legend-color { width: 16px; height: 16px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📊 Petty Cash Analytics</h1>
            <p>Visual insights and spending trends</p>
        </div>
    </div>

    <div class="container">
        <!-- Date Range Filters -->
        <div class="filters">
            <label>From:</label>
            <input type="date" id="filterFrom" />
            <label>To:</label>
            <input type="date" id="filterTo" />
            <button class="btn btn-primary" onclick="loadAllData()">Apply Filters</button>
            <button class="btn" onclick="setQuickRange('week')" style="background: #e5e7eb;">Last 7 Days</button>
            <button class="btn" onclick="setQuickRange('month')" style="background: #e5e7eb;">Last 30 Days</button>
            <button class="btn" onclick="setQuickRange('year')" style="background: #e5e7eb;">This Year</button>
        </div>

        <!-- Summary Stats -->
        <div class="stats">
            <div class="stat-card" style="border-left: 4px solid var(--primary);">
                <div class="stat-value" id="currentBalance">0.00</div>
                <div class="stat-label">Current Balance</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-value" id="totalCredit">0.00</div>
                <div class="stat-label">Total Credits</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-value" id="totalDebit">0.00</div>
                <div class="stat-label">Total Debits</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" id="transactionCount">0</div>
                <div class="stat-label">Transactions</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-2">
            <!-- Category Breakdown Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Spending by Category</div>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div id="categoryLegend" class="legend"></div>
            </div>

            <!-- Daily Usage Trend Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daily Cash Flow Trend</div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Monthly Spending Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Monthly Spending Overview</div>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Top Categories Table -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Top Spending Categories</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th>Total Spent</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody id="topCategoriesTable">
                        <tr><td colspan="4" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed Analytics Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Transaction History Summary</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Credits</th>
                        <th>Debits</th>
                        <th>Net Change</th>
                        <th>Transaction Count</th>
                        <th>Avg Transaction</th>
                    </tr>
                </thead>
                <tbody id="summaryTable">
                    <tr><td colspan="6" style="text-align: center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let categoryChart, trendChart, monthlyChart;

        document.addEventListener('DOMContentLoaded', function() {
            // Set default date range (last 30 days)
            setQuickRange('month');
            loadAllData();
        });

        function setQuickRange(range) {
            const to = new Date();
            const from = new Date();

            if (range === 'week') {
                from.setDate(to.getDate() - 7);
            } else if (range === 'month') {
                from.setDate(to.getDate() - 30);
            } else if (range === 'year') {
                from.setMonth(0, 1);
            }

            document.getElementById('filterFrom').valueAsDate = from;
            document.getElementById('filterTo').valueAsDate = to;
        }

        async function loadAllData() {
            await Promise.all([
                loadSummary(),
                loadCategoryBreakdown(),
                loadDailyTrend(),
                loadMonthlyData()
            ]);
        }

        async function loadSummary() {
            try {
                const response = await fetch('api_petty_cash_analytics.php?type=summary');
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    document.getElementById('currentBalance').textContent = formatCurrency(data.current_balance);
                    document.getElementById('totalCredit').textContent = formatCurrency(data.total_credit);
                    document.getElementById('totalDebit').textContent = formatCurrency(data.total_debit);
                    document.getElementById('transactionCount').textContent = 
                        (parseInt(data.pending_count) + parseInt(data.approved_count)).toLocaleString();
                }
            } catch (error) {
                console.error('Error loading summary:', error);
            }
        }

        async function loadCategoryBreakdown() {
            try {
                let url = 'api_petty_cash_analytics.php?type=category_breakdown';
                url += getDateRangeParams();

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    const categories = result.data.filter(c => parseFloat(c.total_spent) > 0);
                    
                    if (categories.length === 0) {
                        document.getElementById('topCategoriesTable').innerHTML = 
                            '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>';
                        return;
                    }

                    renderCategoryChart(categories);
                    renderCategoryTable(categories);
                }
            } catch (error) {
                console.error('Error loading category breakdown:', error);
            }
        }

        function renderCategoryChart(categories) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            if (categoryChart) {
                categoryChart.destroy();
            }

            const colors = categories.map(c => c.color || getRandomColor());
            
            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categories.map(c => c.name),
                    datasets: [{
                        data: categories.map(c => parseFloat(c.total_spent)),
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.label}: ${formatCurrency(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Custom legend
            const legendHtml = categories.map((c, i) => `
                <div class="legend-item">
                    <div class="legend-color" style="background: ${colors[i]};"></div>
                    <span>${c.icon || ''} ${c.name}</span>
                </div>
            `).join('');
            document.getElementById('categoryLegend').innerHTML = legendHtml;
        }

        function renderCategoryTable(categories) {
            const total = categories.reduce((sum, c) => sum + parseFloat(c.total_spent), 0);
            
            const html = categories.map(c => {
                const spent = parseFloat(c.total_spent);
                const percentage = ((spent / total) * 100).toFixed(1);
                
                return `
                    <tr>
                        <td>${c.icon || ''} ${c.name}</td>
                        <td>${c.transaction_count}</td>
                        <td>${formatCurrency(spent)}</td>
                        <td>${percentage}%</td>
                    </tr>
                `;
            }).join('');
            
            document.getElementById('topCategoriesTable').innerHTML = html;
        }

        async function loadDailyTrend() {
            try {
                let url = 'api_petty_cash_analytics.php?type=daily_usage';
                url += getDateRangeParams();

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    renderTrendChart(result.data);
                }
            } catch (error) {
                console.error('Error loading daily trend:', error);
            }
        }

        function renderTrendChart(data) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            if (trendChart) {
                trendChart.destroy();
            }

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => formatDate(d.date)),
                    datasets: [
                        {
                            label: 'Credits',
                            data: data.map(d => parseFloat(d.credit)),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Debits',
                            data: data.map(d => parseFloat(d.debit)),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        async function loadMonthlyData() {
            try {
                let url = 'api_petty_cash_analytics.php?type=monthly_summary';
                
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    renderMonthlyChart(result.data);
                    renderSummaryTable(result.data);
                }
            } catch (error) {
                console.error('Error loading monthly data:', error);
            }
        }

        function renderMonthlyChart(data) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            if (monthlyChart) {
                monthlyChart.destroy();
            }

            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [
                        {
                            label: 'Credits',
                            data: data.map(d => parseFloat(d.credit)),
                            backgroundColor: '#10b981'
                        },
                        {
                            label: 'Debits',
                            data: data.map(d => parseFloat(d.debit)),
                            backgroundColor: '#ef4444'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderSummaryTable(data) {
            const html = data.map(d => {
                const credit = parseFloat(d.credit);
                const debit = parseFloat(d.debit);
                const net = credit - debit;
                const count = parseInt(d.transaction_count);
                const avg = count > 0 ? (debit / count) : 0;
                
                return `
                    <tr>
                        <td>${d.month}</td>
                        <td>${formatCurrency(credit)}</td>
                        <td>${formatCurrency(debit)}</td>
                        <td style="color: ${net >= 0 ? 'var(--success)' : 'var(--danger)'}; font-weight: 600;">
                            ${formatCurrency(Math.abs(net))} ${net >= 0 ? '↑' : '↓'}
                        </td>
                        <td>${count}</td>
                        <td>${formatCurrency(avg)}</td>
                    </tr>
                `;
            }).join('');
            
            document.getElementById('summaryTable').innerHTML = html || 
                '<tr><td colspan="6" style="text-align: center;">No data available</td></tr>';
        }

        function getDateRangeParams() {
            const from = document.getElementById('filterFrom').value;
            const to = document.getElementById('filterTo').value;
            let params = '';
            
            if (from) params += `&from=${from}`;
            if (to) params += `&to=${to}`;
            
            return params;
        }

        function getRandomColor() {
            const colors = ['#4f46e5', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
            return colors[Math.floor(Math.random() * colors.length)];
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
        }
    </script>
</body>
</html>
