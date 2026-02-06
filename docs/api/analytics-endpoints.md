# Analytics Endpoints API Documentation

This document provides comprehensive documentation for all analytics-related API endpoints.

## Overview

The Analytics API provides powerful insights into user behavior, engagement, career outcomes, fundraising performance, and more. All endpoints require authentication unless otherwise specified.

## Base URL
```
https://api.alumnate.edu/v1/analytics
```

---

## Core Analytics

### Get Analytics Dashboard

```http
GET /api/analytics/dashboard
Authorization: Bearer {token}
```

**Required Role:** admin or super_admin

**Response (200):**
```json
{
  "success": true,
  "data": {
    "engagement": {
      "active_users": 2450,
      "new_registrations": 127,
      "posts_created": 423,
      "events_attended": 98
    },
    "career_outcomes": {
      "employed_rate": 94.2,
      "average_salary": 87500,
      "promotions": 67
    },
    "donations": {
      "total_raised": 156700,
      "active_campaigns": 3,
      "completion_rate": 78.5
    }
  }
}
```

### Get Engagement Metrics

```http
GET /api/analytics/engagement-metrics
Authorization: Bearer {token}
```

**Required Role:** admin or super_admin

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | date | No | Start date (YYYY-MM-DD) |
| end_date | date | No | End date (YYYY-MM-DD) |
| period | string | No | daily, weekly, monthly |

### Get Analytics Summary

```http
GET /api/analytics/summary
Authorization: Bearer {token}
```

**Required Role:** admin or super_admin

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | overview, engagement, career, donations |
| year | integer | No | Filter by year |

---

## Cohort Analysis

### Create Cohort

```http
POST /api/analytics/cohorts/create
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Class of 2023",
  "description": "Graduates of 2023",
  "filters": {
    "graduation_year": 2023,
    "department": "Computer Science"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Class of 2023",
    "member_count": 450
  }
}
```

### List Cohorts

```http
GET /api/analytics/cohorts
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Class of 2023",
      "member_count": 450,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "total": 5,
    "current_page": 1
  }
}
```

### Get Cohort Details

```http
GET /api/analytics/cohorts/{cohortId}
Authorization: Bearer {token}
```

### Compare Cohorts

```http
POST /api/analytics/cohorts/compare
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "cohorts": [123, 124, 125],
  "metrics": ["retention", "engagement", "career_outcomes"]
}
```

---

## Cohort Analysis (Advanced)

### List Cohort Analyses

```http
GET /api/analytics/cohort-analysis
Authorization: Bearer {token}
```

### Create Cohort Analysis

```http
POST /api/analytics/cohort-analysis
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Retention Analysis Q1 2024",
  "cohort_type": "graduation_month",
  "metrics": ["retention_rate", "engagement_score"],
  "timeframe": "12_months"
}
```

### Get Cohort Analysis Results

```http
GET /api/analytics/cohort-analysis/{id}
Authorization: Bearer {token}
```

### Get Retention Metrics

```http
GET /api/analytics/cohort-analysis/{id}/retention
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "cohorts": [
      {
        "period": "2024-01",
        "cohort_size": 100,
        "retention": {
          "month_1": 0.95,
          "month_3": 0.88,
          "month_6": 0.82,
          "month_12": 0.75
        }
      }
    ]
  }
}
```

### Get Engagement Metrics

```http
GET /api/analytics/cohort-analysis/{id}/engagement
Authorization: Bearer {token}
```

### Get Conversion Metrics

```http
GET /api/analytics/cohort-analysis/{id}/conversion
Authorization: Bearer {token}
```

### Compare Cohort Analyses

```http
POST /api/analytics/cohort-analysis/compare
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "cohort_ids": [1, 2, 3],
  "metrics": ["retention", "engagement", "conversion"]
}
```

### Get Trends

```http
GET /api/analytics/cohort-analysis/{id}/trends
Authorization: Bearer {token}
```

### Get Insights

```http
GET /api/analytics/cohort-analysis/{id}/insights
Authorization: Bearer {token}
```

