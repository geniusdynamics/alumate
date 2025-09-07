<?php

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'tenant_id' => 'test-tenant',
    ]);
    
    $this->actingAs($this->user, 'sanctum');
});

describe('TestimonialController', function () {
    it('can list testimonials', function () {
        Testimonial::factory()->count(5)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/testimonials');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'author',
                            'content',
                            'audience_type',
                            'status',
                            'performance',
                        ]
                    ],
                    'meta',
                    'links',
                ]);
    });

    it('can filter testimonials by audience type', function () {
        Testimonial::factory()->count(3)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
            'audience_type' => 'individual',
        ]);

        Testimonial::factory()->count(2)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
            'audience_type' => 'institution',
        ]);

        $response = $this->getJson('/api/testimonials?audience_type=individual');

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(3);
    });

    it('can get testimonials for rotation', function () {
        Testimonial::factory()->count(10)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/testimonials-rotation?limit=5');

        $response->assertOk()
                ->assertJsonCount(5, 'data');
    });

    it('can create a testimonial', function () {
        $data = [
            'author_name' => 'John Doe',
            'author_title' => 'Software Engineer',
            'author_company' => 'Tech Corp',
            'graduation_year' => 2020,
            'industry' => 'Technology',
            'audience_type' => 'individual',
            'content' => 'This is a great testimonial about the platform.',
            'rating' => 5,
        ];

        $response = $this->postJson('/api/testimonials', $data);

        $response->assertCreated()
                ->assertJsonFragment([
                    'author' => [
                        'name' => 'John Doe',
                        'title' => 'Software Engineer',
                        'company' => 'Tech Corp',
                        'photo' => null,
                        'display_name' => 'John Doe, Software Engineer at Tech Corp',
                    ],
                ]);

        $this->assertDatabaseHas('testimonials', [
            'author_name' => 'John Doe',
            'tenant_id' => 'test-tenant',
            'status' => 'pending',
        ]);
    });

    it('can show a specific testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/testimonials/{$testimonial->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    'id' => $testimonial->id,
                ]);

        // Should track view
        expect($testimonial->fresh()->view_count)->toBe(1);
    });

    it('can update a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'author_name' => 'Original Name',
        ]);

        $updateData = [
            'author_name' => 'Updated Name',
            'content' => 'Updated content for the testimonial.',
        ];

        $response = $this->putJson("/api/testimonials/{$testimonial->id}", $updateData);

        $response->assertOk()
                ->assertJsonPath('author.name', 'Updated Name');

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'author_name' => 'Updated Name',
        ]);
    });

    it('can delete a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
        ]);

        $response = $this->deleteJson("/api/testimonials/{$testimonial->id}");

        $response->assertOk()
                ->assertJson(['message' => 'Testimonial deleted successfully']);

        $this->assertDatabaseMissing('testimonials', [
            'id' => $testimonial->id,
        ]);
    });

    it('can approve a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/testimonials/{$testimonial->id}/approve");

        $response->assertOk()
                ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'status' => 'approved',
        ]);
    });

    it('can reject a testimonial', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/testimonials/{$testimonial->id}/reject");

        $response->assertOk()
                ->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'status' => 'rejected',
        ]);
    });

    it('can set testimonial as featured', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'featured' => false,
        ]);

        $response = $this->postJson("/api/testimonials/{$testimonial->id}/featured", [
            'featured' => true,
        ]);

        $response->assertOk()
                ->assertJsonPath('featured', true);

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'featured' => true,
        ]);
    });

    it('can track testimonial clicks', function () {
        $testimonial = Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'click_count' => 0,
        ]);

        $response = $this->postJson("/api/testimonials/{$testimonial->id}/track-click");

        $response->assertOk()
                ->assertJson(['message' => 'Click tracked successfully']);

        expect($testimonial->fresh()->click_count)->toBe(1);
    });

    it('can get performance analytics', function () {
        Testimonial::factory()->count(5)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
            'view_count' => 100,
            'click_count' => 10,
        ]);

        $response = $this->getJson('/api/testimonials-analytics');

        $response->assertOk()
                ->assertJsonStructure([
                    'total_testimonials',
                    'total_views',
                    'total_clicks',
                    'avg_conversion_rate',
                    'avg_rating',
                    'featured_count',
                    'video_count',
                    'text_count',
                ]);
    });

    it('can get filter options', function () {
        Testimonial::factory()->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
            'audience_type' => 'individual',
            'industry' => 'Technology',
            'graduation_year' => 2020,
        ]);

        $response = $this->getJson('/api/testimonials-filter-options');

        $response->assertOk()
                ->assertJsonStructure([
                    'audience_types',
                    'industries',
                    'graduation_years',
                    'graduation_year_ranges',
                ]);
    });

    it('can export testimonials', function () {
        Testimonial::factory()->count(3)->create([
            'tenant_id' => 'test-tenant',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/testimonials-export');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'author_name',
                            'content',
                            'audience_type',
                            'created_at',
                        ]
                    ],
                    'count',
                    'exported_at',
                ]);

        expect($response->json('count'))->toBe(3);
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

        $response = $this->postJson('/api/testimonials-import', [
            'testimonials' => $testimonialsData,
        ]);

        $response->assertOk()
                ->assertJsonFragment([
                    'success_count' => 2,
                    'error_count' => 0,
                ]);

        $this->assertDatabaseHas('testimonials', [
            'author_name' => 'Import Test 1',
            'tenant_id' => 'test-tenant',
        ]);

        $this->assertDatabaseHas('testimonials', [
            'author_name' => 'Import Test 2',
            'tenant_id' => 'test-tenant',
        ]);
    });

    it('validates testimonial creation data', function () {
        $response = $this->postJson('/api/testimonials', [
            'author_name' => '', // Required field missing
            'content' => 'Short', // Too short
            'audience_type' => 'invalid', // Invalid value
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'author_name',
                    'content',
                    'audience_type',
                ]);
    });

    it('validates video testimonial requirements', function () {
        $response = $this->postJson('/api/testimonials', [
            'author_name' => 'Video Test',
            'content' => 'This is a video testimonial.',
            'audience_type' => 'individual',
            'video_url' => 'https://example.com/video.mp4',
            // Missing video_thumbnail
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['video_thumbnail']);
    });
});