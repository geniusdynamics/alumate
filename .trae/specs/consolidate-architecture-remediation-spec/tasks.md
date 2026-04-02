# Tasks

- [x] Task 1: Create remediation program baseline and operating model
  - [x] SubTask 1.1: Define program charter including scope boundaries and non-goals
  - [x] SubTask 1.2: Capture baseline system metrics (startup, p95/p99, DB query count, CI cycle time)
  - [x] SubTask 1.3: Publish ownership matrix across Architecture, Backend, Frontend, DevOps, QA
  - [x] SubTask 1.4: Define decision cadence for architecture board and escalation path
  - [x] SubTask 1.5: Create phase-gate sign-off template with required evidence artifacts

- [x] Task 2: Resolve tenancy mismatch and harden isolation controls (P0)
  - [x] SubTask 2.1: Confirm authoritative tenancy strategy and document target state
  - [x] SubTask 2.2: Align tenancy configuration to authoritative strategy
  - [x] SubTask 2.3: Remove or isolate overlapping custom context-switch paths
  - [x] SubTask 2.4: Define migration safeguards for schema/context switching
  - [x] SubTask 2.5: Add tenant isolation tests for read/write segregation
  - [x] SubTask 2.6: Add tenant isolation tests for queue propagation and cache namespace
  - [x] SubTask 2.7: Implement telemetry for tenant context failures and drift detection
  - [x] SubTask 2.8: Run staged rollout with rollback rehearsals and acceptance sign-off

- [ ] Task 3: Eliminate backend coupling and oversized class risk (P0)
  - [x] SubTask 3.1: Define decomposition boundaries for User domain responsibilities
  - [x] SubTask 3.2: Remove high-cost response appends from critical endpoints
  - [x] SubTask 3.3: Introduce explicit API resources for user and tenant response contracts
  - [ ] SubTask 3.4: Split analytics orchestration into bounded metric/report/export services
  - [ ] SubTask 3.5: Introduce DTO/contracts between controllers and services
  - [ ] SubTask 3.6: Replace high-risk query patterns with optimized equivalents
  - [ ] SubTask 3.7: Add regression tests for endpoint behavior and payload compatibility
  - [ ] SubTask 3.8: Add performance tests proving N+1 remediation on scoped routes

- [ ] Task 4: Refactor controller and routing architecture for maintainability (P1)
  - [x] SubTask 4.1: Inventory route groups by domain and access policy
  - [ ] SubTask 4.2: Extract orchestration logic from fat controllers into domain services
  - [ ] SubTask 4.3: Enforce request validation and resource response consistency
  - [x] SubTask 4.4: Define route module boundaries and ownership map
  - [ ] SubTask 4.5: Add integration tests for route behavior after modular split
  - [ ] SubTask 4.6: Validate no authorization regressions across role-protected routes

- [ ] Task 5: Modernize frontend architecture and enforce composition standards (P1)
  - [x] SubTask 5.1: Define canonical frontend folder policy and naming conventions
  - [x] SubTask 5.2: Resolve case-sensitivity conflicts in component directories and imports
  - [ ] SubTask 5.3: Classify components into UI, Domain, and Page orchestration layers
  - [ ] SubTask 5.4: Move shared primitives into UI layer and remove feature duplication
  - [ ] SubTask 5.5: Add typed store boundaries for global vs local state use cases
  - [ ] SubTask 5.6: Introduce lazy loading boundaries for heavy visual dependencies
  - [ ] SubTask 5.7: Validate rendering parity across critical pages and breakpoints
  - [ ] SubTask 5.8: Validate no TypeScript regressions from structure normalization

- [ ] Task 6: Optimize CI/CD and production runtime readiness (P1)
  - [x] SubTask 6.1: Build dependency caching strategy for Composer and pnpm
  - [ ] SubTask 6.2: Rebuild CI job graph to maximize parallel execution safely
  - [x] SubTask 6.3: Add explicit quality gates for lint, typecheck, tests, coverage, and security
  - [ ] SubTask 6.4: Create production runtime build profile using multi-stage images
  - [ ] SubTask 6.5: Define startup optimization profile (cache warmup, opcache/runtime tuning)
  - [ ] SubTask 6.6: Add deployment health checks and automatic rollback criteria
  - [ ] SubTask 6.7: Run rollback drills and verify release reliability thresholds

- [x] Task 7: Implement risk governance and operational controls (P1)
  - [x] SubTask 7.1: Create active risk register with owner, severity, and response plan
  - [x] SubTask 7.2: Define incident triggers and escalation mapping for each critical stream
  - [x] SubTask 7.3: Add change-freeze policy for high-risk release windows
  - [x] SubTask 7.4: Define communication plan for milestones, blockers, and rollback events

- [ ] Task 8: Execute phase-gate validation and program closeout
  - [x] SubTask 8.1: Validate phase 1 gates (stabilization) with evidence pack
  - [ ] SubTask 8.2: Validate phase 2 gates (decomposition) with contract/perf results
  - [ ] SubTask 8.3: Validate phase 3 gates (frontend modernization) with UX/perf evidence
  - [ ] SubTask 8.4: Validate phase 4 gates (delivery hardening) with CI/deploy metrics
  - [ ] SubTask 8.5: Compare final metrics to baseline and target thresholds
  - [ ] SubTask 8.6: Publish completion report with residual risks and deferred items

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 2
- Task 4 depends on Task 3
- Task 5 depends on Task 1 and can run in parallel with Task 4
- Task 6 depends on Task 1 and can run in parallel with Task 4 and Task 5
- Task 7 depends on Task 1 and runs continuously during Tasks 2-6
- Task 8 depends on Tasks 2, 3, 4, 5, 6, and 7

# Parallelization Notes
- Track A: Task 2 -> Task 3 -> Task 4
- Track B: Task 5 starts after Task 1
- Track C: Task 6 starts after Task 1
- Track D: Task 7 starts after Task 1 and supports all tracks
- Synchronization point: Task 8 begins after Track A/B/C completion and governance evidence from Track D

# Validation Expectations
- Every subtask must produce verifiable output (test results, metric deltas, or sign-off artifact).
- No task may be closed without checklist-linked evidence and owner approval.
- P0 tasks require rollback procedure verification before completion status is granted.
