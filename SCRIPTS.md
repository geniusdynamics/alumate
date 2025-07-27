# Script Organization Summary

## Overview

All development, debugging, testing, and utility scripts have been organized into the `scripts/` directory to clean up the main project directory and improve maintainability.

## Directory Structure

```
scripts/
├── README.md                    # Overview of all scripts
├── development/                 # Development and setup scripts
│   ├── README.md
│   ├── dev-helper.bat          # Interactive development helper menu
│   └── setup-php-path.bat      # Add PHP to system PATH
├── debugging/                   # Debugging and diagnostic scripts
│   ├── README.md
│   ├── diagnose_blank_screen.php
│   ├── fix_blank_screen.bat
│   ├── fix_frontend.php
│   ├── fix_laravel.php
│   ├── fix_migration.php
│   ├── quick_fix.bat
│   └── disable_security_middleware.php
├── testing/                     # Testing utilities
│   ├── README.md
│   ├── test_analytics.php
│   ├── test_routes.php
│   ├── quick_test.php
│   ├── run-tests.bat
│   └── run-tests.sh
├── data/                        # Data creation and management scripts
│   ├── README.md
│   ├── create_sample_data.php
│   ├── create_tenant_sample_data.php
│   ├── check_users.php
│   ├── check_tables.php
│   └── create_tenant.php
└── utilities/                   # General utility scripts
    ├── README.md
    ├── setup_analytics.bat
    ├── setup_tenant.php
    └── simple_key_gen.php
```

## Files Moved

### From Root Directory

**Development Scripts:**
- `dev-helper.bat` → `scripts/development/dev-helper.bat`
- `setup-php-path.bat` → `scripts/development/setup-php-path.bat`

**Debugging Scripts:**
- `diagnose_blank_screen.php` → `scripts/debugging/diagnose_blank_screen.php`
- `fix_blank_screen.bat` → `scripts/debugging/fix_blank_screen.bat`
- `fix_frontend.php` → `scripts/debugging/fix_frontend.php`
- `fix_laravel.php` → `scripts/debugging/fix_laravel.php`
- `fix_migration.php` → `scripts/debugging/fix_migration.php`
- `quick_fix.bat` → `scripts/debugging/quick_fix.bat`
- `disable_security_middleware.php` → `scripts/debugging/disable_security_middleware.php`

**Testing Scripts:**
- `test_analytics.php` → `scripts/testing/test_analytics.php`
- `test_routes.php` → `scripts/testing/test_routes.php`
- `quick_test.php` → `scripts/testing/quick_test.php`
- `run-tests.bat` → `scripts/testing/run-tests.bat`
- `run-tests.sh` → `scripts/testing/run-tests.sh`

**Data Scripts:**
- `create_sample_data.php` → `scripts/data/create_sample_data.php`
- `create_tenant_sample_data.php` → `scripts/data/create_tenant_sample_data.php`
- `check_users.php` → `scripts/data/check_users.php`
- `check_tables.php` → `scripts/data/check_tables.php`
- `create_tenant.php` → `scripts/data/create_tenant.php`

**Utility Scripts:**
- `setup_analytics.bat` → `scripts/utilities/setup_analytics.bat`
- `setup_tenant.php` → `scripts/utilities/setup_tenant.php`
- `simple_key_gen.php` → `scripts/utilities/simple_key_gen.php`

### Files Kept in Root

**Essential Development Files:**
- `start-dev.bat` - Main development server starter (kept for easy access)
- `artisan` - Laravel command-line tool
- `composer.phar` - Composer dependency manager

**Configuration Files:**
- All `.env`, `.json`, `.js`, `.ts`, `.php` configuration files
- All package management files (`package.json`, `composer.json`, etc.)

## Path Updates

All moved scripts have been updated with correct relative paths:
- Database connections: `../../bootstrap/app.php`
- Vendor autoload: `../../vendor/autoload.php`
- File references: Updated to work from new locations
- Cross-script references: Updated to new paths

## Updated Documentation

**Files Updated:**
- `DEVELOPMENT.md` - Updated all script references
- `scripts/README.md` - Created comprehensive script documentation
- Individual `README.md` files in each script directory

**New Command Examples:**
```bash
# Development helper
scripts/development/dev-helper.bat

# Check users
php scripts/data/check_users.php

# Test analytics
php scripts/testing/test_analytics.php

# Fix blank screen
scripts/debugging/fix_blank_screen.bat
```

## Benefits

1. **Cleaner Root Directory**: Main directory now focuses on essential project files
2. **Better Organization**: Scripts grouped by purpose and functionality
3. **Easier Maintenance**: Related scripts are co-located
4. **Clear Documentation**: Each category has its own README
5. **Preserved Functionality**: All scripts work exactly as before
6. **Improved Navigation**: Developers can quickly find the right script type

## Usage

All scripts maintain their original functionality but are now accessed from their new locations:

```bash
# Instead of: dev-helper.bat
# Use: scripts/development/dev-helper.bat

# Instead of: php check_users.php
# Use: php scripts/data/check_users.php

# Instead of: quick_fix.bat
# Use: scripts/debugging/quick_fix.bat
```

The main development script `start-dev.bat` remains in the root directory for convenience, as it's the most frequently used script.