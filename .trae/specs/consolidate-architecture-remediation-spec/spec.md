# Consolidated Architecture Remediation Spec

## Why
The current architecture findings are split across multiple reports, which slows execution and creates ambiguity in ownership, sequencing, and validation. A single execution-grade specification is required to convert audit outcomes into prioritized, measurable, low-risk remediation work.

## What Changes
- Consolidate architecture audit issues, re-architecture guidance, and migration roadmap into one actionable remediation specification.
- Define a single prioritized backlog with severity, dependency, and sequencing metadata.
- Define target technical architecture standards for backend, frontend, tenancy, CI/CD, and runtime performance.
- Define phased migration milestones, acceptance criteria, and validation gates per phase.
- Define risk register, mitigation controls, rollback criteria, and zero-downtime release strategy.
- Define staffing model, resource requirements, and responsibility matrix by workstream.
- Define success metrics with baseline-to-target thresholds and measurement cadence.
- **BREAKING** Tenancy execution model must be standardized to schema-based tenancy using one authoritative implementation path.

## Impact
- Affected specs: Tenancy, User Management, Analytics, Frontend Composition, CI/CD, Deployment, Observability, Testing.
- Affected code: `config/tenancy.php`, `app/Services/TenantContextService.php`, `app/Models/User.php`, `app/Services/AnalyticsService.php`, `routes/web.php`, `routes/api.php`, `resources/js/Components`, `resources/js/Pages`, `vite.config.ts`, `.github/workflows/ci.yml`, Docker/production runtime configuration.

## Consolidated Findings Baseline

### Critical Findings (P0)
- Tenancy mismatch between configured manager and runtime behavior creates isolation and migration risk.
- Excessive model/service responsibilities (`User`, `AnalyticsService`) cause coupling, test fragility, and scale constraints.
- Potential N+1 from computed model appends and broad eager/lazy access patterns.

### High Findings (P1)
- Frontend structure inconsistency and casing conflicts risk CI breakage on Linux runners.
- Route and controller bloat reduce maintainability and team parallelism.
- CI pipeline redundancy increases cycle time and infrastructure cost.

### Medium Findings (P2)
- Bundle optimization strategy is partially manual and incomplete for heavy dependencies.
- Caching practices are inconsistent and not governed by workload profile.

## Target Technical Specification

### 1) Backend Architecture Standard
- Adopt modular monolith boundaries by domain:
  - `Modules/Tenancy`
  - `Modules/Identity`
  - `Modules/Analytics`
  - `Modules/Engagement`
  - `Modules/AdminOps`
- Enforce service boundary policy:
  - Controllers orchestrate I/O only.
  - Services contain business orchestration.
  - Model classes remain persistence-focused.
  - API Resources own response shaping.
- Decompose high-risk classes:
  - `User` split: identity core + profile projection + tenant-access service.
  - `AnalyticsService` split into metrics, report composition, export, and trend engines.

### 2) Tenancy Standard (Authoritative)
- Standardize on schema-based tenancy end-to-end.
- Use one tenancy context authority; deprecate parallel context-switch implementations.
- Add tenant isolation tests for:
  - read/write boundaries
  - queue context propagation
  - cache namespace separation
  - migration safety

### 3) Frontend Architecture Standard
- Normalize folder conventions and naming case policy.
- Enforce component layering:
  - `UI` (presentational),
  - `Domain` (feature components),
  - `Pages` (route orchestration).
- Introduce async boundaries for heavy widgets (maps/charts/editors).
- Standardize state contracts for global stores vs local component state.

### 4) Delivery Platform Standard
- CI optimizations:
  - shared dependency artifacts,
  - parallelized non-dependent jobs,
  - strict quality gates.
- Runtime optimizations:
  - production Docker multi-stage build,
  - opcache/runtime tuning,
  - startup and warm-cache strategy.
- Observability baseline:
  - startup latency,
  - request p95/p99,
  - DB query counts,
  - tenant isolation incident rate.

## Prioritized Remediation Backlog

