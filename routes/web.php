<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\HomepageController::class, 'index'])->name('home');

// PWA Offline Route
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Health Check Routes
Route::get('/health-check/homepage', [\App\Http\Controllers\HealthCheckController::class, 'homepage'])->name('health-check.homepage');

// Monitoring Dashboard Routes (Admin only)
Route::middleware(['auth', 'role:super-admin'])->prefix('monitoring')->name('monitoring.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\MonitoringDashboardController::class, 'index'])->name('dashboard');

    // API endpoints for monitoring dashboard
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('data', [\App\Http\Controllers\MonitoringDashboardController::class, 'data'])->name('data');
        Route::get('uptime', [\App\Http\Controllers\MonitoringDashboardController::class, 'uptime'])->name('uptime');
        Route::get('conversion-metrics', [\App\Http\Controllers\MonitoringDashboardController::class, 'conversionMetrics'])->name('conversion-metrics');
        Route::get('performance-metrics', [\App\Http\Controllers\MonitoringDashboardController::class, 'performanceMetrics'])->name('performance-metrics');
        Route::get('error-logs', [\App\Http\Controllers\MonitoringDashboardController::class, 'errorLogs'])->name('error-logs');
        Route::get('system-health', [\App\Http\Controllers\MonitoringDashboardController::class, 'systemHealth'])->name('system-health');
        Route::post('record-metric', [\App\Http\Controllers\MonitoringDashboardController::class, 'recordMetric'])->name('record-metric');
        Route::post('test-alert', [\App\Http\Controllers\MonitoringDashboardController::class, 'testAlert'])->name('test-alert');
    });
});

// Homepage Enhancement Routes
Route::get('/homepage', [\App\Http\Controllers\HomepageController::class, 'index'])->name('homepage.index');
Route::get('/homepage/institutional', [\App\Http\Controllers\HomepageController::class, 'institutional'])->name('homepage.institutional');

// Homepage CTA and Conversion Tracking
Route::post('/homepage/track-cta', [\App\Http\Controllers\HomepageController::class, 'trackCTAClick'])->name('homepage.track-cta');
Route::post('/homepage/track-conversion', [\App\Http\Controllers\HomepageController::class, 'trackConversion'])->name('homepage.track-conversion');
Route::get('/homepage/ab-test-results/{testId}', [\App\Http\Controllers\HomepageController::class, 'getABTestResults'])->name('homepage.ab-test-results');

