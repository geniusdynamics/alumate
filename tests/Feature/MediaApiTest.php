<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

describe('Media API Endpoints', function () {
    it('can upload an image', function () {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'alt_text' => 'Test image'
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url',
                    'type',
                    'alt_text',
                    'file_size',
                    'dimensions'
                ]
            ]);

        Storage::disk('public')->assertExists('media/' . $file->hashName());
    });

    it('can upload a video', function () {
        $file = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'video',
                'title' => 'Test video'
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url',
                    'type',
                    'title',
                    'file_size',
                    'duration'
                ]
            ]);
    });

    it('validates file types', function () {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image'
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('validates file size limits', function () {
        $file = UploadedFile::fake()->image('large.jpg')->size(10240); // 10MB

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image'
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('can retrieve media gallery', function () {
        // Create some test media files
        $images = collect(range(1, 5))->map(fn($i) => 
            UploadedFile::fake()->image("image{$i}.jpg")
        );

        foreach ($images as $image) {
            $this->actingAs($this->user)
                ->postJson('/api/media/upload', [
                    'file' => $image,
                    'type' => 'image',
                    'alt_text' => 'Test image'
                ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/media/gallery?type=image');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'url',
                        'type',
                        'alt_text',
                        'created_at'
                    ]
                ]
            ]);
    });

    it('can filter media by type', function () {
        $image = UploadedFile::fake()->image('test.jpg');
        $video = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $image,
                'type' => 'image'
            ]);

        $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $video,
                'type' => 'video'
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/media/gallery?type=video');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');

        expect($response->json('data.0.type'))->toBe('video');
    });

    it('can delete media', function () {
        $file = UploadedFile::fake()->image('test.jpg');

        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image'
            ]);

        $mediaId = $uploadResponse->json('data.id');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/media/{$mediaId}");

        $response->assertSuccessful();

        // Verify file is deleted from storage
        Storage::disk('public')->assertMissing('media/' . $file->hashName());
    });

    it('requires authentication for upload', function () {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload', [
            'file' => $file,
            'type' => 'image'
        ]);

        $response->assertUnauthorized();
    });

    it('can generate responsive image variants', function () {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'image',
                'generate_variants' => true
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'variants' => [
                        'thumbnail',
                        'medium',
                        'large'
                    ]
                ]
            ]);
    });
});