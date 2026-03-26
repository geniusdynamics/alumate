# Testing Strategy & Quality Assurance

## Executive Summary

This document defines the comprehensive testing strategy for the Alumate platform, ensuring code quality, reliability, and confidence in the architectural improvements.

### Current State

- Claimed 80%+ coverage (unverified)
- Complex tenancy scenarios under-tested
- Event-driven architecture creates hidden coupling
- Integration tests likely insufficient

### Target State

- **90%+ coverage** for critical paths
- **100% branch coverage** for domain services
- Comprehensive integration test suite
- Automated testing in CI/CD
- Performance benchmarking

---

## Testing Pyramid

```
           /\
          /  \
         / E2E \        ~10% of tests
        /______\       Slow, high-confidence
       /        \
      /Integration\    ~20% of tests
     /____________\   Medium speed
    /              \
   /    Unit Tests  \  ~70% of tests
  /__________________\ Fast, isolated
```

---

## Unit Testing Standards

### Service Testing

```typescript
// tests/Unit/Services/Homepage/HomepageStatisticsServiceTest.ts

import { describe, it, expect, beforeEach } from 'vitest'
import { HomepageStatisticsService } from '@/Services/HomepageStatisticsService'
import { Graduate } from '@/Models/Graduate'
import { Event } from '@/Models/Event'

describe('HomepageStatisticsService', () => {
  let service: HomepageStatisticsService
  
  beforeEach(() => {
    service = new HomepageStatisticsService()
  })
  
  describe('getPlatformStatistics()', () => {
    it('calculates base statistics correctly', async () => {
      // Arrange
      const mockGraduates = [
        { id: 1, is_active: true },
        { id: 2, is_active: true },
      ]
      
      // Act
      const stats = await service.getPlatformStatistics('general')
      
      // Assert
      expect(stats.total_alumni).toBe(2)
      expect(stats.active_users).toBe(2)
      expect(stats).toHaveProperty('last_updated')
    })
    
    it('includes institutional metrics for institutional audience', async () => {
      const stats = await service.getPlatformStatistics('institutional')
      
      expect(stats).toHaveProperty('institutions_served')
      expect(stats).toHaveProperty('branded_apps_deployed')
    })
    
    it('caches results for performance', async () => {
      // First call (cache miss)
      const start1 = performance.now()
      await service.getPlatformStatistics('general')
      const duration1 = performance.now() - start1
      
      // Second call (cache hit)
      const start2 = performance.now()
      await service.getPlatformStatistics('general')
      const duration2 = performance.now() - start2
      
      // Cache hit should be significantly faster
      expect(duration2).toBeLessThan(duration1 / 2)
    })
  })
})
```

### Model Testing

```php
<?php

// tests/Unit/Models/GraduateTest.php

use App\Models\Graduate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $user = User::factory()->create();
    $graduate = Graduate::factory()->create(['user_id' => $user->id]);
    
    expect($graduate->user->id)->toBe($user->id);
});

it('calculates profile completion percentage', function () {
    $graduate = Graduate::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => null, // Missing
        'graduation_year' => 2024,
        'course_id' => 1,
    ]);
    
    // Should be < 100% due to missing phone
    expect($graduate->profile_completion_percentage)->toBeLessThan(100);
});

it('scopes by employment status', function () {
    Graduate::factory()->count(5)->create(['employment_status' => 'employed']);
    Graduate::factory()->count(3)->create(['employment_status' => 'unemployed']);
    
    $employed = Graduate::employed()->count();
    $unemployed = Graduate::unemployed()->count();
    
    expect($employed)->toBe(5);
    expect($unemployed)->toBe(3);
});
```

---

## Integration Testing

### API Endpoint Testing

