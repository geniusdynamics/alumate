<!-- ABOUTME: Alert modal component for displaying detailed alert information and management actions -->
<!-- ABOUTME: Provides modal interface for viewing, acknowledging, and managing system alerts with detailed context -->
<template>
    <div v-if="isOpen" class="alert-modal-overlay" @click="handleOverlayClick">
        <div class="alert-modal" @click.stop>
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="header-content">
                    <div class="alert-severity">
                        <span :class="['severity-badge', alert?.severity]">{{ alert?.severity?.toUpperCase() }}</span>
                    </div>
                    <div class="header-text">
                        <h2 class="modal-title">{{ alert?.title || 'Alert Details' }}</h2>
                        <p class="modal-subtitle">{{ formatTime(alert?.timestamp) }}</p>
                    </div>
                </div>
                <button @click="closeModal" class="close-button">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="modal-content">
                <!-- Alert Information -->
                <div class="alert-section">
                    <h3 class="section-title">Alert Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Source:</span>
                            <span class="info-value">{{ alert?.source }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category:</span>
                            <span class="info-value">{{ alert?.category || 'General' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span :class="['status-badge', alert?.status]">{{ alert?.status }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority:</span>
                            <span class="info-value">{{ alert?.priority || 'Normal' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Alert Description -->
                <div class="alert-section">
                    <h3 class="section-title">Description</h3>
                    <div class="alert-description">
                        {{ alert?.message || alert?.description }}
                    </div>
                </div>

                <!-- Alert Metadata -->
                <div v-if="alert?.metadata" class="alert-section">
                    <h3 class="section-title">Additional Details</h3>
                    <div class="metadata-grid">
                        <div v-for="(value, key) in alert.metadata" :key="key" class="metadata-item">
                            <span class="metadata-label">{{ formatKey(key) }}:</span>
                            <span class="metadata-value">{{ formatValue(value) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Alert Timeline -->
                <div v-if="alert?.timeline" class="alert-section">
                    <h3 class="section-title">Timeline</h3>
                    <div class="timeline">
                        <div v-for="event in alert.timeline" :key="event.id" class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">{{ event.action }}</div>
                                <div class="timeline-description">{{ event.description }}</div>
                                <div class="timeline-time">{{ formatTime(event.timestamp) }}</div>
                                <div v-if="event.user" class="timeline-user">by {{ event.user }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommended Actions -->
                <div v-if="alert?.recommendedActions" class="alert-section">
                    <h3 class="section-title">Recommended Actions</h3>
                    <div class="actions-list">
                        <div v-for="action in alert.recommendedActions" :key="action.id" class="action-item">
                            <div class="action-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <div class="action-content">
                                <div class="action-title">{{ action.title }}</div>
                                <div class="action-description">{{ action.description }}</div>
                            </div>
                            <button v-if="action.executable" @click="executeAction(action.id)" class="execute-button">Execute</button>
                        </div>
                    </div>
                </div>

                <!-- Related Alerts -->
                <div v-if="relatedAlerts.length > 0" class="alert-section">
                    <h3 class="section-title">Related Alerts</h3>
                    <div class="related-alerts">
                        <div
                            v-for="relatedAlert in relatedAlerts"
                            :key="relatedAlert.id"
                            class="related-alert-item"
                            @click="viewRelated// TODO-toast: alert(relatedAlert)"
                        >
                            <span :class="['severity-dot', relatedAlert.severity]"></span>
                            <span class="related-alert-title">{{ relatedAlert.title }}</span>
                            <span class="related-alert-time">{{ formatTime(relatedAlert.timestamp) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <div class="footer-actions">
                    <button v-if="!alert?.acknowledged" @click="acknowledgeAlert" class="action-btn acknowledge" :disabled="processing">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Acknowledge
                    </button>

                    <button v-if="alert?.status !== 'resolved'" @click="resolveAlert" class="action-btn resolve" :disabled="processing">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        Resolve
                    </button>

                    <button @click="escalateAlert" class="action-btn escalate" :disabled="processing">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                        </svg>
                        Escalate
                    </button>

                    <button @click="closeModal" class="action-btn secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface AlertTimelineEvent {
    id: string;
    action: string;
    description: string;
    timestamp: string;
    user?: string;
}

interface RecommendedAction {
    id: string;
    title: string;
    description: string;
    executable: boolean;
}

interface Alert {
    id: string;
    title: string;
    message?: string;
    description?: string;
    severity: 'critical' | 'high' | 'medium' | 'low';
    status: 'open' | 'acknowledged' | 'investigating' | 'resolved';
    source: string;
    category?: string;
    priority?: string;
    timestamp: string;
    acknowledged?: boolean;
    metadata?: Record<string, any>;
    timeline?: AlertTimelineEvent[];
    recommendedActions?: RecommendedAction[];
}

interface Props {
    isOpen: boolean;
    alert?: Alert | null;
    relatedAlerts?: Alert[];
}

interface Emits {
    close: [];
    acknowledge: [alertId: string];
    resolve: [alertId: string];
    escalate: [alertId: string];
    'execute-action': [actionId: string];
    'view-related': [alert: Alert];
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    alert: null,
    relatedAlerts: () => [],
});

const emit = defineEmits<Emits>();

// Refs
const processing = ref(false);

// Methods
const formatTime = (timestamp?: string): string => {
    if (!timestamp) return 'Unknown';

    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minutes ago`;
    if (diffHours < 24) return `${diffHours} hours ago`;
    if (diffDays < 7) return `${diffDays} days ago`;

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatKey = (key: string): string => {
    return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1');
};

const formatValue = (value: any): string => {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }
    return String(value);
};

const handleOverlayClick = () => {
    closeModal();
};

const closeModal = () => {
    emit('close');
};

const acknowledgeAlert = async () => {
    if (!props.alert) return;

    processing.value = true;
    try {
        emit('acknowledge', props.alert.id);
        await new Promise((resolve) => setTimeout(resolve, 500));
    } finally {
        processing.value = false;
    }
};

const resolveAlert = async () => {
    if (!props.alert) return;

    processing.value = true;
    try {
        emit('resolve', props.alert.id);
        await new Promise((resolve) => setTimeout(resolve, 500));
    } finally {
        processing.value = false;
    }
};

const escalateAlert = async () => {
    if (!props.alert) return;

    processing.value = true;
    try {
        emit('escalate', props.alert.id);
        await new Promise((resolve) => setTimeout(resolve, 500));
    } finally {
        processing.value = false;
    }
};

const executeAction = (actionId: string) => {
    emit('execute-action', actionId);
};

const viewRelatedAlert = (alert: Alert) => {
    emit('view-related', alert);
};

// Watch for escape key
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            const handleEscape = (event: KeyboardEvent) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            };
            document.addEventListener('keydown', handleEscape);

            return () => {
                document.removeEventListener('keydown', handleEscape);
            };
        }
    },
);
</script>

<style scoped>
.alert-modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
    @apply p-4;
}

.alert-modal {
    @apply rounded-lg bg-white shadow-xl dark:bg-gray-800;
    @apply max-h-[90vh] w-full max-w-4xl overflow-hidden;
    @apply flex flex-col;
}

.modal-header {
    @apply flex items-start justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.header-content {
    @apply flex flex-grow items-start gap-4;
}

.alert-severity {
    @apply flex-shrink-0;
}

.severity-badge {
    @apply rounded-full px-3 py-1 text-sm font-bold;
}

.severity-badge.critical {
    @apply bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400;
}

.severity-badge.high {
    @apply bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400;
}

.severity-badge.medium {
    @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400;
}

.severity-badge.low {
    @apply bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400;
}

.header-text {
    @apply flex-grow;
}

.modal-title {
    @apply mb-1 text-xl font-semibold text-gray-900 dark:text-white;
}

.modal-subtitle {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.close-button {
    @apply p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
    @apply rounded-md hover:bg-gray-100 dark:hover:bg-gray-700;
    @apply transition-colors duration-200;
}

.close-icon {
    @apply h-5 w-5;
}

.modal-content {
    @apply flex-grow space-y-6 overflow-y-auto p-6;
}

.alert-section {
    @apply space-y-4;
}

.section-title {
    @apply text-lg font-medium text-gray-900 dark:text-white;
}

.info-grid {
    @apply grid grid-cols-1 gap-4 md:grid-cols-2;
}

.info-item {
    @apply flex items-center gap-2;
}

.info-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.info-value {
    @apply text-sm text-gray-900 dark:text-white;
}

.status-badge {
    @apply rounded-full px-2 py-1 text-xs font-medium;
}

.status-badge.open {
    @apply bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400;
}

.status-badge.acknowledged {
    @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400;
}

.status-badge.investigating {
    @apply bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400;
}

.status-badge.resolved {
    @apply bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400;
}

.alert-description {
    @apply text-sm leading-relaxed text-gray-700 dark:text-gray-300;
    @apply rounded-lg bg-gray-50 p-4 dark:bg-gray-700;
}

.metadata-grid {
    @apply space-y-2;
}

.metadata-item {
    @apply flex items-start gap-2 text-sm;
}

.metadata-label {
    @apply min-w-0 flex-shrink-0 font-medium text-gray-600 dark:text-gray-400;
}

.metadata-value {
    @apply break-all text-gray-900 dark:text-white;
}

.timeline {
    @apply space-y-4;
}

.timeline-item {
    @apply flex gap-3;
}

.timeline-marker {
    @apply mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-500;
}

.timeline-content {
    @apply flex-grow;
}

.timeline-title {
    @apply font-medium text-gray-900 dark:text-white;
}

.timeline-description {
    @apply mt-1 text-sm text-gray-600 dark:text-gray-400;
}

.timeline-time {
    @apply mt-1 text-xs text-gray-500 dark:text-gray-400;
}

.timeline-user {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.actions-list {
    @apply space-y-3;
}

.action-item {
    @apply flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700;
}

.action-icon {
    @apply mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400;
}

.action-content {
    @apply flex-grow;
}

.action-title {
    @apply font-medium text-gray-900 dark:text-white;
}

.action-description {
    @apply mt-1 text-sm text-gray-600 dark:text-gray-400;
}

.execute-button {
    @apply px-3 py-1 text-sm font-medium text-blue-600 dark:text-blue-400;
    @apply rounded-md hover:bg-blue-100 dark:hover:bg-blue-900/20;
    @apply transition-colors duration-200;
}

.related-alerts {
    @apply space-y-2;
}

.related-alert-item {
    @apply flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700;
    @apply cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600;
    @apply transition-colors duration-200;
}

.severity-dot {
    @apply h-2 w-2 flex-shrink-0 rounded-full;
}

.severity-dot.critical {
    @apply bg-red-500;
}

.severity-dot.high {
    @apply bg-orange-500;
}

.severity-dot.medium {
    @apply bg-yellow-500;
}

.severity-dot.low {
    @apply bg-blue-500;
}

.related-alert-title {
    @apply flex-grow text-sm font-medium text-gray-900 dark:text-white;
}

.related-alert-time {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.modal-footer {
    @apply border-t border-gray-200 p-6 dark:border-gray-700;
}

.footer-actions {
    @apply flex items-center justify-end gap-3;
}

.action-btn {
    @apply flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium;
    @apply transition-colors duration-200;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}

.action-btn.acknowledge {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-btn.resolve {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.action-btn.escalate {
    @apply bg-orange-600 text-white hover:bg-orange-700;
}

.action-btn.secondary {
    @apply bg-gray-200 text-gray-900 dark:bg-gray-600 dark:text-white;
    @apply hover:bg-gray-300 dark:hover:bg-gray-500;
}

.btn-icon {
    @apply h-4 w-4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .alert-modal {
        @apply m-2 max-w-full;
    }

    .modal-header {
        @apply p-4;
    }

    .modal-content {
        @apply p-4;
    }

    .modal-footer {
        @apply p-4;
    }

    .header-content {
        @apply flex-col gap-2;
    }

    .info-grid {
        @apply grid-cols-1;
    }

    .footer-actions {
        @apply w-full flex-col;
    }

    .action-btn {
        @apply w-full justify-center;
    }
}
</style>
