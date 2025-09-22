<template>
    <div
        :class="[
            'form-field-wrapper relative mb-4 rounded-lg border-2 border-dashed border-gray-300 p-4 transition-all',
            isDragging ? 'border-blue-500 bg-blue-50' : '',
            isSelected ? 'border-blue-500 bg-blue-50' : 'hover:border-gray-400',
        ]"
        @click="selectField"
        @dragover.prevent="handleDragOver"
        @drop.prevent="handleDrop"
    >
        <!-- Field Controls -->
        <div class="absolute right-2 top-2 flex space-x-1 opacity-0 transition-opacity group-hover:opacity-100">
            <button @click.stop="moveFieldUp" :disabled="isFirst" class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50" title="Move Up">
                <Icon name="chevron-up" class="h-4 w-4" />
            </button>
            <button
                @click.stop="moveFieldDown"
                :disabled="isLast"
                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                title="Move Down"
            >
                <Icon name="chevron-down" class="h-4 w-4" />
            </button>
            <button @click.stop="duplicateField" class="p-1 text-gray-400 hover:text-gray-600" title="Duplicate">
                <Icon name="copy" class="h-4 w-4" />
            </button>
            <button @click.stop="deleteField" class="p-1 text-red-400 hover:text-red-600" title="Delete">
                <Icon name="trash" class="h-4 w-4" />
            </button>
        </div>

        <!-- Field Preview -->
        <div class="pr-20">
            <label class="mb-2 block text-sm font-medium text-gray-700">
                {{ field.field_label }}
                <span v-if="field.is_required" class="text-red-500">*</span>
            </label>

            <!-- Text Input -->
            <input
                v-if="field.field_type === 'text' || field.field_type === 'email' || field.field_type === 'url'"
                :type="field.field_type"
                :placeholder="field.field_placeholder"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            />

            <!-- Phone Input -->
            <input
                v-else-if="field.field_type === 'phone'"
                type="tel"
                :placeholder="field.field_placeholder || '+1 (555) 123-4567'"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            />

            <!-- Number Input -->
            <input
                v-else-if="field.field_type === 'number'"
                type="number"
                :placeholder="field.field_placeholder"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            />

            <!-- Date Input -->
            <input
                v-else-if="field.field_type === 'date'"
                type="date"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            />

            <!-- Textarea -->
            <textarea
                v-else-if="field.field_type === 'textarea'"
                :placeholder="field.field_placeholder"
                rows="3"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            ></textarea>

            <!-- Select Dropdown -->
            <select
                v-else-if="field.field_type === 'select'"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled
            >
                <option value="">{{ field.field_placeholder || 'Select an option' }}</option>
                <option v-for="option in field.field_options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>

            <!-- Radio Buttons -->
            <div v-else-if="field.field_type === 'radio'" class="space-y-2">
                <div v-for="option in field.field_options" :key="option.value" class="flex items-center">
                    <input type="radio" :name="`radio-${field.field_name}`" :value="option.value" class="mr-2" disabled />
                    <label class="text-sm text-gray-700">{{ option.label }}</label>
                </div>
            </div>

            <!-- Checkboxes -->
            <div v-else-if="field.field_type === 'checkbox'" class="space-y-2">
                <div v-for="option in field.field_options" :key="option.value" class="flex items-center">
                    <input type="checkbox" :value="option.value" class="mr-2" disabled />
                    <label class="text-sm text-gray-700">{{ option.label }}</label>
                </div>
            </div>

            <!-- File Upload -->
            <div v-else-if="field.field_type === 'file'" class="rounded-lg border-2 border-dashed border-gray-300 p-4 text-center">
                <Icon name="upload" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                <p class="text-sm text-gray-600">{{ field.field_placeholder || 'Click to upload or drag and drop' }}</p>
            </div>

            <!-- Hidden Field -->
            <div v-else-if="field.field_type === 'hidden'" class="text-sm italic text-gray-500">Hidden field: {{ field.field_name }}</div>
        </div>

        <!-- Conditional Logic Indicator -->
        <div v-if="hasConditionalLogic" class="mt-2 flex items-center text-xs text-blue-600">
            <Icon name="branch" class="mr-1 h-3 w-3" />
            Conditional logic applied
        </div>

        <!-- CRM Mapping Indicator -->
        <div v-if="hasCrmMapping" class="mt-1 flex items-center text-xs text-green-600">
            <Icon name="link" class="mr-1 h-3 w-3" />
            Mapped to CRM
        </div>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@/components/ui';
import { computed } from 'vue';

interface FormField {
    field_type: string;
    field_name: string;
    field_label: string;
    field_placeholder?: string;
    field_options?: Array<{ value: string; label: string }>;
    is_required: boolean;
    conditional_logic?: any;
    crm_field_mapping?: any;
    order_index: number;
}

interface Props {
    field: FormField;
    isSelected: boolean;
    isFirst: boolean;
    isLast: boolean;
    isDragging: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    select: [];
    moveUp: [];
    moveDown: [];
    duplicate: [];
    delete: [];
    drop: [event: DragEvent];
}>();

const hasConditionalLogic = computed(() => {
    return props.field.conditional_logic && Object.keys(props.field.conditional_logic).length > 0;
});

const hasCrmMapping = computed(() => {
    return props.field.crm_field_mapping && Object.keys(props.field.crm_field_mapping).length > 0;
});

const selectField = () => {
    emit('select');
};

const moveFieldUp = () => {
    emit('moveUp');
};

const moveFieldDown = () => {
    emit('moveDown');
};

const duplicateField = () => {
    emit('duplicate');
};

const deleteField = () => {
    emit('delete');
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
};

const handleDrop = (event: DragEvent) => {
    emit('drop', event);
};
</script>

<style scoped>
.form-field-wrapper:hover .absolute {
    opacity: 1;
}
</style>











