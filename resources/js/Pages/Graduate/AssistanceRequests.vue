<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const showNewRequestModal = ref(false);

const form = useForm({
    category: '',
    subject: '',
    description: '',
    priority: 'medium',
});

const submitRequest = () => {
    form.post(route('graduate.assistance.submit'), {
        onSuccess: () => {
            showNewRequestModal.value = false;
            form.reset();
        }
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'resolved': 'bg-green-100 text-green-800',
        'closed': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        'low': 'bg-green-100 text-green-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
};

const getCategoryIcon = (category) => {
    const icons = {
        'career': 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5',
        'academic': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'technical': 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'personal': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    };
    return icons[category] || icons['personal'];
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>
    <Head title="Assistance Requests" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Assistance Requests
                </h2>
                <button @click="showNewRequestModal = true" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                    New Request
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Help Categories -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">How Can We Help You?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition-colors cursor-pointer"
                                 @click="form.category = 'career'; showNewRequestModal = true">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Career Guidance</p>
                                        <p class="text-xs text-gray-500">Job search, interviews, career planning</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition-colors cursor-pointer"
                                 @click="form.category = 'academic'; showNewRequestModal = true">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Academic Support</p>
                                        <p class="text-xs text-gray-500">Transcripts, certificates, records</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition-colors cursor-pointer"
                                 @click="form.category = 'technical'; showNewRequestModal = true">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Technical Issues</p>
                                        <p class="text-xs text-gray-500">Platform problems, account issues</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition-colors cursor-pointer"
                                 @click="form.category = 'personal'; showNewRequestModal = true">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Personal Support</p>
                                        <p class="text-xs text-gray-500">General inquiries, other concerns</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requests List -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Your Requests</h3>
                        
                        <div v-if="requests.data.length > 0" class="space-y-4">
                            <div v-for="request in requests.data" :key="request.id" 
                                 class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getCategoryIcon(request.category)" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900">{{ request.subject }}</h4>
                                                <p class="text-xs text-gray-500">{{ request.category?.replace('_', ' ').toUpperCase() }}</p>
                                            </div>
                                        </div>
                                        
                                        <p class="text-sm text-gray-700 mb-3">{{ request.description }}</p>
                                        
                                        <div class="flex items-center gap-2">
                                            <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(request.status)]">
                                                {{ request.status?.replace('_', ' ').toUpperCase() }}
                                            </span>
                                            <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getPriorityBadgeClass(request.priority)]">
                                                {{ request.priority?.toUpperCase() }} PRIORITY
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right text-sm text-gray-500">
                                        <p>{{ formatDate(request.created_at) }}</p>
                                        <p v-if="request.updated_at !== request.created_at" class="text-xs">
                                            Updated {{ formatDate(request.updated_at) }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Response -->
                                <div v-if="request.response" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-900">Institution Response</p>
                                            <p class="text-sm text-blue-700 mt-1">{{ request.response }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-8">
                            <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 mb-2">No assistance requests yet</p>
                            <p class="text-sm text-gray-400 mb-4">Need help? Submit a request and our team will assist you</p>
                            <button @click="showNewRequestModal = true" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Submit Request
                            </button>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Frequently Asked Questions</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>How do I update my employment status?</li>
                                    <li>How can I get a copy of my transcript?</li>
                                    <li>What should I do if I forgot my password?</li>
                                    <li>How do I make my profile visible to employers?</li>
                                    <li>How can I withdraw a job application?</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Request Modal -->
        <div v-if="showNewRequestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showNewRequestModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
                <form @submit.prevent="submitRequest">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Submit Assistance Request</h3>
                        
                        <div class="mb-4">
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select id="category" v-model="form.category" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a category...</option>
                                <option value="career">Career Guidance</option>
                                <option value="academic">Academic Support</option>
                                <option value="technical">Technical Issues</option>
                                <option value="personal">Personal Support</option>
                            </select>
                            <div v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                            <input id="subject" type="text" v-model="form.subject" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Brief description of your request..." />
                            <div v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                            <textarea id="description" v-model="form.description" rows="4" required
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Please provide detailed information about your request..."></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                            <select id="priority" v-model="form.priority"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <div v-if="form.errors.priority" class="mt-1 text-sm text-red-600">{{ form.errors.priority }}</div>
                        </div>
                        
                        <div class="flex gap-4">
                            <button type="button" @click="showNewRequestModal = false" 
                                    class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing"
                                    class="flex-1 px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>