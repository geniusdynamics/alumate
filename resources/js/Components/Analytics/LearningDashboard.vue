<template>
    <div class="learning-dashboard" role="region" aria-label="Learning analytics dashboard">
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
        <div v-else class="learning-dashboard-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Learning Analytics</h1>
                    <p class="text-sm text-gray-600">
                        Track student progress, engagement, and certification across courses
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
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Filters & Search</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <!-- Date Range -->
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

                    <!-- Course Filter -->
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
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <!-- User Filter -->
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

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input
                            id="search"
                            type="text"
                            v-model="searchQuery"
                            @input="updateSearch"
                            placeholder="Search courses..."
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Search learning progress by course name or user ID"
                        />
                    </div>
                </div>
            </div>

            <!-- Aggregate Visualization -->
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Completion Rates by Course -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Completion Rates by Course</h3>
                    <div class="h-64">
                        <canvas ref="completionChartRef" aria-label="Course completion rates chart" role="img"></canvas>
                    </div>
                </div>

                <!-- Engagement Trends -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Engagement Trends</h3>
                    <div class="h-64">
                        <canvas ref="engagementChartRef" aria-label="Engagement trends chart" role="img"></canvas>
                    </div>
                </div>

                <!-- Certification Overview -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Certification Overview</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Progress Records</span>
                            <span class="text-lg font-semibold text-gray-900">{{ learningStats.totalProgress }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Certified</span>
                            <span class="text-lg font-semibold text-green-600">{{ learningStats.certifiedCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Certification Rate</span>
                            <span class="text-lg font-semibold text-blue-600">{{ learningStats.certificationRate }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Avg Engagement</span>
                            <span class="text-lg font-semibold text-purple-600">{{ learningStats.avgEngagement }}/100</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Cards -->
            <div class="mb-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Student Progress ({{ filteredProgress.length }})
                </h3>

                <!-- Empty State -->
                <div v-if="filteredProgress.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No learning progress found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Try adjusting your filters or check back later for new progress data.
                    </p>
                </div>

                <!-- Progress Grid -->
                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="progress in filteredProgress"
                        :key="progress.id"
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow"
                        role="article"
                        :aria-label="`Progress for ${getCourseName(progress.course_id)}`"
                    >
                        <!-- Course Name -->
                        <div class="flex items-start justify-between">
                            <h4 class="text-sm font-medium text-gray-900">{{ getCourseName(progress.course_id) }}</h4>
                            <span v-if="progress.certified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Certified
                            </span>
                        </div>

                        <!-- Progress Info -->
                        <div class="mt-3 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Modules:</span>
                                <span class="font-medium">{{ progress.modules_completed }}/{{ getCourseModules(progress.course_id) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full"
                                    :style="{ width: `${getCompletionPercentage(progress)}%` }"
                                    :aria-label="`${getCompletionPercentage(progress)}% complete`"
                                ></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Engagement:</span>
                                <span class="font-medium text-blue-600">{{ progress.engagement_score || 0 }}/100</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex items-center justify-between">
                            <button
                                @click="openDetailsModal(progress)"
                                class="text-sm text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded px-2 py-1"
                                aria-label="View detailed progress"
                            >
                                Details
                            </button>
                            <button
                                v-if="canVerifyCertification(progress)"
                                @click="verifyCertification(progress.id)"
                                class="text-sm text-green-600 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded px-2 py-1"
                                aria-label="Verify certification"
                            >
                                Verify
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div
            v-if="showDetailsModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            :aria-label="`Progress details for ${selectedProgress ? getCourseName(selectedProgress.course_id) : ''}`"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDetailsModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div v-if="selectedProgress">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ getCourseName(selectedProgress.course_id) }} Progress
                        </h3>

                        <!-- Progress Charts -->
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Module Completion</h4>
                                <div class="h-32">
                                    <canvas ref="moduleChartRef" aria-label="Module completion chart" role="img"></canvas>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Engagement Score Trend</h4>
                                <div class="h-32">
                                    <canvas ref="scoreChartRef" aria-label="Engagement score trend chart" role="img"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button
                                @click="closeDetailsModal"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useLearningStore } from '../../Stores/useLearningStore';
import { useLearning } from '../../Composables/useLearning';
import type { LearningProgress } from '../../Types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    progressData?: LearningProgress[];
    courses?: any[];
    filters?: { dateRange?: { from: string; to: string }; courseId?: string; userId?: string };
}>(), {
    progressData: () => [],
    courses: () => [],
    filters: () => ({}),
});

// Composables
const learningStore = useLearningStore();
const { filteredProgress, learningStats, isLoading, error, courses } = learningStore;

// Local reactive state
const dateFrom = ref('');
const dateTo = ref('');
const selectedCourseId = ref('');
const selectedUserId = ref('');
const searchQuery = ref('');

// Modal state
const showDetailsModal = ref(false);
const selectedProgress = ref<LearningProgress | null>(null);

// Chart refs
const completionChartRef = ref<HTMLCanvasElement>();
const engagementChartRef = ref<HTMLCanvasElement>();
const moduleChartRef = ref<HTMLCanvasElement>();
const scoreChartRef = ref<HTMLCanvasElement>();

let completionChart: Chart | null = null;
let engagementChart: Chart | null = null;
let moduleChart: Chart | null = null;
let scoreChart: Chart | null = null;

// Computed properties
const getCourseName = (courseId: string) => {
    const course = courses.find(c => c.id === courseId);
    return course?.name || 'Unknown Course';
};

const getCourseModules = (courseId: string) => {
    const course = courses.find(c => c.id === courseId);
    return course?.modules_count || 0;
};

const getCompletionPercentage = (progress: LearningProgress) => {
    const totalModules = getCourseModules(progress.course_id);
    return totalModules > 0 ? Math.round((progress.modules_completed / totalModules) * 100) : 0;
};

const canVerifyCertification = (progress: LearningProgress) => {
    const completionRate = getCompletionPercentage(progress);
    const engagementScore = progress.engagement_score || 0;
    return !progress.certified && completionRate >= 70 && engagementScore >= 70;
};

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

const updateSearch = () => {
    learningStore.updateFilters({ search: searchQuery.value });
};

const refreshData = async () => {
    try {
        await learningStore.fetchProgress();
        nextTick(() => updateCharts());
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

const openDetailsModal = (progress: LearningProgress) => {
    selectedProgress.value = progress;
    showDetailsModal.value = true;
    nextTick(() => updateModalCharts());
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedProgress.value = null;
    if (moduleChart) moduleChart.destroy();
    if (scoreChart) scoreChart.destroy();
};

const verifyCertification = async (progressId: string) => {
    try {
        await learningStore.verifyCertification(progressId);
    } catch (err) {
        console.error('Failed to verify certification:', err);
    }
};

const updateCharts = () => {
    updateCompletionChart();
    updateEngagementChart();
};

const updateCompletionChart = () => {
    if (!completionChartRef.value) return;

    const ctx = completionChartRef.value.getContext('2d');
    if (!ctx || typeof ctx === 'undefined') return;

    // Destroy existing chart
    if (completionChart) {
        completionChart.destroy();
    }

    const data = {
        labels: learningStats.completionByCourse.map(c => c.courseName),
        datasets: [{
            label: 'Completion Rate (%)',
            data: learningStats.completionByCourse.map(c => c.completionRate),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
        }],
    };

    completionChart = new Chart(ctx, {
        type: 'bar',
        data,
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
                    display: false,
                },
            },
        },
    });
};

const updateEngagementChart = () => {
    if (!engagementChartRef.value) return;

    const ctx = engagementChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (engagementChart) {
        engagementChart.destroy();
    }

    // Mock engagement trend data - in real app this would come from API
    const trendData = [
        { date: '2024-01-01', score: 75 },
        { date: '2024-01-08', score: 78 },
        { date: '2024-01-15', score: 82 },
        { date: '2024-01-22', score: 85 },
    ];

    const data = {
        labels: trendData.map(d => new Date(d.date).toLocaleDateString()),
        datasets: [{
            label: 'Avg Engagement Score',
            data: trendData.map(d => d.score),
            borderColor: 'rgba(139, 92, 246, 1)',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            tension: 0.4,
            fill: true,
        }],
    };

    engagementChart = new Chart(ctx, {
        type: 'line',
        data,
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
                    display: false,
                },
            },
        },
    });
};

