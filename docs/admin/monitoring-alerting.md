# Monitoring and Alerting Guide

**ABOUTME:** Comprehensive guide for monitoring the Alumate platform including health checks, alert configuration, notification channels, and log management.

## Table of Contents

1. [Overview](#overview)
2. [Health Checks](#health-checks)
3. [Alert Rules](#alert-rules)
4. [Notification Channels](#notification-channels)
5. [Performance Monitoring](#performance-monitoring)
6. [Log Management](#log-management)
7. [Monitoring Troubleshooting](#monitoring-troubleshooting)

---

## Overview

The Alumate monitoring system provides comprehensive visibility into:

- **Application Health**: Real-time status of all components
- **Performance Metrics**: Response times, resource utilization
- **Business Metrics**: User engagement, tenant activity
- **Security Events**: Authentication attempts, access patterns
- **Infrastructure**: Database, cache, queue, storage

### Monitoring Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                      Monitoring Stack                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐  │
│  │ Application │    │  Prometheus │    │      Grafana        │  │
│  │  Metrics    │───▶│   Server   │───▶│     Dashboards      │  │
│  └─────────────┘    └─────────────┘    └─────────────────────┘  │
│         │                   │                                      │
│         │                   ▼                                      │
│  ┌─────────────┐    ┌─────────────┐                               │
│  │  Laravel    │    │ Alertmanager│                               │
│  │  Horizon    │───▶│  (Alerts)  │                               │
│  └─────────────┘    └─────────────┘                               │
│                           │                                        │
│         ┌─────────────────┼─────────────────┐                    │
│         ▼                 ▼                 ▼                    │
│  ┌───────────┐     ┌───────────┐     ┌───────────┐              │
│  │   Email   │     │   Slack   │     │ PagerDuty │              │
│  └───────────┘     └───────────┘     └───────────┘              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Health Checks

### Health Check Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Basic health check (critical components only) |
| `/health/detailed` | GET | Full health check with all components |
| `/ready` | GET | Kubernetes readiness probe |
| `/live` | GET | Kubernetes liveness probe |

### Health Check Response

```json
{
    "status": "healthy",
    "timestamp": "2024-09-01T12:00:00Z",
    "version": "3.0.0",
    "checks": {
        "application": {
            "status": "healthy",
            "php_version": "8.3.0",
            "extensions": "all present"
        },
        "database": {
            "status": "healthy",
            "connection": true,
            "response_time_ms": 5
        },
        "redis": {
            "status": "healthy",
            "connection": true,
            "memory_usage_percent": 45
        },
        "storage": {
            "status": "healthy",
            "disk_usage_percent": 35
        }
    }
}
```

### Health Check Groups

| Group | Components | Severity |
|-------|------------|----------|
| Critical | Application, Database, Redis, Storage | Blocking |
| Important | Queue, Cache, Session, Security | Warning |
| Optional | Mail, External Services | Info |

### Running Health Checks

```bash
# Basic health check
curl https://your-domain.com/health

# Detailed health check
curl https://your-domain.com/health/detailed

# From command line
php artisan health:check

# Generate health report
php artisan health:report --format=json
```

### Health Check Configuration

Configure in [`config/monitoring.php`](config/monitoring.php):

```php
return [
    'health_checks' => [
        'enabled' => true,
        'interval' => 60, // seconds
        'timeout' => 30,
        'groups' => [
            'critical' => [
                'application',
                'database',
                'redis',
                'storage',
            ],
            'important' => [
                'queue',
                'cache',
                'session',
                'security',
            ],
            'optional' => [
                'mail',
                'external_services',
            ],
        ],
    ],
];
```

---

## Alert Rules

### Alert Severity Levels

| Severity | Priority | Color | SLA |
|----------|----------|-------|-----|
| Critical | 1 | 🔴 Red | 15 minutes |
| High | 2 | 🟠 Orange | 30 minutes |
| Medium | 3 | 🟡 Yellow | 2 hours |
| Low | 4 | 🔵 Blue | 8 hours |
| Info | 5 | 🟢 Green | None |

### Infrastructure Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| INF-001 | Application Unavailable | Critical | Health check fails |
| INF-002 | High Memory Usage | Critical | Memory > 95% |
| INF-003 | High CPU Usage | High | CPU > 90% |
| INF-004 | Disk Space Critical | Critical | Disk > 95% |
| INF-005 | Disk Space Warning | Medium | Disk > 80% |

### Database Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| DB-001 | Database Unavailable | Critical | Connection fails |
| DB-002 | Connection Pool Exhausted | Critical | Pool usage > 95% |
| DB-003 | Slow Query Detected | Medium | Query time > 1s |
| DB-004 | Replication Lag | High | Lag > 5 seconds |

### Cache Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| CACHE-001 | Redis Unavailable | Critical | Connection fails |
| CACHE-002 | Low Cache Hit Rate | Medium | Hit rate < 80% |
| CACHE-003 | Redis Memory High | Medium | Memory > 80% |

### Queue Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| QUEUE-001 | Queue Backlog Critical | Critical | Jobs > 500 |
| QUEUE-002 | Queue Backlog Warning | High | Jobs > 100 |
| QUEUE-003 | Failed Jobs Critical | Critical | Failed > 50 |
| QUEUE-004 | Stale Job Detected | High | Job > 10 min |

### Application Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| APP-001 | High Error Rate | Critical | Errors > 5% |
| APP-002 | Elevated Error Rate | High | Errors > 1% |
| APP-003 | Slow Response Time | High | P99 > 3s |
| APP-004 | High Response Time | Medium | P99 > 1s |

### Security Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| SEC-001 | Security Breach | Critical | Threat detected |
| SEC-002 | Brute Force Attack | High | Failed logins > 10 |
| SEC-003 | Suspicious Login | Medium | Anomaly detected |
| SEC-004 | Certificate Expiring | High | Expires < 30 days |

### Managing Alerts

```bash
# List active alerts
php artisan monitoring:alerts --active

# List all alerts (last 24h)
php artisan monitoring:alerts --all

# Alert history
php artisan monitoring:alerts --history --days=7

# Snooze an alert
php artisan monitoring:alerts:snooze ALERT-ID --hours=24

# Acknowledge an alert
php artisan monitoring:alerts:acknowledge ALERT-ID

# Resolve an alert
php artisan monitoring:alerts:resolve ALERT-ID --comment="Issue fixed"
```

---

## Notification Channels

### Email Alerts

```bash
# Configure email recipients
ALERT_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=admin@example.com,dev@example.com,ops@example.com
```

**Email Format:**
```
Subject: [ALUMATE ALERT] Critical - Application Unavailable
Body: Application is not responding to health checks. 
      Time: 2024-09-01 12:00:00 UTC
      Check runbook: https://wiki.alumate.com/runbooks/application-down
```

### Slack Alerts

```bash
# Configure Slack webhook
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/xxx/xxx
ALERT_SLACK_CHANNEL=#alerts
ALERT_SLACK_USERNAME=Alumate Monitor
```

**Slack Message Format:**
```
🚨 *CRITICAL*: Application Unavailable
───────────────────────────────────────
Application is not responding to health checks

*Time:* 2024-09-01 12:00:00 UTC
*Runbook:* https://wiki.alumate.com/runbooks/application-down
```

### PagerDuty Alerts

```bash
# Configure PagerDuty
ALERT_PAGERDUTY_ENABLED=true
PAGERDUTY_INTEGRATION_KEY=xxxxxxxx
PAGERDUTY_SERVICE_NAME=Alumni Platform
```

### SMS/Phone Alerts

```bash
# Configure SMS/Phone alerts
ALERT_SMS_ENABLED=false
ALERT_PHONE_ENABLED=false
ALERT_SMS_RECIPIENTS=+1234567890
ALERT_PHONE_RECIPIENTS=+1234567890
```

### Escalation Policies

#### Default Policy

1. **Step 1** (5 min): Email + Slack → @devops
2. **Step 2** (15 min): Email + Slack + SMS → @platform-lead
3. **Step 3** (30 min): All channels → @engineering-manager

#### Security Policy

1. **Step 1** (0 min): Slack + PagerDuty → @security-team
2. **Step 2** (5 min): Email + SMS + Phone → @security-lead
3. **Step 3** (15 min): All channels → @cto

---

## Performance Monitoring

### Dashboard Access

- **Production Dashboard**: `/admin/monitoring`
- **Grafana**: `http://localhost:3000`
- **Prometheus**: `http://localhost:9090`

### Dashboard Sections

1. **System Overview**: CPU, Memory, Disk, Network
2. **Application Performance**: Response times, Error rates
3. **Database**: Connections, Queries, Slow queries
4. **Cache**: Hit rate, Memory, Keys
5. **Queue**: Backlog, Processing, Failed jobs
6. **Active Alerts**: Current alerts with severity
7. **Business Metrics**: Users, Tenants, Sessions

### Available Metrics

#### Application Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `alumate_http_requests_total` | Counter | Total HTTP requests |
| `alumate_http_request_duration_seconds` | Histogram | Request duration |
| `alumate_http_request_errors_total` | Counter | HTTP errors |
| `alumate_jobs_total` | Counter | Total jobs processed |
| `alumate_job_duration_seconds` | Histogram | Job processing time |

#### Database Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `alumate_db_connections` | Gauge | Active connections |
| `alumate_db_query_duration_seconds` | Histogram | Query duration |
| `alumate_db_slow_queries_total` | Counter | Slow queries |
| `alumate_db_replication_lag_seconds` | Gauge | Replication lag |

#### Cache Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `alumate_cache_hits_total` | Counter | Cache hits |
| `alumate_cache_misses_total` | Counter | Cache misses |
| `alumate_cache_hit_rate` | Gauge | Cache hit percentage |
| `alumate_cache_memory_usage_bytes` | Gauge | Memory usage |

#### Business Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `alumate_active_users` | Gauge | Active users |
| `alumate_new_registrations_total` | Counter | New registrations |
| `alumate_tenant_count` | Gauge | Active tenants |
| `alumate_session_count` | Gauge | Active sessions |

### Accessing Metrics

```bash
# Prometheus endpoint
curl https://your-domain.com/metrics

# Query specific metric
curl https://your-domain.com/metrics | grep alumate_http_requests
```

### Real-time Updates

Dashboard refresh intervals:
- **Realtime**: 30 seconds
- **Default**: 5 minutes
- **Historical**: 1 hour

---

## Log Management

### Viewing Logs

```bash
# View recent logs
php artisan log:view --lines=100

# Filter logs by level
php artisan log:view --level=error --since="1 hour ago"

# Search logs
php artisan log:search --query="database connection"

# View tenant-specific logs
php artisan log:view --tenant=tenant-id --lines=50
```

### Log Levels

| Level | Description |
|-------|-------------|
| DEBUG | Detailed debug information |
| INFO | Informational messages |
| WARNING | Warning conditions |
| ERROR | Error conditions |
| CRITICAL | Critical conditions |
| ALERT | Immediate action required |
| EMERGENCY | System is unusable |

### Log Rotation

```bash
# Rotate logs
php artisan log:rotate --max-size=100MB --keep=30

# Archive old logs
php artisan log:archive --older-than="30 days"

# Clean old logs
php artisan log:clean --older-than=90days
```

### Structured Logging

```php
// Example structured log entry
logger()->info('User logged in', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'tenant_id' => tenant()->id,
]);
```

### Log Configuration

```php
// config/logging.php
return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 30,
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'level' => 'error',
        ],
    ],
];
```

---

## Monitoring Troubleshooting

### Health Check Failures

#### Application Health Fails

```bash
# Check PHP extensions
php -m

# Verify environment
php artisan env

# Check bootstrap cache
ls -la bootstrap/cache/
```

#### Database Health Fails

```bash
# Test connection
php artisan db:connect

# Check migrations
php artisan migrate:status

# Check connections
php artisan tinker
> DB::connection()->select('SELECT 1');
```

#### Redis Health Fails

```bash
# Test connection
redis-cli ping

# Check memory
redis-cli info memory

# Check connected clients
redis-cli info clients
```

### Alert Not Receiving

1. Verify channel is enabled
2. Check webhook URLs are correct
3. Test channel manually:
   ```bash
   php artisan monitoring:test-channel email
   php artisan monitoring:test-channel slack
   ```

### High False Positives

Adjust thresholds in [`config/monitoring.php`](config/monitoring.php):

```php
'thresholds' => [
    'response_time' => [
        'warning' => 2000,  // Increase from 1000
        'critical' => 5000,  // Increase from 3000
    ],
],
```

### Dashboard Not Loading

```bash
# Check Grafana service
docker-compose ps grafana

# Check Prometheus connectivity
curl http://localhost:9090/api/v1/targets
```

### Performance Degradation

```bash
# Check monitoring overhead
php artisan monitoring:overhead

# Reduce sampling rate
'profiling' => [
    'sampling_rate' => 0.001,  // Reduced from 0.01
],
```

### Diagnostic Commands

```bash
# Run full monitoring diagnostic
php artisan monitoring:diagnostic

# Test all notification channels
php artisan monitoring:test-all-channels

# Verify alert rules
php artisan monitoring:verify-rules

# Check Prometheus targets
php artisan monitoring:prometheus:targets
```

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [MONITORING.md](../MONITORING.md)
- [Infrastructure Documentation](../infrastructure/)
- [Grafana Dashboards](http://localhost:3000)

### Command Reference

| Command | Description |
|---------|-------------|
| `php artisan health:check` | Run health check |
| `php artisan monitoring:alerts` | List alerts |
| `php artisan monitoring:test-channel` | Test notification channel |
| `php artisan log:view` | View logs |
| `php artisan log:rotate` | Rotate logs |

### Runbooks

| Alert | Runbook URL |
|-------|-------------|
| Application Down | https://wiki.alumate.com/runbooks/application-down |
| Database Unavailable | https://wiki.alumate.com/runbooks/database-unavailable |
| High Memory Usage | https://wiki.alumate.com/runbooks/memory-critical |
| Disk Full | https://wiki.alumate.com/runbooks/disk-critical |
| Queue Backlog | https://wiki.alumate.com/runbooks/queue-backlog |
| High Error Rate | https://wiki.alumate.com/runbooks/error-rate-critical |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
