# API Reference Documentation

## Overview

The Alumni Tracking Platform provides a comprehensive REST API that allows authorized third-party applications to interact with the platform programmatically. This API enables integration with external systems, automation of business processes, and extension of platform capabilities.

## Table of Contents

1. [Authentication](#authentication)
2. [Endpoints](#endpoints)
3. [Rate Limiting](#rate-limiting)
4. [Error Handling](#error-handling)
5. [Data Formats](#data-formats)
6. [SDKs](#sdks)
7. [Examples](#examples)

## Authentication

### OAuth 2.0 Authorization

All API requests require authentication using OAuth 2.0 Bearer tokens.

#### Obtain Access Token

**POST** `/api/oauth/token`

```json
{
  "grant_type": "password",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "username": "user@institution.edu",
  "password": "user-password"
}
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def50200..."
}
```

#### Use Access Token

Include the token in the Authorization header:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### Application Registration

Contact system administrators to register your application and obtain client credentials.

### Token Renewal

**POST** `/api/oauth/token`

```json
{
  "grant_type": "refresh_token",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "refresh_token": "def50200..."
}
```

## Endpoints

### Graduates API

#### Get Graduates List

**GET** `/api/v1/graduates`

**Parameters:**
- `per_page` (integer): Items per page (default: 20, max: 100)
- `page` (integer): Page number
- `graduation_year` (integer): Filter by graduation year
- `degree` (string): Filter by degree type
- `major` (string): Filter by major
- `location` (string): Filter by current location

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "graduation_year": 2020,
      "degree": "Bachelor of Science",
      "major": "Computer Science",
      "current_location": "New York, NY",
      "current_employer": "Tech Company Inc.",
      "profile_completion": 85,
      "last_active": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  },
  "links": {
    "first": "/api/v1/graduates?page=1",
    "last": "/api/v1/graduates?page=5",
    "prev": null,
    "next": "/api/v1/graduates?page=2"
  }
}
```

#### Get Graduate Details

**GET** `/api/v1/graduates/{id}`

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@email.com",
    "graduation_year": 2020,
    "degree": "Bachelor of Science",
    "major": "Computer Science",
    "gpa": 3.8,
    "current_location": "New York, NY",
    "current_employer": "Tech Company Inc.",
    "current_position": "Software Engineer",
    "linkedin_url": "https://linkedin.com/in/johndoe",
    "profile_completion": 85,
    "skills": ["JavaScript", "Python", "React"],
    "experience": [
      {
        "company": "Tech Company Inc.",
        "position": "Software Engineer",
        "start_date": "2022-01-01",
        "end_date": null,
        "description": "Develop web applications..."
      }
    ]
  }
}
```

#### Update Graduate Information

**PUT** `/api/v1/graduates/{id}`

**Request Body:**
```json
{
  "current_employee": "New Tech Company",
  "current_position": "Senior Software Engineer",
  "location": "San Francisco, CA",
  "linkedin_url": "https://linkedin.com/in/johndoe-updated"
}
```

### Employers API

#### Get Employers List

**GET** `/api/v1/employers`

**Parameters:**
- `per_page` (integer): Items per page (default: 20)
- `page` (integer): Page number
- `industry` (string): Filter by industry
- `company_size` (string): Filter by company size (small/medium/large)
- `location` (string): Filter by location

#### Create Employer Profile

**POST** `/api/v1/employers`

**Request Body:**
```json
{
  "name": "Tech Company Inc.",
  "website": "https://techcompany.com",
  "industry": "Software Development",
  "company_size": "500-1000",
  "headquarters": "New York, NY",
  "description": "Leading software development company...",
  "contact_name": "Jane Smith",
  "contact_email": "jane.smith@techcompany.com",
  "contact_phone": "+1-555-123-4567"
}
```

### Jobs API

#### Get Job Listings

**GET** `/api/v1/jobs`

**Parameters:**
- `per_page` (integer): Items per page (default: 20)
- `page` (integer): Page number
- `employer_id` (integer): Filter by employer
- `location` (string): Filter by location
- `job_type` (string): Filter by type (full-time/part-time/contract)
- `experience_level` (string): Filter by required experience

#### Create Job Posting

**POST** `/api/v1/jobs`

**Request Body:**
```json
{
  "employer_id": 1,
  "title": "Software Engineer",
  "description": "We are looking for a skilled software engineer...",
  "requirements": "5+ years experience with React, Node.js...",
  "location": "New York, NY",
  "job_type": "full-time",
  "salary_range": {
    "min": 80000,
    "max": 120000,
    "currency": "USD"
  },
  "benefits": ["Health Insurance", "401k", "Remote Work"],
  "college_degrees_required": ["Computer Science", "Software Engineering"],
  "application_deadline": "2024-03-31",
  "remote_allowed": true
}
```

### Events API

#### Get Events List

**GET** `/api/v1/events`

**Parameters:**
- `per_page` (integer): Items per page (default: 20)
- `page` (integer): Page number
- `start_date` (date): Filter events from this date
- `end_date` (date): Filter events until this date
- `event_type` (string): Filter by type (career/networking/social)
- `location` (string): Filter by location

#### Create Event

**POST** `/api/v1/events`

**Request Body:**
```json
{
  "title": "2024 Alumni Networking Dinner",
  "description": "Join fellow alumni for networking and casual conversation...",
  "event_type": "networking",
  "start_date": "2024-04-15T18:00:00Z",
  "end_date": "2024-04-15T21:00:00Z",
  "location": "Grand Ballroom, Hilton Hotel",
  "virtual_link": null,
  "rsvp_required": true,
  "capacity": 200,
  "target_audience": [
    { "audience_type": "graduation_year", "values": ["2023", "2024"] },
    { "audience_type": "major", "values": ["Business", "Engineering"] }
  ]
}
```

### Analytics API

#### Get User Engagement Analytics

**GET** `/api/v1/analytics/engagement`

**Parameters:**
- `date_from` (date): Start date for analysis
- `date_to` (date): End date for analysis
- `user_type` (string): Filter by user type
- `graduation_year` (integer): Filter by graduation year

**Response:**
```json
{
  "data": {
    "active_users": 1250,
    "profile_completion_rate": 78.5,
    "networking_activity": {
      "connections_made": 450,
      "messages_sent": 1200,
      "events_attended": 750
    },
    "career_activity": {
      "job_applications": 890,
      "job_offers": 45,
      "career_updates": 320
    },
    "top_features": [
      { "feature": "Networking", "usage_count": 950 },
      { "feature": "Job Search", "usage_count": 720 },
      { "feature": "Events", "usage_count": 600 }
    ]
  },
  "date_range": {
    "from": "2024-01-01",
    "to": "2024-01-31"
  }
}
```

#### Get Career Outcomes

**GET** `/api/v1/analytics/career-outcomes`

**Parameters:**
- `graduation_year` (integer): Specific graduation year
- `major` (string): Filter by academic major
- `timeframe` (string): Analysis timeframe (1_year/3_year/5_year)

**Response:**
```json
{
  "data": {
    "graduation_year": 2020,
    "total_graduates": 250,
    "employment_rate": {
      "1_year": 92.4,
      "3_year": 96.8,
      "overall": 98.2
    },
    "salary_data": {
      "average_starting": 65000,
      "average_current": 85000,
      "median_starting": 62000,
      "median_current": 80000
    },
    "top_industries": [
      { "industry": "Technology", "percentage": 35.2 },
      { "industry": "Finance", "percentage": 18.7 },
      { "industry": "Healthcare", "percentage": 12.8 }
    ],
    "top_companies": [
      { "company": "TechCorp", "hires": 15 },
      { "company": "FinancePlus", "hires": 12 },
      { "company": "HealthFirst", "hires": 8 }
    ]
  }
}
```

### Surveys and Forms API

#### Create Survey

**POST** `/api/v1/surveys`

**Request Body:**
```json
{
  "title": "Alumni Career Survey 2024",
  "description": "Help us understand your career journey...",
  "questions": [
    {
      "question_text": "How long did it take you to find your first job?",
      "question_type": "single_choice",
      "options": ["< 3 months", "3-6 months", "6-12 months", "> 12 months"]
    },
    {
      "question_text": "Rate your current job satisfaction:",
      "question_type": "scale",
      "scale_min": 1,
      "scale_max": 5,
      "scale_labels": {
        "1": "Very Dissatisfied",
        "5": "Very Satisfied"
      }
    }
  ],
  "target_audience": {
    "user_type": "graduate",
    "graduation_years": [2020, 2021, 2022, 2023],
    "majors": ["Computer Science", "Business"]
  },
  "is_anonymous": false,
  "available_from": "2024-02-01T00:00:00Z",
  "available_until": "2024-02-28T23:59:59Z"
}
```

### Notifications API

#### Send Notification

**POST** `/api/v1/notifications`

**Request Body:**
```json
{
  "recipient_type": "all_graduates",
  "recipient_filters": {
    "graduation_year": 2020,
    "major": "Computer Science"
  },
  "notification_type": "custom",
  "title": "Important Alumni Update",
  "message": "Please update your profile information...",
  "action_url": "https://platform.example.com/profile",
  "channels": ["email", "platform"],
  "delivery_date": "2024-02-15T09:00:00Z"
}
```

## Rate Limiting

The API implements rate limiting to ensure fair usage:

- **Authenticated requests**: 1000 requests per hour per application
- **Anonymous requests**: 100 requests per hour per IP address
- **File uploads**: 10 files per hour per user

Rate limit headers are included in all responses:

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
X-RateLimit-Retry-After: 3600
```

## Error Handling

### HTTP Status Codes

- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **400 Bad Request**: Invalid request parameters
- **401 Unauthorized**: Authentication required or invalid
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### Error Response Format

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": [
      {
        "field": "email",
        "message": "The email field is required."
      },
      {
        "field": "graduation_year",
        "message": "The graduation year must be a valid year."
      }
    ]
  }
}
```

## Data Formats

### Request Format

All requests must have:
- Content-Type: application/json
- Properly formatted JSON data
- Valid authentication token (Bearer header)

### Response Format

All successful responses use this structure:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

- `data`: Primary response data
- `meta`: Pagination and metadata
- `links`: Hypermedia links for navigation

### Filtering and Sorting

List endpoints support standard query parameters:

```
GET /api/v1/graduates?filter[graduation_year]=2020&sort=name&order=asc&page=2
```

## SDKs

### Available SDKs

#### JavaScript SDK

```javascript
import { AlumniPlatform } from '@alumni-platform/sdk';

const client = new AlumniPlatform({
  clientId: 'your-client-id',
  clientSecret: 'your-client-secret',
  baseUrl: 'https://api.alumni-platform.com'
});

// Authenticate
await client.authenticate('user@institution.edu', 'password');

// Get graduates
const graduates = await client.graduates.list({
  graduation_year: 2020,
  limit: 50
});
```

#### PHP SDK

```php
use AlumniPlatform\Client;

$client = new Client([
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'base_url' => 'https://api.alumni-platform.com'
]);

// Authenticate
$token = $client->authenticate('user@institution.edu', 'password');

// Get graduate details
$graduate = $client->graduates()->find(123);
```

## Examples

### Complete Integration Example

```javascript
import { AlumniPlatform } from '@alumni-platform/sdk';

async function demonstrateFullIntegration() {
    // Initialize client
    const client = new AlumniPlatform({
        clientId: 'demo-app',
        clientSecret: 'demo-secret',
        baseUrl: 'https://api.demo.alumni-platform.com'
    });

    try {
        // Authenticate
        await client.authenticate('demo@institution.edu', 'password');

        // Get recent graduates
        const graduates = await client.graduates.list({
            graduation_year: 2023,
            limit: 20
        });

        // Get job opportunities
        const jobs = await client.jobs.list({
            location: 'remote',
            limit: 10
        });

        // Create career event
        const newEvent = await client.events.create({
            title: '2024 Career Networking Summit',
            description: 'Connect with industry leaders...',
            start_date: '2024-06-15T09:00:00Z',
            location: 'Conference Center',
            target_audience: {
                graduation_years: [2020, 2021, 2022, 2023]
            }
        });

        console.log('Integration successful!', {
            graduatesCount: graduates.data.length,
            jobsCount: jobs.data.length,
            eventId: newEvent.data.id
        });

    } catch (error) {
        console.error('Integration failed:', error.message);
    }
}
```

---

For additional support or to report issues, please contact the API support team at api.support@alumni-platform.com