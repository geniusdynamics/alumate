<?php

namespace App\Http\Requests\Forms;

use App\Rules\PhoneNumber;
use App\Rules\InstitutionalDomain;

class DynamicFormRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $formConfig = $this->input('_form_config', []);
        $rules = $this->getSpamProtectionRules();
        
        if (isset($formConfig['fields']) && is_array($formConfig['fields'])) {
            foreach ($formConfig['fields'] as $field) {
                $fieldRules = $this->buildFieldRules($field);
                if (!empty($fieldRules)) {
                    $rules[$field['name']] = $fieldRules;
                }
            }
        }
        
        return $rules;
    }

    /**
     * Build validation rules for a specific field
     */
    private function buildFieldRules(array $field): array
    {
        $rules = [];
        
        // Required validation
        if ($field['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }
        
        // Type-specific validation
        switch ($field['type']) {
            case 'text':
            case 'textarea':
                $rules[] = 'string';
                if (isset($field['min_length'])) {
                    $rules[] = 'min:' . $field['min_length'];
                }
                if (isset($field['max_length'])) {
                    $rules[] = 'max:' . $field['max_length'];
                } else {
                    $rules[] = $field['type'] === 'textarea' ? 'max:5000' : 'max:255';
                }
                
                // Add pattern validation if specified
                if (isset($field['pattern'])) {
                    $rules[] = 'regex:' . $field['pattern'];
                }
                break;
                
            case 'email':
                $rules[] = 'email:rfc,dns';
                $rules[] = 'max:255';
                
                // Check if institutional domain is required
                if ($field['institutional_only'] ?? false) {
                    $rules[] = new InstitutionalDomain();
                }
                break;
                
            case 'phone':
                $rules[] = new PhoneNumber();
                break;
                
            case 'url':
                $rules[] = 'url';
                $rules[] = 'max:2048';
                break;
                
            case 'number':
                $rules[] = 'numeric';
                if (isset($field['min'])) {
                    $rules[] = 'min:' . $field['min'];
                }
                if (isset($field['max'])) {
                    $rules[] = 'max:' . $field['max'];
                }
                break;
                
            case 'integer':
                $rules[] = 'integer';
                if (isset($field['min'])) {
                    $rules[] = 'min:' . $field['min'];
                }
                if (isset($field['max'])) {
                    $rules[] = 'max:' . $field['max'];
                }
                break;
                
            case 'date':
                $rules[] = 'date';
                if (isset($field['after'])) {
                    $rules[] = 'after:' . $field['after'];
                }
                if (isset($field['before'])) {
                    $rules[] = 'before:' . $field['before'];
                }
                break;
                
            case 'datetime':
                $rules[] = 'date_format:Y-m-d H:i:s';
                break;
                
            case 'time':
                $rules[] = 'date_format:H:i';
                break;
                
            case 'select':
            case 'radio':
                if (isset($field['options']) && is_array($field['options'])) {
                    $validOptions = array_column($field['options'], 'value');
                    $rules[] = 'in:' . implode(',', $validOptions);
                }
                break;
                
            case 'checkbox':
                if ($field['single'] ?? false) {
                    $rules[] = 'boolean';
                    if ($field['required'] ?? false) {
                        $rules[] = 'accepted';
                    }
                } else {
                    $rules[] = 'array';
                    if (isset($field['min_selections'])) {
                        $rules[] = 'min:' . $field['min_selections'];
                    }
                    if (isset($field['max_selections'])) {
                        $rules[] = 'max:' . $field['max_selections'];
                    }
                    
                    // Validate individual checkbox values
                    if (isset($field['options']) && is_array($field['options'])) {
                        $validOptions = array_column($field['options'], 'value');
                        $rules[$field['name'] . '.*'] = 'in:' . implode(',', $validOptions);
                    }
                }
                break;
                
            case 'file':
                $rules[] = 'file';
                if (isset($field['max_size'])) {
                    $rules[] = 'max:' . $field['max_size']; // in KB
                }
                if (isset($field['mime_types'])) {
                    $rules[] = 'mimes:' . implode(',', $field['mime_types']);
                }
                break;
                
            case 'image':
                $rules[] = 'image';
                if (isset($field['max_size'])) {
                    $rules[] = 'max:' . $field['max_size']; // in KB
                }
                if (isset($field['dimensions'])) {
                    $dimensionRules = [];
                    if (isset($field['dimensions']['min_width'])) {
                        $dimensionRules[] = 'min_width=' . $field['dimensions']['min_width'];
                    }
                    if (isset($field['dimensions']['max_width'])) {
                        $dimensionRules[] = 'max_width=' . $field['dimensions']['max_width'];
                    }
                    if (isset($field['dimensions']['min_height'])) {
                        $dimensionRules[] = 'min_height=' . $field['dimensions']['min_height'];
                    }
                    if (isset($field['dimensions']['max_height'])) {
                        $dimensionRules[] = 'max_height=' . $field['dimensions']['max_height'];
                    }
                    if (!empty($dimensionRules)) {
                        $rules[] = 'dimensions:' . implode(',', $dimensionRules);
                    }
                }
                break;
        }
        
        // Custom validation rules from field configuration
        if (isset($field['validation']) && is_array($field['validation'])) {
            foreach ($field['validation'] as $validationRule) {
                if (isset($validationRule['rule'])) {
                    $rules[] = $this->buildCustomRule($validationRule);
                }
            }
        }
        
        return array_filter($rules);
    }

    /**
     * Build custom validation rule from configuration
     */
    private function buildCustomRule(array $ruleConfig): string
    {
        $rule = $ruleConfig['rule'];
        
        switch ($rule) {
            case 'min_length':
                return 'min:' . ($ruleConfig['value'] ?? 1);
                
            case 'max_length':
                return 'max:' . ($ruleConfig['value'] ?? 255);
                
            case 'pattern':
                return 'regex:' . ($ruleConfig['value'] ?? '/.*/');
                
            case 'unique':
                $table = $ruleConfig['table'] ?? 'users';
                $column = $ruleConfig['column'] ?? 'email';
                return "unique:{$table},{$column}";
                
            case 'exists':
                $table = $ruleConfig['table'] ?? 'users';
                $column = $ruleConfig['column'] ?? 'id';
                return "exists:{$table},{$column}";
                
            case 'confirmed':
                return 'confirmed';
                
            case 'same':
                return 'same:' . ($ruleConfig['field'] ?? 'password');
                
            case 'different':
                return 'different:' . ($ruleConfig['field'] ?? 'email');
                
            case 'alpha':
                return 'alpha';
                
            case 'alpha_num':
                return 'alpha_num';
                
            case 'alpha_dash':
                return 'alpha_dash';
                
            case 'json':
                return 'json';
                
            case 'ip':
                return 'ip';
                
            case 'ipv4':
                return 'ipv4';
                
            case 'ipv6':
                return 'ipv6';
                
            case 'mac_address':
                return 'mac_address';
                
            case 'uuid':
                return 'uuid';
                
            default:
                return $rule;
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $messages = parent::messages();
        $formConfig = $this->input('_form_config', []);
        
        if (isset($formConfig['fields']) && is_array($formConfig['fields'])) {
            foreach ($formConfig['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldLabel = $field['label'] ?? $fieldName;
                
                // Add custom messages for this field
                if (isset($field['validation']) && is_array($field['validation'])) {
                    foreach ($field['validation'] as $validationRule) {
                        if (isset($validationRule['message'])) {
                            $ruleName = $validationRule['rule'];
                            $messages["{$fieldName}.{$ruleName}"] = $validationRule['message'];
                        }
                    }
                }
                
                // Add default messages with field label
                $messages["{$fieldName}.required"] = "The {$fieldLabel} field is required.";
                $messages["{$fieldName}.email"] = "The {$fieldLabel} must be a valid email address.";
                $messages["{$fieldName}.min"] = "The {$fieldLabel} must be at least :min characters.";
                $messages["{$fieldName}.max"] = "The {$fieldLabel} may not be greater than :max characters.";
            }
        }
        
        return $messages;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $formConfig = $this->input('_form_config', []);
        
        if (isset($formConfig['fields']) && is_array($formConfig['fields'])) {
            foreach ($formConfig['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldLabel = $field['label'] ?? $fieldName;
                $attributes[$fieldName] = strtolower($fieldLabel);
            }
        }
        
        return $attributes;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateFormConfiguration($validator);
            $this->validateConditionalFields($validator);
            $this->validateFieldDependencies($validator);
        });
    }

    /**
     * Validate form configuration
     */
    private function validateFormConfiguration($validator): void
    {
        $formConfig = $this->input('_form_config', []);
        
        if (empty($formConfig)) {
            $validator->errors()->add('_form_config', 'Form configuration is required.');
            return;
        }
        
        if (!isset($formConfig['fields']) || !is_array($formConfig['fields'])) {
            $validator->errors()->add('_form_config', 'Form must have valid field configuration.');
            return;
        }
        
        if (count($formConfig['fields']) === 0) {
            $validator->errors()->add('_form_config', 'Form must have at least one field.');
        }
    }

    /**
     * Validate conditional fields
     */
    private function validateConditionalFields($validator): void
    {
        $formConfig = $this->input('_form_config', []);
        
        if (!isset($formConfig['fields'])) {
            return;
        }
        
        foreach ($formConfig['fields'] as $field) {
            if (isset($field['conditional']) && $field['conditional']) {
                $condition = $field['condition'] ?? [];
                $conditionField = $condition['field'] ?? null;
                $conditionValue = $condition['value'] ?? null;
                $conditionOperator = $condition['operator'] ?? 'equals';
                
                if ($conditionField && $conditionValue !== null) {
                    $actualValue = $this->input($conditionField);
                    $conditionMet = $this->evaluateCondition($actualValue, $conditionValue, $conditionOperator);
                    
                    // If condition is met, validate the conditional field
                    if ($conditionMet && ($field['required'] ?? false)) {
                        $fieldValue = $this->input($field['name']);
                        if (empty($fieldValue)) {
                            $fieldLabel = $field['label'] ?? $field['name'];
                            $validator->errors()->add($field['name'], "The {$fieldLabel} field is required when {$conditionField} is {$conditionValue}.");
                        }
                    }
                }
            }
        }
    }

    /**
     * Evaluate conditional logic
     */
    private function evaluateCondition($actualValue, $expectedValue, string $operator): bool
    {
        switch ($operator) {
            case 'equals':
                return $actualValue == $expectedValue;
            case 'not_equals':
                return $actualValue != $expectedValue;
            case 'contains':
                return is_string($actualValue) && str_contains($actualValue, $expectedValue);
            case 'not_contains':
                return is_string($actualValue) && !str_contains($actualValue, $expectedValue);
            case 'greater_than':
                return is_numeric($actualValue) && $actualValue > $expectedValue;
            case 'less_than':
                return is_numeric($actualValue) && $actualValue < $expectedValue;
            case 'in':
                return is_array($expectedValue) && in_array($actualValue, $expectedValue);
            case 'not_in':
                return is_array($expectedValue) && !in_array($actualValue, $expectedValue);
            default:
                return false;
        }
    }

    /**
     * Validate field dependencies
     */
    private function validateFieldDependencies($validator): void
    {
        $formConfig = $this->input('_form_config', []);
        
        if (!isset($formConfig['fields'])) {
            return;
        }
        
        foreach ($formConfig['fields'] as $field) {
            if (isset($field['dependencies']) && is_array($field['dependencies'])) {
                foreach ($field['dependencies'] as $dependency) {
                    $dependentField = $dependency['field'] ?? null;
                    $dependentValue = $dependency['value'] ?? null;
                    
                    if ($dependentField && $dependentValue !== null) {
                        $actualValue = $this->input($dependentField);
                        if ($actualValue !== $dependentValue) {
                            $fieldLabel = $field['label'] ?? $field['name'];
                            $dependentLabel = $dependency['label'] ?? $dependentField;
                            $validator->errors()->add($field['name'], "The {$fieldLabel} field requires {$dependentLabel} to be {$dependentValue}.");
                        }
                    }
                }
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        
        // Process form configuration if it's a JSON string
        $formConfig = $this->input('_form_config');
        if (is_string($formConfig)) {
            try {
                $decodedConfig = json_decode($formConfig, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge(['_form_config' => $decodedConfig]);
                }
            } catch (\Exception $e) {
                // Keep original value if JSON decode fails
            }
        }
    }
}
