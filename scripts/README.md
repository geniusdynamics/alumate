# Development Scripts

This directory contains various development, debugging, and utility scripts for the Graduate Tracking System.

## Directory Structure

```
scripts/
├── development/          # Development and setup scripts
├── debugging/           # Debugging and diagnostic scripts
├── testing/            # Testing utilities
├── data/               # Data creation and management scripts
└── utilities/          # General utility scripts
```

## Script Categories

### Development Scripts (`development/`)
- `dev-helper.bat` - Interactive development helper menu
- `setup-php-path.bat` - Add PHP to system PATH
- `start-dev.bat` - Start development servers (moved to root for convenience)

### Debugging Scripts (`debugging/`)
- `diagnose_blank_screen.php` - Diagnose blank screen issues
- `fix_blank_screen.bat` - Fix blank screen problems
- `fix_frontend.php` - Fix frontend issues
- `fix_laravel.php` - Fix Laravel configuration issues
- `fix_migration.php` - Fix database migration issues
- `quick_fix.bat` - Quick fixes for common issues
- `disable_security_middleware.php` - Disable security middleware for debugging

### Testing Scripts (`testing/`)
- `test_analytics.php` - Test analytics functionality
- `test_routes.php` - Test route functionality
- `quick_test.php` - Quick system tests
- `run-tests.bat` - Run test suite (Windows)
- `run-tests.sh` - Run test suite (Unix/Linux)

### Data Scripts (`data/`)
- `create_sample_data.php` - Create sample data for development
- `create_tenant_sample_data.php` - Create tenant-specific sample data
- `create_tenant.php` - Create new tenant
- `check_users.php` - Check existing users in database
- `check_tables.php` - Check database table structure

### Utility Scripts (`utilities/`)
- `setup_analytics.bat` - Setup analytics system
- `setup_tenant.php` - Setup tenant configuration
- `simple_key_gen.php` - Generate application keys
- `composer-setup.php` - Composer setup script

## Usage

Most scripts can be run directly from their location:

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

## Important Notes

- The main development script `start-dev.bat` remains in the root directory for easy access
- All scripts maintain their original functionality
- Paths in scripts have been updated to work from their new locations
- Documentation has been updated to reflect new locations