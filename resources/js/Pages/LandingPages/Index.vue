<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useLandingPageStore } from '@/Stores/landingPage';
import { Link } from '@inertiajs/vue3';

const store = useLandingPageStore();
const statusFilter = ref<string>('');

onMounted(() => {
    store.fetchLandingPages();
});

function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        reviewing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        published: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        archived: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        suspended: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return colors[status] ?? colors.draft;
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Landing Pages</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage and publish your landing pages</p>
                </div>
                <Link href="/landing-pages/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Create Page
                </Link>
            </div>

            <!-- Filter -->
            <div class="mb-6">
                <select v-model="statusFilter" @change="store.fetchLandingPages({ status: statusFilter || undefined })" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="reviewing">Reviewing</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <!-- Loading -->
            <div v-if="store.loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Pages List -->
            <div v-else-if="store.landingPages.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                No landing pages yet. Create your first page to get started.
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conversions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Updated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="page in store.landingPages" :key="page.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <Link :href="`/landing-pages/${page.id}/edit`" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-900">
                                    {{ page.name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusColor(page.status)]">
                                    {{ page.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ page.usage_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ page.conversion_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(page.updated_at).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button v-if="page.status === 'draft'" @click="store.publishLandingPage(page.id)" class="text-green-600 dark:text-green-400 hover:text-green-800 mr-3">Publish</button>
                                <button v-if="page.status === 'published'" @click="store.unpublishLandingPage(page.id)" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 mr-3">Unpublish</button>
                                <Link :href="`/landing-pages/${page.id}/edit`" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">Edit</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
