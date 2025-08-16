<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeveloperController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    /**
     * Get API documentation data
     */
    public function getApiDocumentation()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => '1.0',
                'base_url' => config('app.url') . '/api',
                'authentication' => [
                    'type' => 'Bearer Token',
                    'header' => 'Authorization: Bearer {token}',
                    'description' => 'All API requests require authentication using Bearer tokens.'
                ],
                'rate_limits' => [
                    'api' => '1000 requests per hour',
                    'search' => '100 requests per hour', 
                    'upload' => '50 requests per hour',
                    'social' => '500 requests per hour'
                ],
                'endpoints' => $this->getEndpointDocumentation(),
                'webhooks' => $this->getWebhookDocumentation(),
                'sdks' => $this->getSdkDocumentation(),
                'examples' => $this->getIntegrationExamples()
            ]
        ]);
    }

    /**
     * Generate API key
     */
    public function generateApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $token = Auth::user()->createToken($request->name);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token->plainTextToken,
                'name' => $request->name,
                'created_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get user's API keys
     */
    public function getApiKeys()
    {
        $tokens = Auth::user()->tokens()->latest()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'abilities' => $token->abilities
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey($tokenId)
    {
        $token = Auth::user()->tokens()->findOrFail($tokenId);
        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully'
        ]);
    }

    /**
     * Create webhook
     */
    public function createWebhook(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'secret' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        $webhook = $this->webhookService->createWebhook(Auth::user(), $request->all());

        return response()->json([
            'success' => true,
            'data' => $webhook,
            'message' => 'Webhook created successfully'
        ], 201);
    }

    /**
     * Get user's webhooks
     */
    public function getWebhooks()
    {
        $webhooks = Auth::user()->webhooks()->with('deliveries')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $webhooks
        ]);
    }

    /**
     * Test webhook
     */
    public function testWebhook($webhookId)
    {
        $webhook = Auth::user()->webhooks()->findOrFail($webhookId);
        $delivery = $this->webhookService->testWebhook($webhook);

        return response()->json([
            'success' => true,
            'data' => $delivery,
            'message' => 'Test webhook sent'
        ]);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook($webhookId)
    {
        $webhook = Auth::user()->webhooks()->findOrFail($webhookId);
        $this->webhookService->deleteWebhook($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully'
        ]);
    }

    /**
     * Get available webhook events
     */
    public function getWebhookEvents()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['event' => 'user.created', 'name' => 'User Created', 'description' => 'Triggered when a new user registers'],
                ['event' => 'user.updated', 'name' => 'User Updated', 'description' => 'Triggered when user profile is updated'],
                ['event' => 'post.created', 'name' => 'Post Created', 'description' => 'Triggered when a new post is published'],
                ['event' => 'post.liked', 'name' => 'Post Liked', 'description' => 'Triggered when a post receives a like'],
                ['event' => 'connection.created', 'name' => 'Connection Created', 'description' => 'Triggered when users connect'],
                ['event' => 'event.registered', 'name' => 'Event Registration', 'description' => 'Triggered when user registers for event'],
                ['event' => 'donation.completed', 'name' => 'Donation Completed', 'description' => 'Triggered when donation is processed'],
                ['event' => 'mentorship.requested', 'name' => 'Mentorship Requested', 'description' => 'Triggered when mentorship is requested'],
                ['event' => 'job.applied', 'name' => 'Job Application', 'description' => 'Triggered when user applies for job'],
                ['event' => 'achievement.earned', 'name' => 'Achievement Earned', 'description' => 'Triggered when user earns achievement']
            ]
        ]);
    }

    /**
     * Generate Postman collection
     */
    public function generatePostmanCollection()
    {
        $collection = [
            'info' => [
                'name' => 'Alumni Platform API',
                'description' => 'Complete API collection for the Alumni Platform',
                'version' => '1.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{api_token}}',
                        'type' => 'string'
                    ]
                ]
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => config('app.url') . '/api',
                    'type' => 'string'
                ],
                [
                    'key' => 'api_token',
                    'value' => 'your_api_token_here',
                    'type' => 'string'
                ]
            ],
            'item' => $this->generatePostmanItems()
        ];

        return response()->json($collection);
    }

    /**
     * Get endpoint documentation
     */
    private function getEndpointDocumentation(): array
    {
        return [
            'social' => [
                'name' => 'Social Features',
                'description' => 'Posts, timeline, connections, and social interactions',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/timeline',
                        'description' => 'Get personalized timeline',
                        'parameters' => [
                            ['name' => 'page', 'required' => false, 'type' => 'integer', 'description' => 'Page number for pagination'],
                            ['name' => 'per_page', 'required' => false, 'type' => 'integer', 'description' => 'Items per page (max 50)']
                        ],
                        'response_example' => [
                            'success' => true,
                            'data' => [
                                [
                                    'id' => 123,
                                    'content' => 'Excited to share my new role!',
                                    'user' => ['id' => 1, 'name' => 'John Doe'],
                                    'created_at' => '2024-01-15T10:30:00Z'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/posts',
                        'description' => 'Create a new post',
                        'parameters' => [
                            ['name' => 'content', 'required' => true, 'type' => 'string', 'description' => 'Post content'],
                            ['name' => 'visibility', 'required' => false, 'type' => 'string', 'description' => 'Post visibility (public, circles, groups)'],
                            ['name' => 'media_urls', 'required' => false, 'type' => 'array', 'description' => 'Array of media URLs']
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/posts/{post}/like',
                        'description' => 'Like a post',
                        'parameters' => [
                            ['name' => 'post', 'required' => true, 'type' => 'integer', 'description' => 'Post ID']
                        ]
                    ]
                ]
            ],
            'alumni' => [
                'name' => 'Alumni Directory',
                'description' => 'Alumni discovery, search, and connections',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/alumni',
                        'description' => 'Get alumni directory',
                        'parameters' => [
                            ['name' => 'search', 'required' => false, 'type' => 'string', 'description' => 'Search query'],
                            ['name' => 'industry', 'required' => false, 'type' => 'string', 'description' => 'Filter by industry'],
                            ['name' => 'location', 'required' => false, 'type' => 'string', 'description' => 'Filter by location'],
                            ['name' => 'graduation_year', 'required' => false, 'type' => 'integer', 'description' => 'Filter by graduation year']
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/alumni/{userId}/connect',
                        'description' => 'Send connection request',
                        'parameters' => [
                            ['name' => 'userId', 'required' => true, 'type' => 'integer', 'description' => 'User ID to connect with'],
                            ['name' => 'message', 'required' => false, 'type' => 'string', 'description' => 'Connection message']
                        ]
                    ]
                ]
            ],
            'career' => [
                'name' => 'Career Development',
                'description' => 'Job matching, mentorship, and career tracking',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/jobs/recommendations',
                        'description' => 'Get personalized job recommendations',
                        'parameters' => [
                            ['name' => 'industry', 'required' => false, 'type' => 'string', 'description' => 'Filter by industry'],
                            ['name' => 'location', 'required' => false, 'type' => 'string', 'description' => 'Filter by location'],
                            ['name' => 'experience_level', 'required' => false, 'type' => 'string', 'description' => 'Filter by experience level']
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/mentorship/request',
                        'description' => 'Request mentorship',
                        'parameters' => [
                            ['name' => 'mentor_id', 'required' => true, 'type' => 'integer', 'description' => 'Mentor user ID'],
                            ['name' => 'message', 'required' => true, 'type' => 'string', 'description' => 'Request message'],
                            ['name' => 'goals', 'required' => false, 'type' => 'array', 'description' => 'Mentorship goals']
                        ]
                    ]
                ]
            ],
            'events' => [
                'name' => 'Events',
                'description' => 'Event management and registration',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/events',
                        'description' => 'Get events list',
                        'parameters' => [
                            ['name' => 'type', 'required' => false, 'type' => 'string', 'description' => 'Filter by event type'],
                            ['name' => 'upcoming', 'required' => false, 'type' => 'boolean', 'description' => 'Show only upcoming events']
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/events/{event}/register',
                        'description' => 'Register for event',
                        'parameters' => [
                            ['name' => 'event', 'required' => true, 'type' => 'integer', 'description' => 'Event ID']
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get webhook documentation
     */
    private function getWebhookDocumentation(): array
    {
        return [
            'description' => 'Webhooks allow your application to receive real-time notifications when events occur in the platform.',
            'authentication' => 'Webhook payloads are signed using HMAC-SHA256 with your webhook secret.',
            'retry_policy' => 'Failed webhooks are retried up to 3 times with exponential backoff.',
            'timeout' => 'Webhook requests timeout after 30 seconds.',
            'events' => [
                [
                    'event' => 'user.created',
                    'description' => 'Triggered when a new user registers',
                    'payload_example' => [
                        'event' => 'user.created',
                        'data' => [
                            'id' => 123,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'created_at' => '2024-01-15T10:30:00Z'
                        ],
                        'timestamp' => '2024-01-15T10:30:00Z'
                    ]
                ],
                [
                    'event' => 'post.created',
                    'description' => 'Triggered when a new post is published',
                    'payload_example' => [
                        'event' => 'post.created',
                        'data' => [
                            'id' => 456,
                            'content' => 'New post content',
                            'user_id' => 123,
                            'created_at' => '2024-01-15T10:30:00Z'
                        ],
                        'timestamp' => '2024-01-15T10:30:00Z'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get SDK documentation
     */
    private function getSdkDocumentation(): array
    {
        return [
            'javascript' => [
                'name' => 'JavaScript/Node.js SDK',
                'description' => 'Official SDK for JavaScript and Node.js applications',
                'installation' => 'npm install @alumni-platform/api-client',
                'repository' => 'https://github.com/alumni-platform/js-sdk',
                'documentation' => 'https://docs.alumni-platform.com/sdks/javascript',
                'example' => "import { AlumniPlatformAPI } from '@alumni-platform/api-client';\n\nconst api = new AlumniPlatformAPI({\n  baseURL: 'https://your-domain.com/api',\n  token: 'your-access-token'\n});\n\nconst timeline = await api.posts.getTimeline();\nconst alumni = await api.alumni.search({ industry: 'tech' });"
            ],
            'php' => [
                'name' => 'PHP SDK',
                'description' => 'Official SDK for PHP and Laravel applications',
                'installation' => 'composer require alumni-platform/api-client',
                'repository' => 'https://github.com/alumni-platform/php-sdk',
                'documentation' => 'https://docs.alumni-platform.com/sdks/php',
                'example' => "use AlumniPlatform\\ApiClient\\Client;\n\n\$client = new Client([\n    'base_uri' => 'https://your-domain.com/api',\n    'token' => 'your-access-token'\n]);\n\n\$alumni = \$client->alumni()->search(['industry' => 'tech']);\n\$posts = \$client->posts()->getTimeline();"
            ],
            'python' => [
                'name' => 'Python SDK',
                'description' => 'Official SDK for Python applications',
                'installation' => 'pip install alumni-platform-api',
                'repository' => 'https://github.com/alumni-platform/python-sdk',
                'documentation' => 'https://docs.alumni-platform.com/sdks/python',
                'example' => "from alumni_platform import AlumniPlatformAPI\n\napi = AlumniPlatformAPI(\n    base_url='https://your-domain.com/api',\n    token='your-access-token'\n)\n\njobs = api.jobs.get_recommendations()\nalumni = api.alumni.search(industry='tech')"
            ]
        ];
    }

    /**
     * Get integration examples
     */
    private function getIntegrationExamples(): array
    {
        return [
            'webhook_verification' => [
                'title' => 'Webhook Signature Verification',
                'description' => 'Verify webhook signatures to ensure authenticity',
                'languages' => [
                    'php' => "<?php\n\nfunction verifyWebhookSignature(\$payload, \$signature, \$secret) {\n    \$expectedSignature = 'sha256=' . hash_hmac('sha256', \$payload, \$secret);\n    return hash_equals(\$expectedSignature, \$signature);\n}\n\n// Usage\n\$payload = file_get_contents('php://input');\n\$signature = \$_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';\n\$secret = 'your-webhook-secret';\n\nif (verifyWebhookSignature(\$payload, \$signature, \$secret)) {\n    // Process webhook\n    \$data = json_decode(\$payload, true);\n    // Handle event...\n}",
                    'javascript' => "const crypto = require('crypto');\n\nfunction verifyWebhookSignature(payload, signature, secret) {\n  const expectedSignature = 'sha256=' + crypto\n    .createHmac('sha256', secret)\n    .update(payload)\n    .digest('hex');\n  \n  return crypto.timingSafeEqual(\n    Buffer.from(expectedSignature),\n    Buffer.from(signature)\n  );\n}\n\n// Express.js example\napp.post('/webhooks/alumni-platform', (req, res) => {\n  const signature = req.headers['x-webhook-signature'];\n  const payload = JSON.stringify(req.body);\n  \n  if (verifyWebhookSignature(payload, signature, process.env.WEBHOOK_SECRET)) {\n    // Process webhook\n    console.log('Event:', req.body.event);\n    res.status(200).send('OK');\n  } else {\n    res.status(401).send('Unauthorized');\n  }\n});"
                ]
            ],
            'alumni_search' => [
                'title' => 'Alumni Search Integration',
                'description' => 'Search and filter alumni directory',
                'languages' => [
                    'javascript' => "// Search alumni with filters\nconst searchAlumni = async (filters) => {\n  const params = new URLSearchParams(filters);\n  \n  const response = await fetch(`/api/alumni/search?\${params}`, {\n    headers: {\n      'Authorization': `Bearer \${apiToken}`,\n      'Content-Type': 'application/json'\n    }\n  });\n  \n  return response.json();\n};\n\n// Usage\nconst alumni = await searchAlumni({\n  industry: 'technology',\n  location: 'San Francisco',\n  graduation_year: 2020\n});",
                    'php' => "// Search alumni with filters\nfunction searchAlumni(\$filters, \$apiToken) {\n    \$url = 'https://your-domain.com/api/alumni/search?' . http_build_query(\$filters);\n    \n    \$response = Http::withToken(\$apiToken)->get(\$url);\n    \n    return \$response->json();\n}\n\n// Usage\n\$alumni = searchAlumni([\n    'industry' => 'technology',\n    'location' => 'San Francisco',\n    'graduation_year' => 2020\n], \$apiToken);"
                ]
            ],
            'job_recommendations' => [
                'title' => 'Job Recommendations',
                'description' => 'Get personalized job recommendations',
                'languages' => [
                    'javascript' => "// Get job recommendations\nconst getJobRecommendations = async (filters = {}) => {\n  const response = await fetch('/api/jobs/recommendations', {\n    method: 'GET',\n    headers: {\n      'Authorization': `Bearer \${apiToken}`,\n      'Content-Type': 'application/json'\n    }\n  });\n  \n  const data = await response.json();\n  return data.data;\n};\n\n// Apply for a job\nconst applyForJob = async (jobId, applicationData) => {\n  const response = await fetch(`/api/jobs/\${jobId}/apply`, {\n    method: 'POST',\n    headers: {\n      'Authorization': `Bearer \${apiToken}`,\n      'Content-Type': 'application/json'\n    },\n    body: JSON.stringify(applicationData)\n  });\n  \n  return response.json();\n};",
                    'python' => "import requests\n\ndef get_job_recommendations(api_token, filters=None):\n    headers = {\n        'Authorization': f'Bearer {api_token}',\n        'Content-Type': 'application/json'\n    }\n    \n    params = filters or {}\n    response = requests.get(\n        'https://your-domain.com/api/jobs/recommendations',\n        headers=headers,\n        params=params\n    )\n    \n    return response.json()['data']\n\ndef apply_for_job(api_token, job_id, application_data):\n    headers = {\n        'Authorization': f'Bearer {api_token}',\n        'Content-Type': 'application/json'\n    }\n    \n    response = requests.post(\n        f'https://your-domain.com/api/jobs/{job_id}/apply',\n        headers=headers,\n        json=application_data\n    )\n    \n    return response.json()"
                ]
            ]
        ];
    }

    /**
     * Generate Postman collection items
     */
    private function generatePostmanItems(): array
    {
        return [
            [
                'name' => 'Authentication',
                'item' => [
                    [
                        'name' => 'Get User Profile',
                        'request' => [
                            'method' => 'GET',
                            'header' => [],
                            'url' => [
                                'raw' => '{{base_url}}/user',
                                'host' => ['{{base_url}}'],
                                'path' => ['user']
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Social Features',
                'item' => [
                    [
                        'name' => 'Get Timeline',
                        'request' => [
                            'method' => 'GET',
                            'header' => [],
                            'url' => [
                                'raw' => '{{base_url}}/timeline',
                                'host' => ['{{base_url}}'],
                                'path' => ['timeline']
                            ]
                        ]
                    ],
                    [
                        'name' => 'Create Post',
                        'request' => [
                            'method' => 'POST',
                            'header' => [
                                [
                                    'key' => 'Content-Type',
                                    'value' => 'application/json'
                                ]
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => json_encode([
                                    'content' => 'This is a sample post',
                                    'visibility' => 'public'
                                ])
                            ],
                            'url' => [
                                'raw' => '{{base_url}}/posts',
                                'host' => ['{{base_url}}'],
                                'path' => ['posts']
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Alumni Directory',
                'item' => [
                    [
                        'name' => 'Search Alumni',
                        'request' => [
                            'method' => 'GET',
                            'header' => [],
                            'url' => [
                                'raw' => '{{base_url}}/alumni/search?industry=technology',
                                'host' => ['{{base_url}}'],
                                'path' => ['alumni', 'search'],
                                'query' => [
                                    [
                                        'key' => 'industry',
                                        'value' => 'technology'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Webhooks',
                'item' => [
                    [
                        'name' => 'Create Webhook',
                        'request' => [
                            'method' => 'POST',
                            'header' => [
                                [
                                    'key' => 'Content-Type',
                                    'value' => 'application/json'
                                ]
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => json_encode([
                                    'url' => 'https://your-app.com/webhooks/alumni-platform',
                                    'events' => ['user.created', 'post.created'],
                                    'name' => 'My Webhook'
                                ])
                            ],
                            'url' => [
                                'raw' => '{{base_url}}/webhooks',
                                'host' => ['{{base_url}}'],
                                'path' => ['webhooks']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}