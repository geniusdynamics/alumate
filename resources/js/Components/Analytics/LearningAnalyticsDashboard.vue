<template>
    <div class="learning-analytics-dashboard" role="region" aria-label="Learning analytics dashboard">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading learning analytics...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else class="learning-analytics-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Learning Analytics Dashboard</h1>
                    <p class="text-sm text-gray-600">
                        Track learning progress, outcomes, and performance insights
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="refreshData"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isLoading"
                        aria-label="Refresh learning analytics data"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Filters</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="date-from" class="block text-sm font-medium text-gray-700">From Date</label>
                        <input
                            id="date-from"
                            type="date"
                            v-model="dateFrom"
                            @change="updateDateRange"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select start date for learning analytics filter"
                        />
                    </div>
                    <div>
                        <label for="date-to" class="block text-sm font-medium text-gray-700">To Date</label>
                        <input
                            id="date-to"
                            type="date"
                            v-model="dateTo"
                            @change="updateDateRange"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select end date for learning analytics filter"
                        />
                    </div>
                    <div>
                        <label for="course-filter" class="block text-sm font-medium text-gray-700">Course</label>
                        <select
                            id="course-filter"
                            v-model="selectedCourseId"
                            @change="updateCourseFilter"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select course to filter learning progress"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in coursesList" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="user-filter" class="block text-sm font-medium text-gray-700">User ID</label>
                        <input
                            id="user-filter"
                            type="text"
                            v-model="selectedUserId"
                            @input="updateUserFilter"
                            placeholder="Enter user ID..."
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Filter learning progress by user ID"
                        />
                    </div>
                </div>
            </div>

            <!-- Key Metrics Overview -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Learners</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.totalLearners }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600">+{{ metrics.newLearnersThisMonth }}%</span>
                        <span class="ml-1 text-gray-500">this month</span>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Avg Progress</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.avgProgress }}%</p>
                        </div>
                        <div class="rounded-full bg-purple-100 p-2">
                            <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600">+{{ metrics.progressChange }}%</span>
                        <span class="ml-1 text-gray-500">vs last period</span>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Completion Rate</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.completionRate }}%</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-2">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600">+{{ metrics.completionChange }}%</span>
                        <span class="ml-1 text-gray-500">vs last period</span>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Avg Engagement</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.avgEngagement }}/100</p>
                        </div>
                        <div class="rounded-full bg-orange-100 p-2">
                            <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600">+{{ metrics.engagementChange }}%</span>
                        <span class="ml-1 text-gray-500">vs last period</span>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Certifications</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.certifications }}</p>
                        </div>
                        <div class="rounded-full bg-indigo-100 p-2">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-green-600">+{{ metrics.certificationChange }}%</span>
                        <span class="ml-1 text-gray-500">vs last period</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Learning Progress Over Time</h3>
                    <div class="h-64">
                        <canvas ref="progressChartRef" aria-label="Learning progress over time chart" role="img"></canvas>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Course Performance Comparison</h3>
                    <div class="h-64">
                        <canvas ref="performanceChartRef" aria-label="Course performance comparison chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            <!-- Learning Outcome Analysis -->
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-green-900">Strengths</h3>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="(strength, index) in outcomeAnalysis.strengths" :key="index" class="flex items-start">
                            <span class="h-2 w-2 rounded-full bg-green-500 mt-2 mr-2 flex-shrink-0"></span>
                            <span class="text-sm text-green-800">{{ strength }}</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-red-900">Areas for Improvement</h3>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="(weakness, index) in outcomeAnalysis.weaknesses" :key="index" class="flex items-start">
                            <span class="h-2 w-2 rounded-full bg-red-500 mt-2 mr-2 flex-shrink-0"></span>
                            <span class="text-sm text-red-800">{{ weakness }}</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Skills Distribution</h3>
                    <div class="h-64">
                        <canvas ref="skillsChartRef" aria-label="Skills distribution chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            <!-- Completion Predictions -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Completion Predictions</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div v-for="prediction in completionPredictions" :key="prediction.courseId" class="rounded-lg bg-gray-50 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-900">{{ prediction.courseName }}</h4>
                            <span class="text-xs px-2 py-1 rounded-full" :class="prediction.predictionClass">
                                {{ prediction.status }}
                            </span>
                        </div>
                        <div class="mb-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600">Predicted Completion</span>
                                <span class="font-medium text-gray-900">{{ prediction.predictedCompletion }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="h-2 rounded-full"
                                    :class="prediction.barClass"
                                    :style="{ width: `${prediction.predictedCompletion}%` }"
                                ></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Est. Date:</span>
                            <span class="font-medium text-gray-900">{{ prediction.estimatedDate }}</span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-gray-600">Confidence:</span>
                            <span class="font-medium text-gray-900">{{ prediction.confidence }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Learning Recommendations -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Personalized Recommendations</h3>
                <div class="space-y-3">
                    <div
                        v-for="recommendation in recommendations"
                        :key="recommendation.id"
                        class="flex items-start p-3 rounded-lg"
                        :class="recommendation.bgClass"
                    >
                        <div class="flex-shrink-0 mr-3">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full" :class="recommendation.iconClass">
                                <svg class="h-4 w-4" :class="recommendation.iconTextClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="recommendation.iconPath" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900">{{ recommendation.title }}</h4>
                            <p class="text-sm mt-1 text-gray-700">{{ recommendation.description }}</p>
                            <div class="flex items-center mt-2 space-x-2">
                                <span class="text-xs px-2 py-1 rounded-full" :class="recommendation.priorityClass">
                                    {{ recommendation.priority }}
                                </span>
                                <span class="text-xs text-gray-500">{{ recommendation.impact }}</span>
                            </div>
                        </div>
                        <button
                            class="ml-4 text-sm px-3 py-1 rounded border"
                            :class="recommendation.actionClass"
                            aria-label="Apply recommendation"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </div>

            <!-- Performance Comparison Table -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Performance Comparison</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Learning performance comparison">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Period</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Previous Period</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="row in performanceComparison" :key="row.metric">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ row.metric }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ row.current }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ row.previous }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :class="row.changeClass"
                                    >
                                        {{ row.change }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <svg v-if="row.trend === 'up'" class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                    <svg v-else-if="row.trend === 'down'" class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                                    </svg>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Real-time Updates Indicator -->
            <div v-if="isRealTimeEnabled" class="fixed bottom-4 right-4">
                <div class="flex items-center bg-gray-900 text-white px-3 py-2 rounded-full shadow-lg">
                    <span class="relative flex h-2 w-2 mr-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-xs">Live updates</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useLearningStore } from '../../Stores/useLearningStore';
import type { Course } from '../../types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    userId?: string;
    courseId?: string;
    dateRange?: { from: string; to: string };
    enableRealTime?: boolean;
}>(), {
    enableRealTime: true,
});

