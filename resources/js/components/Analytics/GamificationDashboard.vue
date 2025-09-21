<template>
    <div class="gamification-dashboard">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Gamification Analytics</h2>
            <p class="text-gray-600 mt-1">Track user engagement through points, badges, and leaderboards</p>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading gamification data...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="hasError" class="rounded-lg border border-red-200 bg-red-50 p-4 mb-6">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
            <button @click="clearError" class="mt-2 text-sm text-red-600 hover:text-red-800">Dismiss</button>
        </div>

        <!-- Metrics Overview -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Events</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ metrics?.totalEvents || 0 }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ metrics?.uniqueUsers || 0 }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Points Awarded</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ metrics?.totalPointsAwarded || 0 }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Badges Earned</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ metrics?.totalBadgesEarned || 0 }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard and Activity Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Leaderboard -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Leaderboard</h3>
                    <p class="text-sm text-gray-600">Top performers by points</p>
                </div>
                <div class="p-6">
                    <div v-if="leaderboard.length === 0" class="text-center text-gray-500 py-8">
                        No leaderboard data available
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="entry in leaderboard" :key="entry.userId"
                             class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-medium">
                                    {{ entry.rank }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">User {{ entry.userId }}</p>
                                    <p class="text-xs text-gray-500">{{ entry.totalEvents }} events</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">{{ entry.totalPoints }} pts</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Activity Timeline</h3>
                    <p class="text-sm text-gray-600">Gamification events over time</p>
                </div>
                <div class="p-6">
                    <div v-if="activityTimeline.length === 0" class="text-center text-gray-500 py-8">
                        No activity data available
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="day in activityTimeline.slice(-7)" :key="day.period"
                             class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ formatDate(day.period) }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-xs text-gray-500">{{ day.totalEvents }} events</span>
                                <span class="text-xs text-gray-500">{{ day.pointsAwarded }} pts</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Screen Reader Description -->
        <div class="sr-only" id="gamification-description">
            Gamification analytics dashboard showing user engagement metrics, leaderboard rankings, and activity timeline.
            Data includes total events, active users, points awarded, and badges earned.
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useGamificationStore } from '../../stores/gamification';

const gamificationStore = useGamificationStore();

// Computed properties
const isLoading = computed(() => gamificationStore.isLoading);
const hasError = computed(() => gamificationStore.hasError);
const error = computed(() => gamificationStore.error);
const metrics = computed(() => gamificationStore.metrics);
const leaderboard = computed(() => gamificationStore.leaderboard);
const activityTimeline = computed(() => gamificationStore.activityTimeline);

// Methods
const clearError = () => {
    gamificationStore.clearError();
};

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString();
};

// Lifecycle
onMounted(async () => {
    await Promise.all([
        gamificationStore.fetchMetrics(),
        gamificationStore.fetchLeaderboard(),
        gamificationStore.fetchActivityTimeline(),
    ]);
});
</script>

<style scoped>
.gamification-dashboard {
    @apply w-full max-w-7xl mx-auto p-6;
}
</style>