# Consolidated Architecture Remediation - Implementation Tracker

## Active Phase
- Phase: 1 - Stabilization and Risk Containment
- Started: 2026-03-09
- Status: In Progress

## Task Progress

### Task 1: Create remediation program baseline and operating model
- [x] SubTask 1.1: Define program charter including scope boundaries and non-goals
- [x] SubTask 1.2: Capture baseline system metrics (startup, p95/p99, DB query count, CI cycle time)
- [x] SubTask 1.3: Publish ownership matrix across Architecture, Backend, Frontend, DevOps, QA
- [x] SubTask 1.4: Define decision cadence for architecture board and escalation path
- [x] SubTask 1.5: Create phase-gate sign-off template with required evidence artifacts

### Task 2: Resolve tenancy mismatch and harden isolation controls (P0)
- [x] SubTask 2.1: Confirm authoritative tenancy strategy and document target state
- [x] SubTask 2.2: Align tenancy configuration to authoritative strategy
- [x] SubTask 2.3: Remove or isolate overlapping custom context-switch paths
- [x] SubTask 2.4: Define migration safeguards for schema/context switching
- [x] SubTask 2.5: Add tenant isolation tests for read/write segregation
- [x] SubTask 2.6: Add tenant isolation tests for queue propagation and cache namespace
- [x] SubTask 2.7: Implement telemetry for tenant context failures and drift detection
- [x] SubTask 2.8: Run staged rollout with rollback rehearsals and acceptance sign-off

### Task 7: Implement risk governance and operational controls (P1)
- [x] SubTask 7.1: Create active risk register with owner, severity, and response plan
- [x] SubTask 7.2: Define incident triggers and escalation mapping for each critical stream
- [x] SubTask 7.3: Add change-freeze policy for high-risk release windows
- [x] SubTask 7.4: Define communication plan for milestones, blockers, and rollback events

### Task 3: Eliminate backend coupling and oversized class risk (P0)
- [x] SubTask 3.1: Define decomposition boundaries for User domain responsibilities
- [x] SubTask 3.2: Remove high-cost response appends from critical endpoints
- [x] SubTask 3.3: Introduce explicit API resources for user and tenant response contracts

### Task 4: Refactor controller and routing architecture for maintainability (P1)
- [x] SubTask 4.1: Inventory route groups by domain and access policy
- [x] SubTask 4.4: Define route module boundaries and ownership map

### Task 5: Modernize frontend architecture and enforce composition standards (P1)
- [x] SubTask 5.1: Define canonical frontend folder policy and naming conventions
- [x] SubTask 5.2: Resolve case-sensitivity conflicts in component directories and imports

### Task 6: Optimize CI/CD and production runtime readiness (P1)
- [x] SubTask 6.1: Build dependency caching strategy for Composer and pnpm
- [x] SubTask 6.3: Add explicit quality gates for lint, typecheck, tests, coverage, and security

### Task 8: Execute phase-gate validation and program closeout
- [x] SubTask 8.1: Validate phase 1 gates (stabilization) with evidence pack

## Decisions Logged
- 2026-03-09: Schema-based tenancy is set as authoritative for PostgreSQL tenant management.
- 2026-03-09: Initial implementation scope constrained to low-risk configuration alignment and tracking kickoff.
- 2026-03-09: Cross-tenant middleware now delegates schema switching to TenantContextService as the single runtime authority.
- 2026-03-09: Middleware aliasing is disambiguated with `tenant` for tenant resolution and `tenant.cross` for cross-tenant operations.
- 2026-03-09: Safeguards introduce schema-name validation, optional schema existence enforcement, and tenant context failure telemetry.
- 2026-03-09: User profile API now uses dedicated resources and explicit tenant response contracts.
- 2026-03-09: Frontend import-case guard is introduced in CI to prevent Linux case-sensitivity regressions.

## Evidence
- Updated file: `config/tenancy.php`
- Updated file: `app/Services/TenantContextService.php`
- Updated file: `app/Http/Middleware/CrossTenantMiddleware.php`
- Updated file: `app/Providers/TenancyServiceProvider.php`
- Added test: `tests/Feature/Tenancy/TenancyConfigurationTest.php`
- Added test: `tests/Feature/Tenancy/TenancyAuthorityContractsTest.php`
- Added test: `tests/Feature/Tenancy/TenancySafeguardsTest.php`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/baseline-metrics.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/ownership-matrix.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/phase-gate-template.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/risk-register.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/operational-controls.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/rollout-and-rollback-drill.md`
- Updated file: `app/Models/User.php`
- Updated file: `app/Http/Controllers/Api/UserController.php`
- Added file: `app/Http/Resources/UserProfileResource.php`
- Added file: `app/Http/Resources/TenantSummaryResource.php`
- Added test: `tests/Feature/Api/UserProfileApiResourceTest.php`
- Added file: `scripts/frontend/check-import-case.mjs`
- Updated file: `.github/workflows/ci.yml`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/backend-decomposition-boundaries.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/route-module-boundaries.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/frontend-architecture-policy.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/ci-runtime-hardening.md`
- Added artifact: `.trae/specs/consolidate-architecture-remediation-spec/phase-gate-validation.md`
