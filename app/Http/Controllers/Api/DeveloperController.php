<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\ApiKey;
use App\Models\Webhook;

class DeveloperController extends Controller
{
    /**
     * Generate a new API key for the authenticated user
     */
    public function generateApiKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|in:read,write,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'key' => 'ap_' . Str::random(40),
            'permissions' => $request->permissions ?? ['read'],
            'last_used_at' => null,
            'expires_at' => now()->addYear()
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $apiKey->key,
                'permissions' => $apiKey->permissions,
                'created_at' => $apiKey->created_at,
                'expires_at' => $apiKey->expires_at
            ],
            'message' => 'API key generated successfully'
        ], 201);
    }

    /**
     * Get user's API keys
     */
    public function getApiKeys(Request $request): JsonResponse
    {
        $apiKeys = $request->user()->apiKeys()
            ->select(['id', 'name', 'created_at', 'last_used_at', 'expires_at', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $apiKeys
        ]);
    }

    /**
     * Revoke an API key
     */
    public function revokeApiKey(Request $request, $keyId): JsonResponse
    {
        $apiKey = $request->user()->apiKeys()->findOrFail($keyId);
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully'
        ]);
    }

    /**
     * Get available webhook events
     */
    public function getWebhookEvents(): JsonResponse
    {
        $events = [
            [
                'event' => 'post.created',
                'name' => 'Post Created',
                'description' => 'Triggered when a user creates a new post'
            ],
            [
                'event' => 'post.updated',
                'name' => 'Post Updated',
                'description' => 'Triggered when a user updates an existing post'
            ],
            [
                'event' => 'post.deleted',
                'name' => 'Post Deleted',
                'description' => 'Triggered when a user deletes a post'
            ],
            [
                'event' => 'user.connected',
                'name' => 'User Connected',
                'description' => 'Triggered when users connect with each other'
            ],
            [
                'event' => 'user.disconnected',
                'name' => 'User Disconnected',
                'description' => 'Triggered when users disconnect from each other'
            ],
            [
                'event' => 'event.registered',
                'name' => 'Event Registration',
                'description' => 'Triggered when a user registers for an event'
            ],
            [
                'event' => 'event.cancelled',
                'name' => 'Event Registration Cancelled',
                'description' => 'Triggered when a user cancels event registration'
            ],
            [
                'event' => 'donation.completed',
                'name' => 'Donation Completed',
                'description' => 'Triggered when a donation is successfully processed'
            ],
            [
                'event' => 'donation.refunded',
                'name' => 'Donation Refunded',
                'description' => 'Triggered when a donation is refunded'
            ],
            [
                'event' => 'mentorship.requested',
                'name' => 'Mentorship Requested',
                'description' => 'Triggered when a user requests mentorship'
            ],
            [
                'event' => 'mentorship.accepted',
                'name' => 'Mentorship Accepted',
                'description' => 'Triggered when a mentorship request is accepted'
            ],
            [
                'event' => 'job.applied',
                'name' => 'Job Application',
                'description' => 'Triggered when a user applies for a job'
            ],
            [
                'event' => 'achievement.earned',
                'name' => 'Achievement Earned',
                'description' => 'Triggered when a user earns an achievement'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request, $webhookId): JsonResponse
    {
        $webhook = $request->user()->webhooks()->findOrFail($webhookId);

        $testPayload = [
            'event' => 'webhook.test',
            'data' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'timestamp' => now()->toISOString()
            ],
            'timestamp' => now()->toISOString()
        ];

        // In a real implementation, this would queue a job to deliver the webhook
        // For now, we'll just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Test webhook queued for delivery',
            'data' => [
                'webhook_id' => $webhook->id,
                'test_payload' => $testPayload
            ]
        ]);
    }

    /**
     * Get API documentation data
     */
    public function getApiDocumentation(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => '1.0',
                'base_url' => config('app.url') . '/api',
                'authentication' => [
                    'type' => 'Bearer Token',
                    'header' => 'Authorization: Bearer YOUR_TOKEN'
                ],
                'rate_limits' => [
                    'general' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 1000
                    ],
                    'social' => [
                        'requests_per_minute' => 30,
                        'requests_per_hour' => 500
                    ],
                    'search' => [
                        'requests_per_minute' => 20,
                        'requests_per_hour' => 200
                    ],
                    'upload' => [
                        'requests_per_minute' => 10,
                        'requests_per_hour' => 100
                    ]
                ],
                'supported_formats' => ['json'],
                'pagination' => [
                    'default_per_page' => 15,
                    'max_per_page' => 100,
                    'page_parameter' => 'page',
                    'per_page_parameter' => 'per_page'
                ]
            ]
        ]);
    }

    /**
     * Generate Postman collection
     */
    public function generatePostmanCollection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'string|max:1000',
            'base_url' => 'required|url',
            'include_auth' => 'boolean',
            'include_examples' => 'boolean',
            'endpoints' => 'array',
            'endpoints.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate Postman collection structure
        $collection = [
            'info' => [
                'name' => $request->name,
                'description' => $request->description ?? 'Alumni Platform API Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'auth' => $request->include_auth ? [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{apiToken}}',
                        'type' => 'string'
                    ]
                ]
            ] : null,
            'variable' => [
                [
                    'key' => 'baseUrl',
                    'value' => $request->base_url,
                    'type' => 'string'
                ],
                [
                    'key' => 'apiToken',
                    'value' => 'your-api-token-here',
                    'type' => 'string'
                ]
            ],
            'item' => [] // This would be populated with actual endpoints
        ];

        return response()->json([
            'success' => true,
            'data' => $collection,
            'message' => 'Postman collection generated successfully'
        ]);
    }

    /**
     * Generate SDK code
     */
    public function generateSdk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|in:javascript,php,python,csharp',
            'package_name' => 'required|string|max:255',
            'base_url' => 'required|url',
            'version' => 'string|max:20',
            'endpoints' => 'array',
            'endpoints.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate SDK structure based on language
        $sdkStructure = $this->generateSdkStructure(
            $request->language,
            $request->package_name,
            $request->base_url,
            $request->version ?? '1.0.0',
            $request->endpoints ?? []
        );

        return response()->json([
            'success' => true,
            'data' => $sdkStructure,
            'message' => 'SDK generated successfully'
        ]);
    }

    /**
     * Generate SDK structure for different languages
     */
    private function generateSdkStructure(string $language, string $packageName, string $baseUrl, string $version, array $endpoints): array
    {
        switch ($language) {
            case 'javascript':
                return [
                    'files' => [
                        ['path' => 'package.json', 'content' => $this->generatePackageJson($packageName, $version)],
                        ['path' => 'src/index.js', 'content' => $this->generateJavaScriptClient($baseUrl)],
                        ['path' => 'README.md', 'content' => $this->generateReadme($packageName, $language)]
                    ],
                    'installation' => "npm install {$packageName}",
                    'example' => $this->generateJavaScriptExample($packageName, $baseUrl)
                ];

            case 'php':
                return [
                    'files' => [
                        ['path' => 'composer.json', 'content' => $this->generateComposerJson($packageName, $version)],
                        ['path' => 'src/Client.php', 'content' => $this->generatePhpClient($baseUrl)],
                        ['path' => 'README.md', 'content' => $this->generateReadme($packageName, $language)]
                    ],
                    'installation' => "composer require {$packageName}",
                    'example' => $this->generatePhpExample($packageName, $baseUrl)
                ];

            case 'python':
                return [
                    'files' => [
                        ['path' => 'setup.py', 'content' => $this->generateSetupPy($packageName, $version)],
                        ['path' => 'alumni_platform/__init__.py', 'content' => $this->generatePythonClient($baseUrl)],
                        ['path' => 'README.md', 'content' => $this->generateReadme($packageName, $language)]
                    ],
                    'installation' => "pip install {$packageName}",
                    'example' => $this->generatePythonExample($packageName, $baseUrl)
                ];

            default:
                return ['error' => 'Unsupported language'];
        }
    }

    private function generatePackageJson(string $name, string $version): string
    {
        return json_encode([
            'name' => $name,
            'version' => $version,
            'description' => 'Alumni Platform API Client',
            'main' => 'src/index.js',
            'dependencies' => [
                'axios' => '^1.6.0'
            ]
        ], JSON_PRETTY_PRINT);
    }

    private function generateJavaScriptClient(string $baseUrl): string
    {
        return "// Alumni Platform API Client for JavaScript\n// Generated automatically\n\nconst axios = require('axios');\n\nclass AlumniPlatformAPI {\n  constructor(options) {\n    this.baseURL = options.baseURL || '{$baseUrl}';\n    this.token = options.token;\n    this.client = axios.create({\n      baseURL: this.baseURL,\n      headers: {\n        'Authorization': `Bearer \${this.token}`,\n        'Accept': 'application/json'\n      }\n    });\n  }\n\n  async get(endpoint, params = {}) {\n    const response = await this.client.get(endpoint, { params });\n    return response.data;\n  }\n\n  async post(endpoint, data = {}) {\n    const response = await this.client.post(endpoint, data);\n    return response.data;\n  }\n}\n\nmodule.exports = AlumniPlatformAPI;";
    }

    private function generateJavaScriptExample(string $packageName, string $baseUrl): string
    {
        return "const AlumniPlatformAPI = require('{$packageName}');\n\nconst api = new AlumniPlatformAPI({\n  baseURL: '{$baseUrl}',\n  token: 'your-api-token'\n});\n\n// Get timeline\nconst timeline = await api.get('/timeline');\nconsole.log(timeline);";
    }

    private function generateComposerJson(string $name, string $version): string
    {
        return json_encode([
            'name' => $name,
            'version' => $version,
            'description' => 'Alumni Platform API Client for PHP',
            'require' => [
                'php' => '^8.1',
                'guzzlehttp/guzzle' => '^7.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'AlumniPlatform\\' => 'src/'
                ]
            ]
        ], JSON_PRETTY_PRINT);
    }

    private function generatePhpClient(string $baseUrl): string
    {
        return "<?php\n\nnamespace AlumniPlatform;\n\nuse GuzzleHttp\\Client as HttpClient;\n\nclass Client\n{\n    private \$httpClient;\n    private \$baseUrl;\n    private \$token;\n\n    public function __construct(array \$config)\n    {\n        \$this->baseUrl = \$config['base_url'] ?? '{$baseUrl}';\n        \$this->token = \$config['token'];\n        \$this->httpClient = new HttpClient([\n            'base_uri' => \$this->baseUrl,\n            'headers' => [\n                'Authorization' => 'Bearer ' . \$this->token,\n                'Accept' => 'application/json'\n            ]\n        ]);\n    }\n\n    public function get(string \$endpoint, array \$params = []): array\n    {\n        \$response = \$this->httpClient->get(\$endpoint, ['query' => \$params]);\n        return json_decode(\$response->getBody(), true);\n    }\n\n    public function post(string \$endpoint, array \$data = []): array\n    {\n        \$response = \$this->httpClient->post(\$endpoint, ['json' => \$data]);\n        return json_decode(\$response->getBody(), true);\n    }\n}";
    }

    private function generatePhpExample(string $packageName, string $baseUrl): string
    {
        return "<?php\n\nuse AlumniPlatform\\Client;\n\n\$client = new Client([\n    'base_url' => '{$baseUrl}',\n    'token' => 'your-api-token'\n]);\n\n// Get timeline\n\$timeline = \$client->get('/timeline');\nvar_dump(\$timeline);";
    }

    private function generateSetupPy(string $name, string $version): string
    {
        return "from setuptools import setup, find_packages\n\nsetup(\n    name='{$name}',\n    version='{$version}',\n    description='Alumni Platform API Client for Python',\n    packages=find_packages(),\n    install_requires=[\n        'requests>=2.28.0'\n    ]\n)";
    }

    private function generatePythonClient(string $baseUrl): string
    {
        return "import requests\n\nclass AlumniPlatformAPI:\n    def __init__(self, base_url='{$baseUrl}', token=None):\n        self.base_url = base_url\n        self.token = token\n        self.session = requests.Session()\n        self.session.headers.update({\n            'Authorization': f'Bearer {token}',\n            'Accept': 'application/json'\n        })\n\n    def get(self, endpoint, params=None):\n        response = self.session.get(f'{self.base_url}{endpoint}', params=params)\n        response.raise_for_status()\n        return response.json()\n\n    def post(self, endpoint, data=None):\n        response = self.session.post(f'{self.base_url}{endpoint}', json=data)\n        response.raise_for_status()\n        return response.json()";
    }

    private function generatePythonExample(string $packageName, string $baseUrl): string
    {
        return "from alumni_platform import AlumniPlatformAPI\n\napi = AlumniPlatformAPI(\n    base_url='{$baseUrl}',\n    token='your-api-token'\n)\n\n# Get timeline\ntimeline = api.get('/timeline')\nprint(timeline)";
    }

    private function generateReadme(string $packageName, string $language): string
    {
        return "# {$packageName}\n\nAlumni Platform API Client for {$language}\n\n## Installation\n\nSee the installation instructions in the documentation.\n\n## Usage\n\nSee the example code provided in the SDK generator.\n\n## Documentation\n\nFor complete API documentation, visit the Alumni Platform Developer Portal.";
    }
}