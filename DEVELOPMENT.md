# Graduate Tracking System - Development Guide

## Quick Start

### 1. Start Development Servers

Run the development helper:

```bash
scripts/development/dev-helper.bat
```

Or start servers directly:

```bash
start-dev.bat
```

### 2. Access the Application

- **Main Application**: <http://127.0.0.1:8080>
- **Vite Dev Server**: <http://localhost:5173> (for assets only)

⚠️ **Important**: Always use <http://127.0.0.1:8080> for the Laravel application, not the Vite server URL.

## Demo Accounts

### Super Admin

- **Email**: <admin@system.com>
- **Password**: password
- **Dashboard**: <http://127.0.0.1:8080/super-admin/dashboard>

### Institution Admin (Tech Institute)

- **Email**: <admin@tech-institute.edu>
- **Password**: password
- **Dashboard**: <http://127.0.0.1:8080/institution-admin/dashboard>

### Institution Admin (Business College)

- **Email**: <admin@business-college.edu>
- **Password**: password
- **Dashboard**: <http://127.0.0.1:8080/institution-admin/dashboard>

### Graduate

- **Email**: <john.smith@student.edu>
- **Password**: password
- **Dashboard**: <http://127.0.0.1:8080/graduate/dashboard>

### Employer

- **Email**: <techcorp@company.com>
- **Password**: password
- **Dashboard**: <http://127.0.0.1:8080/employer/dashboard>

## Quick Access Links

### Authentication

- **Login**: <http://127.0.0.1:8080/login>
- **Register**: <http://127.0.0.1:8080/register>

### Dashboards

- **Super Admin**: <http://127.0.0.1:8080/super-admin/dashboard>
- **Institution Admin**: <http://127.0.0.1:8080/institution-admin/dashboard>
- **Employer**: <http://127.0.0.1:8080/employer/dashboard>
- **Graduate**: <http://127.0.0.1:8080/graduate/dashboard>

### Testing

- **User Acceptance Testing**: <http://127.0.0.1:8080/testing>

## Development Commands

### Using Full PHP Path

```bash
# Check users
D:\DevCenter\xampp\php-8.3.23\php.exe scripts/data/check_users.php

# Run migrations
D:\DevCenter\xampp\php-8.3.23\php.exe artisan migrate

# List tenants
D:\DevCenter\xampp\php-8.3.23\php.exe artisan tenants:list

# Create sample data
D:\DevCenter\xampp\php-8.3.23\php.exe scripts/data/create_sample_data.php
```

### After Setting Up PHP PATH

```bash
# Check users
php scripts/data/check_users.php

# Run migrations
php artisan migrate

# List tenants
php artisan tenants:list

# Create sample data
php scripts/data/create_sample_data.php
```

## Troubleshooting

### "Tenant could not be identified" Error

This happens when accessing tenant-specific routes from the central domain. Make sure you're using the correct URLs:

- Central routes (Super Admin): <http://127.0.0.1:8080>
- Tenant routes: Use institution-specific domains or access through central login

### Vite Server Page Instead of Laravel

If you see the Vite development server page, you're accessing the wrong URL:

- ❌ Wrong: <http://localhost:5173>
- ✅ Correct: <http://127.0.0.1:8080>

### PHP Command Not Found

Run `scripts/development/setup-php-path.bat` to add PHP to your system PATH, or use the full path:

```bash
D:\DevCenter\xampp\php-8.3.23\php.exe
```

## File Structure

```
├── start-dev.bat          # Start development servers (kept in root)
├── scripts/               # Development and utility scripts
│   ├── development/       # Development setup scripts
│   │   ├── dev-helper.bat # Development helper menu
│   │   └── setup-php-path.bat # Add PHP to system PATH
│   ├── debugging/         # Debugging and diagnostic scripts
│   ├── testing/          # Testing utilities
│   ├── data/             # Data creation and management
│   │   ├── check_users.php # Check database users
│   │   └── create_sample_data.php # Create sample data
│   └── utilities/        # General utility scripts
├── routes/
│   ├── central.php        # Central (non-tenant) routes
│   ├── tenant.php         # Tenant-specific routes
│   └── web.php           # Main web routes (minimal)
└── resources/js/Pages/
    └── Welcome.vue        # Enhanced welcome page
```

## Multi-Tenant Architecture

The system uses a multi-tenant architecture:

- **Central Domain**: 127.0.0.1, localhost (for Super Admin)
- **Tenant Domains**: Institution-specific domains (future feature)
- **Database**: Separate tenant databases for each institution

## Need Help?

1. Run `scripts/development/dev-helper.bat` for an interactive menu
2. Check the welcome page at <http://127.0.0.1:8080> for quick links
3. Review this guide for common issues and solutions
