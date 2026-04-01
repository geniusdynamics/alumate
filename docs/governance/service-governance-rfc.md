# Service Governance RFC

## Status: DRAFT
## Created: 2026-04-01
## Priority: CRITICAL

## Problem Statement

The Alumate service layer has grown from 137 services to 182 services (+33%) in an uncontrolled manner. Many services exceed 1,000 lines, with some reaching 1,441 lines. This violates the single responsibility principle and makes the codebase unmaintainable.

## Current State

| Metric | Value | Target |
|--------|-------|--------|
| Total Services | 182 | <100 |
| Services >300 lines | 146 | 0 |
| Largest Service | 1,441 lines | 300 |
| Average Service Size | 490 lines | 200 |
| Services with >5 deps | 12 | 0 |

## Governance Rules (Effective Immediately)

### Rule 1: Service Size Limits
- **Maximum 300 lines** per service file
- **Maximum 15 public methods** per service
- **Maximum 5 constructor dependencies**

### Rule 2: Service Creation Process
1. **Check existing services** before creating new ones
2. **Create RFC** for any new service >100 lines
3. **Architecture review** required before merge
4. **Tests required** with 80%+ coverage

### Rule 3: Naming Conventions
- Domain services: `{Domain}Service` (e.g., `GraduateService`)
- Application services: `{Workflow}Service` (e.g., `OnboardingWorkflowService`)
- Infrastructure services: `{Technology}Service` (e.g., `RedisCacheService`)
- Orchestration services: `{Domain}OrchestrationService`

### Rule 4: Directory Structure
```
app/Services/
├── Analytics/          # Analytics domain services
├── CRM/               # CRM integration services
├── Email/             # Email domain services
├── Homepage/          # Homepage domain services
├── Integrations/      # Third-party integrations
└── [other domains]/   # Future domain services
```

### Rule 5: Decomposition Priority

| Service | Current Lines | Target | Priority |
|---------|--------------|--------|----------|
| AnalyticsMonitoringService | 1,227 | 250 | P0 |
| AnalyticsDataValidationService | 1,205 | 250 | P0 |
| AutomatedInsightsService | 1,165 | 250 | P0 |
| AnalyticsErrorHandlerService | 1,120 | 250 | P0 |
| AnalyticsDataExportImportService | 1,103 | 200 | P1 |
| AnalyticsDisasterRecoveryService | 1,044 | 250 | P1 |
| GoogleAnalyticsService | 1,038 | 250 | P1 |
| AnalyticsDashboardIntegrationService | 1,021 | 270 | P1 |
| AnalyticsComplianceReportingService | 996 | 250 | P1 |
| AnalyticsDataSyncService | 984 | 250 | P1 |

## Enforcement

### Automated Checks (CI/CD)
```bash
# Run before every merge
php scripts/governance/check-service-size.php
```

### Manual Reviews
- Quarterly service audits
- Architecture review for new services >100 lines
- Code review checklist includes service size check

## Migration Plan

### Phase 1: Freeze (Week 1)
- [x] Create governance RFC
- [x] Create automated governance checks
- [ ] Communicate freeze to all developers
- [ ] Block PRs that add services without RFC

### Phase 2: Consolidate (Weeks 2-4)
- [ ] Merge duplicate Analytics services into focused modules
- [ ] Create AnalyticsOrchestrationService as facade
- [ ] Delete redundant bloated services

### Phase 3: Enforce (Week 5+)
- [ ] Add governance checks to CI/CD pipeline
- [ ] Establish architecture review board
- [ ] Quarterly audit schedule

## Success Criteria

- [ ] All services under 300 lines
- [ ] Average service size under 200 lines
- [ ] Zero services with >5 constructor dependencies
- [ ] Service creation RFC process established
- [ ] 100% test coverage for critical services
- [ ] CI/CD checks enforcing governance rules
