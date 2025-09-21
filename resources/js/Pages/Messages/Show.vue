<template>
    <AppLayout title="Message">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ message.subject }}
                </h2>
                <div class="flex items-center space-x-2">
                    <Link :href="route('messages.index')" class="text-gray-500 hover:text-gray-700"> ← Back to Messages </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Message Header -->
                        <div class="mb-6 border-b border-gray-200 pb-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h1 class="mb-2 text-2xl font-bold text-gray-900">
                                        {{ message.subject }}
                                    </h1>
                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">From:</span>
                                            {{ message.sender.name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">To:</span>
                                            {{ message.recipient.name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span>
                                            {{ formatDate(message.created_at) }}
                                        </div>
                                    </div>
                                    <div v-if="message.type !== 'direct'" class="mt-2">
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800"
                                        >
                                            {{ message.type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase()) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="archiveMessage"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Archive
                                    </button>
                                    <Link
                                        :href="route('messages.reply', message.id)"
                                        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-2 text-sm font-medium leading-4 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Reply
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Related Context -->
                        <div v-if="message.related_job || message.related_application" class="mb-6 rounded-lg bg-blue-50 p-4">
                            <h3 class="mb-2 text-sm font-medium text-blue-900">Related to:</h3>
                            <div v-if="message.related_job" class="text-sm text-blue-800">
                                <strong>Job:</strong> {{ message.related_job.title }} at {{ message.related_job.employer.company_name }}
                            </div>
                            <div v-if="message.related_application" class="text-sm text-blue-800">
                                <strong>Application:</strong> {{ message.related_application.job.title }}
                            </div>
                        </div>

                        <!-- Message Content -->
                        <div class="prose max-w-none">
                            <div class="whitespace-pre-wrap text-gray-900">
                                {{ message.content }}
                            </div>
                        </div>

                        <!-- Message Actions -->
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    <span v-if="message.read_at"> Read on {{ formatDate(message.read_at) }} </span>
                                    <span v-else-if="$page.props.auth.user.id === message.recipient_id"> Unread </span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <Link
                                        :href="route('messages.reply', message.id)"
                                        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                            />
                                        </svg>
                                        Reply
                                    </Link>
                                    <button
                                        v-if="$page.props.auth.user.id === message.sender_id"
                                        @click="deleteMessage"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    message: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const archiveMessage = () => {
    router.patch(
        route('messages.archive', props.message.id),
        {},
        {
            onSuccess: () => {
                router.visit(route('messages.index'));
            },
        },
    );
};

const deleteMessage = () => {
    if (confirm('Are you sure you want to delete this message?')) {
        router.delete(route('messages.destroy', props.message.id), {
            onSuccess: () => {
                router.visit(route('messages.index'));
            },
        });
    }
};
</script>
