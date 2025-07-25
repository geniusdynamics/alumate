<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public routes
Route::get('/jobs', [\App\Http\Controllers\JobListController::class, 'publicIndex'])->name('jobs.public.index');
Route::get('/jobs/{job}', [\App\Http\Controllers\JobListController::class, 'publicShow'])->name('jobs.public.show');
Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/discussions', [\App\Http\Controllers\DiscussionController::class, 'index'])->name('discussions.index');
Route::get('/discussions/{discussion}', [\App\Http\Controllers\DiscussionController::class, 'show'])->name('discussions.show');

Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');

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

    // Public Job Listing
    Route::get('browse-jobs', [\App\Http\Controllers\JobListController::class, 'index'])->name('jobs.public.index');
    Route::get('browse-jobs/{job}', [\App\Http\Controllers\JobListController::class, 'show'])->name('jobs.public.show');
    Route::get('api/jobs/search', [\App\Http\Controllers\JobListController::class, 'search'])->name('jobs.search');

    // Job Applications
    Route::get('jobs/{job}/applications', [\App\Http\Controllers\JobApplicationController::class, 'index'])->name('jobs.applications.index');
    Route::get('jobs/{job}/applications/analytics', [\App\Http\Controllers\JobApplicationController::class, 'analytics'])->name('jobs.applications.analytics');
    Route::post('jobs/{job}/apply', [\App\Http\Controllers\JobApplicationController::class, 'store'])->name('jobs.apply');
    Route::get('applications/{application}', [\App\Http\Controllers\JobApplicationController::class, 'show'])->name('applications.show');
    Route::patch('applications/{application}/status', [\App\Http\Controllers\JobApplicationController::class, 'updateStatus'])->name('applications.status.update');
    Route::post('applications/{application}/interview', [\App\Http\Controllers\JobApplicationController::class, 'scheduleInterview'])->name('applications.interview.schedule');
    Route::post('applications/{application}/offer', [\App\Http\Controllers\JobApplicationController::class, 'makeOffer'])->name('applications.offer.make');
    Route::post('applications/{application}/offer/respond', [\App\Http\Controllers\JobApplicationController::class, 'respondToOffer'])->name('applications.offer.respond');
    Route::post('applications/{application}/reject', [\App\Http\Controllers\JobApplicationController::class, 'reject'])->name('applications.reject');
    Route::post('applications/{application}/flag', [\App\Http\Controllers\JobApplicationController::class, 'flag'])->name('applications.flag');
    Route::delete('applications/{application}/flag', [\App\Http\Controllers\JobApplicationController::class, 'unflag'])->name('applications.unflag');
    Route::post('jobs/{job}/applications/bulk', [\App\Http\Controllers\JobApplicationController::class, 'bulkAction'])->name('applications.bulk');
    Route::get('applications/{application}/resume', [\App\Http\Controllers\JobApplicationController::class, 'downloadResume'])->name('applications.resume.download');
    Route::get('applications/{application}/document/{documentIndex}', [\App\Http\Controllers\JobApplicationController::class, 'downloadDocument'])->name('applications.document.download');
    Route::get('my-applications', [\App\Http\Controllers\JobApplicationController::class, 'myApplications'])->name('my.applications');

    // Super Admin Management
    Route::resource('super-admins', \App\Http\Controllers\SuperAdminController::class)->except(['show', 'edit', 'update']);
    
    // Super Admin Dashboard
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

    // Institution Admin Dashboard
    Route::prefix('institution-admin')->name('institution-admin.')->middleware(['auth', 'role:institution-admin'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('analytics', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'analytics'])->name('analytics');
        Route::get('reports', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('staff', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'staffManagement'])->name('staff');
        Route::get('import-export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'importExportCenter'])->name('import-export');
        Route::post('reports/export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'exportReport'])->name('reports.export');
    });

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

    // Announcements
    Route::get('announcements/create', [\App\Http\Controllers\AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('announcements.store');

    // Messages
    Route::resource('messages', \App\Http\Controllers\MessageController::class);
    Route::post('messages/{message}/reply', [\App\Http\Controllers\MessageController::class, 'sendReply'])->name('messages.reply');
    Route::patch('messages/{message}/archive', [\App\Http\Controllers\MessageController::class, 'archive'])->name('messages.archive');
    Route::patch('messages/{message}/unarchive', [\App\Http\Controllers\MessageController::class, 'unarchive'])->name('messages.unarchive');
    Route::patch('messages/{message}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead'])->name('messages.read');

    // Discussions
    Route::resource('discussions', \App\Http\Controllers\DiscussionController::class);
    Route::post('discussions/{discussion}/replies', [\App\Http\Controllers\DiscussionController::class, 'storeReply'])->name('discussions.replies.store');
    Route::patch('discussions/{discussion}/replies/{reply}', [\App\Http\Controllers\DiscussionController::class, 'updateReply'])->name('discussions.replies.update');
    Route::delete('discussions/{discussion}/replies/{reply}', [\App\Http\Controllers\DiscussionController::class, 'destroyReply'])->name('discussions.replies.destroy');
    Route::post('discussions/{discussion}/like', [\App\Http\Controllers\DiscussionController::class, 'toggleLike'])->name('discussions.like');
    Route::post('discussions/{discussion}/replies/{reply}/like', [\App\Http\Controllers\DiscussionController::class, 'toggleReplyLike'])->name('discussions.replies.like');

    // Employer Ratings
    Route::resource('employer-ratings', \App\Http\Controllers\EmployerRatingController::class)->only(['store', 'update', 'destroy']);

    // Help Tickets
    Route::resource('help-tickets', \App\Http\Controllers\HelpTicketController::class);
    Route::post('help-tickets/{ticket}/responses', [\App\Http\Controllers\HelpTicketController::class, 'storeResponse'])->name('help-tickets.responses.store');
    Route::patch('help-tickets/{ticket}/status', [\App\Http\Controllers\HelpTicketController::class, 'updateStatus'])->name('help-tickets.status');

    // Search and Matching Routes
    Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
    Route::get('api/search/jobs', [\App\Http\Controllers\SearchController::class, 'searchJobs']);
    Route::get('api/search/graduates', [\App\Http\Controllers\SearchController::class, 'searchGraduates']);
    Route::get('api/search/courses', [\App\Http\Controllers\SearchController::class, 'searchCourses']);
    Route::get('api/search/recommendations', [\App\Http\Controllers\SearchController::class, 'getRecommendations']);
    Route::get('api/search/suggestions', [\App\Http\Controllers\SearchController::class, 'getSuggestions']);
    Route::post('api/search/save', [\App\Http\Controllers\SearchController::class, 'saveSearch']);
    Route::get('api/search/saved', [\App\Http\Controllers\SearchController::class, 'getSavedSearches']);
    Route::patch('api/search/saved/{savedSearch}', [\App\Http\Controllers\SearchController::class, 'updateSavedSearch']);
    Route::delete('api/search/saved/{savedSearch}', [\App\Http\Controllers\SearchController::class, 'deleteSavedSearch']);
    Route::post('api/search/saved/{savedSearch}/execute', [\App\Http\Controllers\SearchController::class, 'executeSavedSearch']);
    Route::get('api/search/analytics', [\App\Http\Controllers\SearchController::class, 'getSearchAnalytics']);

    // Analytics and Reporting Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('employment', [\App\Http\Controllers\AnalyticsController::class, 'employment'])->name('employment');
        Route::get('courses', [\App\Http\Controllers\AnalyticsController::class, 'courses'])->name('courses');
        Route::get('job-market', [\App\Http\Controllers\AnalyticsController::class, 'jobMarket'])->name('job-market');
        Route::get('kpis', [\App\Http\Controllers\AnalyticsController::class, 'kpis'])->name('kpis');
        Route::get('predictions', [\App\Http\Controllers\AnalyticsController::class, 'predictions'])->name('predictions');
        Route::get('reports', [\App\Http\Controllers\AnalyticsController::class, 'reports'])->name('reports');
        
        // Report Management
        Route::post('reports', [\App\Http\Controllers\AnalyticsController::class, 'createReport'])->name('reports.create');
        Route::post('reports/{report}/execute', [\App\Http\Controllers\AnalyticsController::class, 'executeReport'])->name('reports.execute');
        Route::post('reports/{report}/preview', [\App\Http\Controllers\AnalyticsController::class, 'reportPreview'])->name('reports.preview');
        
        // Data Export
        Route::post('export', [\App\Http\Controllers\AnalyticsController::class, 'exportData'])->name('export');
        
        // Analytics Operations
        Route::post('snapshots', [\App\Http\Controllers\AnalyticsController::class, 'generateSnapshots'])->name('generate-snapshots');
        Route::post('kpis/calculate', [\App\Http\Controllers\AnalyticsController::class, 'calculateKpis'])->name('calculate-kpis');
        Route::post('predictions/generate', [\App\Http\Controllers\AnalyticsController::class, 'generatePredictions'])->name('generate-predictions');
    });
});

