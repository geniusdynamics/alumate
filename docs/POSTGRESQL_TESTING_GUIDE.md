# PostgreSQL Testing Guide

## Overview

This guide explains how PostgreSQL testing is configured in the CI pipeline and how to run tests locally with PostgreSQL.

## Why PostgreSQL Testing?

The production environment uses PostgreSQL 17, but the CI pipeline was previously running tests only on SQLite. This created a mismatch where PostgreSQL-specific bugs could go undetected during testing. PostgreSQL testing ensures:

- **JSONB column operations** work correctly
- **Schema operations** (tenant creation/deletion) function properly
- **PostgreSQL-specific constraints** are enforced
- **Advanced PostgreSQL features** (triggers, views, full-text search) are tested

## CI Pipeline Configuration

### Test Jobs

The CI pipeline now includes the following test jobs:

1. **Unit Tests (SQLite)** - Fast unit tests using SQLite in-memory database
2. **Unit Tests (PostgreSQL)** - Unit tests using PostgreSQL 17
3. **Integration Tests (PostgreSQL)** - Integration tests using PostgreSQL 17
4. **Feature Tests (PostgreSQL)** - Feature tests using PostgreSQL 17
5. **Code Quality** - Linting and formatting checks
6. **Frontend Tests & Build** - Frontend tests and asset compilation

### PostgreSQL Service

All PostgreSQL test jobs use a PostgreSQL 17 service with the following configuration:

```yaml
postgres:
  image: postgres:17
  env:
    POSTGRES_USER: postgres
    POSTGRES_PASSWORD: password
    POSTGRES_DB: test_db
  ports:
    - 5432:5432
  options: --health-cmd="pg_isready -U postgres" --health-interval=10s --health-timeout=5s --health-retries=3
```

## Test Configurations

### phpunit.xml (SQLite)

Default configuration for fast local testing:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

### phpunit.pgsql.xml (PostgreSQL)

Configuration for PostgreSQL testing:

```xml
<php>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_DATABASE" value="test_db"/>
    <env name="DB_HOST" value="localhost"/>
    <env name="DB_PORT" value="5432"/>
    <env name="DB_USERNAME" value="postgres"/>
    <env name="DB_PASSWORD" value="password"/>
</php>
```

## Running Tests Locally

### Running SQLite Tests (Default)

```bash
# Run all tests with SQLite
.\artisan test

# Run specific test suite
.\artisan test --testsuite=Unit

# Run specific test
.\artisan test --filter=JsonbOperationsTest
```

### Running PostgreSQL Tests

#### Option 1: Using Docker (Recommended)

1. Start PostgreSQL container:

```bash
docker run -d \
  --name alumate-postgres-test \
  -e POSTGRES_USER=postgres \
  -e POSTGRES_PASSWORD=password \
  -e POSTGRES_DB=test_db \
  -p 5432:5432 \
  postgres:17
```

2. Run tests with PostgreSQL configuration:

```bash
# Run all tests with PostgreSQL
.\artisan test --configuration=phpunit.pgsql.xml

# Run specific test suite
.\artisan test --configuration=phpunit.pgsql.xml --testsuite=PostgreSQL

# Run specific test
.\artisan test --configuration=phpunit.pgsql.xml --filter=JsonbOperationsTest
```

3. Stop PostgreSQL container when done:

```bash
docker stop alumate-postgres-test
docker rm alumate-postgres-test
```

#### Option 2: Using Local PostgreSQL

If you have PostgreSQL installed locally:

1. Create test database:

```sql
CREATE DATABASE test_db;
```

2. Configure environment variables:

```bash
# Set environment variables
set DB_CONNECTION=pgsql
set DB_DATABASE=test_db
set DB_HOST=localhost
set DB_PORT=5432
set DB_USERNAME=postgres
set DB_PASSWORD=your_password
```

3. Run tests:

```bash
.\artisan test --configuration=phpunit.pgsql.xml
```

## PostgreSQL-Specific Test Suites

### tests/PostgreSQL/JsonbOperationsTest.php

Tests PostgreSQL JSONB column operations:

- JSONB column creation and type verification
- JSONB query operators (`@>`, `?`, `->>`, `->>`)
- JSONB path queries
- JSONB array operations
- JSONB update operations
- JSONB indexing (GIN indexes)
- JSONB aggregation functions
- Tenant metadata JSONB queries
- JSONB null handling

### tests/PostgreSQL/SchemaOperationsTest.php

