<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    importHistories: Object,
});

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
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Graduate Import History</h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('graduates.import.create')"
                        class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                    >
                        New Import
                    </Link>
                    <Link :href="route('graduates.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                        Back to Graduates
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">File & Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Results</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Success Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="history in importHistories.data" :key="history.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ history.filename }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ formatDate(history.created_at) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', getStatusBadge(history.status)]">
                                            {{ history.status.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
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
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="mr-2 h-2 w-16 rounded-full bg-gray-200">
                                                <div
                                                    :class="[
                                                        'h-2 rounded-full',
                                                        history.success_rate >= 80
                                                            ? 'bg-green-500'
                                                            : history.success_rate >= 60
                                                              ? 'bg-yellow-500'
                                                              : 'bg-red-500',
                                                    ]"
                                                    :style="`width: ${history.success_rate || 0}%`"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900"> {{ Math.round(history.success_rate || 0) }}% </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        <div v-if="history.started_at && history.completed_at">
                                            {{ Math.round((new Date(history.completed_at) - new Date(history.started_at)) / 1000) }}s
                                        </div>
                                        <div v-else-if="history.started_at">Processing...</div>
                                        <div v-else>-</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <div class="flex gap-2">
                                            <Link :href="route('graduates.import.show', history.id)" class="text-indigo-600 hover:text-indigo-900">
                                                View Details
                                            </Link>
                                            <button
                                                v-if="history.status === 'completed' && history.created_count > 0"
                                                @click="rollbackImport(history)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Rollback
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="importHistories.links" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="importHistories.prev_page_url"
                                    :href="importHistories.prev_page_url"
                                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="importHistories.next_page_url"
                                    :href="importHistories.next_page_url"
                                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ importHistories.from }} to {{ importHistories.to }} of {{ importHistories.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                        <Link
                                            v-for="link in importHistories.links"
                                            :key="link.label"
                                            :href="link.url"
                                            :class="[
                                                'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                                link.active
                                                    ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600'
                                                    : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                            ]"
                                            v-html="link.label"
                                        >
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="importHistories.data.length === 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mb-4 text-lg text-gray-500">No import history found</div>
                        <p class="mb-4 text-gray-400">You haven't performed any graduate imports yet.</p>
                        <Link
                            :href="route('graduates.import.create')"
                            class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                        >
                            Start Your First Import
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

