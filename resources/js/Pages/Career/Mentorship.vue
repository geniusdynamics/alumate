<template>
    <AppLayout title="Mentorship">
        <Head title="Mentorship" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mentorship Platform</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with experienced alumni or share your expertise with others</p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- Main Content -->
                <div class="space-y-8 lg:col-span-3">
                    <!-- Mentorship Status -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Mentorship Status</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- As Mentee -->
                                <div class="rounded-lg border border-gray-200 p-6 text-center dark:border-gray-700">
                                    <UserIcon class="mx-auto mb-4 h-12 w-12 text-blue-600" />
                                    <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">As Mentee</h3>
                                    <p class="mb-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ mentorshipRequests.filter((r) => r.status === 'accepted').length }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Mentors</p>
                                </div>

                                <!-- As Mentor -->
                                <div class="rounded-lg border border-gray-200 p-6 text-center dark:border-gray-700">
                                    <AcademicCapIcon class="mx-auto mb-4 h-12 w-12 text-green-600" />
                                    <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">As Mentor</h3>
                                    <p class="mb-2 text-3xl font-bold text-green-600 dark:text-green-400">
                                        {{ mentorProfile ? mentorProfile.active_mentees_count || 0 : 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Mentees</p>
                                    <div class="mt-3">
                                        <button
                                            v-if="!mentorProfile"
                                            @click="showBecomeMentorModal = true"
                                            class="rounded-md bg-green-600 px-4 py-2 text-sm text-white transition-colors hover:bg-green-700"
                                        >
                                            Become a Mentor
                                        </button>
                                        <Link
                                            v-else
                                            :href="route('mentorship.dashboard')"
                                            class="rounded-md bg-green-600 px-4 py-2 text-sm text-white transition-colors hover:bg-green-700"
                                        >
                                            Manage Mentees
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Mentors -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Available Mentors</h2>
                                <Link :href="route('mentors.directory')" class="text-sm text-blue-600 hover:text-blue-500"> View All Mentors </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="availableMentors.length === 0" class="py-8 text-center">
                                <UserGroupIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No mentors available</h3>
                                <p class="text-gray-500 dark:text-gray-400">Check back later or expand your search criteria</p>
                            </div>

                            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <MentorCard
                                    v-for="mentor in availableMentors.slice(0, 4)"
                                    :key="mentor.id"
                                    :mentor="mentor"
                                    @request-mentorship="handleMentorshipRequest"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- My Mentorship Requests -->
                    <div v-if="mentorshipRequests.length > 0" class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">My Mentorship Requests</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="request in mentorshipRequests"
                                    :key="request.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                            <UserIcon class="h-6 w-6 text-gray-600 dark:text-gray-300" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ request.mentor.user.name }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ request.mentor.expertise_areas?.join(', ') }}
                                            </p>
                                            <p class="text-xs text-gray-400">Requested {{ formatTimeAgo(request.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            :class="getRequestStatusColor(request.status)"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        >
                                            {{ formatRequestStatus(request.status) }}
                                        </span>
                                        <button
                                            v-if="request.status === 'accepted'"
                                            @click="viewMentorshipDetails(request)"
                                            class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white transition-colors hover:bg-blue-700"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mentorship Sessions -->
                    <div v-if="mentorshipSessions.length > 0" class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Sessions</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="session in mentorshipSessions.slice(0, 3)"
                                    :key="session.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="flex items-center space-x-4">
                                        <CalendarIcon class="h-8 w-8 text-blue-600" />
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                Session with {{ session.mentor.user.name }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatSessionDate(session.scheduled_at) }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ session.duration }} minutes • {{ session.type }}</p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            @click="joinSession(session)"
                                            class="rounded-md bg-green-600 px-3 py-1 text-sm text-white transition-colors hover:bg-green-700"
                                        >
                                            Join
                                        </button>
                                        <button
                                            @click="rescheduleSession(session)"
                                            class="rounded-md bg-gray-600 px-3 py-1 text-sm text-white transition-colors hover:bg-gray-700"
                                        >
                                            Reschedule
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Stats</h3>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ mentorshipSessions.length }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Total Sessions</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ availableMentors.length }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Available Mentors</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="space-y-3 p-6">
                            <Link
                                :href="route('mentors.directory')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="h-5 w-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <button
                                v-if="!mentorProfile"
                                @click="showBecomeMentorModal = true"
                                class="flex w-full items-center space-x-3 text-left text-sm text-gray-700 hover:text-green-600 dark:text-gray-300 dark:hover:text-green-400"
                            >
                                <AcademicCapIcon class="h-5 w-5" />
                                <span>Become a Mentor</span>
                            </button>
                            <Link
                                :href="route('career.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-purple-600 dark:text-gray-300 dark:hover:text-purple-400"
                            >
                                <ChartBarIcon class="h-5 w-5" />
                                <span>Career Timeline</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Become Mentor Modal -->
        <BecomeMentorModal v-if="showBecomeMentorModal" @close="showBecomeMentorModal = false" @saved="handleMentorProfileCreated" />
    </AppLayout>
</template>

<script setup>
import BecomeMentorModal from '@/Components/BecomeMentorModal.vue';
import MentorCard from '@/Components/MentorCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { AcademicCapIcon, CalendarIcon, ChartBarIcon, MagnifyingGlassIcon, UserGroupIcon, UserIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { format, formatDistanceToNow } from 'date-fns';
import { ref } from 'vue';

const props = defineProps({
    mentorshipRequests: Array,
    availableMentors: Array,
    mentorshipSessions: Array,
    mentorProfile: Object,
});

const showBecomeMentorModal = ref(false);

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
};

const formatSessionDate = (timestamp) => {
    return format(new Date(timestamp), 'MMM dd, yyyy at h:mm a');
};

const getRequestStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        declined: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatRequestStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const handleMentorshipRequest = (mentorId) => {
    router.post(
        route('api.mentorship.request'),
        {
            mentor_id: mentorId,
        },
        {
            preserveState: true,
        },
    );
};

const viewMentorshipDetails = (request) => {
    router.visit(route('mentorship.details', request.id));
};

const joinSession = (session) => {
    router.visit(route('mentorship.session', session.id));
};

const rescheduleSession = (session) => {
    // This would open a reschedule modal
    console.log('Reschedule session:', session);
};

const handleMentorProfileCreated = () => {
    showBecomeMentorModal.value = false;
    router.reload();
};
</script>


