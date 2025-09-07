# API Endpoints Reference

This document provides detailed documentation for all available API endpoints, organized by functional area.

## Authentication

### User Authentication

#### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "user@institution.edu",
  "password": "password123",
  "remember": false
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 123,
      "email": "user@institution.edu",
      "name": "John Doe",
      "role": "graduate"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

**Error Responses:**
- `400` - Invalid credentials
- `422` - Validation errors
- `429` - Too many login attempts

#### Register
```http
POST /api/auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john.doe@institution.edu",
  "password": "password123",
  "graduation_year": 2023,
  "degree": "Bachelor of Science"
}
```

#### Get Authenticated User
```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john.doe@institution.edu",
    "graduation_year": 2023,
    "profile_complete": true,
    "role": "graduate"
  }
}
```

## Alumni Networking

### Directory

#### Get Alumni Directory
```http
GET /api/alumni
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (string) - Search term
- `graduation_year` (int) - Filter by graduation year
- `degree` (string) - Filter by degree
- `industry` (string) - Filter by industry
- `location` (string) - Filter by location
- `page` (int) - Page number (default: 1)
- `per_page` (int) - Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "name": "Jane Smith",
      "email": "jane.smith@company.com",
      "graduation_year": 2018,
      "degree": "MBA",
      "industry": "Technology",
      "position": "Senior Developer",
      "company": "Tech Corp",
      "location": "San Francisco, CA"
    }
  ],
  "meta": {
    "total": 1250,
    "per_page": 20,
    "current_page": 1,
    "last_page": 63
  }
}
```

#### Alumni Profile Details
```http
GET /api/alumni/{userId}
Authorization: Bearer {token}
```

#### Connect with Alumni
```http
POST /api/alumni/{userId}/connect
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "message": "I'd like to connect and learn about opportunities at Tech Corp."
}
```

## Career Services

### Job Management

#### Get Job Listings
```http
GET /api/jobs
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (string) - Job title or company search
- `location` (string) - Job location
- `type` (string) - remote, hybrid, onsite
- `experience_level` (string) - entry, mid, senior
- `salary_min` (int) - Minimum salary
- `salary_max` (int) - Maximum salary

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "title": "Senior Laravel Developer",
      "company": "Tech Solutions Inc.",
      "location": "Austin, TX",
      "type": "hybrid",
      "experience_level": "senior",
      "salary_range": {
        "min": 120000,
        "max": 150000,
        "currency": "USD"
      },
      "description": "We're looking for...",
      "requirements": ["PHP", "Laravel", "MySQL"],
      "posted_at": "2023-12-01T10:00:00Z",
      "deadline": "2024-01-15T23:59:59Z"
    }
  ]
}
```

#### Apply for Job
```http
POST /api/jobs/{jobId}/apply
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "cover_letter": "I'm excited to apply for...",
  "resume_id": 123,
  "custom_questions": {
    "why_interested": "I admire your mission...",
    "availability": "Immediate"
  }
}
```

#### Get Job Details
```http
GET /api/jobs/{jobId}
Authorization: Bearer {token}
```

#### Save/Unsave Job
```http
POST /api/jobs/{jobId}/save
DELETE /api/jobs/{jobId}/save
Authorization: Bearer {token}
```

## Events & Networking

### Event Management

#### Get Events
```http
GET /api/events
Authorization: Bearer {token}
```

**Query Parameters:**
- `type` (string) - reunion, networking, career
- `upcoming` (boolean) - Only upcoming events
- `past` (boolean) - Only past events

#### Get Upcoming Events
```http
GET /api/events-upcoming
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "title": "Alumni Reunion 2024",
      "description": "Annual alumni reunion event",
      "start_date": "2024-06-15T18:00:00Z",
      "end_date": "2024-06-15T23:00:00Z",
      "location": {
        "venue": "Grand Hotel Ballroom",
        "address": "123 Main St, City, ST 12345",
        "coordinates": {
          "lat": 30.2672,
          "lng": -97.7431
        }
      },
      "type": "reunion",
      "capacity": 200,
      "registered_count": 150,
      "registration_required": true,
      "registration_deadline": "2024-05-31T23:59:59Z"
    }
  ]
}
```

