<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Channel Performance Request Validation
 *
 * Validates requests for analyzing channel performance.
 */
class ChannelPerformanceRequest extends FormRequest
{
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
            'channels' => 'nullable|array',
            'channels.*' => 'string|max:255',
            'channel_costs' => 'nullable|array',
            'channel_costs.*' => 'numeric|min:0',
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
            'channels.array' => 'Channels must be an array',
            'channels.*.max' => 'Channel name cannot exceed 255 characters',
            'channel_costs.array' => 'Channel costs must be an array',
            'channel_costs.*.numeric' => 'Channel cost must be a number',
            'channel_costs.*.min' => 'Channel cost cannot be negative',
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
            'channels' => 'channels',
            'channels.*' => 'channel',
            'channel_costs' => 'channel costs',
            'channel_costs.*' => 'channel cost',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDateRange($validator);
            $this->validateChannelCosts($validator);
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
     * Validate channel costs structure.
     */
    private function validateChannelCosts($validator): void
    {
        $channelCosts = $this->input('channel_costs');

        if (!$channelCosts || !is_array($channelCosts)) {
            return;
        }

        // If channel costs are provided, channels should also be specified
        if (!$this->has('channels')) {
            $validator->errors()->add('channels', 'Channels must be specified when providing channel costs.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default date range if not provided (last 90 days)
        if (!$this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(90)->toDateString()]);
        }

        if (!$this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }
    }
}
