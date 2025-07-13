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

    // Graduate Management
    Route::resource('graduates', \App\Http\Controllers\GraduateController::class);
    Route::get('graduates/import', [\App\Http\Controllers\GraduateImportController::class, 'create'])->name('graduates.import.create');
    Route::post('graduates/import', [\App\Http\Controllers\GraduateImportController::class, 'store'])->name('graduates.import.store');

    // Job Management
    Route::resource('jobs', \App\Http\Controllers\JobController::class);

    // Graduate Profile
    Route::get('profile', [\App\Http\Controllers\GraduateProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [\App\Http\Controllers\GraduateProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [\App\Http\Controllers\GraduateProfileController::class, 'update'])->name('profile.update');

    // Public Job Listing
    Route::get('jobs', [\App\Http\Controllers\JobListController::class, 'index'])->name('jobs.public.index');

    // Job Applications
    Route::get('jobs/{job}/applications', [\App\Http\Controllers\JobApplicationController::class, 'index'])->name('jobs.applications.index');
    Route::post('jobs/{job}/apply', [\App\Http\Controllers\JobApplicationController::class, 'store'])->name('jobs.apply');
    Route::get('my-applications', [\App\Http\Controllers\JobApplicationController::class, 'myApplications'])->name('my.applications');
});

Route::get('employer/register', [\App\Http\Controllers\EmployerController::class, 'create'])->name('employer.register');
Route::post('employer/register', [\App\Http\Controllers\EmployerController::class, 'store']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
