# Developer Training Materials

This comprehensive developer training guide covers all aspects of developing on the Alumate platform.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Architecture Overview](#architecture-overview)
3. [API Development](#api-development)
4. [Component Development](#component-development)
5. [Analytics Services](#analytics-services)
6. [Testing and Quality Assurance](#testing-and-quality-assurance)
7. [Security Best Practices](#security-best-practices)
8. [Performance Optimization](#performance-optimization)

---

## Development Environment Setup

### Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 8.3+ | Backend language |
| Node.js | 18+ | Frontend tooling |
| Composer | 2.x | PHP package manager |
| PostgreSQL | 17+ | Primary database |
| Redis | Latest | Caching and queues |
| Git | Latest | Version control |

### Local Development Setup

#### 1. Clone Repository

```bash
# Clone the repository
git clone https://github.com/alumate/alumate.git
cd alumate

# Checkout development branch
git checkout develop
```

#### 2. Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# Install development dependencies
composer install --dev
```

#### 3. Install Node Dependencies

```bash
# Install npm dependencies
npm install

# Install development tools
npm install --save-dev
```

#### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

#### 5. Database Setup

```bash
# Create database
createdb alumate_dev

# Run migrations
php artisan migrate

# Run tenant migrations
php artisan tenants:migrate

# Seed database
php artisan db:seed

# Create sample data
php artisan db:seed --class=DemoDataSeeder
```

#### 6. Start Development Servers

```bash
# Terminal 1: Start Vite
npm run dev

# Terminal 2: Start Laravel server
php artisan serve --host=127.0.0.1 --port=8080
```

### IDE Configuration

#### VSCode Settings

Recommended extensions:
- PHP Intelephense
- Vue 3 Support
- ESLint
- Prettier
- GitLens

#### PHPStorm Settings

- Configure PHP interpreter
- Set up database connection
- Configure Laravel plugin

---

## Architecture Overview

### System Architecture

Alumate follows a modern modular architecture:

```
┌─────────────────────────────────────────────────────┐
│                   Frontend Layer                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │   Vue.js    │  │   TypeScript│  │    Vite     │  │
│  │   3.x       │  │    4.x      │  │   Build     │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  │
├─────────────────────────────────────────────────────┤
│                   API Layer                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │   Laravel   │  │   REST API  │  │  Inertia.js │  │
│  │   11.x      │  │   Endpoints │  │   SSR       │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  │
├─────────────────────────────────────────────────────┤
│                  Service Layer                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │  Business   │  │   Domain    │  │   Helper    │  │
│  │  Services   │  │   Services  │  │  Services   │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  │
├─────────────────────────────────────────────────────┤
│                  Data Layer                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │  PostgreSQL │  │    Redis    │  │Elasticsearch│  │
│  │  17+       │  │  Caching    │  │   Search    │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Project Structure

```
alumate/
├── app/                    # Application code
│   ├── Console/           # CLI commands
│   ├── Events/            # Event classes
│   ├── Exceptions/        # Custom exceptions
│   ├── Exports/           # Data exports
│   ├── Jobs/              # Queueable jobs
│   ├── Listeners/         # Event listeners
│   ├── Mail/              # Email templates
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Observers/         # Model observers
│   ├── Providers/         # Service providers
│   ├── Services/          # Business logic
│   └── Traits/            # Reusable traits
├── bootstrap/             # Application bootstrap
├── config/               # Configuration files
├── database/             # Migrations and seeds
├── docs/                  # Documentation
├── infrastructure/        # Docker and deployment
├── public/                # Public assets
├── resources/             # Frontend resources
├── routes/                # Route definitions
├── scripts/               # Utility scripts
├── storage/               # Storage files
└── tests/                 # Test suite
```

---

## API Development

### API Conventions

All API endpoints follow these conventions:

| Aspect | Convention |
|--------|------------|
| URL Format | `/api/v{version}/{resource}` |
| Versioning | URL-based versioning |
| Authentication | Bearer token (JWT) |
| Response Format | JSON |
| Error Format | JSON API specification |

### Creating API Endpoints

#### 1. Define Route

```php
// routes/api.php
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
```

#### 2. Create Controller

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Show single user
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Store new user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create($validated);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully'
        ], 201);
    }
}
```

#### 3. Create Resource

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### API Error Handling

```php
// Standard error response
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid",
        "errors": {
            "email": ["The email field is required."]
        }
    }
}
```

---

## Component Development

### Vue.js Component Structure

Components follow this structure:

```
resources/js/
├── components/
│   ├── common/           # Reusable UI components
│   ├── layout/           # Layout components
│   └── features/        # Feature-specific components
├── composables/         # Vue composables
├── layouts/             # Page layouts
├── Pages/               # Inertia.js pages
├── services/            # API services
└── stores/              # Pinia stores
```

### Creating a Component

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  title: string;
  items?: Item[];
}

interface Item {
  id: number;
  name: string;
}

const props = withDefaults(defineProps<Props>(), {
  items: () => []
});

const { t } = useI18n();
const selectedItems = ref<number[]>([]);

const emit = defineEmits<{
  (e: 'select', items: number[]): void;
  (e: 'create'): void;
}>();

const canSelect = computed(() => selectedItems.value.length > 0);

function handleSelect(itemId: number) {
  if (selectedItems.value.includes(itemId)) {
    selectedItems.value = selectedItems.value.filter(id => id !== itemId);
  } else {
    selectedItems.value.push(itemId);
  }
  emit('select', selectedItems.value);
}
</script>

<template>
  <div class="component-container">
    <header class="component-header">
      <h2>{{ title }}</h2>
      <button
        v-if="canSelect"
        class="btn-primary"
        @click="$emit('create')"
      >
        {{ t('common.create') }}
      </button>
    </header>
    
    <ul class="item-list">
      <li
        v-for="item in items"
        :key="item.id"
        :class="{ selected: selectedItems.includes(item.id) }"
        @click="handleSelect(item.id)"
      >
        {{ item.name }}
      </li>
    </ul>
  </div>
</template>
```

### Using Composables

```typescript
// composables/usePagination.ts
import { ref, computed } from 'vue';

export function usePagination<T>(items: T[], pageSize = 15) {
  const currentPage = ref(1);
  
  const totalPages = computed(() => 
    Math.ceil(items.length / pageSize)
  );
  
  const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    const end = start + pageSize;
    return items.slice(start, end);
  });
  
  function nextPage() {
    if (currentPage.value < totalPages.value) {
      currentPage.value++;
    }
  }
  
  function prevPage() {
    if (currentPage.value > 1) {
      currentPage.value--;
    }
  }
  
  function goToPage(page: number) {
    currentPage.value = Math.max(1, Math.min(page, totalPages.value));
  }
  
  return {
    currentPage,
    totalPages,
    paginatedItems,
    nextPage,
    prevPage,
    goToPage
  };
}
```

---

## Analytics Services

### Analytics Architecture

The analytics system consists of:

| Component | Purpose |
|-----------|---------|
| Event Collector | Capture user interactions |
| Data Processor | Transform and aggregate |
| Storage Layer | Time-series storage |
| Query Engine | Analytics queries |
| Visualization | Dashboard rendering |

### Creating Analytics Events

```php
namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Auth;

class AnalyticsService
{
    /**
     * Track an event
     */
    public function track(
        string $eventType,
        array $properties = [],
        ?int $userId = null
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'user_id' => $userId ?? Auth::id(),
            'event_type' => $eventType,
            'properties' => $properties,
            'timestamp' => now(),
            'session_id' => session()->getId(),
            'tenant_id' => tenant('id'),
        ]);
    }
    
    /**
     * Track page view
     */
    public function trackPageView(
        string $page,
        ?string $referrer = null
    ): void {
        $this->track('page_view', [
            'page' => $page,
            'referrer' => $referrer,
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]);
    }
    
    /**
     * Track user action
     */
    public function trackAction(
        string $action,
        array $metadata = []
    ): void {
        $this->track('user_action', [
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
```

### Querying Analytics

```php
// Get user activity for date range
$activity = AnalyticsEvent::query()
    ->where('user_id', $userId)
    ->whereBetween('timestamp', [$startDate, $endDate])
    ->groupBy('event_type')
    ->selectRaw('event_type, count(*) as count')
    ->pluck('count', 'event_type');

// Get daily active users
$dailyActiveUsers = AnalyticsEvent::query()
    ->where('event_type', 'page_view')
    ->where('timestamp', '>=', now()->subDays(30))
    ->selectRaw('DATE(timestamp) as date, COUNT(DISTINCT user_id) as users')
    ->groupBy('date')
    ->pluck('users', 'date');
```

---

## Testing and Quality Assurance

### Test Structure

```
tests/
├── Unit/                  # Unit tests
│   ├── Models/           # Model tests
│   ├── Services/         # Service tests
│   └── Helpers/          # Helper tests
├── Feature/              # Feature tests
│   ├── Api/             # API endpoint tests
│   ├── Jobs/            # Job tests
│   └── Console/         # Command tests
├── Integration/          # Integration tests
└── Browser/              # Browser tests
```

### Writing Tests

#### Unit Test Example

```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserService;
use App\Models\User;

class UserServiceTest extends TestCase
{
    protected UserService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }
    
    /** @test */
    public function it_can_create_a_user(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];
        
        $user = $this->service->createUser($data);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertDatabaseHas('users', [
            'email' => $data['email']
        ]);
    }
}
```

#### Feature Test Example

```php
namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class UserApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_list_users(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $response = $this->getJson('/api/v1/users');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    }
}
```

### Running Tests

```bash
# Run all tests
.\artisan test

# Run specific test file
.\artisan test tests/Unit/Services/UserServiceTest.php

# Run with coverage
.\artisan test --coverage

# Run PHPUnit directly
./vendor/bin/phpunit
```

---

## Security Best Practices

### Authentication

```php
// Always use middleware for protected routes
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::apiResource('users', UserController::class);
});

// Use policy for authorization
public function update(User $user)
{
    $this->authorize('update', $user);
    // ...
}
```

### Input Validation

```php
// Always validate input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'role' => 'sometimes|in:user,admin',
]);

// Use form requests for complex validation
public function store(UserStoreRequest $request)
{
    // Request is already validated
}
```

### SQL Injection Prevention

```php
// Always use query builder or Eloquent
User::where('email', $request->email)->first();

// Never use raw SQL with user input
// BAD: DB::select("SELECT * FROM users WHERE email = '$email'")
// GOOD: DB::select("SELECT * FROM WHERE email = ?", [$email])
```

### XSS Prevention

```typescript
// Vue automatically escapes content
// Always use v-html with caution
<div v-html="trustedContent"></div>

// Never render untrusted content without sanitization
import DOMPurify from 'dompurify';
const sanitized = DOMPurify.sanitize(userContent);
```

---

## Performance Optimization

### Caching Strategies

```php
// Cache query results
$users = Cache::remember('users.active', 3600, function () {
    return User::where('active', true)->get();
});

// Cache API responses
return Cache::tags(['api', 'users'])->remember(
    "users.{$userId}",
    3600,
    fn() => new UserResource($user)
);
```

### Query Optimization

```php
// Use eager loading to avoid N+1
$users = User::with(['profile', 'roles', 'permissions'])
    ->get();

// Select only needed columns
$users = User::select(['id', 'name', 'email'])
    ->where('active', true)
    ->get();

// Use indexes for frequently queried columns
```

### Queue Optimization

```php
// Dispatch jobs to appropriate queue
DispatchAnalyticsJob::dispatch($data)
    ->onQueue('analytics')
    ->delay(now()->addMinutes(5));

// Use chunking for large datasets
User::chunk(200, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

---

## Next Steps

After completing developer training:

1. Set up local development environment
2. Review existing codebase patterns
3. Complete hands-on exercises
4. Pass assessment
5. Start contributing to the project

---

## Assessment

Take the [Developer Training Assessment](assessment.md#developer-training-assessment) to verify your knowledge.

**Return to**: [Training Overview](README.md)
