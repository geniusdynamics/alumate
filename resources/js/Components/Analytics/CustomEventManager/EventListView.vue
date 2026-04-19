<template>
    <div class="event-list-view" role="region" aria-label="Custom events list">
        <!-- Filters and Search -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
                <!-- Search -->
                <div class="flex-1">
                    <label for="event-search" class="sr-only">Search events</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            id="event-search"
                            v-model="searchQuery"
                            type="text"
                            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="Search events..."
                            @input="handleSearch"
                        />
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category-filter" class="sr-only">Filter by category</label>
                    <select
                        id="category-filter"
                        v-model="selectedCategory"
                        @change="handleCategoryFilter"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Categories</option>
                        <option value="conversion">Conversion</option>
                        <option value="engagement">Engagement</option>
                        <option value="error">Error</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status-filter" class="sr-only">Filter by status</label>
                    <select
                        id="status-filter"
                        v-model="selectedStatus"
                        @change="handleStatusFilter"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <div class="rounded-lg border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Custom events table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                @click="sortBy('name')"
                                :aria-sort="sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            >
                                Event Name
                                <span v-if="sortField === 'name'" class="ml-1">
                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                @click="sortBy('category')"
                                :aria-sort="sortField === 'category' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            >
                                Category
                                <span v-if="sortField === 'category'" class="ml-1">
                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                @click="sortBy('total_events')"
                                :aria-sort="sortField === 'total_events' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            >
                                Total Events
                                <span v-if="sortField === 'total_events'" class="ml-1">
                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                @click="sortBy('unique_users')"
                                :aria-sort="sortField === 'unique_users' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            >
                                Unique Users
                                <span v-if="sortField === 'unique_users'" class="ml-1">
                                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Last Tracked
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="filteredEvents.length === 0" class="text-center">
                            <td colspan="7" class="px-6 py-8 text-sm text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="mb-2 h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <p>No custom events found</p>
                                    <p v-if="searchQuery || selectedCategory || selectedStatus" class="text-xs">
                                        Try adjusting your filters
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="event in paginatedEvents" :key="event.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ event.name }}
                                <div class="text-xs text-gray-500">{{ event.description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getCategoryBadgeClass(event.category)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ event.category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="event.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ event.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ event.analytics?.total_events?.toLocaleString() || 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ event.analytics?.unique_users?.toLocaleString() || 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ event.last_tracked ? formatDate(event.last_tracked) : 'Never' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        @click="$emit('view-analytics', event.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                        :aria-label="`View analytics for ${event.name}`"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="$emit('edit-event', event)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                        :aria-label="`Edit ${event.name}`"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="$emit('delete-event', event.id)"
                                        class="text-red-600 hover:text-red-900"
                                        :aria-label="`Delete ${event.name}`"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                            to
                            <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredEvents.length) }}</span>
                            of
                            <span class="font-medium">{{ filteredEvents.length }}</span>
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                            >
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0',
                                    page === currentPage
                                        ? 'z-10 bg-blue-600 text-white focus:z-20'
                                        : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50'
                                ]"
                            >
                                {{ page }}
                            </button>
                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                            >
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { CustomEventDefinition } from '../../../types/analytics';

// Props
const props = defineProps<{
    events: CustomEventDefinition[];
    isLoading: boolean;
}>();

// Emits
defineEmits<{
    'edit-event': [event: CustomEventDefinition];
    'delete-event': [eventId: string];
    'view-analytics': [eventId: string];
}>();

// Reactive state
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedStatus = ref('');
const sortField = ref<'name' | 'category' | 'total_events' | 'unique_users'>('name');
const sortDirection = ref<'asc' | 'desc'>('asc');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Computed properties
const filteredEvents = computed(() => {
    let filtered = [...props.events];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(event =>
            event.name.toLowerCase().includes(query) ||
            event.description?.toLowerCase().includes(query)
        );
    }

    // Category filter
    if (selectedCategory.value) {
        filtered = filtered.filter(event => event.category === selectedCategory.value);
    }

    // Status filter
    if (selectedStatus.value) {
        const isActive = selectedStatus.value === 'active';
        filtered = filtered.filter(event => event.is_active === isActive);
    }

    // Sorting
    filtered.sort((a, b) => {
        let aValue: any, bValue: any;

        switch (sortField.value) {
            case 'name':
                aValue = a.name.toLowerCase();
                bValue = b.name.toLowerCase();
                break;
            case 'category':
                aValue = a.category;
                bValue = b.category;
                break;
            case 'total_events':
                aValue = a.analytics?.total_events || 0;
                bValue = b.analytics?.total_events || 0;
                break;
            case 'unique_users':
                aValue = a.analytics?.unique_users || 0;
                bValue = b.analytics?.unique_users || 0;
                break;
            default:
                return 0;
        }

        if (sortDirection.value === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });

    return filtered;
});

const totalPages = computed(() => Math.ceil(filteredEvents.value.length / itemsPerPage.value));

const paginatedEvents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredEvents.value.slice(start, end);
});

const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            pages.push(1, 2, 3, 4, 5, '...', total);
        } else if (current >= total - 3) {
            pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total);
        } else {
            pages.push(1, '...', current - 1, current, current + 1, '...', total);
        }
    }

    return pages.filter(page => page !== '...').map(page => Number(page));
});

// Methods
const handleSearch = () => {
    currentPage.value = 1;
};

const handleCategoryFilter = () => {
    currentPage.value = 1;
};

const handleStatusFilter = () => {
    currentPage.value = 1;
};

const sortBy = (field: typeof sortField.value) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const getCategoryBadgeClass = (category: string) => {
    const classes = {
        conversion: 'bg-blue-100 text-blue-800',
        engagement: 'bg-green-100 text-green-800',
        error: 'bg-red-100 text-red-800',
        custom: 'bg-purple-100 text-purple-800',
    };
    return classes[category as keyof typeof classes] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

// Watchers
watch(() => props.events, () => {
    currentPage.value = 1;
});
</script>

<style scoped>
.event-list-view {
    @apply w-full;
}

/* Custom focus styles for accessibility */
.event-list-view button:focus,
.event-list-view select:focus,
.event-list-view input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Table hover effects */
.event-list-view tbody tr:hover {
    @apply bg-gray-50;
}

/* Pagination styles */
.isolate {
    @apply isolate;
}

.-space-x-px > * + * {
    margin-left: calc(0.5rem * calc(1 - 0));
    margin-right: calc(0.5rem * 0);
}

/* Responsive design */
@media (max-width: 768px) {
    .event-list-view .overflow-x-auto {
        @apply overflow-x-scroll;
    }

    .event-list-view table {
        @apply min-w-full;
    }
}
</style>