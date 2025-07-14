<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
    Route::post('applications/{application}/hire', [\App\Http\Controllers\JobApplicationController::class, 'hire'])->name('applications.hire');
    Route::get('my-applications', [\App\Http\Controllers\JobApplicationController::class, 'myApplications'])->name('my.applications');

    // Recommendations
    Route::post('jobs/{job}/recommend', [\App\Http\Controllers\RecommendationController::class, 'store'])->name('jobs.recommend');

    // Super Admin Management
    Route::resource('super-admins', \App\Http\Controllers\SuperAdminController::class)->except(['show', 'edit', 'update']);

    // Merge Records
    Route::get('merge', [\App\Http\Controllers\MergeController::class, 'index'])->name('merge.index');
    Route::post('merge', [\App\Http\Controllers\MergeController::class, 'merge'])->name('merge.store');

    // Tutor Management
    Route::resource('tutors', \App\Http\Controllers\TutorController::class);

    // Course Import
    Route::get('courses/import', [\App\Http\Controllers\CourseImportController::class, 'create'])->name('courses.import.create');
    Route::post('courses/import', [\App\Http\Controllers\CourseImportController::class, 'store'])->name('courses.import.store');

    // Institution Details
    Route::get('institution', [\App\Http\Controllers\InstitutionDetailsController::class, 'edit'])->name('institution.edit');
    Route::patch('institution', [\App\Http\Controllers\InstitutionDetailsController::class, 'update'])->name('institution.update');

    // Education History
    Route::resource('education', \App\Http\Controllers\EducationHistoryController::class)->except(['show', 'edit', 'update']);

    // Assistance Requests
    Route::resource('assistance', \App\Http\Controllers\AssistanceRequestController::class)->except(['show', 'edit', 'update', 'destroy']);

    // Company Approval
    Route::get('companies', [\App\Http\Controllers\CompanyApprovalController::class, 'index'])->name('companies.index');
    Route::post('companies/{employer}/approve', [\App\Http\Controllers\CompanyApprovalController::class, 'approve'])->name('companies.approve');
    Route::delete('companies/{employer}/reject', [\App\Http\Controllers\CompanyApprovalController::class, 'reject'])->name('companies.reject');

    // Graduate Search
    Route::get('graduates/search', [\App\Http\Controllers\GraduateSearchController::class, 'index'])->name('graduates.search');
});

Route::get('employer/register', [\App\Http\Controllers\EmployerController::class, 'create'])->name('employer.register');
Route::post('employer/register', [\App\Http\Controllers\EmployerController::class, 'store']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
