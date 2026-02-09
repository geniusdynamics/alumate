<?php

use App\Http\Controllers\InstitutionAdmin\AnalyticsController as InstitutionAdminAnalyticsController;
use App\Http\Controllers\InstitutionAdmin\SettingsController as InstitutionAdminSettingsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\HomepageController::class, 'index'])->name('home');

// Developer Documentation
Route::middleware('auth')->get('/developer/api-documentation', function () {
    return Inertia::render('Developer/ApiDocumentation');
})->name('developer.api-documentation');

// PWA Offline Route
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Health Check Routes
Route::get('/health-check/homepage', [\App\Http\Controllers\HealthCheckController::class, 'homepage'])->name('health-check.homepage');

// Legal Pages
Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('terms', [\App\Http\Controllers\LegalController::class, 'terms'])->name('terms');
    Route::get('privacy', [\App\Http\Controllers\LegalController::class, 'privacy'])->name('privacy');
    Route::get('cookies', [\App\Http\Controllers\LegalController::class, 'cookies'])->name('cookies');
    Route::get('dpa', [\App\Http\Controllers\LegalController::class, 'dpa'])->name('dpa');
    Route::get('acceptable-use', [\App\Http\Controllers\LegalController::class, 'acceptableUse'])->name('acceptable-use');
    Route::get('gdpr', [\App\Http\Controllers\LegalController::class, 'gdpr'])->name('gdpr');
    Route::get('ccpa', [\App\Http\Controllers\LegalController::class, 'ccpa'])->name('ccpa');
    Route::get('ferpa', [\App\Http\Controllers\LegalController::class, 'ferpa'])->name('ferpa');
    Route::get('cookie-settings', [\App\Http\Controllers\LegalController::class, 'cookieSettings'])->name('cookie-settings');
});

// Privacy & Data Routes (Authenticated)
Route::middleware('auth')->prefix('privacy')->name('privacy.')->group(function () {
    Route::post('export-data', [\App\Http\Controllers\LegalController::class, 'exportData'])->name('export-data');
    Route::post('delete-data', [\App\Http\Controllers\LegalController::class, 'deleteData'])->name('delete-data');
    Route::post('withdraw-consent', [\App\Http\Controllers\LegalController::class, 'withdrawConsent'])->name('withdraw-consent');
    Route::get('consent-status', [\App\Http\Controllers\LegalController::class, 'getConsentStatus'])->name('consent-status');
});

// Tenant Onboarding Routes
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TenantOnboardingController::class, 'index'])->name('index');
    Route::post('start', [\App\Http\Controllers\TenantOnboardingController::class, 'start'])->name('start');
    Route::get('progress', [\App\Http\Controllers\TenantOnboardingController::class, 'progress'])->name('progress');
    Route::post('step/{step}', [\App\Http\Controllers\TenantOnboardingController::class, 'saveStep'])->name('step.save');
    Route::post('step/{step}/skip', [\App\Http\Controllers\TenantOnboardingController::class, 'skipStep'])->name('step.skip');
    Route::post('go-to-step/{step}', [\App\Http\Controllers\TenantOnboardingController::class, 'goToStep'])->name('step.goto');
    Route::post('upload-logo', [\App\Http\Controllers\TenantOnboardingController::class, 'uploadLogo'])->name('upload-logo');
    Route::post('import-courses', [\App\Http\Controllers\TenantOnboardingController::class, 'importCourses'])->name('import-courses');
    Route::post('import-alumni', [\App\Http\Controllers\TenantOnboardingController::class, 'importAlumni'])->name('import-alumni');
    Route::post('complete', [\App\Http\Controllers\TenantOnboardingController::class, 'complete'])->name('complete');
    Route::post('abandon', [\App\Http\Controllers\TenantOnboardingController::class, 'abandon'])->name('abandon');
    Route::get('statistics', [\App\Http\Controllers\TenantOnboardingController::class, 'statistics'])->name('statistics');
});

