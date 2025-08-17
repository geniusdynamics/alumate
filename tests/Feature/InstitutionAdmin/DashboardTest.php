<?php

namespace Tests\Feature\InstitutionAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $institutionAdmin;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();

        $this->institutionAdmin = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->institutionAdmin->assignRole('institution-admin');

        tenancy()->initialize($this->institution);
    }

    /** @test */
    public function institution_admin_can_view_dashboard()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Dashboard')
                ->hasAll(['stats', 'recentActivities', 'employmentStats', 'coursePerformance'])
            );
    }

    /** @test */
    public function institution_admin_can_view_analytics_page_with_new_metrics()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.analytics'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Analytics')
                ->has('analytics.timeToEmployment')
                ->has('analytics.salaryProgression')
                ->has('analytics.employmentByLocation')
            );
    }
}
