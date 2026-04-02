<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Name Field -->
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Test Name * </label>
            <input
                id="name"
                v-model="formData.name"
                type="text"
                required
                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Enter test name"
                aria-describedby="name-error"
            />
            <div v-if="errors.name" id="name-error" class="mt-1 text-sm text-red-600" role="alert">
                {{ errors.name[0] }}
            </div>
        </div>

        <!-- Description Field -->
        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-gray-700"> Description </label>
            <textarea
                id="description"
                v-model="formData.description"
                rows="3"
                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Describe your A/B test"
                aria-describedby="description-error"
            ></textarea>
            <div v-if="errors.description" id="description-error" class="mt-1 text-sm text-red-600" role="alert">
                {{ errors.description[0] }}
            </div>
        </div>

        <!-- Variants Section -->
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700"> Variants * </label>
                <button
                    type="button"
                    @click="addVariant"
                    class="inline-flex items-center rounded bg-blue-100 px-3 py-1 text-sm text-blue-700 hover:bg-blue-200"
                    aria-label="Add variant"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Variant
                </button>
            </div>

            <div class="space-y-3">
                <div v-for="(variant, index) in formData.variants" :key="index" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900">Variant {{ index + 1 }}</h4>
                        <button
                            v-if="formData.variants.length > 2"
                            type="button"
                            @click="removeVariant(index)"
                            class="text-red-600 hover:text-red-800"
                            aria-label="Remove variant"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label :for="`variant-name-${index}`" class="mb-1 block text-xs font-medium text-gray-700"> Name * </label>
                            <input
                                :id="`variant-name-${index}`"
                                v-model="variant.name"
                                type="text"
                                required
                                class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                                :placeholder="`Variant ${index + 1} name`"
                                :aria-describedby="`variant-name-error-${index}`"
                            />
                            <div
                                v-if="errors[`variants.${index}.name`]"
                                :id="`variant-name-error-${index}`"
                                class="mt-1 text-xs text-red-600"
                                role="alert"
                            >
                                {{ errors[`variants.${index}.name`][0] }}
                            </div>
                        </div>

                        <div>
                            <label :for="`variant-weight-${index}`" class="mb-1 block text-xs font-medium text-gray-700"> Weight * </label>
                            <input
                                :id="`variant-weight-${index}`"
                                v-model.number="variant.weight"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Weight (1-100)"
                                :aria-describedby="`variant-weight-error-${index}`"
                            />
                            <div
                                v-if="errors[`variants.${index}.weight`]"
                                :id="`variant-weight-error-${index}`"
                                class="mt-1 text-xs text-red-600"
                                role="alert"
                            >
                                {{ errors[`variants.${index}.weight`][0] }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label :for="`variant-description-${index}`" class="mb-1 block text-xs font-medium text-gray-700"> Description </label>
                        <input
                            :id="`variant-description-${index}`"
                            v-model="variant.description"
                            type="text"
                            class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Optional description"
                        />
                    </div>
                </div>
            </div>

            <div v-if="errors.variants" class="mt-2 text-sm text-red-600" role="alert">
                {{ errors.variants[0] }}
            </div>
        </div>

        <!-- Audience Criteria -->
        <div>
            <label for="audience-criteria" class="mb-1 block text-sm font-medium text-gray-700"> Audience Criteria </label>
            <div class="mb-2 flex flex-wrap gap-2">
                <span
                    v-for="(criterion, index) in formData.audience_criteria"
                    :key="index"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
                >
                    {{ criterion }}
                    <button
                        type="button"
                        @click="removeAudienceCriterion(index)"
                        class="ml-1 text-blue-600 hover:text-blue-800"
                        aria-label="Remove criterion"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </div>
            <div class="flex gap-2">
                <input
                    id="audience-criteria"
                    v-model="newCriterion"
                    type="text"
                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Add audience criterion"
                    @keydown.enter.prevent="addAudienceCriterion"
                    aria-describedby="audience-error"
                />
                <button
                    type="button"
                    @click="addAudienceCriterion"
                    class="rounded bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                    aria-label="Add criterion"
                >
                    Add
                </button>
            </div>
            <div v-if="errors.audience_criteria" id="audience-error" class="mt-1 text-sm text-red-600" role="alert">
                {{ errors.audience_criteria[0] }}
            </div>
        </div>

        <!-- Goal Event -->
        <div>
            <label for="goal-event" class="mb-1 block text-sm font-medium text-gray-700"> Goal Event * </label>
            <input
                id="goal-event"
                v-model="formData.goal_event"
                type="text"
                required
                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="e.g., button_click, page_view"
                aria-describedby="goal-error"
            />
            <div v-if="errors.goal_event" id="goal-error" class="mt-1 text-sm text-red-600" role="alert">
                {{ errors.goal_event[0] }}
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
            <button
                type="button"
                @click="$emit('cancel')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                aria-label="Cancel"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="isSubmitting"
                class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                aria-label="Save test"
            >
                <span v-if="isSubmitting">Saving...</span>
                <span v-else>{{ props.isEdit ? 'Update Test' : 'Create Test' }}</span>
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import type { ABTestData, ABTestFormProps } from '../../types/analytics';

// Props
const props = withDefaults(defineProps<ABTestFormProps>(), {
    test: undefined,
    isEdit: false,
});

// Emits
const emit = defineEmits<{
    submit: [testData: ABTestData];
    cancel: [];
}>();

// Reactive data
const formData = reactive<ABTestData>({
    name: '',
    description: '',
    status: 'draft',
    variants: [
        { name: 'Control', weight: 50, description: 'Original version' },
        { name: 'Variant A', weight: 50, description: 'Test version' },
    ],
    audience_criteria: [],
    goal_event: '',
});

const errors = ref<Record<string, string[]>>({});
const isSubmitting = ref(false);
const newCriterion = ref('');

// Watch for prop changes
watch(
    () => props.test,
    (newTest) => {
        if (newTest) {
            Object.assign(formData, {
                ...newTest,
                variants: [...newTest.variants], // Deep copy
            });
        }
    },
    { immediate: true },
);

// Methods
const addVariant = () => {
    formData.variants.push({
        name: `Variant ${String.fromCharCode(65 + formData.variants.length)}`,
        weight: 0,
        description: '',
    });
    redistributeWeights();
};

const removeVariant = (index: number) => {
    if (formData.variants.length > 2) {
        formData.variants.splice(index, 1);
        redistributeWeights();
    }
};

const redistributeWeights = () => {
    const totalVariants = formData.variants.length;
    const equalWeight = Math.floor(100 / totalVariants);
    const remainder = 100 % totalVariants;

    formData.variants.forEach((variant, index) => {
        variant.weight = equalWeight + (index < remainder ? 1 : 0);
    });
};

const addAudienceCriterion = () => {
    if (newCriterion.value.trim() && !formData.audience_criteria.includes(newCriterion.value.trim())) {
        formData.audience_criteria.push(newCriterion.value.trim());
        newCriterion.value = '';
    }
};

const removeAudienceCriterion = (index: number) => {
    formData.audience_criteria.splice(index, 1);
};

const validateForm = (): boolean => {
    errors.value = {};

    if (!formData.name.trim()) {
        errors.value.name = ['Test name is required'];
    }

    if (formData.variants.length < 2) {
        errors.value.variants = ['At least 2 variants are required'];
    }

    const totalWeight = formData.variants.reduce((sum, variant) => sum + (variant.weight || 0), 0);
    if (totalWeight !== 100) {
        errors.value.variants = [...(errors.value.variants || []), 'Variant weights must total 100%'];
    }

    formData.variants.forEach((variant, index) => {
        if (!variant.name.trim()) {
            errors.value[`variants.${index}.name`] = ['Variant name is required'];
        }
        if (!variant.weight || variant.weight < 1 || variant.weight > 100) {
            errors.value[`variants.${index}.weight`] = ['Weight must be between 1 and 100'];
        }
    });

    if (!formData.goal_event.trim()) {
        errors.value.goal_event = ['Goal event is required'];
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    try {
        const testData: ABTestData = {
            ...formData,
            variants: formData.variants.map((v) => ({ ...v })), // Ensure clean copy
        };

        emit('submit', testData);
    } catch (error) {
        console.error('Form submission error:', error);
    } finally {
        isSubmitting.value = false;
    }
};

// Initialize form data
onMounted(() => {
    if (props.test) {
        Object.assign(formData, {
            ...props.test,
            variants: [...props.test.variants],
        });
    }
});
</script>

<style scoped>
/* Focus styles for accessibility */
input:focus,
textarea:focus,
button:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}
</style>
