<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTrainingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'alumni']);
        Role::create(['name' => 'institution_admin']);
        Role::create(['name' => 'super_admin']);

        $this->user = User::factory()->create();
        $this->user->assignRole('alumni');
    }

    public function test_training_index_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Training/Index')
            ->has('userGuides')
            ->has('videoTutorials')
            ->has('trainingProgress')
            ->has('faqs')
            ->has('role')
        );
    }

    public function test_can_view_specific_guide(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.guide', 'getting-started'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Training/Guide')
            ->has('guide')
            ->where('guide.id', 'getting-started')
            ->has('role')
            ->has('trainingProgress')
        );
    }

    public function test_returns_404_for_invalid_guide(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.guide', 'non-existent-guide'));

        $response->assertNotFound();
    }

    public function test_can_view_video_tutorial(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.tutorial', 'profile-setup-video'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Training/VideoTutorial')
            ->has('tutorial')
            ->where('tutorial.id', 'profile-setup-video')
            ->has('role')
            ->has('relatedTutorials')
            ->has('trainingProgress')
        );
    }

    public function test_returns_404_for_invalid_tutorial(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.tutorial', 'non-existent-tutorial'));

        $response->assertNotFound();
    }

    public function test_can_view_faqs_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('training.faqs'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('Training/FAQs')
            ->has('faqs')
            ->has('role')
        );
    }

    public function test_can_get_user_guides_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/guides');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'category',
                    'estimated_time',
                    'sections',
                    'icon',
                ],
            ],
        ]);
    }

    public function test_can_get_video_tutorials_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/tutorials');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'duration',
                    'category',
                    'topics',
                ],
            ],
        ]);
    }

    public function test_can_get_onboarding_sequence_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/onboarding-sequence');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'icon',
                ],
            ],
        ]);
    }

    public function test_can_get_faqs_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/faqs');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'question',
                    'answer',
                    'category',
                ],
            ],
        ]);
    }

    public function test_can_get_training_progress_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/progress');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_steps',
                'completed_steps',
                'completion_percentage',
                'current_step',
                'next_recommended_action',
            ],
        ]);
    }

    public function test_can_mark_step_as_completed(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/training/mark-step-completed', [
                'step_id' => 'welcome',
            ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Step marked as completed',
        ]);

        // Verify the step was marked as completed
        $this->user->refresh();
        $progress = $this->user->onboarding_progress;
        $this->assertArrayHasKey('welcome', $progress);
        $this->assertTrue($progress['welcome']['completed']);
    }

    public function test_mark_step_completed_requires_step_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/training/mark-step-completed', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['step_id']);
    }

    public function test_can_search_training_content(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/search?query=profile');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'guides',
                'tutorials',
                'faqs',
                'total_results',
            ],
        ]);
    }

    public function test_search_requires_query_parameter(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/search');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_search_requires_minimum_query_length(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/training/search?query=a');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_can_mark_faq_as_helpful(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/training/faq-helpful', [
                'faq_id' => 'profile-visibility',
            ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    public function test_can_submit_training_feedback(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/training/feedback', [
                'type' => 'guide',
                'content_id' => 'getting-started',
                'rating' => 5,
                'feedback' => 'Very helpful guide!',
            ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Thank you for your feedback! We use it to improve our training materials.',
        ]);
    }

    public function test_feedback_requires_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/training/feedback', [
                'type' => 'invalid',
                'rating' => 6,
                'feedback' => '',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type', 'rating', 'feedback']);
    }

    public function test_different_roles_get_different_content(): void
    {
        // Test alumni content
        $alumniResponse = $this->actingAs($this->user)
            ->getJson('/api/training/guides');

        $alumniGuides = $alumniResponse->json('data');

        // Create admin user
        $admin = User::factory()->create();
        $admin->assignRole('institution_admin');

        // Test admin content
        $adminResponse = $this->actingAs($admin)
            ->getJson('/api/training/guides');

        $adminGuides = $adminResponse->json('data');

        // Content should be different
        $this->assertNotEquals(
            collect($alumniGuides)->pluck('id'),
            collect($adminGuides)->pluck('id')
        );
    }

    public function test_unauthenticated_users_cannot_access_training(): void
    {
        $response = $this->get(route('training.index'));
        $response->assertRedirect(route('login'));

        $response = $this->getJson('/api/training/guides');
        $response->assertUnauthorized();
    }
}
