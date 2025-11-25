# Feza Logistics Financial Management System - Enhancements Guide

## Overview
This document describes the comprehensive enhancements made to the Feza Logistics financial management platform to align with globally competitive financial systems.

## 🆕 New Features

### 1. Document Verification System

#### Features
- **Immutable Document Tracking**: All generated documents (invoices, receipts, quotations) are tracked with unique identifiers
- **QR Codes & Barcodes**: Each document includes a QR code and barcode for quick verification
- **Online Verification**: Public endpoint to verify document authenticity
- **Status Management**: Track document status (active, cancelled, void, revised)

#### Database Tables
- `document_verifications`: Stores verification data for all documents

#### Usage
```php
// Register a document
$docVerification = new DocumentVerification($pdo);
$result = $docVerification->registerDocument([
    'doc_type' => 'invoice',
    'doc_id' => 123,
    'doc_number' => 'INV-2025-001',
    'doc_amount' => 50000,
    'doc_currency' => 'RWF',
    'issue_date' => '2025-01-01',
    'issuer_user_id' => 1,
    'status' => 'active'
]);

// Verify a document
$verification = $docVerification->verifyDocument('invoice', 123, $hash);
```

#### Files
- `lib/DocumentVerification.php`: Document verification manager
- `verify_document.php`: Public verification page
- `migrations/017_create_document_verifications_table.sql`

---

### 2. Professional Document Templates

#### Features
- **Multiple Template Styles**: 5 professional templates (Classic, Modern, Elegant, Bold, Simple)
- **Per-Company Preferences**: Companies can choose their preferred templates
- **Customizable Colors & Fonts**: Each template has distinct color schemes and typography
- **Responsive Design**: Optimized for both screen viewing and PDF printing

#### Database Tables
- `document_templates`: Template configurations
- `company_template_preferences`: Company-specific template preferences

#### Usage
```php
$templateManager = new TemplateManager($pdo);
$template = $templateManager->getTemplate('invoice', $companyId);

// Get available templates
$templates = $templateManager->getAvailableTemplates('invoice');

// Set company preference
$templateManager->setCompanyPreference($companyId, 'invoice', $templateId);
```

#### Available Templates
1. **Classic Professional**: Traditional blue and gray, clean layout
2. **Modern Minimalist**: Indigo theme, contemporary design
3. **Elegant Corporate**: Purple accents, sophisticated look
4. **Bold Contemporary**: Red and amber, eye-catching design
5. **Clean Simple**: Minimalist grayscale, straightforward

#### Files
- `lib/TemplateManager.php`: Template management system
- `migrations/019_add_document_template_settings.sql`

---

### 3. Enhanced Email System

#### Features
- **Email Audit Logging**: All emails tracked in database
- **Branded Templates**: Professional HTML email templates
- **Attachment Support**: Documents sent as PDF attachments
- **CC/BCC Support**: Send copies to multiple recipients
- **Delivery Status Tracking**: Success/failure status logged

#### Database Tables
- `email_logs`: Complete audit trail of sent emails

#### Usage
Send documents via the existing `email_document.php` interface or programmatically:

```php
POST /email_document.php
{
    "doc_type": "invoice",
    "doc_id": 123,
    "recipient_email": "client@example.com",
    "recipient_name": "John Doe",
    "subject": "Invoice #123",
    "message": "Please find attached...",
    "cc_emails": "manager@company.com"
}
```

#### Files
- `email_document.php`: Enhanced with logging
- `migrations/018_create_email_logs_table.sql`

---

### 4. Security Dashboard

#### Features
- **Failed Login Monitoring**: Track all failed login attempts
- **Lockout Management**: View and manage locked accounts
- **Real-time Statistics**: Dashboard with key security metrics
- **IP Tracking**: Monitor login attempts by IP address
- **User Agent Logging**: Track devices used for login attempts
- **Admin Controls**: Unlock accounts, view attempt history

