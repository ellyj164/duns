# Database Migration Instructions

## Required Migration: Add created_at Column

Before the new JOSIEPH 24-hour filter feature will work correctly, you need to run the database migration to add the `created_at` column to the `clients` table.

### How to Run the Migration

1. **Via Command Line (Recommended)**:
   ```bash
   cd /path/to/duns
   php migrate_add_created_at.php
   ```

2. **Via SQL Directly** (if PHP CLI is not available):
   ```sql
   -- Run this SQL query on your database
   ALTER TABLE clients 
   ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER created_by_id;
   
   -- Update existing records
   UPDATE clients 
   SET created_at = CONCAT(date, ' 00:00:00') 
   WHERE created_at IS NULL AND date IS NOT NULL;
   ```

### What This Migration Does

- Adds a `created_at` TIMESTAMP column to the `clients` table
- Sets the default value to CURRENT_TIMESTAMP for new records
- Backfills existing records with their `date` field value as the creation timestamp

### Why This is Needed

The JOSIEPH 24-hour filter feature requires accurate timestamp tracking to determine when records were inserted. The `created_at` column provides this information.

### Verification

After running the migration, verify it worked by checking:

```sql
SHOW COLUMNS FROM clients LIKE 'created_at';
```

You should see the `created_at` column with type TIMESTAMP.
