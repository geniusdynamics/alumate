# Alumni Platform API Documentation

## Overview

The Alumni Platform API is a comprehensive REST API that provides programmatic access to all platform features including alumni networking, career services, events, fundraising, mentoring, and administrative functions.

### Current Version: v1.0.0
**Laravel Framework:** 12.26.4
**PHP Version:** 8.3.23
**Database:** PostgreSQL

## Key Features

- **Multi-tenant Architecture**: Supports multiple institutions with data isolation
- **Real-time Communication**: WebSocket support for live messaging and notifications
- **Performance Monitoring**: Built-in analytics and performance tracking
- **Scalable Architecture**: Designed for high-traffic educational platforms
- **Modern JavaScript Integration**: Vue 3 + TypeScript frontend integration
- **Comprehensive Admin Suite**: Full administrative controls and analytics

## Technology Stack

### Backend
- **Framework**: Laravel 12.26.4
- **Language**: PHP 8.3.23
- **Database**: PostgreSQL 13+
- **Cache/Queue**: Redis (optional)
- **API**: REST with Bearer Token Authentication

### Frontend
- **Framework**: Vue 3.5.20 with Composition API
- **Build Tool**: Vite
- **Styling**: Tailwind CSS 3.4.17
- **Routing**: Inertia.js 2.0.6 for SPA experience

### Additional Integrations
- **Real-time**: Laravel Echo 2.2.0
- **Routing**: Ziggy 2.5.3 for JavaScript route generation
- **Testing**: Pest 3.8.4
- **Social Authentication**: Laravel Socialite 5.23.0
- **Deployment**: Laravel Sail 1.45.0 for Docker

### Installed Packages
- `inertiajs/inertia-laravel: 2.0.6` - SPA integration
- `@inertiajs/vue3: 2.1.3` - Vue.js frontend integration
- `tightenco/ziggy: 2.5.3` - JavaScript route generation
- `laravel/socialite: 5.23.0` - OAuth authentication
- `laravel/echo: 2.2.0` - WebSocket integration
- `tailwindcss: 3.4.17` - Utility-first CSS framework
- `pestphp/pest: 3.8.4` - Modern PHP testing

## API Base URL

```
Production: https://api.alumnate.edu/v1
Staging: https://api-staging.alumnate.edu/v1
Development: https://localhost:8000/api/v1
```

## Authentication

All API requests require authentication using Bearer tokens. Obtain your token through the authentication endpoints.

### Authentication Headers

```http
Authorization: Bearer YOUR_API_TOKEN
X-Tenant-Domain: your-tenant-domain.com
```

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/auth/login` | User login |
| POST   | `/auth/register` | User registration |
| POST   | `/auth/refresh` | Refresh access token |
| POST   | `/auth/logout` | User logout |
| GET    | `/auth/user` | Get authenticated user details |

## Rate Limiting

The API implements multiple rate limiting strategies:

### API Rate Limiting
- **Authenticated Requests**: 1000 requests per hour
- **Guest Requests**: 100 requests per hour
- **File Uploads**: 20 uploads per hour (auth required)
- **Social Actions**: 60 actions per hour (auth required)

Rate limit headers are included in all responses:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1634567890
```

## Request/Response Format

### Request Format
All requests should use JSON content type:
```http
Content-Type: application/json
```

### Response Format
All responses are returned in JSON format:

#### Success Response
```json
{
  "success": true,
  "data": { /* response data */ },
  "message": "Optional success message"
}
```

#### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": { /* optional error details */ }
  }
}
```

#### Paginated Response
```json
{
  "success": true,
  "data": [ /* items */ ],
  "meta": {
    "total": 1250,
    "per_page": 50,
    "current_page": 1,
    "last_page": 25,
    "from": 1,
    "to": 50
  }
}
```

## Error Handling

### HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200  | OK | Request successful |
| 201  | Created | Resource created successfully |
| 204  | No Content | Request successful, no content returned |
| 400  | Bad Request | Invalid request parameters |
| 401  | Unauthorized | Authentication required |
| 403  | Forbidden | Access denied |
| 404  | Not Found | Resource not found |
| 422  | Unprocessable Entity | Validation failed |
| 429  | Too Many Requests | Rate limit exceeded |
| 500  | Internal Server Error | Server error |

### Common Error Codes

| Error Code | Description | HTTP Status |
|------------|-------------|-------------|
| `VALIDATION_ERROR` | Input validation failed | 422 |
| `AUTHENTICATION_FAILED` | Invalid credentials | 401 |
| `AUTHORIZATION_FAILED` | Insufficient permissions | 403 |
| `RESOURCE_NOT_FOUND` | Requested resource not found | 404 |
| `RATE_LIMIT_EXCEEDED` | API rate limit exceeded | 429 |
| `TENANT_NOT_FOUND` | Invalid tenant domain | 404 |
| `SERVER_ERROR` | Unexpected server error | 500 |

## Webhooks

The API supports webhooks for real-time notifications. Webhooks are configured per-tenant and can be managed through the developer dashboard.

### Supported Events

#### Alumni Events
- `alumni.created` - New alumni profile created
- `alumni.updated` - Alumni profile updated
- `alumni.deactivated` - Alumni account deactivated

#### Career Events
- `job.created` - New job posting created
- `job.application.submitted` - Job application submitted
- `career.achievement.unlocked` - Career achievement unlocked

#### Financial Events
- `donation.received` - New donation received
- `fundraising.goal.achieved` - Fundraising goal reached
- `tax.receipt.generated` - Tax receipt generated

#### Community Events
- `event.created` - New event created
- `event.registration` - Event registration
- `message.sent` - Direct message sent

## SDKs and Libraries

Official SDKs are available for multiple programming languages:

### JavaScript/Node.js SDK
```bash
npm install @alumnate/sdk
```

### PHP SDK
```bash
composer require alumnate/php-sdk
```

### SDK Documentation
- [JavaScript SDK](./sdks/javascript/README.md)
- [PHP SDK](./sdks/php/README.md)

## API Versions

The API follows semantic versioning:

- **v1.x.x**: Current stable version
- **Backwards Compatibility**: Maintained within major version
- **Deprecation Notice**: 6 months before breaking changes
- **Migration Guides**: Provided for version upgrades

## Testing

### Postman Collection
Import our [Postman collection](./postman/Alumni_Platform.postman_collection.json) for easy API testing.

### Testing Environment
Use the staging environment for testing: `https://api-staging.alumnate.edu/v1`

### Sandbox Environment
Request access to a sandbox environment for development and testing.

## Support and Documentation

### Developer Resources
- [API Reference](./reference/) - Complete API endpoint documentation
- [Integration Guides](../development/integration-guides/) - Step-by-step integration tutorials
- [Code Examples](./examples/) - Sample code for common use cases
- [Troubleshooting](../development/troubleshooting/) - Common issues and solutions

### Support Channels
- **GitHub Issues**: Bug reports and feature requests
- **Developer Forum**: Community discussions
- **Contact Support**: developer-support@alumnate.edu

---

## Getting Started

1. [Register for API Access](./getting-started/registration.md)
2. [Set Up Authentication](./getting-started/authentication.md)
3. [Make Your First API Call](./getting-started/first-call.md)
4. [Explore Available Endpoints](./reference/endpoints.md)

For the latest updates, check our [API Changelog](./changelog.md).