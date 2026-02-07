<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Track Learning Request Validation
 *
 * Validates learning progress tracking requests including course interactions,
 * progress updates, and certification data.
 */
class TrackLearningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the course or is a tenant admin
        $courseId = $this->input('course_id');
        if (! $courseId) {
            return false;
        }

        // Allow if user has enrolled in the course or is admin
        return auth()->user()->can('track-learning', $courseId) ||
               auth()->user()->hasRole(['admin', 'super-admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'module_id' => 'nullable|integer|exists:modules,id',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'engagement_duration' => 'nullable|integer|min:0',
            'interactions_count' => 'nullable|integer|min:0',
            'interaction_type' => 'nullable|string|in:view,complete,quiz,assignment,discussion',
            'score' => 'nullable|numeric|min:0|max:100',
            'certifications' => 'nullable|array',
            'certifications.*.cert_id' => 'required|string|max:255',
            'certifications.*.issued_at' => 'required|date',
            'certifications.*.score' => 'nullable|numeric|min:0|max:100',
            'data' => 'nullable|array',
            'data.*' => 'nullable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'The specified course does not exist',
            'progress_percentage.min' => 'Progress percentage cannot be less than 0',
            'progress_percentage.max' => 'Progress percentage cannot exceed 100',
            'engagement_duration.min' => 'Engagement duration cannot be negative',
            'interactions_count.min' => 'Interactions count cannot be negative',
            'score.min' => 'Score cannot be less than 0',
            'score.max' => 'Score cannot exceed 100',
            'interaction_type.in' => 'Invalid interaction type',
            'certifications.*.cert_id.required' => 'Certification ID is required',
            'certifications.*.issued_at.required' => 'Certification issued date is required',
            'certifications.*.issued_at.date' => 'Invalid certification issued date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'course_id' => 'course',
            'module_id' => 'module',
            'progress_percentage' => 'progress percentage',
            'engagement_duration' => 'engagement duration',
            'interactions_count' => 'interactions count',
            'interaction_type' => 'interaction type',
            'certifications.*.cert_id' => 'certification ID',
            'certifications.*.issued_at' => 'certification issued date',
            'certifications.*.score' => 'certification score',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure tenant context
        if (! session()->has('tenant_id')) {
            abort(403, 'Tenant context required');
        }
    }
}
