# Multi-Tenant Architecture

<cite>
**Referenced Files in This Document**
- [config/tenancy.php](file://config/tenancy.php)
- [app/Providers/TenancyServiceProvider.php](file://app/Providers/TenancyServiceProvider.php)
- [app/Models/Tenant.php](file://app/Models/Tenant.php)
- [app/Models/Domain.php](file://app/Models/Domain.php)
- [database/migrations/2024_01_01_000001_create_domains_table.php](file://database/migrations/2024_01_01_000001_create_domains_table.php)
- [database/migrations/2025_09_05_080622_create_domains_table.php](file://database/migrations/2025_09_05_080622_create_domains_table.php)
- [routes/web.php](file://routes/web.php)
- [app/Http/Controllers/SuperAdminDashboardController.php](file://app/Http/Controllers/SuperAdminDashboardController.php)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php)
- [technical-specification.md](file://technical-specification.md)
- [deployment-plan.md](file://deployment-plan.md)
- [README.md](file://README.md)
- [task-03-multi-tenant-enhancement-recap.md](file://docs/task-03-multi-tenant-enhancement-recap.md)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document explains the multi-tenant architecture that enables the platform to support unlimited educational institutions with complete data isolation, domain-based tenant resolution, and scalable infrastructure. It documents the Stancl Tenancy implementation, tenant-specific database schemas, isolated file storage, and centralized super admin management. It also covers tenant creation, domain configuration, data segregation mechanisms, performance optimization strategies, practical setup examples, migration processes, administrative workflows, security considerations, monitoring capabilities, and troubleshooting guidance.

## Project Structure
The multi-tenant implementation centers around configuration, models, migrations, routing, and controller layers that integrate with Stancl Tenancy. Key areas include:
- Configuration for tenancy bootstrappers, database managers, cache and filesystem isolation, and job handlers
- Tenant and Domain models implementing tenancy contracts and relationships
- Migrations defining central tables for tenants and domains
- Routing groups isolating central/admin routes from tenant routes
- Controllers enabling super admin and institution admin dashboards and customization

```mermaid
graph TB
Config["config/tenancy.php<br/>Tenancy configuration"] --> Provider["app/Providers/TenancyServiceProvider.php<br/>Boots tenancy"]
Provider --> Models["app/Models/Tenant.php<br/>Tenant model"]
Models --> Domains["app/Models/Domain.php<br/>Domain model"]
Domains --> Migrations["database/migrations/*_create_domains_table.php<br/>Domain schema"]
Routes["routes/web.php<br/>Central vs tenant routes"] --> Controllers["Controllers<br/>Super admin, institution admin"]
Controllers --> Models
```

**Diagram sources**
- [config/tenancy.php:12-82](file://config/tenancy.php#L12-L82)
- [app/Providers/TenancyServiceProvider.php:24-40](file://app/Providers/TenancyServiceProvider.php#L24-L40)
- [app/Models/Tenant.php:11-85](file://app/Models/Tenant.php#L11-L85)
- [app/Models/Domain.php:12-58](file://app/Models/Domain.php#L12-L58)
- [database/migrations/2024_01_01_000001_create_domains_table.php:14-24](file://database/migrations/2024_01_01_000001_create_domains_table.php#L14-L24)
- [database/migrations/2025_09_05_080622_create_domains_table.php:12-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L12-L34)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

**Section sources**
- [config/tenancy.php:12-82](file://config/tenancy.php#L12-L82)
- [app/Providers/TenancyServiceProvider.php:24-40](file://app/Providers/TenancyServiceProvider.php#L24-L40)
- [app/Models/Tenant.php:11-85](file://app/Models/Tenant.php#L11-L85)
- [app/Models/Domain.php:12-58](file://app/Models/Domain.php#L12-L58)
- [database/migrations/2024_01_01_000001_create_domains_table.php:14-24](file://database/migrations/2024_01_01_000001_create_domains_table.php#L14-L24)
- [database/migrations/2025_09_05_080622_create_domains_table.php:12-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L12-L34)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

## Core Components
- Tenancy configuration: Defines tenant and domain models, central domains, bootstrappers for database, cache, filesystem, queue, and Redis, database manager for PostgreSQL schema, filesystem suffix base, Redis prefix base, and job handlers for create/migrate/seed/delete.
- Tenant model: Extends the base tenancy tenant, declares fillable attributes, custom columns, casts, and relationships to users, courses, graduates, employers, and jobs.
- Domain model: Implements the tenancy domain contract, defines central connection and cache invalidation traits, tenant relationship, and domain lifecycle events.
- Routing: Central-only routes for super admin and security dashboards; tenant routes are grouped under the tenant middleware group in the application’s route files.
- Controllers: Super admin dashboard aggregates institution metrics; institution customization controller manages branding, features, workflows, and integrations.

**Section sources**
- [config/tenancy.php:12-82](file://config/tenancy.php#L12-L82)
- [app/Models/Tenant.php:11-85](file://app/Models/Tenant.php#L11-L85)
- [app/Models/Domain.php:12-58](file://app/Models/Domain.php#L12-L58)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)
- [app/Http/Controllers/SuperAdminDashboardController.php:84-105](file://app/Http/Controllers/SuperAdminDashboardController.php#L84-L105)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:13-279](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L13-L279)

## Architecture Overview
The system uses domain-based tenant resolution with automatic tenant identification via custom domains. Each tenant operates with its own database schema and isolated filesystem and cache spaces. Central domains host super admin and monitoring dashboards, while tenant routes are protected and scoped to the current tenant context.

```mermaid
graph TB
LB["Load Balancer"] --> T1["Tenant A<br/>Subdomain a.app.com"]
LB --> T2["Tenant B<br/>Subdomain b.app.com"]
LB --> TN["Tenant N<br/>Subdomain n.app.com"]
subgraph "Tenant A"
TA1["App Server 1"] --> DBA["Database A"]
TA2["App Server 2"] --> DBA
end
subgraph "Tenant B"
TB1["App Server 1"] --> DBB["Database B"]
TB2["App Server 2"] --> DBB
end
subgraph "Tenant N"
TN1["App Server"] --> DBN["Database N"]
end
subgraph "Central"
CENTRAL["Central Domains<br/>localhost, 127.0.0.1, *.app.com"]
SA["Super Admin Dashboard"]
MON["Monitoring Dashboard"]
end
CENTRAL --> SA
CENTRAL --> MON
```

**Diagram sources**
- [technical-specification.md:35-73](file://technical-specification.md#L35-L73)
- [deployment-plan.md:19-39](file://deployment-plan.md#L19-L39)
- [config/tenancy.php:15-20](file://config/tenancy.php#L15-L20)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

**Section sources**
- [technical-specification.md:35-73](file://technical-specification.md#L35-L73)
- [deployment-plan.md:19-39](file://deployment-plan.md#L19-L39)
- [config/tenancy.php:15-20](file://config/tenancy.php#L15-L20)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

## Detailed Component Analysis

### Tenancy Configuration and Bootstrapping
- Central domains define which hostnames resolve to central/admin routes.
- Bootstrappers enable tenant-aware database, cache, filesystem, queue, and Redis contexts.
- Database manager configured for PostgreSQL schema management.
- Filesystem suffix base and Redis prefix base ensure tenant isolation.
- Migration/seeding parameters and job handlers define lifecycle operations.

```mermaid
flowchart TD
Start(["Load config/tenancy.php"]) --> Central["Define central_domains"]
Central --> Bootstrappers["Configure bootstrappers:<br/>Database, Cache, Filesystem, Queue, Redis"]
Bootstrappers --> DBMgr["Set PostgreSQL schema manager"]
DBMgr --> FS["Set filesystem suffix_base and root overrides"]
FS --> RedisCfg["Set redis prefix_base"]
RedisCfg --> Jobs["Bind job handlers:<br/>create, migrate, seed, delete"]
Jobs --> End(["Ready"])
```

**Diagram sources**
- [config/tenancy.php:15-82](file://config/tenancy.php#L15-L82)

**Section sources**
- [config/tenancy.php:15-82](file://config/tenancy.php#L15-L82)

### Tenant Model and Relationships
- Tenant model extends the base tenancy tenant and implements the tenant-with-database contract.
- Declares fillable attributes and custom columns to preserve specific fields outside the JSON data column.
- Casts the data column as an array for flexible tenant metadata.
- Provides relationships to users, courses, graduates, employers, and jobs, leveraging tenant context.

```mermaid
classDiagram
class Tenant {
+string id
+string name
+string address
+string contact_information
+string plan
+array data
+users()
+courses()
+graduates()
+employers()
+jobs()
}
class User {
+int institution_id
}
class Course {
+int institution_id
}
class Graduate
class Employer {
+int tenant_id
}
class Job {
+int tenant_id
}
Tenant --> User : "hasMany"
Tenant --> Course : "hasMany"
Tenant --> Graduate : "hasMany"
Tenant --> Employer : "hasMany"
Tenant --> Job : "hasMany"
```

**Diagram sources**
- [app/Models/Tenant.php:11-85](file://app/Models/Tenant.php#L11-L85)

**Section sources**
- [app/Models/Tenant.php:11-85](file://app/Models/Tenant.php#L11-L85)

### Domain Model and Validation
- Domain model implements the tenancy domain contract and uses central connection and cache invalidation traits.
- Defines tenant relationship and dispatches domain lifecycle events.
- Includes domain validation helpers to prevent duplicate domains and enforce lowercase domains.

```mermaid
flowchart TD
Save["Saving Domain"] --> Lower["Convert domain to lowercase"]
Lower --> Occupancy["Check if domain is occupied"]
Occupancy --> |Occupied by another| Error["Throw domain occupied exception"]
Occupancy --> |Free| Persist["Persist domain record"]
```

**Diagram sources**
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)

**Section sources**
- [app/Models/Domain.php:12-58](file://app/Models/Domain.php#L12-L58)

### Domain Schema Evolution
- Initial migration creates domains table with unique domain and foreign key to tenants.
- Subsequent migration evolves the domains table to include domain_type, status, is_primary, and ssl_enabled fields.

```mermaid
erDiagram
DOMAINS {
int id PK
string domain_name UK
string tenant_id FK
string domain_type
string status
boolean is_primary
boolean ssl_enabled
timestamp created_at
timestamp updated_at
}
TENANTS {
string id PK
string name
string address
string contact_information
string plan
json data
timestamp created_at
timestamp updated_at
}
DOMAINS }o--|| TENANTS : "belongs to"
```

**Diagram sources**
- [database/migrations/2024_01_01_000001_create_domains_table.php:16-23](file://database/migrations/2024_01_01_000001_create_domains_table.php#L16-L23)
- [database/migrations/2025_09_05_080622_create_domains_table.php:14-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L14-L34)

**Section sources**
- [database/migrations/2024_01_01_000001_create_domains_table.php:14-34](file://database/migrations/2024_01_01_000001_create_domains_table.php#L14-L34)
- [database/migrations/2025_09_05_080622_create_domains_table.php:12-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L12-L34)

### Routing and Middleware Strategy
- Central-only routes for super admin and security dashboards are defined on central domains.
- Tenant routes are grouped under the tenant middleware group in the application’s route files, ensuring tenant context is active for tenant-facing endpoints.

```mermaid
sequenceDiagram
participant Client as "Browser"
participant LB as "Load Balancer"
participant App as "Laravel App"
participant Router as "Routes/web.php"
participant Ctrl as "Controller"
Client->>LB : Request to central domain
LB->>App : Forward to central route
App->>Router : Match central route pattern
Router->>Ctrl : Dispatch to SuperAdminDashboardController
Ctrl-->>Client : Render central dashboard
Client->>LB : Request to tenant domain
LB->>App : Forward to tenant route
App->>Router : Match tenant route pattern
Router->>Ctrl : Dispatch to tenant-scoped controller
Ctrl-->>Client : Render tenant dashboard
```

**Diagram sources**
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

**Section sources**
- [routes/web.php:114-133](file://routes/web.php#L114-L133)

### Super Admin Management and Monitoring
- Super admin dashboard aggregates institution metrics including users, courses, and domains.
- Security dashboard provides event monitoring, data access logs, failed logins, sessions, and system health.
- Monitoring dashboard exposes endpoints for uptime, conversion metrics, performance metrics, error logs, and system health.

```mermaid
sequenceDiagram
participant SA as "Super Admin"
participant Router as "routes/web.php"
participant Dash as "SuperAdminDashboardController"
participant Tenant as "Tenant model"
participant Domain as "Domain model"
SA->>Router : GET /super-admin/dashboard
Router->>Dash : institutions()
Dash->>Tenant : with(['domains'])->withCount(['users','courses'])
Tenant-->>Dash : Institutions with counts
Dash->>Domain : Resolve domains per tenant
Domain-->>Dash : Domain names
Dash-->>SA : Institutions list with metrics
```

**Diagram sources**
- [routes/web.php:118-133](file://routes/web.php#L118-L133)
- [app/Http/Controllers/SuperAdminDashboardController.php:84-105](file://app/Http/Controllers/SuperAdminDashboardController.php#L84-L105)

**Section sources**
- [app/Http/Controllers/SuperAdminDashboardController.php:84-105](file://app/Http/Controllers/SuperAdminDashboardController.php#L84-L105)
- [routes/web.php:118-133](file://routes/web.php#L118-L133)

### Institution Customization and Branding
- Institution customization controller manages branding assets (logo, banner), color schemes, custom CSS, font families, theme styles, feature flags, custom fields, workflows, reporting configuration, and integrations.
- Updates are persisted to institution settings and integration settings.

```mermaid
flowchart TD
Start(["POST /admin/institutions/{institution}/customization"]) --> Validate["Validate inputs"]
Validate --> UploadLogo["Upload logo if present"]
Validate --> UploadBanner["Upload banner if present"]
UploadLogo --> UpdateColors["Update primary/secondary colors"]
UploadBanner --> UpdateColors
UpdateColors --> UpdateSettings["Merge branding settings into institution.settings"]
UpdateSettings --> Persist["Persist institution updates"]
Persist --> End(["Success response"])
```

**Diagram sources**
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:28-82](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L28-L82)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:162-178](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L162-L178)

**Section sources**
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:28-82](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L28-L82)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:162-178](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L162-L178)

### Practical Examples

#### Tenant Creation Workflow
- Provision a new tenant record and associate a primary domain.
- Trigger database creation and schema migration using configured job handlers.
- Seed initial tenant data using the configured seeder parameters.
- Verify domain uniqueness and lowercased domain name during persistence.

```mermaid
sequenceDiagram
participant Admin as "Super Admin"
participant Router as "routes/web.php"
participant Ctrl as "TenantController"
participant DB as "Database"
participant Jobs as "Job Handlers"
Admin->>Router : POST /super-admin/institutions
Router->>Ctrl : Create tenant
Ctrl->>Jobs : Dispatch create_database
Jobs->>DB : Create tenant schema
Ctrl->>Jobs : Dispatch migrate_database
Jobs->>DB : Run tenant migrations
Ctrl->>Jobs : Dispatch seed_database
Jobs->>DB : Seed tenant data
Ctrl-->>Admin : Tenant created with domain
```

**Diagram sources**
- [config/tenancy.php:76-81](file://config/tenancy.php#L76-L81)
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)

**Section sources**
- [config/tenancy.php:76-81](file://config/tenancy.php#L76-L81)
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)

#### Domain Configuration and Verification
- Add a domain to a tenant with optional domain_type, status, is_primary, and ssl_enabled flags.
- Ensure domain uniqueness and enforce lowercase domain names.
- Invalidate tenant resolver cache upon domain changes.

```mermaid
flowchart TD
AddDomain["Add Domain to Tenant"] --> SetFlags["Set domain_type/status/is_primary/ssl_enabled"]
SetFlags --> ValidateUnique["Ensure domain not occupied"]
ValidateUnique --> Lowercase["Lowercase domain name"]
Lowercase --> Persist["Persist domain"]
Persist --> Invalidate["Invalidate tenant resolver cache"]
```

**Diagram sources**
- [database/migrations/2025_09_05_080622_create_domains_table.php:22-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L22-L34)
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)

**Section sources**
- [database/migrations/2025_09_05_080622_create_domains_table.php:22-34](file://database/migrations/2025_09_05_080622_create_domains_table.php#L22-L34)
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)

#### Administrative Workflows
- Super admin institution listing with counts of users and courses.
- Security dashboard for event monitoring, data access logs, failed logins, sessions, and system health.
- Monitoring dashboard endpoints for uptime, conversion metrics, performance metrics, error logs, and system health.

```mermaid
sequenceDiagram
participant SA as "Super Admin"
participant Router as "routes/web.php"
participant Dash as "SuperAdminDashboardController"
SA->>Router : GET /security/dashboard
Router->>Dash : security dashboard
Dash-->>SA : Security metrics and logs
SA->>Router : GET /monitoring/dashboard
Router->>Dash : monitoring dashboard
Dash-->>SA : Uptime, performance, error logs
```

**Diagram sources**
- [routes/web.php:27-41](file://routes/web.php#L27-L41)
- [routes/web.php:135-147](file://routes/web.php#L135-L147)

**Section sources**
- [routes/web.php:27-41](file://routes/web.php#L27-L41)
- [routes/web.php:135-147](file://routes/web.php#L135-L147)

## Dependency Analysis
- Configuration depends on the Tenant and Domain models and bootstrappers.
- Tenant model depends on the base tenancy tenant and Eloquent relationships.
- Domain model depends on central connection and cache invalidation traits and domain lifecycle events.
- Routes depend on middleware groups to separate central/admin from tenant contexts.
- Controllers depend on models and policies for authorization and data aggregation.

```mermaid
graph TB
Config["config/tenancy.php"] --> Tenant["app/Models/Tenant.php"]
Config --> Domain["app/Models/Domain.php"]
Tenant --> Routes["routes/web.php"]
Domain --> Routes
Routes --> SuperDash["SuperAdminDashboardController.php"]
Routes --> InstCustom["InstitutionCustomizationController.php"]
```

**Diagram sources**
- [config/tenancy.php:12-14](file://config/tenancy.php#L12-L14)
- [app/Models/Tenant.php:11-13](file://app/Models/Tenant.php#L11-L13)
- [app/Models/Domain.php:12-22](file://app/Models/Domain.php#L12-L22)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)
- [app/Http/Controllers/SuperAdminDashboardController.php:84-105](file://app/Http/Controllers/SuperAdminDashboardController.php#L84-L105)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:13-279](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L13-L279)

**Section sources**
- [config/tenancy.php:12-14](file://config/tenancy.php#L12-L14)
- [app/Models/Tenant.php:11-13](file://app/Models/Tenant.php#L11-L13)
- [app/Models/Domain.php:12-22](file://app/Models/Domain.php#L12-L22)
- [routes/web.php:114-133](file://routes/web.php#L114-L133)
- [app/Http/Controllers/SuperAdminDashboardController.php:84-105](file://app/Http/Controllers/SuperAdminDashboardController.php#L84-L105)
- [app/Http/Controllers/Admin/InstitutionCustomizationController.php:13-279](file://app/Http/Controllers/Admin/InstitutionCustomizationController.php#L13-L279)

## Performance Considerations
- Database performance: Proper indexing, connection pooling, and tenant-aware query scoping.
- Application performance: Tenant context caching, optimized middleware stack, resource usage monitoring, and performance benchmarking.
- Scalability: Horizontal scaling per tenant with shared database isolation and load balancer distribution.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
- Cross-tenant access prevention: Ensure middleware-level access control and database-level query scoping are enforced.
- Domain conflicts: Validate domain uniqueness and handle domain occupied exceptions during saving.
- Tenant resolver cache: Invalidate tenant resolver cache after domain changes.
- Security tests: Verify cross-tenant access attempts, data leakage prevention, authentication isolation, and authorization boundaries.

**Section sources**
- [app/Models/Domain.php:38-57](file://app/Models/Domain.php#L38-L57)
- [task-03-multi-tenant-enhancement-recap.md:170-218](file://docs/task-03-multi-tenant-enhancement-recap.md#L170-L218)

## Conclusion
The multi-tenant architecture leverages Stancl Tenancy to deliver complete data isolation, domain-based tenant resolution, and scalable infrastructure. Configuration, models, migrations, routing, and controllers work together to support unlimited institutions with centralized super admin management, tenant-specific databases, isolated filesystems, and robust security and monitoring capabilities.

[No sources needed since this section summarizes without analyzing specific files]

## Appendices

### Security and Compliance Highlights
- Cross-tenant access prevention via middleware and database scoping
- Audit and compliance with tenant-specific logs and tracking
- Security event monitoring and session management

**Section sources**
- [task-03-multi-tenant-enhancement-recap.md:170-183](file://docs/task-03-multi-tenant-enhancement-recap.md#L170-L183)

### Monitoring and Maintenance
- Health monitoring, automated health checks, and resource usage alerts
- Maintenance tools for tenant backup, restore, and bulk operations

**Section sources**
- [task-03-multi-tenant-enhancement-recap.md:218-231](file://docs/task-03-multi-tenant-enhancement-recap.md#L218-L231)

### Platform Purpose and Capabilities
- Multi-tenant architecture supporting unlimited institutions with complete data isolation, domain-based resolution, scalable infrastructure, and centralized super admin management

**Section sources**
- [README.md:54-58](file://README.md#L54-L58)