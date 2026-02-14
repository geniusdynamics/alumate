<template>
    <div class="ab-test-manager">
        <!-- Header -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">A/B Test Manager</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create and manage A/B tests for feature optimization</p>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        Create Test
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="bg-gray-50 px-6 py-4 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ stats.total_tests }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Tests</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ stats.active_tests }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Active Tests</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ stats.total_participants }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Participants</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            {{ stats.total_conversions }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Conversions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tests List -->
        <div class="mt-6 rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Tests</h3>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="test in tests" :key="test.id" class="px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ test.name }}
                                </h4>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="getStatusClass(test.status)"
                                >
                                    {{ test.status }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ test.description }}
                            </p>

                            <!-- Variants -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <div
                                    v-for="(percentage, variant) in test.distribution"
                                    :key="variant"
                                    class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ variant }}: {{ percentage }}%
                                </div>
                            </div>

                            <!-- Metrics -->
                            <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Participants:</span>
                                    <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                        {{ test.participants_count || 0 }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Conversions:</span>
                                    <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                        {{ test.conversions_count || 0 }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Conversion Rate:</span>
                                    <span class="ml-1 font-medium text-gray-900 dark:text-white"> {{ getConversionRate(test) }}% </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2">
                            <button @click="viewTestDetails(test)" class="text-sm font-medium text-blue-600 hover:text-blue-700">View Details</button>
                            <button
                                v-if="test.status === 'active'"
                                @click="pauseTest(test)"
                                class="text-sm font-medium text-yellow-600 hover:text-yellow-700"
                            >
                                Pause
                            </button>
                            <button
                                v-if="test.status === 'paused'"
                                @click="resumeTest(test)"
                                class="text-sm font-medium text-green-600 hover:text-green-700"
                            >
                                Resume
                            </button>
                            <button
                                v-if="['active', 'paused'].includes(test.status)"
                                @click="completeTest(test)"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Complete
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="tests.length === 0" class="px-6 py-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">No A/B tests found. Create your first test to get started.</div>
                </div>
            </div>
        </div>

        <!-- Create Test Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="mx-4 w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create A/B Test</h3>
                </div>

                <form @submit.prevent="createTest" class="space-y-4 px-6 py-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Test Name </label>
                        <input
                            v-model="newTest.name"
                            type="text"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="e.g., homepage_cta_button"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Description </label>
                        <textarea
                            v-model="newTest.description"
                            rows="3"
                            required
                            class="w-full resize-none rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Describe what you're testing..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Variants (one per line) </label>
                        <textarea
                            v-model="variantsText"
                            rows="3"
                            required
                            class="w-full resize-none rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="control&#10;variant_a&#10;variant_b"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Equal distribution will be applied automatically</p>
                    </div>

                    <div class="flex space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="flex-1 rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="creating"
                            class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ creating ? 'Creating...' : 'Create Test' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Details Modal -->
        <div v-if="selectedTest" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ selectedTest.name }}
                        </h3>
                        <button @click="selectedTest = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <div class="space-y-6">
                        <!-- Test Info -->
                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Test Information</h4>
                            <div class="space-y-2 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="getStatusClass(selectedTest.status)"
                                    >
                                        {{ selectedTest.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Started:</span>
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ formatDate(selectedTest.started_at) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Participants:</span>
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ selectedTest.participants_count || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Variant Performance -->
                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Variant Performance</h4>
                            <div class="space-y-3">
                                <div v-for="variant in selectedTest.variants" :key="variant" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ variant }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ selectedTest.distribution[variant] }}% traffic
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Participants</div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ getVariantParticipants(selectedTest, variant) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Conversions</div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ getVariantConversions(selectedTest, variant) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Rate</div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ getVariantConversionRate(selectedTest, variant) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Props
const props = defineProps({
    initialTests: {
        type: Array,
        default: () => [],
    },
    initialStats: {
        type: Object,
        default: () => ({
            total_tests: 0,
            active_tests: 0,
            total_participants: 0,
            total_conversions: 0,
        }),
    },
});

// State
const tests = ref(props.initialTests);
const stats = ref(props.initialStats);
const showCreateModal = ref(false);
const selectedTest = ref(null);
const creating = ref(false);

// Form data
const newTest = ref({
    name: '',
    description: '',
    variants: [],
});

const variantsText = ref('');

// Methods
const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    };
    return classes[status] || classes.draft;
};

const getConversionRate = (test) => {
    const participants = test.participants_count || 0;
    const conversions = test.conversions_count || 0;
    return participants > 0 ? ((conversions / participants) * 100).toFixed(1) : '0.0';
};

const getVariantParticipants = (test, variant) => {
    return test.variant_stats?.[variant]?.participants || 0;
};

const getVariantConversions = (test, variant) => {
    return test.variant_stats?.[variant]?.conversions || 0;
};

const getVariantConversionRate = (test, variant) => {
    const participants = getVariantParticipants(test, variant);
    const conversions = getVariantConversions(test, variant);
    return participants > 0 ? ((conversions / participants) * 100).toFixed(1) : '0.0';
};

const formatDate = (dateString) => {
    if (!dateString) return 'Not started';
    return new Date(dateString).toLocaleDateString();
};

const createTest = async () => {
    creating.value = true;

    try {
        const variants = variantsText.value
            .split('\n')
            .map((v) => v.trim())
            .filter((v) => v.length > 0);

        if (variants.length < 2) {
            alert('Please provide at least 2 variants');
            return;
        }

        await router.post(
            '/api/admin/ab-tests',
            {
                name: newTest.value.name,
                description: newTest.value.description,
                variants: variants,
            },
            {
                preserveState: true,
                onSuccess: (page) => {
                    tests.value = page.props.tests || tests.value;
                    stats.value = page.props.stats || stats.value;
                    showCreateModal.value = false;
                    newTest.value = { name: '', description: '', variants: [] };
                    variantsText.value = '';
                },
            },
        );
    } catch (error) {
        console.error('Error creating test:', error);
    } finally {
        creating.value = false;
    }
};

const viewTestDetails = (test) => {
    selectedTest.value = test;
};

const pauseTest = async (test) => {
    await router.patch(
        `/api/admin/ab-tests/${test.id}`,
        {
            status: 'paused',
        },
        {
            preserveState: true,
            onSuccess: (page) => {
                tests.value = page.props.tests || tests.value;
                stats.value = page.props.stats || stats.value;
            },
        },
    );
};

const resumeTest = async (test) => {
    await router.patch(
        `/api/admin/ab-tests/${test.id}`,
        {
            status: 'active',
        },
        {
            preserveState: true,
            onSuccess: (page) => {
                tests.value = page.props.tests || tests.value;
                stats.value = page.props.stats || stats.value;
            },
        },
    );
};

const completeTest = async (test) => {
    if (confirm('Are you sure you want to complete this test? This action cannot be undone.')) {
        await router.patch(
            `/api/admin/ab-tests/${test.id}`,
            {
                status: 'completed',
            },
            {
                preserveState: true,
                onSuccess: (page) => {
                    tests.value = page.props.tests || tests.value;
                    stats.value = page.props.stats || stats.value;
                },
            },
        );
    }
};

// Load data on mount
onMounted(() => {
    // Refresh data periodically
    setInterval(() => {
        router.reload({ only: ['tests', 'stats'] });
    }, 30000); // Refresh every 30 seconds
});
</script>

