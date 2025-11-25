<?php
/**
 * Email Document Feature
 * Allows sending generated documents directly to recipient's email
 */

session_start();
require_once 'db.php';
require_once 'fpdf/fpdf.php';
require_once 'lib/QRCodeGenerator.php';
require_once 'lib/BarcodeGenerator.php';

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Authentication required']));
}

$user_id = $_SESSION['user_id'];

// Get POST data
$doc_type = $_POST['doc_type'] ?? '';
$doc_id = intval($_POST['doc_id'] ?? 0);
$recipient_email = $_POST['recipient_email'] ?? '';
$recipient_name = $_POST['recipient_name'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$cc_emails = $_POST['cc_emails'] ?? '';

// Validate inputs
if (empty($doc_type) || $doc_id <= 0 || empty($recipient_email)) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

// Validate email
if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid recipient email address']));
}

try {
    // Generate PDF in memory
    ob_start();
    $_GET['type'] = $doc_type;
    $_GET['id'] = $doc_id;
    
    // Include and execute PDF generation
    include 'generate_pdf.php';
    $pdf_content = ob_get_clean();
    
    // Generate filename
    $filename = ucfirst($doc_type) . '-' . $doc_id . '-' . date('Ymd') . '.pdf';
    
    // Prepare email
    $sender_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $sender_email = $_SESSION['email'];
    
    // Default subject if not provided
    if (empty($subject)) {
        $subject = ucfirst($doc_type) . " #$doc_id from Feza Logistics";
    }
    
    // Default message if not provided
    if (empty($message)) {
        $message = "Dear $recipient_name,\n\nPlease find attached your " . strtolower($doc_type) . " (#$doc_id) from Feza Logistics.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n$sender_name\nFeza Logistics";
    }
    
    // Create HTML email body
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #0071ce 0%, #005a9e 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 30px;
            }
            .message {
                background: #f9fafb;
                border-left: 4px solid #0071ce;
                padding: 15px;
                margin: 20px 0;
                white-space: pre-wrap;
            }
            .document-info {
                background: #eff6ff;
                border-radius: 6px;
                padding: 15px;
                margin: 20px 0;
            }
            .document-info h3 {
                margin-top: 0;
                color: #0071ce;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .info-label {
                font-weight: bold;
                color: #4b5563;
            }
            .footer {
                background: #f9fafb;
                padding: 20px 30px;
                text-align: center;
                color: #6b7280;
                font-size: 12px;
                border-top: 1px solid #e5e7eb;
            }
            .footer p {
                margin: 5px 0;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: #0071ce;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📄 Document from Feza Logistics</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>$recipient_name</strong>,</p>
                
                <div class='message'>$message</div>
                
                <div class='document-info'>
                    <h3>Document Details</h3>
                    <div class='info-row'>
                        <span class='info-label'>Document Type:</span>
                        <span>" . ucfirst($doc_type) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Document ID:</span>
                        <span>#$doc_id</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Date:</span>
                        <span>" . date('F j, Y') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Sent by:</span>
                        <span>$sender_name</span>
                    </div>
                </div>
                
                <p><strong>Note:</strong> The document is attached to this email as a PDF file.</p>
                
                <p>If you have any questions or need assistance, please contact us at info@fezalogistics.com or call (+250) 788 616 117.</p>
            </div>
            <div class='footer'>
                <p><strong>Feza Logistics Ltd</strong></p>
                <p>KN 5 Rd, KG 16 AVe 31, Kigali International Airport, Rwanda</p>
                <p>TIN: 121933433 | Phone: (+250) 788 616 117</p>
                <p>Email: info@fezalogistics.com | Web: www.fezalogistics.com</p>
                <p style='margin-top: 15px; color: #9ca3af;'>This is an automated message from Feza Logistics Financial Management System.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $boundary = md5(time());
    $headers = "From: Feza Logistics <no-reply@fezalogistics.com>\r\n";
    $headers .= "Reply-To: $sender_email\r\n";
    
    // Add CC if provided
    if (!empty($cc_emails)) {
        $cc_list = array_map('trim', explode(',', $cc_emails));
        $valid_cc = array_filter($cc_list, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        if (!empty($valid_cc)) {
            $headers .= "Cc: " . implode(', ', $valid_cc) . "\r\n";
        }
    }
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    // Email body
    $email_body = "--{$boundary}\r\n";
    $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email_body .= $html_message . "\r\n\r\n";
    
    // Attach PDF
    $email_body .= "--{$boundary}\r\n";
    $email_body .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
    $email_body .= "Content-Transfer-Encoding: base64\r\n";
    $email_body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $email_body .= chunk_split(base64_encode($pdf_content)) . "\r\n";
    $email_body .= "--{$boundary}--";
    
    // Send email
    $mail_sent = mail($recipient_email, $subject, $email_body, $headers);
    
    // Log email attempt in database
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO email_logs 
            (user_id, doc_type, doc_id, recipient_email, recipient_name, cc_emails, 
             subject, message_body, attachment_name, attachment_size, email_type, 
             status, error_message, ip_address)
            VALUES 
            (:user_id, :doc_type, :doc_id, :recipient_email, :recipient_name, :cc_emails,
             :subject, :message_body, :attachment_name, :attachment_size, :email_type,
             :status, :error_message, :ip_address)
        ");
        
        $log_stmt->execute([
            ':user_id' => $user_id,
            ':doc_type' => $doc_type,
            ':doc_id' => $doc_id,
            ':recipient_email' => $recipient_email,
            ':recipient_name' => $recipient_name,
            ':cc_emails' => $cc_emails,
            ':subject' => $subject,
            ':message_body' => $message,
            ':attachment_name' => $filename,
            ':attachment_size' => strlen($pdf_content),
            ':email_type' => 'document',
            ':status' => $mail_sent ? 'sent' : 'failed',
            ':error_message' => $mail_sent ? null : 'Mail function returned false',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log email: " . $e->getMessage());
    }
    
    if ($mail_sent) {
        // Log activity
        try {
            if (file_exists(__DIR__ . '/activity_logger.php')) {
                require_once __DIR__ . '/activity_logger.php';
                logActivity($user_id, 'email-document', $doc_type, $doc_id, 
                           json_encode([
                               'recipient' => $recipient_email,
                               'subject' => $subject
                           ]));
            }
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log email activity: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Document sent successfully to ' . $recipient_email
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email. Please try again or contact support.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Email document error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending the document: ' . $e->getMessage()
    ]);
}
?>
