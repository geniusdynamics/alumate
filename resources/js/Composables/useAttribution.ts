import { computed, ref } from 'vue';
import { useAttributionStore } from '../Stores/useAttributionStore';
import type { AttributionTouch, AttributionSource } from '../Types/analytics';

// Declare Echo on window for TypeScript
declare global {
    interface Window {
        Echo: any;
    }
}

/**
 * Composable for attribution analysis functionality
 * Provides hooks for visualization, computed flows, filters, and WebSocket subscriptions
 */
export function useAttribution() {
    const attributionStore = useAttributionStore();

    // Reactive state for real-time updates
    const realTimeUpdates = ref(false);
    const lastUpdate = ref<Date | null>(null);

    // Computed properties for visualization
    const attributionSources = computed(() => {
        return attributionStore.attributionReport?.sources || [];
    });

    const touchFlow = computed(() => {
        const touches = attributionStore.touchHistory;
        if (touches.length === 0) return [];

        // Group touches by source for flow visualization
        const sourceGroups = touches.reduce((acc, touch) => {
            const source = touch.source || 'direct';
            if (!acc[source]) {
                acc[source] = [];
            }
            acc[source].push(touch);
            return acc;
        }, {} as Record<string, AttributionTouch[]>);

        // Create flow data
        const timestamps = touches.map(t => new Date(t.timestamp).getTime());
        const minTimestamp = Math.min(...timestamps);
        const maxTimestamp = Math.max(...timestamps);

        const flow = Object.entries(sourceGroups).map(([source, sourceTouches]) => ({
            source,
            touches: sourceTouches.length,
            totalValue: sourceTouches.reduce((sum, touch) => sum + touch.value, 0),
            firstTouch: sourceTouches.some(touch => new Date(touch.timestamp).getTime() === minTimestamp),
            lastTouch: sourceTouches.some(touch => new Date(touch.timestamp).getTime() === maxTimestamp),
            events: sourceTouches.map(touch => ({
                type: touch.event_type,
                timestamp: touch.timestamp,
                value: touch.value,
            })),
        }));

        return flow.sort((a, b) => b.totalValue - a.totalValue);
    });

    const attributionModel = computed(() => {
        return attributionStore.attributionReport?.model || 'last_touch';
    });

    const totalValue = computed(() => {
        return attributionStore.attributionReport?.total_value || 0;
    });

    const touchCount = computed(() => {
        return attributionStore.attributionReport?.touch_count || 0;
    });

    // Filters and sorting
    const filteredTouches = computed(() => {
        let touches = [...attributionStore.touches];

        const filters = attributionStore.filters;

        if (filters.source) {
            touches = touches.filter(touch => touch.source === filters.source);
        }

        if (filters.start_date) {
            touches = touches.filter(touch => touch.timestamp >= filters.start_date!);
        }

        if (filters.end_date) {
            touches = touches.filter(touch => touch.timestamp <= filters.end_date!);
        }

        return touches;
    });

    const sortedTouches = computed(() => {
        return [...filteredTouches.value].sort((a, b) => {
            // Sort by timestamp (newest first)
            return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
        });
    });

    // Source breakdown for charts
    const sourceBreakdown = computed((): AttributionSource[] => {
        return attributionSources.value;
    });

    // Timeline data for visualization
    const timelineData = computed(() => {
        const touches = sortedTouches.value;
        if (touches.length === 0) return [];

        return touches.map(touch => ({
            id: touch.id,
            timestamp: new Date(touch.timestamp),
            source: touch.source || 'direct',
            eventType: touch.event_type,
            value: touch.value,
            summary: touch.touch_summary || `${touch.event_type} from ${touch.source || 'direct'}`,
        }));
    });

    // Attribution helpers
    const getSourceColor = (source: string): string => {
        const colors: Record<string, string> = {
            'google': '#4285F4',
            'facebook': '#1877F2',
            'twitter': '#1DA1F2',
            'linkedin': '#0077B5',
            'email': '#EA4335',
            'direct': '#34A853',
            'referral': '#FBBC05',
        };
        return colors[source.toLowerCase()] || '#9E9E9E';
    };

    const getModelDescription = (model: string): string => {
        const descriptions: Record<string, string> = {
            'last_touch': 'Credits the last touchpoint before conversion',
            'first_touch': 'Credits the first touchpoint in the journey',
            'linear': 'Distributes credit equally across all touchpoints',
            'time_decay': 'Gives more credit to recent touchpoints',
        };
        return descriptions[model] || 'Unknown attribution model';
    };

    const getTopSources = (limit = 5): AttributionSource[] => {
        return sourceBreakdown.value
            .sort((a, b) => b.value - a.value)
            .slice(0, limit);
    };

    // WebSocket subscription for real-time updates
    const subscribeToUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.private(`attribution-updates`)
                .listen('.attribution.updated', (event: any) => {
                    logger.log('Attribution updated:', event);
                    lastUpdate.value = new Date();
                    // Refresh data
                    attributionStore.refreshData();
                });

            realTimeUpdates.value = true;
        }
    };

    const unsubscribeFromUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.leave('attribution-updates');
            realTimeUpdates.value = false;
        }
    };

    // Auto-refresh functionality
    const startAutoRefresh = (intervalMs: number = 30000) => {
        return setInterval(() => {
            if (attributionStore.filters.user_id) {
                attributionStore.refreshData();
            }
        }, intervalMs);
    };

    const stopAutoRefresh = (intervalId: number) => {
        clearInterval(intervalId);
    };

    return {
        // Store access
        attributionStore,

        // Reactive state
        realTimeUpdates,
        lastUpdate,

        // Computed properties
        attributionSources,
        touchFlow,
        attributionModel,
        totalValue,
        touchCount,
        filteredTouches,
        sortedTouches,
        sourceBreakdown,
        timelineData,

        // Helper functions
        getSourceColor,
        getModelDescription,
        getTopSources,

        // Real-time functionality
        subscribeToUpdates,
        unsubscribeFromUpdates,
        startAutoRefresh,
        stopAutoRefresh,
    };
}