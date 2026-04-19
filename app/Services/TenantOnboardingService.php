<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantOnboarding;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    public const STEP_INSTITUTION_INFO = 1;

    public const STEP_BRANDING = 2;

    public const STEP_ADMIN_SETUP = 3;

    public const STEP_DATA_IMPORT = 4;

    public const STEP_PAYMENT = 5;

    public const STEP_REVIEW = 6;

    public const STEPS = [
        self::STEP_INSTITUTION_INFO => [
            'key' => 'institution_info',
            'title' => 'Institution Information',
            'description' => 'Basic information about your institution',
        ],
        self::STEP_BRANDING => [
            'key' => 'branding',
            'title' => 'Branding & Customization',
            'description' => 'Upload your logo and set brand colors',
        ],
        self::STEP_ADMIN_SETUP => [
            'key' => 'admin_setup',
            'title' => 'Administrator Setup',
            'description' => 'Add additional administrators',
        ],
        self::STEP_DATA_IMPORT => [
            'key' => 'data_import',
            'title' => 'Data Import',
            'description' => 'Import courses and alumni data',
        ],
        self::STEP_PAYMENT => [
            'key' => 'payment',
            'title' => 'Select Plan',
            'description' => 'Choose your subscription plan',
        ],
        self::STEP_REVIEW => [
            'key' => 'review',
            'title' => 'Review & Launch',
            'description' => 'Review your setup and launch',
        ],
    ];

    /**
     * Start a new tenant onboarding process.
     */
    public function startOnboarding(Tenant $tenant, User $user, array $initialData = []): TenantOnboarding
    {
        return DB::transaction(function () use ($tenant, $user, $initialData) {
            // Check if onboarding already exists
            $existingOnboarding = TenantOnboarding::where('tenant_id', $tenant->id)
                ->where('status', TenantOnboarding::STATUS_IN_PROGRESS)
                ->first();

            if ($existingOnboarding) {
                return $existingOnboarding;
            }

            $onboarding = TenantOnboarding::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'current_step' => self::STEP_INSTITUTION_INFO,
                'total_steps' => count(self::STEPS),
                'completed_steps' => [],
                'step_data' => [
                    self::STEP_INSTITUTION_INFO => $initialData,
                ],
                'status' => TenantOnboarding::STATUS_IN_PROGRESS,
                'source' => $initialData['source'] ?? null,
            ]);

            $tenant->update([
                'onboarding_status' => 'in_progress',
                'onboarding_data' => [
                    'started_by' => $user->id,
                    'started_at' => now()->toISOString(),
                ],
            ]);

            Log::info('Tenant onboarding started', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);

            return $onboarding;
        });
    }

    /**
     * Save step data and mark as completed.
     */
    public function saveStep(TenantOnboarding $onboarding, int $step, array $data): TenantOnboarding
    {
        DB::transaction(function () use ($onboarding, $step, $data) {
            // Process any file uploads
            $processedData = $this->processStepData($step, $data, $onboarding->tenant);

            $onboarding->completeStep($step, $processedData);

            // Apply step-specific changes to tenant
            $this->applyStepChanges($onboarding, $step, $processedData);

            Log::info('Onboarding step completed', [
                'onboarding_id' => $onboarding->id,
                'step' => $step,
            ]);
        });

        return $onboarding->fresh();
    }

    /**
     * Skip a step.
     */
    public function skipStep(TenantOnboarding $onboarding, int $step): TenantOnboarding
    {
        $onboarding->update([
            'current_step' => min($step + 1, $onboarding->total_steps),
            'last_activity_at' => now(),
        ]);

        return $onboarding->fresh();
    }

    /**
     * Go back to a previous step.
     */
    public function goToStep(TenantOnboarding $onboarding, int $step): TenantOnboarding
    {
        if ($step < 1 || $step > $onboarding->total_steps) {
            throw new \InvalidArgumentException('Invalid step number');
        }

        $onboarding->update([
            'current_step' => $step,
            'last_activity_at' => now(),
        ]);

        return $onboarding->fresh();
    }

    /**
     * Complete the onboarding process.
     */
    public function completeOnboarding(TenantOnboarding $onboarding): TenantOnboarding
    {
        DB::transaction(function () use ($onboarding) {
            $onboarding->complete();

            // Activate tenant
            $onboarding->tenant->update([
                'status' => 'active',
            ]);

            // Send welcome email
            // TODO: Dispatch welcome email job

            Log::info('Tenant onboarding completed', [
                'tenant_id' => $onboarding->tenant_id,
                'onboarding_id' => $onboarding->id,
            ]);
        });

        return $onboarding->fresh();
    }

    /**
     * Get onboarding progress.
     */
    public function getProgress(TenantOnboarding $onboarding): array
    {
        $steps = [];
        foreach (self::STEPS as $number => $info) {
            $steps[] = [
                'number' => $number,
                'key' => $info['key'],
                'title' => $info['title'],
                'description' => $info['description'],
                'is_completed' => in_array($number, $onboarding->completed_steps ?? []),
                'is_current' => $number === $onboarding->current_step,
                'data' => $onboarding->getStepData($number),
            ];
        }

        return [
            'current_step' => $onboarding->current_step,
            'total_steps' => $onboarding->total_steps,
            'progress_percentage' => $onboarding->getProgressPercentage(),
            'status' => $onboarding->status,
            'is_completed' => $onboarding->isCompleted(),
            'is_expired' => $onboarding->isExpired(),
            'steps' => $steps,
            'time_spent_minutes' => $onboarding->getTimeSpent(),
        ];
    }

    /**
     * Import courses from CSV.
     */
    public function importCourses(Tenant $tenant, UploadedFile $file): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_combine($headers, $row);

            try {
                DB::transaction(function () use ($tenant, $data) {
                    $tenant->run(function () use ($data) {
                        \App\Models\Course::create([
                            'name' => $data['name'],
                            'code' => $data['code'] ?? null,
                            'description' => $data['description'] ?? null,
                            'duration' => $data['duration'] ?? null,
                            'level' => $data['level'] ?? 'undergraduate',
                        ]);
                    });
                });

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        Log::info('Course import completed', [
            'tenant_id' => $tenant->id,
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Import alumni from CSV.
     */
    public function importAlumni(Tenant $tenant, UploadedFile $file): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_combine($headers, $row);

            try {
                DB::transaction(function () use ($tenant, $data) {
                    // Create user
                    $user = User::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'name' => $data['name'],
                            'password' => bcrypt(Str::random(16)),
                            'verification_status' => 'verified',
                            'verified_at' => now(),
                        ]
                    );

                    // Associate with tenant
                    \App\Models\TenantUser::firstOrCreate([
                        'tenant_id' => $tenant->id,
                        'user_id' => $user->id,
                    ], [
                        'role' => 'alumni',
                    ]);

                    // Create verification record
                    \App\Models\AlumniVerification::create([
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'status' => 'approved',
                        'verification_method' => 'bulk_import',
                        'graduation_year' => $data['graduation_year'] ?? null,
                        'degree' => $data['degree'] ?? null,
                        'major' => $data['major'] ?? null,
                        'reviewed_at' => now(),
                        'submitted_at' => now(),
                        'notes' => 'Bulk import during onboarding',
                    ]);
                });

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $rowNumber,
                    'email' => $data['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        Log::info('Alumni import completed', [
            'tenant_id' => $tenant->id,
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Process step-specific data.
     */
    private function processStepData(int $step, array $data, Tenant $tenant): array
    {
        switch ($step) {
            case self::STEP_BRANDING:
                // Handle logo upload
                if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
                    $path = $data['logo']->store("tenants/{$tenant->id}/logos", 'public');
                    $data['logo_path'] = $path;
                    $data['logo_url'] = Storage::disk('public')->url($path);
                    unset($data['logo']);
                }

                // Handle favicon upload
                if (isset($data['favicon']) && $data['favicon'] instanceof UploadedFile) {
                    $path = $data['favicon']->store("tenants/{$tenant->id}/logos", 'public');
                    $data['favicon_path'] = $path;
                    unset($data['favicon']);
                }
                break;

            case self::STEP_DATA_IMPORT:
                // Store import results
                if (isset($data['courses_import'])) {
                    $results = $this->importCourses($tenant, $data['courses_import']);
                    $data['courses_import_results'] = $results;
                    unset($data['courses_import']);
                }

                if (isset($data['alumni_import'])) {
                    $results = $this->importAlumni($tenant, $data['alumni_import']);
                    $data['alumni_import_results'] = $results;
                    unset($data['alumni_import']);
                }
                break;
        }

        return $data;
    }

    /**
     * Apply changes to tenant based on step data.
     */
    private function applyStepChanges(TenantOnboarding $onboarding, int $step, array $data): void
    {
        $tenant = $onboarding->tenant;

        switch ($step) {
            case self::STEP_INSTITUTION_INFO:
                $tenant->update([
                    'name' => $data['name'] ?? $tenant->name,
                    'address' => $data['address'] ?? null,
                    'contact_information' => [
                        'email' => $data['contact_email'] ?? null,
                        'phone' => $data['contact_phone'] ?? null,
                        'website' => $data['website'] ?? null,
                    ],
                ]);
                break;

            case self::STEP_BRANDING:
                $settings = $tenant->settings ?? [];
                $settings['branding'] = [
                    'primary_color' => $data['primary_color'] ?? null,
                    'secondary_color' => $data['secondary_color'] ?? null,
                    'logo_path' => $data['logo_path'] ?? null,
                    'favicon_path' => $data['favicon_path'] ?? null,
                ];
                $tenant->update(['settings' => $settings]);
                break;

            case self::STEP_ADMIN_SETUP:
                // Add additional admins
                if (! empty($data['additional_admins'])) {
                    foreach ($data['additional_admins'] as $adminData) {
                        $this->addTenantAdmin($tenant, $adminData);
                    }
                }
                break;
        }
    }

    /**
     * Add a tenant admin.
     */
    private function addTenantAdmin(Tenant $tenant, array $data): void
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => bcrypt(Str::random(16)),
            ]
        );

        \App\Models\TenantUser::firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ], [
            'role' => 'admin',
        ]);
    }

    /**
     * Get onboarding statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => TenantOnboarding::count(),
            'in_progress' => TenantOnboarding::where('status', TenantOnboarding::STATUS_IN_PROGRESS)->count(),
            'completed' => TenantOnboarding::where('status', TenantOnboarding::STATUS_COMPLETED)->count(),
            'abandoned' => TenantOnboarding::where('status', TenantOnboarding::STATUS_ABANDONED)->count(),
            'expired' => TenantOnboarding::expired()->count(),
            'avg_completion_time' => $this->calculateAverageCompletionTime(),
            'completion_rate' => $this->calculateCompletionRate(),
        ];
    }

    /**
     * Calculate average completion time in minutes.
     */
    private function calculateAverageCompletionTime(): ?float
    {
        $completed = TenantOnboarding::completed()
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completed->isEmpty()) {
            return null;
        }

        $totalMinutes = $completed->sum(function ($onboarding) {
            return $onboarding->started_at->diffInMinutes($onboarding->completed_at);
        });

        return round($totalMinutes / $completed->count(), 2);
    }

    /**
     * Calculate completion rate percentage.
     */
    private function calculateCompletionRate(): float
    {
        $total = TenantOnboarding::count();

        if ($total === 0) {
            return 0;
        }

        $completed = TenantOnboarding::where('status', TenantOnboarding::STATUS_COMPLETED)->count();

        return round(($completed / $total) * 100, 2);
    }
}
