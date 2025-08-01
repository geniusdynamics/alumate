<?php

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can create a success story', function () {
    $storyData = [
        'title' => 'My Amazing Journey',
        'summary' => 'A brief summary of my success',
        'content' => 'This is the full story of my success...',
        'achievement_type' => 'promotion',
        'industry' => 'Technology',
        'current_role' => 'Senior Developer',
        'current_company' => 'Tech Corp',
        'tags' => ['leadership', 'innovation'],
        'allow_social_sharing' => true,
        'status' => 'published'
    ];

    $response = $this->postJson('/api/success-stories', $storyData);

    $response->assertStatus(201)
             ->assertJson([
                 'success' => true,
                 'message' => 'Success story created successfully'
             ]);

    $this->assertDatabaseHas('success_stories', [
        'title' => 'My Amazing Journey',
        'user_id' => $this->user->id,
        'status' => 'published'
    ]);
});

test('can upload featured image with success story', function () {
    $image = UploadedFile::fake()->image('featured.jpg');
    
    $storyData = [
        'title' => 'Story with Image',
        'summary' => 'A story with a featured image',
        'content' => 'Content here...',
        'achievement_type' => 'award',
        'featured_image_file' => $image,
        'status' => 'published'
    ];

    $response = $this->postJson('/api/success-stories', $storyData);

    $response->assertStatus(201);
    
    $story = SuccessStory::where('title', 'Story with Image')->first();
    expect($story->featured_image)->not->toBeNull();
    
    Storage::disk('public')->assertExists($story->featured_image);
});

test('can retrieve published success stories', function () {
    SuccessStory::factory()->count(5)->published()->create();
    SuccessStory::factory()->count(3)->draft()->create();

    $response = $this->getJson('/api/success-stories');

    $response->assertStatus(200)
             ->assertJson(['success' => true]);
    
    $stories = $response->json('data.data');
    expect(count($stories))->toBe(5); // Only published stories
});

test('can retrieve featured success stories', function () {
    SuccessStory::factory()->count(3)->featured()->create();
    SuccessStory::factory()->count(5)->published()->create();

    $response = $this->getJson('/api/success-stories/featured');

    $response->assertStatus(200)
             ->assertJson(['success' => true]);
    
    $stories = $response->json('data');
    expect(count($stories))->toBe(3);
    
    foreach ($stories as $story) {
        expect($story['is_featured'])->toBeTrue();
    }
});

test('can filter stories by industry', function () {
    SuccessStory::factory()->published()->create(['industry' => 'Technology']);
    SuccessStory::factory()->published()->create(['industry' => 'Healthcare']);
    SuccessStory::factory()->published()->create(['industry' => 'Technology']);

    $response = $this->getJson('/api/success-stories?industry=Technology');

    $response->assertStatus(200);
    
    $stories = $response->json('data.data');
    expect(count($stories))->toBe(2);
    
    foreach ($stories as $story) {
        expect($story['industry'])->toBe('Technology');
    }
});

test('can filter stories by achievement type', function () {
    SuccessStory::factory()->published()->create(['achievement_type' => 'promotion']);
    SuccessStory::factory()->published()->create(['achievement_type' => 'award']);
    SuccessStory::factory()->published()->create(['achievement_type' => 'promotion']);

    $response = $this->getJson('/api/success-stories?achievement_type=promotion');

    $response->assertStatus(200);
    
    $stories = $response->json('data.data');
    expect(count($stories))->toBe(2);
    
    foreach ($stories as $story) {
        expect($story['achievement_type'])->toBe('promotion');
    }
});

test('can search stories by title and content', function () {
    SuccessStory::factory()->published()->create([
        'title' => 'Amazing Innovation Story',
        'content' => 'This is about innovation in tech'
    ]);
    SuccessStory::factory()->published()->create([
        'title' => 'Leadership Journey',
        'content' => 'This is about leadership development'
    ]);

    $response = $this->getJson('/api/success-stories?search=innovation');

    $response->assertStatus(200);
    
    $stories = $response->json('data.data');
    expect(count($stories))->toBe(1);
    expect($stories[0]['title'])->toBe('Amazing Innovation Story');
});

test('can view individual success story and increment view count', function () {
    $story = SuccessStory::factory()->published()->create(['view_count' => 5]);

    $response = $this->getJson("/api/success-stories/{$story->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'id' => $story->id,
                     'title' => $story->title
                 ]
             ]);

    $story->refresh();
    expect($story->view_count)->toBe(6);
});

test('can like a success story', function () {
    $story = SuccessStory::factory()->published()->create(['like_count' => 10]);

    $response = $this->postJson("/api/success-stories/{$story->id}/like");

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Story liked successfully'
             ]);

    $story->refresh();
    expect($story->like_count)->toBe(11);
});

test('can share a success story', function () {
    $story = SuccessStory::factory()->published()->create([
        'share_count' => 5,
        'allow_social_sharing' => true
    ]);

    $response = $this->postJson("/api/success-stories/{$story->id}/share");

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Share count updated'
             ]);

    $story->refresh();
    expect($story->share_count)->toBe(6);
    
    $shareData = $response->json('data.share_data');
    expect($shareData)->toHaveKeys(['title', 'description', 'url']);
});

test('cannot share story that disallows social sharing', function () {
    $story = SuccessStory::factory()->published()->create([
        'allow_social_sharing' => false
    ]);

    $response = $this->postJson("/api/success-stories/{$story->id}/share");

    $response->assertStatus(403)
             ->assertJson([
                 'success' => false,
                 'message' => 'This story does not allow social sharing'
             ]);
});

test('can update own success story', function () {
    $story = SuccessStory::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'title' => 'Updated Title',
        'summary' => 'Updated summary'
    ];

    $response = $this->putJson("/api/success-stories/{$story->id}", $updateData);

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Success story updated successfully'
             ]);

    $story->refresh();
    expect($story->title)->toBe('Updated Title');
    expect($story->summary)->toBe('Updated summary');
});

test('cannot update other users success story', function () {
    $otherUser = User::factory()->create();
    $story = SuccessStory::factory()->create(['user_id' => $otherUser->id]);

    $updateData = ['title' => 'Hacked Title'];

    $response = $this->putJson("/api/success-stories/{$story->id}", $updateData);

    $response->assertStatus(403)
             ->assertJson([
                 'success' => false,
                 'message' => 'Unauthorized to update this story'
             ]);
});

test('can delete own success story', function () {
    $story = SuccessStory::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/success-stories/{$story->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Success story deleted successfully'
             ]);

    $this->assertSoftDeleted('success_stories', ['id' => $story->id]);
});

test('can get stories by demographics for diversity showcase', function () {
    SuccessStory::factory()->published()->create([
        'demographics' => ['gender' => 'female', 'ethnicity' => 'asian']
    ]);
    SuccessStory::factory()->published()->create([
        'demographics' => ['gender' => 'male', 'ethnicity' => 'black']
    ]);
    SuccessStory::factory()->published()->create([
        'demographics' => ['gender' => 'female', 'ethnicity' => 'hispanic']
    ]);

    $response = $this->postJson('/api/success-stories/demographics', [
        'demographics' => ['gender' => 'female']
    ]);

    $response->assertStatus(200);
    
    $stories = $response->json('data');
    expect(count($stories))->toBe(2);
    
    foreach ($stories as $story) {
        expect($story['demographics']['gender'])->toBe('female');
    }
});
