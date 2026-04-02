<!-- ABOUTME: System health list component displaying health status of various system Components -->
<!-- ABOUTME: Shows health checks, service status, and system component monitoring with status indicators -->
<template>
    <div class="system-health-container">
        <div class="health-header">
            <h3 class="health-title">System Health</h3>
            <div class="health-controls">
                <select v-model="selectedStatus" @change="filterServices" class="status-filter">
                    <option value="all">All Services</option>
                    <option value="healthy">Healthy</option>
                    <option value="warning">Warning</option>
                    <option value="critical">Critical</option>
                    <option value="down">Down</option>
                </select>
                <button @click="refreshHealth" class="refresh-button" :disabled="loading">
                    <svg class="refresh-icon" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Health Overview -->
        <div class="health-overview">
            <div class="overview-stat healthy">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ healthStats.healthy }}</div>
                    <div class="stat-label">Healthy</div>
                </div>
            </div>

            <div class="overview-stat warning">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                        ></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ healthStats.warning }}</div>
                    <div class="stat-label">Warning</div>
                </div>
            </div>

            <div class="overview-stat critical">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ healthStats.critical }}</div>
                    <div class="stat-label">Critical</div>
                </div>
            </div>

            <div class="overview-stat down">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"
                        ></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ healthStats.down }}</div>
                    <div class="stat-label">Down</div>
                </div>
            </div>
        </div>

        <!-- Services List -->
        <div class="Services-list">
            <div v-if="loading" class="health-loading">
                <div class="loading-spinner"></div>
                <span>Loading system health...</span>
            </div>

            <div v-else-if="filteredServices.length === 0" class="no-Services">
                <svg class="no-Services-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>No Services found</p>
            </div>

            <div v-else class="Services-items">
                <div
                    v-for="service in filteredServices"
                    :key="service.id"
                    :class="['service-item', `service-${service.status}`]"
                    @click="handleServiceClick(service)"
                >
                    <div class="service-status">
                        <div :class="['status-indicator', service.status]"></div>
                    </div>

                    <div class="service-info">
                        <div class="service-header">
                            <div class="service-name">{{ service.name }}</div>
                            <div class="service-category">{{ service.category }}</div>
                        </div>
                        <div class="service-description">{{ service.description }}</div>
                        <div class="service-meta">
                            <span class="service-uptime">Uptime: {{ service.uptime }}%</span>
                            <span class="service-response">Response: {{ service.responseTime }}ms</span>
                            <span class="service-updated">{{ formatTime(service.lastCheck) }}</span>
                        </div>
                    </div>

                    <div class="service-metrics">
                        <div v-if="service.metrics" class="metrics-grid">
                            <div v-for="metric in service.metrics" :key="metric.name" class="metric-item">
                                <div class="metric-name">{{ metric.name }}</div>
                                <div class="metric-value">{{ formatMetricValue(metric.value, metric.unit) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="service-actions">
                        <button
                            v-if="service.status === 'down' || service.status === 'critical'"
                            @click.stop="restartService(service.id)"
                            class="action-button restart"
                            title="Restart Service"
                        >
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                        </button>

                        <button @click.stop="viewServiceDetails(service.id)" class="action-button details" title="View Details">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </button>

                        <button @click.stop="viewServiceLogs(service.id)" class="action-button logs" title="View Logs">
                            <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

interface ServiceMetric {
    name: string;
    value: number;
    unit: string;
}

interface SystemService {
    id: string;
    name: string;
    description: string;
    category: string;
    status: 'healthy' | 'warning' | 'critical' | 'down';
    uptime: number;
    responseTime: number;
    lastCheck: string;
    metrics?: ServiceMetric[];
    endpoint?: string;
}

interface Props {
    Services?: SystemService[];
    autoRefresh?: boolean;
    refreshInterval?: number;
}

interface Emits {
    'service-click': [service: SystemService];
    'service-restart': [serviceId: string];
    'service-details': [serviceId: string];
    'service-logs': [serviceId: string];
    refresh: [];
}

const props = withDefaults(defineProps<Props>(), {
    Services: () => [],
    autoRefresh: false,
    refreshInterval: 60000,
});

const emit = defineEmits<Emits>();

// Refs
const loading = ref(false);
const selectedStatus = ref('all');

// Sample data
const sampleServices = ref<SystemService[]>([
    {
        id: 'web-server',
        name: 'Web Server',
        description: 'Main application web server',
        category: 'Infrastructure',
        status: 'healthy',
        uptime: 99.9,
        responseTime: 45,
        lastCheck: new Date(Date.now() - 2 * 60 * 1000).toISOString(),
        metrics: [
            { name: 'CPU', value: 23, unit: 'percent' },
            { name: 'Memory', value: 2.1, unit: 'gb' },
        ],
        endpoint: 'https://api.example.com/health',
    },
    {
        id: 'database',
        name: 'Primary Database',
        description: 'PostgreSQL primary database instance',
        category: 'Database',
        status: 'warning',
        uptime: 98.5,
        responseTime: 120,
        lastCheck: new Date(Date.now() - 1 * 60 * 1000).toISOString(),
        metrics: [
            { name: 'Connections', value: 45, unit: 'count' },
            { name: 'Query Time', value: 89, unit: 'ms' },
        ],
    },
    {
        id: 'cache-server',
        name: 'Redis Cache',
        description: 'Redis caching server',
        category: 'Cache',
        status: 'healthy',
        uptime: 99.8,
        responseTime: 12,
        lastCheck: new Date(Date.now() - 30 * 1000).toISOString(),
        metrics: [
            { name: 'Hit Rate', value: 94.2, unit: 'percent' },
            { name: 'Memory', value: 512, unit: 'mb' },
        ],
    },
    {
        id: 'file-storage',
        name: 'File Storage',
        description: 'S3 compatible file storage service',
        category: 'Storage',
        status: 'critical',
        uptime: 95.2,
        responseTime: 890,
        lastCheck: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
        metrics: [
            { name: 'Usage', value: 78, unit: 'percent' },
            { name: 'IOPS', value: 1250, unit: 'count' },
        ],
    },
    {
        id: 'email-service',
        name: 'Email Service',
        description: 'SMTP email delivery service',
        category: 'Communication',
        status: 'down',
        uptime: 0,
        responseTime: 0,
        lastCheck: new Date(Date.now() - 10 * 60 * 1000).toISOString(),
    },
]);

const healthStats = ref({
    healthy: 2,
    warning: 1,
    critical: 1,
    down: 1,
});

// Computed
const ServicesData = computed(() => {
    return props.Services.length > 0 ? props.Services : sampleServices.value;
});

const filteredServices = computed(() => {
    let filtered = ServicesData.value;

    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter((service) => service.status === selectedStatus.value);
    }

    // Sort by status priority
    const statusOrder = { down: 0, critical: 1, warning: 2, healthy: 3 };

    return filtered.sort((a, b) => {
        const statusDiff = statusOrder[a.status] - statusOrder[b.status];
        if (statusDiff !== 0) return statusDiff;

        return a.name.localeCompare(b.name);
    });
});

// Methods
const formatTime = (timestamp: string): string => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const formatMetricValue = (value: number, unit: string): string => {
    switch (unit) {
        case 'percent':
            return `${value}%`;
        case 'ms':
            return `${value}ms`;
        case 'gb':
            return `${value}GB`;
        case 'mb':
            return `${value}MB`;
        case 'count':
            return value.toString();
        default:
            return value.toString();
    }
};

const filterServices = () => {
    // Filtering is handled by computed property
};

const refreshHealth = async () => {
    loading.value = true;
    try {
        emit('refresh');
        await new Promise((resolve) => setTimeout(resolve, 1000));

        // Update health stats
        const stats = { healthy: 0, warning: 0, critical: 0, down: 0 };
        ServicesData.value.forEach((service) => {
            stats[service.status]++;
        });
        healthStats.value = stats;
    } finally {
        loading.value = false;
    }
};

const handleServiceClick = (service: SystemService) => {
    emit('service-click', service);
};

const restartService = (serviceId: string) => {
    emit('service-restart', serviceId);
};

const viewServiceDetails = (serviceId: string) => {
    emit('service-details', serviceId);
};

const viewServiceLogs = (serviceId: string) => {
    emit('service-logs', serviceId);
};

// Lifecycle
onMounted(() => {
    if (props.autoRefresh) {
        setInterval(refreshHealth, props.refreshInterval);
    }
});
</script>

<style scoped>
.system-health-container {
    @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800;
}

.health-header {
    @apply mb-6 flex items-center justify-between;
}

.health-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.health-controls {
    @apply flex items-center gap-3;
}

.status-filter {
    @apply rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.refresh-button {
    @apply p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white;
    @apply rounded-md border border-gray-300 dark:border-gray-600;
    @apply hover:bg-gray-50 dark:hover:bg-gray-700;
    @apply transition-colors duration-200;
}

.refresh-icon {
    @apply h-4 w-4;
}

.health-overview {
    @apply mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4;
}

.overview-stat {
    @apply flex items-center gap-3 rounded-lg p-4;
}

.overview-stat.healthy {
    @apply border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.overview-stat.warning {
    @apply border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.overview-stat.critical {
    @apply border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.overview-stat.down {
    @apply border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20;
}

.stat-icon {
    @apply flex h-10 w-10 items-center justify-center rounded-lg;
}

.overview-stat.healthy .stat-icon {
    @apply bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400;
}

.overview-stat.warning .stat-icon {
    @apply bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400;
}

.overview-stat.critical .stat-icon {
    @apply bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400;
}

.overview-stat.down .stat-icon {
    @apply bg-gray-100 text-gray-600 dark:bg-gray-900/40 dark:text-gray-400;
}

.stat-icon svg {
    @apply h-5 w-5;
}

.stat-content {
    @apply flex-grow;
}

.stat-value {
    @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.stat-label {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.Services-list {
    @apply space-y-4;
}

.health-loading {
    @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
    @apply h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600;
}

.no-Services {
    @apply flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400;
}

.no-Services-icon {
    @apply mb-3 h-12 w-12 text-green-500;
}

.Services-items {
    @apply space-y-3;
}

.service-item {
    @apply flex cursor-pointer items-start gap-4 rounded-lg border p-4;
    @apply transition-all duration-200 ease-in-out;
    @apply hover:shadow-md;
}

.service-healthy {
    @apply border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.service-warning {
    @apply border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.service-critical {
    @apply border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.service-down {
    @apply border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20;
}

.service-status {
    @apply mt-1 flex-shrink-0;
}

.status-indicator {
    @apply h-3 w-3 rounded-full;
}

.status-indicator.healthy {
    @apply bg-green-500;
}

.status-indicator.warning {
    @apply bg-yellow-500;
}

.status-indicator.critical {
    @apply bg-red-500;
}

.status-indicator.down {
    @apply bg-gray-500;
}

.service-info {
    @apply min-w-0 flex-grow;
}

.service-header {
    @apply mb-1 flex items-center gap-3;
}

.service-name {
    @apply font-medium text-gray-900 dark:text-white;
}

.service-category {
    @apply rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400;
}

.service-description {
    @apply mb-2 text-sm text-gray-600 dark:text-gray-400;
}

.service-meta {
    @apply flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400;
}

.service-uptime,
.service-response,
.service-updated {
    @apply font-medium;
}

.service-metrics {
    @apply flex-shrink-0;
}

.metrics-grid {
    @apply grid grid-cols-2 gap-2;
}

.metric-item {
    @apply text-center;
}

.metric-name {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.metric-value {
    @apply text-sm font-medium text-gray-900 dark:text-white;
}

.service-actions {
    @apply flex items-center gap-2;
}

.action-button {
    @apply rounded-md p-1.5 transition-colors duration-200;
}

.action-button.restart {
    @apply text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/20;
}

.action-button.details {
    @apply text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900/20;
}

.action-button.logs {
    @apply text-purple-600 hover:bg-purple-100 dark:hover:bg-purple-900/20;
}

.action-icon {
    @apply h-4 w-4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .health-header {
        @apply flex-col items-start gap-3;
    }

    .health-overview {
        @apply grid-cols-1;
    }

    .service-item {
        @apply flex-col items-start gap-3;
    }

    .service-header {
        @apply flex-col items-start gap-1;
    }

    .service-meta {
        @apply flex-col items-start gap-1;
    }

    .service-actions {
        @apply w-full justify-end;
    }
}

@media (max-width: 640px) {
    .system-health-container {
        @apply p-4;
    }
}
</style>
