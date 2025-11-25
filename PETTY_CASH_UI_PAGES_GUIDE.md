# Petty Cash UI Pages - Implementation Guide

## Overview

This document describes the 5 new UI pages that complete the Petty Cash Management System, implementing the final 5% of functionality.

## Pages Implemented

### 1. Reconciliation Page (`petty_cash_reconciliation.php`)

**Purpose:** Daily/weekly reconciliation interface to ensure physical cash matches expected balance.

**Features:**
- Real-time balance calculations
- Expected vs actual balance comparison
- Automatic discrepancy detection and flagging
- Historical reconciliation records with filtering
- Modal-based reconciliation form
- Date range filters for searching past reconciliations

**Access:** Requires `reconcile` permission (Approver or Admin roles)

**Key Functions:**
- Create new reconciliation records
- View reconciliation history
- Flag discrepancies with variance amounts
- Add notes explaining differences

**API Integration:** Uses `api_petty_cash_reconciliation.php`

---

### 2. Replenishment Page (`petty_cash_replenishment.php`)

**Purpose:** Request and manage cash float replenishment when balance runs low.

**Features:**
- Current balance monitoring with stats
- Create new replenishment requests
- Multi-tab interface (Pending, Approved, Rejected, Completed)
- Approval workflow for managers
- Justification and expected spend tracking
- Date range filtering

**Access:** Requires `replenish` permission (Cashier, Approver, or Admin roles)

**Key Functions:**
- Submit replenishment requests with justification
- View request status and history
- Approve/reject pending requests (for Approvers)
- Track approved amounts

**API Integration:** Uses `api_petty_cash_replenishment.php`

---

### 3. Analytics Dashboard (`petty_cash_analytics.php`)

**Purpose:** Visual insights and spending analysis with interactive charts.

**Features:**
- **4 Chart Types:**
  - Category spending (doughnut chart)
  - Daily cash flow trends (line chart)
  - Monthly spending overview (bar chart)
  - Transaction summary table
- Date range filters with quick presets (7 days, 30 days, year)
- Top spending categories with percentages
- Real-time statistics cards
- Export-ready data views

**Access:** Requires `view` permission (All roles)

**Key Functions:**
- Visualize spending patterns
- Identify top expense categories
- Track cash flow trends
- Monitor monthly spending
- Generate period reports

**API Integration:** Uses `api_petty_cash_analytics.php`

**Charts Library:** Chart.js (loaded via CDN)

---

### 4. Role Management Page (`petty_cash_roles.php`)

**Purpose:** Manage user roles and permissions for the petty cash system.

**Features:**
- **4 Role Types:**
  - 👁️ **Viewer:** Read-only access to transactions and reports
  - 💼 **Cashier:** Create transactions, upload receipts, request replenishments
  - ✅ **Approver:** Approve transactions, perform reconciliations
  - 🔑 **Admin:** Full system access including settings and role management
- Detailed role descriptions with capability lists
- Permission matrix (11 permissions × 4 roles)
- Assign/remove roles with validation
- User role history tracking

**Access:** Requires `manage_roles` permission (Admin role only)

**Key Functions:**
- Assign roles to users by user ID
- Remove roles from users
- View all users with petty cash roles
- Understand permission matrix

**API Integration:** Uses `api_petty_cash_roles.php`

**Permission Matrix:**
| Permission | Viewer | Cashier | Approver | Admin |
|------------|--------|---------|----------|-------|
| View Transactions | ✓ | ✓ | ✓ | ✓ |
| Create Transactions | ✗ | ✓ | ✗ | ✓ |
| Edit Transactions | ✗ | ✓ | ✗ | ✓ |
| Delete Transactions | ✗ | ✗ | ✗ | ✓ |
| Approve Transactions | ✗ | ✗ | ✓ | ✓ |
| Reconciliation | ✗ | ✗ | ✓ | ✓ |
| Request Replenishment | ✗ | ✓ | ✓ | ✓ |
| Export Data | ✗ | ✓ | ✓ | ✓ |
| Manage Categories | ✗ | ✗ | ✗ | ✓ |
| Manage Settings | ✗ | ✗ | ✗ | ✓ |
| Manage Roles | ✗ | ✗ | ✗ | ✓ |

