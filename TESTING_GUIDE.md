# Testing Guide for Clients Table UX Improvements

## Pre-Deployment Testing (Development Environment)

### 1. Database Migration Test
**Objective**: Verify the migration script works correctly

**Steps**:
1. Backup your database before running migration
   ```bash
   mysqldump -u duns -p duns > backup_before_migration.sql
   ```

2. Run the migration script:
   ```bash
   php migrate_add_created_at.php
   ```

3. **Expected Output**:
   ```
   Starting migration to add created_at column...
   Adding created_at column...
   ✓ Column added successfully.
   Updating existing records...
   ✓ Updated [N] existing records.
   
   Migration completed successfully!
   ```

4. Verify the column was added:
   ```sql
   DESCRIBE clients;
   -- Should show 'created_at' TIMESTAMP column
   ```

5. Verify existing data was updated:
   ```sql
   SELECT id, date, created_at FROM clients LIMIT 5;
   -- created_at should match date (with 00:00:00 time)
   ```

**Pass Criteria**: ✅ Migration completes without errors, column exists, data updated

---

### 2. Time Ago Counter Test
**Objective**: Verify time ago calculation and display

**Test Cases**:

#### Test 2.1: New Record (Just Now)
1. Create a new client record
2. Refresh the page immediately
3. **Expected**: Date shows "just now" below it

#### Test 2.2: Old Records
1. View clients with various ages
2. **Expected Results**:
   - Record from today: "X hours ago" or "X minutes ago"
   - Record from yesterday: "1 day ago"
   - Record from 3 days ago: "3 days ago"
   - Record from 2 weeks ago: "2 weeks ago"
   - Record from 6 months ago: "6 months ago"

#### Test 2.3: Styling Verification
1. Inspect time ago text in browser
2. **Expected**: Text is small, italic, and bold
3. **Expected**: Text appears directly below the date

**Pass Criteria**: ✅ All time calculations are accurate, styling is correct

---

### 3. Single-Line Display Test
**Objective**: Verify all content displays on single lines

#### Test 3.1: Long Reg No
1. Create client with reg no: "S2024/VERY/LONG/REGISTRATION/NUMBER/12345"
2. **Expected**: Text truncates with ellipsis (...) at ~10 characters
3. **Expected**: Hover shows full text in tooltip

#### Test 3.2: Long Client Name
1. Create client: "VERY LONG COMPANY NAME THAT SHOULD BE TRUNCATED WITH ELLIPSIS TO FIT"
2. **Expected**: Text truncates at ~25 characters with ellipsis
3. **Expected**: Hover shows full name

#### Test 3.3: Long Service Name
1. Create client with service: "Computer & Telecommunication Information Services"
2. **Expected**: Service text truncates at ~20 characters with ellipsis
3. **Expected**: Badge still displays correctly

#### Test 3.4: Responsible Column
1. View any client record
2. **Expected**: Avatar circle and name are side-by-side (horizontally)
3. **Expected**: NOT stacked vertically

#### Test 3.5: Numeric Columns
1. View amount, paid, and due columns
2. **Expected**: Numbers don't wrap (e.g., "1,234,567.89" stays on one line)

**Pass Criteria**: ✅ All content on single lines, ellipsis works, tooltips show full text

---

### 4. 24-Hour JOSEPH Filter Test
**Objective**: Verify JOSEPH records are filtered correctly during search

#### Test 4.1: Create New JOSEPH Record
1. Create a new client with Responsible = "JOSEPH"
2. Note the current time
3. Immediately use the search box to search for any text
4. **Expected**: JOSEPH record does NOT appear in results
5. Search for empty text or view all records
6. **Expected**: JOSEPH record DOES appear in full table view

#### Test 4.2: Wait 24+ Hours
1. Using a client record with "JOSEPH" created >24 hours ago
2. Search for any text
3. **Expected**: Old JOSEPH record DOES appear in search results

#### Test 4.3: Case Sensitivity
1. Create records with:
   - Responsible = "joseph" (lowercase)
   - Responsible = "JOSEPH" (uppercase)
   - Responsible = "JoSePh" (mixed case)
2. All should be filtered during search if < 24 hours old
3. **Expected**: Filter is case-insensitive

#### Test 4.4: Partial Matches
1. Create client with Responsible = "JOSEPH SMITH"
2. **Expected**: Still filtered if < 24 hours (contains JOSEPH)

**Pass Criteria**: ✅ JOSEPH records hidden in search for 24hrs, visible after 24hrs, filter is case-insensitive

---

### 5. Duplicate Reg No Validation Test
**Objective**: Verify duplicate registration numbers are prevented

#### Test 5.1: Create Duplicate
1. Note an existing reg_no (e.g., "S2024/D1234")
2. Try to create a new client with the same reg_no
3. Click "Save Client"
4. **Expected**: Red toast notification appears
5. **Expected**: Message says: "Duplicate Registration Number: This reg no already exists in the system"
6. **Expected**: Client is NOT created

#### Test 5.2: Verify Database
1. After failed duplicate attempt
2. Check database: `SELECT COUNT(*) FROM clients WHERE reg_no = 'S2024/D1234'`
3. **Expected**: Count should be 1 (original record only)

