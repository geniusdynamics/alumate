<?php

use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->page = LandingPage::factory()->create([
        'tenant_id' => $this->user->tenant_id,
        'created_by' => $this->user->id,
        'content' => '<div>Test content</div>',
        'styles' => 'body { margin: 0; }'
    ]);
});

it('can generate page preview', function () {
    $response = $this->actingAs($this->user)
        ->get("/api/pages/{$this->page->id}/preview");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    
    $content = $response->getContent();
    expect($content)->toContain('<div>Test content</div>');
    expect($content)->toContain('body { margin: 0; }');
});

it('can generate preview with device mode', function () {
    $response = $this->actingAs($this->user)
        ->get("/api/pages/{$this->page->id}/preview?device=mobile");

    $response->assertStatus(200);
    
    $content = $response->getContent();
    expect($content)->toContain('max-w-sm mx-auto'); // Mobile device class
});

it('can generate preview with interaction mode', function () {
    $response = $this->actingAs($this->user)
        ->get("/api/pages/{$this->page->id}/preview?interaction_mode=1");

    $response->assertStatus(200);
    
    $content = $response->getContent();
    expect($content)->toContain('trackInteraction'); // Interaction tracking script
});

it('can update preview with real-time changes', function () {
    $newHtml = '<div>Updated content</div>';
    $newCss = 'body { background: red; }';

    $response = $this->actingAs($this->user)
        ->postJson("/api/pages/{$this->page->id}/preview", [
            'html' => $newHtml,
            'css' => $newCss,
            'device' => 'tablet'
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);
    
    expect($response->json('preview_url'))->toContain('cache_key');
});

it('validates preview update request', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/pages/{$this->page->id}/preview", [
            'html' => '', // Missing required field
            'css' => 'body { margin: 0; }'
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['html']);
});

it('handles preview generation errors gracefully', function () {
    // Test with non-existent page
    $response = $this->actingAs($this->user)
        ->get("/api/pages/999999/preview");

    $response->assertStatus(404);
});

it('includes performance tracking script in preview', function () {
    $response = $this->actingAs($this->user)
        ->get("/api/pages/{$this->page->id}/preview");

    $response->assertStatus(200);
    
    $content = $response->getContent();
    expect($content)->toContain('performance.getEntriesByType');
    expect($content)->toContain('PerformanceObserver');
});

it('applies correct device classes for different devices', function () {
    $devices = [
        'desktop' => 'min-w-full',
        'tablet' => 'max-w-3xl mx-auto',
        'mobile' => 'max-w-sm mx-auto'
    ];

    foreach ($devices as $device => $expectedClass) {
        $response = $this->actingAs($this->user)
            ->get("/api/pages/{$this->page->id}/preview?device={$device}");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        expect($content)->toContain($expectedClass);
    }
});

it('includes form enhancement script in preview', function () {
    $htmlWithForm = '<form><input name="test" /></form>';
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/pages/{$this->page->id}/preview", [
            'html' => $htmlWithForm,
            'css' => 'body { margin: 0; }'
        ]);

    $response->assertStatus(200);
    
    // Get the preview URL and fetch the content
    $previewUrl = $response->json('preview_url');
    $previewResponse = $this->actingAs($this->user)->get($previewUrl);
    
    $content = $previewResponse->getContent();
    expect($content)->toContain('form.addEventListener');
    expect($content)->toContain('preventDefault');
});

it('requires authentication for preview access', function () {
    $response = $this->get("/api/pages/{$this->page->id}/preview");

    $response->assertStatus(401);
});

it('prevents access to other tenants pages', function () {
    $otherUser = User::factory()->create(['tenant_id' => 'other-tenant']);
    
    $response = $this->actingAs($otherUser)
        ->get("/api/pages/{$this->page->id}/preview");

    $response->assertStatus(404);
});