<template>
    <div
        class="industry-insight-card rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Insight Header -->
        <div class="mb-4 flex items-start justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div :class="getInsightTypeClass(insight.type)" class="flex h-12 w-12 items-center justify-center rounded-lg">
                        <component :is="getInsightIcon(insight.type)" class="h-6 w-6 text-white" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ insight.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ insight.industry }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span :class="getInsightPriorityClass(insight.priority)" class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                    {{ formatInsightPriority(insight.priority) }}
                </span>
            </div>
        </div>

        <!-- Insight Summary -->
        <div class="mb-4">
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">{{ insight.summary }}</p>

            <!-- Key Metrics -->
            <div v-if="insight.metrics && insight.metrics.length > 0" class="mb-4 grid grid-cols-2 gap-4">
                <div v-for="metric in insight.metrics.slice(0, 4)" :key="metric.label" class="rounded-md bg-gray-50 p-3 text-center dark:bg-gray-700">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ metric.value }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ metric.label }}</div>
                    <div v-if="metric.trend" :class="getTrendClass(metric.trend)" class="mt-1 text-xs font-medium">
                        {{ formatTrend(metric.trend) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Points -->
        <div v-if="insight.key_points && insight.key_points.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Key Insights:</h4>
            <ul class="space-y-2">
                <li v-for="point in insight.key_points.slice(0, 3)" :key="point" class="flex items-start text-sm text-gray-600 dark:text-gray-400">
                    <ChevronRightIcon class="mr-2 mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" />
                    {{ point }}
                </li>
                <li v-if="insight.key_points.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ insight.key_points.length - 3 }} more insights
                </li>
            </ul>
        </div>

        <!-- Skills in Demand -->
        <div v-if="insight.skills_in_demand && insight.skills_in_demand.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Skills in Demand:</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in insight.skills_in_demand.slice(0, 5)"
                    :key="skill"
                    class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs text-green-700 dark:bg-green-900/20 dark:text-green-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="insight.skills_in_demand.length > 5"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ insight.skills_in_demand.length - 5 }}
                </span>
            </div>
        </div>

        <!-- Salary Information -->
        <div v-if="insight.salary_info" class="mb-4 rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Salary Insights</h4>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ insight.salary_info.entry_level || 'N/A' }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Entry Level</div>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ insight.salary_info.mid_level || 'N/A' }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Mid Level</div>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ insight.salary_info.senior_level || 'N/A' }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Senior Level</div>
                </div>
            </div>
        </div>

        <!-- Growth Opportunities -->
        <div v-if="insight.growth_opportunities && insight.growth_opportunities.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Growth Opportunities:</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="opportunity in insight.growth_opportunities.slice(0, 3)"
                    :key="opportunity"
                    class="inline-flex items-center rounded-md bg-purple-100 px-2 py-1 text-xs text-purple-700 dark:bg-purple-900/20 dark:text-purple-300"
                >
                    {{ opportunity }}
                </span>
                <span
                    v-if="insight.growth_opportunities.length > 3"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ insight.growth_opportunities.length - 3 }}
                </span>
            </div>
        </div>

        <!-- Source and Date -->
        <div class="mb-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center space-x-2">
                <span>Source: {{ insight.source || 'Industry Analysis' }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <CalendarIcon class="h-4 w-4" />
                <span>{{ formatDate(insight.published_at || insight.created_at) }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="viewDetails"
                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
                View Full Report
            </button>

            <button
                @click="saveInsight"
                :class="insight.is_saved ? 'border-yellow-300 text-yellow-600' : 'border-gray-300 text-gray-600'"
                class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:border-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-200"
            >
                <BookmarkIcon class="h-4 w-4" />
            </button>

            <button
                @click="shareInsight"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:border-gray-400 hover:text-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <ShareIcon class="h-4 w-4" />
            </button>
        </div>

        <!-- Related Industries -->
        <div
            v-if="insight.related_industries && insight.related_industries.length > 0"
            class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700"
        >
            <h5 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Related Industries</h5>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="industry in insight.related_industries.slice(0, 3)"
                    :key="industry"
                    @click="exploreIndustry(industry)"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    {{ industry }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    AcademicCapIcon,
    ArrowTrendingUpIcon,
    BookmarkIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ChartBarIcon,
    ChevronRightIcon,
    CurrencyDollarIcon,
    ShareIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const props = defineProps({
    insight: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['view-details', 'save-insight', 'share-insight', 'explore-industry']);

const getInsightTypeClass = (type) => {
    const classes = {
        market_trends: 'bg-blue-500',
        salary_analysis: 'bg-green-500',
        skills_demand: 'bg-purple-500',
        job_growth: 'bg-orange-500',
        industry_news: 'bg-red-500',
        career_paths: 'bg-indigo-500',
    };
    return classes[type] || 'bg-gray-500';
};

const getInsightIcon = (type) => {
    const icons = {
        market_trends: ArrowTrendingUpIcon,
        salary_analysis: CurrencyDollarIcon,
        skills_demand: AcademicCapIcon,
        job_growth: ChartBarIcon,
        industry_news: BuildingOfficeIcon,
        career_paths: BriefcaseIcon,
    };
    return icons[type] || ChartBarIcon;
};

const getInsightPriorityClass = (priority) => {
    const classes = {
        high: 'bg-red-100 text-red-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-green-100 text-green-800',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
};

const formatInsightPriority = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1);
};

const getTrendClass = (trend) => {
    if (trend > 0) return 'text-green-600';
    if (trend < 0) return 'text-red-600';
    return 'text-gray-600';
};

const formatTrend = (trend) => {
    if (trend > 0) return `+${trend}%`;
    if (trend < 0) return `${trend}%`;
    return 'No change';
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const viewDetails = () => {
    emit('view-details', props.insight.id);
};

const saveInsight = () => {
    emit('save-insight', props.insight.id);
};

const shareInsight = () => {
    emit('share-insight', props.insight.id);
};

const exploreIndustry = (industry) => {
    emit('explore-industry', industry);
};
</script>

<style scoped>
.industry-insight-card {
    transition:
        transform 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;
}

.industry-insight-card:hover {
    transform: translateY(-2px);
}
</style>
