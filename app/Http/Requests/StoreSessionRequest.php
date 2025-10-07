<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Request validation for storing session recording events
 *
 * Validates session tracking data including privacy consent and event structure
 * to ensure GDPR/CCPA compliance and data integrity.
 */
class StoreSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by ConsentMiddleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'session_id' => [
                'required',
                'string',
                'uuid',
                'max:36',
            ],
            'event_data' => [
                'required',
                'array',
                'min:1',
                'max:100', // Limit batch size to prevent abuse
            ],
            'event_data.*.type' => [
                'required',
                'string',
                'in:click,page_view,scroll,input_change,form_submit,error,rage_click,navigation',
                'max:50',
            ],
            'event_data.*.timestamp' => [
                'required',
                'date',
                'before_or_equal:now',
                'after:now -1 hour', // Prevent old events
            ],
            'event_data.*.url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
            ],
            'event_data.*.user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'event_data.*.element' => [
                'nullable',
                'string',
                'max:500',
            ],
            'event_data.*.value' => [
                'nullable',
                'string',
                'max:1000', // Limit sensitive data
            ],
            'event_data.*.position' => [
                'nullable',
                'array',
            ],
            'event_data.*.position.x' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000',
            ],
            'event_data.*.position.y' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000',
            ],
            'event_data.*.metadata' => [
                'nullable',
                'array',
                'max:10', // Limit metadata keys
            ],
            'consent_token' => [
                'required',
                'string',
                'regex:/^[a-f0-9]{64}$/', // SHA-256 hash
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required.',
            'session_id.uuid' => 'Session ID must be a valid UUID.',
            'event_data.required' => 'Event data is required.',
            'event_data.min' => 'At least one event must be provided.',
            'event_data.max' => 'Maximum 100 events per request.',
            'event_data.*.type.required' => 'Event type is required.',
            'event_data.*.type.in' => 'Invalid event type.',
            'event_data.*.timestamp.required' => 'Event timestamp is required.',
            'event_data.*.timestamp.before_or_equal' => 'Event timestamp cannot be in the future.',
            'event_data.*.timestamp.after' => 'Event timestamp is too old.',
            'event_data.*.url.url' => 'Invalid URL format.',
            'event_data.*.user_id.exists' => 'Invalid user ID.',
            'consent_token.required' => 'Consent token is required for privacy compliance.',
            'consent_token.regex' => 'Invalid consent token format.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'session_id' => 'session ID',
            'event_data' => 'event data',
            'event_data.*.type' => 'event type',
            'event_data.*.timestamp' => 'event timestamp',
            'event_data.*.url' => 'URL',
            'event_data.*.user_id' => 'user ID',
            'event_data.*.element' => 'element selector',
            'event_data.*.value' => 'element value',
            'event_data.*.position' => 'position coordinates',
            'event_data.*.position.x' => 'x coordinate',
            'event_data.*.position.y' => 'y coordinate',
            'event_data.*.metadata' => 'event metadata',
            'consent_token' => 'consent token',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and normalize event data
        $eventData = $this->input('event_data', []);

        $sanitizedEvents = array_map(function ($event) {
            // Remove any potentially sensitive data from element selectors
            if (isset($event['element'])) {
                $event['element'] = $this->sanitizeElementSelector($event['element']);
            }

            // Truncate long values to prevent abuse
            if (isset($event['value']) && strlen($event['value']) > 1000) {
                $event['value'] = substr($event['value'], 0, 1000);
            }

            // Ensure metadata is properly structured
            if (isset($event['metadata']) && is_array($event['metadata'])) {
                $event['metadata'] = array_slice($event['metadata'], 0, 10, true); // Limit to 10 keys
            }

            return $event;
        }, $eventData);

        $this->merge(['event_data' => $sanitizedEvents]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional privacy validation
        $this->validatePrivacyCompliance();

        // Validate event sequence integrity
        $this->validateEventSequence();
    }

    /**
     * Validate privacy compliance requirements
     */
    private function validatePrivacyCompliance(): void
    {
        $events = $this->input('event_data', []);

        foreach ($events as $event) {
            // Check for sensitive data patterns
            if (isset($event['value'])) {
                $this->validateSensitiveData($event['value'], $event['type'] ?? '');
            }

            // Validate URL privacy
            if (isset($event['url'])) {
                $this->validateUrlPrivacy($event['url']);
            }
        }
    }

    /**
     * Validate event sequence integrity
     */
    private function validateEventSequence(): void
    {
        $events = $this->input('event_data', []);
        $timestamps = array_column($events, 'timestamp');

        // Ensure events are in chronological order
        $sortedTimestamps = $timestamps;
        sort($sortedTimestamps);

        if ($timestamps !== $sortedTimestamps) {
            // Allow small timing variations but enforce general order
            $this->addFailure('event_data', 'Events must be in chronological order.');
        }

        // Check for duplicate timestamps (unlikely but possible)
        if (count($timestamps) !== count(array_unique($timestamps))) {
            $this->addFailure('event_data', 'Duplicate event timestamps detected.');
        }
    }

    /**
     * Sanitize element selectors to remove sensitive information
     */
    private function sanitizeElementSelector(string $selector): string
    {
        // Remove or mask sensitive selectors
        $sensitivePatterns = [
            '/input\[name=[\'"]?password[\'"]?\]/i',
            '/input\[name=[\'"]?email[\'"]?\]/i',
            '/input\[name=[\'"]?ssn[\'"]?\]/i',
            '/input\[name=[\'"]?credit[\'"]?\]/i',
            '/input\[type=[\'"]?password[\'"]?\]/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $selector)) {
                return '[SENSITIVE_INPUT]';
            }
        }

        return $selector;
    }

    /**
     * Validate sensitive data content
     */
    private function validateSensitiveData(string $value, string $eventType): void
    {
        // Only allow sensitive data for specific safe event types
        $allowedTypes = ['input_change', 'form_submit'];

        if (!in_array($eventType, $allowedTypes)) {
            // Check for potential sensitive patterns
            $sensitivePatterns = [
                '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
                '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Credit card
                '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            ];

            foreach ($sensitivePatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $this->addFailure('event_data', 'Sensitive data detected in event value.');
                    break;
                }
            }
        }
    }

    /**
     * Validate URL privacy compliance
     */
    private function validateUrlPrivacy(string $url): void
    {
        // Check for sensitive URL parameters
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $params);

            $sensitiveParams = ['password', 'token', 'key', 'secret', 'api_key'];

            foreach ($sensitiveParams as $param) {
                if (isset($params[$param])) {
                    $this->addFailure('event_data', 'Sensitive URL parameters detected.');
                    break;
                }
            }
        }
    }

    /**
     * Add a validation failure
     */
    private function addFailure(string $key, string $message): void
    {
        $validator = Validator::make([], []);
        $validator->errors()->add($key, $message);
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}