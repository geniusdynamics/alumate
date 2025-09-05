<?php

namespace App\Http\Requests\Api;

use App\Models\EmailSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating email sequences
 */
class UpdateEmailSequenceRequest extends FormRequest
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
        $rules = EmailSequence::getValidationRules();

        // Remove tenant_id as it's handled internally
        unset($rules['tenant_id']);

        // Make all fields optional for updates
        foreach ($rules as $field => $rule) {
            if (!str_contains($rule, 'required')) {
                $rules[$field] = 'sometimes|' . $rule;
            }
        }

        // Add unique validation for name if provided
        if ($this->has('name')) {
            $rules['name'] = [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('email_sequences', 'name')
                    ->ignore($this->route('sequence')->id)
                    ->where('tenant_id', tenant()->id)
            ];
        }

        // Add custom validation for trigger conditions
        if ($this->has('trigger_conditions') && !empty($this->trigger_conditions)) {
            $rules['trigger_conditions.*.event'] = 'required|string|max:255';
            $rules['trigger_conditions.*.conditions'] = 'nullable|array';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Email sequence name cannot be longer than :max characters.',
            'name.unique' => 'An email sequence with this name already exists.',
            'description.max' => 'Description cannot be longer than :max characters.',
            'audience_type.in' => 'Selected audience type is invalid.',
            'trigger_type.in' => 'Selected trigger type is invalid.',
            'trigger_conditions.array' => 'Trigger conditions must be a valid array.',
            'trigger_conditions.*.event.required' => 'Each trigger condition must have an event.',
            'trigger_conditions.*.conditions.array' => 'Trigger condition details must be an array.',
            'is_active.boolean' => 'Active status must be true or false.',
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
            'name' => 'sequence name',
            'description' => 'sequence description',
            'audience_type' => 'audience type',
            'trigger_type' => 'trigger type',
            'trigger_conditions' => 'trigger conditions',
            'is_active' => 'active status',
        ];
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
            // Validate trigger conditions structure if provided
            if ($this->has('trigger_conditions') && !empty($this->trigger_conditions)) {
                $this->validateTriggerConditions($validator);
            }

            // Validate audience type compatibility with trigger type if both are provided
            if ($this->has('audience_type') && $this->has('trigger_type')) {
                $this->validateAudienceTriggerCompatibility($validator);
            } elseif ($this->has('audience_type') && !$this->has('trigger_type')) {
                // If only audience_type is provided, check compatibility with existing trigger_type
                $existingTriggerType = $this->route('sequence')->trigger_type;
                $this->validateAudienceTriggerCompatibility($validator, $existingTriggerType);
            } elseif (!$this->has('audience_type') && $this->has('trigger_type')) {
                // If only trigger_type is provided, check compatibility with existing audience_type
                $existingAudienceType = $this->route('sequence')->audience_type;
                $this->validateAudienceTriggerCompatibility($validator, null, $existingAudienceType);
            }
        });
    }

    /**
     * Validate trigger conditions structure.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateTriggerConditions($validator): void
    {
        $triggerConditions = $this->trigger_conditions;
        $triggerType = $this->trigger_type ?? $this->route('sequence')->trigger_type;

        foreach ($triggerConditions as $index => $condition) {
            if (!isset($condition['event'])) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.event",
                    'Trigger condition must have an event.'
                );
                continue;
            }

            // Validate event type based on trigger_type
            $validEvents = $this->getValidEventsForTriggerType($triggerType);

            if (!in_array($condition['event'], $validEvents)) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.event",
                    "Event '{$condition['event']}' is not valid for trigger type '{$triggerType}'."
                );
            }
        }
    }

    /**
     * Validate audience type compatibility with trigger type.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string|null $triggerTypeOverride
     * @param string|null $audienceTypeOverride
     */
    private function validateAudienceTriggerCompatibility($validator, ?string $triggerTypeOverride = null, ?string $audienceTypeOverride = null): void
    {
        $audienceType = $this->audience_type ?? $audienceTypeOverride ?? $this->route('sequence')->audience_type;
        $triggerType = $this->trigger_type ?? $triggerTypeOverride ?? $this->route('sequence')->trigger_type;

        // Define compatibility rules
        $compatibilityRules = [
            'individual' => ['form_submission', 'behavior', 'manual'],
            'institutional' => ['form_submission', 'behavior', 'manual'],
            'employer' => ['form_submission', 'behavior', 'manual'],
        ];

        if (isset($compatibilityRules[$audienceType]) &&
            !in_array($triggerType, $compatibilityRules[$audienceType])) {
            $validator->errors()->add(
                'trigger_type',
                "Trigger type '{$triggerType}' is not compatible with audience type '{$audienceType}'."
            );
        }
    }

    /**
     * Get valid events for a given trigger type.
     *
     * @param string $triggerType
     * @return array
     */
    private function getValidEventsForTriggerType(string $triggerType): array
    {
        return match ($triggerType) {
            'form_submission' => [
                'contact_form_submit',
                'newsletter_signup',
                'event_registration',
                'application_submit',
            ],
            'page_visit' => [
                'page_view',
                'landing_page_visit',
                'profile_view',
                'job_posting_view',
            ],
            'behavior' => [
                'email_open',
                'email_click',
                'download',
                'social_share',
                'profile_update',
            ],
            'manual' => [], // Manual triggers don't have specific events
            default => [],
        };
    }
}