---

## Attribution Analysis

### Track Touchpoint

```http
POST /api/analytics/attribution/track-touch
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "user_id": 123,
  "touchpoint_type": "email_click",
  "campaign_id": 456,
  "channel": "email",
  "timestamp": "2024-01-15T10:30:00Z",
  "metadata": {
    "email_id": 789,
    "link_url": "https://..."
  }
}
```

### Get User Attribution

```http
GET /api/analytics/attribution/models/{userId}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "attribution_model": "last_click",
    "touchpoints": [
      {
        "channel": "email",
        "timestamp": "2024-01-15T10:30:00Z",
        "conversion_value": 50.00
      }
    ],
    "attributed_conversion": true
  }
}
```

### Get Channel Performance

```http
GET /api/analytics/attribution/channels
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | date | No | Start date |
| end_date | date | No | End date |
| model | string | No | first_click, last_click, linear, time_decay |

### Get Budget Recommendations

```http
GET /api/analytics/attribution/budget-recommendations
Authorization: Bearer {token}
```

---

## Attribution Analysis (Advanced)

### List Touchpoints

```http
GET /api/analytics/attribution-analysis/touchpoints
Authorization: Bearer {token}
```

### Track Touchpoint

```http
POST /api/analytics/attribution-analysis/touchpoints
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 123,
  "channel": "email",
  "campaign_id": 456,
  "touchpoint_type": "impression",
  "timestamp": "2024-01-15T10:30:00Z",
  "metadata": {}
}
```

### Calculate Attribution

```http
GET /api/analytics/attribution-analysis/calculate/{userId}
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| model | string | No | Attribution model |
| conversion_id | integer | No | Specific conversion |

### Compare Attribution Models

```http
GET /api/analytics/attribution-analysis/compare/{userId}
Authorization: Bearer {token}
```

### Get Channel Performance

```http
GET /api/analytics/attribution-analysis/channels/performance
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "channels": [
      {
        "channel": "email",
        "conversions": 1250,
        "revenue": 62500.00,
        "cost": 5000.00,
        "roi": 1150.00
      }
    ],
    "top_performing": "email"
  }
}
```

### Get Budget Recommendations

```http
GET /api/analytics/attribution-analysis/budget/recommendations
Authorization: Bearer {token}
```

### Get Conversion Path

```http
GET /api/analytics/attribution-analysis/conversion-path/{userId}
Authorization: Bearer {token}
```

---

## Custom Events

### List Custom Event Definitions

```http
GET /api/analytics/custom-events/definitions
Authorization: Bearer {token}
```

### Create Custom Event Definition

```http
POST /api/analytics/custom-events/definitions
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Course Completion",
  "description": "Triggered when a user completes a course",
  "category": "learning",
  "properties": {
    "course_id": "integer",
    "completion_time": "integer",
    "score": "integer"
  }
}
```

### Track Custom Event

```http
POST /api/analytics/custom-events/track
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_name": "Course Completion",
  "user_id": 123,
  "properties": {
    "course_id": 456,
    "completion_time": 1800,
    "score": 95
  }
}
```

### Analyze Custom Event