const updateModalCharts = () => {
    if (!selectedProgress.value) return;

    updateModuleChart();
    updateScoreChart();
};

const updateModuleChart = () => {
    if (!moduleChartRef.value || !selectedProgress.value) return;

    const ctx = moduleChartRef.value.getContext('2d');
    if (!ctx) return;

    if (moduleChart) moduleChart.destroy();

    const progress = selectedProgress.value;
    const totalModules = getCourseModules(progress.course_id);

    const data = {
        labels: ['Completed', 'Remaining'],
        datasets: [{
            data: [progress.modules_completed, totalModules - progress.modules_completed],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',
                'rgba(156, 163, 175, 0.8)',
            ],
            borderColor: [
                'rgba(34, 197, 94, 1)',
                'rgba(156, 163, 175, 1)',
            ],
            borderWidth: 1,
        }],
    };

    moduleChart = new Chart(ctx, {
        type: 'pie',
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom' as const,
                },
            },
        },
    });
};

const updateScoreChart = () => {
    if (!scoreChartRef.value || !selectedProgress.value) return;

    const ctx = scoreChartRef.value.getContext('2d');
    if (!ctx) return;

    if (scoreChart) scoreChart.destroy();

    // Mock score trend data - in real app this would come from API
    const scoreData = [65, 70, 75, 80, 85, selectedProgress.value.engagement_score || 0];

    const data = {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Current'],
        datasets: [{
            label: 'Engagement Score',
            data: scoreData,
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
        }],
    };

    scoreChart = new Chart(ctx, {
        type: 'line',
        data,
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
                    display: false,
                },
            },
        },
    });
};

// Watchers
watch(() => filteredProgress, () => {
    nextTick(() => updateCharts());
}, { deep: true });

// Lifecycle
onMounted(async () => {
    // Initialize filters from props
    if (props.filters?.dateRange) {
        dateFrom.value = props.filters.dateRange.from;
        dateTo.value = props.filters.dateRange.to;
    }
    if (props.filters?.courseId) {
        selectedCourseId.value = props.filters.courseId;
    }
    if (props.filters?.userId) {
        selectedUserId.value = props.filters.userId;
    }

    // Update filters and fetch data
    learningStore.updateFilters({
        dateRange: props.filters?.dateRange,
        courseId: props.filters?.courseId,
        userId: props.filters?.userId,
    });

    await refreshData();
});
</script>

<style scoped>
.learning-dashboard {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.learning-dashboard button:focus,
.learning-dashboard input:focus,
.learning-dashboard select:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Modal animations */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .learning-dashboard-container {
        @apply px-2;
    }

    .grid-cols-1.md\\:grid-cols-5 {
        @apply grid-cols-1;
    }
}
</style>