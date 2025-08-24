<?php

use App\Models\Institution;
use App\Models\User;
use App\Services\WhiteLabelConfigService;

beforeEach(function () {
    $this->institution = Institution::factory()->create([
        'name' => 'Test University',
        'slug' => 'test-university',
        'domain' => 'test.edu',
        'primary_color' => '#007bff',
        'secondary_color' => '#6c757d',
        'feature_flags' => [
            'social_timeline' => true,
            'job_matching' => true,
            'mentorship' => false,
        ],
        'settings' => [
            'branding' => [
                'font_family' => 'roboto',
                'theme_style' => 'modern',
                'custom_css' => '.custom { color: red; }',
            ],
            'custom_fields' => [
                [
                    'name' => 'LinkedIn Profile',
                    'type' => 'text',
                    'required' => false,
                    'section' => 'profile',
                ],
            ],
        ],
        'integration_settings' => [
            [
                'name' => 'email_marketing',
                'enabled' => true,
                'config' => ['provider' => 'mailchimp'],
            ],
        ],
    ]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super-admin');

    $this->institutionAdmin = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);
    $this->institutionAdmin->assignRole('institution-admin');
});

it('can update branding settings', function () {
    $this->actingAs($this->superAdmin)
        ->post("/admin/institutions/{$this->institution->id}/branding", [
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'font_family' => 'poppins',
            'theme_style' => 'minimal',
            'custom_css' => '.new-style { background: blue; }',
        ])
        ->assertRedirect();

    $this->institution->refresh();

    expect($this->institution->primary_color)->toBe('#ff0000');
    expect($this->institution->secondary_color)->toBe('#00ff00');
});

it('can update feature flags', function () {
    $this->actingAs($this->superAdmin)
        ->post("/admin/institutions/{$this->institution->id}/features", [
            'features' => [
                'social_timeline' => false,
                'job_matching' => true,
                'mentorship' => true,
                'events' => true,
            ],
        ])
        ->assertRedirect();

    $this->institution->refresh();

    expect($this->institution->feature_flags['social_timeline'])->toBeFalse();
    expect($this->institution->feature_flags['mentorship'])->toBeTrue();
    expect($this->institution->feature_flags['events'])->toBeTrue();
});

it('can generate white-label configuration', function () {
    $this->actingAs($this->superAdmin)
        ->get("/admin/institutions/{$this->institution->id}/white-label-config")
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('white-label service generates complete configuration', function () {
    $service = new WhiteLabelConfigService;
    $config = $service->generateConfig($this->institution);

    expect($config)->toHaveKeys([
        'deployment',
        'branding',
        'features',
        'customization',
        'integrations',
        'environment',
    ]);

    expect($config['deployment']['subdomain'])->toBe('test-university');
    expect($config['branding']['name'])->toBe('Test University');
});
