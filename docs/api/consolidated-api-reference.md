# Consolidated API Reference

This document provides a comprehensive reference for all APIs across the four integrated systems: Modern Alumni Platform, Graduate Tracking System, Component Library System, and Vue.js Page Builder System.

## Table of Contents

1. [Authentication](#authentication)
2. [Modern Alumni Platform APIs](#modern-alumni-platform-apis)
3. [Graduate Tracking System APIs](#graduate-tracking-system-apis)
4. [Component Library System APIs](#component-library-system-apis)
5. [Page Builder System APIs](#page-builder-system-apis)
6. [Shared Endpoints](#shared-endpoints)
7. [Rate Limiting](#rate-limiting)
8. [Error Handling](#error-handling)
9. [Data Formats](#data-formats)
10. [SDKs](#sdks)
11. [Examples](#examples)

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

## Modern Alumni Platform APIs

### Social Timeline API

#### Get Timeline Posts

**GET** `/api/v1/timeline`

**Parameters:**
- `per_page` (integer): Items per page (default: 20, max: 100)
- `page` (integer): Page number
- `user_id` (integer): Filter by specific user
- `circle_id` (integer): Filter by circle

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "content": "Just finished an amazing mentorship session!",
      "created_at": "2024-01-15T10:30:00Z",
      "likes_count": 15,
      "comments_count": 3,
      "user": {
        "name": "John Doe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

#### Create Post

**POST** `/api/v1/timeline`

**Request Body:**
```json
{
  "content": "Excited to share my latest career achievement!",
  "media_urls": ["https://example.com/image1.jpg"],
  "visibility": "public" // or "connections", "circle"
}
```

### Alumni Network API

#### Search Alumni

**GET** `/api/v1/alumni/search`

**Parameters:**
- `query` (string): Search term
- `location` (string): Filter by location
- `graduation_year` (integer): Filter by graduation year
- `industry` (string): Filter by industry
- `skills` (array): Filter by skills

#### Get Alumni Profile

**GET** `/api/v1/alumni/{id}`

**Response:**
```json
{
  "data": {
    "id": 123,
    "name": "Jane Smith",
    "headline": "Senior Software Engineer at TechCorp",
    "location": "San Francisco, CA",
    "graduation_year": 2015,
    "major": "Computer Science",
    "skills": ["JavaScript", "React", "Node.js"],
    "experience": [
      {
        "title": "Senior Software Engineer",
        "company": "TechCorp",
        "start_date": "2020-01-01",
        "end_date": null
      }
    ],
    "education": [
      {
        "degree": "B.S. Computer Science",
        "institution": "University Name",
        "graduation_year": 2015
      }
    ]
  }
}
```

### Career Development API

#### Get Career Timeline

**GET** `/api/v1/career/timeline/{user_id}`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Software Engineer",
      "company": "Startup Inc.",
      "start_date": "2015-06-01",
      "end_date": "2018-03-01",
      "description": "Developed web applications using modern JavaScript frameworks"
    }
  ]
}
```

#### Get Job Recommendations

**GET** `/api/v1/career/recommendations`

**Parameters:**
- `user_id` (integer): User ID
- `limit` (integer): Number of recommendations (default: 10)

### Events API

#### Get Events

**GET** `/api/v1/events`

**Parameters:**
- `start_date` (date): Filter events from this date
- `end_date` (date): Filter events until this date
- `event_type` (string): Filter by type (networking, career, social)
- `location` (string): Filter by location

#### RSVP to Event

**POST** `/api/v1/events/{id}/rsvp`

**Request Body:**
```json
{
  "status": "attending" // or "not_attending", "maybe"
}
```

## Graduate Tracking System APIs

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

## Component Library System APIs

### ComponentLibraryBridge Service

The `ComponentLibraryBridge` service provides the core integration between the Component Library System and GrapeJS page builder.

#### Class: ComponentLibraryBridge

##### Constructor

```typescript
constructor(
  editor: Editor,
  options: ComponentLibraryBridgeOptions = {}
)
```

**Parameters:**
- `editor`: GrapeJS Editor instance
- `options`: Configuration options for the bridge

**Options:**
```typescript
interface ComponentLibraryBridgeOptions {
  apiEndpoint?: string;
  cacheEnabled?: boolean;
  debugMode?: boolean;
  themeIntegration?: boolean;
  responsiveBreakpoints?: ResponsiveBreakpoints;
}
```

##### Methods

###### registerComponent()

Registers a component with the GrapeJS editor and Component Library system.

```typescript
registerComponent(config: ComponentRegistrationConfig): Promise<void>
```

**Parameters:**
```typescript
interface ComponentRegistrationConfig {
  id: string;
  category: string;
  label: string;
 component: ComponentDefinition;
  metadata: ComponentMetadata;
  schema?: JSONSchema;
  traits?: TraitDefinition[];
}
```

**Example:**
```typescript
await bridge.registerComponent({
  id: 'custom-hero',
  category: 'Hero Components',
  label: 'Custom Hero Section',
  component: () => import('@/components/CustomHero.vue'),
  metadata: {
    blockId: 'custom-hero',
    icon: '<svg>...</svg>',
    responsive: true
  },
  traits: [
    {
      type: 'text',
      name: 'headline',
      label: 'Headline',
      changeProp: 1
    }
  ]
});
```

###### convertToGrapeJSBlock()

Converts a Component Library component to GrapeJS block format.

```typescript
convertToGrapeJSBlock(component: Component): GrapeJSBlock
```

**Parameters:**
- `component`: Component model instance

**Returns:**
```typescript
interface GrapeJSBlock {
  id: string;
  label: string;
 category: string;
 media: string;
 content: BlockContent;
  traits: TraitDefinition[];
}
```

**Example:**
```typescript
const component = await Component.find(1);
const block = bridge.convertToGrapeJSBlock(component);
editor.BlockManager.add(block.id, block);
```

### Component Management API

#### ComponentService

Core service for component CRUD operations and business logic.

##### Methods

###### create()

Creates a new component with validation and tenant scoping.

```typescript
create(data: CreateComponentData): Promise<Component>
```

**Parameters:**
```typescript
interface CreateComponentData {
  name: string;
  category: ComponentCategory;
  type: string;
  config: ComponentConfig;
  metadata?: ComponentMetadata;
 tenantId: string;
}
```

###### update()

Updates an existing component with validation.

```typescript
update(
  id: string,
  data: UpdateComponentData
): Promise<Component>
```

###### duplicate()

Creates a copy of an existing component.

```typescript
duplicate(
  id: string,
  options: DuplicationOptions = {}
): Promise<Component>
```

**Parameters:**
```typescript
interface DuplicationOptions {
  newName?: string;
  preserveMetadata?: boolean;
  updateVersion?: boolean;
}
```

###### activate() / deactivate()

Controls component availability in the library.

```typescript
activate(id: string): Promise<void>
deactivate(id: string): Promise<void>
```

###### getByCategory()

Retrieves components filtered by category.

```typescript
getByCategory(
  category: string,
  options: FilterOptions = {}
): Promise<Component[]>
```

###### validateConfig()

Validates component configuration against schema.

```typescript
validateConfig(
  componentType: string,
  config: ComponentConfig
): ValidationResult
```

### ComponentThemeService

Service for managing component themes and styling.

#### Methods

###### applyTheme()

Applies a theme to one or more components.

```typescript
applyTheme(
  themeId: string,
  componentIds: string[]
): Promise<void>
```

###### createTheme()

Creates a new theme with validation.

```typescript
createTheme(data: CreateThemeData): Promise<ComponentTheme>
```

**Parameters:**
```typescript
interface CreateThemeData {
  name: string;
  config: ThemeConfig;
  isDefault?: boolean;
  tenantId: string;
}
```

###### inheritTheme()

Creates a theme that inherits from another theme.

```typescript
inheritTheme(
  parentThemeId: string,
  overrides: Partial<ThemeConfig>
): Promise<ComponentTheme>
```

###### validateTheme()

Validates theme configuration for compatibility.

```typescript
validateTheme(config: ThemeConfig): ThemeValidationResult
```

**Returns:**
```typescript
interface ThemeValidationResult {
  valid: boolean;
  errors: ThemeValidationError[];
  compatibility: ComponentCompatibility[];
}
```

###### generateCSS()

Generates CSS variables and classes from theme configuration.

```typescript
generateCSS(theme: ComponentTheme): Promise<string>
```

### ComponentAnalyticsService

Service for tracking component performance and usage.

#### Methods

###### trackEvent()

Records a component interaction event.

```typescript
trackEvent(event: ComponentEvent): Promise<void>
```

**Parameters:**
```typescript
interface ComponentEvent {
  componentId: string;
  eventType: 'view' | 'click' | 'conversion' | 'form_submit';
  userId?: string;
  sessionId: string;
  data?: Record<string, any>;
}
```

###### getMetrics()

Retrieves analytics metrics for components.

```typescript
getMetrics(
  query: MetricsQuery
): Promise<ComponentMetrics>
```

**Parameters:**
```typescript
interface MetricsQuery {
  componentIds?: string[];
  dateRange: DateRange;
  metrics: MetricType[];
  groupBy?: 'component' | 'date' | 'variant';
}
```

###### createABTest()

Sets up A/B testing for component variants.

```typescript
createABTest(config: ABTestConfig): Promise<ABTest>
```

**Parameters:**
```typescript
interface ABTestConfig {
  name: string;
  componentId: string;
 variants: ComponentVariant[];
  trafficSplit: number[];
  duration: number;
  successMetric: string;
}
```

### REST API Endpoints

#### Components

##### GET /api/components

Retrieves paginated list of components.

**Query Parameters:**
- `category` (string): Filter by category
- `search` (string): Search by name or description
- `active` (boolean): Filter by active status
- `page` (number): Page number for pagination
- `per_page` (number): Items per page (max 100)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Hero Component",
      "category": "hero",
      "type": "individual",
      "config": {...},
      "metadata": {...},
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 20
  }
}
```

##### POST /api/components

Creates a new component.

**Request Body:**
```json
{
  "name": "Custom Hero",
  "category": "hero",
  "type": "custom",
  "config": {
    "headline": "Welcome",
    "backgroundColor": "#ffffff"
  },
  "metadata": {
    "tags": ["custom", "hero"],
    "description": "Custom hero component"
  }
}
```

##### GET /api/components/{id}

Retrieves a specific component.

##### PUT /api/components/{id}

Updates an existing component.

##### DELETE /api/components/{id}

Soft deletes a component.

##### POST /api/components/{id}/duplicate

Creates a duplicate of the component.

##### POST /api/components/{id}/activate

Activates the component.

##### POST /api/components/{id}/deactivate

Deactivates the component.

#### Component Themes

##### GET /api/component-themes

Retrieves available themes.

##### POST /api/component-themes

Creates a new theme.

##### PUT /api/component-themes/{id}

Updates an existing theme.

##### POST /api/component-themes/{id}/apply

Applies theme to specified components.

**Request Body:**
```json
{
  "component_ids": ["uuid1", "uuid2", "uuid3"]
}
```

#### Component Analytics

##### POST /api/component-analytics/events

Records component interaction events.

**Request Body:**
```json
{
  "component_id": "uuid",
  "event_type": "click",
  "session_id": "session-uuid",
  "data": {
    "button": "cta-primary",
    "position": "hero"
  }
}
```

##### GET /api/component-analytics/metrics

Retrieves analytics metrics.

**Query Parameters:**
- `component_ids[]` (array): Component IDs to include
- `start_date` (date): Start of date range
- `end_date` (date): End of date range
- `metrics[]` (array): Metrics to include (views, clicks, conversions)

## Page Builder System APIs

### Page Management API

#### Create Page

**POST** `/api/v1/pages`

**Request Body:**
```json
{
  "title": "Alumni Career Network",
  "slug": "career-network",
  "template_id": "landing-page",
  "status": "draft" // or "published"
}
```

#### Get Pages

**GET** `/api/v1/pages`

**Parameters:**
- `status` (string): Filter by status (draft, published, archived)
- `template_id` (string): Filter by template
- `per_page` (integer): Items per page
- `page` (integer): Page number

#### Update Page

**PUT** `/api/v1/pages/{id}`

**Request Body:**
```json
{
  "title": "Updated Page Title",
  "content": {
    "blocks": [...]
  },
  "status": "published"
}
```

#### Delete Page

**DELETE** `/api/v1/pages/{id}`

### Template API

#### Get Templates

**GET** `/api/v1/templates`

**Parameters:**
- `category` (string): Filter by category
- `per_page` (integer): Items per page
- `page` (integer): Page number

#### Create Template

**POST** `/api/v1/templates`

**Request Body:**
```json
{
  "name": "Event Landing Page",
  "category": "events",
  "content": {
    "blocks": [...]
  },
  "preview_image": "https://example.com/preview.jpg"
}
```

### Asset Management API

#### Upload Asset

**POST** `/api/v1/assets`

**Form Data:**
- `file`: The file to upload
- `type`: Asset type (image, video, document)

#### Get Assets

**GET** `/api/v1/assets`

**Parameters:**
- `type` (string): Filter by asset type
- `per_page` (integer): Items per page
- `page` (integer): Page number

## Shared Endpoints

### User Management

#### Get Current User

**GET** `/api/v1/user`

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "alumni",
    "institution_id": 1,
    "preferences": {
      "notifications": {
        "email": true,
        "push": false
      }
    }
  }
}
```

#### Update User Preferences

**PUT** `/api/v1/user/preferences`

**Request Body:**
```json
{
  "notifications": {
    "email": false,
    "push": true
  }
}
```

### Notifications API

#### Get Notifications

**GET** `/api/v1/notifications`

**Parameters:**
- `per_page` (integer): Items per page
- `page` (integer): Page number
- `unread_only` (boolean): Filter by unread status

#### Mark as Read

**PUT** `/api/v1/notifications/{id}/read`

#### Mark All as Read

**PUT** `/api/v1/notifications/read-all`

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