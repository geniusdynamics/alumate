<?php

namespace App\Services;

use App\Models\FormBuilder;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Jobs\ProcessFormSubmissionToCrm;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FormBuilderService
{
    public function __construct(
        private CrmIntegrationService $crmService
    ) {}

    public function createForm(array $data): FormBuilder
    {
        $form = FormBuilder::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'page_id' => $data['page_id'] ?? null,
            'configuration' => $data['configuration'] ?? [],
            'validation_rules' => $data['validation_rules'] ?? [],
            'conditional_logic' => $data['conditional_logic'] ?? [],
            'crm_integration_config' => $data['crm_integration_config'] ?? [],
            'success_message' => $data['success_message'] ?? 'Thank you for your submission!',
            'error_message' => $data['error_message'] ?? 'Please correct the errors below.',
            'redirect_url' => $data['redirect_url'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'tenant_id' => tenant('id')
        ]);

        // Create form fields if provided
        if (isset($data['fields']) && is_array($data['fields'])) {
            $this->createFormFields($form, $data['fields']);
        }

        return $form->load('fields');
    }

    public function updateForm(FormBuilder $form, array $data): FormBuilder
    {
        $form->update([
            'name' => $data['name'] ?? $form->name,
            'description' => $data['description'] ?? $form->description,
            'configuration' => $data['configuration'] ?? $form->configuration,
            'validation_rules' => $data['validation_rules'] ?? $form->validation_rules,
            'conditional_logic' => $data['conditional_logic'] ?? $form->conditional_logic,
            'crm_integration_config' => $data['crm_integration_config'] ?? $form->crm_integration_config,
            'success_message' => $data['success_message'] ?? $form->success_message,
            'error_message' => $data['error_message'] ?? $form->error_message,
            'redirect_url' => $data['redirect_url'] ?? $form->redirect_url,
            'is_active' => $data['is_active'] ?? $form->is_active
        ]);

        // Update form fields if provided
        if (isset($data['fields']) && is_array($data['fields'])) {
            $this->updateFormFields($form, $data['fields']);
        }

        return $form->load('fields');
    }
}    pu
blic function createFormFields(FormBuilder $form, array $fieldsData): void
    {
        foreach ($fieldsData as $index => $fieldData) {
            FormField::create([
                'form_id' => $form->id,
                'field_type' => $fieldData['field_type'],
                'field_name' => $fieldData['field_name'],
                'field_label' => $fieldData['field_label'],
                'field_placeholder' => $fieldData['field_placeholder'] ?? null,
                'field_options' => $fieldData['field_options'] ?? [],
                'validation_rules' => $fieldData['validation_rules'] ?? [],
                'conditional_logic' => $fieldData['conditional_logic'] ?? [],
                'order_index' => $fieldData['order_index'] ?? $index,
                'is_required' => $fieldData['is_required'] ?? false,
                'is_visible' => $fieldData['is_visible'] ?? true,
                'crm_field_mapping' => $fieldData['crm_field_mapping'] ?? []
            ]);
        }
    }

    public function updateFormFields(FormBuilder $form, array $fieldsData): void
    {
        // Delete existing fields
        $form->fields()->delete();
        
        // Create new fields
        $this->createFormFields($form, $fieldsData);
    }

    public function processFormSubmission(FormBuilder $form, array $submissionData, array $metadata = []): FormSubmission
    {
        // Validate submission data
        $validatedData = $this->validateSubmissionData($form, $submissionData);
        
        // Create submission record
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submission_data' => $validatedData,
            'user_ip' => $metadata['user_ip'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'referrer_url' => $metadata['referrer_url'] ?? request()->header('referer'),
            'utm_source' => $metadata['utm_source'] ?? null,
            'utm_medium' => $metadata['utm_medium'] ?? null,
            'utm_campaign' => $metadata['utm_campaign'] ?? null,
            'tenant_id' => tenant('id')
        ]);

        // Queue CRM integration if configured
        if ($this->shouldSyncToCrm($form)) {
            ProcessFormSubmissionToCrm::dispatch($submission);
        }

        return $submission;
    }

    private function validateSubmissionData(FormBuilder $form, array $data): array
    {
        $rules = [];
        $messages = [];
        
        foreach ($form->fields as $field) {
            if ($field->is_required) {
                $rules[$field->field_name] = 'required';
            }
            
            // Add field-specific validation rules
            if (!empty($field->validation_rules)) {
                $fieldRules = $field->validation_rules;
                if (isset($rules[$field->field_name])) {
                    $rules[$field->field_name] .= '|' . implode('|', $fieldRules);
                } else {
                    $rules[$field->field_name] = implode('|', $fieldRules);
                }
            }
            
            // Add field type specific validation
            $rules[$field->field_name] = $this->addFieldTypeValidation($field, $rules[$field->field_name] ?? '');
        }

        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}    
