# Graduate Tracking System - Development Guide

## Quick Start

### 1. Start Development Servers

**🚀 Choose your preferred startup method**:

```bash
# Enhanced PowerShell script (recommended)
.\start-dev-final.ps1

# Windows Batch script (simple and reliable)
.\start-dev.bat

# Interactive development helper
scripts/development/dev-helper.bat
```

**Manual startup**:
```bash
# Start Vite first
npm run dev

# Then start Laravel (in another terminal)
php artisan serve --host=127.0.0.1 --port=8080
```

### 2. Access the Application

- **Main Application**: <http://127.0.0.1:8080>
- **Vite Dev Server**: <http://localhost:5100> (for assets only)

⚠️ **Important**: Always use <http://127.0.0.1:8080> for the Laravel application, not the Vite server URL.

## 🔑 Demo Accounts

> **Note**: All demo accounts use the password: `password`

| Role | Email | Password | Dashboard URL |
|------|-------|----------|---------------|
| **🔧 Super Admin** | admin@system.com | password | http://127.0.0.1:8080/super-admin/dashboard |
| **🏫 Institution Admin** | admin@tech-institute.edu | password | http://127.0.0.1:8080/institution-admin/dashboard |
| **🏢 Institution Admin** | admin@business-college.edu | password | http://127.0.0.1:8080/institution-admin/dashboard |
| **🎓 Graduate** | john.smith@student.edu | password | http://127.0.0.1:8080/graduate/dashboard |
| **💼 Employer** | techcorp@company.com | password | http://127.0.0.1:8080/employer/dashboard |

### 🚀 Quick Test Login
1. Go to: http://127.0.0.1:8080/login
2. Use any account above
3. Password is always: `password`

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

- ❌ Wrong: <http://localhost:5100>
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
