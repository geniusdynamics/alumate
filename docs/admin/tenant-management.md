# Tenant Management Guide

**ABOUTME:** Comprehensive guide for managing tenants in the Alumate multi-tenant platform including tenant creation, configuration, resource allocation, and data isolation.

## Table of Contents

1. [Overview](#overview)
2. [Creating Tenants](#creating-tenants)
3. [Tenant Configuration](#tenant-configuration)
4. [Resource Allocation](#resource-allocation)
5. [Tenant Data Isolation](#tenant-data-isolation)
6. [Tenant Deactivation](#tenant-deactivation)
7. [Tenant Monitoring](#tenant-monitoring)
8. [Tenant Troubleshooting](#tenant-troubleshooting)

---

## Overview

The Alumate platform implements a multi-tenant architecture where each institution (tenant) operates in an isolated environment. Key features include:

- **Schema-Based Isolation**: Each tenant has its own database schema
- **Resource Quotas**: Configurable limits per tenant
- **Custom Branding**: Tenant-specific themes and configurations
- **Independent Settings**: Tenant-level feature flags and settings
- **Data Separation**: Strict data isolation between tenants

### Tenant Types

| Type | Description | Use Case |
|------|-------------|----------|
| Educational Institution | Universities, colleges | Alumni management |
| Corporate | Companies | Employee networking |
| Association | Professional groups | Member engagement |
| High School | Secondary schools | Graduate tracking |

---

## Creating Tenants

### Via Admin Interface

1. **Navigate to Tenant Management**
   - Go to `/admin/tenants`
   - Click "Add New Tenant"

2. **Fill Tenant Details**
   ```markdown
   Required Fields:
   - Institution Name
   - Domain/Subdomain
   - Admin Contact Email
   - Subscription Plan
   
   Optional Fields:
   - Logo
   - Theme
   - Custom settings
   ```

3. **Configure Initial Settings**
   - Select subscription plan
   - Set user quota
   - Configure features
   - Set up integrations

4. **Create Admin Account**
   - Auto-generate admin user
   - Send invitation email
   - Set temporary credentials

### Via Command Line

```bash
# Create basic tenant
php artisan tenant:create \
    --name="University Name" \
    --domain="university.example.com" \
    --admin-email="admin@university.edu"

# Create tenant with custom configuration
php artisan tenant:create \
    --name="University Name" \
    --domain="university.example.com" \
    --admin-email="admin@university.edu" \
    --plan="enterprise" \
    --max-users=10000 \
    --storage-limit=100GB \
    --theme="corporate"

# Create tenant with custom settings
php artisan tenant:create \
    --name="University Name" \
    --domain="university.example.com" \
    --config='{"features": {"analytics": true, "jobs": true}, "branding": {"primary_color": "#ff0000"}}'
```

### Via API

```bash
# Create tenant via API
curl -X POST "https://your-domain.com/api/tenants" \
    --header "Authorization: Bearer {token}" \
    --header "Content-Type: application/json" \
    --data '{
        "name": "New University",
        "domain": "newuniversity.example.com",
        "admin_email": "admin@newuniversity.edu",
        "plan": "professional",
        "settings": {
            "features": {
                "analytics": true,
                "mentorship": true
            }
        }
    }'
```

### Tenant Setup Checklist

```markdown
After tenant creation, complete:

1. [ ] Verify domain DNS configuration
2. [ ] Configure SSL certificate
3. [ ] Set up email domain
4. [ ] Configure integrations (if applicable)
5. [ ] Import initial data
6. [ ] Set up admin user account
7. [ ] Configure branding and theme
8. [ ] Enable required features
9. [ ] Set up billing (if applicable)
```

---

## Tenant Configuration

### Configuration Categories

| Category | Description |
|----------|-------------|
| General | Name, domain, contact information |
| Features | Enable/disable platform features |
| Branding | Colors, logos, custom CSS |
| Integrations | Third-party service connections |
| Users | User quotas, registration settings |
| Security | Authentication, password policies |
| Analytics | Analytics configuration |

### General Settings

```bash
# Update tenant name
php artisan tenant:config \
    --tenant="tenant-id" \
    --set="name=New Institution Name"

# Update domain
php artisan tenant:config \
    --tenant="tenant-id" \
    --set="domain=subdomain.example.com"

# Update contact email
php artisan tenant:config \
    --tenant="tenant-id" \
    --set="contact_email=newemail@example.com"

# View configuration
php artisan tenant:config \
    --tenant="tenant-id" \
    --show
```

### Feature Flags

```bash
# Enable feature
php artisan tenant:feature \
    --tenant="tenant-id" \
    --enable="analytics,jobs,mentorship"

# Disable feature
php artisan tenant:feature \
    --tenant="tenant-id" \
    --disable="premium_features"

# List enabled features
php artisan tenant:feature \
    --tenant="tenant-id" \
    --list
```

### Branding Configuration

```bash
# Set theme
php artisan tenant:branding \
    --tenant="tenant-id" \
    --theme="corporate"

# Set primary color
php artisan tenant:branding \
    --tenant="tenant-id" \
    --primary-color="#0066cc"

# Upload logo
php artisan tenant:branding \
    --tenant="tenant-id" \
    --logo="path/to/logo.png"

# Configure custom CSS
php artisan tenant:branding \
    --tenant="tenant-id" \
    --custom-css="path/to/styles.css"
```

### Integration Settings

```bash
# Configure email integration
php artisan tenant:integration \
    --tenant="tenant-id" \
    --type="email" \
    --provider="sendgrid" \
    --api-key="xxx"

# Configure CRM integration
php artisan tenant:integration \
    --tenant="tenant-id" \
    --type="crm" \
    --provider="hubspot" \
    --api-key="xxx"

# List integrations
php artisan tenant:integration \
    --tenant="tenant-id" \
    --list
```

### Security Settings

```bash
# Configure password policy
php artisan tenant:security \
    --tenant="tenant-id" \
    --password-policy="standard"

# Configure 2FA requirement
php artisan tenant:security \
    --tenant="tenant-id" \
    --require-2fa="admin"

# Set session timeout
php artisan tenant:security \
    --tenant="tenant-id" \
    --session-timeout=3600
```

---

## Resource Allocation

### Resource Types

| Resource | Description | Default Limit |
|----------|-------------|---------------|
| Users | Maximum number of users | 5,000 |
| Storage | File storage limit | 10 GB |
| API Calls | Monthly API quota | 100,000 |
| Email | Monthly email limit | 50,000 |
| Reports | Report generation quota | 1,000 |

### Managing Resources

```bash
# View resource usage
php artisan tenant:usage \
    --tenant="tenant-id"

# Allocate resources
php artisan tenant:allocate \
    --tenant="tenant-id" \
    --cpu=2 \
    --memory=4GB \
    --storage=50GB \
    --users=10000

# Set resource limits
php artisan tenant:limits \
    --tenant="tenant-id" \
    --max-users=10000 \
    --max-storage=100GB \
    --max-api-calls=1000000

# View resource history
php artisan tenant:resource-history \
    --tenant="tenant-id"
```

### Subscription Plans

| Plan | Users | Storage | API Calls | Price |
|------|-------|---------|-----------|-------|
| Free | 1,000 | 5 GB | 10,000 | $0 |
| Basic | 5,000 | 25 GB | 100,000 | $99/mo |
| Professional | 25,000 | 100 GB | 500,000 | $299/mo |
| Enterprise | Unlimited | Unlimited | Unlimited | Custom |

```bash
# Change subscription plan
php artisan tenant:plan \
    --tenant="tenant-id" \
    --plan="professional"

# Upgrade plan with prorated billing
php artisan tenant:plan:upgrade \
    --tenant="tenant-id" \
    --plan="enterprise" \
    --prorate=true
```

### Resource Monitoring

```bash
# Check resource usage
php artisan tenant:monitor \
    --tenant="tenant-id" \
    --resources=cpu,memory,storage,api

# Set up resource alerts
php artisan tenant:alerts \
    --tenant="tenant-id" \
    --storage-threshold=80 \
    --api-threshold=90

# View resource trends
php artisan tenant:trends \
    --tenant="tenant-id" \
    --period=30days
```

---

## Tenant Data Isolation

### Isolation Mechanisms

1. **Schema-Based Isolation**
   - Each tenant has its own PostgreSQL schema
   - Separate database tables per tenant
   - Automatic tenant scope in queries

2. **Cache Isolation**
   - Separate cache keys per tenant
   - Tenant-prefixed cache tags
   - Isolated cache storage

3. **Queue Isolation**
   - Tenant-specific queues
   - Isolated job processing
   - Separate failed job queues

### Verifying Isolation

```bash
# Check tenant data boundaries
php artisan tenant:verify-isolation \
    --tenant="tenant-id"

# Run tenant isolation tests
php artisan test --filter=TenantIsolationTest

# Check cross-tenant access
php artisan tenant:check-access \
    --tenant="tenant-id"
```

### Cross-Tenant Operations

For support and data migration purposes:

```bash
# Grant temporary cross-tenant access (for support)
php artisan tenant:grant-access \
    --source="tenant-a" \
    --target="tenant-b" \
    --duration="1h" \
    --scope="read"

# Revoke cross-tenant access
php artisan tenant:revoke-access \
    --source="tenant-a" \
    --target="tenant-b"

# View access grants
php artisan tenant:access-list \
    --tenant="tenant-id"
```

### Data Export for Tenant

```bash
# Export all tenant data
php artisan tenant:export \
    --tenant="tenant-id" \
    --format=sql \
    --output="backup.sql"

# Export specific tables
php artisan tenant:export \
    --tenant="tenant-id" \
    --tables="users,posts,comments" \
    --format=csv
```

---

## Tenant Deactivation

### Deactivation Process

```bash
# Schedule tenant deactivation
php artisan tenant:deactivate \
    --tenant="tenant-id" \
    --reason="Subscription ended" \
    --grace-period=30days

# Immediate deactivation
php artisan tenant:deactivate \
    --tenant="tenant-id" \
    --immediate

# View deactivation status
php artisan tenant:status \
    --tenant="tenant-id"
```

### Deactivation Effects

| Effect | Description |
|--------|-------------|
| User Access | All users lose access |
| API Access | API calls return 403 |
| Data Preservation | Data retained during grace period |
| Scheduled Tasks | All scheduled tasks paused |
| Integrations | Integration sync paused |

### Reactivation

```bash
# Reactivate tenant
php artisan tenant:reactivate \
    --tenant="tenant-id" \
    --reason="Subscription renewed"

# Restore from suspension
php artisan tenant:restore \
    --tenant="tenant-id"
```

### Permanent Deletion

```bash
# Schedule permanent deletion
php artisan tenant:delete \
    --tenant="tenant-id" \
    --reason="Institution closed" \
    --retention-period=90days

# Immediate deletion (warning!)
php artisan tenant:delete \
    --tenant="tenant-id" \
    --immediate \
    --confirm

# View deletion queue
php artisan tenant:delete-queue
```

---

## Tenant Monitoring

### Monitoring Dashboard

Access at `/admin/tenants/{tenant-id}/monitoring`:

```
┌─────────────────────────────────────────────────────────────────┐
│ Tenant Monitoring: University Name                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Status: Active                    Plan: Enterprise             │
│  Users: 8,234 / 10,000             Storage: 45.2 GB / 100 GB    │
│                                                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐│
│  │ Active Users    │  │ API Calls       │  │ Storage Used    ││
│  │ 1,234          │  │ 45,678 / 500K   │  │ 45.2 GB         ││
│  │ ↑ 12%          │  │ ↑ 8%            │  │ ↑ 2%            ││
│  └─────────────────┘  └─────────────────┘  └─────────────────┘│
│                                                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐│
│  │ Disk I/O        │  │ Email Sent      │  │ Reports Gen     ││
│  │ 245 MB/s        │  │ 12,345 / 50K    │  │ 456 / 1,000     ││
│  │ Normal          │  │ ↑ 5%            │  │ ↓ 10%           ││
│  └─────────────────┘  └─────────────────┘  └─────────────────┘│
│                                                                 │
│  Recent Activity:                                               │
│  • New user registration (john@uni.edu) - 2 min ago            │
│  • Support ticket created - 15 min ago                         │
│  • API quota warning triggered - 1 hour ago                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Alert Configuration

```bash
# Set up tenant-specific alerts
php artisan tenant:alerts \
    --tenant="tenant-id" \
    --storage-warning=70 \
    --storage-critical=90 \
    --api-warning=80 \
    --api-critical=95 \
    --user-warning=90

# View active alerts
php artisan tenant:alerts:active \
    --tenant="tenant-id"
```

### Performance Monitoring

```bash
# View tenant performance metrics
php artisan tenant:performance \
    --tenant="tenant-id" \
    --period=7days

# Check response times
php artisan tenant:performance:response \
    --tenant="tenant-id"

# Check error rates
php artisan tenant:performance:errors \
    --tenant="tenant-id"
```

---

## Tenant Troubleshooting

### Common Issues

#### 1. Domain Not Resolving

**Symptom**: Tenant domain shows error or redirect

**Solution**:
```bash
# Check DNS configuration
php artisan tenant:dns-check \
    --tenant="tenant-id"

# Verify SSL certificate
php artisan tenant:ssl-check \
    --tenant="tenant-id"

# Test domain access
curl -I https://tenant-domain.example.com
```

#### 2. User Quota Exceeded

**Symptom**: Cannot add new users

**Solution**:
```bash
# Check current usage
php artisan tenant:usage \
    --tenant="tenant-id"

# Increase user quota
php artisan tenant:limits \
    --tenant="tenant-id" \
    --max-users=15000

# Upgrade subscription
php artisan tenant:plan:upgrade \
    --tenant="tenant-id" \
    --plan="enterprise"
```

#### 3. Storage Full

**Symptom**: File uploads failing

**Solution**:
```bash
# Check storage usage
php artisan tenant:storage \
    --tenant="tenant-id" \
    --breakdown

# Clean up unused files
php artisan tenant:storage:cleanup \
    --tenant="tenant-id" \
    --older-than=90days

# Increase storage limit
php artisan tenant:allocate \
    --tenant="tenant-id" \
    --storage=200GB
```

#### 4. API Rate Limiting

**Symptom**: API requests returning 429

**Solution**:
```bash
# Check API usage
php artisan tenant:api-usage \
    --tenant="tenant-id"

# View rate limit status
php artisan tenant:api-limit \
    --tenant="tenant-id"

# Increase API quota
php artisan tenant:limits \
    --tenant="tenant-id" \
    --max-api-calls=1000000
```

### Diagnostic Commands

```bash
# Full tenant diagnostic
php artisan tenant:diagnostic \
    --tenant="tenant-id"

# Check database connection
php artisan tenant:db-check \
    --tenant="tenant-id"

# Test cache functionality
php artisan tenant:cache-check \
    --tenant="tenant-id"

# Verify queue functionality
php artisan tenant:queue-check \
    --tenant="tenant-id"
```

### Support Procedures

If issues cannot be resolved:

1. **Gather Information**
   ```bash
   php artisan tenant:diagnostic \
       --tenant="tenant-id" \
       --output=json \
       --file=tenant_diagnostic.json
   ```

2. **Check Monitoring History**

3. **Review Recent Changes**

4. **Contact Support** with:
   - Tenant ID
   - Error messages
   - Diagnostic output
   - Steps to reproduce

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Schema Tenancy Documentation](../SCHEMA_TENANCY_ROLLBACK_GUIDE.md)
- [Database Configuration](../config/database.php)
- [Multi-Tenancy Setup](../tenancy/)

### Command Reference

| Command | Description |
|---------|-------------|
| `php artisan tenant:create` | Create new tenant |
| `php artisan tenant:config` | Configure tenant settings |
| `php artisan tenant:allocate` | Allocate resources |
| `php artisan tenant:deactivate` | Deactivate tenant |
| `php artisan tenant:monitor` | Monitor tenant |
| `php artisan tenant:export` | Export tenant data |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
