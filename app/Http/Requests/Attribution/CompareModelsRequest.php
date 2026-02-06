<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Compare Attribution Models Request Validation
 *
 * Validates requests for comparing multiple attribution models for a user.
 */
class CompareModelsRequest extends FormRequest
{
    /**
     * Valid attribution models
     */
    private const VALID_MODELS = [
        'first_click',
        'last_click',
        'linear',
        'time_decay',
        'position_based',
    ];

    /**
     * Maximum number of models that can be compared
     */
    private const MAX_MODELS = 5;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() || Auth::guard('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'models' => 'nullable|array|min:2|max:' . self::MAX_MODELS,
            'models.*' => 'string|in:' . implode(',', self::VALID_MODELS),
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'models.required' => 'At least two models are required for comparison',
            'models.min' => 'At least two models must be selected for comparison',
            'models.max' => 'Maximum of ' . self::MAX_MODELS . ' models can be compared at once',
            'models.*.in' => 'Invalid model selected. Valid options are: ' . implode(', ', self::VALID_MODELS),
            'start_date.date' => 'Start date must be a valid date',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'models' => 'attribution models',
            'models.*' => 'model',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDateRange($validator);
        });
    }

    /**
     * Validate that date range is consistent.
     */
    private function validateDateRange($validator): void
    {
        $hasStartDate = $this->has('start_date');
        $hasEndDate = $this->has('end_date');

        if ($hasStartDate && !$hasEndDate) {
            $validator->errors()->add('end_date', 'Both start_date and end_date must be provided together.');
        }

        if (!$hasStartDate && $hasEndDate) {
            $validator->errors()->add('start_date', 'Both start_date and end_date must be provided together.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default date range if not provided (last 30 days)
        if (!$this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(30)->toDateString()]);
        }

        if (!$this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }
    }
}
