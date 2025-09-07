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

    /** @test */
    public function institution_admin_can_view_course_roi_page()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.analytics.course-roi'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Analytics/CourseROI')
            );
    }

    /** @test */
    public function course_roi_api_returns_correct_data()
    {
        $this->actingAs($this->institutionAdmin);

        // You might want to create some courses and graduates here to test the actual calculation

        $response = $this->getJson(route('institution-admin.api.analytics.course-roi'));

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'course_name',
                    'average_salary',
                    'total_graduates',
                    'estimated_roi_percentage',
                ],
            ]);
    }

    /** @test */
    public function institution_admin_can_view_employer_engagement_page()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.analytics.employer-engagement'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Analytics/EmployerEngagement')
            );
    }

    /** @test */
    public function employer_engagement_api_returns_correct_data()
    {
        $this->actingAs($this->institutionAdmin);

        $response = $this->getJson(route('institution-admin.api.analytics.employer-engagement'));

        $response->assertOk()
            ->assertJsonStructure([
                'top_engaging_employers',
                'most_in_demand_skills',
                'hiring_trends_by_industry',
            ]);
    }

    /** @test */
    public function institution_admin_can_view_community_health_page()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.analytics.community-health'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Analytics/CommunityHealth')
            );
    }

    /** @test */
    public function community_health_api_returns_correct_data()
    {
        $this->actingAs($this->institutionAdmin);

        $response = $this->getJson(route('institution-admin.api.analytics.community-health'));

        $response->assertOk()
            ->assertJsonStructure([
                'daily_active_users',
                'post_activity',
                'engagement_trends',
                'group_participation',
                'events_attended',
                'connections_made',
            ]);
    }
}
