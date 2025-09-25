# Administrator Training Guide

This comprehensive guide is designed to help system administrators effectively manage and maintain the four integrated platforms: Modern Alumni Platform, Graduate Tracking System, Component Library System, and Vue.js Page Builder System.

## Table of Contents

1. [System Overview](#system-overview)
2. [Installation and Setup](#installation-and-setup)
3. [User Management](#user-management)
4. [Tenant Management](#tenant-management)
5. [Content Management](#content-management)
6. [Component Library Administration](#component-library-administration)
7. [Page Builder Administration](#page-builder-administration)
8. [Monitoring and Maintenance](#monitoring-and-maintenance)
9. [Security Management](#security-management)
10. [Troubleshooting](#troubleshooting)
11. [Performance Optimization](#performance-optimization)
12. [Backup and Recovery](#backup-and-recovery)

## System Overview

### Platform Architecture

The integrated system consists of four main components:

1. **Modern Alumni Platform**: Social networking and career development features
2. **Graduate Tracking System**: Graduate outcome tracking and analytics
3. **Component Library System**: Reusable UI components for page building
4. **Vue.js Page Builder System**: Drag-and-drop page creation interface

### Technical Stack

- **Backend**: Laravel PHP Framework with multi-tenancy support
- **Frontend**: Vue.js 3 with TypeScript
- **Database**: PostgreSQL with tenant isolation
- **Caching**: Redis for performance optimization
- **Queue Processing**: Redis-based job queues
- **Real-time Communication**: WebSocket support
- **Storage**: Cloud storage integration (AWS S3, Google Cloud Storage)

### System Requirements

#### Server Requirements

- PHP 8.3+
- PostgreSQL 17+
- Redis 6+
- Node.js 18+
- Composer 2.x
- Nginx or Apache web server

#### Recommended Hardware

- **CPU**: 4+ cores
- **RAM**: 16GB+ for production
- **Storage**: 100GB+ SSD storage
- **Network**: 100Mbps+ connectivity

## Installation and Setup

### Prerequisites

Before installation, ensure the following prerequisites are met:

1. Server meets hardware requirements
2. Required software packages are installed
3. Database server is accessible
4. DNS records are configured
5. SSL certificates are available

### Installation Steps

#### 1. Clone Repository

```bash
git clone https://github.com/organization/alumni-platform.git
cd alumni-platform
```

#### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### 3. Configure Environment

```bash
# Copy example environment file
cp .env.example .env

# Edit environment configuration
nano .env
```

Key configuration variables:
- `DB_HOST`: Database server hostname
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `REDIS_HOST`: Redis server hostname
- `APP_URL`: Application base URL

#### 4. Generate Application Key

```bash
php artisan key:generate
```

#### 5. Run Database Migrations

```bash
# Run base migrations
php artisan migrate

# Run tenant migrations
php artisan tenants:migrate
```

#### 6. Seed Initial Data

```bash
# Seed base data
php artisan db:seed

# Seed demo data (optional)
php artisan db:seed --class=DemoDataSeeder
```

#### 7. Build Frontend Assets

```bash
npm run build
```

#### 8. Configure Web Server

Example Nginx configuration:

```nginx
server {
    listen 443 ssl;
    server_name alumni.example.com;
    
    root /var/www/alumni-platform/public;
    index index.php;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Post-Installation Configuration

#### 1. Create Super Admin User

```bash
php artisan admin:create
```

#### 2. Configure Email Settings

Update `.env` file with email configuration:
- `MAIL_MAILER`: Mail driver (smtp, sendmail, etc.)
- `MAIL_HOST`: SMTP server
- `MAIL_PORT`: SMTP port
- `MAIL_USERNAME`: SMTP username
- `MAIL_PASSWORD`: SMTP password
- `MAIL_ENCRYPTION`: Encryption method (tls, ssl)

#### 3. Set Up Scheduled Tasks

Add to crontab:

```bash
* * * * * cd /var/www/alumni-platform && php artisan schedule:run >> /dev/null 2>&1
```

#### 4. Configure Queue Workers

Start queue worker:

```bash
php artisan queue:work --daemon
```

## User Management

### User Roles and Permissions

The system supports the following user roles:

1. **Super Admin**: Full system access
2. **Institution Admin**: Institution-specific administration
3. **Alumni**: Standard alumni user
4. **Employer**: Employer account
5. **Graduate**: Recent graduate account
6. **Content Editor**: Page and content management
7. **Developer**: API and integration access

### Creating Users

#### Via Admin Interface

1. Navigate to "User Management" in admin panel
2. Click "Add New User"
3. Fill in user details:
   - Name
   - Email
   - Role
   - Institution (if applicable)
4. Set initial password or send invitation email

#### Via Command Line

```bash
# Create user with specific role
php artisan user:create --name="John Doe" --email="john@example.com" --role="alumni"

# Create institution admin
php artisan user:create --name="Admin User" --email="admin@institution.edu" --role="institution_admin" --institution="institution-id"
```

### Managing User Roles

#### Assigning Roles

```bash
# Assign role to existing user
php artisan user:assign-role --user="user-id" --role="content-editor"

# Remove role from user
php artisan user:remove-role --user="user-id" --role="content-editor"
```

#### Bulk Role Management

```bash
# Assign role to multiple users
php artisan user:bulk-assign --role="alumni" --filter="graduation_year:2023"

# Remove inactive users
php artisan user:cleanup --inactive-days=365
```

### User Activity Monitoring

#### Viewing User Activity

1. Navigate to "Activity Logs" in admin panel
2. Filter by:
   - User
   - Date range
   - Activity type
   - IP address

#### Exporting Activity Reports

```bash
# Export activity logs
php artisan logs:export --format=csv --start-date="2024-01-01" --end-date="2024-01-31"
```

## Tenant Management

### Creating New Tenants

#### Via Admin Interface

1. Navigate to "Tenant Management"
2. Click "Add New Tenant"
3. Fill in tenant details:
   - Institution name
   - Domain/subdomain
   - Admin contact
   - Subscription plan

#### Via Command Line

```bash
# Create new tenant
php artisan tenant:create --name="University Name" --domain="university.example.com" --admin-email="admin@university.edu"

# Create tenant with custom configuration
php artisan tenant:create --name="University Name" --domain="university.example.com" --config="theme:blue,max_users:1000"
```

### Tenant Configuration

#### Customizing Tenant Settings

```bash
# Update tenant configuration
php artisan tenant:config --tenant="tenant-id" --set="theme=corporate" --set="max_storage=10GB"

# View tenant configuration
php artisan tenant:config --tenant="tenant-id" --show
```

#### Managing Tenant Resources

```bash
# Allocate resources to tenant
php artisan tenant:allocate --tenant="tenant-id" --cpu=2 --memory=4GB --storage=50GB

# View resource usage
php artisan tenant:usage --tenant="tenant-id"
```

### Tenant Data Isolation

#### Verifying Isolation

```bash
# Check tenant data boundaries
php artisan tenant:verify-isolation --tenant="tenant-id"

# Run tenant-specific tests
php artisan test --filter=TenantIsolationTest
```

#### Cross-Tenant Data Access

```bash
# Grant temporary cross-tenant access (for support)
php artisan tenant:grant-access --source="tenant-a" --target="tenant-b" --duration="1h"

# Revoke cross-tenant access
php artisan tenant:revoke-access --source="tenant-a" --target="tenant-b"
```

## Content Management

### Managing Pages

#### Creating Pages

1. Navigate to "Content Management" → "Pages"
2. Click "Add New Page"
3. Enter page details:
   - Title
   - URL slug
   - Template (if applicable)
   - Status (draft/published)

#### Editing Pages

1. Select page from page list
2. Click "Edit"
3. Modify content using:
   - Visual editor
   - Raw HTML editor
   - Component library integration

#### Publishing Workflow

```bash
# Publish page via CLI
php artisan page:publish --page="page-id" --schedule="2024-02-01 09:00"

# Unpublish page
php artisan page:unpublish --page="page-id"
```

### Managing Media Assets

#### Uploading Assets

1. Navigate to "Media Library"
2. Click "Upload"
3. Select files to upload
4. Add metadata:
   - Title
   - Description
   - Tags
   - Access permissions

#### Organizing Assets

```bash
# Create asset collection
php artisan media:collection --name="Event Photos 2024" --tags="event,2024"

# Move assets to collection
php artisan media:move --assets="asset-ids" --collection="collection-id"
```

#### Asset Optimization

```bash
# Optimize images
php artisan media:optimize --type=image --quality=80

# Generate thumbnails
php artisan media:thumbnails --sizes="150x150,300x300,600x600"
```

### Content Scheduling

#### Scheduling Publication

```bash
# Schedule content publication
php artisan content:schedule --content="content-id" --publish-at="2024-02-01 09:00:00"

# Schedule content expiration
php artisan content:expire --content="content-id" --expire-at="2024-12-31 23:59"
```

#### Managing Scheduled Content

```bash
# View scheduled content
php artisan content:scheduled --status=pending

# Cancel scheduled publication
php artisan content:unschedule --content="content-id"
```

## Component Library Administration

### Managing Components

#### Creating Components

1. Navigate to "Component Library" → "Components"
2. Click "Add New Component"
3. Fill in component details:
   - Name
   - Category
   - Type
   - Configuration schema
   - Preview image

#### Component Validation

```bash
# Validate component configuration
php artisan component:validate --component="component-id"

# Test component rendering
php artisan component:test --component="component-id" --data="test-data.json"
```

#### Component Versioning

```bash
# Create new component version
php artisan component:version --component="component-id" --version="2.0.0" --changelog="Added new features"

# Rollback component version
php artisan component:rollback --component="component-id" --version="1.2.3"
```

### Managing Themes

#### Creating Themes

1. Navigate to "Component Library" → "Themes"
2. Click "Add New Theme"
3. Configure theme settings:
   - Name
   - Color palette
   - Typography
   - Spacing system
   - Custom CSS (optional)

#### Theme Application

```bash
# Apply theme to tenant
php artisan theme:apply --theme="theme-id" --tenant="tenant-id"

# Apply theme to specific components
php artisan theme:apply --theme="theme-id" --components="component-ids"
```

#### Theme Inheritance

```bash
# Create child theme
php artisan theme:inherit --parent="parent-theme-id" --name="Child Theme" --overrides="color-primary:#ff0000"

# View theme hierarchy
php artisan theme:hierarchy --theme="theme-id"
```

### Component Analytics

#### Viewing Analytics

1. Navigate to "Component Library" → "Analytics"
2. Select component or category
3. Choose date range
4. View metrics:
   - Usage count
   - Performance data
   - User feedback

#### A/B Testing

```bash
# Create A/B test
php artisan component:abtest --component="component-id" --variants="variant-a,variant-b" --traffic=50,50

# View test results
php artisan component:abtest-results --test="test-id"

# Promote winning variant
php artisan component:promote --variant="winning-variant-id"
```

## Page Builder Administration

### Managing Templates

#### Creating Templates

1. Navigate to "Page Builder" → "Templates"
2. Click "Add New Template"
3. Design template using:
   - Component library
   - Custom styling
   - Layout configuration
4. Save as template

#### Template Categories

```bash
# Create template category
php artisan template:category --name="Event Landing Pages" --description="Templates for event promotions"

# Assign template to category
php artisan template:assign-category --template="template-id" --category="category-id"
```

#### Template Permissions

```bash
# Set template access permissions
php artisan template:permissions --template="template-id" --roles="admin,content-editor" --tenants="tenant-a,tenant-b"
```

### Managing Page Assets

#### Asset Libraries

```bash
# Create asset library
php artisan page:asset-library --name="Corporate Assets" --tenant="tenant-id"

# Add assets to library
php artisan page:asset-add --library="library-id" --assets="asset-ids"
```

#### Asset Optimization

```bash
# Optimize page assets
php artisan page:optimize-assets --page="page-id" --quality=85

# Generate responsive images
php artisan page:responsive-images --page="page-id" --sizes="320,768,1024,1200"
```

### Page Performance Monitoring

#### Performance Metrics

```bash
# Check page performance
php artisan page:performance --page="page-id" --metrics="load_time,first_paint,cls"

# Generate performance report
php artisan page:performance-report --page="page-id" --format=pdf
```

#### Performance Optimization

```bash
# Optimize page for performance
php artisan page:optimize --page="page-id" --strategy="aggressive"

# Enable lazy loading
php artisan page:lazy-load --page="page-id" --components="image,video"
```

## Monitoring and Maintenance

### System Monitoring

#### Health Checks

```bash
# Run system health check
php artisan system:health

# Check specific service
php artisan system:health --service=database

# Generate health report
php artisan system:health-report --format=json
```

#### Resource Monitoring

```bash
# Monitor system resources
php artisan system:monitor --resources=cpu,memory,disk

# Set up monitoring alerts
php artisan system:alert --metric=cpu --threshold=80 --action="email:admin@example.com"
```

### Log Management

#### Viewing Logs

```bash
# View recent logs
php artisan log:view --lines=100

# Filter logs by level
php artisan log:view --level=error --since="1 hour ago"

# Search logs
php artisan log:search --query="database connection"
```

#### Log Rotation

```bash
# Rotate logs
php artisan log:rotate --max-size=100MB --keep=30

# Archive old logs
php artisan log:archive --older-than="30 days"
```

### Scheduled Maintenance

#### Maintenance Windows

```bash
# Schedule maintenance window
php artisan maintenance:schedule --start="2024-02-01 02:00:00" --duration="2h" --message="System maintenance in progress"

# Cancel maintenance window
php artisan maintenance:cancel --window="window-id"
```

#### Automated Maintenance Tasks

```bash
# Clean up old sessions
php artisan cleanup:sessions --older-than="24h"

# Clear cache
php artisan cache:clear-all

# Optimize database
php artisan db:optimize
```

## Security Management

### User Authentication

#### Two-Factor Authentication

```bash
# Enable 2FA for user
php artisan user:enable-2fa --user="user-id"

# Disable 2FA for user
php artisan user:disable-2fa --user="user-id"

# View 2FA status
php artisan user:2fa-status --user="user-id"
```

#### Password Policies

```bash
# Set password policy
php artisan security:password-policy --min-length=12 --require-special=true --expire-days=90

# Force password reset
php artisan user:force-password-reset --users="user-ids"
```

### Access Control

#### Role-Based Access Control

```bash
# Create custom role
php artisan role:create --name="Marketing Manager" --permissions="page.create,page.edit,media.upload"

# Assign permissions to role
php artisan role:assign-permission --role="role-id" --permissions="component.manage,theme.edit"
```

#### IP Whitelisting

```bash
# Add IP to whitelist
php artisan security:whitelist-ip --ip="192.168.1.100" --description="Admin office"

# Remove IP from whitelist
php artisan security:unwhitelist-ip --ip="192.168.1.100"
```

### Security Auditing

#### Security Scans

```bash
# Run security scan
php artisan security:scan

# Check for vulnerable dependencies
php artisan security:check-dependencies

# Audit user permissions
php artisan security:audit-permissions
```

#### Incident Response

```bash
# Lock user account
php artisan user:lock --user="user-id" --reason="Suspicious activity"

# Unlock user account
php artisan user:unlock --user="user-id"

# Generate security report
php artisan security:report --format=pdf --period="last_30_days"
```

## Troubleshooting

### Common Issues and Solutions

#### Database Connection Issues

```bash
# Test database connection
php artisan db:test-connection

# Reset database connection pool
php artisan db:reset-connections

# View database status
php artisan db:status
```

#### Cache Issues

```bash
# Clear all caches
php artisan cache:clear-all

# Clear specific cache
php artisan cache:clear --tags="pages,components"

# View cache statistics
php artisan cache:stats
```

#### Queue Processing Issues

```bash
# Restart queue workers
php artisan queue:restart

# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry --id=job-id

# Clear failed jobs
php artisan queue:flush
```

### Performance Issues

#### Identifying Bottlenecks

```bash
# Profile application performance
php artisan profile --duration=60

# Check slow queries
php artisan db:slow-queries --threshold=1s

# Analyze memory usage
php artisan memory:analyze
```

#### Optimization Recommendations

```bash
# Get optimization suggestions
php artisan optimize:suggest

# Apply automatic optimizations
php artisan optimize:auto

# Generate optimization report
php artisan optimize:report
```

### Debugging Tools

#### Debugging Commands

```bash
# Enable debug mode
php artisan debug:enable

# View debug information
php artisan debug:info

# Disable debug mode
php artisan debug:disable
```

#### Logging and Tracing

```bash
# Enable detailed logging
php artisan log:verbose --level=debug

# Trace request flow
php artisan trace:request --request-id="req-123"

# View application timeline
php artisan timeline --request-id="req-123"
```

## Performance Optimization

### Database Optimization

#### Query Optimization

```bash
# Analyze slow queries
php artisan db:analyze-queries

# Optimize database tables
php artisan db:optimize-tables

# Update database statistics
php artisan db:update-stats
```

#### Index Management

```bash
# Create database index
php artisan db:create-index --table="graduates" --columns="graduation_year,status"

# View existing indexes
php artisan db:list-indexes --table="users"

# Remove unused indexes
php artisan db:remove-index --table="events" --index="idx_old_filter"
```

### Caching Strategies

#### Cache Configuration

```bash
# Configure cache settings
php artisan cache:configure --driver=redis --ttl=3600

# Warm up cache
php artisan cache:warmup --tags="pages,components,users"

# Monitor cache performance
php artisan cache:monitor
```

#### Cache Invalidation

```bash
# Invalidate specific cache tags
php artisan cache:invalidate --tags="pages,components"

# Clear user-specific cache
php artisan cache:clear-user --user="user-id"

# Set cache expiration
php artisan cache:set-expiry --key="page-123" --ttl=1800
```

### Asset Optimization

#### Image Optimization

```bash
# Optimize all images
php artisan media:optimize-images --quality=85 --format=webp

# Generate responsive image sets
php artisan media:responsive --sizes="320,768,1024,1200"

# Compress SVG files
php artisan media:compress-svg
```

#### CSS/JS Optimization

```bash
# Minify CSS files
php artisan assets:minify-css

# Minify JavaScript files
php artisan assets:minify-js

# Bundle assets
php artisan assets:bundle --output="public/bundle.min.js"
```

## Backup and Recovery

### Database Backup

#### Creating Backups

```bash
# Create full database backup
php artisan backup:create --type=full

# Create incremental backup
php artisan backup:create --type=incremental

# Backup specific tables
php artisan backup:create --tables="users,graduates,jobs"
```

#### Backup Management

```bash
# List backups
php artisan backup:list

# Verify backup integrity
php artisan backup:verify --backup="backup-id"

# Delete old backups
php artisan backup:cleanup --older-than="30 days"
```

### File Backup

#### Media Assets

```bash
# Backup media assets
php artisan backup:media --destination=s3://backup-bucket/media

# Sync assets between storage providers
php artisan media:sync --source=local --target=s3

# Verify asset integrity
php artisan media:verify --check-checksums
```

#### Configuration Backup

```bash
# Backup configuration files
php artisan backup:config --files=".env,config/*.php"

# Export tenant configurations
php artisan backup:tenants --format=json
```

### Disaster Recovery

#### Restoration Procedures

```bash
# Restore database from backup
php artisan backup:restore --backup="backup-id" --target=database

# Restore specific tables
php artisan backup:restore --backup="backup-id" --tables="users,graduates"

# Restore media assets
php artisan backup:restore --backup="backup-id" --target=media
```

#### Recovery Testing

```bash
# Test disaster recovery plan
php artisan recovery:test --scenario="database_failure"

# Validate backup restoration
php artisan recovery:validate --backup="backup-id"

# Generate recovery report
php artisan recovery:report --format=pdf
```

### Business Continuity

#### High Availability

```bash
# Configure database replication
php artisan ha:configure-db --master="db1.example.com" --slaves="db2.example.com,db3.example.com"

# Set up load balancing
php artisan ha:configure-lb --servers="web1.example.com,web2.example.com"
```

#### Failover Management

```bash
# Trigger failover
php artisan ha:failover --service=database

# Check failover status
php artisan ha:status

# Configure failover alerts
php artisan ha:alert --service=web --action="email:admin@example.com"
```

---

For additional support or to report issues, please contact the system administration team at admin.support@alumni-platform.com