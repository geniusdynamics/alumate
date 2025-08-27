<?php

namespace Tests\Integration;

use App\Events\PostCreated;
use App\Events\PostEngagement;
use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SocialPlatformIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Circle $circle;

    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->circle = Circle::factory()->create();
        $this->group = Group::factory()->create();

        // Set up relationships
        $this->user->circles()->attach($this->circle->id);
        $this->otherUser->circles()->attach($this->circle->id);
        $this->user->groups()->attach($this->group->id);
        $this->otherUser->groups()->attach($this->group->id);
    }

    public function test_complete_post_lifecycle_integration()
    {
        Event::fake();

        // Step 1: Create post
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => 'Integration test post',
                'post_type' => 'text',
                'visibility' => 'circles',
                'circle_ids' => [$this->circle->id],
            ]);

        $response->assertStatus(201);
        $postId = $response->json('data.post.id');

        Event::assertDispatched(PostCreated::class);

        // Step 2: Other user engages with post
        $this->actingAs($this->otherUser)
            ->postJson("/api/posts/{$postId}/engage", [
                'type' => 'like',
            ]);

        Event::assertDispatched(PostEngagement::class);

        // Step 3: Verify post appears in timeline
        $timelineResponse = $this->actingAs($this->otherUser)
            ->getJson('/api/timeline');

        $timelineResponse->assertStatus(200);
        $posts = $timelineResponse->json('data.posts.data');
        $this->assertCount(1, $posts);
        $this->assertEquals($postId, $posts[0]['id']);

        // Step 4: Update post
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/posts/{$postId}", [
                'content' => 'Updated integration test post',
            ]);

        $updateResponse->assertStatus(200);

        // Step 5: Delete post
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$postId}");

        $deleteResponse->assertStatus(200);

        // Step 6: Verify post no longer appears in timeline
        $finalTimelineResponse = $this->actingAs($this->otherUser)
            ->getJson('/api/timeline');

        $finalTimelineResponse->assertStatus(200);
        $finalPosts = $finalTimelineResponse->json('data.posts.data');
        $this->assertCount(0, $finalPosts);
    }

    public function test_alumni_directory_and_connection_integration()
    {
        // Create additional users
        $alumni = User::factory()->count(5)->create();

        // Add them to the same circle
        foreach ($alumni as $user) {
            $user->circles()->attach($this->circle->id);
        }

        // Step 1: Browse alumni directory
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni');

        $response->assertStatus(200);
        $alumniData = $response->json('data');
        $this->assertGreaterThan(5, count($alumniData));

        // Step 2: Send connection request
        $targetAlumnus = $alumni->first();
        $connectionResponse = $this->actingAs($this->user)
            ->postJson("/api/users/{$targetAlumnus->id}/connect", [
                'message' => 'Would love to connect!',
            ]);

        $connectionResponse->assertStatus(201);

        // Step 3: Accept connection
        $connection = $this->user->sentConnections()->first();
        $acceptResponse = $this->actingAs($targetAlumnus)
            ->putJson("/api/connections/{$connection->id}", [
                'status' => 'accepted',
            ]);

        $acceptResponse->assertStatus(200);

        // Step 4: Verify connection appears in network
        $networkResponse = $this->actingAs($this->user)
            ->getJson('/api/connections');

        $networkResponse->assertStatus(200);
        $connections = $networkResponse->json('data');
        $this->assertCount(1, $connections);
    }

    public function test_job_matching_and_application_integration()
    {
        // Create company and job
        $company = \App\Models\Company::factory()->create();
        $job = \App\Models\JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create job match score
        \App\Models\JobMatchScore::factory()->create([
            'user_id' => $this->user->id,
            'job_id' => $job->id,
            'score' => 85.0,
        ]);

        // Step 1: Get job recommendations
        $recommendationsResponse = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations');

        $recommendationsResponse->assertStatus(200);
        $jobs = $recommendationsResponse->json('data.data');
        $this->assertCount(1, $jobs);
        $this->assertEquals($job->id, $jobs[0]['id']);

        // Step 2: View job details
        $jobDetailsResponse = $this->actingAs($this->user)
            ->getJson("/api/jobs/{$job->id}");

        $jobDetailsResponse->assertStatus(200);

        // Step 3: Apply for job
        $applicationResponse = $this->actingAs($this->user)
            ->postJson("/api/jobs/{$job->id}/apply", [
                'cover_letter' => 'I am very interested in this position.',
            ]);

        $applicationResponse->assertStatus(200);

        // Step 4: Check application status
        $applicationsResponse = $this->actingAs($this->user)
            ->getJson('/api/applications');

        $applicationsResponse->assertStatus(200);
        $applications = $applicationsResponse->json('data.data');
        $this->assertCount(1, $applications);
    }

    public function test_event_registration_and_participation_integration()
    {
        // Create event
        $event = \App\Models\Event::factory()->create([
            'title' => 'Alumni Networking Event',
            'event_date' => now()->addWeeks(2),
            'is_virtual' => false,
        ]);

        // Step 1: Browse events
        $eventsResponse = $this->actingAs($this->user)
            ->getJson('/api/events');

        $eventsResponse->assertStatus(200);
        $events = $eventsResponse->json('data.data');
        $this->assertCount(1, $events);

        // Step 2: Register for event
        $registrationResponse = $this->actingAs($this->user)
            ->postJson("/api/events/{$event->id}/register", [
                'dietary_requirements' => 'None',
            ]);

        $registrationResponse->assertStatus(201);

        // Step 3: Check registration status
        $myEventsResponse = $this->actingAs($this->user)
            ->getJson('/api/events/my-events');

        $myEventsResponse->assertStatus(200);
        $myEvents = $myEventsResponse->json('data');
        $this->assertCount(1, $myEvents);

        // Step 4: Cancel registration
        $cancelResponse = $this->actingAs($this->user)
            ->deleteJson("/api/events/{$event->id}/register");

        $cancelResponse->assertStatus(200);
    }

    public function test_mentorship_workflow_integration()
    {
        // Set up mentor profile
        $mentor = User::factory()->create();
        $mentor->update([
            'is_mentor' => true,
            'mentorship_areas' => ['Career Development', 'Technical Skills'],
        ]);

        // Step 1: Browse mentors
        $mentorsResponse = $this->actingAs($this->user)
            ->getJson('/api/mentors');

        $mentorsResponse->assertStatus(200);
        $mentors = $mentorsResponse->json('data');
        $this->assertCount(1, $mentors);

        // Step 2: Request mentorship
        $requestResponse = $this->actingAs($this->user)
            ->postJson('/api/mentorship/requests', [
                'mentor_id' => $mentor->id,
                'message' => 'I would like guidance on career development.',
                'areas' => ['Career Development'],
            ]);

        $requestResponse->assertStatus(201);

        // Step 3: Accept mentorship request (as mentor)
        $request = \App\Models\MentorshipRequest::first();
        $acceptResponse = $this->actingAs($mentor)
            ->putJson("/api/mentorship/requests/{$request->id}", [
                'status' => 'accepted',
            ]);

        $acceptResponse->assertStatus(200);

        // Step 4: Schedule session
        $sessionResponse = $this->actingAs($this->user)
            ->postJson('/api/mentorship/sessions', [
                'mentor_id' => $mentor->id,
                'topic' => 'Career Planning',
                'preferred_date' => now()->addWeek()->format('Y-m-d'),
                'duration' => 60,
            ]);

        $sessionResponse->assertStatus(201);
    }

    public function test_search_and_discovery_integration()
    {
        // Create diverse alumni data
        $alumni = User::factory()->count(10)->create();
        foreach ($alumni as $user) {
            $user->update([
                'skills' => ['PHP', 'Laravel', 'Vue.js'],
                'location' => 'San Francisco, CA',
            ]);
        }

        // Step 1: Perform search
        $searchResponse = $this->actingAs($this->user)
            ->getJson('/api/alumni?skills[]=Laravel&location=San Francisco');

        $searchResponse->assertStatus(200);
        $results = $searchResponse->json('data');
        $this->assertCount(10, $results);

        // Step 2: Save search
        $saveSearchResponse = $this->actingAs($this->user)
            ->postJson('/api/search/save', [
                'name' => 'Laravel Developers in SF',
                'type' => 'alumni',
                'criteria' => [
                    'skills' => ['Laravel'],
                    'location' => 'San Francisco',
                ],
            ]);

        $saveSearchResponse->assertStatus(200);

        // Step 3: Execute saved search
        $savedSearch = $this->user->savedSearches()->first();
        $executeResponse = $this->actingAs($this->user)
            ->postJson("/api/search/execute/{$savedSearch->id}");

        $executeResponse->assertStatus(200);
    }

    public function test_notification_system_integration()
    {
        // Step 1: Create post that should trigger notifications
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Hello @'.$this->user->username.'!',
            'visibility' => 'public',
        ]);

        // Step 2: Engage with post (should create notification)
        $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        // Step 3: Check notifications
        $notificationsResponse = $this->actingAs($this->otherUser)
            ->getJson('/api/notifications');

        $notificationsResponse->assertStatus(200);
        $notifications = $notificationsResponse->json('data');
        $this->assertNotEmpty($notifications);

        // Step 4: Mark notification as read
        $notification = collect($notifications)->first();
        $markReadResponse = $this->actingAs($this->otherUser)
            ->putJson("/api/notifications/{$notification['id']}/read");

        $markReadResponse->assertStatus(200);
    }

    public function test_analytics_and_insights_integration()
    {
        // Create activity data
        Post::factory()->count(5)->create(['user_id' => $this->user->id]);

        // Create connections
        $connections = User::factory()->count(3)->create();
        foreach ($connections as $connection) {
            $this->user->connections()->attach($connection->id, [
                'status' => 'accepted',
                'connected_at' => now(),
            ]);
        }

        // Step 1: Get user analytics
        $analyticsResponse = $this->actingAs($this->user)
            ->getJson('/api/analytics/user');

        $analyticsResponse->assertStatus(200);
        $analytics = $analyticsResponse->json('data');

        $this->assertArrayHasKey('posts_count', $analytics);
        $this->assertArrayHasKey('connections_count', $analytics);
        $this->assertArrayHasKey('engagement_rate', $analytics);

        // Step 2: Get platform insights
        $insightsResponse = $this->actingAs($this->user)
            ->getJson('/api/analytics/insights');

        $insightsResponse->assertStatus(200);
        $insights = $insightsResponse->json('data');

        $this->assertArrayHasKey('trending_topics', $insights);
        $this->assertArrayHasKey('active_users', $insights);
    }
}