Tests PostgreSQL schema operations:

- Tenant schema creation and deletion
- Foreign key constraints with cascade delete
- Unique constraints
- Check constraints
- Index creation (simple, composite, partial)
- Generated columns
- Array columns
- Transaction isolation levels
- Full-text search
- Triggers
- Views

## Test Coverage

### SQLite Tests

- Fast execution (in-memory database)
- Suitable for unit tests
- Tests business logic
- Tests Laravel framework integration

### PostgreSQL Tests

- Production-like environment
- Tests PostgreSQL-specific features
- Tests schema operations
- Tests advanced database features
- Catches PostgreSQL-specific bugs

## Best Practices

### When to Use SQLite

- **Unit tests** that don't depend on database-specific features
- **Fast feedback** during development
- **Testing business logic** independent of database

### When to Use PostgreSQL

- **Integration tests** that interact with the database
- **Testing PostgreSQL-specific features** (JSONB, arrays, etc.)
- **Testing schema operations** (migrations, tenant creation)
- **Pre-production validation** of database-dependent code

### Writing Database-Agnostic Tests

When possible, write tests that work on both databases:

```php
// Good - Works on both SQLite and PostgreSQL
$user = User::where('email', 'test@example.com')->first();
$this->assertEquals('John', $user->name);

// PostgreSQL-specific - Only works on PostgreSQL
$user = User::whereRaw("metadata @> '{\"role\": \"admin\"}'::jsonb")->first();
```

For PostgreSQL-specific tests, place them in `tests/PostgreSQL/` directory.

## Troubleshooting

### PostgreSQL Connection Issues

**Problem**: Tests fail with connection errors

**Solution**:
1. Verify PostgreSQL is running: `docker ps` or `pg_isready`
2. Check connection parameters in `phpunit.pgsql.xml`
3. Verify firewall allows connections to port 5432

### JSONB Query Errors

**Problem**: JSONB queries fail in SQLite tests

**Solution**:
- JSONB queries only work in PostgreSQL
- Use `--configuration=phpunit.pgsql.xml` for JSONB tests
- Place JSONB-specific tests in `tests/PostgreSQL/` directory

### Schema Operation Failures

**Problem**: Schema operations fail in SQLite

**Solution**:
- Schema operations like `CREATE SCHEMA` are PostgreSQL-specific
- Use PostgreSQL configuration for schema tests
- Ensure `TenantSchemaService` is properly mocked in SQLite tests

### Slow Test Execution

**Problem**: PostgreSQL tests are slower than SQLite

**Solution**:
- This is expected - PostgreSQL has more overhead
- Use SQLite for fast feedback during development
- Use PostgreSQL for final validation before commits

## CI Pipeline Status

### Current Status

✅ Unit tests run on both SQLite and PostgreSQL
✅ Integration tests run on PostgreSQL
✅ Feature tests run on PostgreSQL
✅ PostgreSQL-specific test suites created
✅ Separate test configurations for SQLite and PostgreSQL

### Test Reports

- SQLite test reports: `tests/reports/testdox.html`
- PostgreSQL test reports: `tests/reports/testdox.pgsql.html`

## Migration Notes

### Migrating from SQLite-Only Testing

If you have existing tests that only work on SQLite:

1. **Identify PostgreSQL-specific code**:
   - JSONB queries
   - Schema operations
   - PostgreSQL functions

2. **Create PostgreSQL-specific tests**:
   - Place in `tests/PostgreSQL/` directory
   - Use `--configuration=phpunit.pgsql.xml`

3. **Update existing tests**:
   - Make database-agnostic where possible
   - Add conditional logic for database-specific features

4. **Run both test suites**:
   - SQLite for fast feedback
   - PostgreSQL for production validation

## Additional Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Laravel Database Testing](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

## Support

For issues or questions about PostgreSQL testing:

1. Check this guide first
2. Review test output in CI logs
3. Check PostgreSQL service logs
4. Consult PostgreSQL documentation for specific features
5. Open an issue with detailed error information

## Summary

PostgreSQL testing is now fully integrated into the CI pipeline, ensuring that:

- ✅ All tests run on both SQLite and PostgreSQL
- ✅ PostgreSQL-specific features have dedicated test coverage
- ✅ Production bugs are caught during testing
- ✅ Developers can run both test suites locally
- ✅ Test reports are generated for both databases

This critical infrastructure fix ensures production readiness by catching PostgreSQL-specific issues before deployment.
