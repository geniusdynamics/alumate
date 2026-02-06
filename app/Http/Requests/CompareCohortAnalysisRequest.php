<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Cohort;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Compare Cohort Analysis Request
 *
 * Validates requests for comparing multiple cohorts with statistical analysis.
 * Implements role-based access control using Spatie permissions and tenant isolation.
 */
class CompareCohortAnalysisRequest extends FormRequest
{
    protected TenantContextService $tenantContextService;

    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admins can compare cohorts across all tenants
        if ($user->is_super_admin ?? false) {
            return true;
        }

        // Use Laravel's Gate for policy-based authorization
        return Gate::allows('cohort.compare', $user);
    }

    /**
     * Get the authorization failure response.
     */
    public function failedAuthorization(): Response
    {
        $user = Auth::user();

        if (!$user) {
            return Response::deny('You must be logged in to compare cohorts.');
        }

        if ($user->is_super_admin ?? false) {
            return Response::allow();
        }

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
                'integer',
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
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateCohortAccess($validator);
        });
    }

    /**
     * Validate that user has access to all requested cohorts.
     */
    private function validateCohortAccess($validator): void
    {
        $cohortIds = $this->input('cohort_ids', []);

        if (empty($cohortIds)) {
            return;
        }

        $user = Auth::user();

        // Super admins can access cohorts from any tenant without restrictions
        if ($user && ($user->is_super_admin ?? false)) {
            return;
        }

        // Get current tenant ID for non-super admin users
        $currentTenantId = $this->tenantContextService->getCurrentTenantId();

        if (!$currentTenantId) {
            $validator->errors()->add('cohort_ids', 'Tenant context is required for cohort comparison.');
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
    }
}