// Store
const learningStore = useLearningStore();

// Local reactive state
const dateFrom = ref('');
const dateTo = ref('');
const selectedCourseId = ref('');
const selectedUserId = ref('');
const isRealTimeEnabled = ref(true);

// Chart refs
const progressChartRef = ref<HTMLCanvasElement>();
const performanceChartRef = ref<HTMLCanvasElement>();
const skillsChartRef = ref<HTMLCanvasElement>();

let progressChart: Chart | null = null;
let performanceChart: Chart | null = null;
let skillsChart: Chart | null = null;

// Computed properties from store
const isLoading = computed(() => learningStore.isLoading);
const error = computed(() => learningStore.error);
const filteredProgress = computed(() => learningStore.filteredProgress);

// Mock courses list for dropdown
const coursesList = ref<Course[]>([
    { id: 'course-1', tenant_id: '1', name: 'Introduction to Data Science', modules_count: 10 },
    { id: 'course-2', tenant_id: '1', name: 'Machine Learning Fundamentals', modules_count: 12 },
    { id: 'course-3', tenant_id: '1', name: 'Advanced Analytics', modules_count: 8 },
]);

// Metrics data
const metrics = computed(() => ({
    totalLearners: 1247,
    newLearnersThisMonth: 12.5,
    avgProgress: 68,
    progressChange: 5.2,
    completionRate: 72,
    completionChange: 3.8,
    avgEngagement: 82,
    engagementChange: 4.1,
    certifications: 892,
    certificationChange: 8.3,
}));

