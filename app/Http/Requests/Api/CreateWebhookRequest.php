<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $availableEvents = [
            'user.created', 'user.updated', 'user.deleted',
            'post.created', 'post.updated', 'post.deleted', 'post.liked', 'post.commented', 'post.shared',
            'connection.created', 'connection.accepted',
            'event.created', 'event.updated', 'event.registered', 'event.cancelled',
            'donation.completed', 'donation.failed', 'donation.refunded',
            'mentorship.requested', 'mentorship.accepted', 'mentorship.declined',
            'job.applied', 'achievement.earned', 'notification.sent',
        ];

        return [
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:'.implode(',', $availableEvents)],
            'secret' => ['nullable', 'string', 'min:8', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string', 'max:1000'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Webhook URL is required.',
            'url.url' => 'Please provide a valid URL.',
            'url.max' => 'URL cannot exceed 2048 characters.',
            'events.required' => 'At least one event must be selected.',
            'events.min' => 'At least one event must be selected.',
            'events.*.in' => 'Invalid event type selected.',
            'secret.min' => 'Secret must be at least 8 characters long.',
            'timeout.min' => 'Timeout must be at least 5 seconds.',
            'timeout.max' => 'Timeout cannot exceed 300 seconds.',
            'retry_attempts.max' => 'Maximum retry attempts is 10.',
        ];
    }
}
