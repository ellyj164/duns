<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission
if (!isset($_SESSION['user_id']) || !canPerformPettyCashAction($_SESSION['user_id'], 'replenish')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Replenishment - Feza Logistics</title>
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
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; }
        .stat-label { color: #6b7280; font-size: 14px; }
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
        .btn-warning { background: var(--warning); color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: 500; color: #6b7280; }
        .tab.active { color: var(--primary); border-bottom: 2px solid var(--primary); margin-bottom: -2px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; max-width: 600px; width: 90%; padding: 30px; border-radius: 12px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 20px; font-weight: 600; }
        .close { cursor: pointer; font-size: 24px; color: #6b7280; }
        .close:hover { color: #374151; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'Inter', sans-serif; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .info-box { background: #eff6ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .warning-box { background: #fef3c7; border-left: 4px solid var(--warning); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .empty-state { text-align: center; padding: 40px; color: #6b7280; }
        .filters { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .filters select, .filters input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>💰 Petty Cash Replenishment</h1>
            <p>Request and manage cash float replenishment</p>
        </div>
    </div>

    <div class="container">
        <div class="stats">
            <div class="stat-card" style="border-left: 4px solid var(--primary);">
                <div class="stat-value" id="currentBalance">0.00</div>
                <div class="stat-label">Current Balance</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" id="pendingRequests">0</div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-value" id="approvedCount">0</div>
                <div class="stat-label">Approved This Month</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-value" id="totalRequested">0.00</div>
                <div class="stat-label">Total Requested</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Replenishment Requests</div>
                <button class="btn btn-primary" onclick="openRequestModal()">+ New Request</button>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('pending', event)">Pending</button>
                <button class="tab" onclick="switchTab('approved', event)">Approved</button>
                <button class="tab" onclick="switchTab('rejected', event)">Rejected</button>
                <button class="tab" onclick="switchTab('completed', event)">Completed</button>
            </div>

            <div class="filters">
                <input type="date" id="filterFrom" placeholder="From Date" />
                <input type="date" id="filterTo" placeholder="To Date" />
                <button class="btn btn-primary" onclick="loadRequests()">Apply Filters</button>
            </div>

            <table id="requestsTable">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Requested Amount</th>
                        <th>Current Balance</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requestsList">
                    <tr><td colspan="7" style="text-align: center; padding: 40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">New Replenishment Request</div>
                <span class="close" onclick="closeRequestModal()">&times;</span>
            </div>
            <form id="requestForm" onsubmit="submitRequest(event)">
                <div class="info-box">
                    <strong>Current Balance:</strong> <span id="modalCurrentBalance">Loading...</span><br>
                    <strong>Replenishment Threshold:</strong> <span id="replenishmentThreshold">Loading...</span>
                </div>

                <div class="form-group">
                    <label>Requested Amount *</label>
                    <input type="number" id="requestedAmount" step="0.01" min="0.01" required />
                </div>

                <div class="form-group">
                    <label>Justification *</label>
                    <textarea id="justification" required placeholder="Explain why this replenishment is needed..."></textarea>
                </div>

                <div class="form-group">
                    <label>Expected Spend (Next Period)</label>
                    <textarea id="expectedSpend" placeholder="Describe anticipated expenses..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeRequestModal()" style="background: #e5e7eb; color: #374151;">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Request Details</div>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="requestDetails"></div>
            <div id="actionButtons" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;"></div>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="actionModalTitle">Action</div>
                <span class="close" onclick="closeActionModal()">&times;</span>
            </div>
            <form id="actionForm" onsubmit="submitAction(event)">
                <input type="hidden" id="actionRequestId" />
                <input type="hidden" id="actionType" />

                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="actionNotes" placeholder="Add notes about this decision..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeActionModal()" style="background: #e5e7eb; color: #374151;">Cancel</button>
                    <button type="submit" class="btn" id="actionSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let requests = [];
        let currentTab = 'pending';
        let currentRequestId = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadRequests();
            loadSummaryStats();
            loadSettings();
        });

        async function loadSummaryStats() {
            try {
                const response = await fetch('api_petty_cash_analytics.php?type=summary');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('currentBalance').textContent = formatCurrency(result.data.current_balance);
                }

                const reqResponse = await fetch('api_petty_cash_replenishment.php');
                const reqResult = await reqResponse.json();

                if (reqResult.success) {
                    const reqs = reqResult.data;
                    const pending = reqs.filter(r => r.status === 'pending');
                    document.getElementById('pendingRequests').textContent = pending.length;

                    const thisMonth = reqs.filter(r => {
                        const date = new Date(r.request_date);
                        const now = new Date();
                        return date.getMonth() === now.getMonth() && 
                               date.getFullYear() === now.getFullYear() &&
                               r.status === 'approved';
                    });
                    document.getElementById('approvedCount').textContent = thisMonth.length;

                    const total = reqs.reduce((sum, r) => sum + parseFloat(r.requested_amount || 0), 0);
                    document.getElementById('totalRequested').textContent = formatCurrency(total);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadSettings() {
            try {
                const response = await fetch('api_petty_cash_settings.php');
                const result = await response.json();

                if (result.success) {
                    const settings = result.data;
                    document.getElementById('replenishmentThreshold').textContent = 
                        formatCurrency(settings.replenishment_threshold || 50000);
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        async function loadRequests() {
            try {
                let url = 'api_petty_cash_replenishment.php?status=' + currentTab;
                const from = document.getElementById('filterFrom').value;
                const to = document.getElementById('filterTo').value;

                if (from) url += `&from=${from}`;
                if (to) url += `&to=${to}`;

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    requests = result.data;
                    renderRequests();
                }
            } catch (error) {
                console.error('Error loading requests:', error);
                showError('Failed to load requests');
            }
        }

        function renderRequests() {
            const tbody = document.getElementById('requestsList');

            if (requests.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No requests found</td></tr>';
                return;
            }

            tbody.innerHTML = requests.map(req => {
                const statusBadges = {
                    'pending': '<span class="badge badge-pending">⏳ Pending</span>',
                    'approved': '<span class="badge badge-approved">✓ Approved</span>',
                    'rejected': '<span class="badge badge-rejected">✗ Rejected</span>',
                    'completed': '<span class="badge badge-completed">✓ Completed</span>'
                };

                return `
                    <tr>
                        <td>${formatDate(req.request_date)}</td>
                        <td>${formatCurrency(req.requested_amount)}</td>
                        <td>${formatCurrency(req.current_balance)}</td>
                        <td>${req.justification ? req.justification.substring(0, 50) + '...' : '-'}</td>
                        <td>${statusBadges[req.status] || req.status}</td>
                        <td>${req.requested_by_name || 'N/A'}</td>
                        <td>
                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;" onclick="viewDetails(${req.id})">View</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function switchTab(tab, event) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            loadRequests();
        }

        async function openRequestModal() {
            document.getElementById('requestModal').classList.add('show');

            try {
                const response = await fetch('api_petty_cash_analytics.php?type=summary');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('modalCurrentBalance').textContent = 
                        formatCurrency(result.data.current_balance);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function closeRequestModal() {
            document.getElementById('requestModal').classList.remove('show');
            document.getElementById('requestForm').reset();
        }

        async function submitRequest(event) {
            event.preventDefault();

            const data = {
                action: 'create',
                requested_amount: parseFloat(document.getElementById('requestedAmount').value),
                justification: document.getElementById('justification').value,
                expected_spend: document.getElementById('expectedSpend').value
            };

            try {
                const response = await fetch('api_petty_cash_replenishment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Replenishment request submitted successfully');
                    closeRequestModal();
                    loadRequests();
                    loadSummaryStats();
                } else {
                    showError(result.error || 'Failed to submit request');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred');
            }
        }

        async function viewDetails(id) {
            try {
                const response = await fetch(`api_petty_cash_replenishment.php?id=${id}`);
                const result = await response.json();

                if (result.success) {
                    currentRequestId = id;
                    const req = result.data;

                    document.getElementById('requestDetails').innerHTML = `
                        <div style="line-height: 1.8;">
                            <p><strong>Request Date:</strong> ${formatDate(req.request_date)}</p>
                            <p><strong>Requested Amount:</strong> ${formatCurrency(req.requested_amount)}</p>
                            <p><strong>Current Balance:</strong> ${formatCurrency(req.current_balance)}</p>
                            <p><strong>Status:</strong> ${req.status}</p>
                            <p><strong>Requested By:</strong> ${req.requested_by_name || 'N/A'}</p>
                            ${req.approved_by_name ? `<p><strong>Approved By:</strong> ${req.approved_by_name}</p>` : ''}
                            ${req.approval_date ? `<p><strong>Approval Date:</strong> ${formatDate(req.approval_date)}</p>` : ''}
                            <p><strong>Justification:</strong><br>${req.justification || 'N/A'}</p>
                            ${req.expected_spend ? `<p><strong>Expected Spend:</strong><br>${req.expected_spend}</p>` : ''}
                            ${req.approval_notes ? `<p><strong>Approval Notes:</strong><br>${req.approval_notes}</p>` : ''}
                        </div>
                    `;

                    // Show action buttons for pending requests
                    const actionButtons = document.getElementById('actionButtons');
                    if (req.status === 'pending') {
                        actionButtons.innerHTML = `
                            <button class="btn btn-success" onclick="openActionModal(${id}, 'approve')">Approve</button>
                            <button class="btn btn-danger" onclick="openActionModal(${id}, 'reject')">Reject</button>
                        `;
                    } else {
                        actionButtons.innerHTML = '';
                    }

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

        function openActionModal(id, action) {
            document.getElementById('actionRequestId').value = id;
            document.getElementById('actionType').value = action;
            document.getElementById('actionModalTitle').textContent = 
                action === 'approve' ? 'Approve Request' : 'Reject Request';

            const submitBtn = document.getElementById('actionSubmitBtn');
            submitBtn.className = 'btn ' + (action === 'approve' ? 'btn-success' : 'btn-danger');
            submitBtn.textContent = action === 'approve' ? 'Approve' : 'Reject';

            document.getElementById('detailsModal').classList.remove('show');
            document.getElementById('actionModal').classList.add('show');
        }

        function closeActionModal() {
            document.getElementById('actionModal').classList.remove('show');
            document.getElementById('actionForm').reset();
        }

        async function submitAction(event) {
            event.preventDefault();

            const id = document.getElementById('actionRequestId').value;
            const action = document.getElementById('actionType').value;
            const notes = document.getElementById('actionNotes').value;

            try {
                const response = await fetch('api_petty_cash_replenishment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        request_id: id,
                        notes: notes
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(`Request ${action}d successfully`);
                    closeActionModal();
                    loadRequests();
                    loadSummaryStats();
                } else {
                    showError(result.error || `Failed to ${action} request`);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred');
            }
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

        function showSuccess(message) {
            alert('✓ ' + message);
        }

        function showError(message) {
            alert('✗ ' + message);
        }
    </script>
</body>
</html>
