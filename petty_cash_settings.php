<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission - only admins can manage settings
if (!isset($_SESSION['user_id']) || !canPerformPettyCashAction($_SESSION['user_id'], 'manage_settings')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Settings - Feza Logistics</title>
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
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #111827, #374151); color: white; padding: 30px 20px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 20px; font-weight: 600; }
        .card-subtitle { color: #6b7280; font-size: 14px; margin-top: 5px; }
        .form-section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #e5e7eb; }
        .form-section:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
        .section-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #374151; }
        .section-description { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        .form-group .label-description { font-size: 13px; color: #6b7280; font-weight: 400; margin-top: 2px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn { padding: 12px 24px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .info-box { background: #eff6ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .info-box strong { display: block; margin-bottom: 5px; }
        .warning-box { background: #fef3c7; border-left: 4px solid var(--warning); padding: 15px; border-radius: 6px; margin: 15px 0; }
        .warning-box strong { display: block; margin-bottom: 5px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .save-bar { position: sticky; bottom: 20px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .current-value { background: #f3f4f6; padding: 10px 15px; border-radius: 6px; font-size: 14px; margin-top: 10px; }
        .current-value strong { color: var(--primary); }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>⚙️ Petty Cash Settings</h1>
            <p>Configure float settings, limits, and system preferences</p>
        </div>
    </div>

    <div class="container">
        <form id="settingsForm" onsubmit="saveSettings(event)">
            <!-- Cash Float Settings -->
            <div class="card">
                <div class="card-title">💰 Cash Float Configuration</div>
                <div class="card-subtitle">Set up the initial cash float and maximum limits</div>

                <div class="form-section" style="margin-top: 20px;">
                    <div class="info-box">
                        <strong>What is Cash Float?</strong>
                        The cash float is the initial amount of money allocated for petty cash expenses. 
                        It should be sufficient to cover regular small expenses without requiring frequent replenishment.
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label>
                                Initial Float Amount *
                                <div class="label-description">Starting balance for petty cash</div>
                            </label>
                            <input type="number" id="initialFloat" step="0.01" min="0" required />
                            <div class="current-value" id="currentInitialFloat"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                Maximum Limit
                                <div class="label-description">Maximum cash that can be held</div>
                            </label>
                            <input type="number" id="maxLimit" step="0.01" min="0" />
                            <div class="current-value" id="currentMaxLimit"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">🔔 Replenishment Settings</div>
                    <div class="section-description">
                        Configure when to trigger replenishment notifications
                    </div>

                    <div class="form-group">
                        <label>
                            Replenishment Threshold
                            <div class="label-description">Alert when balance falls below this amount</div>
                        </label>
                        <input type="number" id="replenishmentThreshold" step="0.01" min="0" />
                        <div class="current-value" id="currentReplenishmentThreshold"></div>
                    </div>
                </div>
            </div>

            <!-- Approval & Control Settings -->
            <div class="card">
                <div class="card-title">✅ Approval & Control Settings</div>
                <div class="card-subtitle">Configure approval workflows and spending controls</div>

                <div class="form-section" style="margin-top: 20px;">
                    <div class="warning-box">
                        <strong>Important:</strong>
                        Transactions above the approval threshold will require manager approval before processing.
                        Set this value carefully based on your organization's risk tolerance.
                    </div>

                    <div class="form-group">
                        <label>
                            Approval Threshold *
                            <div class="label-description">Transactions above this amount require approval</div>
                        </label>
                        <input type="number" id="approvalThreshold" step="0.01" min="0" required />
                        <div class="current-value" id="currentApprovalThreshold"></div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">📊 Spending Limits</div>
                    <div class="section-description">
                        Set daily and monthly spending limits to control cash outflow
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label>
                                Daily Spending Limit
                                <div class="label-description">Maximum that can be spent per day (optional)</div>
                            </label>
                            <input type="number" id="dailyLimit" step="0.01" min="0" placeholder="No limit" />
                            <div class="current-value" id="currentDailyLimit"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                Monthly Spending Limit
                                <div class="label-description">Maximum that can be spent per month (optional)</div>
                            </label>
                            <input type="number" id="monthlyLimit" step="0.01" min="0" placeholder="No limit" />
                            <div class="current-value" id="currentMonthlyLimit"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card">
                <div class="card-title">ℹ️ System Information</div>
                <div class="card-subtitle">Current system status and configuration details</div>

                <div style="margin-top: 20px;">
                    <div class="info-box">
                        <p><strong>Last Updated:</strong> <span id="lastUpdated">Loading...</span></p>
                        <p><strong>Updated By:</strong> <span id="updatedBy">Loading...</span></p>
                    </div>

                    <div class="info-box">
                        <strong>Configuration Tips:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px; line-height: 1.8;">
                            <li>Set the initial float high enough to cover 2-4 weeks of typical expenses</li>
                            <li>Keep the approval threshold low enough to maintain proper oversight</li>
                            <li>Use spending limits to prevent unexpected cash depletion</li>
                            <li>Review and adjust settings quarterly based on actual usage patterns</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Save Bar -->
            <div class="save-bar">
                <button type="button" class="btn" onclick="loadSettings()" style="background: #e5e7eb; color: #374151;">
                    Reset to Current
                </button>
                <button type="submit" class="btn btn-success">
                    💾 Save Settings
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentSettings = {};

        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
        });

        async function loadSettings() {
            try {
                const response = await fetch('api_petty_cash_settings.php');
                const result = await response.json();

                if (result.success) {
                    currentSettings = result.data;
                    populateForm(currentSettings);
                    updateCurrentValues(currentSettings);
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                showError('Failed to load settings');
            }
        }

        function populateForm(settings) {
            document.getElementById('initialFloat').value = settings.initial_float || '';
            document.getElementById('maxLimit').value = settings.max_limit || '';
            document.getElementById('replenishmentThreshold').value = settings.replenishment_threshold || '';
            document.getElementById('approvalThreshold').value = settings.approval_threshold || '';
            document.getElementById('dailyLimit').value = settings.daily_limit || '';
            document.getElementById('monthlyLimit').value = settings.monthly_limit || '';

            // Update system info
            if (settings.updated_at) {
                document.getElementById('lastUpdated').textContent = formatDateTime(settings.updated_at);
            } else {
                document.getElementById('lastUpdated').textContent = 'Not configured yet';
            }

            if (settings.updated_by) {
                document.getElementById('updatedBy').textContent = 'User #' + settings.updated_by;
            } else {
                document.getElementById('updatedBy').textContent = 'N/A';
            }
        }

        function updateCurrentValues(settings) {
            document.getElementById('currentInitialFloat').innerHTML = 
                `<strong>Current:</strong> ${formatCurrency(settings.initial_float || 0)}`;
            document.getElementById('currentMaxLimit').innerHTML = 
                `<strong>Current:</strong> ${settings.max_limit ? formatCurrency(settings.max_limit) : 'No limit'}`;
            document.getElementById('currentReplenishmentThreshold').innerHTML = 
                `<strong>Current:</strong> ${settings.replenishment_threshold ? formatCurrency(settings.replenishment_threshold) : 'Not set'}`;
            document.getElementById('currentApprovalThreshold').innerHTML = 
                `<strong>Current:</strong> ${formatCurrency(settings.approval_threshold || 0)}`;
            document.getElementById('currentDailyLimit').innerHTML = 
                `<strong>Current:</strong> ${settings.daily_limit ? formatCurrency(settings.daily_limit) : 'No limit'}`;
            document.getElementById('currentMonthlyLimit').innerHTML = 
                `<strong>Current:</strong> ${settings.monthly_limit ? formatCurrency(settings.monthly_limit) : 'No limit'}`;
        }

        async function saveSettings(event) {
            event.preventDefault();

            const data = {
                initial_float: parseFloat(document.getElementById('initialFloat').value),
                max_limit: document.getElementById('maxLimit').value ? 
                    parseFloat(document.getElementById('maxLimit').value) : null,
                replenishment_threshold: document.getElementById('replenishmentThreshold').value ? 
                    parseFloat(document.getElementById('replenishmentThreshold').value) : null,
                approval_threshold: parseFloat(document.getElementById('approvalThreshold').value),
                daily_limit: document.getElementById('dailyLimit').value ? 
                    parseFloat(document.getElementById('dailyLimit').value) : null,
                monthly_limit: document.getElementById('monthlyLimit').value ? 
                    parseFloat(document.getElementById('monthlyLimit').value) : null
            };

            // Validation
            if (data.initial_float <= 0) {
                showError('Initial float must be greater than 0');
                return;
            }

            if (data.approval_threshold <= 0) {
                showError('Approval threshold must be greater than 0');
                return;
            }

            if (data.max_limit && data.initial_float > data.max_limit) {
                showError('Initial float cannot exceed maximum limit');
                return;
            }

            if (data.replenishment_threshold && data.replenishment_threshold > data.initial_float) {
                showError('Replenishment threshold should be less than initial float');
                return;
            }

            try {
                const response = await fetch('api_petty_cash_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Settings saved successfully');
                    loadSettings();
                } else {
                    showError(result.error || 'Failed to save settings');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred while saving settings');
            }
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
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
