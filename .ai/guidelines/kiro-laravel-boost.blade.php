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