| Priority | Work Item | Dependency | Owner Group | Exit Criteria |
|---|---|---|---|---|
| P0 | Resolve tenancy configuration/runtime mismatch | None | Backend Platform | Single tenancy authority, all isolation tests pass |
| P0 | Remove high-cost model appends causing query amplification | None | Backend Domain | N+1 eliminated on targeted endpoints |
| P0 | Split `AnalyticsService` into bounded services | Tenancy fix recommended first | Backend Analytics | Services under size/complexity thresholds, passing tests |
| P1 | Normalize frontend naming and component layering | None | Frontend Platform | Case-safe CI pass and import map stable |
| P1 | Refactor fat controller orchestration into service layer | P0 tenancy + service boundaries | Backend Domain | Route handlers slimmed with defined contracts |
| P1 | CI artifact and job graph optimization | None | DevOps | Pipeline duration reduction target met |
| P2 | Bundle optimization and lazy boundaries | Frontend structure normalization | Frontend Platform | Initial payload and TTI targets achieved |
| P2 | Cache governance policy rollout | Tenancy authority unified | Backend Platform | Cache hit ratio and correctness thresholds met |

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation | Trigger for Rollback |
|---|---|---|---|---|
| Tenancy regression during context unification | Medium | Critical | Parallel shadow validation + tenant smoke matrix | Any cross-tenant data leak or failed tenant migration |
| Behavioral drift during service decomposition | High | High | Contract tests + golden endpoint snapshots | API contract mismatch on protected routes |
| Frontend refactor import instability | Medium | Medium | Batch rename + static import checks + CI on Linux | Build failure in protected branch |
| CI optimization causing hidden test omissions | Low | High | Explicit test inventory + gate assertions | Missing required suite execution |
| Performance tuning regressions under load | Medium | High | Before/after load test baselines | p95 latency worse than baseline by >10% |

## Resource Requirements

### Team Composition
- 1 Staff/Lead Architect (cross-stream governance)
- 2 Backend Engineers (tenancy + decomposition)
- 2 Frontend Engineers (structure + bundle/perf)
- 1 DevOps Engineer (CI/CD + runtime)
- 1 QA/SDET (automation + regression + tenant isolation)

## RACI Table

| Workstream | Responsible (R) | Accountable (A) | Consulted (C) | Informed (I) |
|---|---|---|---|---|
| Tenancy standardization and isolation hardening | Backend Engineers | Staff/Lead Architect | DevOps Engineer, QA/SDET | Frontend Engineers |
| Backend decomposition (`User`, `AnalyticsService`, controller slimming) | Backend Engineers | Staff/Lead Architect | QA/SDET | DevOps Engineer, Frontend Engineers |
| Frontend architecture normalization and async boundaries | Frontend Engineers | Staff/Lead Architect | Backend Engineers, QA/SDET | DevOps Engineer |
| CI/CD graph and runtime optimization | DevOps Engineer | Staff/Lead Architect | Backend Engineers, Frontend Engineers, QA/SDET | All delivery stakeholders |
| Validation governance, regression strategy, and acceptance evidence | QA/SDET | Staff/Lead Architect | Backend Engineers, Frontend Engineers, DevOps Engineer | All delivery stakeholders |

### Environment/Tooling
- Staging environment with production-like tenancy footprint
- Load-testing capability (startup, API p95/p99, DB query profiles)
- Coverage and static-analysis gates in CI
- Release controls: feature flags + blue/green or atomic deployment strategy

## Success Metrics

### Architecture Quality
- Tenant isolation incidents: `0`
- High-severity architecture findings unresolved: `< 2` by end of phase 2
- Endpoint contract breakage in remediation stream: `0`

### Performance
- Backend cold-start or first-request startup: `< 500ms` target path
- Frontend TTI (key dashboard/home routes): `< 1.0s` target in benchmark profile
- p95 API latency on priority endpoints: `>= 20%` improvement from baseline
- N+1 regressions on scoped endpoints: `0`

### Delivery Efficiency
- CI total duration: `>= 30%` reduction from baseline
- Mean PR validation turnaround: `>= 25%` reduction
- Deployment failure rate: `< 5%` per release window

## Phased Implementation Plan

### Phase 1: Stabilization and Risk Containment
- Scope:
  - unify tenancy execution path,
  - remove high-risk append/query hotspots,
  - enforce frontend casing normalization.
- Milestones:
  - M1.1 tenancy authority unified
  - M1.2 tenant isolation suite green
  - M1.3 model serialization hotspots remediated
  - M1.4 cross-platform frontend build stability
- Validation criteria:
  - zero isolation breaches,
  - target endpoint query count reduced,
  - Linux CI frontend build passes repeatedly.

### Phase 2: Structural Decomposition
- Scope:
  - split oversized backend services/models,
  - enforce service/controller boundaries,
  - route responsibility cleanup.
- Milestones:
  - M2.1 analytics bounded services introduced
  - M2.2 user-domain responsibilities redistributed
  - M2.3 controller orchestration slimmed and contract-verified
- Validation criteria:
  - class complexity thresholds met,
  - endpoint contract tests green,
  - no functional regression in critical flows.

### Phase 3: Frontend and Performance Modernization
- Scope:
  - component layering standard,
  - async boundaries for heavy dependencies,
  - Vite/bundle optimization.
- Milestones:
  - M3.1 component architecture normalized
  - M3.2 lazy-loading strategy rolled out
  - M3.3 startup and TTI performance goals reached
- Validation criteria:
  - payload and TTI targets achieved,
  - no new route-level rendering regressions,
  - performance CI checks green.

### Phase 4: Delivery Platform Hardening
- Scope:
  - CI graph optimization,
  - artifact sharing,
  - production runtime hardening and release controls.
