# Petty Cash Management Module - Test Plan

## Pre-requisites
1. MySQL/MariaDB database server running
2. Database `duns` exists with proper credentials
3. Table `users` exists (for foreign key constraint)
4. Web server (Apache/Nginx) configured to serve PHP files
5. PHP 7.4 or higher installed
6. User account created and logged in

## Database Setup Tests

### Test 1: Create Database Table
**Steps:**
```sql
-- Execute the SQL script
mysql -u duns -p duns < petty_cash_table.sql

-- Verify table creation
SHOW CREATE TABLE petty_cash;
DESCRIBE petty_cash;
```

**Expected Results:**
- Table `petty_cash` created successfully
- All columns exist with correct data types
- Indexes created: idx_user_id, idx_transaction_date, idx_transaction_type, idx_created_at, idx_amount
- Foreign key constraint to `users` table exists
- Default values set correctly (current_timestamp for created_at/updated_at)

### Test 2: Foreign Key Constraint
**Steps:**
```sql
-- Try to insert with invalid user_id
INSERT INTO petty_cash (user_id, transaction_date, description, amount, transaction_type) 
VALUES (999999, CURDATE(), 'Test', 100.00, 'credit');
```

**Expected Results:**
- Error: "Cannot add or update a child row: a foreign key constraint fails"
- Ensures data integrity

## Backend API Tests

### Test 3: Authentication Check
**Steps:**
1. Clear browser cookies/session
2. Try to access: `fetch_petty_cash.php`

**Expected Results:**
- HTTP Status: 401 Unauthorized
- JSON response: `{"success": false, "error": "Authentication required."}`

### Test 4: Fetch Transactions (Empty State)
**Steps:**
1. Login as valid user
2. Access: `fetch_petty_cash.php`

**Expected Results:**
- HTTP Status: 200 OK
- JSON response: `{"success": true, "data": []}`

### Test 5: Create Transaction (Credit)
**Steps:**
```javascript
POST /add_petty_cash.php
Content-Type: application/json

{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Initial petty cash fund",
  "amount": "1000.00",
  "transaction_type": "credit",
  "payment_method": "CASH",
  "reference": "PC-001"
}
```

**Expected Results:**
- HTTP Status: 200 OK
- JSON response includes: `{"success": true, "message": "Transaction created successfully.", "id": <new_id>}`
- Database record created

### Test 6: Create Transaction (Debit)
**Steps:**
```javascript
POST /add_petty_cash.php
Content-Type: application/json

{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Office supplies purchase",
  "amount": "150.50",
  "transaction_type": "debit",
  "payment_method": "CASH",
  "reference": null
}
```

**Expected Results:**
- HTTP Status: 200 OK
- Transaction created with null reference

### Test 7: Validation - Negative Amount
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Test",
  "amount": "-100.00",
  "transaction_type": "credit"
}
```

**Expected Results:**
- HTTP Status: 400 Bad Request
- JSON response: `{"success": false, "error": "Amount must be a positive number."}`

### Test 8: Validation - Invalid Transaction Type
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Test",
  "amount": "100.00",
  "transaction_type": "invalid"
}
```

**Expected Results:**
- HTTP Status: 400 Bad Request
- JSON response: `{"success": false, "error": "Invalid transaction type. Must be credit or debit."}`

### Test 9: Validation - Non-numeric Amount
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Test",
  "amount": "abc",
  "transaction_type": "credit"
}
```

**Expected Results:**
- HTTP Status: 400 Bad Request
- JSON response: `{"success": false, "error": "Amount must be a positive number."}`

### Test 10: Update Transaction
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "update",
  "id": 1,
  "transaction_date": "2025-11-23",
  "description": "Updated description",
  "amount": "200.00",
  "transaction_type": "credit",
  "payment_method": "BANK",
  "reference": "PC-001-UPD"
}
```

**Expected Results:**
- HTTP Status: 200 OK
- JSON response: `{"success": true, "message": "Transaction updated successfully."}`
- Database record updated

### Test 11: Delete Transaction
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "delete",
  "id": 1
}
```

**Expected Results:**
- HTTP Status: 200 OK
- JSON response: `{"success": true, "message": "Transaction deleted successfully."}`
- Database record removed

### Test 12: Delete Non-existent Transaction
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "delete",
  "id": 999999
}
```

**Expected Results:**
- HTTP Status: 404 Not Found
- JSON response: `{"success": false, "error": "Transaction not found."}`

### Test 13: Fetch with Date Filter
**Steps:**
1. Create transactions with different dates
2. Access: `fetch_petty_cash.php?from=2025-11-01&to=2025-11-30`

**Expected Results:**
- Only transactions within date range returned
- Correct JSON structure

### Test 14: Fetch with Type Filter
**Steps:**
1. Create both credit and debit transactions
2. Access: `fetch_petty_cash.php?type=credit`

**Expected Results:**
- Only credit transactions returned

### Test 15: Fetch with Search
**Steps:**
1. Create transactions with various descriptions
2. Access: `fetch_petty_cash.php?q=office`

