# API Conventions Guide

This document covers the standard conventions used throughout the Alumni Platform API, including rate limiting, pagination, filtering, sorting, and other common patterns.

---

## Rate Limiting

The API implements a tiered rate limiting system to ensure fair usage and platform stability.

### Rate Limit Tiers

| Tier | Requests/Hour | Description |
|------|---------------|-------------|
| Authenticated (Standard) | 1,000 | Standard authenticated users |
| Authenticated (Premium) | 5,000 | Premium tier users |
| Guest | 100 | Unauthenticated requests |
| Search | 60 | Search API endpoints |
| File Upload | 20 | Media upload endpoints |
| Social Actions | 60 | Likes, comments, shares |
| Webhooks | 100 | Webhook deliveries |
| Analytics | 50 | Analytics endpoints |

### Rate Limit Headers

All responses include rate limit information:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 998
X-RateLimit-Reset: 1705316400
X-RateLimit-Window: 3600
```

### Headers Description

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed in the window |
| `X-RateLimit-Remaining` | Remaining requests in current window |
| `X-RateLimit-Reset` | Unix timestamp when the window resets |
| `X-RateLimit-Window` | Window size in seconds |

### Handling Rate Limits

#### Example: Rate Limited Response

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "API rate limit exceeded. Please try again later.",
    "details": {
      "limit": 1000,
      "remaining": 0,
      "reset_at": "2024-01-15T11:00:00Z",
      "retry_after": 3600
    }
  }
}
```

#### Retry-After Header

When rate limited, responses include a `Retry-After` header:

```http
Retry-After: 3600
```

### Best Practices for Rate Limiting

1. **Implement Exponential Backoff**

```javascript
async function apiRequest(endpoint, options = {}, retries = 3) {
  for (let attempt = 0; attempt < retries; attempt++) {
    const response = await fetch(endpoint, options);
    
    if (response.status === 429) {
      const waitTime = Math.pow(2, attempt) * 1000;
      await new Promise(resolve => setTimeout(resolve, waitTime));
      continue;
    }
    
    return response;
  }
  throw new Error('Max retries exceeded');
}
```

2. **Track Rate Limits Proactively**

```javascript
class RateLimitTracker {
  constructor() {
    this.limit = 1000;
    this.remaining = 1000;
    this.resetTime = null;
  }
  
  updateFromHeaders(headers) {
    this.limit = parseInt(headers['X-RateLimit-Limit']);
    this.remaining = parseInt(headers['X-RateLimit-Remaining']);
    this.resetTime = new Date(headers['X-RateLimit-Reset'] * 1000);
  }
  
  shouldThrottle() {
    return this.remaining <= 10;
  }
}
```

3. **Batch Requests When Possible**

Instead of making multiple individual requests, use batch endpoints when available.

---

## Pagination

All list endpoints support pagination using a consistent format.

### Pagination Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 20 | Items per page |
| `cursor` | string | No | null | Cursor for cursor-based pagination |

### Pagination Response

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Item 1" },
    { "id": 2, "name": "Item 2" }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20,
    "path": "/api/users",
    "first_page_url": "/api/users?page=1",
    "last_page_url": "/api/users?page=8",
    "next_page_url": "/api/users?page=2",
    "prev_page_url": null
  }
}
```

### Pagination Fields

| Field | Description |
|-------|-------------|
| `total` | Total number of items |
| `per_page` | Items per page |
| `current_page` | Current page number |
| `last_page` | Last page number |
| `from` | Starting item number |
| `to` | Ending item number |
| `path` | Base URL path |
| `first_page_url` | URL to first page |
| `last_page_url` | URL to last page |
| `next_page_url` | URL to next page (null if last page) |
| `prev_page_url` | URL to previous page (null if first page) |

### Custom Page Sizes

Control page size with the `per_page` parameter:

```http
GET /api/users?per_page=50
GET /api/posts?per_page=100
```

**Maximum `per_page` values:**
- Standard endpoints: 100
- Analytics endpoints: 1000
- Search endpoints: 50

### Cursor-Based Pagination

For large datasets, cursor-based pagination is available:

```http
GET /api/users?cursor=abc123xyz
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "next_cursor": "def456uvw",
    "has_more": true
  }
}
```

### Pagination Best Practices

1. **Use Appropriate Page Sizes**
```javascript
// Don't fetch everything at once
const pageSize = 50; // Reasonable default

