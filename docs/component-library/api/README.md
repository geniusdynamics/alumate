# Component Library API Documentation

## Overview

The Component Library API provides RESTful endpoints for managing components, themes, media, and analytics. All endpoints require authentication and automatically scope data to the authenticated tenant.

## Base URL

```
https://your-domain.com/api/v1
```

## Authentication

All API requests require authentication using Laravel Sanctum tokens:

```http
Authorization: Bearer {your-token}
```

## Response Format

All API responses follow a consistent JSON structure:

```json
{
  "data": {},
  "meta": {
    "pagination": {},
    "total": 0
  },
  "links": {
    "first": "url",
    "last": "url",
    "prev": null,
    "next": "url"
  }
}
```

## Error Handling

Error responses include detailed information:

```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  },
  "status": 422
}
```

## Rate Limiting

- **Authenticated requests**: 1000 requests per minute
- **File uploads**: 100 requests per minute
- **Analytics endpoints**: 500 requests per minute

## API Endpoints

### Components

#### List Components
```http
GET /api/v1/components
```

**Parameters:**
- `category` (string, optional): Filter by category (hero, forms, testimonials, statistics, ctas, media)
- `search` (string, optional): Search by name or description
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `sort` (string, optional): Sort field (name, created_at, updated_at)
- `direction` (string, optional): Sort direction (asc, desc)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Hero Section - Alumni",
      "slug": "hero-section-alumni",
      "category": "hero",
      "type": "individual",
      "description": "Hero section optimized for individual alumni",
      "config": {
        "headline": "Welcome Back, Alumni",
        "subheading": "Connect with your network",
        "cta_text": "Get Started",
        "background_type": "image"
      },
      "metadata": {
        "tags": ["alumni", "hero", "conversion"],
        "usage_count": 45,
        "conversion_rate": 0.12
      },
      "version": "1.0.0",
      "is_active": true,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

