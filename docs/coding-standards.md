# Modern Alumni Platform - Coding Standards

## Overview

This document establishes coding standards and conventions for the Modern Alumni Platform to ensure consistency, maintainability, and quality across the codebase.

## Table of Contents

1. [General Principles](#general-principles)
2. [PHP/Laravel Standards](#phplaravel-standards)
3. [Vue.js/TypeScript Standards](#vuejstypescript-standards)
4. [CSS/Tailwind Standards](#csstailwind-standards)
5. [Database Standards](#database-standards)
6. [Testing Standards](#testing-standards)
7. [File Organization](#file-organization)
8. [Naming Conventions](#naming-conventions)
9. [Documentation Standards](#documentation-standards)
10. [Performance Guidelines](#performance-guidelines)

## General Principles

### Code Quality
- **Readability First**: Code should be self-documenting and easy to understand
- **Consistency**: Follow established patterns throughout the codebase
- **DRY Principle**: Don't Repeat Yourself - extract common functionality
- **SOLID Principles**: Follow SOLID design principles for maintainable code
- **Security First**: Always consider security implications

### Version Control
- Use meaningful commit messages following conventional commits
- Keep commits atomic and focused on a single change
- Use feature branches for new development
- Write descriptive pull request descriptions

## PHP/Laravel Standards

### PSR Standards
Follow PSR-12 coding standards for PHP:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AlumniService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService
    ) {}

    public function findAlumniByGraduationYear(int $year): Collection
    {
        return $this->userRepository
            ->whereHas('educations', function ($query) use ($year) {
                $query->where('graduation_year', $year);
            })
            ->get();
    }
}
```

### Laravel Conventions

#### Controllers
- Keep controllers thin - delegate business logic to services
- Use Form Request classes for validation
- Return consistent response formats

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAlumniRequest;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;

class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService $alumniService
    ) {}

    public function store(CreateAlumniRequest $request): JsonResponse
    {
        $alumni = $this->alumniService->create($request->validated());

        return response()->json([
            'data' => $alumni,
            'message' => 'Alumni created successfully'
        ], 201);
    }
}
```

#### Models
- Use explicit return types
- Implement proper relationships
- Use model factories for testing

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumni extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'graduation_year',
        'major',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function connections(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'alumni_connections', 'alumni_id', 'connected_alumni_id')
            ->withTimestamps();
    }
}
```

#### Services
- Use dependency injection
- Keep methods focused and single-purpose
- Handle exceptions appropriately

```php
<?php

namespace App\Services;

use App\Models\Alumni;
use App\Repositories\AlumniRepository;
use Illuminate\Support\Collection;

class AlumniConnectionService
{
    public function __construct(
        private readonly AlumniRepository $alumniRepository,
        private readonly NotificationService $notificationService
    ) {}

    public function createConnection(Alumni $alumni, Alumni $targetAlumni): bool
    {
        if ($this->connectionExists($alumni, $targetAlumni)) {
            throw new \InvalidArgumentException('Connection already exists');
        }

        $alumni->connections()->attach($targetAlumni->id);
        
        $this->notificationService->sendConnectionNotification($targetAlumni, $alumni);

        return true;
    }

    private function connectionExists(Alumni $alumni, Alumni $targetAlumni): bool
    {
        return $alumni->connections()->where('connected_alumni_id', $targetAlumni->id)->exists();
    }
}
```

## Vue.js/TypeScript Standards

### Component Structure
Use Composition API with TypeScript:

```vue
<template>
  <div class="alumni-card">
    <div class="alumni-card__header">
      <h3 class="alumni-card__name">{{ alumni.name }}</h3>
      <span class="alumni-card__year">Class of {{ alumni.graduationYear }}</span>
    </div>
    
    <div class="alumni-card__content">
      <p class="alumni-card__major">{{ alumni.major }}</p>
      <p class="alumni-card__company">{{ alumni.currentCompany }}</p>
    </div>

    <div class="alumni-card__actions">
      <Button 
        variant="outline" 
        size="sm" 
        @click="handleConnect"
        :disabled="isConnecting"
      >
        {{ isConnected ? 'Connected' : 'Connect' }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import type { Alumni } from '@/types/alumni'

interface Props {
  alumni: Alumni
  currentUserId: number
}

interface Emits {
  (e: 'connect', alumniId: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isConnecting = ref(false)

const isConnected = computed(() => {
  return props.alumni.connections?.some(
    connection => connection.id === props.currentUserId
  ) ?? false
})

const handleConnect = async (): Promise<void> => {
  if (isConnected.value || isConnecting.value) return

  isConnecting.value = true
  
  try {
    emit('connect', props.alumni.id)
  } finally {
    isConnecting.value = false
  }
}
</script>

<style scoped>
.alumni-card {
  @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow;
}

.alumni-card__header {
  @apply flex items-center justify-between mb-3;
}

.alumni-card__name {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.alumni-card__year {
  @apply text-sm text-gray-500 dark:text-gray-400;
}

.alumni-card__content {
  @apply space-y-2 mb-4;
}

.alumni-card__major {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.alumni-card__company {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.alumni-card__actions {
  @apply flex justify-end;
}
</style>
```

### TypeScript Types
Define clear interfaces and types:

```typescript
// types/alumni.ts
export interface Alumni {
  id: number
  name: string
  email: string
  graduationYear: number
  major: string
  currentCompany?: string
  currentPosition?: string
  connections?: Alumni[]
  createdAt: string
  updatedAt: string
}

export interface AlumniConnection {
  id: number
  alumniId: number
  connectedAlumniId: number
  status: 'pending' | 'accepted' | 'rejected'
  createdAt: string
}

export interface AlumniSearchFilters {
  graduationYear?: number
  major?: string
  company?: string
  location?: string
  industry?: string
}
```

### Composables
Create reusable composables for common functionality:

```typescript
// composables/useAlumniConnections.ts
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Alumni, AlumniConnection } from '@/types/alumni'

export function useAlumniConnections() {
  const connections = ref<AlumniConnection[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const pendingConnections = computed(() => 
    connections.value.filter(conn => conn.status === 'pending')
  )

  const acceptedConnections = computed(() => 
    connections.value.filter(conn => conn.status === 'accepted')
  )

  const sendConnectionRequest = async (alumniId: number): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      await router.post('/api/alumni/connections', { alumni_id: alumniId })
    } catch (err) {
      error.value = 'Failed to send connection request'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const acceptConnection = async (connectionId: number): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      await router.patch(`/api/alumni/connections/${connectionId}/accept`)
    } catch (err) {
      error.value = 'Failed to accept connection'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    connections,
    isLoading,
    error,
    pendingConnections,
    acceptedConnections,
    sendConnectionRequest,
    acceptConnection
  }
}
```

## CSS/Tailwind Standards

### Utility-First Approach
Use Tailwind utilities first, custom CSS only when necessary:

```vue
<template>
  <!-- Good: Using Tailwind utilities -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
      Alumni Directory
    </h2>
    
    <!-- Custom component with scoped styles when needed -->
    <div class="alumni-grid">
      <AlumniCard 
        v-for="alumni in alumniList" 
        :key="alumni.id" 
        :alumni="alumni" 
      />
    </div>
  </div>
</template>

<style scoped>
.alumni-grid {
  @apply grid gap-6;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

@media (max-width: 640px) {
  .alumni-grid {
    @apply grid-cols-1;
  }
}
</style>
```

### BEM Methodology for Custom CSS
When custom CSS is needed, use BEM methodology:

```scss
// Good: BEM naming
.alumni-card {
  @apply bg-white rounded-lg shadow-sm;

  &__header {
    @apply flex items-center justify-between p-4 border-b;
  }

  &__title {
    @apply text-lg font-semibold;
  }

  &__content {
    @apply p-4;
  }

  &--featured {
    @apply ring-2 ring-blue-500;
  }

  &--loading {
    @apply opacity-50 pointer-events-none;
  }
}
```

### Responsive Design
Mobile-first responsive design:

```vue
<template>
  <div class="
    grid 
    grid-cols-1 
    gap-4 
    sm:grid-cols-2 
    lg:grid-cols-3 
    xl:grid-cols-4
  ">
    <!-- Content -->
  </div>
</template>
```

## Database Standards

### Migration Naming
Use descriptive migration names with timestamps:

```php
// Good
2024_01_15_143000_create_alumni_connections_table.php
2024_01_15_144000_add_graduation_year_index_to_alumni_table.php

// Bad
create_connections.php
add_index.php
```

### Migration Structure
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained()->onDelete('cascade');
            $table->foreignId('connected_alumni_id')->constrained('alumni')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['alumni_id', 'status']);
            $table->index(['connected_alumni_id', 'status']);
            
            // Unique constraint to prevent duplicate connections
            $table->unique(['alumni_id', 'connected_alumni_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_connections');
    }
};
```

### Model Factories
```php
<?php

namespace Database\Factories;

use App\Models\Alumni;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'graduation_year' => $this->faker->numberBetween(2000, 2024),
            'major' => $this->faker->randomElement([
                'Computer Science',
                'Business Administration',
                'Engineering',
                'Psychology',
                'Biology'
            ]),
            'current_company' => $this->faker->company(),
            'current_position' => $this->faker->jobTitle(),
        ];
    }

    public function recentGraduate(): static
    {
        return $this->state(fn (array $attributes) => [
            'graduation_year' => $this->faker->numberBetween(2020, 2024),
        ]);
    }

    public function withConnections(int $count = 3): static
    {
        return $this->afterCreating(function (Alumni $alumni) use ($count) {
            $connections = Alumni::factory($count)->create();
            $alumni->connections()->attach($connections->pluck('id'));
        });
    }
}
```

## Testing Standards

### Feature Tests
```php
<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_connection_request(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/alumni/connections', [
                'alumni_id' => $alumni->id
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Connection request sent successfully'
            ]);

        $this->assertDatabaseHas('alumni_connections', [
            'alumni_id' => $user->id,
            'connected_alumni_id' => $alumni->id,
            'status' => 'pending'
        ]);
    }

    public function test_user_cannot_send_duplicate_connection_request(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->create();

        // Create existing connection
        $user->connections()->attach($alumni->id, ['status' => 'pending']);

        $response = $this->actingAs($user)
            ->postJson('/api/alumni/connections', [
                'alumni_id' => $alumni->id
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alumni_id']);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit;

use App\Models\Alumni;
use App\Services\AlumniConnectionService;
use Tests\TestCase;

class AlumniConnectionServiceTest extends TestCase
{
    public function test_can_create_connection_between_alumni(): void
    {
        $alumni1 = Alumni::factory()->create();
        $alumni2 = Alumni::factory()->create();

        $service = app(AlumniConnectionService::class);
        $result = $service->createConnection($alumni1, $alumni2);

        $this->assertTrue($result);
        $this->assertTrue($alumni1->connections()->where('connected_alumni_id', $alumni2->id)->exists());
    }

    public function test_throws_exception_for_duplicate_connection(): void
    {
        $alumni1 = Alumni::factory()->create();
        $alumni2 = Alumni::factory()->create();

        // Create existing connection
        $alumni1->connections()->attach($alumni2->id);

        $service = app(AlumniConnectionService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection already exists');

        $service->createConnection($alumni1, $alumni2);
    }
}
```

## File Organization

### Directory Structure
```
app/
├── Console/
├── Events/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   └── Web/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Providers/
├── Services/
└── Traits/

resources/
├── js/
│   ├── Components/
│   │   ├── ui/
│   │   ├── layout/
│   │   ├── common/
│   │   └── [feature]/
│   ├── Pages/
│   ├── composables/
│   ├── types/
│   └── utils/
├── css/
└── views/

tests/
├── Feature/
├── Unit/
└── Integration/
```

## Naming Conventions

### PHP/Laravel
- **Classes**: PascalCase (`AlumniService`, `ConnectionRequest`)
- **Methods**: camelCase (`createConnection`, `findAlumniByYear`)
- **Variables**: camelCase (`$alumniData`, `$connectionStatus`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_CONNECTIONS`, `DEFAULT_PAGE_SIZE`)
- **Database Tables**: snake_case plural (`alumni_connections`, `user_profiles`)
- **Database Columns**: snake_case (`graduation_year`, `created_at`)

### Vue.js/TypeScript
- **Components**: PascalCase (`AlumniCard.vue`, `ConnectionModal.vue`)
- **Props**: camelCase (`alumniData`, `isLoading`)
- **Events**: kebab-case (`alumni-selected`, `connection-created`)
- **CSS Classes**: kebab-case (`alumni-card`, `connection-status`)
- **Files**: kebab-case (`alumni-service.ts`, `connection-types.ts`)

### Routes
- **Web Routes**: kebab-case (`/alumni-directory`, `/career-timeline`)
- **API Routes**: kebab-case with version (`/api/v1/alumni-connections`)

## Documentation Standards

### Code Comments
```php
<?php

/**
 * Service for managing alumni connections and networking features.
 * 
 * This service handles the creation, management, and analysis of connections
 * between alumni members, including connection requests, acceptance, and
 * recommendation algorithms.
 */
class AlumniConnectionService
{
    /**
     * Create a new connection between two alumni.
     * 
     * @param Alumni $alumni The alumni initiating the connection
     * @param Alumni $targetAlumni The alumni to connect with
     * @return bool True if connection was created successfully
     * @throws InvalidArgumentException If connection already exists
     */
    public function createConnection(Alumni $alumni, Alumni $targetAlumni): bool
    {
        // Implementation
    }
}
```

### API Documentation
Use OpenAPI/Swagger documentation:

```php
/**
 * @OA\Post(
 *     path="/api/alumni/connections",
 *     summary="Create a new alumni connection",
 *     tags={"Alumni Connections"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"alumni_id"},
 *             @OA\Property(property="alumni_id", type="integer", example=123)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Connection request sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Connection request sent successfully")
 *         )
 *     )
 * )
 */
public function store(CreateConnectionRequest $request): JsonResponse
{
    // Implementation
}
```

## Performance Guidelines

### Database Optimization
- Use eager loading to prevent N+1 queries
- Add appropriate indexes
- Use database transactions for related operations
- Implement query caching where appropriate

```php
// Good: Eager loading
$alumni = Alumni::with(['connections', 'educations', 'experiences'])
    ->where('graduation_year', 2020)
    ->get();

// Bad: N+1 queries
$alumni = Alumni::where('graduation_year', 2020)->get();
foreach ($alumni as $alumnus) {
    $connections = $alumnus->connections; // N+1 query
}
```

### Frontend Optimization
- Use lazy loading for components
- Implement virtual scrolling for large lists
- Optimize images and assets
- Use proper caching strategies

```vue
<script setup lang="ts">
// Good: Lazy loading
const AlumniModal = defineAsyncComponent(() => import('@/Components/AlumniModal.vue'))

// Good: Computed properties for expensive operations
const filteredAlumni = computed(() => {
  return alumni.value.filter(alumnus => 
    alumnus.name.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})
</script>
```

### Caching
- Use Redis for session and cache storage
- Implement API response caching
- Use browser caching for static assets

```php
// Good: Cache expensive operations
public function getAlumniStatistics(): array
{
    return Cache::remember('alumni_statistics', 3600, function () {
        return [
            'total_alumni' => Alumni::count(),
            'recent_graduates' => Alumni::where('graduation_year', '>=', now()->year - 5)->count(),
            'top_companies' => $this->getTopCompanies(),
        ];
    });
}
```

## Security Guidelines

### Input Validation
- Always validate and sanitize user input
- Use Form Request classes for validation
- Implement proper authorization checks

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-connections');
    }

    public function rules(): array
    {
        return [
            'alumni_id' => [
                'required',
                'integer',
                'exists:alumni,id',
                'different:' . $this->user()->id,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'alumni_id.different' => 'You cannot connect with yourself.',
        ];
    }
}
```

### SQL Injection Prevention
- Always use Eloquent ORM or Query Builder
- Never concatenate user input into raw SQL

```php
// Good: Using Query Builder
$alumni = DB::table('alumni')
    ->where('graduation_year', $year)
    ->where('major', $major)
    ->get();

// Bad: Raw SQL with concatenation
$alumni = DB::select("SELECT * FROM alumni WHERE graduation_year = $year");
```

### XSS Prevention
- Always escape output in templates
- Use Vue.js built-in XSS protection
- Sanitize rich text content

```vue
<template>
  <!-- Good: Vue automatically escapes -->
  <p>{{ alumni.bio }}</p>
  
  <!-- Good: Explicit HTML sanitization when needed -->
  <div v-html="sanitizedContent"></div>
</template>
```

This coding standards document ensures consistency, quality, and maintainability across the Modern Alumni Platform codebase. All developers should follow these guidelines to maintain a high-quality codebase.