---

### 5. Settings Page (`petty_cash_settings.php`)

**Purpose:** Configure cash float parameters and system-wide settings.

**Features:**
- **Cash Float Configuration:**
  - Initial float amount
  - Maximum limit
  - Replenishment threshold
- **Approval & Control Settings:**
  - Approval threshold for transactions
  - Daily spending limit
  - Monthly spending limit
- Real-time validation
- Current vs new value comparison
- Configuration tips and warnings
- System information display

**Access:** Requires `manage_settings` permission (Admin role only)

**Key Functions:**
- Set initial cash float
- Configure replenishment alerts
- Define approval thresholds
- Set spending limits
- View configuration history

**API Integration:** Uses `api_petty_cash_settings.php`

---

## Technical Architecture

### Design Pattern
All pages follow a consistent pattern:
1. **Authentication:** Via `header.php` (redirects if not logged in)
2. **Authorization:** Via `petty_cash_rbac.php` (checks user permissions)
3. **UI Structure:** HTML with embedded CSS (Inter font, consistent color scheme)
4. **JavaScript:** Vanilla JS with async/await for API calls
5. **API Integration:** RESTful endpoints with JSON responses

### Security Features
- Session-based authentication check
- RBAC permission verification
- Defensive null checks for `$_SESSION['user_id']`
- Input validation on forms
- CSRF protection via session validation
- SQL injection prevention (APIs use PDO prepared statements)

### UI/UX Design
- **Color Scheme:**
  - Primary: `#4f46e5` (Indigo)
  - Success: `#10b981` (Green)
  - Danger: `#ef4444` (Red)
  - Warning: `#f59e0b` (Orange)
- **Typography:** Inter font family (Google Fonts)
- **Components:**
  - Cards with rounded corners and shadows
  - Modal dialogs for forms
  - Responsive tables
  - Badge components for status
  - Stats cards with colored borders
  - Tab navigation

### Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- Chart.js for analytics (loaded from CDN)

---

## User Workflows

### Daily Reconciliation Workflow
1. Navigate to **Reconciliation** page
2. Click **"+ New Reconciliation"**
3. Select date (defaults to today)
4. Enter physical cash count in "Actual Balance"
5. System calculates expected balance automatically
6. If discrepancy detected, warning appears
7. Add notes explaining variance
8. Submit reconciliation
9. Record appears in history table

### Replenishment Request Workflow
1. Navigate to **Replenishment** page
2. Check current balance in stats
3. Click **"+ New Request"**
4. Enter requested amount
5. Provide justification for need
6. Optionally add expected spend details
7. Submit request
8. **Approver reviews:**
   - Views request details
   - Approves or rejects with notes
9. Request status updates to approved/rejected

### Role Assignment Workflow
1. Navigate to **Role Management** page
2. Review role descriptions and permissions
3. Click **"+ Assign Role"**
4. Enter user ID
5. Select role from dropdown
6. Submit assignment
7. User appears in table with role badge
8. **To remove:** Click "Remove {role}" button for user

### Settings Configuration Workflow
1. Navigate to **Settings** page
2. Review current values in gray boxes
3. Update desired fields:
   - Initial Float Amount
   - Max Limit
   - Replenishment Threshold
   - Approval Threshold
   - Daily/Monthly Limits
4. System validates entries
5. Click **"💾 Save Settings"**
6. Settings saved and system updated

---

## API Endpoints Used

| Page | API Endpoint | Methods | Purpose |
|------|-------------|---------|---------|
| Reconciliation | `api_petty_cash_reconciliation.php` | GET, POST | CRUD operations for reconciliations |
| Replenishment | `api_petty_cash_replenishment.php` | GET, POST | Manage replenishment requests |
| Analytics | `api_petty_cash_analytics.php` | GET | Fetch analytics data (7 report types) |
| Roles | `api_petty_cash_roles.php` | GET, POST | User role management |
| Settings | `api_petty_cash_settings.php` | GET, POST | Float configuration |

All endpoints return JSON responses with `{success: boolean, data/error: ...}` format.

---

## Database Tables

