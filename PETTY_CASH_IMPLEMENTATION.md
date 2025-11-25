# Petty Cash Management Module Implementation Guide

## Overview
This document describes the implementation of the Petty Cash Management module for the DUNS application. The module allows users to track small operational expenses and cash replenishments.

## Files Created

### Database Schema
- **`petty_cash_table.sql`** - SQL script to create the `petty_cash` table
  - Fields: id, user_id, transaction_date, description, amount, transaction_type, payment_method, reference, created_at, updated_at
  - Indexes on: user_id, transaction_date, transaction_type, created_at, amount
  - Foreign key constraint to `users` table

### Backend API Files
- **`fetch_petty_cash.php`** - Retrieves petty cash transactions with filtering
  - Supports date range filters (from/to)
  - Supports transaction type filter (credit/debit)
  - Supports search across description, reference, payment method, dates, and amounts
  - Returns JSON response with transaction data

- **`add_petty_cash.php`** - Handles CRUD operations (create, update, delete)
  - Action: 'create' - Adds new petty cash transaction
  - Action: 'update' - Updates existing transaction
  - Action: 'delete' - Removes transaction
  - Requires authentication (session check)
  - Returns JSON response

- **`delete_petty_cash.php`** - Standalone delete endpoint
  - Alternative to using `add_petty_cash.php` with action='delete'
  - Requires transaction ID
  - Returns JSON response

### Frontend Interface
- **`petty_cash.php`** - Main dashboard for petty cash management
  - **Features:**
    - Current balance display (calculated as credits - debits)
    - Total money added and total money spent summaries
    - Transaction history table with inline editing
    - Add Money and Spend Money buttons
    - Collapsible form for adding/editing transactions
    - Date range and transaction type filters
    - Real-time search functionality
    - Visual charts (Pie chart for distribution, Line chart for monthly trends)
    - Responsive design matching the existing UI style
  
  - **User Interface:**
    - Modern design with Inter font
    - Color-coded transaction types (green for credits, red for debits)
    - Skeleton loaders during data fetch
    - Empty state messages
    - Inline row editing
    - Delete confirmation dialogs
    - Inactivity warning (5-minute timeout)

### Navigation Updates
- **`header.php`** - Updated to include "Petty Cash" link in the dropdown menu
  - Added after "Transactions" link
  - Accessible from any page in the application

## Database Setup

To create the petty cash table in your database, run:

```bash
mysql -u [username] -p [database_name] < petty_cash_table.sql
```

Or execute the SQL commands directly in phpMyAdmin or your MySQL client.

## Usage Instructions

### Adding Money (Replenishment)
1. Navigate to the Petty Cash page from the header dropdown menu
2. Click "Add Money" button
3. Fill in the form:
   - Date: Transaction date (defaults to today)
   - Amount: Amount to add
   - Description: Purpose of the money (required)
   - Payment Method: Optional (CASH, BANK, MTN, OTHER)
   - Reference: Optional reference number
4. Click "Save"

### Recording Expenses
1. Click "Spend Money" button
2. Fill in the form with expense details
3. Click "Save"

### Editing Transactions
1. Click the "Edit" button on any transaction row
2. Modify the values inline
3. Click "Save" to confirm or "Cancel" to discard changes

### Deleting Transactions
1. Click the "Delete" button on any transaction row
2. Confirm the deletion in the popup dialog

### Filtering Transactions
1. Use the date range filters to view transactions in a specific period
2. Select transaction type (All/Money Added/Money Spent)
3. Use the search box to find specific transactions
4. Click "Apply" to refresh the results

## Technical Details

### Authentication
All API endpoints check for valid session:
```php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}
```

### Database Connection
Uses the existing `db.php` file for PDO database connection:
```php
require_once 'db.php';
```

### Transaction Types
- **credit**: Money added/replenished
- **debit**: Money spent/expenses

### Balance Calculation
```javascript
const balance = totalCredit - totalDebit;
```

### API Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 11,
      "transaction_date": "2025-11-22",
      "description": "Office supplies",
      "amount": "150.00",
      "transaction_type": "debit",
      "payment_method": "CASH",
      "reference": "PC-001",
      "created_at": "2025-11-22 12:00:00",
      "updated_at": "2025-11-22 12:00:00"
    }
  ]
}
```

## Security Features
1. Session-based authentication
2. Prepared SQL statements to prevent SQL injection
3. Input validation on both frontend and backend
4. CSRF protection through session management
5. User-specific transactions linked to user_id

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design for mobile and tablet devices

## Dependencies
- PHP 7.4 or higher
- MySQL/MariaDB database
- Chart.js (loaded via CDN)
- Modern web browser with JavaScript enabled

## Styling
The module follows the existing design system:
- Inter font family
- CSS variables for theming
- Consistent color palette matching transactions.php
- Responsive grid layout
- Modern card-based UI

## Future Enhancements (Optional)
- Export to CSV/Excel functionality
- PDF report generation
- Category/tags for expenses
- Receipt attachments
- Approval workflow
- Multi-currency support
- Budget limits and alerts
- Recurring transactions

## Troubleshooting

### Database Connection Error
- Verify database credentials in `db.php`
- Ensure MySQL service is running
- Check that the `petty_cash` table exists

### Authentication Error
- Clear browser cache and cookies
- Log out and log back in
- Check session configuration in PHP

### Data Not Displaying
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Ensure proper file permissions

## Code Style
The implementation follows the existing codebase patterns:
- PHP files use session_start() and authentication checks
- JSON responses for API endpoints
- Modern JavaScript (ES6+) with async/await
- Consistent naming conventions
- Comprehensive error handling

## Maintenance
- Regular database backups recommended
- Monitor table size and consider archiving old transactions
- Review and optimize indexes if performance degrades
- Keep Chart.js library updated via CDN
