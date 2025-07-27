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

// Super Admin Management (Central only - works on central domains)
Route::resource('super-admins', \App\Http\Controllers\SuperAdminController::class)->except(['show', 'edit', 'update']);

// Super Admin Dashboard (Central only - works on central domains)
Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('analytics', [\App\Http\Controllers\SuperAdminDashboardController::class, 'analytics'])->name('analytics');
    Route::get('institutions', [\App\Http\Controllers\SuperAdminDashboardController::class, 'institutions'])->name('institutions');
    Route::get('users', [\App\Http\Controllers\SuperAdminDashboardController::class, 'users'])->name('users');
    Route::get('employer-verification', [\App\Http\Controllers\SuperAdminDashboardController::class, 'employerVerification'])->name('employer-verification');
    Route::get('reports', [\App\Http\Controllers\SuperAdminDashboardController::class, 'reports'])->name('reports');
    Route::get('system-health', [\App\Http\Controllers\SuperAdminDashboardController::class, 'systemHealth'])->name('system-health');
    Route::post('reports/export', [\App\Http\Controllers\SuperAdminDashboardController::class, 'exportReport'])->name('reports.export');
});

// Security Routes (Super Admin Only - Central)
Route::middleware(['auth', 'role:super-admin'])->prefix('security')->name('security.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\SecurityController::class, 'dashboard'])->name('dashboard');
    Route::get('events', [\App\Http\Controllers\SecurityController::class, 'events'])->name('events');
    Route::post('events/{event}/resolve', [\App\Http\Controllers\SecurityController::class, 'resolveEvent'])->name('events.resolve');
    Route::get('data-access', [\App\Http\Controllers\SecurityController::class, 'dataAccessLogs'])->name('data-access');
    Route::get('failed-logins', [\App\Http\Controllers\SecurityController::class, 'failedLogins'])->name('failed-logins');
    Route::post('unblock-ip', [\App\Http\Controllers\SecurityController::class, 'unblockIp'])->name('unblock-ip');
    Route::get('sessions', [\App\Http\Controllers\SecurityController::class, 'activeSessions'])->name('sessions');
    Route::post('sessions/{session}/terminate', [\App\Http\Controllers\SecurityController::class, 'terminateSession'])->name('sessions.terminate');
    Route::get('report', [\App\Http\Controllers\SecurityController::class, 'securityReport'])->name('report');
    Route::get('system-health', [\App\Http\Controllers\SecurityController::class, 'systemHealth'])->name('system-health');
});

