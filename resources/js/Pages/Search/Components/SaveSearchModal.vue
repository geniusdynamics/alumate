<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="$emit('close')">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Save Search
                    </h3>
                    
                    <form @submit.prevent="saveSearch">
                        <div class="mb-4">
                            <label for="search-name" class="block text-sm font-medium text-gray-700">
                                Search Name
                            </label>
                            <input
                                id="search-name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter a name for this search"
                            />
                        </div>
                        
                        <div class="mb-4">
                            <label for="search-description" class="block text-sm font-medium text-gray-700">
                                Description (Optional)
                            </label>
                            <textarea
                                id="search-description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Describe this search..."
                            ></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input
                                    v-model="form.notify_new_results"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-600">
                                    Notify me when new results match this search
                                </span>
                            </label>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="saveSearch"
                        :disabled="!form.name || processing"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                    >
                        {{ processing ? 'Saving...' : 'Save Search' }}
                    </button>
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    searchParams: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    name: '',
    description: '',
    notify_new_results: false
});

const processing = ref(false);

const saveSearch = async () => {
    if (!form.value.name) return;
    
    processing.value = true;
    
    try {
        // Here you would make an API call to save the search
        // For now, just emit the saved event
        emit('saved', {
            ...form.value,
            search_params: props.searchParams
        });
        
        // Reset form
        form.value = {
            name: '',
            description: '',
            notify_new_results: false
        };
        
        emit('close');
    } catch (error) {
        console.error('Error saving search:', error);
    } finally {
        processing.value = false;
    }
};
</script>