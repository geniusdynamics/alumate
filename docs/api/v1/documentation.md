# Modern Alumni Platform API Documentation v1.0

## Overview

The Modern Alumni Platform API provides comprehensive access to all platform features including social networking, career development, events management, fundraising, and analytics. This RESTful API is designed for external integrations, mobile applications, and third-party services.

## Base URL

```
https://your-domain.com/api
```

## Authentication

The API uses Laravel Sanctum for authentication. All endpoints require authentication unless otherwise specified.

### Authentication Methods

1. **Bearer Token Authentication**
   ```http
   Authorization: Bearer {your-token}
   ```

2. **Session Authentication** (for web applications)
   - CSRF token required in headers: `X-CSRF-TOKEN`

### Obtaining Access Tokens

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com"
    }
}
```

## Rate Limiting

- **General API**: 60 requests per minute per user
- **Search API**: 30 requests per minute per user
- **Upload API**: 10 requests per minute per user
- **Webhook API**: 100 requests per minute per webhook

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Request limit per window
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Unix timestamp when the rate limit resets

## Response Format

All API responses follow a consistent JSON structure:

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100
        }
    }
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "email": ["The email field is required."]
        }
    }
}
```

## API Versioning

The API uses URL-based versioning. Current version is v1.

- Current: `/api/v1/...`
- Future: `/api/v2/...`

Version compatibility is maintained for at least 12 months after a new version release.

## Core Endpoints

### Health Check

```http
GET /api/ping
```

Returns API status and timestamp.

### User Profile

```http
GET /api/user
Authorization: Bearer {token}
```

Returns authenticated user information.

## Social Features

### Posts

#### Create Post
```http
POST /api/posts
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Excited to share my new role!",
    "media_urls": ["https://example.com/image.jpg"],
    "visibility": "circles",
    "circle_ids": [1, 2],
    "group_ids": [3]
}
```

#### Get Timeline
```http
GET /api/timeline?page=1&per_page=15
Authorization: Bearer {token}
```

#### Engage with Posts
```http
POST /api/posts/{id}/like
POST /api/posts/{id}/comment
POST /api/posts/{id}/share
POST /api/posts/{id}/reaction
```

### Alumni Directory

#### Search Alumni
```http
GET /api/alumni?search=john&graduation_year=2020&industry=tech
Authorization: Bearer {token}
```

#### Connect with Alumni
```http
POST /api/alumni/{userId}/connect
Authorization: Bearer {token}
Content-Type: application/json

{
    "message": "Hi! I'd love to connect with a fellow alumnus."
}
```

## Career Development

### Job Matching

#### Get Job Recommendations
```http
GET /api/jobs/recommendations?page=1&per_page=10
Authorization: Bearer {token}
```

#### Apply for Job
```http
POST /api/jobs/{jobId}/apply
Authorization: Bearer {token}
Content-Type: application/json

{
    "cover_letter": "I'm excited about this opportunity...",
    "resume_url": "https://example.com/resume.pdf"
}
```

### Mentorship

#### Find Mentors
```http
GET /api/mentorships/find-mentors?industry=tech&experience_level=senior
Authorization: Bearer {token}
```

#### Request Mentorship
```http
POST /api/mentorships/request
Authorization: Bearer {token}
Content-Type: application/json

{
    "mentor_id": 123,
    "message": "I would love to learn from your experience in...",
    "goals": ["Career transition", "Leadership skills"]
}
```

## Events

### Event Management

#### List Events
```http
GET /api/events?type=networking&location=san-francisco&upcoming=true
Authorization: Bearer {token}
```

#### Register for Event
```http
POST /api/events/{eventId}/register
Authorization: Bearer {token}
Content-Type: application/json

{
    "dietary_restrictions": "Vegetarian",
    "accessibility_needs": "Wheelchair access"
}
```

#### Event Check-in
```http
POST /api/events/{eventId}/checkin
Authorization: Bearer {token}
Content-Type: application/json

{
    "location": {
        "latitude": 37.7749,
        "longitude": -122.4194
    }
}
```

## Fundraising

### Campaigns

#### List Campaigns
```http
GET /api/fundraising-campaigns?status=active&category=scholarship
Authorization: Bearer {token}
```

#### Donate to Campaign
```http
POST /api/campaign-donations
Authorization: Bearer {token}
Content-Type: application/json

{
    "campaign_id": 123,
    "amount": 100.00,
    "currency": "USD",
    "payment_method": "stripe",
    "is_anonymous": false,
    "dedication": "In memory of John Smith"
}
```

### Recurring Donations

#### Set up Recurring Donation
```http
POST /api/recurring-donations
Authorization: Bearer {token}
Content-Type: application/json

{
    "campaign_id": 123,
    "amount": 50.00,
    "frequency": "monthly",
    "payment_method": "stripe_pm_123"
}
```

## Analytics

### Engagement Analytics
```http
GET /api/analytics/engagement?period=30d&metric=posts
Authorization: Bearer {token}
```

