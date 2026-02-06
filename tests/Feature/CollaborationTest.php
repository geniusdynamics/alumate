<?php

use App\Models\LandingPage;
use App\Models\CollaborationSession;
use App\Models\PageChange;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create(['tenant_id' => $this->user1->tenant_id]);
    $this->page = LandingPage::factory()->create([
        'tenant_id' => $this->user1->tenant_id,
    ]);
    $this->collaborationService = app(CollaborationService::class);
});

it('can start a collaboration session', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);

    expect($session)->toBeInstanceOf(CollaborationSession::class);
    expect($session->page_id)->toBe($this->page->id);
    expect($session->user_id)->toBe($this->user1->id);
    expect($session->status)->toBe('active');
    expect($session->session_id)->not->toBeNull();
});

it('can end a collaboration session', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);
    
    $this->collaborationService->endSession($session->session_id);
    
    $session->refresh();
    expect($session->status)->toBe('disconnected');
});

it('can get active sessions for a page', function () {
    $session1 = $this->collaborationService->startSession($this->page, $this->user1);
    $session2 = $this->collaborationService->startSession($this->page, $this->user2);

    $activeSessions = $this->collaborationService->getActiveSessions($this->page);

    expect($activeSessions)->toHaveCount(2);
    expect($activeSessions->pluck('user_id'))->toContain($this->user1->id, $this->user2->id);
});

it('can record page changes', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);

    $operationData = [
        'component_id' => 'test-component',
        'action' => 'add',
        'data' => ['type' => 'text', 'content' => 'Hello World'],
    ];

    $change = $this->collaborationService->recordChange(
        $this->page,
        $this->user1,
        $session->session_id,
        'add',
        $operationData,
        'test-component'
    );

    expect($change)->toBeInstanceOf(PageChange::class);
    expect($change->operation_type)->toBe('add');
    expect($change->operation_data)->toBe($operationData);
    expect($change->component_id)->toBe('test-component');
    expect($change->sequence_number)->toBe(1);
});

it('can update session activity', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);
    
    $cursorPosition = ['x' => 100, 'y' => 200];
    $selectedComponent = ['id' => 'test-component', 'type' => 'text'];

    $this->collaborationService->updateSessionActivity(
        $session->session_id,
        $cursorPosition,
        $selectedComponent
    );

    $session->refresh();
    expect($session->cursor_position)->toBe($cursorPosition);
    expect($session->selected_component)->toBe($selectedComponent);
});

it('can get recent changes for a page', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);

    // Record multiple changes
    $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'add', ['test' => 1]);
    $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'update', ['test' => 2]);
    $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'delete', ['test' => 3]);

    $recentChanges = $this->collaborationService->getRecentChanges($this->page);

    expect($recentChanges)->toHaveCount(3);
    expect($recentChanges->first()->operation_type)->toBe('delete'); // Most recent first
    expect($recentChanges->last()->operation_type)->toBe('add'); // Oldest last
});

it('can get changes since a sequence number', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);

    // Record multiple changes
    $change1 = $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'add', ['test' => 1]);
    $change2 = $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'update', ['test' => 2]);
    $change3 = $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'delete', ['test' => 3]);

    $changesSince = $this->collaborationService->getChangesSince($this->page, $change1->sequence_number);

    expect($changesSince)->toHaveCount(2);
    expect($changesSince->first()->sequence_number)->toBe($change2->sequence_number);
    expect($changesSince->last()->sequence_number)->toBe($change3->sequence_number);
});

it('can resolve conflicts', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);
    
    $change = $this->collaborationService->recordChange(
        $this->page,
        $this->user1,
        $session->session_id,
        'update',
        ['test' => 'data']
    );

    // Resolve conflict by accepting
    $resolved = $this->collaborationService->resolveConflict($change, 'accept');
    
    expect($resolved)->toBeTrue();
    
    $change->refresh();
    expect($change->is_applied)->toBeTrue();
});

it('can cleanup inactive sessions', function () {
    $session1 = $this->collaborationService->startSession($this->page, $this->user1);
    $session2 = $this->collaborationService->startSession($this->page, $this->user2);

    // Manually set one session as old
    $session1->update(['last_activity' => now()->subHours(2)]);

    $cleaned = $this->collaborationService->cleanupInactiveSessions();

    expect($cleaned)->toBe(1);
    
    $session1->refresh();
    $session2->refresh();
    
    expect($session1->status)->toBe('disconnected');
    expect($session2->status)->toBe('active');
});

it('can get user activity for a page', function () {
    $session = $this->collaborationService->startSession($this->page, $this->user1);

    // Record some changes
    $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'add', ['test' => 1]);
    $this->collaborationService->recordChange($this->page, $this->user1, $session->session_id, 'update', ['test' => 2]);

    $activity = $this->collaborationService->getUserActivity($this->page, $this->user1);

    expect($activity)->toHaveCount(2);
    expect($activity->every(fn($change) => $change->user_id === $this->user1->id))->toBeTrue();
});

it('ends existing sessions when starting a new one', function () {
    $session1 = $this->collaborationService->startSession($this->page, $this->user1);
    expect($session1->status)->toBe('active');

    $session2 = $this->collaborationService->startSession($this->page, $this->user1);
    
    $session1->refresh();
    expect($session1->status)->toBe('disconnected');
    expect($session2->status)->toBe('active');
});