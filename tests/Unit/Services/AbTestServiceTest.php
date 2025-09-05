<?php

namespace Tests\Unit\Services;

use App\Models\Template;
use App\Models\TemplateAbTest;
use App\Models\AbTestEvent;
use App\Services\AbTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AbTestServiceTest extends TestCase
{
    use RefreshDatabase;

    private AbTestService $abTestService;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->abTestService = new AbTestService();

        // Create a test template
        $this->template = Template::factory()->create([
            'name' => 'Test Template',
            'category' => 'landing_page',
            'audience_type' => 'general',
            'is_active' => true
        ]);
    }

    public function test_can_create_ab_test()
    {
        $abTestData = [
            'template_id' => $this->template->id,
            'name' => 'Test A/B Test',
            'description' => 'Testing A/B functionality',
            'variants' => [
                [
                    'id' => 'A',
                    'name' => 'Control Variant',
                    'config' => ['cta_text' => 'Sign Up Now']
                ],
                [
                    'id' => 'B',
                    'name' => 'Test Variant',
                    'config' => ['cta_text' => 'Get Started Today']
                ]
            ],
            'goal_metric' => 'conversion_rate',
            'confidence_threshold' => 0.95,
            'sample_size_per_variant' => 1000
        ];

        $abTest = $this->abTestService->createAbTest($abTestData);

        $this->assertInstanceOf(TemplateAbTest::class, $abTest);
        $this->assertEquals('Test A/B Test', $abTest->name);
        $this->assertEquals('draft', $abTest->status);
        $this->assertCount(2, $abTest->variants);
    }

    public function test_cannot_create_ab_test_for_inactive_template()
    {
        $inactiveTemplate = Template::factory()->create(['is_active' => false]);

        $abTestData = [
            'template_id' => $inactiveTemplate->id,
            'name' => 'Test A/B Test',
            'variants' => [
                ['id' => 'A', 'name' => 'Control'],
                ['id' => 'B', 'name' => 'Test']
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->abTestService->createAbTest($abTestData);
    }

    public function test_can_start_ab_test()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'draft'
        ]);

        $result = $this->abTestService->startAbTest($abTest->id);

        $this->assertTrue($result);
        $this->assertEquals('active', $abTest->fresh()->status);
        $this->assertNotNull($abTest->fresh()->started_at);
    }

    public function test_can_stop_ab_test()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'active',
            'started_at' => now()
        ]);

        $result = $this->abTestService->stopAbTest($abTest->id);

        $this->assertTrue($result);
        $this->assertEquals('completed', $abTest->fresh()->status);
        $this->assertNotNull($abTest->fresh()->ended_at);
    }

    public function test_can_get_variant_for_session()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'active',
            'started_at' => now(),
            'variants' => [
                ['id' => 'A', 'name' => 'Control'],
                ['id' => 'B', 'name' => 'Test']
            ]
        ]);

        $variant = $this->abTestService->getVariantForSession($this->template->id, 'session123');

        $this->assertIsArray($variant);
        $this->assertContains($variant['id'], ['A', 'B']);
    }

    public function test_returns_null_for_inactive_template_ab_test()
    {
        $variant = $this->abTestService->getVariantForSession($this->template->id, 'session123');

        $this->assertNull($variant);
    }

    public function test_can_record_conversion()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'active',
            'started_at' => now(),
            'variants' => [
                ['id' => 'A', 'name' => 'Control'],
                ['id' => 'B', 'name' => 'Test']
            ]
        ]);

        $result = $this->abTestService->recordConversion($this->template->id, 'session123');

        $this->assertTrue($result);

        // Check that an event was recorded
        $this->assertDatabaseHas('ab_test_events', [
            'ab_test_id' => $abTest->id,
            'event_type' => 'conversion',
            'session_id' => 'session123'
        ]);
    }

    public function test_ab_test_has_statistical_significance()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'completed',
            'results' => [
                'confidence_level' => 0.96,
                'winner' => 'B'
            ]
        ]);

        $this->assertTrue($abTest->hasStatisticalSignificance());
    }

    public function test_ab_test_does_not_have_statistical_significance()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'completed',
            'results' => [
                'confidence_level' => 0.90,
                'winner' => null
            ]
        ]);

        $this->assertFalse($abTest->hasStatisticalSignificance());
    }

    public function test_can_get_winning_variant()
    {
        $abTest = TemplateAbTest::factory()->create([
            'template_id' => $this->template->id,
            'status' => 'completed',
            'variants' => [
                ['id' => 'A', 'name' => 'Control'],
                ['id' => 'B', 'name' => 'Test']
            ],
            'results' => [
                'winner' => 'B'
            ]
        ]);

        $winner = $abTest->getWinningVariant();

        $this->assertIsArray($winner);
        $this->assertEquals('B', $winner['id']);
        $this->assertEquals('Test', $winner['name']);
    }

    public function test_can_get_ab_test_statistics()
    {
        // Create some test data
        TemplateAbTest::factory()->create(['status' => 'active']);
        TemplateAbTest::factory()->create(['status' => 'completed']);
        TemplateAbTest::factory()->create(['status' => 'completed']);

        $stats = $this->abTestService->getAbTestStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_tests', $stats);
        $this->assertArrayHasKey('active_tests', $stats);
        $this->assertArrayHasKey('completed_tests', $stats);
        $this->assertEquals(3, $stats['total_tests']);
        $this->assertEquals(1, $stats['active_tests']);
        $this->assertEquals(2, $stats['completed_tests']);
    }

    public function test_validates_variant_structure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A/B test must have at least 2 variants');

        $abTestData = [
            'template_id' => $this->template->id,
            'name' => 'Test A/B Test',
            'variants' => [
                ['id' => 'A', 'name' => 'Single Variant']
            ]
        ];

        $this->abTestService->createAbTest($abTestData);
    }

    public function test_validates_variant_uniqueness()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Variant IDs must be unique');

        $abTestData = [
            'template_id' => $this->template->id,
            'name' => 'Test A/B Test',
            'variants' => [
                ['id' => 'A', 'name' => 'Variant A'],
                ['id' => 'A', 'name' => 'Variant A Duplicate']
            ]
        ];

        $this->abTestService->createAbTest($abTestData);
    }
}