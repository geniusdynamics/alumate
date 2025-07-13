<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // User Management
    Route::resource('users', UserController::class);

    // Role Management
    Route::resource('roles', RoleController::class);

    // Institution Management
    Route::resource('institutions', \App\Http\Controllers\InstitutionController::class);

    // Course Management
    Route::resource('courses', \App\Http\Controllers\CourseController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
