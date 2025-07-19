<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    analytics: Object,
});

const getLevelBadge = (level) => {
    const badges = {
        'certificate': 'bg-green-100 text-green-800',
        'diploma': 'bg-blue-100 text-blue-800',
        'advanced_diploma': 'bg-purple-100 text-purple-800',
        'degree': 'bg-indigo-100 text-indigo-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return badges[level] || 'bg-gray-100 text-gray-800';
};

const getStudyModeBadge = (mode) => {
    const badges = {
        'full_time': 'bg-blue-100 text-blue-800',
        'part_time': 'bg-yellow-100 text-yellow-800',
        'online': 'bg-green-100 text-green-800',
        'hybrid': 'bg-purple-100 text-purple-800',
    };
    return badges[mode] || 'bg-gray-100 text-gray-800';
};

const formatDuration = (months) => {
    if (months >= 12) {
        const years = Math.floor(months / 12);
        const remainingMonths = months % 12;
        return remainingMonths > 0 ? `${years}y ${remainingMonths}m` : `${years} year${years > 1 ? 's' : ''}`;
    }
    return `${months} month${months > 1 ? 's' : ''}`;
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Head :title="`${course.name} - Course Details`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Course: {{ course.name }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('courses.analytics', course.id)" 
                          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                        View Analytics
                    </Link>
                    <Link :href="route('courses.edit', course.id)" 
                          class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                        Edit Course
                    </Link>
                    <Link :href="route('courses.index')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to Courses
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Course Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ course.name }}</h3>
                                <p class="text-lg text-gray-600">{{ course.code }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getLevelBadge(course.level)]">
                                        {{ course.level?.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStudyModeBadge(course.study_mode)]">
                                        {{ course.study_mode?.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span v-if="course.is_featured" 
                                          class="inline-flex px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                        Featured
                                    </span>
                                    <span v-if="!course.is_active" 
                                          class="inline-flex px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                        Inactive
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Duration</div>
                                <div class="text-lg font-semibold text-gray-900">{{ formatDuration(course.duration_months) }}</div>
                                <div v-if="course.department" class="text-sm text-gray-600 mt-1">{{ course.department }}</div>
                            </div>
                        </div>
                        
                        <div v-if="course.description" class="mb-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Description</h4>
                            <p class="text-gray-700">{{ course.description }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Course Details -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Prerequisites -->
                        <div v-if="course.prerequisites && course.prerequisites.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Prerequisites</h3>
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="prerequisite in course.prerequisites" :key="prerequisite" class="text-gray-700">
                                        {{ prerequisite }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Required Skills -->
                        <div v-if="course.required_skills && course.required_skills.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Required Skills</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="skill in course.required_skills" :key="skill"
                                          class="inline-flex px-3 py-1 text-sm bg-red-100 text-red-800 rounded-full">
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Skills Gained -->
                        <div v-if="course.skills_gained && course.skills_gained.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Skills You'll Gain</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="skill in course.skills_gained" :key="skill"
                                          class="inline-flex px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full">
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Career Paths -->
                        <div v-if="course.career_paths && course.career_paths.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Career Paths</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="path in course.career_paths" :key="path"
                                          class="inline-flex px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                                        {{ path }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Learning Outcomes -->
                        <div v-if="course.learning_outcomes && course.learning_outcomes.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Learning Outcomes</h3>
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="outcome in course.learning_outcomes" :key="outcome" class="text-gray-700">
                                        {{ outcome }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Recent Graduates -->
                        <div v-if="analytics.recent_graduates && analytics.recent_graduates.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Graduates</h3>
                                <div class="space-y-3">
                                    <div v-for="graduate in analytics.recent_graduates" :key="graduate.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ graduate.name }}</div>
                                            <div class="text-sm text-gray-600">Graduated: {{ graduate.graduation_year }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-900">{{ graduate.employment_status?.replace('_', ' ').toUpperCase() }}</div>
                                            <div v-if="graduate.current_job_title" class="text-xs text-gray-600">{{ graduate.current_job_title }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Matching Jobs -->
                        <div v-if="analytics.matching_jobs && analytics.matching_jobs.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Related Job Opportunities</h3>
                                <div class="space-y-3">
                                    <div v-for="job in analytics.matching_jobs" :key="job.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ job.title }}</div>
                                            <div class="text-sm text-gray-600">{{ job.employer?.company_name }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-900">{{ job.location }}</div>
                                            <div class="text-xs text-gray-600">{{ formatDate(job.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Sidebar -->
                    <div class="space-y-6">
                        
                        <!-- Quick Stats -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Course Statistics</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Enrolled</span>
                                        <span class="text-sm font-medium text-gray-900">{{ course.total_enrolled || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Graduated</span>
                                        <span class="text-sm font-medium text-gray-900">{{ course.total_graduated || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Completion Rate</span>
                                        <span class="text-sm font-medium text-gray-900">{{ Math.round(course.completion_rate || 0) }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Employment Rate</span>
                                        <span class="text-sm font-medium text-gray-900">{{ Math.round(course.employment_rate || 0) }}%</span>
                                    </div>
                                    <div v-if="course.average_salary" class="flex justify-between">
                                        <span class="text-sm text-gray-600">Average Salary</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatCurrency(course.average_salary) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Trends -->
                        <div v-if="analytics.employment_trends" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Trends</h3>
                                <div class="space-y-3">
                                    <div v-for="(trend, year) in analytics.employment_trends" :key="year" 
                                         class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ year }}</span>
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-indigo-600 h-2 rounded-full" 
                                                     :style="`width: ${trend.rate}%`"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ trend.rate }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Course Actions -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                                <div class="space-y-2">
                                    <Link :href="route('graduates.index', { course_id: course.id })" 
                                          class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        View Graduates
                                    </Link>
                                    <Link :href="route('jobs.index', { course_id: course.id })" 
                                          class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        View Related Jobs
                                    </Link>
                                    <Link :href="route('courses.analytics', course.id)" 
                                          class="block w-full text-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                        Detailed Analytics
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Course Metadata -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Course Information</h3>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm text-gray-600">Created</span>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(course.created_at) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Last Updated</span>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(course.updated_at) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Status</span>
                                        <p class="text-sm font-medium text-gray-900">{{ course.is_active ? 'Active' : 'Inactive' }}</p>
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