#### Statistics Displayed
- Total failed attempts
- Currently locked accounts
- Affected users
- Unique IP addresses
- Password vs OTP failures

#### Usage
Navigate to `/security_dashboard.php` (Admin/Super Admin only)

Filter by:
- Time period (24 hours, 7 days, 30 days, 90 days)
- Attempt type (password, OTP)
- Specific user

#### Files
- `security_dashboard.php`: Comprehensive security monitoring interface
- `admin_unlock_account.php`: Enhanced for account management

---

### 5. Notifications & Alerts System

#### Features
- **Automated Alerts**: Rule-based alerts for important events
- **In-App Notifications**: Real-time notifications in the system
- **Email/SMS Support**: Multi-channel notification delivery
- **Priority Levels**: Low, normal, high, urgent priorities
- **Notification Center**: Centralized notification management
- **User Preferences**: Control notification settings per category

#### Database Tables
- `notifications`: User and system notifications
- `alert_rules`: Automated alert rule configurations
- `notification_preferences`: User notification preferences

#### Built-in Alert Rules
1. **Overdue Invoices**: Alert when invoices are overdue by 7+ days
2. **Low Petty Cash Balance**: Alert when balance falls below threshold
3. **Pending Approvals**: Alert for approvals pending 2+ days
4. **Large Transactions**: Alert for transactions above threshold

#### Usage
```php
// Create notification
$notificationManager = new NotificationManager($pdo);
$result = $notificationManager->createNotification([
    'user_id' => 123,
    'type' => 'alert',
    'category' => 'invoice',
    'title' => 'Overdue Invoice',
    'message' => 'Invoice #123 is overdue',
    'priority' => 'high',
    'action_url' => 'create_invoice.php?id=123'
]);

// Check alert rules
$alerts = $notificationManager->checkAlertRules();

// Get user notifications
$notifications = $notificationManager->getUserNotifications($userId);
```

#### API Endpoints
- `GET /api_notifications.php?action=list`: Get notifications
- `GET /api_notifications.php?action=count`: Get unread count
- `POST /api_notifications.php?action=mark_read`: Mark as read
- `POST /api_notifications.php?action=mark_all_read`: Mark all as read
- `GET /api_notifications.php?action=check_alerts`: Trigger alert check (admin)

#### Files
- `lib/NotificationManager.php`: Notification management system
- `api_notifications.php`: API for notifications
- `migrations/021_create_notifications_system.sql`

---

### 6. Payment Scheduling System

#### Features
- **Installment Plans**: Break payments into multiple installments
- **Flexible Schedules**: Weekly, bi-weekly, monthly, quarterly, custom
- **Automatic Reminders**: Send payment reminders automatically
- **Status Tracking**: Monitor payment progress (active, completed, overdue)
- **Receipt Generation**: Auto-generate receipts upon payment
- **Partial Payment Support**: Track partial payments and remaining balance

#### Database Tables
- `payment_schedules`: Payment schedule configurations
- `payment_installments`: Individual installment records

#### Usage
```php
// Create payment schedule
INSERT INTO payment_schedules 
(invoice_id, total_amount, currency, frequency, start_date, number_of_payments, created_by)
VALUES (123, 100000, 'RWF', 'monthly', '2025-01-01', 4, 1);

// Create installments
INSERT INTO payment_installments
(schedule_id, installment_number, due_date, amount_due)
VALUES (1, 1, '2025-02-01', 25000);
```

#### Features in Schedule
- Total amount and currency
- Paid vs remaining amount (computed)
- Schedule type and frequency
- Auto-receipt generation flag
- Reminder settings

#### Files
- `migrations/020_create_payment_schedules_table.sql`

---

### 7. Receipt Attachments System

