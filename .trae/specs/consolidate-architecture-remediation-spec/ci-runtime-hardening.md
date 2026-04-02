# CI and Runtime Hardening Controls

## Dependency Caching
- Composer cache + vendor restore
- pnpm lockfile-based cache restore

## Quality Gates
- PHP formatting gate
- ESLint gate
- TypeScript typecheck gate
- Import path case-sensitivity gate
- Unit, integration, feature, and full test suites
- Security dependency audit gates

## Runtime Build Profile
- Build artifact excludes transient and sensitive paths
- Production workflow enforces migration + tenant migration checks
- Deployment artifact generated from tested commit

## Health and Rollback
- Deployment gates rely on test and audit completion
- Rollback workflow remains available for failed release criteria
- Post-deploy health checks required before completion sign-off
