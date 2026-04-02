import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useWebSocket } from '@vueuse/core';
import { useLearningStore } from '../Stores/useLearningStore';
import type { LearningProgress } from '../types/analytics';

/**
 * Composable for learning analytics functionality
 * Handles WebSocket real-time updates and learning progress filtering/sorting
 */
export function useLearning() {
    const learningStore = useLearningStore();

    // WebSocket connection for real-time updates
    const { data, send, close, open } = useWebSocket('/learning-updates', {
        autoReconnect: true,
        heartbeat: {
            message: 'ping',
            interval: 30000,
        },
        onConnected() {
            logger.log('Connected to learning WebSocket');
            // Subscribe to learning updates
            send(JSON.stringify({
                action: 'subscribe',
                channel: 'learning-updated'
            }));
        },
        onDisconnected() {
            logger.log('Disconnected from learning WebSocket');
        },
        onMessage(ws, event) {
            try {
                const message = JSON.parse(event.data);
                if (message.type === 'learning-updated') {
                    // Refetch learning progress when updates are received
                    learningStore.fetchProgress({
                        date_from: learningStore.filters.dateRange?.from,
                        date_to: learningStore.filters.dateRange?.to,
                        course_id: learningStore.filters.courseId,
                        user_id: learningStore.filters.userId,
                    }).catch(err => {
                        console.error('Failed to refetch learning progress after WebSocket update:', err);
                    });
                }
            } catch (err) {
                console.error('Failed to parse WebSocket message:', err);
            }
        },
    });

    // Reactive state for local component management
    const selectedProgress = ref<LearningProgress | null>(null);
    const showDetailsModal = ref(false);

    // Computed properties
    const filteredProgress = computed(() => learningStore.filteredProgress);
    const learningStats = computed(() => learningStore.learningStats);
    const isLoading = computed(() => learningStore.isLoading);
    const error = computed(() => learningStore.error);

    // Methods
    const openDetailsModal = (progress: LearningProgress) => {
        selectedProgress.value = progress;
        showDetailsModal.value = true;
    };

    const closeDetailsModal = () => {
        showDetailsModal.value = false;
        selectedProgress.value = null;
    };

    const updateFilters = (filters: Partial<typeof learningStore.filters>) => {
        learningStore.updateFilters(filters);
    };

    const refreshData = async () => {
        try {
            await learningStore.fetchProgress({
                date_from: learningStore.filters.dateRange?.from,
                date_to: learningStore.filters.dateRange?.to,
                course_id: learningStore.filters.courseId,
                user_id: learningStore.filters.userId,
            });
        } catch (err) {
            console.error('Failed to refresh learning data:', err);
        }
    };

    const trackInteraction = async (interaction: {
        user_id: string;
        course_id: string;
        interaction_type: string;
        data?: Record<string, any>;
    }) => {
        try {
            await learningStore.trackInteraction(interaction);
        } catch (err) {
            console.error('Failed to track interaction:', err);
        }
    };

    const verifyCertification = async (progressId: string) => {
        try {
            await learningStore.verifyCertification(progressId);
        } catch (err) {
            console.error('Failed to verify certification:', err);
        }
    };

    // Reactive functions for filtering/sorting
    const filterByEngagementScore = (minScore: number) => {
        return filteredProgress.value.filter(p => (p.engagement_score || 0) >= minScore);
    };

    const filterCertifiedOnly = () => {
        return filteredProgress.value.filter(p => p.certified);
    };

    const sortByEngagementScore = (ascending: boolean = false) => {
        return [...filteredProgress.value].sort((a, b) => {
            const scoreA = a.engagement_score || 0;
            const scoreB = b.engagement_score || 0;
            return ascending ? scoreA - scoreB : scoreB - scoreA;
        });
    };

    const sortByCompletionRate = (ascending: boolean = false) => {
        return [...filteredProgress.value].sort((a, b) => {
            // This would need course data to calculate completion rate
            // For now, sort by modules_completed
            return ascending
                ? a.modules_completed - b.modules_completed
                : b.modules_completed - a.modules_completed;
        });
    };

    const getAggregates = () => {
        const progress = filteredProgress.value;
        const total = progress.length;
        const certified = progress.filter(p => p.certified).length;
        const avgEngagement = total > 0
            ? progress.reduce((sum, p) => sum + (p.engagement_score || 0), 0) / total
            : 0;

        return {
            totalProgress: total,
            certifiedCount: certified,
            certificationRate: total > 0 ? (certified / total) * 100 : 0,
            avgEngagement,
        };
    };

    // Lifecycle
    onMounted(() => {
        // WebSocket connection is handled by useWebSocket
    });

    onUnmounted(() => {
        close();
    });

    return {
        // State
        selectedProgress,
        showDetailsModal,

        // Computed
        filteredProgress,
        learningStats,
        isLoading,
        error,

        // Methods
        openDetailsModal,
        closeDetailsModal,
        updateFilters,
        refreshData,
        trackInteraction,
        verifyCertification,

        // Filtering/Sorting functions
        filterByEngagementScore,
        filterCertifiedOnly,
        sortByEngagementScore,
        sortByCompletionRate,
        getAggregates,

        // WebSocket
        wsData: data,
        wsSend: send,
        wsClose: close,
        wsOpen: open,
    };
}