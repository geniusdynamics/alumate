<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserTrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserTrainingServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserTrainingService $trainingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trainingService = new UserTrainingService;
    }

    public function test_gets_alumni_user_guides(): void
    {
        $guides = $this->trainingService->getUserGuides('alumni');

        $this->assertNotEmpty($guides);
        $this->assertTrue($guides->contains('id', 'getting-started'));
        $this->assertTrue($guides->contains('id', 'networking-guide'));
        $this->assertTrue($guides->contains('id', 'career-development'));
        $this->assertTrue($guides->contains('id', 'social-features'));

        $guide = $guides->firstWhere('id', 'getting-started');
        $this->assertEquals('Getting Started Guide', $guide['title']);
        $this->assertArrayHasKey('sections', $guide);
        $this->assertArrayHasKey('estimated_time', $guide);
    }

    public function test_gets_institution_admin_user_guides(): void
    {
        $guides = $this->trainingService->getUserGuides('institution_admin');

        $this->assertNotEmpty($guides);
        $this->assertTrue($guides->contains('id', 'admin-dashboard'));
        $this->assertTrue($guides->contains('id', 'alumni-management'));
        $this->assertTrue($guides->contains('id', 'events-fundraising'));
        $this->assertTrue($guides->contains('id', 'analytics-reporting'));
    }

    public function test_gets_video_tutorials_for_role(): void
    {
        $tutorials = $this->trainingService->getVideoTutorials('alumni');

        $this->assertNotEmpty($tutorials);
        $this->assertTrue($tutorials->contains('id', 'profile-setup-video'));
        $this->assertTrue($tutorials->contains('id', 'networking-video'));
        $this->assertTrue($tutorials->contains('id', 'job-search-video'));

        $tutorial = $tutorials->firstWhere('id', 'profile-setup-video');
        $this->assertEquals('Setting Up Your Alumni Profile', $tutorial['title']);
        $this->assertArrayHasKey('duration', $tutorial);
        $this->assertArrayHasKey('topics', $tutorial);
    }

    public function test_gets_onboarding_sequence_for_alumni(): void
    {
        $sequence = $this->trainingService->getOnboardingSequence('alumni');

        $this->assertNotEmpty($sequence);
        $this->assertArrayHasKey('id', $sequence[0]);
        $this->assertArrayHasKey('title', $sequence[0]);
        $this->assertArrayHasKey('description', $sequence[0]);
        $this->assertArrayHasKey('type', $sequence[0]);

        // Check for key onboarding steps
        $stepIds = collect($sequence)->pluck('id')->toArray();
        $this->assertContains('welcome', $stepIds);
        $this->assertContains('profile-completion', $stepIds);
        $this->assertContains('explore-directory', $stepIds);
    }

    public function test_gets_faqs_for_role(): void
    {
        $faqs = $this->trainingService->getFAQs('alumni');

        $this->assertNotEmpty($faqs);

        $faq = $faqs->first();
        $this->assertArrayHasKey('id', $faq);
        $this->assertArrayHasKey('question', $faq);
        $this->assertArrayHasKey('answer', $faq);
        $this->assertArrayHasKey('category', $faq);
    }

    public function test_calculates_training_progress(): void
    {
        $user = User::factory()->create();
        $user->update([
            'onboarding_progress' => [
                'welcome' => ['completed' => true, 'completed_at' => now()->toISOString()],
                'profile-completion' => ['completed' => true, 'completed_at' => now()->toISOString()],
            ],
        ]);

        $progress = $this->trainingService->getTrainingProgress($user);

        $this->assertArrayHasKey('total_steps', $progress);
        $this->assertArrayHasKey('completed_steps', $progress);
        $this->assertArrayHasKey('completion_percentage', $progress);
        $this->assertArrayHasKey('current_step', $progress);
        $this->assertArrayHasKey('next_recommended_action', $progress);

        $this->assertEquals(2, $progress['completed_steps']);
        $this->assertGreaterThan(0, $progress['completion_percentage']);
    }

    public function test_marks_step_as_completed(): void
    {
        $user = User::factory()->create();

        $result = $this->trainingService->markStepCompleted($user, 'welcome');

        $this->assertTrue($result);

        $user->refresh();
        $progress = $user->onboarding_progress;

        $this->assertArrayHasKey('welcome', $progress);
        $this->assertTrue($progress['welcome']['completed']);
        $this->assertArrayHasKey('completed_at', $progress['welcome']);
    }

    public function test_caches_user_guides(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('user_guides_alumni', 3600, \Closure::class)
            ->andReturn(collect([]));

        $this->trainingService->getUserGuides('alumni');
    }

    public function test_gets_different_guides_for_different_roles(): void
    {
        $alumniGuides = $this->trainingService->getUserGuides('alumni');
        $adminGuides = $this->trainingService->getUserGuides('institution_admin');
        $superAdminGuides = $this->trainingService->getUserGuides('super_admin');

        // Each role should have different guides
        $this->assertNotEquals($alumniGuides->pluck('id'), $adminGuides->pluck('id'));
        $this->assertNotEquals($adminGuides->pluck('id'), $superAdminGuides->pluck('id'));

        // Alumni should have networking guide, admin should not
        $this->assertTrue($alumniGuides->contains('id', 'networking-guide'));
        $this->assertFalse($adminGuides->contains('id', 'networking-guide'));

        // Admin should have admin dashboard guide, alumni should not
        $this->assertTrue($adminGuides->contains('id', 'admin-dashboard'));
        $this->assertFalse($alumniGuides->contains('id', 'admin-dashboard'));
    }

    public function test_gets_role_specific_faqs(): void
    {
        $alumniFaqs = $this->trainingService->getFAQs('alumni');
        $adminFaqs = $this->trainingService->getFAQs('institution_admin');

        // Both should have general FAQs
        $this->assertTrue($alumniFaqs->contains('category', 'security'));
        $this->assertTrue($adminFaqs->contains('category', 'security'));

        // Alumni should have networking FAQs
        $this->assertTrue($alumniFaqs->contains('category', 'networking'));

        // Admin should have data management FAQs
        $this->assertTrue($adminFaqs->contains('category', 'data-management'));
    }

    public function test_onboarding_sequence_varies_by_role(): void
    {
        $alumniSequence = $this->trainingService->getOnboardingSequence('alumni');
        $adminSequence = $this->trainingService->getOnboardingSequence('institution_admin');
        $employerSequence = $this->trainingService->getOnboardingSequence('employer');

        // Each role should have different sequences
        $this->assertNotEquals(
            collect($alumniSequence)->pluck('id'),
            collect($adminSequence)->pluck('id')
        );

        // Alumni should have profile completion step
        $this->assertTrue(collect($alumniSequence)->contains('id', 'profile-completion'));

        // Admin should have dashboard overview step
        $this->assertTrue(collect($adminSequence)->contains('id', 'dashboard-overview'));

        // Employer should have company verification step
        $this->assertTrue(collect($employerSequence)->contains('id', 'company-verification'));
    }
}
