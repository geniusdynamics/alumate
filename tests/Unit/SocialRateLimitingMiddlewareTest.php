<?php

use App\Http\Middleware\SocialRateLimiting;
use Illuminate\Http\Request;

describe('Social Rate Limiting Middleware', function () {
    it('can instantiate middleware', function () {
        $middleware = new SocialRateLimiting;

        expect($middleware)->toBeInstanceOf(SocialRateLimiting::class);
    });

    it('can resolve request signature for different actions', function () {
        $middleware = new SocialRateLimiting;
        $request = Request::create('/test', 'POST');

        // Use reflection to test protected method
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('resolveRequestSignature');
        $method->setAccessible(true);

        $user = (object) ['id' => 1];

        $signature = $method->invoke($middleware, $request, $user, 'post_creation');

        expect($signature)->toBe('social:post_creation:1');
    });

    it('can get max attempts for different actions', function () {
        $middleware = new SocialRateLimiting;

        // Use reflection to test protected method
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getMaxAttempts');
        $method->setAccessible(true);

        $postCreationLimit = $method->invoke($middleware, 'post_creation');
        $postInteractionLimit = $method->invoke($middleware, 'post_interaction');

        expect($postCreationLimit)->toBe(10);
        expect($postInteractionLimit)->toBe(100);
    });

    it('can get decay minutes for different actions', function () {
        $middleware = new SocialRateLimiting;

        // Use reflection to test protected method
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getDecayMinutes');
        $method->setAccessible(true);

        $postCreationDecay = $method->invoke($middleware, 'post_creation');
        $connectionRequestDecay = $method->invoke($middleware, 'connection_request');

        expect($postCreationDecay)->toBe(60);
        expect($connectionRequestDecay)->toBe(1440); // 24 hours
    });

    it('can calculate user trust score', function () {
        $middleware = new SocialRateLimiting;

        // Use reflection to test protected method
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('calculateUserTrustScore');
        $method->setAccessible(true);

        $user = (object) [
            'id' => 1,
            'created_at' => now()->subMonths(6),
            'email_verified_at' => now(),
            'bio' => 'Test bio',
            'location' => 'Test location',
            'website' => 'https://example.com',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ];

        // Mock the connections method
        $user->connections = function () {
            return (object) ['count' => function () {
                return 10;
            }];
        };

        $trustScore = $method->invoke($middleware, $user);

        expect($trustScore)->toBeFloat()
            ->toBeGreaterThanOrEqual(0.0)
            ->toBeLessThanOrEqual(1.0);
    });
});
