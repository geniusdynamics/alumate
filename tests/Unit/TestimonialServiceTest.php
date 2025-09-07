<?php

use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TestimonialService();
    $this->tenantId = 'test-tenant';
});

describe('TestimonialService', function () {
    it('can get testimonials with filtering', function () {
        // Create test testimonials
        Testimonial::factory()->count(5)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'audience_type' => 'individual',
        ]);
        
        Testimonial::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'audience_type' => 'institution',
        ]);

        $filters = ['audience_type' => 'individual'];
        $result = $this->service->getTestimonials($filters);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($result->total())->toBe(5);
    });

    it('can get testimonials for rotation with variety', function () {
        // Create featured testimonials
        Testimonial::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'featured' => true,
        ]);

        // Create regular testimonials
        Testimonial::factory()->count(7)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'featured' => false,
        ]);

        $result = $this->service->getTestimonialsForRotation([], 10);

        expect($result)->toHaveCount(10);
        
        // Should have a mix of featured and regular
        $featuredCount = $result->where('featured', true)->count();
        expect($featuredCount)->toBeGreaterThan(0);
        expect($featuredCount)->toBeLessThan(10);
    });

    it('can create a testimonial', function () {
        $data = [
            'tenant_id' => $this->tenantId,
            'author_name' => 'John Doe',
            'author_title' => 'Software Engineer',
            'author_company' => 'Tech Corp',
            'graduation_year' => 2020,
            'industry' => 'Technology',
            'audience_type' => 'individual',
            'content' => 'This is a great testimonial about the platform.',
            'rating' => 5,
            'status' => 'pending',
        ];

        $testimonial = $this->service->createTestimonial($data);

        expect($testimonial)->toBeInstanceOf(Testimonial::class);
        expect($testimonial->author_name)->toBe('John Doe');
        expect($testimonial->status)->toBe('pending');
    });

    it('can update a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'author_name' => 'Original Name',
        ]);

        $updateData = [
            'author_name' => 'Updated Name',
            'content' => 'Updated content for the testimonial.',
        ];

        $updated = $this->service->updateTestimonial($testimonial, $updateData);

        expect($updated->author_name)->toBe('Updated Name');
        expect($updated->content)->toBe('Updated content for the testimonial.');
    });

    it('can approve a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'status' => 'pending',
        ]);

        $result = $this->service->approveTestimonial($testimonial);

        expect($result)->toBeTrue();
        expect($testimonial->fresh()->status)->toBe('approved');
    });

    it('can reject a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'status' => 'pending',
        ]);

        $result = $this->service->rejectTestimonial($testimonial);

        expect($result)->toBeTrue();
        expect($testimonial->fresh()->status)->toBe('rejected');
    });

    it('can set testimonial as featured', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'featured' => false,
        ]);

        $result = $this->service->setFeatured($testimonial, true);

        expect($result)->toBeTrue();
        expect($testimonial->fresh()->featured)->toBeTrue();
    });

    it('can track testimonial views and clicks', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'view_count' => 0,
            'click_count' => 0,
        ]);

        $this->service->trackView($testimonial);
        expect($testimonial->fresh()->view_count)->toBe(1);

        $this->service->trackClick($testimonial);
        expect($testimonial->fresh()->click_count)->toBe(1);
    });

    it('can get performance analytics', function () {
        Testimonial::factory()->count(5)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'view_count' => 100,
            'click_count' => 10,
            'rating' => 5,
        ]);

        $analytics = $this->service->getPerformanceAnalytics();

        expect($analytics)->toHaveKey('total_testimonials');
        expect($analytics)->toHaveKey('total_views');
        expect($analytics)->toHaveKey('total_clicks');
        expect($analytics)->toHaveKey('avg_conversion_rate');
        expect($analytics)->toHaveKey('avg_rating');
        
        expect($analytics['total_testimonials'])->toBe(5);
        expect($analytics['total_views'])->toBe(500);
        expect($analytics['total_clicks'])->toBe(50);
    });

    it('can get filter options', function () {
        Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'audience_type' => 'individual',
            'industry' => 'Technology',
            'graduation_year' => 2020,
        ]);

        Testimonial::factory()->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
            'audience_type' => 'institution',
            'industry' => 'Healthcare',
            'graduation_year' => 2021,
        ]);

        $options = $this->service->getFilterOptions($this->tenantId);

        expect($options)->toHaveKey('audience_types');
        expect($options)->toHaveKey('industries');
        expect($options)->toHaveKey('graduation_years');
        
        expect($options['audience_types'])->toContain('individual', 'institution');
        expect($options['industries'])->toContain('Technology', 'Healthcare');
        expect($options['graduation_years'])->toContain(2020, 2021);
    });

    it('can export testimonials', function () {
        Testimonial::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'approved',
        ]);

        $exported = $this->service->exportTestimonials();

        expect($exported)->toHaveCount(3);
        expect($exported[0])->toHaveKey('id');
        expect($exported[0])->toHaveKey('author_name');
        expect($exported[0])->toHaveKey('content');
        expect($exported[0])->toHaveKey('created_at');
    });

    it('can import testimonials', function () {
        $testimonialsData = [
            [
                'author_name' => 'Import Test 1',
                'content' => 'This is imported testimonial 1.',
                'audience_type' => 'individual',
            ],
            [
                'author_name' => 'Import Test 2',
                'content' => 'This is imported testimonial 2.',
                'audience_type' => 'institution',
            ],
        ];

        $results = $this->service->importTestimonials($testimonialsData, $this->tenantId);

        expect($results['success'])->toBe(2);
        expect($results['errors'])->toBeEmpty();
        
        expect(Testimonial::where('tenant_id', $this->tenantId)->count())->toBe(2);
    });

    it('validates video testimonial requirements', function () {
        $data = [
            'tenant_id' => $this->tenantId,
            'author_name' => 'Video Test',
            'content' => 'This is a video testimonial.',
            'audience_type' => 'individual',
            'video_url' => 'https://example.com/video.mp4',
            // Missing video_thumbnail - should fail validation
        ];

        expect(fn() => $this->service->createTestimonial($data))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});