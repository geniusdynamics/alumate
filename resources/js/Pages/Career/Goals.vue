<template>
    <AppLayout title="Career Goals">
        <Head title="Career Goals" />

        <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Career Goals</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Set, track, and achieve your career objectives</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3 space-y-8">
                    <!-- Add Goal Button -->
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Your Goals</h2>
                        <button 
                            @click="showAddGoalModal = true"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Add New Goal
                        </button>
                    </div>

                    <!-- Active Goals -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Goals</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="activeGoals.length === 0" class="text-center py-12">
                                <TargetIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No active goals</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Set your first career goal to start tracking your progress</p>
                                <button 
                                    @click="showAddGoalModal = true"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Set Your First Goal
                                </button>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <MilestoneCard
                                    v-for="goal in activeGoals"
                                    :key="goal.id"
                                    :milestone="goal"
                                    :can-edit="true"
                                    :can-delete="true"
                                    @edit="editGoal"
                                    @delete="deleteGoal"
                                    @update-progress="updateProgress"
                                    @mark-complete="markComplete"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Achieved Goals -->
                    <div v-if="achievedGoals.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Achieved Goals</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <MilestoneCard
                                    v-for="goal in achievedGoals"
                                    :key="goal.id"
                                    :milestone="goal"
                                    :can-edit="false"
                                    :can-delete="false"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Goal Suggestions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Goal Suggestions</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div 
                                    v-for="suggestion in goalSuggestions" 
                                    :key="suggestion.title"
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                    @click="createGoalFromSuggestion(suggestion)"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                        {{ suggestion.title }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ suggestion.description }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-500">
                                            {{ suggestion.category.replace('_', ' ').toUpperCase() }}
                                        </span>
                                        <span class="text-xs text-blue-600 dark:text-blue-400">
                                            {{ suggestion.timeline }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Overview -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Progress Overview</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ achievedGoals.length }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Goals Achieved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                        {{ activeGoals.length }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Active Goals</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                        {{ getAverageProgress() }}%
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Average Progress</div>
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
                                :href="route('career.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <BriefcaseIcon class="w-5 h-5" />
                                <span>Career Timeline</span>
                            </Link>
                            <Link 
                                :href="route('jobs.dashboard')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="w-5 h-5" />
                                <span>Job Dashboard</span>
                            </Link>
                            <Link 
                                :href="route('career.mentorship')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="w-5 h-5" />
                                <span>Find Mentors</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Goal Modal -->
        <GoalModal 
            v-if="showAddGoalModal || editingGoal"
            :goal="editingGoal"
            @close="closeGoalModal"
            @saved="handleGoalSaved"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import MilestoneCard from '@/Components/MilestoneCard.vue'
import GoalModal from '@/Components/GoalModal.vue'
import {
    TargetIcon,
    BriefcaseIcon,
    MagnifyingGlassIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activeGoals: Array,
    achievedGoals: Array,
    goalSuggestions: Array,
})

const showAddGoalModal = ref(false)
const editingGoal = ref(null)

const getAverageProgress = () => {
    if (props.activeGoals.length === 0) return 0
    const totalProgress = props.activeGoals.reduce((sum, goal) => sum + (goal.progress || 0), 0)
    return Math.round(totalProgress / props.activeGoals.length)
}

const createGoalFromSuggestion = (suggestion) => {
    editingGoal.value = {
        title: suggestion.title,
        description: suggestion.description,
        category: suggestion.category,
        timeline: suggestion.timeline
    }
    showAddGoalModal.value = true
}

const editGoal = (goal) => {
    editingGoal.value = goal
}

const deleteGoal = (goal) => {
    if (confirm('Are you sure you want to delete this goal?')) {
        router.delete(route('api.career.goals.destroy', goal.id), {
            preserveState: true
        })
    }
}

const updateProgress = (goal) => {
    // This would open a progress update modal
    console.log('Update progress for goal:', goal)
}

const markComplete = (goal) => {
    router.post(route('api.career.goals.complete', goal.id), {}, {
        preserveState: true
    })
}

const closeGoalModal = () => {
    showAddGoalModal.value = false
    editingGoal.value = null
}

const handleGoalSaved = () => {
    closeGoalModal()
    router.reload()
}
</script>
