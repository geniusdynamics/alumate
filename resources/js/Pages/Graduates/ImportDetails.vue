<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    importHistory: Object,
});

const showValidRows = ref(false);
const showInvalidRows = ref(false);
const showConflicts = ref(false);

const getStatusBadge = (status) => {
    const badges = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        rolled_back: 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString();
};

const rollbackImport = () => {
    if (
        confirm(`Are you sure you want to rollback this import? This will remove ${props.importHistory.created_count} graduates that were created.`)
    ) {
        router.post(route('graduates.import.rollback', props.importHistory.id));
    }
};
</script>

<template>
    <Head :title="`Import Details - ${importHistory.filename}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Import Details: {{ importHistory.filename }}</h2>
                <div class="flex gap-2">
                    <button
                        v-if="importHistory.status === 'completed' && importHistory.created_count > 0"
                        @click="rollbackImport"
                        class="rounded-md bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700"
                    >
                        Rollback Import
                    </button>
                    <Link
                        :href="route('graduates.import.history')"
                        class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400"
                    >
                        Back to History
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Import Summary -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Import Summary</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', getStatusBadge(importHistory.status)]">
                                        {{ importHistory.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">File Name:</span>
                                    <span class="font-medium">{{ importHistory.filename }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Started At:</span>
                                    <span class="font-medium">{{ formatDate(importHistory.started_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Completed At:</span>
                                    <span class="font-medium">{{ formatDate(importHistory.completed_at) }}</span>
                                </div>
                                <div v-if="importHistory.started_at && importHistory.completed_at" class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium">
                                        {{ Math.round((new Date(importHistory.completed_at) - new Date(importHistory.started_at)) / 1000) }}s
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Rows:</span>
                                    <span class="font-medium">{{ importHistory.total_rows || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">Created:</span>
                                    <span class="font-medium text-green-600">{{ importHistory.created_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-600">Updated:</span>
                                    <span class="font-medium text-blue-600">{{ importHistory.updated_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-600">Skipped:</span>
                                    <span class="font-medium text-red-600">{{ importHistory.skipped_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Success Rate:</span>
                                    <span class="font-medium">{{ Math.round(importHistory.success_rate || 0) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="importHistory.error_message" class="rounded-lg border border-red-200 bg-red-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Import Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                {{ importHistory.error_message }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Valid Rows -->
                <div v-if="importHistory.valid_rows && importHistory.valid_rows.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Successfully Processed Rows ({{ importHistory.valid_rows.length }})</h3>
                            <button @click="showValidRows = !showValidRows" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                {{ showValidRows ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>

                        <div v-if="showValidRows" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Row</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Graduate ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="row in importHistory.valid_rows" :key="row.row" class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900">{{ row.row }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">{{ row.data.name }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">{{ row.data.email }}</td>
                                        <td class="whitespace-nowrap px-3 py-2">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                    row.action === 'created' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800',
                                                ]"
                                            >
                                                {{ row.action.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">
                                            <Link :href="route('graduates.show', row.graduate_id)" class="text-indigo-600 hover:text-indigo-900">
                                                #{{ row.graduate_id }}
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Invalid Rows -->
                <div
                    v-if="importHistory.invalid_rows && importHistory.invalid_rows.length > 0"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Invalid Rows ({{ importHistory.invalid_rows.length }})</h3>
                            <button @click="showInvalidRows = !showInvalidRows" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                {{ showInvalidRows ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>

                        <div v-if="showInvalidRows" class="space-y-4">
                            <div v-for="row in importHistory.invalid_rows" :key="row.row" class="rounded-lg border border-red-200 bg-red-50 p-4">
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="font-medium text-red-800">Row {{ row.row }}</span>
                                    <span class="text-sm text-red-600">{{ Object.keys(row.errors || {}).length }} error(s)</span>
                                </div>
                                <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Name:</span>
                                        <span class="ml-2 text-sm text-gray-900">{{ row.data.name || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Email:</span>
                                        <span class="ml-2 text-sm text-gray-900">{{ row.data.email || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div v-if="row.errors" class="space-y-1">
                                    <div v-for="(messages, field) in row.errors" :key="field" class="text-sm">
                                        <span class="font-medium text-red-700">{{ field }}:</span>
                                        <ul class="ml-4 list-inside list-disc text-red-600">
                                            <li v-for="message in messages" :key="message">{{ message }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conflicts -->
                <div v-if="importHistory.conflicts && importHistory.conflicts.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Conflicts ({{ importHistory.conflicts.length }})</h3>
                            <button @click="showConflicts = !showConflicts" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                {{ showConflicts ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>

                        <div v-if="showConflicts" class="space-y-4">
                            <div
                                v-for="conflict in importHistory.conflicts"
                                :key="conflict.row"
                                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4"
                            >
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="font-medium text-yellow-800">Row {{ conflict.row }}</span>
                                    <span class="inline-flex rounded bg-yellow-100 px-2 py-1 text-xs text-yellow-800">
                                        {{ conflict.conflict_type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <h4 class="mb-2 font-medium text-gray-700">Import Data:</h4>
                                        <div class="space-y-1 text-sm">
                                            <div><span class="font-medium">Name:</span> {{ conflict.data.name }}</div>
                                            <div><span class="font-medium">Email:</span> {{ conflict.data.email }}</div>
                                            <div v-if="conflict.data.student_id">
                                                <span class="font-medium">Student ID:</span> {{ conflict.data.student_id }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="mb-2 font-medium text-gray-700">Existing Graduate:</h4>
                                        <div class="space-y-1 text-sm">
                                            <div><span class="font-medium">Name:</span> {{ conflict.existing.name }}</div>
                                            <div><span class="font-medium">Email:</span> {{ conflict.existing.email }}</div>
                                            <div v-if="conflict.existing.student_id">
                                                <span class="font-medium">Student ID:</span> {{ conflict.existing.student_id }}
                                            </div>
                                            <div class="mt-2">
                                                <Link
                                                    :href="route('graduates.show', conflict.existing.id)"
                                                    class="text-sm text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View Graduate #{{ conflict.existing.id }}
                                                </Link>
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
    </AppLayout>
</template>