// Homepage API Routes for dynamic content
Route::prefix('api/homepage')->name('api.homepage.')->group(function () {
    Route::get('statistics', [\App\Http\Controllers\Api\HomepageController::class, 'getStatistics'])->name('statistics');
    Route::get('testimonials', [\App\Http\Controllers\Api\HomepageController::class, 'getTestimonials'])->name('testimonials');
    Route::get('trust-badges', [\App\Http\Controllers\Api\HomepageController::class, 'getTrustBadges'])->name('trust-badges');
    Route::get('platform-preview', [\App\Http\Controllers\Api\HomepageController::class, 'getPlatformPreview'])->name('platform-preview');
    Route::get('success-stories', [\App\Http\Controllers\Api\HomepageController::class, 'getSuccessStories'])->name('success-stories');
    Route::get('features', [\App\Http\Controllers\Api\HomepageController::class, 'getFeatures'])->name('features');
    Route::get('branded-apps', [\App\Http\Controllers\Api\HomepageController::class, 'getBrandedAppsData'])->name('branded-apps');
    Route::post('calculator', [\App\Http\Controllers\Api\HomepageController::class, 'calculateValue'])->name('calculator');

    // Career Calculator Routes
    Route::post('calculator/calculate', [\App\Http\Controllers\Api\CareerCalculatorController::class, 'calculate'])->name('calculator.calculate');
    Route::post('calculator/email-report', [\App\Http\Controllers\Api\CareerCalculatorController::class, 'emailReport'])->name('calculator.email-report');
    Route::get('calculator/benchmarks', [\App\Http\Controllers\Api\CareerCalculatorController::class, 'benchmarks'])->name('calculator.benchmarks');

    // Pricing Routes
    Route::get('pricing/plans', [\App\Http\Controllers\Api\PricingController::class, 'getPlans'])->name('pricing.plans');
    Route::get('pricing/feature-comparison', [\App\Http\Controllers\Api\PricingController::class, 'getFeatureComparison'])->name('pricing.feature-comparison');
    Route::post('pricing/track-interaction', [\App\Http\Controllers\Api\PricingController::class, 'trackInteraction'])->name('pricing.track-interaction');
    Route::get('pricing/statistics', [\App\Http\Controllers\Api\PricingController::class, 'getStatistics'])->name('pricing.statistics');
    Route::post('demo-request', [\App\Http\Controllers\Api\HomepageController::class, 'requestDemo'])->name('demo-request');
    Route::post('trial-signup', [\App\Http\Controllers\Api\HomepageController::class, 'trialSignup'])->name('trial-signup');
    Route::post('lead-capture', [\App\Http\Controllers\Api\HomepageController::class, 'captureLeads'])->name('lead-capture');

    // Lead capture statistics
    Route::get('lead-statistics', [\App\Http\Controllers\Api\HomepageController::class, 'getLeadStatistics'])->name('lead-statistics');

    // Audience Detection and Personalization Routes
    Route::get('detect-audience', [\App\Http\Controllers\Api\HomepageController::class, 'detectAudience'])->name('detect-audience');
    Route::get('personalized-content', [\App\Http\Controllers\Api\HomepageController::class, 'getPersonalizedContent'])->name('personalized-content');
    Route::post('audience-preference', [\App\Http\Controllers\Api\HomepageController::class, 'storeAudiencePreference'])->name('store-audience-preference');
    Route::get('audience-preference', [\App\Http\Controllers\Api\HomepageController::class, 'getAudiencePreference'])->name('get-audience-preference');

    // A/B Testing Routes
    Route::get('content-variations', [\App\Http\Controllers\Api\HomepageController::class, 'getContentVariations'])->name('content-variations');
    Route::get('ab-test-variant', [\App\Http\Controllers\Api\HomepageController::class, 'getABTestVariant'])->name('ab-test-variant');
    Route::post('ab-test-conversion', [\App\Http\Controllers\Api\HomepageController::class, 'trackABTestConversion'])->name('ab-test-conversion');
    Route::get('active-ab-tests', [\App\Http\Controllers\Api\HomepageController::class, 'getActiveABTests'])->name('active-ab-tests');
    Route::get('ab-test-results/{testId}', [\App\Http\Controllers\Api\HomepageController::class, 'getABTestResults'])->name('ab-test-results');

    // Enterprise Metrics and ROI Routes
    Route::get('enterprise-metrics', [\App\Http\Controllers\Api\HomepageController::class, 'getEnterpriseMetrics'])->name('enterprise-metrics');
    Route::get('institutional-comparison', [\App\Http\Controllers\Api\HomepageController::class, 'getInstitutionalComparison'])->name('institutional-comparison');
    Route::get('implementation-timeline', [\App\Http\Controllers\Api\HomepageController::class, 'getImplementationTimeline'])->name('implementation-timeline');
    Route::get('success-metrics-tracking', [\App\Http\Controllers\Api\HomepageController::class, 'getSuccessMetricsTracking'])->name('success-metrics-tracking');

    // Content Management Routes
    Route::get('content-config', [\App\Http\Controllers\Api\HomepageController::class, 'getContentManagementConfig'])->name('content-config');
    Route::get('analytics', [\App\Http\Controllers\Api\HomepageController::class, 'getPersonalizationAnalytics'])->name('analytics');
    Route::delete('cache', [\App\Http\Controllers\Api\HomepageController::class, 'clearPersonalizationCache'])->name('clear-cache');
});

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
    Route::get('content', [\App\Http\Controllers\SuperAdminDashboardController::class, 'content'])->name('content');
    Route::get('activity', [\App\Http\Controllers\SuperAdminDashboardController::class, 'activity'])->name('activity');
    Route::get('database', [\App\Http\Controllers\SuperAdminDashboardController::class, 'database'])->name('database');
    Route::get('performance', [\App\Http\Controllers\SuperAdminDashboardController::class, 'performance'])->name('performance');
    Route::get('notifications', [\App\Http\Controllers\SuperAdminDashboardController::class, 'notifications'])->name('notifications');
    Route::get('settings', [\App\Http\Controllers\SuperAdminDashboardController::class, 'settings'])->name('settings');
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

