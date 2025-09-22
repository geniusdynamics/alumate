<template>
    <div class="form-field-config-panel h-full w-80 overflow-y-auto border-l border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-4">
            <h3 class="text-lg font-semibold text-gray-900">Field Configuration</h3>
            <p class="mt-1 text-sm text-gray-600">Configure the selected form field</p>
        </div>

        <div v-if="!selectedField" class="p-4 text-center text-gray-500">
            <Icon name="cursor-click" class="mx-auto mb-3 h-12 w-12 text-gray-300" />
            <p>Select a form field to configure its properties</p>
        </div>

        <div v-else class="space-y-6 p-4">
            <!-- Basic Properties -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-900">Basic Properties</h4>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Field Type</label>
                    <select
                        v-model="fieldConfig.field_type"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @change="onFieldTypeChange"
                    >
                        <option v-for="(type, key) in fieldTypes" :key="key" :value="key">
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Field Name</label>
                    <input
                        v-model="fieldConfig.field_name"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="field_name"
                    />
                    <p class="mt-1 text-xs text-gray-500">Used for form submission data</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Field Label</label>
                    <input
                        v-model="fieldConfig.field_label"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter field label"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Placeholder Text</label>
                    <input
                        v-model="fieldConfig.field_placeholder"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter placeholder text"
                    />
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input v-model="fieldConfig.is_required" type="checkbox" class="mr-2" />
                        <span class="text-sm text-gray-700">Required field</span>
                    </label>

                    <label class="flex items-center">
                        <input v-model="fieldConfig.is_visible" type="checkbox" class="mr-2" />
                        <span class="text-sm text-gray-700">Visible</span>
                    </label>
                </div>
            </div>

            <!-- Field Options (for select, radio, checkbox) -->
            <div v-if="needsOptions" class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-900">Field Options</h4>

                <div class="space-y-2">
                    <div v-for="(option, index) in fieldConfig.field_options" :key="index" class="flex items-center space-x-2">
                        <input
                            v-model="option.label"
                            type="text"
                            placeholder="Option label"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <input
                            v-model="option.value"
                            type="text"
                            placeholder="Value"
                            class="w-24 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button @click="removeOption(index)" class="p-2 text-red-500 hover:text-red-700">
                            <Icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <button
                    @click="addOption"
                    class="w-full rounded-md border border-dashed border-gray-300 px-3 py-2 text-gray-600 hover:border-gray-400 hover:text-gray-800"
                >
                    + Add Option
                </button>
            </div>

            <!-- Validation Rules -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-900">Validation Rules</h4>

                <div class="space-y-3">
                    <div v-for="rule in availableValidationRules" :key="rule.name" class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input v-model="selectedValidationRules" :value="rule.name" type="checkbox" class="mr-2" />
                            <span class="text-sm text-gray-700">{{ rule.label }}</span>
                        </label>

                        <input
                            v-if="rule.hasValue && selectedValidationRules.includes(rule.name)"
                            v-model="validationRuleValues[rule.name]"
                            type="text"
                            :placeholder="rule.placeholder"
                            class="w-20 rounded border border-gray-300 px-2 py-1 text-xs"
                        />
                    </div>
                </div>
            </div>

            <!-- Conditional Logic -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-900">Conditional Logic</h4>
                    <button
                        @click="toggleConditionalLogic"
                        :class="[
                            'relative inline-flex h-5 w-9 items-center rounded-full text-xs transition-colors',
                            hasConditionalLogic ? 'bg-blue-600' : 'bg-gray-200',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-3 w-3 transform rounded-full bg-white transition-transform',
                                hasConditionalLogic ? 'translate-x-5' : 'translate-x-1',
                            ]"
                        />
                    </button>
                </div>

                <div v-if="hasConditionalLogic" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Show this field when:</label>
                        <select
                            v-model="fieldConfig.conditional_logic.logic"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="and">All conditions are met</option>
                            <option value="or">Any condition is met</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="(rule, index) in fieldConfig.conditional_logic.rules"
                            :key="index"
                            class="flex items-center space-x-2 rounded-md border border-gray-200 p-3"
                        >
                            <select v-model="rule.field" class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm">
                                <option value="">Select field</option>
                                <option v-for="field in availableFields" :key="field.field_name" :value="field.field_name">
                                    {{ field.field_label }}
                                </option>
                            </select>

                            <select v-model="rule.operator" class="rounded border border-gray-300 px-2 py-1 text-sm">
                                <option value="equals">equals</option>
                                <option value="not_equals">not equals</option>
                                <option value="contains">contains</option>
                                <option value="not_contains">not contains</option>
                                <option value="is_empty">is empty</option>
                                <option value="is_not_empty">is not empty</option>
                            </select>

                            <input
                                v-if="!['is_empty', 'is_not_empty'].includes(rule.operator)"
                                v-model="rule.value"
                                type="text"
                                placeholder="Value"
                                class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                            />

                            <button @click="removeConditionalRule(index)" class="p-1 text-red-500 hover:text-red-700">
                                <Icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <button
                        @click="addConditionalRule"
                        class="w-full rounded-md border border-dashed border-gray-300 px-3 py-2 text-gray-600 hover:border-gray-400 hover:text-gray-800"
                    >
                        + Add Condition
                    </button>
                </div>
            </div>

            <!-- CRM Field Mapping -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-900">CRM Mapping</h4>
                    <button
                        @click="toggleCrmMapping"
                        :class="[
                            'relative inline-flex h-5 w-9 items-center rounded-full text-xs transition-colors',
                            hasCrmMapping ? 'bg-green-600' : 'bg-gray-200',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-3 w-3 transform rounded-full bg-white transition-transform',
                                hasCrmMapping ? 'translate-x-5' : 'translate-x-1',
                            ]"
                        />
                    </button>
                </div>

                <div v-if="hasCrmMapping" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">CRM Field</label>
                        <input
                            v-model="fieldConfig.crm_field_mapping.crm_field"
                            type="text"
                            placeholder="e.g., FirstName, Email, Company"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@/Components/ui';
