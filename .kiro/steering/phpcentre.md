---
inclusion: always
---

# Alumni Tracking System Development Guidelines

## Architecture & Framework

- Laravel 11 application with multi-tenant architecture using Spatie Laravel Tenancy
- Vue 3 with TypeScript for frontend components
- Inertia.js for seamless SPA experience
- Pest PHP for testing framework
- Elasticsearch for advanced search capabilities

## Development Environment

- Use `start-dev.ps1`, `start-dev.bat`, or `start-dev-final.ps1` to start development server
- Use `artisan.ps1` to run PHP Artisan commands
- Docker Compose configuration available for containerized development
- SQLite database for local development

## Code Style & Standards

- Follow PSR-12 coding standards for PHP
- Use PHP CS Fixer configuration (`.php-cs-fixer.php`)
- TypeScript strict mode enabled for frontend
- Prettier for JavaScript/Vue formatting
- ESLint with auto-import configuration

## Database Conventions

- Use descriptive migration names with timestamps
- Implement soft deletes where appropriate (`deleted_at` column)
- Factory classes for all models to support testing
- Seeders for sample data and test users
- Foreign key constraints with proper cascading

## Model Patterns

- Eloquent models with proper relationships
- Use traits for shared functionality (see `app/Traits`)
- Implement proper scopes for tenant isolation
- Model factories for consistent test data generation

## Service Layer Architecture

- Business logic in dedicated service classes (`app/Services`)
- Controllers should be thin, delegating to services
- Use dependency injection for service dependencies
- Return consistent response formats from API controllers

## API Design

- RESTful API endpoints under `/api` prefix
- Consistent JSON response structure
- Proper HTTP status codes
- API resource classes for data transformation

## Frontend Component Structure

- Vue 3 Composition API preferred
- TypeScript interfaces for props and data
- Reusable components in `resources/js/Components`
- Page components in `resources/js/Pages`
- Consistent naming: PascalCase for components

## Testing Strategy

- Feature tests for API endpoints
- Unit tests for services and models
- Integration tests for complex workflows
- Use factories for test data generation
- Pest PHP syntax for readable tests

## Job Queue & Background Processing

- Use Laravel jobs for heavy operations
- Queue jobs for email notifications
- Implement proper job failure handling
- Use database queue driver for development

## Notification System

- Laravel notification classes for all alerts
- Support multiple channels (mail, database, broadcast)
- Notification preferences per user
- Digest notifications for batched updates

## Security Practices

- Role-based access control with Spatie Permission
- Tenant isolation at database level
- Input validation using Form Requests
- CSRF protection enabled
- Secure file upload handling

## Performance Considerations

- Eager loading for N+1 query prevention
- Database indexing for search fields
- Caching for frequently accessed data
- Elasticsearch for complex search queries
- Queue heavy operations
