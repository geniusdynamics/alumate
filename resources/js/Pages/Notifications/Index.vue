<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    notifications: Array,
    unreadCount: Number,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getNotificationIcon = (type) => {
    const icons = {
        job_match: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5',
        application_status: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        interview_reminder:
            'M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V7a2 2 0 012-2h4a2 2 0 012 2v0M9 11h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        job_deadline: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        system_updates: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        employer_contact:
            'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    };

    // Extract notification type from class name
    const typeMatch = type.match(/([^\\]+)Notification$/);
    const notificationType = typeMatch ? typeMatch[1].toLowerCase().replace(/([a-z])([A-Z])/g, '$1_$2') : 'default';

    return icons[notificationType] || icons['system_updates'];
};

const getNotificationColor = (type) => {
    const colors = {
        job_match: 'bg-blue-100 text-blue-600',
        application_status: 'bg-green-100 text-green-600',
        interview_reminder: 'bg-purple-100 text-purple-600',
        job_deadline: 'bg-yellow-100 text-yellow-600',
        system_updates: 'bg-gray-100 text-gray-600',
        employer_contact: 'bg-indigo-100 text-indigo-600',
    };

    const typeMatch = type.match(/([^\\]+)Notification$/);
    const notificationType = typeMatch ? typeMatch[1].toLowerCase().replace(/([a-z])([A-Z])/g, '$1_$2') : 'default';

    return colors[notificationType] || colors['system_updates'];
};

const getNotificationTitle = (notification) => {
    const data = JSON.parse(notification.data);
    const type = notification.type;

    if (type.includes('JobMatch')) {
        return `New Job Match: ${data.job_title}`;
    } else if (type.includes('ApplicationStatus')) {
        return `Application Status Updated`;
    } else if (type.includes('InterviewReminder')) {
        return `Interview Reminder`;
    } else if (type.includes('JobDeadline')) {
        return `Application Deadline Reminder`;
    } else if (type.includes('SystemUpdates')) {
        return data.update_title || 'System Update';
    } else if (type.includes('EmployerContact')) {
        return `Message from ${data.employer_name}`;
    }

    return 'Notification';
};

const getNotificationMessage = (notification) => {
    const data = JSON.parse(notification.data);
    const type = notification.type;

    if (type.includes('JobMatch')) {
        return `${data.job_title} at ${data.company_name}`;
    } else if (type.includes('ApplicationStatus')) {
        return `${data.job_title} - Status: ${data.new_status}`;
    } else if (type.includes('InterviewReminder')) {
        return `${data.job_title} at ${data.company_name} on ${data.interview_datetime}`;
    } else if (type.includes('JobDeadline')) {
        return `${data.job_title} - ${data.days_left} days left to apply`;
    } else if (type.includes('SystemUpdates')) {
        return data.update_message || 'System update available';
    } else if (type.includes('EmployerContact')) {
        return data.contact_message;
    }

    return 'You have a new notification';
};

const markAsRead = (notification) => {
    if (!notification.read_at) {
        router.post(
            route('notifications.mark-read', notification.id),
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    notification.read_at = new Date().toISOString();
                },
            },
        );
    }
};

const markAllAsRead = () => {
    router.post(
        route('notifications.mark-all-read'),
        {},
        {
            preserveState: true,
            onSuccess: () => {
                props.notifications.forEach((notification) => {
                    if (!notification.read_at) {
                        notification.read_at = new Date().toISOString();
                    }
                });
            },
        },
    );
};

const getNotificationAction = (notification) => {
    const data = JSON.parse(notification.data);
    const type = notification.type;

    if (type.includes('JobMatch') && data.job_url) {
        return { text: 'View Job', url: data.job_url };
    } else if (type.includes('ApplicationStatus') && data.application_url) {
        return { text: 'View Application', url: data.application_url };
    } else if (type.includes('InterviewReminder') && data.application_url) {
        return { text: 'View Details', url: data.application_url };
    } else if (type.includes('JobDeadline') && data.job_url) {
        return { text: 'Apply Now', url: data.job_url };
    } else if (type.includes('SystemUpdates') && data.dashboard_url) {
        return { text: 'Go to Dashboard', url: data.dashboard_url };
    } else if (type.includes('EmployerContact') && data.employer_url) {
        return { text: 'View Employer', url: data.employer_url };
    }

    return null;
};
</script>

<template>
    <Head title="Notifications" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifications</h2>
                    <p v-if="unreadCount > 0" class="mt-1 text-sm text-gray-600">
                        You have {{ unreadCount }} unread notification{{ unreadCount !== 1 ? 's' : '' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                    >
                        Mark All Read
                    </button>
                    <Link
                        :href="route('notifications.preferences')"
                        class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400"
                    >
                        Preferences
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            :class="['cursor-pointer p-6 transition-colors hover:bg-gray-50', !notification.read_at ? 'bg-blue-50' : '']"
                            @click="markAsRead(notification)"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', getNotificationColor(notification.type)]">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                :d="getNotificationIcon(notification.type)"
                                            />
                                        </svg>
                                    </div>
                                </div>

                                <div class="ml-4 flex-1">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h3 class="text-sm font-medium text-gray-900">
                                                    {{ getNotificationTitle(notification) }}
                                                </h3>
                                                <div v-if="!notification.read_at" class="ml-2 h-2 w-2 rounded-full bg-blue-600"></div>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ getNotificationMessage(notification) }}
                                            </p>
                                            <p class="mt-2 text-xs text-gray-500">
                                                {{ formatDate(notification.created_at) }} at {{ formatTime(notification.created_at) }}
                                            </p>
                                        </div>

                                        <div v-if="getNotificationAction(notification)" class="ml-4">
                                            <Link
                                                :href="getNotificationAction(notification).url"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                            >
                                                {{ getNotificationAction(notification).text }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="notifications.length === 0" class="p-12 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2zM4 7h8V5H4v2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-medium text-gray-900">No notifications yet</h3>
                        <p class="mb-4 text-gray-600">You'll receive notifications here when there are updates about jobs, applications, and more.</p>
                        <Link
                            :href="route('notifications.preferences')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Configure Preferences
                        </Link>
                    </div>
                </div>

                <!-- Notification Tips -->
                <div class="mt-6 rounded-md border border-blue-200 bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Notification Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-inside list-disc space-y-1">
                                    <li>Click on notifications to mark them as read</li>
                                    <li>Use "Mark All Read" to clear all unread notifications</li>
                                    <li>Customize your notification preferences to control what you receive</li>
                                    <li>Important notifications like interview reminders are sent via multiple channels</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
