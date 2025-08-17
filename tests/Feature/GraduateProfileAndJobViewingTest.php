<?php

namespace Tests\Feature;

use App\Models\Graduate;
use App\Models\GraduateProfile;
use App\Models\Job;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduateProfileAndJobViewingTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $graduateUser;

    protected $graduate;

    protected $profile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Test Tenant']);
        $this->tenant->domains()->create(['domain' => 'test.localhost']);

        // Switch to the tenant's context
        tenancy()->initialize($this->tenant);

        // Create a graduate user
        $this->graduateUser = User::factory()->create();
        $this->graduateUser->assignRole('Graduate');
        $this->graduate = Graduate::factory()->create(['email' => $this->graduateUser->email]);
        $this->profile = GraduateProfile::factory()->create(['graduate_id' => $this->graduate->id]);

        $this->actingAs($this->graduateUser);
    }

    /** @test */
    public function a_graduate_can_view_their_profile()
    {
        $response = $this->get(route('profile.show'));

        $response->assertInertia(fn ($page) => $page->component('Profile/Show')
            ->has('graduate')
            ->has('profile')
            ->has('institution', fn ($prop) => $prop->where('name', 'Test Tenant'))
        );
    }

    /** @test */
    public function a_graduate_can_update_their_profile()
    {
        $response = $this->post(route('profile.update'), [
            'bio' => 'This is my bio.',
        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertEquals('This is my bio.', $this->profile->fresh()->bio);
    }

    /** @test */
    public function a_graduate_can_view_jobs()
    {
        Job::factory()->count(3)->create();

        $response = $this->get(route('jobs.public.index'));

        $response->assertInertia(function ($page) {
            $this->assertCount(3, $page->component('Jobs/PublicIndex')->prop('jobs'));
        });
    }
}
