# Project Context: Alumate Fix Mission

## Mission Overview

Fixing 39 issues identified in PROJECT_ANALYSIS.md to make the application usable.

## Environment

- **PHP**: 8.3.23
- **Laravel**: 12.x
- **Vue**: 3.x with TypeScript
- **Inertia**: v2
- **Database**: PostgreSQL with multi-tenant architecture
- **Package Manager**: pnpm

## Critical Issues Being Fixed

### Phase 1: Critical Blockers

1. **C-01**: 40+ missing routes causing Ziggy exceptions
2. **C-02**: GraduateDashboardController redirects to non-existent route
3. **C-03**: Employer registration validation rejects 'employer' role
4. **C-04**: Institution Admin has only 1 registered route
5. **C-05**: Super Admin has 7 unregistered routes

### Security Issues

- **H-01**: Debug panel exposes form data on login page
- **H-02**: console.log leaks credentials

## File Structure

- Routes: `routes/web.php`
- Navigation: `resources/js/lib/navigation.ts`
- Graduate Dashboard Controller: `app/Http/Controllers/GraduateDashboardController.php`
- Employer Dashboard Controller: `app/Http/Controllers/EmployerDashboardController.php`
- Institution Admin Controller: `app/Http/Controllers/InstitutionAdminDashboardController.php`
- Super Admin Controller: `app/Http/Controllers/SuperAdminDashboardController.php`
- Auth Controllers: `app/Http/Controllers/Auth/`
- Login Page: `resources/js/Pages/Auth/Login.vue`
- Register Page: `resources/js/Pages/Auth/Register.vue`

## Multi-Tenant Architecture

The app uses a hybrid tenancy approach:

1. Stancl Tenancy v3 (partially configured)
2. Custom TenantContextService + TenantMiddleware

Tenant resolution is skipped on localhost for development.

## Testing Commands

```bash
# Run tests
.\artisan test --compact

# Run PHP linter
vendor\bin\pint --dirty

# Run TypeScript checks
pnpm run typecheck

# Fresh migrate
.\artisan migrate:fresh --seed
```

## Work Log

- **2026-02-19 00:43**: Mission started - Phase 1 critical fixes