private function addFieldTypeValidation(FormField $field, string $existingRules): string
    {
        $typeRules = [];
        
        switch ($field->field_type) {
            case 'email':
                $typeRules[] = 'email';
                break;
            case 'phone':
                $typeRules[] = 'regex:/^[\+]?[1-9][\d]{0,15}$/';
                break;
            case 'url':
                $typeRules[] = 'url';
                break;
            case 'number':
                $typeRules[] = 'numeric';
                break;
            case 'date':
                $typeRules[] = 'date';
                break;
            case 'file':
                $typeRules[] = 'file';
                if (isset($field->field_options['max_size'])) {
                    $typeRules[] = 'max:' . $field->field_options['max_size'];
                }
                if (isset($field->field_options['allowed_types'])) {
                    $typeRules[] = 'mimes:' . implode(',', $field->field_options['allowed_types']);
                }
                break;
        }
        
        if (!empty($typeRules)) {
            return $existingRules ? $existingRules . '|' . implode('|', $typeRules) : implode('|', $typeRules);
        }
        
        return $existingRules;
    }

    private function shouldSyncToCrm(FormBuilder $form): bool
    {
        return !empty($form->crm_integration_config) && 
               isset($form->crm_integration_config['enabled']) && 
               $form->crm_integration_config['enabled'] === true;
    }

    public function evaluateConditionalLogic(FormBuilder $form, array $submissionData): array
    {
        $visibleFields = [];
        
        foreach ($form->fields as $field) {
            $isVisible = true;
            
            if (!empty($field->conditional_logic)) {
                $isVisible = $this->evaluateFieldConditions($field->conditional_logic, $submissionData);
            }
            
            $visibleFields[$field->field_name] = $isVisible;
        }
        
        return $visibleFields;
    }

    private function evaluateFieldConditions(array $conditions, array $data): bool
    {
        if (empty($conditions)) {
            return true;
        }
        
        $logic = $conditions['logic'] ?? 'and'; // 'and' or 'or'
        $rules = $conditions['rules'] ?? [];
        
        $results = [];
        
        foreach ($rules as $rule) {
            $fieldName = $rule['field'];
            $operator = $rule['operator'];
            $value = $rule['value'];
            $fieldValue = $data[$fieldName] ?? null;
            
            $results[] = $this->evaluateCondition($fieldValue, $operator, $value);
        }
        
        return $logic === 'and' ? !in_array(false, $results) : in_array(true, $results);
    }

    private function evaluateCondition($fieldValue, string $operator, $expectedValue): bool
    {
        switch ($operator) {
            case 'equals':
                return $fieldValue == $expectedValue;
            case 'not_equals':
                return $fieldValue != $expectedValue;
            case 'contains':
                return str_contains((string)$fieldValue, (string)$expectedValue);
            case 'not_contains':
                return !str_contains((string)$fieldValue, (string)$expectedValue);
            case 'greater_than':
                return (float)$fieldValue > (float)$expectedValue;
            case 'less_than':
                return (float)$fieldValue < (float)$expectedValue;
            case 'is_empty':
                return empty($fieldValue);
            case 'is_not_empty':
                return !empty($fieldValue);
            default:
                return true;
        }
    }
}