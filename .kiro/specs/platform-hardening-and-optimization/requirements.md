# Requirements Document: Alumate Platform Hardening & Optimization

## Introduction

Alumate is a comprehensive multi-tenant alumni engagement platform built on Laravel 12, Vue 3, and PostgreSQL with schema-based tenancy. The platform currently implements 52 feature areas across 1,018 routes, 257 models, 182 services, and 225 Inertia pages.

This specification addresses **platform hardening and optimization** — resolving structural issues, code quality problems, and organizational deficiencies identified during the comprehensive viability assessment. The goal is not to add new features, but to ensure all 52 existing features work correctly, perform well, and are maintainable for the long term.

Key problems addressed:
1. **API route bloat** — 884 routes in a single `api.php` file
2. **Service duplication** — Overlapping services (ABTestingService/AbTestService, etc.)
3. **N+1 query issues** — Missing eager loading in critical services
4. **Frontend gaps** — Missing Vue components for Template/Brand/LandingPage systems
5. **Code organization** — Inconsistent patterns, inline closures, missing abstractions
6. **Performance foundation** — Caching, query optimization, bundle size
7. **Security hardening** — alert() replacement, null safety, tenant isolation verification

## Glossary

- **Alumate**: The complete alumni engagement platform
- **Route Reorganization**: Splitting api.php into domain-specific route files
- **Service Deduplication**: Merging overlapping service classes
- **N+1 Resolution**: Adding eager loading to prevent query explosion
- **Template System**: Template creation, editing, preview, and management
- **Brand Management**: Logo, color, font, and guideline management
- **Landing Page System**: Page creation, publishing, and analytics
- **Tenant Isolation**: Ensuring data is properly scoped to tenant context
- **Frontend Foundation**: TypeScript types, Pinia stores, API services, Vue components

## Requirements

### Requirement 1: API Route Reorganization

**User Story:** As a developer working on the Alumate platform, I want API routes organized by domain in separate files, so that I can find, understand, and modify routes efficiently without navigating a 2,162-line file.

#### Acceptance Criteria

1. WHEN a developer opens the routes directory, THE Alumate_System SHALL have domain-specific route files (api/jobs.php, api/events.php, api/posts.php, etc.)
2. WHEN a route is accessed, THE Alumate_System SHALL respond identically to before reorganization (zero breaking changes)
3. WHEN a new feature is added, THE Alumate_System SHALL allow adding routes to the appropriate domain file without modifying other files
4. ALL existing route names SHALL be preserved (backward compatibility for named routes)
5. ALL middleware configurations SHALL be preserved (auth, rate limiting, roles)
6. THE main api.php file SHALL be reduced to under 100 lines (imports + route file includes only)
7. ALL inline route closures SHALL be extracted to dedicated controller methods

### Requirement 2: Service Layer Deduplication

**User Story:** As a developer maintaining the Alumate platform, I want each business concern handled by exactly one service class, so that I don't have to guess which service to modify when fixing a bug or adding a feature.

#### Acceptance Criteria

1. WHEN analyzing the service layer, THE Alumate_System SHALL have zero duplicate services for the same concern
2. WHEN ABTestingService and AbTestService exist, THEY SHALL be merged into a single service
3. WHEN AnalyticsService and Analytics/ directory services overlap, THEY SHALL be consolidated
4. WHEN a service is removed, ALL references to it SHALL be updated to use the replacement
5. WHEN a service is merged, ALL tests SHALL continue to pass
6. THE service directory SHALL have clear domain subdirectories (Analytics/, CRM/, Integrations/, etc.)

### Requirement 3: N+1 Query Resolution

**User Story:** As a user of the Alumate platform, I want pages to load quickly without excessive database queries, so that my experience is smooth and responsive.

#### Acceptance Criteria

1. WHEN the JobController analytics endpoint is called, THE Alumate_System SHALL execute no more than 5 queries (consolidated from current N+1 pattern)
2. WHEN AlumniRecommendationService generates recommendations, THE Alumate_System SHALL eager load circles, groups, and location relationships
3. WHEN AlumniMapService retrieves map data, THE Alumate_System SHALL eager load location relationships
4. WHEN CareerTimelineService loads career data, THE Alumate_System SHALL eager load milestone relationships
5. WHEN any list endpoint returns paginated results, THE Alumate_System SHALL use eager loading for all displayed relationships
6. THE CI pipeline SHALL include N+1 query detection (Laravel Debugbar or similar tool)

