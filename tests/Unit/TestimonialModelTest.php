<?php

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Testimonial Model', function () {
    it('can create a testimonial with required fields', function () {
        $testimonial = Testimonial::create([
            'tenant_id' => 'test-tenant',
            'author_name' => 'John Doe',
            'content' => 'This is a great testimonial about the platform.',
            'audience_type' => 'individual',
        ]);

        expect($testimonial)->toBeInstanceOf(Testimonial::class);
        expect($testimonial->author_name)->toBe('John Doe');
        expect($testimonial->content)->toBe('This is a great testimonial about the platform.');
        expect($testimonial->audience_type)->toBe('individual');
        expect($testimonial->status)->toBe('pending'); // Default status
        expect($testimonial->featured)->toBeFalse(); // Default featured
    });

    it('has correct default values', function () {
        $testimonial = new Testimonial([
            'tenant_id' => 'test-tenant',
            'author_name' => 'Jane Doe',
            'content' => 'Another testimonial.',
            'audience_type' => 'institution',
        ]);

        expect($testimonial->status)->toBe('pending');
        expect($testimonial->featured)->toBeFalse();
        expect($testimonial->view_count)->toBe(0);
        expect($testimonial->click_count)->toBe(0);
        expect((float)$testimonial->conversion_rate)->toBe(0.0);
    });

    it('can check if testimonial is approved', function () {
        $testimonial = Testimonial::factory()->make(['status' => 'approved']);
        expect($testimonial->isApproved())->toBeTrue();

        $testimonial = Testimonial::factory()->make(['status' => 'pending']);
        expect($testimonial->isApproved())->toBeFalse();
    });

    it('can check if testimonial has video', function () {
        $testimonial = Testimonial::factory()->make([
            'video_url' => 'https://example.com/video.mp4',
            'video_thumbnail' => 'https://example.com/thumb.jpg',
        ]);
        expect($testimonial->hasVideo())->toBeTrue();

        $testimonial = Testimonial::factory()->make([
            'video_url' => null,
            'video_thumbnail' => null,
        ]);
        expect($testimonial->hasVideo())->toBeFalse();
    });

    it('generates correct author display name', function () {
        $testimonial = Testimonial::factory()->make([
            'author_name' => 'John Doe',
            'author_title' => 'Software Engineer',
            'author_company' => 'Tech Corp',
        ]);

        expect($testimonial->author_display_name)->toBe('John Doe, Software Engineer at Tech Corp');

        $testimonial = Testimonial::factory()->make([
            'author_name' => 'Jane Smith',
            'author_title' => 'Manager',
            'author_company' => null,
        ]);

        expect($testimonial->author_display_name)->toBe('Jane Smith, Manager');

        $testimonial = Testimonial::factory()->make([
            'author_name' => 'Bob Johnson',
            'author_title' => null,
            'author_company' => 'Big Corp',
        ]);

        expect($testimonial->author_display_name)->toBe('Bob Johnson at Big Corp');
    });

    it('can truncate content for previews', function () {
        $longContent = str_repeat('This is a long testimonial. ', 20);
        $testimonial = Testimonial::factory()->make(['content' => $longContent]);

        expect(strlen($testimonial->truncated_content))->toBeLessThanOrEqual(150);
        expect($testimonial->truncated_content)->toEndWith('...');
    });

    it('can approve, reject, and archive testimonials', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'status' => 'pending',
        ]);

        expect($testimonial->approve())->toBeTrue();
        expect($testimonial->fresh()->status)->toBe('approved');

        expect($testimonial->reject())->toBeTrue();
        expect($testimonial->fresh()->status)->toBe('rejected');

        expect($testimonial->archive())->toBeTrue();
        expect($testimonial->fresh()->status)->toBe('archived');
    });

    it('can set featured status', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'featured' => false,
        ]);

        expect($testimonial->setFeatured(true))->toBeTrue();
        expect($testimonial->fresh()->featured)->toBeTrue();

        expect($testimonial->setFeatured(false))->toBeTrue();
        expect($testimonial->fresh()->featured)->toBeFalse();
    });

    it('can increment view and click counts', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'view_count' => 0,
            'click_count' => 0,
        ]);

        $testimonial->incrementViewCount();
        expect($testimonial->fresh()->view_count)->toBe(1);

        $testimonial->incrementClickCount();
        expect($testimonial->fresh()->click_count)->toBe(1);
    });

    it('can manage metadata', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'metadata' => ['source' => 'website', 'tags' => ['success']],
        ]);

        expect($testimonial->getMetadataValue('source'))->toBe('website');
        expect($testimonial->getMetadataValue('tags'))->toBe(['success']);
        expect($testimonial->getMetadataValue('nonexistent', 'default'))->toBe('default');

        $testimonial->setMetadataValue('new_field', 'new_value');
        $testimonial->save();

        expect($testimonial->fresh()->getMetadataValue('new_field'))->toBe('new_value');
    });
});