import { onUnmounted, reactive, ref } from 'vue';

interface CollaborationSession {
    id: number;
    session_id: string;
    user: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
    status: 'active' | 'idle' | 'disconnected';
    last_activity: string;
    cursor_position?: any;
    selected_component?: any;
}

interface PageChange {
    id: number;
    operation_type: string;
    operation_data: any;
    component_id?: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    created_at: string;
    is_applied: boolean;
    sequence_number: number;
}

interface Conflict {
    change_id: number;
    error: string;
    change: PageChange;
}

interface CollaborationState {
    currentSession: CollaborationSession | null;
    activeSessions: CollaborationSession[];
    recentChanges: PageChange[];
    conflicts: Conflict[];
    isConnected: boolean;
    isLoadingChanges: boolean;
    isApplyingChanges: boolean;
    realTimeSyncEnabled: boolean;
    lastSequenceNumber: number;
}

const state = reactive<CollaborationState>({
    currentSession: null,
    activeSessions: [],
    recentChanges: [],
    conflicts: [],
    isConnected: false,
    isLoadingChanges: false,
    isApplyingChanges: false,
    realTimeSyncEnabled: true,
    lastSequenceNumber: 0,
});

export function useCollaboration(pageId: number) {
    let echoChannel: any = null;
    let activityInterval: number | null = null;

    const startSession = async (): Promise<CollaborationSession | null> => {
        try {
            const response = await fetch(`/api/pages/${pageId}/collaboration/start`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                state.currentSession = data.data;
                state.isConnected = true;

                // Start real-time updates
                if (state.realTimeSyncEnabled) {
                    startRealTimeUpdates();
                }

                // Start activity heartbeat
                startActivityHeartbeat();

                // Load initial data
                await Promise.all([loadActiveSessions(), loadRecentChanges()]);

                return state.currentSession;
            } else {
                console.error('Failed to start collaboration session:', response.statusText);
                return null;
            }
        } catch (error) {
            console.error('Error starting collaboration session:', error);
            return null;
        }
    };

    const endSession = async (): Promise<void> => {
        if (!state.currentSession) return;

        try {
            await fetch('/api/collaboration/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: state.currentSession.session_id,
                }),
            });
        } catch (error) {
            console.error('Error ending collaboration session:', error);
        } finally {
            state.currentSession = null;
            state.isConnected = false;
            stopRealTimeUpdates();
            stopActivityHeartbeat();
        }
    };

    const loadActiveSessions = async (): Promise<void> => {
        try {
            const response = await fetch(`/api/pages/${pageId}/collaboration/sessions`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                state.activeSessions = data.data.filter((session: CollaborationSession) => session.id !== state.currentSession?.id);
            }
        } catch (error) {
            console.error('Error loading active sessions:', error);
        }
    };

    const loadRecentChanges = async (): Promise<void> => {
        state.isLoadingChanges = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/collaboration/changes`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                state.recentChanges = data.data;

                // Update last sequence number
                if (data.data.length > 0) {
                    state.lastSequenceNumber = Math.max(...data.data.map((c: PageChange) => c.sequence_number));
                }
            }
        } catch (error) {
            console.error('Error loading recent changes:', error);
        } finally {
            state.isLoadingChanges = false;
        }
    };

    const recordChange = async (
        operationType: string,
        operationData: any,
        componentId?: string,
        previousState?: any,
        newState?: any,
    ): Promise<PageChange | null> => {
        if (!state.currentSession) return null;

        try {
            const response = await fetch(`/api/pages/${pageId}/collaboration/changes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: state.currentSession.session_id,
                    operation_type: operationType,
                    operation_data: operationData,
                    component_id: componentId,
                    previous_state: previousState,
                    new_state: newState,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                const change = data.data;

                // Add to recent changes
                state.recentChanges.unshift(change);

                return change;
            } else {
                console.error('Failed to record change:', response.statusText);
                return null;
            }
        } catch (error) {
            console.error('Error recording change:', error);
            return null;
        }
    };

    const applyChanges = async (changeIds: number[]): Promise<boolean> => {
        state.isApplyingChanges = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/collaboration/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    change_ids: changeIds,
                }),
            });

            if (response.ok) {
                const data = await response.json();

                // Update applied changes
                changeIds.forEach((id) => {
                    const change = state.recentChanges.find((c) => c.id === id);
                    if (change) {
                        change.is_applied = true;
                    }
                });

                // Handle conflicts
                if (data.data.conflicts && data.data.conflicts.length > 0) {
                    state.conflicts = data.data.conflicts;
                }

                return true;
            } else {
                console.error('Failed to apply changes:', response.statusText);
                return false;
            }
        } catch (error) {
            console.error('Error applying changes:', error);
            return false;
        } finally {
            state.isApplyingChanges = false;
        }
    };

    const resolveConflict = async (changeId: number, resolution: 'accept' | 'reject' | 'merge', mergedData?: any): Promise<boolean> => {
        try {
            const response = await fetch(`/api/collaboration/changes/${changeId}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    resolution,
                    merged_data: mergedData,
                }),
            });

            if (response.ok) {
                // Remove from conflicts
                state.conflicts = state.conflicts.filter((c) => c.change_id !== changeId);

                // Update or remove from recent changes
                if (resolution === 'reject') {
                    state.recentChanges = state.recentChanges.filter((c) => c.id !== changeId);
                } else {
                    const change = state.recentChanges.find((c) => c.id === changeId);
                    if (change) {
                        change.is_applied = true;
                    }
                }

                return true;
            } else {
                console.error('Failed to resolve conflict:', response.statusText);
                return false;
            }
        } catch (error) {
            console.error('Error resolving conflict:', error);
            return false;
        }
    };

    const updateActivity = async (cursorPosition?: any, selectedComponent?: any): Promise<void> => {
        if (!state.currentSession) return;

        try {
            await fetch('/api/collaboration/activity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: state.currentSession.session_id,
                    cursor_position: cursorPosition,
                    selected_component: selectedComponent,
                }),
            });
        } catch (error) {
            console.error('Error updating activity:', error);
        }
    };

    const startRealTimeUpdates = (): void => {
        if (!window.Echo || echoChannel) return;

        echoChannel = window.Echo.private(`page.${pageId}`)
            .listen('PageChangeRecorded', (event: any) => {
                // Add new change to the list
                state.recentChanges.unshift(event.change);

                // Update sequence number
                if (event.change.sequence_number > state.lastSequenceNumber) {
                    state.lastSequenceNumber = event.change.sequence_number;
                }
            })
            .listen('SessionJoined', (event: any) => {
                // Add new session to active sessions
                if (event.session.id !== state.currentSession?.id) {
                    state.activeSessions.push(event.session);
                }
            })
            .listen('SessionLeft', (event: any) => {
                // Remove session from active sessions
                state.activeSessions = state.activeSessions.filter((s) => s.id !== event.session.id);
            })
            .listen('ActivityUpdated', (event: any) => {
                // Update session activity
                const session = state.activeSessions.find((s) => s.id === event.session.id);
                if (session) {
                    Object.assign(session, event.session);
                }
            });
    };

    const stopRealTimeUpdates = (): void => {
        if (echoChannel) {
            echoChannel.stopListening('PageChangeRecorded');
            echoChannel.stopListening('SessionJoined');
            echoChannel.stopListening('SessionLeft');
            echoChannel.stopListening('ActivityUpdated');
            echoChannel = null;
        }
    };

    const startActivityHeartbeat = (): void => {
        if (activityInterval) return;

        activityInterval = window.setInterval(() => {
            updateActivity();
        }, 30000); // Update every 30 seconds
    };

    const stopActivityHeartbeat = (): void => {
        if (activityInterval) {
            clearInterval(activityInterval);
            activityInterval = null;
        }
    };

    const toggleRealTimeSync = (): void => {
        state.realTimeSyncEnabled = !state.realTimeSyncEnabled;

        if (state.realTimeSyncEnabled && state.isConnected) {
            startRealTimeUpdates();
        } else {
            stopRealTimeUpdates();
        }
    };

    // Cleanup on unmount
    onUnmounted(() => {
        endSession();
    });

    return {
        // State
        currentSession: ref(state.currentSession),
        activeSessions: ref(state.activeSessions),
        recentChanges: ref(state.recentChanges),
        conflicts: ref(state.conflicts),
        isConnected: ref(state.isConnected),
        isLoadingChanges: ref(state.isLoadingChanges),
        isApplyingChanges: ref(state.isApplyingChanges),
        realTimeSyncEnabled: ref(state.realTimeSyncEnabled),

        // Actions
        startSession,
        endSession,
        loadActiveSessions,
        loadRecentChanges,
        recordChange,
        applyChanges,
        resolveConflict,
        updateActivity,
        toggleRealTimeSync,
    };
}
