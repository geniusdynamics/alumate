# Getting Started

<cite>
**Referenced Files in This Document**
- [README.md](file://README.md)
- [composer.json](file://composer.json)
- [package.json](file://package.json)
- [artisan.sh](file://artisan.sh)
- [artisan.ps1](file://artisan.ps1)
- [start-dev.bat](file://start-dev.bat)
- [start-dev.sh](file://start-dev.sh)
- [start-dev.ps1](file://start-dev.ps1)
- [config/app.php](file://config/app.php)
- [config/database.php](file://config/database.php)
- [config/cache.php](file://config/cache.php)
- [config/queue.php](file://config/queue.php)
- [scripts/README.md](file://scripts/README.md)
- [scripts/data/create_sample_data.php](file://scripts/data/create_sample_data.php)
- [scripts/debugging/fix_blank_screen.bat](file://scripts/debugging/fix_blank_screen.bat)
- [scripts/testing/quick_test.php](file://scripts/testing/quick_test.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Development Setup](#development-setup)
5. [Environment Configuration](#environment-configuration)
6. [Database Setup](#database-setup)
7. [Sample Data Creation](#sample-data-creation)
8. [Development Server Startup](#development-server-startup)
9. [Access URLs](#access-urls)
10. [Verification Steps](#verification-steps)
11. [Troubleshooting](#troubleshooting)
12. [Environment-Specific Considerations](#environment-specific-considerations)
13. [Development Tools Recommendations](#development-tools-recommendations)
14. [Conclusion](#conclusion)

## Introduction
This guide provides a comprehensive, step-by-step approach to setting up the Alumate platform for development. It covers prerequisites, installation, environment configuration, database setup, sample data creation, development server startup, and troubleshooting. The platform is built with Laravel 12, Vue.js 3, TypeScript, and PostgreSQL, with optional Redis support for caching and queues.

## Prerequisites
Before installing the Alumate platform, ensure your system meets the following requirements:
- PHP 8.3+ (required)
- Node.js 18+ (required)
- Composer 2.x (required)
- PostgreSQL 13+ (required)
- Redis (optional, recommended for caching and queues)

These requirements are explicitly documented in the project's quick start section and technical architecture overview.

**Section sources**
- [README.md:267-274](file://README.md#L267-L274)
- [README.md:240-246](file://README.md#L240-L246)

## Installation
Follow these steps to clone and set up the project locally:

1. Clone the repository
   - Use your preferred Git client to clone the repository to your local machine.

2. Install PHP dependencies
   - Navigate to the project root and run the Composer installer to install PHP packages.

3. Install frontend dependencies
   - From the project root, run the Node package manager installer to install frontend packages.

4. Generate application key
   - Copy the example environment file to create your local configuration, then generate the application key.

5. Configure environment variables
   - Set the database connection details (host, port, database name, username, password) and any other required environment variables.

6. Run migrations
   - Execute database migrations to create the required tables.
   - Run tenant-specific migrations to set up multi-tenant schemas.

7. Seed the database
   - Populate the database with initial data using the seed command.

8. Create sample data
   - Use the provided script to generate sample data for development and testing.

9. Start development servers
   - Choose either the Windows batch script or the cross-platform shell script to start both Laravel and Vite servers.

10. Access the application
    - Open your browser and navigate to the main application URL to verify the setup.

**Section sources**
- [README.md:275-329](file://README.md#L275-L329)

## Development Setup
The project includes multiple development scripts tailored for different operating systems and workflows:

- Windows Batch Scripts
  - `start-dev.bat`: Cleans up existing processes, checks PHP and Node.js installations, clears Laravel caches, starts Vite in a separate window, waits for initialization, then starts Laravel in another window, and finally opens both URLs in the browser.
  - `start-dev.ps1`: PowerShell script with enhanced error handling, execution policy checks, and correct PHP paths. It performs similar steps to the batch script but with more robust process management and health checks.

- Unix/Linux Shell Script
  - `start-dev.sh`: Comprehensive script with port conflict detection, process cleanup, health monitoring, memory usage tracking, and interactive controls. It manages both Vite and Laravel servers with detailed logging and restart capabilities.

- Interactive Development Helper
  - `scripts/development/dev-helper.bat`: Provides an interactive menu for common development tasks, including starting servers, running tests, and managing data.

These scripts automate the development environment setup and provide reliable server management across different platforms.

**Section sources**
- [start-dev.bat:1-128](file://start-dev.bat#L1-L128)
- [start-dev.ps1:1-250](file://start-dev.ps1#L1-L250)
- [start-dev.sh:1-484](file://start-dev.sh#L1-L484)
- [scripts/README.md:47-51](file://scripts/README.md#L47-L51)

## Environment Configuration
Configure your local environment by setting the appropriate values in your `.env` file:

- Application settings
  - Set the application name, environment, debug mode, and URL according to your development setup.

- Database connection
  - Configure the default connection driver (PostgreSQL), host, port, database name, username, and password.
  - Ensure the database server is accessible and the specified database exists.

- Cache configuration
  - Choose the cache store (database, Redis, etc.) and configure connection details if using Redis.
  - Adjust template cache policies and tenant isolation settings as needed.

- Queue configuration
  - Select the queue driver (database, Redis, etc.) and configure connection details.
  - Set retry intervals and failed job handling options.

- Application key
  - Generate a unique application key using the Artisan key generation command.

The configuration files demonstrate the expected structure and available options for each component.

**Section sources**
- [config/app.php:16-100](file://config/app.php#L16-L100)
- [config/database.php:19-135](file://config/database.php#L19-L135)
- [config/cache.php:18-172](file://config/cache.php#L18-L172)
- [config/queue.php:16-75](file://config/queue.php#L16-L75)

## Database Setup
The platform uses PostgreSQL as the primary database with multi-tenant support. Follow these steps to prepare your database:

1. Create a PostgreSQL database for the application
   - Ensure PostgreSQL 13+ is installed and running on your system.
   - Create a dedicated database for the Alumate platform.

2. Configure database credentials
   - Update your `.env` file with the correct database connection details:
     - `DB_CONNECTION=pgsql`
     - `DB_HOST=127.0.0.1`
     - `DB_PORT=5432`
     - `DB_DATABASE=your_database_name`
     - `DB_USERNAME=your_username`
     - `DB_PASSWORD=your_password`

3. Run database migrations
   - Execute the migration command to create all required tables.
   - Run tenant-specific migrations to set up multi-tenant schemas.

4. Seed the database
   - Execute the database seeding command to populate initial data.

The database configuration supports multiple connection types including PostgreSQL, MySQL, MariaDB, SQLite, and SQL Server, with dedicated connections for central and tenant schemas.

**Section sources**
- [config/database.php:92-135](file://config/database.php#L92-L135)
- [README.md:293-300](file://README.md#L293-L300)

## Sample Data Creation
The platform provides scripts to generate realistic sample data for development and testing:

- Basic sample data
  - Use the sample data creation script to generate courses, employers, graduates, jobs, and related entities.
  - The script creates predefined roles (super-admin, institution-admin, graduate, employer) and assigns them appropriately.

- Tenant-specific sample data
  - Use the tenant sample data creation script to generate data specific to multi-tenant scenarios.

- Quick verification
  - Run the quick test script to verify that core services, models, routes, and database connectivity are functioning correctly.

The sample data creation process ensures you have a complete dataset for testing analytics, job matching, and other platform features.

**Section sources**
- [scripts/data/create_sample_data.php:19-313](file://scripts/data/create_sample_data.php#L19-L313)
- [scripts/testing/quick_test.php:9-93](file://scripts/testing/quick_test.php#L9-L93)

## Development Server Startup
Choose the development script that best fits your operating system:

### Windows Users
- Option 1: Batch script (simple)
  - Run the batch script to automatically handle all setup steps and open both servers.
  - The script cleans up existing processes, checks dependencies, clears caches, starts Vite and Laravel, and opens browser windows.

- Option 2: PowerShell script (enhanced)
  - Use the PowerShell script for better error handling and process management.
  - Includes execution policy checks and detailed health monitoring.

### Unix/Linux Users
- Use the shell script for comprehensive server management:
  - Port conflict detection and automatic cleanup
  - Health monitoring and restart capabilities
  - Memory usage tracking and detailed logging
  - Interactive controls for server management

### Manual Startup
If you prefer manual control:
- Start the Laravel development server on port 8080
- Start the Vite development server on port 5100
- Ensure both servers are running and accessible

**Section sources**
- [start-dev.bat:1-128](file://start-dev.bat#L1-L128)
- [start-dev.ps1:1-250](file://start-dev.ps1#L1-L250)
- [start-dev.sh:1-484](file://start-dev.sh#L1-L484)

## Access URLs
Once the development servers are running, access the application using these URLs:

- Main Application: http://127.0.0.1:8080
- Login Page: http://127.0.0.1:8080/login
- Registration Page: http://127.0.0.1:8080/register
- Jobs Portal: http://127.0.0.1:8080/jobs
- Testing Suite: http://127.0.0.1:8080/testing

The development scripts automatically open these URLs in your browser after startup.

**Section sources**
- [README.md:326-347](file://README.md#L326-L347)
- [start-dev.bat:114-123](file://start-dev.bat#L114-L123)

## Verification Steps
Perform these quick checks to ensure your installation is working correctly:

1. **Server Status**
   - Verify both Laravel (port 8080) and Vite (port 5100) servers are running
   - Check that the development monitors show "DEVELOPMENT SERVERS RUNNING"

2. **Application Access**
   - Navigate to the main application URL and confirm the homepage loads
   - Test login functionality with provided test credentials

3. **Database Connectivity**
   - Run the quick test script to verify database connectivity and core functionality
   - Check that analytics routes and services are available

4. **Sample Data**
   - Verify that sample users, courses, and other entities are present in the database
   - Test analytics dashboards and reporting features

5. **Development Tools**
   - Confirm that hot reload works for both backend and frontend changes
   - Test that the interactive development helper menu is accessible

**Section sources**
- [scripts/testing/quick_test.php:9-93](file://scripts/testing/quick_test.php#L9-L93)
- [scripts/debugging/fix_blank_screen.bat:39-48](file://scripts/debugging/fix_blank_screen.bat#L39-L48)

## Troubleshooting
Common issues and their solutions:

### Blank Screen Issues
- Run the blank screen fix script which handles:
  - Frontend dependency installation
  - Asset rebuilding
  - Laravel cache clearing and optimization
  - Sample data regeneration

### Port Conflicts
- The development scripts automatically detect and free conflicting ports
- For manual resolution, identify processes using the required ports and terminate them

### PHP Path Issues
- The PowerShell script uses a hardcoded PHP path that may need adjustment
- Update the PHP path in the script to match your local installation

### Node.js Version Problems
- Ensure Node.js 18+ is installed and accessible in your PATH
- The scripts check for Node.js availability before proceeding

### Database Connection Errors
- Verify PostgreSQL is running and accessible
- Check that the database credentials in your `.env` file are correct
- Ensure the specified database exists and is accessible

### Cache and Configuration Issues
- Clear all Laravel caches using the provided commands
- Regenerate the application key if needed
- Check Redis connectivity if using Redis for caching or queues

### Execution Policy Restrictions (Windows)
- The PowerShell script checks for execution policy restrictions
- Grant appropriate permissions or use the batch script alternative

**Section sources**
- [scripts/debugging/fix_blank_screen.bat:1-49](file://scripts/debugging/fix_blank_screen.bat#L1-L49)
- [start-dev.ps1:4-16](file://start-dev.ps1#L4-L16)
- [artisan.ps1:13-20](file://artisan.ps1#L13-L20)

## Environment-Specific Considerations
Different operating systems have specific considerations for development:

### Windows Development
- Use the batch script for simplest setup
- PowerShell script provides enhanced error handling and monitoring
- PHP path may need manual adjustment based on your XAMPP installation

### Unix/Linux Development
- The shell script provides comprehensive server management
- Automatic port conflict detection and resolution
- Detailed memory usage monitoring and logging
- Interactive controls for server restart and status checking

### Cross-Platform Consistency
- All scripts maintain consistent behavior across platforms
- Environment variables and configuration remain the same regardless of OS
- Development workflow is standardized through shared scripts

## Development Tools Recommendations
Enhance your development experience with these recommended tools:

### Backend Development
- PHP 8.3+ with Composer for dependency management
- Laravel Valet or XAMPP for local web server management
- Database tools for PostgreSQL administration

### Frontend Development
- Node.js 18+ with npm or pnpm for package management
- VS Code with Vue.js extensions for optimal development experience
- Browser developer tools for debugging

### Development Workflow
- Use the interactive development helper for common tasks
- Leverage the comprehensive testing suite for quality assurance
- Utilize the debugging scripts for issue resolution

### Optional Enhancements
- Redis for caching and queue processing
- Docker for containerized development environments
- IDE plugins for Laravel and Vue.js development

**Section sources**
- [composer.json:11-23](file://composer.json#L11-L23)
- [package.json:48-82](file://package.json#L48-L82)
- [scripts/README.md:101-124](file://scripts/README.md#L101-L124)

## Conclusion
You now have all the information needed to successfully set up and develop on the Alumate platform. The comprehensive scripts and documentation ensure a smooth onboarding experience across different operating systems. Remember to verify your setup using the provided verification steps and utilize the troubleshooting guidance for common issues. For ongoing development, leverage the interactive helper scripts and comprehensive testing suite to maintain code quality and system reliability.