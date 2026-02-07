<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Learning Index Request
 *
 * Validates requests for retrieving learning progress with filtering and pagination.
 */
class LearningIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => [
                'nullable',
                'date',
                'before_or_equal:date_to',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'course_id' => [
                'nullable',
                'uuid',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date_from.date' => 'Start date must be a valid date.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.date' => 'End date must be a valid date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'course_id.uuid' => 'Course ID must be a valid UUID.',
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert date strings to proper format if needed
        if ($this->has('date_from') && $this->date_from) {
            $this->merge([
                'date_from' => date('Y-m-d', strtotime($this->date_from)),
            ]);
        }

        if ($this->has('date_to') && $this->date_to) {
            $this->merge([
                'date_to' => date('Y-m-d', strtotime($this->date_to)),
            ]);
        }
    }
}
