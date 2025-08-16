<template>
    <AppLayout title="Mentorship Hub">
        <Head title="Mentorship Hub" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mentorship Hub</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with mentors and advance your career through meaningful relationships</p>
                    </div>
                    <div class="flex space-x-3">
                        <BecomeMentorModal />
                        <Link 
                            :href="route('career.mentorship')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            My Mentorships
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Mentorship Dashboard -->
                    <div class="mb-8">
                        <MentorshipDashboard 
                            :active-mentorships="activeMentorships"
                            :pending-requests="pendingRequests"
                            :upcoming-sessions="upcomingSessions"
                            @schedule-session="handleScheduleSession"
                            @accept-request="handleAcceptRequest"
                            @decline-request="handleDeclineRequest"
                        />
                    </div>

                    <!-- Mentor Directory -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Find Mentors</h2>
                                <div class="flex items-center space-x-2">
                                    <select
                                        v-model="filters.expertise"
                                        @change="applyFilters"
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">All Expertise</option>
                                        <option v-for="expertise in expertiseAreas" :key="expertise" :value="expertise">
                                            {{ expertise }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="mentors.length === 0" class="text-center py-12">
                                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No mentors found</h3>
                                <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or check back later</p>
                            </div>
                            <div v-else>
                                <MentorDirectory 
                                    :mentors="mentors"
                                    @request-mentorship="handleRequestMentorship"
                                    @view-profile="handleViewProfile"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Mentorship Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Mentorship</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Active Mentorships</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentorshipStats.active_mentorships }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Sessions Completed</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentorshipStats.completed_sessions }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Goals Achieved</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentorshipStats.goals_achieved }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Sessions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Sessions</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="upcomingSessions.length === 0" class="text-center py-4">
                                <CalendarIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming sessions</p>
                            </div>
                            <div v-else class="space-y-3">
                                <UpcomingSessionCard
                                    v-for="session in upcomingSessions"
                                    :key="session.id"
                                    :session="session"
                                    @join-session="handleJoinSession"
                                    @reschedule="handleReschedule"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mentorship Goals -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mentorship Goals</h3>
                                <button
                                    @click="showGoalModal = true"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    Add Goal
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="mentorshipGoals.length === 0" class="text-center py-4">
                                <FlagIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No goals set yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <MentorshipGoalCard
                                    v-for="goal in mentorshipGoals"
                                    :key="goal.id"
                                    :goal="goal"
                                    @update-progress="handleUpdateProgress"
                                    @complete-goal="handleCompleteGoal"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Learning Resources -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Learning Resources</h3>
                        </div>
                        <div class="p-6">
                            <LearningResources :resources="learningResources" />
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <button
                                @click="showRequestModal = true"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 w-full text-left"
                            >
                                <UserPlusIcon class="w-5 h-5" />
                                <span>Request Mentorship</span>
                            </button>
                            <Link 
                                :href="route('career.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <ChartBarIcon class="w-5 h-5" />
                                <span>Career Timeline</span>
                            </Link>
                            <Link 
                                :href="route('jobs.dashboard')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <BriefcaseIcon class="w-5 h-5" />
                                <span>Job Dashboard</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <MentorshipRequestModal 
            v-if="showRequestModal"
            :mentor="selectedMentor"
            @close="showRequestModal = false"
            @request-sent="handleRequestSent"
        />

        <MentorshipGoalModal 
            v-if="showGoalModal"
            @close="showGoalModal = false"
            @goal-created="handleGoalCreated"
        />

        <SessionScheduler 
            v-if="showScheduler"
            :mentorship="selectedMentorship"
            @close="showScheduler = false"
            @session-scheduled="handleSessionScheduled"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import MentorshipDashboard from '@/components/MentorshipDashboard.vue'
import MentorDirectory from '@/components/MentorDirectory.vue'
import BecomeMentorModal from '@/components/BecomeMentorModal.vue'
import MentorshipRequestModal from '@/components/MentorshipRequestModal.vue'
import MentorshipGoalModal from '@/components/MentorshipGoalModal.vue'
import MentorshipGoalCard from '@/components/MentorshipGoalCard.vue'
import UpcomingSessionCard from '@/components/UpcomingSessionCard.vue'
import SessionScheduler from '@/components/SessionScheduler.vue'
import LearningResources from '@/components/LearningResources.vue'
import {
    UserGroupIcon,
    CalendarIcon,
    FlagIcon,
    UserPlusIcon,
    ChartBarIcon,
    BriefcaseIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mentors: Array,
    activeMentorships: Array,
    pendingRequests: Array,
    upcomingSessions: Array,
    mentorshipGoals: Array,
    learningResources: Array,
    expertiseAreas: Array,
    mentorshipStats: Object,
    currentFilters: Object,
})

const filters = reactive({
    expertise: props.currentFilters?.expertise || '',
    location: props.currentFilters?.location || '',
    availability: props.currentFilters?.availability || '',
})

const showRequestModal = ref(false)
const showGoalModal = ref(false)
const showScheduler = ref(false)
const selectedMentor = ref(null)
const selectedMentorship = ref(null)

const applyFilters = () => {
    router.get(route('career.mentorship'), filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const handleRequestMentorship = (mentor) => {
    selectedMentor.value = mentor
    showRequestModal.value = true
}

const handleViewProfile = (mentor) => {
    // Navigate to mentor profile or show detailed modal
    console.log('View mentor profile:', mentor)
}

const handleScheduleSession = (mentorship) => {
    selectedMentorship.value = mentorship
    showScheduler.value = true
}

const handleAcceptRequest = (requestId) => {
    router.post(route('api.mentorship.accept', requestId), {}, {
        preserveState: true,
        onSuccess: () => {
            // Update UI or show success message
        }
    })
}

const handleDeclineRequest = (requestId) => {
    router.post(route('api.mentorship.decline', requestId), {}, {
        preserveState: true,
    })
}

const handleJoinSession = (sessionId) => {
    // Handle joining virtual session
    console.log('Join session:', sessionId)
}

const handleReschedule = (sessionId) => {
    // Handle rescheduling session
    console.log('Reschedule session:', sessionId)
}

const handleUpdateProgress = (goalId, progress) => {
    router.patch(route('api.mentorship.goals.update', goalId), {
        progress: progress
    }, {
        preserveState: true,
    })
}

const handleCompleteGoal = (goalId) => {
    router.post(route('api.mentorship.goals.complete', goalId), {}, {
        preserveState: true,
    })
}

const handleRequestSent = () => {
    showRequestModal.value = false
    selectedMentor.value = null
    // Show success message or update UI
}

const handleGoalCreated = () => {
    showGoalModal.value = false
    // Refresh goals or update UI
    router.reload({ only: ['mentorshipGoals'] })
}

const handleSessionScheduled = () => {
    showScheduler.value = false
    selectedMentorship.value = null
    // Refresh sessions or update UI
    router.reload({ only: ['upcomingSessions'] })
}
</script>