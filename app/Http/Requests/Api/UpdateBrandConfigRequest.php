<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandConfigRequest extends StoreBrandConfigRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Make fields optional for updates
        foreach (array_keys($rules) as $field) {
            if (in_array($field, ['name', 'tenant_id', 'created_by', 'updated_by'])) {
                continue; // Keep required fields required
            }

            if (is_array($rules[$field])) {
                array_unshift($rules[$field], 'sometimes');
            } elseif (is_string($rules[$field])) {
                $rules[$field] = 'sometimes|' . $rules[$field];
            }
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
        return array_merge(parent::messages(), [
            'name.unique' => 'A brand configuration with this name already exists.',
        ]);
    }
}