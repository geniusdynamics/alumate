<?php

use App\Models\Circle;
use App\Models\Connection;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('Social Graph Access Control', function () {
    it('prevents unauthorized access to private posts', function () {
        $privatePost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'private',
            'content' => 'This is a private post',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/posts/{$privatePost->id}");

        $response->assertNotFound();
    });

    it('enforces circle-based post visibility', function () {
        $circle = Circle::factory()->create();
        $circle->members()->attach($this->otherUser->id);

        $circlePost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'circles',
            'circle_ids' => [$circle->id],
        ]);

        Sanctum::actingAs($this->user);

        // User not in circle shouldn't see the post
        $response = $this->getJson("/api/posts/{$circlePost->id}");
        $response->assertNotFound();

        // Add user to circle
        $circle->members()->attach($this->user->id);

        // Now user should see the post
        $response = $this->getJson("/api/posts/{$circlePost->id}");
        $response->assertSuccessful();
    });

    it('prevents unauthorized profile data access', function () {
        $this->otherUser->update([
            'privacy_settings' => [
                'profile_visibility' => 'connections_only',
                'email_visibility' => 'private',
                'phone_visibility' => 'private',
            ],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/users/{$this->otherUser->id}");

        // Should get limited profile data
        $response->assertSuccessful()
            ->assertJsonMissing(['email', 'phone'])
            ->assertJsonStructure(['id', 'name', 'avatar_url']);
    });

    it('enforces connection-based access controls', function () {
        $this->otherUser->update([
            'privacy_settings' => ['profile_visibility' => 'connections_only'],
        ]);

        Sanctum::actingAs($this->user);

        // Without connection
        $response = $this->getJson("/api/users/{$this->otherUser->id}");
        $response->assertSuccessful()
            ->assertJsonMissing(['bio', 'location', 'website']);

        // Create connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->otherUser->id,
            'status' => 'accepted',
            'connected_at' => now(),
        ]);

        // With connection
        $response = $this->getJson("/api/users/{$this->otherUser->id}");
        $response->assertSuccessful()
            ->assertJsonStructure(['bio', 'location', 'website']);
    });

    it('prevents mass data harvesting through pagination abuse', function () {
        Sanctum::actingAs($this->user);

        // Try to request excessive page size
        $response = $this->getJson('/api/users?per_page=10000');

        $response->assertSuccessful();
        $data = $response->json();

        // Should be limited to reasonable page size
        expect(count($data['data']))->toBeLessThanOrEqual(100);
    });

    it('prevents social graph enumeration attacks', function () {
        Sanctum::actingAs($this->user);

        // Try to enumerate user IDs
        $responses = [];
        for ($i = 1; $i <= 100; $i++) {
            $response = $this->getJson("/api/users/{$i}");
            $responses[] = $response->status();
        }

        // Should not reveal which user IDs exist through different response codes
        $uniqueStatuses = array_unique($responses);
        expect(count($uniqueStatuses))->toBeLessThanOrEqual(2); // Only 200 and 404
    });

    it('prevents unauthorized group member enumeration', function () {
        $privateGroup = Group::factory()->create([
            'privacy' => 'private',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/groups/{$privateGroup->id}/members");

        $response->assertForbidden();
    });

    it('enforces rate limiting on social interactions', function () {
        $posts = Post::factory()->count(20)->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'public',
        ]);

        Sanctum::actingAs($this->user);

        // Rapid-fire likes should be rate limited
        $successCount = 0;
        foreach ($posts as $post) {
            $response = $this->postJson("/api/posts/{$post->id}/like");
            if ($response->status() === 200) {
                $successCount++;
            }
        }

        // Should be limited to prevent spam
        expect($successCount)->toBeLessThan(20);
    });

    it('prevents unauthorized message access', function () {
        // Create a private conversation between other users
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        // This would be a message in their private conversation
        $messageData = [
            'sender_id' => $participant1->id,
            'recipient_id' => $participant2->id,
            'content' => 'Private message',
        ];

        Sanctum::actingAs($this->user);

        // Try to access conversation user is not part of
        $response = $this->getJson("/api/conversations/{$participant1->id}-{$participant2->id}/messages");

        $response->assertForbidden();
    });

    it('validates post editing permissions', function () {
        $otherUserPost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Original content',
        ]);

        Sanctum::actingAs($this->user);

        // Try to edit another user's post
        $response = $this->putJson("/api/posts/{$otherUserPost->id}", [
            'content' => 'Modified content',
        ]);

        $response->assertForbidden();
    });

    it('prevents unauthorized connection manipulation', function () {
        $connection = Connection::create([
            'user_id' => $this->otherUser->id,
            'connected_user_id' => User::factory()->create()->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->user);

        // Try to accept someone else's connection request
        $response = $this->putJson("/api/connections/{$connection->id}", [
            'status' => 'accepted',
        ]);

        $response->assertForbidden();
    });
});

describe('Data Leakage Prevention', function () {
    it('sanitizes user data in API responses', function () {
        $this->user->update([
            'password' => bcrypt('secret'),
            'remember_token' => 'secret-token',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user');

        $response->assertSuccessful()
            ->assertJsonMissing(['password', 'remember_token'])
            ->assertJsonStructure(['id', 'name', 'email']);
    });

    it('prevents sensitive data exposure in error messages', function () {
        Sanctum::actingAs($this->user);

        // Try to access non-existent resource with sensitive ID
        $response = $this->getJson('/api/posts/999999');

        $response->assertNotFound();

        // Error message shouldn't reveal internal details
        $errorMessage = $response->json('message');
        expect($errorMessage)->not->toContain('SQL');
        expect($errorMessage)->not->toContain('database');
        expect($errorMessage)->not->toContain('table');
    });

    it('prevents information disclosure through timing attacks', function () {
        $existingEmail = $this->user->email;
        $nonExistentEmail = 'nonexistent@example.com';

        // Measure response times for existing vs non-existent emails
        $start1 = microtime(true);
        $this->postJson('/api/password/email', ['email' => $existingEmail]);
        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $this->postJson('/api/password/email', ['email' => $nonExistentEmail]);
        $time2 = microtime(true) - $start2;

        // Response times should be similar to prevent user enumeration
        $timeDifference = abs($time1 - $time2);
        expect($timeDifference)->toBeLessThan(0.1); // 100ms tolerance
    });

    it('prevents cross-tenant data leakage in search results', function () {
        $otherUser = User::factory()->create([
            'name' => 'John Doe from Other Tenant',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/search/users?q=John');

        $response->assertSuccessful();

        // Should not return users from other tenants (simulated)
        $userIds = collect($response->json('data'))->pluck('id');
        expect($userIds)->not->toContain($otherUser->id);
    });

    it('validates file upload security', function () {
        Sanctum::actingAs($this->user);

        // Try to upload potentially malicious file
        $maliciousFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 100);

        $response = $this->postJson('/api/user/avatar', [
            'avatar' => $maliciousFile,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    });
});
