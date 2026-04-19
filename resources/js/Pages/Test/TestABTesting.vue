<template>
    <div class="test-ab-testing p-6">
        <div class="mx-auto max-w-7xl">
            <h1 class="mb-8 text-3xl font-bold text-gray-900">A/B Testing Components Test Page</h1>

            <div class="space-y-12">
                <!-- Test Controls -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="mb-4 text-xl font-semibold">Test Controls</h2>
                    <div class="flex flex-wrap gap-4">
                        <button @click="resetMockData" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Reset Mock Data</button>
                        <button @click="simulateApiError" class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">Simulate API Error</button>
                        <button @click="toggleEmptyState" class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700">
                            Toggle Empty State
                        </button>
                    </div>
                </div>

                <!-- A/B Test Manager Component -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="mb-4 text-xl font-semibold">A/B Test Manager</h2>
                    <p class="mb-4 text-gray-600">Test the main management interface with table, pagination, and modals.</p>
                    <ABTestManager />
                </div>

                <!-- A/B Test Form Component -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="mb-4 text-xl font-semibold">A/B Test Form</h2>
                    <p class="mb-4 text-gray-600">Test the create/edit form with validation and dynamic fields.</p>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <button @click="showCreateForm" class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                                Test Create Form
                            </button>
                            <button @click="showEditForm" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Test Edit Form</button>
                        </div>
                        <div v-if="showForm" class="rounded-lg border p-4">
                            <ABTestForm :test="formTestData" :is-edit="isEditMode" @submit="handleFormSubmit" @cancel="hideForm" />
                        </div>
                    </div>
                </div>

                <!-- A/B Test Results Component -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="mb-4 text-xl font-semibold">A/B Test Results</h2>
                    <p class="mb-4 text-gray-600">Test the results visualization with charts and date filtering.</p>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <button @click="showResults" class="rounded bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                                Show Test Results
                            </button>
                            <button @click="showEmptyResults" class="rounded bg-orange-600 px-4 py-2 text-white hover:bg-orange-700">
                                Show Empty Results
                            </button>
                        </div>
                        <div v-if="showResultsComponent" class="rounded-lg border p-4">
                            <ABTestResults :test-id="resultsTestId" :date-range="resultsDateRange" @close="hideResults" />
                        </div>
                    </div>
                </div>

                <!-- Test Results Summary -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="mb-4 text-xl font-semibold">Test Results Summary</h2>
                    <div class="space-y-2">
                        <p><strong>Form Submissions:</strong> {{ formSubmissions.length }}</p>
                        <p><strong>API Calls Made:</strong> {{ apiCallCount }}</p>
                        <p><strong>Errors Simulated:</strong> {{ errorCount }}</p>
                        <div v-if="lastSubmission" class="mt-4 rounded bg-gray-50 p-4">
                            <h3 class="mb-2 font-medium">Last Form Submission:</h3>
                            <pre class="text-sm">{{ JSON.stringify(lastSubmission, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { ref } from 'vue';
import ABTestForm from '../../Components/Analytics/ABTestForm.vue';
import ABTestManager from '../../Components/Analytics/ABTestManager.vue';
import ABTestResults from '../../Components/Analytics/ABTestResults.vue';
import type { ABTestData } from '../../types/analytics';
// Test state
const showForm = ref(false);
const showResultsComponent = ref(false);
const isEditMode = ref(false);
const formTestData = ref<ABTestData | undefined>();
const resultsTestId = ref('test-1');
const resultsDateRange = ref({ from: '2024-01-01', to: '2024-01-31' });

// Test data tracking
const formSubmissions = ref<any[]>([]);
const apiCallCount = ref(0);
const errorCount = ref(0);
const lastSubmission = ref<any>(null);

// Mock data
const mockTestData: ABTestData = {
    id: 'test-1',
    name: 'Button Color Test',
    description: 'Testing different button colors for conversion optimization',
    status: 'active',
    variants: [
        {
            id: 'v1',
            name: 'Blue Button',
            weight: 50,
            description: 'Primary blue button',
        },
        {
            id: 'v2',
            name: 'Green Button',
            weight: 50,
            description: 'Success green button',
        },
    ],
    audience_criteria: ['desktop_users', 'new_visitors'],
    goal_event: 'button_click',
    created_at: '2024-01-15T10:00:00Z',
};

// Methods
const resetMockData = () => {
    formSubmissions.value = [];
    apiCallCount.value = 0;
    errorCount.value = 0;
    lastSubmission.value = null;
    logger.log('Mock data reset');
};

const simulateApiError = () => {
    errorCount.value++;
    // This would normally be handled by intercepting axios calls
    logger.log('API error simulated');
};

const toggleEmptyState = () => {
    // This would affect the mock API responses
    logger.log('Empty state toggled');
};

const showCreateForm = () => {
    formTestData.value = undefined;
    isEditMode.value = false;
    showForm.value = true;
};

const showEditForm = () => {
    formTestData.value = mockTestData;
    isEditMode.value = true;
    showForm.value = true;
};

const hideForm = () => {
    showForm.value = false;
    formTestData.value = undefined;
};

const handleFormSubmit = (testData: ABTestData) => {
    formSubmissions.value.push(testData);
    lastSubmission.value = testData;
    apiCallCount.value++;
    logger.log('Form submitted:', testData);
    hideForm();
};

const showResults = () => {
    resultsTestId.value = 'test-1';
    showResultsComponent.value = true;
};

const showEmptyResults = () => {
    resultsTestId.value = 'empty-test';
    showResultsComponent.value = true;
};

const hideResults = () => {
    showResultsComponent.value = false;
};

// Mock axios interceptor for testing
// This would normally be set up in a test environment
const setupMockInterceptors = () => {
    // In a real test environment, you would use libraries like msw or axios-mock-adapter
    logger.log('Mock interceptors would be set up here');
};

// Initialize
setupMockInterceptors();
</script>

<style scoped>
.test-ab-testing {
    min-height: 100vh;
    background-color: #f9fafb;
}

pre {
    white-space: pre-wrap;
    word-break: break-all;
}
</style>
