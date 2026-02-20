<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| This file is intentionally left as a placeholder because the application
| uses a custom tenant middleware (TenantContextService) instead of 
| Stancl's domain-based tenancy.
|
| The custom tenancy system handles tenant identification via:
| - TenantMiddleware (app/Http/Middleware/TenantMiddleware.php)
| - TenantContextService (app/Services/TenantContextService.php)
|
| All routes are defined in routes/web.php with role-based middleware.
|
*/

// This file is not currently loaded by the application.
// Routes for tenant-scoped features are defined in web.php.