// Adjust based on data volume
const adjustedSize = totalCount > 10000 ? 25 : 50;
```

2. **Handle Empty Pages**
```javascript
if (response.data.length === 0) {
  console.log('No more results');
  return;
}
```

3. **Preload Related Data**
```javascript
// Instead of multiple requests
const users = await fetchUsers({ per_page: 100 });

// Fetch related data in batch when needed
const profiles = await fetchProfilesBatch(users.map(u => u.id));
```

---

## Filtering

All list endpoints support filtering through query parameters.

### Common Filter Types

#### Exact Match Filters
```http
GET /api/users?role=admin
GET /api/posts?status=published
```

#### Multiple Values
```http
GET /api/users?role=admin,moderator
GET /api/posts?status=published,draft
```

#### Range Filters
```http
GET /api/users?created_at[from]=2024-01-01
GET /api/users?created_at[to]=2024-12-31
```

#### Date Range Examples
```http
GET /api/alumni?graduation_year[min]=2015
GET /api/alumni?graduation_year[max]=2023
```

#### Search Filters
```http
GET /api/users?search=john
GET /api/users?q=john+doe
```

#### Boolean Filters
```http
GET /api/users?active=true
GET /api/users?verified=false
```

### Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Equals | `?status=active` |
| `!=` | Not equals | `?status!=archived` |
| `>` | Greater than | `?age=gt:18` |
| `<` | Less than | `?age=lt:65` |
| `>=` | Greater than or equal | `?experience=gte:5` |
| `<=` | Less than or equal | `?salary=lte:100000` |
| `like` | Pattern matching | `?name=like:john%` |
| `in` | Value in list | `?role=in:admin,moderator` |
| `not_in` | Value not in list | `?role=not_in:banned` |

### Date/Time Filters

```http
GET /api/posts?created_at=2024-01-15
GET /api/posts?created_at[from]=2024-01-01
GET /api/posts?created_at[to]=2024-01-31
GET /api/users?last_login[relative]=7_days_ago
```

### Nested Filters

```http
GET /api/posts?author.name=John
GET /api/posts?comments.user.active=true
```

### Combining Filters

```http
GET /api/users?role=admin&active=true&created_at[from]=2024-01-01
GET /api/posts?(status=published OR status=featured)&category=news
```

### Filter Response

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "total": 50,
    "filters_applied": {
      "role": "admin",
      "active": true,
      "created_at": {
        "from": "2024-01-01"
      }
    },
    "filter_options": {
      "roles": ["admin", "moderator", "user"],
      "statuses": ["active", "inactive", "suspended"]
    }
  }
}
```

---

## Sorting

All list endpoints support sorting through the `sort` parameter.

### Basic Sorting

```http
GET /api/users?sort=name
GET /api/users?sort=-created_at
```

### Sort Format

| Format | Description | Example |
|--------|-------------|---------|
| `field` | Ascending | `?sort=name` |
| `-field` | Descending | `?sort=-created_at` |

### Multi-Column Sorting

```http
GET /api/users?sort=role,-created_at
GET /api/posts?sort=status,-published_at,title
```

### Available Sort Fields

Each endpoint documents its available sort fields. Common fields include:

```http
GET /api/users?sort=id,name,email,role,created_at,updated_at,last_login
GET /api/posts?sort=title,created_at,views,likes,comments
```

### Sort with Pagination

```http
GET /api/users?sort=-created_at&page=1&per_page=20
```

### Default Sorting

If no sort is specified, results are typically sorted by `created_at` descending.

---

## Field Selection

Request only the fields you need using the `fields` parameter.

### Basic Field Selection

```http
GET /api/users?fields=id,name,email
GET /api/posts?fields=id,title,slug
```

### Nested Field Selection

```http
GET /api/posts?fields=id,title,author.id,author.name
```

### Excluding Fields

```http
GET /api/users?exclude=password,api_token
```

### Wildcard Field Selection

```http
GET /api/users?fields=id,name,profile.*
```

---

## Including Related Resources

Include related resources using the `include` parameter.

### Basic Includes

```http
GET /api/posts?include=author
GET /api/posts?include=author,comments
```

### Nested Includes

```http
GET /api/posts?include=author,comments.author
GET /api/posts?include=author.profile,comments.reactions
```

### Available Includes

