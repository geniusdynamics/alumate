<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Graduate;
use App\Models\JobApplication;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CrossTenantAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected CrossTenantAggregationService $aggregationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
        $this->aggregationService = app(CrossTenantAggregationService::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function super_admin_can_view_dashboard_with_system_growth_data()
    {
        \App\Models\AnalyticsSnapshot::factory()->create([
            'type' => 'system_growth',
            'date' => now()->toDateString(),
            'data' => [
                'new_users' => 10,
                'new_institutions' => 2,
                'user_growth_data' => [['date' => '2025-08-17', 'count' => 5]],
            ],
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Dashboard')
                ->has('systemStats.new_users')
                ->where('systemStats.new_users', 10)
            );
    }

    /** @test */
    public function dashboard_shows_correct_total_graduates_across_tenants()
    {
        // Create multiple tenants with graduates
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1->run(function () {
            Graduate::factory()->count(10)->create();
        });

        $tenant2->run(function () {
            Graduate::factory()->count(15)->create();
        });

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemStats.total_graduates', 25)
            );
    }

    /** @test */
    public function dashboard_shows_correct_total_applications_across_tenants()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1->run(function () {
            JobApplication::factory()->count(8)->create();
        });

        $tenant2->run(function () {
            JobApplication::factory()->count(12)->create();
        });

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemStats.total_applications', 20)
            );
    }

    /** @test */
    public function employment_report_shows_correct_employment_rate_across_tenants()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1->run(function () {
            Graduate::factory()->count(10)->create(['employment_status' => 'employed']);
            Graduate::factory()->count(5)->create(['employment_status' => 'unemployed']);
        });

        $tenant2->run(function () {
            Graduate::factory()->count(8)->create(['employment_status' => 'self_employed']);
            Graduate::factory()->count(2)->create(['employment_status' => 'studying']);
        });

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.reports', ['type' => 'employment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reports.overall_employment_rate', 60.0) // 18 employed / 30 total * 100
            );
    }

    /** @test */
    public function employment_report_shows_correct_employment_by_status()
    {
        $tenant = Tenant::factory()->create();

        $tenant->run(function () {
            Graduate::factory()->create(['employment_status' => 'employed']);
            Graduate::factory()->create(['employment_status' => 'unemployed']);
            Graduate::factory()->create(['employment_status' => 'self_employed']);
            Graduate::factory()->create(['employment_status' => 'studying']);
            Graduate::factory()->create(['employment_status' => 'seeking']);
        });

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.reports', ['type' => 'employment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reports.employment_by_status')
            );
    }

    /** @test */
    public function institutions_page_shows_correct_graduate_counts_per_tenant()
    {
        $tenant1 = Tenant::factory()->create(['data' => ['name' => 'Institution 1']]);
        $tenant2 = Tenant::factory()->create(['data' => ['name' => 'Institution 2']]);

        $tenant1->run(function () {
            Graduate::factory()->count(10)->create();
        });

        $tenant2->run(function () {
            Graduate::factory()->count(20)->create();
        });

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.institutions'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('institutions', 2)
                ->where('institutions.0.graduates_count', 10)
                ->where('institutions.1.graduates_count', 20)
            );
    }

    /** @test */
    public function cross_tenant_data_isolation_is_maintained()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create graduates with specific employment status in each tenant
        $tenant1->run(function () {
            Graduate::factory()->count(5)->create(['employment_status' => 'employed']);
        });

        $tenant2->run(function () {
            Graduate::factory()->count(10)->create(['employment_status' => 'unemployed']);
        });

        // Verify tenant 1 has 5 employed graduates
        $tenant1Count = $this->aggregationService->getTenantGraduateCount($tenant1->id);
        $this->assertEquals(5, $tenant1Count);

        // Verify tenant 2 has 10 unemployed graduates
        $tenant2Count = $this->aggregationService->getTenantGraduateCount($tenant2->id);
        $this->assertEquals(10, $tenant2Count);

        // Verify total is 15
        $totalCount = $this->aggregationService->getTotalGraduateCount();
        $this->assertEquals(15, $totalCount);
    }

    /** @test */
    public function employment_trends_filter_by_date()
    {
        $tenant = Tenant::factory()->create();

        $tenant->run(function () {
            // Create graduates at different times
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'created_at' => now()->subDays(10),
            ]);
            Graduate::factory()->create([
                'employment_status' => 'unemployed',
                'created_at' => now()->subDays(5),
            ]);
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'created_at' => now()->subDays(2),
            ]);
        });

        // Get trends with start date (7 days ago)
        $startDate = now()->subDays(7);
        $trends = $this->aggregationService->getEmploymentTrends($startDate);

        // Only graduates created after start date should be counted
        $employed = $trends->firstWhere('employment_status', 'employed');
        $unemployed = $trends->firstWhere('employment_status', 'unemployed');

        $this->assertEquals(1, $employed['count']);
        $this->assertEquals(1, $unemployed['count']);
    }

    /** @test */
    public function cached_aggregated_metrics_improve_performance()
    {
        $tenant = Tenant::factory()->create();

        $tenant->run(function () {
            Graduate::factory()->count(100)->create();
        });

        // First call - should query database
        $start1 = microtime(true);
        $count1 = $this->aggregationService->getTotalGraduateCount();
        $time1 = microtime(true) - $start1;

        // Second call - should use cache
        $start2 = microtime(true);
        $count2 = $this->aggregationService->getTotalGraduateCount();
        $time2 = microtime(true) - $start2;

        // Both should return same value
        $this->assertEquals(100, $count1);
        $this->assertEquals(100, $count2);

        // Cached call should be faster
        $this->assertLessThanOrEqual($time1, $time2);
    }

    /** @test */
    public function aggregation_handles_tenant_database_errors_gracefully()
    {
        $tenant = Tenant::factory()->create();

        // Simulate database error by not creating graduates table
        // The service should handle this gracefully and return 0
        $count = $this->aggregationService->getTenantGraduateCount($tenant->id);

        // Should return 0 instead of throwing exception
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function employment_rate_calculated_correctly_across_tenants()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1->run(function () {
            Graduate::factory()->count(10)->create(['employment_status' => 'employed']);
            Graduate::factory()->count(5)->create(['employment_status' => 'unemployed']);
        });

        $tenant2->run(function () {
            Graduate::factory()->count(8)->create(['employment_status' => 'employed']);
            Graduate::factory()->count(2)->create(['employment_status' => 'self_employed']);
            Graduate::factory()->count(5)->create(['employment_status' => 'unemployed']);
        });

        $rate = $this->aggregationService->getOverallEmploymentRate();

        // Total graduates: 30
        // Employed (including self-employed): 20
        // Expected rate: (20 / 30) * 100 = 66.7
        $this->assertEquals(66.7, $rate);
    }

    /** @test */
    public function tenant_metrics_includes_employment_rate()
    {
        $tenant = Tenant::factory()->create();

        $tenant->run(function () {
            Graduate::factory()->count(10)->create(['employment_status' => 'employed']);
            Graduate::factory()->count(5)->create(['employment_status' => 'unemployed']);
            JobApplication::factory()->count(15)->create();
        });

        $metrics = $this->aggregationService->getTenantMetrics($tenant->id);

        $this->assertEquals(15, $metrics['graduate_count']);
        $this->assertEquals(66.7, $metrics['employment_rate']);
        $this->assertEquals(15, $metrics['total_applications']);
    }

    /** @test */
    public function super_admin_can_view_analytics_page_with_benchmarking_and_market_trends_data()
    {
        // Mock the AnalyticsSnapshot models to return some data
        \App\Models\AnalyticsSnapshot::factory()->create([
            'type' => 'platform_benchmarks',
            'date' => now()->toDateString(),
            'data' => [
                ['institution_id' => 'tenant1', 'employment_rate' => 85, 'average_salary' => 60000],
            ],
        ]);

        \App\Models\AnalyticsSnapshot::factory()->create([
            'type' => 'market_trends',
            'date' => now()->toDateString(),
            'data' => [
                'top_skills' => [['skill' => 'PHP', 'count' => 10]],
            ],
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Analytics')
                ->has('analytics.platform_benchmarks')
                ->where('analytics.platform_benchmarks.0.employment_rate', 85)
                ->has('analytics.market_trends')
                ->where('analytics.market_trends.top_skills.0.skill', 'PHP')
            );
    }
}