// Social Authentication Routes
Route::prefix('auth')->name('social.')->group(function () {
    Route::get('{provider}', [\App\Http\Controllers\SocialAuthController::class, 'redirectToProvider'])->name('redirect');
    Route::get('{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'handleProviderCallback'])->name('callback');

    Route::middleware('auth')->group(function () {
        Route::get('link/{provider}', [\App\Http\Controllers\SocialAuthController::class, 'linkProfile'])->name('link');
        Route::delete('unlink/{profileId}', [\App\Http\Controllers\SocialAuthController::class, 'unlinkProfile'])->name('unlink');
        Route::get('profiles', [\App\Http\Controllers\SocialAuthController::class, 'showLinkingPage'])->name('profiles');
    });
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

    // Course Import/Export System
    Route::get('courses/import', [\App\Http\Controllers\CourseImportController::class, 'create'])->name('courses.import.create');
    Route::post('courses/import', [\App\Http\Controllers\CourseImportController::class, 'store'])->name('courses.import.store');

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

        // Graduate Management for Institution Admin
        Route::get('graduates', [\App\Http\Controllers\GraduateController::class, 'index'])->name('graduates.index');
        Route::get('graduates/create', [\App\Http\Controllers\GraduateController::class, 'create'])->name('graduates.create');
        Route::post('graduates', [\App\Http\Controllers\GraduateController::class, 'store'])->name('graduates.store');
        Route::get('graduates/{graduate}', [\App\Http\Controllers\GraduateController::class, 'show'])->name('graduates.show');
        Route::get('graduates/{graduate}/edit', [\App\Http\Controllers\GraduateController::class, 'edit'])->name('graduates.edit');
        Route::put('graduates/{graduate}', [\App\Http\Controllers\GraduateController::class, 'update'])->name('graduates.update');
        Route::delete('graduates/{graduate}', [\App\Http\Controllers\GraduateController::class, 'destroy'])->name('graduates.destroy');

        // Course Management for Institution Admin
        Route::get('courses', [\App\Http\Controllers\CourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [\App\Http\Controllers\CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [\App\Http\Controllers\CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}', [\App\Http\Controllers\CourseController::class, 'show'])->name('courses.show');
        Route::get('courses/{course}/edit', [\App\Http\Controllers\CourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [\App\Http\Controllers\CourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [\App\Http\Controllers\CourseController::class, 'destroy'])->name('courses.destroy');
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

// Social Features Routes
Route::middleware(['auth'])->prefix('social')->name('social.')->group(function () {
    Route::get('timeline', [\App\Http\Controllers\SocialController::class, 'timeline'])->name('timeline');
    Route::get('posts/create', [\App\Http\Controllers\SocialController::class, 'createPost'])->name('posts.create');
    Route::get('circles', [\App\Http\Controllers\SocialController::class, 'circles'])->name('circles');
    Route::get('groups', [\App\Http\Controllers\SocialController::class, 'groups'])->name('groups');
});

// Alumni Network Routes
Route::middleware(['auth'])->prefix('alumni')->name('alumni.')->group(function () {
    Route::get('directory', [\App\Http\Controllers\AlumniController::class, 'directory'])->name('directory');
    Route::get('recommendations', [\App\Http\Controllers\AlumniController::class, 'recommendations'])->name('recommendations');
    Route::get('connections', [\App\Http\Controllers\AlumniController::class, 'connections'])->name('connections');
    Route::get('map', [\App\Http\Controllers\AlumniController::class, 'map'])->name('map');
});

// Career Services Routes
Route::middleware(['auth'])->prefix('career')->name('career.')->group(function () {
    Route::get('timeline', [\App\Http\Controllers\CareerController::class, 'timeline'])->name('timeline');
    Route::get('goals', [\App\Http\Controllers\CareerController::class, 'goals'])->name('goals');
    Route::get('mentorship', [\App\Http\Controllers\CareerController::class, 'mentorship'])->name('mentorship');
    Route::get('mentorship-hub', [\App\Http\Controllers\CareerController::class, 'mentorshipHub'])->name('mentorship-hub');
});

// Job Matching Routes
Route::middleware(['auth'])->prefix('jobs')->name('jobs.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\JobController::class, 'dashboard'])->name('dashboard');
    Route::get('recommendations', [\App\Http\Controllers\JobController::class, 'recommendations'])->name('recommendations');
    Route::get('saved', [\App\Http\Controllers\JobController::class, 'saved'])->name('saved');
});

// Events Routes
Route::middleware(['auth'])->prefix('events')->name('events.')->group(function () {
    Route::get('/', [\App\Http\Controllers\EventController::class, 'index'])->name('index');
    Route::get('discovery', [\App\Http\Controllers\EventController::class, 'discovery'])->name('discovery');
    Route::get('create', [\App\Http\Controllers\EventController::class, 'create'])->name('create');
    Route::get('my-events', [\App\Http\Controllers\EventController::class, 'myEvents'])->name('my-events');
});

// Success Stories Routes
Route::middleware(['auth'])->prefix('stories')->name('stories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuccessStoryController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\SuccessStoryController::class, 'create'])->name('create');
    Route::get('my-stories', [\App\Http\Controllers\SuccessStoryController::class, 'myStories'])->name('my-stories');
});

// What's New and Help Routes
Route::middleware(['auth'])->group(function () {
    Route::get('whats-new', function () {
        return Inertia::render('WhatsNew');
    })->name('whats-new');

    Route::get('help', function () {
        return Inertia::render('Help');
    })->name('help');
});

// Student-specific routes
Route::prefix('students')->name('students.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('stories/discovery', [\App\Http\Controllers\StudentController::class, 'storiesDiscovery'])->name('stories.discovery');
    Route::get('mentorship/hub', [\App\Http\Controllers\StudentController::class, 'mentorshipHub'])->name('mentorship.hub');
    Route::get('mentors/browse', [\App\Http\Controllers\StudentController::class, 'browseMentors'])->name('mentors.browse');
    Route::get('career-guidance', [\App\Http\Controllers\StudentController::class, 'careerGuidance'])->name('career-guidance');
    Route::get('resources', [\App\Http\Controllers\StudentController::class, 'resources'])->name('resources');
});

// Speaker Bureau routes
Route::prefix('speaker-bureau')->name('speaker-bureau.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\SpeakerBureauController::class, 'index'])->name('index');
    Route::get('speaker/{speaker}', [\App\Http\Controllers\SpeakerBureauController::class, 'show'])->name('speaker');
    Route::match(['get', 'post'], 'request/{speaker?}', [\App\Http\Controllers\SpeakerBureauController::class, 'request'])->name('request');
    Route::match(['get', 'post'], 'join', [\App\Http\Controllers\SpeakerBureauController::class, 'join'])->name('join');
    Route::get('events', [\App\Http\Controllers\SpeakerBureauController::class, 'events'])->name('events');
});

// API Routes for AJAX requests
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    // Connection API
    Route::post('connections/request', [\App\Http\Controllers\Api\ConnectionController::class, 'request'])->name('connections.request');
    Route::post('connections/{connection}/accept', [\App\Http\Controllers\Api\ConnectionController::class, 'accept'])->name('connections.accept');
    Route::post('connections/{connection}/decline', [\App\Http\Controllers\Api\ConnectionController::class, 'decline'])->name('connections.decline');
    Route::delete('connections/{connection}', [\App\Http\Controllers\Api\ConnectionController::class, 'remove'])->name('connections.remove');

    // Post API
    Route::post('posts', [\App\Http\Controllers\Api\PostController::class, 'store'])->name('posts.store');
    Route::post('posts/{post}/react', [\App\Http\Controllers\Api\PostController::class, 'react'])->name('posts.react');
    Route::post('posts/{post}/comments', [\App\Http\Controllers\Api\PostController::class, 'comment'])->name('posts.comment');

    // Job API
    Route::post('jobs/{job}/save', [\App\Http\Controllers\Api\JobController::class, 'save'])->name('jobs.save');
    Route::delete('jobs/{job}/unsave', [\App\Http\Controllers\Api\JobController::class, 'unsave'])->name('jobs.unsave');
    Route::post('jobs/{job}/apply', [\App\Http\Controllers\Api\JobController::class, 'apply'])->name('jobs.apply');

    // Career API
    Route::post('career/timeline', [\App\Http\Controllers\Api\CareerController::class, 'store'])->name('career.store');
    Route::put('career/timeline/{timeline}', [\App\Http\Controllers\Api\CareerController::class, 'update'])->name('career.update');
    Route::delete('career/timeline/{timeline}', [\App\Http\Controllers\Api\CareerController::class, 'destroy'])->name('career.destroy');
    Route::post('career/milestones', [\App\Http\Controllers\Api\CareerController::class, 'storeMilestone'])->name('career.milestones.store');
    Route::post('career/goals', [\App\Http\Controllers\Api\CareerController::class, 'storeGoal'])->name('career.goals.store');
    Route::put('career/goals/{goal}', [\App\Http\Controllers\Api\CareerController::class, 'updateGoal'])->name('career.goals.update');
    Route::delete('career/goals/{goal}', [\App\Http\Controllers\Api\CareerController::class, 'destroyGoal'])->name('career.goals.destroy');
    Route::post('career/goals/{goal}/complete', [\App\Http\Controllers\Api\CareerController::class, 'completeGoal'])->name('career.goals.complete');

    // Event API
    Route::post('events/{event}/register', [\App\Http\Controllers\Api\EventController::class, 'register'])->name('events.register');
    Route::delete('events/{event}/unregister', [\App\Http\Controllers\Api\EventController::class, 'unregister'])->name('events.unregister');
    Route::post('events/{event}/favorite', [\App\Http\Controllers\Api\EventController::class, 'favorite'])->name('events.favorite');
    Route::delete('events/{event}/unfavorite', [\App\Http\Controllers\Api\EventController::class, 'unfavorite'])->name('events.unfavorite');

    // Mentorship API
    Route::post('mentorship/become-mentor', [\App\Http\Controllers\Api\MentorshipController::class, 'becomeMentor'])->name('mentorship.become-mentor');
    Route::post('mentorship/request', [\App\Http\Controllers\Api\MentorshipController::class, 'requestMentorship'])->name('mentorship.request');
    Route::post('mentorship/{request}/accept', [\App\Http\Controllers\Api\MentorshipController::class, 'acceptRequest'])->name('mentorship.accept');
    Route::post('mentorship/{request}/decline', [\App\Http\Controllers\Api\MentorshipController::class, 'declineRequest'])->name('mentorship.decline');
    Route::post('mentorship/sessions', [\App\Http\Controllers\Api\MentorshipController::class, 'scheduleSession'])->name('mentorship.schedule-session');

    // Reunion API
    Route::post('reunions/{reunion}/rsvp', [\App\Http\Controllers\Api\ReunionController::class, 'rsvp'])->name('reunions.rsvp');
    Route::post('reunions/{reunion}/favorite', [\App\Http\Controllers\Api\ReunionController::class, 'favorite'])->name('reunions.favorite');
    Route::delete('reunions/{reunion}/unfavorite', [\App\Http\Controllers\Api\ReunionController::class, 'unfavorite'])->name('reunions.unfavorite');
    Route::post('reunions/{reunion}/memories', [\App\Http\Controllers\Api\ReunionController::class, 'addMemory'])->name('reunions.memories.add');

    // User Flow Integration API
    Route::get('dashboard/integrated-data', [\App\Http\Controllers\Api\UserFlowController::class, 'getDashboardData'])->name('dashboard.integrated-data');
    Route::get('jobs/network-recommendations', [\App\Http\Controllers\Api\UserFlowController::class, 'getNetworkJobRecommendations'])->name('jobs.network-recommendations');
    Route::get('alumni/referral-connections', [\App\Http\Controllers\Api\UserFlowController::class, 'getReferralConnections'])->name('alumni.referral-connections');
    Route::get('events/{event}/attendees', [\App\Http\Controllers\Api\UserFlowController::class, 'getEventAttendees'])->name('events.attendees');
    Route::get('mentors/suggestions', [\App\Http\Controllers\Api\UserFlowController::class, 'getMentorSuggestions'])->name('mentors.suggestions');
    Route::get('skills/suggestions', [\App\Http\Controllers\Api\UserFlowController::class, 'getSkillSuggestions'])->name('skills.suggestions');
    Route::get('social/suggested-content', [\App\Http\Controllers\Api\UserFlowController::class, 'getSuggestedSocialContent'])->name('social.suggested-content');

    // Additional integration endpoints
    Route::post('jobs/request-referral', [\App\Http\Controllers\Api\JobController::class, 'requestReferral'])->name('jobs.request-referral');
    Route::post('conversations/start', [\App\Http\Controllers\Api\ConnectionController::class, 'startConversation'])->name('conversations.start');
    Route::post('skills/add', [\App\Http\Controllers\Api\SkillsController::class, 'addSkill'])->name('skills.add');
    Route::post('skills/request-endorsement', [\App\Http\Controllers\Api\SkillsController::class, 'requestEndorsement'])->name('skills.request-endorsement');
    Route::post('events/{event}/feedback', [\App\Http\Controllers\Api\EventController::class, 'submitFeedback'])->name('events.feedback');

    // Onboarding API
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('state', [\App\Http\Controllers\Api\OnboardingController::class, 'getOnboardingState'])->name('state');
        Route::get('new-features', [\App\Http\Controllers\Api\OnboardingController::class, 'getNewFeatures'])->name('new-features');
        Route::get('profile-completion', [\App\Http\Controllers\Api\OnboardingController::class, 'getProfileCompletion'])->name('profile-completion');
        Route::get('whats-new', [\App\Http\Controllers\Api\OnboardingController::class, 'getWhatsNewUpdates'])->name('whats-new');
        Route::post('complete', [\App\Http\Controllers\Api\OnboardingController::class, 'completeOnboarding'])->name('complete');
        Route::post('skip', [\App\Http\Controllers\Api\OnboardingController::class, 'skipOnboarding'])->name('skip');
        Route::post('feature-explored', [\App\Http\Controllers\Api\OnboardingController::class, 'markFeatureExplored'])->name('feature-explored');
        Route::post('feature-discovery-viewed', [\App\Http\Controllers\Api\OnboardingController::class, 'markFeatureDiscoveryViewed'])->name('feature-discovery-viewed');
        Route::post('dismiss-prompt', [\App\Http\Controllers\Api\OnboardingController::class, 'dismissPrompt'])->name('dismiss-prompt');
        Route::post('whats-new-viewed', [\App\Http\Controllers\Api\OnboardingController::class, 'markWhatsNewViewed'])->name('whats-new-viewed');
        Route::put('preferences', [\App\Http\Controllers\Api\OnboardingController::class, 'updatePreferences'])->name('preferences');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/testing.php';
require __DIR__.'/user-flows.php';
require __DIR__.'/auth.php';
// Fundraising Campaign Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/campaigns', function () {
        return Inertia::render('Fundraising/CampaignIndex');
    })->name('campaigns.index');

    Route::get('/campaigns/{campaign}', function (\App\Models\FundraisingCampaign $campaign) {
        $campaign->load(['creator', 'institution', 'donations', 'updates', 'peerFundraisers']);

        return Inertia::render('Fundraising/CampaignShow', [
            'campaign' => $campaign,
        ]);
    })->name('campaigns.show');

    Route::get('/peer-fundraisers/{peerFundraiser}', function (\App\Models\PeerFundraiser $peerFundraiser) {
        $peerFundraiser->load(['campaign', 'user', 'donations']);

        return Inertia::render('Fundraising/PeerFundraiserShow', [
            'peerFundraiser' => $peerFundraiser,
        ]);
    })->name('peer-fundraiser.show');
});

