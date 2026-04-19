# AGENTS.md

This file provides guidance for AI coding agents working in this repository.

## Environment Setup

### Required Software

- PHP 8.3+ (Path: `D:\DevCenter\xampp\php-8.3.23\php.exe`)
- Node.js 22+ (LTS)
- Composer 2.x
- PostgreSQL 17+
- Redis (optional)
- pnpm 10.x (Package Manager)

### Initial Setup Commands

```bash
# Install dependencies
composer install
pnpm install

# Database setup
.\artisan migrate
.\artisan tenants:migrate
.\artisan db:seed

# Create sample data
.\artisan db:seed --class=DemoDataSeeder
php scripts/data/create_sample_data.php
php scripts/data/create_tenant_sample_data.php
```

### Development Server Commands

```bash
# Start Vite development server
pnpm run dev

# Start Laravel server
.\artisan serve --host=127.0.0.1 --port=8080

# Start all services (concurrent)
composer run dev
```

## Workflow Guidelines

### Before Making Changes

1. Read relevant files completely for context
2. Consider tenant isolation implications
3. Review existing component patterns
4. Check for similar implementations
5. Consider performance implications
6. **Run type checking**: `pnpm run typecheck` (if available) or `vue-tsc --noEmit`

### Making Changes

1. Make incremental, testable changes
2. Follow existing code patterns
3. Update dependent code
4. Add/update tests
5. Verify changes in all contexts (tenant/non-tenant)
6. **Run linting**: `vendor/bin/pint` and `pnpm run lint`

### After Changes

1. Run full test suite: `scripts\testing\run-tests.bat`
2. Verify tenant isolation
3. Check performance impact
4. Update documentation if needed
5. Test in all supported browsers
6. **Verify CI/CD compatibility** (see CI/CD Guidelines below)

## Code Standards

### Backend (PHP)

- Follow PSR-12
- Use strict types: `declare(strict_types=1);`
- Document public APIs with PHPDoc
- Write comprehensive tests (target: 80%+ coverage)
- Maintain tenant isolation
- Use type hints for all parameters and returns

### Frontend (Vue/TypeScript)

- Use Vue 3 Composition API with `<script setup>`
- Strict TypeScript mode - no `any` types
- Follow component hierarchy and naming conventions
- Match existing patterns in `resources/js/`
- Document complex logic
- Use strict type checking: `vue-tsc --noEmit`

### Database

- Use migrations for schema changes
- Consider tenant implications (central vs tenant tables)
- Follow naming conventions
- Add appropriate indexes
- Document complex queries
- Test migrations on fresh and existing databases

## Testing Requirements

### Test Suite Organization

```
tests/
├── Unit/           # Fast, isolated tests (SQLite)
├── Feature/        # HTTP endpoint tests (PostgreSQL)
├── Integration/    # Component integration tests (PostgreSQL)
├── Performance/    # Load and stress tests
├── Security/       # Security vulnerability tests
├── EndToEnd/       # Full user flow tests
├── Browser/        # Dusk browser tests
└── Js/            # Vitest frontend tests
```

### Running Tests

```bash
# Full test suite
scripts/testing/run-tests.bat

# Backend tests by suite
.\artisan test --testsuite=Unit
.\artisan test --testsuite=Feature
.\artisan test --testsuite=Integration

# With coverage
./vendor/bin/pest --coverage --min=80

# Frontend tests
pnpm test
pnpm test:run
pnpm test:ui

# Specific test suites
pnpm test:homepage
pnpm test:smoke
```

### Test Coverage Expectations

- New features require tests
- Target: 80%+ coverage (enforced in CI)
- Cover edge cases and error conditions
- Test tenant isolation scenarios
- Test performance impact for heavy operations
- Include integration tests for critical paths

## CI/CD Guidelines

### Pipeline Overview

1. **ci.yml** - Main CI pipeline (runs on all PRs/pushes)
2. **ci-enhanced.yml** - Extended checks with security scans
3. **staging-deployment.yml** - Deploy to staging environment
4. **production-deployment.yml** - Deploy to production (manual approval)
5. **rollback.yml** - Emergency rollback procedures
6. **homepage-deployment.yml** - Homepage-specific deployments

### Pre-Commit Checklist