- Milestones:
  - M4.1 CI duration reduction achieved
  - M4.2 production container/runtime profile deployed
  - M4.3 deployment reliability SLOs achieved
- Validation criteria:
  - CI metrics targets met for rolling 2-week window,
  - release rollback drills successful,
  - post-deploy incidents below threshold.

## Milestone Acceptance Test Matrix

| Milestone | Acceptance Tests | Evidence Required | Exit Gate |
|---|---|---|---|
| M1.1 tenancy authority unified | Tenant context resolution tests, schema-switch smoke tests, negative cross-tenant access tests | Test run report + configuration diff + tenant boundary log sample | No cross-tenant read/write observed in test suite |
| M1.2 tenant isolation suite green | Read/write boundary suite, queue propagation suite, cache namespace suite, tenant migration safety suite | Isolation matrix report + CI artifact links | 100% pass on mandatory isolation suites |
| M1.3 model serialization hotspots remediated | Query-count regression tests for prioritized endpoints, serialization contract tests | Before/after query profile + test report | Targeted endpoints show reduced query amplification with no contract drift |
| M1.4 cross-platform frontend build stability | Linux CI build, case-sensitivity import checks, route smoke tests | CI pipeline logs + artifact checksums | Repeated Linux builds pass without import/casing failures |
| M2.1 analytics bounded services introduced | Unit tests per service boundary, feature tests for analytics endpoints, contract snapshot comparison | Coverage delta + endpoint snapshot diff | All analytics endpoint contracts preserved and suites green |
| M2.2 user-domain responsibilities redistributed | User domain unit tests, authorization and tenant access feature tests | Responsibility map + test evidence bundle | No auth/tenant regression in user-critical flows |
| M2.3 controller orchestration slimmed and contract-verified | HTTP feature tests on refactored routes, controller thinness/static analysis checks | Route test report + static analysis output | Refactored controllers meet boundary policy and endpoint behavior parity |
| M3.1 component architecture normalized | Component structure lint checks, import path consistency checks, visual smoke tests | Lint report + route-level smoke output | Component layering policy validated on targeted modules |
| M3.2 lazy-loading strategy rolled out | Bundle split verification, lazy route/component load tests, hydration/render smoke tests | Bundle analyzer report + runtime load traces | Heavy components load asynchronously without regression |
| M3.3 startup and TTI performance goals reached | Synthetic TTI benchmark tests, startup latency checks, p95 API benchmark comparison | Performance baseline vs target report | TTI/startup/p95 targets meet or exceed phase thresholds |
| M4.1 CI duration reduction achieved | CI workflow timing comparison across rolling runs, job dependency graph validation | CI metrics dashboard export | CI duration reduction target met for 2-week window |
| M4.2 production container/runtime profile deployed | Container build reproducibility tests, runtime health probes, warm-cache startup tests | Deployment manifest + probe logs | Production runtime profile stable under expected load |
| M4.3 deployment reliability SLOs achieved | Deployment canary checks, rollback drill tests, post-deploy synthetic monitoring | Incident/release report + rollback drill evidence | Deployment failure rate below threshold and rollback verified |

## Validation and Governance Model
- Define architecture gate per phase with mandatory sign-off.
- Require passing checks:
  - targeted test suites,
  - tenant isolation matrix,
  - performance benchmark delta,
  - contract compatibility tests.
- Require completion evidence artifacts:
  - metric snapshots,
  - risk log updates,
  - rollback rehearsal outcomes.

## ADDED Requirements

### Requirement: Consolidated Remediation Execution Document
The system SHALL provide one authoritative remediation specification that combines audit issues, architecture decisions, migration sequencing, risk controls, and measurable success criteria.

#### Scenario: Stakeholder planning and execution kickoff
- **WHEN** engineering and delivery leads prepare architecture remediation execution
- **THEN** they use one consolidated spec containing prioritized tasks, milestones, resources, and validation criteria

### Requirement: Phase-Gated Validation
The system SHALL enforce phase completion gates with explicit validation criteria before progressing to the next phase.

#### Scenario: Phase transition approval
- **WHEN** a phase is marked complete
- **THEN** all listed validation criteria and quality gates are verified and recorded

### Requirement: Risk-Controlled Migration
The system SHALL define rollback triggers, mitigation controls, and zero-downtime transition mechanisms for each high-impact architecture change.

#### Scenario: High-risk change rollout
- **WHEN** tenancy or decomposition changes are released
- **THEN** rollback criteria and mitigation controls are active and auditable

## MODIFIED Requirements

### Requirement: Architecture Planning Artifacts
Architecture planning SHALL move from multiple independent reports to a single authoritative execution specification with linked tasks and checkpoints.

## REMOVED Requirements

### Requirement: Fragmented Execution Planning
**Reason**: Multiple disconnected planning artifacts increase ambiguity, create sequencing conflicts, and weaken accountability.
**Migration**: Existing audit, strategy, and roadmap content is preserved as references and consolidated into this specification for execution.