// Scholarship Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/scholarships', function () {
        return Inertia::render('Scholarships/Index');
    })->name('scholarships.index');

    Route::get('/scholarships/{scholarship}', function (\App\Models\Scholarship $scholarship) {
        $scholarship->load(['institution', 'applications', 'recipients']);

        return Inertia::render('Scholarships/Show', [
            'scholarship' => $scholarship,
        ]);
    })->name('scholarships.show');

    Route::get('/scholarships/{scholarship}/apply', function (\App\Models\Scholarship $scholarship) {
        return Inertia::render('Scholarships/Apply', [
            'scholarship' => $scholarship,
        ]);
    })->name('scholarships.apply');
});

// Achievement and Recognition Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/achievements', function () {
        return Inertia::render('Achievements/Index');
    })->name('achievements.index');

    Route::get('/achievements/{achievement}', function (\App\Models\Achievement $achievement) {
        $achievement->load(['user', 'institution', 'recognitions']);

        return Inertia::render('Achievements/Show', [
            'achievement' => $achievement,
        ]);
    })->name('achievements.show');

    Route::get('/leaderboard', function () {
        return Inertia::render('Achievements/Leaderboard');
    })->name('achievements.leaderboard');
});

// Global Search API
Route::middleware(['auth'])->group(function () {
    Route::get('/api/search/global', function (Request $request) {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => false, 'message' => 'Query too short']);
        }

        $results = [];

        // Search Alumni
        $alumni = \App\Models\User::where('role', 'alumni')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('company', 'LIKE', "%{$query}%")
                    ->orWhere('job_title', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'alumni',
                    'title' => $user->name,
                    'subtitle' => $user->job_title.' at '.$user->company,
                    'url' => "/alumni/profile/{$user->id}",
                ];
            });

        // Search Jobs
        $jobs = \App\Models\Job::where('title', 'LIKE', "%{$query}%")
            ->orWhere('company', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'type' => 'job',
                    'title' => $job->title,
                    'subtitle' => $job->company.' • '.$job->location,
                    'url' => "/jobs/{$job->id}",
                ];
            });

        // Search Events
        $events = \App\Models\Event::where('title', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orWhere('location', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => 'event',
                    'title' => $event->title,
                    'subtitle' => $event->location.' • '.$event->start_date->format('M j, Y'),
                    'url' => "/events/{$event->id}",
                ];
            });

        // Search Success Stories
        $stories = \App\Models\SuccessStory::where('title', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($story) {
                return [
                    'id' => $story->id,
                    'type' => 'story',
                    'title' => $story->title,
                    'subtitle' => 'By '.$story->user->name,
                    'url' => "/stories/{$story->id}",
                ];
            });

        $results = collect()
            ->merge($alumni)
            ->merge($jobs)
            ->merge($events)
            ->merge($stories)
            ->take(20)
            ->values();

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => $results->count(),
        ]);
    });

    Route::get('/api/ping', function () {
        return response()->json(['status' => 'ok']);
    });
});

