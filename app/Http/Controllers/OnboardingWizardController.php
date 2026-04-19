<?php

namespace App\Http\Controllers;

use App\Models\TenantOnboarding;
use App\Services\TenantOnboardingService;
use Inertia\Inertia;

class OnboardingWizardController extends Controller
{
    protected TenantOnboardingService $onboardingService;

    public function __construct(TenantOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Display the onboarding wizard index page.
     */
    public function index()
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not found');
        }

        // Check if tenant has an active onboarding
        $onboarding = TenantOnboarding::forTenant($tenant->id)
            ->inProgress()
            ->first();

        // If no active onboarding, check if onboarding is needed
        if (! $onboarding) {
            // Check if tenant setup is complete
            $isSetupComplete = $this->checkTenantSetupComplete($tenant);

            if ($isSetupComplete) {
                return redirect()->route('dashboard')
                    ->with('info', 'Your institution is already set up.');
            }

            // Start new onboarding
            $onboarding = $this->onboardingService->startOnboarding($tenant);
        }

        // Redirect to current step
        return redirect()->route('tenant.onboarding.wizard.step', [
            'step' => $onboarding->current_step,
        ]);
    }

    /**
     * Display a specific step of the onboarding wizard.
     */
    public function showStep(string $step)
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not found');
        }

        // Get active onboarding
        $onboarding = TenantOnboarding::forTenant($tenant->id)
            ->inProgress()
            ->first();

        if (! $onboarding) {
            return redirect()->route('tenant.onboarding.wizard');
        }

        // Validate step
        $validSteps = $onboarding->getSteps();

        if (! in_array($step, $validSteps)) {
            abort(404, 'Invalid step');
        }

        // Get progress summary
        $progress = $this->onboardingService->getProgressSummary($onboarding);

        // Get step data
        $stepData = $onboarding->getStepData($step);

        // Prepare step-specific data
        $stepProps = $this->getStepProps($step, $tenant, $onboarding);

        return Inertia::render('Tenant/Onboarding/Wizard', [
            'step' => $step,
            'stepIndex' => array_search($step, $validSteps) + 1,
            'totalSteps' => count($validSteps),
            'progress' => $progress,
            'stepData' => $stepData,
            'onboardingId' => $onboarding->id,
            'canSkip' => $onboarding->canSkip($step),
            'canComplete' => $this->onboardingService->canCompleteOnboarding($onboarding),
            ...$stepProps,
        ]);
    }

    /**
     * Get step-specific props for the wizard.
     */
    protected function getStepProps(string $step, $tenant, TenantOnboarding $onboarding): array
    {
        switch ($step) {
            case 'institution':
                return [
                    'institution' => [
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'domain' => $tenant->domain,
                        'address' => $tenant->address,
                        'contact_email' => $tenant->contact_information['email'] ?? null,
                        'contact_phone' => $tenant->contact_information['phone'] ?? null,
                        'website' => $tenant->contact_information['website'] ?? null,
                    ],
                ];

            case 'branding':
                $brandingData = $onboarding->getStepData('branding');
                $settings = $tenant->settings ?? [];

                return [
                    'branding' => [
                        'primary_color' => $brandingData['primary_color'] ?? $settings['branding']['primary_color'] ?? '#000000',
                        'secondary_color' => $brandingData['secondary_color'] ?? $settings['branding']['secondary_color'] ?? '#ffffff',
                        'accent_color' => $brandingData['accent_color'] ?? $settings['branding']['accent_color'] ?? null,
                        'font_family' => $brandingData['font_family'] ?? $settings['branding']['font_family'] ?? 'Inter',
                        'logo_url' => isset($brandingData['logo_path'])
                            ? \Storage::url($brandingData['logo_path'])
                            : ($tenant->logo_path ? \Storage::url($tenant->logo_path) : null),
                    ],
                ];

            case 'admins':
                $adminsData = $onboarding->getStepData('admins');

                return [
                    'admins' => $adminsData['admins'] ?? [
                        [
                            'name' => '',
                            'email' => '',
                            'role' => 'institution-admin',
                            'is_primary' => true,
                        ],
                    ],
                    'availableRoles' => [
                        ['value' => 'institution-admin', 'label' => 'Institution Admin'],
                        ['value' => 'content-manager', 'label' => 'Content Manager'],
                        ['value' => 'readonly-admin', 'label' => 'Read-only Admin'],
                    ],
                ];

            case 'payment':
                $paymentData = $onboarding->getStepData('payment');

                return [
                    'payment' => [
                        'plan_id' => $paymentData['plan_id'] ?? $tenant->subscription_plan ?? 'basic',
                        'billing_cycle' => $paymentData['billing_cycle'] ?? 'monthly',
                        'trial_ends_at' => $paymentData['trial_ends_at'] ?? now()->addDays(14)->toDateString(),
                    ],
                    'availablePlans' => [
                        ['value' => 'basic', 'label' => 'Basic', 'price_monthly' => 99, 'price_yearly' => 999],
                        ['value' => 'professional', 'label' => 'Professional', 'price_monthly' => 199, 'price_yearly' => 1999],
                        ['value' => 'enterprise', 'label' => 'Enterprise', 'price_monthly' => 499, 'price_yearly' => 4999],
                    ],
                ];

            case 'imports':
                return [
                    'imports' => [
                        'courses_imported' => $onboarding->getStepData('imports', 'courses_imported', false),
                        'alumni_imported' => $onboarding->getStepData('imports', 'alumni_imported', false),
                        'courses_results' => $onboarding->getStepData('imports', 'courses_results'),
                        'alumni_results' => $onboarding->getStepData('imports', 'alumni_results'),
                    ],
                    'templates' => [
                        'courses' => route('tenant.onboarding.template', ['type' => 'courses']),
                        'alumni' => route('tenant.onboarding.template', ['type' => 'alumni']),
                    ],
                ];

            default:
                return [];
        }
    }

    /**
     * Check if tenant setup is complete.
     */
    protected function checkTenantSetupComplete($tenant): bool
    {
        // Check if tenant has completed onboarding
        $completedOnboarding = TenantOnboarding::forTenant($tenant->id)
            ->completed()
            ->exists();

        if ($completedOnboarding) {
            return true;
        }

        // Check if tenant has basic required data
        $hasBasicSetup = ! empty($tenant->name)
            && ! empty($tenant->settings)
            && $tenant->users()->exists();

        return $hasBasicSetup;
    }
}