Before pushing code, ensure:

```bash
# 1. Code style compliance
vendor/bin/pint
pnpm run lint

# 2. Type checking (CRITICAL)
vue-tsc --noEmit

# 3. Run relevant tests
.\artisan test --testsuite=Unit --filter=YourFeature
pnpm test:run --filter=YourComponent

# 4. Build verification
pnpm run build
```

### CI/CD Failure Common Causes

1. **TypeScript errors** - Run `vue-tsc --noEmit` locally
2. **Linting failures** - Run `vendor/bin/pint` and `pnpm run lint --fix`
3. **Test coverage below 80%** - Add more tests
4. **Tenant isolation issues** - Ensure proper tenant context in tests
5. **Database migration conflicts** - Test migrations on fresh DB
6. **Dependency vulnerabilities** - Run `composer audit` and `pnpm audit`

### Environment Variables Required

Ensure these secrets are configured in GitHub:

- `STAGING_HOST`, `STAGING_USER`, `STAGING_SSH_KEY`, `STAGING_PATH`
- `PROD_HOST`, `PROD_USER`, `PROD_SSH_KEY`, `PROD_PATH`
- `SLACK_WEBHOOK_URL` (for notifications)
- Database credentials for each environment

### Deployment Process

1. **Staging**: Auto-deploy on `develop` branch push
2. **Production**: Manual trigger with approval
3. **Rollback**: Use `rollback.yml` workflow with reason
4. **Verification**: All deployments include health checks

## Development Rules

### Critical Requirements

1. Never stage/commit files automatically
2. Always verify file creation in Windows
3. Maintain tenant data isolation
4. Follow existing patterns
5. Test thoroughly before completion
6. **Never commit secrets or API keys**
7. **Always run type checking before committing**

### File Operations

1. Use `.\artisan` for Laravel commands (Windows)
2. Use `pnpm` instead of `npm`
3. Verify file paths work in Windows
4. Check file permissions
5. Validate file existence
6. Handle paths consistently

### Security Practices

1. Never expose sensitive data in logs or errors
2. Use environment variables for all secrets
3. Validate user input at all entry points
4. Maintain tenant boundaries strictly
5. Follow security protocols (CSRF, XSS, SQL injection prevention)
6. Run security audits: `composer audit`, `pnpm audit`

## Troubleshooting Guide

### Common Issues

#### 1. **Tenant Identification**

- Check domain access configuration
- Verify tenant context in tests
- Use correct URLs with tenant domains
- Check `tenants:migrate` has been run

#### 2. **PHP Command Issues**

- Use `.\artisan` on Windows
- Verify PHP 8.3+ in PATH
- Use full PHP path if needed: `D:\DevCenter\xampp\php-8.3.23\php.exe`

#### 3. **Development Server**

- Use correct ports (8080 for Laravel, 5173 for Vite)
- Check file permissions on `storage/` and `bootstrap/cache/`
- Verify environment variables in `.env`
- Clear caches: `.\artisan config:clear`, `.\artisan cache:clear`

#### 4. **CI/CD Failures**

- Check PHP/Node version compatibility
- Verify all tests pass locally first
- Check for uncommitted changes affecting tests
- Review artifact retention limits
- Check secret availability

#### 5. **TypeScript Errors**

- Run `vue-tsc --noEmit` to see all errors
- Check for missing imports
- Verify component prop types
- Check strict null safety settings

#### 6. **Test Failures**

- Check database state: `.\artisan migrate:fresh --seed`
- Clear test caches: `.\artisan config:clear`
- Verify tenant context in multi-tenant tests
- Check for race conditions in parallel tests

## Project Structure

### Key Directories

```
resources/js/
├── components/    # Reusable Vue components
├── composables/   # Vue composables (useXxx)
├── layouts/       # Page layouts
├── Pages/         # Inertia.js pages (route-based)
├── services/      # Business logic services
├── stores/        # Pinia stores
├── types/         # TypeScript type definitions
└── utils/         # Utility functions

app/
├── Console/       # Artisan commands
├── Http/          # Controllers, Middleware, Requests
├── Models/        # Eloquent models
├── Services/      # Business logic
├── Providers/     # Service providers
└── Tenancy/       # Multi-tenant logic

database/
├── migrations/    # Schema migrations
├── seeders/       # Data seeders
├── factories/     # Model factories
└── schema/        # Schema dumps
```

