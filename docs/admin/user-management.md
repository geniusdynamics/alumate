# User Management Guide

**ABOUTME:** Comprehensive guide for managing users in the Alumate platform including role-based access control, user creation, bulk operations, and activity monitoring.

## Table of Contents

1. [Overview](#overview)
2. [User Roles and Permissions](#user-roles-and-permissions)
3. [Creating Users](#creating-users)
4. [Managing Users](#managing-users)
5. [Bulk User Operations](#bulk-user-operations)
6. [User Activity Monitoring](#user-activity-monitoring)
7. [Password Management](#password-management)
8. [User Troubleshooting](#user-troubleshooting)

---

## Overview

The Alumate platform provides comprehensive user management capabilities supporting multiple user types with role-based access control. The system supports:

- **User Authentication**: Secure login with multi-factor authentication
- **Role-Based Access**: Granular permissions based on user roles
- **Tenant Isolation**: Multi-tenant user data separation
- **Activity Tracking**: Comprehensive audit logging
- **Bulk Operations**: Efficient management of large user sets

### User Types

| Type | Description | Typical Use |
|------|-------------|-------------|
| Alumni | Graduated students | Career networking, community |
| Student | Current students | Academic resources, mentorship |
| Employer | Company representatives | Job postings, recruitment |
| Faculty | Academic staff | Student mentorship, career guidance |
| Admin | System administrators | Platform management |
| Super Admin | Platform-wide admins | System configuration |

---

## User Roles and Permissions

### Role Hierarchy

```
Super Admin
├── Institution Admin
│   ├── Content Manager
│   ├── Analytics Viewer
│   └── User Manager
├── Alumni Manager
└── Employer Manager
```

### Role Permissions Matrix

| Permission | Super Admin | Institution Admin | Content Manager | Analytics Viewer | User Manager |
|------------|-------------|-------------------|-----------------|------------------|--------------|
| View Users | ✅ | ✅ (tenant) | ❌ | ❌ | ✅ |
| Create Users | ✅ | ✅ (tenant) | ❌ | ❌ | ✅ |
| Edit Users | ✅ | ✅ (tenant) | ❌ | ❌ | ✅ |
| Delete Users | ✅ | ✅ (tenant) | ❌ | ❌ | ❌ |
| View Analytics | ✅ | ✅ (tenant) | ❌ | ✅ | ❌ |
| Manage Content | ✅ | ✅ (tenant) | ✅ | ❌ | ❌ |
| Manage Tenants | ✅ | ❌ | ❌ | ❌ | ❌ |
| System Config | ✅ | ❌ | ❌ | ❌ | ❌ |
| Security Settings | ✅ | ❌ | ❌ | ❌ | ❌ |

### Creating Custom Roles

```bash
# Create custom role
php artisan role:create \
    --name="Marketing Manager" \
    --description="Manage marketing content and campaigns"

# Assign permissions to role
php artisan role:assign-permission \
    --role="Marketing Manager" \
    --permissions="page.create,page.edit,media.upload"

# View role permissions
php artisan role:permissions --role="Marketing Manager"
```

### Permission Types

| Category | Permissions |
|----------|-------------|
| Users | user.view, user.create, user.edit, user.delete, user.manage_roles |
| Content | page.view, page.create, page.edit, page.delete, page.publish |
| Analytics | analytics.view, analytics.export, analytics.manage_reports |
| Settings | settings.view, settings.edit, settings.manage_integrations |
| Security | security.view, security.audit, security.manage_users |

---

## Creating Users

### Via Admin Interface

1. **Navigate to User Management**
   - Go to `/admin/users`
   - Click "Add New User"

2. **Fill User Details**
   ```markdown
   Required Fields:
   - Name (full name)
   - Email (unique identifier)
   - Role (select from dropdown)
   - Institution (if applicable)
   
   Optional Fields:
   - Phone number
   - Graduation year
   - Location
   - Custom attributes
   ```

3. **Set Initial Password**
   - Option 1: Auto-generate password
   - Option 2: Set temporary password
   - Option 3: Send invitation email

4. **Send Welcome Email**
   - Check "Send welcome email"
   - Customize email template (optional)

### Via Command Line

```bash
# Create basic user
php artisan user:create \
    --name="John Doe" \
    --email="john@example.com" \
    --role="alumni"

# Create institution admin
php artisan user:create \
    --name="Admin User" \
    --email="admin@institution.edu" \
    --role="institution_admin" \
    --institution="institution-id"

# Create user with custom attributes
php artisan user:create \
    --name="Jane Smith" \
    --email="jane@example.com" \
    --role="employer" \
    --custom-attributes='{"company": "Acme Corp", "title": "HR Manager"}'
```

### Via API

```bash
# Create user via API
curl -X POST "https://your-domain.com/api/users" \
    --header "Authorization: Bearer {token}" \
    --header "Content-Type: application/json" \
    --data '{
        "name": "New User",
        "email": "newuser@example.com",
        "role": "alumni",
        "institution_id": 1,
        "send_welcome": true
    }'
```

### Bulk User Creation

```bash
# Create users from CSV
php artisan user:import \
    --file="path/to/users.csv" \
    --role="alumni" \
    --institution="institution-id"

# CSV Format:
# name,email,graduation_year,location
# John Doe,john@example.com,2023,New York
# Jane Smith,jane@example.com,2022,Los Angeles
```

---

## Managing Users

### Editing Users

1. **Via Admin Interface**
   - Navigate to `/admin/users`
   - Click user row or "Edit" button
   - Modify fields as needed
   - Save changes

2. **Via Command Line**

```bash
# Update user details
php artisan user:update \
    --user="user-id" \
    --name="New Name" \
    --email="newemail@example.com"

# Update user role
php artisan user:assign-role \
    --user="user-id" \
    --role="content_editor"

# Remove user role
php artisan user:remove-role \
    --user="user-id" \
    --role="content_editor"
```

### Deactivating Users

```bash
# Deactivate user
php artisan user:deactivate \
    --user="user-id" \
    --reason="Account suspension" \
    --duration="7 days"

# Reactivate user
php artisan user:reactivate --user="user-id"

# Permanently delete user
php artisan user:delete \
    --user="user-id" \
    --confirm \
    --anonymize-data
```

### User Profile Management

```bash
# View user profile
php artisan user:show --user="user-id"

# Update user profile
php artisan user:profile:update \
    --user="user-id" \
    --bio="New bio" \
    --skills="PHP,Laravel,Vue.js"

# Add user metadata
php artisan user:metadata:set \
    --user="user-id" \
    --key="preferences" \
    --value='{"notifications": true, "newsletter": false}'
```

---

## Bulk User Operations

### Bulk Role Assignment

```bash
# Assign role to multiple users
php artisan user:bulk-assign \
    --role="alumni" \
    --filter="graduation_year:2023"

# Remove role from multiple users
php artisan user:bulk-remove-role \
    --role="inactive" \
    --filter="last_login:<2024-01-01"
```

### Bulk Status Update

```bash
# Deactivate multiple users
php artisan user:bulk-deactivate \
    --filter="inactive_days=365" \
    --reason="Inactive account cleanup"

# Activate multiple users
php artisan user:bulk-activate \
    --filter="pending_activation=true"
```

### Bulk Data Export

```bash
# Export user data
php artisan user:export \
    --format=csv \
    --filter="institution_id=1" \
    --fields="name,email,role,created_at" \
    --output="path/to/export.csv"
```

### Bulk Import

```bash
# Import users from file
php artisan user:import \
    --file="path/to/users.xlsx" \
    --role="alumni" \
    --update-existing=true \
    --send-welcome-emails=true

# Validate import file
php artisan user:import:validate \
    --file="path/to/users.csv"
```

### Bulk Cleanup Operations

```bash
# Clean up inactive users
php artisan user:cleanup \
    --inactive-days=365 \
    --action=deactivate \
    --notify-before=30

# Remove duplicate users
php artisan user:cleanup:duplicates \
    --merge=true \
    --keep="oldest"
```

---

## User Activity Monitoring

### Viewing Activity Logs

1. **Navigate to Activity Logs**
   - Go to `/admin/users/{user-id}/activity`
   - View chronological activity list

2. **Activity Types**

| Activity Type | Description |
|---------------|-------------|
| login | User logged in |
| logout | User logged out |
| profile_update | Profile information updated |
| password_change | Password was changed |
| role_change | User role was modified |
| content_create | User created content |
| content_delete | User deleted content |
| export_data | User exported data |

### Activity Filters

```bash
# Filter by user
php artisan user:activity \
    --user="user-id" \
    --days=30

# Filter by activity type
php artisan user:activity \
    --type=login \
    --days=7

# Filter by IP address
php artisan user:activity \
    --ip="192.168.1.100" \
    --days=1
```

### Exporting Activity Reports

```bash
# Export activity logs
php artisan logs:export \
    --format=csv \
    --start-date="2024-01-01" \
    --end-date="2024-01-31" \
    --output="activity_report.csv"

# Generate activity summary
php artisan user:activity:summary \
    --days=30 \
    --format=pdf
```

### Real-time Activity Monitoring

Access real-time activity at `/admin/monitoring/activity`:

```
┌─────────────────────────────────────────────────────────────────┐
│ Live Activity Monitor                                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Time          User           Action           IP Address       │
│ ─────────────────────────────────────────────────────────────  │
│ 14:32:15     John Doe       Login           192.168.1.100    │
│ 14:32:08     Jane Smith     Profile Update   10.0.0.50       │
│ 14:31:55     Bob Wilson     Content Create   192.168.1.105    │
│                                                                 │
│ Total Events: 1,247    Active Users: 156    New Today: 23       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Password Management

### Password Policy Configuration

```php
// config/auth.php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_resets',
        'expire' => 60, // minutes
        'throttle' => 60, // seconds
        'min_length' => 12,
        'require_special' => true,
        'require_number' => true,
        'require_uppercase' => true,
    ],
],
```

### Password Reset Operations

```bash
# Force password reset for user
php artisan user:force-password-reset \
    --user="user-id"

# Force reset for multiple users
php artisan user:force-password-reset \
    --filter="password_never_changed=true"

# Send password reset email
php artisan user:send-password-reset \
    --email="user@example.com"

# Reset password manually (admin)
php artisan user:reset-password \
    --user="user-id" \
    --new-password="TempPass123!"
```

### Password Expiration

```bash
# Configure password expiration
php artisan security:password-policy \
    --expire-days=90 \
    --warn-days=14

# View users with expiring passwords
php artisan user:password:expiring \
    --days=14

# Expire all passwords (force reset)
php artisan user:password:expire-all
```

---

## User Troubleshooting

### Common Issues

#### 1. Login Failures

**Symptom**: User cannot log in

**Possible Causes**:
- Incorrect credentials
- Account deactivated
- IP restrictions
- 2FA issues

**Solution**:
```bash
# Check account status
php artisan user:status --email="user@example.com"

# Reset login attempts
php artisan user:reset-login-attempts --email="user@example.com"

# Unlock account
php artisan user:unlock --email="user@example.com"

# Reset 2FA
php artisan user:reset-2fa --email="user@example.com"
```

#### 2. Account Activation Issues

**Symptom**: New user cannot activate account

**Solution**:
```bash
# Resend activation email
php artisan user:resend-activation \
    --email="user@example.com"

# Manual activation
php artisan user:activate --email="user@example.com"

# Check activation token
php artisan user:activation-token \
    --email="user@example.com"
```

#### 3. Permission Denied Errors

**Symptom**: User cannot access expected features

**Solution**:
```bash
# Check user roles
php artisan user:roles --email="user@example.com"

# Verify role permissions
php artisan role:permissions --role="role-name"

# Add missing permission
php artisan user:add-permission \
    --user="user-id" \
    --permission="analytics.view"
```

#### 4. Duplicate User Accounts

**Symptom**: Same email appears multiple times

**Solution**:
```bash
# Find duplicates
php artisan user:find-duplicates \
    --by="email"

# Merge duplicate accounts
php artisan user:merge-duplicates \
    --email="user@example.com" \
    --keep="primary" \
    --merge-data=true
```

### Diagnostic Commands

```bash
# Run user diagnostics
php artisan user:diagnostic --user="user-id"

# Verify user data integrity
php artisan user:integrity:check

# Test authentication
php artisan user:test-auth --email="user@example.com"

# Check session status
php artisan user:sessions --user="user-id"
```

### User Data Issues

```bash
# Fix user data inconsistencies
php artisan user:data:fix

# Rebuild user search index
php artisan user:search:rebuild

# Sync user data across tenants
php artisan user:sync:tenants
```

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Security Documentation](../security/)
- [API Documentation](../api/)
- [Role Permission Seeder](../database/seeders/RolePermissionSeeder.php)

### Command Reference

| Command | Description |
|---------|-------------|
| `php artisan user:create` | Create new user |
| `php artisan user:update` | Update user details |
| `php artisan user:deactivate` | Deactivate user |
| `php artisan user:activity` | View user activity |
| `php artisan user:import` | Import users from file |
| `php artisan user:export` | Export user data |
| `php artisan role:create` | Create custom role |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
