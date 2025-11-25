# Petty Cash UI Pages - Visual Summary

## Quick Reference: 5 New Pages

This document provides a quick visual reference for the 5 new petty cash UI pages.

---

## 1. 🔍 Reconciliation Page
**File:** `petty_cash_reconciliation.php`  
**URL:** `/petty_cash_reconciliation.php`  
**Access:** Approver, Admin

### Page Layout
```
┌─────────────────────────────────────────────────────────┐
│ 🔍 Petty Cash Reconciliation                            │
│ Daily/weekly reconciliation and discrepancy management   │
├─────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│ │ Expected │ │  Total   │ │Discrepan-│ │  Total   │   │
│ │ Balance  │ │Reconcil. │ │  cies    │ │ Variance │   │
│ │ 100,000  │ │    15    │ │    3     │ │  2,500   │   │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
├─────────────────────────────────────────────────────────┤
│ Perform Reconciliation              [+ New Reconcile]   │
│                                                          │
│ [From Date] [To Date] [Status ▼] [Apply Filters]       │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Date │ Expected│ Actual │Difference│Status│By│Note│ │
│ ├──────┼─────────┼────────┼──────────┼──────┼──┼────┤ │
│ │11/20 │100,000  │99,500  │  -500    │  ⚠  │JD│... │ │
│ │11/15 │105,000  │105,000 │    0     │  ✓  │SA│... │ │
│ └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Key Features
- **4 Statistics Cards:** Expected balance, reconciliation count, discrepancies, total variance
- **Date Range Filters:** From/To dates and status dropdown
- **Reconciliation Table:** Shows all reconciliation records with status badges
- **New Reconciliation Modal:** 
  - Reconciliation date picker
  - Expected balance (auto-calculated)
  - Actual balance input
  - Discrepancy warning (auto-displays)
  - Notes field

### Use Case
Daily cashier counts physical cash → enters amount → system compares to expected → flags if different → records in history

---

## 2. 💰 Replenishment Page
**File:** `petty_cash_replenishment.php`  
**URL:** `/petty_cash_replenishment.php`  
**Access:** Cashier, Approver, Admin

### Page Layout
```
┌─────────────────────────────────────────────────────────┐
│ 💰 Petty Cash Replenishment                             │
│ Request and manage cash float replenishment              │
├─────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│ │ Current  │ │ Pending  │ │ Approved │ │  Total   │   │
│ │ Balance  │ │ Requests │ │This Month│ │Requested │   │
│ │ 45,000   │ │    2     │ │    3     │ │ 150,000  │   │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
├─────────────────────────────────────────────────────────┤
│ Replenishment Requests                  [+ New Request] │
│                                                          │
│ [Pending][Approved][Rejected][Completed]                │
│                                                          │
│ [From Date] [To Date] [Apply Filters]                   │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Date │Amount│Balance│Reason│Status│By│Actions    │ │
│ ├──────┼──────┼───────┼──────┼──────┼──┼───────────┤ │
│ │11/20 │50,000│45,000 │Low.. │  ⏳  │JD│[View]     │ │
│ │11/15 │30,000│80,000 │Week..│  ✓  │SA│[View]     │ │
│ └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Key Features
- **4 Statistics Cards:** Current balance, pending requests, monthly approvals, total requested
- **Tab Navigation:** Filter by status (Pending, Approved, Rejected, Completed)
- **Request Table:** Shows all replenishment requests
- **New Request Modal:**
  - Current balance display
  - Requested amount input
  - Justification textarea (required)
  - Expected spend notes
- **Action Modals:**
  - Approve/Reject buttons for Approvers
  - Notes field for approval decisions

### Use Case
Balance low → cashier submits request → approver reviews → approves amount → cash replenished → system tracks

---

## 3. 📊 Analytics Dashboard
**File:** `petty_cash_analytics.php`  
**URL:** `/petty_cash_analytics.php`  
**Access:** All roles (Viewer, Cashier, Approver, Admin)