Route::middleware('auth')->group(function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('users.unsuspend');
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');

    // Role Management
    Route::resource('roles', RoleController::class);

    // Institution Management
    Route::resource('institutions', \App\Http\Controllers\InstitutionController::class);

    // Course Management
    Route::resource('courses', \App\Http\Controllers\CourseController::class);
    Route::get('courses/{course}/analytics', [\App\Http\Controllers\CourseController::class, 'analytics'])->name('courses.analytics');
    Route::post('courses/{course}/statistics', [\App\Http\Controllers\CourseController::class, 'updateStatistics'])->name('courses.statistics.update');
    Route::get('courses/export', [\App\Http\Controllers\CourseController::class, 'export'])->name('courses.export');

    // Graduate Management
    Route::resource('graduates', \App\Http\Controllers\GraduateController::class);
    Route::patch('graduates/{graduate}/employment', [\App\Http\Controllers\GraduateController::class, 'updateEmployment'])->name('graduates.employment.update');
    Route::patch('graduates/{graduate}/privacy', [\App\Http\Controllers\GraduateController::class, 'updatePrivacySettings'])->name('graduates.privacy.update');
    Route::get('graduates/export', [\App\Http\Controllers\GraduateController::class, 'export'])->name('graduates.export');
    Route::get('graduates/export-fields', [\App\Http\Controllers\GraduateController::class, 'exportFields'])->name('graduates.export.fields');
    
    // Graduate Import/Export System
    Route::get('graduates/import/history', [\App\Http\Controllers\GraduateImportController::class, 'index'])->name('graduates.import.history');
    Route::get('graduates/import/template', [\App\Http\Controllers\GraduateImportController::class, 'template'])->name('graduates.import.template');
    Route::get('graduates/import', [\App\Http\Controllers\GraduateImportController::class, 'create'])->name('graduates.import.create');
    Route::post('graduates/import/preview', [\App\Http\Controllers\GraduateImportController::class, 'preview'])->name('graduates.import.preview');
    Route::post('graduates/import', [\App\Http\Controllers\GraduateImportController::class, 'store'])->name('graduates.import.store');
    Route::get('graduates/import/{importHistory}', [\App\Http\Controllers\GraduateImportController::class, 'show'])->name('graduates.import.show');
    Route::post('graduates/import/{importHistory}/rollback', [\App\Http\Controllers\GraduateImportController::class, 'rollback'])->name('graduates.import.rollback');

    // Job Management
    Route::resource('jobs', \App\Http\Controllers\JobController::class);
    Route::get('jobs/{job}/analytics', [\App\Http\Controllers\JobController::class, 'analytics'])->name('jobs.analytics');
    Route::post('jobs/{job}/pause', [\App\Http\Controllers\JobController::class, 'pause'])->name('jobs.pause');
    Route::post('jobs/{job}/resume', [\App\Http\Controllers\JobController::class, 'resume'])->name('jobs.resume');
    Route::post('jobs/{job}/mark-filled', [\App\Http\Controllers\JobController::class, 'markAsFilled'])->name('jobs.mark-filled');
    Route::patch('jobs/{job}/extend', [\App\Http\Controllers\JobController::class, 'extend'])->name('jobs.extend');
    Route::post('jobs/{job}/recommend', [\App\Http\Controllers\JobController::class, 'recommend'])->name('jobs.recommend');
    Route::post('jobs/{job}/renew', [\App\Http\Controllers\JobController::class, 'renew'])->name('jobs.renew');
    Route::post('jobs/{job}/duplicate', [\App\Http\Controllers\JobController::class, 'duplicate'])->name('jobs.duplicate');
    Route::post('jobs/{job}/auto-renew', [\App\Http\Controllers\JobController::class, 'autoRenew'])->name('jobs.auto-renew');
    Route::get('jobs/{job}/insights', [\App\Http\Controllers\JobController::class, 'getJobInsights'])->name('jobs.insights');
    Route::get('jobs/{job}/smart-recommendations', [\App\Http\Controllers\JobController::class, 'smartRecommendations'])->name('jobs.smart-recommendations');
    Route::post('jobs/bulk-action', [\App\Http\Controllers\JobController::class, 'bulkAction'])->name('jobs.bulk-action');

    // Job Approval (Admin)
    Route::get('admin/job-approval', [\App\Http\Controllers\JobApprovalController::class, 'index'])->name('admin.job-approval.index');
    Route::get('admin/job-approval/{job}', [\App\Http\Controllers\JobApprovalController::class, 'show'])->name('admin.job-approval.show');
    Route::post('admin/job-approval/{job}/approve', [\App\Http\Controllers\JobApprovalController::class, 'approve'])->name('admin.job-approval.approve');
    Route::post('admin/job-approval/{job}/reject', [\App\Http\Controllers\JobApprovalController::class, 'reject'])->name('admin.job-approval.reject');
    Route::post('admin/job-approval/bulk-approve', [\App\Http\Controllers\JobApprovalController::class, 'bulkApprove'])->name('admin.job-approval.bulk-approve');
    Route::post('admin/job-approval/bulk-reject', [\App\Http\Controllers\JobApprovalController::class, 'bulkReject'])->name('admin.job-approval.bulk-reject');

    // Graduate Profile
    Route::get('profile', [\App\Http\Controllers\GraduateProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [\App\Http\Controllers\GraduateProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [\App\Http\Controllers\GraduateProfileController::class, 'update'])->name('profile.update');

    // Institution Admin Dashboard
    Route::prefix('institution-admin')->name('institution-admin.')->middleware(['auth', 'role:institution-admin'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('analytics', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'analytics'])->name('analytics');
        Route::get('reports', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('staff', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'staffManagement'])->name('staff');
        Route::get('import-export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'importExportCenter'])->name('import-export');
        Route::post('reports/export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'exportReport'])->name('reports.export');
    });

    // Employer Dashboard (Protected)
    Route::middleware(['auth', 'role:employer'])->prefix('employer')->name('employer.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\EmployerDashboardController::class, 'index'])->name('dashboard');
        Route::get('jobs', [\App\Http\Controllers\EmployerDashboardController::class, 'jobManagement'])->name('jobs');
        Route::get('applications', [\App\Http\Controllers\EmployerDashboardController::class, 'applicationManagement'])->name('applications');
        Route::get('graduates/search', [\App\Http\Controllers\EmployerDashboardController::class, 'graduateSearch'])->name('graduates.search');
        Route::get('profile', [\App\Http\Controllers\EmployerDashboardController::class, 'companyProfile'])->name('profile');
        Route::get('analytics', [\App\Http\Controllers\EmployerDashboardController::class, 'analytics'])->name('analytics');
        Route::get('communications', [\App\Http\Controllers\EmployerDashboardController::class, 'communications'])->name('communications');
        Route::post('communications/start', [\App\Http\Controllers\EmployerDashboardController::class, 'startConversation'])->name('communications.start');
        Route::post('communications/{conversation}/send', [\App\Http\Controllers\EmployerDashboardController::class, 'sendMessage'])->name('communications.send');
        Route::post('communications/{conversation}/mark-read', [\App\Http\Controllers\EmployerDashboardController::class, 'markAsRead'])->name('communications.mark-read');
        Route::patch('communications/{conversation}/archive', [\App\Http\Controllers\EmployerDashboardController::class, 'archiveConversation'])->name('communications.archive');
        Route::patch('communications/{conversation}/block', [\App\Http\Controllers\EmployerDashboardController::class, 'blockCandidate'])->name('communications.block');
    });

    // Graduate Dashboard (Protected)
    Route::middleware(['auth', 'role:graduate'])->prefix('graduate')->name('graduate.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\GraduateDashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [\App\Http\Controllers\GraduateDashboardController::class, 'profile'])->name('profile');
        Route::get('jobs', [\App\Http\Controllers\GraduateDashboardController::class, 'jobBrowsing'])->name('jobs');
        Route::get('applications', [\App\Http\Controllers\GraduateDashboardController::class, 'applications'])->name('applications');
        Route::get('classmates', [\App\Http\Controllers\GraduateDashboardController::class, 'classmates'])->name('classmates');
        Route::get('career', [\App\Http\Controllers\GraduateDashboardController::class, 'careerProgress'])->name('career');
        Route::get('assistance', [\App\Http\Controllers\GraduateDashboardController::class, 'assistanceRequests'])->name('assistance');
        Route::post('assistance', [\App\Http\Controllers\GraduateDashboardController::class, 'submitAssistanceRequest'])->name('assistance.submit');
    });

    // Notification Routes (Protected)
    Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('preferences');
        Route::put('preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('unread', [\App\Http\Controllers\NotificationController::class, 'getUnread'])->name('unread');
        
        // Test route for development
        Route::post('test', [\App\Http\Controllers\NotificationController::class, 'testNotification'])->name('test');
    });

    // Two-Factor Authentication Routes (All authenticated users)
    Route::middleware(['auth'])->prefix('security')->name('security.')->group(function () {
        Route::get('two-factor/setup', [\App\Http\Controllers\SecurityController::class, 'twoFactorSetup'])->name('two-factor.setup');
        Route::post('two-factor/verify', [\App\Http\Controllers\SecurityController::class, 'twoFactorVerify'])->name('two-factor.verify');
        Route::post('two-factor/disable', [\App\Http\Controllers\SecurityController::class, 'twoFactorDisable'])->name('two-factor.disable');
    });
});

// Public routes
Route::get('/jobs', [\App\Http\Controllers\JobListController::class, 'publicIndex'])->name('jobs.public.index');
Route::get('/jobs/{job}', [\App\Http\Controllers\JobListController::class, 'publicShow'])->name('jobs.public.show');
Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/discussions', [\App\Http\Controllers\DiscussionController::class, 'index'])->name('discussions.index');
Route::get('/discussions/{discussion}', [\App\Http\Controllers\DiscussionController::class, 'show'])->name('discussions.show');

require __DIR__ . '/settings.php';
require __DIR__ . '/testing.php';
require __DIR__ . '/auth.php';
