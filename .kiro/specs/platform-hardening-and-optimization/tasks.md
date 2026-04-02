# Implementation Plan: Alumate Platform Hardening & Optimization

## Overview

This implementation plan breaks down the Alumate platform hardening and optimization into discrete, incremental coding tasks. The platform has 52 feature areas that already exist — this plan ensures they all work correctly, perform well, and are maintainable.

The plan follows a phased approach:
1. **Foundation** — Route reorganization, service deduplication, N+1 fixes
2. **Frontend** — Complete Template/Brand/LandingPage UI wiring
3. **Performance** — Caching, lazy loading, bundle optimization
4. **Quality** — Code cleanup, security hardening, tenant verification
5. **Deployment** — Docker, CI/CD, documentation

Each task builds on previous work, with parallel execution where possible.

## Task ID Convention

- **R1.x** — Route Reorganization tasks
- **R2.x** — Service Deduplication tasks
- **R3.x** — N+1 Query Resolution tasks
- **R4.x** — Frontend Foundation tasks
- **R5.x** — Code Quality tasks
- **R6.x** — Performance Optimization tasks
- **R7.x** — Tenant Isolation tasks
- **R8.x** — Docker & Deployment tasks

---

## Phase 1: Foundation — Route Reorganization

### R1: API Route Reorganization | agent: Worker | size: XL | depends: None

- [ ] R1.1: Create routes/api/ directory structure
  - Create 27 domain-specific route files in routes/api/
  - Files: auth.php, posts.php, alumni.php, career.php, events.php, mentorship.php, skills.php, search.php, notifications.php, messaging.php, fundraising.php, scholarships.php, forums.php, video.php, email.php, analytics.php, components.php, templates.php, landing-pages.php, brand.php, admin.php, crm.php, subscriptions.php, security.php, privacy.php, system.php, webhooks.php
  - _Requirements: 1.1, 1.3_

