<template>
    <div class="collaboration-panel flex h-full w-80 flex-col overflow-hidden border-l border-gray-200 bg-white">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Collaboration</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ activeSessions.length }} {{ activeSessions.length === 1 ? 'user' : 'users' }} editing</p>
                </div>

                <div class="flex items-center gap-2">
                    <div :class="['h-3 w-3 rounded-full', isConnected ? 'bg-green-500' : 'bg-red-500']"></div>
                    <span class="text-sm text-gray-600">
                        {{ isConnected ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="border-b border-gray-200 p-4">
            <h4 class="mb-3 text-sm font-medium text-gray-900">Active Users</h4>

            <div v-if="activeSessions.length === 0" class="py-4 text-center">
                <Icon name="users" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                <p class="text-sm text-gray-500">No other users editing</p>
            </div>

            <div v-else class="space-y-2">
                <div v-for="session in activeSessions" :key="session.id" class="flex items-center gap-3 rounded-md bg-gray-50 p-2">
                    <div class="relative">
                        <img v-if="session.user.avatar" :src="session.user.avatar" :alt="session.user.name" class="h-8 w-8 rounded-full" />
                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-sm font-medium text-white">
                            {{ session.user.name.charAt(0).toUpperCase() }}
                        </div>

                        <div
                            :class="['absolute -bottom-1 -right-1 h-3 w-3 rounded-full border-2 border-white', getStatusColor(session.status)]"
                        ></div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">
                            {{ session.user.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ getActivityText(session) }}
                        </p>
                    </div>

                    <div class="text-xs text-gray-400">
                        {{ formatTime(session.last_activity) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Changes -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-sm font-medium text-gray-900">Recent Changes</h4>
                    <button
                        @click="loadRecentChanges"
                        :disabled="isLoadingChanges"
                        class="text-sm text-blue-600 hover:text-blue-700 disabled:opacity-50"
                    >
                        <Icon :name="isLoadingChanges ? 'spinner' : 'refresh'" :class="['h-4 w-4', { 'animate-spin': isLoadingChanges }]" />
                    </button>
                </div>

                <div v-if="isLoadingChanges" class="space-y-2">
                    <div v-for="i in 5" :key="i" class="animate-pulse">
                        <div class="h-12 rounded-md bg-gray-200"></div>
                    </div>
                </div>

                <div v-else-if="recentChanges.length === 0" class="py-8 text-center">
                    <Icon name="clock" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                    <p class="text-sm text-gray-500">No recent changes</p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="change in recentChanges"
                        :key="change.id"
                        :class="[
                            'rounded-md border p-3 text-sm',
                            change.is_applied ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50',
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Icon :name="getOperationIcon(change.operation_type)" class="h-4 w-4" />
                                    <span class="font-medium">{{ getOperationText(change.operation_type) }}</span>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                            change.is_applied ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800',
                                        ]"
                                    >
                                        {{ change.is_applied ? 'Applied' : 'Pending' }}
                                    </span>
                                </div>

                                <p class="mt-1 text-gray-600">by {{ change.user.name }}</p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ formatTime(change.created_at) }}
                                </p>
                            </div>

                            <div v-if="!change.is_applied" class="ml-2 flex items-center gap-1">
                                <button
                                    @click="applyChange(change)"
                                    :disabled="isApplyingChanges"
                                    class="p-1 text-green-600 hover:text-green-700 disabled:opacity-50"
                                    title="Apply change"
                                >
                                    <Icon name="check" class="h-4 w-4" />
                                </button>

                                <button
                                    @click="rejectChange(change)"
                                    :disabled="isApplyingChanges"
                                    class="p-1 text-red-600 hover:text-red-700 disabled:opacity-50"
                                    title="Reject change"
                                >
                                    <Icon name="x" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflict Resolution -->
        <div v-if="conflicts.length > 0" class="border-t border-red-200 bg-red-50 p-4">
            <div class="mb-3 flex items-center gap-2">
                <Icon name="alert-triangle" class="h-5 w-5 text-red-600" />
                <h4 class="text-sm font-medium text-red-900">{{ conflicts.length }} Conflict{{ conflicts.length === 1 ? '' : 's' }}</h4>
            </div>

            <div class="space-y-2">
                <div v-for="conflict in conflicts" :key="conflict.change_id" class="rounded-md border border-red-200 bg-white p-3">
                    <p class="mb-2 text-sm text-red-800">
                        {{ conflict.error }}
                    </p>

                    <div class="flex gap-2">
                        <button
                            @click="resolveConflict(conflict, 'accept')"
                            class="rounded bg-green-100 px-2 py-1 text-xs text-green-800 hover:bg-green-200"
                        >
                            Accept
                        </button>
                        <button
                            @click="resolveConflict(conflict, 'reject')"
                            class="rounded bg-red-100 px-2 py-1 text-xs text-red-800 hover:bg-red-200"
                        >
                            Reject
                        </button>
                        <button @click="showMergeModal(conflict)" class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800 hover:bg-blue-200">
                            Merge
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="border-t border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700"> Real-time sync </label>
                <button
                    @click="toggleRealTimeSync"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        realTimeSyncEnabled ? 'bg-blue-600' : 'bg-gray-200',
                    ]"
                >
                    <span
                        :class="[
                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                            realTimeSyncEnabled ? 'translate-x-6' : 'translate-x-1',
                        ]"
                    />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/ui/Icon.vue';