```http
GET /api/analytics/custom-events/{definitionId}/analyze
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | date | No | Start date |
| end_date | date | No | End date |
| group_by | string | No | Property to group by |

### Get Event Analytics

```http
GET /api/analytics/custom-events/{definitionId}/analytics
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_count": 15234,
    "unique_users": 1234,
    "avg_daily": 125.5,
    "trends": [
      {"date": "2024-01-01", "count": 150}
    ]
  }
}
```

### Get Behavior Flow

```http
GET /api/analytics/custom-events/{definitionId}/behavior-flow
Authorization: Bearer {token}
```

### Get Optimization Suggestions

```http
GET /api/analytics/custom-events/{definitionId}/optimization-suggestions
Authorization: Bearer {token}
```

### Create Funnel Analysis

```http
POST /api/analytics/custom-events/funnel
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Course Funnel",
  "steps": [
    {"event": "course_start", "name": "Course Started"},
    {"event": "lesson_complete", "name": "Lesson Completed"},
    {"event": "quiz_start", "name": "Quiz Started"},
    {"event": "course_complete", "name": "Course Completed"}
  ],
  "time_window": 86400
}
```

---

## Learning Analytics

### Get Learning Analytics Overview

```http
GET /api/analytics/learning-analytics
Authorization: Bearer {token}
```

### Get User Learning Progress

```http
GET /api/analytics/learning-analytics/user/{userId}
Authorization: Bearer {token}
```

### Track Learning Progress

```http
POST /api/analytics/learning-analytics/progress/track
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 123,
  "course_id": 456,
  "progress_percentage": 75,
  "time_spent": 3600,
  "last_activity": "2024-01-15T10:30:00Z"
}
```

### Get Learning Progress

```http
GET /api/analytics/learning-analytics/progress/{userId}/{courseId}
Authorization: Bearer {token}
```

### Analyze Learning Outcomes

```http
GET /api/analytics/learning-analytics/outcomes/{userId}
Authorization: Bearer {token}
```

### Get Learning Metrics

```http
GET /api/analytics/learning-analytics/metrics/{userId}
Authorization: Bearer {token}
```

### Compare Learning Performance

```http
POST /api/analytics/learning-analytics/compare
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_ids": [123, 124, 125],
  "course_id": 456,
  "metrics": ["completion_rate", "avg_score", "time_spent"]
}
```

### Predict Course Completion

```http
GET /api/analytics/learning-analytics/predict/{userId}/{courseId}
Authorization: Bearer {token}
```

### Get Learning Recommendations

```http
GET /api/analytics/learning-analytics/recommendations/{userId}
Authorization: Bearer {token}
```

### Track Learning Activity

```http
POST /api/analytics/learning-analytics/activity/track
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 123,
  "activity_type": "video_watch",
  "resource_id": 789,
  "duration": 1200,
  "progress": 0.8
}
```

### Verify Certification

```http
GET /api/analytics/learning-analytics/certification/{userId}/{courseId}
Authorization: Bearer {token}
```

---

## Insights API

### List Insights

```http
GET /api/analytics/insights
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "type": "trend",
      "title": "Engagement Increase",
      "description": "User engagement has increased by 25% this month",
      "severity": "info",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "total": 15,
    "unread": 5
  }
}
```

### Get Insight Details

```http
GET /api/analytics/insights/{id}
Authorization: Bearer {token}
```

### Generate Insights

```http
POST /api/analytics/insights/generate
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "type": "cohort",
  "parameters": {
    "cohort_id": 123,
    "metrics": ["retention", "engagement"]
  }
}
```

### Export Insights

```http
POST /api/analytics/insights/export
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "format": "pdf",
  "insight_ids": [123, 124, 125]
}
```

### Dismiss Insight

```http
POST /api/analytics/insights/{id}/dismiss
Authorization: Bearer {token}
```

### Track Insight Feedback

```http
POST /api/analytics/insights/{insightId}/feedback
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "helpful": true,
  "comment": "This insight was very useful"
}
```

---

## External Integrations

### Google Analytics Integration

#### Get Google Analytics Report

```http
POST /api/analytics/external-integrations/google-analytics/report
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "metrics": ["sessions", "users", "pageviews"],
  "dimensions": ["date"],
  "start_date": "2024-01-01",
  "end_date": "2024-01-31"
}
```

#### Get Google Analytics Realtime Data

```http
GET /api/analytics/external-integrations/google-analytics/realtime
Authorization: Bearer {token}
```

#### Create Google Analytics Goal

```http
POST /api/analytics/external-integrations/google-analytics/goals
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Donation Completed",
  "conditions": [
    {"dimension": "event_category", "match": "equals", "value": "donation"}
  ],
  "conversion_value": 50.00
}
```

### Matomo Analytics Integration

#### Track Matomo Event

```http
POST /api/analytics/matomo/track
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_category": "user_action",
  "event_action": "click",
  "event_name": "donate_button"
}
```

#### Get Matomo Report

```http
POST /api/analytics/external-integrations/matomo/report
Authorization: Bearer {token}
Content-Type: application/json
```

#### Get Matomo Realtime Data

```http
GET /api/analytics/external-integrations/matomo/realtime
Authorization: Bearer {token}
```

### Sync Status

```http
GET /api/analytics/external-integrations/sync-status
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "google_analytics": {
      "status": "connected",
      "last_sync": "2024-01-15T10:30:00Z",
      "records_synced": 15420
    },
    "matomo": {
      "status": "connected",
      "last_sync": "2024-01-15T10:25:00Z",
      "records_synced": 8934
    }
  }
}
```

---

## Career Outcome Analytics

### Get Career Analytics Overview

```http
GET /api/career-analytics
Authorization: Bearer {token}
```

### Get Career Analytics Overview Summary

```http
GET /api/career-analytics/overview
Authorization: Bearer {token}
```

### Get Program Effectiveness

```http
GET /api/career-analytics/program-effectiveness
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| program_id | integer | No | Filter by program |
| start_date | date | No | Start date |
| end_date | date | No | End date |

