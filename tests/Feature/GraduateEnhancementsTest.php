<?php

namespace Tests\Feature;

use App\Models\Graduate;
use App\Models\GraduateProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduateEnhancementsTest extends TestCase
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
    public function a_graduate_can_add_their_education_history()
    {
        $response = $this->post(route('education.store'), [
            'institution_name' => 'Test University',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
            'start_year' => 2018,
            'end_year' => 2022,
        ]);

        $response->assertRedirect(route('education.index'));
        $this->assertDatabaseHas('education_histories', ['institution_name' => 'Test University']);
    }

    /** @test */
    public function a_graduate_can_mark_themselves_as_self_employed()
    {
        $response = $this->post(route('profile.update'), [
            'self_employed' => true,
        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertTrue($this->profile->fresh()->self_employed);
    }

    /** @test */
    public function a_graduate_can_request_assistance()
    {
        $response = $this->post(route('assistance.store'), [
            'subject' => 'Help with my profile',
            'message' => 'I need help updating my profile.',
        ]);

        $response->assertRedirect(route('assistance.index'));
        $this->assertDatabaseHas('assistance_requests', ['subject' => 'Help with my profile']);
    }
}