### Important Files

- `artisan`: Laravel CLI tool
- `package.json`: Node dependencies (use pnpm)
- `composer.json`: PHP dependencies
- `tsconfig.json`: TypeScript configuration
- `vite.config.ts`: Build configuration
- `phpunit.xml`: Test configuration
- `pint.json`: PHP code style rules
- `eslint.config.js`: ESLint configuration

## Continuous Integration

### Quality Gates (All Must Pass)

1. ✅ PHP Code Style (Pint)
2. ✅ JavaScript/TypeScript Linting (ESLint)
3. ✅ TypeScript Type Checking (vue-tsc)
4. ✅ Unit Tests (SQLite, 80%+ coverage)
5. ✅ Feature Tests (PostgreSQL)
6. ✅ Integration Tests (PostgreSQL)
7. ✅ Frontend Tests (Vitest)
8. ✅ Security Audit (Composer + npm)
9. ✅ Build Verification

### Before Submitting Changes

1. Run all tests locally
2. Check code style compliance
3. Verify type checking passes
4. Test tenant isolation scenarios
5. Verify security audit passes
6. Update documentation if needed
7. Test in all supported browsers

### Quality Checks

```bash
# PHP checks
vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=5
composer audit

# JavaScript/TypeScript
pnpm run lint
pnpm run format:check
vue-tsc --noEmit
pnpm audit

# Full test suite
scripts/testing/run-tests.bat

# Build verification
pnpm run build
```

## Accessibility Requirements

- Follow WCAG 2.1 AA guidelines
- Test with screen readers
- Support keyboard navigation
- Maintain color contrast ratios (4.5:1 minimum)
- Provide text alternatives for images
- Use semantic HTML elements
- Test with accessibility tools (axe, Lighthouse)

## Performance Guidelines

- Lazy load routes and components
- Optimize images and assets
- Use caching strategies
- Monitor bundle size
- Profile database queries
- Use eager loading to prevent N+1
- Implement pagination for large datasets

## Documentation Standards

- Document all public APIs
- Include JSDoc/PHPDoc comments
- Update README for major changes
- Document breaking changes in migration guides
- Keep AGENTS.md updated with new patterns

===

<laravel-boost-guidelines>
=== .ai/kiro-laravel-boost rules ===

# Laravel Boost Guidelines for Kiro IDE

## Project Context
This is a comprehensive multi-tenant Laravel 12 alumni platform with the following key technologies:

### **Core Stack**
- **Framework**: Laravel 12.20.0 with Inertia.js v2 and Vue 3 + TypeScript
- **Database**: PostgreSQL with multi-tenant architecture (Spatie Laravel Tenancy v3.7)
- **Testing**: Pest PHP v3.8 with Laravel plugin
- **Frontend**: Vue 3 Composition API, TypeScript, Tailwind CSS v3.x
- **Search**: Elasticsearch integration for advanced search
- **Authentication**: Laravel Socialite v5.23, Spatie Laravel Permission v6.19