```php
<?php

// tests/Integration/Api/GraduateApiTest.php

use App\Models\Graduate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Graduate API Endpoints', function () {
    
    describe('GET /api/graduates', function () {
        it('returns paginated graduate list', function () {
            Graduate::factory()->count(20)->create();
            
            $response = $this->getJson('/api/graduates');
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'graduation_year']
                    ],
                    'links' => ['first', 'last', 'prev', 'next'],
                    'meta' => ['current_page', 'total', 'per_page']
                ]);
        });
        
        it('filters graduates by course', function () {
            $course1 = Course::factory()->create();
            $course2 = Course::factory()->create();
            
            Graduate::factory()->count(5)->create(['course_id' => $course1->id]);
            Graduate::factory()->count(3)->create(['course_id' => $course2->id]);
            
            $response = $this->getJson("/api/graduates?course_id={$course1->id}");
            
            $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
        });
        
        it('requires authentication', function () {
            $response = $this->getJson('/api/graduates');
            
            $response->assertStatus(401);
        });
    });
    
    describe('POST /api/graduates', function () {
        it('creates new graduate record', function () {
            $user = User::factory()->create();
            
            $payload = [
                'user_id' => $user->id,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'graduation_year' => 2024,
                'course_id' => 1,
            ];
            
            $response = $this->postJson('/api/graduates', $payload);
            
            $response->assertStatus(201)
                ->assertJson([
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ]);
            
            $this->assertDatabaseHas('graduates', [
                'email' => 'jane@example.com',
            ]);
        });
        
        it('validates required fields', function () {
            $response = $this->postJson('/api/graduates', []);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'graduation_year']);
        });
    });
    
});
```

### Tenancy Integration Testing

```php
<?php

// tests/Integration/Tenancy/SchemaIsolationTest.php

use App\Models\ComponentTheme;
use App\Models\Tenant;

describe('Schema-Based Tenancy', function () {
    
    it('isolates data between tenant schemas', function () {
        // Setup Tenant A
        $tenantA = Tenant::create(['id' => 'tenant-a', 'name' => 'Tenant A']);
        tenancy()->init('tenant-a');
        
        ComponentTheme::create([
            'name' => 'Theme A',
            'config' => ['color' => 'blue']
        ]);
        
        // Setup Tenant B
        $tenantB = Tenant::create(['id' => 'tenant-b', 'name' => 'Tenant B']);
        tenancy()->init('tenant-b');
        
        ComponentTheme::create([
            'name' => 'Theme B',
            'config' => ['color' => 'red']
        ]);
        
        // Verify isolation
        tenancy()->init('tenant-a');
        expect(ComponentTheme::count())->toBe(1)
            ->and(ComponentTheme::first()->name)->toBe('Theme A');
        
        tenancy()->init('tenant-b');
        expect(ComponentTheme::count())->toBe(1)
            ->and(ComponentTheme::first()->name)->toBe('Theme B');
    });
    
    it('prevents cross-schema queries', function () {
        tenancy()->init('tenant-a');
        
        // Try to access tenant B schema directly (should fail)
        $exception = null;
        try {
            DB::select("SELECT * FROM tenant_tenant_b.component_themes");
        } catch (\Exception $e) {
            $exception = $e;
        }
        
        expect($exception)->not->toBeNull();
    });
    
});
```

---

## Performance Testing

### Load Testing Scripts

```bash
#!/bin/bash
# scripts/testing/load-test-graduates-api.sh

echo "=== GRADUATES API LOAD TEST ==="
echo ""

# Install artillery if not present
npm install -g artillery

# Run load test
artillery quick --count 100 --num 10 http://localhost:8080/api/graduates \
  --output load-test-results.json

# Generate report
artillery report load-test-results.json

# Expected results:
# - 95th percentile response time < 200ms
# - Error rate < 0.1%
# - Throughput > 100 req/sec
```

### Benchmark Tests