#### Register for Event
```http
POST /api/events/{event}/register
Authorization: Bearer {token}
```

#### Cancel Event Registration
```http
DELETE /api/events/{event}/register
Authorization: Bearer {token}
```

#### Check In to Event
```http
POST /api/events/{event}/checkin
Authorization: Bearer {token}
```

## Mentoring Program

### Mentorship Requests

#### Become a Mentor
```http
POST /api/mentorships/become-mentor
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "expertise": ["Laravel", "PHP", "System Architecture"],
  "availability": {
    "schedule": "weekdays_evenings",
    "timezone": "America/New_York",
    "max_sessions": 3
  },
  "preferences": {
    "mentee_level": "beginner",
    "industry_focus": ["technology", "finance"]
  }
}
```

#### Find Mentors
```http
GET /api/mentorships/find-mentors
Authorization: Bearer {token}
```

**Query Parameters:**
- `expertise` (array) - Required expertise areas
- `industry` (string) - Industry focus
- `experience_level` (string) - Beginner, mid, senior
- `availability` (string) - Schedule preference

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 202,
      "name": "Alice Johnson",
      "company": "Big Tech Corp",
      "position": "CTO",
      "expertise": ["Leadership", "System Design", "Team Management"],
      "rating": 4.8,
      "total_sessions": 156,
      "availability": "weekdays_evenings",
      "bio": "I've spent 15 years in tech leadership..."
    }
  ]
}
```

#### Request Mentorship
```http
POST /api/mentorships/request
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "mentor_id": 202,
  "topic": "Career Transition to Tech",
  "goals": "Learn Laravel development for full-stack role",
  "preferred_schedule": "Evenings",
  "duration_weeks": 8
}
```

#### Book Mentorship Session
```http
POST /api/mentorships/sessions
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "mentorship_id": 101,
  "date": "2024-03-20",
  "time": "19:00",
  "duration": 60,
  "topic": "Advanced Laravel Features",
  "meeting_type": "video_call"
}
```

#### Get Upcoming Mentorship Sessions
```http
GET /api/mentorships/sessions/upcoming
Authorization: Bearer {token}
```

## Fundraising & Donations

### Campaign Management

#### Get Fundraising Campaigns
```http
GET /api/fundraising-campaigns
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 301,
      "title": "Student Scholarship Fund",
      "description": "Fund scholarships for deserving students",
      "goal": 50000,
      "raised": 32500,
      "currency": "USD",
      "start_date": "2024-01-01T00:00:00Z",
      "end_date": "2024-12-31T23:59:59Z",
      "status": "active",
      "category": "education",
      "beneficiaries": "Undergraduate Students"
    }
  ]
}
```

#### Get Campaign Analytics
```http
GET /api/fundraising-campaigns/{campaign}/analytics
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "performance": {
      "raised": 32500,
      "goal": 50000,
      "progress_percentage": 65,
      "days_remaining": 68,
      "average_donation": 48.50,
      "total_donors": 672
    },
    "time_series": [
      {"date": "2024-01-01", "amount": 12500},
      {"date": "2024-01-15", "amount": 17000}
    ],
    "top_donors": [
      {"name": "Anonymous", "amount": 2500},
      {"name": "John Smith", "amount": 1500}
    ]
  }
}
```

#### Make Donation
```http
POST /api/campaign-donations
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "campaign_id": 301,
  "amount": 100,
  "currency": "USD",
  "is_anonymous": false,
  "dedication": "In honor of the Class of 2023",
  "recurring": {
    "frequency": "monthly",
    "duration": "until_goal_met"
  }
}
```

#### Get User's Donations
```http
GET /api/user/donations
Authorization: Bearer {token}
```

### Recurring Donations
```http
GET /api/recurring-donations
Authorization: Bearer {token}
```

## Social Features

### Posts & Timeline

#### Get Timeline Posts
```http
GET /api/timeline
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int) - Page number
- `per_page` (int) - Items per page
- `network` (string) - posts, events, updates

