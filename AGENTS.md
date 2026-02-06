# AGENTS.md

This file provides guidance for AI coding agents working in this repository.

## Environment Setup

### Required Software

- PHP 8.3+ (Path: `D:\DevCenter\xampp\php-8.3.23\php.exe`)
- Node.js 22+ (LTS)
- Composer 2.x
- PostgreSQL 17+
- Redis (optional)
- pnpm 10.x (Package Manager)

### Initial Setup Commands

```bash
# Install dependencies
composer install
pnpm install

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
pnpm run dev

# Start Laravel server
.\artisan serve --host=127.0.0.1 --port=8080

# Start all services (concurrent)
composer run dev
```

## Workflow Guidelines

### Before Making Changes

1. Read relevant files completely for context
2. Consider tenant isolation implications
3. Review existing component patterns
4. Check for similar implementations
5. Consider performance implications
6. **Run type checking**: `pnpm run typecheck` (if available) or `vue-tsc --noEmit`

### Making Changes

1. Make incremental, testable changes
2. Follow existing code patterns
3. Update dependent code
4. Add/update tests
5. Verify changes in all contexts (tenant/non-tenant)
6. **Run linting**: `vendor/bin/pint` and `pnpm run lint`

### After Changes

1. Run full test suite: `scripts\testing\run-tests.bat`
2. Verify tenant isolation
3. Check performance impact
4. Update documentation if needed
5. Test in all supported browsers
6. **Verify CI/CD compatibility** (see CI/CD Guidelines below)

## Code Standards

### Backend (PHP)

- Follow PSR-12
- Use strict types: `declare(strict_types=1);`
- Document public APIs with PHPDoc
- Write comprehensive tests (target: 80%+ coverage)
- Maintain tenant isolation
- Use type hints for all parameters and returns

### Frontend (Vue/TypeScript)

- Use Vue 3 Composition API with `<script setup>`
- Strict TypeScript mode - no `any` types
- Follow component hierarchy and naming conventions
- Match existing patterns in `resources/js/`
- Document complex logic
- Use strict type checking: `vue-tsc --noEmit`

### Database

- Use migrations for schema changes
- Consider tenant implications (central vs tenant tables)
- Follow naming conventions
- Add appropriate indexes
- Document complex queries
- Test migrations on fresh and existing databases

## Testing Requirements

### Test Suite Organization

```
tests/
├── Unit/           # Fast, isolated tests (SQLite)
├── Feature/        # HTTP endpoint tests (PostgreSQL)
├── Integration/    # Component integration tests (PostgreSQL)
├── Performance/    # Load and stress tests
├── Security/       # Security vulnerability tests
├── EndToEnd/       # Full user flow tests
├── Browser/        # Dusk browser tests
└── Js/            # Vitest frontend tests
```

### Running Tests

```bash
# Full test suite
scripts/testing/run-tests.bat

# Backend tests by suite
.\artisan test --testsuite=Unit
.\artisan test --testsuite=Feature
.\artisan test --testsuite=Integration

# With coverage
./vendor/bin/pest --coverage --min=80

# Frontend tests
pnpm test
pnpm test:run
pnpm test:ui

# Specific test suites
pnpm test:homepage
pnpm test:smoke
```

### Test Coverage Expectations

- New features require tests
- Target: 80%+ coverage (enforced in CI)
- Cover edge cases and error conditions
- Test tenant isolation scenarios
- Test performance impact for heavy operations
- Include integration tests for critical paths

## CI/CD Guidelines

### Pipeline Overview

1. **ci.yml** - Main CI pipeline (runs on all PRs/pushes)
2. **ci-enhanced.yml** - Extended checks with security scans
3. **staging-deployment.yml** - Deploy to staging environment
4. **production-deployment.yml** - Deploy to production (manual approval)
5. **rollback.yml** - Emergency rollback procedures
6. **homepage-deployment.yml** - Homepage-specific deployments

### Pre-Commit Checklist

Before pushing code, ensure:

```bash
# 1. Code style compliance
vendor/bin/pint
pnpm run lint

# 2. Type checking (CRITICAL)
vue-tsc --noEmit

# 3. Run relevant tests
.\artisan test --testsuite=Unit --filter=YourFeature
pnpm test:run --filter=YourComponent

# 4. Build verification
pnpm run build
```

### CI/CD Failure Common Causes

1. **TypeScript errors** - Run `vue-tsc --noEmit` locally
2. **Linting failures** - Run `vendor/bin/pint` and `pnpm run lint --fix`
3. **Test coverage below 80%** - Add more tests
4. **Tenant isolation issues** - Ensure proper tenant context in tests
5. **Database migration conflicts** - Test migrations on fresh DB
6. **Dependency vulnerabilities** - Run `composer audit` and `pnpm audit`

### Environment Variables Required

Ensure these secrets are configured in GitHub:

- `STAGING_HOST`, `STAGING_USER`, `STAGING_SSH_KEY`, `STAGING_PATH`
- `PROD_HOST`, `PROD_USER`, `PROD_SSH_KEY`, `PROD_PATH`
- `SLACK_WEBHOOK_URL` (for notifications)
- Database credentials for each environment

### Deployment Process

