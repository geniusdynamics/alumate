# Alumni Platform JavaScript SDK

Official JavaScript/TypeScript SDK for the Alumni Platform API.

## Installation

```bash
npm install @alumni-platform/api-client
```

## Quick Start

```javascript
import { AlumniPlatformAPI } from '@alumni-platform/api-client';

const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',
  token: 'your-access-token'
});

// Get user profile
const user = await api.getUser();

// Get timeline
const timeline = await api.getTimeline();

// Create a post
const post = await api.createPost({
  content: 'Hello from the SDK!',
  visibility: 'public'
});

// Search alumni
const alumni = await api.searchAlumni({
  industry: 'technology',
  location: 'San Francisco'
});
```

## Configuration

```javascript
const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',  // Required: Your API base URL
  token: 'your-access-token',              // Required: Your API token
  timeout: 30000                           // Optional: Request timeout in ms (default: 30000)
});
```

## API Methods

### User

- `getUser()` - Get current user profile

### Timeline & Posts

- `getTimeline(page?, perPage?)` - Get personalized timeline
- `refreshTimeline()` - Refresh timeline with latest posts
- `createPost(data)` - Create a new post
- `likePost(postId)` - Like a post
- `commentOnPost(postId, content)` - Comment on a post

### Alumni Directory

- `searchAlumni(filters)` - Search alumni directory
- `connectWithAlumni(userId, message?)` - Send connection request

### Career & Jobs

- `getJobRecommendations(filters?)` - Get personalized job recommendations
- `applyForJob(jobId, applicationData)` - Apply for a job

### Events

- `getEvents(filters?)` - Get events list
- `registerForEvent(eventId)` - Register for an event

### Mentorship

- `requestMentorship(data)` - Request mentorship
- `getMentorships()` - Get user's mentorships

### Webhooks

- `createWebhook(data)` - Create a webhook
- `getWebhooks()` - Get user's webhooks
- `deleteWebhook(webhookId)` - Delete a webhook

### Notifications

- `getNotifications(page?)` - Get notifications
- `markNotificationAsRead(notificationId)` - Mark notification as read
- `markAllNotificationsAsRead()` - Mark all notifications as read

## Error Handling

The SDK automatically handles common errors:

```javascript
try {
  const timeline = await api.getTimeline();
} catch (error) {
  if (error.message.includes('Authentication failed')) {
    // Handle authentication error
  } else if (error.message.includes('Rate limit exceeded')) {
    // Handle rate limiting
  } else {
    // Handle other errors
  }
}
```

## TypeScript Support

The SDK is written in TypeScript and includes full type definitions:

```typescript
import { AlumniPlatformAPI, User, Post } from '@alumni-platform/api-client';

const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',
  token: 'your-access-token'
});

const user: User = await api.getUser();
const timeline: PaginatedResponse<Post> = await api.getTimeline();
```

## Examples

### Search and Connect with Alumni

```javascript
// Search for alumni in tech industry
const techAlumni = await api.searchAlumni({
  industry: 'technology',
  location: 'San Francisco',
  graduation_year: 2020
});

// Connect with an alumnus
await api.connectWithAlumni(techAlumni.data[0].id, 'Hi! I saw we both work in tech. Would love to connect!');
```

### Job Search and Application

```javascript
// Get job recommendations
const jobs = await api.getJobRecommendations({
  industry: 'technology',
  location: 'remote'
});

// Apply for a job
await api.applyForJob(jobs[0].id, {
  cover_letter: 'I am very interested in this position...',
  resume_url: 'https://example.com/my-resume.pdf'
});
```

### Event Management

```javascript
// Get upcoming events
const events = await api.getEvents({
  upcoming: true,
  type: 'networking'
});

// Register for an event
await api.registerForEvent(events.data[0].id);
```

### Webhook Management

```javascript
// Create a webhook
const webhook = await api.createWebhook({
  url: 'https://your-app.com/webhooks/alumni-platform',
  events: ['user.created', 'post.created', 'connection.created'],
  name: 'My App Webhook'
});

// List webhooks
const webhooks = await api.getWebhooks();

// Delete a webhook
await api.deleteWebhook(webhook.id);
```

## Rate Limiting

The API has rate limits in place:

- General API: 1000 requests per hour
- Search API: 100 requests per hour
- Upload API: 50 requests per hour
- Social interactions: 500 requests per hour

The SDK will automatically throw an error when rate limits are exceeded.

## Support

- Documentation: <https://docs.alumni-platform.com>
- GitHub: <https://github.com/alumni-platform/js-sdk>
- Issues: <https://github.com/alumni-platform/js-sdk/issues>

## License

MIT
