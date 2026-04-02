<template>
    <div class="event-create-view" role="region" aria-label="Create custom event form">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Basic Information -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Basic Information</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="event-name" class="block text-sm font-medium text-gray-700">
                            Event Name *
                        </label>
                        <input
                            id="event-name"
                            v-model="formData.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="e.g., user_signup_completed"
                        />
                    </div>
                    <div>
                        <label for="event-category" class="block text-sm font-medium text-gray-700">
                            Category *
                        </label>
                        <select
                            id="event-category"
                            v-model="formData.category"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select category</option>
                            <option value="conversion">Conversion</option>
                            <option value="engagement">Engagement</option>
                            <option value="error">Error</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6">
                    <label for="event-description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea
                        id="event-description"
                        v-model="formData.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="Describe what this event tracks..."
                    ></textarea>
                </div>
            </div>

            <!-- Schema Builder -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Event Schema</h3>
                    <button
                        type="button"
                        @click="addField"
                        class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Add Field
                    </button>
                </div>

                <div v-if="formData.schema.properties && Object.keys(formData.schema.properties).length === 0" class="text-center py-8 text-gray-500">
                    No fields defined yet. Click "Add Field" to get started.
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(field, index) in schemaFields"
                        :key="field.name || index"
                        class="rounded border border-gray-200 p-4"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Field Name</label>
                                <input
                                    v-model="field.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                    placeholder="field_name"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select
                                    v-model="field.type"
                                    required
                                    class="mt-1 block w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                >
                                    <option value="string">String</option>
                                    <option value="number">Number</option>
                                    <option value="integer">Integer</option>
                                    <option value="boolean">Boolean</option>
                                    <option value="object">Object</option>
                                    <option value="array">Array</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Required</label>
                                <input
                                    v-model="field.required"
                                    type="checkbox"
                                    class="mt-2 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="button"
                                    @click="removeField(index)"
                                    class="rounded bg-red-600 px-2 py-1 text-sm text-white hover:bg-red-700"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <input
                                v-model="field.description"
                                type="text"
                                class="mt-1 block w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                placeholder="Field description"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schema Preview -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Schema Preview</h3>
                <pre class="rounded bg-gray-100 p-4 text-sm overflow-x-auto">{{ JSON.stringify(formData.schema, null, 2) }}</pre>
                <div v-if="validationErrors.length > 0" class="mt-4">
                    <h4 class="text-sm font-medium text-red-800">Validation Errors:</h4>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                        <li v-for="error in validationErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="isSubmitting || validationErrors.length > 0"
                    class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ isSubmitting ? 'Saving...' : (editingEvent ? 'Update Event' : 'Create Event') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { ref, computed, watch } from 'vue';
import type { CustomEventDefinition, SchemaField } from '../../../types/analytics';

// Props
const props = defineProps<{
    event?: CustomEventDefinition | null;
}>();

// Emits
defineEmits<{
    save: [eventData: Partial<CustomEventDefinition>];
    cancel: [];
}>();

// Reactive state
const formData = ref<Partial<CustomEventDefinition>>({
    name: '',
    description: '',
    category: 'custom',
    schema: {
        type: 'object',
        properties: {},
        required: []
    }
});

const validationErrors = ref<string[]>([]);
const isSubmitting = ref(false);

// Computed properties
const editingEvent = computed(() => props.event);

const schemaFields = computed({
    get: () => {
        const schema = formData.value.schema;
        if (!schema?.properties) return [];

        return Object.entries(schema.properties).map(([name, field]: [string, any]) => ({
            name,
            type: field.type || 'string',
            required: schema.required?.includes(name) || false,
            description: field.description || ''
        }));
    },
    set: (fields: SchemaField[]) => {
        const properties: Record<string, any> = {};
        const required: string[] = [];

        fields.forEach(field => {
            properties[field.name] = {
                type: field.type,
                description: field.description
            };
            if (field.required) {
                required.push(field.name);
            }
        });

        formData.value.schema = {
            type: 'object',
            properties,
            required
        };
    }
});

// Methods
const addField = () => {
    const newField: SchemaField = {
        name: `field_${Date.now()}`,
        type: 'string',
        required: false,
        description: ''
    };

    schemaFields.value = [...schemaFields.value, newField];
};

const removeField = (index: number) => {
    schemaFields.value = schemaFields.value.filter((_, i) => i !== index);
};

const validateForm = (): boolean => {
    validationErrors.value = [];

    if (!formData.value.name?.trim()) {
        validationErrors.value.push('Event name is required');
    }

    if (!formData.value.category) {
        validationErrors.value.push('Event category is required');
    }

    // Check for duplicate field names
    const fieldNames = schemaFields.value.map(f => f.name);
    const duplicates = fieldNames.filter((name, index) => fieldNames.indexOf(name) !== index);
    if (duplicates.length > 0) {
        validationErrors.value.push(`Duplicate field names found: ${duplicates.join(', ')}`);
    }

    return validationErrors.value.length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) return;

    isSubmitting.value = true;
    try {
        // Emit save event with form data
        // This will be handled by the parent component
        logger.log('Saving event:', formData.value);
    } finally {
        isSubmitting.value = false;
    }
};

// Watchers
watch(() => props.event, (newEvent) => {
    if (newEvent) {
        formData.value = { ...newEvent };
    } else {
        formData.value = {
            name: '',
            description: '',
            category: 'custom',
            schema: {
                type: 'object',
                properties: {},
                required: []
            }
        };
    }
}, { immediate: true });
</script>

<style scoped>
.event-create-view {
    @apply w-full max-w-4xl mx-auto;
}

/* Custom focus styles for accessibility */
.event-create-view input:focus,
.event-create-view select:focus,
.event-create-view textarea:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}
</style>