<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    courses: Object,
    levels: Array,
    studyModes: Array,
    departments: Array,
    filters: Object,
    error: String,
});

const search = ref(props.filters.search || '');
const level = ref(props.filters.level || '');
const studyMode = ref(props.filters.study_mode || '');
const department = ref(props.filters.department || '');
const isActive = ref(props.filters.is_active || '');
const isFeatured = ref(props.filters.is_featured || '');
const employmentRateMin = ref(props.filters.employment_rate_min || '');
const durationMin = ref(props.filters.duration_min || '');
const durationMax = ref(props.filters.duration_max || '');
const skills = ref(props.filters.skills || '');
const sortBy = ref(props.filters.sort_by || 'name');
const sortOrder = ref(props.filters.sort_order || 'asc');

const showAdvancedFilters = ref(false);

const applyFilters = () => {
    router.get(route('courses.index'), {
        search: search.value,
        level: level.value,
        study_mode: studyMode.value,
        department: department.value,
        is_active: isActive.value,
        is_featured: isFeatured.value,
        employment_rate_min: employmentRateMin.value,
        duration_min: durationMin.value,
        duration_max: durationMax.value,
        skills: skills.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    level.value = '';
    studyMode.value = '';
    department.value = '';
    isActive.value = '';
    isFeatured.value = '';
    employmentRateMin.value = '';
    durationMin.value = '';
    durationMax.value = '';
    skills.value = '';
    sortBy.value = 'name';
    sortOrder.value = 'asc';
    applyFilters();
};

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
    applyFilters();
};

const exportCourses = () => {
    const params = new URLSearchParams();
    
    if (search.value) params.append('search', search.value);
    if (level.value) params.append('level', level.value);
    if (studyMode.value) params.append('study_mode', studyMode.value);
    if (department.value) params.append('department', department.value);
    if (isActive.value) params.append('is_active', isActive.value);
    if (isFeatured.value) params.append('is_featured', isFeatured.value);
    if (employmentRateMin.value) params.append('employment_rate_min', employmentRateMin.value);
    if (durationMin.value) params.append('duration_min', durationMin.value);
    if (durationMax.value) params.append('duration_max', durationMax.value);
    if (skills.value) params.append('skills', skills.value);
    
    params.append('sort_by', sortBy.value);
    params.append('sort_order', sortOrder.value);
    params.append('format', 'csv');
    
    window.open(route('courses.export') + '?' + params.toString());
};

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

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const formatDuration = (months) => {
    if (months >= 12) {
        const years = Math.floor(months / 12);
        const remainingMonths = months % 12;
        return remainingMonths > 0 ? `${years}y ${remainingMonths}m` : `${years} year${years > 1 ? 's' : ''}`;
    }
    return `${months} month${months > 1 ? 's' : ''}`;
};

const updateStatistics = (course) => {
    router.post(route('courses.statistics.update', course.id), {}, {
        preserveState: true,
        onSuccess: () => {
            // Refresh the page to show updated statistics
            router.reload({ only: ['courses'] });
        }
    });
};
</script>

<template>
    <Head title="Courses Management" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Courses Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Action Buttons -->
                <div class="flex justify-between mb-6">
                    <div class="flex gap-2">
                        <Link :href="route('courses.create')" 
                              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Add Course
                        </Link>
                        <Link :href="route('courses.import.create')" 
                              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                            Import Courses
                        </Link>
                        <button @click="exportCourses" 
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                            Export Courses
                        </button>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Search & Filter Courses</h3>
                        <button @click="showAdvancedFilters = !showAdvancedFilters" 
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            {{ showAdvancedFilters ? 'Hide Advanced' : 'Show Advanced' }}
                        </button>
                    </div>
                    
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" placeholder="Name, code, description..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="level" class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                            <select id="level" v-model="level"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Levels</option>
                                <option v-for="levelOption in levels" :key="levelOption" :value="levelOption">
                                    {{ levelOption.replace('_', ' ').toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="study_mode" class="block text-sm font-medium text-gray-700 mb-1">Study Mode</label>
                            <select id="study_mode" v-model="studyMode"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Modes</option>
                                <option v-for="mode in studyModes" :key="mode" :value="mode">
                                    {{ mode.replace('_', ' ').toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select id="department" v-model="department"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Departments</option>
                                <option v-for="dept in departments" :key="dept" :value="dept">
                                    {{ dept }}
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-800 mb-3">Advanced Filters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="is_active" v-model="isActive"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="is_featured" class="block text-sm font-medium text-gray-700 mb-1">Featured</label>
                                <select id="is_featured" v-model="isFeatured"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="true">Featured</option>
                                    <option value="false">Not Featured</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="employment_rate_min" class="block text-sm font-medium text-gray-700 mb-1">Min Employment Rate (%)</label>
                                <input id="employment_rate_min" type="number" min="0" max="100" v-model="employmentRateMin" placeholder="0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                                <input id="skills" type="text" v-model="skills" placeholder="e.g., JavaScript, Python"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="duration_min" class="block text-sm font-medium text-gray-700 mb-1">Min Duration (months)</label>
                                <input id="duration_min" type="number" min="1" v-model="durationMin" placeholder="1"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="duration_max" class="block text-sm font-medium text-gray-700 mb-1">Max Duration (months)</label>
                                <input id="duration_max" type="number" min="1" v-model="durationMax" placeholder="120"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Apply Filters
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Courses List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('name')" class="flex items-center hover:text-gray-700">
                                            Course
                                            <svg v-if="sortBy === 'name'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Level & Duration
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('total_graduated')" class="flex items-center hover:text-gray-700">
                                            Statistics
                                            <svg v-if="sortBy === 'total_graduated'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('employment_rate')" class="flex items-center hover:text-gray-700">
                                            Employment Rate
                                            <svg v-if="sortBy === 'employment_rate'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Skills
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="course in courses.data" :key="course.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ course.name }}
                                                    </div>
                                                    <span v-if="course.is_featured" 
                                                          class="ml-2 inline-flex px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">
                                                        Featured
                                                    </span>
                                                    <span v-if="!course.is_active" 
                                                          class="ml-2 inline-flex px-2 py-1 text-xs bg-red-100 text-red-800 rounded">
                                                        Inactive
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ course.code }}
                                                </div>
                                                <div v-if="course.department" class="text-xs text-gray-400">
                                                    {{ course.department }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full mb-1', getLevelBadge(course.level)]">
                                            {{ course.level?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                        <div class="text-sm text-gray-900">{{ formatDuration(course.duration_months) }}</div>
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStudyModeBadge(course.study_mode)]">
                                            {{ course.study_mode?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Graduates:</span>
                                                <span class="font-medium">{{ course.total_graduated || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Enrolled:</span>
                                                <span class="font-medium">{{ course.total_enrolled || 0 }}</span>
                                            </div>
                                            <div v-if="course.average_salary" class="flex justify-between">
                                                <span class="text-gray-600">Avg Salary:</span>
                                                <span class="font-medium">${{ Math.round(course.average_salary).toLocaleString() }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div :class="[
                                                    'h-2 rounded-full',
                                                    course.employment_rate >= 80 ? 'bg-green-500' : 
                                                    course.employment_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500'
                                                ]" 
                                                     :style="`width: ${course.employment_rate || 0}%`"></div>
                                            </div>
                                            <span :class="['text-sm font-medium', getEmploymentRateColor(course.employment_rate || 0)]">
                                                {{ Math.round(course.employment_rate || 0) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="skill in (course.skills_gained || []).slice(0, 3)" :key="skill"
                                                  class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                {{ skill }}
                                            </span>
                                            <span v-if="(course.skills_gained || []).length > 3" 
                                                  class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                                +{{ (course.skills_gained || []).length - 3 }} more
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            <Link :href="route('courses.show', course.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            <Link :href="route('courses.analytics', course.id)" 
                                                  class="text-blue-600 hover:text-blue-900">
                                                Analytics
                                            </Link>
                                            <Link :href="route('courses.edit', course.id)" 
                                                  class="text-green-600 hover:text-green-900">
                                                Edit
                                            </Link>
                                            <button @click="updateStatistics(course)" 
                                                    class="text-purple-600 hover:text-purple-900">
                                                Update Stats
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="courses.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="courses.prev_page_url" :href="courses.prev_page_url" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </Link>
                                <Link v-if="courses.next_page_url" :href="courses.next_page_url" 
                                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ courses.from }} to {{ courses.to }} of {{ courses.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link v-for="link in courses.links" :key="link.label" 
                                              :href="link.url" 
                                              :class="[
                                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                  link.active 
                                                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                              ]"
                                              v-html="link.label">
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="courses.data.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No courses found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search filters.' : 'Get started by adding your first course.' }}
                        </p>
                        <Link v-if="!Object.values(filters).some(f => f)" :href="route('courses.create')" 
                              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Add First Course
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
