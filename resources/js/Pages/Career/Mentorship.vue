<template>
    <AppLayout title="Mentorship">
        <Head title="Mentorship" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mentorship Platform</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with experienced alumni or share your expertise with others</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3 space-y-8">
                    <!-- Mentorship Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Mentorship Status</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- As Mentee -->
                                <div class="text-center p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <UserIcon class="mx-auto h-12 w-12 text-blue-600 mb-4" />
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">As Mentee</h3>
                                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                                        {{ mentorshipRequests.filter(r => r.status === 'accepted').length }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Mentors</p>
                                </div>

                                <!-- As Mentor -->
                                <div class="text-center p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <AcademicCapIcon class="mx-auto h-12 w-12 text-green-600 mb-4" />
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">As Mentor</h3>
                                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                                        {{ mentorProfile ? mentorProfile.active_mentees_count || 0 : 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Mentees</p>
                                    <div class="mt-3">
                                        <button
                                            v-if="!mentorProfile"
                                            @click="showBecomeMentorModal = true"
                                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors"
                                        >
                                            Become a Mentor
                                        </button>
                                        <Link
                                            v-else
                                            :href="route('mentorship.dashboard')"
                                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors"
                                        >
                                            Manage Mentees
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Mentors -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Available Mentors</h2>
                                <Link
                                    :href="route('mentors.directory')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All Mentors
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="availableMentors.length === 0" class="text-center py-8">
                                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No mentors available</h3>
                                <p class="text-gray-500 dark:text-gray-400">Check back later or expand your search criteria</p>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <div v-if="mentorshipRequests.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">My Mentorship Requests</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div 
                                    v-for="request in mentorshipRequests" 
                                    :key="request.id"
                                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <UserIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ request.mentor.user.name }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ request.mentor.expertise_areas?.join(', ') }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                Requested {{ formatTimeAgo(request.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span 
                                            :class="getRequestStatusColor(request.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ formatRequestStatus(request.status) }}
                                        </span>
                                        <button
                                            v-if="request.status === 'accepted'"
                                            @click="viewMentorshipDetails(request)"
                                            class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md transition-colors"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mentorship Sessions -->
                    <div v-if="mentorshipSessions.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Sessions</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div 
                                    v-for="session in mentorshipSessions.slice(0, 3)" 
                                    :key="session.id"
                                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-4">
                                        <CalendarIcon class="w-8 h-8 text-blue-600" />
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                Session with {{ session.mentor.user.name }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatSessionDate(session.scheduled_at) }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ session.duration }} minutes • {{ session.type }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            @click="joinSession(session)"
                                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md transition-colors"
                                        >
                                            Join
                                        </button>
                                        <button
                                            @click="rescheduleSession(session)"
                                            class="text-sm bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded-md transition-colors"
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Stats</h3>
                        </div>
                        <div class="p-6 space-y-4">
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('mentors.directory')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="w-5 h-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <button
                                v-if="!mentorProfile"
                                @click="showBecomeMentorModal = true"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 w-full text-left"
                            >
                                <AcademicCapIcon class="w-5 h-5" />
                                <span>Become a Mentor</span>
                            </button>
                            <Link 
                                :href="route('career.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400"
                            >
                                <ChartBarIcon class="w-5 h-5" />
                                <span>Career Timeline</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Become Mentor Modal -->
        <BecomeMentorModal 
            v-if="showBecomeMentorModal"
            @close="showBecomeMentorModal = false"
            @saved="handleMentorProfileCreated"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import MentorCard from '@/Components/MentorCard.vue'
import BecomeMentorModal from '@/Components/BecomeMentorModal.vue'
import { formatDistanceToNow, format } from 'date-fns'
import {
    UserIcon,
    AcademicCapIcon,
    UserGroupIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mentorshipRequests: Array,
    availableMentors: Array,
    mentorshipSessions: Array,
    mentorProfile: Object,
})

const showBecomeMentorModal = ref(false)

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const formatSessionDate = (timestamp) => {
    return format(new Date(timestamp), 'MMM dd, yyyy at h:mm a')
}

const getRequestStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        declined: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatRequestStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const handleMentorshipRequest = (mentorId) => {
    router.post(route('api.mentorship.request'), {
        mentor_id: mentorId
    }, {
        preserveState: true
    })
}

const viewMentorshipDetails = (request) => {
    router.visit(route('mentorship.details', request.id))
}

const joinSession = (session) => {
    router.visit(route('mentorship.session', session.id))
}

const rescheduleSession = (session) => {
    // This would open a reschedule modal
    console.log('Reschedule session:', session)
}

const handleMentorProfileCreated = () => {
    showBecomeMentorModal.value = false
    router.reload()
}
</script>
