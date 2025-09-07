export const apiEndpoints = {
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
          },
          401: {
            description: 'Unauthorized - Invalid or missing token',
            example: {
              success: false,
              message: 'Unauthenticated.',
              error_code: 'UNAUTHORIZED'
            }
          }
        },
        codeExamples: [
          {
            language: 'JavaScript',
            code: `// Using fetch API
const response = await fetch('/api/timeline?page=1&per_page=20', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});

const timeline = await response.json();
console.log(timeline.data);`
          },
          {
            language: 'PHP',
            code: `// Using Guzzle HTTP client
$client = new \\GuzzleHttp\\Client();

$response = $client->get('/api/timeline', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ],
    'query' => [
        'page' => 1,
        'per_page' => 20
    ]
]);

$timeline = json_decode($response->getBody(), true);`
          },
          {
            language: 'Python',
            code: `import requests

headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json'
}

params = {
    'page': 1,
    'per_page': 20
}

response = requests.get('/api/timeline', headers=headers, params=params)
timeline = response.json()`
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
            content: 'Excited to share my new role as Senior Developer at TechCorp! Looking forward to working with amazing technologies.',
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
          { name: 'visibility', type: 'string', required: false, description: 'Post visibility (public, circles, groups, private)', default: 'public' },
          { name: 'media_urls', type: 'array', required: false, description: 'Array of media URLs' },
          { name: 'tags', type: 'array', required: false, description: 'Array of tags' },
          { name: 'circle_ids', type: 'array', required: false, description: 'Array of circle IDs to share with' },
          { name: 'group_ids', type: 'array', required: false, description: 'Array of group IDs to share with' },
          { name: 'scheduled_at', type: 'datetime', required: false, description: 'Schedule post for future publication' }
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
                engagement: {
                  likes_count: 0,
                  comments_count: 0,
                  shares_count: 0
                },
                created_at: '2024-01-15T11:00:00Z'
              },
              message: 'Post created successfully'
            }
          },
          422: {
            description: 'Validation error',
            example: {
              success: false,
              message: 'The given data was invalid.',
              errors: {
                content: ['The content field is required.']
              }
            }
          }
        },
        codeExamples: [
          {
            language: 'JavaScript',
            code: `const postData = {
  content: 'Excited to share my new role!',
  post_type: 'career_update',
  visibility: 'public',
  tags: ['career', 'technology']
};

const response = await fetch('/api/posts', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify(postData)
});

const result = await response.json();`
          }
        ]
      },
      {
        method: 'POST',
        path: '/api/posts/{post}/react',
        description: 'React to a post with like, love, celebrate, support, or insightful',
        auth: { required: true },
        tags: ['posts', 'reactions', 'engagement'],
        parameters: [
          { name: 'post', type: 'integer', required: true, description: 'Post ID', location: 'path' },
          { name: 'reaction_type', type: 'string', required: true, description: 'Type of reaction (like, love, celebrate, support, insightful)' }
        ],
        requestBody: {
          contentType: 'application/json',
          example: {
            reaction_type: 'celebrate'
          }
        },
        responses: {
          200: {
            description: 'Reaction added successfully',
            example: {
              success: true,
              data: {
                reaction_type: 'celebrate',
                user_id: 1,
                post_id: 123
              },
              message: 'Reaction added successfully'
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
          { name: 'salary_min', type: 'integer', required: false, description: 'Minimum salary filter' },
          { name: 'page', type: 'integer', required: false, description: 'Page number', default: 1 }
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
              ],
              meta: {
                total_matches: 25,
                page: 1,
                per_page: 10
              }
            }
          }
        },
        codeExamples: [
          {
            language: 'JavaScript',
            code: `const filters = {
  industry: 'technology',
  location: 'San Francisco',
  remote: true,
  experience_level: 'senior'
};

const queryString = new URLSearchParams(filters).toString();
const response = await fetch(\`/api/jobs/recommendations?\${queryString}\`, {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});

const jobs = await response.json();`
          }
        ]
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
          { name: 'experience_level', type: 'string', required: false, description: 'Mentor experience level preference' },
          { name: 'availability', type: 'string', required: false, description: 'Mentor availability (weekly, biweekly, monthly)' }
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
                  shared_background: ['Computer Science', 'Startup Experience'],
                  bio: 'Passionate about helping engineers grow into leadership roles...'
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
          { name: 'status', type: 'string', required: false, description: 'Event status (upcoming, past, cancelled)' },
          { name: 'registered', type: 'boolean', required: false, description: 'Show only events user is registered for' }
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
                  },
                  featured_speakers: [
                    {
                      name: 'Mike Chen',
                      title: 'CTO at StartupXYZ',
                      bio: 'Leading technology innovation...'
                    }
                  ],
                  registration_deadline: '2024-02-10T23:59:59Z',
                  price: 0,
                  tags: ['technology', 'networking', 'career']
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
            special_requests: 'Wheelchair accessible seating',
            emergency_contact: {
              name: 'Jane Doe',
              phone: '+1-555-0123'
            }
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
              },
              message: 'Successfully registered for event'
            }
          },
          409: {
            description: 'Already registered or event full',
            example: {
              success: false,
              message: 'You are already registered for this event',
              error_code: 'ALREADY_REGISTERED'
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
          { name: 'category', type: 'string', required: false, description: 'Campaign category (scholarship, infrastructure, research)' },
          { name: 'sort', type: 'string', required: false, description: 'Sort by (created_at, goal_amount, raised_amount)', default: 'created_at' }
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
                  end_date: '2024-12-31T23:59:59Z',
                  featured_image: 'https://example.com/campaign.jpg',
                  organizer: {
                    name: 'CS Department',
                    contact: 'cs-dept@university.edu'
                  },
                  recent_donations: [
                    {
                      donor_name: 'Anonymous',
                      amount: 500,
                      message: 'Happy to support future engineers!',
                      donated_at: '2024-01-14T15:30:00Z'
                    }
                  ]
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
          { name: 'metric', type: 'string', required: false, description: 'Specific metric (posts, connections, events, all)', default: 'all' },
          { name: 'segment', type: 'string', required: false, description: 'User segment (graduation_year, industry, location)' }
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
                  ],
                  post_engagement: [
                    { date: '2024-01-01', likes: 234, comments: 67, shares: 23 }
                  ]
                },
                segments: {
                  by_graduation_year: [
                    { year: '2020', active_users: 234, engagement_rate: 72.1 },
                    { year: '2019', active_users: 198, engagement_rate: 65.3 }
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
    name: 'Webhooks',
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
                  last_delivery: '2024-01-15T10:30:00Z',
                  delivery_stats: {
                    total_deliveries: 156,
                    successful_deliveries: 154,
                    failed_deliveries: 2
                  }
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
        parameters: [
          { name: 'url', type: 'string', required: true, description: 'Webhook endpoint URL' },
          { name: 'events', type: 'array', required: true, description: 'Array of event types to subscribe to' },
          { name: 'secret', type: 'string', required: false, description: 'Secret for webhook signature verification' }
        ],
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
                secret: 'wh_secret_...',
                created_at: '2024-01-15T12:00:00Z'
              },
              message: 'Webhook created successfully'
            }
          }
        }
      }
    ]
  }
};