### Career Analytics
```http
GET /api/analytics/careers?graduation_year=2020&industry=tech
Authorization: Bearer {token}
```

### Fundraising Analytics
```http
GET /api/fundraising-analytics/dashboard?period=1y
Authorization: Bearer {token}
```

## Search

### Advanced Search
```http
POST /api/search
Authorization: Bearer {token}
Content-Type: application/json

{
    "query": "software engineer",
    "filters": {
        "type": "alumni",
        "graduation_year": [2018, 2019, 2020],
        "location": "San Francisco Bay Area",
        "industry": "Technology"
    },
    "sort": "relevance",
    "page": 1,
    "per_page": 20
}
```

### Save Search
```http
POST /api/saved-searches
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Tech Alumni in SF",
    "query": "software engineer",
    "filters": {...},
    "alert_frequency": "weekly"
}
```

## Notifications

### Get Notifications
```http
GET /api/notifications?unread=true&type=connection_request
Authorization: Bearer {token}
```

### Mark as Read
```http
POST /api/notifications/{notificationId}/read
Authorization: Bearer {token}
```

### Update Preferences
```http
PUT /api/notifications/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
    "email_notifications": true,
    "push_notifications": true,
    "digest_frequency": "daily",
    "types": {
        "connection_requests": true,
        "post_likes": false,
        "event_reminders": true
    }
}
```

## Webhooks

Webhooks allow your application to receive real-time notifications when events occur in the platform.

### Supported Events

- `user.created`
- `user.updated`
- `post.created`
- `post.liked`
- `connection.created`
- `event.registered`
- `donation.completed`
- `mentorship.requested`

### Webhook Configuration

```http
POST /api/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
    "url": "https://your-app.com/webhooks/alumni-platform",
    "events": ["post.created", "donation.completed"],
    "secret": "your-webhook-secret"
}
```

### Webhook Payload Example

```json
{
    "id": "evt_123456",
    "event": "post.created",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "post": {
            "id": 789,
            "user_id": 123,
            "content": "Excited to share my new role!",
            "created_at": "2024-01-15T10:30:00Z"
        }
    }
}
```

### Webhook Security

All webhook payloads are signed using HMAC-SHA256. Verify the signature using the `X-Signature` header:

```php
$signature = hash_hmac('sha256', $payload, $webhook_secret);
$expected = 'sha256=' . $signature;
$received = $_SERVER['HTTP_X_SIGNATURE'];

if (!hash_equals($expected, $received)) {
    // Invalid signature
}
```

## Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `AUTHENTICATION_REQUIRED` | Authentication token required |
| `AUTHORIZATION_FAILED` | Insufficient permissions |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `SERVER_ERROR` | Internal server error |
| `MAINTENANCE_MODE` | API temporarily unavailable |

## SDKs and Libraries

### JavaScript/Node.js
```bash
npm install @alumni-platform/api-client
```

```javascript
import { AlumniPlatformAPI } from '@alumni-platform/api-client';

const api = new AlumniPlatformAPI({
    baseURL: 'https://your-domain.com/api',
    token: 'your-access-token'
});

// Get timeline
const timeline = await api.posts.getTimeline();

// Create post
const post = await api.posts.create({
    content: 'Hello world!',
    visibility: 'public'
});
```

### PHP
```bash
composer require alumni-platform/api-client
```

```php
use AlumniPlatform\ApiClient\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token'
]);

// Get alumni directory
$alumni = $client->alumni()->search(['industry' => 'tech']);

// Create event
$event = $client->events()->create([
    'title' => 'Tech Networking Night',
    'date' => '2024-02-15',
    'location' => 'San Francisco'
]);
```

### Python
```bash
pip install alumni-platform-api
```

```python
from alumni_platform import AlumniPlatformAPI

api = AlumniPlatformAPI(
    base_url='https://your-domain.com/api',
    token='your-access-token'
)

# Get job recommendations
jobs = api.jobs.get_recommendations()

# Apply for job
application = api.jobs.apply(job_id=123, cover_letter='...')
```

## Testing

### Sandbox Environment

Use the sandbox environment for testing:
- Base URL: `https://sandbox.your-domain.com/api`
- Test data is reset daily
- No real emails or payments are processed

### Test Data

The sandbox includes:
- 100+ test alumni profiles
- Sample posts and interactions
- Mock events and registrations
- Test fundraising campaigns
- Simulated job postings

## Support

- **Documentation**: https://docs.your-domain.com/api
- **Status Page**: https://status.your-domain.com
- **Support Email**: api-support@your-domain.com
- **Developer Forum**: https://community.your-domain.com/developers

## Changelog

### v1.0.0 (2024-01-15)
- Initial API release
- Core social features
- Alumni directory and search
- Career development endpoints
- Events management
- Fundraising capabilities
- Analytics and reporting
- Webhook system

---

*This documentation is automatically updated. Last updated: 2024-01-15*