### Generate Program Effectiveness Report

```http
POST /api/career-analytics/program-effectiveness/generate
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "program_id": 123,
  "output_format": "pdf"
}
```

### Get Salary Analysis

```http
GET /api/career-analytics/salary-analysis
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "average_salary": 87500,
    "median_salary": 82000,
    "salary_by_industry": [
      {"industry": "Technology", "avg_salary": 105000},
      {"industry": "Finance", "avg_salary": 95000}
    ],
    "salary_trends": [
      {"year": 2023, "avg_salary": 85000}
    ]
  }
}
```

### Get Industry Placement

```http
GET /api/career-analytics/industry-placement
Authorization: Bearer {token}
```

### Generate Industry Placement Report

```http
POST /api/career-analytics/industry-placement/generate
Authorization: Bearer {token}
Content-Type: application/json
```

### Get Demographic Outcomes

```http
GET /api/career-analytics/demographic-outcomes
Authorization: Bearer {token}
```

### Get Career Path Analysis

```http
GET /api/career-analytics/career-path-analysis
Authorization: Bearer {token}
```

### Get Trend Analysis

```http
GET /api/career-analytics/trend-analysis
Authorization: Bearer {token}
```

### Generate Career Analytics Snapshot

```http
POST /api/career-analytics/generate-snapshot
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "type": "comprehensive",
  "timeframe": "annual",
  "include_recommendations": true
}
```

### List Career Analytics Snapshots

```http
GET /api/career-analytics/snapshots
Authorization: Bearer {token}
```

### Get Filter Options

```http
GET /api/career-analytics/filter-options
Authorization: Bearer {token}
```

### Export Career Analytics

```http
POST /api/career-analytics/export
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "format": "csv",
  "metrics": ["employment_rate", "salary", "satisfaction"]
}
```

---

## Fundraising Analytics

### Get Fundraising Dashboard

```http
GET /api/fundraising-analytics/dashboard
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_raised": 156700,
    "goal": 200000,
    "donors": 1250,
    "avg_donation": 125.36,
    "campaigns": [
      {
        "id": 123,
        "name": "Annual Fund 2024",
        "raised": 45600,
        "goal": 50000
      }
    ]
  }
}
```

### Get Giving Patterns

```http
GET /api/fundraising-analytics/giving-patterns
Authorization: Bearer {token}
```

### Get Campaign Performance

```http
GET /api/fundraising-analytics/campaigns/{campaignId}/performance
Authorization: Bearer {token}
```

### Get Donor Analytics

```http
GET /api/fundraising-analytics/donor-analytics
Authorization: Bearer {token}
```

### Get Predictive Analytics

```http
GET /api/fundraising-analytics/predictive-analytics
Authorization: Bearer {token}
```

