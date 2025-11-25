# Comprehensive Petty Cash Management System - Complete Guide

## 🎯 Overview

This comprehensive petty cash management system provides a complete solution for tracking, approving, reconciling, and reporting on petty cash transactions with advanced features including role-based access control, approval workflows, receipt management, and integration with accounting systems.

## 📦 What's Included

### Backend APIs (14 Endpoints)

1. **api_petty_cash_settings.php** - Float configuration management
2. **api_petty_cash_categories.php** - Expense category CRUD operations
3. **api_petty_cash_receipt_upload.php** - File upload and management
4. **api_petty_cash_approval.php** - Approval workflow engine
5. **api_petty_cash_reconciliation.php** - Reconciliation management
6. **api_petty_cash_replenishment.php** - Replenishment requests
7. **api_petty_cash_analytics.php** - 7 analytics report types
8. **api_petty_cash_export.php** - PDF/CSV/Excel exports
9. **api_petty_cash_roles.php** - Role and permission management
10. **api_petty_cash_integration.php** - System integration (expenses, ledger)
11. **add_petty_cash.php** - Enhanced CRUD with approval workflow
12. **fetch_petty_cash.php** - Enhanced data retrieval
13. **delete_petty_cash.php** - Delete endpoint
14. **petty_cash_rbac.php** - RBAC helper library

### Frontend Pages (5 Total)

1. **petty_cash.php** - Main dashboard (existing, to be enhanced)
2. **petty_cash_categories.php** - Category management interface
3. **petty_cash_approvals.php** - Approval workflow interface
4. **petty_cash_reconciliation.php** - Reconciliation page (to be created)
5. **petty_cash_replenishment.php** - Replenishment requests (to be created)

### Database Schema (8 Tables)

1. **petty_cash** - Enhanced main transactions table
2. **petty_cash_categories** - Expense categories
3. **petty_cash_float_settings** - Float configuration
4. **petty_cash_reconciliation** - Reconciliation records
5. **petty_cash_replenishment** - Replenishment requests
6. **petty_cash_roles** - User role assignments
7. **petty_cash_edit_history** - Audit trail
8. **petty_cash_receipts** - Receipt file metadata

## 🚀 Installation

### Step 1: Apply Database Migration

```bash
mysql -u [username] -p [database] < migrations/005_enhance_petty_cash_comprehensive.sql
```

This will:
- Enhance the petty_cash table with new columns
- Create 7 new supporting tables
- Insert 9 default expense categories
- Set up default float settings

### Step 2: Create Upload Directory

```bash
mkdir -p uploads/petty_cash_receipts
chmod 755 uploads/petty_cash_receipts
```

### Step 3: Verify PHP Files

```bash
php -l api_petty_cash_*.php
php -l petty_cash_*.php
```

### Step 4: Configure Permissions

Access the system and assign roles to users:
- Navigate to petty_cash_roles.php (when created)
- Or use the API directly to assign roles

Default roles available:
- **viewer** - Read-only access
- **cashier** - Create/edit transactions, request replenishments
- **approver** - Approve transactions, perform reconciliations
- **admin** - Full access including settings and roles

## 📋 Feature Details

### 1. Cash Float Setup

**Backend API:** `api_petty_cash_settings.php`

**Capabilities:**
- Set initial petty cash float (starting balance)
- Define maximum cash limit
- Set replenishment threshold (alert level)
- Configure approval threshold (amount requiring approval)
- Set daily and monthly spending limits

**API Usage:**
```javascript
// Get current settings
GET /api_petty_cash_settings.php

// Update settings (admin only)
POST /api_petty_cash_settings.php
{
  "initial_float": 100000.00,
  "max_limit": 500000.00,
  "replenishment_threshold": 50000.00,
  "approval_threshold": 50000.00,
  "daily_limit": 200000.00,
  "monthly_limit": 5000000.00
}
```

### 2. Enhanced Expense Recording

**Enhanced Fields in petty_cash table:**
- `category_id` - Link to expense category
- `beneficiary` - Person/entity receiving payment
- `purpose` - Detailed payment purpose
- `receipt_path` - Path to uploaded receipt
- `approval_status` - pending/approved/rejected
- `is_locked` - Locked after approval
- `notes` - Additional notes