```php
<?php

// tests/Performance/QueryPerformanceTest.php

it('queries graduates efficiently', function () {
    // Create large dataset
    Graduate::factory()->count(10000)->create();
    
    $start = microtime(true);
    
    // Execute query
    $graduates = Graduate::with(['user', 'course'])
        ->where('employment_status', 'employed')
        ->orderBy('graduation_year')
        ->get();
    
    $duration = microtime(true) - $start;
    
    // Should complete in under 100ms
    expect($duration)->toBeLessThan(0.1);
    expect($graduates)->toHaveCount(/* expected count */);
});

it('caches analytics queries effectively', function () {
    // Warm cache
    $service = app(AnalyticsService::class);
    $service->getEngagementMetrics([]);
    
    // Measure cached query
    $start = microtime(true);
    $service->getEngagementMetrics([]);
    $duration = microtime(true) - $start;
    
    // Cached query should be < 10ms
    expect($duration)->toBeLessThan(0.01);
});
```

---

## Coverage Requirements

### Minimum Coverage Thresholds

```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <report>
        <html outputDirectory="coverage-report"/>
        <text outputFile="php://stdout" showOnlySummary="true"/>
    </report>
    
    <thresholds>
        <threshold type="lines" lowUpperBound="80" highMinimum="90"/>
        <threshold type="functions" lowUpperBound="80" highMinimum="90"/>
        <threshold type="classes" lowUpperBound="85" highMinimum="95"/>
    </thresholds>
    
    <include>
        <directory suffix=".php">app/Services</directory>
        <directory suffix=".php">app/Models</directory>
        <directory suffix=".php">app/Http/Controllers</directory>
    </include>
    
    <exclude>
        <directory>app/Models/Observers</directory>
        <directory>app/Providers</directory>
    </exclude>
</coverage>
```

### Enforcement in CI/CD

```yaml
# .github/workflows/testing.yml
name: Test Coverage Enforcement

on: [pull_request]

jobs:
  test-coverage:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Run tests with coverage
        run: ./vendor/bin/pest --coverage --min=90
      
      - name: Upload coverage report
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage-report/clover.xml
          fail_ci_if_below_threshold: true
```

---

## Test Data Management

### Factory Patterns

```php
<?php

// database/factories/GraduateFactory.php

namespace Database\Factories;

use App\Models\Graduate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class GraduateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_id' => $this->faker->unique()->numerify('STU-#####'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'graduation_year' => $this->faker->numberBetween(2020, 2026),
            'course_id' => Course::factory(),
            'employment_status' => $this->faker->randomElement([
                'employed',
                'unemployed',
                'self_employed',
                'further_education'
            ]),
            'profile_completion_percentage' => $this->faker->numberBetween(50, 100),
        ];
    }
    
    /**
     * Indicate that graduate is employed
     */
    public function employed(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'employed',
            'current_job_title' => $this->faker->jobTitle(),
            'current_company' => $this->faker->company(),
        ]);
    }
    
    /**
     * Indicate that graduate has complete profile
     */
    public function completeProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_completion_percentage' => 100,
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'skills' => ['PHP', 'Laravel', 'Vue.js'],
        ]);
    }
}
```

---

## Success Criteria

✅ **90%+ overall code coverage**  
✅ **100% branch coverage for domain services**  
✅ **All critical paths tested**  
✅ **Performance benchmarks met**  
✅ **Zero regressions in production**  
✅ **Automated testing in CI/CD**  

---

## Next Steps

Continue reading:
- [Implementation Roadmap](./08-implementation-roadmap.md)

---

## Appendix: Testing Checklist

### Pre-Merge Checklist

```markdown
## Unit Tests
- [ ] All new services have unit tests
- [ ] Branch coverage >90%
- [ ] Edge cases covered
- [ ] Error conditions tested

## Integration Tests
- [ ] API endpoints tested
- [ ] Database interactions tested
- [ ] External service mocks working
- [ ] Tenancy isolation verified

## Performance Tests
- [ ] Load tests passing
- [ ] Response times within SLA
- [ ] No memory leaks detected
- [ ] Cache effectiveness verified

## Code Quality
- [ ] No linting errors
- [ ] TypeScript strict mode passing
- [ ] PHPStan level 7+ passing
- [ ] Documentation updated
```
