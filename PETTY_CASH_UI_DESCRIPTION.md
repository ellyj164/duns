# Petty Cash Management UI Description

## Page Layout

### Header Section
- **Dark gradient background** (from #111827 to #374151)
- **Left side**: Money emoji (💰) logo + "Petty Cash Management" title (28px, bold)
- **Right side**: Two action buttons
  - **"Add Money"** button (green/success color) - with plus icon
  - **"Spend Money"** button (red/danger color) - with wallet icon

### Main Content Area (White card-based layout)

#### 1. Add/Edit Transaction Form (Collapsible)
- Initially hidden, appears when clicking "Add Money" or "Spend Money"
- **Form title** changes based on action:
  - "Add Money (Replenish)" for credit transactions
  - "Spend Money (Expense)" for debit transactions
- **Form fields** (2-column grid layout):
  - Date* (date picker, defaults to today)
  - Amount* (numeric input)
  - Description/Purpose* (textarea, full width)
  - Payment Method (dropdown: CASH, BANK, MTN, OTHER)
  - Reference (text input)
- **Action buttons**: Save (primary) and Cancel (secondary)

#### 2. Summary Cards (3 cards in a row)
**Card 1: Current Balance**
- Label: "Current Balance"
- Value: Large number (32px, bold)
- Color: Green if positive, Red if negative

**Card 2: Total Money Added**
- Label: "Total Money Added"
- Value: Large number (32px, bold, green color)

**Card 3: Total Money Spent**
- Label: "Total Money Spent"
- Value: Large number (32px, bold, red color)

#### 3. Charts Section (2 charts side by side)
**Chart 1: Transaction Distribution (Pie/Doughnut Chart)**
- Title: "Transaction Distribution"
- Shows: Money Added (green) vs Money Spent (red)
- Responsive canvas element

**Chart 2: Monthly Trend (Line Chart)**
- Title: "Monthly Trend"
- Shows: Line graph with two series
  - Green line: Money Added over time
  - Red line: Money Spent over time
- X-axis: Months (YYYY-MM format)
- Y-axis: Amount

#### 4. Filters Section
- **Filters bar** with inline elements:
  - Start Date (date picker)
  - End Date (date picker)
  - Transaction Type (dropdown: All Types / Money Added / Money Spent)
  - Search box (flexible width, placeholder: "Search anything...")
  - Apply button (secondary style with filter icon)

#### 5. Transaction History Table
**Table Header:**
- Title: "Transaction History (count)"
- Displays number of transactions

**Table Columns:**
1. Date (formatted date)
2. Type (badge: "Money Added" in green or "Money Spent" in red)
3. Description (full transaction description)
4. Payment Method (CASH, BANK, MTN, etc., or "—" if empty)
5. Reference (reference number or "—" if empty)
6. Amount (right-aligned, with +/- prefix based on type)
7. Actions (Edit and Delete buttons)

**Table Features:**
- Alternating row colors (even rows slightly gray)
- Hover effect (light blue background)
- Inline editing mode:
  - Clicking "Edit" converts row to editable form fields
  - Yellow background for editing row
  - Input fields for all editable columns
  - Save and Cancel buttons appear in Actions column
- Delete with confirmation dialog
- Empty state: Shows icon, heading, and message when no data

**Empty State:**
- Large empty icon
- Heading: "No Transactions Found"
- Message: "No petty cash transactions yet. Start by adding money or recording an expense."

**Skeleton Loader:**
- Shows animated loading bars while fetching data
- 8 skeleton rows by default

## Color Scheme
- **Primary**: #4f46e5 (indigo/purple)
- **Success/Green**: #10b981 (for money added/positive balance)
- **Danger/Red**: #ef4444 (for money spent/negative balance)
- **Secondary**: #eef2ff (light indigo for secondary buttons)
- **Background**: #f8f9fc (light gray page background)
- **Cards**: #ffffff (white)
- **Text**: #1f2937 (dark gray)
- **Muted text**: #6b7280 (medium gray)
- **Borders**: #e5e7eb (light gray)

## Typography
- **Font family**: Inter (from Google Fonts)
- **Headings**: 18-28px, bold (600-700 weight)
- **Body text**: 14px, normal (400 weight)
- **Labels**: 13px, medium (500 weight)
- **Buttons**: 14px, semi-bold (600 weight)

## Interactive Elements

### Buttons
- **Primary buttons**: Indigo background, white text, rounded corners
- **Success buttons**: Green background, white text
- **Danger buttons**: Red background, white text
- **Secondary buttons**: Light indigo background, indigo text
- **Hover effect**: Slightly darker shade + subtle lift (translateY)
- **Icons**: 16x16px SVG icons inline with text

### Form Inputs
- **Style**: White background, gray border, rounded corners
- **Focus state**: Indigo border + light indigo shadow
- **Disabled**: Gray background, cursor not-allowed

### Table
- **Min width**: 1000px (horizontal scroll on smaller screens)
- **Cell padding**: 16px 18px
- **Border**: Light gray between rows
- **Last row**: No bottom border

## Responsive Behavior
- **Desktop (1200px+)**: 
  - Summary cards in single row (4 columns each)
  - Charts side by side (6 columns each)
  
- **Tablet (768px-1199px)**:
  - Summary cards in single row (4 columns each)
  - Charts may stack or adjust
  
- **Mobile (<768px)**:
  - Summary cards stack vertically
  - Charts stack vertically
  - Table scrolls horizontally

## User Interactions

### Adding Money Flow
1. User clicks "Add Money" button in header
2. Form slides down with smooth animation
3. Form title shows "Add Money (Replenish)"
4. "Add Money" button hides, "Spend Money" stays visible
5. User fills form and clicks "Save"
6. Form slides up, data refreshes
7. Success feedback via updated balance and table

### Spending Money Flow
1. User clicks "Spend Money" button in header
2. Form slides down with smooth animation
3. Form title shows "Spend Money (Expense)"
4. "Spend Money" button hides, "Add Money" stays visible
5. User fills form and clicks "Save"
6. Form slides up, data refreshes
7. Success feedback via updated balance and table

### Editing Transaction Flow
1. User clicks "Edit" button on table row
2. Row background changes to yellow
3. All cells become editable inputs
4. User modifies values
5. Clicks "Save" to commit or "Cancel" to revert
6. Row returns to normal view with updated data

### Filtering Flow
1. User selects date range, type, or enters search term
2. Clicks "Apply" button
3. Table shows skeleton loader
4. Filtered results appear
5. Summary cards and charts update to match filtered data

### Search Flow
1. User types in search box
2. 400ms debounce delay
3. Automatic search triggered
4. Results filter in real-time

## Accessibility Features
- Focus indicators on interactive elements
- Keyboard navigation support
- ARIA labels where needed
- Semantic HTML structure
- Color contrast meets WCAG guidelines

## Performance Features
- Lazy loading of Chart.js from CDN
- Debounced search (400ms delay)
- Skeleton loaders for better perceived performance
- Efficient DOM updates
- CSS transitions for smooth animations

## Session Management
- 5-minute inactivity timeout
- Warning appears at 4 minutes
- "Stay Logged In" button to reset timer
- Auto-logout after 5 minutes of inactivity
- User actions reset the timer

## Error Handling
- Empty state message when no transactions
- Error state message on API failures
- Form validation errors inline
- Delete confirmation dialogs
- Failed update feedback with alert

## Data Flow
1. Page loads → Shows skeleton loader
2. Fetch API call to `fetch_petty_cash.php`
3. Receive JSON response with transaction data
4. Calculate totals and balance
5. Render table, cards, and charts
6. Enable interactive features
7. Listen for user actions
8. Update via API calls (create/update/delete)
9. Refresh data on success

This UI provides a modern, intuitive interface for managing petty cash with all the features users need for tracking small operational expenses.
