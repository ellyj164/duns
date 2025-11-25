# RBAC Enhancements & Unified Document Template Guide

## Overview

This guide documents the comprehensive Role-Based Access Control (RBAC) enhancements and unified professional document templates implemented in the DUNS financial management system.

## Table of Contents

1. [RBAC System Enhancements](#rbac-system-enhancements)
2. [Database Migration](#database-migration)
3. [Permission Structure](#permission-structure)
4. [Role Definitions](#role-definitions)
5. [Implementation Details](#implementation-details)
6. [Unified Document Templates](#unified-document-templates)
7. [Testing Guide](#testing-guide)

## RBAC System Enhancements

### New Features

#### 1. Granular Permissions
- **New Permissions Added:**
  - `create-quotation` - Create new quotations
  - `edit-quotation` - Edit existing quotations
  - `delete-quotation` - Delete quotations
  - `view-quotation` - View quotations
  - `delete-transaction` - Delete transactions
  - `edit-transaction` - Edit transactions
  - `create-transaction` - Create new transactions
  - `export-data` - Export data and reports
  - `view-dashboard` - View dashboard and analytics

#### 2. New Viewer Role
- **Strictly Read-Only Access**
- Can view but cannot create, edit, or delete
- Permissions: `view-invoice`, `view-client`, `view-quotation`, `view-transactions`, `view-reports`, `view-dashboard`

#### 3. Backend Permission Enforcement
All critical endpoints now check permissions:
- Client Management: `insert_client.php`, `update_client.php`, `delete_client.php`
- Document Creation: `create_invoice.php`, `create_quotation.php`, `create_receipt.php`
- Transaction Management: `add_transaction.php`, `api_transactions.php`, `delete_transaction.php`

#### 4. Frontend UI Permission-Based Display
- User roles displayed in profile dropdown with visual badges
- Create/Edit/Delete buttons hidden based on permissions
- Navigation items conditionally displayed
- Role Management only visible to authorized users

## Database Migration

### Applying the Migration

```bash
mysql -u duns -p duns < migrations/010_add_viewer_role_and_permissions.sql
```

### What the Migration Does

1. **Adds New Permissions** to the `permissions` table
2. **Creates Viewer Role** in the `roles` table
3. **Assigns Permissions** to all roles appropriately
4. **Updates Existing Roles** with new permissions

### Migration File Location
`/migrations/010_add_viewer_role_and_permissions.sql`

## Permission Structure

### Permission Categories

#### Invoice Management
- `create-invoice` - Create new invoices
- `edit-invoice` - Edit existing invoices
- `delete-invoice` - Delete invoices
- `view-invoice` - View invoices

#### Client Management
- `create-client` - Create new client records
- `edit-client` - Edit existing client records
- `delete-client` - Delete client records
- `view-client` - View client records

#### Quotation Management (NEW)
- `create-quotation` - Create new quotations
- `edit-quotation` - Edit existing quotations
- `delete-quotation` - Delete quotations
- `view-quotation` - View quotations

#### Transaction Management (NEW)
- `create-transaction` - Create new transactions
- `edit-transaction` - Edit transactions
- `delete-transaction` - Delete transactions
- `view-transactions` - View transaction history

#### User Management
- `create-user` - Create new user accounts
- `edit-user` - Edit user accounts
- `delete-user` - Delete user accounts
- `view-user` - View user accounts

#### System & Reports
- `view-reports` - View financial reports and analytics
- `manage-settings` - Manage application settings
- `view-audit-logs` - View audit trail and activity logs
- `manage-roles` - Manage roles and permissions
- `export-data` - Export data and reports (NEW)
- `view-dashboard` - View dashboard and analytics (NEW)

#### Receipt Management
- `create-receipt` - Create payment receipts
- `edit-receipt` - Edit payment receipts

## Role Definitions

### 1. Super Admin (ID: 1)
**Access Level:** Full unrestricted access
**Permissions:** ALL permissions

**Use Case:** System administrators who need complete control

### 2. Admin (ID: 2)
**Access Level:** Full access with minor restrictions
**Permissions:** All permissions EXCEPT `delete-user` and `manage-roles`

**Use Case:** Trusted administrators who manage day-to-day operations

### 3. Accountant (ID: 3)
**Access Level:** Financial operations
**Permissions:**
- Invoice: create, edit, view
- Client: view
- Quotation: create, edit, view
- Transaction: create, edit, view
- Receipt: create, edit
- Reports: view
- Dashboard: view
- Export: allowed

**Use Case:** Financial staff who manage invoices, receipts, and financial reporting

### 4. Manager (ID: 4)
**Access Level:** View and manage clients
**Permissions:**
- Invoice: view
- Client: edit, view
- Quotation: view
- Transaction: view
- Reports: view
- Dashboard: view
- Export: allowed

**Use Case:** Managers who need oversight and client management capabilities

### 5. Viewer (ID: 5) - NEW
**Access Level:** Strictly read-only
**Permissions:**
- Invoice: view
- Client: view
- Quotation: view
- Transaction: view
- Reports: view
- Dashboard: view

**Use Case:** Auditors, consultants, or staff who only need to review data

## Implementation Details

### Backend Permission Checks

#### Example: Client Creation
```php
require_once 'rbac.php';

// Check permission to create clients
if (!userHasPermission($_SESSION['user_id'], 'create-client')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied.']);
    exit;
}
```

#### Example: Transaction API
```php
case 'delete': 
    if (!userHasPermission($_SESSION['user_id'], 'delete-transaction')) {
        send_json_response(['success' => false, 'error' => 'Access denied.'], 403);
        exit;
    }
    delete_transaction($pdo, $data); 
    break;
```

### Frontend Permission Checks

#### Example: Header Dropdown
```php
<?php if ($canCreateInvoice): ?>
    <a href="create_invoice.php">Create Invoice</a>
<?php endif; ?>
```

#### Example: Role Badge Display
```php
<div class="user-role">
    <?php foreach ($userRoles as $role): ?>
        <span class="role-badge"><?php echo htmlspecialchars($role['name']); ?></span>
    <?php endforeach; ?>
</div>
```

## Unified Document Templates

### Standard Document Features

#### 1. Professional Header
- Company logo (right-aligned)
- Company name: FEZA LOGISTICS LTD (bold, branded color)
- Complete contact information: address, TIN, phone, email, website
- Consistent across all documents

#### 2. Document Metadata
- Document type title (18-22px bold, centered)
- Auto-generated document number (e.g., INV-2025-00045)
- Issue date and due/expiry dates
- Client information (name, address, email)
- "Prepared by: Automated System" text

#### 3. Professional Styling
- **Fonts:** Arial (Calibri/Helvetica alternatives)
  - Title: 18px bold
  - Headers: 10-12px bold
  - Body: 9-10px regular
- **Colors:** 
  - Primary: #0071ce (Feza blue)
  - Text: #495057 (dark gray)
  - Borders: #dee2e6 (light gray)
- **Tables:** 
  - Text left-aligned
  - Numbers right-aligned
  - Auto-calculated totals
  - Alternating row colors for readability

#### 4. Standard Footer
- Generation timestamp: "Generated on [date] at [time]"
- Page numbers: "Page X of Y"
- System signature: "System Generated Document - No Signature Required"
- Thank you message

### Document-Specific Features

#### Invoice Template
- Line items table (Description, Qty, Unit Price, Total)
- Subtotal, Tax, Discount calculations
- Grand Total (bold)
- Payment terms and bank details
- "Prepared by: Automated System"

#### Receipt Template
- Receipt number and payment details
- Amount in figures and words
- Payment method and date
- Reference to original invoice
- **PAID watermark/stamp** (large, green, centered)
- "Prepared by: Automated System"

#### Quotation Template
- Validity period notice: "This quotation is valid for 30 days"
- Unit price table
- Terms & conditions section
- Expiry date prominently displayed
- "Prepared by: Automated System"

### Client Name Handling

#### Transaction Reports
- Reference field displays client names
- Fallback text: "No client reference" for empty values
- Graceful null handling with `??` operator
- Example: `${escapeHtml(tx.reference || 'No client reference')}`

## Testing Guide

### 1. Testing RBAC Permissions

#### Super Admin Testing
```
✓ Can access all features
✓ Can manage roles and permissions
✓ Can create, edit, delete all resources
```

#### Viewer Role Testing
```
✓ Can view all pages
✗ Cannot see Create/Edit/Delete buttons
✗ Cannot access creation endpoints (returns 403)
✗ Cannot modify any data
```

#### Accountant Role Testing
```
✓ Can create invoices, quotations, receipts
✓ Can edit financial records
✗ Cannot delete users
✗ Cannot manage roles
```

### 2. Testing Document Generation

#### Invoice Test
1. Create invoice with multiple line items
2. Generate PDF
3. Verify: header, client info, line items, totals, footer
4. Check: "Prepared by: Automated System" text

#### Receipt Test
1. Create receipt for an invoice
2. Generate PDF
3. Verify: PAID watermark is visible
4. Check: payment details, amounts, system signature

#### Quotation Test
1. Create quotation
2. Generate PDF
3. Verify: validity period notice
4. Check: expiry date, terms, professional layout

### 3. Testing Client Names in Transactions

#### Test Cases
1. **With Client Reference:**
   - Transaction with reference: "CROWN (C983)"
   - Expected: Displays "CROWN (C983)"

2. **Without Client Reference:**
   - Transaction with empty reference
   - Expected: Displays "No client reference"

3. **Null Reference:**
   - Transaction with NULL reference
   - Expected: Displays "No client reference"

## Security Considerations

### 1. Backend Enforcement is Mandatory
- Never rely solely on frontend hiding
- Always check permissions in backend
- Use `userHasPermission()` before any action

### 2. Session Security
- Ensure sessions are started properly
- Validate user authentication before permission checks
- Use HTTPS in production

### 3. SQL Injection Prevention
- All database queries use prepared statements
- Parameters are properly bound
- Never concatenate user input into queries

### 4. XSS Prevention
- All output is escaped using `htmlspecialchars()` or `escapeHtml()`
- User input is sanitized before display
- Special characters are properly encoded

## Troubleshooting

### Common Issues

#### 1. "Access Denied" Errors
**Cause:** User lacks required permission
**Solution:** 
- Verify user has correct role assigned in `user_roles` table
- Check role has required permission in `role_permissions` table
- Apply migration if Viewer role is missing

#### 2. Buttons Still Visible
**Cause:** Frontend permission checks not implemented
**Solution:**
- Ensure `rbac.php` is included in page
- Check permission variables are set correctly
- Verify conditional rendering logic

#### 3. Documents Missing Information
**Cause:** Null/empty fields not handled
**Solution:**
- Use `??` operator for fallbacks
- Check escapeHtml handles empty strings
- Add "N/A" or default text for missing data

#### 4. Migration Fails
**Cause:** Duplicate keys or missing dependencies
**Solution:**
- Check if permissions already exist
- Verify roles table has expected IDs
- Run migration in clean database state

## Best Practices

### 1. Permission Naming Convention
- Use lowercase with hyphens: `create-invoice`
- Format: `<action>-<resource>`
- Keep consistent across system

### 2. Role Assignment
- Assign minimal required permissions
- Use principle of least privilege
- Document role capabilities clearly

### 3. Code Organization
- Keep RBAC checks near top of files
- Use consistent error messages
- Log permission denials for security audit

### 4. Documentation
- Document all new permissions
- Update role descriptions when permissions change
- Maintain migration history

## Support and Maintenance

### Adding New Permissions

1. **Add to Database:**
   ```sql
   INSERT INTO permissions (name, description) 
   VALUES ('new-permission', 'Description of permission');
   ```

2. **Assign to Roles:**
   ```sql
   INSERT INTO role_permissions (role_id, permission_id)
   VALUES (1, (SELECT id FROM permissions WHERE name = 'new-permission'));
   ```

3. **Implement in Code:**
   ```php
   if (!userHasPermission($_SESSION['user_id'], 'new-permission')) {
       // Deny access
   }
   ```

4. **Update Frontend:**
   ```php
   <?php if (userHasPermission($_SESSION['user_id'], 'new-permission')): ?>
       <!-- Show UI element -->
   <?php endif; ?>
   ```

### Creating New Roles

1. Create role in database
2. Assign appropriate permissions
3. Test thoroughly with all features
4. Document role capabilities
5. Update this guide

## Conclusion

This RBAC enhancement provides:
- ✅ Granular permission control
- ✅ Strictly enforced read-only Viewer role
- ✅ Backend security enforcement
- ✅ User-friendly UI with role badges
- ✅ Professional unified document templates
- ✅ Graceful handling of missing data
- ✅ Comprehensive testing framework

For questions or issues, refer to the troubleshooting section or consult the development team.
