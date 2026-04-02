# Migration Roadmap

## Phase 1: Stabilization & Fixes (Weeks 1-2)
**Goal**: Resolve critical architectural conflicts and performance killers.

- [ ] **Fix Tenancy Configuration**:
    - Update `config/tenancy.php` to match Schema-based implementation.
    - Refactor `TenantContextService` to use `stancl/tenancy` native features.
    - Verify migration path for existing tenants.
- [ ] **Fix User Model Performance**:
    - Remove `$appends` from `User` model.
    - Create `UserResource` for API responses.
    - Refactor `User::boot` logic to a background Job or Listener (Async).
- [ ] **Fix Frontend Casing**:
    - Rename `resources/js/Components/admin` to `Admin`.
    - Fix all import references.

## Phase 2: Modularization (Weeks 3-6)
**Goal**: decoupling the monolith.

- [ ] **Create Module Structure**:
    - Create `app/Modules` directory.
    - Define namespaces in `composer.json` (e.g., `App\Modules\`).
- [ ] **Extract Analytics Module**:
    - Move `AnalyticsService` and related models/controllers to `app/Modules/Analytics`.
    - Refactor "God Service" into smaller, focused services.
- [ ] **Refactor Controllers**:
    - Move logic from `InstitutionAdminDashboardController` to specific Service classes.

## Phase 3: Frontend Modernization (Weeks 7-10)
**Goal**: Improve maintainability and performance.

- [ ] **Component Restructuring**:
    - Create `resources/js/Components/UI` for atomic components.
    - Move domain components to `resources/js/Components/Domain/{Module}`.
- [ ] **Bundle Optimization**:
    - Implement Lazy Loading for `Chart.js` and `Leaflet` components.
    - Configure Vite splitChunks strategy.

## Phase 4: DevOps & CI/CD (Week 11)
**Goal**: Faster, reliable deployments.

- [ ] **Optimize GitHub Actions**:
    - Implement caching for `composer` and `pnpm`.
    - Combine build steps where possible.
- [ ] **Docker Production Build**:
    - Create optimized `Dockerfile` with Octane support.

## Zero-Downtime Transition Plan
1.  **Database**: Since we stick with PostgreSQL/Schemas, no massive data migration is needed. We just fix the *access pattern*.
2.  **Code Deployment**: Use "Blue/Green" deployment or Atomic Deployments (standard with Envoyer/Deployer) to switch codebases.
3.  **Feature Flags**: Use feature flags to roll out new Modules incrementally.
