<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission
if (!isset($_SESSION['user_id']) || !canPerformPettyCashAction($_SESSION['user_id'], 'reconcile')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Reconciliation - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
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
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #111827, #374151); color: white; padding: 30px 20px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { color: #6b7280; font-size: 14px; }
        .stat-sublabel { color: #9ca3af; font-size: 12px; margin-top: 4px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 20px; font-weight: 600; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-balanced { background: #d1fae5; color: #065f46; }
        .badge-discrepancy { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; max-width: 600px; width: 90%; padding: 30px; border-radius: 12px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 20px; font-weight: 600; }
        .close { cursor: pointer; font-size: 24px; color: #6b7280; }
        .close:hover { color: #374151; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'Inter', sans-serif; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .discrepancy-alert { background: #fef3c7; border-left: 4px solid var(--warning); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .filters { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .filters input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; }
        .empty-state { text-align: center; padding: 40px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔍 Petty Cash Reconciliation</h1>
            <p>Daily/weekly reconciliation and discrepancy management</p>
        </div>
    </div>

    <div class="container">
        <div class="stats">
            <div class="stat-card" style="border-left: 4px solid var(--primary);">
                <div class="stat-value" id="expectedBalance">0.00</div>
                <div class="stat-label">Expected Balance</div>
                <div class="stat-sublabel">Based on transactions</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-value" id="reconciliationCount">0</div>
                <div class="stat-label">Total Reconciliations</div>
                <div class="stat-sublabel">All time</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" id="discrepancyCount">0</div>
                <div class="stat-label">Discrepancies Found</div>
                <div class="stat-sublabel">Requiring attention</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-value" id="totalDiscrepancy">0.00</div>
                <div class="stat-label">Total Variance</div>
                <div class="stat-sublabel">Absolute difference</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Perform Reconciliation</div>
                <button class="btn btn-primary" onclick="openReconcileModal()">+ New Reconciliation</button>
            </div>
            
            <div class="filters">
                <input type="date" id="filterFrom" placeholder="From Date" />
                <input type="date" id="filterTo" placeholder="To Date" />
                <select id="filterStatus" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="">All Status</option>
                    <option value="balanced">Balanced</option>
                    <option value="discrepancy">Discrepancy</option>
                </select>
                <button class="btn btn-primary" onclick="loadReconciliations()">Apply Filters</button>
            </div>

            <table id="reconciliationsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Difference</th>
                        <th>Status</th>
                        <th>Reconciled By</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reconciliationsList">
                    <tr><td colspan="8" style="text-align: center; padding: 40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reconciliation Modal -->
    <div id="reconcileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">New Reconciliation</div>
                <span class="close" onclick="closeReconcileModal()">&times;</span>
            </div>
            <form id="reconcileForm" onsubmit="performReconciliation(event)">
                <div class="form-group">
                    <label>Reconciliation Date *</label>
                    <input type="date" id="reconciliationDate" required />
                </div>
                
                <div class="form-group">
                    <label>Expected Balance</label>
                    <input type="number" id="expectedBalanceInput" step="0.01" readonly style="background: #f3f4f6;" />
                </div>
                
                <div class="form-group">
                    <label>Actual Balance (Physical Count) *</label>
                    <input type="number" id="actualBalance" step="0.01" required />
                </div>
                
                <div id="discrepancyWarning" style="display: none;"></div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="reconciliationNotes" placeholder="Add any observations or explanations..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeReconcileModal()" style="background: #e5e7eb; color: #374151;">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Reconciliation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Reconciliation Details</div>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="reconciliationDetails"></div>
        </div>
    </div>

    <script>
        let reconciliations = [];
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadReconciliations();
            loadSummaryStats();
            
            // Set default date to today
            document.getElementById('reconciliationDate').valueAsDate = new Date();
            
            // Calculate expected balance when date changes
            document.getElementById('reconciliationDate').addEventListener('change', function() {
                calculateExpectedBalance(this.value);
            });
            
            // Check for discrepancy when actual balance changes
            document.getElementById('actualBalance').addEventListener('input', checkDiscrepancy);
        });
        
        async function loadSummaryStats() {
            try {
                const response = await fetch('api_petty_cash_analytics.php?type=summary');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('expectedBalance').textContent = formatCurrency(result.data.current_balance);
                }
                
                // Load reconciliation stats
                const recResponse = await fetch('api_petty_cash_reconciliation.php');
                const recResult = await recResponse.json();
                
                if (recResult.success) {
                    const recs = recResult.data;
                    document.getElementById('reconciliationCount').textContent = recs.length;
                    
                    const discrepancies = recs.filter(r => r.status === 'discrepancy');
                    document.getElementById('discrepancyCount').textContent = discrepancies.length;
                    
                    const totalVar = discrepancies.reduce((sum, r) => sum + Math.abs(parseFloat(r.difference || 0)), 0);
                    document.getElementById('totalDiscrepancy').textContent = formatCurrency(totalVar);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        async function loadReconciliations() {
            try {
                let url = 'api_petty_cash_reconciliation.php?';
                const from = document.getElementById('filterFrom').value;
                const to = document.getElementById('filterTo').value;
                const status = document.getElementById('filterStatus').value;
                
                if (from) url += `from=${from}&`;
                if (to) url += `to=${to}&`;
                if (status) url += `status=${status}&`;
                
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    reconciliations = result.data;
                    renderReconciliations();
                }
            } catch (error) {
                console.error('Error loading reconciliations:', error);
                showError('Failed to load reconciliations');
            }
        }
        
        function renderReconciliations() {
            const tbody = document.getElementById('reconciliationsList');
            
            if (reconciliations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No reconciliations found</td></tr>';
                return;
            }
            
            tbody.innerHTML = reconciliations.map(rec => {
                const diff = parseFloat(rec.difference || 0);
                const status = rec.status === 'balanced' ? 'balanced' : 'discrepancy';
                const statusBadge = status === 'balanced' 
                    ? '<span class="badge badge-balanced">✓ Balanced</span>'
                    : '<span class="badge badge-discrepancy">⚠ Discrepancy</span>';
                
                return `
                    <tr>
                        <td>${formatDate(rec.reconciliation_date)}</td>
                        <td>${formatCurrency(rec.expected_balance)}</td>
                        <td>${formatCurrency(rec.actual_balance)}</td>
                        <td style="color: ${diff === 0 ? 'var(--success)' : 'var(--danger)'}; font-weight: 600;">
                            ${formatCurrency(Math.abs(diff))} ${diff > 0 ? '(Over)' : diff < 0 ? '(Short)' : ''}
                        </td>
                        <td>${statusBadge}</td>
                        <td>${rec.reconciled_by_name || 'N/A'}</td>
                        <td>${rec.notes ? rec.notes.substring(0, 50) + '...' : '-'}</td>
                        <td>
                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;" onclick="viewDetails(${rec.id})">View</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function openReconcileModal() {
            document.getElementById('reconcileModal').classList.add('show');
            document.getElementById('reconciliationDate').valueAsDate = new Date();
            calculateExpectedBalance(document.getElementById('reconciliationDate').value);
        }
        
        function closeReconcileModal() {
            document.getElementById('reconcileModal').classList.remove('show');
            document.getElementById('reconcileForm').reset();
            document.getElementById('discrepancyWarning').style.display = 'none';
        }
        
        async function calculateExpectedBalance(date) {
            try {
                const response = await fetch('api_petty_cash_analytics.php?type=summary');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('expectedBalanceInput').value = result.data.current_balance.toFixed(2);
                }
            } catch (error) {
                console.error('Error calculating expected balance:', error);
            }
        }
        
        function checkDiscrepancy() {
            const expected = parseFloat(document.getElementById('expectedBalanceInput').value) || 0;
            const actual = parseFloat(document.getElementById('actualBalance').value) || 0;
            const diff = actual - expected;
            const warningDiv = document.getElementById('discrepancyWarning');
            
            if (Math.abs(diff) > 0.01) {
                warningDiv.innerHTML = `
                    <div class="discrepancy-alert">
                        <strong>⚠ Discrepancy Detected!</strong><br>
                        Difference: ${formatCurrency(Math.abs(diff))} ${diff > 0 ? '(Over)' : '(Short)'}<br>
                        Please verify the physical count and add notes explaining the variance.
                    </div>
                `;
                warningDiv.style.display = 'block';
            } else {
                warningDiv.style.display = 'none';
            }
        }
        
        async function performReconciliation(event) {
            event.preventDefault();
            
            const data = {
                action: 'create',
                reconciliation_date: document.getElementById('reconciliationDate').value,
                actual_balance: parseFloat(document.getElementById('actualBalance').value),
                notes: document.getElementById('reconciliationNotes').value
            };
            
            try {
                const response = await fetch('api_petty_cash_reconciliation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Reconciliation completed successfully');
                    closeReconcileModal();
                    loadReconciliations();
                    loadSummaryStats();
                } else {
                    showError(result.error || 'Failed to complete reconciliation');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred');
            }
        }
        
        async function viewDetails(id) {
            try {
                const response = await fetch(`api_petty_cash_reconciliation.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const rec = result.data;
                    const diff = parseFloat(rec.difference || 0);
                    
                    document.getElementById('reconciliationDetails').innerHTML = `
                        <div style="line-height: 1.8;">
                            <p><strong>Date:</strong> ${formatDate(rec.reconciliation_date)}</p>
                            <p><strong>Expected Balance:</strong> ${formatCurrency(rec.expected_balance)}</p>
                            <p><strong>Actual Balance:</strong> ${formatCurrency(rec.actual_balance)}</p>
                            <p><strong>Difference:</strong> 
                                <span style="color: ${diff === 0 ? 'var(--success)' : 'var(--danger)'}; font-weight: 600;">
                                    ${formatCurrency(Math.abs(diff))} ${diff > 0 ? '(Over)' : diff < 0 ? '(Short)' : '(Balanced)'}
                                </span>
                            </p>
                            <p><strong>Status:</strong> ${rec.status === 'balanced' ? '✓ Balanced' : '⚠ Discrepancy'}</p>
                            <p><strong>Reconciled By:</strong> ${rec.reconciled_by_name || 'N/A'}</p>
                            <p><strong>Created:</strong> ${formatDateTime(rec.created_at)}</p>
                            ${rec.notes ? `<p><strong>Notes:</strong><br>${rec.notes}</p>` : ''}
                        </div>
                    `;
                    document.getElementById('detailsModal').classList.add('show');
                }
            } catch (error) {
                console.error('Error loading details:', error);
                showError('Failed to load details');
            }
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('show');
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
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        
        function formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showSuccess(message) {
            alert('✓ ' + message);
        }
        
        function showError(message) {
            alert('✗ ' + message);
        }
    </script>
</body>
</html>
