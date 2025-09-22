<template>
    <AppLayout title="New Message">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New Message</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Recipient -->
                            <div class="mb-6">
                                <label for="recipient_id" class="mb-2 block text-sm font-medium text-gray-700"> To </label>
                                <div v-if="recipient" class="flex items-center rounded-md bg-gray-50 p-3">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ recipient.name }}</div>
                                        <div class="text-sm text-gray-500">{{ recipient.email }}</div>
                                    </div>
                                    <button type="button" @click="clearRecipient" class="text-gray-400 hover:text-gray-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-else>
                                    <input
                                        v-model="recipientSearch"
                                        type="text"
                                        placeholder="Search for a user..."
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @input="searchUsers"
                                    />
                                    <div
                                        v-if="searchResults.length > 0"
                                        class="mt-2 max-h-60 overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg"
                                    >
                                        <div
                                            v-for="user in searchResults"
                                            :key="user.id"
                                            @click="selectRecipient(user)"
                                            class="cursor-pointer border-b border-gray-100 p-3 last:border-b-0 hover:bg-gray-50"
                                        >
                                            <div class="font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.recipient_id" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.recipient_id }}
                                </div>
                            </div>

                            <!-- Subject -->
                            <div class="mb-6">
                                <label for="subject" class="mb-2 block text-sm font-medium text-gray-700"> Subject </label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :class="{ 'border-red-300': form.errors.subject }"
                                />
                                <div v-if="form.errors.subject" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.subject }}
                                </div>
                            </div>

                            <!-- Message Type -->
                            <div class="mb-6">
                                <label for="type" class="mb-2 block text-sm font-medium text-gray-700"> Type </label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="direct">Direct Message</option>
                                    <option value="application_related">Application Related</option>
                                    <option value="system">System Message</option>
                                </select>
                            </div>

                            <!-- Content -->
                            <div class="mb-6">
                                <label for="content" class="mb-2 block text-sm font-medium text-gray-700"> Message </label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="8"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :class="{ 'border-red-300': form.errors.content }"
                                    placeholder="Type your message here..."
                                ></textarea>
                                <div v-if="form.errors.content" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between">
                                <Link
                                    :href="route('messages.index')"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    <svg v-if="form.processing" class="-ml-1 mr-3 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref } from 'vue';

const props = defineProps({
    recipient: Object,
    jobId: Number,
    applicationId: Number,
});

const form = useForm({
    recipient_id: props.recipient?.id || '',
    subject: '',
    content: '',
    type: 'direct',
    related_job_id: props.jobId || null,
    related_application_id: props.applicationId || null,
});

const recipientSearch = ref('');
const searchResults = ref([]);
const recipient = ref(props.recipient);

const searchUsers = debounce(async () => {
    if (recipientSearch.value.length < 2) {
        searchResults.value = [];
        return;
    }

    try {
        const response = await fetch(`/api/users/search?q=${encodeURIComponent(recipientSearch.value)}`);
        const data = await response.json();
        searchResults.value = data.users || [];
    } catch (error) {
        console.error('Error searching users:', error);
        searchResults.value = [];
    }
}, 300);

const selectRecipient = (user) => {
    recipient.value = user;
    form.recipient_id = user.id;
    recipientSearch.value = '';
    searchResults.value = [];
};

const clearRecipient = () => {
    recipient.value = null;
    form.recipient_id = '';
};

const submit = () => {
    form.post(route('messages.store'));
};
</script>













