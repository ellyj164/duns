# Petty Cash Management - Quick Reference Card

## 🚀 Quick Start (3 Steps)

1. **Install Database Table**
   ```bash
   mysql -u duns -p duns < petty_cash_table.sql
   ```

2. **Access Module**
   - Login → Click Avatar → Select "Petty Cash"

3. **Start Using**
   - Click "Add Money" or "Spend Money"

---

## 📋 Main Features

| Feature | Action |
|---------|--------|
| Add Money | Click green "Add Money" button → Fill form → Save |
| Spend Money | Click red "Spend Money" button → Fill form → Save |
| Edit Transaction | Click "Edit" on row → Modify → Save/Cancel |
| Delete Transaction | Click "Delete" → Confirm |
| Filter by Date | Set From/To dates → Apply |
| Filter by Type | Select type dropdown → Apply |
| Search | Type in search box (auto-searches) |

---

## 🎯 Transaction Types

| Type | Badge Color | When to Use |
|------|-------------|-------------|
| **Credit** (Money Added) | 🟢 Green | Cash replenishments, deposits |
| **Debit** (Money Spent) | 🔴 Red | Expenses, purchases |

---

## 📊 Dashboard Elements

```
┌─────────────────────────────────────────┐
│ Current Balance = Added - Spent         │
│ ✓ Green if positive                     │
│ ✗ Red if negative                       │
└─────────────────────────────────────────┘

┌──────────────┐ ┌──────────────┐
│ Total Added  │ │ Total Spent  │
│ (Green)      │ │ (Red)        │
└──────────────┘ └──────────────┘

┌─────────────┐ ┌─────────────┐
│ Pie Chart   │ │ Line Chart  │
│ (Split)     │ │ (Trend)     │
└─────────────┘ └─────────────┘
```

---

## 🔍 Search Tips

The search box finds matches in:
- Description/Purpose
- Reference number
- Payment method
- Transaction dates
- Amount values

**Example searches:**
- `office` → finds "Office supplies"
- `PC-001` → finds reference PC-001
- `150` → finds amounts of 150
- `2025-11` → finds November 2025 transactions

---

## 🛠️ Common Tasks

### Record an Expense
1. Click "Spend Money"
2. Enter today's date (auto-filled)
3. Enter amount: `50.00`
4. Description: `Coffee for meeting`
5. Payment Method: `CASH`
6. Click "Save"

### Add Cash to Fund
1. Click "Add Money"
2. Enter amount: `1000.00`
3. Description: `Monthly petty cash replenishment`
4. Payment Method: `BANK`
5. Reference: `TRF-2025-001`
6. Click "Save"

### Find Last Month's Transactions
1. Set "From" to: `2025-10-01`
2. Set "To" to: `2025-10-31`
3. Click "Apply"

### Edit Wrong Amount
1. Find the transaction in table
2. Click "Edit" button
3. Change amount field
4. Click "Save" (or "Cancel" to discard)

---

## ⚠️ Validation Rules

| Field | Rule |
|-------|------|
| Amount | Must be positive number |
| Date | Required, must be valid date |
| Description | Required, max 500 chars |
| Type | Must be credit or debit |
| Payment Method | Optional (CASH/BANK/MTN/OTHER) |
| Reference | Optional, max 100 chars |

---

## 🔐 Security Notes

- ✓ Session required (auto-logout after 5 min)
- ✓ All amounts validated server-side
- ✓ SQL injection protected
- ✓ HTML escaping on output
- ⚠️ Warning shown at 4 minutes of inactivity

---

## 🎨 Visual Indicators

| Element | Color | Meaning |
|---------|-------|---------|
| Balance | 🟢 Green | Positive (money available) |
| Balance | 🔴 Red | Negative (overspent) |
| Money Added Badge | 🟢 Green | Credit transaction |
| Money Spent Badge | 🔴 Red | Debit transaction |
| Edit Row | 🟡 Yellow | Currently editing |
| Hover Row | 🔵 Light Blue | Mouse over row |

---

## ⌨️ Keyboard Shortcuts

