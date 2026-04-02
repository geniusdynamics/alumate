import { computed, ref } from 'vue';
import { useCustomEventStore } from '../Stores/useCustomEventStore';
import type { CustomEventDefinition, CustomEvent, CustomEventAnalytics } from '../Types/analytics';

// Declare Echo on window for TypeScript
declare global {
    interface Window {
        Echo: any;
    }
}

/**
 * Composable for custom event functionality
 * Provides hooks for visualization, computed flows, filters, and WebSocket subscriptions
 */
export function useCustomEvent() {
    const customEventStore = useCustomEventStore();

    // Reactive state for real-time updates
    const realTimeUpdates = ref(false);
    const lastUpdate = ref<Date | null>(null);

    // Computed properties for visualization
    const eventDefinitions = computed(() => {
        return customEventStore.definitions;
    });

    const eventData = computed(() => {
        return customEventStore.events;
    });

    const analytics = computed(() => {
        return customEventStore.analyticsData;
    });

    // Filters and sorting
    const filteredEvents = computed(() => {
        // In a real implementation, we would apply filters here
        return [...customEventStore.events];
    });

    const sortedEvents = computed(() => {
        return [...filteredEvents.value].sort((a, b) => {
            // Sort by timestamp (newest first)
            return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
        });
    });

    // Timeline data for visualization
    const timelineData = computed(() => {
        const events = sortedEvents.value;
        if (events.length === 0) return [];

        return events.map(event => ({
            id: event.id,
            timestamp: new Date(event.timestamp),
            definitionId: event.definition_id,
            userId: event.user_id,
            data: event.data_json,
        }));
    });

    // Event helpers
    const getEventColor = (eventName: string): string => {
        // Generate a consistent color based on the event name
        let hash = 0;
        for (let i = 0; i < eventName.length; i++) {
            hash = eventName.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        const c = (hash & 0x00FFFFFF).toString(16).toUpperCase();
        return "#" + "00000".substring(0, 6 - c.length) + c;
    };

    const validateEventData = (data: any, definition: CustomEventDefinition): boolean => {
        // Check required parameters
        for (const param of definition.parameters_json) {
            if (!(param.name in data)) {
                console.warn(`Missing required parameter: ${param.name}`);
                return false;
            }
            
            // Validate type
            const value = data[param.name];
            switch (param.type) {
                case 'string':
                    if (typeof value !== 'string') {
                        console.warn(`Parameter ${param.name} should be a string`);
                        return false;
                    }
                    break;
                case 'number':
                    if (typeof value !== 'number') {
                        console.warn(`Parameter ${param.name} should be a number`);
                        return false;
                    }
                    break;
                case 'boolean':
                    if (typeof value !== 'boolean') {
                        console.warn(`Parameter ${param.name} should be a boolean`);
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    };

    const getDefinitionById = (id: number): CustomEventDefinition | undefined => {
        return customEventStore.definitions.find(def => def.id === id);
    };

    // WebSocket subscription for real-time updates
    const subscribeToUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.private(`custom-events`)
                .listen('.custom-event.created', (event: any) => {
                    logger.log('Custom event created:', event);
                    lastUpdate.value = new Date();
                    // Refresh data
                    customEventStore.refreshData();
                });

            realTimeUpdates.value = true;
        }
    };

    const unsubscribeFromUpdates = () => {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.leave('custom-events');
            realTimeUpdates.value = false;
        }
    };

    // Auto-refresh functionality
    const startAutoRefresh = (intervalMs: number = 30000) => {
        return setInterval(() => {
            customEventStore.refreshData();
        }, intervalMs);
    };

    const stopAutoRefresh = (intervalId: number) => {
        clearInterval(intervalId);
    };

    return {
        // Store access
        customEventStore,

        // Reactive state
        realTimeUpdates,
        lastUpdate,

        // Computed properties
        eventDefinitions,
        eventData,
        analytics,
        filteredEvents,
        sortedEvents,
        timelineData,

        // Helper functions
        getEventColor,
        validateEventData,
        getDefinitionById,

        // Real-time functionality
        subscribeToUpdates,
        unsubscribeFromUpdates,
        startAutoRefresh,
        stopAutoRefresh,
    };
}