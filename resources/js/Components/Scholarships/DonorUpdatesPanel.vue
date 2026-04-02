<template>
    <div class="rounded-lg bg-white p-6 shadow-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Scholarship Updates</h2>
            <div class="flex space-x-3">
                <select
                    v-model="selectedScholarship"
                    @change="fetchUpdates"
                    class="rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Scholarships</option>
                    <option v-for="scholarship in scholarships" :key="scholarship.id" :value="scholarship.id">
                        {{ scholarship.name }}
                    </option>
                </select>
                <button @click="fetchUpdates" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">Refresh</button>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
        </div>

        <div v-else-if="updates.length === 0" class="py-8 text-center">
            <div class="mb-4 text-gray-400">
                <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                </svg>
            </div>
            <p class="text-gray-600">No updates available for your scholarships.</p>
        </div>

        <div v-else class="space-y-6">
            <div
                v-for="update in updates"
                :key="`${update.scholarship_name}-${update.recipient_name}-${update.update_date}`"
                class="rounded-lg border border-gray-200 p-6 transition-shadow hover:shadow-md"
            >
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ update.scholarship_name }}</h3>
                        <p class="text-sm text-gray-600">Update from {{ update.recipient_name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">{{ formatDate(update.update_date) }}</p>
                    </div>
                </div>

                <div v-if="update.updates && update.updates.length > 0" class="space-y-4">
                    <div v-for="(updateItem, index) in update.updates" :key="index" class="rounded-lg bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-900">{{ updateItem.title }}</h4>
                        <p class="mb-2 text-gray-700">{{ updateItem.description }}</p>
                        <p class="text-xs text-gray-500">{{ formatDate(updateItem.date) }}</p>
                    </div>
                </div>

                <div v-if="update.success_story" class="mt-4 rounded-lg bg-blue-50 p-4">
                    <h4 class="mb-2 font-medium text-blue-900">Success Story</h4>
                    <p class="text-blue-800">{{ update.success_story }}</p>
                </div>

                <div class="mt-4 flex justify-end space-x-3">
                    <button
                        @click="sendMessage(update)"
                        class="rounded bg-gray-100 px-3 py-1 text-sm text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Send Message
                    </button>
                    <button
                        @click="viewRecipientProfile(update)"
                        class="rounded bg-blue-100 px-3 py-1 text-sm text-blue-700 transition-colors hover:bg-blue-200"
                    >
                        View Profile
                    </button>
                </div>
            </div>

            <!-- Load More Button -->
            <div v-if="hasMore" class="text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="rounded-lg bg-gray-100 px-6 py-2 text-gray-700 transition-colors hover:bg-gray-200 disabled:opacity-50"
                >
                    {{ loadingMore ? 'Loading...' : 'Load More Updates' }}
                </button>
            </div>
        </div>

        <!-- Message Modal -->
        <div v-if="showMessageModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeMessageModal"></div>

                <div
                    class="my-8 inline-block w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Send Message</h3>
                        <button @click="closeMessageModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="sendMessageToRecipient">
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700"> To: {{ selectedUpdate?.recipient_name }} </label>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Subject </label>
                            <input
                                v-model="messageForm.subject"
                                type="text"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter message subject"
                            />
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Message </label>
                            <textarea
                                v-model="messageForm.message"
                                required
                                rows="4"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Write your message here..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeMessageModal"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="sendingMessage"
                                class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ sendingMessage ? 'Sending...' : 'Send Message' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/Utils/logger';
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface Scholarship {
    id: number;
    name: string;
}

interface DonorUpdate {
    scholarship_name: string;
    recipient_name: string;
    update_date: string;
    updates?: Array<{
        title: string;
        description: string;
        date: string;
    }>;
    success_story?: string;
}

const loading = ref(true);
const loadingMore = ref(false);
const updates = ref<DonorUpdate[]>([]);
const scholarships = ref<Scholarship[]>([]);
const selectedScholarship = ref('');
const hasMore = ref(false);
const page = ref(1);

const showMessageModal = ref(false);
const selectedUpdate = ref<DonorUpdate | null>(null);
const sendingMessage = ref(false);
const messageForm = ref({
    subject: '',
    message: '',
});

const fetchUpdates = async (loadMore = false) => {
    if (loadMore) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        page.value = 1;
    }

    try {
        const params = new URLSearchParams({
            page: page.value.toString(),
        });

        if (selectedScholarship.value) {
            params.append('scholarship_id', selectedScholarship.value);
        }

        const response = await axios.get(`/api/user/donor-updates?${params}`);

        if (response.data.success) {
            if (loadMore) {
                updates.value.push(...response.data.data);
            } else {
                updates.value = response.data.data;
            }

            hasMore.value = response.data.data.length === 15; // Assuming 15 per page
        }
    } catch (error) {
        console.error('Error fetching donor updates:', error);
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    page.value++;
    fetchUpdates(true);
};

const sendMessage = (update: DonorUpdate) => {
    selectedUpdate.value = update;
    messageForm.value.subject = `Thank you for your update - ${update.scholarship_name}`;
    messageForm.value.message = '';
    showMessageModal.value = true;
};

const closeMessageModal = () => {
    showMessageModal.value = false;
    selectedUpdate.value = null;
    messageForm.value = { subject: '', message: '' };
};

const sendMessageToRecipient = async () => {
    sendingMessage.value = true;

    try {
        // Implementation for sending message to recipient
        logger.log('Sending message:', messageForm.value);

        // Close modal after successful send
        closeMessageModal();
    } catch (error) {
        console.error('Error sending message:', error);
    } finally {
        sendingMessage.value = false;
    }
};

const viewRecipientProfile = (update: DonorUpdate) => {
    // Implementation for viewing recipient profile
    logger.log('Viewing profile for:', update.recipient_name);
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

onMounted(async () => {
    // Fetch user's scholarships for filter
    try {
        const response = await axios.get('/api/scholarships?creator_id=' + 'current_user_id');
        if (response.data.success) {
            scholarships.value = response.data.data.data;
        }
    } catch (error) {
        console.error('Error fetching scholarships:', error);
    }

    // Fetch initial updates
    await fetchUpdates();
});
</script>
