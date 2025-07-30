<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Circle;
use App\Models\Group;
use App\Jobs\PublishScheduledPostJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    public function test_user_can_create_basic_text_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'This is my first post!',
                'post_type' => 'text',
                'visibility' => 'public'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'content',
                    'post_type',
                    'visibility',
                    'user' => ['id', 'name'],
                    'created_at'
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is my first post!',
            'post_type' => 'text',
            'visibility' => 'public'
        ]);
    }

    public function test_user_can_create_post_with_media()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Check out this photo!',
                'post_type' => 'media',
                'visibility' => 'public',
                'media' => [$file]
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'content',
                    'post_type',
                    'visibility',
                    'media_urls',
                    'user' => ['id', 'name'],
                    'created_at'
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Check out this photo!',
            'post_type' => 'media',
            'visibility' => 'public'
        ]);

        // Check if file was stored (using a more reliable method)
        $this->assertTrue(Storage::disk('public')->exists('media/posts/' . $user->id . '/' . date('Y/m') . '/' . $file->hashName()));
    }

    public function test_user_can_create_post_for_specific_circles()
    {
        $user = User::factory()->create();
        $circle = Circle::factory()->create();
        $user->circles()->attach($circle);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Circle-specific post',
                'post_type' => 'text',
                'visibility' => 'circles',
                'circle_ids' => [$circle->id]
            ]);

        $response->assertStatus(201);

        $post = Post::latest()->first();
        $this->assertContains($circle->id, $post->circle_ids);
    }

    public function test_user_can_create_post_for_specific_groups()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Group-specific post',
                'post_type' => 'text',
                'visibility' => 'groups',
                'group_ids' => [$group->id]
            ]);

        $response->assertStatus(201);

        $post = Post::latest()->first();
        $this->assertContains($group->id, $post->group_ids);
    }

    public function test_user_can_schedule_post()
    {
        $user = User::factory()->create();
        $scheduledTime = Carbon::now()->addHours(2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Scheduled post',
                'post_type' => 'text',
                'visibility' => 'public',
                'scheduled_at' => $scheduledTime->toISOString()
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Scheduled post',
            'scheduled_at' => $scheduledTime
        ]);

        Queue::assertPushed(PublishScheduledPostJob::class);
    }

    public function test_user_can_save_post_as_draft()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts/drafts', [
                'content' => 'Draft post',
                'post_type' => 'text',
                'visibility' => 'public'
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('post_drafts', [
            'user_id' => $user->id,
            'content' => 'Draft post'
        ]);
    }

    public function test_user_cannot_create_post_without_content()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'post_type' => 'text',
                'visibility' => 'public'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_cannot_create_post_with_invalid_visibility()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Test post',
                'post_type' => 'text',
                'visibility' => 'invalid'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    public function test_user_cannot_post_to_circles_they_dont_belong_to()
    {
        $user = User::factory()->create();
        $circle = Circle::factory()->create();
        // User is not a member of this circle

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Unauthorized circle post',
                'post_type' => 'text',
                'visibility' => 'circles',
                'circle_ids' => [$circle->id]
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_post_to_groups_they_dont_belong_to()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        // User is not a member of this group

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Unauthorized group post',
                'post_type' => 'text',
                'visibility' => 'groups',
                'group_ids' => [$group->id]
            ]);

        $response->assertStatus(403);
    }

    public function test_media_upload_validates_file_types()
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with invalid media',
                'post_type' => 'media',
                'visibility' => 'public',
                'media' => [$invalidFile]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['media.0']);
    }

    public function test_media_upload_validates_file_size()
    {
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(10000); // 10MB

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with large media',
                'post_type' => 'media',
                'visibility' => 'public',
                'media' => [$largeFile]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['media.0']);
    }
}