// Homepage Content Management Routes (Admin)
Route::middleware(['auth', 'role:super-admin|institution-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('homepage-content')->name('homepage-content.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HomepageContentController::class, 'index'])->name('index');
        Route::get('/content', [\App\Http\Controllers\Admin\HomepageContentController::class, 'getContent'])->name('content');
        Route::put('/', [\App\Http\Controllers\Admin\HomepageContentController::class, 'update'])->name('update');
        Route::post('/bulk-update', [\App\Http\Controllers\Admin\HomepageContentController::class, 'bulkUpdate'])->name('bulk-update');
        Route::post('/{contentId}/request-approval', [\App\Http\Controllers\Admin\HomepageContentController::class, 'requestApproval'])->name('request-approval');
        Route::post('/{contentId}/approve', [\App\Http\Controllers\Admin\HomepageContentController::class, 'approve'])->name('approve');
        Route::post('/{contentId}/reject', [\App\Http\Controllers\Admin\HomepageContentController::class, 'reject'])->name('reject');
        Route::post('/{contentId}/publish', [\App\Http\Controllers\Admin\HomepageContentController::class, 'publish'])->name('publish');
        Route::get('/{contentId}/history', [\App\Http\Controllers\Admin\HomepageContentController::class, 'history'])->name('history');
        Route::post('/{contentId}/revert', [\App\Http\Controllers\Admin\HomepageContentController::class, 'revert'])->name('revert');
        Route::post('/preview', [\App\Http\Controllers\Admin\HomepageContentController::class, 'preview'])->name('preview');
        Route::get('/export', [\App\Http\Controllers\Admin\HomepageContentController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Admin\HomepageContentController::class, 'import'])->name('import');
    });
});

