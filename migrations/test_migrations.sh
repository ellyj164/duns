#!/bin/bash

# Test script for validating migration SQL syntax
# This script checks the syntax of migration files without actually executing them

echo "=== Migration Syntax Validation ==="
echo ""

# Array of migration files to test
migrations=(
    "010_add_additional_roles.sql"
    "011_populate_user_roles.sql"
    "012_add_default_role_to_users.sql"
    "013_upgrade_admin_to_superadmin.sql"
)

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

all_passed=true

for migration in "${migrations[@]}"; do
    echo -e "${YELLOW}Testing: $migration${NC}"
    
    # Check if file exists
    if [ ! -f "$migration" ]; then
        echo -e "${RED}✗ File not found: $migration${NC}"
        all_passed=false
        continue
    fi
    
    # Basic syntax check - look for common SQL errors
    errors=0
    
    # Check for unmatched quotes
    single_quotes=$(grep -o "'" "$migration" | wc -l)
    if [ $((single_quotes % 2)) -ne 0 ]; then
        echo -e "${RED}  ✗ Unmatched single quotes detected${NC}"
        errors=$((errors + 1))
    fi
    
    # Check for basic SQL syntax patterns
    if ! grep -q "INSERT INTO\|ALTER TABLE\|CREATE TABLE" "$migration"; then
        echo -e "${RED}  ✗ No valid SQL statements found${NC}"
        errors=$((errors + 1))
    fi
    
    # Check for proper semicolons at statement ends
    if ! grep -q ";" "$migration"; then
        echo -e "${RED}  ✗ Missing semicolons${NC}"
        errors=$((errors + 1))
    fi
    
    if [ $errors -eq 0 ]; then
        echo -e "${GREEN}  ✓ Syntax validation passed${NC}"
        # Show summary of what the migration does
        echo "  Summary:"
        grep -E "^-- Purpose:" "$migration" | sed 's/^/    /'
    else
        echo -e "${RED}  ✗ Validation failed with $errors error(s)${NC}"
        all_passed=false
    fi
    
    echo ""
done

echo "=== Validation Complete ==="
if [ "$all_passed" = true ]; then
    echo -e "${GREEN}All migrations passed syntax validation!${NC}"
    exit 0
else
    echo -e "${RED}Some migrations failed validation.${NC}"
    exit 1
fi
