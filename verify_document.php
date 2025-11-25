<?php
/**
 * Document Verification Page
 * Allows users to verify the authenticity of generated documents
 */

require_once 'db.php';
require_once 'config.php';
require_once 'lib/DocumentVerification.php';

$doc_type = $_GET['type'] ?? '';
$doc_id = intval($_GET['id'] ?? 0);
$hash = $_GET['hash'] ?? '';
$barcode = $_GET['barcode'] ?? '';

$verification_result = null;
$document = null;
$error = null;

if ($doc_type && $doc_id) {
    try {
        // Use the new verification system
        $docVerification = new DocumentVerification($pdo);
        $result = $docVerification->verifyDocument($doc_type, $doc_id, $hash);
        
        if ($result['success']) {
            $document = $result['document'];
            $verification_result = $result['status'];
            
            // If hash was provided and matched
            if ($hash && $result['status'] === 'active') {
                $verification_result = 'verified';
            }
        } else {
            $verification_result = $result['status'] ?? 'error';
            $error = $result['message'] ?? $result['error'] ?? 'Verification failed';
        }
        
    } catch (Exception $e) {
        $error = 'System error: ' . $e->getMessage();
        $verification_result = 'error';
    }
} else {
    $error = 'Missing document information';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Verification - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .status {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .status.verified {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        
        .status.invalid {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        
        .status.warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
        }
        
        .status-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .status h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .status.verified h2 {
            color: #065f46;
        }
        
        .status.invalid h2 {
            color: #991b1b;
        }
        
        .status.warning h2 {
            color: #92400e;
        }
        
        .status p {
            color: #4b5563;
            line-height: 1.6;
        }
        
        .document-details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .document-details h3 {
            font-size: 1.125rem;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }
        
        .detail-value {
            color: #1f2937;
            text-align: right;
        }
        
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 30px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <div class="logo-icon">🔐</div>
                <h1>Feza Logistics</h1>
                <p>Document Verification System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="status invalid">
                    <div class="status-icon">❌</div>
                    <h2>Verification Failed</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif ($verification_result === 'verified'): ?>
                <div class="status verified">
                    <div class="status-icon">✅</div>
                    <h2>Document Verified</h2>
                    <p>This document is authentic and issued by Feza Logistics.</p>
                </div>
                
                <?php if ($document): ?>
                    <div class="document-details">
                        <h3>Document Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Document Type:</span>
                            <span class="detail-value"><?php echo strtoupper(htmlspecialchars($doc_type)); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Document ID:</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($doc_id); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Client Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($document['client_name']); ?></span>
                        </div>
                        <?php if (!empty($document['tin'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">TIN:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($document['tin']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($document['currency'] . ' ' . number_format($document['amount'], 2)); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date Issued:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($document['date_issued'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value"><?php echo ucfirst(htmlspecialchars($document['status'])); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif ($verification_result === 'invalid_hash'): ?>
                <div class="status invalid">
                    <div class="status-icon">⚠️</div>
                    <h2>Invalid Verification Hash</h2>
                    <p>The verification hash does not match. This document may have been tampered with.</p>
                </div>
            <?php elseif ($verification_result === 'valid_no_hash'): ?>
                <div class="status warning">
                    <div class="status-icon">⚠️</div>
                    <h2>Document Found (No Hash Verification)</h2>
                    <p>The document exists in our system but no verification hash was provided.</p>
                </div>
                
                <?php if ($document): ?>
                    <div class="document-details">
                        <h3>Document Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Document Type:</span>
                            <span class="detail-value"><?php echo strtoupper(htmlspecialchars($doc_type)); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Document ID:</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($doc_id); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Client Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($document['client_name']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif ($verification_result === 'not_found'): ?>
                <div class="status invalid">
                    <div class="status-icon">❌</div>
                    <h2>Document Not Found</h2>
                    <p>No document with this ID was found in our system.</p>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>© 2025 Feza Logistics. All rights reserved.</p>
                <p><a href="index.php">Return to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
