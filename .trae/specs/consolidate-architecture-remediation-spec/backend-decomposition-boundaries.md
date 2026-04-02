# Backend Decomposition Boundaries

## User Domain Boundaries
- Identity/Profile: `User` core attributes and profile read model
- Tenant Membership: tenant affiliation and role mapping
- Security: authentication, verification, and account status controls
- Activity/Telemetry: login and behavior events through activity logging contracts

## Service Split Targets
- `UserMembershipService`: tenant membership assign/remove/status operations
- `UserSecurityService`: 2FA enable/disable and auth security actions
- `UserProfileService`: profile updates and preference operations
- `UserAnalyticsService`: user metrics and usage summaries

## Controller Contracts
- Controllers orchestrate request validation and response resource shaping
- Services execute business logic and return DTO-style arrays
- Models retain relationships and simple scopes only

## Query Optimization Expectations
- Explicit eager-loading for role, tenant, and profile relations
- Disallow implicit heavy appends in collection responses
- Enforce endpoint-level query budget checks for high-traffic routes
