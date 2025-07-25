<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    preferences: Object,
});

const form = useForm({
    preferences: Object.values(props.preferences),
});

const submit = () => {
    form.put(route('notifications.preferences.update'));
};

const getNotificationTypeLabel = (type) => {
    const labels = {
        'job_match': 'Job Matches',
        'application_status': 'Application Status Updates',
        'interview_reminder': 'Interview Reminders',
        'job_deadline': 'Application Deadlines',
        'system_updates': 'System Updates',
        'employer_contact': 'Employer Messages',
    };
    return labels[type] || type;
};

const getNotificationTypeDescription = (type) => {
    const descriptions = {
        'job_match': 'Get notified when new jobs match your profile and preferences',
        'application_status': 'Receive updates when your application status changes',
        'interview_reminder': 'Get reminders about upcoming interviews',
        'job_deadline': 'Be reminded about approaching application deadlines',
        'system_updates': 'Receive important system announcements and updates',
        'employer_contact': 'Get notified when employers send you messages',
    };
    return descriptions[type] || '';
};

const toggleAll = (type, enabled) => {
    const preference = form.preferences.find(p => p.notification_type === type);
    if (preference) {
        preference.email_enabled = enabled;
        preference.sms_enabled = enabled;
        preference.in_app_enabled = enabled;
        preference.push_enabled = enabled;
    }
};
</script>

<template>
    <Head title="Notification Preferences" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Notification Preferences
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="mb-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Manage Your Notifications</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Choose how you want to receive different types of notifications. You can customize settings for each notification type.
                            </p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="space-y-8">
                                <div v-for="preference in form.preferences" :key="preference.notification_type" 
                                     class="border-b border-gray-200 pb-8 last:border-b-0">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h4 class="text-md font-medium text-gray-900">
                                                {{ getNotificationTypeLabel(preference.notification_type) }}
                                            </h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ getNotificationTypeDescription(preference.notification_type) }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <button type="button" 
                                                    @click="toggleAll(preference.notification_type, true)"
                                                    class="text-sm text-indigo-600 hover:text-indigo-500">
                                                Enable All
                                            </button>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" 
                                                    @click="toggleAll(preference.notification_type, false)"
                                                    class="text-sm text-gray-600 hover:text-gray-500">
                                                Disable All
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <!-- Email -->
                                        <div class="flex items-center">
                                            <input :id="`${preference.notification_type}_email`" 
                                                   type="checkbox" 
                                                   v-model="preference.email_enabled"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                            <label :for="`${preference.notification_type}_email`" 
                                                   class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm text-gray-700">Email</span>
                                            </label>
                                        </div>
                                        
                                        <!-- SMS -->
                                        <div class="flex items-center">
                                            <input :id="`${preference.notification_type}_sms`" 
                                                   type="checkbox" 
                                                   v-model="preference.sms_enabled"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                            <label :for="`${preference.notification_type}_sms`" 
                                                   class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm text-gray-700">SMS</span>
                                            </label>
                                        </div>
                                        
                                        <!-- In-App -->
                                        <div class="flex items-center">
                                            <input :id="`${preference.notification_type}_in_app`" 
                                                   type="checkbox" 
                                                   v-model="preference.in_app_enabled"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                            <label :for="`${preference.notification_type}_in_app`" 
                                                   class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2zM4 7h8V5H4v2z" />
                                                </svg>
                                                <span class="text-sm text-gray-700">In-App</span>
                                            </label>
                                        </div>
                                        
                                        <!-- Push -->
                                        <div class="flex items-center">
                                            <input :id="`${preference.notification_type}_push`" 
                                                   type="checkbox" 
                                                   v-model="preference.push_enabled"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                            <label :for="`${preference.notification_type}_push`" 
                                                   class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2zM4 7h8V5H4v2z" />
                                                </svg>
                                                <span class="text-sm text-gray-700">Push</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" 
                                        :disabled="form.processing"
                                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md">
                                    {{ form.processing ? 'Saving...' : 'Save Preferences' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Information Cards -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">About Notifications</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Email notifications are sent to your registered email address</li>
                                        <li>SMS notifications require a valid phone number in your profile</li>
                                        <li>In-app notifications appear when you're using the platform</li>
                                        <li>Push notifications work on supported browsers and devices</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Recommendations</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Keep job match notifications enabled to not miss opportunities</li>
                                        <li>Enable application status updates to track your progress</li>
                                        <li>Interview reminders help you stay prepared</li>
                                        <li>You can always change these settings later</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>