<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method_id' => 'required|string',
            'interval' => 'nullable|in:monthly,yearly',
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Please select a subscription plan.',
            'plan_id.exists' => 'The selected plan is invalid.',
            'payment_method_id.required' => 'Payment method is required.',
            'interval.in' => 'The interval must be monthly or yearly.',
        ];
    }
}
