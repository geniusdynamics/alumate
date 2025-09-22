<template>
    <div class="template-customizer">
        <!-- Header -->
        <div class="customizer-header">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Customize Form Template</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Add, remove, or modify fields to create your perfect form</p>
        </div>

        <!-- Template Selection -->
        <div class="template-selection mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Base Template </label>
            <select
                v-model="selectedTemplateId"
                @change="loadTemplate"
                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">Select a template...</option>
                <option v-for="template in availableTemplates" :key="template.id" :value="template.id">
                    {{ template.name }}
                </option>
            </select>
        </div>

        <!-- Customization Options -->
        <div v-if="currentTemplate" class="customization-options">
            <!-- Preset Customizations -->
            <div class="preset-section mb-6">
                <h4 class="text-md mb-3 font-medium text-gray-900 dark:text-white">Quick Presets</h4>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <button
                        v-for="preset in availablePresets"
                        :key="preset.id"
                        @click="applyPreset(preset.id)"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700"
                    >
                        {{ preset.name }}
                    </button>
                </div>
            </div>

            <!-- Field Management -->
            <div class="field-management mb-6">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white">Form Fields</h4>
                    <button
                        @click="showAddFieldModal = true"
                        class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white transition-colors hover:bg-blue-700"
                    >
                        Add Field
                    </button>
                </div>

                <!-- Field List -->
                <div class="field-list space-y-3">
                    <div
                        v-for="(field, index) in customizedTemplate.config.fields"
                        :key="field.id"
                        class="field-item rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                        :class="{ 'opacity-50': field.disabled }"
                    >
                        <div class="flex items-center justify-between">
                            <div class="field-info flex-1">
                                <div class="flex items-center space-x-3">
                                    <span
                                        class="field-type-badge rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ field.type }}
                                    </span>
                                    <span class="field-name font-medium text-gray-900 dark:text-white">
                                        {{ field.label }}
                                    </span>
                                    <span v-if="field.required" class="text-sm text-red-500">*</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ field.placeholder || field.helpText || 'No description' }}
                                </p>
                            </div>

                            <!-- Field Actions -->
                            <div class="field-actions flex items-center space-x-2">
                                <button
                                    @click="moveField(index, -1)"
                                    :disabled="index === 0"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Move up"
                                >
                                    <ChevronUpIcon class="h-4 w-4" />
                                </button>
                                <button
                                    @click="moveField(index, 1)"
                                    :disabled="index === customizedTemplate.config.fields.length - 1"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Move down"
                                >
                                    <ChevronDownIcon class="h-4 w-4" />
                                </button>
                                <button @click="editField(field)" class="p-1 text-blue-600 hover:text-blue-800" title="Edit field">
                                    <PencilIcon class="h-4 w-4" />
                                </button>
                                <button
                                    @click="removeField(field.id)"
                                    :disabled="isRequiredField(field.id)"
                                    class="p-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Remove field"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Configuration -->
            <div class="template-config mb-6">
                <h4 class="text-md mb-3 font-medium text-gray-900 dark:text-white">Template Settings</h4>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Form Title </label>
                        <input
                            v-model="customizedTemplate.config.title"
                            type="text"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Layout </label>
                        <select
                            v-model="customizedTemplate.config.layout"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="single-column">Single Column</option>
                            <option value="two-column">Two Column</option>
                            <option value="grid">Grid</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Theme </label>
                        <select
                            v-model="customizedTemplate.config.theme"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="default">Default</option>
                            <option value="minimal">Minimal</option>
                            <option value="modern">Modern</option>
                            <option value="classic">Classic</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Scheme </label>
                        <select
                            v-model="customizedTemplate.config.colorScheme"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="default">Default</option>
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="accent">Accent</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="preview-section mb-6">
                <h4 class="text-md mb-3 font-medium text-gray-900 dark:text-white">Preview</h4>
                <div class="preview-container rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <FormBase :config="customizedTemplate.config" :preview-mode="true" class="mx-auto max-w-2xl" />
                </div>
            </div>

            <!-- Actions -->
            <div class="actions flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <button @click="saveCustomization" class="rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                        Save Template
                    </button>
                    <button
                        @click="exportTemplate"
                        class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Export JSON
                    </button>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="resetTemplate" class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Reset
                    </button>
                    <button
                        @click="validateTemplate"
                        class="rounded-md border border-green-300 px-4 py-2 text-green-700 transition-colors hover:bg-green-50 dark:border-green-600 dark:text-green-400 dark:hover:bg-green-900/20"
                    >
                        Validate
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Field Modal -->
        <FieldEditorModal
            v-if="showAddFieldModal"
            :field="null"
            :available-field-types="allowedFieldTypes"
            @save="addField"
            @cancel="showAddFieldModal = false"
        />

        <!-- Edit Field Modal -->
        <FieldEditorModal
            v-if="showEditFieldModal && editingField"
            :field="editingField"
            :available-field-types="allowedFieldTypes"
            @save="updateField"
            @cancel="showEditFieldModal = false"
        />

        <!-- Validation Results Modal -->
        <div v-if="showValidationResults" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Validation Results</h3>
                <div v-if="validationResults.valid" class="text-green-600 dark:text-green-400">
                    <CheckCircleIcon class="mr-2 inline h-5 w-5" />
                    Template is valid and ready to use!
                </div>
                <div v-else class="text-red-600 dark:text-red-400">
                    <XCircleIcon class="mr-2 inline h-5 w-5" />
                    <p class="mb-2">Template has validation errors:</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li v-for="error in validationResults.errors" :key="error" class="text-sm">
                            {{ error }}
                        </li>
                    </ul>
                </div>
                <div class="mt-4 flex justify-end">
                    <button
                        @click="showValidationResults = false"
                        class="rounded-md bg-gray-600 px-4 py-2 text-white transition-colors hover:bg-gray-700"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { FieldCustomizationOptions, FormField, FormTemplate } from '@/types/components';