**Validation Rules:**
- Amount must be positive
- Date required
- Description required
- Auto-check approval threshold
- Lock transactions after approval

### 3. Categories & Rules

**Backend API:** `api_petty_cash_categories.php`
**Frontend UI:** `petty_cash_categories.php`

**Default Categories:**
1. Fuel ⛽ - Max: 50,000
2. Office Supplies 📎 - Max: 20,000
3. Transport 🚗 - Max: 30,000
4. Maintenance 🔧 - Max: 100,000
5. Refreshments ☕ - Max: 15,000
6. Utilities 💡 - Max: 25,000
7. Communication 📞 - Max: 10,000
8. Cleaning 🧹 - Max: 20,000
9. Miscellaneous 📦 - Max: 50,000

**Features:**
- Custom categories with icons and colors
- Max amount per transaction validation
- Active/inactive status
- Full CRUD operations
- Category usage tracking (prevents deletion if in use)

### 4. Approval Workflow

**Backend API:** `api_petty_cash_approval.php`
**Frontend UI:** `petty_cash_approvals.php`

**Workflow:**
1. Transaction created
2. If amount > approval_threshold → status = 'pending'
3. Approver reviews and approves/rejects
4. Approved transactions are locked
5. Rejection requires a reason

**Features:**
- Configurable approval threshold
- Bulk approval capability
- Individual approval with notes
- Rejection with mandatory reason
- Transaction locking after approval
- Approval history tracking

**Dashboard Metrics:**
- Pending approval count
- Today's approvals
- Today's rejections
- Total pending amount

### 5. Receipt Upload

**Backend API:** `api_petty_cash_receipt_upload.php`

**Features:**
- Upload images (JPEG, PNG, GIF) or PDFs
- Max file size: 5MB
- Multiple receipts per transaction
- Secure file storage in uploads/petty_cash_receipts/
- Automatic unique filename generation
- File type validation
- Receipt deletion capability

**API Usage:**
```javascript
// Upload receipt
POST /api_petty_cash_receipt_upload.php
FormData: {
  transaction_id: 123,
  receipt: <file>
}

// Get receipts for transaction
GET /api_petty_cash_receipt_upload.php?transaction_id=123

// Delete receipt
DELETE /api_petty_cash_receipt_upload.php
{
  receipt_id: 456
}
```

### 6. Reconciliation

**Backend API:** `api_petty_cash_reconciliation.php`

**Features:**
- Daily/weekly/monthly reconciliation
- Expected balance auto-calculation
- Actual balance entry
- Automatic discrepancy detection
- Explanation for differences
- Status tracking (pending/resolved/escalated)
- Historical reconciliation records

**Workflow:**
1. Select reconciliation date
2. System calculates expected balance
3. Enter actual physical cash count
4. System calculates difference
5. Add explanation if discrepancy exists
6. Track status until resolved

### 7. Analytics & Dashboard

**Backend API:** `api_petty_cash_analytics.php`

**7 Report Types:**

1. **Summary** - Overall statistics
2. **Category Breakdown** - Spending by category
3. **Daily Usage** - Day-by-day analysis
4. **Weekly Usage** - Week-by-week trends
5. **Monthly Totals** - Monthly summary by category
6. **Top Spenders** - Top 10 users by spending
7. **Balance History** - Running balance over time

**API Usage:**
```javascript
// Get summary
GET /api_petty_cash_analytics.php?type=summary

// Category breakdown with date range
GET /api_petty_cash_analytics.php?type=category_breakdown&from=2025-01-01&to=2025-01-31

// Top spenders
GET /api_petty_cash_analytics.php?type=top_spenders
```

### 8. Audit Trail

**Features:**
- Edit history tracking (petty_cash_edit_history table)
- Activity logging integration
- Transaction locking system
- Who/when/what tracking for all operations
- Immutable approved transactions

**Tracked Events:**
- Transaction creation
- Transaction updates (with field-level changes)
- Transaction deletion
- Approval/rejection
- Role assignments
- Settings changes

