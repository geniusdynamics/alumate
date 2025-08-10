<?php

use App\Models\HomepageContent;
use App\Models\User;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    // In a real app, you'd assign admin role here
    $this->actingAs($this->admin);
});

test('can view content management index', function () {
    HomepageContent::factory()->count(3)->create();

    $response = $this->get('/admin/homepage-content');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Admin/HomepageContent/Index')
             ->has('content', 3)
    );
});

test('can get content via api', function () {
    HomepageContent::factory()->published()->create([
        'audience' => 'individual',
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Test headline',
    ]);

    $response = $this->get('/admin/homepage-content/content?audience=individual');

    $response->assertStatus(200);
    $response->assertJson([
        'audience' => 'individual',
        'content' => [
            'hero' => [
                'headline' => [
                    'value' => 'Test headline',
                ]
            ]
        ]
    ]);
});

test('can update content', function () {
    $response = $this->put('/admin/homepage-content', [
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'New headline',
        'audience' => 'individual',
        'metadata' => ['image_url' => 'test.jpg'],
        'change_notes' => 'Updated headline',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Content updated successfully',
    ]);

    $this->assertDatabaseHas('homepage_content', [
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'New headline',
        'audience' => 'individual',
    ]);
});

test('can bulk update content', function () {
    $updates = [
        [
            'section' => 'hero',
            'key' => 'headline',
            'value' => 'Bulk headline',
            'audience' => 'individual',
        ],
        [
            'section' => 'hero',
            'key' => 'subtitle',
            'value' => 'Bulk subtitle',
            'audience' => 'individual',
        ],
    ];

    $response = $this->post('/admin/homepage-content/bulk-update', [
        'updates' => $updates,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'updated_count' => 2,
    ]);

    $this->assertDatabaseHas('homepage_content', [
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Bulk headline',
    ]);

    $this->assertDatabaseHas('homepage_content', [
        'section' => 'hero',
        'key' => 'subtitle',
        'value' => 'Bulk subtitle',
    ]);
});

test('can request approval for content', function () {
    $content = HomepageContent::factory()->create([
        'status' => 'draft',
        'created_by' => $this->admin->id,
    ]);

    $response = $this->post("/admin/homepage-content/{$content->id}/request-approval", [
        'notes' => 'Please review this content',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Approval requested successfully',
    ]);

    $content->refresh();
    expect($content->status)->toBe('pending');
});

test('can approve content', function () {
    $content = HomepageContent::factory()->create([
        'status' => 'pending',
        'created_by' => $this->admin->id,
    ]);

    $content->requestApproval('Please approve');

    $response = $this->post("/admin/homepage-content/{$content->id}/approve", [
        'notes' => 'Approved',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Content approved successfully',
    ]);

    $content->refresh();
    expect($content->status)->toBe('approved');
});

test('can reject content', function () {
    $content = HomepageContent::factory()->create([
        'status' => 'pending',
        'created_by' => $this->admin->id,
    ]);

    $content->requestApproval('Please approve');

    $response = $this->post("/admin/homepage-content/{$content->id}/reject", [
        'notes' => 'Needs more work',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Content approval rejected',
    ]);

    $content->refresh();
    expect($content->status)->toBe('draft');
});

test('can publish approved content', function () {
    $content = HomepageContent::factory()->create([
        'status' => 'approved',
        'approved_by' => $this->admin->id,
        'approved_at' => now(),
    ]);

    $response = $this->post("/admin/homepage-content/{$content->id}/publish");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Content published successfully',
    ]);

    $content->refresh();
    expect($content->status)->toBe('published');
});

test('can get content history', function () {
    $content = HomepageContent::factory()->create([
        'created_by' => $this->admin->id,
    ]);

    // Create some versions
    $content->createVersion('Version 1');
    $content->createVersion('Version 2');

    $response = $this->get("/admin/homepage-content/{$content->id}/history");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
    $response->assertJsonCount(2, 'history');
});

test('can revert to version', function () {
    $content = HomepageContent::factory()->create([
        'value' => 'Original value',
        'created_by' => $this->admin->id,
    ]);

    $content->createVersion('Original version');
    $content->update(['value' => 'Updated value']);

    $response = $this->post("/admin/homepage-content/{$content->id}/revert", [
        'version_number' => 1,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Content reverted successfully',
    ]);

    $content->refresh();
    expect($content->value)->toBe('Original value');
});

test('can preview content changes', function () {
    HomepageContent::factory()->published()->create([
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Current headline',
        'audience' => 'individual',
    ]);

    $changes = [
        [
            'section' => 'hero',
            'key' => 'headline',
            'value' => 'Preview headline',
        ],
    ];

    $response = $this->post('/admin/homepage-content/preview', [
        'changes' => $changes,
        'audience' => 'individual',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'preview' => [
            'hero' => [
                'headline' => [
                    'value' => 'Preview headline',
                    'preview' => true,
                ]
            ]
        ]
    ]);
});

test('can export content', function () {
    HomepageContent::factory()->count(2)->create();

    $response = $this->get('/admin/homepage-content/export');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
    $response->assertJsonCount(2, 'content');
});

test('can import content', function () {
    $contentData = [
        [
            'section' => 'hero',
            'key' => 'headline',
            'value' => 'Imported headline',
            'audience' => 'individual',
            'metadata' => null,
        ],
    ];

    $response = $this->post('/admin/homepage-content/import', [
        'content' => $contentData,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'imported_count' => 1,
    ]);

    $this->assertDatabaseHas('homepage_content', [
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Imported headline',
    ]);
});

test('validates content update request', function () {
    $response = $this->put('/admin/homepage-content', [
        'section' => '', // Invalid: required
        'key' => 'headline',
        'value' => 'Test',
        'audience' => 'invalid', // Invalid: not in enum
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['section', 'audience']);
});

test('validates bulk update request', function () {
    $response = $this->post('/admin/homepage-content/bulk-update', [
        'updates' => [
            [
                'section' => 'hero',
                // Missing required 'key' field
                'value' => 'Test',
                'audience' => 'individual',
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['updates.0.key']);
});
