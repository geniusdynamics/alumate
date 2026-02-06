# Admin Training Materials

This comprehensive admin training guide covers all aspects of administering the Alumate platform.

## Table of Contents

1. [System Overview](#system-overview)
2. [User Management](#user-management)
3. [Tenant Management](#tenant-management)
4. [Analytics Administration](#analytics-administration)
5. [Security Administration](#security-administration)
6. [System Configuration](#system-configuration)
7. [Monitoring and Alerting](#monitoring-and-alerting)
8. [Backup and Recovery](#backup-and-recovery)

---

## System Overview

### Architecture Introduction

Alumate is built on a modern architecture:

- **Frontend**: Vue.js 3 with TypeScript
- **Backend**: Laravel (PHP 8.3+)
- **Database**: PostgreSQL 17+
- **Caching**: Redis
- **Search**: Elasticsearch
- **Queue System**: Redis-based job queues

### Admin Dashboard

Access: `/admin/dashboard`

#### Dashboard Sections

| Section | Purpose | Access Level |
|---------|---------|--------------|
| Overview | System health summary | All admins |
| Users | User management | User admins |
| Analytics | Analytics configuration | Analytics admins |
| Settings | System configuration | Super admins |
| Security | Security monitoring | Security admins |

### Admin Roles

| Role | Permissions |
|------|-------------|
| Super Admin | Full system access |
| Analytics Admin | Analytics features only |
| User Admin | User management only |
| Security Admin | Security features only |
| Support Admin | Limited support access |

---

## User Management

### User Administration

Access: `/admin/users`

#### User List

The user management interface provides:

- **Search**: Find users by name, email, etc.
- **Filters**: Filter by status, role, tenant, etc.
- **Bulk Actions**: Perform actions on multiple users
- **Export**: Export user data

#### Creating Users

```php
// Via Admin Panel
1. Navigate to /admin/users
2. Click "Add User"
3. Fill in required fields
4. Assign roles
5. Send welcome email
```

#### User States

| State | Description |
|-------|-------------|
| Active | Normal user account |
| Inactive | Disabled but recoverable |
| Suspended | Temporarily disabled |
| Deleted | Permanently removed (soft delete) |

### Role Management

#### Role Types

| Role | Description | Capabilities |
|------|-------------|--------------|
| User | Standard user | Basic features |
| Moderator | Content moderation | Approve/reject content |
| Admin | Administrative | User/content management |
| Super Admin | Full access | System configuration |

#### Assigning Roles

1. Navigate to user profile
2. Click "Edit Roles"
3. Select appropriate roles
4. Save changes

### Bulk Operations

Available bulk operations:

- **Export**: Export selected users to CSV
- **Activate**: Activate inactive users
- **Deactivate**: Deactivate active users
- **Delete**: Soft delete users
- **Role Update**: Update roles for multiple users

---

## Tenant Management

### Multi-Tenancy Overview

Alumate supports multi-tenancy for:

- Multiple institutions
- Isolated data per tenant
- Custom configurations per tenant

### Tenant Administration

Access: `/admin/tenants`

#### Creating Tenants

```php
// Required fields
$tenantData = [
    'name' => 'Institution Name',
    'domain' => 'institution.alumate.io',
    'slug' => 'institution-name',
    'settings' => [
        'features' => [...],
        'branding' => [...],
        'limits' => [...]
    ]
];
```

#### Tenant Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Feature Flags | Enabled features | Per plan |
| Branding | Custom colors/logo | Default |
| User Limits | Max users | Per plan |
| Storage Limits | Max storage | Per plan |

### Tenant Migration

#### Running Tenant Migrations

```bash
# Migrate all tenants
php artisan tenants:migrate

# Migrate specific tenant
php artisan tenants:migrate --tenant=tenant_id
```

---

## Analytics Administration

### Analytics Dashboard

Access: `/admin/analytics`

#### Key Metrics

| Metric | Description | Refresh Rate |
|--------|-------------|--------------|
| Active Users | Users in last 30 days | Hourly |
| Engagement Score | Overall platform engagement | Daily |
| Retention Rate | User retention percentage | Daily |
| Growth Rate | User growth percentage | Weekly |

### Analytics Configuration

#### Enabling/Disable Analytics

```php
// config/analytics.php
return [
    'enabled' => true,
    'retention_days' => 365,
    'sampling_rate' => 1.0,
    'features' => [
        'page_views' => true,
        'user_tracking' => true,
        'event_tracking' => true,
        'custom_metrics' => true
    ]
];
```

### Custom Metrics

Creating custom analytics metrics:

1. Define metric in configuration
2. Implement data collection
3. Configure aggregation rules
4. Set up visualization

### Analytics Reports

Available reports:

- **User Activity Report**: Daily/weekly/monthly
- **Engagement Report**: Feature usage
- **Retention Report**: Cohort analysis
- **Custom Reports**: User-defined filters

---

## Security Administration

### Security Overview

Access: `/admin/security`

#### Security Dashboard

| Component | Monitoring |
|-----------|------------|
| Authentication | Login attempts, failures |
| Authorization | Permission violations |
| Data Protection | Encryption status |
| Network | API usage, threats |

### Authentication Settings

#### Password Policy

```php
// Password requirements
$policy = [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special' => true,
    'expiry_days' => 90,
    'remember_count' => 12
];
```

#### Two-Factor Authentication

| Option | Description |
|--------|-------------|
| Required | All users must enable 2FA |
| Optional | Users can enable 2FA |
| Disabled | 2FA not available |

### Access Control

#### IP Whitelisting

Configure allowed IP ranges:

```php
'ip_whitelist' => [
    '192.168.1.0/24',
    '10.0.0.0/8'
]
```

#### Session Management

| Setting | Value | Description |
|---------|-------|-------------|
| Timeout | 30 minutes | Auto logout |
| Max Sessions | 5 | Concurrent sessions |
| Regenerate | On login | Session regeneration |

### Audit Logging

Access: `/admin/security/audit`

#### Log Types

| Type | Description | Retention |
|------|-------------|-----------|
| Authentication | Login/logout events | 1 year |
| Authorization | Permission changes | 1 year |
| Data Access | Sensitive data access | 1 year |
| Configuration | System changes | Permanent |

---

## System Configuration

### General Settings

Access: `/admin/settings/general`

#### Core Settings

| Setting | Description | Example |
|---------|-------------|---------|
| Site Name | Platform display name | Alumate |
| Site URL | Base URL | https://alumate.io |
| Support Email | Contact email | support@alumate.io |
| Timezone | Default timezone | Africa/Nairobi |
| Locale | Default language | en |

### Feature Flags

Access: `/admin/settings/features`

#### Available Features

| Feature | Status | Description |
|---------|--------|-------------|
| Analytics | Enabled | Analytics dashboard |
| Mentorship | Enabled | Mentorship program |
| Jobs | Enabled | Job board |
| Events | Enabled | Event management |
| Groups | Enabled | Community groups |

### Notification Settings

Access: `/admin/settings/notifications`

#### Email Notifications

| Notification Type | Default | Description |
|-------------------|---------|-------------|
| Welcome Email | Enabled | New user welcome |
| Password Reset | Enabled | Password recovery |
| Activity Digest | Enabled | Daily/weekly summaries |
| System Alerts | Enabled | Critical notifications |

---

## Monitoring and Alerting

### Monitoring Dashboard

Access: `/admin/monitoring`

#### System Health

| Metric | Status | Threshold |
|--------|--------|-----------|
| CPU Usage | Healthy | < 80% |
| Memory Usage | Healthy | < 85% |
| Disk Space | Healthy | < 90% |
| Response Time | Healthy | < 500ms |

### Alert Configuration

Access: `/admin/monitoring/alerts`

#### Alert Types

| Type | Priority | Response Time |
|------|----------|---------------|
| Critical | Immediate | < 15 minutes |
| Warning | Within 1 hour | < 1 hour |
| Info | Within 24 hours | < 24 hours |

### Setting Up Alerts

```php
// Alert configuration
$alerts = [
    'cpu_threshold' => 80,
    'memory_threshold' => 85,
    'disk_threshold' => 90,
    'error_rate_threshold' => 5,
    'response_time_threshold' => 500
];
```

### Log Management

Access: `/admin/monitoring/logs`

#### Log Levels

| Level | Description | Use Case |
|-------|-------------|----------|
| Emergency | System unusable | Critical failures |
| Alert | Immediate action | Server errors |
| Critical | Critical conditions | Component failures |
| Error | Error conditions | Failed operations |
| Warning | Warning conditions | Potential issues |
| Notice | Normal but significant | Important events |
| Info | Informational | General events |
| Debug | Debug-level messages | Development |

---

## Backup and Recovery

### Backup Management

Access: `/admin/backup`

#### Backup Types

| Type | Schedule | Retention |
|------|----------|-----------|
| Full | Weekly | 12 months |
| Incremental | Daily | 30 days |
| Transaction Log | Continuous | 7 days |

### Creating Backups

#### Manual Backup

```bash
# Create manual backup
php artisan backup:run

# Backup specific tenant
php artisan backup:run --tenant=tenant_id

# Download backup
php artisan backup:download
```

### Recovery Procedures

#### Database Recovery

```bash
# List available backups
php artisan backup:list

# Restore from backup
php artisan backup:restore backup_file.zip

# Restore to specific point
php artisan backup:restore --point="2024-01-15 10:00:00"
```

#### Point-in-Time Recovery

1. Identify recovery point
2. Stop all write operations
3. Restore from last full backup
4. Apply transaction logs
5. Verify data integrity
6. Resume operations

### Testing Recovery

Regular recovery testing ensures backup reliability:

- **Frequency**: Monthly
- **Process**: Restore to isolated environment
- **Validation**: Verify data completeness

---

## Troubleshooting

### Common Admin Issues

| Issue | Solution |
|-------|----------|
| User can't login | Check user status, reset password |
| Slow performance | Check server resources, optimize queries |
| Emails not sending | Verify SMTP settings, check logs |
| Analytics not updating | Check data sync jobs |

### Support Resources

- **Documentation**: See [Admin Guide](../admin/README.md)
- **API Docs**: See [API Documentation](../api/README.md)
- **Support**: admin-support@alumate.io

---

## Assessment

Take the [Admin Training Assessment](assessment.md#admin-training-assessment) to verify your knowledge.

**Next Module**: [Developer Training](developer-training.md)
