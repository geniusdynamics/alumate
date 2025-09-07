<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for enrolling users in email sequences
 */
class EnrollUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1|max:100',
            'user_ids.*' => 'required|integer|exists:users,id',
            'start_step' => 'sometimes|integer|min:0',
            'enrollment_date' => 'sometimes|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'User IDs are required.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.min' => 'At least one user must be selected.',
            'user_ids.max' => 'Cannot enroll more than 100 users at once.',
            'user_ids.*.required' => 'Each user ID is required.',
            'user_ids.*.integer' => 'User IDs must be valid numbers.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'start_step.integer' => 'Start step must be a valid number.',
            'start_step.min' => 'Start step cannot be negative.',
            'enrollment_date.date' => 'Enrollment date must be a valid date.',
            'enrollment_date.after_or_equal' => 'Enrollment date cannot be in the past.',
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
            'user_ids' => 'user IDs',
            'user_ids.*' => 'user ID',
            'start_step' => 'start step',
            'enrollment_date' => 'enrollment date',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('start_step')) {
            $this->merge(['start_step' => 0]);
        }

        if (!$this->has('enrollment_date')) {
            $this->merge(['enrollment_date' => now()->toDateString()]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that users are not already enrolled in this sequence
            if ($this->has('user_ids') && !empty($this->user_ids)) {
                $this->validateExistingEnrollments($validator);
            }

            // Validate start step is within sequence bounds
            if ($this->has('start_step')) {
                $this->validateStartStep($validator);
            }

            // Validate users belong to the same tenant
            if ($this->has('user_ids') && !empty($this->user_ids)) {
                $this->validateUserTenantAccess($validator);
            }
        });
    }

    /**
     * Validate that users are not already enrolled in this sequence.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateExistingEnrollments($validator): void
    {
        $sequence = $this->route('sequence');
        $existingEnrollments = $sequence->sequenceEnrollments()
            ->whereIn('lead_id', $this->user_ids)
            ->whereIn('status', ['active', 'paused'])
            ->pluck('lead_id')
            ->toArray();

        if (!empty($existingEnrollments)) {
            $existingUserIds = implode(', ', $existingEnrollments);
            $validator->errors()->add(
                'user_ids',
                "Users with IDs {$existingUserIds} are already enrolled in this sequence."
            );
        }
    }

    /**
     * Validate start step is within sequence bounds.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateStartStep($validator): void
    {
        $sequence = $this->route('sequence');
        $maxStep = $sequence->sequenceEmails()->max('send_order') ?? 0;

        if ($this->start_step > $maxStep) {
            $validator->errors()->add(
                'start_step',
                "Start step cannot be greater than the last email in sequence (step {$maxStep})."
            );
        }
    }

    /**
     * Validate that users belong to the same tenant as the sequence.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateUserTenantAccess($validator): void
    {
        $users = \App\Models\User::whereIn('id', $this->user_ids)
            ->where('tenant_id', '!=', tenant()->id)
            ->pluck('id')
            ->toArray();

        if (!empty($users)) {
            $invalidUserIds = implode(', ', $users);
            $validator->errors()->add(
                'user_ids',
                "Users with IDs {$invalidUserIds} do not belong to your organization."
            );
        }
    }
}