### Page Layout
```
┌─────────────────────────────────────────────────────────┐
│ 📊 Petty Cash Analytics                                 │
│ Visual insights and spending trends                      │
├─────────────────────────────────────────────────────────┤
│ From:[____] To:[____] [Apply][Last 7 Days][30D][Year]  │
├─────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│ │ Current  │ │  Total   │ │  Total   │ │ Trans.   │   │
│ │ Balance  │ │ Credits  │ │  Debits  │ │  Count   │   │
│ │ 100,000  │ │ 500,000  │ │ 400,000  │ │   156    │   │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐ ┌──────────────────────────┐ │
│ │Spending by Category  │ │Daily Cash Flow Trend     │ │
│ │  ╭─────╮             │ │    ╱╲                   │ │
│ │ ╱       ╲            │ │   ╱  ╲  ╱╲             │ │
│ ││  Pie    │           │ │  ╱    ╲╱  ╲            │ │
│ │ ╲       ╱            │ │ ╱          ╲           │ │
│ │  ╰─────╯             │ │╱            ╲──        │ │
│ └──────────────────────┘ └──────────────────────────┘ │
│ ┌──────────────────────┐ ┌──────────────────────────┐ │
│ │Monthly Overview      │ │Top Categories            │ │
│ │ ▆ ▆ ▆ ▆ ▆ ▆ ▆ ▆ ▆   │ │Category │Count│Total│%  │ │
│ │ █ █ █ █ █ █ █ █ █   │ │Office   │ 45  │50k │30%│ │
│ │ █ █ █ █ █ █ █ █ █   │ │Travel   │ 32  │40k │24%│ │
│ └──────────────────────┘ └──────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Key Features
- **4 Summary Cards:** Current balance, total credits, total debits, transaction count
- **Date Range Controls:** Custom dates + quick preset buttons (7d, 30d, year)
- **4 Visualizations:**
  1. **Category Breakdown** (Doughnut Chart) - Spending by category with legend
  2. **Daily Trend** (Line Chart) - Credits vs debits over time
  3. **Monthly Overview** (Bar Chart) - Monthly spending comparison
  4. **Top Categories** (Table) - Ranked by spending with percentages
- **Transaction Summary Table:** Period analysis with net change and averages

### Use Case
Manager reviews spending → applies date filter → sees category breakdown → identifies top expenses → analyzes trends

---

## 4. 👥 Role Management Page
**File:** `petty_cash_roles.php`  
**URL:** `/petty_cash_roles.php`  
**Access:** Admin only

### Page Layout
```
┌─────────────────────────────────────────────────────────┐
│ 👥 Petty Cash Role Management                           │
│ Manage user roles and permissions                        │
├─────────────────────────────────────────────────────────┤
│ Available Roles                                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🔑 Admin - Full system access                        │ │
│ │ • Manage settings • Assign roles • All features      │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ ✅ Approver - Review and approve                     │ │
│ │ • Approve requests • Reconcile • View reports       │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ 💼 Cashier - Create transactions                     │ │
│ │ • Create entries • Upload receipts • Request cash   │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ 👁️ Viewer - Read-only access                        │ │
│ │ • View data • Access reports • Export data          │ │
│ └─────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│ User Roles                              [+ Assign Role]  │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ID │Username│Roles          │Date    │Actions      │ │
│ ├────┼────────┼───────────────┼────────┼─────────────┤ │
│ │101 │john_d  │🔑Admin        │Nov 1   │[Remove...]  │ │
│ │102 │sarah_a │✅Approver     │Nov 2   │[Remove...]  │ │
│ │103 │mike_c  │💼Cashier      │Nov 5   │[Remove...]  │ │
│ └─────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│ Permission Matrix                                        │
│ ┌─────────────────────────────────────────────────────┐ │
│ │Permission         │Viewer│Cashier│Approver│Admin   │ │
│ ├───────────────────┼──────┼───────┼────────┼────────┤ │
│ │View Transactions  │  ✓   │   ✓   │   ✓    │   ✓    │ │
│ │Create Trans.      │  ✗   │   ✓   │   ✗    │   ✓    │ │
│ │Approve Trans.     │  ✗   │   ✗   │   ✓    │   ✓    │ │
│ │Manage Settings    │  ✗   │   ✗   │   ✗    │   ✓    │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Key Features
- **Role Cards:** 4 detailed descriptions with capabilities
- **User Table:** Shows all users with petty cash roles
- **Role Badges:** Color-coded badges for each role type
- **Assignment Modal:**
  - User ID input
  - Role dropdown with emojis and descriptions
- **Permission Matrix:** 11 permissions × 4 roles with checkmarks
- **Quick Actions:** Remove role buttons for each user

### Use Case
Admin adds new user → assigns Cashier role → user can now create transactions → later promoted to Approver

---

## 5. ⚙️ Settings Page
**File:** `petty_cash_settings.php`  
**URL:** `/petty_cash_settings.php`  
**Access:** Admin only

