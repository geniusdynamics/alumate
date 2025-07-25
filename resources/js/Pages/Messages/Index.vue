<template>
    <AppLayout title="Messages">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Messages
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Tabs -->
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex space-x-8">
                                <Link 
                                    :href="route('messages.index', { tab: 'inbox' })"
                                    :class="[
                                        'py-2 px-1 border-b-2 font-medium text-sm',
                                        tab === 'inbox' 
                                            ? 'border-indigo-500 text-indigo-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    Inbox
                                    <span v-if="unreadCount > 0" class="ml-2 bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ unreadCount }}
                                    </span>
                                </Link>
                                <Link 
                                    :href="route('messages.index', { tab: 'sent' })"
                                    :class="[
                                        'py-2 px-1 border-b-2 font-medium text-sm',
                                        tab === 'sent' 
                                            ? 'border-indigo-500 text-indigo-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    Sent
                                </Link>
                                <Link 
                                    :href="route('messages.index', { tab: 'archived' })"
                                    :class="[
                                        'py-2 px-1 border-b-2 font-medium text-sm',
                                        tab === 'archived' 
                                            ? 'border-indigo-500 text-indigo-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    Archived
                                </Link>
                            </nav>
                        </div>

                        <!-- Filters -->
                        <div class="mb-6 flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Search messages..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="search"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="searchForm.type"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="search"
                                >
                                    <option value="">All Types</option>
                                    <option value="direct">Direct</option>
                                    <option value="application_related">Application Related</option>
                                    <option value="system">System</option>
                                </select>
                            </div>
                            <div>
                                <Link
                                    :href="route('messages.create')"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    New Message
                                </Link>
                            </div>
                        </div>

                        <!-- Messages List -->
                        <div class="space-y-4">
                            <div
                                v-for="message in messages.data"
                                :key="message.id"
                                class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <Link
                                                :href="route('messages.show', message.id)"
                                                :class="[
                                                    'font-medium text-lg',
                                                    !message.read_at && tab === 'inbox' ? 'text-gray-900' : 'text-gray-700'
                                                ]"
                                            >
                                                {{ message.subject }}
                                            </Link>
                                            <span
                                                v-if="!message.read_at && tab === 'inbox'"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                            >
                                                New
                                            </span>
                                            <span
                                                v-if="message.type !== 'direct'"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                            >
                                                {{ message.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span v-if="tab === 'sent'">To: {{ message.recipient.name }}</span>
                                            <span v-else>From: {{ message.sender.name }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ formatDate(message.created_at) }}</span>
                                        </div>
                                        <p class="text-gray-700 text-sm line-clamp-2">
                                            {{ message.content }}
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button
                                            v-if="tab !== 'archived'"
                                            @click="archiveMessage(message)"
                                            class="text-gray-400 hover:text-gray-600"
                                            title="Archive"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l4 4 4-4m0 0l4-4 4 4m-4-4v12" />
                                            </svg>
                                        </button>
                                        <button
                                            v-else
                                            @click="unarchiveMessage(message)"
                                            class="text-gray-400 hover:text-gray-600"
                                            title="Unarchive"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l4-4 4 4m0 0l4-4-4-4m4 4H3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="messages.links" class="mt-6">
                            <Pagination :links="messages.links" />
                        </div>

                        <!-- Empty State -->
                        <div v-if="messages.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4-4-4m0 0L9 9l-4-4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No messages</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ tab === 'inbox' ? "You don't have any messages yet." : `No ${tab} messages found.` }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    messages: Object,
    tab: String,
    filters: Object,
    unreadCount: Number,
})

const searchForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
})

const search = debounce(() => {
    router.get(route('messages.index'), {
        tab: props.tab,
        search: searchForm.search,
        type: searchForm.type,
    }, {
        preserveState: true,
        replace: true,
    })
}, 300)

const archiveMessage = (message) => {
    router.patch(route('messages.archive', message.id), {}, {
        preserveScroll: true,
    })
}

const unarchiveMessage = (message) => {
    router.patch(route('messages.unarchive', message.id), {}, {
        preserveScroll: true,
    })
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