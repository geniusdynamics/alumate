// Complete API Documentation with all endpoints from routes/api.php
export const completeApiEndpoints = {
  authentication: {
    name: 'Authentication & User Management',
    description: 'User authentication, profile management, and session handling',
    endpoints: [
      {
        method: 'GET',
        path: '/api/user',
        description: 'Get authenticated user information',
        auth: { required: true, description: 'Sanctum Bearer token required' },
        tags: ['auth', 'user', 'profile'],
        parameters: [],
        responses: {
          200: {
            description: 'User information retrieved successfully',
            example: {
              id: 1,
              name: 'John Doe',
              email: 'john@example.com',
              avatar_url: 'https://example.com/avatar.jpg',
              created_at: '2024-01-01T00:00:00Z',
              updated_at: '2024-01-15T10:30:00Z'
            }
          },
          401: {
            description: 'Unauthenticated',
            example: {
              message: 'Unauthenticated.'
            }
          }
        },
        codeExamples: [
          {
            language: 'JavaScript',
            code: `const response = await fetch('/api/user', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});

const user = await response.json();`
          },
          {
            language: 'PHP',
            code: `$response = $client->get('/api/user', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ]
]);

$user = json_decode($response->getBody(), true);`
          }
        ]
      }
    ]
  },

  social: {
    name: 'Social Features',
    description: 'Posts, timeline, connections, and social interactions',
    endpoints: [
      {
        method: 'GET',
        path: '/api/timeline',
        description: 'Get personalized timeline with posts from circles and groups',
        auth: { required: true, description: 'Bearer token required' },
        tags: ['timeline', 'posts', 'social'],
        parameters: [
          { name: 'page', type: 'integer', required: false, description: 'Page number for pagination', default: 1 },
          { name: 'per_page', type: 'integer', required: false, description: 'Items per page (max 50)', default: 15 },
          { name: 'filter', type: 'string', required: false, description: 'Filter by post type (all, posts, achievements, milestones)' },
          { name: 'circle_id', type: 'integer', required: false, description: 'Filter by specific circle' },
          { name: 'group_id', type: 'integer', required: false, description: 'Filter by specific group' }
        ],
        responses: {
          200: {
            description: 'Timeline retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 123,
                  content: 'Excited to share my new role as Senior Developer at TechCorp!',
                  post_type: 'career_update',
                  user: {
                    id: 1,
                    name: 'John Doe',
                    avatar_url: 'https://example.com/avatar.jpg',
                    title: 'Senior Developer',
                    company: 'TechCorp'
                  },
                  engagement: {
                    likes_count: 15,
                    comments_count: 3,
                    shares_count: 2,
                    user_liked: false
                  },
                  circles: ['Computer Science 2020', 'Bay Area Alumni'],
                  created_at: '2024-01-15T10:30:00Z'
                }
              ],
              meta: {
                current_page: 1,
                per_page: 15,
                total: 150,
                last_page: 10
              }
            }
          }
        },
        codeExamples: [
          {
            language: 'JavaScript',
            code: `const response = await fetch('/api/timeline?page=1&per_page=20', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});

const timeline = await response.json();`
          }
        ]
      },
      {
        method: 'POST',
        path: '/api/posts',
        description: 'Create a new post with rich content and media',
        auth: { required: true },
        tags: ['posts', 'create', 'social'],
        requestBody: {
          contentType: 'application/json',
          example: {
            content: 'Excited to share my new role as Senior Developer at TechCorp!',
            post_type: 'career_update',
            visibility: 'public',
            media_urls: ['https://example.com/image1.jpg'],
            tags: ['career', 'technology'],
            circle_ids: [1, 2],
            group_ids: [5],
            scheduled_at: null
          }
        },
        parameters: [
          { name: 'content', type: 'string', required: true, description: 'Post content (max 5000 characters)' },
          { name: 'post_type', type: 'string', required: false, description: 'Type of post (text, career_update, achievement, milestone)', default: 'text' },
          { name: 'visibility', type: 'string', required: false, description: 'Post visibility (public, circles, groups, private)', default: 'public' }
        ],
        responses: {
          201: {
            description: 'Post created successfully',
            example: {
              success: true,
              data: {
                id: 124,
                content: 'Excited to share my new role as Senior Developer at TechCorp!',
                post_type: 'career_update',
                visibility: 'public',
                user_id: 1,
                created_at: '2024-01-15T11:00:00Z'
              }
            }
          }
        }
      },
      {
        method: 'POST',
        path: '/api/posts/{post}/like',
        description: 'Like or unlike a post',
        auth: { required: true },
        tags: ['posts', 'engagement', 'likes'],
        parameters: [
          { name: 'post', type: 'integer', required: true, description: 'Post ID', location: 'path' }
        ],
        responses: {
          200: {
            description: 'Post liked/unliked successfully',
            example: {
              success: true,
              data: {
                liked: true,
                likes_count: 16
              }
            }
          }
        }
      }
    ]
  },

  alumni: {
    name: 'Alumni Directory & Networking',
    description: 'Alumni discovery, connections, and networking features',
    endpoints: [
      {
        method: 'GET',
        path: '/api/alumni',
        description: 'Get alumni directory with filtering and search capabilities',
        auth: { required: true },
        tags: ['alumni', 'directory', 'networking'],
        parameters: [
          { name: 'search', type: 'string', required: false, description: 'Search by name, company, or title' },
          { name: 'industry', type: 'string', required: false, description: 'Filter by industry' },
          { name: 'location', type: 'string', required: false, description: 'Filter by location' },
          { name: 'graduation_year', type: 'integer', required: false, description: 'Filter by graduation year' },
          { name: 'company', type: 'string', required: false, description: 'Filter by company' },
          { name: 'page', type: 'integer', required: false, description: 'Page number', default: 1 }
        ],
        responses: {
          200: {
            description: 'Alumni directory retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 2,
                  name: 'Jane Smith',
                  title: 'Product Manager',
                  company: 'InnovateCorp',
                  location: 'San Francisco, CA',
                  graduation_year: 2019,
                  industry: 'Technology',
                  avatar_url: 'https://example.com/jane-avatar.jpg',
                  mutual_connections: 5,
                  shared_circles: ['Computer Science Alumni', 'Bay Area Tech'],
                  connection_status: 'not_connected'
                }
              ],
              meta: {
                current_page: 1,
                per_page: 20,
                total: 1250,
                last_page: 63
              }
            }
          }
        }
      },
      {
        method: 'POST',
        path: '/api/alumni/{userId}/connect',
        description: 'Send a connection request to another alumni',
        auth: { required: true },
        tags: ['alumni', 'connections', 'networking'],
        parameters: [
          { name: 'userId', type: 'integer', required: true, description: 'User ID to connect with', location: 'path' }
        ],
        requestBody: {
          contentType: 'application/json',
          example: {
            message: 'Hi Jane! I saw we both work in tech and graduated from the same program. Would love to connect!'
          }
        },
        responses: {
          201: {
            description: 'Connection request sent successfully',
            example: {
              success: true,
              message: 'Connection request sent successfully',
              data: {
                id: 456,
                status: 'pending',
                sent_at: '2024-01-15T12:00:00Z'
              }
            }
          }
        }
      }
    ]
  },

  career: {
    name: 'Career Development',
    description: 'Job matching, mentorship, career tracking, and professional development',
    endpoints: [
      {
        method: 'GET',
        path: '/api/jobs/recommendations',
        description: 'Get personalized job recommendations based on profile and network connections',
        auth: { required: true },
        tags: ['jobs', 'recommendations', 'career'],
        parameters: [
          { name: 'industry', type: 'string', required: false, description: 'Filter by industry' },
          { name: 'location', type: 'string', required: false, description: 'Filter by location' },
          { name: 'experience_level', type: 'string', required: false, description: 'Filter by experience level (entry, mid, senior, executive)' },
          { name: 'remote', type: 'boolean', required: false, description: 'Filter for remote positions' },
          { name: 'salary_min', type: 'integer', required: false, description: 'Minimum salary filter' }
        ],
        responses: {
          200: {
            description: 'Job recommendations retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 456,
                  title: 'Senior Full Stack Developer',
                  company: {
                    name: 'TechCorp',
                    logo_url: 'https://example.com/logo.jpg',
                    size: '500-1000 employees'
                  },
                  location: 'San Francisco, CA',
                  remote: true,
                  salary_range: '$120,000 - $160,000',
                  match_score: 92,
                  match_reasons: [
                    'Skills match: React, Node.js, Python',
                    '3 mutual connections at company',
                    'Similar role progression'
                  ],
                  connections: [
                    {
                      name: 'Jane Smith',
                      title: 'Engineering Manager',
                      mutual_connection: true
                    }
                  ],
                  posted_at: '2024-01-10T09:00:00Z'
                }
              ]
            }
          }
        }
      },
      {
        method: 'GET',
        path: '/api/mentorships/find-mentors',
        description: 'Find available mentors based on career goals and interests',
        auth: { required: true },
        tags: ['mentorship', 'career', 'networking'],
        parameters: [
          { name: 'industry', type: 'string', required: false, description: 'Target industry' },
          { name: 'role', type: 'string', required: false, description: 'Target role or position' },
          { name: 'skills', type: 'array', required: false, description: 'Skills to develop' },
          { name: 'experience_level', type: 'string', required: false, description: 'Mentor experience level preference' }
        ],
        responses: {
          200: {
            description: 'Available mentors found',
            example: {
              success: true,
              data: [
                {
                  id: 789,
                  name: 'Sarah Johnson',
                  title: 'VP of Engineering',
                  company: 'InnovateInc',
                  avatar_url: 'https://example.com/avatar2.jpg',
                  experience_years: 15,
                  mentorship_areas: ['Leadership', 'Technical Architecture', 'Career Growth'],
                  availability: 'biweekly',
                  rating: 4.9,
                  total_mentees: 12,
                  match_score: 88,
                  shared_background: ['Computer Science', 'Startup Experience']
                }
              ]
            }
          }
        }
      }
    ]
  },

  events: {
    name: 'Events & Networking',
    description: 'Event management, registration, networking, and follow-up',
    endpoints: [
      {
        method: 'GET',
        path: '/api/events',
        description: 'Get upcoming and past events with filtering options',
        auth: { required: true },
        tags: ['events', 'networking'],
        parameters: [
          { name: 'type', type: 'string', required: false, description: 'Event type (networking, webinar, reunion, workshop)' },
          { name: 'location', type: 'string', required: false, description: 'Event location or "virtual"' },
          { name: 'date_from', type: 'date', required: false, description: 'Filter events from this date' },
          { name: 'date_to', type: 'date', required: false, description: 'Filter events until this date' },
          { name: 'status', type: 'string', required: false, description: 'Event status (upcoming, past, cancelled)' }
        ],
        responses: {
          200: {
            description: 'Events retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 101,
                  title: 'Alumni Tech Networking Night',
                  description: 'Join fellow tech alumni for an evening of networking...',
                  type: 'networking',
                  location: 'San Francisco, CA',
                  virtual: false,
                  start_date: '2024-02-15T18:00:00Z',
                  end_date: '2024-02-15T21:00:00Z',
                  capacity: 100,
                  registered_count: 67,
                  user_registered: true,
                  organizer: {
                    name: 'Alumni Association',
                    contact_email: 'events@alumni.edu'
                  }
                }
              ]
            }
          }
        }
      },
      {
        method: 'POST',
        path: '/api/events/{event}/register',
        description: 'Register for an event',
        auth: { required: true },
        tags: ['events', 'registration'],
        parameters: [
          { name: 'event', type: 'integer', required: true, description: 'Event ID', location: 'path' }
        ],
        requestBody: {
          contentType: 'application/json',
          example: {
            dietary_restrictions: 'Vegetarian',
            special_requests: 'Wheelchair accessible seating'
          }
        },
        responses: {
          201: {
            description: 'Successfully registered for event',
            example: {
              success: true,
              data: {
                registration_id: 'REG-2024-001',
                event_id: 101,
                user_id: 1,
                status: 'confirmed',
                registered_at: '2024-01-15T12:00:00Z'
              }
            }
          }
        }
      }
    ]
  },

  fundraising: {
    name: 'Fundraising & Donations',
    description: 'Campaign management, donations, and donor relations',
    endpoints: [
      {
        method: 'GET',
        path: '/api/fundraising-campaigns',
        description: 'Get active fundraising campaigns',
        auth: { required: true },
        tags: ['fundraising', 'campaigns'],
        parameters: [
          { name: 'status', type: 'string', required: false, description: 'Campaign status (active, completed, draft)' },
          { name: 'category', type: 'string', required: false, description: 'Campaign category (scholarship, infrastructure, research)' }
        ],
        responses: {
          200: {
            description: 'Campaigns retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 201,
                  title: 'Computer Science Scholarship Fund',
                  description: 'Supporting the next generation of computer scientists...',
                  category: 'scholarship',
                  goal_amount: 100000,
                  raised_amount: 67500,
                  donor_count: 145,
                  progress_percentage: 67.5,
                  start_date: '2024-01-01T00:00:00Z',
                  end_date: '2024-12-31T23:59:59Z'
                }
              ]
            }
          }
        }
      }
    ]
  },

  analytics: {
    name: 'Analytics & Reporting',
    description: 'Platform analytics, engagement metrics, and custom reports',
    endpoints: [
      {
        method: 'GET',
        path: '/api/analytics/engagement',
        description: 'Get user engagement analytics and metrics',
        auth: { required: true, roles: ['admin', 'institution_admin'] },
        tags: ['analytics', 'engagement', 'admin'],
        parameters: [
          { name: 'period', type: 'string', required: false, description: 'Time period (7d, 30d, 90d, 1y)', default: '30d' },
          { name: 'metric', type: 'string', required: false, description: 'Specific metric (posts, connections, events, all)', default: 'all' }
        ],
        responses: {
          200: {
            description: 'Engagement analytics retrieved',
            example: {
              success: true,
              data: {
                summary: {
                  total_active_users: 1250,
                  posts_created: 456,
                  connections_made: 234,
                  events_attended: 89,
                  engagement_rate: 68.5
                },
                trends: {
                  daily_active_users: [
                    { date: '2024-01-01', count: 145 },
                    { date: '2024-01-02', count: 167 }
                  ]
                }
              }
            }
          }
        }
      }
    ]
  },

  webhooks: {
    name: 'Webhooks & Integrations',
    description: 'Real-time event notifications and webhook management',
    endpoints: [
      {
        method: 'GET',
        path: '/api/webhooks',
        description: 'Get user webhooks',
        auth: { required: true },
        tags: ['webhooks', 'integrations'],
        responses: {
          200: {
            description: 'Webhooks retrieved successfully',
            example: {
              success: true,
              data: [
                {
                  id: 1,
                  url: 'https://your-app.com/webhooks/alumni-platform',
                  events: ['post.created', 'user.connected', 'event.registered'],
                  status: 'active',
                  created_at: '2024-01-01T00:00:00Z',
                  last_delivery: '2024-01-15T10:30:00Z'
                }
              ]
            }
          }
        }
      },
      {
        method: 'POST',
        path: '/api/webhooks',
        description: 'Create a new webhook',
        auth: { required: true },
        tags: ['webhooks', 'integrations'],
        requestBody: {
          contentType: 'application/json',
          example: {
            url: 'https://your-app.com/webhooks/alumni-platform',
            events: ['post.created', 'user.connected', 'event.registered'],
            secret: 'your-webhook-secret'
          }
        },
        responses: {
          201: {
            description: 'Webhook created successfully',
            example: {
              success: true,
              data: {
                id: 2,
                url: 'https://your-app.com/webhooks/alumni-platform',
                events: ['post.created', 'user.connected'],
                status: 'active',
                created_at: '2024-01-15T12:00:00Z'
              }
            }
          }
        }
      }
    ]
  }
};

