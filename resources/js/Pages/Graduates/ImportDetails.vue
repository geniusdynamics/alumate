<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
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
        'pending': 'bg-yellow-100 text-yellow-800',
        'processing': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'rolled_back': 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString();
};

const rollbackImport = () => {
    if (confirm(`Are you sure you want to rollback this import? This will remove ${props.importHistory.created_count} graduates that were created.`)) {
        router.post(route('graduates.import.rollback', props.importHistory.id));
    }
};
</script>

<template>
    <Head :title="`Import Details - ${importHistory.filename}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Import Details: {{ importHistory.filename }}
                </h2>
                <div class="flex gap-2">
                    <button v-if="importHistory.status === 'completed' && importHistory.created_count > 0" 
                            @click="rollbackImport"
                            class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                        Rollback Import
                    </button>
                    <Link :href="route('graduates.import.history')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to History
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Import Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Import Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadge(importHistory.status)]">
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
                <div v-if="importHistory.error_message" class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
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
                <div v-if="importHistory.valid_rows && importHistory.valid_rows.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Successfully Processed Rows ({{ importHistory.valid_rows.length }})
                            </h3>
                            <button @click="showValidRows = !showValidRows" 
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                {{ showValidRows ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>
                        
                        <div v-if="showValidRows" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Row</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduate ID</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="row in importHistory.valid_rows" :key="row.row" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ row.row }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ row.data.name }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ row.data.email }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                row.action === 'created' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                                            ]">
                                                {{ row.action.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            <Link :href="route('graduates.show', row.graduate_id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
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
                <div v-if="importHistory.invalid_rows && importHistory.invalid_rows.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Invalid Rows ({{ importHistory.invalid_rows.length }})
                            </h3>
                            <button @click="showInvalidRows = !showInvalidRows" 
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                {{ showInvalidRows ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>
                        
                        <div v-if="showInvalidRows" class="space-y-4">
                            <div v-for="row in importHistory.invalid_rows" :key="row.row" 
                                 class="border border-red-200 rounded-lg p-4 bg-red-50">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-medium text-red-800">Row {{ row.row }}</span>
                                    <span class="text-sm text-red-600">{{ Object.keys(row.errors || {}).length }} error(s)</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Name:</span>
                                        <span class="text-sm text-gray-900 ml-2">{{ row.data.name || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Email:</span>
                                        <span class="text-sm text-gray-900 ml-2">{{ row.data.email || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div v-if="row.errors" class="space-y-1">
                                    <div v-for="(messages, field) in row.errors" :key="field" class="text-sm">
                                        <span class="font-medium text-red-700">{{ field }}:</span>
                                        <ul class="list-disc list-inside ml-4 text-red-600">
                                            <li v-for="message in messages" :key="message">{{ message }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conflicts -->
                <div v-if="importHistory.conflicts && importHistory.conflicts.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Conflicts ({{ importHistory.conflicts.length }})
                            </h3>
                            <button @click="showConflicts = !showConflicts" 
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                {{ showConflicts ? 'Hide' : 'Show' }} Details
                            </button>
                        </div>
                        
                        <div v-if="showConflicts" class="space-y-4">
                            <div v-for="conflict in importHistory.conflicts" :key="conflict.row" 
                                 class="border border-yellow-200 rounded-lg p-4 bg-yellow-50">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-medium text-yellow-800">Row {{ conflict.row }}</span>
                                    <span class="inline-flex px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">
                                        {{ conflict.conflict_type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Import Data:</h4>
                                        <div class="space-y-1 text-sm">
                                            <div><span class="font-medium">Name:</span> {{ conflict.data.name }}</div>
                                            <div><span class="font-medium">Email:</span> {{ conflict.data.email }}</div>
                                            <div v-if="conflict.data.student_id"><span class="font-medium">Student ID:</span> {{ conflict.data.student_id }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Existing Graduate:</h4>
                                        <div class="space-y-1 text-sm">
                                            <div><span class="font-medium">Name:</span> {{ conflict.existing.name }}</div>
                                            <div><span class="font-medium">Email:</span> {{ conflict.existing.email }}</div>
                                            <div v-if="conflict.existing.student_id"><span class="font-medium">Student ID:</span> {{ conflict.existing.student_id }}</div>
                                            <div class="mt-2">
                                                <Link :href="route('graduates.show', conflict.existing.id)" 
                                                      class="text-indigo-600 hover:text-indigo-900 text-sm">
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