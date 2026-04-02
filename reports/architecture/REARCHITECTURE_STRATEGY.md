# Re-architecture Strategy

## 1. Architectural Vision: Modular Monolith
We recommend transitioning from the current "Layered Monolith" (Controllers/Models/Services) to a **Modular Monolith** architecture. This organizes code by **Domain** (e.g., `Modules/Alumni`, `Modules/Tenancy`, `Modules/Reporting`) rather than by technical function. This approach improves cohesion, enforces boundaries, and simplifies future extraction to microservices if needed.

### 1.1 Backend Technology Stack
- **Framework**: **Laravel 12** (Retain).
    - *Justification*: The team is already invested in PHP/Laravel. Switching to Node.js/Go would require a complete rewrite and retraining, with minimal gain for the current scale. Laravel Octane can provide near-Node.js performance if needed.
- **Runtime**: **Laravel Octane (Swoole/FrankenPHP)**.
    - *Benefit*: Drastically reduces boot time and overhead for high-throughput API requests.
- **Database**: **PostgreSQL 17**.
    - *Tenancy Strategy*: **Standardize on Schema-based Tenancy** but fix the configuration conflict.

### 1.2 Frontend Technology Stack
- **Framework**: **Vue 3 + Inertia.js** (Retain).
    - *Refinement*: Strict Component Architecture (Atomic Design) and separation of concerns.
- **State Management**: **Pinia** (Standardize).
    - *Strategy*: Use Stores for global state (User, Tenant, Theme) and local state for UI interactions.
- **Bundler**: **Vite**.
    - *Optimization*: Implement automated chunk splitting and dynamic imports for heavy libraries.

## 2. Detailed Recommendations

### 2.1 Backend Refactoring
1.  **Resolve Tenancy Conflict**:
    - Update `config/tenancy.php` to explicitly use `Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager`.
    - Deprecate custom `TenantContextService` logic that overlaps with the package. Use the package's native methods for switching context.
2.  **Decompose "God Objects"**:
    - **User Model**:
        - Extract Profile logic to `UserProfile` model.
        - Extract Tenant logic to `TenantService`.
        - Remove `$appends` and use **API Resources** (`UserResource`) for serialization.
    - **Analytics Service**:
        - Split into `EngagementMetricsService`, `ReportingService`, `ExportService`.
        - Implement the **Strategy Pattern** for different report types.
3.  **Implement Service Layer Pattern**:
    - Services should handle business logic.
    - Controllers should handle HTTP request/response only.
    - Use **Data Transfer Objects (DTOs)** for passing data between layers.

### 2.2 Frontend Refactoring
1.  **Restructure Components**:
    - `components/ui` (Atomic: Buttons, Inputs)
    - `components/domain` (Business: `UserCard`, `JobList`)
    - `layouts` (Page wrappers)
2.  **Fix Casing Issues**: Rename all folders to `PascalCase` (e.g., `Components/Admin`) to ensure cross-platform compatibility.
3.  **Optimize Bundle**:
    - Use `defineAsyncComponent` for heavy widgets (Charts, Maps).
    - Audit `package.json` to remove unused dependencies.

### 2.3 Deployment & DevOps
1.  **Optimize CI/CD**:
    - Implement **Artifact Caching**: Build `vendor` and `node_modules` once, then share across jobs.
    - Use **Parallel Testing**: Run Unit and Feature tests in parallel containers.
2.  **Production Dockerfile**:
    - Create a multi-stage `Dockerfile` optimized for Octane.
    - Configure `opcache` for maximum performance.

## 3. Technology Comparison Matrix

| Feature | Current Stack (Laravel/Inertia) | Node.js (Express/NestJS) | Recommendation |
| :--- | :--- | :--- | :--- |
| **Development Speed** | High (Rapid Prototyping) | Medium (More boilerplate) | **Laravel** |
| **Performance** | Medium (Traditional PHP) | High (Event Loop) | **Laravel Octane** |
| **Ecosystem** | Mature (Admin Panels, SaaS) | Fragmented | **Laravel** |
| **Type Safety** | Low (PHP) / High (TS Frontend) | High (TypeScript everywhere) | **Laravel + PHPStan** |
| **Hiring** | Easy (PHP devs common) | Easy (JS devs common) | **Laravel** |

## 4. Success Metrics
- **Startup Time**: < 500ms (Backend), < 1s (Frontend TTI).
- **Code Coverage**: > 80% (Backend), > 50% (Frontend Components).
- **Deployment Frequency**: On-demand (via optimized CI/CD).
- **N+1 Queries**: 0 detected in critical paths.
