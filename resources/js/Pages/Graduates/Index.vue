<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import GraduateActions from '@/Pages/Graduates/Partials/GraduateActions.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    graduates: Object,
    courses: Array,
    graduationYears: Array,
    employmentStatuses: Array,
    academicStandings: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const employmentStatus = ref(props.filters.employment_status || '');
const graduationYear = ref(props.filters.graduation_year || '');
const graduationYearRange = ref(props.filters.graduation_year_range || '');
const courseId = ref(props.filters.course_id || '');
const skills = ref(props.filters.skills || '');
const gpaMin = ref(props.filters.gpa_min || '');
const gpaMax = ref(props.filters.gpa_max || '');
const academicStanding = ref(props.filters.academic_standing || '');
const jobSearchActive = ref(props.filters.job_search_active || '');
const profileCompletionMin = ref(props.filters.profile_completion_min || '');
const hasCertifications = ref(props.filters.has_certifications || '');
const sortBy = ref(props.filters.sort_by || 'name');
const sortOrder = ref(props.filters.sort_order || 'asc');

const showAdvancedFilters = ref(false);

const applyFilters = () => {
    router.get(route('graduates.index'), {
        search: search.value,
        employment_status: employmentStatus.value,
        graduation_year: graduationYear.value,
        graduation_year_range: graduationYearRange.value,
        course_id: courseId.value,
        skills: skills.value,
        gpa_min: gpaMin.value,
        gpa_max: gpaMax.value,
        academic_standing: academicStanding.value,
        job_search_active: jobSearchActive.value,
        profile_completion_min: profileCompletionMin.value,
        has_certifications: hasCertifications.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    employmentStatus.value = '';
    graduationYear.value = '';
    graduationYearRange.value = '';
    courseId.value = '';
    skills.value = '';
    gpaMin.value = '';
    gpaMax.value = '';
    academicStanding.value = '';
    jobSearchActive.value = '';
    profileCompletionMin.value = '';
    hasCertifications.value = '';
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

const getEmploymentStatusBadge = (status) => {
    const badges = {
        'unemployed': 'bg-red-100 text-red-800',
        'employed': 'bg-green-100 text-green-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'further_studies': 'bg-purple-100 text-purple-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getProfileCompletionColor = (percentage) => {
    if (percentage >= 80) return 'text-green-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <Head title="Graduates" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Graduates Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Action Buttons -->
                <div class="flex justify-between mb-6">
                    <div class="flex gap-2">
                        <Link :href="route('graduates.create')" 
                              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Add Graduate
                        </Link>
                        <Link :href="route('graduates.import.create')" 
                              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                            Import Graduates
                        </Link>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Search & Filter Graduates</h3>
                        <button @click="showAdvancedFilters = !showAdvancedFilters" 
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            {{ showAdvancedFilters ? 'Hide Advanced' : 'Show Advanced' }}
                        </button>
                    </div>
                    
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" placeholder="Name, email, job title, company..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="employment_status" class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                            <select id="employment_status" v-model="employmentStatus"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option v-for="status in employmentStatuses" :key="status" :value="status">
                                    {{ status.replace('_', ' ').toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="graduation_year" class="block text-sm font-medium text-gray-700 mb-1">Graduation Year</label>
                            <select id="graduation_year" v-model="graduationYear"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Years</option>
                                <option v-for="year in graduationYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                            <select id="course_id" v-model="courseId"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Courses</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                            <input id="skills" type="text" v-model="skills" placeholder="e.g., JavaScript, Python"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="job_search_active" class="block text-sm font-medium text-gray-700 mb-1">Job Search Status</label>
                            <select id="job_search_active" v-model="jobSearchActive"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="true">Actively Looking</option>
                                <option value="false">Not Looking</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-800 mb-3">Advanced Filters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="graduation_year_range" class="block text-sm font-medium text-gray-700 mb-1">Graduation Year Range</label>
                                <input id="graduation_year_range" type="text" v-model="graduationYearRange" placeholder="e.g., 2020-2023"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="gpa_min" class="block text-sm font-medium text-gray-700 mb-1">Min GPA</label>
                                <input id="gpa_min" type="number" step="0.1" min="0" max="4" v-model="gpaMin" placeholder="0.0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="gpa_max" class="block text-sm font-medium text-gray-700 mb-1">Max GPA</label>
                                <input id="gpa_max" type="number" step="0.1" min="0" max="4" v-model="gpaMax" placeholder="4.0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="academic_standing" class="block text-sm font-medium text-gray-700 mb-1">Academic Standing</label>
                                <select id="academic_standing" v-model="academicStanding"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Standings</option>
                                    <option v-for="standing in academicStandings" :key="standing" :value="standing">
                                        {{ standing.replace('_', ' ').toUpperCase() }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="profile_completion_min" class="block text-sm font-medium text-gray-700 mb-1">Min Profile Completion (%)</label>
                                <input id="profile_completion_min" type="number" min="0" max="100" v-model="profileCompletionMin" placeholder="0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            
                            <div>
                                <label for="has_certifications" class="block text-sm font-medium text-gray-700 mb-1">Has Certifications</label>
                                <select id="has_certifications" v-model="hasCertifications"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="true">Has Certifications</option>
                                    <option value="false">No Certifications</option>
                                </select>
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

                <!-- Graduates List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('name')" class="flex items-center hover:text-gray-700">
                                            Graduate
                                            <svg v-if="sortBy === 'name'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('graduation_year')" class="flex items-center hover:text-gray-700">
                                            Course & Year
                                            <svg v-if="sortBy === 'graduation_year'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('employment_status')" class="flex items-center hover:text-gray-700">
                                            Employment Status
                                            <svg v-if="sortBy === 'employment_status'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('profile_completion_percentage')" class="flex items-center hover:text-gray-700">
                                            Profile Completion
                                            <svg v-if="sortBy === 'profile_completion_percentage'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
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
                                <tr v-for="graduate in graduates.data" :key="graduate.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ graduate.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ graduate.email }}
                                                </div>
                                                <div v-if="graduate.student_id" class="text-xs text-gray-400">
                                                    ID: {{ graduate.student_id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ graduate.course?.name || 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ graduate.graduation_year }}</div>
                                        <div v-if="graduate.gpa" class="text-xs text-gray-400">
                                            GPA: {{ graduate.gpa }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getEmploymentStatusBadge(graduate.employment_status)]">
                                            {{ graduate.employment_status?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                        <div v-if="graduate.current_job_title" class="text-xs text-gray-500 mt-1">
                                            {{ graduate.current_job_title }}
                                        </div>
                                        <div v-if="graduate.current_company" class="text-xs text-gray-400">
                                            {{ graduate.current_company }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-indigo-600 h-2 rounded-full" 
                                                     :style="`width: ${graduate.profile_completion_percentage || 0}%`"></div>
                                            </div>
                                            <span :class="['text-sm font-medium', getProfileCompletionColor(graduate.profile_completion_percentage || 0)]">
                                                {{ Math.round(graduate.profile_completion_percentage || 0) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="skill in (graduate.skills || []).slice(0, 3)" :key="skill"
                                                  class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                {{ skill }}
                                            </span>
                                            <span v-if="(graduate.skills || []).length > 3" 
                                                  class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                                +{{ (graduate.skills || []).length - 3 }} more
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <GraduateActions :graduate="graduate" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="graduates.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="graduates.prev_page_url" :href="graduates.prev_page_url" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </Link>
                                <Link v-if="graduates.next_page_url" :href="graduates.next_page_url" 
                                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ graduates.from }} to {{ graduates.to }} of {{ graduates.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link v-for="link in graduates.links" :key="link.label" 
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
                <div v-if="graduates.data.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No graduates found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search filters.' : 'Get started by adding your first graduate.' }}
                        </p>
                        <Link v-if="!Object.values(filters).some(f => f)" :href="route('graduates.create')" 
                              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Add First Graduate
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
