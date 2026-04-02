<?php

declare(strict_types=1);

use App\Models\User;

it('returns authenticated user profile with resource contract', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/user/profile');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'full_name',
                'initials',
                'avatar_url',
                'roles',
                'permissions',
                'student_profile',
                'graduate_profile',
                'institution_profile',
                'current_tenant',
                'accessible_tenants',
            ],
        ]);
});

it('updates profile and returns resource contract', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'full_name',
                'initials',
                'avatar_url',
                'accessible_tenants',
            ],
        ]);
});
