# Analytics Services Documentation

This document provides comprehensive documentation for the Alumate Platform's analytics services, covering architecture, implementation, and usage patterns.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Core Services](#core-services)
4. [Event Tracking](#event-tracking)
5. [Data Collection](#data-collection)
6. [Privacy & Compliance](#privacy--compliance)
7. [Performance Optimization](#performance-optimization)
8. [Integration Guide](#integration-guide)
9. [API Reference](#api-reference)

## Overview

The Analytics System provides comprehensive tracking, analysis, and insights for the Alumate Platform. It supports:

- **Real-time Event Tracking**: Track user interactions and system events
- **Cohort Analysis**: Analyze user behavior across graduation years and demographics
- **Career Prediction**: ML-powered career trajectory predictions
- **Attribution Tracking**: Track conversion and engagement attribution
- **Privacy Compliance**: GDPR and CCPA compliant data handling

### Key Features

| Feature | Description | Service |
|---------|-------------|---------|
| Event Tracking | Custom event collection | [`CustomEventService`](../../app/Services/Analytics/CustomEventService.php) |
| Session Recording | User session replay | [`SessionRecordingService`](../../app/Services/Analytics/SessionRecordingService.php) |
| Cohort Analysis | Graduation year analysis | [`CohortAnalysisService`](../../app/Services/Analytics/CohortAnalysisService.php) |
| Career Prediction | ML career predictions | [`CareerPredictionService`](../../app/Services/Analytics/CareerPredictionService.php) |
| Attribution | Conversion tracking | [`AttributionService`](../../app/Services/Analytics/AttributionService.php) |
| Privacy | Consent management | [`ConsentService`](../../app/Services/Analytics/ConsentService.php) |

## Architecture

### Service Layer Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Analytics Controller Layer                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ AnalyticsService│  │ InsightsService │  │ ReportingService│ │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘ │
│           │                    │                    │           │
├───────────┴────────────────────┴────────────────────┴───────────┤
│                     Core Analytics Services                      │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │CustomEventService│ │CohortAnalysis   │  │CareerPrediction │ │
│  │                 │  │Service          │  │Service          │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │AttributionService│ │BehaviorFlow     │  │LearningAnalytics│ │
│  │                 │  │Service          │  │Service          │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                  │
├─────────────────────────────────────────────────────────────────┤
│                     Support Services                             │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ConsentService   │  │PrivacyCompliance│  │PrivacyAudit     │ │
│  │                 │  │Service          │  │Service          │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │MonitoringService│  │LoggingService   │  │PerformanceOpt   │ │
│  │                 │  │                 │  │                 │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                  │
├─────────────────────────────────────────────────────────────────┤
│                     External Integrations                        │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │GoogleAnalytics  │  │MatomoService    │  │SyncService      │ │
│  │Service          │  │                 │  │                 │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow

```
User Action → Event Capture → Validation → Processing → Storage → Analysis
     │              │              │            │           │         │
     │              │              │            │           │         │
     ▼              ▼              ▼            ▼           ▼         ▼
  Browser      JavaScript      Backend      Queue      Database   Reports
  Events       SDK/API        Services     Workers    (PostgreSQL)
```

## Core Services

### 1. AnalyticsService

The main entry point for analytics operations.

**Location**: [`app/Services/AnalyticsService.php`](../../app/Services/AnalyticsService.php)

```php
<?php

namespace App\Services;

use App\Services\Analytics\CustomEventService;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\Analytics\InsightsService;

class AnalyticsService extends BaseService
{
    public function __construct(
        private readonly CustomEventService $eventService,
        private readonly CohortAnalysisService $cohortService,
        private readonly InsightsService $insightsService
    ) {}

    /**
     * Track a custom event
     */
    public function trackEvent(string $eventName, array $properties = []): void
    {
        $this->eventService->track($eventName, $properties);
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics(array $filters = []): array
    {
        return [
            'overview' => $this->getOverviewMetrics($filters),
            'engagement' => $this->getEngagementMetrics($filters),
            'cohorts' => $this->cohortService->getActiveCohorts($filters),
            'insights' => $this->insightsService->getTopInsights($filters),
        ];
    }

    /**
     * Generate analytics report
     */
    public function generateReport(string $reportType, array $options = []): array
    {
        return match($reportType) {
            'engagement' => $this->generateEngagementReport($options),
            'cohort' => $this->cohortService->generateCohortReport($options),
            'career' => $this->generateCareerReport($options),
            default => throw new \InvalidArgumentException("Unknown report type: {$reportType}")
        };
    }
}
```

### 2. CustomEventService

Handles custom event tracking and processing.

**Location**: [`app/Services/Analytics/CustomEventService.php`](../../app/Services/Analytics/CustomEventService.php)

```php
<?php

namespace App\Services\Analytics;

class CustomEventService
{
    /**
     * Track a custom event
     *
     * @param string $eventName Event identifier
     * @param array $properties Event properties
     * @param array $context Additional context (user, session, etc.)
     */
    public function track(string $eventName, array $properties = [], array $context = []): void
    {
        $event = [
            'name' => $eventName,
            'properties' => $properties,
            'context' => array_merge($this->getDefaultContext(), $context),
            'timestamp' => now()->toIso8601String(),
        ];

        $this->validateEvent($event);
        $this->processEvent($event);
    }

    /**
     * Track page view event
     */
    public function trackPageView(string $path, array $properties = []): void
    {
        $this->track('page_view', array_merge([
            'path' => $path,
            'referrer' => request()->header('Referer'),
            'user_agent' => request()->userAgent(),
        ], $properties));
    }

    /**
     * Track user action event
     */
    public function trackAction(string $action, string $category, array $properties = []): void
    {
        $this->track('user_action', array_merge([
            'action' => $action,
            'category' => $category,
        ], $properties));
    }

    /**
     * Batch track multiple events
     */
    public function batchTrack(array $events): void
    {
        foreach ($events as $event) {
            $this->track(
                $event['name'],
                $event['properties'] ?? [],
                $event['context'] ?? []
            );
        }
    }
}
```

### 3. CohortAnalysisService

Provides cohort-based analytics for graduation years and user segments.

**Location**: [`app/Services/Analytics/CohortAnalysisService.php`](../../app/Services/Analytics/CohortAnalysisService.php)

```php
<?php

namespace App\Services\Analytics;

class CohortAnalysisService
{
    /**
     * Analyze cohort retention
     *
     * @param string $cohortType Type of cohort (graduation_year, signup_month, etc.)
     * @param array $options Analysis options
     */
    public function analyzeRetention(string $cohortType, array $options = []): array
    {
        $cohorts = $this->getCohorts($cohortType, $options);
        
        return [
            'cohorts' => $cohorts,
            'retention_matrix' => $this->calculateRetentionMatrix($cohorts),
            'average_retention' => $this->calculateAverageRetention($cohorts),
            'trends' => $this->identifyRetentionTrends($cohorts),
        ];
    }

    /**
     * Compare cohort performance
     */
    public function compareCohorts(array $cohortIds, array $metrics): array
    {
        $comparison = [];
        
        foreach ($cohortIds as $cohortId) {
            $comparison[$cohortId] = $this->getCohortMetrics($cohortId, $metrics);
        }
        
        return [
            'comparison' => $comparison,
            'insights' => $this->generateComparisonInsights($comparison),
        ];
    }

    /**
     * Get cohort engagement metrics
     */
    public function getCohortEngagement(int $graduationYear): array
    {
        return [
            'active_users' => $this->getActiveUsersCount($graduationYear),
            'engagement_rate' => $this->calculateEngagementRate($graduationYear),
            'top_activities' => $this->getTopActivities($graduationYear),
            'connection_rate' => $this->getConnectionRate($graduationYear),
        ];
    }
}
```

### 4. CareerPredictionService

ML-powered career trajectory predictions.

**Location**: [`app/Services/Analytics/CareerPredictionService.php`](../../app/Services/Analytics/CareerPredictionService.php)

```php
<?php

namespace App\Services\Analytics;

class CareerPredictionService
{
    /**
     * Predict career trajectory for a user
     *
     * @param int $userId User ID
     * @param array $options Prediction options
     */
    public function predictCareerTrajectory(int $userId, array $options = []): array
    {
        $userProfile = $this->getUserProfile($userId);
        $historicalData = $this->getHistoricalCareerData($userProfile);
        
        return [
            'predicted_roles' => $this->predictRoles($userProfile, $historicalData),
            'salary_projection' => $this->projectSalary($userProfile, $historicalData),
            'skill_recommendations' => $this->recommendSkills($userProfile),
            'confidence_score' => $this->calculateConfidence($userProfile, $historicalData),
        ];
    }

    /**
     * Analyze career patterns for a cohort
     */
    public function analyzeCareerPatterns(int $graduationYear): array
    {
        return [
            'common_paths' => $this->getCommonCareerPaths($graduationYear),
            'industry_distribution' => $this->getIndustryDistribution($graduationYear),
            'salary_ranges' => $this->getSalaryRanges($graduationYear),
            'time_to_promotion' => $this->getAverageTimeToPromotion($graduationYear),
        ];
    }

    /**
     * Get career recommendations
     */
    public function getCareerRecommendations(int $userId): array
    {
        $profile = $this->getUserProfile($userId);
        
        return [
            'job_matches' => $this->findJobMatches($profile),
            'skill_gaps' => $this->identifySkillGaps($profile),
            'networking_suggestions' => $this->suggestConnections($profile),
            'learning_paths' => $this->recommendLearningPaths($profile),
        ];
    }
}
```

### 5. AttributionService

Tracks conversion attribution and engagement sources.

**Location**: [`app/Services/Analytics/AttributionService.php`](../../app/Services/Analytics/AttributionService.php)

```php
<?php

namespace App\Services\Analytics;

class AttributionService
{
    /**
     * Track attribution for a conversion event
     */
    public function trackConversion(string $conversionType, array $data): void
    {
        $attribution = $this->determineAttribution($data);
        
        $this->storeConversion([
            'type' => $conversionType,
            'attribution' => $attribution,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get attribution report
     */
    public function getAttributionReport(array $filters = []): array
    {
        return [
            'by_source' => $this->getAttributionBySource($filters),
            'by_campaign' => $this->getAttributionByCampaign($filters),
            'by_channel' => $this->getAttributionByChannel($filters),
            'conversion_paths' => $this->getConversionPaths($filters),
        ];
    }

    /**
     * Calculate attribution model
     */
    public function calculateAttribution(string $model, array $touchpoints): array
    {
        return match($model) {
            'first_touch' => $this->firstTouchAttribution($touchpoints),
            'last_touch' => $this->lastTouchAttribution($touchpoints),
            'linear' => $this->linearAttribution($touchpoints),
            'time_decay' => $this->timeDecayAttribution($touchpoints),
            'position_based' => $this->positionBasedAttribution($touchpoints),
            default => throw new \InvalidArgumentException("Unknown attribution model: {$model}")
        };
    }
}
```

### 6. BehaviorFlowService

Analyzes user behavior patterns and navigation flows.

**Location**: [`app/Services/Analytics/BehaviorFlowService.php`](../../app/Services/Analytics/BehaviorFlowService.php)

```php
<?php

namespace App\Services\Analytics;

class BehaviorFlowService
{
    /**
     * Analyze user behavior flow
     */
    public function analyzeBehaviorFlow(array $filters = []): array
    {
        return [
            'flow_diagram' => $this->generateFlowDiagram($filters),
            'drop_off_points' => $this->identifyDropOffPoints($filters),
            'common_paths' => $this->getCommonPaths($filters),
            'conversion_funnels' => $this->getConversionFunnels($filters),
        ];
    }

    /**
     * Get user journey analysis
     */
    public function analyzeUserJourney(int $userId): array
    {
        $sessions = $this->getUserSessions($userId);
        
        return [
            'journey_map' => $this->buildJourneyMap($sessions),
            'touchpoints' => $this->identifyTouchpoints($sessions),
            'engagement_score' => $this->calculateEngagementScore($sessions),
            'recommendations' => $this->generateRecommendations($sessions),
        ];
    }

    /**
     * Identify behavior patterns
     */
    public function identifyPatterns(array $options = []): array
    {
        return [
            'frequent_sequences' => $this->findFrequentSequences($options),
            'anomalies' => $this->detectAnomalies($options),
            'segments' => $this->identifyBehaviorSegments($options),
        ];
    }
}
```

## Event Tracking

### Standard Events

The platform tracks these standard events automatically:

| Event | Description | Properties |
|-------|-------------|------------|
| `page_view` | Page navigation | `path`, `referrer`, `duration` |
| `user_login` | User authentication | `method`, `success` |
| `profile_view` | Profile page view | `profile_id`, `viewer_id` |
| `connection_request` | Connection initiated | `target_id`, `source` |
| `job_application` | Job application submitted | `job_id`, `source` |
| `event_registration` | Event registration | `event_id`, `type` |
| `search_performed` | Search action | `query`, `filters`, `results_count` |

### Custom Event Tracking

```php
// Track custom event in controller
public function trackCustomEvent(Request $request)
{
    $analyticsService = app(AnalyticsService::class);
    
    $analyticsService->trackEvent('custom_action', [
        'action_type' => $request->input('action_type'),
        'target_id' => $request->input('target_id'),
        'metadata' => $request->input('metadata', []),
    ]);
    
    return response()->json(['success' => true]);
}
```

### Frontend Event Tracking

```typescript
// resources/js/services/analytics.ts
import { useAnalytics } from '@/composables/useAnalytics'

export function trackEvent(eventName: string, properties: Record<string, any> = {}) {
  const { track } = useAnalytics()
  
  track(eventName, {
    ...properties,
    timestamp: new Date().toISOString(),
    page: window.location.pathname,
  })
}

// Usage in component
import { trackEvent } from '@/services/analytics'

const handleButtonClick = () => {
  trackEvent('button_click', {
    button_id: 'connect-alumni',
    context: 'alumni-directory',
  })
}
```

## Data Collection

### Data Points Collected

| Category | Data Points | Purpose |
|----------|-------------|---------|
| User | ID, role, graduation year | Segmentation |
| Session | Duration, pages viewed, actions | Engagement analysis |
| Device | Browser, OS, screen size | UX optimization |
| Location | Country, region (anonymized) | Geographic insights |
| Referrer | Source, campaign, medium | Attribution |

### Data Retention

| Data Type | Retention Period | Notes |
|-----------|------------------|-------|
| Raw events | 90 days | Full detail |
| Aggregated metrics | 2 years | Summary data |
| User profiles | Account lifetime | With consent |
| Session recordings | 30 days | Privacy compliant |

## Privacy & Compliance

### Consent Management

```php
// Check user consent before tracking
public function trackWithConsent(string $eventName, array $properties): void
{
    $consentService = app(ConsentService::class);
    
    if ($consentService->hasConsent(auth()->id(), 'analytics')) {
        $this->analyticsService->trackEvent($eventName, $properties);
    }
}
```

### Data Anonymization

```php
// Anonymize sensitive data
public function anonymizeData(array $data): array
{
    return [
        'user_id' => hash('sha256', $data['user_id'] . config('app.key')),
        'ip_address' => $this->anonymizeIp($data['ip_address']),
        'email' => $this->maskEmail($data['email']),
        // ... other anonymized fields
    ];
}
```

### GDPR Compliance

- **Right to Access**: Users can export their analytics data
- **Right to Erasure**: Users can request data deletion
- **Data Portability**: Export in standard formats (JSON, CSV)
- **Consent Management**: Granular consent controls

## Performance Optimization

### Caching Strategy

```php
// Cache expensive analytics queries
public function getCachedMetrics(string $key, callable $callback, int $ttl = 3600): array
{
    return Cache::remember("analytics:{$key}", $ttl, $callback);
}

// Usage
$metrics = $this->getCachedMetrics('dashboard_overview', function () {
    return $this->calculateDashboardMetrics();
}, 300); // 5 minutes cache
```

### Batch Processing

```php
// Process events in batches
public function processBatchEvents(array $events): void
{
    $chunks = array_chunk($events, 100);
    
    foreach ($chunks as $chunk) {
        ProcessAnalyticsEvents::dispatch($chunk);
    }
}
```

### Query Optimization

```php
// Use database indexes effectively
public function getOptimizedMetrics(array $filters): array
{
    return DB::table('analytics_events')
        ->select([
            'event_name',
            DB::raw('COUNT(*) as count'),
            DB::raw('DATE(created_at) as date'),
        ])
        ->where('created_at', '>=', $filters['start_date'])
        ->where('created_at', '<=', $filters['end_date'])
        ->groupBy('event_name', 'date')
        ->orderBy('date')
        ->get()
        ->toArray();
}
```

## Integration Guide

### Adding New Analytics Service

1. Create service class in `app/Services/Analytics/`
2. Implement required interface
3. Register in service provider
4. Add configuration options

```php
// 1. Create service
namespace App\Services\Analytics;

class NewAnalyticsService
{
    public function __construct(
        private readonly AnalyticsLoggingService $logger
    ) {}

    public function analyze(array $data): array
    {
        $this->logger->info('Starting analysis', $data);
        
        // Implementation
        
        return $results;
    }
}

// 2. Register in AppServiceProvider
public function register(): void
{
    $this->app->singleton(NewAnalyticsService::class, function ($app) {
        return new NewAnalyticsService(
            $app->make(AnalyticsLoggingService::class)
        );
    });
}
```

### External Integrations

#### Google Analytics

```php
// config/analytics.php
'google_analytics' => [
    'enabled' => env('GA_ENABLED', false),
    'tracking_id' => env('GA_TRACKING_ID'),
    'api_secret' => env('GA_API_SECRET'),
],

// Usage
$gaService = app(GoogleAnalyticsService::class);
$gaService->sendEvent('page_view', ['page_path' => '/alumni']);
```

#### Matomo

```php
// config/analytics.php
'matomo' => [
    'enabled' => env('MATOMO_ENABLED', false),
    'url' => env('MATOMO_URL'),
    'site_id' => env('MATOMO_SITE_ID'),
    'token' => env('MATOMO_TOKEN'),
],
```

## API Reference

### Analytics Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/analytics/events` | POST | Track event |
| `/api/analytics/metrics` | GET | Get metrics |
| `/api/analytics/reports` | GET | Generate report |
| `/api/analytics/cohorts` | GET | Get cohort data |
| `/api/analytics/insights` | GET | Get insights |

### Request/Response Examples

```bash
# Track event
POST /api/analytics/events
Content-Type: application/json

{
  "event_name": "profile_view",
  "properties": {
    "profile_id": 123,
    "source": "search"
  }
}

# Response
{
  "success": true,
  "event_id": "evt_abc123"
}
```

```bash
# Get metrics
GET /api/analytics/metrics?start_date=2026-01-01&end_date=2026-01-31

# Response
{
  "success": true,
  "data": {
    "total_events": 15420,
    "unique_users": 3250,
    "engagement_rate": 0.72,
    "top_events": [...]
  }
}
```

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Events not tracking | Consent not granted | Check consent status |
| Slow queries | Missing indexes | Add database indexes |
| Data discrepancy | Cache stale | Clear analytics cache |
| High memory usage | Large batch size | Reduce batch size |

### Debug Mode

```php
// Enable analytics debug mode
// .env
ANALYTICS_DEBUG=true

// Check debug logs
tail -f storage/logs/analytics.log
```

---

**Related Documentation**:
- [API Development Guide](./api-development-guide.md)
- [Performance Optimization Guide](./performance-optimization-guide.md)
- [Security Best Practices](./security-best-practices.md)

**Last Updated**: February 2026
