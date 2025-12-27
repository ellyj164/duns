# Test Cases for Clients Table UX Improvements

## Test Environment Setup
1. Ensure database migration has been run: `php migrate_add_created_at.php`
2. Clear browser cache to ensure CSS updates are loaded
3. Log in to the application and navigate to the main dashboard (index.php)

---

## Test Case 1: Text Truncation and No Wrapping

### Objective
Verify that all table columns display content on a single line without wrapping.

### Steps
1. Navigate to the clients table on index.php
2. Look for clients with long text in columns (especially Service column)
3. Observe the Reg No, Client Name, and Service columns

### Expected Results
- ✅ All text in table cells appears on a single line
- ✅ Long text shows ellipsis (...) to indicate truncation
- ✅ Hovering over truncated text shows full content in tooltip
- ✅ No text wraps to a second line in any column

### Test Data
Add a test client with:
- Service: "computer & telecommunication services including import and export"
- Client Name: "Very Long Company Name That Should Be Truncated In The Table"

---

## Test Case 2: Responsible Column Side-by-Side Layout

### Objective
Verify that Avatar and Name in the Responsible column display side-by-side.

### Steps
1. View any row in the clients table
2. Locate the Responsible column
3. Observe the layout of the avatar (initials circle) and name

### Expected Results
- ✅ Avatar appears on the LEFT
- ✅ Name appears on the RIGHT
- ✅ Avatar and Name are on the same horizontal line
- ✅ They do NOT stack vertically
- ✅ Layout remains consistent across all rows

### Visual Check
```
[JD] John Doe    ← Correct (side-by-side)

[JD]             ← Incorrect (stacked)
John Doe
```

---

## Test Case 3: Date Column with Time Ago

### Objective
Verify that the Date column shows the date on one line with "Time Ago" below it.

### Steps
1. View the Date column in the clients table
2. Look at the date format and layout
3. Check the "Time Ago" text below the date

### Expected Results
- ✅ Main date appears on ONE horizontal line (e.g., "2024-12-27")
- ✅ "Time Ago" text appears BELOW the main date
- ✅ "Time Ago" text is:
  - Small font size (smaller than main date)
  - Italic style
  - Bold weight
- ✅ Shows appropriate time format:
  - "just now" (< 1 minute)
  - "5 minutes ago" (< 1 hour)
  - "2 hours ago" (< 24 hours)
  - "3 days ago" (< 7 days)
  - "2 weeks ago" (< 4 weeks)
  - "1 month ago" (< 12 months)
  - "1 year ago" (>= 12 months)

### Visual Example
```
2024-12-27
2 days ago
```

---

## Test Case 4: Duplicate Reg No Check

### Objective
Verify that the system prevents duplicate Registration Numbers.

### Steps
1. Add a new client with Reg No "TEST001"
2. Save the client successfully
3. Try to add another client with the same Reg No "TEST001"
4. Observe the error message

### Expected Results
- ✅ First client with "TEST001" saves successfully
- ✅ Second attempt to save "TEST001" is REJECTED
- ✅ Error message displays: "Duplicate Warning: Registration Number 'TEST001' already exists in the system. Please use a unique Reg No."
- ✅ Error appears as a toast notification (red/error color)
- ✅ Duplicate entry is NOT saved to database
- ✅ Original client data remains unchanged

### Edge Cases to Test
- Empty Reg No (should be allowed)
- Case sensitivity (TEST001 vs test001 - both should be blocked if first is TEST001)

---

## Test Case 5: JOSIEPH 24-Hour Filter

### Objective
Verify that JOSIEPH-related clients only appear in search results after 24 hours.

### Prerequisites
- Database migration completed (created_at column exists)
- Test client with Responsible = "JOSIEPH" inserted recently

### Steps - Scenario A (New JOSIEPH Record)
1. Insert a new client with Responsible = "JOSIEPH"
2. Note the current time
3. Immediately perform a search (e.g., search for "JOSIEPH")
4. Check if the new record appears in search results
5. Use any filter (date filter, status filter, currency filter)
6. Check if the new record appears in filtered results

### Expected Results - Scenario A
- ✅ New JOSIEPH record does NOT appear in search results (< 24 hours old)
- ✅ New JOSIEPH record does NOT appear in any filtered results (< 24 hours old)
- ✅ Record DOES appear when viewing ALL clients (no search/filter active)

### Steps - Scenario B (Old JOSIEPH Record)
1. Manually update created_at for a JOSIEPH record to be >24 hours old:
   ```sql
   UPDATE clients 
   SET created_at = DATE_SUB(NOW(), INTERVAL 25 HOUR)
   WHERE id = [test_client_id];
   ```
2. Perform a search for "JOSIEPH"
3. Apply various filters

### Expected Results - Scenario B
- ✅ Old JOSIEPH record (>24 hours) DOES appear in search results
- ✅ Old JOSIEPH record (>24 hours) DOES appear in filtered results
- ✅ Record appears normally in all views

### Steps - Scenario C (Non-JOSIEPH Records)
1. Create clients with other names (e.g., "John Doe", "Jane Smith")
2. Search and filter these clients

### Expected Results - Scenario C
- ✅ Non-JOSIEPH clients always appear regardless of age
- ✅ No 24-hour restriction on non-JOSIEPH records
- ✅ Search and filter work normally for all other clients

---

## Test Case 6: Overall Table Behavior

### Objective
Verify that table functionality still works correctly after changes.

### Steps
1. Load the clients table
2. Test all existing features:
   - Sorting (click column headers)
   - Editing a row (click edit button)
   - Deleting a row (click delete button)
   - Bulk selection (checkboxes)
   - Export to Excel
   - Print table
   - Filter by status (quick filter chips)
   - Date range filtering

### Expected Results
- ✅ All existing functionality works as before
- ✅ No JavaScript errors in console
- ✅ Table loads and displays correctly
- ✅ All buttons and actions work properly

---

## Browser Compatibility Testing

### Browsers to Test
1. Chrome/Edge (latest)
2. Firefox (latest)
3. Safari (latest)

### Expected Results
- ✅ All UX improvements work consistently across browsers
- ✅ CSS flexbox layouts display correctly
- ✅ No layout issues or broken styling

---

## Performance Testing

### Steps
1. Load table with large dataset (100+ records)
2. Perform searches and filters
3. Observe page load time and responsiveness

### Expected Results
- ✅ No noticeable performance degradation
- ✅ Table remains responsive
- ✅ Search/filter operations complete quickly

---

## Regression Testing Checklist

- [ ] Client creation works correctly
- [ ] Client editing works correctly
- [ ] Client deletion works correctly
- [ ] Search functionality works
- [ ] Date filtering works
- [ ] Status filtering works
- [ ] Currency filtering works
- [ ] Export to Excel works
- [ ] Print functionality works
- [ ] Bulk operations work
- [ ] Document generation (invoices/receipts) works
- [ ] Email functionality works

---

## Known Limitations

1. **Database Migration Required**: The JOSIEPH 24-hour filter will not work until the database migration is run.

2. **Existing Records**: Old records without a `created_at` timestamp will be backfilled with their `date` field value by the migration.

3. **Time Calculation**: "Time Ago" is calculated on page load and does not auto-update without refreshing.

---

## Reporting Issues

If any test case fails, please report:
1. Test case number and name
2. Steps to reproduce
3. Expected vs actual result
4. Browser and version
5. Screenshot (if applicable)
6. Console errors (if any)