import { useCollaboration } from '@/composables/useCollaboration';
import { onMounted, onUnmounted } from 'vue';

interface CollaborationSession {
    id: number;
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
    user: {
        id: number;
        name: string;
        email: string;
    };
    created_at: string;
    is_applied: boolean;
}

interface Conflict {
    change_id: number;
    error: string;
    change: PageChange;
}

const props = defineProps<{
    pageId: number;
}>();

const {
    activeSessions,
    recentChanges,
    conflicts,
    isConnected,
    isLoadingChanges,
    isApplyingChanges,
    realTimeSyncEnabled,
    startSession,
    endSession,
    loadRecentChanges,
    applyChanges,
    resolveConflict: resolveConflictAction,
    toggleRealTimeSync,
} = useCollaboration(props.pageId);

onMounted(() => {
    startSession();
    loadRecentChanges();
});

onUnmounted(() => {
    endSession();
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-500';
        case 'idle':
            return 'bg-yellow-500';
        case 'disconnected':
            return 'bg-gray-500';
        default:
            return 'bg-gray-500';
    }
};

const getActivityText = (session: CollaborationSession) => {
    if (session.selected_component) {
        return `Editing ${session.selected_component.type || 'component'}`;
    }
    return 'Viewing page';
};

const getOperationIcon = (operationType: string) => {
    switch (operationType) {
        case 'add':
            return 'plus';
        case 'update':
            return 'edit';
        case 'delete':
            return 'trash';
        case 'move':
            return 'move';
        default:
            return 'edit';
    }
};

const getOperationText = (operationType: string) => {
    switch (operationType) {
        case 'add':
            return 'Added component';
        case 'update':
            return 'Updated component';
        case 'delete':
            return 'Deleted component';
        case 'move':
            return 'Moved component';
        default:
            return 'Modified component';
    }
};

const applyChange = async (change: PageChange) => {
    await applyChanges([change.id]);
};

const rejectChange = async (change: PageChange) => {
    await resolveConflictAction(change.id, 'reject');
};

const resolveConflict = async (conflict: Conflict, resolution: string) => {
    await resolveConflictAction(conflict.change_id, resolution);
};

const showMergeModal = (conflict: Conflict) => {
    // TODO: Implement merge modal
    console.log('Show merge modal for conflict:', conflict);
};

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
    return date.toLocaleDateString();
};
</script>

<style scoped>
.collaboration-panel {
    min-height: 0;
}
</style>
