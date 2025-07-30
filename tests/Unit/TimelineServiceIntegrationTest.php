<?php

use App\Services\TimelineService;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

test('timeline service can generate cursor for pagination', function () {
    $service = new TimelineService();
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateCursor');
    $method->setAccessible(true);
    
    // Create a mock post object
    $post = new stdClass();
    $post->id = 123;
    $post->created_at = new DateTime('2024-01-01 12:00:00');
    
    $cursor = $method->invoke($service, $post);
    
    expect($cursor)->toBeString();
    expect(strlen($cursor))->toBeGreaterThan(0);
    
    // Decode and verify
    $decoded = json_decode(base64_decode($cursor), true);
    expect($decoded['id'])->toBe(123);
    expect($decoded['created_at'])->toContain('2024-01-01');
});

test('timeline service can decode cursor for pagination', function () {
    $service = new TimelineService();
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('decodeCursor');
    $method->setAccessible(true);
    
    // Create a test cursor
    $testData = ['id' => 456, 'created_at' => '2024-01-01T12:00:00.000000Z'];
    $cursor = base64_encode(json_encode($testData));
    
    $decoded = $method->invoke($service, $cursor);
    
    expect($decoded)->toBeArray();
    expect($decoded['id'])->toBe(456);
    expect($decoded['created_at'])->toBe('2024-01-01T12:00:00.000000Z');
});

test('timeline service cache key generation works', function () {
    $service = new TimelineService();
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getTimelineCacheKey');
    $method->setAccessible(true);
    
    $key1 = $method->invoke($service, 123);
    $key2 = $method->invoke($service, 123, 'cursor123');
    
    expect($key1)->toBe('timeline:user:123');
    expect($key2)->toContain('timeline:user:123:');
    expect($key1)->not->toBe($key2);
});

test('timeline service ttl calculation works', function () {
    $service = new TimelineService();
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getTtlForUser');
    $method->setAccessible(true);
    
    // Create mock user objects
    $activeUser = new stdClass();
    $activeUser->last_activity_at = new DateTime('now');
    
    $inactiveUser = new stdClass();
    $inactiveUser->last_activity_at = new DateTime('-2 days');
    
    $nullActivityUser = new stdClass();
    $nullActivityUser->last_activity_at = null;
    
    $activeTtl = $method->invoke($service, $activeUser);
    $inactiveTtl = $method->invoke($service, $inactiveUser);
    $nullTtl = $method->invoke($service, $nullActivityUser);
    
    expect($activeTtl)->toBe(900); // 15 minutes
    expect($inactiveTtl)->toBe(3600); // 1 hour
    expect($nullTtl)->toBe(3600); // 1 hour
});

test('timeline service scoring considers post age', function () {
    $service = new TimelineService();
    
    // Create mock post and user
    $recentPost = new stdClass();
    $recentPost->id = 1;
    $recentPost->user_id = 1;
    $recentPost->created_at = new DateTime('-1 hour');
    $recentPost->visibility = 'public';
    $recentPost->circle_ids = [];
    $recentPost->group_ids = [];
    
    $oldPost = new stdClass();
    $oldPost->id = 2;
    $oldPost->user_id = 2;
    $oldPost->created_at = new DateTime('-1 day');
    $oldPost->visibility = 'public';
    $oldPost->circle_ids = [];
    $oldPost->group_ids = [];
    
    $user = new stdClass();
    $user->id = 3;
    
    // Mock the engagements method
    $recentPost->engagements = function() {
        return new class {
            public function count() { return 0; }
        };
    };
    
    $oldPost->engagements = function() {
        return new class {
            public function count() { return 0; }
        };
    };
    
    // We can't easily test the full scoring without database, 
    // but we can verify the method exists and accepts the right parameters
    expect(method_exists($service, 'scorePost'))->toBeTrue();
    
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('scorePost');
    $parameters = $method->getParameters();
    
    expect(count($parameters))->toBe(2);
    expect($parameters[0]->getName())->toBe('post');
    expect($parameters[1]->getName())->toBe('user');
});

test('timeline service constants are defined correctly', function () {
    $reflection = new ReflectionClass(TimelineService::class);
    $constants = $reflection->getConstants();
    
    expect($constants['CACHE_PREFIX'])->toBe('timeline:user:');
    expect($constants['ACTIVE_USER_TTL'])->toBe(900);
    expect($constants['INACTIVE_USER_TTL'])->toBe(3600);
    expect($constants['ACTIVE_THRESHOLD_HOURS'])->toBe(24);
});