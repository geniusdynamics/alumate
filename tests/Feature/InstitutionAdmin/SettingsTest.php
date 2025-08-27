<?php

namespace Tests\Feature\InstitutionAdmin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $institutionAdmin;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an institution
        $this->institution = Institution::factory()->create();

        // Create an institution admin for that institution
        $this->institutionAdmin = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->institutionAdmin->assignRole('institution-admin');

        // Set the tenant context
        tenancy()->initialize($this->institution);
    }

    /** @test */
    public function institution_admin_can_view_branding_settings_page()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.settings.branding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Settings/Branding')
                ->has('institution')
            );
    }

    /** @test */
    public function institution_admin_can_update_branding_settings()
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png');
        $primaryColor = '#123456';
        $secondaryColor = '#654321';

        $this->actingAs($this->institutionAdmin)
            ->post(route('institution-admin.settings.branding.update'), [
                'logo' => $logo,
                'primary_color' => $primaryColor,
                'secondary_color' => $secondaryColor,
            ]);

        $this->institution->refresh();

        Storage::disk('public')->assertExists($this->institution->logo_path);
        $this->assertEquals($primaryColor, $this->institution->primary_color);
        $this->assertEquals($secondaryColor, $this->institution->secondary_color);
    }

    /** @test */
    public function institution_admin_can_update_feature_flags()
    {
        $this->actingAs($this->institutionAdmin)
            ->post(route('institution-admin.settings.branding.update'), [
                'feature_flags' => [
                    'enable_social_timeline' => false,
                    'enable_fundraising' => true,
                ],
            ]);

        $this->institution->refresh();

        $this->assertFalse($this->institution->feature_flags['enable_social_timeline']);
        $this->assertTrue($this->institution->feature_flags['enable_fundraising']);
    }

    /** @test */
    public function institution_admin_can_view_integrations_settings_page()
    {
        $this->actingAs($this->institutionAdmin)
            ->get(route('institution-admin.settings.integrations'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('InstitutionAdmin/Settings/Integrations')
                ->has('settings')
            );
    }

    /** @test */
    public function institution_admin_can_update_integration_settings()
    {
        $settings = [
            'email' => ['apiKey' => 'test-email-key'],
            'crm' => ['apiUrl' => 'https://example.com/api'],
        ];

        $this->actingAs($this->institutionAdmin)
            ->post(route('institution-admin.settings.integrations.update'), [
                'integrations' => $settings,
            ]);

        $this->institution->refresh();

        $this->assertEquals($settings, $this->institution->integration_settings);
    }
}
