# API Development Guide

This guide covers best practices and patterns for developing APIs in the Alumate Platform, including RESTful design, authentication, validation, and error handling.

## Table of Contents

1. [API Architecture](#api-architecture)
2. [Route Definition](#route-definition)
3. [Controller Design](#controller-design)
4. [Request Validation](#request-validation)
5. [Response Formatting](#response-formatting)
6. [Authentication & Authorization](#authentication--authorization)
7. [Error Handling](#error-handling)
8. [Versioning](#versioning)
9. [Rate Limiting](#rate-limiting)
10. [Testing APIs](#testing-apis)
11. [Documentation](#documentation)

## API Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── V1/
│   │       │   ├── AlumniController.php
│   │       │   ├── EventController.php
│   │       │   └── JobController.php
│   │       └── V2/
│   │           └── ...
│   ├── Requests/
│   │   └── Api/
│   │       ├── CreateAlumniRequest.php
│   │       └── UpdateAlumniRequest.php
│   ├── Resources/
│   │   ├── AlumniResource.php
│   │   └── AlumniCollection.php
│   └── Middleware/
│       ├── ApiRateLimitMiddleware.php
│       └── ApiVersionMiddleware.php
routes/
└── api.php
```

### Design Principles

1. **RESTful Design**: Follow REST conventions for resource naming and HTTP methods
2. **Consistent Responses**: Use standardized response formats
3. **Proper Status Codes**: Return appropriate HTTP status codes
4. **Versioning**: Support API versioning for backward compatibility
5. **Documentation**: Document all endpoints with OpenAPI/Swagger

## Route Definition

### Basic Route Structure

```php
// routes/api.php

use App\Http\Controllers\Api\V1\AlumniController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\JobController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('health', fn() => response()->json(['status' => 'ok']));
    
    // Authentication routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Alumni routes
        Route::apiResource('alumni', AlumniController::class);
        Route::get('alumni/{alumni}/connections', [AlumniController::class, 'connections']);
        Route::post('alumni/{alumni}/connect', [AlumniController::class, 'connect']);
        
        // Event routes
        Route::apiResource('events', EventController::class);
        Route::post('events/{event}/register', [EventController::class, 'register']);
        
        // Job routes
        Route::apiResource('jobs', JobController::class);
        Route::post('jobs/{job}/apply', [JobController::class, 'apply']);
    });
});
```

### Route Naming Conventions

| HTTP Method | Route | Action | Name |
|-------------|-------|--------|------|
| GET | `/api/v1/alumni` | index | `api.v1.alumni.index` |
| GET | `/api/v1/alumni/{id}` | show | `api.v1.alumni.show` |
| POST | `/api/v1/alumni` | store | `api.v1.alumni.store` |
| PUT/PATCH | `/api/v1/alumni/{id}` | update | `api.v1.alumni.update` |
| DELETE | `/api/v1/alumni/{id}` | destroy | `api.v1.alumni.destroy` |

### Nested Resources

```php
// Nested resource routes
Route::apiResource('alumni.experiences', AlumniExperienceController::class)
    ->shallow();

// Results in:
// GET    /api/v1/alumni/{alumni}/experiences
// POST   /api/v1/alumni/{alumni}/experiences
// GET    /api/v1/experiences/{experience}
// PUT    /api/v1/experiences/{experience}
// DELETE /api/v1/experiences/{experience}
```

## Controller Design

### Base API Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    /**
     * Return success response
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return error response
     */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return paginated response
     */
    protected function paginated($resource, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource->items(),
            'meta' => [
                'current_page' => $resource->currentPage(),
                'last_page' => $resource->lastPage(),
                'per_page' => $resource->perPage(),
                'total' => $resource->total(),
            ],
            'links' => [
                'first' => $resource->url(1),
                'last' => $resource->url($resource->lastPage()),
                'prev' => $resource->previousPageUrl(),
                'next' => $resource->nextPageUrl(),
            ],
        ]);
    }
}
```

### Resource Controller Example

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\CreateAlumniRequest;
use App\Http\Requests\Api\UpdateAlumniRequest;
use App\Http\Resources\AlumniResource;
use App\Http\Resources\AlumniCollection;
use App\Models\Alumni;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumniController extends ApiController
{
    public function __construct(
        private readonly AlumniService $alumniService
    ) {}

    /**
     * Display a listing of alumni
     *
     * @OA\Get(
     *     path="/api/v1/alumni",
     *     summary="List all alumni",
     *     tags={"Alumni"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/AlumniCollection")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = $this->alumniService->getAlumni(
            filters: $request->only(['graduation_year', 'major', 'company']),
            perPage: $request->input('per_page', 15)
        );

        return $this->paginated(
            AlumniResource::collection($alumni),
            'Alumni retrieved successfully'
        );
    }

    /**
     * Store a newly created alumni
     */
    public function store(CreateAlumniRequest $request): JsonResponse
    {
        $alumni = $this->alumniService->create($request->validated());

        return $this->success(
            new AlumniResource($alumni),
            'Alumni created successfully',
            201
        );
    }

    /**
     * Display the specified alumni
     */
    public function show(Alumni $alumni): JsonResponse
    {
        $this->authorize('view', $alumni);

        return $this->success(
            new AlumniResource($alumni->load(['experiences', 'educations', 'skills'])),
            'Alumni retrieved successfully'
        );
    }

    /**
     * Update the specified alumni
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $this->authorize('update', $alumni);

        $alumni = $this->alumniService->update($alumni, $request->validated());

        return $this->success(
            new AlumniResource($alumni),
            'Alumni updated successfully'
        );
    }

    /**
     * Remove the specified alumni
     */
    public function destroy(Alumni $alumni): JsonResponse
    {
        $this->authorize('delete', $alumni);

        $this->alumniService->delete($alumni);

        return $this->success(null, 'Alumni deleted successfully');
    }

    /**
     * Get alumni connections
     */
    public function connections(Alumni $alumni): JsonResponse
    {
        $connections = $this->alumniService->getConnections($alumni);

        return $this->success(
            AlumniResource::collection($connections),
            'Connections retrieved successfully'
        );
    }

    /**
     * Send connection request
     */
    public function connect(Request $request, Alumni $alumni): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $this->alumniService->sendConnectionRequest(
            auth()->user(),
            $alumni,
            $request->input('message')
        );

        return $this->success(null, 'Connection request sent successfully', 201);
    }
}
```

## Request Validation

### Form Request Classes

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:alumni,email'],
            'graduation_year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 10)],
            'major' => ['required', 'string', 'max:255'],
            'degree' => ['required', 'string', 'in:bachelor,master,doctorate,associate'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'linkedin_url' => ['nullable', 'url', 'regex:/linkedin\.com/'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An alumni with this email already exists.',
            'graduation_year.max' => 'Graduation year cannot be more than 10 years in the future.',
            'linkedin_url.regex' => 'Please provide a valid LinkedIn URL.',
        ];
    }

    /**
     * Handle failed validation for API requests
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

### Update Request with Conditional Rules

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('alumni'));
    }

    public function rules(): array
    {
        $alumniId = $this->route('alumni')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('alumni', 'email')->ignore($alumniId),
            ],
            'graduation_year' => ['sometimes', 'integer', 'min:1900', 'max:' . (date('Y') + 10)],
            'major' => ['sometimes', 'string', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

## Response Formatting

### API Resources

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->canViewEmail($request), $this->email),
            'graduation_year' => $this->graduation_year,
            'major' => $this->major,
            'degree' => $this->degree,
            'current_company' => $this->current_company,
            'current_position' => $this->current_position,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'linkedin_url' => $this->linkedin_url,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'experiences' => ExperienceResource::collection($this->whenLoaded('experiences')),
            'educations' => EducationResource::collection($this->whenLoaded('educations')),
            'connections_count' => $this->when(
                $this->connections_count !== null,
                $this->connections_count
            ),
            'is_connected' => $this->when(
                $request->user(),
                fn() => $this->isConnectedTo($request->user())
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    private function canViewEmail(Request $request): bool
    {
        return $request->user()?->id === $this->user_id
            || $request->user()?->isAdmin()
            || $this->isConnectedTo($request->user());
    }
}
```

### Resource Collections

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AlumniCollection extends ResourceCollection
{
    public $collects = AlumniResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_count' => $this->total(),
                'graduation_years' => $this->getGraduationYearRange(),
            ],
        ];
    }

    private function getGraduationYearRange(): array
    {
        $years = $this->collection->pluck('graduation_year')->unique()->sort();
        
        return [
            'min' => $years->first(),
            'max' => $years->last(),
        ];
    }
}
```

## Authentication & Authorization

### Sanctum Authentication

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // Protected routes
});

// Controller usage
public function index(Request $request)
{
    $user = $request->user(); // Get authenticated user
    // ...
}
```

### Token Generation

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
```

### Policy-Based Authorization

```php
<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

class AlumniPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Alumni $alumni): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }

    public function update(User $user, Alumni $alumni): bool
    {
        return $user->id === $alumni->user_id
            || $user->hasRole('admin');
    }

    public function delete(User $user, Alumni $alumni): bool
    {
        return $user->hasRole('admin');
    }
}
```

## Error Handling

### Global Exception Handler

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Log unexpected errors
        report($e);

        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'Server error',
        ], 500);
    }
}
```

