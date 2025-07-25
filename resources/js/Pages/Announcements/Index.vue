<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    announcements: Object,
    filters: Object,
});

const typeFilter = ref(props.filters.type || '');
const priorityFilter = ref(props.filters.priority || '');

const applyFilters = () => {
    router.get(route('announcements.index'), {
        type: typeFilter.value,
        priority: priorityFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    typeFilter.value = '';
    priorityFilter.value = '';
    applyFilters();
};

const getTypeColor = (type) => {
    const colors = {
        'general': 'bg-blue-100 text-blue-800',
        'urgent': 'bg-red-100 text-red-800',
        'maintenance': 'bg-yellow-100 text-yellow-800',
        'feature': 'bg-green-100 text-green-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getPriorityColor = (priority) => {
    const colors = {
        'low': 'bg-gray-100 text-gray-800',
        'normal': 'bg-blue-100 text-blue-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

const getTypeIcon = (type) => {
    const icons = {
        'general': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'urgent': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z',
        'maintenance': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'feature': 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    };
    return icons[type] || icons['general'];
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Announcements" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Announcements
                </h2>
                <Link v-if="$page.props.auth.user.roles.some(role => ['super-admin', 'institution-admin'].includes(role.name))" 
                      :href="route('announcements.create')" 
                      class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                    Create Announcement
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Announcements</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select id="type" v-model="typeFilter"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                <option value="general">General</option>
                                <option value="urgent">Urgent</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="feature">Feature</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select id="priority" v-model="priorityFilter"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Apply Filters
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="space-y-4">
                    <div v-for="announcement in announcements.data" :key="announcement.id" 
                         class="bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', getTypeColor(announcement.type)]">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(announcement.type)" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                <Link :href="route('announcements.show', announcement.id)" 
                                                      class="hover:text-indigo-600">
                                                    {{ announcement.title }}
                                                </Link>
                                            </h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getTypeColor(announcement.type)]">
                                                    {{ announcement.type.toUpperCase() }}
                                                </span>
                                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getPriorityColor(announcement.priority)]">
                                                    {{ announcement.priority.toUpperCase() }}
                                                </span>
                                                <span v-if="announcement.is_pinned" 
                                                      class="inline-flex px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                                    PINNED
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-700 mb-4 line-clamp-3">
                                        {{ announcement.content }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center gap-4">
                                            <span>By {{ announcement.creator?.name }}</span>
                                            <span>{{ formatDate(announcement.published_at) }} at {{ formatTime(announcement.published_at) }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <Link :href="route('announcements.show', announcement.id)" 
                                                  class="text-indigo-600 hover:text-indigo-500 font-medium">
                                                Read More
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="announcements.links" class="bg-white px-4 py-3 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="announcements.prev_page_url" :href="announcements.prev_page_url" 
                                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="announcements.next_page_url" :href="announcements.next_page_url" 
                                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ announcements.from }} to {{ announcements.to }} of {{ announcements.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="link in announcements.links" :key="link.label" 
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

                <!-- Empty State -->
                <div v-if="announcements.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-12 text-center">
                        <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No announcements found</h3>
                        <p class="text-gray-600 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your filters.' : 'There are no announcements at this time.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>