#### Get Component
```http
GET /api/v1/components/{id}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Hero Section - Alumni",
    "slug": "hero-section-alumni",
    "category": "hero",
    "type": "individual",
    "description": "Hero section optimized for individual alumni",
    "config": {
      "headline": "Welcome Back, Alumni",
      "subheading": "Connect with your network",
      "cta_text": "Get Started",
      "background_type": "image",
      "background_image": "https://cdn.example.com/hero-bg.jpg"
    },
    "metadata": {
      "tags": ["alumni", "hero", "conversion"],
      "usage_count": 45,
      "conversion_rate": 0.12,
      "last_used": "2024-01-20T14:30:00Z"
    },
    "version": "1.0.0",
    "is_active": true,
    "theme": {
      "id": 1,
      "name": "Default Theme",
      "config": {
        "primary_color": "#3B82F6",
        "secondary_color": "#1F2937",
        "font_family": "Inter"
      }
    },
    "instances": [
      {
        "id": 1,
        "page_type": "landing_page",
        "page_id": 5,
        "position": 1,
        "custom_config": {
          "headline": "Custom Headline Override"
        }
      }
    ],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

#### Create Component
```http
POST /api/v1/components
```

**Request Body:**
```json
{
  "name": "Custom Hero Section",
  "category": "hero",
  "type": "custom",
  "description": "Custom hero section for special campaigns",
  "config": {
    "headline": "Join Our Network",
    "subheading": "Connect with thousands of alumni",
    "cta_text": "Sign Up Now",
    "background_type": "gradient",
    "gradient_colors": ["#3B82F6", "#1F2937"]
  },
  "metadata": {
    "tags": ["custom", "campaign", "hero"]
  }
}
```

**Validation Rules:**
- `name`: required, string, max:255, unique per tenant
- `category`: required, enum (hero, forms, testimonials, statistics, ctas, media)
- `type`: required, string, max:100
- `description`: optional, string, max:1000
- `config`: required, array, validated against component schema
- `metadata`: optional, array

**Response:**
```json
{
  "data": {
    "id": 2,
    "name": "Custom Hero Section",
    "slug": "custom-hero-section",
    "category": "hero",
    "type": "custom",
    "description": "Custom hero section for special campaigns",
    "config": {
      "headline": "Join Our Network",
      "subheading": "Connect with thousands of alumni",
      "cta_text": "Sign Up Now",
      "background_type": "gradient",
      "gradient_colors": ["#3B82F6", "#1F2937"]
    },
    "metadata": {
      "tags": ["custom", "campaign", "hero"],
      "usage_count": 0,
      "conversion_rate": null
    },
    "version": "1.0.0",
    "is_active": true,
    "created_at": "2024-01-20T15:45:00Z",
    "updated_at": "2024-01-20T15:45:00Z"
  }
}
```

#### Update Component
```http
PUT /api/v1/components/{id}
```

**Request Body:** Same as create, all fields optional

#### Delete Component
```http
DELETE /api/v1/components/{id}
```

**Response:**
```json
{
  "message": "Component deleted successfully"
}
```

#### Duplicate Component
```http
POST /api/v1/components/{id}/duplicate
```

**Request Body:**
```json
{
  "name": "Copy of Hero Section",
  "modifications": {
    "config": {
      "headline": "Modified Headline"
    }
  }
}
```

### Component Themes

#### List Themes
```http
GET /api/v1/component-themes
```

#### Get Theme
```http
GET /api/v1/component-themes/{id}
```

#### Create Theme
```http
POST /api/v1/component-themes
```

**Request Body:**
```json
{
  "name": "Brand Theme",
  "config": {
    "colors": {
      "primary": "#3B82F6",
      "secondary": "#1F2937",
      "accent": "#F59E0B",
      "background": "#FFFFFF",
      "text": "#111827"
    },
    "typography": {
      "font_family": "Inter",
      "heading_font": "Poppins",
      "font_sizes": {
        "xs": "0.75rem",
        "sm": "0.875rem",
        "base": "1rem",
        "lg": "1.125rem",
        "xl": "1.25rem",
        "2xl": "1.5rem",
        "3xl": "1.875rem",
        "4xl": "2.25rem"
      }
    },
    "spacing": {
      "xs": "0.25rem",
      "sm": "0.5rem",
      "md": "1rem",
      "lg": "1.5rem",
      "xl": "2rem",
      "2xl": "3rem"
    },
    "borders": {
      "radius": {
        "sm": "0.25rem",
        "md": "0.375rem",
        "lg": "0.5rem",
        "xl": "0.75rem"
      }
    }
  },
  "is_default": false
}
```

#### Apply Theme to Component
```http
POST /api/v1/components/{id}/apply-theme
```

**Request Body:**
```json
{
  "theme_id": 1
}
```

### Component Media

#### Upload Media
```http
POST /api/v1/component-media
```

**Request Body:** (multipart/form-data)
- `file`: File upload (required)
- `type`: Media type (image, video, document)
- `alt_text`: Alt text for images
- `caption`: Media caption

**Response:**
```json
{
  "data": {
    "id": 1,
    "filename": "hero-background.jpg",
    "original_name": "background-image.jpg",
    "mime_type": "image/jpeg",
    "size": 1024000,
    "type": "image",
    "url": "https://cdn.example.com/media/hero-background.jpg",
    "thumbnail_url": "https://cdn.example.com/media/thumbnails/hero-background.jpg",
    "alt_text": "Alumni networking event",
    "metadata": {
      "width": 1920,
      "height": 1080,
      "optimized_formats": ["webp", "avif"]
    },
    "created_at": "2024-01-20T16:00:00Z"
  }
}
```

### Component Analytics

#### Track Event
```http
POST /api/v1/component-analytics/track
```

**Request Body:**
```json
{
  "component_instance_id": 1,
  "event_type": "click",
  "data": {
    "element": "cta_button",
    "position": "hero",
    "variant": "blue_button"
  },
  "user_id": 123,
  "session_id": "sess_abc123"
}
```

#### Get Analytics
```http
GET /api/v1/component-analytics
```

**Parameters:**
- `component_id` (integer, optional): Filter by component
- `event_type` (string, optional): Filter by event type
- `date_from` (date, optional): Start date (YYYY-MM-DD)
- `date_to` (date, optional): End date (YYYY-MM-DD)
- `group_by` (string, optional): Group results by (day, week, month)

**Response:**
```json
{
  "data": {
    "summary": {
      "total_views": 1250,
      "total_clicks": 89,
      "conversion_rate": 0.071,
      "unique_users": 892
    },
    "events": [
      {
        "date": "2024-01-20",
        "views": 45,
        "clicks": 3,
        "conversions": 1,
        "conversion_rate": 0.022
      }
    ],
    "top_performing": [
      {
        "component_id": 1,
        "component_name": "Hero Section - Alumni",
        "views": 500,
        "clicks": 35,
        "conversion_rate": 0.07
      }
    ]
  }
}
```

## OpenAPI/Swagger Specification

The complete OpenAPI 3.0 specification is available at:
- **JSON**: `/api/documentation.json`
- **YAML**: `/api/documentation.yaml`
- **Interactive UI**: `/api/documentation`

## SDK Examples

### JavaScript/TypeScript
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://your-domain.com/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Get components
const components = await api.get('/components', {
  params: { category: 'hero', per_page: 10 }
});

// Create component
const newComponent = await api.post('/components', {
  name: 'New Hero',
  category: 'hero',
  type: 'custom',
  config: { headline: 'Welcome' }
});
```

### PHP
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get('https://your-domain.com/api/v1/components', [
        'category' => 'hero',
        'per_page' => 10
    ]);

$components = $response->json('data');
```

### cURL
```bash
# Get components
curl -X GET "https://your-domain.com/api/v1/components?category=hero" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json"

# Create component
curl -X POST "https://your-domain.com/api/v1/components" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Hero",
    "category": "hero",
    "type": "custom",
    "config": {"headline": "Welcome"}
  }'
```

## Webhooks

The system supports webhooks for real-time notifications:

### Available Events
- `component.created`
- `component.updated`
- `component.deleted`
- `theme.applied`
- `analytics.conversion`

### Webhook Payload
```json
{
  "event": "component.created",
  "data": {
    "component": { /* component data */ },
    "tenant_id": 1,
    "user_id": 123
  },
  "timestamp": "2024-01-20T16:30:00Z",
  "signature": "sha256=..."
}
```

### Webhook Configuration
```http
POST /api/v1/webhooks
```

**Request Body:**
```json
{
  "url": "https://your-app.com/webhooks/components",
  "events": ["component.created", "component.updated"],
  "secret": "your-webhook-secret"
}
```