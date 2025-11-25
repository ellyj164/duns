#!/bin/bash

###############################################################################
# Petty Cash Module Installation Script
# 
# This script automates the installation of the Petty Cash Management module
# for the DUNS application.
#
# Usage: ./install_petty_cash.sh [database_name] [database_user]
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
DB_NAME="${1:-duns}"
DB_USER="${2:-duns}"

echo -e "${GREEN}╔════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  Petty Cash Module Installation Script       ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print status messages
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if we're in the correct directory
if [ ! -f "petty_cash_table.sql" ]; then
    print_error "Error: petty_cash_table.sql not found!"
    print_error "Please run this script from the application root directory."
    exit 1
fi

print_status "Found petty_cash_table.sql"

# Check for required files
echo ""
echo "Checking for required files..."

REQUIRED_FILES=(
    "petty_cash_table.sql"
    "add_petty_cash.php"
    "fetch_petty_cash.php"
    "delete_petty_cash.php"
    "petty_cash.php"
    "header.php"
    "db.php"
)

MISSING_FILES=0

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "$file"
    else
        print_error "Missing: $file"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done

if [ $MISSING_FILES -gt 0 ]; then
    print_error "Installation cannot proceed. Missing $MISSING_FILES file(s)."
    exit 1
fi

echo ""
echo "All required files found!"
echo ""

# Check PHP syntax
echo "Validating PHP syntax..."

PHP_FILES=(
    "add_petty_cash.php"
    "fetch_petty_cash.php"
    "delete_petty_cash.php"
    "petty_cash.php"
)

for file in "${PHP_FILES[@]}"; do
    if php -l "$file" > /dev/null 2>&1; then
        print_status "$file - Syntax OK"
    else
        print_error "$file - Syntax Error!"
        php -l "$file"
        exit 1
    fi
done

echo ""
print_status "All PHP files have valid syntax"
echo ""

# Database installation
echo "════════════════════════════════════════════════"
echo "Database Installation"
echo "════════════════════════════════════════════════"
echo ""
echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo ""

read -p "Do you want to install the database table now? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Enter MySQL password for user '$DB_USER':"
    
    if mysql -u "$DB_USER" -p "$DB_NAME" < petty_cash_table.sql; then
        print_status "Database table created successfully!"
        echo ""
        
        # Verify table creation
        if mysql -u "$DB_USER" -p"$MYSQL_PWD" -e "DESCRIBE petty_cash;" "$DB_NAME" > /dev/null 2>&1; then
            print_status "Table verification: petty_cash table exists"
            echo ""
            mysql -u "$DB_USER" -p"$MYSQL_PWD" -e "DESCRIBE petty_cash;" "$DB_NAME"
        else
            print_warning "Could not verify table creation automatically"
        fi
    else
        print_error "Failed to create database table"
        print_warning "You can create the table manually using:"
        echo "  mysql -u $DB_USER -p $DB_NAME < petty_cash_table.sql"
    fi
else
    print_warning "Skipping database installation"
    print_warning "Remember to run the SQL script manually:"
    echo "  mysql -u $DB_USER -p $DB_NAME < petty_cash_table.sql"
fi

echo ""
echo "════════════════════════════════════════════════"
echo "Installation Summary"
echo "════════════════════════════════════════════════"
echo ""
print_status "Backend API files: 3"
print_status "Frontend UI file: 1"
print_status "Database schema: 1"
print_status "Documentation files: 5"
echo ""

# Check web server
echo "Web Server Check:"
if pgrep -x "apache2" > /dev/null || pgrep -x "httpd" > /dev/null; then
    print_status "Apache is running"
elif pgrep -x "nginx" > /dev/null; then
    print_status "Nginx is running"
else
    print_warning "No web server detected running"
    echo "  You may need to start Apache or Nginx"
fi

echo ""
echo "════════════════════════════════════════════════"
echo "Next Steps"
echo "════════════════════════════════════════════════"
echo ""
echo "1. Ensure web server is running (Apache/Nginx)"
echo "2. Ensure PHP-FPM or mod_php is configured"
echo "3. Verify database credentials in db.php"
echo "4. Login to the application"
echo "5. Access Petty Cash from the header dropdown menu"
echo "6. Test all functionality:"
echo "   - Add Money"
echo "   - Spend Money"
echo "   - Edit transactions"
echo "   - Delete transactions"
echo "   - Filter and search"
echo "   - View charts"
echo ""

# Documentation links
echo "════════════════════════════════════════════════"
echo "Documentation"
echo "════════════════════════════════════════════════"
echo ""
echo "Quick Start:       README_PETTY_CASH.md"
echo "Implementation:    PETTY_CASH_IMPLEMENTATION.md"
echo "UI Description:    PETTY_CASH_UI_DESCRIPTION.md"
echo "Test Plan:         PETTY_CASH_TEST_PLAN.md"
echo "Visual Guide:      PETTY_CASH_VISUAL_GUIDE.md"
echo ""

# File permissions check
echo "════════════════════════════════════════════════"
echo "File Permissions Check"
echo "════════════════════════════════════════════════"
echo ""

for file in "${PHP_FILES[@]}"; do
    if [ -r "$file" ]; then
        print_status "$file is readable"
    else
        print_warning "$file is not readable by current user"
    fi
done

echo ""

# Final message
echo -e "${GREEN}╔════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Installation Complete!                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Note:${NC} If you skipped database installation, remember to"
echo "      create the table manually before using the module."
echo ""
echo "For support, refer to the documentation files or"
echo "review the test plan for troubleshooting steps."
echo ""

exit 0
