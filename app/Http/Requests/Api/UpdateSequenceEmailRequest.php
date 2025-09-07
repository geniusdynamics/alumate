<?php

namespace App\Http\Requests\Api;

use App\Models\SequenceEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating sequence emails
 */
class UpdateSequenceEmailRequest extends FormRequest
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
        $rules = SequenceEmail::getValidationRules();

        // Remove sequence_id as it's handled by route model binding
        unset($rules['sequence_id']);

        // Make all fields optional for updates
        foreach ($rules as $field => $rule) {
            if (!str_contains($rule, 'required')) {
                $rules[$field] = 'sometimes|' . $rule;
            }
        }

        // Add unique validation for send_order if provided
        if ($this->has('send_order')) {
            $rules['send_order'] = [
                'sometimes',
                'integer',
                'min:0',
                Rule::unique('sequence_emails', 'send_order')
                    ->ignore($this->route('email')->id)
                    ->where('sequence_id', $this->route('sequence')->id)
            ];
        }

        // Add validation for template existence and tenant ownership if provided
        if ($this->has('template_id')) {
            $rules['template_id'] = [
                'sometimes',
                'exists:templates,id',
                Rule::exists('templates', 'id')
                    ->where('tenant_id', tenant()->id)
            ];
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
            'template_id.exists' => 'Selected template does not exist or is not accessible.',
            'subject_line.max' => 'Subject line cannot be longer than :max characters.',
            'delay_hours.integer' => 'Delay hours must be a valid number.',
            'delay_hours.min' => 'Delay hours cannot be negative.',
            'send_order.integer' => 'Send order must be a valid number.',
            'send_order.min' => 'Send order cannot be negative.',
            'send_order.unique' => 'This send order is already used in this sequence.',
            'trigger_conditions.array' => 'Trigger conditions must be a valid array.',
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
            'template_id' => 'email template',
            'subject_line' => 'subject line',
            'delay_hours' => 'delay hours',
            'send_order' => 'send order',
            'trigger_conditions' => 'trigger conditions',
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
            // Validate template accessibility if provided
            if ($this->has('template_id')) {
                $this->validateTemplateAccessibility($validator);
            }

            // Validate send order if provided
            if ($this->has('send_order')) {
                $this->validateSendOrderSequence($validator);
            }

            // Validate trigger conditions if provided
            if ($this->has('trigger_conditions') && !empty($this->trigger_conditions)) {
                $this->validateTriggerConditions($validator);
            }
        });
    }

    /**
     * Validate that the template is accessible to the current tenant.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateTemplateAccessibility($validator): void
    {
        $template = \App\Models\Template::where('id', $this->template_id)
            ->where('tenant_id', tenant()->id)
            ->first();

        if (!$template) {
            $validator->errors()->add(
                'template_id',
                'The selected template is not accessible to your organization.'
            );
        }

        if ($template && !$template->is_active) {
            $validator->errors()->add(
                'template_id',
                'The selected template is not active.'
            );
        }
    }

    /**
     * Validate send order doesn't create large gaps in sequence.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateSendOrderSequence($validator): void
    {
        $sequence = $this->route('sequence');
        $existingOrders = $sequence->sequenceEmails()
            ->where('id', '!=', $this->route('email')->id)
            ->pluck('send_order')
            ->sort()
            ->values()
            ->toArray();

        $requestedOrder = $this->send_order;

        // Check if this creates a gap larger than 1
        if (!empty($existingOrders)) {
            $maxExisting = max($existingOrders);

            if ($requestedOrder > $maxExisting + 1) {
                $validator->errors()->add(
                    'send_order',
                    'Send order cannot create gaps larger than 1. Next available order is ' . ($maxExisting + 1) . '.'
                );
            }
        }
    }

    /**
     * Validate trigger conditions structure.
     *
     * @param \Illuminate\Validation\Validator $validator
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

            // Validate event type
            $validEvents = [
                'email_open',
                'email_click',
                'link_click',
                'time_delay',
                'behavior_event',
            ];

            if (!in_array($condition['event'], $validEvents)) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.event",
                    "Event '{$condition['event']}' is not valid."
                );
            }

            // Validate condition parameters based on event type
            if (isset($condition['conditions']) && is_array($condition['conditions'])) {
                $this->validateConditionParameters($condition['event'], $condition['conditions'], $index, $validator);
            }
        }
    }

    /**
     * Validate condition parameters based on event type.
     *
     * @param string $event
     * @param array $conditions
     * @param int $index
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateConditionParameters(string $event, array $conditions, int $index, $validator): void
    {
        $requiredParams = match ($event) {
            'email_open' => [],
            'email_click' => ['link_url'],
            'link_click' => ['link_url'],
            'time_delay' => ['delay_minutes'],
            'behavior_event' => ['event_name'],
            default => [],
        };

        foreach ($requiredParams as $param) {
            if (!isset($conditions[$param])) {
                $validator->errors()->add(
                    "trigger_conditions.{$index}.conditions.{$param}",
                    "Parameter '{$param}' is required for event '{$event}'."
                );
            }
        }

        // Validate specific parameter formats
        if (isset($conditions['delay_minutes']) && (!is_int($conditions['delay_minutes']) || $conditions['delay_minutes'] < 0)) {
            $validator->errors()->add(
                "trigger_conditions.{$index}.conditions.delay_minutes",
                'Delay minutes must be a positive integer.'
            );
        }

        if (isset($conditions['link_url']) && !filter_var($conditions['link_url'], FILTER_VALIDATE_URL)) {
            $validator->errors()->add(
                "trigger_conditions.{$index}.conditions.link_url",
                'Link URL must be a valid URL.'
            );
        }
    }
}