import { CheckCircleIcon, ChevronDownIcon, ChevronUpIcon, PencilIcon, TrashIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';
import FieldEditorModal from './FieldEditorModal.vue';
import FormBase from './FormBase.vue';
import { formTemplates } from './templates';
import { TemplateCustomizer } from './templates/customization';

// Props
interface Props {
    initialTemplateId?: string;
    customizationOptions?: FieldCustomizationOptions;
}

const props = withDefaults(defineProps<Props>(), {
    initialTemplateId: '',
    customizationOptions: () => ({
        allowAdd: true,
        allowRemove: true,
        allowModify: true,
        allowReorder: true,
    }),
});

// Emits
const emit = defineEmits<{
    templateSaved: [template: FormTemplate];
    templateExported: [json: string];
}>();

// Reactive state
const selectedTemplateId = ref(props.initialTemplateId);
const currentTemplate = ref<FormTemplate | null>(null);
const customizedTemplate = ref<FormTemplate | null>(null);
const customizer = ref<TemplateCustomizer | null>(null);

const showAddFieldModal = ref(false);
const showEditFieldModal = ref(false);
const editingField = ref<FormField | null>(null);
const showValidationResults = ref(false);
const validationResults = ref<{ valid: boolean; errors: string[] }>({ valid: true, errors: [] });

// Computed properties
const availableTemplates = computed(() => formTemplates);

const availablePresets = computed(() => [
    { id: 'minimal', name: 'Minimal' },
    { id: 'detailed', name: 'Detailed' },
    { id: 'gdpr-compliant', name: 'GDPR Compliant' },
]);

const allowedFieldTypes = computed(() => {
    return (
        props.customizationOptions.allowedFieldTypes || ['text', 'email', 'phone', 'select', 'checkbox', 'textarea', 'radio', 'number', 'url', 'date']
    );
});

const requiredFields = computed(() => {
    return props.customizationOptions.requiredFields || [];
});

// Methods
const loadTemplate = () => {
    if (!selectedTemplateId.value) {
        currentTemplate.value = null;
        customizedTemplate.value = null;
        customizer.value = null;
        return;
    }

    const template = formTemplates.find((t) => t.id === selectedTemplateId.value);
    if (template) {
        currentTemplate.value = { ...template };
        customizedTemplate.value = { ...template };
        customizer.value = new TemplateCustomizer(template);
    }
};

const applyPreset = (presetId: string) => {
    if (!currentTemplate.value || !customizer.value) return;

    try {
        const preset = TemplateCustomizer.createPreset(presetId, currentTemplate.value.id);
        const newTemplate = customizer.value.applyCustomizations(preset);
        customizedTemplate.value = newTemplate;
    } catch (error) {
        console.error('Failed to apply preset:', error);
    }
};

const addField = (field: FormField) => {
    if (!customizer.value || !customizedTemplate.value) return;

    try {
        const newTemplate = customizer.value.addField(field);
        customizedTemplate.value = newTemplate;
        showAddFieldModal.value = false;
    } catch (error) {
        console.error('Failed to add field:', error);
    }
};

const removeField = (fieldId: string) => {
    if (!customizer.value || !customizedTemplate.value) return;

    try {
        const newTemplate = customizer.value.removeField(fieldId, props.customizationOptions);
        customizedTemplate.value = newTemplate;
    } catch (error) {
        console.error('Failed to remove field:', error);
        alert(error.message);
    }
};

const editField = (field: FormField) => {
    editingField.value = { ...field };
    showEditFieldModal.value = true;
};

const updateField = (updatedField: FormField) => {
    if (!customizer.value || !customizedTemplate.value) return;

    try {
        const newTemplate = customizer.value.modifyField(updatedField.id, updatedField);
        customizedTemplate.value = newTemplate;
        showEditFieldModal.value = false;
        editingField.value = null;
    } catch (error) {
        console.error('Failed to update field:', error);
    }
};

const moveField = (index: number, direction: number) => {
    if (!customizedTemplate.value) return;

    const fields = [...customizedTemplate.value.config.fields];
    const newIndex = index + direction;

    if (newIndex < 0 || newIndex >= fields.length) return;

    // Swap fields
    [fields[index], fields[newIndex]] = [fields[newIndex], fields[index]];

    customizedTemplate.value.config.fields = fields;
};

const isRequiredField = (fieldId: string): boolean => {
    return requiredFields.value.includes(fieldId);
};

const saveCustomization = () => {
    if (!customizedTemplate.value) return;

    // Validate before saving
    const validation = validateCurrentTemplate();
    if (!validation.valid) {
        validationResults.value = validation;
        showValidationResults.value = true;
        return;
    }

    emit('templateSaved', customizedTemplate.value);
};

const exportTemplate = () => {
    if (!customizer.value) return;

    const json = customizer.value.exportCustomization();
    emit('templateExported', json);

    // Download as file
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${customizedTemplate.value?.id || 'template'}-customized.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const resetTemplate = () => {
    if (currentTemplate.value) {
        customizedTemplate.value = { ...currentTemplate.value };
        customizer.value = new TemplateCustomizer(currentTemplate.value);
    }
};

const validateTemplate = () => {
    const validation = validateCurrentTemplate();
    validationResults.value = validation;
    showValidationResults.value = true;
};

const validateCurrentTemplate = (): { valid: boolean; errors: string[] } => {
    if (!customizer.value || !customizedTemplate.value) {
        return { valid: false, errors: ['No template loaded'] };
    }

    // Create a mock customization to validate
    const mockCustomization = {
        id: 'validation-test',
        templateId: customizedTemplate.value.id,
        name: 'Validation Test',
        modifications: {
            addedFields: [],
            removedFieldIds: [],
            modifiedFields: [],
            configChanges: {},
        },
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    };

    return customizer.value.validateCustomization(mockCustomization, props.customizationOptions);
};

// Lifecycle
onMounted(() => {
    if (props.initialTemplateId) {
        loadTemplate();
    }
});
</script>

<style scoped>
.template-customizer {
    @apply mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-lg dark:bg-gray-900;
}

.customizer-header {
    @apply mb-6 border-b border-gray-200 pb-4 dark:border-gray-700;
}

.field-item {
    @apply transition-all duration-200;
}

.field-item:hover {
    @apply shadow-sm;
}

.field-type-badge {
    @apply font-mono;
}

.preview-container {
    @apply max-h-96 overflow-y-auto;
}
</style>














