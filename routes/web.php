<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\HomepageController::class, 'index'])->name('home');

// Developer Documentation
Route::middleware('auth')->get('/developer/api-documentation', function () {
    return Inertia::render('Developer/ApiDocumentation');
})->name('developer.api-documentation');

// PWA Offline Route
Route::get('/offline', function () {
    return Inertia::render('Offline');
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
    if (! auth()->check()) {
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

// Profile route - redirects users based on their role
Route::get('/profile', function () {
    $user = auth()->user();

    if ($user->hasRole('employer')) {
        return redirect()->route('employer.profile');
    } elseif ($user->hasRole('institution-admin')) {
        return redirect()->route('institution-admin.institution.edit');
    } elseif ($user->hasRole('super-admin')) {
        return redirect()->route('super-admin.settings');
    }

    // Default to graduate profile
    return redirect()->route('graduate.profile');
})->middleware(['auth'])->name('profile.show');

// Settings route - redirects users based on their role
Route::get('/settings', function () {
    $user = auth()->user();

    if ($user->hasRole('super-admin')) {
        return redirect()->route('super-admin.settings');
    } elseif ($user->hasRole('institution-admin')) {
        return redirect()->route('institution-admin.settings.branding');
    }

    // Default to graduate profile
    return redirect()->route('graduate.profile');
})->middleware(['auth'])->name('settings.profile');

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
        Route::get('/content', [\App\Http\Controllers\SuperAdminDashboardController::class, 'content'])->name('content');
        Route::get('/activity', [\App\Http\Controllers\SuperAdminDashboardController::class, 'activity'])->name('activity');
        Route::get('/database', [\App\Http\Controllers\SuperAdminDashboardController::class, 'database'])->name('database');
        Route::get('/performance', [\App\Http\Controllers\SuperAdminDashboardController::class, 'performance'])->name('performance');
        Route::get('/notifications', [\App\Http\Controllers\SuperAdminDashboardController::class, 'notifications'])->name('notifications');
        Route::get('/settings', [\App\Http\Controllers\SuperAdminDashboardController::class, 'settings'])->name('settings');
    });

    // Institution Admin Dashboard
    Route::middleware(['role:institution-admin'])->prefix('institution-admin')->name('institution-admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'analytics'])->name('analytics');
        Route::get('/analytics/course-roi', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'courseRoi'])->name('analytics.course-roi');
        Route::get('/analytics/employer-engagement', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'employerEngagement'])->name('analytics.employer-engagement');
        Route::get('/analytics/community-health', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'communityHealth'])->name('analytics.community-health');
        Route::get('/reports', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'exportReport'])->name('reports.export');
        Route::get('/staff', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'staffManagement'])->name('staff');
        Route::get('/import-export', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'importExportCenter'])->name('import-export');

        // JSON API endpoints used by the axios-based analytics Vue pages
        Route::prefix('api/analytics')->name('api.analytics.')->group(function () {
            Route::get('/course-roi', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'courseRoiApi'])->name('course-roi');
            Route::get('/employer-engagement', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'employerEngagementApi'])->name('employer-engagement');
            Route::get('/community-health', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'communityHealthApi'])->name('community-health');
        });
    });

    // Graduate Dashboard
    Route::middleware(['role:graduate,alumni'])->prefix('graduate')->name('graduate.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GraduateDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\GraduateDashboardController::class, 'profile'])->name('profile');
        Route::get('/jobs', [\App\Http\Controllers\GraduateDashboardController::class, 'jobBrowsing'])->name('jobs');
        Route::get('/applications', [\App\Http\Controllers\GraduateDashboardController::class, 'applications'])->name('applications');
        Route::get('/classmates', [\App\Http\Controllers\GraduateDashboardController::class, 'classmates'])->name('classmates');
        Route::get('/career-progress', [\App\Http\Controllers\GraduateDashboardController::class, 'careerProgress'])->name('career-progress');
        Route::get('/assistance', [\App\Http\Controllers\GraduateDashboardController::class, 'assistanceRequests'])->name('assistance');
        Route::post('/assistance', [\App\Http\Controllers\GraduateDashboardController::class, 'submitAssistanceRequest'])->name('assistance.submit');
    });

    // Employer Dashboard
    Route::middleware(['role:employer'])->prefix('employer')->name('employer.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\EmployerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/jobs', [\App\Http\Controllers\EmployerDashboardController::class, 'jobManagement'])->name('jobs');
        Route::get('/applications', [\App\Http\Controllers\EmployerDashboardController::class, 'applicationManagement'])->name('applications');
        Route::get('/search-graduates', [\App\Http\Controllers\EmployerDashboardController::class, 'graduateSearch'])->name('search-graduates');
        Route::get('/profile', [\App\Http\Controllers\EmployerDashboardController::class, 'companyProfile'])->name('profile');
        Route::get('/analytics', [\App\Http\Controllers\EmployerDashboardController::class, 'analytics'])->name('analytics');
        Route::get('/communications', [\App\Http\Controllers\EmployerDashboardController::class, 'communications'])->name('communications');
    });

    // Jobs Routes
    Route::middleware(['auth'])->prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\JobController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [\App\Http\Controllers\JobController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\JobController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\JobController::class, 'store'])->name('store');
        Route::get('/{job}', [\App\Http\Controllers\JobController::class, 'show'])->name('show');
        Route::get('/{job}/edit', [\App\Http\Controllers\JobController::class, 'edit'])->name('edit');
        Route::put('/{job}', [\App\Http\Controllers\JobController::class, 'update'])->name('update');
        Route::delete('/{job}', [\App\Http\Controllers\JobController::class, 'destroy'])->name('destroy');
    });

    // Graduates Search Route (alias for employer.search-graduates)
    Route::middleware(['auth'])->get('/graduates/search', [\App\Http\Controllers\EmployerDashboardController::class, 'graduateSearch'])->name('graduates.search');

    // Career Routes
    Route::middleware(['auth'])->prefix('career')->name('career.')->group(function () {
        Route::get('/timeline', [\App\Http\Controllers\GraduateDashboardController::class, 'careerProgress'])->name('timeline');
        Route::get('/mentorship-hub', [\App\Http\Controllers\GraduateDashboardController::class, 'classmates'])->name('mentorship-hub');
    });

    // Social Routes
    Route::middleware(['auth'])->prefix('social')->name('social.')->group(function () {
        Route::get('/timeline', [\App\Http\Controllers\SocialController::class, 'index'])->name('timeline');
    });

    // Alumni Routes
    Route::middleware(['auth'])->prefix('alumni')->name('alumni.')->group(function () {
        Route::get('/directory', [\App\Http\Controllers\AlumniController::class, 'index'])->name('directory');
    });

    // Events Routes
    Route::middleware(['auth'])->prefix('events')->name('events.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EventController::class, 'index'])->name('discovery');
        Route::get('/discovery', [\App\Http\Controllers\EventController::class, 'index'])->name('index');
    });

    // Success Stories Routes
    Route::middleware(['auth'])->prefix('stories')->name('stories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SuccessStoryController::class, 'index'])->name('index');
    });

    // Scholarships Routes
    Route::middleware(['auth'])->prefix('scholarships')->name('scholarships.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ScholarshipController::class, 'index'])->name('index');
    });

    // Achievements Routes
    Route::middleware(['auth'])->prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AchievementController::class, 'index'])->name('index');
    });

    // Education Routes
    Route::middleware(['auth'])->prefix('education')->name('education.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EducationHistoryController::class, 'index'])->name('index');
    });

    // Assistance Routes
    Route::middleware(['auth'])->prefix('assistance')->name('assistance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GraduateDashboardController::class, 'assistanceRequests'])->name('index');
    });

    // Institution Admin Resource Routes
    Route::middleware(['auth', 'role:institution-admin'])->prefix('institution-admin')->name('institution-admin.')->group(function () {
        // Graduates Management
        Route::get('/graduates', [\App\Http\Controllers\GraduateController::class, 'index'])->name('graduates.index');
        Route::get('/graduates/create', [\App\Http\Controllers\GraduateController::class, 'create'])->name('graduates.create');

        // Courses Management
        Route::get('/courses', [\App\Http\Controllers\CourseController::class, 'index'])->name('courses.index');

        // Tutors Management
        Route::get('/tutors', [\App\Http\Controllers\TutorController::class, 'index'])->name('tutors.index');

        // Jobs Public
        Route::get('/jobs-public', [\App\Http\Controllers\JobController::class, 'index'])->name('jobs.public.index');

        // Companies Approval
        Route::get('/companies', [\App\Http\Controllers\CompanyApprovalController::class, 'index'])->name('companies.index');

        // User Management
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');

        // Role Management
        Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');

        // Settings
        Route::get('/settings/branding', [\App\Http\Controllers\InstitutionAdmin\SettingsController::class, 'branding'])->name('settings.branding');
        Route::get('/settings/integrations', [\App\Http\Controllers\InstitutionAdmin\SettingsController::class, 'integrations'])->name('settings.integrations');

        // Institution Edit
        Route::get('/institution/edit', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('institution.edit');
    });

    // Super Admin Resource Routes
    Route::middleware(['auth', 'role:super-admin'])->group(function () {
        // Institutions
        Route::get('/institutions', [\App\Http\Controllers\SuperAdminDashboardController::class, 'institutions'])->name('institutions.index');

        // Super Admins Management
        Route::get('/super-admins', [\App\Http\Controllers\UserController::class, 'index'])->name('super-admins.index');

        // Security Dashboard
        Route::get('/security/dashboard', [\App\Http\Controllers\SecurityController::class, 'index'])->name('security.dashboard');
    });

    // Campaigns/Fundraising Routes
    Route::middleware(['auth'])->prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('index');
    });

    // Merge Routes
    Route::middleware(['auth'])->prefix('merge')->name('merge.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InstitutionAdminDashboardController::class, 'index'])->name('index');
    });

    // Template System Routes
    Route::middleware(['auth'])->prefix('templates')->name('templates.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('TemplateSystem/Library');
        })->name('index');
        Route::get('/create', function () {
            return Inertia::render('TemplateSystem/Create');
        })->name('create');
        Route::get('/{id}/edit', function () {
            return Inertia::render('TemplateSystem/Edit');
        })->name('edit');
        Route::get('/{id}/customize', function () {
            return Inertia::render('TemplateSystem/Customize');
        })->name('customize');
    });

    // Brand Management Routes
    Route::middleware(['auth'])->prefix('brand')->name('brand.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Brand/Manager');
        })->name('index');
        Route::get('/{section?}', function () {
            return Inertia::render('Brand/Manager');
        })->name('section');
    });

    // Landing Page Routes
    Route::middleware(['auth'])->prefix('landing-pages')->name('landing-pages.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('LandingPages/Index');
        })->name('index');
        Route::get('/create', function () {
            return Inertia::render('LandingPages/Create');
        })->name('create');
        Route::get('/{id}/edit', function () {
            return Inertia::render('LandingPages/Edit');
        })->name('edit');
        Route::get('/{id}/publish', function () {
            return Inertia::render('LandingPages/Publish');
        })->name('publish');
    });

    // Analytics Dashboard Routes
    Route::middleware(['auth'])->prefix('analytics/dashboard')->name('analytics.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Analytics/Dashboard');
        })->name('dashboard');
    });

    // A/B Testing Routes
    Route::middleware(['auth'])->prefix('ab-tests')->name('ab-tests.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('ABTests/Manager');
        })->name('index');
    });
});

require __DIR__.'/auth.php';
