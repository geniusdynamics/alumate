<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormBuilderRequest extends FormRequest
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
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'configuration' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'conditional_logic' => 'nullable|array',
            'crm_integration_config' => 'nullable|array',
            'crm_integration_config.enabled' => 'boolean',
            'crm_integration_config.provider' => 'required_if:crm_integration_config.enabled,true|in:salesforce,hubspot,pipedrive',
            'crm_integration_config.field_mappings' => 'nullable|array',
            'success_message' => 'nullable|string|max:500',
            'error_message' => 'nullable|string|max:500',
            'redirect_url' => 'nullable|url',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_type' => 'required|string|in:text,email,phone,textarea,select,radio,checkbox,file,date,number,url,hidden',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_label' => 'required|string|max:255',
            'fields.*.field_placeholder' => 'nullable|string|max:255',
            'fields.*.field_options' => 'nullable|array',
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.conditional_logic' => 'nullable|array',
            'fields.*.order_index' => 'nullable|integer|min:0',
            'fields.*.is_required' => 'boolean',
            'fields.*.is_visible' => 'boolean',
            'fields.*.crm_field_mapping' => 'nullable|array'
        ];
    }
}