#### Features
- **Secure File Uploads**: Upload receipts, invoices, and documents
- **Multiple File Types**: Support for images, PDFs, Word, Excel
- **File Size Validation**: Maximum 10MB per file
- **Security**: MIME type validation, secure filename generation
- **Directory Protection**: .htaccess protection on upload directories
- **Attachment Tracking**: Count and flag entities with attachments

#### Database Tables
- `document_attachments`: File attachment records

#### Supported File Types
- Images: JPEG, PNG, GIF, WebP
- Documents: PDF, Word (DOC, DOCX), Excel (XLS, XLSX)

#### Usage
```php
$fileHandler = new FileUploadHandler($pdo);

// Upload file
$result = $fileHandler->uploadFile(
    $_FILES['file'],
    'petty_cash',    // entity type
    123,              // entity ID
    'receipt',        // file type
    $userId,          // uploader
    'Receipt for office supplies'  // description
);

// Get attachments
$attachments = $fileHandler->getAttachments('petty_cash', 123);

// Delete attachment
$fileHandler->deleteAttachment($attachmentId, $userId);
```

#### API Endpoints
- `POST /api_upload_attachment.php?action=upload`: Upload file
- `GET /api_upload_attachment.php?action=list&entity_type=X&entity_id=Y`: List attachments
- `POST /api_upload_attachment.php?action=delete`: Delete attachment

#### Directory Structure
```
uploads/
├── receipts/       # Receipt images
├── invoices/       # Invoice copies
├── petty_cash/     # Petty cash receipts
└── documents/      # General documents
```

#### Files
- `lib/FileUploadHandler.php`: Secure file upload handler
- `api_upload_attachment.php`: File upload API
- `migrations/022_add_receipt_attachments.sql`

---

## 🔧 Installation & Setup

### 1. Run Database Migrations

```bash
cd migrations

# Document verification
mysql -u username -p database_name < 017_create_document_verifications_table.sql

# Email logging
mysql -u username -p database_name < 018_create_email_logs_table.sql

# Document templates
mysql -u username -p database_name < 019_add_document_template_settings.sql

# Payment schedules
mysql -u username -p database_name < 020_create_payment_schedules_table.sql

# Notifications
mysql -u username -p database_name < 021_create_notifications_system.sql

# Attachments
mysql -u username -p database_name < 022_add_receipt_attachments.sql
```

### 2. Create Upload Directories

```bash
mkdir -p uploads/receipts uploads/invoices uploads/petty_cash uploads/documents
chmod 755 uploads
chmod 755 uploads/*
```

### 3. Configure Environment

Update `config.php` with any required settings:

```php
// Document verification secret
define('DOCUMENT_VERIFICATION_SECRET', 'your-secret-key-here');

// Upload limits
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
```

### 4. Set Up Automated Alerts (Optional)

Create a cron job to check alert rules:

```bash
# Check alerts every hour
0 * * * * php /path/to/duns/cron/check_alerts.php

# Or use the API endpoint
0 * * * * curl -X GET "https://yourdomain.com/api_notifications.php?action=check_alerts"
```

---

## 📊 Database Schema Overview

### New Tables Summary

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `document_verifications` | Track all generated documents | Immutable tracking, barcode/QR, status |
| `email_logs` | Audit trail for emails | Sender, recipients, attachments, status |
| `document_templates` | Template configurations | Multiple styles, per-company settings |
| `company_template_preferences` | Company template choices | Override default templates |
| `payment_schedules` | Payment plans | Installments, frequency, reminders |
| `payment_installments` | Individual payments | Due dates, amounts, status |
| `notifications` | User notifications | Types, priorities, actions |
| `alert_rules` | Automated alert config | Conditions, actions, frequency |
| `notification_preferences` | User settings | Email, SMS, in-app preferences |
| `document_attachments` | File uploads | Secure storage, MIME validation |

---

## 🔒 Security Enhancements

### Account Lockout (Already Implemented)
- Automatic lockout after 3 failed attempts within 24 hours
- Manual unlock by administrators
- Failed attempt tracking with IP and user agent
- 24-hour auto-unlock period

