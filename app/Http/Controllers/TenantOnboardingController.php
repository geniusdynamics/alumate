<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TenantOnboarding;
use App\Services\TenantOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class TenantOnboardingController extends Controller
{
    public function __construct(
        private TenantOnboardingService $onboardingService
    ) {}

    /**
     * Display the onboarding wizard.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        if (! $tenant) {
            return Inertia::render('Onboarding/CreateTenant');
        }

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->whereIn('status', [TenantOnboarding::STATUS_IN_PROGRESS, TenantOnboarding::STATUS_PAUSED])
            ->first();

        if (! $onboarding && $tenant->onboarding_status === 'completed') {
            return redirect()->route('dashboard');
        }

        $progress = $onboarding ? $this->onboardingService->getProgress($onboarding) : null;

        return Inertia::render('Onboarding/Wizard', [
            'progress' => $progress,
            'steps' => TenantOnboardingService::STEPS,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Start a new onboarding process.
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Create tenant
        $tenant = \App\Models\Tenant::create([
            'name' => $request->tenant_name,
            'slug' => \Illuminate\Support\Str::slug($request->tenant_name).'-'.uniqid(),
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Associate user with tenant
        \App\Models\TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        // Set as current tenant
        $user->update(['current_tenant_id' => $tenant->id]);

        // Start onboarding
        $onboarding = $this->onboardingService->startOnboarding(
            $tenant,
            $user,
            $request->only(['source'])
        );

        return response()->json([
            'message' => 'Onboarding started successfully',
            'onboarding' => [
                'id' => $onboarding->id,
                'current_step' => $onboarding->current_step,
                'progress' => $this->onboardingService->getProgress($onboarding),
            ],
        ], 201);
    }

    /**
     * Get onboarding progress.
     */
    public function progress(): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        if (! $tenant) {
            return response()->json([
                'message' => 'No tenant found',
            ], 404);
        }

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No onboarding found',
            ], 404);
        }

        return response()->json([
            'progress' => $this->onboardingService->getProgress($onboarding),
        ]);
    }

    /**
     * Save step data.
     */
    public function saveStep(Request $request, int $step): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenant = $user->currentTenant;

        if (! $tenant) {
            return response()->json([
                'message' => 'No tenant found',
            ], 404);
        }

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No active onboarding found',
            ], 404);
        }

        try {
            $onboarding = $this->onboardingService->saveStep($onboarding, $step, $request->data);

            return response()->json([
                'message' => 'Step saved successfully',
                'progress' => $this->onboardingService->getProgress($onboarding),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save step',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Skip a step.
     */
    public function skipStep(int $step): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No active onboarding found',
            ], 404);
        }

        $onboarding = $this->onboardingService->skipStep($onboarding, $step);

        return response()->json([
            'message' => 'Step skipped',
            'progress' => $this->onboardingService->getProgress($onboarding),
        ]);
    }

    /**
     * Go to a specific step.
     */
    public function goToStep(int $step): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No active onboarding found',
            ], 404);
        }

        try {
            $onboarding = $this->onboardingService->goToStep($onboarding, $step);

            return response()->json([
                'message' => 'Step changed',
                'progress' => $this->onboardingService->getProgress($onboarding),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload logo for branding step.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenant = $user->currentTenant;

        $path = $request->file('logo')->store("tenants/{$tenant->id}/logos", 'public');

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'path' => $path,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Import courses from CSV.
     */
    public function importCourses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenant = $user->currentTenant;

        $results = $this->onboardingService->importCourses($tenant, $request->file('file'));

        return response()->json([
            'message' => 'Import completed',
            'results' => $results,
        ]);
    }

    /**
     * Import alumni from CSV.
     */
    public function importAlumni(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenant = $user->currentTenant;

        $results = $this->onboardingService->importAlumni($tenant, $request->file('file'));

        return response()->json([
            'message' => 'Import completed',
            'results' => $results,
        ]);
    }

    /**
     * Complete onboarding.
     */
    public function complete(): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No active onboarding found',
            ], 404);
        }

        $onboarding = $this->onboardingService->completeOnboarding($onboarding);

        return response()->json([
            'message' => 'Onboarding completed successfully',
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Abandon onboarding.
     */
    public function abandon(): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        $onboarding = TenantOnboarding::where('tenant_id', $tenant->id)
            ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
            ->first();

        if (! $onboarding) {
            return response()->json([
                'message' => 'No active onboarding found',
            ], 404);
        }

        $onboarding->abandon();

        return response()->json([
            'message' => 'Onboarding abandoned',
        ]);
    }

    /**
     * Get onboarding statistics (admin).
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->onboardingService->getStatistics();

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