No new tables were created. These pages use existing tables:
- `petty_cash` - Main transactions
- `petty_cash_reconciliation` - Reconciliation records
- `petty_cash_replenishment` - Replenishment requests
- `petty_cash_roles` - User role assignments
- `petty_cash_float_settings` - System settings
- `petty_cash_categories` - Expense categories
- `users` - User information

---

## Installation & Setup

These pages are ready to use immediately as they integrate with existing infrastructure:

1. **No database changes required** - all tables already exist
2. **No configuration needed** - uses existing API endpoints
3. **Access via URL:**
   - `/petty_cash_reconciliation.php`
   - `/petty_cash_replenishment.php`
   - `/petty_cash_analytics.php`
   - `/petty_cash_roles.php`
   - `/petty_cash_settings.php`

### Adding to Navigation Menu
To add these pages to the main navigation, edit `header.php` or your menu file:

```php
<a href="petty_cash_reconciliation.php">Reconciliation</a>
<a href="petty_cash_replenishment.php">Replenishment</a>
<a href="petty_cash_analytics.php">Analytics</a>
<a href="petty_cash_roles.php">Role Management</a>
<a href="petty_cash_settings.php">Settings</a>
```

---

## Testing Recommendations

### Manual Testing Checklist

**Reconciliation Page:**
- [ ] Create reconciliation with balanced amount
- [ ] Create reconciliation with discrepancy
- [ ] Filter by date range
- [ ] View reconciliation details
- [ ] Verify discrepancy alerts appear

**Replenishment Page:**
- [ ] Submit new request
- [ ] View requests in different tabs
- [ ] Approve a request (as Approver)
- [ ] Reject a request (as Approver)
- [ ] Filter by date range

**Analytics Page:**
- [ ] View all 4 charts
- [ ] Apply date range filters
- [ ] Test quick range buttons
- [ ] Verify chart legends
- [ ] Check data accuracy in tables

**Role Management Page:**
- [ ] Assign role to user
- [ ] Remove role from user
- [ ] View permission matrix
- [ ] Test with different user IDs
- [ ] Verify role badges display

**Settings Page:**
- [ ] Update float settings
- [ ] Test validation (negative values)
- [ ] Test validation (initial > max)
- [ ] Reset to current values
- [ ] Verify settings persist

### Permission Testing
Test each page with users having different roles:
- Viewer: Should only access Analytics
- Cashier: Should access Replenishment
- Approver: Should access Reconciliation, Replenishment
- Admin: Should access all pages

---

## Troubleshooting

### Common Issues

**Issue: "Permission Denied" error**
- **Cause:** User lacks required role
- **Solution:** Assign appropriate role via Role Management page

**Issue: Charts not loading in Analytics**
- **Cause:** Chart.js CDN blocked or slow
- **Solution:** Check internet connection, try different browser

**Issue: API returns empty data**
- **Cause:** No transactions in database
- **Solution:** Create test transactions via main petty cash page

**Issue: Session expired error**
- **Cause:** User logged out or session timeout
- **Solution:** Log in again via login page

---

## Future Enhancements

Potential improvements for future versions:
- Toast notifications instead of alerts
- Export functionality on all pages
- Bulk operations in reconciliation
- Email notifications for replenishment
- Advanced analytics with custom date ranges
- Mobile-responsive improvements
- Dark mode support

---

## Support & Documentation

**Related Documentation:**
- `PETTY_CASH_FINAL_SUMMARY.md` - Overall system summary
- `PETTY_CASH_MODULE_SUMMARY.md` - Module overview
- `PETTY_CASH_QUICK_REFERENCE.md` - Quick reference guide
- `PETTY_CASH_TEST_PLAN.md` - Comprehensive test cases

**For Issues:**
Contact system administrator or refer to main README.md

---

## Summary

The implementation of these 5 UI pages completes the Petty Cash Management System at 100%. All pages:
- ✅ Follow existing design patterns
- ✅ Integrate with existing APIs
- ✅ Include RBAC security
- ✅ Provide comprehensive functionality
- ✅ Are production-ready
- ✅ Require no database changes

**Total Implementation:**
- 5 new PHP pages
- ~2,250 lines of code
- 100% feature completion
- Full RBAC integration
- 0 database schema changes required