// Outcome analysis
const outcomeAnalysis = computed(() => ({
    strengths: [
        'High engagement in video content (87% completion)',
        'Strong performance in practical exercises',
        'Active participation in discussion forums',
        'Consistent weekly learning habits',
        'Excellent peer collaboration scores',
    ],
    weaknesses: [
        'Low quiz completion rates in Module 3',
        'Struggle with time-based assessments',
        'Incomplete optional reading materials',
        'Need improvement in final project submissions',
        'Limited use of supplementary resources',
    ],
}));

// Completion predictions
const completionPredictions = computed(() => [
    {
        courseId: 'course-1',
        courseName: 'Introduction to Data Science',
        predictedCompletion: 85,
        estimatedDate: '2024-03-15',
        confidence: 92,
        status: 'On Track',
        predictionClass: 'bg-green-100 text-green-800',
        barClass: 'bg-green-500',
    },
    {
        courseId: 'course-2',
        courseName: 'Machine Learning Fundamentals',
        predictedCompletion: 62,
        estimatedDate: '2024-04-20',
        confidence: 78,
        status: 'At Risk',
        predictionClass: 'bg-yellow-100 text-yellow-800',
        barClass: 'bg-yellow-500',
    },
    {
        courseId: 'course-3',
        courseName: 'Advanced Analytics',
        predictedCompletion: 45,
        estimatedDate: '2024-05-10',
        confidence: 65,
        status: 'Needs Attention',
        predictionClass: 'bg-red-100 text-red-800',
        barClass: 'bg-red-500',
    },
]);

// Recommendations type
interface Recommendation {
    id: string;
    title: string;
    description: string;
    priority: string;
    impact: string;
    priorityClass: string;
    bgClass: string;
    iconClass: string;
    iconTextClass: string;
    iconPath: string;
    actionClass: string;
}

// Recommendations
const recommendations = computed<Recommendation[]>(() => [
    {
        id: 'rec-1',
        title: 'Complete Module 3 Quiz',
        description: 'You have 3 pending quizzes in Module 3. Completing them will unlock the final project.',
        priority: 'High',
        impact: '+15% course completion',
        priorityClass: 'bg-red-100 text-red-800',
        bgClass: 'bg-red-50',
        iconClass: 'bg-red-100',
        iconTextClass: 'text-red-600',
        iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        actionClass: 'border-red-300 text-red-700 hover:bg-red-100',
    },
    {
        id: 'rec-2',
        title: 'Join Study Group',
        description: 'A new study group for Machine Learning topics is forming. Join to improve your understanding.',
        priority: 'Medium',
        impact: '+8% engagement score',
        priorityClass: 'bg-yellow-100 text-yellow-800',
        bgClass: 'bg-yellow-50',
        iconClass: 'bg-yellow-100',
        iconTextClass: 'text-yellow-600',
        iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        actionClass: 'border-yellow-300 text-yellow-700 hover:bg-yellow-100',
    },
    {
        id: 'rec-3',
        title: 'Review Optional Materials',
        description: 'You\'ve completed 60% of core materials. Reviewing optional resources can boost your certification score.',
        priority: 'Low',
        impact: '+5% certification score',
        priorityClass: 'bg-blue-100 text-blue-800',
        bgClass: 'bg-blue-50',
        iconClass: 'bg-blue-100',
        iconTextClass: 'text-blue-600',
        iconPath: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        actionClass: 'border-blue-300 text-blue-700 hover:bg-blue-100',
    },
]);

// Performance comparison
interface PerformanceRow {
    metric: string;
    current: string;
    previous: string;
    change: string;
    changeClass: string;
    trend: 'up' | 'down' | 'neutral';
}