// A/B Testing Management Routes (Admin)
Route::middleware(['auth', 'role:super-admin|institution-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ABTestController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ABTestController::class, 'store'])->name('store');
        Route::get('/{test}', [\App\Http\Controllers\ABTestController::class, 'show'])->name('show');
        Route::put('/{test}', [\App\Http\Controllers\ABTestController::class, 'update'])->name('update');
        Route::delete('/{test}', [\App\Http\Controllers\ABTestController::class, 'destroy'])->name('destroy');
        Route::post('/{test}/start', [\App\Http\Controllers\ABTestController::class, 'start'])->name('start');
        Route::post('/{test}/stop', [\App\Http\Controllers\ABTestController::class, 'stop'])->name('stop');
        Route::get('/{test}/results', [\App\Http\Controllers\ABTestController::class, 'results'])->name('results');
        Route::get('/{test}/export', [\App\Http\Controllers\ABTestController::class, 'export'])->name('export');
    });
});

// Lead Management Routes (Admin)
Route::middleware(['auth', 'role:super-admin|institution-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('lead-management')->name('lead-management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LeadManagementController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\LeadManagementController::class, 'store'])->name('store');
        Route::get('/{lead}', [\App\Http\Controllers\Admin\LeadManagementController::class, 'show'])->name('show');
        Route::put('/{lead}', [\App\Http\Controllers\Admin\LeadManagementController::class, 'update'])->name('update');
        Route::post('/{lead}/qualify', [\App\Http\Controllers\Admin\LeadManagementController::class, 'qualify'])->name('qualify');
        Route::post('/{lead}/follow-up', [\App\Http\Controllers\Admin\LeadManagementController::class, 'createFollowUp'])->name('follow-up');
        Route::post('/{lead}/activity', [\App\Http\Controllers\Admin\LeadManagementController::class, 'addActivity'])->name('activity');
        Route::post('/{lead}/behavior', [\App\Http\Controllers\Admin\LeadManagementController::class, 'updateBehavior'])->name('behavior');
        Route::get('/analytics/data', [\App\Http\Controllers\Admin\LeadManagementController::class, 'analytics'])->name('analytics');
        Route::post('/bulk-sync', [\App\Http\Controllers\Admin\LeadManagementController::class, 'bulkSync'])->name('bulk-sync');
        Route::get('/export', [\App\Http\Controllers\Admin\LeadManagementController::class, 'export'])->name('export');

        // Lead scoring rules
        Route::get('/scoring-rules', [\App\Http\Controllers\Admin\LeadManagementController::class, 'getScoringRules'])->name('scoring-rules');
        Route::post('/scoring-rules', [\App\Http\Controllers\Admin\LeadManagementController::class, 'storeScoringRule'])->name('scoring-rules.store');

        // CRM integrations
        Route::get('/crm-integrations', [\App\Http\Controllers\Admin\LeadManagementController::class, 'getCrmIntegrations'])->name('crm-integrations');
        Route::post('/crm-integrations', [\App\Http\Controllers\Admin\LeadManagementController::class, 'storeCrmIntegration'])->name('crm-integrations.store');
        Route::post('/crm-integrations/{integration}/test', [\App\Http\Controllers\Admin\LeadManagementController::class, 'testCrmConnection'])->name('crm-integrations.test');
    });
});