**Expected Results:**
- Only transactions matching search term returned
- Search covers description, reference, payment_method, dates, and amounts

## Frontend UI Tests

### Test 16: Page Load
**Steps:**
1. Login as valid user
2. Navigate to `petty_cash.php`

**Expected Results:**
- Page loads without errors
- Header displays "Petty Cash Management"
- "Add Money" and "Spend Money" buttons visible
- Summary cards show "—" (no data yet)
- Charts render empty or with placeholder
- Table shows empty state message
- No console errors

### Test 17: Navigation Link
**Steps:**
1. Click on user avatar in header
2. Verify "Petty Cash" link appears in dropdown
3. Click "Petty Cash" link

**Expected Results:**
- Link appears in dropdown menu after "Transactions"
- Clicking navigates to `petty_cash.php`

### Test 18: Add Money - UI Flow
**Steps:**
1. Click "Add Money" button
2. Form should slide down
3. Fill in all fields:
   - Date: Today
   - Amount: 1000
   - Description: "Initial fund"
   - Payment Method: CASH
   - Reference: PC-001
4. Click "Save"

**Expected Results:**
- Form slides down smoothly
- Form title shows "Add Money (Replenish)"
- "Add Money" button hides
- Date defaults to today
- All fields editable
- Save button creates transaction
- Form slides up after save
- Balance updates to +1000.00
- New row appears in table
- Charts update

### Test 19: Spend Money - UI Flow
**Steps:**
1. Click "Spend Money" button
2. Fill in fields:
   - Date: Today
   - Amount: 150.50
   - Description: "Office supplies"
   - Payment Method: CASH
3. Click "Save"

**Expected Results:**
- Form slides down
- Form title shows "Spend Money (Expense)"
- "Spend Money" button hides
- Transaction created
- Balance updates to +849.50 (1000 - 150.50)
- Total Spent shows 150.50
- Charts update with new data

### Test 20: Cancel Add/Edit
**Steps:**
1. Click "Add Money"
2. Start filling form
3. Click "Cancel"

**Expected Results:**
- Form slides up
- No data saved
- Both buttons reappear

### Test 21: Inline Edit
**Steps:**
1. Create a transaction
2. Click "Edit" button on table row
3. Row background changes to yellow
4. Modify description
5. Click "Save"

**Expected Results:**
- Row enters edit mode
- All fields become editable
- Changes save successfully
- Row returns to normal state
- Updated data displayed

### Test 22: Inline Edit Cancel
**Steps:**
1. Click "Edit" on a row
2. Make changes
3. Click "Cancel"

**Expected Results:**
- Row returns to original state
- No changes saved
- Original data displayed

### Test 23: Delete Transaction
**Steps:**
1. Click "Delete" button on a row
2. Confirm deletion dialog appears
3. Click "OK"

**Expected Results:**
- Confirmation dialog appears
- Row deleted from table
- Balance recalculated
- Charts update

### Test 24: Delete Cancel
**Steps:**
1. Click "Delete"
2. Click "Cancel" in confirmation dialog

**Expected Results:**
- Transaction not deleted
- Row remains in table

### Test 25: Date Range Filter
**Steps:**
1. Create transactions across multiple dates
2. Set "From" date to 2025-11-01
3. Set "To" date to 2025-11-15
4. Click "Apply"

**Expected Results:**
- Only transactions in range displayed
- Summary cards recalculate for filtered data
- Charts update to show filtered data
- Transaction count updates

### Test 26: Type Filter
**Steps:**
1. Create mix of credit and debit transactions
2. Select "Money Added" from type filter
3. Click "Apply"

**Expected Results:**
- Only credit transactions displayed
- Summary updates accordingly

### Test 27: Search Functionality
**Steps:**
1. Create multiple transactions with different descriptions
2. Type "office" in search box
3. Wait for debounced search (400ms)

**Expected Results:**
- Search triggers automatically
- Only matching transactions displayed
- Search works across description, reference, payment method, and amounts

### Test 28: Clear Filters
**Steps:**
1. Apply various filters
2. Clear all filter fields
3. Click "Apply"

**Expected Results:**
- All transactions displayed
- Summary shows full totals

### Test 29: Balance Calculation
**Steps:**
1. Add multiple credit transactions (e.g., 1000, 500)
2. Add multiple debit transactions (e.g., 200, 150)

**Expected Results:**
- Current Balance = 1150 (1500 - 350)
- Total Money Added = 1500
- Total Money Spent = 350
- Balance is green (positive)

### Test 30: Negative Balance Display
**Steps:**
1. Create debit greater than credit
2. Check balance display

**Expected Results:**
- Balance shows negative number
- Balance is red (negative)

### Test 31: Charts - Pie Chart
**Steps:**
1. Create transactions
2. Verify pie chart

**Expected Results:**
- Pie chart shows two segments
- Green for Money Added
- Red for Money Spent
- Proportions match actual data
- Legend displays correctly

