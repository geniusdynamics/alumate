<template>
    <AppLayout title="Analytics Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Analytics Dashboard
                </h2>
                <div class="flex items-center space-x-4">
                    <select 
                        v-model="selectedTimeframe" 
                        @change="updateTimeframe"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option v-for="(label, value) in availableTimeframes" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                    >
                        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ loading ? 'Refreshing...' : 'Refresh' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div v-for="(metric, key) in analytics.overview" :key="key" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                        <component :is="getMetricIcon(key)" class="w-5 h-5 text-white" />
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            {{ getMetricLabel(key) }}
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            {{ formatMetricValue(key, metric) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Key Performance Indicators</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="kpi in analytics.kpis" :key="kpi.key" class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900">{{ kpi.name }}</h4>
                                    <span :class="getStatusBadgeClass(kpi.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ kpi.status }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-bold text-gray-900">{{ kpi.formatted_value }}</span>
                                    <div class="flex items-center">
                                        <component :is="getTrendIcon(kpi.trend)" :class="getTrendColor(kpi.trend)" class="w-4 h-4" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Employment Trend Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Trend</h3>
                            <AnalyticsChart
                                :data="analytics.charts.employment_trend"
                                type="line"
                                :height="300"
                            />
                        </div>
                    </div>

                    <!-- Course Performance Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Course Performance</h3>
                            <AnalyticsChart
                                :data="analytics.charts.course_performance"
                                type="bar"
                                :height="300"
                            />
                        </div>
                    </div>

                    <!-- Job Market Activity Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Job Market Activity</h3>
                            <AnalyticsChart
                                :data="analytics.charts.job_market_activity"
                                type="area"
                                :height="300"
                            />
                        </div>
                    </div>

                    <!-- Application Funnel Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Application Funnel</h3>
                            <AnalyticsChart
                                :data="analytics.charts.application_funnel"
                                type="funnel"
                                :height="300"
                            />
                        </div>
                    </div>
                </div>

                <!-- Predictions Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8" v-if="analytics.predictions && analytics.predictions.length > 0">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Predictions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="model in analytics.predictions" :key="model.type" class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900">{{ model.name }}</h4>
                                    <span class="text-xs text-gray-500">{{ model.accuracy }}</span>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="prediction in model.recent_predictions.slice(0, 3)" :key="prediction.id" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">{{ prediction.subject }}</span>
                                        <span :class="getConfidenceColor(prediction.confidence)" class="px-2 py-1 text-xs rounded-full">
                                            {{ prediction.score }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" v-if="analytics.alerts && analytics.alerts.length > 0">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Alerts</h3>
                        <div class="space-y-4">
                            <div v-for="alert in analytics.alerts" :key="alert.type" :class="getAlertClass(alert.severity)" class="p-4 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <component :is="getAlertIcon(alert.severity)" :class="getAlertIconColor(alert.severity)" class="h-5 w-5" />
                                    </div>
                                    <div class="ml-3">
                                        <h3 :class="getAlertTitleColor(alert.severity)" class="text-sm font-medium">
                                            {{ alert.title }}
                                        </h3>
                                        <div :class="getAlertTextColor(alert.severity)" class="mt-2 text-sm">
                                            <p>{{ alert.message }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AnalyticsChart from '@/Components/AnalyticsChart.vue'
import { 
    UserGroupIcon, 
    BriefcaseIcon, 
    DocumentTextIcon, 
    BuildingOfficeIcon,
    TrendingUpIcon,
    TrendingDownIcon,
    MinusIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    analytics: Object,
    timeframe: String,
    availableTimeframes: Object,
})

const selectedTimeframe = ref(props.timeframe)
const loading = ref(false)

const updateTimeframe = () => {
    loading.value = true
    router.get(route('analytics.dashboard'), { timeframe: selectedTimeframe.value }, {
        preserveState: true,
        onFinish: () => loading.value = false
    })
}

const refreshData = () => {
    loading.value = true
    router.reload({
        onFinish: () => loading.value = false
    })
}

const getMetricIcon = (key) => {
    const icons = {
        graduates: UserGroupIcon,
        jobs: BriefcaseIcon,
        applications: DocumentTextIcon,
        employment_rate: TrendingUpIcon,
    }
    return icons[key] || UserGroupIcon
}

const getMetricLabel = (key) => {
    const labels = {
        graduates: 'Total Graduates',
        jobs: 'Active Jobs',
        applications: 'Applications',
        employment_rate: 'Employment Rate',
    }
    return labels[key] || key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatMetricValue = (key, value) => {
    if (key === 'employment_rate') {
        return `${value}%`
    }
    return new Intl.NumberFormat().format(value)
}

const getStatusBadgeClass = (status) => {
    const classes = {
        good: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        poor: 'bg-red-100 text-red-800',
        unknown: 'bg-gray-100 text-gray-800',
    }
    return classes[status] || classes.unknown
}

const getTrendIcon = (trend) => {
    const icons = {
        up: TrendingUpIcon,
        down: TrendingDownIcon,
        neutral: MinusIcon,
    }
    return icons[trend] || MinusIcon
}

const getTrendColor = (trend) => {
    const colors = {
        up: 'text-green-500',
        down: 'text-red-500',
        neutral: 'text-gray-500',
    }
    return colors[trend] || colors.neutral
}

const getConfidenceColor = (confidence) => {
    const colors = {
        high: 'bg-green-100 text-green-800',
        medium: 'bg-blue-100 text-blue-800',
        low: 'bg-yellow-100 text-yellow-800',
        very_low: 'bg-red-100 text-red-800',
    }
    return colors[confidence] || colors.low
}

const getAlertClass = (severity) => {
    const classes = {
        info: 'bg-blue-50 border border-blue-200',
        warning: 'bg-yellow-50 border border-yellow-200',
        critical: 'bg-red-50 border border-red-200',
    }
    return classes[severity] || classes.info
}

const getAlertIcon = (severity) => {
    const icons = {
        info: InformationCircleIcon,
        warning: ExclamationTriangleIcon,
        critical: XCircleIcon,
    }
    return icons[severity] || InformationCircleIcon
}

const getAlertIconColor = (severity) => {
    const colors = {
        info: 'text-blue-400',
        warning: 'text-yellow-400',
        critical: 'text-red-400',
    }
    return colors[severity] || colors.info
}

const getAlertTitleColor = (severity) => {
    const colors = {
        info: 'text-blue-800',
        warning: 'text-yellow-800',
        critical: 'text-red-800',
    }
    return colors[severity] || colors.info
}

const getAlertTextColor = (severity) => {
    const colors = {
        info: 'text-blue-700',
        warning: 'text-yellow-700',
        critical: 'text-red-700',
    }
    return colors[severity] || colors.info
}
</script>