- [ ] R1.2: Extract authentication & user routes to routes/api/auth.php
  - Move /user, /user/profile, /push/* routes from api.php
  - Preserve all middleware (auth:sanctum, api.rate_limit)
  - Preserve all named routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.3: Extract social timeline routes to routes/api/posts.php
  - Move posts, timeline, post engagement routes
  - Include media upload routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.4: Extract alumni directory routes to routes/api/alumni.php
  - Move alumni directory, map, recommendations routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.5: Extract career & job routes to routes/api/career.php
  - Move career timeline, job matching, applications, saved jobs routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.6: Extract event routes to routes/api/events.php
  - Move events, reunions, check-in, networking routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.7: Extract mentorship routes to routes/api/mentorship.php
  - Move mentor profiles, requests, sessions, coffee chat routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.8: Extract skills & learning routes to routes/api/skills.php
  - Move skills, endorsements, learning resources routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.9: Extract search routes to routes/api/search.php
  - Move advanced search, saved searches, search alerts routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.10: Extract notification routes to routes/api/notifications.php
  - Move notification CRUD, preferences, unread count routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.11: Extract messaging routes to routes/api/messaging.php
  - Move conversations, messages, typing indicators routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.12: Extract fundraising & donation routes to routes/api/fundraising.php
  - Move campaigns, donations, recurring donations, tax receipts routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.13: Extract scholarship routes to routes/api/scholarships.php
  - Move scholarship CRUD, applications, review routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.14: Extract forum routes to routes/api/forums.php
  - Move forums, topics, posts, tags, moderation routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.15: Extract video call routes to routes/api/video.php
  - Move video calls, participants, recordings, screen sharing routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.16: Extract email marketing routes to routes/api/email.php
  - Move email campaigns, templates, sequences, automation routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.17: Extract analytics routes to routes/api/analytics.php
  - Move analytics events, insights, custom events, cohort analysis routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.18: Extract component & template routes to routes/api/components.php + routes/api/templates.php
  - Move component CRUD, themes, instances, template management, variants, A/B tests routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.19: Extract landing page & brand routes to routes/api/landing-pages.php + routes/api/brand.php
  - Move landing pages, submissions, brand logos, colors, fonts, guidelines routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.20: Extract admin routes to routes/api/admin.php
  - Move super admin, institution admin, staff management routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.21: Extract CRM & webhook routes to routes/api/crm.php + routes/api/webhooks.php
  - Move CRM integrations, sync, webhook delivery routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.22: Extract subscription routes to routes/api/subscriptions.php
  - Move plans, billing, invoices, payment methods routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.23: Extract security & privacy routes to routes/api/security.php + routes/api/privacy.php
  - Move security events, audit logs, consent, GDPR, data export/deletion routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.24: Extract system routes to routes/api/system.php
  - Move backups, exports, migrations, health checks, statistics routes
  - _Requirements: 1.2, 1.4, 1.5_

- [ ] R1.25: Rewrite routes/api.php to include domain files only
  - Reduce api.php to under 100 lines
  - Use glob() or explicit require for each domain file
  - Keep health check endpoints (ping, health)
  - _Requirements: 1.6_

- [ ] R1.26: Verify all named routes preserved after reorganization
  - Run `php artisan route:list` before and after
  - Compare named routes — must be identical
  - _Requirements: 1.4_

- [ ] R1.27: Run full test suite after route reorganization
  - Run `php artisan test --compact`
  - All tests must pass (zero regressions)
  - _Requirements: 1.2_

### R1-REVIEW: Route Reorganization Verification | agent: Reviewer | depends: R1.27

- [ ] R1.28: Verify route file count and organization
  - Confirm 27+ domain files exist in routes/api/
  - Confirm api.php is under 100 lines
  - Confirm all routes resolve correctly
  - _Requirements: 1.1, 1.6_

- [ ] R1.29: Verify zero breaking changes
  - Test 10 random API endpoints before/after
  - Confirm identical responses
  - _Requirements: 1.2_

---

## Phase 2: Service Deduplication

### R2: Service Layer Deduplication | agent: Worker | size: L | depends: R1.27

- [ ] R2.1: Audit all service files for duplicates
  - Scan app/Services/ for overlapping responsibilities
  - Document all duplicate pairs with file paths
  - _Requirements: 2.1_

- [ ] R2.2: Merge ABTestingService into AbTestService
  - Move all unique methods from ABTestingService to AbTestService
  - Update all references to ABTestingService
  - Delete ABTestingService.php
  - _Requirements: 2.2_

- [ ] R2.3: Consolidate Email services (EmailMarketingService, EmailDeliveryService, EmailSendingService)
  - Create unified EmailService with sub-methods for marketing, delivery, sending
  - Update all references
  - Delete original three files
  - _Requirements: 2.3_

- [ ] R2.4: Merge ComponentRenderService into ComponentService
  - Move render logic into ComponentService
  - Update all references
  - Delete ComponentRenderService.php
  - _Requirements: 2.3_

- [ ] R2.5: Organize services into domain subdirectories
  - Create Analytics/, CRM/, Email/, Integrations/, Homepage/ subdirectories
  - Move services to appropriate directories
  - Update all namespace declarations
  - Update all import statements across the codebase
  - _Requirements: 2.6_

- [ ] R2.6: Run full test suite after service deduplication
  - Run `php artisan test --compact`
  - All tests must pass
  - _Requirements: 2.5_

### R2-REVIEW: Service Deduplication Verification | agent: Reviewer | depends: R2.6

- [ ] R2.7: Verify zero duplicate services remain
  - Scan app/Services/ for overlapping responsibilities
  - Confirm all services have single responsibility
  - _Requirements: 2.1_

- [ ] R2.8: Verify service directory organization
  - Confirm domain subdirectories exist and are populated
  - Confirm no services remain at root that belong in subdirectories
  - _Requirements: 2.6_

---

## Phase 3: N+1 Query Resolution

### R3: N+1 Query Resolution | agent: Worker | size: L | depends: R2.6

- [ ] R3.1: Add eager loading to AlumniRecommendationService
  - Add with(['circles', 'groups', 'location']) to all queries
  - _Requirements: 3.2_

- [ ] R3.2: Add eager loading to AlumniMapService
  - Add with(['location', 'institution']) to all queries
  - _Requirements: 3.3_

- [ ] R3.3: Add eager loading to CareerTimelineService
  - Add with(['milestones', 'experiences']) to all queries
  - _Requirements: 3.4_

- [ ] R3.4: Add eager loading to JobController analytics
  - Add with(['applications', 'matches', 'skills']) to all queries
  - _Requirements: 3.1_

- [ ] R3.5: Add eager loading to EventsController analytics
  - Add with(['attendees', 'feedback', 'highlights']) to all queries
  - _Requirements: 3.5_

- [ ] R3.6: Add eager loading to PostController timeline
  - Add with(['user', 'engagements', 'comments']) to all queries
  - _Requirements: 3.5_

- [ ] R3.7: Audit all list endpoints for N+1 patterns
  - Review all index() methods in controllers
  - Add eager loading where relationships are accessed in loops
  - _Requirements: 3.5_

- [ ] R3.8: Run full test suite after N+1 fixes
  - Run `php artisan test --compact`
  - All tests must pass
  - _Requirements: 3.5_

### R3-REVIEW: N+1 Resolution Verification | agent: Reviewer | depends: R3.8

- [ ] R3.9: Verify query count reduction
  - Use Laravel Debugbar or query log to count queries
  - Confirm each fixed endpoint executes ≤5 queries
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

---

## Phase 4: Frontend Foundation Completion

### R4: Frontend Wiring & Integration | agent: Worker | size: XL | depends: R3.8

- [ ] R4.1: Add web routes for Template System pages
  - Add routes in routes/web.php for /templates, /templates/{id}/edit, /templates/create
  - Connect to Inertia pages
  - _Requirements: 4.1, 4.2_

- [ ] R4.2: Add web routes for Brand Management pages
  - Add routes in routes/web.php for /brand, /brand/{section}
  - Connect to Inertia pages
  - _Requirements: 4.3_

- [ ] R4.3: Add web routes for Landing Page pages
  - Add routes in routes/web.php for /landing-pages, /landing-pages/{id}/edit
  - Connect to Inertia pages
  - _Requirements: 4.4_

- [ ] R4.4: Add web routes for Analytics Dashboard
  - Add routes in routes/web.php for /analytics
  - Connect to Inertia pages
  - _Requirements: 4.5_

- [ ] R4.5: Add web routes for A/B Tests
  - Add routes in routes/web.php for /ab-tests
  - Connect to Inertia pages
  - _Requirements: 4.5_

- [ ] R4.6: Wire TemplateLibrary to TemplateStore
  - Connect fetchTemplates, createTemplate, deleteTemplate to actual API calls
  - Add loading, error, and empty states
  - _Requirements: 4.1, 4.6_

- [ ] R4.7: Wire BrandManager to BrandStore
  - Connect uploadLogo, createColor, createFont, updateGuidelines to actual API calls
  - Add loading, error, and empty states
  - _Requirements: 4.3, 4.6_

- [ ] R4.8: Wire LandingPages Index to LandingPageStore
  - Connect fetchLandingPages, publishLandingPage, unpublishLandingPage to actual API calls
  - Add loading, error, and empty states
  - _Requirements: 4.4, 4.6_

- [ ] R4.9: Wire AnalyticsDashboard to AnalyticsStore
  - Connect fetchTemplateAnalytics, fetchLandingPageAnalytics, fetchBrandAnalytics
  - Add loading, error, and empty states
  - _Requirements: 4.5, 4.6_

- [ ] R4.10: Wire ABTestManager to AnalyticsStore
  - Connect fetchABTests, fetchABTestResults to actual API calls
  - Add loading, error, and empty states
  - _Requirements: 4.5, 4.6_

- [ ] R4.11: Create TemplateEditor with GrapeJS integration
  - Create TemplateEditor.vue component with GrapeJS canvas
  - Connect to TemplateStore for save/load
  - Add preview panel
  - _Requirements: 4.2_

- [ ] R4.12: Create TemplateCustomizer component
  - Create component for applying brand to templates
  - Connect color picker, font selector to BrandStore
  - _Requirements: 4.2_

- [ ] R4.13: Create LandingPageBuilder component
  - Create page builder with section management
  - Connect to LandingPageStore for CRUD
  - Add live preview
  - _Requirements: 4.4_

- [ ] R4.14: Create ResponsivePreview component
  - Create mobile/tablet/desktop preview frames
  - Connect to BrandStore for live brand application
  - _Requirements: 4.3_

- [ ] R4.15: Run frontend build verification
  - Run `pnpm run build`
  - Build must succeed with zero errors
  - _Requirements: 4.6_

### R4-REVIEW: Frontend Verification | agent: Reviewer | depends: R4.15

- [ ] R4.16: Verify all components are wired to stores and API
  - Confirm all 10+ components use Pinia stores
  - Confirm all API calls use typed service layer
  - _Requirements: 4.6, 4.7_

- [ ] R4.17: Verify loading/error/empty states
  - Confirm all components handle loading state
  - Confirm all components handle error state
  - Confirm all components handle empty state
  - _Requirements: 4.6_

---

## Phase 5: Code Quality & Security

### R5: Code Quality Hardening | agent: Worker | size: M | depends: R4.15

- [ ] R5.1: Replace all alert() calls with toast notifications
  - Search for alert() across all Vue/JS files
  - Replace with toast.success(), toast.error(), toast.warning()
  - _Requirements: 5.1_

- [ ] R5.2: Remove all console.log from production code
  - Search for console.log across all Vue/JS files
  - Remove or replace with proper logging service
  - _Requirements: 5.2_

- [ ] R5.3: Consolidate admin layouts into single AdminLayout.vue
  - Identify all admin layout variants
  - Merge into single AdminLayout with configurable slots
  - Update all pages to use unified layout
  - _Requirements: 5.3_

- [ ] R5.4: Add null guards to Super Admin stats
  - Review all Super Admin dashboard components
  - Add null checks and fallback values for all optional stats
  - _Requirements: 5.4_

- [ ] R5.5: Fix sticky-mobile class issue
  - Identify and fix CSS class conflict
  - _Requirements: 5.5_

- [ ] R5.6: Run Pint on all PHP files
  - Run `vendor/bin/pint`
  - All files must pass formatting checks
  - _Requirements: 5.6_

- [ ] R5.7: Run ESLint on all JS/TS files
  - Run `pnpm run lint`
  - All files must pass linting
  - _Requirements: 5.6_

### R5-REVIEW: Code Quality Verification | agent: Reviewer | depends: R5.7

- [ ] R5.8: Verify zero alert() calls remain
  - Search codebase for alert() — must be zero results
  - _Requirements: 5.1_

- [ ] R5.9: Verify zero console.log in production
  - Search codebase for console.log — must be zero results
  - _Requirements: 5.2_

- [ ] R5.10: Verify single admin layout
  - Confirm only one AdminLayout.vue exists
  - _Requirements: 5.3_

---

## Phase 6: Performance Optimization

### R6: Performance Optimization | agent: Worker | size: M | depends: R5.7

- [ ] R6.1: Add Redis caching for template structures
  - Cache template structures with 5-minute TTL
  - Invalidate cache on template update/delete
  - _Requirements: 6.1_

- [ ] R6.2: Add Redis caching for brand configurations
  - Cache brand configs with 15-minute TTL
  - Invalidate cache on brand update
  - _Requirements: 6.1_

- [ ] R6.3: Add Redis caching for analytics data
  - Cache analytics metrics with 5-minute TTL
  - Invalidate cache on new events
  - _Requirements: 6.1_

- [ ] R6.4: Implement lazy loading for heavy Vue components
  - Use defineAsyncComponent for GrapeJS editor
  - Use defineAsyncComponent for chart components
  - Use defineAsyncComponent for map components
  - _Requirements: 6.2_

- [ ] R6.5: Configure Vite code splitting for template system
  - Add manual chunks for TemplateSystem, BrandManager, AnalyticsDashboard
  - Verify bundle size reduction
  - _Requirements: 6.3_

- [ ] R6.6: Optimize image upload pipeline
  - Add image resizing for brand logos
  - Add WebP conversion
  - Add CDN URL support
  - _Requirements: 6.4_

- [ ] R6.7: Run build size analysis
  - Run `pnpm run build:analyze`
  - Verify initial JS bundle under 200KB gzipped
  - _Requirements: 6.5_

### R6-REVIEW: Performance Verification | agent: Reviewer | depends: R6.7

- [ ] R6.8: Verify caching is working
  - Check Redis for cached keys after accessing templates/brand/analytics
  - _Requirements: 6.1_

- [ ] R6.9: Verify bundle size
  - Confirm initial JS bundle under 200KB gzipped
  - _Requirements: 6.5_

---

## Phase 7: Tenant Isolation Verification

### R7: Tenant Isolation | agent: Worker | size: M | depends: R6.7

- [ ] R7.1: Audit all 257 models for tenant scoping
  - Verify each model has TenantContextService global scope
  - Document central vs tenant models
  - _Requirements: 7.4_

- [ ] R7.2: Verify tenant context in all API endpoints
  - Review all route files in routes/api/
  - Confirm tenant middleware is applied
  - _Requirements: 7.5_

- [ ] R7.3: Add tenant isolation tests to CI pipeline
  - Create tests that verify cross-tenant data access is blocked
  - Add to GitHub Actions workflow
  - _Requirements: 7.6_

- [ ] R7.4: Run tenant isolation test suite
  - Run `php artisan test --testsuite=Integration`
  - All tenant isolation tests must pass
  - _Requirements: 7.6_

### R7-REVIEW: Tenant Isolation Verification | agent: Reviewer | depends: R7.4

- [ ] R7.5: Verify all models are properly scoped
  - Confirm zero models without tenant scope (except central models)
  - _Requirements: 7.4_

---

## Phase 8: Docker & Deployment

### R8: Docker & Deployment Readiness | agent: Worker | size: S | depends: R7.4

- [ ] R8.1: Update docker-compose.yml to use Node 22
  - Change Vite service from Node 20 to Node 22
  - _Requirements: 8.2_

- [ ] R8.2: Add health checks to all Docker services
  - Add health check for PostgreSQL
  - Add health check for Redis
  - Add health check for PHP-FPM
  - _Requirements: 8.3_

- [ ] R8.3: Create Docker deployment documentation
  - Write docs/deployment/docker.md with step-by-step instructions
  - Include environment variable configuration
  - Include troubleshooting section
  - _Requirements: 8.4_

- [ ] R8.4: Test Docker Compose locally
  - Run `docker-compose up`
  - Verify all services start
  - Verify application is accessible
  - _Requirements: 8.1_

### R8-REVIEW: Docker Verification | agent: Reviewer | depends: R8.4

- [ ] R8.5: Verify Docker deployment
  - Confirm all services start without errors
  - Confirm health checks pass
  - _Requirements: 8.1, 8.3_

---

## Phase 9: Full System Verification

### R9: Full System Verification | agent: Reviewer | depends: R8.5

- [ ] R9.1: Run full PHP test suite
  - Run `php artisan test --compact`
  - All tests must pass
  - _Requirements: All_

- [ ] R9.2: Run frontend test suite
  - Run `pnpm test:run`
  - All tests must pass
  - _Requirements: All_

- [ ] R9.3: Run Pint on all PHP files
  - Run `vendor/bin/pint`
  - All files must pass
  - _Requirements: 5.6_

- [ ] R9.4: Run ESLint on all JS/TS files
  - Run `pnpm run lint`
  - All files must pass
  - _Requirements: 5.6_

- [ ] R9.5: Run build verification
  - Run `pnpm run build`
  - Build must succeed
  - _Requirements: 4.6, 6.5_

- [ ] R9.6: Verify route list integrity
  - Run `php artisan route:list --json`
  - Compare with pre-refactoring route list
  - All routes must be present
  - _Requirements: 1.4_

- [ ] R9.7: Verify service count
  - Count services in app/Services/
  - Confirm no duplicates remain
  - _Requirements: 2.1_

- [ ] R9.8: Final integration test
  - Test complete user flow: login → browse templates → create landing page → publish → view analytics
  - All steps must work end-to-end
  - _Requirements: All_

---

## Parallel Execution Groups

### Group P1: Route Reorganization (Independent)
- R1.2 through R1.24 can run in parallel (each extracts routes to a different file)
- R1.25 depends on all R1.2-R1.24
- R1.26 and R1.27 depend on R1.25

### Group P2: Service Deduplication (Independent)
- R2.2, R2.3, R2.4 can run in parallel (each merges different services)
- R2.5 depends on R2.2, R2.3, R2.4
- R2.6 depends on R2.5

### Group P3: N+1 Fixes (Independent)
- R3.1 through R3.7 can run in parallel (each fixes a different service)
- R3.8 depends on all R3.1-R3.7

### Group P4: Frontend Wiring (Independent within sub-groups)
- R4.1 through R4.5 can run in parallel (each adds different routes)
- R4.6 through R4.10 can run in parallel (each wires different components)
- R4.11, R4.12, R4.13, R4.14 can run in parallel (each creates different components)
- R4.15 depends on all R4.1-R4.14

### Group P5: Code Quality (Independent)
- R5.1 through R5.5 can run in parallel
- R5.6 and R5.7 depend on R5.1-R5.5

### Group P6: Performance (Independent within sub-groups)
- R6.1, R6.2, R6.3 can run in parallel (caching)
- R6.4, R6.5 can run in parallel (frontend optimization)
- R6.6 is independent
- R6.7 depends on R6.4, R6.5

### Group P7: Tenant Isolation (Sequential)
- R7.1, R7.2 can run in parallel
- R7.3 depends on R7.1, R7.2
- R7.4 depends on R7.3

### Group P8: Docker (Sequential)
- R8.1, R8.2, R8.3 can run in parallel
- R8.4 depends on R8.1, R8.2

---

## Success Criteria

- [ ] All 9 phases completed with all sub-tasks marked [x]
- [ ] Full PHP test suite passes (zero failures)
- [ ] Full frontend test suite passes (zero failures)
- [ ] Zero TypeScript errors (`vue-tsc --noEmit`)
- [ ] Zero PHP lint errors (`vendor/bin/pint`)
- [ ] Zero ESLint errors (`pnpm run lint`)
- [ ] API routes organized into 27+ domain files
- [ ] Zero duplicate services remain
- [ ] All N+1 queries resolved (≤5 queries per endpoint)
- [ ] All frontend components wired to stores and API
- [ ] Docker Compose starts all services successfully
- [ ] Tenant isolation verified for all models and endpoints
