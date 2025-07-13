<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UIImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_displays_the_correct_dashboard_for_each_role()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $this->actingAs($superAdmin);
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->component('Dashboard/SuperAdmin'));

        $institutionAdmin = User::factory()->create();
        $institutionAdmin->assignRole('Institution Admin');
        $this->actingAs($institutionAdmin);
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->component('Dashboard/InstitutionAdmin'));

        $graduate = User::factory()->create();
        $graduate->assignRole('Graduate');
        $this->actingAs($graduate);
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->component('Dashboard/Graduate'));

        $employer = User::factory()->create();
        $employer->assignRole('Employer');
        $this->actingAs($employer);
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->component('Dashboard/Employer'));
    }

    /** @test */
    public function it_can_filter_jobs_by_search_term()
    {
        \App\Models\Job::factory()->create(['title' => 'Software Engineer']);
        \App\Models\Job::factory()->create(['title' => 'Product Manager']);

        $this->get(route('jobs.public.index', ['search' => 'Software']))
            ->assertInertia(function ($page) {
                $this->assertCount(1, $page->component('Jobs/PublicIndex')->prop('jobs.data'));
                $this->assertEquals('Software Engineer', $page->component('Jobs/PublicIndex')->prop('jobs.data.0.title'));
            });
    }
}
