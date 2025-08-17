<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Institution;
use App\Models\ReunionMemory;
use App\Models\ReunionPhoto;
use App\Models\User;
use App\Services\ReunionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReunionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Institution $institution;

    private ReunionService $reunionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->user = User::factory()->create([
            'institution_id' => $this->institution->id,
            'graduation_year' => 2020,
        ]);

        $this->reunionService = app(ReunionService::class);

        // Fake storage for file uploads
        Storage::fake('public');
    }

    public function test_can_create_reunion_event()
    {
        $reunionData = [
            'title' => 'Class of 2020 - 5 Year Reunion',
            'description' => 'Join us for our 5-year reunion celebration!',
            'graduation_year' => 2020,
            'class_identifier' => 'Class of 2020',
            'reunion_theme' => 'Memories and Milestones',
            'start_date' => now()->addMonths(6),
            'end_date' => now()->addMonths(6)->addHours(4),
            'venue_name' => 'Alumni Hall',
            'venue_address' => '123 University Ave',
            'institution_id' => $this->institution->id,
            'enable_photo_sharing' => true,
            'enable_memory_wall' => true,
        ];

        $reunion = $this->reunionService->createReunionEvent($reunionData, $this->user);

        $this->assertInstanceOf(Event::class, $reunion);
        $this->assertTrue($reunion->is_reunion);
        $this->assertEquals('reunion', $reunion->type);
        $this->assertEquals(5, $reunion->reunion_year_milestone); // 2025 - 2020 = 5
        $this->assertTrue($reunion->enable_photo_sharing);
        $this->assertTrue($reunion->enable_memory_wall);
        $this->assertEquals($this->user->id, $reunion->organizer_id);
    }

    public function test_can_list_reunions()
    {
        // Create some reunion events
        $reunion1 = Event::factory()->create([
            'is_reunion' => true,
            'graduation_year' => 2020,
            'reunion_year_milestone' => 5,
            'status' => 'published',
            'visibility' => 'alumni_only',
            'institution_id' => $this->institution->id,
        ]);

        $reunion2 = Event::factory()->create([
            'is_reunion' => true,
            'graduation_year' => 2015,
            'reunion_year_milestone' => 10,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/reunions');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_reunion', true)
            ->assertJsonPath('data.1.is_reunion', true);
    }

    public function test_can_filter_reunions_by_graduation_year()
    {
        Event::factory()->create([
            'is_reunion' => true,
            'graduation_year' => 2020,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        Event::factory()->create([
            'is_reunion' => true,
            'graduation_year' => 2015,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/reunions?graduation_year=2020');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.graduation_year', 2020);
    }

    public function test_can_upload_reunion_photo()
    {
        $reunion = Event::factory()->create([
            'is_reunion' => true,
            'enable_photo_sharing' => true,
            'institution_id' => $this->institution->id,
        ]);

        $file = UploadedFile::fake()->image('reunion-photo.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunions/{$reunion->id}/photos", [
                'photo' => $file,
                'title' => 'Great memories!',
                'description' => 'Having fun at the reunion',
                'visibility' => 'class_only',
            ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Great memories!')
            ->assertJsonPath('visibility', 'class_only');

        $this->assertDatabaseHas('reunion_photos', [
            'event_id' => $reunion->id,
            'uploaded_by' => $this->user->id,
            'title' => 'Great memories!',
        ]);
    }

    public function test_cannot_upload_photo_when_sharing_disabled()
    {
        $reunion = Event::factory()->create([
            'is_reunion' => true,
            'enable_photo_sharing' => false,
        ]);

        $file = UploadedFile::fake()->image('reunion-photo.jpg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunions/{$reunion->id}/photos", [
                'photo' => $file,
                'title' => 'Test photo',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Photo sharing not available');
    }

    public function test_can_create_reunion_memory()
    {
        $reunion = Event::factory()->create([
            'is_reunion' => true,
            'enable_memory_wall' => true,
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunions/{$reunion->id}/memories", [
                'title' => 'Best College Days',
                'content' => 'I remember when we used to study together in the library...',
                'type' => 'story',
                'visibility' => 'class_only',
                'memory_date' => '2019-05-15',
            ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Best College Days')
            ->assertJsonPath('type', 'story');

        $this->assertDatabaseHas('reunion_memories', [
            'event_id' => $reunion->id,
            'submitted_by' => $this->user->id,
            'title' => 'Best College Days',
            'type' => 'story',
        ]);
    }

    public function test_can_like_reunion_photo()
    {
        $reunion = Event::factory()->create(['is_reunion' => true]);
        $photo = ReunionPhoto::factory()->forEvent($reunion)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunion-photos/{$photo->id}/like");

        $response->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('reunion_photo_likes', [
            'reunion_photo_id' => $photo->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_unlike_reunion_photo()
    {
        $reunion = Event::factory()->create(['is_reunion' => true]);
        $photo = ReunionPhoto::factory()->forEvent($reunion)->create(['likes_count' => 1]);

        // Create existing like
        $photo->likes()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/reunion-photos/{$photo->id}/like");

        $response->assertOk()
            ->assertJsonPath('unliked', true)
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('reunion_photo_likes', [
            'reunion_photo_id' => $photo->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_comment_on_reunion_photo()
    {
        $reunion = Event::factory()->create(['is_reunion' => true]);
        $photo = ReunionPhoto::factory()->forEvent($reunion)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunion-photos/{$photo->id}/comments", [
                'comment' => 'Great photo! Brings back memories.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('comment', 'Great photo! Brings back memories.')
            ->assertJsonPath('user.id', $this->user->id);

        $this->assertDatabaseHas('reunion_photo_comments', [
            'reunion_photo_id' => $photo->id,
            'user_id' => $this->user->id,
            'comment' => 'Great photo! Brings back memories.',
        ]);
    }

    public function test_can_like_reunion_memory()
    {
        $reunion = Event::factory()->create(['is_reunion' => true]);
        $memory = ReunionMemory::factory()->forEvent($reunion)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/reunion-memories/{$memory->id}/like");

        $response->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('reunion_memory_likes', [
            'reunion_memory_id' => $memory->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_get_upcoming_reunion_milestones()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reunions/milestones');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'milestone',
                    'year',
                    'graduation_year',
                    'years_away',
                ],
            ]);

        // Should include 10-year milestone (2030) since user graduated in 2020
        $milestones = $response->json();
        $tenYearMilestone = collect($milestones)->firstWhere('milestone', 10);

        $this->assertNotNull($tenYearMilestone);
        $this->assertEquals(2030, $tenYearMilestone['year']);
        $this->assertEquals(2020, $tenYearMilestone['graduation_year']);
    }

    public function test_can_manage_committee_members()
    {
        $reunion = Event::factory()->create([
            'is_reunion' => true,
            'organizer_id' => $this->user->id,
        ]);

        $committeeMember = User::factory()->create();

        // Add committee member
        $response = $this->actingAs($this->user)
            ->postJson("/api/reunions/{$reunion->id}/committee", [
                'user_id' => $committeeMember->id,
                'role' => 'Decorations Chair',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Committee member added successfully');

        $reunion->refresh();
        $this->assertTrue($reunion->isCommitteeMember($committeeMember));
        $this->assertEquals('Decorations Chair', $reunion->getCommitteeRole($committeeMember));

        // Remove committee member
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/reunions/{$reunion->id}/committee", [
                'user_id' => $committeeMember->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Committee member removed successfully');

        $reunion->refresh();
        $this->assertFalse($reunion->isCommitteeMember($committeeMember));
    }

    public function test_can_get_reunion_statistics()
    {
        $reunion = Event::factory()->create(['is_reunion' => true]);

        // Create some test data
        ReunionPhoto::factory()->count(5)->forEvent($reunion)->create();
        ReunionMemory::factory()->count(3)->forEvent($reunion)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/reunions/{$reunion->id}/statistics");

        $response->assertOk()
            ->assertJsonStructure([
                'total_photos',
                'total_memories',
                'total_registered',
                'total_attended',
                'attendance_rate',
                'engagement_score',
            ])
            ->assertJsonPath('total_photos', 5)
            ->assertJsonPath('total_memories', 3);
    }

    public function test_reunion_visibility_controls()
    {
        $publicReunion = Event::factory()->create([
            'is_reunion' => true,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $privateReunion = Event::factory()->create([
            'is_reunion' => true,
            'visibility' => 'private',
            'status' => 'published',
        ]);

        $institutionReunion = Event::factory()->create([
            'is_reunion' => true,
            'visibility' => 'institution_only',
            'institution_id' => $this->institution->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/reunions');

        $response->assertOk();

        $reunionIds = collect($response->json('data'))->pluck('id');

        // Should see public and institution reunions, but not private
        $this->assertContains($publicReunion->id, $reunionIds);
        $this->assertContains($institutionReunion->id, $reunionIds);
        $this->assertNotContains($privateReunion->id, $reunionIds);
    }

    public function test_reunion_photo_visibility_controls()
    {
        $reunion = Event::factory()->create([
            'is_reunion' => true,
            'graduation_year' => 2020,
            'institution_id' => $this->institution->id,
        ]);

        $publicPhoto = ReunionPhoto::factory()
            ->forEvent($reunion)
            ->visibility('public')
            ->create();

        $classOnlyPhoto = ReunionPhoto::factory()
            ->forEvent($reunion)
            ->visibility('class_only')
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/reunions/{$reunion->id}/photos");

        $response->assertOk();

        $photoIds = collect($response->json())->pluck('id');

        // User should see both photos since they're from the same class
        $this->assertContains($publicPhoto->id, $photoIds);
        $this->assertContains($classOnlyPhoto->id, $photoIds);
    }
}
