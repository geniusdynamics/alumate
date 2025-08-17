# Alumni Platform PHP SDK

Official PHP SDK for the Alumni Platform API.

## Installation

Install via Composer:

```bash
composer require alumni-platform/api-client
```

## Quick Start

```php
<?php

use AlumniPlatform\ApiClient\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token'
]);

// Get user profile
$user = $client->getUser();

// Get timeline
$timeline = $client->getTimeline();

// Create a post
$post = $client->createPost([
    'content' => 'Hello from the PHP SDK!',
    'visibility' => 'public'
]);

// Search alumni
$alumni = $client->searchAlumni([
    'industry' => 'technology',
    'location' => 'San Francisco'
]);
```

## Configuration

```php
$client = new Client([
    'base_uri' => 'https://your-domain.com/api',  // Required: Your API base URL
    'token' => 'your-access-token',               // Required: Your API token
    'timeout' => 30                               // Optional: Request timeout in seconds (default: 30)
]);
```

## API Methods

### User

- `getUser()` - Get current user profile

### Timeline & Posts

- `getTimeline($page = 1, $perPage = 20)` - Get personalized timeline
- `createPost(array $data)` - Create a new post
- `likePost(int $postId)` - Like a post
- `commentOnPost(int $postId, string $content)` - Comment on a post

### Alumni Directory

- `searchAlumni(array $filters = [])` - Search alumni directory
- `connectWithAlumni(int $userId, ?string $message = null)` - Send connection request

### Career & Jobs

- `getJobRecommendations(array $filters = [])` - Get personalized job recommendations
- `applyForJob(int $jobId, array $applicationData)` - Apply for a job

### Events

- `getEvents(array $filters = [])` - Get events list
- `registerForEvent(int $eventId)` - Register for an event

### Mentorship

- `requestMentorship(array $data)` - Request mentorship
- `getMentorships()` - Get user's mentorships

### Webhooks

- `createWebhook(array $data)` - Create a webhook
- `getWebhooks()` - Get user's webhooks
- `deleteWebhook(int $webhookId)` - Delete a webhook

### Notifications

- `getNotifications(int $page = 1)` - Get notifications
- `markNotificationAsRead(int $notificationId)` - Mark notification as read
- `markAllNotificationsAsRead()` - Mark all notifications as read

### Utilities

- `ping()` - Ping the API to check connectivity
- `verifyWebhookSignature(string $payload, string $signature, string $secret)` - Verify webhook signature

## Error Handling

The SDK throws `AlumniPlatformException` for API errors:

```php
use AlumniPlatform\ApiClient\AlumniPlatformException;

try {
    $timeline = $client->getTimeline();
} catch (AlumniPlatformException $e) {
    switch ($e->getCode()) {
        case 401:
            // Handle authentication error
            break;
        case 403:
            // Handle authorization error
            break;
        case 404:
            // Handle not found error
            break;
        case 422:
            // Handle validation error
            break;
        case 429:
            // Handle rate limit error
            break;
        default:
            // Handle other errors
            break;
    }
}
```

## Laravel Integration

The SDK includes a Laravel service provider for easy integration:

### Configuration

Add to your `config/services.php`:

```php
'alumni_platform' => [
    'base_uri' => env('ALUMNI_PLATFORM_BASE_URI'),
    'token' => env('ALUMNI_PLATFORM_TOKEN'),
    'timeout' => env('ALUMNI_PLATFORM_TIMEOUT', 30),
],
```

Add to your `.env`:

```env
ALUMNI_PLATFORM_BASE_URI=https://your-domain.com/api
ALUMNI_PLATFORM_TOKEN=your-access-token
ALUMNI_PLATFORM_TIMEOUT=30
```

### Usage in Laravel

```php
<?php

namespace App\Http\Controllers;

use AlumniPlatform\ApiClient\Client;

class AlumniController extends Controller
{
    public function __construct(private Client $alumniClient)
    {
        //
    }

    public function index()
    {
        $alumni = $this->alumniClient->searchAlumni([
            'industry' => 'technology'
        ]);

        return view('alumni.index', compact('alumni'));
    }
}
```

## Examples

### Search and Connect with Alumni

```php
// Search for alumni in tech industry
$techAlumni = $client->searchAlumni([
    'industry' => 'technology',
    'location' => 'San Francisco',
    'graduation_year' => 2020
]);

// Connect with an alumnus
$client->connectWithAlumni(
    $techAlumni['data'][0]['id'], 
    'Hi! I saw we both work in tech. Would love to connect!'
);
```

### Job Search and Application

```php
// Get job recommendations
$jobs = $client->getJobRecommendations([
    'industry' => 'technology',
    'location' => 'remote'
]);

// Apply for a job
$client->applyForJob($jobs[0]['id'], [
    'cover_letter' => 'I am very interested in this position...',
    'resume_url' => 'https://example.com/my-resume.pdf'
]);
```

### Event Management

```php
// Get upcoming events
$events = $client->getEvents([
    'upcoming' => true,
    'type' => 'networking'
]);

// Register for an event
$client->registerForEvent($events['data'][0]['id']);
```

### Webhook Management

```php
// Create a webhook
$webhook = $client->createWebhook([
    'url' => 'https://your-app.com/webhooks/alumni-platform',
    'events' => ['user.created', 'post.created', 'connection.created'],
    'name' => 'My App Webhook'
]);

// List webhooks
$webhooks = $client->getWebhooks();

// Delete a webhook
$client->deleteWebhook($webhook['id']);
```

### Webhook Signature Verification

```php
// In your webhook endpoint
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ALUMNI_PLATFORM_SIGNATURE'] ?? '';
$secret = 'your-webhook-secret';

if (!Client::verifyWebhookSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Process webhook payload
$data = json_decode($payload, true);
// Handle the webhook event...
```

## Rate Limiting

The API has rate limits in place:

- General API: 1000 requests per hour
- Search API: 100 requests per hour
- Upload API: 50 requests per hour
- Social interactions: 500 requests per hour

The SDK will automatically throw an `AlumniPlatformException` with code 429 when rate limits are exceeded.

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client
- Laravel 10+ (optional, for Laravel integration)

## Support

- Documentation: <https://docs.alumni-platform.com>
- GitHub: <https://github.com/alumni-platform/php-sdk>
- Issues: <https://github.com/alumni-platform/php-sdk/issues>

## License

MIT