| Key | Action |
|-----|--------|
| Tab | Navigate between fields |
| Enter | Submit form (when focused on button) |
| Esc | Close modal/cancel (if implemented) |

---

## 📱 Mobile Usage

On mobile devices:
- Cards stack vertically
- Charts stack vertically
- Table scrolls horizontally →→→
- Forms remain full-width
- Buttons remain accessible

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| "Authentication required" | Login again |
| Transactions not showing | Check filters, clear all and apply |
| Charts not displaying | Check browser console, reload page |
| Can't edit | Make sure only editing one row at a time |
| Balance wrong | Check if all transactions loaded |
| Session timeout | Click "Stay Logged In" when warning appears |

---

## 📂 File Locations

```
Root Directory/
├── petty_cash.php          ← Main UI
├── add_petty_cash.php      ← Create/Update/Delete API
├── fetch_petty_cash.php    ← Retrieve API
├── delete_petty_cash.php   ← Delete API (alt)
├── petty_cash_table.sql    ← Database schema
└── Documentation/
    ├── README_PETTY_CASH.md
    ├── PETTY_CASH_IMPLEMENTATION.md
    ├── PETTY_CASH_UI_DESCRIPTION.md
    ├── PETTY_CASH_TEST_PLAN.md
    └── PETTY_CASH_VISUAL_GUIDE.md
```

---

## 🔗 API Quick Reference

### Fetch All Transactions
```
GET /fetch_petty_cash.php
```

### Fetch with Filters
```
GET /fetch_petty_cash.php?from=2025-11-01&to=2025-11-30&type=debit
```

### Create Transaction
```
POST /add_petty_cash.php
{
  "action": "create",
  "transaction_date": "2025-11-22",
  "description": "Office supplies",
  "amount": "50.00",
  "transaction_type": "debit",
  "payment_method": "CASH"
}
```

### Update Transaction
```
POST /add_petty_cash.php
{
  "action": "update",
  "id": 123,
  "amount": "55.00",
  "description": "Office supplies (updated)"
}
```

### Delete Transaction
```
POST /add_petty_cash.php
{
  "action": "delete",
  "id": 123
}
```

---

## 💡 Best Practices

1. **Enter transactions promptly** - Don't wait until end of day
2. **Use clear descriptions** - "Office supplies" not just "Supplies"
3. **Include references** - Add receipt numbers when available
4. **Reconcile regularly** - Check balance matches physical cash
5. **Archive old data** - Export or backup monthly
6. **Use filters** - For monthly reports and analysis

---

## 📞 Support

**For detailed help, see:**
- Quick Start: `README_PETTY_CASH.md`
- Technical Details: `PETTY_CASH_IMPLEMENTATION.md`
- All Test Cases: `PETTY_CASH_TEST_PLAN.md`

**Database Issues?**
- Check `db.php` for credentials
- Ensure MySQL is running
- Verify table exists: `SHOW TABLES LIKE 'petty_cash';`

**UI Issues?**
- Check browser console for errors
- Clear browser cache
- Try different browser
- Ensure JavaScript is enabled

---

## 📊 Report Examples

### Daily Balance Check
```
1. No filters applied
2. Current Balance = What's in cash box?
3. If mismatch → Review recent transactions
```

### Monthly Expense Report
```
1. Set date range: First to last day of month
2. Select type: Money Spent
3. Review table → Export if needed
```

### Category Analysis (Manual)
```
1. Use search to find categories:
   - Search "office" for office expenses
   - Search "food" for food/beverage
   - Search "travel" for travel costs
```

---

## ⏱️ Performance Tips

- **Large datasets**: Use date filters to narrow results
- **Slow search**: Wait for debounce (400ms)
- **Chart lag**: Reduce time range if many transactions
- **Archive old data**: Consider yearly archival

---

## ✅ Daily Checklist

- [ ] Record all expenses immediately
- [ ] Keep receipts organized
- [ ] Match balance with physical cash
- [ ] Add references for tracking
- [ ] Report any discrepancies

---

**Quick Reference Card v1.0**  
**Last Updated: November 2025**

*Print this page for quick reference at your desk!*
