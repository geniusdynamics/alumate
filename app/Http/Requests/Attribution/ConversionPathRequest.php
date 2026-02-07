<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Conversion Path Request Validation
 *
 * Validates requests for retrieving a user's conversion path.
 */
class ConversionPathRequest extends FormRequest
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
     * Maximum number of models allowed
     */
    private const MAX_MODELS = 5;

    /**
     * Maximum path length for filtering
     */
    private const MAX_PATH_LENGTH = 1000;

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
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'include_attribution' => 'nullable|boolean',
            'models' => 'nullable|array|max:'.self::MAX_MODELS,
            'models.*' => 'string|in:'.implode(',', self::VALID_MODELS),
            'min_touches' => 'nullable|integer|min:1|max:'.self::MAX_PATH_LENGTH,
            'max_touches' => 'nullable|integer|min:1|max:'.self::MAX_PATH_LENGTH,
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
            'start_date.date' => 'Start date must be a valid date',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'include_attribution.boolean' => 'include_attribution must be a boolean',
            'models.array' => 'Models must be an array',
            'models.max' => 'Maximum of '.self::MAX_MODELS.' models can be specified',
            'models.*.in' => 'Invalid model selected. Valid options are: '.implode(', ', self::VALID_MODELS),
            'min_touches.integer' => 'Minimum touches must be an integer',
            'min_touches.min' => 'Minimum touches must be at least 1',
            'min_touches.max' => 'Minimum touches cannot exceed '.self::MAX_PATH_LENGTH,
            'max_touches.integer' => 'Maximum touches must be an integer',
            'max_touches.min' => 'Maximum touches must be at least 1',
            'max_touches.max' => 'Maximum touches cannot exceed '.self::MAX_PATH_LENGTH,
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
            'start_date' => 'start date',
            'end_date' => 'end date',
            'include_attribution' => 'include attribution',
            'models' => 'attribution models',
            'models.*' => 'model',
            'min_touches' => 'minimum touches',
            'max_touches' => 'maximum touches',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDateRange($validator);
            $this->validateTouchRange($validator);
        });
    }

    /**
     * Validate that date range is consistent.
     */
    private function validateDateRange($validator): void
    {
        $hasStartDate = $this->has('start_date');
        $hasEndDate = $this->has('end_date');

        if ($hasStartDate && ! $hasEndDate) {
            $validator->errors()->add('end_date', 'Both start_date and end_date must be provided together.');
        }

        if (! $hasStartDate && $hasEndDate) {
            $validator->errors()->add('start_date', 'Both start_date and end_date must be provided together.');
        }
    }

    /**
     * Validate touch range constraints.
     */
    private function validateTouchRange($validator): void
    {
        $minTouches = $this->input('min_touches');
        $maxTouches = $this->input('max_touches');

        if ($minTouches !== null && $maxTouches !== null && $minTouches > $maxTouches) {
            $validator->errors()->add('max_touches', 'Maximum touches must be greater than or equal to minimum touches.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default date range if not provided (last 30 days)
        if (! $this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(30)->toDateString()]);
        }

        if (! $this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }

        // Set default include_attribution
        if (! $this->has('include_attribution')) {
            $this->merge(['include_attribution' => false]);
        }
    }
}
