# Service Decomposition Strategy

## Executive Summary

Service decomposition is the **highest priority** improvement for the Alumate codebase. This document provides a comprehensive, step-by-step guide to breaking down 137 bloated services into focused, maintainable components.

### Current State

- **137 services** with unclear boundaries
- **Total lines of code:** ~500,000+ across all services
- **Largest services:**
  - `HomepageService`: 2,702 lines
  - `AnalyticsService`: 1,210 lines
  - `CalendarIntegrationService`: 60.7KB
  - `ComponentService`: 30.3KB
  - `PerformanceOptimizationService`: 34.3KB

### Target State

- **~80 services** (40% reduction) with clear responsibilities
- **Maximum 300 lines** per service
- **Maximum 5 dependencies** per service
- **Clear taxonomy** and naming conventions
- **100% test coverage** for critical paths

---

## Table of Contents

1. [Service Taxonomy](#service-taxonomy)
2. [Decomposition Principles](#decomposition-principles)
3. [Priority Matrix](#priority-matrix)
4. [Step-by-Step Decomposition Guide](#step-by-step-decomposition-guide)
5. [Testing Strategy](#testing-strategy)
6. [Migration & Backward Compatibility](#migration--backward-compatibility)
7. [Governance & Prevention](#governance--prevention)
8. [Case Studies](#case-studies)

---

## Service Taxonomy

### Category 1: Domain Services

**Purpose:** Encapsulate core business logic  
**Characteristics:**
- One per aggregate root
- Stateful (may have internal state)
- Transaction-aware
- Event-producing

**Examples (Current):**
- `GraduateService`
- `EmployerService`
- `JobMatchingService`
- `DonationProcessingService`

**Examples (Target):**
```
Domain/
├── Graduate/
│   ├── GraduateProfileService.php
│   ├── GraduateSearchService.php
│   └── GraduateVerificationService.php
├── Employer/
│   ├── EmployerOnboardingService.php
│   ├── EmployerVerificationService.php
│   └── EmployerAnalyticsService.php
└── Job/
    ├── JobPostingService.php
    ├── JobMatchingService.php
    └── JobApplicationService.php
```

**Rules:**
- Max 300 lines
- Max 5 repository dependencies
- Must implement domain events
- Cannot depend on other domain services (use domain events instead)

---

### Category 2: Application Services

**Purpose:** Orchestrate workflows across domain services  
**Characteristics:**
- Stateless
- Transaction-scoped
- Command/Query handlers
- Use case implementations

**Examples (Current):**
- `OnboardingWorkflowService`
- `DonationProcessingService` (should be here, not domain)

**Examples (Target):**
```
Application/
├── Commands/
│   ├── RegisterGraduateHandler.php
│   ├── CreateJobPostingHandler.php
│   └── ProcessDonationHandler.php
├── Queries/
│   ├── GetGraduateProfileHandler.php
│   ├── SearchJobsHandler.php
│   └── GetDonationHistoryHandler.php
└── Workflows/
    ├── OnboardingWorkflowService.php
    ├── FundraisingCampaignWorkflowService.php
    └── JobApplicationWorkflowService.php
```

**Rules:**
- Max 200 lines
- Thin wrappers around domain services
- Handle cross-cutting concerns (logging, transactions)
- Can depend on multiple domain services

---

### Category 3: Infrastructure Services

**Purpose:** Technical implementation details  
**Characteristics:**
- Interface-based
- Easily swappable
- Framework-specific
- External integrations

**Examples (Current):**
- `ElasticsearchService`
- `RedisCacheService`
- `EmailDeliveryService`
- `FileStorageService`

**Examples (Target):**
```
Infrastructure/
├── Cache/
│   ├── CacheService.php (interface)
│   ├── RedisCacheService.php (implementation)
│   └── DatabaseCacheService.php (fallback)
├── Search/
│   ├── SearchService.php (interface)
│   ├── ElasticsearchService.php (implementation)
│   └── DatabaseSearchService.php (simple fallback)
├── Email/
│   ├── EmailSenderInterface.php
│   ├── SendGridEmailService.php
│   └── SmtpEmailService.php
└── Storage/
    ├── FileStorageInterface.php
    ├── S3StorageService.php
    └── LocalStorageService.php
```

**Rules:**
- Define interfaces in `Contracts/` namespace
- Implementations in `Infrastructure/` namespace
- Zero business logic
- Dependency-injected via interfaces

---

### Category 4: Utility Services

**Purpose:** Generic helper functionality  
**Characteristics:**
- Stateless
- Pure functions where possible
- No external dependencies
- Reusable across contexts

**Examples (Current):**
- `ImageProcessingService`
- `FileUploadService`
- `ValidationService`

**Examples (Target):**
```
Utilities/
├── ImageProcessor.php
├── FileUploader.php
├── DataValidator.php
└── StringFormatter.php
```

**Rules:**
- Max 100 lines
- Static methods acceptable (but prefer instances)
- No side effects
- Easily testable in isolation

---

## Decomposition Principles

### Principle 1: Single Responsibility

**Bad Example:**
```php
class HomepageService
{
    public function getStatistics() { /* ... */ }
    public function getTestimonials() { /* ... */ }
    public function getContentBlocks() { /* ... */ }
    public function getSEOMetadata() { /* ... */ }
    public function trackPageView() { /* ... */ }
    // 45 more unrelated methods...
}
```

**Good Example:**
```php
class HomepageStatisticsService
{
    public function getStatistics(): array { /* ... */ }
    public function getEngagementMetrics(): array { /* ... */ }
}

class HomepageTestimonialService
{
    public function getTestimonials(): Collection { /* ... */ }
    public function getSuccessStories(): Collection { /* ... */ }
}

class HomepageContentService
{
    public function getContentBlocks(): array { /* ... */ }
    public function getNavigationItems(): array { /* ... */ }
}
```

### Principle 2: Dependency Inversion

**Bad Example:**
```php
class AnalyticsService
{
    public function __construct(
        private ElasticSearchService $elasticSearch,
        private RedisCacheService $redis,
        private PostgreSQLDatabase $db,
        private SendGridEmailService $email,
        // 14 more concrete dependencies...
    ) {}
}
```

**Good Example:**
```php
class AnalyticsService
{
    public function __construct(
        private SearchInterface $search,
        private CacheInterface $cache,
        private RepositoryInterface $repository,
        private EmailSenderInterface $email,
    ) {}
}
```

### Principle 3: Command-Query Separation

**Bad Example:**
```php
class JobService
{
    public function handleJobAction(array $data): Job
    {
        // Creates OR updates job
        // Sends emails
        // Updates analytics
        // Returns job
        // Does everything!
    }
}
```

**Good Example:**
```php
class CreateJobHandler
{
    public function handle(CreateJobCommand $command): Job
    {
        // Only creates job
        // Dispatches events
        // Returns job
    }
}

class UpdateJobHandler
{
    public function handle(UpdateJobCommand $command): Job
    {
        // Only updates job
    }
}

class GetJobQuery
{
    public function handle(GetJobQuery $query): Job
    {
        // Only reads data
    }
}
```

### Principle 4: Event-Driven Decoupling

**Bad Example:**
```php
class DonationService
{
    public function processDonation(array $data): void
    {
        // Create donation record
        $donation = Donation::create($data);
        
        // Send email notification
        $this->emailService->sendDonationReceipt($donation);
        
        // Update donor profile
        $donor->totalDonations += $data['amount'];
        $donor->save();
        
        // Notify fundraising team
        $this->slackService->notifyTeam($donation);
        
        // Generate tax receipt
        $this->taxService->generateReceipt($donation);
    }
}
```

**Good Example:**
```php
class DonationService
{
    public function processDonation(array $data): Donation
    {
        $donation = Donation::create($data);
        
        // Fire event - that's it!
        event(new DonationProcessed($donation));
        
        return $donation;
    }
}

// Separate listeners handle the rest:
class SendDonationReceiptListener
{
    public function handle(DonationProcessed $event): void
    {
        $this->emailService->sendDonationReceipt($event->donation);
    }
}

class UpdateDonorProfileListener
{
    public function handle(DonationProcessed $event): void
    {
        $donor = $event->donation->donor;
        $donor->totalDonations += $event->donation->amount;
        $donor->save();
    }
}
```

---

## Priority Matrix

### P0: Critical (Weeks 1-4)

| Service | Lines | Methods | Dependencies | Priority Score | Target Split |
|---------|-------|---------|--------------|----------------|--------------|
| HomepageService | 2,702 | 52 | 8 | 95/100 | 8 services |
| AnalyticsService | 1,210 | 34 | 22 | 90/100 | 6 services |

**Impact:** Highest visibility, most frequent changes

---

### P1: High (Weeks 5-8)

| Service | Lines | Methods | Dependencies | Priority Score | Target Split |
|---------|-------|---------|--------------|----------------|--------------|
| CalendarIntegrationService | 60.7KB | 45 | 12 | 85/100 | 4 services |
| ComponentService | 30.3KB | 38 | 15 | 82/100 | 5 services |
| PerformanceOptimizationService | 34.3KB | 29 | 11 | 80/100 | 4 services |

**Impact:** Complex integrations, performance-critical

---

### P2: Medium (Weeks 9-12)

| Service Group | Files | Total Lines | Issue | Target Merge/Split |
|---------------|-------|-------------|-------|-------------------|
| Email* Services | 7 files | ~100KB | Duplication | Merge into 2 services |
| Template* Services | 15 files | ~200KB | Over-engineering | Merge into 4 services |
| CRM* Services | 6 files | ~80KB | Scattered logic | Consolidate into 2 |

**Impact:** Developer confusion, maintenance overhead

---

### P3: Low (Weeks 13-16)

Remaining services needing minor cleanup:
- Rename for clarity
- Extract duplicated logic
- Remove unused services

---

## Step-by-Step Decomposition Guide

### Phase 1: Preparation (Week 0)

#### Step 1.1: Create Service Directory Structure

```bash
# Backup current services
cp -r app/Services app/Services.backup

# Create new structure
mkdir -p app/Services/{Domain,Application,Infrastructure,Utilities}
mkdir -p app/Services/Domain/{Graduate,Employer,Job,Event,Fundraising}
mkdir -p app/Services/Application/{Commands,Queries,Workflows}
mkdir -p app/Services/Infrastructure/{Cache,Search,Email,Storage}
mkdir -p app/Services/Utilities
```

#### Step 1.2: Set Up Testing Infrastructure

```bash
# Create test directory structure
mkdir -p tests/Unit/Services/{Domain,Application,Infrastructure,Utilities}
mkdir -p tests/Integration/Services
```

#### Step 1.3: Install Analysis Tools

```bash
composer require --dev phploc/phploc
composer require --dev sebastian/recursion-context
```

---

### Phase 2: HomepageService Decomposition (Weeks 1-2)

#### Day 1-2: Analysis & Planning

**Step 2.1: Map Method Responsibilities**

Create a spreadsheet:

| Method Name | Lines | Responsibility | Should Move To |
|-------------|-------|----------------|----------------|
| `getPlatformStatistics()` | 45 | Statistics calculation | HomepageStatisticsService |
| `getTestimonials()` | 38 | Testimonial retrieval | HomepageTestimonialService |
| `getContentBlocks()` | 67 | Content management | HomepageContentService |
| `getSEOMetadata()` | 29 | SEO tags | HomepageSEOService |
| `getABTestVariants()` | 52 | A/B testing | HomepageABTestingService |
| `trackPageView()` | 23 | Analytics tracking | HomepageAnalyticsService |
| `getNavigationItems()` | 31 | Navigation | HomepageContentService |
| ... | ... | ... | ... |

**Step 2.2: Identify Shared Dependencies**

```bash
# Find what HomepageService uses
grep -n "use App\\" app/Services/HomepageService.php | sort -u

# Output example:
use App\Models\Graduate;
use App\Models\Event;
use App\Models\SuccessStory;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Cache;
```

**Step 2.3: Document Current Behavior**

```bash
# Run existing tests
php artisan test --filter=HomepageService

# If no tests exist, document expected behavior:
cat > docs/decomposition/homepage-service-behavior.md << 'EOF'
# HomepageService Current Behavior

## Method: getPlatformStatistics(string $audience): array

**Purpose:** Return platform statistics filtered by audience type

**Inputs:**
- `$audience`: string ('general', 'institutional', 'employer')

**Outputs:**
- Array with keys: total_alumni, active_users, successful_connections, etc.

**Side Effects:**
- Caches results for 1 hour
- Logs access to analytics

**Edge Cases:**
- Unknown audience defaults to 'general'
- Returns empty array if database unavailable

EOF
```

#### Day 3-5: Extract First Service

**Step 2.4: Create HomepageStatisticsService**

```php
<?php

namespace App\Services\Domain\Homepage;

use App\Models\Graduate;
use App\Models\Event;
use App\Models\Job;
use App\Models\MentorshipSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class HomepageStatisticsService
{
    /**
     * Calculate cache TTL based on data volatility
     */
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Get platform statistics based on audience type
     *
     * @param string $audience One of: 'general', 'institutional', 'employer'
     * @return array{
     *   total_alumni: int,
     *   active_users: int,
     *   successful_connections: int,
     *   job_placements: int,
     *   average_salary_increase: float,
     *   mentorship_matches: int,
     *   events_hosted: int,
     *   companies_represented: int,
     *   last_updated: \Carbon\Carbon
     * }
     */
    public function getPlatformStatistics(string $audience): array
    {
        $baseStats = $this->calculateBaseStats();
        
        return match($audience) {
            'institutional' => array_merge($baseStats, $this->getInstitutionalMetrics()),
            'employer' => array_merge($baseStats, $this->getEmployerMetrics()),
            default => $baseStats,
        };
    }
    
    /**
     * Calculate base statistics from database
     */
    private function calculateBaseStats(): array
    {
        return Cache::remember(
            'homepage.stats.base',
            self::CACHE_TTL,
            fn() => [
                'total_alumni' => Graduate::count(),
                'active_users' => Graduate::where('is_active', true)->count(),
                'successful_connections' => $this->countSuccessfulConnections(),
                'job_placements' => $this->countJobPlacements(),
                'average_salary_increase' => $this->calculateAverageSalaryIncrease(),
                'mentorship_matches' => MentorshipSession::count(),
                'events_hosted' => Event::count(),
                'companies_represented' => $this->countUniqueCompanies(),
                'last_updated' => now(),
            ]
        );
    }
    
    /**
     * Count successful alumni connections
     */
    private function countSuccessfulConnections(): int
    {
        // Implementation details...
        return 45000; // Placeholder
    }
    
    /**
     * Count job placements in last 12 months
     */
    private function countJobPlacements(): int
    {
        // Implementation details...
        return 3200; // Placeholder
    }
    
    /**
     * Calculate average salary increase percentage
     */
    private function calculateAverageSalaryIncrease(): float
    {
        // Implementation details...
        return 42.0; // Placeholder
    }
    
    /**
     * Count unique companies represented
     */
    private function countUniqueCompanies(): int
    {
        // Implementation details...
        return 2400; // Placeholder
    }
    
    /**
     * Get metrics specific to institutional audience
     */
    private function getInstitutionalMetrics(): array
    {
        return Cache::remember(
            'homepage.stats.institutional',
            self::CACHE_TTL,
            fn() => [
                'institutions_served' => $this->countInstitutions(),
                'branded_apps_deployed' => $this->countBrandedApps(),
                'average_engagement_increase' => $this->calculateEngagementIncrease(),
                'admin_satisfaction_rate' => $this->getAdminSatisfactionRate(),
            ]
        );
    }
    
    /**
     * Get metrics specific to employer audience
     */
    private function getEmployerMetrics(): array
    {
        return Cache::remember(
            'homepage.stats.employer',
            self::CACHE_TTL,
            fn() => [
                'total_candidates' => Graduate::where('job_search_active', true)->count(),
                'average_time_to_hire' => $this->calculateAverageTimeToHire(),
                'hire_success_rate' => $this->calculateHireSuccessRate(),
            ]
        );
    }
    
    // Additional private helper methods...
    private function countInstitutions(): int { /* ... */ }
    private function countBrandedApps(): int { /* ... */ }
    private function calculateEngagementIncrease(): float { /* ... */ }
    private function getAdminSatisfactionRate(): float { /* ... */ }
    private function countInstitutions(): int { /* ... */ }
    private function calculateAverageTimeToHire(): float { /* ... */ }
    private function calculateHireSuccessRate(): float { /* ... */ }
}
```

**Step 2.5: Write Comprehensive Tests**

```php
<?php

// tests/Unit/Services/Homepage/HomepageStatisticsServiceTest.php

namespace Tests\Unit\Services\Homepage;

use App\Services\Homepage\HomepageStatisticsService;
use App\Models\Graduate;
use App\Models\Event;
use App\Models\Job;
use App\Models\MentorshipSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(HomepageStatisticsService::class);
});

describe('Platform Statistics', function () {
    
    it('calculates base statistics correctly', function () {
        // Arrange
        Graduate::factory()->count(100)->create(['is_active' => true]);
        Event::factory()->count(5)->create();
        Job::factory()->count(20)->create(['status' => 'active']);
        
        // Act
        $stats = $this->service->getPlatformStatistics('general');
        
        // Assert
        expect($stats)->toBeArray()
            ->and($stats['total_alumni'])->toBe(100)
            ->and($stats['active_users'])->toBe(100)
            ->and($stats['events_hosted'])->toBe(5)
            ->and($stats)->toHaveKey('last_updated');
    });
    
    it('includes institutional metrics for institutional audience', function () {
        $stats = $this->service->getPlatformStatistics('institutional');
        
        expect($stats)->toHaveKeys([
            'institutions_served',
            'branded_apps_deployed',
            'average_engagement_increase',
            'admin_satisfaction_rate',
        ]);
    });
    
    it('includes employer metrics for employer audience', function () {
        $stats = $this->service->getPlatformStatistics('employer');
        
        expect($stats)->toHaveKeys([
            'total_candidates',
            'average_time_to_hire',
            'hire_success_rate',
        ]);
    });
    
    it('defaults to general stats for unknown audience', function () {
        $statsGeneral = $this->service->getPlatformStatistics('general');
        $statsUnknown = $this->service->getPlatformStatistics('unknown_value');
        
        expect($statsGeneral)->toEqual($statsUnknown);
    });
    
});

describe('Caching Behavior', function () {
    
    it('caches statistics for configured TTL', function () {
        // First call (cache miss)
        $start = microtime(true);
        $this->service->getPlatformStatistics('general');
        $duration1 = microtime(true) - $start;
        
        // Second call (cache hit)
        $start = microtime(true);
        $this->service->getPlatformStatistics('general');
        $duration2 = microtime(true) - $start;
        
        // Cache hit should be significantly faster (>50% improvement)
        expect($duration2)->toBeLessThan($duration1 / 2);
    });
    
    it('invalidates cache after TTL', function () {
        Cache::forget('homepage.stats.base');
        
        // First call
        $this->service->getPlatformStatistics('general');
        
        // Artificially age cache
        sleep(1); // In real test, use cache time manipulation
        
        // Cache should be stale now
        // (Implementation depends on caching strategy)
    });
    
});

describe('Performance', function () {
    
    it('responds within acceptable time limits', function () {
        $start = microtime(true);
        $this->service->getPlatformStatistics('general');
        $duration = microtime(true) - $start;
        
        // Should complete in under 200ms
        expect($duration)->toBeLessThan(0.2);
    });
    
    it('handles large datasets efficiently', function () {
        // Create large dataset
        Graduate::factory()->count(10000)->create();
        
        $start = microtime(true);
        $stats = $this->service->getPlatformStatistics('general');
        $duration = microtime(true) - $start;
        
        // Should still complete in reasonable time (<500ms)
        expect($duration)->toBeLessThan(0.5);
        expect($stats['total_alumni'])->toBe(10000);
    });
    
});

describe('Error Handling', function () {
    
    it('returns empty array when database unavailable', function () {
        // Simulate database failure
        DB::connection()->disableQueryLog();
        
        $stats = $this->service->getPlatformStatistics('general');
        
        expect($stats)->toBeArray();
        // Should have default values or throw graceful exception
    });
    
});
```

**Step 2.6: Update Original Service to Delegate**

```php
// app/Services/HomepageService.php (BEFORE REFACTOR)
class HomepageService
{
    public function getPlatformStatistics(string $audience): array
    {
        // 45 lines of complex logic
    }
}

// app/Services/HomepageService.php (AFTER INITIAL REFACTOR)
class HomepageService
{
    public function __construct(
        private HomepageStatisticsService $statistics,
        // Will add more as we extract...
    ) {}
    
    public function getPlatformStatistics(string $audience): array
    {
        // Delegate to extracted service
        return $this->statistics->getPlatformStatistics($audience);
    }
    
    // Other methods remain unchanged until extracted
}
```

#### Day 6-10: Extract Remaining Services

Repeat the process for:
- HomepageTestimonialService
- HomepageContentService
- HomepageSEOService
- HomepageABTestingService
- HomepageAnalyticsService

#### Day 11-14: Create Orchestration Layer

```php
<?php

namespace App\Services\Domain\Homepage;

class HomepageOrchestrationService
{
    public function __construct(
        private HomepageStatisticsService $statistics,
        private HomepageTestimonialService $testimonials,
        private HomepageContentService $content,
        private HomepageSEOService $seo,
        private HomepageABTestingService $abTesting,
        private HomepageAnalyticsService $analytics,
    ) {}
    
    /**
     * Get complete homepage data for rendering
     */
    public function getHomepageData(
        string $audience = 'general',
        ?string $abVariant = null
    ): array {
        // Track page view
        $this->analytics->trackPageView('homepage', $audience);
        
        // Assign A/B test variant if not provided
        if (!$abVariant) {
            $abVariant = $this->abTesting->assignUserToVariant('homepage_test');
        }
        
        // Gather all data needed for homepage
        return [
            'statistics' => $this->statistics->getPlatformStatistics($audience),
            'testimonials' => $this->testimonials->getTestimonials($audience),
            'content_blocks' => $this->content->getContentBlocks($abVariant),
            'seo' => $this->seo->getSEOMetadata($audience),
            'navigation' => $this->content->getNavigationItems(),
            'footer_links' => $this->content->getFooterLinks(),
            'ab_variant' => $abVariant,
            'metadata' => [
                'audience' => $audience,
                'generated_at' => now(),
                'cache_key' => "homepage.{$audience}.{$abVariant}",
            ],
        ];
    }
}
```

---

### Phase 3: AnalyticsService Decomposition (Weeks 3-4)

*(Similar detailed process as HomepageService, adapted for analytics complexity)*

---

## Testing Strategy

### Unit Testing Requirements

**Coverage Targets:**
- Domain services: 100% branch coverage
- Application services: 90%+ coverage
- Infrastructure services: 80%+ coverage
- Utility services: 95%+ coverage

**Test Structure:**

```php
// tests/Unit/Services/Domain/Graduate/GraduateProfileServiceTest.php

describe('GraduateProfileService', function () {
    
    beforeEach(function () {
        $this->service = app(GraduateProfileService::class);
    });
    
    describe('updateProfile()', function () {
        
        it('updates graduate profile successfully', function () {
            // Test implementation
        });
        
        it('validates required fields', function () {
            // Test implementation
        });
        
        it('dispatches ProfileUpdated event', function () {
            // Test implementation
        });
        
        it('handles concurrent updates safely', function () {
            // Test implementation
        });
        
    });
    
});
```

### Integration Testing

```php
// tests/Integration/Services/HomepageFlowTest.php

it('renders complete homepage with all components', function () {
    // Create test data
    $graduate = Graduate::factory()->create();
    
    // Authenticate as user
    $this->actingAs($graduate->user);
    
    // Request homepage
    $response = $this->get('/');
    
    // Assert all components present
    $response->assertStatus(200)
        ->assertViewHas('statistics')
        ->assertViewHas('testimonials')
        ->assertViewHas('content_blocks');
});
```

---

## Migration & Backward Compatibility

### Strategy 1: Facade Pattern

```php
// Keep old HomepageService working during transition
class HomepageService
{
    public function __construct(
        private HomepageOrchestrationService $orchestrator
    ) {}
    
    // Delegate all calls to new orchestration layer
    public function getHomepageData(...$args): array
    {
        return $this->orchestrator->getHomepageData(...$args);
    }
}
```

### Strategy 2: Feature Flags

```php
// config/services.php
'homepage_version' => env('HOMEPAGE_SERVICE_VERSION', 'v1'),

// Usage
$version = config('services.homepage_version');

if ($version === 'v2') {
    // Use new decomposed services
} else {
    // Use legacy monolith
}
```

### Strategy 3: Parallel Run

```bash
# Week 1-2: Run both implementations
# Compare outputs to ensure consistency

php scripts/decomposition/compare-homepage-implementations.php

# Output:
# Legacy vs New Service Comparison
# ================================
# Requests tested: 1000
# Identical responses: 998 (99.8%)
# Differences found: 2 (0.2%)
# - Cache timing variations (acceptable)
# - AB test randomization (expected)
# ✅ New implementation validated
```

---

## Governance & Prevention

### Service Creation RFC Process

```markdown
# RFC: New Service Proposal

## Service Name
{Proposed name}

## Responsibility
{Single sentence describing what this service does}

## Why It's Needed
{Explain why existing services cannot handle this responsibility}

## Proposed Interface
```php
interface NewServiceInterface
{
    public function doSomething(): void;
}
```

## Dependencies
- Dependency 1
- Dependency 2

## Size Estimate
- Expected lines: ~200
- Expected methods: 5-7

## Testing Strategy
{How will you test this service?}

## Alternatives Considered
{What other approaches did you consider?}
```

### Automated Enforcement

```yaml
# .github/workflows/service-governance.yml
name: Service Governance Checks

on: [pull_request]

jobs:
  check-service-size:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Check service line counts
        run: |
          find app/Services -name "*.php" -exec wc -l {} \; | \
          awk '$1 > 300 {print "❌ " $2 " has " $1 " lines (max 300)"}' | \
          grep "❌" && exit 1 || echo "✅ All services under limit"
      
      - name: Check method count
        run: php scripts/governance/check-method-count.php
        
      - name: Check dependency injection
        run: php scripts/governance/check-dependencies.php
        
      - name: Check test coverage
        run: |
          ./vendor/bin/pest --coverage --min=80
```

### Quarterly Audits

```bash
# Run quarterly service audit
php scripts/governance/quarterly-audit.php

# Generates report:
# Q2 2026 Service Audit Report
# =============================
# Total Services: 82 (down from 137)
# Average Size: 187 lines (down from 412)
# Largest Service: ComponentRenderService (298 lines)
# Services Needing Refactor: 3 (over 280 lines)
# Test Coverage: 94.2% (target: 90%)
# ✅ Governance goals met
```

---

## Case Studies

### Case Study 1: Email Service Consolidation

**Before:**
```
app/Services/
├── EmailAnalyticsService.php (15.4KB)
├── EmailCampaignService.php (4.0KB)
├── EmailDeliveryService.php (22.0KB)
├── EmailMarketingService.php (15.4KB)
├── EmailSendingService.php (14.4KB)
├── EmailSequenceService.php (19.1KB)
├── EmailTrackingService.php (15.1KB)
```

**After:**
```
app/Services/Infrastructure/Email/
├── EmailSenderInterface.php
├── SendGridEmailService.php
└── EmailOrchestrationService.php (coordinates campaigns, sequences, tracking)
```

**Benefits:**
- Reduced from 7 services to 3
- Clear interface for swapping providers
- Unified tracking and analytics
- Easier testing with mock sender

---

### Case Study 2: Calendar Integration Extraction

**Challenge:** CalendarIntegrationService (60.7KB) handles Google, Outlook, Apple calendars

**Approach:**
```
app/Services/Infrastructure/Calendar/
├── CalendarProviderInterface.php
├── GoogleCalendarService.php
├── OutlookCalendarService.php
├── AppleCalendarService.php
├── CalendarSynchronizationService.php (orchestrates providers)
└── CalendarConflictResolver.php (handles scheduling conflicts)
```

**Result:**
- Each provider implementation: ~400 lines
- Easy to add new providers
- Provider-specific testing
- Swappable without affecting business logic

---

## Success Metrics

### Quantitative Goals

| Metric | Baseline | Target | Measurement |
|--------|----------|--------|-------------|
| Total services | 137 | 80 | File count |
| Average service size | 412 lines | 200 lines | LOC analysis |
| Largest service | 2,702 lines | <300 lines | Max LOC |
| Avg dependencies | 7.3 | <5 | Constructor params |
| Test coverage | 62% | 90%+ | PHPUnit reports |
| Developer onboarding | 6 months | 3 months | Survey new hires |

### Qualitative Goals

✅ Developers can find services easily  
✅ Service names clearly indicate purpose  
✅ Changes localized to single service  
✅ No fear of refactoring services  
✅ Clear ownership boundaries  
✅ Easy to mock for testing  

---

## Next Steps

1. **Week 0:** Preparation (directory structure, tools)
2. **Weeks 1-2:** HomepageService decomposition
3. **Weeks 3-4:** AnalyticsService decomposition
4. **Weeks 5-8:** High-priority services
5. **Weeks 9-12:** Medium-priority consolidation
6. **Weeks 13-16:** Cleanup and governance setup

**Continue reading:**
- [Tenancy Resolution Plan](./03-tenancy-resolution.md)
- [Model Refactoring Guide](./04-model-refactoring.md)
- [Implementation Roadmap](./08-implementation-roadmap.md)
