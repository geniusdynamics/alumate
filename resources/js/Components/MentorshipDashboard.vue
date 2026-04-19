<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mentorship Dashboard</h1>
            <p class="mt-2 text-gray-600">Manage your mentorships and upcoming sessions</p>
        </div>

        <!-- Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UserGroupIcon class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Active Mentorships</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.active_mentorships }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CalendarIcon class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Upcoming Sessions</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ upcomingSessions.length }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-6 w-6 text-purple-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Completed Sessions</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.completed_sessions }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <StarIcon class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Average Rating</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.average_session_rating || 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        activeTab === tab.id
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium',
                    ]"
                >
                    {{ tab.name }}
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="rounded-lg bg-white shadow">
            <!-- Upcoming Sessions Tab -->
            <div v-if="activeTab === 'sessions'" class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Upcoming Sessions</h3>
                    <button
                        @click="showScheduler = true"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
                    >
                        <PlusIcon class="mr-2 h-4 w-4" />
                        Schedule Session
                    </button>
                </div>

                <div v-if="upcomingSessions.length === 0" class="py-8 text-center">
                    <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming sessions</h3>
                    <p class="mt-1 text-sm text-gray-500">Schedule a session to get started.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="session in upcomingSessions" :key="session.id" class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <img
                                        :src="getOtherUser(session).avatar_url || '/default-avatar.png'"
                                        :alt="getOtherUser(session).name"
                                        class="h-10 w-10 rounded-full object-cover"
                                    />
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">
                                            {{ getOtherUser(session).name }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            {{ isUserMentor(session) ? 'Mentee' : 'Mentor' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <CalendarIcon class="mr-1 h-4 w-4" />
                                        {{ formatDate(session.scheduled_at) }}
                                    </div>
                                    <div class="flex items-center">
                                        <ClockIcon class="mr-1 h-4 w-4" />
                                        {{ session.duration }} minutes
                                    </div>
                                </div>
                                <p v-if="session.notes" class="mt-2 text-sm text-gray-600">
                                    {{ session.notes }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button @click="editSession(session)" class="text-sm font-medium text-blue-600 hover:text-blue-800">Edit</button>
                                <button @click="cancelSession(session)" class="text-sm font-medium text-red-600 hover:text-red-800">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- As Mentor Tab -->
            <div v-if="activeTab === 'as-mentor'" class="p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Mentorships (As Mentor)</h3>

                <div v-if="mentorships.as_mentor.length === 0" class="py-8 text-center">
                    <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No mentorships yet</h3>
                    <p class="mt-1 text-sm text-gray-500">You haven't accepted any mentees yet.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="mentorship in mentorships.as_mentor" :key="mentorship.id" class="rounded-lg border border-gray-200 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="mentorship.mentee.avatar_url || '/default-avatar.png'"
                                    :alt="mentorship.mentee.name"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">{{ mentorship.mentee.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ mentorship.mentee.title || 'Alumni' }}</p>
                                    <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                        <span>Started {{ formatDate(mentorship.created_at) }}</span>
                                        <span class="capitalize">{{ mentorship.status }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    v-if="mentorship.status === 'pending'"
                                    @click="acceptRequest(mentorship.id)"
                                    class="text-sm font-medium text-green-600 hover:text-green-800"
                                >
                                    Accept
                                </button>
                                <button
                                    v-if="mentorship.status === 'pending'"
                                    @click="declineRequest(mentorship.id)"
                                    class="text-sm font-medium text-red-600 hover:text-red-800"
                                >
                                    Decline
                                </button>
                                <button
                                    v-if="mentorship.status === 'accepted'"
                                    @click="scheduleWithMentorship(mentorship)"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    Schedule Session
                                </button>
                            </div>
                        </div>
                        <p v-if="mentorship.message" class="mt-3 text-sm text-gray-600">"{{ mentorship.message }}"</p>
                        <p v-if="mentorship.goals" class="mt-2 text-sm text-gray-600"><strong>Goals:</strong> {{ mentorship.goals }}</p>
                    </div>
                </div>
            </div>

            <!-- As Mentee Tab -->
            <div v-if="activeTab === 'as-mentee'" class="p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Mentorships (As Mentee)</h3>

                <div v-if="mentorships.as_mentee.length === 0" class="py-8 text-center">
                    <UserIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No mentorships yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Find a mentor to get started.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="mentorship in mentorships.as_mentee" :key="mentorship.id" class="rounded-lg border border-gray-200 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="mentorship.mentor.avatar_url || '/default-avatar.png'"
                                    :alt="mentorship.mentor.name"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">{{ mentorship.mentor.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ mentorship.mentor.title || 'Mentor' }}</p>
                                    <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                        <span>Requested {{ formatDate(mentorship.created_at) }}</span>
                                        <span class="capitalize">{{ mentorship.status }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    v-if="mentorship.status === 'accepted'"
                                    @click="scheduleWithMentorship(mentorship)"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    Schedule Session
                                </button>
                            </div>
                        </div>
                        <p v-if="mentorship.goals" class="mt-3 text-sm text-gray-600"><strong>Goals:</strong> {{ mentorship.goals }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Scheduler Modal -->
        <SessionScheduler
            v-if="showScheduler"
            :mentorships="availableMentorships"
            @close="showScheduler = false"
            @sessionScheduled="onSessionScheduled"
        />
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { CalendarIcon, CheckCircleIcon, ClockIcon, PlusIcon, StarIcon, UserGroupIcon, UserIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import SessionScheduler from './SessionScheduler.vue';

// Reactive data
const activeTab = ref('sessions');
const mentorships = ref({ as_mentor: [], as_mentee: [] });
const upcomingSessions = ref([]);
const stats = ref({});
const showScheduler = ref(false);
const loading = ref(false);

// Computed properties
const tabs = computed(() => [
    { id: 'sessions', name: 'Upcoming Sessions' },
    { id: 'as-mentor', name: 'As Mentor' },
    { id: 'as-mentee', name: 'As Mentee' },
]);

const availableMentorships = computed(() => {
    return [
        ...mentorships.value.as_mentor.filter((m) => m.status === 'accepted'),
        ...mentorships.value.as_mentee.filter((m) => m.status === 'accepted'),
    ];
});

// Methods
const loadData = async () => {
    loading.value = true;
    try {
        const [mentorshipsRes, sessionsRes, analyticsRes] = await Promise.all([
            axios.get('/api/mentorships'),
            axios.get('/api/mentorships/sessions/upcoming'),
            axios.get('/api/mentorships/analytics'),
        ]);

        mentorships.value = mentorshipsRes.data;
        upcomingSessions.value = sessionsRes.data.sessions;
        stats.value = analyticsRes.data.analytics;
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    } finally {
        loading.value = false;
    }
};

const acceptRequest = async (requestId) => {
    try {
        await axios.post(`/api/mentorships/requests/${requestId}/accept`);
        await loadData(); // Refresh data
    } catch (error) {
        console.error('Failed to accept request:', error);
    }
};

const declineRequest = async (requestId) => {
    try {
        await axios.post(`/api/mentorships/requests/${requestId}/decline`);
        await loadData(); // Refresh data
    } catch (error) {
        console.error('Failed to decline request:', error);
    }
};

const scheduleWithMentorship = (mentorship) => {
    showScheduler.value = true;
};

const onSessionScheduled = (session) => {
    upcomingSessions.value.push(session);
    showScheduler.value = false;
};

const getOtherUser = (session) => {
    const currentUserId = window.Laravel?.user?.id;
    return session.mentorship.mentor.id === currentUserId ? session.mentorship.mentee : session.mentorship.mentor;
};

const isUserMentor = (session) => {
    const currentUserId = window.Laravel?.user?.id;
    return session.mentorship.mentor.id === currentUserId;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const editSession = (session) => {
    // TODO: Implement session editing
    logger.log('Edit session:', session);
};

const cancelSession = async (session) => {
    if (confirm('Are you sure you want to cancel this session?')) {
        try {
            // TODO: Implement session cancellation API
            logger.log('Cancel session:', session);
        } catch (error) {
            console.error('Failed to cancel session:', error);
        }
    }
};

// Lifecycle
onMounted(() => {
    loadData();
});
</script>

