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
