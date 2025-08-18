<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
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
            ]
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
    public function super_admin_can_view_analytics_page_with_benchmarking_and_market_trends_data()
    {
        // Mock the AnalyticsSnapshot models to return some data
        \App\Models\AnalyticsSnapshot::factory()->create([
            'type' => 'platform_benchmarks',
            'date' => now()->toDateString(),
            'data' => [
                ['institution_id' => 'tenant1', 'employment_rate' => 85, 'average_salary' => 60000]
            ]
        ]);

        \App\Models\AnalyticsSnapshot::factory()->create([
            'type' => 'market_trends',
            'date' => now()->toDateString(),
            'data' => [
                'top_skills' => [['skill' => 'PHP', 'count' => 10]],
            ]
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