#### Test 5.3: Empty Reg No
1. Create a client with empty reg_no
2. **Expected**: Should succeed (empty values are allowed)

#### Test 5.4: Unique Reg No
1. Create client with unique reg_no
2. **Expected**: Should succeed normally

**Pass Criteria**: ✅ Duplicates are prevented, error message is clear, unique values work

---

### 6. Responsive Design Test
**Objective**: Verify table works on different screen sizes

#### Test 6.1: Desktop (1920x1080)
1. View table on full-screen desktop
2. **Expected**: Table fits within viewport or scrolls horizontally
3. **Expected**: All columns are visible

#### Test 6.2: Tablet (768x1024)
1. Resize browser to tablet size
2. **Expected**: Table has horizontal scrollbar
3. **Expected**: Can scroll to see all columns
4. **Expected**: Single-line display maintained

#### Test 6.3: Mobile (375x667)
1. Resize browser to mobile size
2. **Expected**: Table scrolls horizontally
3. **Expected**: Content doesn't wrap or overflow vertically
4. **Expected**: Touch scrolling works smoothly

**Pass Criteria**: ✅ Table is usable on all screen sizes with proper scrolling

---

### 7. Browser Compatibility Test
**Objective**: Verify functionality across browsers

Test all features above in:
- ✅ Chrome 100+
- ✅ Firefox 95+
- ✅ Safari 15+
- ✅ Edge 100+

**Pass Criteria**: ✅ All features work in all supported browsers

---

## Post-Deployment Testing (Production)

### 1. Smoke Test
1. Navigate to index.php
2. **Expected**: Page loads without errors
3. Check browser console (F12) for JavaScript errors
4. **Expected**: No errors

### 2. Existing Data Verification
1. View existing client records
2. **Expected**: All existing data displays correctly
3. **Expected**: Time ago shows for all records
4. **Expected**: No broken layouts

### 3. Performance Test
1. Load page with 100+ client records
2. **Expected**: Page loads in < 3 seconds
3. **Expected**: Scrolling is smooth
4. **Expected**: No lag when interacting with table

### 4. Search Performance
1. Search for various terms
2. **Expected**: Results appear in < 1 second
3. **Expected**: JOSEPH filter works correctly

---

## Regression Testing

### Test Existing Features Still Work

1. ✅ **Add Client**: Can create new clients
2. ✅ **Edit Client**: Inline editing still works
3. ✅ **Delete Client**: Deletion works with confirmation
4. ✅ **Sorting**: Click column headers to sort
5. ✅ **Filtering**: Date range and status filters work
6. ✅ **Quick Filters**: Filter chips work correctly
7. ✅ **Bulk Actions**: Select multiple and export/delete
8. ✅ **Print**: Print functionality works
9. ✅ **Excel Export**: Download Excel works
10. ✅ **Excel Import**: Import from Excel works
11. ✅ **Invoice Generation**: Can generate invoices
12. ✅ **Receipt Generation**: Can generate receipts
13. ✅ **Email Documents**: Email functionality works
14. ✅ **History**: Client history modal works

---

## Known Issues & Limitations

1. **Time ago is static**: Updates only on page refresh (not real-time)
2. **Service badges**: Very long service names (>30 chars) may still be truncated significantly
3. **JOSEPH filter**: Only applies during search, not in initial full table view
4. **Month/Year calculations**: Uses approximate values (30 days/month, 365 days/year)

---

## Rollback Plan

If issues are found in production:

1. **Stop further damage**:
   ```bash
   # Revert the code changes
   git revert HEAD
   git push origin main
   ```

2. **Rollback database** (if needed):
   ```sql
   ALTER TABLE clients DROP COLUMN created_at;
   ```

3. **Restore from backup**:
   ```bash
   mysql -u duns -p duns < backup_before_migration.sql
   ```

---

## Sign-off Checklist

Before marking as complete:

- [ ] All tests pass in development environment
- [ ] Database migration runs successfully
- [ ] Code review comments addressed
- [ ] No JavaScript errors in console
- [ ] No PHP errors in logs
- [ ] Documentation is complete
- [ ] Screenshots/videos captured
- [ ] Stakeholders have approved changes
- [ ] Backup created before production deployment
- [ ] Rollback plan tested

---

## Test Results Template

```
Test Date: _______________
Tester: _______________
Environment: [ ] Dev [ ] Staging [ ] Production

Database Migration: [ ] PASS [ ] FAIL
Time Ago Counter: [ ] PASS [ ] FAIL
Single-Line Display: [ ] PASS [ ] FAIL
JOSEPH Filter: [ ] PASS [ ] FAIL
Duplicate Validation: [ ] PASS [ ] FAIL
Responsive Design: [ ] PASS [ ] FAIL
Browser Compatibility: [ ] PASS [ ] FAIL
Regression Tests: [ ] PASS [ ] FAIL

Issues Found:
_________________________________
_________________________________
_________________________________

Notes:
_________________________________
_________________________________
_________________________________

Sign-off: _______________
```
