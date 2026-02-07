<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Payment method is required.',
        ];
    }
}
