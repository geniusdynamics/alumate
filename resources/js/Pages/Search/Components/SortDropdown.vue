<template>
    <div class="relative inline-block text-left">
        <div>
            <button
                @click="isOpen = !isOpen"
                type="button"
                class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Sort by: {{ getCurrentSortLabel() }}
                <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div
            v-if="isOpen"
            class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
        >
            <div class="py-1">
                <button
                    v-for="option in sortOptions"
                    :key="option.value"
                    @click="selectSort(option.value)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                    :class="{ 'bg-gray-100 text-gray-900': currentSort === option.value }"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    currentSort: {
        type: String,
        default: 'relevance'
    },
    searchType: {
        type: String,
        default: 'jobs'
    }
});

const emit = defineEmits(['sort-changed']);

const isOpen = ref(false);

const sortOptions = computed(() => {
    const baseOptions = [
        { value: 'relevance', label: 'Relevance' },
        { value: 'date', label: 'Date' },
        { value: 'name', label: 'Name' }
    ];

    if (props.searchType === 'jobs') {
        return [
            ...baseOptions,
            { value: 'salary', label: 'Salary' },
            { value: 'location', label: 'Location' }
        ];
    } else if (props.searchType === 'graduates') {
        return [
            ...baseOptions,
            { value: 'graduation_year', label: 'Graduation Year' },
            { value: 'employment_status', label: 'Employment Status' }
        ];
    } else if (props.searchType === 'courses') {
        return [
            ...baseOptions,
            { value: 'level', label: 'Level' },
            { value: 'duration', label: 'Duration' }
        ];
    }

    return baseOptions;
});

const getCurrentSortLabel = () => {
    const option = sortOptions.value.find(opt => opt.value === props.currentSort);
    return option ? option.label : 'Relevance';
};

const selectSort = (value) => {
    emit('sort-changed', value);
    isOpen.value = false;
};

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
        isOpen.value = false;
    }
});
</script>