// Employer Registration (Public)
Route::get('employer/register', [\App\Http\Controllers\EmployerController::class, 'create'])->name('employer.register');
Route::post('employer/register', [\App\Http\Controllers\EmployerController::class, 'store']);

// Employer Management (Protected)
Route::middleware(['auth'])->group(function () {
    Route::resource('employers', \App\Http\Controllers\EmployerController::class);
    Route::post('employers/{employer}/verify', [\App\Http\Controllers\EmployerController::class, 'verify'])->name('employers.verify');
    Route::post('employers/{employer}/reject', [\App\Http\Controllers\EmployerController::class, 'reject'])->name('employers.reject');
    Route::post('employers/{employer}/suspend', [\App\Http\Controllers\EmployerController::class, 'suspend'])->name('employers.suspend');
    Route::post('employers/{employer}/reactivate', [\App\Http\Controllers\EmployerController::class, 'reactivate'])->name('employers.reactivate');
    Route::post('employers/{employer}/verification', [\App\Http\Controllers\EmployerController::class, 'submitVerification'])->name('employers.verification.submit');
    Route::patch('employers/{employer}/subscription', [\App\Http\Controllers\EmployerController::class, 'updateSubscription'])->name('employers.subscription.update');
    Route::get('employers/export', [\App\Http\Controllers\EmployerController::class, 'export'])->name('employers.export');
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

// Security Routes (Super Admin Only)
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

// Two-Factor Authentication Routes (All authenticated users)
Route::middleware(['auth'])->prefix('security')->name('security.')->group(function () {
    Route::get('two-factor/setup', [\App\Http\Controllers\SecurityController::class, 'twoFactorSetup'])->name('two-factor.setup');
    Route::post('two-factor/verify', [\App\Http\Controllers\SecurityController::class, 'twoFactorVerify'])->name('two-factor.verify');
    Route::post('two-factor/disable', [\App\Http\Controllers\SecurityController::class, 'twoFactorDisable'])->name('two-factor.disable');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
