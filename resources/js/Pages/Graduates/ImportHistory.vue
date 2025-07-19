<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    importHistories: Object,
});

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

const rollbackImport = (importHistory) => {
    if (confirm(`Are you sure you want to rollback this import? This will remove ${importHistory.created_count} graduates that were created.`)) {
        router.post(route('graduates.import.rollback', importHistory.id));
    }
};
</script>

<template>
    <Head title="Import History" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Graduate Import History
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('graduates.import.create')" 
                          class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                        New Import
                    </Link>
                    <Link :href="route('graduates.index')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to Graduates
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        File & Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Results
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Success Rate
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Duration
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="history in importHistories.data" :key="history.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ history.filename }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ formatDate(history.created_at) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadge(history.status)]">
                                            {{ history.status.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Total:</span>
                                                <span class="font-medium">{{ history.total_rows || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-green-600">Created:</span>
                                                <span class="font-medium">{{ history.created_count || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-blue-600">Updated:</span>
                                                <span class="font-medium">{{ history.updated_count || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-red-600">Skipped:</span>
                                                <span class="font-medium">{{ history.skipped_count || 0 }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div :class="[
                                                    'h-2 rounded-full',
                                                    history.success_rate >= 80 ? 'bg-green-500' : 
                                                    history.success_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500'
                                                ]" 
                                                     :style="`width: ${history.success_rate || 0}%`"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ Math.round(history.success_rate || 0) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div v-if="history.started_at && history.completed_at">
                                            {{ Math.round((new Date(history.completed_at) - new Date(history.started_at)) / 1000) }}s
                                        </div>
                                        <div v-else-if="history.started_at">
                                            Processing...
                                        </div>
                                        <div v-else>
                                            -
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            <Link :href="route('graduates.import.show', history.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View Details
                                            </Link>
                                            <button v-if="history.status === 'completed' && history.created_count > 0" 
                                                    @click="rollbackImport(history)"
                                                    class="text-red-600 hover:text-red-900">
                                                Rollback
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="importHistories.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="importHistories.prev_page_url" :href="importHistories.prev_page_url" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </Link>
                                <Link v-if="importHistories.next_page_url" :href="importHistories.next_page_url" 
                                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ importHistories.from }} to {{ importHistories.to }} of {{ importHistories.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link v-for="link in importHistories.links" :key="link.label" 
                                              :href="link.url" 
                                              :class="[
                                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                  link.active 
                                                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                              ]"
                                              v-html="link.label">
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="importHistories.data.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No import history found</div>
                        <p class="text-gray-400 mb-4">
                            You haven't performed any graduate imports yet.
                        </p>
                        <Link :href="route('graduates.import.create')" 
                              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Start Your First Import
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>