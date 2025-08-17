<?php

use App\Models\FederationMapping;
use App\Models\Post;
use App\Models\User;
use App\Services\Federation\ActivityPubMapper;
use App\Services\Federation\FederationBridge;
use App\Services\Federation\MatrixEventMapper;

beforeEach(function () {
    $this->matrixMapper = app(MatrixEventMapper::class);
    $this->activityPubMapper = app(ActivityPubMapper::class);
    $this->federationBridge = app(FederationBridge::class);
});

it('converts post to matrix event', function () {
    $user = User::factory()->create(['email' => 'testuser@example.com']);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'content' => 'Test post content',
        'post_type' => 'text',
        'visibility' => 'public',
    ]);

    $matrixEvent = $this->matrixMapper->postToMatrixEvent($post);

    expect($matrixEvent)->toHaveKeys([
        'type', 'content', 'sender', 'origin_server_ts', 'event_id',
    ]);
    expect($matrixEvent['type'])->toBe('m.room.message');
    expect($matrixEvent['content']['msgtype'])->toBe('m.text');
    expect($matrixEvent['content']['body'])->toBe('Test post content');
    expect($matrixEvent['content']['alumni.post_id'])->toBe($post->id);
    expect($matrixEvent['sender'])->toContain('@testuser:');
});

it('converts user to matrix profile', function () {
    $user = User::factory()->create([
        'email' => 'testuser@example.com',
        'name' => 'Test User',
        'profile_data' => ['bio' => 'Test bio'],
    ]);

    $matrixProfile = $this->matrixMapper->userToMatrixProfile($user);

    expect($matrixProfile)->toHaveKeys([
        'user_id', 'displayname', 'alumni.user_id', 'alumni.username',
    ]);
    expect($matrixProfile['displayname'])->toBe('Test User');
    expect($matrixProfile['alumni.user_id'])->toBe($user->id);
    expect($matrixProfile['alumni.username'])->toBe('testuser');
    expect($matrixProfile['user_id'])->toContain('@testuser:');
});

it('converts post to activitypub object', function () {
    $user = User::factory()->create(['email' => 'testuser@example.com']);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'content' => 'Test post content',
        'post_type' => 'text',
        'visibility' => 'public',
    ]);

    $activityPubObject = $this->activityPubMapper->postToActivityPubObject($post);

    expect($activityPubObject)->toHaveKeys([
        '@context', 'type', 'id', 'attributedTo', 'content', 'published',
    ]);
    expect($activityPubObject['type'])->toBe('Note');
    expect($activityPubObject['content'])->toBe('Test post content');
    expect($activityPubObject['alumni:postId'])->toBe($post->id);
    expect($activityPubObject['attributedTo'])->toContain('/federation/users/testuser');
});

it('converts user to activitypub actor', function () {
    $user = User::factory()->create([
        'email' => 'testuser@example.com',
        'name' => 'Test User',
        'profile_data' => ['bio' => 'Test bio'],
    ]);

    $activityPubActor = $this->activityPubMapper->userToActivityPubActor($user);

    expect($activityPubActor)->toHaveKeys([
        '@context', 'type', 'id', 'preferredUsername', 'name', 'summary',
    ]);
    expect($activityPubActor['type'])->toBe('Person');
    expect($activityPubActor['preferredUsername'])->toBe('testuser');
    expect($activityPubActor['name'])->toBe('Test User');
    expect($activityPubActor['summary'])->toBe('Test bio');
    expect($activityPubActor['alumni:userId'])->toBe($user->id);
});

it('creates and retrieves federation mappings', function () {
    $mapping = FederationMapping::factory()->create([
        'local_type' => 'post',
        'local_id' => 123,
        'protocol' => 'matrix',
        'federation_id' => '$test_event:example.com',
    ]);

    expect($mapping->local_type)->toBe('post');
    expect($mapping->local_id)->toBe(123);
    expect($mapping->protocol)->toBe('matrix');
    expect($mapping->federation_id)->toBe('$test_event:example.com');

    $found = FederationMapping::findMapping('post', 123, 'matrix');
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($mapping->id);
});

it('checks if entity is federated', function () {
    FederationMapping::factory()->create([
        'local_type' => 'user',
        'local_id' => 456,
        'protocol' => 'activitypub',
    ]);

    expect(FederationMapping::isFederated('user', 456, 'activitypub'))->toBeTrue();
    expect(FederationMapping::isFederated('user', 456, 'matrix'))->toBeFalse();
    expect(FederationMapping::isFederated('user', 999, 'activitypub'))->toBeFalse();
});

