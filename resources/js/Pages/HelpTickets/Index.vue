<template>
    <AppLayout title="Help & Support">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Help & Support
                </h2>
                <Link
                    :href="route('help-tickets.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Create Ticket
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filters -->
                        <div class="mb-6 flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Search tickets..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="search"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="searchForm.status"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Status</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    v-model="searchForm.priority"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tickets List -->
                        <div class="space-y-4">
                            <div
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <Link
                                                :href="route('help-tickets.show', ticket.id)"
                                                class="font-medium text-lg text-gray-900 hover:text-indigo-600"
                                            >
                                                #{{ ticket.id }} - {{ ticket.subject }}
                                            </Link>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    getStatusColor(ticket.status)
                                                ]"
                                            >
                                                {{ ticket.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    getPriorityColor(ticket.priority)
                                                ]"
                                            >
                                                {{ ticket.priority.replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-gray-700 text-sm mb-3 line-clamp-2">
                                            {{ ticket.description }}
                                        </p>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <div>
                                                <span class="font-medium">Category:</span>
                                                {{ ticket.category.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Created:</span>
                                                {{ formatDate(ticket.created_at) }}
                                            </div>
                                            <div v-if="ticket.responses_count > 0">
                                                <span class="font-medium">Responses:</span>
                                                {{ ticket.responses_count }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 text-right">
                                        <div v-if="ticket.assigned_to" class="text-sm">
                                            <div class="text-gray-500">Assigned to</div>
                                            <div class="font-medium text-gray-900">{{ ticket.assigned_to.name }}</div>
                                        </div>
                                        <div v-if="ticket.updated_at !== ticket.created_at" class="text-xs text-gray-500 mt-1">
                                            Updated {{ formatDate(ticket.updated_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="tickets.links" class="mt-6">
                            <Pagination :links="tickets.links" />
                        </div>

                        <!-- Empty State -->
                        <div v-if="tickets.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No support tickets</h3>
                            <p class="mt-1 text-sm text-gray-500">Need help? Create a support ticket and we'll assist you.</p>
                            <div class="mt-6">
                                <Link
                                    :href="route('help-tickets.create')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Create Ticket
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    tickets: Object,
    filters: Object,
})

const searchForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
})

const search = debounce(() => {
    router.get(route('help-tickets.index'), {
        search: searchForm.search,
        status: searchForm.status,
        priority: searchForm.priority,
    }, {
        preserveState: true,
        replace: true,
    })
}, 300)

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-800',
        normal: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
    }
    return colors[priority] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>