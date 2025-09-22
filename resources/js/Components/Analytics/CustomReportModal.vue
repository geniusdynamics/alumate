<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Generate Custom Report</h3>
                <button @click="closeModal" class="close-button">
                    <Icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Report Name</label>
                    <input v-model="reportConfig.name" type="text" class="form-input" placeholder="Enter report name" />
                </div>

                <div class="form-group">
                    <label class="form-label">Select Metrics</label>
                    <div class="metrics-grid">
                        <label v-for="metric in availableMetrics" :key="metric.key" class="metric-option">
                            <input v-model="reportConfig.metrics" type="checkbox" :value="metric.key" class="metric-checkbox" />
                            <div class="metric-content">
                                <span class="metric-name">{{ metric.label }}</span>
                                <span class="metric-description">{{ metric.description }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Date Range</label>
                    <div class="date-range">
                        <input v-model="reportConfig.start_date" type="date" class="form-input" />
                        <span class="date-separator">to</span>
                        <input v-model="reportConfig.end_date" type="date" class="form-input" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Filters (Optional)</label>
                    <div class="filters-grid">
                        <select v-model="reportConfig.graduation_year" class="form-select">
                            <option value="">All Years</option>
                            <option v-for="year in graduationYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>

                        <input v-model="reportConfig.location" type="text" class="form-input" placeholder="Location filter" />
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button @click="closeModal" class="btn btn-secondary">Cancel</button>
                <button @click="generateReport" class="btn btn-primary" :disabled="!canGenerate || generating">
                    <Icon v-if="generating" name="loader" class="h-4 w-4 animate-spin" />
                    {{ generating ? 'Generating...' : 'Generate Report' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { computed, reactive, ref } from 'vue';

const emit = defineEmits<{
    close: [];
    generate: [config: any];
}>();

const generating = ref(false);

const reportConfig = reactive({
    name: '',
    metrics: [] as string[],
    start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    graduation_year: '',
    location: '',
});

const availableMetrics = [
    {
        key: 'engagement_rate',
        label: 'Engagement Rate',
        description: 'Overall platform engagement percentage',
    },
    {
        key: 'active_users',
        label: 'Active Users',
        description: 'Number of users active in the period',
    },
    {
        key: 'new_users',
        label: 'New Users',
        description: 'New user registrations',
    },
    {
        key: 'posts_created',
        label: 'Posts Created',
        description: 'Total posts created by users',
    },
    {
        key: 'connections_made',
        label: 'Connections Made',
        description: 'New connections between alumni',
    },
    {
        key: 'events_attended',
        label: 'Events Attended',
        description: 'Event attendance statistics',
    },
];

const graduationYears = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let year = currentYear; year >= currentYear - 50; year--) {
        years.push(year);
    }
    return years;
});

const canGenerate = computed(() => {
    return reportConfig.name.trim() && reportConfig.metrics.length > 0;
});

const closeModal = () => {
    emit('close');
};

const generateReport = async () => {
    if (!canGenerate.value) return;

    generating.value = true;
    try {
        await emit('generate', { ...reportConfig });
    } finally {
        generating.value = false;
    }
};
</script>

<style scoped>
.modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.modal-container {
    @apply mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800;
}

.modal-header {
    @apply flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.modal-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-button {
    @apply text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300;
}

.modal-body {
    @apply space-y-6 p-6;
}

.form-group {
    @apply space-y-2;
}

.form-label {
    @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.form-select {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.metrics-grid {
    @apply grid grid-cols-1 gap-3 md:grid-cols-2;
}

.metric-option {
    @apply relative cursor-pointer;
}

.metric-checkbox {
    @apply sr-only;
}

.metric-content {
    @apply flex flex-col rounded-lg border-2 border-gray-200 p-3 dark:border-gray-600;
    @apply transition-colors hover:border-blue-300 dark:hover:border-blue-500;
}

.metric-option input:checked + .metric-content {
    @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.metric-name {
    @apply font-medium text-gray-900 dark:text-white;
}

.metric-description {
    @apply mt-1 text-sm text-gray-600 dark:text-gray-400;
}

.date-range {
    @apply flex items-center space-x-3;
}

.date-separator {
    @apply text-gray-500 dark:text-gray-400;
}

.filters-grid {
    @apply grid grid-cols-1 gap-3 md:grid-cols-2;
}

.modal-footer {
    @apply flex items-center justify-end space-x-3 border-t border-gray-200 p-6 dark:border-gray-700;
}

.btn {
    @apply inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-medium;
    @apply transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
    @apply border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500;
    @apply dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}
</style>











