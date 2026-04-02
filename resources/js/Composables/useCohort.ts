import { computed, ref } from 'vue';
import { useCohortStore } from '../Stores/useCohortStore';
import type { CohortData } from '../Types/analytics';

/**
 * Composable for cohort analysis functionality
 * Provides hooks for analysis, computed retention, filters, and WebSocket subscriptions
 */
export function useCohort() {
    const cohortStore = useCohortStore();

    // Reactive state for real-time updates
    const realTimeUpdates = ref(false);
    const lastUpdate = ref<Date | null>(null);

    // Computed properties for analysis
    const retentionRates = computed(() => {
        if (!cohortStore.cohortData) return {};

        const data = cohortStore.cohortData;
        return {
            day7: data.metrics?.retention?.day7 || 0,
            day30: data.metrics?.retention?.day30 || 0,
            day90: data.metrics?.retention?.day90 || 0,
            day180: data.metrics?.retention?.day180 || 0,
        };
    });

    const churnRates = computed(() => {
        if (!cohortStore.cohortData) return {};

        const data = cohortStore.cohortData;
        return {
            day7: data.metrics?.churn_rate?.day7 || 0,
            day30: data.metrics?.churn_rate?.day30 || 0,
            day90: data.metrics?.churn_rate?.day90 || 0,
            day180: data.metrics?.churn_rate?.day180 || 0,
        };
    });

    const engagementMetrics = computed(() => {
        if (!cohortStore.cohortData) return {};

        const data = cohortStore.cohortData;
        return {
            score: data.metrics?.engagement?.score || 0,
            sessionsPerWeek: data.metrics?.engagement?.sessionsPerWeek || 0,
            pagesPerSession: data.metrics?.engagement?.pagesPerSession || 0,
            activeDaysPerWeek: data.metrics?.engagement?.activeDaysPerWeek || 0,
        };
    });

    // Filters and sorting
    const filteredCohorts = computed(() => {
        let cohorts = [...cohortStore.availableCohorts];

        // Apply filters based on store filters
        const filters = cohortStore.filters;

        if (filters.dateRange?.from || filters.dateRange?.to) {
            cohorts = cohorts.filter(cohort => {
                const cohortDate = new Date(cohort.created_at);
                const fromDate = filters.dateRange?.from ? new Date(filters.dateRange.from) : null;
                const toDate = filters.dateRange?.to ? new Date(filters.dateRange.to) : null;

                if (fromDate && cohortDate < fromDate) return false;
                if (toDate && cohortDate > toDate) return false;

                return true;
            });
        }

        // Apply segment filters if any
        if (filters.metrics && filters.metrics.length > 0) {
            cohorts = cohorts.filter(cohort => {
                return filters.metrics!.some(metric => {
                    switch (metric) {
                        case 'retention':
                            return (cohort.metrics?.retention?.day30 || 0) > 0;
                        case 'engagement':
                            return (cohort.metrics?.engagement?.score || 0) > 0;
                        case 'conversion':
                            return (cohort.metrics?.conversion?.rate || 0) > 0;
                        default:
                            return true;
                    }
                });
            });
        }

        return cohorts;
    });

    const sortedCohorts = computed(() => {
        return [...filteredCohorts.value].sort((a, b) => {
            // Default sort by creation date (newest first)
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        });
    });

    // Analysis helpers
    const getRetentionTrend = (cohort: CohortData) => {
        const retention = cohort.metrics?.retention;
        if (!retention) return [];

        return [
            { day: 7, rate: retention.day7 || 0 },
            { day: 30, rate: retention.day30 || 0 },
            { day: 90, rate: retention.day90 || 0 },
            { day: 180, rate: retention.day180 || 0 },
        ];
    };

    const getEngagementScore = (cohort: CohortData) => {
        return cohort.metrics?.engagement?.score || 0;
    };

    const getChurnRate = (cohort: CohortData) => {
        return cohort.metrics?.churn_rate?.day30 || 0;
    };

    // WebSocket subscription for real-time updates
    const subscribeToUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.private(`cohort-updates`)
                .listen('.cohort.updated', (event: any) => {
                    logger.log('Cohort updated:', event);
                    lastUpdate.value = new Date();
                    // Refresh data
                    cohortStore.refreshData();
                });

            realTimeUpdates.value = true;
        }
    };

    const unsubscribeFromUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.leave('cohort-updates');
            realTimeUpdates.value = false;
        }
    };

    // Auto-refresh functionality
    const startAutoRefresh = (intervalMs: number = 30000) => {
        return setInterval(() => {
            if (cohortStore.filters.cohortIds.length > 0) {
                cohortStore.refreshData();
            }
        }, intervalMs);
    };

    const stopAutoRefresh = (intervalId: number) => {
        clearInterval(intervalId);
    };

    return {
        // Store access
        cohortStore,

        // Reactive state
        realTimeUpdates,
        lastUpdate,

        // Computed properties
        retentionRates,
        churnRates,
        engagementMetrics,
        filteredCohorts,
        sortedCohorts,

        // Helper functions
        getRetentionTrend,
        getEngagementScore,
        getChurnRate,

        // Real-time functionality
        subscribeToUpdates,
        unsubscribeFromUpdates,
        startAutoRefresh,
        stopAutoRefresh,
    };
}