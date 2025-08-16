# Performance Monitoring System

## Overview

The Performance Monitoring System provides comprehensive real-time monitoring and optimization capabilities for the Alumni Platform. It includes advanced caching strategies, query optimization, and performance budgets to ensure optimal system performance.

## Features

### 1. Real-time Performance Metrics
- **Cache Hit Rate**: Monitors Redis cache effectiveness
- **Query Performance**: Tracks average database query execution time
- **Memory Usage**: Monitors PHP memory consumption
- **Active Connections**: Tracks database connection pool usage
- **Timeline Generation Time**: Measures social timeline generation performance

### 2. Performance Budgets
- **Timeline Generation**: Budget of 1000ms for timeline creation
- **Cache Hit Rate**: Target of 85% cache effectiveness
- **Memory Usage**: Budget of 256MB for PHP processes
- **Active Connections**: Limit of 50 concurrent database connections

### 3. Social Graph Optimization
- **Connection Caching**: O(1) lookup for user connections
- **Circle Membership Caching**: Fast access to user circles
- **Group Membership Caching**: Optimized group access
- **Pre-computed Metrics**: Social graph analytics caching

### 4. Timeline Query Optimization
- **Database Indexing**: Optimized indexes for timeline queries
- **Slow Query Analysis**: Automatic detection and logging
- **Timeline Segmentation**: Pre-computed timeline segments for active users

## API Endpoints

### GET /api/admin/performance/metrics
Returns current performance metrics, budget status, and alerts.

**Response:**
```json
{
  "success": true,
  "metrics": {
    "cache_hit_rate": 92.5,
    "average_query_time": 45.2,
    "active_connections": 12,
    "memory_usage": 134217728,
    "redis_memory_usage": {
      "used_memory_human": "15.2MB",
      "used_memory_peak_human": "18.7MB"
    },
    "slow_queries_count": 2,
    "timeline_generation_time": 850.3
  },
  "budgets": {
    "timeline_generation": {
      "budget": 1000,
      "current": 850.3,
      "status": "within_budget",
      "percentage": 85.03
    }
  },
  "alerts": []
}
```

### POST /api/admin/performance/clear-caches
Clears all performance-related caches.

### POST /api/admin/performance/optimize-social-graph
Triggers social graph caching optimization.

### POST /api/admin/performance/optimize-timeline
Triggers timeline query optimization.

## Web Interface

### Admin Dashboard
Access the performance monitoring dashboard at `/admin/performance` (requires super-admin role).

**Features:**
- Real-time metrics display
- Performance budget visualization
- Alert notifications
- Cache management controls
- Optimization triggers

## Performance Optimization Service

### PerformanceOptimizationService

The core service provides:

#### Social Graph Caching
```php
// Cache user connections
$this->performanceService->optimizeSocialGraphCaching();

// Get cached connections
$connections = $this->performanceService->getCachedUserConnections($userId);
```

#### Timeline Optimization
```php
// Optimize timeline queries
$this->performanceService->optimizeTimelineQueries();
```

#### Performance Monitoring
```php
// Get current metrics
$metrics = $this->performanceService->monitorPerformanceMetrics();

// Get budget status
$budgets = $this->performanceService->getPerformanceBudgetStatus();
```

## Artisan Commands

### php artisan optimize:performance
Runs comprehensive performance optimization including:
- Social graph caching optimization
- Timeline query optimization
- Database index creation
- Cache warming for active users

## Caching Strategy

### Cache Keys
- `social_graph:connections:{userId}` - User connections
- `social_graph:circles:{userId}` - User circle memberships
- `social_graph:groups:{userId}` - User group memberships
- `query_cache:timeline_segments:{userId}` - Pre-computed timeline segments
- `performance_metrics:*` - Performance metrics and alerts

### Cache TTL
- **Connections**: 6 hours
- **Circles/Groups**: 4 hours
- **Timeline Segments**: 30 minutes
- **Performance Metrics**: 1 hour

## Performance Alerts

### Alert Types
- **Low Cache Hit Rate**: < 80%
- **Slow Queries**: Average > 500ms
- **High Memory Usage**: > 512MB
- **Slow Timeline Generation**: > 2000ms

### Alert Channels
- Application logs (performance channel)
- Dashboard notifications
- Cache storage for real-time display

## Database Optimization

### Indexes Created
- `posts_timeline_idx`: Optimized timeline queries
- `posts_circles_gin_idx`: Circle-based post filtering
- `posts_groups_gin_idx`: Group-based post filtering
- `connections_composite_idx`: Connection lookups
- `circle_memberships_composite_idx`: Circle membership queries
- `group_memberships_composite_idx`: Group membership queries

## Testing

### Feature Tests
Run performance monitoring tests:
```bash
php artisan test --filter=PerformanceMonitoringTest
```

### Test Coverage
- API endpoint authentication and authorization
- Performance metrics calculation
- Cache clearing functionality
- Optimization triggers
- Error handling

## Security

### Access Control
- **Super Admin Only**: All performance monitoring features require super-admin role
- **API Authentication**: Sanctum token authentication for API endpoints
- **Role Middleware**: Enforced at route level

### Data Privacy
- No sensitive user data in performance metrics
- Aggregated statistics only
- Secure cache key patterns

## Monitoring and Alerting

### Real-time Monitoring
- 30-second auto-refresh in dashboard
- Performance budget tracking
- Alert notifications

### Historical Data
- Hourly performance snapshots
- 24-hour data retention
- Trend analysis capabilities

## Best Practices

### Cache Management
- Regular cache warming for active users
- Intelligent cache invalidation
- Hierarchical caching strategy

### Query Optimization
- Eager loading for N+1 prevention
- Optimized database indexes
- Query result caching

### Performance Budgets
- Conservative budget limits
- Proactive alert thresholds
- Regular budget review and adjustment