### 9. Replenishment Module

**Backend API:** `api_petty_cash_replenishment.php`

**Workflow:**
1. User creates replenishment request
2. System captures current balance
3. Request pending approval
4. Approver reviews and approves/rejects
5. Upon completion, transaction can be auto-created
6. Request marked as completed

**Features:**
- Justification required
- Current balance auto-captured
- Approval workflow
- Optional auto-create credit transaction
- Printable replenishment report (PDF)

### 10. Export & Reports

**Backend API:** `api_petty_cash_export.php`

**Export Formats:**
- PDF (using FPDF)
- CSV
- Excel (CSV format)

**Report Types:**

1. **Petty Cash Ledger**
   - All transactions with running balance
   - Date range filtering
   - Includes category, reference
   - Totals row

2. **Reconciliation Summary**
   - All reconciliation records
   - Expected vs actual comparison
   - Discrepancy details
   - Explanations

3. **Monthly Category Summary**
   - Spending by month and category
   - Transaction counts
   - Sortable data

4. **Replenishment Report**
   - Individual request details
   - Professional format for accounting
   - Request number, dates, amounts
   - Approval information

**API Usage:**
```javascript
// Export ledger as PDF
GET /api_petty_cash_export.php?type=pdf&report=ledger&from=2025-01-01&to=2025-01-31

// Export reconciliation as CSV
GET /api_petty_cash_export.php?type=csv&report=reconciliation

// Export monthly summary
GET /api_petty_cash_export.php?report=monthly_category

// Export specific replenishment request
GET /api_petty_cash_export.php?type=pdf&report=replenishment&id=123
```

### 11. User Roles & Permissions

**Backend API:** `api_petty_cash_roles.php`
**RBAC Library:** `petty_cash_rbac.php`

**Roles and Permissions:**

| Permission | Viewer | Cashier | Approver | Admin |
|------------|--------|---------|----------|-------|
| View transactions | ✅ | ✅ | ✅ | ✅ |
| Create transactions | ❌ | ✅ | ❌ | ✅ |
| Edit transactions | ❌ | ✅ | ❌ | ✅ |
| Delete transactions | ❌ | ❌ | ❌ | ✅ |
| Approve transactions | ❌ | ❌ | ✅ | ✅ |
| Reconcile | ❌ | ❌ | ✅ | ✅ |
| Request replenishment | ❌ | ✅ | ✅ | ✅ |
| Export reports | ❌ | ✅ | ✅ | ✅ |
| Manage categories | ❌ | ❌ | ❌ | ✅ |
| Manage settings | ❌ | ❌ | ❌ | ✅ |
| Manage roles | ❌ | ❌ | ❌ | ✅ |

**API Usage:**
```javascript
// Get all users with roles
GET /api_petty_cash_roles.php

// Get roles for specific user
GET /api_petty_cash_roles.php?user_id=123

// Assign role
POST /api_petty_cash_roles.php
{
  "action": "assign",
  "user_id": 123,
  "role": "cashier"
}

// Remove role
POST /api_petty_cash_roles.php
{
  "action": "remove",
  "user_id": 123,
  "role": "cashier"
}

// Update all roles for user
POST /api_petty_cash_roles.php
{
  "action": "update",
  "user_id": 123,
  "roles": ["cashier", "approver"]
}

// Check permission
POST /api_petty_cash_roles.php
{
  "action": "check_permission",
  "user_id": 123,
  "permission": "approve"
}
```

### 12. Integration

**Backend API:** `api_petty_cash_integration.php`

**Integration Features:**

1. **Sync to Expenses**
   - Sync approved petty cash to main expenses table
   - Prevents duplicate syncing
   - Tracks sync status in notes field

2. **Post to Ledger**
   - Post transactions to general ledger
   - Create journal entries
   - Track posting status

3. **Link to Invoice**
   - Link petty cash transactions to invoices
   - Bi-directional reference

4. **Auto-Sync**
   - Automatically sync all approved, unsynced transactions
   - Scheduled execution capability

