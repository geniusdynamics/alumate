<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Calculate Attribution Request Validation
 *
 * Validates requests for calculating user attribution using a specific model.
 */
class CalculateAttributionRequest extends FormRequest
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
            'model' => 'nullable|string|in:'.implode(',', self::VALID_MODELS),
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
            'model.in' => 'Invalid attribution model. Valid models are: '.implode(', ', self::VALID_MODELS),
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
            'model' => 'attribution model',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default model if not provided
        if (! $this->has('model')) {
            $this->merge(['model' => 'last_click']);
        }

        // Set default date range if not provided (last 30 days)
        if (! $this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(30)->toDateString()]);
        }

        if (! $this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }
    }
}
