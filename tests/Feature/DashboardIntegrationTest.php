<?php

use App\Models\User;

test('super admin dashboard loads', function () {
    $user = User::where('email', 'admin@system.com')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/super-admin/dashboard');
    $response->assertStatus(200);
});

test('institution admin dashboard loads', function () {
    $user = User::where('email', 'admin@tech-institute.edu')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/institution-admin/dashboard');
    $response->assertStatus(200);
});

test('employer dashboard loads', function () {
    $user = User::where('email', 'techcorp@company.com')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/employer/dashboard');
    $response->assertStatus(200);
});

test('graduate dashboard loads', function () {
    $user = User::where('email', 'john.smith@student.edu')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/graduate/dashboard');
    $response->assertStatus(200);
});

test('super admin analytics loads', function () {
    $user = User::where('email', 'admin@system.com')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/super-admin/analytics');
    $response->assertStatus(200);
});

test('super admin reports loads', function () {
    $user = User::where('email', 'admin@system.com')->first();
    expect($user)->not->toBeNull();

    $response = $this->actingAs($user)->get('/super-admin/reports');
    $response->assertStatus(200);
});
