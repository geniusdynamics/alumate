<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ABTest;
use App\Models\ABTestAssignment;
use App\Models\ABTestConversion;
use App\Models\AnalyticsEvent;
use App\Services\ABTestingService;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for ABTestingService
 *
 * Tests A/B test creation, variant assignment, exposure tracking,
 * conversion recording, and results calculation with tenant isolation.
 */
class ABTestingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ABTestingService $service;
    private TenantContextService $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = app(TenantContextService::class);
        $this->service = new ABTestingService($this->tenantContext);

        // Set up a test tenant context
        $this->tenantContext->setTenant('test-tenant-123');
    }

    /**
     * Test creating a new A/B test
     */
    public function test_creates_ab_test_successfully(): void
    {
        $testData = [
            'name' => 'Test CTA Button',
            'description' => 'Testing different CTA button texts',
            'variants' => [
                ['name' => 'control', 'weight' => 50],
                ['name' => 'variant_a', 'weight' => 50],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'button_click',
        ];

        $testId = $this->service->createTest($testData);

        $this->assertIsString($testId);
        $this->assertGreaterThan(0, (int) $testId);

        $test = ABTest::find($testId);
        $this->assertNotNull($test);
        $this->assertEquals('Test CTA Button', $test->name);
        $this->assertEquals('active', $test->status);
        $this->assertEquals('button_click', $test->goal_metric);
    }

    /**
     * Test variant assignment is deterministic
     */
    public function test_variant_assignment_is_deterministic(): void
    {
        $testData = [
            'name' => 'Deterministic Test',
            'description' => 'Testing deterministic assignment',
            'variants' => [
                ['name' => 'control', 'weight' => 50],
                ['name' => 'variant_a', 'weight' => 50],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];

        $testId = $this->service->createTest($testData);

        // Assign variant multiple times for same user
        $variant1 = $this->service->assignVariant('user123', (int) $testId);
        $variant2 = $this->service->assignVariant('user123', (int) $testId);
        $variant3 = $this->service->assignVariant('user123', (int) $testId);

        $this->assertEquals($variant1, $variant2);
        $this->assertEquals($variant2, $variant3);
        $this->assertContains($variant1, ['control', 'variant_a']);
    }

    /**
     * Test variant assignment uses cache for performance
     */
    public function test_variant_assignment_uses_cache(): void
    {
        $testData = [
            'name' => 'Cache Test',
            'description' => 'Testing cache usage',
            'variants' => [
                ['name' => 'control', 'weight' => 100],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];

        $testId = $this->service->createTest($testData);

        // First assignment should create cache
        $variant = $this->service->assignVariant('user456', (int) $testId);
        $this->assertEquals('control', $variant);

        // Check cache was created
        $cacheKey = "ab_assignment_{$testId}_user456";
        $this->assertEquals('control', Cache::get($cacheKey));
    }

    /**
     * Test exposure recording
     */
    public function test_records_exposure(): void
    {
        $testData = [
            'name' => 'Exposure Test',
            'description' => 'Testing exposure recording',
            'variants' => [
                ['name' => 'control', 'weight' => 100],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];

        $testId = $this->service->createTest($testData);

        // Create a mock analytics event
        $event = AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-123',
            'event_type' => 'page_view',
            'event_name' => 'page_view',
            'properties' => [
                'ab_test_id' => $testId,
                'ab_variant' => 'control',
            ],
            'occurred_at' => now(),
        ]);

        $this->service->recordExposure($event->id);

        // Check cache was updated
        $cacheKey = "ab_impressions_{$testId}_control_" . now()->format('Y-m-d');
        $this->assertEquals(1, Cache::get($cacheKey));
    }

    /**
     * Test conversion recording
     */
    public function test_records_conversion(): void
    {
        $testData = [
            'name' => 'Conversion Test',
            'description' => 'Testing conversion recording',
            'variants' => [
                ['name' => 'control', 'weight' => 100],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'button_click',
        ];

        $testId = $this->service->createTest($testData);

        // Create a mock analytics event for the goal
        $event = AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-123',
            'event_type' => 'button_click',
            'event_name' => 'button_click',
            'user_id' => 1,
            'session_id' => 'session123',
            'properties' => [
                'ab_test_id' => $testId,
                'ab_variant' => 'control',
            ],
            'occurred_at' => now(),
        ]);

        $this->service->recordConversion($event->id);

        // Check conversion was recorded in database
        $conversion = ABTestConversion::where('ab_test_id', $testId)
            ->where('variant', 'control')
            ->first();

        $this->assertNotNull($conversion);
        $this->assertEquals('control', $conversion->variant);

        // Check cache was updated
        $cacheKey = "ab_conversions_{$testId}_control_" . now()->format('Y-m-d');
        $this->assertEquals(1, Cache::get($cacheKey));
    }

    /**
     * Test getting test results
     */
    public function test_gets_test_results(): void
    {
        $testData = [
            'name' => 'Results Test',
            'description' => 'Testing results calculation',
            'variants' => [
                ['name' => 'control', 'weight' => 50],
                ['name' => 'variant_a', 'weight' => 50],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'button_click',
        ];

        $testId = $this->service->createTest($testData);

        // Create some mock data
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Mock impressions in cache
        Cache::put("ab_impressions_{$testId}_control_" . $startDate->format('Y-m-d'), 100, 3600);
        Cache::put("ab_impressions_{$testId}_variant_a_" . $startDate->format('Y-m-d'), 100, 3600);

        // Mock conversions in cache
        Cache::put("ab_conversions_{$testId}_control_" . $startDate->format('Y-m-d'), 10, 3600);
        Cache::put("ab_conversions_{$testId}_variant_a_" . $startDate->format('Y-m-d'), 15, 3600);

        $results = $this->service->getResults((int) $testId, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $this->assertNotNull($results['test']);
        $this->assertArrayHasKey('control', $results['variants']);
        $this->assertArrayHasKey('variant_a', $results['variants']);

        $this->assertEquals(100, $results['variants']['control']['impressions']);
        $this->assertEquals(10, $results['variants']['control']['conversions']);
        $this->assertEquals(10.0, $results['variants']['control']['conversion_rate']);

        $this->assertEquals(100, $results['variants']['variant_a']['impressions']);
        $this->assertEquals(15, $results['variants']['variant_a']['conversions']);
        $this->assertEquals(15.0, $results['variants']['variant_a']['conversion_rate']);
    }

    /**
     * Test tenant isolation
     */
    public function test_tenant_isolation(): void
    {
        // Create test in first tenant
        $this->tenantContext->setTenant('tenant1');
        $testData1 = [
            'name' => 'Tenant 1 Test',
            'description' => 'Test for tenant 1',
            'variants' => [['name' => 'control', 'weight' => 100]],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];
        $testId1 = $this->service->createTest($testData1);

        // Switch to second tenant
        $this->tenantContext->setTenant('tenant2');
        $testData2 = [
            'name' => 'Tenant 2 Test',
            'description' => 'Test for tenant 2',
            'variants' => [['name' => 'control', 'weight' => 100]],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];
        $testId2 = $this->service->createTest($testData2);

        // Tests should be different
        $this->assertNotEquals($testId1, $testId2);

        // Switch back to tenant1 and verify test exists
        $this->tenantContext->setTenant('tenant1');
        $test1 = $this->service->getTest((int) $testId1);
        $this->assertNotNull($test1);
        $this->assertEquals('Tenant 1 Test', $test1->name);

        // Tenant2 test should not be accessible from tenant1
        $test2FromTenant1 = $this->service->getTest((int) $testId2);
        $this->assertNull($test2FromTenant1);
    }

    /**
     * Test error handling for invalid test ID
     */
    public function test_handles_invalid_test_id(): void
    {
        $variant = $this->service->assignVariant('user123', 99999);
        $this->assertEquals('control', $variant); // Should return control for invalid test

        $results = $this->service->getResults(99999);
        $this->assertNull($results['test']);
        $this->assertEmpty($results['variants']);
    }

    /**
     * Test statistical significance calculation
     */
    public function test_calculates_statistical_significance(): void
    {
        $testData = [
            'name' => 'Significance Test',
            'description' => 'Testing significance calculation',
            'variants' => [
                ['name' => 'control', 'weight' => 50],
                ['name' => 'variant_a', 'weight' => 50],
            ],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'button_click',
        ];

        $testId = $this->service->createTest($testData);

        // Create sufficient data for significance testing
        $startDate = Carbon::now()->subDays(7);

        // Mock large sample sizes
        Cache::put("ab_impressions_{$testId}_control_" . $startDate->format('Y-m-d'), 1000, 3600);
        Cache::put("ab_impressions_{$testId}_variant_a_" . $startDate->format('Y-m-d'), 1000, 3600);
        Cache::put("ab_conversions_{$testId}_control_" . $startDate->format('Y-m-d'), 100, 3600);
        Cache::put("ab_conversions_{$testId}_variant_a_" . $startDate->format('Y-m-d'), 150, 3600);

        $results = $this->service->getResults((int) $testId, [
            'start_date' => $startDate->format('Y-m-d'),
        ]);

        // With large sample sizes and different conversion rates, should be significant
        $this->assertTrue($results['overall_significance']);
    }

    /**
     * Test test deletion
     */
    public function test_deletes_test(): void
    {
        $testData = [
            'name' => 'Delete Test',
            'description' => 'Testing test deletion',
            'variants' => [['name' => 'control', 'weight' => 100]],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];

        $testId = $this->service->createTest($testData);

        // Verify test exists
        $test = $this->service->getTest((int) $testId);
        $this->assertNotNull($test);

        // Delete test
        $deleted = $this->service->deleteTest((int) $testId);
        $this->assertTrue($deleted);

        // Verify test is deleted
        $deletedTest = $this->service->getTest((int) $testId);
        $this->assertNull($deletedTest);
    }

    /**
     * Test test update
     */
    public function test_updates_test(): void
    {
        $testData = [
            'name' => 'Update Test',
            'description' => 'Testing test update',
            'variants' => [['name' => 'control', 'weight' => 100]],
            'audience_criteria' => ['audience' => 'individual'],
            'goal_event' => 'page_view',
        ];

        $testId = $this->service->createTest($testData);

        // Update test
        $updated = $this->service->updateTest((int) $testId, [
            'name' => 'Updated Test Name',
            'status' => 'paused',
        ]);

        $this->assertTrue($updated);

        // Verify update
        $test = $this->service->getTest((int) $testId);
        $this->assertEquals('Updated Test Name', $test->name);
        $this->assertEquals('paused', $test->status);
    }
}