<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">External Integrations</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure and manage external system integrations for your institution
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        <PlusIcon class="mr-2 h-4 w-4" />
                        Add Integration
                    </button>
                </div>
            </div>

            <!-- Integration Types Overview -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(typeConfig, type) in integrationTypes"
                    :key="type"
                    class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <component :is="getIcon(typeConfig.icon)" class="h-8 w-8 text-blue-600" />
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ typeConfig.label }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ getIntegrationsCount(type) }} configured</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button @click="createIntegration(type)" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            Configure {{ typeConfig.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Integrations List -->
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Configured Integrations</h2>
                </div>

                <div v-if="integrations.length === 0" class="p-6 text-center">
                    <div class="text-gray-500 dark:text-gray-400">
                        <CogIcon class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p class="mb-2 text-lg font-medium">No integrations configured</p>
                        <p class="text-sm">Get started by adding your first external integration</p>
                    </div>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="integration in integrations"
                        :key="integration.id"
                        class="p-6 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <component
                                        :is="getIcon(integrationTypes[integration.type]?.icon || 'cog')"
                                        class="h-8 w-8 text-gray-600 dark:text-gray-400"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ integration.name }}
                                    </h3>
                                    <div class="mt-1 flex items-center space-x-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ integrationTypes[integration.type]?.label }} • {{ integration.provider }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                integration.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                                            ]"
                                        >
                                            {{ integration.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span
                                            v-if="integration.is_test_mode"
                                            class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                                        >
                                            Test Mode
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <!-- Status Indicator -->
                                <div class="flex items-center space-x-2">
                                    <div :class="['h-2 w-2 rounded-full', getStatusColor(integration)]"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ getStatusText(integration) }}
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="testIntegration(integration)"
                                        :disabled="testingIntegrations.includes(integration.id)"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        <span v-if="testingIntegrations.includes(integration.id)">
                                            <ArrowPathIcon class="mr-1 h-4 w-4 animate-spin" />
                                            Testing...
                                        </span>
                                        <span v-else>
                                            <PlayIcon class="mr-1 h-4 w-4" />
                                            Test
                                        </span>
                                    </button>

                                    <Link
                                        :href="route('admin.integrations.show', integration.id)"
                                        class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                                    >
                                        <EyeIcon class="mr-1 h-4 w-4" />
                                        View
                                    </Link>

                                    <Link
                                        :href="route('admin.integrations.edit', integration.id)"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        <PencilIcon class="mr-1 h-4 w-4" />
                                        Edit
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Validation Errors -->
                        <div
                            v-if="integration.validation_errors.length > 0"
                            class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20"
                        >
                            <div class="flex">
                                <ExclamationTriangleIcon class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-red-400" />
                                <div>
                                    <h4 class="text-sm font-medium text-red-800 dark:text-red-400">Configuration Issues</h4>
                                    <ul class="mt-1 list-inside list-disc text-sm text-red-700 dark:text-red-300">
                                        <li v-for="error in integration.validation_errors" :key="error">
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Last Sync Info -->
                        <div v-if="integration.last_sync_at" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            Last synced: {{ integration.last_sync_at }}
                            <span v-if="integration.sync_status === 'failed'" class="ml-2 text-red-600 dark:text-red-400"> (Failed) </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Integration Modal -->
        <CreateModal
            v-if="showCreateModal"
            :integration-types="integrationTypes"
            :selected-type="selectedType"
            @close="showCreateModal = false"
            @created="handleIntegrationCreated"
        />

        <!-- Test Results Modal -->
        <TestResultsModal v-if="showTestResults" :results="testResults" @close="showTestResults = false" />
    </AdminLayout>
</template>

<script setup>
<<<<<<< HEAD
import IntegrationCreateModal from '@/Components/Admin/Integrations/CreateModal.vue';
import TestResultsModal from '@/Components/Admin/Integrations/TestResultsModal.vue';
=======
import CreateModal from '@/Components/admin/Integrations/CreateModal.vue';
import TestResultsModal from '@/Components/admin/Integrations/TestResultsModal.vue';
>>>>>>> origin/db1
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowPathIcon,
    CalendarIcon,
    ChartBarIcon,
    CogIcon,
    CreditCardIcon,
    EnvelopeIcon,
    ExclamationTriangleIcon,
    EyeIcon,
    PencilIcon,
    PlayIcon,
    PlusIcon,
    ShieldCheckIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    integrations: Array,
    integrationTypes: Object,
});

const showCreateModal = ref(false);
const selectedType = ref(null);
const testingIntegrations = ref([]);
const showTestResults = ref(false);
const testResults = ref(null);

const iconComponents = {
    mail: EnvelopeIcon,
    calendar: CalendarIcon,
    'shield-check': ShieldCheckIcon,
    users: UsersIcon,
    'credit-card': CreditCardIcon,
    'chart-bar': ChartBarIcon,
    cog: CogIcon,
};

const getIcon = (iconName) => {
    return iconComponents[iconName] || CogIcon;
};

const getIntegrationsCount = (type) => {
    return props.integrations.filter((integration) => integration.type === type).length;
};

const getStatusColor = (integration) => {
    if (!integration.is_valid) return 'bg-red-500';
    if (integration.sync_status === 'failed') return 'bg-red-500';
    if (integration.sync_status === 'success') return 'bg-green-500';
    return 'bg-yellow-500';
};

const getStatusText = (integration) => {
    if (!integration.is_valid) return 'Configuration Error';
    if (integration.sync_status === 'failed') return 'Sync Failed';
    if (integration.sync_status === 'success') return 'Healthy';
    return 'Not Tested';
};

const createIntegration = (type) => {
    selectedType.value = type;
    showCreateModal.value = true;
};

const testIntegration = async (integration) => {
    testingIntegrations.value.push(integration.id);

    try {
        const response = await fetch(route('admin.integrations.test', integration.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        const result = await response.json();
        testResults.value = result;
        showTestResults.value = true;

        // Refresh the page to show updated status
        router.reload({ only: ['integrations'] });
    } catch (error) {
        console.error('Test failed:', error);
    } finally {
        testingIntegrations.value = testingIntegrations.value.filter((id) => id !== integration.id);
    }
};

const handleIntegrationCreated = () => {
    showCreateModal.value = false;
    selectedType.value = null;
    router.reload({ only: ['integrations'] });
};
</script>