#### Get Timeline Refresh
```http
GET /api/timeline/refresh
Authorization: Bearer {token}
```

#### Load More Posts
```http
GET /api/timeline/load-more
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "after": 1623456789,
  "network": "posts"
}
```

#### Create Post
```http
POST /api/posts
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "content": "Excited to announce that I've started a new role at Tech Corp!",
  "media_ids": [123, 456],
  "tags": ["career", "achievement"],
  "privacy": "public",
  "post_type": "update"
}
```

#### Save Draft
```http
POST /api/posts/drafts
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Draft Post",
  "content": "Work in progress...",
  "scheduled_date": "2024-03-15T10:00:00Z"
}
```

#### Upload Media for Post
```http
POST /api/posts/media
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### Post Engagement

#### Like Post
```http
POST /api/posts/{post}/like
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "reaction_type": "heart"
}
```

Supported reaction types: `like`, `love`, `congratulations`, `anger`, `surprise`

#### Comment on Post
```http
POST /api/posts/{post}/comment
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "content": "Congratulations on the new role!",
  "parent_id": null
}
```

#### Share Post
```http
POST /api/posts/{post}/share
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "share_type": "reshare",
  "comment": "Great news!"
}
```

#### Get Post Stats
```http
GET /api/posts/{post}/stats
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "likes": 45,
    "comments": 8,
    "shares": 12,
    "views": 350,
    "reactions": {
      "heart": 25,
      "congratulations": 20
    }
  }
}
```

## Skills Development

### Skills Management

#### Get User Skills
```http
GET /api/users/{userId}/skills
Authorization: Bearer {token}
```

#### Add Skill
```http
POST /api/users/skills
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "skill_name": "Laravel",
  "level": "intermediate",
  "years_experience": 3
}
```

#### Endorse Skill
```http
POST /api/skills/endorse
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "user_id": 456,
  "skill_id": 123
}
```

#### Search Skills
```http
GET /api/skills/search
Authorization: Bearer {token}
```

**Query Parameters:**
- `q` (string, required) - Search query
- `category` (string) - Technical, business, design, etc.

#### Get Skill Progress
```http
GET /api/skills/{skillId}/progression
Authorization: Bearer {token}
```

### Learning Resources

#### Get Learning Resources
```http
GET /api/learning-resources
Authorization: Bearer {token}
```

**Query Parameters:**
- `skill_id` (int) - Filter by skill
- `type` (string) - Course, tutorial, book, etc.
- `level` (string) - Beginner, intermediate, advanced
- `format` (string) - Video, text, interactive

#### Create Learning Resource
```http
POST /api/learning-resources
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Advanced Laravel Patterns",
  "description": "Master advanced Laravel design patterns",
  "type": "course",
  "url": "https://example.com/course",
  "skill_id": 123,
  "level": "advanced",
  "duration_minutes": 480,
  "difficulty": "hard",
  "tags": ["laravel", "php", "patterns"]
}
```

## Analytics & Performance

### Alumni Analytics

#### Get Analytics Dashboard
```http
GET /api/analytics/dashboard
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "engagement": {
      "active_users": 2450,
      "new_registrations": 127,
      "posts_created": 423,
      "events_attended": 98
    },
    "career_outcomes": {
      "employed_rate": 94.2,
      "average_salary": 87500,
      "promotions": 67
    },
    "donations": {
      "total_raised": 156700,
      "active_campaigns": 3,
      "completion_rate": 78.5
    }
  }
}
```

#### Get Engagement Metrics
```http
GET /api/analytics/engagement-metrics
Authorization: Bearer {token}
```

### Performance Monitoring

#### Store Performance Metrics
```http
POST /api/performance/metrics
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "page_url": "/dashboard",
  "metrics": {
    "first_paint": 1200,
    "first_contentful_paint": 1400,
    "largest_contentful_paint": 2800,
    "dom_content_loaded": 1800,
    "load_complete": 3200
  },
  "device": {
    "type": "desktop",
    "browser": "chrome",
    "version": "120.0.0"
  }
}
```

#### Get Performance Analytics
```http
GET /api/performance/analytics
Authorization: Bearer {token}
```

#### Get Core Web Vitals
```http
GET /api/performance/core-web-vitals
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "scores": {
      "largest_contentful_paint": "good",
      "first_input_delay": "needs_improvement",
      "cumulative_layout_shift": "good"
    },
    "metrics": {
      "largest_contentful_paint": {
        "value": 2150,
        "rating": "good",
        "improvement_suggestions": []
      }
    }
  }
}
```

### Notifications

#### Get Notifications
```http
GET /api/notifications
Authorization: Bearer {token}
```

**Query Parameters:**
- `read` (boolean) - Filter by read status
- `type` (string) - Filter by notification type
- `page` (int) - Page number

#### Mark as Read
```http
POST /api/notifications/{notification}/read
Authorization: Bearer {token}
```

#### Mark All as Read
```http
POST /api/notifications/mark-all-read
Authorization: Bearer {token}
```

#### Get Unread Count
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_unread": 8,
    "by_type": {
      "connection": 3,
      "event": 2,
      "job": 2,
      "message": 1
    }
  }
}
```

