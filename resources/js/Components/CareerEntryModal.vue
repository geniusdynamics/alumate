<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-gray-800"
            >
                <form @submit.prevent="saveEntry">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-gray-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white" id="modal-title">
                                    {{ entry.id ? 'Edit Career Entry' : 'Add Career Entry' }}
                                </h3>

                                <div class="mt-6 space-y-4">
                                    <!-- Position Title -->
                                    <div>
                                        <label for="position_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Position Title *
                                        </label>
                                        <input
                                            id="position_title"
                                            v-model="form.position_title"
                                            type="text"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="e.g., Software Engineer"
                                        />
                                        <div v-if="errors.position_title" class="mt-1 text-sm text-red-600">
                                            {{ errors.position_title }}
                                        </div>
                                    </div>

                                    <!-- Company Name -->
                                    <div>
                                        <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Company Name *
                                        </label>
                                        <input
                                            id="company_name"
                                            v-model="form.company_name"
                                            type="text"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="e.g., Tech Corp Inc."
                                        />
                                        <div v-if="errors.company_name" class="mt-1 text-sm text-red-600">
                                            {{ errors.company_name }}
                                        </div>
                                    </div>

                                    <!-- Start Date -->
                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Start Date *
                                        </label>
                                        <input
                                            id="start_date"
                                            v-model="form.start_date"
                                            type="date"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <div v-if="errors.start_date" class="mt-1 text-sm text-red-600">
                                            {{ errors.start_date }}
                                        </div>
                                    </div>

                                    <!-- End Date -->
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> End Date </label>
                                        <input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            :disabled="form.is_current"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <div class="mt-2">
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.is_current"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                />
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">This is my current position</span>
                                            </label>
                                        </div>
                                        <div v-if="errors.end_date" class="mt-1 text-sm text-red-600">
                                            {{ errors.end_date }}
                                        </div>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Location </label>
                                        <input
                                            id="location"
                                            v-model="form.location"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="e.g., San Francisco, CA"
                                        />
                                        <div v-if="errors.location" class="mt-1 text-sm text-red-600">
                                            {{ errors.location }}
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Description
                                        </label>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Describe your role and responsibilities..."
                                        ></textarea>
                                        <div v-if="errors.description" class="mt-1 text-sm text-red-600">
                                            {{ errors.description }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-700">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ processing ? 'Saving...' : entry.id ? 'Update' : 'Save' }}
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm dark:border-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    entry: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'saved']);

const processing = ref(false);
const errors = ref({});

const form = reactive({
    position_title: props.entry.position_title || '',
    company_name: props.entry.company_name || '',
    start_date: props.entry.start_date || '',
    end_date: props.entry.end_date || '',
    location: props.entry.location || '',
    description: props.entry.description || '',
    is_current: props.entry.is_current || false,
});

// Clear end_date when is_current is checked
watch(
    () => form.is_current,
    (newValue) => {
        if (newValue) {
            form.end_date = '';
        }
    },
);

const saveEntry = async () => {
    processing.value = true;
    errors.value = {};

    try {
        const url = props.entry.id ? route('api.career.update', props.entry.id) : route('api.career.store');

        const method = props.entry.id ? 'put' : 'post';

        await router[method](url, form, {
            preserveState: true,
            onSuccess: () => {
                emit('saved');
            },
            onError: (responseErrors) => {
                errors.value = responseErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } catch (error) {
        console.error('Error saving career entry:', error);
        processing.value = false;
    }
};
</script>

