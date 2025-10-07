# Learning Analytics System

## Overview

The Learning Analytics System provides comprehensive tracking and analysis of user learning progress, engagement metrics, and certification achievements. It integrates with the broader analytics platform to provide insights into learning effectiveness and career development.

## Features

### Core Functionality

- **Progress Tracking**: Monitor individual user progress through courses and modules
- **Engagement Scoring**: Calculate engagement scores using weighted formula (duration*0.4 + interactions*0.3 + completion*0.3)
- **Certification Management**: Track and verify certification eligibility and achievements
- **Real-time Updates**: WebSocket integration for live progress updates
- **Privacy Compliance**: GDPR/CCPA compliant with consent management

### Engagement Score Formula

The engagement score is calculated using the following weighted formula:

```
Engagement Score = (Duration Score × 0.4) + (Interactions Score × 0.3) + (Completion Score × 0.3)
```

Where:
- **Duration Score**: `min(engagement_duration / 600, 1) × 100` (max 10 hours = 600 minutes)
- **Interactions Score**: `min(interactions_count / 100, 1) × 100` (max 100 interactions)
- **Completion Score**: `progress_percentage` (already 0-100)

### Certification Impact

Certifications provide career impact scores based on certification level:
- **Advanced/Expert**: 15 points
- **Intermediate**: 10 points
- **Beginner/Fundamental**: 5 points

## API Endpoints

### Learning Progress

```http
GET /api/analytics/learning
POST /api/analytics/learning
GET /api/analytics/learning/{userId}/{courseId}
PATCH /api/analytics/learning/{userId}
```

### Learning Insights

```http
GET /api/analytics/learning/insights
POST /api/analytics/learning/certify
```

## Database Schema

### learning_progress Table

| Column | Type | Description |
|--------|------|-------------|
| tenant_id | string | Tenant isolation |
| user_id | foreignId | User reference |
| course_id | foreignId | Course reference |
| module_id | foreignId | Current module |
| progress_percentage | decimal | 0-100 completion |
| engagement_duration | integer | Minutes spent |
| completion_timestamp | timestamp | When completed |
| certifications | json | Certification data |
| interactions_count | integer | Total interactions |
| modules_completed | integer | Modules finished |
| total_score | decimal | Overall score |
| engagement_score | decimal | Calculated score |
| certified | boolean | Certification status |

### learning_events Table

| Column | Type | Description |
|--------|------|-------------|
| tenant_id | string | Tenant isolation |
| learning_progress_id | foreignId | Progress reference |
| event_type | string | Event category |
| event_data | json | Event details |
| timestamp | datetime | When occurred |
| duration | integer | Event duration |
| metadata | json | Additional data |

## Frontend Components

### LearningDashboard.vue

Main dashboard component featuring:
- Progress visualization with radial charts
- Engagement timeline with line charts
- Certification badges display
- Real-time WebSocket updates
- Filter controls for date/course/user

### useLearningStore.ts

Pinia store managing:
- Learning progress state
- API interactions
- Filtering and sorting
- Real-time updates

### useLearning.ts

Composable providing:
- WebSocket connection management
- Real-time update handling
- Filtering utilities
- Certification verification

## Privacy & Consent

### Consent Integration

- **Check before tracking**: All learning data collection requires explicit consent
- **Audit logging**: Privacy events are logged for compliance
- **Data purge**: Consent revocation triggers immediate data removal
- **Anonymized aggregates**: Non-consented users see only aggregate statistics

### CCPA Compliance

- **Opt-out handling**: California users can opt-out of learning analytics
- **Data export**: Users can request export of their learning data
- **Data deletion**: Complete removal of learning records on request

## Background Processing

### LearningScoreJob

Background job for batch processing:
- **Chunked processing**: Handles large datasets efficiently
- **Error recovery**: 3 retry attempts with backoff
- **Tenant isolation**: Processes data per tenant
- **Insights generation**: Triggers insights when scores change significantly (>10%)

### Queue Configuration

```php
'learning' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'learning',
    'retry_after' => 300,
    'block_for' => 60,
],
```

## Testing

### Unit Tests

- **LearningAnalyticsServiceTest**: 86% coverage
  - Engagement score calculations
  - Certification verification
  - Consent integration
  - Batch processing

### Feature Tests

- **LearningApiTest**: 85% coverage
  - API endpoint validation
  - Middleware integration
  - Tenant isolation
  - Consent enforcement

### Integration Tests

- **LearningPrivacyIntegrationTest**: Consent revocation and data purge verification

### Frontend Tests

- **LearningDashboard.test.ts**: 90% coverage
  - Component rendering
  - Real-time updates
  - Accessibility compliance

## Configuration

### Environment Variables

```env
# Learning Analytics Configuration
LEARNING_ENGAGEMENT_WEIGHT_DURATION=0.4
LEARNING_ENGAGEMENT_WEIGHT_INTERACTIONS=0.3
LEARNING_ENGAGEMENT_WEIGHT_COMPLETION=0.3
LEARNING_CACHE_TTL=60
LEARNING_BATCH_SIZE=100
```

### Cache Configuration

```php
'learning_score' => [
    'ttl' => env('LEARNING_CACHE_TTL', 60),
    'prefix' => 'learning:score:',
],
```

## Performance Considerations

### Caching Strategy

- **Engagement scores**: Cached for 60 minutes with Redis
- **Progress data**: Cached per user/course combination
- **Insights**: Cached with automatic invalidation on updates

### Database Optimization

- **Composite indexes**: `tenant_id + user_id`, `tenant_id + course_id`
- **Partitioning**: Consider partitioning by tenant for large deployments
- **Archiving**: Automatic archiving of completed learning records

### Scalability

- **Horizontal scaling**: Queue-based processing allows multiple workers
- **Read replicas**: Analytics queries can use read replicas
- **CDN integration**: Static assets served via CDN

## Monitoring & Alerts

### Key Metrics

- **Engagement score distribution**: Track average engagement across courses
- **Certification rates**: Monitor completion and certification trends
- **Consent compliance**: Track consent grant/revoke rates
- **Performance**: API response times and job processing duration

### Alerts

- **Low engagement**: Automatic alerts for courses with <30% average engagement
- **Consent issues**: Alerts when consent rates drop below threshold
- **Performance degradation**: Response time monitoring with thresholds

## Troubleshooting

### Common Issues

1. **WebSocket disconnections**: Check network connectivity and server configuration
2. **Consent blocking**: Verify consent service integration and user permissions
3. **Cache invalidation**: Ensure cache keys are properly cleared on updates
4. **Job failures**: Check queue configuration and worker processes

### Debug Commands

```bash
# Check learning progress for user
php artisan tinker
>>> App\Models\LearningProgress::byUser(123)->get()

# Clear learning cache
php artisan cache:clear
php artisan cache:forget learning_score_123_456

# Check queue status
php artisan queue:status learning
```

## Future Enhancements

### Planned Features

- **AI-powered recommendations**: Machine learning for personalized learning paths
- **Peer comparison**: Anonymous comparison with similar learners
- **Gamification**: Achievement system for learning milestones
- **Mobile app integration**: Native mobile learning tracking
- **Advanced analytics**: Predictive modeling for learning outcomes