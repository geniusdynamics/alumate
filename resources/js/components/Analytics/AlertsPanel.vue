<template>
    <div class="alerts-panel">
        <div class="panel-header">
            <h3 class="panel-title">
                <Icon name="alert-triangle" class="h-5 w-5" />
                Analytics Alerts
            </h3>
            <button v-if="alerts.length > 3" @click="showAll = !showAll" class="toggle-button">
                {{ showAll ? 'Show Less' : `Show All (${alerts.length})` }}
            </button>
        </div>

        <div class="alerts-list">
            <div v-for="(alert, index) in displayedAlerts" :key="index" class="alert-item" :class="alertTypeClass(alert.type)">
                <div class="alert-icon">
                    <Icon :name="getAlertIcon(alert.type)" class="h-5 w-5" />
                </div>

                <div class="alert-content">
                    <p class="alert-message">{{ alert.message }}</p>
                    <div class="alert-details">
                        <span class="alert-metric">{{ alert.metric }}</span>
                        <span class="alert-value">{{ formatValue(alert.value) }}</span>
                    </div>
                </div>

                <button @click="dismissAlert(index)" class="dismiss-button">
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div v-if="alerts.length === 0" class="no-alerts">
            <Icon name="check-circle" class="h-8 w-8 text-green-500" />
            <p class="no-alerts-text">All systems are performing well!</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { computed, ref } from 'vue';

interface Alert {
    type: 'success' | 'warning' | 'info' | 'error';
    message: string;
    metric: string;
    value: number | string;
}

interface Props {
    alerts: Alert[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    dismiss: [index: number];
}>();

const showAll = ref(false);

const displayedAlerts = computed(() => {
    if (showAll.value || props.alerts.length <= 3) {
        return props.alerts;
    }
    return props.alerts.slice(0, 3);
});

const alertTypeClass = (type: string) => {
    const classes = {
        success: 'alert-success',
        warning: 'alert-warning',
        info: 'alert-info',
        error: 'alert-error',
    };
    return classes[type as keyof typeof classes] || 'alert-info';
};

const getAlertIcon = (type: string) => {
    const icons = {
        success: 'check-circle',
        warning: 'alert-triangle',
        info: 'info',
        error: 'alert-circle',
    };
    return icons[type as keyof typeof icons] || 'info';
};

const formatValue = (value: number | string) => {
    if (typeof value === 'number') {
        return value.toLocaleString();
    }
    return value;
};

const dismissAlert = (index: number) => {
    emit('dismiss', index);
};
</script>

<style scoped>
.alerts-panel {
    @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800;
}

.panel-header {
    @apply mb-4 flex items-center justify-between;
}

.panel-title {
    @apply flex items-center space-x-2 text-lg font-semibold text-gray-900 dark:text-white;
}

.toggle-button {
    @apply text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300;
    @apply font-medium transition-colors;
}

.alerts-list {
    @apply space-y-3;
}

.alert-item {
    @apply flex items-start space-x-3 rounded-lg border-l-4 p-4;
    @apply transition-all duration-200 hover:shadow-sm;
}

.alert-success {
    @apply border-l-green-500 bg-green-50 dark:bg-green-900/10;
}

.alert-warning {
    @apply border-l-yellow-500 bg-yellow-50 dark:bg-yellow-900/10;
}

.alert-info {
    @apply border-l-blue-500 bg-blue-50 dark:bg-blue-900/10;
}

.alert-error {
    @apply border-l-red-500 bg-red-50 dark:bg-red-900/10;
}

.alert-icon {
    @apply mt-0.5 flex-shrink-0;
}

.alert-success .alert-icon {
    @apply text-green-600 dark:text-green-400;
}

.alert-warning .alert-icon {
    @apply text-yellow-600 dark:text-yellow-400;
}

.alert-info .alert-icon {
    @apply text-blue-600 dark:text-blue-400;
}

.alert-error .alert-icon {
    @apply text-red-600 dark:text-red-400;
}

.alert-content {
    @apply min-w-0 flex-1;
}

.alert-message {
    @apply mb-1 text-sm font-medium text-gray-900 dark:text-white;
}

.alert-details {
    @apply flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400;
}

.alert-metric {
    @apply font-medium;
}

.alert-value {
    @apply rounded bg-gray-100 px-2 py-1 dark:bg-gray-700;
}

.dismiss-button {
    @apply flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
    @apply rounded p-1 transition-colors;
}

.no-alerts {
    @apply flex flex-col items-center justify-center py-8 text-center;
}

.no-alerts-text {
    @apply mt-2 text-gray-600 dark:text-gray-400;
}
</style>