**API Usage:**
```javascript
// Sync specific transactions to expenses
POST /api_petty_cash_integration.php
{
  "action": "sync_to_expenses",
  "transaction_ids": [123, 124, 125]
}

// Sync all unsynced
POST /api_petty_cash_integration.php
{
  "action": "auto_sync"
}

// Post to ledger
POST /api_petty_cash_integration.php
{
  "action": "post_to_ledger",
  "transaction_ids": [123]
}

// Link to invoice
POST /api_petty_cash_integration.php
{
  "action": "link_to_invoice",
  "transaction_id": 123,
  "invoice_id": 456
}

// Get sync status
POST /api_petty_cash_integration.php
{
  "action": "get_sync_status",
  "transaction_ids": [123, 124]
}

// Get sync summary
GET /api_petty_cash_integration.php
```

## 🔒 Security Features

1. **Session-based Authentication** - All endpoints require valid session
2. **Role-Based Access Control** - Fine-grained permissions
3. **SQL Injection Prevention** - Prepared statements throughout
4. **XSS Prevention** - Output escaping
5. **File Upload Validation** - Type and size checks
6. **Transaction Locking** - Prevents editing approved transactions
7. **Audit Trail** - Complete history of all changes
8. **Input Validation** - Server-side validation of all inputs

## 🧪 Testing Checklist

### API Testing
- [ ] Test all 14 API endpoints
- [ ] Verify authentication checks
- [ ] Test RBAC permissions
- [ ] Test input validation
- [ ] Test error handling

### Frontend Testing
- [ ] Category management CRUD
- [ ] Approval workflow (approve/reject)
- [ ] Bulk operations
- [ ] Modal forms
- [ ] Data refresh

### Integration Testing
- [ ] Receipt upload and download
- [ ] PDF generation
- [ ] CSV export
- [ ] Sync to expenses
- [ ] Post to ledger

### Security Testing
- [ ] Unauthorized access attempts
- [ ] SQL injection attempts
- [ ] File upload malicious files
- [ ] Role escalation attempts

## 📊 Performance Considerations

1. **Database Indexes** - Added on frequently queried fields
2. **Query Optimization** - Efficient joins and aggregations
3. **Pagination** - Supported in all list endpoints
4. **File Size Limits** - 5MB max for receipts
5. **Auto-refresh** - 30-second intervals on approval page

## 🐛 Troubleshooting

### Database Errors
```bash
# Check if migration applied
mysql -u [username] -p [database] -e "SHOW TABLES LIKE 'petty_cash%'"

# Verify foreign keys
mysql -u [username] -p [database] -e "SHOW CREATE TABLE petty_cash"
```

### Permission Issues
```bash
# Check user roles
SELECT * FROM petty_cash_roles WHERE user_id = [user_id];

# Assign default role
INSERT INTO petty_cash_roles (user_id, role, assigned_by) VALUES ([user_id], 'cashier', [admin_id]);
```

### File Upload Issues
```bash
# Check directory permissions
ls -la uploads/petty_cash_receipts/

# Fix permissions
chmod 755 uploads/petty_cash_receipts/
```

## 📈 Future Enhancements

1. **Notifications** - Email/SMS for approval requests
2. **OCR Integration** - Automatic receipt scanning
3. **Mobile App** - Native mobile interface
4. **Advanced Analytics** - Predictive budgeting
5. **Scheduled Reports** - Automatic report generation
6. **API Webhooks** - Real-time integrations
7. **Multi-currency** - Support for multiple currencies

## 📞 Support

For issues or questions:
1. Check this comprehensive guide
2. Review API endpoint documentation
3. Check the test plan (PETTY_CASH_TEST_PLAN.md)
4. Contact development team

## 📝 Version History

**Version 2.0 (2025-11-23)**
- Complete rewrite with comprehensive features
- 14 API endpoints
- 8 database tables
- RBAC system
- Approval workflow
- Receipt management
- Analytics and reporting
- Export functionality
- Integration capabilities

**Version 1.0 (2025-11)**
- Basic petty cash tracking
- Simple CRUD operations
- Basic reporting

---

**Status:** ✅ Backend Complete | 🔨 Frontend In Progress | ⏳ Testing Pending
