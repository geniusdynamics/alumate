<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    conversations: Object,
    candidates: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const selectedConversation = ref(null);
const newMessage = ref('');
const showNewMessageModal = ref(false);
const selectedCandidate = ref(null);

const applyFilters = () => {
    router.get(
        route('employer.communications'),
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const selectConversation = (conversation) => {
    selectedConversation.value = conversation;
    // Mark as read
    if (conversation.unread_count > 0) {
        router.post(route('employer.communications.mark-read', conversation.id));
    }
};

const sendMessage = () => {
    if (!newMessage.value.trim()) return;

    router.post(
        route('employer.communications.send', selectedConversation.value.id),
        {
            message: newMessage.value,
        },
        {
            onSuccess: () => {
                newMessage.value = '';
            },
        },
    );
};

const startNewConversation = () => {
    if (!selectedCandidate.value) return;

    router.post(
        route('employer.communications.start'),
        {
            candidate_id: selectedCandidate.value,
            message: newMessage.value,
        },
        {
            onSuccess: () => {
                showNewMessageModal.value = false;
                newMessage.value = '';
                selectedCandidate.value = null;
            },
        },
    );
};

const getStatusBadge = (status) => {
    const badges = {
        active: 'bg-green-100 text-green-800',
        archived: 'bg-gray-100 text-gray-800',
        blocked: 'bg-red-100 text-red-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const archiveConversation = (conversation) => {
    if (confirm('Are you sure you want to archive this conversation?')) {
        router.patch(route('employer.communications.archive', conversation.id));
    }
};

const blockCandidate = (conversation) => {
    if (confirm('Are you sure you want to block this candidate?')) {
        router.patch(route('employer.communications.block', conversation.id));
    }
};
</script>

<template>
    <Head title="Communications" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Communications</h2>
                <button @click="showNewMessageModal = true" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                    New Message
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow" style="height: 600px">
                    <div class="flex h-full">
                        <!-- Conversations List -->
                        <div class="flex w-1/3 flex-col border-r border-gray-200">
                            <!-- Search and Filters -->
                            <div class="border-b border-gray-200 p-4">
                                <div class="space-y-3">
                                    <input
                                        type="text"
                                        v-model="search"
                                        placeholder="Search conversations..."
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />

                                    <select
                                        v-model="status"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">All Conversations</option>
                                        <option value="active">Active</option>
                                        <option value="archived">Archived</option>
                                        <option value="blocked">Blocked</option>
                                    </select>

                                    <div class="flex gap-2">
                                        <button
                                            @click="applyFilters"
                                            class="flex-1 rounded bg-indigo-600 px-2 py-1 text-sm font-medium text-white hover:bg-indigo-700"
                                        >
                                            Filter
                                        </button>
                                        <button
                                            @click="clearFilters"
                                            class="flex-1 rounded bg-gray-300 px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-400"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversations -->
                            <div class="flex-1 overflow-y-auto">
                                <div v-if="conversations.data.length > 0" class="divide-y divide-gray-200">
                                    <div
                                        v-for="conversation in conversations.data"
                                        :key="conversation.id"
                                        @click="selectConversation(conversation)"
                                        :class="[
                                            'cursor-pointer p-4 transition-colors hover:bg-gray-50',
                                            selectedConversation?.id === conversation.id ? 'border-r-2 border-indigo-500 bg-indigo-50' : '',
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <p class="truncate text-sm font-medium text-gray-900">
                                                        {{ conversation.candidate?.user?.name }}
                                                    </p>
                                                    <div class="flex items-center gap-1">
                                                        <span
                                                            v-if="conversation.unread_count > 0"
                                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                                                        >
                                                            {{ conversation.unread_count }}
                                                        </span>
                                                        <span
                                                            :class="[
                                                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                                getStatusBadge(conversation.status),
                                                            ]"
                                                        >
                                                            {{ conversation.status?.toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ conversation.candidate?.course?.name }}
                                                </p>
                                                <p class="mt-1 truncate text-sm text-gray-600">
                                                    {{ conversation.last_message?.content || 'No messages yet' }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-400">
                                                    {{ conversation.last_message ? formatDate(conversation.last_message.created_at) : '' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-4 text-center text-gray-500">No conversations found</div>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex flex-1 flex-col">
                            <div v-if="selectedConversation" class="flex h-full flex-col">
                                <!-- Conversation Header -->
                                <div class="border-b border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">
                                                {{ selectedConversation.candidate?.user?.name }}
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                {{ selectedConversation.candidate?.course?.name }} • Graduated
                                                {{ selectedConversation.candidate?.graduation_year }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <Link
                                                :href="route('applications.show', selectedConversation.application_id)"
                                                v-if="selectedConversation.application_id"
                                                class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700"
                                            >
                                                View Application
                                            </Link>
                                            <button
                                                @click="archiveConversation(selectedConversation)"
                                                class="rounded bg-gray-600 px-3 py-1 text-sm text-white hover:bg-gray-700"
                                            >
                                                Archive
                                            </button>
                                            <button
                                                @click="blockCandidate(selectedConversation)"
                                                class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                                            >
                                                Block
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Messages -->
                                <div class="flex-1 space-y-4 overflow-y-auto p-4">
                                    <div
                                        v-for="message in selectedConversation.messages"
                                        :key="message.id"
                                        :class="['flex', message.sender_type === 'employer' ? 'justify-end' : 'justify-start']"
                                    >
                                        <div
                                            :class="[
                                                'max-w-xs rounded-lg px-4 py-2 lg:max-w-md',
                                                message.sender_type === 'employer' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-900',
                                            ]"
                                        >
                                            <p class="text-sm">{{ message.content }}</p>
                                            <p :class="['mt-1 text-xs', message.sender_type === 'employer' ? 'text-indigo-200' : 'text-gray-500']">
                                                {{ formatTime(message.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div class="border-t border-gray-200 p-4">
                                    <div class="flex gap-2">
                                        <input
                                            v-model="newMessage"
                                            type="text"
                                            placeholder="Type your message..."
                                            @keyup.enter="sendMessage"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <button
                                            @click="sendMessage"
                                            :disabled="!newMessage.trim()"
                                            class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            Send
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- No Conversation Selected -->
                            <div v-else class="flex flex-1 items-center justify-center">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                        />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No conversation selected</h3>
                                    <p class="mt-1 text-sm text-gray-500">Choose a conversation from the list to start messaging</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Message Modal -->
        <div
            v-if="showNewMessageModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @click="showNewMessageModal = false"
        >
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg" @click.stop>
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Start New Conversation</h3>

                    <div class="mb-4">
                        <label for="candidate" class="mb-2 block text-sm font-medium text-gray-700">Select Candidate</label>
                        <select
                            id="candidate"
                            v-model="selectedCandidate"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Choose a candidate...</option>
                            <option v-for="candidate in candidates" :key="candidate.id" :value="candidate.id">
                                {{ candidate.user?.name }} - {{ candidate.course?.name }}
                            </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="mb-2 block text-sm font-medium text-gray-700">Message</label>
                        <textarea
                            id="message"
                            v-model="newMessage"
                            rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Type your message..."
                        ></textarea>
                    </div>

                    <div class="flex gap-4">
                        <button
                            @click="showNewMessageModal = false"
                            class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="startNewConversation"
                            :disabled="!selectedCandidate || !newMessage.trim()"
                            class="flex-1 rounded-md bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Send Message
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
