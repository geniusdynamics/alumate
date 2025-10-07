<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Create test tenant
    $this->tenant = Tenant::factory()->create([
        'id' => 'test-tenant-123',
        'name' => 'Test Tenant',
    ]);

    // Create test user with admin role
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    Sanctum::actingAs($this->user);
});

test('it can create valid ab test', function () {
    $testData = [
        'name' => 'Homepage CTA Test',
        'description' => 'Testing different CTA button colors',
        'variants' => [
            [
                'name' => 'Blue Button',
                'weight' => 50,
            ],
            [
                'name' => 'Green Button',
                'weight' => 50,
            ],
        ],
        'goal_event' => 'cta_click',
        'audience_criteria' => [
            'user_type' => 'registered',
        ],
    ];

    $response = $this->postJson('/api/ab-tests', $testData);

    $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'variants',
                    'goal_event',
                    'status',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Homepage CTA Test',
                    'description' => 'Testing different CTA button colors',
                    'variants' => $testData['variants'],
                    'goal_event' => 'cta_click',
                    'status' => 'active',
                ],
                'message' => 'A/B test created successfully',
            ]);
});

test('it validates required fields when creating ab test', function () {
    $invalidData = [
        'name' => '', // Empty name
        'variants' => [
            [
                'name' => 'Only One Variant', // Need at least 2
                'weight' => 100,
            ],
        ],
        // Missing goal_event
    ];

    $response = $this->postJson('/api/ab-tests', $invalidData);

    $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors([
                'name',
                'variants',
                'goal_event',
            ]);
});

test('it can list ab tests with pagination', function () {
    // Create some test data first
    $this->postJson('/api/ab-tests', [
        'name' => 'Test A/B 1',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $this->postJson('/api/ab-tests', [
        'name' => 'Test A/B 2',
        'variants' => [
            ['name' => 'Variant C', 'weight' => 50],
            ['name' => 'Variant D', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $response = $this->getJson('/api/ab-tests');

    $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ]);
});

test('it can get ab test details', function () {
    // Create a test first
    $createResponse = $this->postJson('/api/ab-tests', [
        'name' => 'Detail Test',
        'description' => 'Test for getting details',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $testId = $createResponse->json('data.id');

    $response = $this->getJson("/api/ab-tests/{$testId}");

    $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'variants',
                    'status',
                    'goal_event',
                    'started_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Detail Test',
                    'description' => 'Test for getting details',
                    'goal_event' => 'click',
                ],
            ]);
});

test('it returns 404 for non existent ab test', function () {
    $response = $this->getJson('/api/ab-tests/999');

    $response->assertStatus(404)
            ->assertJson([
                'message' => 'A/B test not found',
            ]);
});

test('it can update ab test', function () {
    // Create a test first
    $createResponse = $this->postJson('/api/ab-tests', [
        'name' => 'Original Test',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $testId = $createResponse->json('data.id');

    $updateData = [
        'name' => 'Updated Test Name',
        'description' => 'Updated description',
        'status' => 'inactive',
    ];

    $response = $this->putJson("/api/ab-tests/{$testId}", $updateData);

    $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'variants',
                    'status',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Updated Test Name',
                    'description' => 'Updated description',
                    'status' => 'inactive',
                ],
                'message' => 'A/B test updated successfully',
            ]);
});

test('it can delete ab test', function () {
    // Create a test first
    $createResponse = $this->postJson('/api/ab-tests', [
        'name' => 'Test to Delete',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $testId = $createResponse->json('data.id');

    $response = $this->deleteJson("/api/ab-tests/{$testId}");

    $response->assertStatus(200)
            ->assertJson([
                'message' => 'A/B test deleted successfully',
            ]);

    // Verify it's gone
    $this->getJson("/api/ab-tests/{$testId}")->assertStatus(404);
});

test('it can get ab test results', function () {
    // Create a test first
    $createResponse = $this->postJson('/api/ab-tests', [
        'name' => 'Results Test',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ]);

    $testId = $createResponse->json('data.id');

    $response = $this->getJson("/api/ab-tests/{$testId}/results");

    $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'test' => [
                        'id',
                        'name',
                        'goal_event',
                    ],
                    'variants',
                    'significance',
                ],
            ]);
});

test('it requires authentication', function () {
    $this->withoutMiddleware();

    $testData = [
        'name' => 'Auth Test',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ];

    $response = $this->postJson('/api/ab-tests', $testData);

    $response->assertStatus(401);
});

test('it requires admin role for ab test operations', function () {
    // Create user without admin role
    $regularUser = User::factory()->create();
    Sanctum::actingAs($regularUser);

    $testData = [
        'name' => 'Role Test',
        'variants' => [
            ['name' => 'Variant A', 'weight' => 50],
            ['name' => 'Variant B', 'weight' => 50],
        ],
        'goal_event' => 'click',
    ];

    $response = $this->postJson('/api/ab-tests', $testData);

    $response->assertStatus(403);
});

test('it validates variant structure', function () {
    $invalidVariants = [
        'name' => 'Invalid Variants Test',
        'variants' => [
            [
                'name' => '', // Empty name
                'weight' => 150, // Invalid weight > 100
            ],
            [
                // Missing name
                'weight' => 50,
            ],
        ],
        'goal_event' => 'click',
    ];

    $response = $this->postJson('/api/ab-tests', $invalidVariants);

    $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'variants.0.name',
                'variants.0.weight',
                'variants.1.name',
            ]);
});

test('it enforces minimum two variants', function () {
    $singleVariant = [
        'name' => 'Single Variant Test',
        'variants' => [
            [
                'name' => 'Only Variant',
                'weight' => 100,
            ],
        ],
        'goal_event' => 'click',
    ];

    $response = $this->postJson('/api/ab-tests', $singleVariant);

    $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
});