1. **Staging**: Auto-deploy on `develop` branch push
2. **Production**: Manual trigger with approval
3. **Rollback**: Use `rollback.yml` workflow with reason
4. **Verification**: All deployments include health checks

## Development Rules

### Critical Requirements

1. Never stage/commit files automatically
2. Always verify file creation in Windows
3. Maintain tenant data isolation
4. Follow existing patterns
5. Test thoroughly before completion
6. **Never commit secrets or API keys**
7. **Always run type checking before committing**

### File Operations

1. Use `.\artisan` for Laravel commands (Windows)
2. Use `pnpm` instead of `npm`
3. Verify file paths work in Windows
4. Check file permissions
5. Validate file existence
6. Handle paths consistently

### Security Practices

1. Never expose sensitive data in logs or errors
2. Use environment variables for all secrets
3. Validate user input at all entry points
4. Maintain tenant boundaries strictly
5. Follow security protocols (CSRF, XSS, SQL injection prevention)
6. Run security audits: `composer audit`, `pnpm audit`

## Troubleshooting Guide

### Common Issues

#### 1. **Tenant Identification**

- Check domain access configuration
- Verify tenant context in tests
- Use correct URLs with tenant domains
- Check `tenants:migrate` has been run

#### 2. **PHP Command Issues**

- Use `.\artisan` on Windows
- Verify PHP 8.3+ in PATH
- Use full PHP path if needed: `D:\DevCenter\xampp\php-8.3.23\php.exe`

#### 3. **Development Server**

- Use correct ports (8080 for Laravel, 5173 for Vite)
- Check file permissions on `storage/` and `bootstrap/cache/`
- Verify environment variables in `.env`
- Clear caches: `.\artisan config:clear`, `.\artisan cache:clear`

#### 4. **CI/CD Failures**

- Check PHP/Node version compatibility
- Verify all tests pass locally first
- Check for uncommitted changes affecting tests
- Review artifact retention limits
- Check secret availability

#### 5. **TypeScript Errors**

- Run `vue-tsc --noEmit` to see all errors
- Check for missing imports
- Verify component prop types
- Check strict null safety settings

#### 6. **Test Failures**

- Check database state: `.\artisan migrate:fresh --seed`
- Clear test caches: `.\artisan config:clear`
- Verify tenant context in multi-tenant tests
- Check for race conditions in parallel tests

## Project Structure

### Key Directories

```
resources/js/
├── components/    # Reusable Vue components
├── composables/   # Vue composables (useXxx)
├── layouts/       # Page layouts
├── Pages/         # Inertia.js pages (route-based)
├── services/      # Business logic services
├── stores/        # Pinia stores
├── types/         # TypeScript type definitions
└── utils/         # Utility functions

app/
├── Console/       # Artisan commands
├── Http/          # Controllers, Middleware, Requests
├── Models/        # Eloquent models
├── Services/      # Business logic
├── Providers/     # Service providers
└── Tenancy/       # Multi-tenant logic

database/
├── migrations/    # Schema migrations
├── seeders/       # Data seeders
├── factories/     # Model factories
└── schema/        # Schema dumps
```

### Important Files

- `artisan`: Laravel CLI tool
- `package.json`: Node dependencies (use pnpm)
- `composer.json`: PHP dependencies
- `tsconfig.json`: TypeScript configuration
- `vite.config.ts`: Build configuration
- `phpunit.xml`: Test configuration
- `pint.json`: PHP code style rules
- `eslint.config.js`: ESLint configuration

## Continuous Integration

### Quality Gates (All Must Pass)

1. ✅ PHP Code Style (Pint)
2. ✅ JavaScript/TypeScript Linting (ESLint)
3. ✅ TypeScript Type Checking (vue-tsc)
4. ✅ Unit Tests (SQLite, 80%+ coverage)
5. ✅ Feature Tests (PostgreSQL)
6. ✅ Integration Tests (PostgreSQL)
7. ✅ Frontend Tests (Vitest)
8. ✅ Security Audit (Composer + npm)
9. ✅ Build Verification

### Before Submitting Changes

1. Run all tests locally
2. Check code style compliance
3. Verify type checking passes
4. Test tenant isolation scenarios
5. Verify security audit passes
6. Update documentation if needed
7. Test in all supported browsers

### Quality Checks

```bash
# PHP checks
vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=5
composer audit

# JavaScript/TypeScript
pnpm run lint
pnpm run format:check
vue-tsc --noEmit
pnpm audit

# Full test suite
scripts/testing/run-tests.bat

# Build verification
pnpm run build
```

## Accessibility Requirements

- Follow WCAG 2.1 AA guidelines
- Test with screen readers
- Support keyboard navigation
- Maintain color contrast ratios (4.5:1 minimum)
- Provide text alternatives for images
- Use semantic HTML elements
- Test with accessibility tools (axe, Lighthouse)

## Performance Guidelines

- Lazy load routes and components
- Optimize images and assets
- Use caching strategies
- Monitor bundle size
- Profile database queries
- Use eager loading to prevent N+1
- Implement pagination for large datasets

## Documentation Standards

- Document all public APIs
- Include JSDoc/PHPDoc comments
- Update README for major changes
- Document breaking changes in migration guides
- Keep AGENTS.md updated with new patterns