it('gets federated protocols for entity', function () {
    FederationMapping::factory()->create([
        'local_type' => 'group',
        'local_id' => 789,
        'protocol' => 'matrix',
    ]);

    FederationMapping::factory()->create([
        'local_type' => 'group',
        'local_id' => 789,
        'protocol' => 'activitypub',
    ]);

    $protocols = FederationMapping::getFederatedProtocols('group', 789);
    expect($protocols)->toHaveCount(2);
    expect($protocols)->toContain('matrix');
    expect($protocols)->toContain('activitypub');
});

it('creates federation mapping through bridge', function () {
    $mapping = $this->federationBridge->createFederationMapping(
        'post',
        123,
        'matrix',
        '$test_event:example.com',
        ['type' => 'm.room.message'],
        'example.com'
    );

    expect($mapping)->toBeInstanceOf(FederationMapping::class);
    expect($mapping->local_type)->toBe('post');
    expect($mapping->local_id)->toBe(123);
    expect($mapping->protocol)->toBe('matrix');
    expect($mapping->federation_id)->toBe('$test_event:example.com');
    expect($mapping->federation_data)->toBe(['type' => 'm.room.message']);
    expect($mapping->server_name)->toBe('example.com');
});

it('gets user federated identity', function () {
    $user = User::factory()->create(['email' => 'testuser@example.com']);

    $matrixId = $this->federationBridge->getUserFederatedIdentity($user, 'matrix');
    $activityPubId = $this->federationBridge->getUserFederatedIdentity($user, 'activitypub');

    expect($matrixId)->toContain('@testuser:');
    expect($activityPubId)->toContain('/federation/users/testuser');
});

it('returns federation status', function () {
    // Create some test mappings
    FederationMapping::factory()->matrix()->create();
    FederationMapping::factory()->activitypub()->create();

    $status = $this->federationBridge->getFederationStatus();

    expect($status)->toHaveKeys([
        'enabled', 'protocols', 'mappings', 'last_activity',
    ]);
    expect($status['mappings']['total'])->toBeGreaterThan(0);
    expect($status['mappings']['by_protocol'])->toHaveKey('matrix');
    expect($status['mappings']['by_protocol'])->toHaveKey('activitypub');
});

it('handles incoming activities', function () {
    // Enable federation for this test
    config(['federation.enabled' => true, 'federation.protocols' => ['matrix']]);

    // Create a new bridge instance with updated config
    $bridge = new \App\Services\Federation\FederationBridge(
        app(\App\Services\Federation\MatrixEventMapper::class),
        app(\App\Services\Federation\ActivityPubMapper::class)
    );

    $matrixEvent = [
        'type' => 'm.room.message',
        'sender' => '@user:example.com',
        'content' => ['msgtype' => 'm.text', 'body' => 'Hello'],
    ];

    $result = $bridge->handleIncomingActivity('matrix', $matrixEvent);

    expect($result)->toHaveKey('status');
    expect($result['status'])->toBe('processed');
});

it('gets compatibility information', function () {
    $compatibility = $this->federationBridge->getFederationCompatibility();

    expect($compatibility)->toHaveKeys(['matrix', 'activitypub']);
    expect($compatibility['matrix'])->toHaveKeys([
        'version', 'supported_events', 'extensions',
    ]);
    expect($compatibility['activitypub'])->toHaveKeys([
        'version', 'supported_activities', 'supported_objects', 'extensions',
    ]);
});

it('generates consistent matrix user ids', function () {
    $user = User::factory()->create(['email' => 'testuser@example.com']);

    $matrixId1 = $this->matrixMapper->getUserMatrixId($user);
    $matrixId2 = $this->matrixMapper->getUserMatrixId($user);

    expect($matrixId1)->toBe($matrixId2);
    expect($matrixId1)->toMatch('/^@testuser:.+$/');
});

it('creates valid activitypub activities', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $createActivity = $this->activityPubMapper->createPostActivity($post);
    $likeActivity = $this->activityPubMapper->createLikeActivity($user, $post);

    expect($createActivity['type'])->toBe('Create');
    expect($createActivity['object']['type'])->toBe('Note');
    expect($likeActivity['type'])->toBe('Like');
    expect($likeActivity['actor'])->toBe($createActivity['actor']);
});

it('provides federation mapping statistics', function () {
    FederationMapping::factory()->matrix()->forLocalType('post')->count(3)->create();
    FederationMapping::factory()->activitypub()->forLocalType('user')->count(2)->create();

    $stats = FederationMapping::getStatistics();

    expect($stats['total_mappings'])->toBe(5);
    expect($stats['by_protocol']['matrix'])->toBe(3);
    expect($stats['by_protocol']['activitypub'])->toBe(2);
    expect($stats['by_type']['post'])->toBe(3);
    expect($stats['by_type']['user'])->toBe(2);
});
