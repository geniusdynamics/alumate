# AGENTS.md

This file provides guidance for AI coding agents working in this repository.

## Environment Setup

### Required Software
- PHP 8.3+ (Path: `D:\DevCenter\xampp\php-8.3.23\php.exe`)
- Node.js 18+
- Composer 2.x
- PostgreSQL 13+
- Redis (optional)

### Initial Setup Commands
```bash
# Install dependencies
composer install
npm install

# Database setup
.\artisan migrate
.\artisan tenants:migrate
.\artisan db:seed

# Create sample data
.\artisan db:seed --class=DemoDataSeeder
php scripts/data/create_sample_data.php
php scripts/data/create_tenant_sample_data.php
```

### Development Server Commands
```bash
# Start Vite development server
npm run dev

# Start Laravel server
.\artisan serve --host=127.0.0.1 --port=8080
```

## Workflow Guidelines

### Before Making Changes
1. Read relevant files completely for context
2. Consider tenant isolation implications
3. Review existing component patterns
4. Check for similar implementations
5. Consider performance implications

### Making Changes
1. Make incremental, testable changes
2. Follow existing code patterns
3. Update dependent code
4. Add/update tests
5. Verify changes in all contexts (tenant/non-tenant)

### After Changes
1. Run full test suite
2. Verify tenant isolation
3. Check performance impact
4. Update documentation if needed
5. Test in all supported browsers

## Code Standards

### Backend (PHP)
- Follow PSR-12
- Use strict types
- Document public APIs
- Write comprehensive tests
- Maintain tenant isolation

### Frontend (Vue/TypeScript)
- Use Vue 3 Composition API
- Strict TypeScript mode
- Follow component hierarchy
- Match existing patterns
- Document complex logic

### Database
- Use migrations for schema changes
- Consider tenant implications
- Follow naming conventions
- Add appropriate indexes
- Document complex queries

## Testing Requirements

### Running Tests
```bash
# Full test suite
scripts/testing/run-tests.bat

# Specific backend test
.\artisan test --filter=TestName

# Frontend tests
npm run test
npm run test:watch
```

### Test Coverage Expectations
- New features require tests
- Cover edge cases
- Test tenant isolation
- Test performance impact
- Include integration tests

## Development Rules

### Critical Requirements
1. Never stage/commit files automatically
2. Always verify file creation in Windows
3. Maintain tenant data isolation
4. Follow existing patterns
5. Test thoroughly before completion

### File Operations
1. Use `.\artisan` for Laravel commands
2. Verify file paths work in Windows
3. Check file permissions
4. Validate file existence
5. Handle paths consistently

### Security Practices
1. Never expose sensitive data
2. Use environment variables
3. Validate user input
4. Maintain tenant boundaries
5. Follow security protocols

## Troubleshooting Guide

### Common Issues
1. **Tenant Identification**
   - Check domain access
   - Verify tenant context
   - Use correct URLs

2. **PHP Command Issues**
   - Use `.\artisan` on Windows
   - Verify PHP in PATH
   - Use full PHP path if needed

3. **Development Server**
   - Use correct ports
   - Check file permissions
   - Verify environment setup

## Project Structure

### Key Directories
```
resources/js/
├── components/    # Vue components
├── composables/   # Vue composables
├── layouts/       # Page layouts
├── Pages/         # Inertia.js pages
├── services/      # Business logic
├── stores/        # Pinia stores
└── types/         # TypeScript types
```

### Important Files
- `artisan`: Laravel CLI tool
- `package.json`: Node dependencies
- `composer.json`: PHP dependencies
- `tsconfig.json`: TypeScript config
- `vite.config.ts`: Build config

## Continuous Integration

### Before Submitting Changes
1. Run all tests
2. Check code style
3. Verify tenant isolation
4. Test all environments
5. Update documentation

### Quality Checks
```bash
# PHP checks
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer fix

# JavaScript/TypeScript
npm run lint
npm run format
```

## Accessibility Requirements
- Follow WCAG 2.1 guidelines
- Test with screen readers
- Support keyboard navigation
- Maintain color contrast
- Provide text alternatives