### Document Verification
- Secure hash generation for document verification
- Immutable document tracking
- Public verification endpoint (no sensitive data exposed)

### File Upload Security
- MIME type validation
- File size limits
- Secure filename generation
- Directory protection with .htaccess
- Upload directory isolation

### Database Security
- PDO prepared statements throughout
- Foreign key constraints
- Proper indexing for performance
- Audit logging on sensitive operations

---

## 📈 Performance Considerations

### Indexes Added
- Document verification lookups (composite indexes)
- Notification queries (user_id, is_read, created_at)
- Email log searches (user_id, doc_type, doc_id)
- Attachment queries (entity_type, entity_id)
- Payment schedule tracking (invoice_id, status)

### Optimization Tips
1. **Regular Cleanup**: Remove expired notifications periodically
2. **Archive Old Logs**: Archive email logs older than 1 year
3. **Monitor Upload Directory**: Clean up orphaned files
4. **Cache Templates**: Cache template configurations in memory
5. **Batch Alert Checks**: Run alert checks during off-peak hours

---

## 🧪 Testing

### Document Verification
1. Generate an invoice/receipt
2. Check `document_verifications` table for entry
3. Scan QR code or visit verification URL
4. Verify document details displayed correctly

### Email Logging
1. Send a document via email
2. Check `email_logs` table for entry
3. Verify all fields populated correctly
4. Check attachment size logged

### Notifications
1. Create overdue invoice (set due date in past)
2. Run alert check via API
3. Verify notification created
4. Check notification appears in UI

### File Uploads
1. Upload a receipt image
2. Verify file saved in correct directory
3. Check `document_attachments` table
4. Verify attachment count updated on entity

---

## 🚀 Future Enhancements

### Planned Features
- [ ] Notification center widget in main dashboard
- [ ] Payment schedule UI for creating installment plans
- [ ] Template selection UI for users
- [ ] Bulk document email capability
- [ ] SMS notification integration
- [ ] Mobile app push notifications
- [ ] Advanced reporting on payment schedules
- [ ] Document template editor
- [ ] Multi-language support for templates
- [ ] Integration with payment gateways

---

## 📞 Support & Troubleshooting

### Common Issues

**Problem**: Documents not appearing in verification system
- **Solution**: Check if migrations ran successfully, verify DocumentVerification class is instantiated

**Problem**: File uploads failing
- **Solution**: Check directory permissions (755 for uploads/), verify max upload size in php.ini

**Problem**: Notifications not triggering
- **Solution**: Ensure cron job is running, check alert_rules table has active rules

**Problem**: Email logs not being created
- **Solution**: Verify email_logs table exists, check PDO connection

### Debug Mode
Enable error reporting for troubleshooting:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Log Files
Check these locations:
- PHP error log: `/var/log/php/error.log`
- MySQL slow query log
- Application activity logs: `activity_logs` table

---

## 📝 Configuration Reference

### Environment Variables (Optional)
```bash
# Email configuration
MAIL_FROM_ADDRESS=no-reply@fezalogistics.com
MAIL_FROM_NAME=Feza Logistics

# Document verification
DOC_VERIFICATION_SECRET=your-secret-key

# File uploads
MAX_FILE_SIZE=10485760  # 10MB in bytes
UPLOAD_PATH=/path/to/uploads

# Notifications
ENABLE_SMS_NOTIFICATIONS=false
ENABLE_EMAIL_NOTIFICATIONS=true
```

### Database Configuration
All tables use `InnoDB` engine with `utf8mb4` charset for full Unicode support.

---

## 📄 License
This system is proprietary software for Feza Logistics Ltd.

## 👥 Contributors
- Development Team
- System Administrators
- Financial Operations Team

---

**Last Updated**: 2025-01-23
**Version**: 2.2.0
