import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
    GamificationEvent,
    GamificationMetrics,
    LeaderboardEntry,
    UserPoints,
    UserBadge,
    ActivityTimelineEntry,
    GamificationStore
} from '../types/gamification';

export const useGamificationStore = defineStore('gamification', (): GamificationStore => {
    // State
    const metrics = ref<GamificationMetrics | null>(null);
    const leaderboard = ref<LeaderboardEntry[]>([]);
    const userPoints = ref<UserPoints | null>(null);
    const userBadges = ref<UserBadge[]>([]);
    const activityTimeline = ref<ActivityTimelineEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => error.value !== null);
    const topLeaderboardEntry = computed(() => leaderboard.value[0] || null);
    const userEngagementScore = computed(() => {
        if (!userPoints.value || !userBadges.value.length) return 0;

        // Simple engagement calculation based on points and badges
        const pointsScore = Math.min(userPoints.value.totalPoints / 100, 1) * 50;
        const badgesScore = Math.min(userBadges.value.length / 5, 1) * 30;
        const activityScore = Math.min(userPoints.value.totalEvents / 20, 1) * 20;

        return Math.round(pointsScore + badgesScore + activityScore);
    });

    // Actions
    const fetchMetrics = async (dateRange?: string[]): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (dateRange && dateRange.length === 2) {
                params.append('start_date', dateRange[0]);
                params.append('end_date', dateRange[1]);
            }

            const response = await fetch(`/api/analytics/gamification/metrics?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            if (data.success) {
                metrics.value = data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch gamification metrics');
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to fetch gamification metrics:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchLeaderboard = async (limit: number = 10): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/analytics/gamification/leaderboard?limit=${limit}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            if (data.success) {
                leaderboard.value = data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch leaderboard');
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to fetch leaderboard:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchUserPoints = async (userId: string): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            // Note: This would need a backend endpoint to get user-specific points
            // For now, we'll use the general metrics and filter
            const response = await fetch('/api/analytics/gamification/metrics', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            if (data.success) {
                // This is a placeholder - in reality, we'd need user-specific endpoints
                userPoints.value = {
                    totalPoints: Math.floor(Math.random() * 500), // Placeholder
                    totalEvents: Math.floor(Math.random() * 50), // Placeholder
                    lastEarnedAt: new Date().toISOString(),
                };
            } else {
                throw new Error(data.message || 'Failed to fetch user points');
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to fetch user points:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchUserBadges = async (userId: string): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            // Placeholder implementation
            userBadges.value = [
                {
                    badge: 'first_login',
                    earnedAt: new Date(Date.now() - 86400000).toISOString(),
                    details: { points: 10 },
                },
                {
                    badge: 'profile_complete',
                    earnedAt: new Date(Date.now() - 43200000).toISOString(),
                    details: { points: 25 },
                },
            ];
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to fetch user badges:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchActivityTimeline = async (period: string = 'daily', days: number = 30): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            // Placeholder implementation
            const timeline: ActivityTimelineEntry[] = [];
            const now = new Date();

            for (let i = days - 1; i >= 0; i--) {
                const date = new Date(now.getTime() - i * 24 * 60 * 60 * 1000);
                timeline.push({
                    period: date.toISOString().split('T')[0],
                    totalEvents: Math.floor(Math.random() * 50) + 10,
                    uniqueUsers: Math.floor(Math.random() * 20) + 5,
                    pointsAwarded: Math.floor(Math.random() * 200) + 50,
                });
            }

            activityTimeline.value = timeline;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to fetch activity timeline:', err);
        } finally {
            loading.value = false;
        }
    };

    const trackEvent = async (event: Partial<GamificationEvent>): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/analytics/gamification/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(event),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data.success || false;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Failed to track gamification event:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const clearError = (): void => {
        error.value = null;
    };

    return {
        // State
        metrics,
        leaderboard,
        userPoints,
        userBadges,
        activityTimeline,
        loading,
        error,

        // Getters
        isLoading,
        hasError,
        topLeaderboardEntry,
        userEngagementScore,

        // Actions
        fetchMetrics,
        fetchLeaderboard,
        fetchUserPoints,
        fetchUserBadges,
        fetchActivityTimeline,
        trackEvent,
        clearError,
    };
});