## Admin Functions

### User Management

#### Get Users
```http
GET /admin/users
Authorization: Bearer {token} (admin only)
```

**Query Parameters:**
- `role` (string) - Filter by user role
- `status` (string) - Active, inactive, pending
- `search` (string) - Search by name or email
- `graduation_year` (int) - Filter by graduation year

#### Update User Role
```http
PUT /admin/users/{user}/role
Authorization: Bearer {token} (super-admin only)
```

### System Statistics

#### Get Platform Health
```http
GET /api/statistics/health
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "uptime": "7 days",
    "database": {
      "status": "connected",
      "response_time": "12ms"
    },
    "cache": {
      "status": "healthy",
      "response_time": "2ms"
    },
    "disk_usage": {
      "used": "45%",
      "available": "500GB"
    }
  }
}
```

#### Get Platform Metrics
```http
GET /api/statistics/platform-metrics
Authorization: Bearer {token}
```

### Component Library (Admin/Moderator)

#### Get Component Library Categories
```http
GET /api/components/categories
Authorization: Bearer {token}
```

#### Search Components
```http
GET /api/components
Authorization: Bearer {token}
```

**Query Parameters:**
- `category_id` (int) - Category filter
- `search` (string) - Search term
- `type` (string) - Button, form, modal, etc.
- `tags` (array) - Tag filters

#### Get Component Details
```http
GET /api/components/{component}
Authorization: Bearer {token}
```

#### Create Component
```http
POST /api/components
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Custom Button",
  "description": "A reusable button component",
  "code": {
    "html": "<button class=\"btn\">Button</button>",
    "css": ".btn { background: blue; color: white; }",
    "js": "console.log('Button clicked');"
  },
  "category_id": 15,
  "tags": ["button", "ui", "interaction"],
  "accessibility": {
    "wcag_level": "AA",
    "keyboard_navigable": true,
    "screen_reader_support": true
  }
}
```

---

## Error Response Format

All endpoints return errors in a consistent format:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password field must be at least 8 characters."]
    }
  }
}
```

## Rate Limiting

All authenticated endpoints are rate limited:
- General API calls: 1000/hour
- File uploads: 20/hour
- Social interactions: 60/hour

Rate limit headers are included in all responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1634567890
```

## Pagination

All list endpoints support pagination:

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "total": 1250,
    "per_page": 50,
    "current_page": 1,
    "last_page": 25,
    "from": 1,
    "to": 50,
    "path": "http://api.alumnate.edu/v1/users",
    "prev_page_url": null,
    "next_page_url": "http://api.alumnate.edu/v1/users?page=2"
  }
}