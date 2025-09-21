import { ref } from 'vue';

interface FormField {
    field_type: string;
    field_name: string;
    field_label: string;
    field_placeholder?: string;
    field_options?: Array<{ value: string; label: string }>;
    validation_rules?: string[];
    conditional_logic?: {
        logic: 'and' | 'or';
        rules: Array<{
            field: string;
            operator: string;
            value: string;
        }>;
    };
    is_required: boolean;
    is_visible: boolean;
    crm_field_mapping?: {
        crm_field: string;
    };
    order_index: number;
}

interface FormBuilder {
    id?: number;
    name: string;
    description?: string;
    page_id?: number;
    configuration?: Record<string, any>;
    validation_rules?: Record<string, any>;
    conditional_logic?: Record<string, any>;
    crm_integration_config?: {
        enabled: boolean;
        provider: string;
        field_mappings: Record<string, string>;
    };
    success_message?: string;
    error_message?: string;
    redirect_url?: string;
    is_active: boolean;
    fields?: FormField[];
}

interface FormSubmission {
    id: number;
    form_id: number;
    submission_data: Record<string, any>;
    crm_sync_status: 'pending' | 'synced' | 'failed';
    status: 'pending' | 'processed' | 'failed' | 'synced';
    created_at: string;
}

export function useFormBuilder() {
    const forms = ref<FormBuilder[]>([]);
    const currentForm = ref<FormBuilder | null>(null);
    const fieldTypes = ref<Record<string, any>>({});
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const loadForms = async (pageId?: number) => {
        isLoading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (pageId) params.append('page_id', pageId.toString());

            const response = await fetch(`/api/form-builders?${params}`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load forms');
            }

            const data = await response.json();
            forms.value = data.data || [];
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error loading forms:', err);
        } finally {
            isLoading.value = false;
        }
    };

    const loadForm = async (formId: number) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/form-builders/${formId}`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load form');
            }

            const data = await response.json();
            currentForm.value = data;
            return data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error loading form:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createForm = async (formData: Partial<FormBuilder>) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/form-builders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create form');
            }

            const data = await response.json();
            currentForm.value = data.form;
            forms.value.unshift(data.form);
            return data.form;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error creating form:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateForm = async (formId: number, formData: Partial<FormBuilder>) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/form-builders/${formId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update form');
            }

            const data = await response.json();
            currentForm.value = data.form;

            // Update in forms list
            const index = forms.value.findIndex((f) => f.id === formId);
            if (index !== -1) {
                forms.value[index] = data.form;
            }

            return data.form;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error updating form:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteForm = async (formId: number) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/form-builders/${formId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to delete form');
            }

            // Remove from forms list
            forms.value = forms.value.filter((f) => f.id !== formId);

            if (currentForm.value?.id === formId) {
                currentForm.value = null;
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error deleting form:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const loadFieldTypes = async () => {
        try {
            const response = await fetch('/api/form-builders/field-types', {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load field types');
            }

            const data = await response.json();
            fieldTypes.value = data;
        } catch (err) {
            console.error('Error loading field types:', err);
        }
    };

    const submitForm = async (formId: number, submissionData: Record<string, any>, metadata?: Record<string, any>) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/form-builders/${formId}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    ...submissionData,
                    ...metadata,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Form submission failed');
            }

            return data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error submitting form:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const evaluateConditionalLogic = async (formId: number, submissionData: Record<string, any>) => {
        try {
            const response = await fetch(`/api/form-builders/${formId}/conditional-logic`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(submissionData),
            });

            if (!response.ok) {
                throw new Error('Failed to evaluate conditional logic');
            }

            const data = await response.json();
            return data.visible_fields;
        } catch (err) {
            console.error('Error evaluating conditional logic:', err);
            return {};
        }
    };

    return {
        // State
        forms,
        currentForm,
        fieldTypes,
        isLoading,
        error,

        // Methods
        loadForms,
        loadForm,
        createForm,
        updateForm,
        deleteForm,
        loadFieldTypes,
        submitForm,
        evaluateConditionalLogic,
    };
}