### Custom API Exceptions

```php
<?php

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected int $statusCode = 400;
    protected array $errors = [];

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = []
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }
}

// Usage
throw new ApiException('Alumni not found', 404);
throw new ApiException('Invalid operation', 400, ['field' => ['Error message']]);
```

## Versioning

### URL-Based Versioning

```php
// routes/api.php
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('alumni', V1\AlumniController::class);
});

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::apiResource('alumni', V2\AlumniController::class);
});
```

### Header-Based Versioning

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $version = $request->header('Accept-Version', 'v1');
        
        // Store version for later use
        $request->attributes->set('api_version', $version);
        
        return $next($request);
    }
}
```

## Rate Limiting

### Configure Rate Limits

```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('api-heavy', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('api-auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

### Apply Rate Limits

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    // Standard rate limited routes
});

Route::middleware(['throttle:api-heavy'])->group(function () {
    // Heavy operation routes (exports, reports)
    Route::get('reports/generate', [ReportController::class, 'generate']);
});

Route::middleware(['throttle:api-auth'])->group(function () {
    // Auth routes with stricter limits
    Route::post('auth/login', [AuthController::class, 'login']);
});
```

## Testing APIs

### Feature Test Example

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlumniApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_alumni(): void
    {
        Alumni::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'graduation_year', 'major']
                ],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_can_create_alumni(): void
    {
        $this->user->assignRole('admin');

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'graduation_year' => 2023,
            'major' => 'Computer Science',
            'degree' => 'bachelor',
        ];

        $response = $this->postJson('/api/v1/alumni', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('alumni', ['email' => 'john@example.com']);
    }

    public function test_validation_errors_return_422(): void
    {
        $response = $this->postJson('/api/v1/alumni', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'graduation_year']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        // Clear authentication
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(401);
    }
}
```

