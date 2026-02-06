# Security Administration Guide

**ABOUTME:** Comprehensive guide for administering security in the Alumate platform including authentication settings, access control, security auditing, compliance management, and incident response.

## Table of Contents

1. [Overview](#overview)
2. [Authentication Settings](#authentication-settings)
3. [Access Control](#access-control)
4. [Security Auditing](#security-auditing)
5. [Compliance Management](#compliance-management)
6. [Incident Response](#incident-response)
7. [Security Troubleshooting](#security-troubleshooting)

---

## Overview

The Alumate security system provides comprehensive protection:

- **Authentication**: Secure login with multi-factor authentication
- **Authorization**: Role-based and permission-based access control
- **Data Protection**: Encryption at rest and in transit
- **Audit Logging**: Comprehensive activity tracking
- **Compliance**: GDPR and CCPA compliance features
- **Threat Detection**: Intrusion detection and prevention

### Security Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                         Security Layers                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐   │
│  │  Network    │  │ Application │  │      Database        │   │
│  │  Security   │  │  Security   │  │      Security       │   │
│  │             │  │             │  │                     │   │
│  │ - Firewall  │  │ - Auth      │  │ - Encryption        │   │
│  │ - WAF       │  │ - Sessions  │  │ - Access Control    │   │
│  │ - DDoS      │  │ - CSRF      │  │ - Audit Logging     │   │
│  └─────────────┘  └─────────────┘  └─────────────────────┘   │
│                                                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐   │
│  │  Data       │  │  Compliance │  │      Monitoring      │   │
│  │  Security   │  │             │  │                      │   │
│  │             │  │ - GDPR      │  │ - Threat Detection   │   │
│  │ - Encryption│  │ - CCPA      │  │ - Incident Response  │   │
│  │ - Masking   │  │ - Audit     │  │ - Alerting           │   │
│  └─────────────┘  └─────────────┘  └─────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Authentication Settings

### Password Policy

```bash
# Configure password policy
php artisan security:password-policy \
    --min-length=12 \
    --require-special=true \
    --require-number=true \
    --require-uppercase=true \
    --expire-days=90 \
    --prevent-reuse=12
```

### Password Policy Options

| Setting | Description | Default |
|---------|-------------|---------|
| min_length | Minimum password length | 12 |
| require_special | Require special character | true |
| require_number | Require number | true |
| require_uppercase | Require uppercase | true |
| expire_days | Password expiration | 90 |
| prevent_reuse | Previous passwords to remember | 12 |
| max_attempts | Maximum login attempts | 5 |
| lockout_duration | Account lockout duration | 30 minutes |

### Two-Factor Authentication

```bash
# Enable 2FA for all users
php artisan security:2fa:enable --scope=all

# Enable 2FA for admins only
php artisan security:2fa:enable --scope=admin

# Disable 2FA for specific user
php artisan security:2fa:disable --user=user-id

# View 2FA status
php artisan security:2fa:status --user=user-id

# Reset 2FA for user
php artisan security:2fa:reset --user=user-id
```

### 2FA Methods

| Method | Description | Security Level |
|--------|-------------|----------------|
| TOTP | Time-based OTP (Google Authenticator) | High |
| SMS | SMS-based OTP | Medium |
| Email | Email-based OTP | Medium |
| Hardware | Hardware token (YubiKey) | Very High |

### Session Management

```php
// config/session.php
return [
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'regenerate' => true,
    'regenerate_time' => 30,
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
];
```

```bash
# Configure session settings
php artisan security:sessions \
    --lifetime=120 \
    --regenerate=true \
    --regenerate-time=30

# Invalidate all sessions for user
php artisan security:sessions:invalidate --user=user-id

# View active sessions
php artisan security:sessions:list --user=user-id
```

---

## Access Control

### Role-Based Access Control (RBAC)

```bash
# Create custom role
php artisan role:create \
    --name="Marketing Manager" \
    --description="Manage marketing content"

# Assign permissions to role
php artisan role:assign-permission \
    --role="Marketing Manager" \
    --permissions="page.create,page.edit,media.upload"

# View role permissions
php artisan role:permissions --role="Marketing Manager"

# Delete role
php artisan role:delete --role="Marketing Manager"
```

### Permission Types

| Category | Permissions |
|----------|-------------|
| Users | user.view, user.create, user.edit, user.delete |
| Content | page.view, page.create, page.edit, page.publish |
| Analytics | analytics.view, analytics.export |
| Settings | settings.view, settings.edit |
| Security | security.audit, security.settings |

### IP Whitelisting

```bash
# Add IP to whitelist
php artisan security:whitelist-ip \
    --ip="192.168.1.100" \
    --description="Admin office"

# Remove IP from whitelist
php artisan security:unwhitelist-ip --ip="192.168.1.100"

# View whitelist
php artisan security:whitelist --list

# Enable IP restriction
php artisan security:ip-restriction --enable
```

### API Rate Limiting

```php
// config/api.php
return [
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 60,
        'decay_minutes' => 1,
        'prefix' => 'api_rate_limit',
    ],
];
```

```bash
# Configure rate limiting
php artisan security:rate-limit \
    --max=60 \
    --decay=1

# View rate limit status
php artisan security:rate-limit:status

# Reset rate limits for IP
php artisan security:rate-limit:reset --ip=192.168.1.100
```

---

## Security Auditing

### Audit Log Configuration

```php
// config/audit.php
return [
    'enabled' => true,
    'storage' => 'database',
    'retention' => 365, // days
    'events' => [
        'auth.login',
        'auth.logout',
        'auth.failed',
        'user.created',
        'user.updated',
        'user.deleted',
        'role.assigned',
        'role.revoked',
        'settings.changed',
        'data.exported',
        'data.deleted',
    ],
];
```

### Viewing Audit Logs

```bash
# View recent audit logs
php artisan audit:view --lines=100

# Filter by user
php artisan audit:view --user=user-id

# Filter by event type
php artisan audit:view --event=auth.login

# Filter by date range
php artisan audit:view \
    --start="2024-01-01" \
    --end="2024-01-31"

# Search audit logs
php artisan audit:search --query="password change"
```

### Security Scans

```bash
# Run security scan
php artisan security:scan

# Check for vulnerable dependencies
php artisan security:check-dependencies

# Audit user permissions
php artisan security:audit-permissions

# Check file permissions
php artisan security:check-files

# Scan for malware
php artisan security:malware-scan
```

### Compliance Reports

```bash
# Generate compliance report
php artisan security:compliance-report \
    --standard=gdpr \
    --format=pdf

# Generate access report
php artisan security:access-report \
    --start="2024-01-01" \
    --end="2024-01-31"

# Generate data retention report
php artisan security:retention-report
```

---

## Compliance Management

### GDPR Compliance

```bash
# Export user data (Data Portability)
php artisan gdpr:export \
    --user=user-id \
    --format=json

# Delete user data (Right to Erasure)
php artisan gdpr:erase \
    --user=user-id \
    --confirm

# Anonymize user data
php artisan gdpr:anonymize \
    --user=user-id

# View consent records
php artisan gdpr:consent-view \
    --user=user-id

# Generate GDPR compliance report
php artisan gdpr:compliance-report
```

### Consent Management

```bash
# View consent status
php artisan consent:status --user=user-id

# Record consent
php artisan consent:record \
    --user=user-id \
    --type=analytics \
    --granted=true

# Withdraw consent
php artisan consent:withdraw \
    --user=user-id \
    --type=marketing

# Generate consent report
php artisan consent:report --format=csv
```

### Data Retention

```php
// config/data-retention.php
return [
    'users' => [
        'active' => null, // Keep forever
        'deleted' => 365, // 1 year after deletion
        'logs' => 90,
    ],
    'analytics' => [
        'events' => 365,
        'aggregated' => null,
    ],
    'sessions' => [
        'expired' => 30,
    ],
    'backups' => [
        'daily' => 7,
        'weekly' => 4,
        'monthly' => 12,
        'yearly' => 7,
    ],
];
```

```bash
# Run data cleanup
php artisan data:cleanup \
    --older-than=90days \
    --type=logs

# View data retention status
php artisan data:retention-status

# Enforce retention policy
php artisan data:enforce-retention
```

---

## Incident Response

### Incident Types

| Severity | Description | Response Time |
|----------|-------------|---------------|
| Critical | Data breach, system compromise | Immediate |
| High | Security vulnerability, attack detected | 1 hour |
| Medium | Policy violation, suspicious activity | 4 hours |
| Low | Minor security issue | 24 hours |

### Response Procedures

#### 1. Lock User Account

```bash
# Lock user account
php artisan user:lock \
    --user=user-id \
    --reason="Suspicious activity"

# View lock status
php: artisan user:lockstatus --user=user-id
```

#### 2. Investigate Incident

```bash
# Generate security report
php artisan security:incident-report \
    --incident-id=xxx \
    --format=pdf

# View user activity
php artisan user:activity \
    --user=user-id \
    --days=7

# Check access logs
php artisan audit:view \
    --user=user-id \
    --event=auth.*

# Check IP geolocation
php artisan security:ip-lookup --ip=192.168.1.100
```

#### 3. Contain Threat

```bash
# Disable compromised account
php artisan user:disable --user=user-id

# Revoke all sessions
php artisan security:sessions:invalidate --user=user-id

# Reset passwords
php artisan user:reset-password --user=user-id

# Block IP address
php artisan security:block-ip --ip=192.168.1.100
```

#### 4. Eradicate Threat

```bash
# Remove malicious content
php artisan security:remove-malicious \
    --scan-id=xxx

# Reset compromised keys
php artisan security:reset-keys

# Update security rules
php artisan security:update-rules
```

#### 5. Recover Systems

```bash
# Restore from clean backup
php artisan backup:restore \
    --backup=pre-incident \
    --scope=affected-data

# Verify system integrity
php artisan security:verify

# Re-enable services
php artisan security:restore-services
```

#### 6. Document Incident

```bash
# Create incident report
php artisan security:incident-create \
    --type="unauthorized_access" \
    --severity=high \
    --affected-users=5 \
    --description="Investigation findings..."

# Update incident status
php artisan security:incident-update \
    --id=xxx \
    --status=resolved \
    --resolution="User account compromised, password reset completed"
```

### Security Alerts

```bash
# Configure security alerts
php artisan security:alerts \
    --email=admin@example.com \
    --slack=#security-alerts \
    --critical=true \
    --high=true \
    --medium=false

# View active security alerts
php artisan security:alerts:list --active

# Acknowledge alert
php artisan security:alerts:acknowledge --id=xxx
```

---

## Security Troubleshooting

### Common Issues

#### 1. Login Failures

**Symptom**: Users cannot log in

**Solution**:
```bash
# Check authentication service
php artisan auth:check

# Verify database connection
php artisan db:connect

# Check session configuration
php artisan session:check

# Review auth logs
tail -100 storage/logs/auth.log
```

#### 2. Permission Denied

**Symptom**: Users get permission errors

**Solution**:
```bash
# Verify user roles
php artisan user:roles --user=user-id

# Check role permissions
php artisan role:permissions --role=role-name

# Clear permission cache
php artisan permission:clear-cache

# Rebuild permission cache
php artisan permission:rebuild
```

#### 3. 2FA Issues

**Symptom**: 2FA codes not working

**Solution**:
```bash
# Check 2FA status
php artisan security:2fa:status --user=user-id

# Sync time (for TOTP)
php artisan security:2fa:sync-time

# Reset 2FA
php artisan security:2fa:reset --user=user-id

# Verify TOTP configuration
php artisan security:2fa:verify-config
```

#### 4. Session Issues

**Symptom**: Sessions not persisting

**Solution**:
```bash
# Check session driver
php artisan session:driver

# Verify session configuration
php artisan session:check-config

# Clear session cache
php artisan session:clear

# Test session functionality
php artisan session:test
```

### Diagnostic Commands

```bash
# Run full security diagnostic
php artisan security:diagnostic

# Check security status
php artisan security:status

# Verify SSL certificate
php artisan security:ssl-check

# Test firewall rules
php artisan security:firewall-test

# Check for vulnerabilities
php artisan security:vuln-scan
```

### Support Procedures

1. **Gather Information**
   ```bash
   php artisan security:diagnostic --output=json > security_issue.json
   ```

2. **Review Security Logs**
   ```bash
   tail -500 storage/logs/security.log
   ```

3. **Check Active Incidents**
   ```bash
   php artisan security:incidents:active
   ```

4. **Contact Security Team**
   - Provide diagnostic output
   - Describe issue
   - Include timeline
   - List affected users

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Security Documentation](../security/)
- [Privacy Policy](../privacy.md)
- [GDPR Compliance Guide](../security/gdpr.md)

### Security Checklist

- [ ] Password policy enabled
- [ ] 2FA for admins
- [ ] SSL/TLS configured
- [ ] Firewall enabled
- [ ] Audit logging enabled
- [ ] Backups tested
- [ ] Incident response plan ready
- [ ] Security scans scheduled

### Command Reference

| Command | Description |
|---------|-------------|
| `php artisan security:scan` | Run security scan |
| `php artisan security:password-policy` | Configure password policy |
| `php artisan security:2fa:enable` | Enable 2FA |
| `php artisan audit:view` | View audit logs |
| `php artisan gdpr:export` | Export user data |
| `php artisan security:incident-create` | Create incident report |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
