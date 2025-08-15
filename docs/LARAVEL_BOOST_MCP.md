# Laravel Boost MCP Integration Guide

## Overview

This project integrates Laravel Boost's Model Context Protocol (MCP) server to provide AI-assisted development capabilities. The MCP server offers 15+ specialized tools for Laravel development, documentation search, and application inspection.

## Environment Configuration

### System Requirements
- **PHP**: 8.3.23 (XAMPP installation)
- **Laravel**: 12.20.0
- **Project Path**: `D:\DevCenter\abuilds\alumate`
- **MCP Server**: Laravel Boost v1.0.17

### MCP Configuration

The MCP server is configured in `.kiro/settings/mcp.json`:

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "D:\\DevCenter\\xampp\\php-8.3.23\\php.exe",
      "args": ["artisan", "boost:mcp"],
      "cwd": "D:\\DevCenter\\abuilds\\alumate",
      "env": {
        "FASTMCP_LOG_LEVEL": "ERROR"
      },
      "disabled": false,
      "autoApprove": [
        "application_info",
        "database_schema",
        "list_routes",
        "get_config",
        "search_docs",
        "list_artisan_commands",
        "tinker",
        "read_log_entries",
        "last_error"
      ]
    }
  }
}
```

## Available MCP Tools

### Database Operations
| Tool | Description | Example Usage |
|------|-------------|---------------|
| `database_schema` | Inspect database structure | "Show me the analytics tables structure" |
| `database_query` | Execute read-only queries | "Query user engagement metrics for last month" |
| `database_connections` | Check DB connections | "Show current database configuration" |

### Application Inspection
| Tool | Description | Example Usage |
|------|-------------|---------------|
| `application_info` | Get versions, packages, models | "What Laravel packages are installed?" |
| `list_routes` | Show all application routes | "List all analytics-related routes" |
| `get_config` | Read config values | "Check cache configuration settings" |
| `list_artisan_commands` | Available Artisan commands | "Show available Artisan commands" |

### Development Tools
| Tool | Description | Example Usage |
|------|-------------|---------------|
| `tinker` | Execute Laravel code | "Test the AnalyticsService methods" |
| `search_docs` | Query Laravel docs | "Find Laravel 12 service class patterns" |
| `read_log_entries` | Check application logs | "Show last 10 log entries" |
| `last_error` | Get recent errors | "What was the last application error?" |
| `browser_logs` | Frontend error logs | "Check for JavaScript errors" |

## AI-Assisted Development Workflows

### 1. Feature Development Workflow

```
1. Context Gathering:
   - Use `application_info` to understand current setup
   - Use `database_schema` to inspect relevant tables
   - Use `list_routes` to check existing endpoints

2. Code Generation:
   - Reference AI guidelines in `.ai/guidelines/`
   - Generate Laravel 12 compatible code
   - Include proper TypeScript definitions

3. Testing & Validation:
   - Use `tinker` to test code snippets
   - Use `read_log_entries` to check for errors
   - Write Pest PHP tests for new functionality

4. Documentation:
   - Use `search_docs` for Laravel best practices
   - Update relevant documentation files
```

### 2. Debugging Workflow

```
1. Error Investigation:
   - Use `last_error` to get recent error details
   - Use `read_log_entries` for error context
   - Use `browser_logs` for frontend issues

2. Configuration Check:
   - Use `get_config` to verify settings
   - Use `database_connections` for DB issues
   - Use `list_routes` to check route definitions

3. Code Analysis:
   - Use `tinker` to test problematic code
   - Use `database_query` to verify data integrity
   - Use `search_docs` for solution patterns
```

### 3. Analytics Dashboard Workflow

```
1. Data Analysis:
   - Use `database_schema` to understand analytics tables
   - Use `database_query` to test metric calculations
   - Use `get_config` to check analytics settings

2. Component Development:
   - Reference Vue 3 + TypeScript patterns
   - Use Canvas API for chart rendering
   - Implement proper error handling

3. Performance Optimization:
   - Use `tinker` to test service methods
   - Check caching configuration
   - Optimize database queries
```

## Example AI Prompts

### Database Analysis
```
"Use database_schema to show me the structure of analytics_events and user_engagement_metrics tables, then suggest indexes for better performance."
```

### Route Inspection
```
"Use list_routes to show all routes with 'analytics' in the name, then check if we need additional endpoints for the dashboard."
```

### Configuration Review
```
"Use get_config to check our cache, database, and queue configurations, then recommend optimizations for production."
```

### Code Testing
```
"Use tinker to test the AnalyticsService::getEngagementMetrics method with sample data, then show me the results."
```

### Documentation Search
```
"Use search_docs to find Laravel 12 best practices for service classes and dependency injection, then help me refactor the AnalyticsService."
```

## Troubleshooting

### MCP Connection Issues

1. **Check PHP Path**:
   ```powershell
   D:\DevCenter\xampp\php-8.3.23\php.exe --version
   ```

2. **Test MCP Server**:
   ```powershell
   D:\DevCenter\xampp\php-8.3.23\php.exe artisan boost:mcp --version
   ```

3. **Verify Working Directory**:
   ```powershell
   cd D:\DevCenter\abuilds\alumate
   .\artisan.ps1 boost:mcp --help
   ```

### Alternative Configurations

#### PowerShell Wrapper (mcp-alternative.json)
```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "powershell.exe",
      "args": ["-ExecutionPolicy", "Bypass", "-File", ".\\artisan.ps1", "boost:mcp"],
      "cwd": "D:\\DevCenter\\abuilds\\alumate"
    }
  }
}
```

#### Batch File Wrapper (boost-mcp.bat)
```batch
@echo off
cd /d "D:\DevCenter\abuilds\alumate"
"D:\DevCenter\xampp\php-8.3.23\php.exe" artisan boost:mcp %*
```

## Best Practices

### 1. Always Start with Context
Before generating code, use MCP tools to understand the current application state:
- Check existing database schema
- Review current routes and configurations
- Understand installed packages and versions

### 2. Leverage Documentation Search
Use `search_docs` to find Laravel-specific patterns and best practices for your exact version.

### 3. Test Before Implementation
Use `tinker` to validate code snippets and logic before implementing in the application.

### 4. Monitor Application Health
Regularly use `last_error` and `read_log_entries` to catch issues early.

### 5. Follow Project Conventions
Reference the AI guidelines in `.ai/guidelines/kiro-laravel-boost.blade.php` for project-specific patterns and conventions.

## Integration Benefits

- **Context-Aware Code Generation**: AI understands your specific application structure
- **Real-Time Application Inspection**: Live access to database, routes, and configuration
- **Laravel-Specific Documentation**: Instant access to relevant Laravel docs
- **Debugging Assistance**: Quick error diagnosis and log analysis
- **Performance Optimization**: Database and configuration analysis tools
- **Testing Support**: Code validation through Laravel's tinker environment

This MCP integration transforms AI assistance from generic code generation to intelligent, context-aware Laravel development support.