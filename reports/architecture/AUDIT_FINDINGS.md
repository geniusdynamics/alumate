# Architectural Audit Report

## Executive Summary
This audit reveals a **Classic Monolithic Application** with significant **"God Object" coupling** and a **conflicting Multi-tenancy Architecture**. The backend suffers from tight coupling between Models and Business Logic, leading to performance bottlenecks (N+1 queries) and maintainability issues. The frontend, while functional, lacks consistent organization and component hierarchy, leading to a sprawling codebase. The critical finding is a conflict between the `stancl/tenancy` package configuration (Database-based) and the custom `TenantContextService` implementation (Schema-based).

## 1. Backend Architecture (Laravel)

### 1.1 Model Complexity & "God Objects"
- **Critical Issue**: The `User` model (`app/Models/User.php`) violates the Single Responsibility Principle (SRP). It handles Authentication, Authorization (RBAC + Tenant Logic), Activity Logging, Profile helpers, Tenant management logic, Statistics calculation, and relationships to virtually every other entity.
- **Performance Risk**: The `User` model uses `$appends` extensively (`full_name`, `initials`, `avatar_url`, `accessible_tenants`, `current_tenant_role`). Specifically, `accessible_tenants` performs database queries every time a `User` model is serialized to JSON. This guarantees **N+1 query problems** when listing users.
- **Business Logic Leakage**: Business logic (e.g., `bulkInviteToTenant`, `addToTenant`) is embedded directly in the Model rather than in dedicated Service classes.

### 1.2 Service Layer Analysis
- **"God Service" Anti-pattern**: The `AnalyticsService` (`app/Services/AnalyticsService.php`) is a massive class (1200+ lines) handling everything from Engagement Metrics to KPI Calculation, Report Generation, and Export logic. It lacks cohesion and is hard to test.
- **Duplicate Logic**: There are multiple service implementations (e.g., `AnalyticsService` at root vs `Analytics/AnalyticsService.php`) leading to confusion about the source of truth.
- **Inefficient Queries**: Services rely heavily on `DB::raw` and inefficient collection operations (e.g., `pluck('id')` then `whereIn` for large datasets), which will not scale.

### 1.3 Multi-Tenancy Conflict
- **Critical Configuration Mismatch**:
    - **Implementation**: The custom `TenantContextService` (`app/Services/TenantContextService.php`) implements **Schema-based** tenancy (PostgreSQL Schemas) using `SET search_path`.
    - **Configuration**: The `config/tenancy.php` file is configured for **Database-based** tenancy (`PostgreSQLDatabaseManager`).
    - **Impact**: This conflict risks data corruption, migration failures (migrations running in the wrong database context), and severe bugs in tenant isolation. The application is fighting its own framework configuration.

### 1.4 Controller Bloat
- **Fat Controllers**: Controllers like `InstitutionAdminDashboardController` handle too many responsibilities, acting as data aggregators for complex dashboards rather than delegating to specialized View Models or dedicated Services.
- **Route Clutter**: `routes/web.php` and `api.php` contain hundreds of routes, making it difficult to understand the API surface area.

## 2. Frontend Architecture (Vue + Inertia)

### 2.1 Code Organization
- **Inconsistent Structure**:
    - `resources/js/Components` contains a mix of atomic components, domain-specific widgets, and full page sections.
    - Casing inconsistencies exist (e.g., `Components/admin` vs `Components/Admin`), which will cause build failures on case-sensitive file systems (Linux/CI).
    - Top-level pages are mixed with domain folders in `resources/js/Pages`.
- **Component Design**:
    - Lack of clear "Smart" (Container) vs "Dumb" (Presentational) component separation. Components often contain heavy business logic and API calls.

### 2.2 Performance
- **Bundle Size**: The current `vite.config.ts` attempts manual chunking, but the monolithic nature of the imports and the lack of lazy-loading for heavy components (e.g., `Chart.js`, `Leaflet`) likely results in a large initial bundle size.
- **State Management**: While `Pinia` is installed, state management appears fragmented, with much state likely managed locally in components or via prop drilling.

## 3. Infrastructure & DevOps

### 3.1 Docker & Environment
- **Standard Setup**: Uses Laravel Sail (`docker-compose.yml`), which is good for development but lacks a production-ready `Dockerfile` optimization (e.g., multi-stage builds).
- **CI/CD Inefficiency**: The GitHub Actions workflow installs dependencies (`composer install`, `npm install`) repeatedly across multiple jobs instead of sharing artifacts, increasing build times and cost.

### 3.2 Scalability
- **Database**: PostgreSQL 17 is a solid choice, but the Schema-based tenancy model (while efficient for resources) can lead to migration headaches at scale (thousands of tenants = thousands of schemas to migrate).
- **Caching**: Redis is configured but implementation details in Services (`Cache::remember`) are inconsistent.
