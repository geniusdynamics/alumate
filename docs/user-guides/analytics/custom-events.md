# Custom Events User Guide

## Overview

Custom events allow you to track business-specific actions beyond standard analytics. This guide explains how to define, track, and analyze custom events to gain deeper insights into user behavior and business metrics.

## Table of Contents

1. [Introduction to Custom Events](#introduction-to-custom-events)
2. [Creating Event Definitions](#creating-event-definitions)
3. [Tracking Events](#tracking-events)
4. [Event Aggregation](#event-aggregation)
5. [Conversion Events](#conversion-events)
6. [Managing Event Definitions](#managing-event-definitions)
7. [Best Practices](#best-practices)

---

## Introduction to Custom Events

### What are Custom Events?

Custom events are user-defined tracking events that capture specific business actions not covered by standard analytics. They allow you to track:

- **Business Milestones**: Key achievements and goals
- **Feature Interactions**: Usage of specific features
- **User Actions**: Important user behaviors
- **Process Completions**: Workflow and process milestones

### Benefits of Custom Events

| Benefit | Description |
|---------|-------------|
| **Specificity** | Track exactly what matters to your business |
| **Flexibility** | Define events that match your terminology |
| **Actionable Data** | Focus on meaningful metrics |
| **Integration** | Connect with attribution and cohorts |
| **Custom Reports** | Build reports around custom metrics |

### Standard vs. Custom Events

| Aspect | Standard Events | Custom Events |
|--------|-----------------|---------------|
| **Predefined** | Yes | No |
| **Flexibility** | Limited | Highly flexible |
| **Setup Required** | None | Event definition |
| **Use Case** | Generic tracking | Specific business needs |
| **Examples** | Page views, clicks | Course completion, badge earned |

---

## Creating Event Definitions

### Event Definition Components

Before tracking an event, you must define its structure:

```json
{
  "event_definition": {
    "name": "course_completion",
    "description": "Track when a user completes a course",
    "parameters": [
      {
        "name": "course_id",
        "type": "number",
        "required": true,
        "description": "The ID of the completed course"
      },
      {
        "name": "completion_score",
        "type": "number",
        "required": true,
        "description": "Score achieved on completion"
      },
      {
        "name": "time_spent_minutes",
        "type": "number",
        "required": false,
        "description": "Time spent completing the course"
      },
      {
        "name": "certificate_earned",
        "type": "boolean",
        "required": false,
        "description": "Whether a certificate was earned"
      }
    ]
  }
}
```

### Parameter Types

| Type | Description | Example Values |
|------|-------------|----------------|
| **string** | Text values | "course_name", "category" |
| **number** | Numeric values | 85.5, 100, -5 |
| **boolean** | True/false | true, false |

### Naming Conventions

Follow these naming rules:

1. **Use Snake Case**: `course_completion`, `badge_earned`
2. **Start with Letter or Underscore**: Cannot start with number
3. **Alphanumeric + Underscores Only**: No special characters
4. **Be Descriptive**: Name should clearly indicate the event
5. **Use Past Tense**: `badge_earned` (not `badge_earn`)

### Creating a New Event Definition

1. Navigate to **Analytics** > **Custom Events**
2. Click **+ Create Event Definition**
3. Fill in the form:

**Required Fields:**
- **Event Name**: Unique identifier
- **Description**: Detailed explanation of the event
- **Parameters**: Define what data to collect

**Example:**
```
Event Name: graduate_profile_complete
Description: Triggered when a graduate completes their profile
Parameters:
  - profile_section (string, required)
  - completion_percentage (number, required)
  - time_to_complete (number, optional)
```

### Event Definition Examples

#### Example 1: Career Milestone

```json
{
  "name": "career_milestone",
  "description": "Track career milestones achieved by graduates",
  "parameters": [
    {"name": "milestone_type", "type": "string", "required": true},
    {"name": "company_name", "type": "string", "required": false},
    {"name": "job_title", "type": "string", "required": false},
    {"name": "years_in_position", "type": "number", "required": false}
  ]
}
```

#### Example 2: Gamification Achievement

```json
{
  "name": "badge_earned",
  "description": "Track gamification badge achievements",
  "parameters": [
    {"name": "badge_id", "type": "number", "required": true},
    {"name": "badge_name", "type": "string", "required": true},
    {"name": "badge_tier", "type": "string", "required": true},
    {"name": "points_awarded", "type": "number", "required": false}
  ]
}
```

#### Example 3: Course Interaction

```json
{
  "name": "module_completed",
  "description": "Track course module completions",
  "parameters": [
    {"name": "module_id", "type": "number", "required": true},
    {"name": "course_id", "type": "number", "required": true},
    {"name": "score", "type": "number", "required": false},
    {"name": "time_spent_seconds", "type": "number", "required": false},
    {"name": "attempt_number", "type": "number", "required": false}
  ]
}
```

---

## Tracking Events

### Event Tracking Methods

Events can be tracked through:

1. **Frontend SDK**: JavaScript library for web/mobile
2. **Server-side API**: Direct API calls from your backend
3. **Mobile SDK**: Native mobile tracking
4. **Webhook Integration**: Receive events from external systems

### Frontend Tracking

```javascript
// Example: Track course completion
analytics.track('course_completion', {
  course_id: 12345,
  completion_score: 92,
  time_spent_minutes: 45,
  certificate_earned: true
});
```

### Server-Side Tracking

```php
// Example: PHP server-side tracking
$eventService = app(CustomEventService::class);

$eventService->trackEvent([
  'definition_id' => $definitionId,
  'user_id' => $userId,
  'data_json' => [
    'course_id' => 12345,
    'completion_score' => 92,
    'time_spent_minutes' => 45
  ],
  'timestamp' => now()
]);
```

### Required Parameters for Tracking

| Parameter | Type | Description |
|-----------|------|-------------|
| `definition_id` | number | ID of the event definition |
| `user_id` | number | User who triggered the event |
| `data_json` | object | Event-specific data matching definition |
| `timestamp` | datetime | When the event occurred (optional) |

### Tracking Best Practices

1. **Track in Real-Time**: Record events as they happen
2. **Include Context**: Add relevant metadata
3. **Validate Data**: Ensure values match parameter types
4. **Handle Errors**: Log and handle tracking failures
5. **Respect Privacy**: Only track with user consent

### Consent Requirements

Events are only tracked for users who have:
- Provided analytics consent
- Not revoked consent since

Users without consent will not have events recorded.

---

## Event Aggregation

### Viewing Aggregated Data

Access aggregated event data from **Analytics** > **Custom Events** > **Event Name**:

```json
{
  "event_aggregation": {
    "definition_id": 123,
    "total_events": 15420,
    "unique_users": 8420,
    "aggregates": {
      "completion_score": {
        "count": 15420,
        "sum": 1387800,
        "avg": 90.0,
        "min": 45,
        "max": 100
      },
      "certificate_earned": {
        "unique_values": 2,
        "top_values": {
          "true": 12500,
          "false": 2920
        }
      }
    },
    "time_series": [
      {"date": "2024-03-01", "count": 520, "unique_users": 480},
      {"date": "2024-03-02", "count": 485, "unique_users": 445}
    ]
  }
}
```

### Aggregation Types

| Parameter Type | Available Aggregates |
|----------------|---------------------|
| **number** | count, sum, avg, min, max |
| **string** | unique_values, top_values |
| **boolean** | true_count, false_count |

### Time Series Analysis

View how events trend over time:

| Date | Events | Unique Users | Trend |
|------|--------|--------------|-------|
| 2024-03-01 | 520 | 480 | ↑ 8% |
| 2024-03-02 | 485 | 445 | ↓ 7% |
| 2024-03-03 | 510 | 470 | ↑ 5% |
| 2024-03-04 | 495 | 455 | ↓ 3% |

### Filtering Aggregated Data

Apply filters to refine analysis:

```json
{
  "filters": {
    "user_id": null,  // No filter
    "start_date": "2024-01-01",
    "end_date": "2024-03-31",
    "parameters": {
      "course_id": 12345
    }
  }
}
```

### Large-Scale Aggregation

For large datasets, queue aggregation jobs:

1. Navigate to **Custom Events** > **Event Name**
2. Click **Queue Aggregation**
3. Configure filters and options
4. Receive notification when complete

---

## Conversion Events

### Defining Conversion Events

Mark specific events as conversions for attribution:

```json
{
  "conversion_events": [
    "course_completion",
    "badge_earned",
    "profile_complete",
    "job_application_submit"
  ]
}
```

### Integration with Attribution

Conversion events automatically integrate with attribution:

```
User Journey:
Social Ad → Email → Course Enrollment → Completion → Badge Earned

Conversion Events:
✓ Course Enrollment (Conversion 1)
✓ Course Completion (Conversion 2)
✓ Badge Earned (Conversion 3)
```

### Conversion Rate Calculation

Track conversion rates for custom events:

| Event | Total Events | Conversions | Rate |
|-------|--------------|-------------|------|
| Profile Start | 10,000 | - | - |
| Profile Complete | 8,500 | 8,500 | 85% |
| Course Start | 6,000 | 6,000 | 71% |
| Course Complete | 4,800 | 4,800 | 80% |
| Badge Earned | 3,600 | 3,600 | 75% |

### Funnel Analysis

Build funnels around custom events:

```json
{
  "funnel_analysis": {
    "name": "Course Completion Funnel",
    "steps": [
      {"event": "course_enrollment", "users": 10000},
      {"event": "module_start", "users": 8500},
      {"event": "module_complete", "users": 7200},
      {"event": "course_complete", "users": 6000}
    ],
    "overall_conversion": "60%",
    "biggest_drop": "Module Start → Complete"
  }
}
```

---

## Managing Event Definitions

### Viewing Event Definitions

1. Navigate to **Analytics** > **Custom Events**
2. View all event definitions in the list
3. Filter by status: Active, Inactive

### Editing Event Definitions

1. Click the event name in the list
2. Click **Edit Definition**
3. Modify name, description, or parameters
4. **Note**: Cannot change parameter types after creation

### Deactivating Events

Instead of deleting, deactivate events:

1. Open event definition
2. Set **Status** to **Inactive**
3. Historical data is preserved
4. No new events will be tracked

### Parameter Validation

Events are validated against definitions:

| Validation Rule | Example |
|-----------------|---------|
| Required parameters | Must be present |
| Type matching | Numbers must be numeric |
| Valid values | Within expected ranges |

### Exporting Event Data

Export custom event data for external analysis:

1. Navigate to **Custom Events** > **Event Name**
2. Click **Export**
3. Select format: CSV, JSON, Excel
4. Configure date range and filters
5. Download exported data

---

## Best Practices

### Event Design

1. **Be Specific**: Define clear, actionable events
2. **Avoid Over-Tracking**: Track what matters, not everything
3. **Use Consistent Naming**: Follow naming conventions
4. **Document Events**: Maintain event documentation
5. **Plan for Scale**: Consider future analysis needs

### Data Collection

1. **Track in Context**: Include relevant metadata
2. **Use Appropriate Types**: Match data to types correctly
3. **Handle Missing Data**: Mark optional vs. required
4. **Validate Early**: Catch errors at the source
5. **Test Tracking**: Verify events are recorded correctly

### Privacy Compliance

1. **Obtain Consent**: Only track with user permission
2. **Minimize PII**: Avoid collecting personal data
3. **Respect Preferences**: Honor opt-out requests
4. **Data Retention**: Delete old event data
5. **Audit Access**: Monitor who views event data

### Common Use Cases

| Use Case | Example Events |
|----------|---------------|
| **Education** | `course_enrolled`, `module_completed`, `quiz_passed` |
| **Career** | `job_applied`, `interview_scheduled`, `offer_received` |
| **Engagement** | `post_created`, `comment_added`, `connection_made` |
| **Gamification** | `badge_earned`, `points_accumulated`, `level_up` |

### Event Naming Examples

| Good Name | Poor Name | Why |
|-----------|-----------|-----|
| `course_completed` | `done` | Descriptive, specific |
| `profile_section_updated` | `update` | Contextual, past tense |
| `job_application_submit` | `apply` | Business-relevant |

### Performance Considerations

1. **Batch Events**: Send multiple events together
2. **Cache Locally**: Buffer events in client-side storage
3. **Handle Offline**: Queue events when disconnected
4. **Limit Payload**: Keep event data minimal
5. **Monitor Volume**: Track event volume trends

---

## Troubleshooting

### Events Not Tracking

**Issue**: Events appear not to be recorded

**Solutions:**
1. Verify event definition exists and is active
2. Check user has granted analytics consent
3. Validate parameter types match definition
4. Check browser console for errors
5. Verify network connectivity

### Data Discrepancies

**Issue**: Event counts differ from expected

**Possible Causes:**
- Event tracking delays
- Duplicate event filtering
- Consent revocation
- Parameter validation failures

**Solution**: Review event logs and validation results

### Parameter Validation Errors

**Issue**: Events fail validation

**Common Errors:**
- Missing required parameter
- Invalid parameter type
- Value out of range

**Solution**: Check event data against definition

### Attribution Not Working

**Issue**: Custom events not showing in attribution

**Solutions:**
1. Verify event is marked as conversion event
2. Check attribution window settings
3. Ensure user has consistent identifier
4. Review consent status

---

## Additional Resources

- [Analytics Dashboard Guide](analytics-dashboard.md)
- [Cohort Analysis Guide](cohort-analysis.md)
- [Attribution Analysis Guide](attribution-analysis.md)
- [Insights Dashboard Guide](insights-dashboard.md)
- [Privacy Management Guide](privacy-management.md)
- [Learning Analytics Guide](learning-analytics.md)
- [Troubleshooting Guide](troubleshooting.md)

---

For additional support, contact the analytics team at analytics-support@alumni-platform.com