// Landing Page Builder Routes (Admin)
Route::middleware(['auth', 'role:super-admin|institution-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('landing-pages')->name('landing-pages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LandingPageController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\LandingPageController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\LandingPageController::class, 'store'])->name('store');
        Route::get('/{landingPage}', [\App\Http\Controllers\Admin\LandingPageController::class, 'show'])->name('show');
        Route::get('/{landingPage}/edit', [\App\Http\Controllers\Admin\LandingPageController::class, 'edit'])->name('edit');
        Route::put('/{landingPage}', [\App\Http\Controllers\Admin\LandingPageController::class, 'update'])->name('update');
        Route::post('/{landingPage}/publish', [\App\Http\Controllers\Admin\LandingPageController::class, 'publish'])->name('publish');
        Route::post('/{landingPage}/unpublish', [\App\Http\Controllers\Admin\LandingPageController::class, 'unpublish'])->name('unpublish');
        Route::post('/{landingPage}/duplicate', [\App\Http\Controllers\Admin\LandingPageController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{landingPage}', [\App\Http\Controllers\Admin\LandingPageController::class, 'destroy'])->name('destroy');
        Route::get('/{landingPage}/analytics', [\App\Http\Controllers\Admin\LandingPageController::class, 'analytics'])->name('analytics');

        // API endpoints for builder
        Route::get('/api/templates', [\App\Http\Controllers\Admin\LandingPageController::class, 'getTemplates'])->name('api.templates');
        Route::get('/api/components', [\App\Http\Controllers\Admin\LandingPageController::class, 'getComponents'])->name('api.components');
    });
});

// Public Landing Page Routes
Route::prefix('landing')->name('landing-page.')->group(function () {
    Route::get('/{slug}', [\App\Http\Controllers\LandingPagePublicController::class, 'show'])->name('show');
    Route::post('/{slug}/submit', [\App\Http\Controllers\LandingPagePublicController::class, 'submitForm'])->name('submit');
    Route::post('/{slug}/track', [\App\Http\Controllers\LandingPagePublicController::class, 'trackEvent'])->name('track');
});
// Analytics Dashboard Routes (Admin only)
Route::middleware(['auth', 'role:admin|super_admin'])->prefix('analytics')->name('analytics.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Analytics/Dashboard');
    })->name('dashboard');
    
    Route::get('career-outcomes', function () {
        return Inertia::render('Analytics/CareerOutcomes');
    })->name('career-outcomes');
});