### Test 32: Charts - Line Chart
**Steps:**
1. Create transactions across multiple months
2. Verify line chart

**Expected Results:**
- X-axis shows months (YYYY-MM)
- Two lines: green (added) and red (spent)
- Data points match transaction amounts per month

### Test 33: Empty State
**Steps:**
1. Clear all transactions or use fresh database
2. Load page

**Expected Results:**
- Empty state message displays
- Icon and descriptive text shown
- "No Transactions Found" heading
- Helpful message to start adding data

### Test 34: Skeleton Loader
**Steps:**
1. Reload page
2. Observe table during data fetch

**Expected Results:**
- Skeleton rows appear immediately
- Animated pulse effect
- Replaced by actual data when loaded

### Test 35: Responsive Design - Mobile
**Steps:**
1. Resize browser to mobile width (<768px)
2. Check all elements

**Expected Results:**
- Summary cards stack vertically
- Charts stack vertically
- Table scrolls horizontally
- Forms remain usable
- Buttons remain accessible

### Test 36: Responsive Design - Tablet
**Steps:**
1. Resize to tablet width (768-1199px)
2. Check layout

**Expected Results:**
- Summary cards in row
- Charts may adjust or stack
- Table scrolls if needed

### Test 37: Session Timeout Warning
**Steps:**
1. Stay idle for 4 minutes
2. Warning should appear

**Expected Results:**
- Warning banner appears at top
- Message: "You will be logged out in 1 minute..."
- "Stay Logged In" button present

### Test 38: Session Timeout - Auto Logout
**Steps:**
1. Stay idle for 5 minutes
2. Do not click "Stay Logged In"

**Expected Results:**
- User redirected to logout.php
- Session cleared
- Must login again

### Test 39: Session Reset on Activity
**Steps:**
1. Wait 4 minutes (warning appears)
2. Click "Stay Logged In"

**Expected Results:**
- Warning disappears
- Timer resets
- User remains logged in

### Test 40: Multiple Session Starts
**Steps:**
1. Navigate between pages
2. Check console for session errors

**Expected Results:**
- No "session already started" warnings
- Session handling uses conditional check

## Performance Tests

### Test 41: Large Dataset
**Steps:**
1. Create 100+ transactions
2. Load page

**Expected Results:**
- Page loads in reasonable time (<3 seconds)
- Table renders smoothly
- Charts display correctly
- No browser lag

### Test 42: Search Performance
**Steps:**
1. Have 100+ transactions
2. Type quickly in search box

**Expected Results:**
- Debounce prevents excessive API calls
- Search completes in reasonable time
- UI remains responsive

## Security Tests

### Test 43: SQL Injection
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "create",
  "description": "'; DROP TABLE petty_cash; --",
  "amount": "100",
  "transaction_type": "credit",
  "transaction_date": "2025-11-22"
}
```

**Expected Results:**
- No SQL injection occurs
- Transaction created with literal string
- Table remains intact

### Test 44: XSS in Description
**Steps:**
```javascript
POST /add_petty_cash.php
{
  "action": "create",
  "description": "<script>alert('XSS')</script>",
  "amount": "100",
  "transaction_type": "credit",
  "transaction_date": "2025-11-22"
}
```

**Expected Results:**
- HTML escaped on display
- No script execution
- Safe rendering in table

### Test 45: CSRF Protection
**Steps:**
1. Logout
2. Try to make API call without session

**Expected Results:**
- 401 Unauthorized
- Action not performed

## Browser Compatibility Tests

### Test 46: Chrome/Edge
- Load page in latest Chrome/Edge
- Test all features
- Expected: Full functionality

### Test 47: Firefox
- Load page in latest Firefox
- Test all features
- Expected: Full functionality

### Test 48: Safari
- Load page in latest Safari
- Test all features
- Expected: Full functionality

## Error Handling Tests

### Test 49: Database Connection Error
**Steps:**
1. Stop MySQL service
2. Try to fetch transactions

**Expected Results:**
- Error message displayed
- No PHP errors shown to user
- Graceful failure

### Test 50: Invalid JSON Response
**Steps:**
1. Modify API to return invalid JSON
2. Try to fetch

**Expected Results:**
- Error caught by frontend
- Error message displayed
- No uncaught exceptions

## Success Criteria
- All CRUD operations work correctly
- Validation catches invalid inputs
- UI is responsive and intuitive
- Charts display accurately
- Filters and search work as expected
- Security measures prevent common attacks
- Session management works properly
- Error handling is graceful
- Performance is acceptable with large datasets
- Cross-browser compatibility confirmed

## Test Execution Checklist
- [ ] Database setup tests (2 tests)
- [ ] Backend API tests (13 tests)
- [ ] Frontend UI tests (37 tests)
- [ ] Performance tests (2 tests)
- [ ] Security tests (3 tests)
- [ ] Browser compatibility tests (3 tests)
- [ ] Error handling tests (2 tests)

**Total: 50 Tests**
