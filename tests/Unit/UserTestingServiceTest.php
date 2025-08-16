<?php

use App\Models\ABTest;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserTestingSession;
use App\Services\UserTestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserTestingService', function () {
    beforeEach(function () {
        $this->service = app(UserTestingService::class);
        $this->user = User::factory()->create();
    });

    it('can create a testing session', function () {
        $session = $this->service->createTestingSession(
            $this->user,
            'alumni_onboarding',
            ['test_type' => 'unit_test']
        );

        expect($session)->toBeInstanceOf(UserTestingSession::class);
        expect($session->user_id)->toBe($this->user->id);
        expect($session->scenario)->toBe('alumni_onboarding');
        expect($session->status)->toBe('active');
        expect($session->metadata)->toHaveKey('test_type');
    });

    it('can record user feedback', function () {
        $feedback = $this->service->recordFeedback(
            $this->user,
            'general_feedback',
            'This is a test feedback',
            5,
            ['page' => '/test']
        );

        expect($feedback)->toBeInstanceOf(UserFeedback::class);
        expect($feedback->user_id)->toBe($this->user->id);
        expect($feedback->type)->toBe('general_feedback');
        expect($feedback->content)->toBe('This is a test feedback');
        expect($feedback->rating)->toBe(5);
        expect($feedback->status)->toBe('pending');
    });

    it('can create an A/B test', function () {
        $test = $this->service->createABTest(
            'test_button_color',
            'Testing different button colors',
            ['blue', 'green', 'red']
        );

        expect($test)->toBeInstanceOf(ABTest::class);
        expect($test->name)->toBe('test_button_color');
        expect($test->variants)->toBe(['blue', 'green', 'red']);
        expect($test->status)->toBe('draft');
        expect($test->distribution)->toHaveKey('blue');
        expect($test->distribution)->toHaveKey('green');
        expect($test->distribution)->toHaveKey('red');
    });

    it('can assign user to A/B test variant', function () {
        $test = ABTest::factory()->active()->create([
            'name' => 'button_test',
            'variants' => ['control', 'variant_a'],
            'distribution' => ['control' => 50, 'variant_a' => 50],
        ]);

        $variant = $this->service->getABTestVariant($this->user, 'button_test');

        expect($variant)->toBeIn(['control', 'variant_a']);

        // Should return same variant on subsequent calls
        $variant2 = $this->service->getABTestVariant($this->user, 'button_test');
        expect($variant2)->toBe($variant);
    });

    it('can track A/B test conversion', function () {
        $test = ABTest::factory()->active()->create([
            'name' => 'conversion_test',
            'variants' => ['control', 'variant_a'],
            'distribution' => ['control' => 50, 'variant_a' => 50],
        ]);

        // First get variant assignment
        $variant = $this->service->getABTestVariant($this->user, 'conversion_test');

        // Track conversion
        $this->service->trackConversion(
            $this->user,
            'conversion_test',
            'button_click',
            ['button_id' => 'cta_button']
        );

        // Verify conversion was recorded
        $conversion = $test->conversions()->where('user_id', $this->user->id)->first();
        expect($conversion)->not->toBeNull();
        expect($conversion->variant)->toBe($variant);
        expect($conversion->event)->toBe('button_click');
    });

    it('can get user experience metrics', function () {
        // Create some test sessions
        UserTestingSession::factory()->completed()->create([
            'user_id' => $this->user->id,
            'scenario' => 'onboarding',
            'duration_seconds' => 300,
        ]);

        UserTestingSession::factory()->completed()->create([
            'user_id' => $this->user->id,
            'scenario' => 'job_search',
            'duration_seconds' => 180,
        ]);

        // Create some feedback
        UserFeedback::factory()->generalFeedback()->create([
            'user_id' => $this->user->id,
            'rating' => 4,
        ]);

        $metrics = $this->service->getUserExperienceMetrics($this->user);

        expect($metrics['total_sessions'])->toBe(2);
        expect($metrics['completed_sessions'])->toBe(2);
        expect($metrics['scenarios_tested'])->toBe(2);
        expect($metrics['feedback_count'])->toBe(1);
        expect($metrics['average_rating'])->toBe(4.0);
        expect($metrics['average_duration'])->toBe(240.0); // (300 + 180) / 2
    });

    it('can get testing analytics', function () {
        // Create test data
        $user2 = User::factory()->create();

        UserTestingSession::factory()->completed()->create(['user_id' => $this->user->id]);
        UserTestingSession::factory()->abandoned()->create(['user_id' => $user2->id]);

        UserFeedback::factory()->create(['user_id' => $this->user->id, 'rating' => 5]);
        UserFeedback::factory()->create(['user_id' => $user2->id, 'rating' => 3]);

        $analytics = $this->service->getTestingAnalytics();

        expect($analytics['total_sessions'])->toBe(2);
        expect($analytics['unique_users'])->toBe(2);
        expect($analytics['completion_rate'])->toBe(0.5); // 1 completed out of 2
        expect($analytics['feedback_summary']['total_feedback'])->toBe(2);
        expect($analytics['feedback_summary']['average_rating'])->toBe(4.0);
    });

    it('returns null for non-existent A/B test', function () {
        $variant = $this->service->getABTestVariant($this->user, 'non_existent_test');
        expect($variant)->toBeNull();
    });

    it('provides testing scenarios', function () {
        $scenarios = $this->service->getTestingScenarios();

        expect($scenarios)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($scenarios->count())->toBeGreaterThan(0);

        $firstScenario = $scenarios->first();
        expect($firstScenario)->toHaveKey('name');
        expect($firstScenario)->toHaveKey('title');
        expect($firstScenario)->toHaveKey('description');
        expect($firstScenario)->toHaveKey('steps');
        expect($firstScenario)->toHaveKey('success_criteria');
    });
});
