<template>
    <div class="industry-insight-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Insight Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div 
                        :class="getInsightTypeClass(insight.type)"
                        class="w-12 h-12 rounded-lg flex items-center justify-center"
                    >
                        <component 
                            :is="getInsightIcon(insight.type)" 
                            class="w-6 h-6 text-white"
                        />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ insight.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ insight.industry }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span 
                    :class="getInsightPriorityClass(insight.priority)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatInsightPriority(insight.priority) }}
                </span>
            </div>
        </div>

        <!-- Insight Summary -->
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ insight.summary }}</p>
            
            <!-- Key Metrics -->
            <div v-if="insight.metrics && insight.metrics.length > 0" class="grid grid-cols-2 gap-4 mb-4">
                <div 
                    v-for="metric in insight.metrics.slice(0, 4)"
                    :key="metric.label"
                    class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ metric.value }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ metric.label }}</div>
                    <div v-if="metric.trend" :class="getTrendClass(metric.trend)" class="text-xs font-medium mt-1">
                        {{ formatTrend(metric.trend) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Points -->
        <div v-if="insight.key_points && insight.key_points.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Key Insights:</h4>
            <ul class="space-y-2">
                <li 
                    v-for="point in insight.key_points.slice(0, 3)"
                    :key="point"
                    class="flex items-start text-sm text-gray-600 dark:text-gray-400"
                >
                    <ChevronRightIcon class="w-4 h-4 text-blue-500 mr-2 flex-shrink-0 mt-0.5" />
                    {{ point }}
                </li>
                <li v-if="insight.key_points.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ insight.key_points.length - 3 }} more insights
                </li>
            </ul>
        </div>

        <!-- Skills in Demand -->
        <div v-if="insight.skills_in_demand && insight.skills_in_demand.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Skills in Demand:</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in insight.skills_in_demand.slice(0, 5)"
                    :key="skill"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="insight.skills_in_demand.length > 5"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ insight.skills_in_demand.length - 5 }}
                </span>
            </div>
        </div>

        <!-- Salary Information -->
        <div v-if="insight.salary_info" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Salary Insights</h4>
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
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Growth Opportunities:</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="opportunity in insight.growth_opportunities.slice(0, 3)"
                    :key="opportunity"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300"
                >
                    {{ opportunity }}
                </span>
                <span
                    v-if="insight.growth_opportunities.length > 3"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ insight.growth_opportunities.length - 3 }}
                </span>
            </div>
        </div>

        <!-- Source and Date -->
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
            <div class="flex items-center space-x-2">
                <span>Source: {{ insight.source || 'Industry Analysis' }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4" />
                <span>{{ formatDate(insight.published_at || insight.created_at) }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="viewDetails"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                View Full Report
            </button>
            
            <button
                @click="saveInsight"
                :class="insight.is_saved ? 'text-yellow-600 border-yellow-300' : 'text-gray-600 border-gray-300'"
                class="px-4 py-2 hover:text-yellow-800 border hover:border-yellow-400 rounded-md text-sm font-medium transition-colors dark:hover:text-yellow-200"
            >
                <BookmarkIcon class="w-4 h-4" />
            </button>
            
            <button
                @click="shareInsight"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 rounded-md text-sm font-medium transition-colors dark:text-gray-400 dark:hover:text-gray-200 dark:border-gray-600"
            >
                <ShareIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Related Industries -->
        <div v-if="insight.related_industries && insight.related_industries.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Related Industries</h5>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="industry in insight.related_industries.slice(0, 3)"
                    :key="industry"
                    @click="exploreIndustry(industry)"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    {{ industry }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns'
import {
    ChevronRightIcon,
    CalendarIcon,
    BookmarkIcon,
    ShareIcon,
    ArrowTrendingUpIcon,
    ChartBarIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CurrencyDollarIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    insight: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['view-details', 'save-insight', 'share-insight', 'explore-industry'])

const getInsightTypeClass = (type) => {
    const classes = {
        'market_trends': 'bg-blue-500',
        'salary_analysis': 'bg-green-500',
        'skills_demand': 'bg-purple-500',
        'job_growth': 'bg-orange-500',
        'industry_news': 'bg-red-500',
        'career_paths': 'bg-indigo-500'
    }
    return classes[type] || 'bg-gray-500'
}

const getInsightIcon = (type) => {
    const icons = {
        'market_trends': ArrowTrendingUpIcon,
        'salary_analysis': CurrencyDollarIcon,
        'skills_demand': AcademicCapIcon,
        'job_growth': ChartBarIcon,
        'industry_news': BuildingOfficeIcon,
        'career_paths': BriefcaseIcon
    }
    return icons[type] || ChartBarIcon
}

const getInsightPriorityClass = (priority) => {
    const classes = {
        'high': 'bg-red-100 text-red-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'low': 'bg-green-100 text-green-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const formatInsightPriority = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1)
}

const getTrendClass = (trend) => {
    if (trend > 0) return 'text-green-600'
    if (trend < 0) return 'text-red-600'
    return 'text-gray-600'
}

const formatTrend = (trend) => {
    if (trend > 0) return `+${trend}%`
    if (trend < 0) return `${trend}%`
    return 'No change'
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const viewDetails = () => {
    emit('view-details', props.insight.id)
}

const saveInsight = () => {
    emit('save-insight', props.insight.id)
}

const shareInsight = () => {
    emit('share-insight', props.insight.id)
}

const exploreIndustry = (industry) => {
    emit('explore-industry', industry)
}
</script>

<style scoped>
.industry-insight-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.industry-insight-card:hover {
    transform: translateY(-2px);
}
</style>
