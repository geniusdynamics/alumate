<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sync Run Request
 *
 * Validates parameters for data synchronization operations.
 */
class SyncRunRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sources' => 'required|array|min:1',
            'sources.*' => 'in:ga,matomo',
            'force' => 'boolean',
            'time_range' => 'nullable|array',
            'time_range.start' => 'nullable|date',
            'time_range.end' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sources.required' => 'At least one data source must be specified.',
            'sources.*.in' => 'Invalid data source. Must be one of: ga, matomo.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sources' => 'data sources',
            'sources.*' => 'data source',
            'force' => 'force sync flag',
            'time_range.start' => 'start date',
            'time_range.end' => 'end date',
        ];
    }
}
