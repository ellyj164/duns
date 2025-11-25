<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission
if (!canPerformPettyCashAction($_SESSION['user_id'], 'approve')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Approvals - Feza Logistics</title>
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
        .header h1 { font-size: 28px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; }
        .stat-label { color: #6b7280; font-size: 14px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: 500; color: #6b7280; }
        .tab.active { color: var(--primary); border-bottom: 2px solid var(--primary); margin-bottom: -2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-primary { background: var(--primary); color: white; }
        .btn:hover { opacity: 0.9; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; max-width: 500px; margin: 50px auto; padding: 30px; border-radius: 12px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; min-height: 100px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>✅ Petty Cash Approvals</h1>
            <p>Review and approve pending transactions</p>
        </div>
    </div>

    <div class="container">
        <div class="stats">
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" id="pendingCount">0</div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-value" id="approvedCount">0</div>
                <div class="stat-label">Approved Today</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-value" id="rejectedCount">0</div>
                <div class="stat-label">Rejected Today</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--primary);">
                <div class="stat-value" id="totalAmount">0</div>
                <div class="stat-label">Total Pending Amount</div>
            </div>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('pending')">Pending</button>
                <button class="tab" onclick="switchTab('approved')">Approved</button>
                <button class="tab" onclick="switchTab('rejected')">Rejected</button>
            </div>

            <div style="margin-bottom: 15px; display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="bulkApprove()" id="bulkApproveBtn">Approve Selected</button>
                <button class="btn btn-danger" onclick="bulkReject()" id="bulkRejectBtn">Reject Selected</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" /></th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Beneficiary</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionsList">
                    <tr><td colspan="9" style="text-align: center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h2>Reject Transaction</h2>
            <form id="rejectForm" onsubmit="submitRejection(event)">
                <input type="hidden" id="rejectTransactionId" />
                <div class="form-group">
                    <label>Reason for Rejection *</label>
                    <textarea id="rejectionReason" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Reject</button>
                    <button type="button" class="btn" onclick="closeRejectModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTab = 'pending';
        let transactions = [];

        async function loadTransactions(status = 'pending') {
            try {
                const response = await fetch(`api_petty_cash_approval.php?status=${status}`);
                const data = await response.json();
                if (data.success) {
                    transactions = data.data;
                    renderTransactions();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            }
        }

        function renderTransactions() {
            const tbody = document.getElementById('transactionsList');
            const showCheckbox = currentTab === 'pending';
            
            if (transactions.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align: center;">No ${currentTab} transactions</td></tr>`;
                return;
            }

            tbody.innerHTML = transactions.map(t => `
                <tr>
                    <td>${showCheckbox ? `<input type="checkbox" class="select-item" value="${t.id}" />` : ''}</td>
                    <td>${t.transaction_date}</td>
                    <td>${t.description}</td>
                    <td>${t.beneficiary || '-'}</td>
                    <td>${t.category_name || '-'}</td>
                    <td style="font-weight: 600;">${parseFloat(t.amount).toLocaleString()}</td>
                    <td>${t.requester_name || 'N/A'}</td>
                    <td><span class="badge badge-${t.approval_status}">${t.approval_status.toUpperCase()}</span></td>
                    <td>
                        ${currentTab === 'pending' ? `
                            <button class="btn btn-success" style="padding: 6px 12px;" onclick="approveTransaction(${t.id})">Approve</button>
                            <button class="btn btn-danger" style="padding: 6px 12px;" onclick="openRejectModal(${t.id})">Reject</button>
                        ` : '-'}
                    </td>
                </tr>
            `).join('');
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            loadTransactions(tab);
        }

        async function approveTransaction(id) {
            if (!confirm('Approve this transaction?')) return;

            try {
                const response = await fetch('api_petty_cash_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'approve', transaction_id: id })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Transaction approved successfully');
                    loadTransactions(currentTab);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error approving transaction:', error);
            }
        }

        function openRejectModal(id) {
            document.getElementById('rejectTransactionId').value = id;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejectForm').reset();
        }

        async function submitRejection(event) {
            event.preventDefault();

            const id = document.getElementById('rejectTransactionId').value;
            const reason = document.getElementById('rejectionReason').value;

            try {
                const response = await fetch('api_petty_cash_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'reject', 
                        transaction_id: id,
                        rejection_reason: reason
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Transaction rejected successfully');
                    closeRejectModal();
                    loadTransactions(currentTab);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error rejecting transaction:', error);
            }
        }

        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.select-item').forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }

        async function bulkApprove() {
            const selected = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => parseInt(cb.value));
            if (selected.length === 0) {
                alert('Please select transactions to approve');
                return;
            }

            if (!confirm(`Approve ${selected.length} transaction(s)?`)) return;

            try {
                const response = await fetch('api_petty_cash_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'bulk_approve', transaction_ids: selected })
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    loadTransactions(currentTab);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error bulk approving:', error);
            }
        }

        async function bulkReject() {
            alert('Please reject transactions individually with a reason.');
        }

        async function updateStats() {
            try {
                const response = await fetch('api_petty_cash_approval.php?status=pending');
                const data = await response.json();
                if (data.success) {
                    const pending = data.data;
                    document.getElementById('pendingCount').textContent = pending.length;
                    const totalAmount = pending.reduce((sum, t) => sum + parseFloat(t.amount), 0);
                    document.getElementById('totalAmount').textContent = totalAmount.toLocaleString();
                }

                // Get today's approved count
                const approvedResp = await fetch('api_petty_cash_approval.php?status=approved');
                const approvedData = await approvedResp.json();
                if (approvedData.success) {
                    const today = new Date().toISOString().split('T')[0];
                    const approvedToday = approvedData.data.filter(t => t.approved_at?.startsWith(today));
                    document.getElementById('approvedCount').textContent = approvedToday.length;
                }

                // Get today's rejected count
                const rejectedResp = await fetch('api_petty_cash_approval.php?status=rejected');
                const rejectedData = await rejectedResp.json();
                if (rejectedData.success) {
                    const today = new Date().toISOString().split('T')[0];
                    const rejectedToday = rejectedData.data.filter(t => t.approved_at?.startsWith(today));
                    document.getElementById('rejectedCount').textContent = rejectedToday.length;
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        // Load transactions on page load
        loadTransactions('pending');
        
        // Refresh every 30 seconds
        setInterval(() => loadTransactions(currentTab), 30000);
    </script>
</body>
</html>
