<?php

use App\Models\HomepageContent;
use App\Models\User;
use App\Services\HomepageContentService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = new HomepageContentService;
});

test('can get content for audience', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create content for different audiences
    HomepageContent::factory()->individual()->published()->create([
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Individual headline',
    ]);

    HomepageContent::factory()->institutional()->published()->create([
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Institutional headline',
    ]);

    HomepageContent::factory()->published()->create([
        'section' => 'hero',
        'key' => 'subtitle',
        'value' => 'Both audiences subtitle',
        'audience' => 'both',
    ]);

    // Test individual audience
    $individualContent = $this->service->getContent('individual');
    expect($individualContent)->toHaveCount(2); // individual + both

    // Test institutional audience
    $institutionalContent = $this->service->getContent('institutional');
    expect($institutionalContent)->toHaveCount(2); // institutional + both
});

test('can get formatted content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    HomepageContent::factory()->published()->create([
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Test headline',
        'metadata' => ['image_url' => 'test.jpg'],
    ]);

    $formatted = $this->service->getFormattedContent('both');

    expect($formatted)->toHaveKey('hero');
    expect($formatted['hero'])->toHaveKey('headline');
    expect($formatted['hero']['headline']['value'])->toBe('Test headline');
    expect($formatted['hero']['headline']['metadata'])->toBe(['image_url' => 'test.jpg']);
});

test('can update content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = $this->service->updateContent(
        'hero',
        'headline',
        'New headline',
        'individual',
        ['image_url' => 'new.jpg'],
        'Updated headline text'
    );

    expect($content->section)->toBe('hero');
    expect($content->key)->toBe('headline');
    expect($content->value)->toBe('New headline');
    expect($content->audience)->toBe('individual');
    expect($content->metadata)->toBe(['image_url' => 'new.jpg']);
    expect($content->status)->toBe('draft');

    // Check version was created
    expect($content->versions)->toHaveCount(1);
    expect($content->versions->first()->change_notes)->toBe('Updated headline text');
});

test('can update existing content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create initial content
    $original = HomepageContent::factory()->create([
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Original headline',
        'audience' => 'individual',
        'created_by' => $user->id,
    ]);

    // Update the content
    $updated = $this->service->updateContent(
        'hero',
        'headline',
        'Updated headline',
        'individual',
        null,
        'Content update'
    );

    expect($updated->id)->toBe($original->id);
    expect($updated->value)->toBe('Updated headline');
    expect($updated->status)->toBe('draft');

    // Check version was created
    expect($updated->versions)->toHaveCount(1);
});

test('can request approval', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = HomepageContent::factory()->create([
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $approval = $this->service->requestApproval($content->id, 'Please review this content');

    $content->refresh();
    expect($content->status)->toBe('pending');
    expect($approval->request_notes)->toBe('Please review this content');
    expect($approval->requested_by)->toBe($user->id);
});

test('can approve content', function () {
    $user = User::factory()->create();
    $approver = User::factory()->create();
    $this->actingAs($approver);

    $content = HomepageContent::factory()->create([
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $content->requestApproval('Please approve');

    $this->service->approveContent($content->id, 'Approved with minor changes');

    $content->refresh();
    expect($content->status)->toBe('approved');
    expect($content->approved_by)->toBe($approver->id);
    expect($content->approved_at)->not->toBeNull();
});

test('can publish approved content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = HomepageContent::factory()->create([
        'status' => 'approved',
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $this->service->publishContent($content->id);

    $content->refresh();
    expect($content->status)->toBe('published');
    expect($content->published_at)->not->toBeNull();
});

test('cannot publish unapproved content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = HomepageContent::factory()->create([
        'status' => 'draft',
    ]);

    expect(fn () => $this->service->publishContent($content->id))
        ->toThrow(Exception::class, 'Content must be approved before publishing');
});

test('can revert to version', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = HomepageContent::factory()->create([
        'value' => 'Current value',
        'created_by' => $user->id,
    ]);

    // Create a version
    $content->createVersion('Initial version');

    // Update content
    $content->update(['value' => 'Updated value']);
    $content->createVersion('Updated version');

    // Revert to first version
    $reverted = $this->service->revertToVersion($content->id, 1);

    expect($reverted->value)->toBe('Current value');
    expect($reverted->status)->toBe('draft');
    expect($reverted->versions)->toHaveCount(3); // original + updated + revert
});

test('can bulk update content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $updates = [
        [
            'section' => 'hero',
            'key' => 'headline',
            'value' => 'Bulk headline 1',
            'audience' => 'individual',
        ],
        [
            'section' => 'hero',
            'key' => 'subtitle',
            'value' => 'Bulk subtitle 1',
            'audience' => 'individual',
        ],
    ];

    $results = $this->service->bulkUpdateContent($updates);

    expect($results)->toHaveCount(2);
    expect($results[0]->value)->toBe('Bulk headline 1');
    expect($results[1]->value)->toBe('Bulk subtitle 1');
});

test('content caching works', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    HomepageContent::factory()->published()->create([
        'section' => 'hero',
        'key' => 'headline',
        'audience' => 'individual',
    ]);

    // First call should cache the result
    $content1 = $this->service->getContent('individual');

    // Second call should use cache
    $content2 = $this->service->getContent('individual');

    expect($content1->count())->toBe($content2->count());

    // Verify cache key exists
    expect(Cache::has('homepage_content_individual_'))->toBeTrue();
});

test('can export content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    HomepageContent::factory()->count(3)->create();

    $exported = $this->service->exportContent();

    expect($exported)->toHaveCount(3);
    expect($exported[0])->toHaveKey('section');
    expect($exported[0])->toHaveKey('key');
    expect($exported[0])->toHaveKey('value');
});

test('can import content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contentData = [
        [
            'section' => 'hero',
            'key' => 'headline',
            'value' => 'Imported headline',
            'audience' => 'individual',
            'metadata' => ['test' => 'data'],
        ],
        [
            'section' => 'hero',
            'key' => 'subtitle',
            'value' => 'Imported subtitle',
            'audience' => 'institutional',
            'metadata' => null,
        ],
    ];

    $results = $this->service->importContent($contentData);

    expect($results)->toHaveCount(2);
    expect($results[0]->value)->toBe('Imported headline');
    expect($results[1]->value)->toBe('Imported subtitle');
    expect($results[0]->audience)->toBe('individual');
    expect($results[1]->audience)->toBe('institutional');
});
