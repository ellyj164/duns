<?php
session_start();
require_once 'db.php';
require_once 'rbac.php';
require_once 'settings.php';
require_once 'activity_logger.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if user has permission to manage settings
if (!userHasPermission($_SESSION['user_id'], 'manage-settings')) {
    $_SESSION['error_message'] = "Access denied. You don't have permission to manage settings.";
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyInfo = [
        'company_name' => $_POST['company_name'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'company_website' => $_POST['company_website'] ?? '',
        'company_tin' => $_POST['company_tin'] ?? '',
        'default_currency' => $_POST['default_currency'] ?? 'RWF',
        'tax_rate' => $_POST['tax_rate'] ?? '18',
        'logo_url' => $_POST['logo_url'] ?? ''
    ];
    
    if (updateCompanyInfo($companyInfo)) {
        $message = "Settings updated successfully!";
        logActivity($_SESSION['user_id'], 'update-settings', 'settings', null, $companyInfo);
    } else {
        $error = "Failed to update settings.";
    }
}

// Get current settings
$currentSettings = getCompanyInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0052cc;
            --primary-hover: #0041a3;
            --secondary-color: #f4f7f6;
            --text-color: #333;
            --border-color: #dee2e6;
            --white-color: #fff;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            padding: 20px;
            color: var(--text-color);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white-color);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85em;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .section:last-child {
            border-bottom: none;
        }

        .section h2 {
            color: var(--primary-color);
            font-size: 1.3em;
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>System Settings</h1>
        <p style="color: #666; margin-bottom: 30px;">Manage system-wide configuration and company information</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="section">
                <h2>Company Information</h2>
                
                <div class="form-group">
                    <label for="company_name">Company Name *</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($currentSettings['company_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="company_address">Company Address *</label>
                    <textarea id="company_address" name="company_address" required><?php echo htmlspecialchars($currentSettings['company_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="company_phone">Phone Number *</label>
                        <input type="text" id="company_phone" name="company_phone" 
                               value="<?php echo htmlspecialchars($currentSettings['company_phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="company_email">Email Address *</label>
                        <input type="email" id="company_email" name="company_email" 
                               value="<?php echo htmlspecialchars($currentSettings['company_email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="company_website">Website</label>
                        <input type="text" id="company_website" name="company_website" 
                               value="<?php echo htmlspecialchars($currentSettings['company_website'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="company_tin">TIN (Tax Identification Number) *</label>
                        <input type="text" id="company_tin" name="company_tin" 
                               value="<?php echo htmlspecialchars($currentSettings['company_tin'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Financial Settings</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="default_currency">Default Currency *</label>
                        <select id="default_currency" name="default_currency" required>
                            <option value="RWF" <?php echo ($currentSettings['default_currency'] ?? '') === 'RWF' ? 'selected' : ''; ?>>RWF - Rwandan Franc</option>
                            <option value="USD" <?php echo ($currentSettings['default_currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo ($currentSettings['default_currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                            <option value="GBP" <?php echo ($currentSettings['default_currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tax_rate">Tax Rate (%) *</label>
                        <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100"
                               value="<?php echo htmlspecialchars($currentSettings['tax_rate'] ?? '18'); ?>" required>
                        <small>Enter tax rate as a percentage (e.g., 18 for 18%)</small>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Branding</h2>
                
                <div class="form-group">
                    <label for="logo_url">Company Logo URL</label>
                    <input type="url" id="logo_url" name="logo_url" 
                           value="<?php echo htmlspecialchars($currentSettings['logo_url'] ?? ''); ?>">
                    <small>Enter the full URL to your company logo (e.g., https://example.com/logo.png)</small>
                </div>

                <?php if (!empty($currentSettings['logo_url'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Current Logo Preview:</strong><br>
                        <img src="<?php echo htmlspecialchars($currentSettings['logo_url']); ?>" 
                             alt="Company Logo" 
                             style="max-width: 200px; max-height: 100px; margin-top: 10px; border: 1px solid var(--border-color); padding: 10px; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</body>
</html>
