# Petty Cash Management Module

## Quick Start

### 1. Database Setup
Execute the SQL script to create the required table:
```bash
mysql -u [username] -p [database_name] < petty_cash_table.sql
```

### 2. Access the Module
- Login to the application
- Click on your user avatar in the header
- Select "Petty Cash" from the dropdown menu

### 3. Start Using
- Click **"Add Money"** to record cash replenishments
- Click **"Spend Money"** to record expenses
- View your current balance and transaction history
- Use filters and search to find specific transactions

## Features

### Dashboard Overview
- **Current Balance**: Real-time calculation of available petty cash
- **Total Money Added**: Sum of all cash replenishments
- **Total Money Spent**: Sum of all expenses
- **Transaction History**: Complete log of all petty cash activities

### Transaction Management
- **Add Money**: Record cash deposits/replenishments
- **Spend Money**: Record expenses and purchases
- **Edit**: Modify existing transactions with inline editing
- **Delete**: Remove transactions with confirmation

### Filtering & Search
- **Date Range**: Filter by specific time periods
- **Transaction Type**: View only additions or expenses
- **Search**: Real-time search across all transaction fields
- **Auto-update**: Charts and summaries update with filters

### Visual Analytics
- **Pie Chart**: Distribution of money added vs. money spent
- **Line Chart**: Monthly trend analysis of cash flow

## Files Included

```
petty_cash_table.sql              # Database schema
add_petty_cash.php                # API for create/update/delete
fetch_petty_cash.php              # API for retrieving transactions
delete_petty_cash.php             # API for deletion (standalone)
petty_cash.php                    # Main dashboard UI
header.php                        # Updated with navigation link

PETTY_CASH_IMPLEMENTATION.md      # Technical implementation guide
PETTY_CASH_UI_DESCRIPTION.md      # UI/UX documentation
PETTY_CASH_TEST_PLAN.md           # Comprehensive test plan (50 tests)
README_PETTY_CASH.md              # This file
```

## API Endpoints

### Fetch Transactions
```
GET /fetch_petty_cash.php
Query Parameters:
  - from: Start date (YYYY-MM-DD)
  - to: End date (YYYY-MM-DD)
  - type: Transaction type (credit/debit/all)
  - q: Search query
```

### Create Transaction
```
POST /add_petty_cash.php
Body: {
  "action": "create",
  "transaction_date": "YYYY-MM-DD",
  "description": "Purpose of transaction",
  "amount": "100.00",
  "transaction_type": "credit" | "debit",
  "payment_method": "CASH" | "BANK" | "MTN" | "OTHER" (optional),
  "reference": "Reference number" (optional)
}
```

### Update Transaction
```
POST /add_petty_cash.php
Body: {
  "action": "update",
  "id": 1,
  "transaction_date": "YYYY-MM-DD",
  "description": "Updated description",
  "amount": "150.00",
  "transaction_type": "credit" | "debit",
  "payment_method": "CASH",
  "reference": "REF-001"
}
```

### Delete Transaction
```
POST /add_petty_cash.php
Body: {
  "action": "delete",
  "id": 1
}
```

## Database Schema

### Table: petty_cash

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key, auto-increment |
| user_id | INT(11) | Foreign key to users table |
| transaction_date | DATE | Date of transaction |
| description | VARCHAR(500) | Purpose/description |
| amount | DECIMAL(10,2) | Transaction amount |
| transaction_type | ENUM('credit','debit') | Type of transaction |
| payment_method | VARCHAR(50) | Payment method (optional) |
| reference | VARCHAR(100) | Reference number (optional) |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

### Indexes
- Primary key on `id`
- Index on `user_id`
- Index on `transaction_date`
- Index on `transaction_type`
- Index on `created_at`
- Index on `amount`

## Security Features

### Authentication
- Session-based authentication required for all operations
- User ID automatically captured from session
- 5-minute inactivity timeout with warning

### Input Validation
- Amount must be numeric and positive
- Transaction type must be 'credit' or 'debit'
- Required field validation (date, description, amount, type)
- Date format validation

### SQL Injection Prevention
- Prepared statements with parameter binding
- No direct SQL query concatenation

### XSS Prevention
- HTML escaping on output
- Safe rendering of user-supplied data

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies
- **Backend**: PHP 7.4+, MySQL/MariaDB
- **Frontend**: Chart.js (loaded from CDN)
- **Fonts**: Inter font family (Google Fonts)

## Styling
The module uses a modern design system consistent with the rest of the application:
- Inter font family
- CSS custom properties for theming
- Responsive grid layout
- Card-based UI components
- Smooth animations and transitions

## Common Use Cases

### Scenario 1: Setting Up Initial Fund
1. Click "Add Money"
2. Enter amount (e.g., 5000)
3. Description: "Initial petty cash fund"
4. Payment Method: BANK
5. Save

### Scenario 2: Recording Office Expenses
1. Click "Spend Money"
2. Enter amount (e.g., 150)
3. Description: "Office supplies - printer paper"
4. Payment Method: CASH
5. Save

### Scenario 3: Monthly Reconciliation
1. Set date range to current month
2. Click "Apply"
3. Review all transactions
4. Check balance matches physical cash
5. Export or print if needed

### Scenario 4: Finding Specific Transaction
1. Use search box
2. Type reference number or description keyword
3. Results filter automatically

## Troubleshooting

### "Authentication required" error
- **Solution**: Clear cookies and login again

### Transactions not appearing
- **Solution**: Check filters, click "Apply" without any filters to show all

### Charts not displaying
- **Solution**: Ensure Chart.js CDN is accessible, check browser console for errors

### Database connection error
- **Solution**: Verify db.php credentials, ensure MySQL is running

### Foreign key constraint error
- **Solution**: Ensure users table exists and user_id is valid

## Performance Tips
- Archive old transactions periodically (e.g., annually)
- Use date range filters for large datasets
- Optimize MySQL if table grows beyond 10,000 records
- Consider adding database indexes on frequently searched fields

## Best Practices

### Data Entry
- Use clear, descriptive transaction descriptions
- Include reference numbers when available
- Enter transactions promptly for accuracy
- Regularly reconcile with physical cash

### Security
- Change session timeout if needed (in petty_cash.php)
- Regular database backups
- Limit user access via role-based permissions
- Monitor for unusual transaction patterns

### Maintenance
- Review and archive old data annually
- Test after PHP/MySQL upgrades
- Keep Chart.js library updated
- Monitor database size and performance

## Support & Documentation

For detailed information, see:
- **Implementation Guide**: `PETTY_CASH_IMPLEMENTATION.md`
- **UI Documentation**: `PETTY_CASH_UI_DESCRIPTION.md`
- **Test Plan**: `PETTY_CASH_TEST_PLAN.md` (50 test cases)

## License
This module is part of the DUNS application and follows the same license terms.

## Version
Version 1.0.0 - Initial Release (November 2025)

---

**Note**: This module requires the database table to be created before first use. Execute `petty_cash_table.sql` in your MySQL database to set up the required table structure.
