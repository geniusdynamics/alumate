import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import webSocketService from '@/Services/WebSocketService';

export function useRealTimeUpdates() {
    const page = usePage();
    const isConnected = ref(false);
    const connectionState = ref('disconnected');
    const reconnectAttempts = ref(0);
    const lastActivity = ref(null);
    const unsubscribeFunctions = ref([]);

    // Get current user from Inertia page props
    const currentUser = computed(() => page.props.auth?.user || null);

    /**
     * Initialize real-time connection
     */
    const connect = () => {
        if (!currentUser.value) {
            console.warn('No authenticated user found, skipping WebSocket connection');
            return;
        }

        // Listen for connection events
        webSocketService.on('connected', () => {
            isConnected.value = true;
            connectionState.value = 'connected';
            reconnectAttempts.value = 0;
            console.log('Real-time updates connected');
        });

        webSocketService.on('disconnected', () => {
            isConnected.value = false;
            connectionState.value = 'disconnected';
            console.log('Real-time updates disconnected');
        });

        webSocketService.on('state_change', (states) => {
            connectionState.value = states.current;
        });

        webSocketService.on('error', (error) => {
            console.error('WebSocket error:', error);
        });

        // Update connection status
        isConnected.value = webSocketService.isConnectedToWebSocket();
        connectionState.value = webSocketService.getConnectionState();
    };

    /**
     * Listen for timeline updates
     */
    const listenForTimelineUpdates = (callback) => {
        if (!currentUser.value) return null;

        const unsubscribe = webSocketService.listenForTimelineUpdates(
            currentUser.value.id,
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Listen for post engagement updates
     */
    const listenForPostEngagement = (postId, callback) => {
        const unsubscribe = webSocketService.listenForPostEngagement(
            postId,
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Listen for connection requests
     */
    const listenForConnectionRequests = (callback) => {
        if (!currentUser.value) return null;

        const unsubscribe = webSocketService.listenForConnectionRequests(
            currentUser.value.id,
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Listen for circle activity
     */
    const listenForCircleActivity = (circleId, callback) => {
        const unsubscribe = webSocketService.listenForCircleActivity(
            circleId,
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Listen for group activity
     */
    const listenForGroupActivity = (groupId, callback) => {
        const unsubscribe = webSocketService.listenForGroupActivity(
            groupId,
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Listen for public timeline updates
     */
    const listenForPublicTimeline = (callback) => {
        const unsubscribe = webSocketService.listenForPublicTimeline(
            (data) => {
                lastActivity.value = new Date();
                callback(data);
            }
        );

        if (unsubscribe) {
            unsubscribeFunctions.value.push(unsubscribe);
        }

        return unsubscribe;
    };

    /**
     * Get connection statistics
     */
    const getConnectionStats = () => {
        return webSocketService.getChannelStats();
    };

    /**
     * Manually reconnect
     */
    const reconnect = () => {
        webSocketService.reconnect();
    };

    /**
     * Disconnect from WebSocket
     */
    const disconnect = () => {
        // Call all unsubscribe functions
        unsubscribeFunctions.value.forEach(unsubscribe => {
            try {
                unsubscribe();
            } catch (error) {
                console.warn('Error unsubscribing from channel:', error);
            }
        });
        unsubscribeFunctions.value = [];

        webSocketService.disconnect();
        isConnected.value = false;
        connectionState.value = 'disconnected';
    };

    // Auto-connect when user is available
    watch(currentUser, (newUser) => {
        if (newUser && !isConnected.value) {
            connect();
        } else if (!newUser && isConnected.value) {
            disconnect();
        }
    }, { immediate: true });

    // Cleanup on unmount
    onUnmounted(() => {
        disconnect();
    });

    return {
        // State
        isConnected: computed(() => isConnected.value),
        connectionState: computed(() => connectionState.value),
        reconnectAttempts: computed(() => reconnectAttempts.value),
        lastActivity: computed(() => lastActivity.value),
        currentUser,

        // Methods
        connect,
        disconnect,
        reconnect,
        getConnectionStats,

        // Listeners
        listenForTimelineUpdates,
        listenForPostEngagement,
        listenForConnectionRequests,
        listenForCircleActivity,
        listenForGroupActivity,
        listenForPublicTimeline,
    };
}

/**
 * Composable for post-specific real-time updates
 */
export function usePostRealTime(postId) {
    const { listenForPostEngagement } = useRealTimeUpdates();
    const engagementCounts = ref({
        likes: 0,
        comments: 0,
        shares: 0,
        reactions: 0,
    });
    const recentEngagements = ref([]);

    const startListening = (initialCounts = {}) => {
        engagementCounts.value = { ...engagementCounts.value, ...initialCounts };

        return listenForPostEngagement(postId, (data) => {
            // Update engagement counts
            if (data.engagement_counts) {
                engagementCounts.value = data.engagement_counts;
            }

            // Add to recent engagements
            recentEngagements.value.unshift({
                user: data.user,
                type: data.engagement_type,
                timestamp: data.timestamp,
                data: data.comment || data.reaction_type || null,
            });

            // Keep only last 10 engagements
            if (recentEngagements.value.length > 10) {
                recentEngagements.value = recentEngagements.value.slice(0, 10);
            }
        });
    };

    return {
        engagementCounts: computed(() => engagementCounts.value),
        recentEngagements: computed(() => recentEngagements.value),
        startListening,
    };
}

/**
 * Composable for timeline real-time updates
 */
export function useTimelineRealTime() {
    const { listenForTimelineUpdates, listenForPublicTimeline } = useRealTimeUpdates();
    const newPosts = ref([]);
    const hasNewPosts = computed(() => newPosts.value.length > 0);

    const startListening = (includePublic = false) => {
        const unsubscribeFunctions = [];

        // Listen for personal timeline updates
        const personalUnsubscribe = listenForTimelineUpdates((data) => {
            if (data.post) {
                newPosts.value.unshift(data.post);
            }
        });
        if (personalUnsubscribe) unsubscribeFunctions.push(personalUnsubscribe);

        // Listen for public timeline if requested
        if (includePublic) {
            const publicUnsubscribe = listenForPublicTimeline((data) => {
                if (data.post) {
                    newPosts.value.unshift(data.post);
                }
            });
            if (publicUnsubscribe) unsubscribeFunctions.push(publicUnsubscribe);
        }

        return () => {
            unsubscribeFunctions.forEach(unsubscribe => unsubscribe());
        };
    };

    const clearNewPosts = () => {
        newPosts.value = [];
    };

    const getNewPosts = () => {
        const posts = [...newPosts.value];
        clearNewPosts();
        return posts;
    };

    return {
        newPosts: computed(() => newPosts.value),
        hasNewPosts,
        startListening,
        clearNewPosts,
        getNewPosts,
    };
}

/**
 * Composable for connection real-time updates
 */
export function useConnectionRealTime() {
    const { listenForConnectionRequests } = useRealTimeUpdates();
    const connectionRequests = ref([]);
    const connectionUpdates = ref([]);

    const startListening = () => {
        return listenForConnectionRequests((data) => {
            if (data.notification_type === 'connection_request_received') {
                connectionRequests.value.unshift(data);
            } else {
                connectionUpdates.value.unshift(data);
            }
        });
    };

    const clearConnectionRequests = () => {
        connectionRequests.value = [];
    };

    const clearConnectionUpdates = () => {
        connectionUpdates.value = [];
    };

    return {
        connectionRequests: computed(() => connectionRequests.value),
        connectionUpdates: computed(() => connectionUpdates.value),
        hasNewConnectionRequests: computed(() => connectionRequests.value.length > 0),
        hasConnectionUpdates: computed(() => connectionUpdates.value.length > 0),
        startListening,
        clearConnectionRequests,
        clearConnectionUpdates,
    };
}