## Documentation

### OpenAPI/Swagger Documentation

```php
/**
 * @OA\Info(
 *     title="Alumate Platform API",
 *     version="1.0.0",
 *     description="API documentation for the Alumate Platform",
 *     @OA\Contact(
 *         email="api-support@alumate.edu"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Alumni",
 *     type="object",
 *     required={"id", "name", "email", "graduation_year"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="graduation_year", type="integer", example=2023),
 *     @OA\Property(property="major", type="string", example="Computer Science"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
```

### Generate Documentation

```bash
# Generate OpenAPI documentation
php artisan l5-swagger:generate

# View documentation at /api/documentation
```

## Best Practices Summary

### Do's
- ✅ Use Form Request classes for validation
- ✅ Return consistent response formats
- ✅ Use API Resources for response transformation
- ✅ Implement proper error handling
- ✅ Version your APIs
- ✅ Apply rate limiting
- ✅ Write comprehensive tests
- ✅ Document all endpoints

### Don'ts
- ❌ Return raw model data
- ❌ Use inconsistent response structures
- ❌ Expose sensitive data in responses
- ❌ Skip validation
- ❌ Ignore error handling
- ❌ Forget authentication/authorization

---

**Related Documentation**:
- [Coding Standards](./coding-standards.md)
- [Testing Guide](./testing-guide.md)
- [Security Best Practices](./security-best-practices.md)

**Last Updated**: February 2026