### **Development Environment**
- **PHP**: 8.3.23 (XAMPP installation at `D:\DevCenter\xampp\php-8.3.23\`)
- **Project Path**: `D:\DevCenter\abuilds\alumate`
- **Custom Scripts**: Uses `artisan.ps1` for PowerShell compatibility
- **Development Server**: Port 8080 via `start-dev-final.ps1`

### **Recent Implementations**
- **Analytics Dashboard**: Comprehensive analytics with Vue 3 components, Canvas charts, export functionality
- **MCP Integration**: Laravel Boost MCP server configured for this environment
- **Multi-Tenant**: Complete tenant isolation with domain-based resolution

## Laravel Boost MCP Tools Available
You have access to Laravel Boost's MCP tools through Kiro. Use these tools to:

### Database Operations
- `database_schema` - Inspect database structure and relationships
- `database_query` - Execute queries for data analysis
- `database_connections` - Check connection configurations

### Application Inspection  
- `application_info` - Get PHP/Laravel versions, packages, and models
- `list_routes` - Inspect all application routes
- `get_config` - Read configuration values
- `list_artisan_commands` - See available Artisan commands

### Development Tools
- `tinker` - Execute code in Laravel's context
- `search_docs` - Query Laravel documentation
- `read_log_entries` - Check application logs
- `last_error` - Get recent error information

## Code Generation Guidelines

### Analytics Dashboard Context
The analytics dashboard you just implemented includes:
- Comprehensive analytics service with engagement metrics
- Vue 3 components with TypeScript
- Chart visualizations using Canvas API
- Role-based access control (admin/super_admin only)
- Export functionality (CSV, JSON, Excel)
- Real-time alerts and trend analysis

### Laravel Best Practices
- Use service classes for business logic
- Implement proper validation with Form Requests
- Follow PSR-12 coding standards
- Use Eloquent relationships and scopes
- Implement caching for performance
- Use queued jobs for heavy operations

### Vue 3 + TypeScript Patterns
- Use Composition API with `<script setup>`
- Define proper TypeScript interfaces
- Implement reactive data with `ref()` and `reactive()`
- Use computed properties for derived state
- Handle async operations with proper error handling

### Testing Approach
- Write feature tests for API endpoints
- Use Pest PHP syntax for readable tests
- Create factories for consistent test data
- Test both happy path and edge cases

## Multi-Tenant Considerations
- All database queries are automatically scoped to current tenant
- Use tenant-aware models and relationships
- Consider tenant isolation in caching strategies
- Test multi-tenant scenarios

## Performance Optimization
- Use eager loading to prevent N+1 queries
- Implement database indexing for search fields
- Cache frequently accessed data
- Use Elasticsearch for complex searches
- Queue heavy operations

## Security Guidelines
- Validate all user inputs
- Use role-based access control
- Implement CSRF protection
- Sanitize data for XSS prevention
- Use secure file upload handling

### Environment-Specific Commands
- **Artisan**: Use `.\artisan.ps1` or `D:\DevCenter\xampp\php-8.3.23\php.exe artisan`
- **Development**: Use `.\start-dev-final.ps1` to start all services
- **Testing**: Use `.\artisan.ps1 test` for Pest PHP tests
- **Migration**: Use `.\artisan.ps1 migrate` for database changes

### MCP Tools Integration
Always leverage Laravel Boost MCP tools before code generation:

1. **Start with Context**: Use `application_info` to understand current setup
2. **Check Database**: Use `database_schema` to inspect table structure  
3. **Validate Routes**: Use `list_routes` before adding new endpoints
4. **Test Code**: Use `tinker` to validate code snippets
5. **Check Config**: Use `get_config` for environment-specific settings
6. **Monitor Issues**: Use `last_error` and `read_log_entries` for debugging

### Code Generation Priorities
1. **Laravel 12 Compatibility**: Ensure all code uses Laravel 12 features
2. **Multi-Tenant Awareness**: All queries must be tenant-scoped
3. **TypeScript Strict**: Frontend code must have proper type definitions
4. **Performance First**: Consider caching, eager loading, and queuing
5. **Test Coverage**: Include Pest PHP tests for new functionality

When generating code, always consider these patterns and use Laravel Boost's MCP tools to inspect the current application state before making recommendations.

### Recent Analytics Implementation
The analytics dashboard includes:
- `AnalyticsService` with comprehensive metrics calculation
- Vue 3 components with Canvas-based charts
- Role-based access control (admin/super_admin only)
- Export functionality (CSV, JSON, Excel)
- Real-time alerts and trend analysis
- Database tables: `analytics_events`, `user_activity_sessions`, `feature_usage_tracking`, `user_engagement_metrics`

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.25
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/cashier (CASHIER) - v15
- laravel/framework (LARAVEL) - v12
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- tightenco/ziggy (ZIGGY) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA) - v2
- laravel-echo (ECHO) - v2
- vue (VUE) - v3
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

## Inertia

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use the `search-docs` tool for accurate guidance on all things Inertia.

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use the `search-docs` tool with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use the `search-docs` tool with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use the `search-docs` tool with a query of `form component resetting` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>

=== inertia-vue/v2/forms rules ===

## Inertia v2 + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v3 rules ===

## Tailwind CSS 3

- Always use Tailwind CSS v3; verify you're using only classes supported by this version.
</laravel-boost-guidelines>