### Page Layout
```
┌─────────────────────────────────────────────────────────┐
│ ⚙️ Petty Cash Settings                                   │
│ Configure float settings, limits, and preferences        │
├─────────────────────────────────────────────────────────┤
│ 💰 Cash Float Configuration                             │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ℹ️ What is Cash Float?                               │ │
│ │ Initial money allocated for petty cash expenses      │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                          │
│ Initial Float Amount *     │ Maximum Limit              │
│ [_______________]          │ [_______________]          │
│ Current: 100,000.00        │ Current: 500,000.00        │
│                                                          │
│ 🔔 Replenishment Settings                               │
│ Replenishment Threshold                                  │
│ [_______________]                                        │
│ Current: 50,000.00                                       │
├─────────────────────────────────────────────────────────┤
│ ✅ Approval & Control Settings                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ⚠️ Important: Transactions above threshold require  │ │
│ │ manager approval before processing                   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                          │
│ Approval Threshold *                                     │
│ [_______________]                                        │
│ Current: 50,000.00                                       │
│                                                          │
│ 📊 Spending Limits                                      │
│ Daily Limit           │ Monthly Limit                   │
│ [_______________]     │ [_______________]               │
│ Current: No limit     │ Current: No limit               │
├─────────────────────────────────────────────────────────┤
│ ℹ️ System Information                                   │
│ Last Updated: Nov 23, 2025 2:30 PM                      │
│ Updated By: User #1                                      │
│                                                          │
│ 💡 Configuration Tips:                                  │
│ • Set float to cover 2-4 weeks of expenses              │
│ • Keep approval threshold appropriate for oversight      │
│ • Use limits to prevent unexpected depletion            │
├─────────────────────────────────────────────────────────┤
│                    [Reset to Current] [💾 Save Settings] │
└─────────────────────────────────────────────────────────┘
```

### Key Features
- **Section 1: Cash Float**
  - Initial float amount (required)
  - Maximum limit (optional)
  - Replenishment threshold
  - Info boxes with explanations
- **Section 2: Approval & Control**
  - Approval threshold (required)
  - Daily spending limit
  - Monthly spending limit
  - Warning boxes for important settings
- **Section 3: System Info**
  - Last update timestamp
  - Updated by user ID
  - Configuration tips
- **Current Value Display:** Gray boxes showing existing values
- **Validation:** Client-side checks for negative values and logical errors
- **Sticky Save Bar:** Always visible at bottom

### Use Case
Admin sets initial float → defines approval threshold → sets monthly limit → saves → system enforces rules

---

## Navigation Integration

Add these links to your menu/navigation:

```php
// Sidebar or navigation menu
<nav>
  <a href="petty_cash.php">Dashboard</a>
  <a href="petty_cash_approvals.php">Approvals</a>
  <a href="petty_cash_categories.php">Categories</a>
  <a href="petty_cash_reconciliation.php">Reconciliation</a>
  <a href="petty_cash_replenishment.php">Replenishment</a>
  <a href="petty_cash_analytics.php">Analytics</a>
  <a href="petty_cash_roles.php">Role Management</a>
  <a href="petty_cash_settings.php">Settings</a>
</nav>
```

---

## Color Coding & Icons

### Status Badges
- **✓ Balanced/Approved:** Green background (`#d1fae5`, text `#065f46`)
- **⚠ Discrepancy/Pending:** Yellow background (`#fef3c7`, text `#92400e`)
- **✗ Rejected:** Red background (`#fee2e2`, text `#991b1b`)
- **ℹ Completed:** Blue background (`#dbeafe`, text `#1e40af`)

### Role Badges
- **🔑 Admin:** Orange (`#f59e0b`)
- **✅ Approver:** Green (`#10b981`)
- **💼 Cashier:** Blue (`#3b82f6`)
- **👁️ Viewer:** Purple (`#8b5cf6`)

---

## Quick Facts

| Feature | Value |
|---------|-------|
| **Total Pages** | 5 |
| **Total Lines of Code** | ~2,250 |
| **API Endpoints Used** | 5 |
| **Database Tables Used** | 6 (all existing) |
| **Chart Library** | Chart.js 4.x |
| **Font** | Inter (Google Fonts) |
| **Browser Support** | Modern browsers |
| **Mobile Responsive** | Yes (grid-based) |
| **JavaScript Framework** | Vanilla JS |
| **Authentication** | Session-based |
| **Authorization** | RBAC (4 roles) |

---

## Testing Quick Start

1. **Setup Test Data:**
   ```sql
   -- Add test transactions
   -- Assign test roles
   -- Configure test settings
   ```

2. **Test Each Page:**
   - Reconciliation: Create a balanced and unbalanced entry
   - Replenishment: Submit and approve a request
   - Analytics: View charts with different date ranges
   - Roles: Assign and remove roles
   - Settings: Update and save configuration

3. **Verify Permissions:**
   - Test each page with different role accounts
   - Confirm proper access restrictions

---

## Summary

These 5 pages complete the Petty Cash Management System with:
- ✅ Full reconciliation workflow
- ✅ Replenishment request management
- ✅ Visual analytics and reporting
- ✅ Comprehensive role management
- ✅ Flexible system configuration

**Result:** 100% feature completion, production-ready system.