// Subscription Management Routes
Route::middleware('auth')->prefix('subscription')->name('subscription.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('index');
    Route::get('plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('plans');
    Route::get('current', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('show');
    Route::post('/', [\App\Http\Controllers\SubscriptionController::class, 'store'])->name('store');
    Route::post('change-plan', [\App\Http\Controllers\SubscriptionController::class, 'changePlan'])->name('change-plan');
    Route::post('cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('resume', [\App\Http\Controllers\SubscriptionController::class, 'resume'])->name('resume');
    Route::post('payment-method', [\App\Http\Controllers\SubscriptionController::class, 'updatePaymentMethod'])->name('payment-method');
    Route::get('preview-change', [\App\Http\Controllers\SubscriptionController::class, 'previewChange'])->name('preview-change');
    Route::get('invoices', [\App\Http\Controllers\SubscriptionController::class, 'invoices'])->name('invoices');
    Route::get('invoices/{invoice}/download', [\App\Http\Controllers\SubscriptionController::class, 'downloadInvoice'])->name('invoices.download');
});

// Broadcasting Auth Route
Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastingController::class, 'auth'])
    ->middleware('auth')
    ->name('broadcasting.auth');

Route::get('/broadcasting/config', [\App\Http\Controllers\BroadcastingController::class, 'config'])
    ->name('broadcasting.config');

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

// Horizon Queue Monitoring Routes (Admin only)
Route::middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/horizon', function () {
        return redirect(config('horizon.path'));
    })->name('horizon');
});

// Homepage Enhancement Routes
Route::get('/homepage', [\App\Http\Controllers\HomepageController::class, 'index'])->name('homepage.index');
Route::get('/homepage/institutional', [\App\Http\Controllers\HomepageController::class, 'institutional'])->name('homepage.institutional');

// Homepage CTA and Conversion Tracking
Route::post('/homepage/track-cta', [\App\Http\Controllers\HomepageController::class, 'trackCTAClick'])->name('homepage.track-cta');
Route::post('/homepage/track-conversion', [\App\Http\Controllers\HomepageController::class, 'trackConversion'])->name('homepage.track-conversion');
Route::get('/homepage/ab-test-results/{testId}', [\App\Http\Controllers\HomepageController::class, 'getABTestResults'])->name('homepage.ab-test-results');

// Main dashboard route - redirects users based on their role
Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    // Redirect based on user role
    if ($user->hasRole('super-admin')) {
        return redirect()->route('super-admin.dashboard');
    } elseif ($user->hasRole('institution-admin')) {
        return redirect()->route('institution-admin.dashboard');
    } elseif ($user->hasRole('employer')) {
        return redirect()->route('employer.dashboard');
    } elseif ($user->hasRole('graduate') || $user->hasRole('alumni')) {
        return redirect()->route('graduate.dashboard');
    } else {
        // Default to general dashboard for other user types
        return Inertia::render('Dashboard');
    }
})->middleware(['auth'])->name('dashboard');

// Dashboard routes for different user types
Route::middleware(['auth'])->group(function () {
    // Super Admin Dashboard
    Route::middleware(['role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [\App\Http\Controllers\SuperAdminDashboardController::class, 'analytics'])->name('analytics');
        Route::get('/institutions', [\App\Http\Controllers\SuperAdminDashboardController::class, 'institutions'])->name('institutions');
        Route::get('/users', [\App\Http\Controllers\SuperAdminDashboardController::class, 'users'])->name('users');
        Route::get('/employer-verification', [\App\Http\Controllers\SuperAdminDashboardController::class, 'employerVerification'])->name('employer-verification');
        Route::get('/reports', [\App\Http\Controllers\SuperAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/system-health', [\App\Http\Controllers\SuperAdminDashboardController::class, 'systemHealth'])->name('system-health');
    });

    // Institution Admin Dashboard
    Route::middleware(['role:institution-admin'])->prefix('institution-admin')->name('institution-admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('dashboard');
    });

    // Graduate Dashboard
    Route::middleware(['role:graduate,alumni'])->prefix('graduate')->name('graduate.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GraduateDashboardController::class, 'index'])->name('dashboard');
    });

    // Employer Dashboard
    Route::middleware(['role:employer'])->prefix('employer')->name('employer.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\EmployerDashboardController::class, 'index'])->name('dashboard');
    });
});

require __DIR__ . '/auth.php';
