<?php

namespace App\Http\Requests\Api;

use App\Models\EmailSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for creating email sequences
 */
class StoreEmailSequenceRequest extends FormRequest
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

        // Make some fields optional for creation
        $rules['description'] = 'nullable|string|max:1000';
        $rules['trigger_conditions'] = 'nullable|array';

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
            'name.required' => 'Email sequence name is required.',
            'name.max' => 'Email sequence name cannot be longer than :max characters.',
            'name.unique' => 'An email sequence with this name already exists.',
            'description.max' => 'Description cannot be longer than :max characters.',
            'audience_type.required' => 'Audience type is required.',
            'audience_type.in' => 'Selected audience type is invalid.',
            'trigger_type.required' => 'Trigger type is required.',
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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Set default tenant_id from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            $this->merge(['tenant_id' => auth()->user()->tenant_id]);
        }

        // Set default values
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if (!$this->has('trigger_conditions')) {
            $this->merge(['trigger_conditions' => []]);
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
            // Validate trigger conditions structure
            if ($this->has('trigger_conditions') && !empty($this->trigger_conditions)) {
                $this->validateTriggerConditions($validator);
            }

            // Validate audience type compatibility with trigger type
            if ($this->has('audience_type') && $this->has('trigger_type')) {
                $this->validateAudienceTriggerCompatibility($validator);
            }
        });
    }

    /**
     * Validate trigger conditions structure.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @throws \Exception
     */
    private function validateTriggerConditions($validator): void
    {
        $triggerConditions = $this->trigger_conditions;

        foreach ($triggerConditions as $index => $condition) {
            if (!isset($condition['event'])) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.event",
                    'Trigger condition must have an event.'
                );
                continue;
            }

            // Validate event type based on trigger_type
            $validEvents = $this->getValidEventsForTriggerType($this->trigger_type ?? 'manual');

            if (!in_array($condition['event'], $validEvents)) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.event",
                    "Event '{$condition['event']}' is not valid for trigger type '{$this->trigger_type}'."
                );
            }
        }
    }

    /**
     * Validate audience type compatibility with trigger type.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateAudienceTriggerCompatibility($validator): void
    {
        $audienceType = $this->audience_type;
        $triggerType = $this->trigger_type;

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