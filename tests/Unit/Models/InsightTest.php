<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Insight;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test insight has correctly configured fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $insight = new Insight();

        $fillable = $insight->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('data', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('effectiveness_score', $fillable);
        $this->assertContains('tracked_at', $fillable);
    }

    /**
     * Test insight has correctly configured casted attributes
     */
    public function test_cast_attributes(): void
    {
        $insight = new Insight();

        $casts = $insight->casts();

        $this->assertArrayHasKey('data', $casts);
        $this->assertEquals('array', $casts['data']);

        $this->assertArrayHasKey('effectiveness_score', $casts);
        $this->assertEquals('decimal:2', $casts['effectiveness_score']);

        $this->assertArrayHasKey('tracked_at', $casts);
        $this->assertEquals('datetime', $casts['tracked_at']);
    }

    /**
     * Test insight belongs to tenant relationship
     */
    public function test_belongs_to_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        $insight = Insight::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $insight->tenant);
        $this->assertEquals($tenant->id, $insight->tenant->id);
    }

    /**
     * Test insight by tenant scope
     */
    public function test_by_tenant_scope(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Insight::factory()->create(['tenant_id' => $tenant1->id]);
        Insight::factory()->create(['tenant_id' => $tenant2->id]);

        $tenant1Insights = Insight::byTenant($tenant1->id)->get();
        $tenant2Insights = Insight::byTenant($tenant2->id)->get();

        $this->assertCount(1, $tenant1Insights);
        $this->assertCount(1, $tenant2Insights);
        $this->assertEquals($tenant1->id, $tenant1Insights->first()->tenant_id);
        $this->assertEquals($tenant2->id, $tenant2Insights->first()->tenant_id);
    }

    /**
     * Test insight by type scope
     */
    public function test_by_type_scope(): void
    {
        Insight::factory()->create(['type' => 'trend']);
        Insight::factory()->create(['type' => 'recommendation']);
        Insight::factory()->create(['type' => 'trend']);

        $trendInsights = Insight::byType('trend')->get();
        $recommendationInsights = Insight::byType('recommendation')->get();

        $this->assertCount(2, $trendInsights);
        $this->assertCount(1, $recommendationInsights);
        $this->assertEquals('trend', $trendInsights->first()->type);
        $this->assertEquals('recommendation', $recommendationInsights->first()->type);
    }

    /**
     * Test insight active scope
     */
    public function test_active_scope(): void
    {
        Insight::factory()->create(['status' => 'active']);
        Insight::factory()->create(['status' => 'dismissed']);
        Insight::factory()->create(['status' => 'implemented']);

        $activeInsights = Insight::active()->get();

        $this->assertCount(1, $activeInsights);
        $this->assertEquals('active', $activeInsights->first()->status);
    }

    /**
     * Test insight by status scope
     */
    public function test_by_status_scope(): void
    {
        Insight::factory()->create(['status' => 'active']);
        Insight::factory()->create(['status' => 'dismissed']);
        Insight::factory()->create(['status' => 'implemented']);

        $dismissedInsights = Insight::byStatus('dismissed')->get();
        $implementedInsights = Insight::byStatus('implemented')->get();

        $this->assertCount(1, $dismissedInsights);
        $this->assertCount(1, $implementedInsights);
        $this->assertEquals('dismissed', $dismissedInsights->first()->status);
        $this->assertEquals('implemented', $implementedInsights->first()->status);
    }

    /**
     * Test insight is active method
     */
    public function test_is_active_method(): void
    {
        $activeInsight = Insight::factory()->create(['status' => 'active']);
        $dismissedInsight = Insight::factory()->create(['status' => 'dismissed']);

        $this->assertTrue($activeInsight->isActive());
        $this->assertFalse($dismissedInsight->isActive());
    }

    /**
     * Test insight is implemented method
     */
    public function test_is_implemented_method(): void
    {
        $implementedInsight = Insight::factory()->create(['status' => 'implemented']);
        $activeInsight = Insight::factory()->create(['status' => 'active']);

        $this->assertTrue($implementedInsight->isImplemented());
        $this->assertFalse($activeInsight->isImplemented());
    }

    /**
     * Test insight is dismissed method
     */
    public function test_is_dismissed_method(): void
    {
        $dismissedInsight = Insight::factory()->create(['status' => 'dismissed']);
        $activeInsight = Insight::factory()->create(['status' => 'active']);

        $this->assertTrue($dismissedInsight->isDismissed());
        $this->assertFalse($activeInsight->isDismissed());
    }

    /**
     * Test insight types static method
     */
    public function test_get_insight_types_method(): void
    {
        $types = Insight::getInsightTypes();

        $this->assertArrayHasKey('trend', $types);
        $this->assertArrayHasKey('recommendation', $types);
        $this->assertEquals('Trend Analysis', $types['trend']);
        $this->assertEquals('Actionable Recommendation', $types['recommendation']);
    }

    /**
     * Test insight statuses static method
     */
    public function test_get_insight_statuses_method(): void
    {
        $statuses = Insight::getInsightStatuses();

        $this->assertArrayHasKey('active', $statuses);
        $this->assertArrayHasKey('dismissed', $statuses);
        $this->assertArrayHasKey('implemented', $statuses);
        $this->assertEquals('Active', $statuses['active']);
        $this->assertEquals('Dismissed', $statuses['dismissed']);
        $this->assertEquals('Implemented', $statuses['implemented']);
    }

    /**
     * Test insight factory creation
     */
    public function test_factory_creation(): void
    {
        $insight = Insight::factory()->create();

        $this->assertInstanceOf(Insight::class, $insight);
        $this->assertNotNull($insight->id);
        $this->assertNotNull($insight->tenant_id);
        $this->assertContains($insight->type, ['trend', 'recommendation']);
        $this->assertContains($insight->status, ['active', 'dismissed', 'implemented']);
        $this->assertIsArray($insight->data);
    }

    /**
     * Test insight JSON data serialization
     */
    public function test_json_data_serialization(): void
    {
        $data = [
            'title' => 'Test Insight',
            'description' => 'This is a test insight',
            'impact_score' => 85,
            'metrics' => ['conversion_rate' => 12.5],
        ];

        $insight = Insight::factory()->create(['data' => $data]);

        $this->assertEquals($data, $insight->data);
        $this->assertEquals('Test Insight', $insight->data['title']);
        $this->assertEquals(85, $insight->data['impact_score']);
    }

    /**
     * Test insight effectiveness score casting
     */
    public function test_effectiveness_score_casting(): void
    {
        $insight = Insight::factory()->create(['effectiveness_score' => 85.75]);

        $this->assertIsFloat($insight->effectiveness_score);
        $this->assertEquals(85.75, $insight->effectiveness_score);

        // Test with null value
        $insight2 = Insight::factory()->create(['effectiveness_score' => null]);
        $this->assertNull($insight2->effectiveness_score);
    }

    /**
     * Test insight tracked_at datetime casting
     */
    public function test_tracked_at_datetime_casting(): void
    {
        $now = now();
        $insight = Insight::factory()->create(['tracked_at' => $now]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $insight->tracked_at);
        $this->assertEquals($now->toDateTimeString(), $insight->tracked_at->toDateTimeString());
    }

    /**
     * Test insight mass assignment protection
     */
    public function test_mass_assignment_protection(): void
    {
        $insight = new Insight();

        // Test that created_at and updated_at are not fillable
        $this->assertNotContains('created_at', $insight->getFillable());
        $this->assertNotContains('updated_at', $insight->getFillable());
        $this->assertNotContains('id', $insight->getFillable());
    }

    /**
     * Test insight database constraints
     */
    public function test_database_constraints(): void
    {
        // Test that tenant_id is required
        $this->expectException(\Illuminate\Database\QueryException::class);
        Insight::factory()->create(['tenant_id' => null]);
    }

    /**
     * Test insight soft deletes (if implemented)
     */
    public function test_soft_deletes(): void
    {
        $insight = Insight::factory()->create();

        // Check if soft deletes are enabled
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($insight));

        if ($usesSoftDeletes) {
            $insight->delete();
            $this->assertSoftDeleted($insight);
        } else {
            // If not using soft deletes, deletion should permanently remove
            $insight->delete();
            $this->assertDatabaseMissing('insights', ['id' => $insight->id]);
        }
    }
}