<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Cohort;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Compare Cohorts Request
 *
 * Validates requests for comparing multiple cohorts with statistical analysis.
 * Implements role-based access control using Spatie permissions and tenant isolation.
 */
class CompareCohortsRequest extends FormRequest
{
    protected TenantContextService $tenantContextService;

    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * Uses Spatie's permission system for role-based access control
     * and ensures tenant isolation for cohort access.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        // Unauthenticated users are not authorized
        if (!$user) {
            return false;
        }

        // Super admins can compare cohorts across all tenants
        if ($user->is_super_admin) {
            return true;
        }

        // Use Laravel's Gate for policy-based authorization
        // This ensures consistent authorization across the application
        return Gate::allows('cohort.compare', $user);
    }

    /**
     * Get the authorization failure response.
     *
     * Provides a structured error response for unauthorized access attempts.
     */
    public function failedAuthorization(): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return Response::deny('You must be logged in to compare cohorts.');
        }

        if ($user->is_super_admin) {
            return Response::allow();
        }

        // Check if user has the required permission
        if (!$user->can('cohort.compare')) {
            return Response::deny(
                'You do not have permission to compare cohorts. ' .
                'Required permission: view analytics for cohorts. ' .
                'Contact your administrator if you believe this is an error.'
            );
        }

        return Response::deny('You are not authorized to compare cohorts in this tenant.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cohort_ids' => [
                'required',
                'array',
                'min:2',
                'max:5',
            ],
            'cohort_ids.*' => [
                'required',
                'string',
                'exists:cohorts,id',
            ],
            'metrics' => [
                'nullable',
                'array',
            ],
            'metrics.*' => [
                'string',
                'in:retention,engagement,conversion',
            ],
            'time_range' => [
                'nullable',
                'array',
            ],
            'time_range.days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'time_range.start_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'time_range.end_date' => [
                'nullable',
                'date',
                'after_or_equal:time_range.start_date',
                'before_or_equal:today',
            ],
            'include_statistical_significance' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cohort_ids.required' => 'At least two cohort IDs are required for comparison.',
            'cohort_ids.min' => 'At least two cohorts must be selected for comparison.',
            'cohort_ids.max' => 'Maximum of 5 cohorts can be compared at once.',
            'cohort_ids.*.exists' => 'One or more selected cohorts do not exist.',
            'metrics.*.in' => 'Invalid metric selected. Valid options are: retention, engagement, conversion.',
            'time_range.days.min' => 'Time range must be at least 1 day.',
            'time_range.days.max' => 'Time range cannot exceed 365 days.',
            'time_range.start_date.before_or_equal' => 'Start date cannot be in the future.',
            'time_range.end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'time_range.end_date.before_or_equal' => 'End date cannot be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTimeRange($validator);
            $this->validateCohortAccess($validator);
        });
    }

    /**
     * Validate time range configuration.
     */
    private function validateTimeRange($validator): void
    {
        $timeRange = $this->input('time_range', []);

        if (empty($timeRange)) {
            return;
        }

        $hasDays = isset($timeRange['days']);
        $hasStartDate = isset($timeRange['start_date']);
        $hasEndDate = isset($timeRange['end_date']);

        // If any time range parameter is provided, ensure consistency
        if ($hasDays && ($hasStartDate || $hasEndDate)) {
            $validator->errors()->add('time_range', 'Cannot specify both days and date range. Choose either days or start_date/end_date.');
        }

        if (($hasStartDate && !$hasEndDate) || (!$hasStartDate && $hasEndDate)) {
            $validator->errors()->add('time_range', 'Both start_date and end_date must be provided together.');
        }
    }

    /**
     * Validate that user has access to all requested cohorts.
     *
     * Implements tenant-scoped authorization ensuring:
     * 1. Super admins can access cohorts from any tenant
     * 2. Regular users can only access cohorts from their current tenant
     * 3. Users must have appropriate roles/permissions for cohort comparison
     */
    private function validateCohortAccess($validator): void
    {
        $cohortIds = $this->input('cohort_ids', []);

        if (empty($cohortIds)) {
            return;
        }

        $user = Auth::user();

        // Super admins can access cohorts from any tenant without restrictions
        if ($user && $user->is_super_admin) {
            return;
        }

        // Verify user has required permission for cohort comparison
        if (!$user || !$user->can('cohort.compare')) {
            $validator->errors()->add('cohort_ids', 'You do not have permission to compare cohorts.');
            return;
        }

        // Get current tenant ID for non-super admin users
        $currentTenantId = $this->tenantContextService->getCurrentTenantId();

        if (!$currentTenantId) {
            $validator->errors()->add('cohort_ids', 'Tenant context is required for cohort comparison.');
            return;
        }

        // Verify user has access to the current tenant
        if (!$user->hasAccessToTenant($currentTenantId)) {
            $validator->errors()->add('cohort_ids', 'You do not have access to the current tenant.');
            return;
        }

        // Fetch all requested cohorts and verify they belong to the current tenant
        $cohorts = Cohort::whereIn('id', $cohortIds)->get();

        if ($cohorts->count() !== count($cohortIds)) {
            $validator->errors()->add('cohort_ids', 'One or more selected cohorts do not exist.');
            return;
        }

        // Check that all cohorts belong to the current tenant
        $inaccessibleCohorts = $cohorts->filter(function ($cohort) use ($currentTenantId) {
            return (string) $cohort->tenant_id !== (string) $currentTenantId;
        });

        if ($inaccessibleCohorts->isNotEmpty()) {
            $inaccessibleNames = $inaccessibleCohorts->pluck('name')->implode(', ');
            $validator->errors()->add(
                'cohort_ids',
                "You do not have access to the following cohorts: {$inaccessibleNames}. " .
                'All cohorts must belong to your current tenant.'
            );
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default metrics if not provided
        if (!$this->has('metrics') || empty($this->input('metrics'))) {
            $this->merge([
                'metrics' => ['retention', 'engagement'],
            ]);
        }

        // Set default statistical significance flag
        if (!$this->has('include_statistical_significance')) {
            $this->merge([
                'include_statistical_significance' => true,
            ]);
        }
    }

    /**
     * Get the validated data with defaults applied.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        // Apply default time range if not specified
        if (!isset($validated['time_range']) || empty($validated['time_range'])) {
            $validated['time_range'] = [
                'days' => 30, // Default to 30 days
            ];
        }

        return $validated;
    }
}
