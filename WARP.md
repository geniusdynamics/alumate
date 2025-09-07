# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Development Environment

### Prerequisites
- PHP 8.3+ (Specific path: `D:\DevCenter\xampp\php-8.3.23\php.exe`)
- Node.js 18+
- Composer 2.x
- PostgreSQL 13+
- Redis (optional, for caching and queues)

### Quick Start Commands

```bash
# Start development servers (choose one)
./start-dev-final.ps1      # Enhanced PowerShell script (recommended)
./start-dev.bat            # Simple Windows batch script
scripts/development/dev-helper.bat  # Interactive helper

# Start servers manually
npm run dev                # Start Vite server
.\artisan serve --host=127.0.0.1 --port=8080  # Start Laravel server

# Database setup
.\artisan migrate       # Run migrations
.\artisan tenants:migrate  # Run tenant migrations
.\artisan db:seed      # Seed the database

# Create sample data
php scripts/data/create_sample_data.php
php scripts/data/create_tenant_sample_data.php
```

## Architecture Overview

### Multi-Tenant System
- **Framework**: Laravel 11 + Vue.js 3 (Composition API)
- **Multi-Tenancy**: Stancl Tenancy package for complete isolation
- **Database**: PostgreSQL with tenant-specific schemas
- **Authentication**: Laravel Breeze with Spatie Permissions
- **API**: RESTful APIs with comprehensive validation
- **Queue System**: Redis-backed job processing
- **Frontend**: TypeScript + Tailwind CSS + Shadcn/Vue components

### Key Technical Components
- **State Management**: Pinia for complex state handling
- **Build Tool**: Vite for development and production builds
- **Testing**: Vitest (frontend) and Pest PHP (backend)
- **Code Quality**: ESLint, PHP CS Fixer, PHPStan

## Common Development Commands

### Database Operations
```bash
# Using full PHP path when PHP is not in PATH
D:\DevCenter\xampp\php-8.3.23\php.exe .\artisan migrate
D:\DevCenter\xampp\php-8.3.23\php.exe .\artisan tenants:list
D:\DevCenter\xampp\php-8.3.23\php.exe scripts/data/check_users.php

# After setting up PHP PATH
.\artisan migrate
.\artisan tenants:list
php scripts/data/check_users.php
```

### Testing Commands
```bash
# Run comprehensive test suite
scripts/testing/run-tests.bat

# Quick system test
php scripts/testing/quick_test.php

# Frontend tests
npm run test
```

### Code Quality
```bash
# PHP
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer fix

# JavaScript/TypeScript
npm run lint
npm run format
```

## Development URLs

### Main Application
- Main App: http://127.0.0.1:8080
- Login: http://127.0.0.1:8080/login
- Register: http://127.0.0.1:8080/register
- Testing Suite: http://127.0.0.1:8080/testing

### Test Accounts
All accounts use password: `password`

| Role | Email | URL |
|------|-------|-----|
| Super Admin | admin@system.com | http://127.0.0.1:8080/super-admin/dashboard |
| Institution Admin | admin@tech-institute.edu | http://127.0.0.1:8080/institution-admin/dashboard |
| Graduate | john.smith@student.edu | http://127.0.0.1:8080/graduate/dashboard |
| Employer | techcorp@company.com | http://127.0.0.1:8080/employer/dashboard |

## Troubleshooting

### Common Issues

1. **"Tenant could not be identified" Error**
   - Occurs when accessing tenant-specific routes from central domain
   - Use correct URLs:
     - Central (Super Admin): http://127.0.0.1:8080
     - Tenant: Use institution-specific domains or access through central login

2. **PHP Command Not Found**
   - Run `scripts/development/setup-php-path.bat` to add PHP to PATH
   - Or use full path: `D:\DevCenter\xampp\php-8.3.23\php.exe`

3. **Wrong Development Server**
   - ❌ Wrong: http://localhost:5100 (Vite server)
   - ✅ Correct: http://127.0.0.1:8080 (Laravel application)

## Development Workflow

### Component Development
- Follow Vue 3 Composition API patterns
- Use TypeScript for all new components
- Match existing component structure in `resources/js/components/`
- Follow UI component hierarchy (base → feature-specific)

### State Management
- Use Pinia for global state management
- Follow store organization by feature
- Implement type-safe store definitions
- Use composables for reusable logic

### Code Standards
- Follow PSR-12 for PHP code
- Use ESLint + TypeScript for frontend code
- Maintain complete type definitions
- Preserve code documentation

### Performance Practices
- Implement code splitting and lazy loading
- Optimize images and assets
- Use caching strategically
- Monitor and analyze performance metrics

## Testing Strategy

### Frontend Testing
```bash
# Run frontend tests
npm run test

# Run specific test file
npm run test path/to/test

# Watch mode for development
npm run test:watch
```

### Backend Testing
```bash
# Run all tests
scripts/testing/run-tests.bat

# Run specific test
.\artisan test --filter=TestName

# Run tests for a feature
.\artisan test tests/Feature/DirectoryName
```

### Test Coverage
- Maintain high test coverage for critical paths
- Write tests for new features before implementation
- Include unit, integration, and feature tests
- Test cross-browser compatibility

## Additional Documentation

Key documentation files for reference:
- [Development Guide](docs/DEVELOPMENT.md)
- [Port Configuration](PORTS.md)
- [Frontend Architecture](docs/FRONTEND_ARCHITECTURE_RECAP.md)
- [Build Guide](docs/BUILD_SUCCESS_SUMMARY.md)
