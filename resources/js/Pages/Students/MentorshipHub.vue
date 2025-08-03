<template>
    <AppLayout title="Student Mentorship Hub">
        <Head title="Student Mentorship Hub" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Student Mentorship Hub</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Connect with experienced alumni mentors to guide your academic and career journey
                </p>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                        {{ availableMentors.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Available Mentors</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                        {{ activeMentorships.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Active Mentorships</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                        {{ upcomingSessions.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Upcoming Sessions</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-2">
                        {{ completedSessions }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Sessions Completed</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Recommended Mentors -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recommended Mentors</h2>
                                <Link
                                    :href="route('students.mentors.browse')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    Browse All Mentors
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="recommendedMentors.length === 0" class="text-center py-8">
                                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No mentors found</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Complete your profile to get personalized mentor recommendations</p>
                                <Link
                                    :href="route('profile.edit')"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Complete Profile
                                </Link>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <StudentMentorCard
                                    v-for="mentor in recommendedMentors.slice(0, 4)"
                                    :key="mentor.id"
                                    :mentor="mentor"
                                    @request-mentorship="handleMentorshipRequest"
                                    @schedule-intro="handleScheduleIntro"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Active Mentorships -->
                    <div v-if="activeMentorships.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Active Mentorships</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <ActiveMentorshipCard
                                    v-for="mentorship in activeMentorships"
                                    :key="mentorship.id"
                                    :mentorship="mentorship"
                                    @schedule-session="handleScheduleSession"
                                    @send-message="handleSendMessage"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Sessions -->
                    <div v-if="upcomingSessions.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Sessions</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <UpcomingSessionCard
                                    v-for="session in upcomingSessions"
                                    :key="session.id"
                                    :session="session"
                                    @join-session="handleJoinSession"
                                    @reschedule-session="handleRescheduleSession"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mentorship Goals -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Mentorship Goals</h2>
                                <button
                                    @click="showGoalModal = true"
                                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md transition-colors"
                                >
                                    Add Goal
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="mentorshipGoals.length === 0" class="text-center py-8">
                                <TargetIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No goals set</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Set mentorship goals to track your progress and get better guidance</p>
                                <button
                                    @click="showGoalModal = true"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Set Your First Goal
                                </button>
                            </div>

                            <div v-else class="space-y-4">
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
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Mentorship Progress -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Progress</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Profile Completion</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ profileCompletion }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div 
                                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: profileCompletion + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Goals Progress</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ goalsProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div 
                                            class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: goalsProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
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
                                :href="route('students.mentors.browse')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="w-5 h-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <button
                                @click="showGoalModal = true"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 w-full text-left"
                            >
                                <TargetIcon class="w-5 h-5" />
                                <span>Set Goals</span>
                            </button>
                            <Link 
                                :href="route('students.resources')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400"
                            >
                                <BookOpenIcon class="w-5 h-5" />
                                <span>Learning Resources</span>
                            </Link>
                            <Link 
                                :href="route('students.career-guidance')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-orange-600 dark:hover:text-orange-400"
                            >
                                <BriefcaseIcon class="w-5 h-5" />
                                <span>Career Guidance</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="recentActivity.length === 0" class="text-center py-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="activity in recentActivity"
                                    :key="activity.id"
                                    class="flex items-start space-x-3"
                                >
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900 dark:text-white">{{ activity.description }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatTimeAgo(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goal Modal -->
        <MentorshipGoalModal 
            v-if="showGoalModal"
            @close="showGoalModal = false"
            @saved="handleGoalSaved"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import AppLayout from '@/layouts/AppLayout.vue'
import StudentMentorCard from '@/Components/StudentMentorCard.vue'
import ActiveMentorshipCard from '@/Components/ActiveMentorshipCard.vue'
import UpcomingSessionCard from '@/Components/UpcomingSessionCard.vue'
import MentorshipGoalCard from '@/Components/MentorshipGoalCard.vue'
import MentorshipGoalModal from '@/Components/MentorshipGoalModal.vue'
import {
    UserGroupIcon,
    TargetIcon,
    MagnifyingGlassIcon,
    BookOpenIcon,
    BriefcaseIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    recommendedMentors: Array,
    availableMentors: Array,
    activeMentorships: Array,
    upcomingSessions: Array,
    mentorshipGoals: Array,
    recentActivity: Array,
    completedSessions: Number,
    profileCompletion: Number,
})

const showGoalModal = ref(false)

const goalsProgress = computed(() => {
    if (props.mentorshipGoals.length === 0) return 0
    const completedGoals = props.mentorshipGoals.filter(goal => goal.completed).length
    return Math.round((completedGoals / props.mentorshipGoals.length) * 100)
})

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const handleMentorshipRequest = (mentorId) => {
    router.post(route('api.mentorship.request'), {
        mentor_id: mentorId
    }, {
        preserveState: true
    })
}

const handleScheduleIntro = (mentorId) => {
    router.visit(route('students.mentors.schedule-intro', mentorId))
}

const handleScheduleSession = (mentorshipId) => {
    router.visit(route('students.mentorship.schedule-session', mentorshipId))
}

const handleSendMessage = (mentorshipId) => {
    router.visit(route('students.mentorship.messages', mentorshipId))
}

const handleJoinSession = (sessionId) => {
    router.visit(route('students.mentorship.session', sessionId))
}

const handleRescheduleSession = (sessionId) => {
    router.visit(route('students.mentorship.reschedule', sessionId))
}

const handleUpdateProgress = (goalId, progress) => {
    router.post(route('api.mentorship.goals.update-progress', goalId), {
        progress: progress
    }, {
        preserveState: true
    })
}

const handleCompleteGoal = (goalId) => {
    router.post(route('api.mentorship.goals.complete', goalId), {}, {
        preserveState: true
    })
}

const handleGoalSaved = () => {
    showGoalModal.value = false
    router.reload()
}
</script>