export const webhookEvents = [
  {
    event: 'post.created',
    name: 'Post Created',
    description: 'Triggered when a user creates a new post',
    example_payload: {
      event: 'post.created',
      data: {
        id: 123,
        content: 'Excited to share my new role!',
        user_id: 1,
        created_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  },
  {
    event: 'user.connected',
    name: 'User Connected',
    description: 'Triggered when users connect with each other',
    example_payload: {
      event: 'user.connected',
      data: {
        user_id: 1,
        connected_user_id: 2,
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
        event_id: 101,
        user_id: 1,
        registration_id: 'REG-2024-001',
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
        donation_id: 456,
        campaign_id: 201,
        user_id: 1,
        amount: 100.00,
        currency: 'USD',
        completed_at: '2024-01-15T10:30:00Z'
      },
      timestamp: '2024-01-15T10:30:01Z'
    }
  }
];

export const sdkExamples = {
  javascript: {
    name: 'JavaScript/Node.js',
    description: 'For web and Node.js applications',
    installation: 'npm install @alumni-platform/api-client',
    example: `import { AlumniPlatformAPI } from '@alumni-platform/api-client';

const api = new AlumniPlatformAPI({
  baseURL: 'https://your-domain.com/api',
  token: 'your-access-token'
});

// Get timeline
const timeline = await api.posts.getTimeline();

// Create a post
const post = await api.posts.create({
  content: 'Hello from the API!',
  visibility: 'public'
});

// Search alumni
const alumni = await api.alumni.search({
  industry: 'technology',
  location: 'San Francisco'
});`
  },
  php: {
    name: 'PHP',
    description: 'For Laravel and PHP applications',
    installation: 'composer require alumni-platform/api-client',
    example: `<?php

use AlumniPlatform\\ApiClient\\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token'
]);

// Get timeline
$timeline = $client->posts()->getTimeline();

// Create a post
$post = $client->posts()->create([
    'content' => 'Hello from the API!',
    'visibility' => 'public'
]);

// Search alumni
$alumni = $client->alumni()->search([
    'industry' => 'technology',
    'location' => 'San Francisco'
]);`
  },
  python: {
    name: 'Python',
    description: 'For Python applications',
    installation: 'pip install alumni-platform-api',
    example: `from alumni_platform import AlumniPlatformAPI

api = AlumniPlatformAPI(
    base_url='https://your-domain.com/api',
    token='your-access-token'
)

# Get timeline
timeline = api.posts.get_timeline()

# Create a post
post = api.posts.create(
    content='Hello from the API!',
    visibility='public'
)

# Search alumni
alumni = api.alumni.search(
    industry='technology',
    location='San Francisco'
)`
  }
};