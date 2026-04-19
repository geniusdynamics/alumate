# Route Module Boundaries

## Route Group Inventory
- Public and legal routes
- Onboarding and subscription routes
- Monitoring routes
- Role-segmented dashboard routes
- Feature routes: jobs, career, social, alumni, events

## Target Module Files
- `routes/modules/public.php`
- `routes/modules/monitoring.php`
- `routes/modules/dashboards.php`
- `routes/modules/jobs.php`
- `routes/modules/social.php`
- `routes/modules/alumni.php`
- `routes/modules/events.php`

## Access Policy Contracts
- Super-admin routes remain behind `role:super-admin`
- Institution routes remain behind `role:institution-admin`
- Graduate routes remain behind `role:graduate,alumni`
- Employer routes remain behind `role:employer`

## Validation Scope
- Guest access rejects protected routes
- Wrong-role access returns forbidden status
- Role-allowed access returns success for primary dashboards
