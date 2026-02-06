# Monitoring and Alerting Documentation

This document provides comprehensive documentation for the Alumate monitoring and alerting system.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Configuration Files](#configuration-files)
4. [Health Checks](#health-checks)
5. [Alert Rules](#alert-rules)
6. [Notification Channels](#notification-channels)
7. [Metrics](#metrics)
8. [Dashboard](#dashboard)
9. [Runbooks](#runbooks)
10. [Troubleshooting](#troubleshooting)

## Overview

The Alumate monitoring system provides comprehensive visibility into application performance, infrastructure health, and business metrics. It includes:

- **Application Performance Monitoring (APM)**: Track response times, error rates, and resource utilization
- **Infrastructure Monitoring**: Monitor database, cache, queue, and storage systems
- **Health Checks**: Automated health verification for all critical services
- **Alerting**: Multi-channel notifications with intelligent escalation
- **Custom Metrics**: Business and application-specific metrics collection
- **Uptime Monitoring**: Continuous availability checking

## Architecture

### Components

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

### Data Flow

1. **Metrics Collection**: Application and infrastructure metrics are collected by Laravel Horizon and exposed via Prometheus endpoints
2. **Storage**: Metrics are stored in Prometheus time-series database
3. **Alerting**: Prometheus Alertmanager evaluates alert rules and routes notifications
4. **Visualization**: Grafana dashboards provide real-time visualization
5. **Notifications**: Alerts are sent via configured channels (email, Slack, PagerDuty)

## Configuration Files

### Main Configuration Files

| File | Purpose |
|------|---------|
| [`config/monitoring.php`](config/monitoring.php) | Core monitoring configuration |
| [`infrastructure/production/monitoring-config.php`](infrastructure/production/monitoring-config.php) | Production-specific settings |
| [`infrastructure/production/alert-rules.json`](infrastructure/production/alert-rules.json) | Structured alert rules |
| [`infrastructure/production/health-checks.php`](infrastructure/production/health-checks.php) | Health check definitions |
| [`config/logging-prod.php`](config/logging-prod.php) | Production logging configuration |

### Environment Variables

Key environment variables for monitoring:

```bash
# General
MONITORING_ENABLED=true
APP_ENV=production

# Alerting
ALERTS_ENABLED=true
ALERT_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=admin@example.com,dev@example.com
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/...
ALERT_PAGERDUTY_ENABLED=false
ALERT_PAGERDUTY_INTEGRATION_KEY=...

# Health Checks
HEALTH_CHECKS_ENABLED=true
HEALTH_CHECK_INTERVAL=60

# External Services
SENTRY_ENABLED=false
SENTRY_DSN=...
DATADOG_ENABLED=false
DATADOG_API_KEY=...
NEWRELIC_ENABLED=false
NEWRELIC_LICENSE_KEY=...

# Database
DB_HOST=127.0.0.1
DB_PORT=5432

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Health Checks

### Available Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Basic health check (critical components only) |
| `/health/detailed` | GET | Full health check with all components |
| `/ready` | GET | Kubernetes readiness probe |
| `/live` | GET | Kubernetes liveness probe |

### Health Check Response Format

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

- **Critical**: Application, Database, Redis, Storage
- **Important**: Queue, Cache, Session, Security
- **Optional**: Mail, External Services

### Running Health Checks Manually

```bash
# Basic health check
curl https://your-domain.com/health

# Detailed health check
curl https://your-domain.com/health/detailed

# From command line
php artisan health:check
```

## Alert Rules

### Alert Severity Levels

| Severity | Priority | Color | SLA |
|----------|----------|-------|-----|
| Critical | 1 | 🔴 Red | 15 minutes |
| High | 2 | 🟠 Orange | 30 minutes |
| Medium | 3 | 🟡 Yellow | 2 hours |
| Low | 4 | 🔵 Blue | 8 hours |
| Info | 5 | 🟢 Green | None |

### Alert Categories

#### Infrastructure Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| INF-001 | Application Unavailable | Critical | Health check fails |
| INF-002 | High Memory Usage | Critical | Memory > 95% |
| INF-003 | High CPU Usage | High | CPU > 90% |
| INF-004 | Disk Space Critical | Critical | Disk > 95% |
| INF-005 | Disk Space Warning | Medium | Disk > 80% |

#### Database Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| DB-001 | Database Unavailable | Critical | Connection fails |
| DB-002 | Connection Pool Exhausted | Critical | Pool usage > 95% |
| DB-003 | Slow Query Detected | Medium | Query time > 1s |
| DB-004 | Replication Lag | High | Lag > 5 seconds |

#### Cache Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| CACHE-001 | Redis Unavailable | Critical | Connection fails |
| CACHE-002 | Low Cache Hit Rate | Medium | Hit rate < 80% |
| CACHE-003 | Redis Memory High | Medium | Memory > 80% |

#### Queue Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| QUEUE-001 | Queue Backlog Critical | Critical | Jobs > 500 |
| QUEUE-002 | Queue Backlog Warning | High | Jobs > 100 |
| QUEUE-003 | Failed Jobs Critical | Critical | Failed > 50 |
| QUEUE-004 | Stale Job Detected | High | Job > 10 min |

#### Application Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| APP-001 | High Error Rate | Critical | Errors > 5% |
| APP-002 | Elevated Error Rate | High | Errors > 1% |
| APP-003 | Slow Response Time | High | P99 > 3s |
| APP-004 | High Response Time | Medium | P99 > 1s |

#### Security Alerts

| Alert ID | Name | Severity | Condition |
|----------|------|----------|-----------|
| SEC-001 | Security Breach | Critical | Threat detected |
| SEC-002 | Brute Force Attack | High | Failed logins > 10 |
| SEC-003 | Suspicious Login | Medium | Anomaly detected |
| SEC-004 | Certificate Expiring | High | Expires < 30 days |

### Viewing Active Alerts

```bash
# List active alerts
php artisan monitoring:alerts --active

# List all alerts (last 24h)
php artisan monitoring:alerts --all

# Alert history
php artisan monitoring:alerts --history --days=7
```

### Managing Alerts

```bash
# Snooze an alert
php artisan monitoring:alerts:snooze ALERT-ID --hours=24

# Acknowledge an alert
php artisan monitoring:alerts:acknowledge ALERT-ID

# Resolve an alert
php artisan monitoring:alerts:resolve ALERT-ID --comment="Issue fixed"
```

## Notification Channels

### Email Alerts

Configure email recipients in environment:

```bash
ALERT_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=admin@example.com,dev@example.com,ops@example.com
```

Email format:
```
Subject: [ALUMATE ALERT] Critical - Application Unavailable
Body: Application is not responding to health checks. 
      Time: 2024-09-01 12:00:00 UTC
      Check runbook: https://wiki.alumate.com/runbooks/application-down
```

### Slack Alerts

Configure Slack webhook:

```bash
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/xxx/xxx
ALERT_SLACK_CHANNEL=#alerts
ALERT_SLACK_USERNAME=Alumate Monitor
```

Slack message format:
```
🚨 *CRITICAL*: Application Unavailable
────────────────────────────────────────
Application is not responding to health checks

*Time:* 2024-09-01 12:00:00 UTC
*Runbook:* https://wiki.alumate.com/runbooks/application-down
```

### PagerDuty Alerts

```bash
ALERT_PAGERDUTY_ENABLED=true
PAGERDUTY_INTEGRATION_KEY=xxxxxxxx
PAGERDUTY_SERVICE_NAME=Alumni Platform
```

### SMS/Phone Alerts

For critical and high-severity alerts:

```bash
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

## Metrics

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

#### Queue Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `alumate_queue_jobs_waiting` | Gauge | Jobs waiting |
| `alumate_queue_jobs_processing` | Gauge | Jobs processing |
| `alumate_queue_jobs_failed_total` | Counter | Failed jobs |
| `alumate_queue_backlog` | Gauge | Queue backlog |

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

### Grafana Dashboards

Access Grafana at: `http://localhost:3000`

Available dashboards:
- **Overview**: System-wide metrics
- **Application Performance**: Request metrics and errors
- **Database**: Query performance and connections
- **Cache**: Redis performance
- **Queue**: Job processing
- **Business**: User engagement and KPIs

## Dashboard

### Access

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

### Real-time Updates

Dashboard refresh intervals:
- **Realtime**: 30 seconds
- **Default**: 5 minutes
- **Historical**: 1 hour

## Runbooks

### Common Runbooks

| Alert | Runbook URL |
|-------|-------------|
| Application Down | https://wiki.alumate.com/runbooks/application-down |
| Database Unavailable | https://wiki.alumate.com/runbooks/database-unavailable |
| High Memory Usage | https://wiki.alumate.com/runbooks/memory-critical |
| Disk Full | https://wiki.alumate.com/runbooks/disk-critical |
| Queue Backlog | https://wiki.alumate.com/runbooks/queue-backlog |
| High Error Rate | https://wiki.alumate.com/runbooks/error-rate-critical |
| Security Breach | https://wiki.alumate.com/runbooks/security-breach |

### Sample Runbook Structure

```markdown
# Runbook: Application Down

## Severity
Critical

## Description
Application is not responding to health checks.

## Symptoms
- `/health` endpoint returns unhealthy
- Users cannot access the application
- API requests are failing

## Diagnosis Steps
1. Check container status:
   ```bash
   docker-compose ps
   ```

2. Check application logs:
   ```bash
   docker-compose logs app | tail -100
   ```

3. Check resource usage:
   ```bash
   docker stats
   ```

4. Check database connection:
   ```bash
   php artisan db:connect
   ```

## Resolution Steps
1. If container is down, restart it:
   ```bash
   docker-compose restart app
   ```

2. If memory issue, scale up:
   ```bash
   docker-compose up -d --scale app=2
   ```

3. If database issue, check database status:
   ```bash
   docker-compose exec db pg_isready
   ```

## Escalation
If not resolved in 15 minutes, escalate to @engineering-manager.

## Prevention
- Monitor memory usage proactively
- Set up automatic scaling
- Regular capacity planning
```

## Troubleshooting

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

Adjust thresholds in `config/monitoring.php`:

```php
'thresholds' => [
    'response_time' => [
        'warning' => 2000,  // Increase from 1000
        'critical' => 5000,  // Increase from 3000
    ],
],
```

### Dashboard Not Loading

1. Check Grafana service:
   ```bash
   docker-compose ps grafana
   ```

2. Check Prometheus connectivity:
   ```bash
   curl http://localhost:9090/api/v1/targets
   ```

### Performance Degradation

1. Check monitoring overhead:
   ```bash
   php artisan monitoring:overhead
   ```

2. Reduce sampling rate:
   ```php
   'profiling' => [
       'sampling_rate' => 0.001,  // Reduced from 0.01
   ],
   ```

## Maintenance

### Data Retention

| Data Type | Retention |
|-----------|-----------|
| High-resolution metrics | 7 days |
| Hourly aggregates | 30 days |
| Daily aggregates | 1 year |
| Alerts | 180 days |
| Logs | 90 days |

### Log Rotation

Logs are automatically rotated:
- Daily rotation
- 30-day retention for app logs
- 90-day retention for security logs
- 365-day retention for audit logs

### Cleanup Commands

```bash
# Clean old metrics
php artisan monitoring:cleanup-metrics --older-than=30days

# Clean old alerts
php artisan monitoring:cleanup-alerts --older-than=90days

# Clean old logs
php artisan monitoring:cleanup-logs --older-than=90days
```

## API Reference

### Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan monitoring:cycle` | Run monitoring cycle |
| `php artisan monitoring:alerts` | List alerts |
| `php artisan monitoring:health` | Check health |
| `php artisan monitoring:metrics` | Collect metrics |
| `php artisan monitoring:reports` | Generate reports |

### HTTP Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Basic health check |
| `/health/detailed` | GET | Detailed health check |
| `/metrics` | GET | Prometheus metrics |
| `/ready` | GET | Readiness probe |
| `/live` | GET | Liveness probe |

## Support

For monitoring issues:

1. Check runbooks first
2. Review recent alerts
3. Check Grafana dashboards
4. Review application logs
5. Contact #devops on Slack

### Escalation Path

1. On-call engineer (first response)
2. Platform lead (15+ minutes)
3. Engineering manager (30+ minutes)
4. CTO (critical issues)

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 3.0.0 | 2024-09-01 | Complete rewrite with new architecture |
| 2.0.0 | 2024-01-15 | Added Prometheus integration |
| 1.0.0 | 2023-06-01 | Initial release |
