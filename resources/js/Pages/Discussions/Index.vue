<template>
    <AppLayout title="Discussions">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Discussions</h2>
                <Link
                    :href="route('discussions.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-900"
                >
                    Start Discussion
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filters -->
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row">
                            <div class="flex-1">
                                <input
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Search discussions..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="search"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="searchForm.category"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Categories</option>
                                    <option value="general">General</option>
                                    <option value="job_search">Job Search</option>
                                    <option value="career_advice">Career Advice</option>
                                    <option value="networking">Networking</option>
                                    <option value="technical">Technical</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    v-model="searchForm.sort"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="latest">Latest</option>
                                    <option value="popular">Most Popular</option>
                                    <option value="most_replies">Most Replies</option>
                                </select>
                            </div>
                        </div>

                        <!-- Discussions List -->
                        <div class="space-y-6">
                            <div
                                v-for="discussion in discussions.data"
                                :key="discussion.id"
                                class="rounded-lg border p-6 transition-colors hover:bg-gray-50"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="mb-2 flex items-center space-x-2">
                                            <Link
                                                :href="route('discussions.show', discussion.id)"
                                                class="text-xl font-semibold text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ discussion.title }}
                                            </Link>
                                            <span
                                                v-if="discussion.is_pinned"
                                                class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800"
                                            >
                                                Pinned
                                            </span>
                                            <span
                                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800"
                                            >
                                                {{ discussion.category.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase()) }}
                                            </span>
                                        </div>

                                        <p class="mb-3 line-clamp-2 text-gray-700">
                                            {{ discussion.content }}
                                        </p>

                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <div class="flex items-center space-x-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                    />
                                                </svg>
                                                <span>{{ discussion.author.name }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                <span>{{ formatDate(discussion.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                                    />
                                                </svg>
                                                <span>{{ discussion.replies_count }} replies</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                                    />
                                                </svg>
                                                <span>{{ discussion.likes_count }} likes</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="discussion.last_reply" class="ml-4 text-right">
                                        <div class="text-sm text-gray-500">Last reply</div>
                                        <div class="text-sm font-medium text-gray-900">{{ discussion.last_reply.author.name }}</div>
                                        <div class="text-xs text-gray-500">{{ formatDate(discussion.last_reply.created_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="discussions.links" class="mt-6">
                            <Pagination :links="discussions.links" />
                        </div>

                        <!-- Empty State -->
                        <div v-if="discussions.data.length === 0" class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No discussions</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new discussion.</p>
                            <div class="mt-6">
                                <Link
                                    :href="route('discussions.create')"
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Start Discussion
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive } from 'vue';

const props = defineProps({
    discussions: Object,
    filters: Object,
});

const searchForm = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
    sort: props.filters.sort || 'latest',
});

const search = debounce(() => {
    router.get(
        route('discussions.index'),
        {
            search: searchForm.search,
            category: searchForm.category,
            sort: searchForm.sort,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>















