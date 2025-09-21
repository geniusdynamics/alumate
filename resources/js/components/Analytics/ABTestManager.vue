<template>
    <div class="ab-test-manager">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">A/B Test Management</h1>
                <p class="mt-1 text-gray-600">Create and manage your A/B tests</p>
            </div>
            <button
                @click="openCreateModal"
                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                aria-label="Create new A/B test"
            >
                <svg class="mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Test
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading A/B tests...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Tests Table -->
        <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="A/B Tests">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Test Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Variants</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="test in tests" :key="test.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ test.name }}</div>
                            <div class="text-sm text-gray-500">{{ test.description }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span :class="getStatusBadgeClass(test.status)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
                                {{ test.status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            {{ test.variants.length }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(test.created_at) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                            <div class="flex space-x-2">
                                <button @click="viewResults(test)" class="text-blue-600 hover:text-blue-900" aria-label="View results">View</button>
                                <button @click="editTest(test)" class="text-indigo-600 hover:text-indigo-900" aria-label="Edit test">Edit</button>
                                <button @click="deleteTest(test)" class="text-red-600 hover:text-red-900" aria-label="Delete test">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="tests.length === 0" class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No A/B tests</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first A/B test.</p>
                <div class="mt-6">
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
                    >
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create Test
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="tests.length > 0" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalTests) }} of {{ totalTests }} results
            </div>
            <div class="flex space-x-2">
                <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="rounded border border-gray-300 bg-white px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    aria-label="Previous page"
                >
                    Previous
                </button>
                <button
                    @click="nextPage"
                    :disabled="currentPage === totalPages"
                    class="rounded border border-gray-300 bg-white px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    aria-label="Next page"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @keydown.escape="closeModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            <div class="relative top-20 mx-auto w-11/12 max-w-2xl rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3">
                    <h3 id="modal-title" class="mb-4 text-lg font-medium text-gray-900">
                        {{ isEdit ? 'Edit A/B Test' : 'Create New A/B Test' }}
                    </h3>
                    <ABTestForm :test="selectedTest" :is-edit="isEdit" @submit="handleFormSubmit" @cancel="closeModal" />
                </div>
            </div>
        </div>

        <!-- Results Modal -->
        <div
            v-if="showResultsModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @keydown.escape="closeResultsModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="results-modal-title"
        >
            <div class="relative top-20 mx-auto max-h-screen w-11/12 max-w-4xl overflow-y-auto rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 id="results-modal-title" class="text-lg font-medium text-gray-900">A/B Test Results: {{ selectedTest?.name }}</h3>
                        <button @click="closeResultsModal" class="text-gray-400 hover:text-gray-600" aria-label="Close results modal">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <ABTestResults :test-id="selectedTest?.id || ''" :date-range="resultsDateRange" @close="closeResultsModal" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import type { ABTestApiResponse, ABTestData } from '../../types/analytics';
import ABTestForm from './ABTestForm.vue';
import ABTestResults from './ABTestResults.vue';

// Reactive data
const tests = ref<ABTestData[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const currentPage = ref(1);
const perPage = ref(10);
const totalTests = ref(0);
const showModal = ref(false);
const showResultsModal = ref(false);
const isEdit = ref(false);
const selectedTest = ref<ABTestData | undefined>();
const resultsDateRange = ref<{ from?: string; to?: string }>({});

// Computed properties
const totalPages = computed(() => Math.ceil(totalTests.value / perPage.value));

// Methods
const fetchTests = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await axios.get<ABTestApiResponse>(`/api/ab-tests?page=${currentPage.value}&per_page=${perPage.value}`);
        tests.value = response.data.data as ABTestData[];
        // Assuming pagination metadata is in response
        totalTests.value = response.data.total || tests.value.length;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to load A/B tests';
        console.error('A/B tests fetch error:', err);
    } finally {
        isLoading.value = false;
    }
};

const openCreateModal = () => {
    selectedTest.value = undefined;
    isEdit.value = false;
    showModal.value = true;
};

const editTest = (test: ABTestData) => {
    selectedTest.value = { ...test };
    isEdit.value = true;
    showModal.value = true;
};

const viewResults = (test: ABTestData) => {
    selectedTest.value = test;
    showResultsModal.value = true;
};

const deleteTest = async (test: ABTestData) => {
    if (!confirm(`Are you sure you want to delete "${test.name}"? This action cannot be undone.`)) {
        return;
    }

    try {
        await axios.delete(`/api/ab-tests/${test.id}`);
        await fetchTests(); // Refresh the list
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to delete test';
        console.error('Delete test error:', err);
    }
};

const handleFormSubmit = async (testData: ABTestData) => {
    try {
        if (isEdit.value && selectedTest.value?.id) {
            await axios.put(`/api/ab-tests/${selectedTest.value.id}`, testData);
        } else {
            await axios.post('/api/ab-tests', testData);
        }
        closeModal();
        await fetchTests(); // Refresh the list
    } catch (err) {
        // Error handling is done in the form component
        console.error('Form submit error:', err);
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedTest.value = undefined;
};

const closeResultsModal = () => {
    showResultsModal.value = false;
    selectedTest.value = undefined;
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
        fetchTests();
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
        fetchTests();
    }
};

const getStatusBadgeClass = (status: string) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        active: 'bg-green-100 text-green-800',
        paused: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
    };
    return classes[status as keyof typeof classes] || classes.draft;
};

const formatDate = (dateString?: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
};

// Lifecycle
onMounted(() => {
    fetchTests();
});
</script>

<style scoped>
.ab-test-manager {
    @apply mx-auto w-full max-w-7xl p-6;
}

/* Focus styles for accessibility */
button:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}
</style>
