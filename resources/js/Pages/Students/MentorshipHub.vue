<template>
    <AppLayout title="Student Mentorship Hub">
        <Head title="Student Mentorship Hub" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Student Mentorship Hub</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with experienced alumni mentors to guide your academic and career journey</p>
            </div>

            <!-- Quick Stats -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ availableMentors.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Available Mentors</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ activeMentorships.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Active Mentorships</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ upcomingSessions.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Upcoming Sessions</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ completedSessions }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Sessions Completed</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-8 lg:col-span-2">
                    <!-- Recommended Mentors -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recommended Mentors</h2>
                                <Link :href="route('students.mentors.browse')" class="text-sm text-blue-600 hover:text-blue-500">
                                    Browse All Mentors
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="recommendedMentors.length === 0" class="py-8 text-center">
                                <UserGroupIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No mentors found</h3>
                                <p class="mb-4 text-gray-500 dark:text-gray-400">Complete your profile to get personalized mentor recommendations</p>
                                <Link
                                    :href="route('profile.edit')"
                                    class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                                >
                                    Complete Profile
                                </Link>
                            </div>

                            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                    <div v-if="activeMentorships.length > 0" class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
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
                    <div v-if="upcomingSessions.length > 0" class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
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
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Mentorship Goals</h2>
                                <button
                                    @click="showGoalModal = true"
                                    class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white transition-colors hover:bg-blue-700"
                                >
                                    Add Goal
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="mentorshipGoals.length === 0" class="py-8 text-center">
                                <FlagIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No goals set</h3>
                                <p class="mb-4 text-gray-500 dark:text-gray-400">
                                    Set mentorship goals to track your progress and get better guidance
                                </p>
                                <button
                                    @click="showGoalModal = true"
                                    class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
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
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Progress</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Profile Completion</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ profileCompletion }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                                            :style="{ width: profileCompletion + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Goals Progress</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ goalsProgress }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-2 rounded-full bg-green-600 transition-all duration-300"
                                            :style="{ width: goalsProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
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
                                :href="route('students.mentors.browse')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="h-5 w-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <button
                                @click="showGoalModal = true"
                                class="flex w-full items-center space-x-3 text-left text-sm text-gray-700 hover:text-green-600 dark:text-gray-300 dark:hover:text-green-400"
                            >
                                <FlagIcon class="h-5 w-5" />
                                <span>Set Goals</span>
                            </button>
                            <Link
                                :href="route('students.resources')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-purple-600 dark:text-gray-300 dark:hover:text-purple-400"
                            >
                                <BookOpenIcon class="h-5 w-5" />
                                <span>Learning Resources</span>
                            </Link>
                            <Link
                                :href="route('students.career-guidance')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-orange-600 dark:text-gray-300 dark:hover:text-orange-400"
                            >
                                <BriefcaseIcon class="h-5 w-5" />
                                <span>Career Guidance</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="recentActivity.length === 0" class="py-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start space-x-3">
                                    <div class="mt-2 h-2 w-2 rounded-full bg-blue-500"></div>
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
        <MentorshipGoalModal v-if="showGoalModal" @close="showGoalModal = false" @saved="handleGoalSaved" />
    </AppLayout>
</template>

<script setup lang="ts">
import ActiveMentorshipCard from '@/Components/ActiveMentorshipCard.vue';
import MentorshipGoalCard from '@/Components/MentorshipGoalCard.vue';
import MentorshipGoalModal from '@/Components/MentorshipGoalModal.vue';
import StudentMentorCard from '@/Components/StudentMentorCard.vue';
import UpcomingSessionCard from '@/Components/UpcomingSessionCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BookOpenIcon, BriefcaseIcon, FlagIcon, MagnifyingGlassIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { computed, ref } from 'vue';

const props = defineProps({
    recommendedMentors: Array,
    availableMentors: Array,
    activeMentorships: Array,
    upcomingSessions: Array,
    mentorshipGoals: Array,
    recentActivity: Array,
    completedSessions: Number,
    profileCompletion: Number,
});

const showGoalModal = ref(false);

const goalsProgress = computed(() => {
    if (props.mentorshipGoals.length === 0) return 0;
    const completedGoals = props.mentorshipGoals.filter((goal) => goal.completed).length;
    return Math.round((completedGoals / props.mentorshipGoals.length) * 100);
});

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
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

const handleScheduleIntro = (mentorId) => {
    router.visit(route('students.mentors.schedule-intro', mentorId));
};

const handleScheduleSession = (mentorshipId) => {
    router.visit(route('students.mentorship.schedule-session', mentorshipId));
};

const handleSendMessage = (mentorshipId) => {
    router.visit(route('students.mentorship.messages', mentorshipId));
};

const handleJoinSession = (sessionId) => {
    router.visit(route('students.mentorship.session', sessionId));
};

const handleRescheduleSession = (sessionId) => {
    router.visit(route('students.mentorship.reschedule', sessionId));
};

const handleUpdateProgress = (goalId, progress) => {
    router.post(
        route('api.mentorship.goals.update-progress', goalId),
        {
            progress: progress,
        },
        {
            preserveState: true,
        },
    );
};

const handleCompleteGoal = (goalId) => {
    router.post(
        route('api.mentorship.goals.complete', goalId),
        {},
        {
            preserveState: true,
        },
    );
};

const handleGoalSaved = () => {
    showGoalModal.value = false;
    router.reload();
};
</script>















