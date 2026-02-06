# Analytics System Documentation

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Analytics Services](#analytics-services)
   - [AttributionService](#attributionservice)
   - [BehaviorFlowService](#behaviorservice)
   - [CohortAnalysisService](#cohortanalysisservice)
4. [API Documentation](#api-documentation)
   - [AttributionController](#attributioncontroller)
   - [CohortAnalysisController](#cohortanalysiscontroller)
   - [ExternalIntegrationController](#externalintegrationcontroller)
   - [AnalyticsController](#analyticscontroller)
5. [Frontend Components](#frontend-components)
   - [Analytics Dashboard](#analytics-dashboard)
   - [Chart Components](#chart-components)
6. [Testing Documentation](#testing-documentation)
   - [Unit Tests](#unit-tests)
   - [Feature Tests](#feature-tests)
   - [Tenant Isolation Tests](#tenant-isolation-tests)
7. [Configuration Guide](#configuration-guide)
8. [Troubleshooting Guide](#troubleshooting-guide)

---

## Overview

The Alumate Analytics System is a comprehensive analytics platform that provides advanced user behavior analysis, cohort tracking, attribution modeling, and external platform integrations. The system is built as part of Phase 3: Learning Analytics & Testing and is designed to support multi-tenant architectures while maintaining strict data isolation between tenants.

Key capabilities include:
- **Cohort Analysis**: Track and compare user groups based on acquisition date, behavior patterns, and characteristics
- **Attribution Modeling**: Understand marketing touchpoint contributions across multiple attribution models
- **Behavior Flow Analysis**: Analyze user navigation paths and identify common behavior patterns
- **External Integrations**: Connect with Google Analytics and Matomo for unified data views
- **Privacy Compliance**: Built-in consent management and data anonymization support

---

## System Architecture

The analytics system follows a layered architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Frontend Layer                                │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Analytics        │  │ Dashboard       │  │ Chart Components    │  │
│  │ Tracking Service │  │ (Vue.js)        │  │ (Recharts/Chart.js) │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        API Layer                                     │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Attribution      │  │ Cohort Analysis │  │ External Integration│  │
│  │ Controller       │  │ Controller      │  │ Controller          │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                    AnalyticsController                         │  │
│  │  (Event Tracking, Reports, Export, Custom Events)              │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       Service Layer                                  │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ AttributionService│ │ BehaviorFlow    │  │ CohortAnalysis      │  │
│  │                  │ │ Service         │  │ Service             │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Custom │ MatomoServiceEvent     │     │  │ SyncService         │  │
│  │ Service         │  │                  │  │                     │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     Storage Layer                                     │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Analytics Events │  │ Custom Events   │  │ Attribution Touches │  │
│  │                  │  │                 │  │                     │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Cohorts         │  │ Sync Logs       │  │ Cache (Redis)       │  │
│  │                 │  │                 │  │                     │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

### Key Architectural Features

1. **Multi-Tenant Support**: All analytics data is scoped to the current tenant with automatic tenant isolation
2. **Event-Driven Processing**: User interactions are captured and processed asynchronously through Laravel jobs
3. **Caching Strategy**: Redis-based caching for frequently accessed analytics data with configurable TTL
4. **Privacy Compliance**: Built-in GDPR/CCPA compliance with consent management and data anonymization
5. **API-First Design**: All functionality accessible through RESTful APIs

---

## Analytics Services

### AttributionService

**Location**: [`app/Services/Analytics/AttributionService.php`](app/Services/Analytics/AttributionService.php)

The AttributionService provides comprehensive attribution modeling functionality for marketing analytics. It supports multiple attribution models to understand how different marketing touchpoints contribute to user conversions.

#### Supported Attribution Models

| Model | Description | Use Case |
|-------|-------------|----------|
| `last_touch` | 100% credit to the last touchpoint before conversion | Simple, direct response campaigns |
| `first_touch` | 100% credit to the first touchpoint | Awareness-focused campaigns |
| `linear` | Equal credit distributed across all touchpoints | Multi-touch consideration journeys |
| `time_decay` | More credit to touchpoints closer to conversion | Short sales cycles |
| `position_based` | 40% first, 40% last, 20% distributed middle | Balanced consideration journeys |

#### Key Methods

```php
// Track a user touchpoint for attribution analysis
public function trackTouch(array $touchData): AttributionTouch

// Calculate attribution for a user within a time period
public function calculateAttribution(
    int $userId,
    string $startDate,
    string $endDate,
    string $model = 'last_touch'
): array

// Get attribution summary for multiple users
public function getAttributionSummary(
    array $userIds,
    string $startDate,
    string $endDate,
    string $model = 'last_touch'
): array

// Get touch history for a user
public function getTouchHistory(int $userId, int $limit = 50): Collection

// Get channel contribution analysis
public function getChannelContribution(
    string $channel,
    string $startDate,
    string $endDate
): array

// Calculate channel ROI
public function calculateChannelROI(
    string $channel,
    string $startDate,
    string $endDate,
    float $channelSpend = 0
): array

// Generate budget allocation recommendations
public function generateBudgetRecommendations(
    string $startDate,
    string $endDate,
    float $totalBudget = 0
): array
```

#### Usage Example

```php
use App\Services\Analytics\AttributionService;

$attributionService = app(AttributionService::class);

// Calculate attribution for a user
$attribution = $attributionService->calculateAttribution(
    userId: 123,
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    model: 'position_based'
);

// Get channel performance with ROI
$channelROI = $attributionService->calculateChannelROI(
    channel: 'google',
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    channelSpend: 5000.00
);
```

---

### BehaviorFlowService

**Location**: [`app/Services/Analytics/BehaviorFlowService.php`](app/Services/Analytics/BehaviorFlowService.php)

The BehaviorFlowService analyzes user behavior patterns and event sequences to understand how users navigate through the application and identify common paths.

#### Key Methods

```php
// Analyze behavior flow for a specific event
public function analyzeBehaviorFlow(int $definitionId, array $filters = []): array

// Analyze funnel for a sequence of events
public function analyzeFunnel(array $eventSequence, array $filters = []): array
```

#### Flow Analysis Response Structure

```php
[
    'definition' => [
        'id' => int,
        'name' => string,
        'description' => string,
    ],
    'flow_graph' => [
        // Node structure with outgoing edges
    ],
    'metrics' => [
        'total_users' => int,
        'total_events' => int,
        'unique_events' => int,
        'path_length_distribution' => [
            'min' => int,
            'max' => int,
            'avg' => float,
            'median' => float,
        ],
    ],
    'common_paths' => [
        [
            'path' => string,
            'frequency' => int,
            'percentage' => float,
            'unique_users' => int,
        ],
    ],
    'optimization_suggestions' => [
        [
            'type' => string,
            'severity' => 'high' | 'medium' | 'low',
            'message' => string,
            'recommendation' => string,
        ],
    ],
]
```

#### Funnel Analysis Response Structure

```php
[
    'steps' => [
        [
            'step' => int,
            'definition_id' => int,
            'event_name' => string,
            'total_events' => int,
            'unique_users' => int,
        ],
    ],
    'conversion_rates' => [
        'step_1' => [
            'step' => int,
            'users' => int,
            'conversion_rate' => float,
            'step_conversion_rate' => float,
        ],
        'overall' => float,
    ],
    'drop_off_points' => [
        [
            'from_step' => int,
            'to_step' => int,
            'from_event' => string,
            'to_event' => string,
            'drop_off_rate' => float,
            'users_lost' => int,
            'severity' => 'high' | 'medium' | 'low',
        ],
    ],
    'insights' => [
        [
            'type' => 'positive' | 'neutral' | 'negative',
            'message' => string,
            'recommendation' => string,
        ],
    ],
]
```

---

### CohortAnalysisService

**Location**: [`app/Services/Analytics/CohortAnalysisService.php`](app/Services/Analytics/CohortAnalysisService.php)

The CohortAnalysisService provides comprehensive cohort analysis functionality for user behavior analysis over time, including retention, engagement, and conversion rate calculations.

#### Key Methods

```php
// Create a cohort based on specified criteria
public function createCohort(
    string $name,
    array $criteria,
    ?int $createdBy = null
): Cohort

// Calculate retention rate for a cohort
public function calculateRetention(int $cohortId, int $daysAfter): float

// Calculate engagement metrics for a cohort
public function calculateEngagement(int $cohortId): array

// Calculate conversion rates for a cohort
public function calculateConversionRate(int $cohortId): array

// Analyze a cohort with comprehensive metrics
public function analyzeCohort(int $cohortId, array $options = []): array

// Compare multiple cohorts
public function compareCohorts(array $cohortIds): array

// Analyze trends over time for a cohort
public function analyzeTrends(
    int $cohortId,
    string $period = 'week',
    int $periods = 12
): array

// Generate automated insights for a cohort
public function generateInsights(int $cohortId): array
```

#### Cohort Criteria Structure

```php
[
    'grad_year' => int,           // Graduation year
    'degree' => string,           // Degree type
    'acquisition_date' => string,  // Date range start
    'acquisition_source' => string,// Marketing source
    'major' => string,            // Academic major
    'metadata' => array,          // Additional filters
]
```

#### Analysis Response Structure

```php
[
    'cohort_id' => int,
    'cohort_name' => string,
    'size' => int,
    'retention' => [
        'day7' => float,
        'day30' => float,
        'day90' => float,
    ],
    'churn_rate' => [
        'day7' => float,
        'day30' => float,
        'day90' => float,
    ],
    'engagement' => [
        'avg_sessions_per_week' => float,
        'avg_pages_per_session' => float,
        'avg_active_days_per_week' => float,
        'engagement_score' => float,
    ],
    'conversions' => [
        'signup' => ['count' => int, 'rate' => float],
        'first_login' => ['count' => int, 'rate' => float],
        'profile_complete' => ['count' => int, 'rate' => float],
        'first_purchase' => ['count' => int, 'rate' => float],
    ],
    'criteria' => array,
    'analyzed_at' => string,
]
```

---

## API Documentation

### AttributionController

**Location**: [`app/Http/Controllers/Analytics/AttributionController.php`](app/Http/Controllers/Analytics/AttributionController.php)

#### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/analytics/attribution` | List attribution touches with filtering |
| POST | `/api/analytics/attribution/touch` | Track a new attribution touch |
| GET | `/api/analytics/attribution/{userId?}` | Get attribution report for user |
| GET | `/api/analytics/attribution/summary` | Get attribution summary for multiple users |
| GET | `/api/analytics/attribution/channel-performance` | Get channel performance metrics |
| GET | `/api/analytics/attribution/budget-recommendations` | Get budget allocation recommendations |

#### Request Parameters

**index() Endpoint:**
```php
// Query Parameters
page: int (default: 1)
per_page: int (default: 50)
user_id: int|null
source: string|null
start_date: string|null
end_date: string|null
model: string (default: 'last_touch')
```

**store() Endpoint:**
```json
{
    "user_id": int,
    "source": "string",
    "event_type": "page_view|click|form_submit|purchase|signup|login",
    "value": float|null,
    "timestamp": datetime|null
}
```

---

### CohortAnalysisController

**Location**: [`app/Http/Controllers/Analytics/CohortAnalysisController.php`](app/Http/Controllers/Analytics/CohortAnalysisController.php)

#### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cohorts` | List all cohorts with pagination |
| POST | `/api/cohorts` | Create a new cohort |
| GET | `/api/cohorts/{id}` | Get cohort details with analysis |
| PUT | `/api/cohorts/{id}` | Update cohort configuration |
| DELETE | `/api/cohorts/{id}` | Delete a cohort |
| GET | `/api/cohorts/{id}/retention` | Get retention metrics |
| GET | `/api/cohorts/{id}/engagement` | Get engagement metrics |
| GET | `/api/cohorts/{id}/conversion` | Get conversion rates |
| POST | `/api/cohorts/compare` | Compare multiple cohorts |
| GET | `/api/cohorts/{id}/trends` | Get trend analysis |
| GET | `/api/cohorts/{id}/insights` | Get automated insights |

#### Request Validation

**Store Cohort Request:**
```json
{
    "name": "string|required|max:255",
    "criteria": {
        "grad_year": "integer",
        "degree": "string",
        "acquisition_date": "date",
        "acquisition_source": "string"
    }
}
```

**Compare Cohorts Request:**
```json
{
    "cohort_ids": "array|min:2|max:10",
    "metrics": "array|in:retention,engagement,conversion"
}
```

**Retention Request:**
```json
{
    "days_after": "array|items:integer|min:1|max:365"
}
```

---

### ExternalIntegrationController

**Location**: [`app/Http/Controllers/Analytics/ExternalIntegrationController.php`](app/Http/Controllers/Analytics/ExternalIntegrationController.php)

#### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/analytics/integrations/unified-data` | Get unified data from all sources |
| POST | `/api/analytics/integrations/sync-events` | Sync events to external platforms |
| GET | `/api/analytics/integrations/sync-status` | Get sync status |
| GET | `/api/analytics/integrations/discrepancies` | Get data discrepancies |
| POST | `/api/analytics/integrations/resolve-discrepancies` | Resolve data discrepancies |
| POST | `/api/analytics/integrations/validate-configuration` | Validate integration config |
| POST | `/api/analytics/integrations/google/goals` | Create Google Analytics goal |
| POST | `/api/analytics/integrations/google/audiences` | Create Google Analytics audience |
| GET | `/api/analytics/integrations/google/report` | Get Google Analytics report |
| GET | `/api/analytics/integrations/google/realtime` | Get GA real-time data |
| POST | `/api/analytics/integrations/matomo/goals` | Create Matomo goal |
| POST | `/api/analytics/integrations/matomo/segments` | Create Matomo segment |
| GET | `/api/analytics/integrations/matomo/report` | Get Matomo report |
| GET | `/api/analytics/integrations/matomo/realtime` | Get Matomo real-time data |

---

### AnalyticsController

**Location**: [`app/Http/Controllers/AnalyticsController.php`](app/Http/Controllers/AnalyticsController.php)

#### Core Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/analytics/events` | Store analytics events in batch |
| POST | `/api/analytics/conversions` | Store conversion event |
| POST | `/api/analytics/errors` | Store error event |
| GET | `/api/analytics/metrics` | Get analytics metrics |
| GET | `/api/analytics/reports/{type}` | Generate analytics report |
| GET | `/api/analytics/export` | Export analytics data |

#### Report Types

- `conversion`: Conversion funnel analysis
- `engagement`: User engagement metrics
- `performance`: Platform performance data
- `funnel`: Funnel step analysis

#### Cohort Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/cohorts/create` | Create a new cohort |
| GET | `/api/cohorts/{cohortId}` | Get cohort with metrics |
| POST | `/api/cohorts/compare` | Compare multiple cohorts |
| GET | `/api/cohorts/list` | List cohorts with filters |

#### Attribution Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/analytics/attribution/track` | Track touchpoint |
| GET | `/api/analytics/attribution/{userId}` | Get user attribution |
| GET | `/api/analytics/channel-performance` | Get channel performance |
| GET | `/api/analytics/budget-recommendations` | Get budget recommendations |

#### Custom Events Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/analytics/events/define` | Define custom event |
| POST | `/api/analytics/events/track` | Track custom event |
| GET | `/api/analytics/events/{eventName}/analysis` | Get event analysis |
| GET | `/api/analytics/events/list` | List custom events |

---

## Frontend Components

### Analytics Dashboard

**Location**: [`resources/js/Pages/Analytics/Dashboard.vue`](resources/js/Pages/Analytics/Dashboard.vue)

The Analytics Dashboard provides a comprehensive view of platform analytics with real-time data visualization.

#### Component Structure

```vue
<template>
    <div class="analytics-dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Analytics Dashboard</h1>
            <div class="header-actions">
                <DateRangePicker v-model:start-date="filters.start_date" />
                <button @click="showExportModal = true">Export Data</button>
                <button @click="showCustomReportModal = true">Custom Report</button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <SummaryCard
                v-for="metric in summaryMetrics"
                :key="metric.key"
                :title="metric.title"
                :value="metric.value"
                :change="metric.change"
                :trend="metric.trend"
            />
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <EngagementChart :data="engagementData" />
            <UserActivityChart :data="activityData.daily_active_users" />
            <PostActivityChart :data="activityData.post_activity" />
            <FeatureUsageChart :data="activityData.feature_usage" />
            <NetworkDensityGauge :value="communityHealth.network_density" />
            <GroupParticipationChart :data="communityHealth.group_participation" />
            <DeviceBreakdownChart :data="platformUsage.device_breakdown" />
            <PeakUsageChart :data="platformUsage.peak_usage_times" />
            <GeographicMap :data="activityData.geographic_distribution" />
        </div>

        <!-- Data Tables -->
        <div class="tables-section">
            <AnalyticsTable title="Top Performing Groups" :data="..." />
            <AnalyticsTable title="Graduation Year Activity" :data="..." />
        </div>
    </div>
</template>
```

#### Reactive Data Structure

```typescript
interface Filters {
    start_date: string;
    end_date: string;
    institution_id: number | null;
    graduation_year: number | null;
    location: string | null;
    program: string | null;
}

interface AnalyticsData {
    engagement_metrics: {
        total_users: number;
        active_users: number;
        engagement_rate: number;
    };
    alumni_activity: {
        daily_active_users: TimeSeriesData[];
        post_activity: PostActivityData[];
        feature_usage: FeatureUsageData[];
        geographic_distribution: GeoData[];
        graduation_year_activity: YearActivityData[];
    };
    community_health: {
        network_density: number;
        group_participation: GroupData[];
    };
    platform_usage: {
        device_breakdown: DeviceData[];
        peak_usage_times: PeakUsageData[];
    };
}
```

#### API Integration

```typescript
const loadDashboardData = async () => {
    const response = await axios.get('/api/analytics/dashboard', {
        params: filters,
    });

    if (response.data.success) {
        analyticsData.value = response.data.data;
    }
};

const handleExport = async (exportConfig: ExportConfig) => {
    const response = await axios.get('/api/analytics/export', {
        params: {
            ...exportConfig,
            ...filters,
        },
        responseType: 'blob',
    });
    // Handle blob download
};
```

---

### Chart Components

The dashboard utilizes the following chart components located in `resources/js/Components/Analytics/Charts/`:

| Component | Description |
|-----------|-------------|
| `EngagementChart.vue` | User engagement trends over time |
| `UserActivityChart.vue` | Daily active users visualization |
| `PostActivityChart.vue` | Post engagement metrics |
| `FeatureUsageChart.vue` | Feature adoption and usage |
| `NetworkDensityGauge.vue` | Network connectivity visualization |
| `GroupParticipationChart.vue` | Community group participation |
| `DeviceBreakdownChart.vue` | Device type distribution |
| `PeakUsageChart.vue` | Usage patterns by time |
| `GeographicMap.vue` | Geographic distribution map |

---

## Testing Documentation

### Unit Tests

#### AttributionService Tests

**Location**: [`tests/Unit/Services/Analytics/AttributionServiceTest.php`](tests/Unit/Services/Analytics/AttributionServiceTest.php)

```php
class AttributionServiceTest extends TestCase
{
    public function test_track_touch_creates_attribution_touch(): void
    {
        $touchData = [
            'user_id' => 1,
            'source' => 'google',
            'event_type' => 'click',
            'value' => 10.50,
        ];

        $touch = $this->attributionService->trackTouch($touchData);

        $this->assertInstanceOf(AttributionTouch::class, $touch);
        $this->assertEquals('google', $touch->source);
    }

    public function test_calculate_attribution_with_last_touch_model(): void
    {
        $attribution = $this->attributionService->calculateAttribution(
            userId: 1,
            startDate: '2024-01-01',
            endDate: '2024-12-31',
            model: 'last_touch'
        );

        $this->assertArrayHasKey('sources', $attribution);
        $this->assertEquals('last_touch', $attribution['model']);
    }

    public function test_calculate_channel_roi(): void
    {
        $roi = $this->attributionService->calculateChannelROI(
            channel: 'google',
            startDate: '2024-01-01',
            endDate: '2024-12-31',
            channelSpend: 5000.00
        );

        $this->assertArrayHasKey('roi', $roi);
        $this->assertArrayHasKey('roas', $roi);
    }
}
```

#### BehaviorFlowService Tests

**Location**: [`tests/Unit/Services/Analytics/BehaviorFlowServiceTest.php`](tests/Unit/Services/Analytics/BehaviorFlowServiceTest.php)

```php
class BehaviorFlowServiceTest extends TestCase
{
    public function test_analyze_behavior_flow_returns_flow_graph(): void
    {
        $flow = $this->behaviorFlowService->analyzeBehaviorFlow(
            definitionId: 1,
            filters: ['start_date' => '2024-01-01']
        );

        $this->assertArrayHasKey('flow_graph', $flow);
        $this->assertArrayHasKey('metrics', $flow);
        $this->assertArrayHasKey('common_paths', $flow);
    }

    public function test_analyze_funnel_calculates_conversion_rates(): void
    {
        $funnel = $this->behaviorFlowService->analyzeFunnel(
            eventSequence: [1, 2, 3],
            filters: []
        );

        $this->assertArrayHasKey('steps', $funnel);
        $this->assertArrayHasKey('conversion_rates', $funnel);
        $this->assertArrayHasKey('drop_off_points', $funnel);
    }
}
```

#### CohortAnalysisService Tests

**Location**: [`tests/Unit/Services/Analytics/CohortAnalysisServiceTest.php`](tests/Unit/Services/Analytics/CohortAnalysisServiceTest.php)

```php
class CohortAnalysisServiceTest extends TestCase
{
    public function test_create_cohort_validates_criteria(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cohortAnalysisService->createCohort(
            name: 'Test Cohort',
            criteria: [] // Empty criteria should throw
        );
    }

    public function test_calculate_retention_returns_percentage(): void
    {
        $retention = $this->cohortAnalysisService->calculateRetention(
            cohortId: 1,
            daysAfter: 30
        );

        $this->assertIsFloat($retention);
        $this->assertGreaterThanOrEqual(0, $retention);
        $this->assertLessThanOrEqual(100, $retention);
    }

    public function test_compare_cohorts_requires_minimum_two(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cohortAnalysisService->compareCohorts(
            cohortIds: [1] // Single cohort should throw
        );
    }

    public function test_generate_insights_returns_actionable_data(): void
    {
        $insights = $this->cohortAnalysisService->generateInsights(cohortId: 1);

        $this->assertIsArray($insights);
        foreach ($insights as $insight) {
            $this->assertArrayHasKey('type', $insight);
            $this->assertArrayHasKey('severity', $insight);
            $this->assertArrayHasKey('message', $insight);
            $this->assertArrayHasKey('recommendation', $insight);
        }
    }
}
```

---

### Feature Tests

#### CohortAnalysisControllerTest

**Location**: [`tests/Feature/CohortAnalysisControllerTest.php`](tests/Feature/CohortAnalysisControllerTest.php)

```php
class CohortAnalysisControllerTest extends TestCase
{
    // CRUD Operation Tests
    public function test_index_returns_cohort_list_for_admin(): void { /* ... */ }
    public function test_store_creates_new_cohort(): void { /* ... */ }
    public function test_show_returns_cohort_details(): void { /* ... */ }
    public function test_update_modifies_cohort(): void { /* ... */ }
    public function test_destroy_deletes_cohort(): void { /* ... */ }

    // Metrics Tests
    public function test_retention_returns_retention_metrics(): void { /* ... */ }
    public function test_engagement_returns_engagement_metrics(): void { /* ... */ }
    public function test_conversion_returns_conversion_metrics(): void { /* ... */ }

    // Comparison Tests
    public function test_compare_returns_comparison_data(): void { /* ... */ }
    public function test_compare_validation_fails_with_single_cohort(): void { /* ... */ }

    // Trend Analysis Tests
    public function test_trends_returns_trend_analysis(): void { /* ... */ }

    // Insights Tests
    public function test_insights_returns_automated_insights(): void { /* ... */ }
}
```

---

### Tenant Isolation Tests

```php
class CohortAnalysisControllerTenantIsolationTest extends TestCase
{
    public function test_user_cannot_access_other_tenant_cohort(): void
    {
        $otherCohort = Cohort::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->controller->show($otherCohort->id);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_super_admin_can_access_any_tenant_cohort(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Auth::login($superAdmin);

        $otherCohort = Cohort::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->controller->show($otherCohort->id);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_user_cannot_compare_different_tenant_cohorts(): void
    {
        $cohort1 = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        $cohort2 = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->controller->compare([$cohort1->id, $cohort2->id]);

        $this->assertEquals(403, $response->getStatusCode());
    }
}
```

---

## Configuration Guide

### Analytics Configuration

**Location**: [`config/analytics.php`](config/analytics.php)

#### Cache Settings

```php
'cache' => [
    'enabled' => env('ANALYTICS_CACHE_ENABLED', true),
    'ttl' => env('ANALYTICS_CACHE_TTL', 300), // 5 minutes
    'prefix' => 'analytics:',
],
```

#### Snapshots Settings

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

#### KPI Settings

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

#### Performance Settings

```php
'performance' => [
    'query_timeout' => env('ANALYTICS_QUERY_TIMEOUT', 60),
    'memory_limit' => env('ANALYTICS_MEMORY_LIMIT', '512M'),
    'chunk_size' => env('ANALYTICS_CHUNK_SIZE', 1000),
    'parallel_processing' => env('ANALYTICS_PARALLEL_PROCESSING', false),
],
```

#### Security Settings

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

#### Environment Variables

```env
# Analytics
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

## Troubleshooting Guide

### Common Issues

#### 1. Missing Tenant Context

**Symptom**: Error message "Tenant context not available"

**Solution**: Ensure the request includes proper tenant identification through:
- Session-based tenant detection
- Domain-based tenant mapping
- API token with tenant scope

```php
// Check tenant context
$tenantId = session('tenant_id', 'default');

if (!$tenantId) {
    throw new \Exception('Tenant context not available');
}
```

#### 2. Attribution Calculation Returns Empty Results

**Symptom**: Attribution report shows no data

**Possible Causes**:
- No touchpoints recorded for the user
- Date range too narrow
- Missing consent for analytics tracking

**Solution**:
```php
// Verify consent
$consentService = app(ConsentService::class);
if (!$consentService->checkConsent($userId, 'analytics')) {
    return 'User has not consented to analytics tracking';
}

// Check date range
$startDate = now()->subDays(30);
$endDate = now();

// Verify touchpoints exist
$touches = AttributionTouch::byUser($userId)
    ->byPeriod($startDate, $endDate)
    ->count();
```

#### 3. Cohort Analysis Timeout

**Symptom**: Cohort analysis requests timeout

**Solution**: Adjust performance settings or use pagination

```php
// Use smaller cohorts or chunked processing
$cohortAnalysisService->analyzeCohort($cohortId, [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'limit' => 1000, // Process in chunks
]);
```

#### 4. Cache Invalidation Issues

**Symptom**: Dashboard shows stale data after updates

**Solution**: Manually clear cache after updates

```php
use Illuminate\Support\Facades\Cache;

// Clear analytics cache
Cache::tags(['analytics'])->flush();

// Or clear specific cache keys
Cache::forget("cohort_analysis_{$cohortId}");
Cache::forget("attribution_{$userId}_{$startDate}_{$endDate}");
```

#### 5. External Integration Sync Failures

**Symptom**: Google Analytics or Matomo sync fails

**Solution**: Validate configuration and check credentials

```php
// Validate integration configuration
$validation = $this->dataSyncService->validateConfiguration();

if (!$validation['google']['valid']) {
    // Check Google credentials
    logger()->error('Google Analytics configuration invalid', [
        'error' => $validation['google']['error'],
    ]);
}

if (!$validation['matomo']['valid']) {
    // Check Matomo credentials
    logger()->error('Matomo configuration invalid', [
        'error' => $validation['matomo']['error'],
    ]);
}
```

---

### Performance Optimization Tips

1. **Use Caching**: Enable Redis caching for frequently accessed analytics data
2. **Limit Date Ranges**: Use smaller date ranges for detailed analysis
3. **Paginate Results**: Use pagination for large datasets
4. **Use Summary Endpoints**: Use `/api/analytics/summary` for dashboard data
5. **Schedule Off-Peak Jobs**: Run heavy analytics jobs during off-peak hours

---

### Support and Resources

- **Documentation**: See [`docs/analytics/`](docs/analytics/) for additional documentation
- **API Reference**: See [`docs/api/`](docs/api/) for full API documentation
- **Testing Guide**: See [`docs/testing/`](docs/testing/) for testing best practices
- **Configuration Guide**: See [`docs/setup/`](docs/setup/) for environment setup