const performanceComparison = computed<PerformanceRow[]>(() => [
    { metric: 'Active Learners', current: '1,247', previous: '1,102', change: '+13.2%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Avg Course Progress', current: '68%', previous: '62%', change: '+9.7%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Completion Rate', current: '72%', previous: '68%', change: '+5.9%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Engagement Score', current: '82/100', previous: '78/100', change: '+5.1%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Certifications', current: '892', previous: '756', change: '+18.0%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Dropout Rate', current: '8%', previous: '12%', change: '-33.3%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
    { metric: 'Avg Time per Session', current: '45 min', previous: '38 min', change: '+18.4%', changeClass: 'bg-green-100 text-green-800', trend: 'up' },
]);

// Methods
const updateDateRange = () => {
    learningStore.updateFilters({
        dateRange: {
            from: dateFrom.value,
            to: dateTo.value,
        },
    });
};

const updateCourseFilter = () => {
    learningStore.updateFilters({ courseId: selectedCourseId.value });
};

const updateUserFilter = () => {
    learningStore.updateFilters({ userId: selectedUserId.value });
};

const refreshData = async () => {
    try {
        await learningStore.fetchProgress({
            date_from: dateFrom.value,
            date_to: dateTo.value,
            course_id: selectedCourseId.value,
            user_id: selectedUserId.value,
        });
        nextTick(() => updateCharts());
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

const updateCharts = () => {
    updateProgressChart();
    updatePerformanceChart();
    updateSkillsChart();
};

const updateProgressChart = () => {
    if (!progressChartRef.value) return;

    const ctx = progressChartRef.value.getContext('2d');
    if (!ctx) return;

    if (progressChart) {
        progressChart.destroy();
    }

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const progressData = [45, 52, 58, 65, 68, 72];
    const targetData = [50, 60, 70, 80, 90, 100];

    progressChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Actual Progress',
                    data: progressData,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Target Progress',
                    data: targetData,
                    borderColor: 'rgba(156, 163, 175, 1)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
            },
            plugins: {
                legend: {
                    position: 'bottom' as const,
                },
            },
        },
    });
};

const updatePerformanceChart = () => {
    if (!performanceChartRef.value) return;

    const ctx = performanceChartRef.value.getContext('2d');
    if (!ctx) return;

    if (performanceChart) {
        performanceChart.destroy();
    }

    const courses = ['Data Science', 'ML Basics', 'Analytics', 'Statistics', 'Visualization'];
    const completionRates = [85, 72, 68, 78, 82];
    const engagementScores = [88, 75, 70, 80, 85];

    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: courses,
            datasets: [
                {
                    label: 'Completion Rate (%)',
                    data: completionRates,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                },
                {
                    label: 'Engagement Score',
                    data: engagementScores,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                },
            },
            plugins: {
                legend: {
                    position: 'bottom' as const,
                },
            },
        },
    });
};

const updateSkillsChart = () => {
    if (!skillsChartRef.value) return;

    const ctx = skillsChartRef.value.getContext('2d');
    if (!ctx) return;

    if (skillsChart) {
        skillsChart.destroy();
    }

    const skills = ['Python', 'SQL', 'Statistics', 'ML', 'Visualization', 'Communication'];
    const proficiency = [85, 78, 72, 68, 82, 75];

    skillsChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: skills,
            datasets: [
                {
                    label: 'Proficiency Level',
                    data: proficiency,
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
            },
        },
    });
};

// Lifecycle
onMounted(async () => {
    // Initialize filters from props
    if (props.dateRange?.from) {
        dateFrom.value = props.dateRange.from;
        dateTo.value = props.dateRange.to;
    }
    if (props.courseId) {
        selectedCourseId.value = props.courseId;
    }
    if (props.userId) {
        selectedUserId.value = props.userId;
    }

    // Update filters
    learningStore.updateFilters({
        dateRange: props.dateRange,
        courseId: props.courseId,
        userId: props.userId,
    });

    // Enable real-time updates if configured
    if (props.enableRealTime) {
        isRealTimeEnabled.value = true;
    }

    // Fetch data and initialize charts
    await refreshData();
});

onUnmounted(() => {
    if (progressChart) progressChart.destroy();
    if (performanceChart) performanceChart.destroy();
    if (skillsChart) skillsChart.destroy();
});
</script>

<style scoped>
.learning-analytics-dashboard {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.learning-analytics-dashboard button:focus,
.learning-analytics-dashboard input:focus,
.learning-analytics-dashboard select:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive design */
@media (max-width: 768px) {
    .learning-analytics-container {
        @apply px-2;
    }
}
</style>