import { computed, reactive, ref, watch } from 'vue';

// ... (component logic will be added in the next part)
</script>
<script s etup lang="ts">
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

interface Props {
    selectedField: FormField | null;
    fieldTypes: Record<string, any>;
    availableFields: FormField[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    updateField: [field: FormField];
}>();

const fieldConfig = reactive<FormField>({
    field_type: 'text',
    field_name: '',
    field_label: '',
    field_placeholder: '',
    field_options: [],
    validation_rules: [],
    conditional_logic: {
        logic: 'and',
        rules: [],
    },
    is_required: false,
    is_visible: true,
    crm_field_mapping: {
        crm_field: '',
    },
    order_index: 0,
});

const selectedValidationRules = ref<string[]>([]);
const validationRuleValues = reactive<Record<string, string>>({});

const needsOptions = computed(() => {
    return ['select', 'radio', 'checkbox'].includes(fieldConfig.field_type);
});

const hasConditionalLogic = computed(() => {
    return fieldConfig.conditional_logic && fieldConfig.conditional_logic.rules.length > 0;
});

const hasCrmMapping = computed(() => {
    return fieldConfig.crm_field_mapping && fieldConfig.crm_field_mapping.crm_field;
});

const availableValidationRules = computed(() => {
    const fieldType = fieldConfig.field_type;
    const rules = [
        { name: 'required', label: 'Required', hasValue: false },
        { name: 'min', label: 'Minimum length', hasValue: true, placeholder: '3' },
        { name: 'max', label: 'Maximum length', hasValue: true, placeholder: '255' },
    ];

    if (fieldType === 'email') {
        rules.push({ name: 'email', label: 'Valid email', hasValue: false });
    }

    if (fieldType === 'number') {
        rules.push(
            { name: 'numeric', label: 'Numeric only', hasValue: false },
            { name: 'min_value', label: 'Minimum value', hasValue: true, placeholder: '0' },
            { name: 'max_value', label: 'Maximum value', hasValue: true, placeholder: '100' },
        );
    }

    if (fieldType === 'file') {
        rules.push(
            { name: 'file', label: 'File upload', hasValue: false },
            { name: 'mimes', label: 'Allowed types', hasValue: true, placeholder: 'jpg,png,pdf' },
            { name: 'max_size', label: 'Max size (KB)', hasValue: true, placeholder: '2048' },
        );
    }

    return rules;
});

// Watch for selected field changes
watch(
    () => props.selectedField,
    (newField) => {
        if (newField) {
            Object.assign(fieldConfig, newField);
            selectedValidationRules.value = newField.validation_rules || [];

            // Initialize validation rule values
            if (newField.validation_rules) {
                newField.validation_rules.forEach((rule) => {
                    const [ruleName, ruleValue] = rule.split(':');
                    if (ruleValue) {
                        validationRuleValues[ruleName] = ruleValue;
                    }
                });
            }
        }
    },
    { immediate: true },
);

// Watch for field config changes and emit updates
watch(
    fieldConfig,
    () => {
        if (props.selectedField) {
            // Build validation rules array
            const validationRules = selectedValidationRules.value.map((ruleName) => {
                const ruleValue = validationRuleValues[ruleName];
                return ruleValue ? `${ruleName}:${ruleValue}` : ruleName;
            });

            const updatedField = {
                ...fieldConfig,
                validation_rules: validationRules,
            };

            emit('updateField', updatedField);
        }
    },
    { deep: true },
);

const onFieldTypeChange = () => {
    // Reset field options when field type changes
    if (!needsOptions.value) {
        fieldConfig.field_options = [];
    } else if (fieldConfig.field_options.length === 0) {
        // Add default options for new option-based fields
        fieldConfig.field_options = [
            { label: 'Option 1', value: 'option1' },
            { label: 'Option 2', value: 'option2' },
        ];
    }

    // Reset validation rules
    selectedValidationRules.value = [];
    Object.keys(validationRuleValues).forEach((key) => {
        delete validationRuleValues[key];
    });
};

const addOption = () => {
    fieldConfig.field_options.push({
        label: `Option ${fieldConfig.field_options.length + 1}`,
        value: `option${fieldConfig.field_options.length + 1}`,
    });
};

const removeOption = (index: number) => {
    fieldConfig.field_options.splice(index, 1);
};

const toggleConditionalLogic = () => {
    if (hasConditionalLogic.value) {
        fieldConfig.conditional_logic = { logic: 'and', rules: [] };
    } else {
        addConditionalRule();
    }
};

const addConditionalRule = () => {
    if (!fieldConfig.conditional_logic) {
        fieldConfig.conditional_logic = { logic: 'and', rules: [] };
    }

    fieldConfig.conditional_logic.rules.push({
        field: '',
        operator: 'equals',
        value: '',
    });
};

const removeConditionalRule = (index: number) => {
    fieldConfig.conditional_logic?.rules.splice(index, 1);
};

const toggleCrmMapping = () => {
    if (hasCrmMapping.value) {
        fieldConfig.crm_field_mapping = { crm_field: '' };
    } else {
        if (!fieldConfig.crm_field_mapping) {
            fieldConfig.crm_field_mapping = { crm_field: '' };
        }
    }
};
</script>