### Requirement 4: Frontend Foundation Completion

**User Story:** As an institution administrator, I want to manage templates, brand assets, and landing pages through a polished UI, so that I can create marketing materials without developer assistance.

#### Acceptance Criteria

1. WHEN a user navigates to the Template Library, THE Alumate_System SHALL display templates in a filterable, searchable grid
2. WHEN a user creates a new template, THE Alumate_System SHALL connect to the backend API and persist the template
3. WHEN a user manages brand assets, THE Alumate_System SHALL allow uploading logos, defining colors, and selecting fonts
4. WHEN a user views landing pages, THE Alumate_System SHALL show a list with publish/unpublish actions
5. WHEN a user views analytics, THE Alumate_System SHALL display template, landing page, and brand metrics
6. ALL Vue components SHALL use the Pinia stores for state management
7. ALL API calls SHALL use the typed API service layer
8. ALL components SHALL have proper loading states, error states, and empty states

### Requirement 5: Code Quality & Organization

**User Story:** As a developer onboarding to the Alumate project, I want consistent code patterns and clear organization, so that I can be productive within my first week.

#### Acceptance Criteria

1. WHEN reviewing the codebase, THE Alumate_System SHALL have zero `alert()` calls (replaced with toast notifications)
2. WHEN reviewing the codebase, THE Alumate_System SHALL have zero `console.log` in production code
3. WHEN reviewing the codebase, THE Alumate_System SHALL have a single AdminLayout component
4. WHEN TypeScript types are used, THE Alumate_System SHALL have null-safe type definitions
5. WHEN Super Admin stats are displayed, THE Alumate_System SHALL have null guards for all optional values
6. ALL PHP files SHALL pass `vendor/bin/pint` formatting checks
7. ALL TypeScript files SHALL pass `vue-tsc --noEmit` type checking

### Requirement 6: Performance Optimization

**User Story:** As a user of the Alumate platform, I want pages to load in under 2 seconds, so that I can work efficiently without waiting.

#### Acceptance Criteria

1. WHEN template structures are accessed, THE Alumate_System SHALL cache them using Redis (5-minute TTL)
2. WHEN heavy Vue components are loaded, THE Alumate_System SHALL use lazy loading (defineAsyncComponent)
3. WHEN the application builds, THE Alumate_System SHALL split the bundle by domain (TemplateSystem, BrandManager, etc.)
4. WHEN brand logos are uploaded, THE Alumate_System SHALL optimize them (resize, compress, WebP conversion)
5. WHEN the initial page loads, THE Alumate_System SHALL deliver under 200KB of JavaScript (gzipped)

### Requirement 7: Tenant Isolation Verification

**User Story:** As an institution administrator, I want my data to be completely isolated from other institutions, so that there is zero risk of data leakage.

#### Acceptance Criteria

1. WHEN any model query is executed, THE Alumate_System SHALL apply the TenantContextService global scope
2. WHEN a tenant context is switched, THE Alumate_System SHALL verify the user has access to the target tenant
3. WHEN cross-tenant data access is attempted, THE Alumate_System SHALL throw an authorization exception
4. ALL 257 models SHALL be verified as either central or tenant-scoped
5. ALL API endpoints SHALL be verified to respect tenant boundaries
6. THE CI pipeline SHALL include tenant isolation tests

### Requirement 8: Docker & Deployment Readiness

**User Story:** As a DevOps engineer deploying Alumate, I want reliable Docker configuration, so that I can deploy consistently across environments.

#### Acceptance Criteria

1. WHEN running `docker-compose up`, THE Alumate_System SHALL start all services (PHP, PostgreSQL, Vite, Redis)
2. WHEN the production Dockerfile is built, THE Alumate_System SHALL use Node 22 (not Node 20)
3. WHEN health checks are configured, THE Alumate_System SHALL verify database, cache, and storage connectivity
4. WHEN the deployment documentation is read, IT SHALL provide step-by-step instructions for Docker deployment