### Get ROI Metrics

```http
GET /api/fundraising-analytics/roi-metrics
Authorization: Bearer {token}
```

### Get Donor Engagement

```http
GET /api/fundraising-analytics/donor-engagement
Authorization: Bearer {token}
```

### Get Fundraising Trends

```http
GET /api/fundraising-analytics/trends
Authorization: Bearer {token}
```

### Export Fundraising Analytics

```http
POST /api/fundraising-analytics/export
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "format": "pdf",
  "report_type": "comprehensive",
  "date_range": {
    "start": "2024-01-01",
    "end": "2024-12-31"
  }
}
```

---

## Performance Monitoring

### Store Performance Metrics

```http
POST /api/performance/metrics
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "page_url": "/dashboard",
  "metrics": {
    "first_paint": 1200,
    "first_contentful_paint": 1400,
    "largest_contentful_paint": 2800,
    "dom_content_loaded": 1800,
    "load_complete": 3200
  },
  "device": {
    "type": "desktop",
    "browser": "chrome",
    "version": "120.0.0"
  }
}
```

### Get Performance Analytics

```http
GET /api/performance/analytics
Authorization: Bearer {token}
```

### Get Core Web Vitals

```http
GET /api/performance/core-web-vitals
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "scores": {
      "largest_contentful_paint": "good",
      "first_input_delay": "needs_improvement",
      "cumulative_layout_shift": "good"
    },
    "metrics": {
      "largest_contentful_paint": {
        "value": 2150,
        "rating": "good",
        "p75": 2500,
        "improvement_suggestions": []
      }
    }
  }
}
```

---

## Email Analytics

### Get Email Performance

```http
GET /api/analytics/email/performance
Authorization: Bearer {token}
```

### Get Email Funnel

```http
GET /api/analytics/email/funnel
Authorization: Bearer {token}
```

### Get Email Engagement

```http
GET /api/analytics/email/engagement
Authorization: Bearer {token}
```

### Get Email A/B Test Results

```http
GET /api/analytics/email/ab-test
Authorization: Bearer {token}
```

### Get Email Realtime Data

```http
GET /api/analytics/email/realtime
Authorization: Bearer {token}
```

### Track Email Event

```http
POST /api/analytics/email/track
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_type": "open",
  "email_id": 123,
  "user_id": 456,
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Get Email Dashboard

```http
GET /api/analytics/email/dashboard
Authorization: Bearer {token}
```

---

## Heat Maps

### Get Heat Map Data

```http
GET /api/analytics/heatmaps/{pageUrl}
Authorization: Bearer {token}
```

### Generate Heat Map

```http
POST /api/analytics/heatmaps/generate
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "page_url": "/homepage",
  "heatmap_type": "click",
  "timeframe": "7_days"
}
```

---

## Rate Limits for Analytics Endpoints

| Endpoint Category | Rate Limit |
|-----------------|------------|
| Core Analytics | 100 requests/hour |
| Cohort Analysis | 50 requests/hour |
| Attribution | 100 requests/hour |
| Custom Events | 200 requests/hour |
| Learning Analytics | 100 requests/hour |
| Fundraising Analytics | 50 requests/hour |
| Performance Metrics | 500 requests/hour |

---

## Error Responses

All analytics endpoints return errors in the standard format:

```json
{
  "success": false,
  "error": {
    "code": "ANALYTICS_ERROR",
    "message": "Failed to generate analytics report",
    "details": {
      "reason": "Insufficient data for the requested timeframe"
    }
  }
}
```

### Common Analytics Error Codes

| Error Code | Description |
|------------|-------------|
| `INSUFFICIENT_DATA` | Not enough data for analysis |
| `INVALID_TIMEFRAME` | Invalid date range specified |
| `ANALYSIS_IN_PROGRESS` | Analysis is still processing |
| `EXPORT_FAILED` | Failed to export data |
| `SYNC_IN_PROGRESS` | External sync in progress |

---

## Support

For analytics API support, contact: analytics-support@alumnate.edu
