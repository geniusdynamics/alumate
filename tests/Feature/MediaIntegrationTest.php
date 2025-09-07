<?php

use App\Models\User;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessMediaJob;
use App\Jobs\GenerateImageVariantsJob;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
    Queue::fake();
});

describe('Media Integration Workflows', function () {
    it('completes full image upload and processing workflow', function () {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        // Step 1: Upload image
        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'alt_text' => 'Test image',
                'generate_variants' => true
            ]);

        $uploadResponse->assertSuccessful();
        $mediaId = $uploadResponse->json('data.id');

        // Step 2: Verify media record created
        $media = Media::find($mediaId);
        expect($media)->not->toBeNull();
        expect($media->type)->toBe('image');
        expect($media->alt_text)->toBe('Test image');

        // Step 3: Verify file stored
        Storage::disk('public')->assertExists($media->file_path);

        // Step 4: Verify background jobs dispatched
        Queue::assertPushed(ProcessMediaJob::class);
        Queue::assertPushed(GenerateImageVariantsJob::class);

        // Step 5: Retrieve media in gallery
        $galleryResponse = $this->actingAs($this->user)
            ->getJson('/api/media/gallery');

        $galleryResponse->assertSuccessful()
            ->assertJsonCount(1, 'data');

        // Step 6: Delete media
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/media/{$mediaId}");

        $deleteResponse->assertSuccessful();
        Storage::disk('public')->assertMissing($media->file_path);
    });

    it('handles video upload with thumbnail generation', function () {
        $file = UploadedFile::fake()->create('test.mp4', 2048, 'video/mp4');

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'video',
                'title' => 'Test video',
                'generate_thumbnail' => true
            ]);

        $response->assertSuccessful();
        $mediaId = $response->json('data.id');

        $media = Media::find($mediaId);
        expect($media->type)->toBe('video');
        expect($media->title)->toBe('Test video');

        // Verify thumbnail generation job was dispatched
        Queue::assertPushed(function (ProcessMediaJob $job) use ($mediaId) {
            return $job->mediaId === $mediaId && $job->shouldGenerateThumbnail;
        });
    });

    it('creates and manages media galleries', function () {
        // Upload multiple images
        $images = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("image{$i}.jpg");
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/media/upload', [
                    'file' => $file,
                    'type' => 'image',
                    'alt_text' => "Image {$i}"
                ]);

            $images[] = $response->json('data.id');
        }

        // Create gallery
        $galleryResponse = $this->actingAs($this->user)
            ->postJson('/api/media/galleries', [
                'title' => 'Test Gallery',
                'description' => 'A test gallery',
                'media_ids' => $images
            ]);

        $galleryResponse->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'media_count',
                    'media_items'
                ]
            ]);

        expect($galleryResponse->json('data.media_count'))->toBe(3);

        // Retrieve gallery
        $galleryId = $galleryResponse->json('data.id');
        $getResponse = $this->actingAs($this->user)
            ->getJson("/api/media/galleries/{$galleryId}");

        $getResponse->assertSuccessful()
            ->assertJsonCount(3, 'data.media_items');
    });

    it('handles media search across different types', function () {
        // Create mixed media
        $imageFile = UploadedFile::fake()->image('sunset.jpg');
        $videoFile = UploadedFile::fake()->create('tutorial.mp4', 1024, 'video/mp4');

        $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $imageFile,
                'type' => 'image',
                'alt_text' => 'Beautiful sunset over mountains'
            ]);

        $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $videoFile,
                'type' => 'video',
                'title' => 'Vue.js tutorial for beginners'
            ]);

        // Search for sunset
        $searchResponse = $this->actingAs($this->user)
            ->getJson('/api/media/search?q=sunset');

        $searchResponse->assertSuccessful()
            ->assertJsonCount(1, 'data');

        expect($searchResponse->json('data.0.alt_text'))->toContain('sunset');

        // Search for tutorial
        $tutorialResponse = $this->actingAs($this->user)
            ->getJson('/api/media/search?q=tutorial');

        $tutorialResponse->assertSuccessful()
            ->assertJsonCount(1, 'data');

        expect($tutorialResponse->json('data.0.title'))->toContain('tutorial');
    });

    it('enforces user permissions and isolation', function () {
        $otherUser = User::factory()->create();
        
        // User 1 uploads media
        $file = UploadedFile::fake()->image('private.jpg');
        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'alt_text' => 'Private image'
            ]);

        $mediaId = $response->json('data.id');

        // User 2 cannot access User 1's media
        $unauthorizedResponse = $this->actingAs($otherUser)
            ->getJson("/api/media/{$mediaId}");

        $unauthorizedResponse->assertForbidden();

        // User 2 cannot delete User 1's media
        $deleteResponse = $this->actingAs($otherUser)
            ->deleteJson("/api/media/{$mediaId}");

        $deleteResponse->assertForbidden();

        // User 2's gallery doesn't show User 1's media
        $galleryResponse = $this->actingAs($otherUser)
            ->getJson('/api/media/gallery');

        $galleryResponse->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('handles batch upload with mixed success and failures', function () {
        $validImage = UploadedFile::fake()->image('valid.jpg');
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');
        $oversizedImage = UploadedFile::fake()->image('huge.jpg')->size(15360); // 15MB

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/batch-upload', [
                'files' => [$validImage, $invalidFile, $oversizedImage]
            ]);

        $response->assertStatus(207) // Multi-status
            ->assertJsonStructure([
                'results' => [
                    '*' => [
                        'success',
                        'data',
                        'errors'
                    ]
                ],
                'summary' => [
                    'total',
                    'successful',
                    'failed'
                ]
            ]);

        $results = $response->json('results');
        expect($results[0]['success'])->toBeTrue(); // Valid image
        expect($results[1]['success'])->toBeFalse(); // Invalid file
        expect($results[2]['success'])->toBeFalse(); // Oversized

        expect($response->json('summary.successful'))->toBe(1);
        expect($response->json('summary.failed'))->toBe(2);
    });

    it('processes media optimization pipeline', function () {
        $file = UploadedFile::fake()->image('large.jpg', 2400, 1600);

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'optimize' => true,
                'generate_webp' => true,
                'quality' => 85
            ]);

        $response->assertSuccessful();
        $mediaId = $response->json('data.id');

        // Verify optimization job was queued
        Queue::assertPushed(function (ProcessMediaJob $job) use ($mediaId) {
            return $job->mediaId === $mediaId && 
                   $job->shouldOptimize && 
                   $job->shouldGenerateWebp;
        });

        // Simulate job processing
        $media = Media::find($mediaId);
        expect($media->optimization_status)->toBe('pending');

        // After processing (simulated)
        $media->update([
            'optimization_status' => 'completed',
            'optimized_size' => $media->file_size * 0.7, // 30% reduction
            'webp_path' => str_replace('.jpg', '.webp', $media->file_path)
        ]);

        $optimizedResponse = $this->actingAs($this->user)
            ->getJson("/api/media/{$mediaId}");

        $optimizedResponse->assertSuccessful();
        expect($optimizedResponse->json('data.optimization_status'))->toBe('completed');
        expect($optimizedResponse->json('data.webp_url'))->toContain('.webp');
    });

    it('handles media analytics and usage tracking', function () {
        $file = UploadedFile::fake()->image('tracked.jpg');

        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'track_usage' => true
            ]);

        $mediaId = $uploadResponse->json('data.id');

        // Simulate media views
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)
                ->postJson("/api/media/{$mediaId}/view");
        }

        // Check analytics
        $analyticsResponse = $this->actingAs($this->user)
            ->getJson("/api/media/{$mediaId}/analytics");

        $analyticsResponse->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'views',
                    'downloads',
                    'last_accessed',
                    'popular_times'
                ]
            ]);

        expect($analyticsResponse->json('data.views'))->toBe(5);
    });
});