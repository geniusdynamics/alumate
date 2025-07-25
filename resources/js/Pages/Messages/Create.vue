<template>
    <AppLayout title="New Message">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Message
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Recipient -->
                            <div class="mb-6">
                                <label for="recipient_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    To
                                </label>
                                <div v-if="recipient" class="flex items-center p-3 bg-gray-50 rounded-md">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ recipient.name }}</div>
                                        <div class="text-sm text-gray-500">{{ recipient.email }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="clearRecipient"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div v-if="searchResults.length > 0" class="mt-2 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                        <div
                                            v-for="user in searchResults"
                                            :key="user.id"
                                            @click="selectRecipient(user)"
                                            class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
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
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subject
                                </label>
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
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Type
                                </label>
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
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message
                                </label>
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
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
import { ref, reactive } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { debounce } from 'lodash'

const props = defineProps({
    recipient: Object,
    jobId: Number,
    applicationId: Number,
})

const form = useForm({
    recipient_id: props.recipient?.id || '',
    subject: '',
    content: '',
    type: 'direct',
    related_job_id: props.jobId || null,
    related_application_id: props.applicationId || null,
})

const recipientSearch = ref('')
const searchResults = ref([])
const recipient = ref(props.recipient)

const searchUsers = debounce(async () => {
    if (recipientSearch.value.length < 2) {
        searchResults.value = []
        return
    }

    try {
        const response = await fetch(`/api/users/search?q=${encodeURIComponent(recipientSearch.value)}`)
        const data = await response.json()
        searchResults.value = data.users || []
    } catch (error) {
        console.error('Error searching users:', error)
        searchResults.value = []
    }
}, 300)

const selectRecipient = (user) => {
    recipient.value = user
    form.recipient_id = user.id
    recipientSearch.value = ''
    searchResults.value = []
}

const clearRecipient = () => {
    recipient.value = null
    form.recipient_id = ''
}

const submit = () => {
    form.post(route('messages.store'))
}
</script>