Each endpoint documents its available includes. Example:

```http
GET /api/posts?include=author,category,tags,comments,reactions
```

### Include Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Post Title",
    "author": {
      "id": 1,
      "name": "John Doe"
    },
    "comments": [
      { "id": 1, "body": "Comment 1", "author": { "id": 2, "name": "Jane" } }
    ]
  },
  "included": {
    "authors": [
      { "id": 1, "name": "John Doe", "email": "john@example.com" }
    ],
    "categories": [...],
    "tags": [...]
  }
}
```

---

## API Versioning

### Version Header

```http
Accept: application/vnd.alumnate.v1+json
```

### URL Versioning

```http
GET /api/v1/users
GET /api/v2/users
```

### Version Response Header

```http
X-API-Version: 1.0.0
```

### Version Compatibility

| API Version | Support Status |
|-------------|----------------|
| v1.0.0 | Current (Active) |
| v0.9.0 | Deprecated (6 months remaining) |
| v0.8.0 | Unsupported |

### Deprecation Policy

- **Major Version Changes**: Breaking changes require a new major version
- **Deprecation Notice**: 6 months before breaking changes
- **Sunset Date**: 12 months after deprecation

---

## Content Negotiation

### Supported Formats

| Format | Accept Header |
|--------|---------------|
| JSON | `application/json` |
| JSON API | `application/vnd.api+json` |

### Request Format

```http
GET /api/users
Accept: application/json
Content-Type: application/json
```

### Response Format

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

---

## Timezones

### Request Timezone

Specify timezone in requests:

```http
GET /api/events?timezone=America/New_York
GET /api/events?timezone=UTC
```

### Default Timezone

Default timezone is **UTC**.

### Response Timezone

All timestamps are returned in ISO 8601 format with UTC timezone:

```json
{
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}
```

---

## Language and Localization

### Accept-Language Header

```http
Accept-Language: en-US
Accept-Language: es
Accept-Language: fr-CA
```

### Supported Languages

| Language | Code | Status |
|----------|------|--------|
| English | en | Full Support |
| Spanish | es | Full Support |
| French | fr | Full Support |
| German | de | Partial |

### Localized Responses

```json
{
  "message": {
    "en": "Success",
    "es": "Éxito",
    "fr": "Succès"
  }
}
```

---

## Compression

### Gzip Compression

The API supports gzip compression for responses.

### Request Compression

```http
Accept-Encoding: gzip, deflate
Content-Encoding: gzip
```

### Automatic Compression

Large responses are automatically compressed when Accept-Encoding header is present.

---

## Caching

### ETag Support

```http
GET /api/users
If-None-Match: "abc123"
```

**Response (Not Modified):**
```http
HTTP/304 Not Modified
```

### Last-Modified Header

```http
Last-Modified: Wed, 15 Jan 2024 10:30:00 GMT
If-Modified-Since: Wed, 15 Jan 2024 10:30:00 GMT
```

### Cache-Control Headers

```http
Cache-Control: public, max-age=3600
```

---

## Request IDs

### Request ID Header

Every request includes a unique ID:

```http
X-Request-ID: req_abc123xyz
```

Include this ID when contacting support.

### Client-Generated IDs

Generate your own request IDs for tracking:

```http
X-Request-ID: client_12345
```

---

## Best Practices Summary

### 1. Use Pagination
```javascript
// Good
const users = await fetchUsers({ per_page: 50 });

// Avoid
const users = await fetchUsers(); // May return all records
```

### 2. Select Only Needed Fields
```javascript
// Good
const users = await fetchUsers({ fields: 'id,name,email' });

// Avoid
const users = await fetchUsers(); // Gets all fields
```

### 3. Handle Rate Limits
```javascript
const tracker = new RateLimitTracker();

// Check before making requests
if (tracker.shouldThrottle()) {
  await sleep(tracker.getWaitTime());
}
```

### 4. Use Compression
```javascript
const response = await fetch(url, {
  headers: {
    'Accept-Encoding': 'gzip'
  }
});
```

### 5. Include Request IDs for Support
```javascript
const requestId = response.headers['X-Request-ID'];
// Log or save for debugging
```

---

## Support

For questions about API conventions:
- **Documentation**: See full API documentation
- **Developer Forum**: community.alumnate.edu
- **Support Email**: developer-support@alumnate.edu
