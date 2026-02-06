# Analytics Administration Guide

**ABOUTME:** Comprehensive guide for administering the Alumate analytics system including dashboard management, custom reports, data exports, and performance monitoring.

## Table of Contents

1. [Overview](#overview)
2. [Dashboard Access and Navigation](#dashboard-access-and-navigation)
3. [Analytics Configuration](#analytics-configuration)
4. [Custom Reports](#custom-reports)
5. [Data Export and Scheduling](#data-export-and-scheduling)
6. [Performance Monitoring](#performance-monitoring)
7. [Analytics Troubleshooting](#analytics-troubleshooting)

---

## Overview

The Alumate Analytics System provides comprehensive analytics capabilities including:

- **Engagement Metrics**: Track user activity, session duration, and feature usage
- **Alumni Activity**: Monitor graduation year activity and geographic distribution
- **Community Health**: Analyze network density and group participation
- **Platform Usage**: Device breakdown and peak usage patterns
- **Advanced Analytics**: Cohort analysis, attribution modeling, and behavior flow

### System Architecture

The analytics system consists of:

| Component | Description |
|-----------|-------------|
| AnalyticsService | Core service for event tracking and data aggregation |
| CohortAnalysisService | Cohort-based user behavior analysis |
| AttributionService | Marketing attribution modeling |
| BehaviorFlowService | User navigation path analysis |
| HeatMapService | Click and scroll heat map generation |
| ABTestingService | A/B test management and analysis |

---

## Dashboard Access and Navigation

### Accessing Analytics Dashboard

1. **Via Admin Panel**
   - Navigate to `/admin/analytics`
   - Sign in with administrator credentials
   - Ensure proper role permissions (analytics.view)

2. **Via Direct URL**
   - Production: `https://your-domain.com/admin/analytics`
   - Development: `http://localhost:8080/admin/analytics`

### Dashboard Sections

```
┌─────────────────────────────────────────────────────────────────┐
│ Analytics Dashboard                                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ Engagement      │  │ Alumni Activity │  │ Community       │  │
│  │ Metrics         │  │                 │  │ Health          │  │
│  │                 │  │                 │  │                 │  │
│  │ - Total Users   │  │ - Daily Active  │  │ - Network       │  │
│  │ - Active Users  │  │   Users         │  │   Density       │  │
│  │ - Engagement    │  │ - Post Activity │  │ - Group         │  │
│  │   Rate          │  │ - Feature Usage │  │   Participation │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ Platform Usage  │  │ Geographic      │  │ Conversion       │  │
│  │                 │  │ Distribution   │  │ Funnels         │  │
│  │ - Device        │  │                 │  │                 │  │
│  │   Breakdown     │  │ - Activity Map  │  │ - Signup Funnel │  │
│  │ - Peak Times    │  │ - Location Data │  │ - Job Search    │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Date Range Selection

1. **Quick Select**
   - Today
   - Yesterday
   - Last 7 days
   - Last 30 days
   - Last 90 days
   - This month
   - This quarter
   - This year

2. **Custom Range**
   - Click date picker
   - Select start date
   - Select end date
   - Apply filter

3. **Relative Time**
   - Past X days
   - Since last login
   - Since registration

### Filtering Options

| Filter | Description |
|--------|-------------|
| Institution | Filter by specific institution |
| Graduation Year | Filter by graduation cohort |
| Location | Filter by geographic location |
| Program | Filter by academic program |
| User Segment | Filter by user behavior segment |

---

## Analytics Configuration

### Cache Settings

Configure analytics caching in [`config/analytics.php`](config/analytics.php):

```php
'cache' => [
    'enabled' => env('ANALYTICS_CACHE_ENABLED', true),
    'ttl' => env('ANALYTICS_CACHE_TTL', 300), // 5 minutes
    'prefix' => 'analytics:',
],
```

### Snapshot Settings

```php
'snapshots' => [
    'enabled' => env('ANALYTICS_SNAPSHOTS_ENABLED', true),
    'retention_days' => env('ANALYTICS_SNAPSHOTS_RETENTION', 365),
    'auto_generate' => [
        'daily' => env('ANALYTICS_AUTO_DAILY_SNAPSHOTS', true),
        'weekly' => env('ANALYTICS_AUTO_WEEKLY_SNAPSHOTS', true),
        'monthly' => env('ANALYTICS_AUTO_MONTHLY_SNAPSHOTS', true),
    ],
],
```

### KPI Settings

```php
'kpis' => [
    'auto_calculate' => env('ANALYTICS_AUTO_CALCULATE_KPIS', true),
    'calculation_schedule' => env('ANALYTICS_KPI_SCHEDULE', 'daily'),
    'alert_thresholds' => [
        'employment_rate' => [
            'warning' => 70.0,
            'critical' => 60.0,
        ],
        'job_placement_rate' => [
            'warning' => 15.0,
            'critical' => 10.0,
        ],
    ],
],
```

### Performance Settings

```php
'performance' => [
    'query_timeout' => env('ANALYTICS_QUERY_TIMEOUT', 60),
    'memory_limit' => env('ANALYTICS_MEMORY_LIMIT', '512M'),
    'chunk_size' => env('ANALYTICS_CHUNK_SIZE', 1000),
    'parallel_processing' => env('ANALYTICS_PARALLEL_PROCESSING', false),
],
```

### Security Settings

```php
'security' => [
    'data_anonymization' => env('ANALYTICS_ANONYMIZE_DATA', false),
    'audit_access' => env('ANALYTICS_AUDIT_ACCESS', true),
    'rate_limiting' => [
        'enabled' => env('ANALYTICS_RATE_LIMITING', true),
        'max_requests_per_minute' => env('ANALYTICS_MAX_REQUESTS_PER_MINUTE', 60),
    ],
],
```

### Environment Variables

```bash
# Analytics Configuration
ANALYTICS_CACHE_ENABLED=true
ANALYTICS_CACHE_TTL=300
ANALYTICS_SNAPSHOTS_ENABLED=true
ANALYTICS_SNAPSHOTS_RETENTION=365
ANALYTICS_AUTO_CALCULATE_KPIS=true
ANALYTICS_KPI_SCHEDULE=daily
ANALYTICS_QUERY_TIMEOUT=60
ANALYTICS_MEMORY_LIMIT=512M
ANALYTICS_CHUNK_SIZE=1000
ANALYTICS_RATE_LIMITING=true
ANALYTICS_MAX_REQUESTS_PER_MINUTE=60
```

---

## Custom Reports

### Creating Custom Reports

1. **Navigate to Reports**
   - Go to `/admin/analytics/reports`
   - Click "Create New Report"

2. **Configure Report**
   ```json
   {
       "name": "Monthly Engagement Report",
       "description": "Monthly user engagement metrics",
       "metrics": [
           "total_users",
           "active_users",
           "engagement_rate",
           "sessions_per_user"
       ],
       "dimensions": [
           "graduation_year",
           "institution",
           "location"
       ],
       "date_range": {
           "type": "month",
           "start": "2024-01-01",
           "end": "2024-12-31"
       },
       "filters": {
           "user_type": ["alumni", "student"],
           "min_sessions": 1
       },
       "format": "pdf",
       "schedule": {
           "frequency": "monthly",
           "day": 1,
           "time": "09:00"
       }
   }
   ```

3. **Select Metrics**

   | Category | Metrics Available |
   |----------|------------------|
   | Engagement | Total users, Active users, Sessions, Avg session duration |
   | Alumni | Graduation year activity, Degree distribution, Major breakdown |
   | Community | Network density, Group participation, Connection rate |
   | Platform | Device breakdown, Browser usage, Peak times |

4. **Apply Filters**
   - User type (alumni, student, employer)
   - Institution
   - Location
   - Program
   - Custom segments

5. **Save and Generate**
   - Preview report
   - Save configuration
   - Generate immediately or schedule

### Managing Reports

```bash
# List all reports
php artisan analytics:reports --list

# Generate specific report
php artisan analytics:reports:generate --id=1 --format=pdf

# Delete report
php artisan analytics:reports:delete --id=1

# Export report configuration
php artisan analytics:reports:export --id=1
```

### Report Templates

Pre-built report templates:

1. **Executive Summary**
   - High-level KPIs
   - Trend analysis
   - Key insights

2. **User Engagement Report**
   - Active user metrics
   - Session analysis
   - Feature adoption

3. **Community Health Report**
   - Network metrics
   - Group participation
   - Connection patterns

4. **Platform Performance Report**
   - Technical metrics
   - Performance benchmarks
   - Usage patterns

---

## Data Export and Scheduling

### Export Formats

| Format | Description | Use Case |
|--------|-------------|----------|
| CSV | Comma-separated values | Spreadsheet analysis |
| JSON | JavaScript Object Notation | API integration |
| PDF | Portable Document Format | Reports |
| Excel | Microsoft Excel format | Detailed analysis |

### Export Data Types

1. **Event Data**
   - Page views
   - Clicks
   - Form submissions
   - Custom events

2. **User Data**
   - User profiles
   - Activity logs
   - Session data

3. **Aggregated Metrics**
   - Daily active users
   - Engagement rates
   - Conversion funnels

### Scheduled Exports

Configure automated exports:

```bash
# Create scheduled export
php artisan analytics:export:schedule \
    --report=engagement_monthly \
    --frequency=monthly \
    --format=csv \
    --destination=s3://bucket/exports
```

### Export via API

```bash
# Export analytics data
curl -X GET "https://your-domain.com/api/analytics/export" \
    --header "Authorization: Bearer {token}" \
    --data-urlencode "start_date=2024-01-01" \
    --data-urlencode "end_date=2024-01-31" \
    --data-urlencode "format=csv" \
    --data-urlencode "metrics=active_users,engagement_rate"
```

### Export Management

```bash
# List scheduled exports
php artisan analytics:exports --list

# View export history
php artisan analytics:exports:history --days=30

# Cancel scheduled export
php artisan analytics:exports:cancel --id=1
```

---

## Performance Monitoring

### Dashboard Performance Metrics

| Metric | Description | Target |
|--------|-------------|--------|
| Page Load Time | Time to load dashboard | < 3 seconds |
| Data Refresh | Time to fetch analytics data | < 5 seconds |
| Chart Render | Time to render visualizations | < 2 seconds |

### Query Performance

Monitor analytics query performance:

```bash
# Check slow queries
php artisan analytics:queries:slow --threshold=1s

# Analyze query performance
php artisan analytics:queries:analyze --date=2024-01-15

# Optimize queries
php artisan analytics:queries:optimize
```

### Cache Performance

```bash
# View cache statistics
php artisan analytics:cache:stats

# Clear analytics cache
php artisan analytics:cache:clear

# Warm up cache
php artisan analytics:cache:warmup --tags=metrics,dashboards
```

### Resource Usage

Monitor system resources:

```bash
# Check memory usage
php artisan analytics:resources:memory

# Check CPU usage
php artisan analytics:resources:cpu

# View processing queue
php artisan analytics:queue:status
```

### Performance Optimization

1. **Enable Caching**
   ```bash
   ANALYTICS_CACHE_ENABLED=true
   ANALYTICS_CACHE_TTL=300
   ```

2. **Optimize Queries**
   - Use date range filters
   - Limit data granularity
   - Enable query caching

3. **Schedule Off-Peak Jobs**
   ```bash
   # Run heavy analytics during off-peak hours
   SCHEDULE_ANALYTICS_JOBS=2:00 AM
   ```

4. **Use Pagination**
   - Implement paginated data retrieval
   - Use incremental loading

---

## Analytics Troubleshooting

### Common Issues

#### 1. Missing or Empty Data

**Symptom**: Dashboard shows no data or zero values

**Possible Causes**:
- Incorrect date range selected
- Cache not populated
- Data collection disabled
- Permission issues

**Solution**:
```bash
# Verify data collection
php artisan analytics:check --verbose

# Clear and refresh cache
php artisan analytics:cache:clear
php artisan analytics:cache:warmup

# Check permissions
php artisan analytics:permissions:check
```

#### 2. Slow Dashboard Loading

**Symptom**: Dashboard takes longer than expected to load

**Solution**:
```bash
# Check query performance
php artisan analytics:queries:slow

# Optimize database indexes
php artisan analytics:indexes:optimize

# Increase cache TTL
ANALYTICS_CACHE_TTL=600
```

#### 3. Export Failures

**Symptom**: Data export fails or times out

**Solution**:
```bash
# Check export status
php artisan analytics:exports:status

# Retry failed export
php artisan analytics:exports:retry --id=1

# Reduce data scope
--start_date=2024-01-01 --end_date=2024-01-07
```

#### 4. Cache Invalidation Issues

**Symptom**: Dashboard shows stale data after updates

**Solution**:
```bash
# Clear specific cache
php artisan cache:flush --tags=analytics

# Clear all analytics cache
php artisan analytics:cache:clear
```

### Diagnostic Commands

```bash
# Run full diagnostics
php artisan analytics:diagnostic

# Check data integrity
php artisan analytics:data:integrity

# Verify tenant isolation
php artisan analytics:tenant:verify

# Test external integrations
php artisan analytics:integrations:test
```

### Log Analysis

```bash
# View analytics logs
tail -f storage/logs/analytics.log

# Search for errors
grep "ERROR" storage/logs/analytics.log

# Filter by date
grep "2024-01-15" storage/logs/analytics.log
```

### Support Escalation

If issues persist after troubleshooting:

1. Gather diagnostic information:
   ```bash
   php artisan analytics:diagnostic --output=json
   ```

2. Review monitoring dashboard

3. Check active alerts

4. Contact support with:
   - Error messages
   - Diagnostic output
   - Steps to reproduce
   - Affected users/tenants

---

## Additional Resources

### Related Documentation

- [Analytics System Documentation](../analytics/ANALYTICS_SYSTEM_DOCUMENTATION.md)
- [Advanced Analytics System](../analytics/advanced-analytics-system.md)
- [API Documentation](../api/)
- [Monitoring Documentation](../MONITORING.md)

### Tools and Commands Reference

| Command | Description |
|---------|-------------|
| `php artisan analytics:dashboard` | Refresh dashboard data |
| `php artisan analytics:reports:list` | List all reports |
| `php artisan analytics:cache:clear` | Clear analytics cache |
| `php artisan analytics:diagnostic` | Run diagnostics |
| `php artisan analytics:export` | Export analytics data |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