// Enhanced webhook events with complete schemas
export const enhancedWebhookEvents = [
  {
    event: 'post.created',
    name: 'Post Created',
    description: 'Triggered when a user creates a new post',
    example_payload: {
      event: 'post.created',
      data: {
        id: 123,
        content: 'Excited to share my new role!',
        post_type: 'career_update',
        user: {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          avatar_url: 'https://example.com/avatar.jpg'
        },
        visibility: 'public',
        circles: ['Computer Science 2020'],
        groups: ['Bay Area Alumni'],
        engagement: {
          likes_count: 0,
          comments_count: 0,
          shares_count: 0
        },
        created_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z',
      signature: 'sha256=...'
    },
    schema: {
      type: 'object',
      properties: {
        event: { type: 'string', enum: ['post.created'] },
        data: {
          type: 'object',
          properties: {
            id: { type: 'integer' },
            content: { type: 'string' },
            post_type: { type: 'string' },
            user: {
              type: 'object',
              properties: {
                id: { type: 'integer' },
                name: { type: 'string' },
                email: { type: 'string' },
                avatar_url: { type: 'string' }
              }
            },
            visibility: { type: 'string' },
            created_at: { type: 'string', format: 'date-time' }
          }
        },
        timestamp: { type: 'string', format: 'date-time' }
      }
    }
  },
  {
    event: 'user.connected',
    name: 'User Connected',
    description: 'Triggered when users connect with each other',
    example_payload: {
      event: 'user.connected',
      data: {
        connection_id: 456,
        user: {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com'
        },
        connected_user: {
          id: 2,
          name: 'Jane Smith',
          email: 'jane@example.com'
        },
        message: 'Great to connect with a fellow alumni!',
        connected_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  },
  {
    event: 'event.registered',
    name: 'Event Registration',
    description: 'Triggered when a user registers for an event',
    example_payload: {
      event: 'event.registered',
      data: {
        registration_id: 'REG-2024-001',
        event: {
          id: 101,
          title: 'Alumni Tech Networking Night',
          start_date: '2024-02-15T18:00:00Z',
          location: 'San Francisco, CA'
        },
        user: {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com'
        },
        registration_details: {
          dietary_restrictions: 'Vegetarian',
          special_requests: null
        },
        registered_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  },
  {
    event: 'donation.completed',
    name: 'Donation Completed',
    description: 'Triggered when a donation is successfully processed',
    example_payload: {
      event: 'donation.completed',
      data: {
        donation_id: 789,
        campaign: {
          id: 201,
          title: 'Computer Science Scholarship Fund',
          category: 'scholarship'
        },
        donor: {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com'
        },
        amount: 100.00,
        currency: 'USD',
        payment_method: 'credit_card',
        is_recurring: false,
        is_anonymous: false,
        message: 'Happy to support future engineers!',
        completed_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  },
  {
    event: 'mentorship.requested',
    name: 'Mentorship Requested',
    description: 'Triggered when a user requests mentorship',
    example_payload: {
      event: 'mentorship.requested',
      data: {
        request_id: 'MENTOR-REQ-001',
        mentee: {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          current_role: 'Junior Developer',
          graduation_year: 2023
        },
        mentor: {
          id: 2,
          name: 'Sarah Johnson',
          email: 'sarah@example.com',
          title: 'VP of Engineering',
          company: 'TechCorp'
        },
        goals: ['Career advancement', 'Technical leadership'],
        message: 'I would love to learn from your experience in engineering leadership.',
        requested_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  }
];

// SDK examples with complete implementation details
export const enhancedSdkExamples = {
  javascript: {
    name: 'JavaScript/Node.js',
    description: 'For web and Node.js applications',
    installation: 'npm install @alumni-platform/api-client',
    packageJson: {
      name: '@alumni-platform/api-client',
      version: '1.0.0',
      description: 'Official JavaScript SDK for Alumni Platform API',
      main: 'dist/index.js',
      types: 'dist/index.d.ts',
      dependencies: {
        'axios': '^1.6.0',
        'ws': '^8.14.0'
      }
    },
    example: `import { AlumniPlatformAPI } from '@alumni-platform/api-client';

// Initialize the client
const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',
  token: 'your-access-token',
  timeout: 30000
});

// Timeline operations
const timeline = await api.posts.getTimeline({
  page: 1,
  per_page: 20,
  filter: 'all'
});

// Create a post
const post = await api.posts.create({
  content: 'Excited to share my new role at TechCorp!',
  post_type: 'career_update',
  visibility: 'public',
  tags: ['career', 'technology']
});

// Alumni directory search
const alumni = await api.alumni.search({
  industry: 'technology',
  location: 'San Francisco',
  graduation_year: 2020
});

// Job recommendations
const jobs = await api.jobs.getRecommendations({
  industry: 'technology',
  experience_level: 'senior',
  remote: true
});

// Event management
const events = await api.events.getUpcoming();
await api.events.register(eventId, {
  dietary_restrictions: 'Vegetarian'
});

// Webhook management
const webhook = await api.webhooks.create({
  url: 'https://your-app.com/webhooks',
  events: ['post.created', 'user.connected'],
  secret: 'your-secret'
});

// Real-time updates with WebSocket
api.realtime.connect();
api.realtime.on('post.created', (data) => {
  console.log('New post:', data);
});

// Error handling
try {
  const result = await api.posts.create(postData);
} catch (error) {
  if (error.status === 422) {
    console.log('Validation errors:', error.data.errors);
  }
}`
  },
  
  php: {
    name: 'PHP',
    description: 'For Laravel and PHP applications',
    installation: 'composer require alumni-platform/api-client',
    composerJson: {
      name: 'alumni-platform/api-client',
      description: 'Official PHP SDK for Alumni Platform API',
      require: {
        'php': '^8.1',
        'guzzlehttp/guzzle': '^7.0',
        'illuminate/support': '^10.0'
      }
    },
    example: `<?php

use AlumniPlatform\\ApiClient\\Client;
use AlumniPlatform\\ApiClient\\Exceptions\\ValidationException;

// Initialize the client
$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token',
    'timeout' => 30
]);

// Timeline operations
$timeline = $client->posts()->getTimeline([
    'page' => 1,
    'per_page' => 20,
    'filter' => 'all'
]);

// Create a post
$post = $client->posts()->create([
    'content' => 'Excited to share my new role at TechCorp!',
    'post_type' => 'career_update',
    'visibility' => 'public',
    'tags' => ['career', 'technology']
]);

// Alumni directory search
$alumni = $client->alumni()->search([
    'industry' => 'technology',
    'location' => 'San Francisco',
    'graduation_year' => 2020
]);

// Job recommendations
$jobs = $client->jobs()->getRecommendations([
    'industry' => 'technology',
    'experience_level' => 'senior',
    'remote' => true
]);

// Event management
$events = $client->events()->getUpcoming();
$client->events()->register($eventId, [
    'dietary_restrictions' => 'Vegetarian'
]);

// Webhook management
$webhook = $client->webhooks()->create([
    'url' => 'https://your-app.com/webhooks',
    'events' => ['post.created', 'user.connected'],
    'secret' => 'your-secret'
]);

// Error handling
try {
    $result = $client->posts()->create($postData);
} catch (ValidationException $e) {
    $errors = $e->getValidationErrors();
    foreach ($errors as $field => $messages) {
        echo "Field {$field}: " . implode(', ', $messages) . "\\n";
    }
}

// Laravel Service Provider integration
// In config/app.php
'providers' => [
    AlumniPlatform\\ApiClient\\AlumniPlatformServiceProvider::class,
];

// Usage in Laravel
$timeline = app('alumni-platform')->posts()->getTimeline();`
  },

  python: {
    name: 'Python',
    description: 'For Python applications',
    installation: 'pip install alumni-platform-api',
    setupPy: {
      name: 'alumni-platform-api',
      version: '1.0.0',
      description: 'Official Python SDK for Alumni Platform API',
      install_requires: [
        'requests>=2.28.0',
        'websocket-client>=1.6.0',
        'pydantic>=2.0.0'
      ]
    },
    example: `from alumni_platform import AlumniPlatformAPI
from alumni_platform.exceptions import ValidationError
import asyncio

# Initialize the client
api = AlumniPlatformAPI(
    base_url='https://your-domain.com/api',
    token='your-access-token',
    timeout=30
)

# Timeline operations
timeline = api.posts.get_timeline(
    page=1,
    per_page=20,
    filter='all'
)

# Create a post
post = api.posts.create(
    content='Excited to share my new role at TechCorp!',
    post_type='career_update',
    visibility='public',
    tags=['career', 'technology']
)

# Alumni directory search
alumni = api.alumni.search(
    industry='technology',
    location='San Francisco',
    graduation_year=2020
)

# Job recommendations
jobs = api.jobs.get_recommendations(
    industry='technology',
    experience_level='senior',
    remote=True
)

# Event management
events = api.events.get_upcoming()
api.events.register(event_id, dietary_restrictions='Vegetarian')

# Webhook management
webhook = api.webhooks.create(
    url='https://your-app.com/webhooks',
    events=['post.created', 'user.connected'],
    secret='your-secret'
)

# Async support
async def get_timeline_async():
    async with AlumniPlatformAPI.async_client(
        base_url='https://your-domain.com/api',
        token='your-access-token'
    ) as client:
        timeline = await client.posts.get_timeline()
        return timeline

# Real-time updates
def handle_post_created(data):
    print(f"New post: {data['content']}")

api.realtime.connect()
api.realtime.on('post.created', handle_post_created)

# Error handling
try:
    result = api.posts.create(post_data)
except ValidationError as e:
    for field, messages in e.errors.items():
        print(f"Field {field}: {', '.join(messages)}")

# Django integration
# In settings.py
ALUMNI_PLATFORM = {
    'BASE_URL': 'https://your-domain.com/api',
    'TOKEN': 'your-access-token'
}

# Usage in Django views
from django.conf import settings
from alumni_platform import AlumniPlatformAPI

api = AlumniPlatformAPI(**settings.ALUMNI_PLATFORM)
timeline = api.posts.get_timeline()`
  }
};

// Rate limiting information
export const rateLimits = {
  general: {
    requests_per_minute: 60,
    requests_per_hour: 1000,
    description: 'General API endpoints'
  },
  social: {
    requests_per_minute: 30,
    requests_per_hour: 500,
    description: 'Social interactions (posts, likes, comments)'
  },
  search: {
    requests_per_minute: 20,
    requests_per_hour: 200,
    description: 'Search and discovery endpoints'
  },
  upload: {
    requests_per_minute: 10,
    requests_per_hour: 100,
    description: 'File upload endpoints'
  }
};

// Error codes and descriptions
export const errorCodes = {
  400: {
    code: 'BAD_REQUEST',
    description: 'The request was invalid or cannot be served',
    common_causes: ['Missing required parameters', 'Invalid parameter format']
  },
  401: {
    code: 'UNAUTHORIZED',
    description: 'Authentication failed or user lacks permissions',
    common_causes: ['Invalid or expired token', 'Missing Authorization header']
  },
  403: {
    code: 'FORBIDDEN',
    description: 'The request is understood but access is denied',
    common_causes: ['Insufficient permissions', 'Account suspended']
  },
  404: {
    code: 'NOT_FOUND',
    description: 'The requested resource could not be found',
    common_causes: ['Invalid endpoint URL', 'Resource deleted or moved']
  },
  422: {
    code: 'VALIDATION_ERROR',
    description: 'The request was well-formed but contains semantic errors',
    common_causes: ['Invalid field values', 'Business rule violations']
  },
  429: {
    code: 'RATE_LIMIT_EXCEEDED',
    description: 'Too many requests have been made in a given time period',
    common_causes: ['Exceeded rate limits', 'Burst traffic patterns']
  },
  500: {
    code: 'INTERNAL_SERVER_ERROR',
    description: 'An unexpected error occurred on the server',
    common_causes: ['Server configuration issues', 'Database connectivity problems']
  }
};